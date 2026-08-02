<?php
require_once __DIR__ . '/config.php';
session_init();

if (!isset($_SESSION['teacher_id'])) {
    header('Location: teacher-login.php'); exit;
}

// ── Session timeout ───────────────────────────────────────────
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset(); session_destroy();
    header('Location: teacher-login.php?timeout=1'); exit;
}
$_SESSION['last_activity'] = time();

// ── Session fingerprint (Issue #6) ────────────────────────────
$_fp = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? ''));
if (isset($_SESSION['teacher_fp']) && !hash_equals($_SESSION['teacher_fp'], $_fp)) {
    session_unset(); session_destroy();
    header('Location: teacher-login.php?hijack=1'); exit;
}
$_SESSION['teacher_fp'] = $_fp;

$teacher_id   = (int)$_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$teacher_user = $_SESSION['teacher_username'] ?? '';

$conn = get_db();

// ── AJAX: send chat message ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'send_msg') {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        $body          = trim($_POST['body'] ?? '');
        $msg_type      = trim($_POST['msg_type'] ?? 'text');
        $attach_url    = trim($_POST['attachment_url'] ?? '') ?: null;

        // ── Issue #8: message length limit ───────────────────
        if (mb_strlen($body) > 5000) {
            echo json_encode(['success'=>false,'message'=>'Message too long (max 5000 characters).']); exit;
        }

        // ── Issue #12: URL must be http/https ─────────────────
        if ($attach_url && !preg_match('/^https?:\/\//i', $attach_url)) {
            echo json_encode(['success'=>false,'message'=>'URL must start with http:// or https://']); exit;
        }
        if ($attach_url && mb_strlen($attach_url) > 500) {
            echo json_encode(['success'=>false,'message'=>'URL too long (max 500 chars).']); exit;
        }

        // Verify this assignment belongs to this teacher
        $chk = $conn->prepare("SELECT id FROM teacher_assignments WHERE id=? AND teacher_id=?");
        $chk->bind_param('ii', $assignment_id, $teacher_id);
        $chk->execute();
        if (!$chk->get_result()->num_rows) {
            echo json_encode(['success'=>false,'message'=>'Unauthorized.']); $conn->close(); exit;
        }
        $chk->close();

        if (!$body && !$attach_url) {
            echo json_encode(['success'=>false,'message'=>'Message cannot be empty.']); $conn->close(); exit;
        }

        $allowed = ['text','link','video','file','question'];
        if (!in_array($msg_type, $allowed)) $msg_type = 'text';

        $stmt = $conn->prepare(
            "INSERT INTO chat_messages (assignment_id,sender_type,sender_id,msg_type,body,attachment_url)
             VALUES (?,?,?,?,?,?)"
        );
        $stype = 'teacher';
        $stmt->bind_param('iissss', $assignment_id, $stype, $teacher_id, $msg_type, $body, $attach_url);
        $ok = $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();
        $conn->close();
        echo json_encode(['success'=>$ok,'id'=>$new_id]);
        exit;
    }

    if ($_POST['action'] === 'get_msgs') {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        $after_id      = (int)($_POST['after_id'] ?? 0);

        // ── Issue #3: Verify assignment belongs to THIS teacher ──
        $auth = $conn->prepare("SELECT id FROM teacher_assignments WHERE id=? AND teacher_id=?");
        $auth->bind_param('ii', $assignment_id, $teacher_id);
        $auth->execute();
        if (!$auth->get_result()->num_rows) {
            echo json_encode(['success'=>false,'message'=>'Unauthorized.']); $conn->close(); exit;
        }
        $auth->close();

        // Mark student messages as read — using prepared statement (Issue #15)
        $upd = $conn->prepare(
            "UPDATE chat_messages SET is_read=1
             WHERE assignment_id=? AND sender_type='student' AND is_read=0"
        );
        $upd->bind_param('i', $assignment_id);
        $upd->execute();
        $upd->close();

        // ── Issue #14: LIMIT 100 to avoid loading thousands of messages ──
        $q = $conn->prepare(
            "SELECT cm.id, cm.sender_type, cm.msg_type, cm.body, cm.attachment_url,
                    cm.is_read, cm.created_at,
                    CASE cm.sender_type
                        WHEN 'teacher' THEN tu.full_name
                        ELSE e.full_name
                    END as sender_name
             FROM chat_messages cm
             LEFT JOIN teacher_assignments ta ON ta.id = cm.assignment_id
             LEFT JOIN teacher_users tu ON tu.id = ta.teacher_id
             LEFT JOIN enroll e ON e.id = ta.enrollment_id
             WHERE cm.assignment_id=? AND cm.id>?
             ORDER BY cm.created_at ASC
             LIMIT 100"
        );
        $q->bind_param('ii', $assignment_id, $after_id);
        $q->execute();
        $result = $q->get_result();
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        $q->close();
        $conn->close();
        echo json_encode(['success'=>true,'messages'=>$rows]);
        exit;
    }
    exit;
}

// ── Load teacher's students (assignments) ─────────────────────
$students = $conn->query(
    "SELECT ta.id as assignment_id, ta.enrollment_id,
            e.full_name as student_name, e.phone, e.email,
            e.course_interest, e.study_mode,
            IFNULL(NULLIF(e.enrollment_status,''),'pending') as enrollment_status,
            (SELECT COUNT(*) FROM chat_messages cm WHERE cm.assignment_id=ta.id AND cm.sender_type='student' AND cm.is_read=0) as unread_count
     FROM teacher_assignments ta
     JOIN enroll e ON e.id = ta.enrollment_id
     WHERE ta.teacher_id = $teacher_id
     ORDER BY e.full_name"
);

// Teacher info
$tinfo = $conn->query("SELECT * FROM teacher_users WHERE id=$teacher_id")->fetch_assoc();
$conn->close();

$initials = strtoupper(implode('', array_map(fn($w)=>$w[0]??'', array_slice(explode(' ', trim($teacher_name)), 0, 2))));
$stColor = ['pending'=>'#f59e0b','approved'=>'#10b981','rejected'=>'#ef4444','in_progress'=>'#0094d9','completed'=>'#8b5cf6'];
$stBg    = ['pending'=>'rgba(245,158,11,.12)','approved'=>'rgba(16,185,129,.12)','rejected'=>'rgba(239,68,68,.12)','in_progress'=>'rgba(0,148,217,.12)','completed'=>'rgba(139,92,246,.12)'];
$stLabel = ['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','in_progress'=>'In Progress','completed'=>'Completed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher Portal — Programmers Lab</title>
<meta name="robots" content="noindex,nofollow">
<link href="img/favicon-16x16.png" rel="shortcut icon"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;}
body{margin:0;font-family:'Inter',sans-serif;background:#f0f2f5;color:#1a1a2e;}
#sidebar{position:fixed;top:0;left:0;bottom:0;width:260px;background:#0d1b2a;display:flex;flex-direction:column;z-index:1000;transition:transform .3s ease;}
.sb-brand{padding:20px 18px 16px;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:10px;}
.sb-brand-icon{width:36px;height:36px;border-radius:10px;background:#f07b14;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;flex-shrink:0;}
.sb-brand-name{font-size:14px;font-weight:700;color:#fff;} .sb-brand-name span{color:#f07b14;}
.sb-brand-sub{font-size:11px;color:rgba(255,255,255,.3);}
.sb-user{margin:12px 12px;padding:14px;background:rgba(255,255,255,.05);border-radius:12px;display:flex;align-items:center;gap:10px;}
.sb-user-av{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#f07b14,#d96a00);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:900;color:#fff;flex-shrink:0;}
.sb-user-name{font-size:13.5px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sb-user-role{font-size:11px;color:rgba(255,255,255,.35);}
.sb-nav{padding:6px 12px;flex:1;overflow-y:auto;}
.sb-nav a{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;color:rgba(255,255,255,.55);font-size:13.5px;font-weight:500;text-decoration:none;margin-bottom:2px;transition:all .2s;}
.sb-nav a:hover{background:rgba(255,255,255,.06);color:#fff;}
.sb-nav a.active{background:#f07b14;color:#fff;font-weight:600;}
.sb-nav a i{width:18px;text-align:center;font-size:13px;color:rgba(255,255,255,.3);}
.sb-nav a.active i,.sb-nav a:hover i{color:inherit;}
.sb-footer{padding:14px 12px;border-top:1px solid rgba(255,255,255,.07);}
.sb-footer a{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;color:rgba(255,255,255,.4);font-size:13px;text-decoration:none;transition:all .2s;}
.sb-footer a:hover{background:rgba(239,68,68,.12);color:#ef4444;}
#topbar{position:fixed;top:0;left:260px;right:0;height:64px;background:#fff;border-bottom:1px solid #e8ecf0;display:flex;align-items:center;justify-content:space-between;padding:0 26px;z-index:999;box-shadow:0 1px 4px rgba(0,0,0,.04);}
.tb-hamburger{display:none;background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:#6b7280;font-size:18px;}
.tb-title{font-size:17px;font-weight:800;color:#0d1b2a;}
.tb-right{display:flex;align-items:center;gap:14px;}
.tb-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#f07b14,#d96a00);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:800;}
#main{margin-left:260px;padding:84px 24px 48px;min-height:100vh;}
/* Chat UI */
.chat-layout{display:grid;grid-template-columns:280px 1fr;gap:16px;height:calc(100vh - 140px);}
.chat-sidebar{background:#fff;border-radius:16px;border:1px solid #eef0f4;overflow-y:auto;display:flex;flex-direction:column;}
.chat-sidebar-header{padding:16px 18px;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#0d1b2a;}
.chat-student-item{padding:14px 16px;border-bottom:1px solid #f8f9fb;cursor:pointer;transition:background .15s;display:flex;align-items:center;gap:11px;}
.chat-student-item:hover{background:#f8fafc;}
.chat-student-item.active{background:#fff7ed;border-left:3px solid #f07b14;}
.student-av{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0;}
.chat-main{background:#fff;border-radius:16px;border:1px solid #eef0f4;display:flex;flex-direction:column;}
.chat-header{padding:16px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;}
.chat-messages{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:12px;background:#f8fafc;}
.msg-bubble{max-width:70%;padding:12px 16px;border-radius:16px;font-size:13.5px;line-height:1.55;word-break:break-word;}
.msg-bubble.sent{background:#f07b14;color:#fff;border-bottom-right-radius:4px;margin-left:auto;}
.msg-bubble.recv{background:#fff;color:#0d1b2a;border-bottom-left-radius:4px;border:1px solid #e5e7eb;}
.msg-meta{font-size:11px;opacity:.65;margin-top:5px;}
.msg-type-tag{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;margin-bottom:5px;}
.chat-input-area{padding:14px 16px;border-top:1px solid #f3f4f6;display:flex;flex-direction:column;gap:10px;}
.chat-type-btns{display:flex;gap:6px;flex-wrap:wrap;}
.type-btn{padding:5px 12px;border:1.5px solid #e5e7eb;border-radius:20px;font-size:11.5px;font-weight:700;cursor:pointer;background:#fff;color:#6b7280;transition:all .15s;}
.type-btn.active,.type-btn:hover{background:#f07b14;color:#fff;border-color:#f07b14;}
.chat-input-row{display:flex;gap:8px;}
.chat-input-row textarea{flex:1;padding:11px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font-family:inherit;font-size:13.5px;resize:none;outline:none;transition:border .2s;}
.chat-input-row textarea:focus{border-color:#f07b14;}
.chat-send-btn{padding:0 20px;background:#f07b14;color:#fff;border:none;border-radius:12px;font-size:15px;cursor:pointer;transition:background .2s;}
.chat-send-btn:hover{background:#d96a00;}
.no-student{flex:1;display:flex;align-items:center;justify-content:center;color:#9ca3af;text-align:center;padding:40px;}
.unread-dot{width:8px;height:8px;background:#ef4444;border-radius:50%;flex-shrink:0;}
@media(max-width:900px){
    .chat-layout{grid-template-columns:1fr;height:auto;}
    .chat-sidebar{height:200px;}
    .chat-main{height:calc(100vh - 400px);}
}
@media(max-width:768px){
    #sidebar{transform:translateX(-100%);}#sidebar.open{transform:translateX(0);}
    #topbar{left:0;}#main{margin-left:0;padding:76px 12px 36px;}
    .tb-hamburger{display:flex;}
}
#sb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;}
#sb-overlay.show{display:block;}
</style>
</head>
<body>
<div id="sb-overlay" onclick="closeSb()"></div>
<aside id="sidebar">
    <div class="sb-brand">
        <div class="sb-brand-icon"><i class="fas fa-code"></i></div>
        <div><div class="sb-brand-name"><span>Programmers</span> Lab</div><div class="sb-brand-sub">Teacher Portal</div></div>
    </div>
    <div class="sb-user">
        <div class="sb-user-av"><?= h($initials) ?></div>
        <div style="min-width:0;"><div class="sb-user-name"><?= h($teacher_name) ?></div><div class="sb-user-role">Teacher · @<?= h($teacher_user) ?></div></div>
    </div>
    <nav class="sb-nav">
        <a href="teacher-portal.php" class="active"><i class="fas fa-comments"></i> My Students</a>
        <a href="index.html"><i class="fas fa-home"></i> Back to Website</a>
    </nav>
    <div class="sb-footer"><a href="teacher-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</aside>
<header id="topbar">
    <div style="display:flex;align-items:center;gap:12px;">
        <button class="tb-hamburger" onclick="toggleSb()"><i class="fas fa-bars"></i></button>
        <div class="tb-title">Teacher Portal</div>
    </div>
    <div class="tb-right">
        <span style="font-size:13px;font-weight:600;color:#374151;"><?= h($teacher_name) ?></span>
        <div class="tb-avatar"><?= h($initials) ?></div>
    </div>
</header>
<main id="main">

<div class="chat-layout">
    <!-- Students List -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <i class="fas fa-users me-2" style="color:#f07b14;"></i>My Students
            <span style="float:right;background:#f3f4f6;color:#6b7280;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;"><?= $students->num_rows ?></span>
        </div>
        <?php
        $students_arr = [];
        while ($s = $students->fetch_assoc()) $students_arr[] = $s;
        if (count($students_arr) === 0):
        ?>
        <div style="padding:32px 16px;text-align:center;color:#9ca3af;">
            <i class="fas fa-users" style="font-size:32px;display:block;margin-bottom:12px;opacity:.3;"></i>
            No students assigned yet.<br>
            <a href="admin/teacher_assignments.php" style="color:#f07b14;font-size:13px;font-weight:600;">Ask admin to assign students</a>
        </div>
        <?php else: foreach ($students_arr as $idx => $s):
            $sinit = strtoupper(substr($s['student_name'],0,1));
            $st = $s['enrollment_status'];
        ?>
        <div class="chat-student-item <?= $idx===0?'active':'' ?>"
            onclick="selectStudent(<?= $s['assignment_id'] ?>, '<?= h(addslashes($s['student_name'])) ?>', '<?= h(addslashes($s['course_interest'])) ?>', <?= $idx ?>)"
            id="sitem-<?= $s['assignment_id'] ?>"
            data-assignment="<?= $s['assignment_id'] ?>">
            <div class="student-av"><?= $sinit ?></div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13.5px;font-weight:700;color:#0d1b2a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($s['student_name']) ?></div>
                <div style="font-size:11.5px;color:#9ca3af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($s['course_interest']) ?></div>
            </div>
            <?php if ($s['unread_count'] > 0): ?>
            <div style="background:#ef4444;color:#fff;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;" id="unread-<?= $s['assignment_id'] ?>">
                <?= min((int)$s['unread_count'],99) ?>
            </div>
            <?php else: ?>
            <div id="unread-<?= $s['assignment_id'] ?>" style="display:none;"></div>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Chat Window -->
    <div class="chat-main" id="chatMain">
        <?php if (count($students_arr) > 0):
            $first = $students_arr[0];
        ?>
        <div class="chat-header" id="chatHeader">
            <div>
                <div style="font-size:15px;font-weight:800;color:#0d1b2a;" id="chatStudentName"><?= h($first['student_name']) ?></div>
                <div style="font-size:12px;color:#9ca3af;" id="chatCourseInfo">
                    <i class="fas fa-graduation-cap me-1"></i><?= h($first['course_interest']) ?>
                    <span style="margin:0 6px;">·</span>
                    <span id="chatStatus" style="color:<?= $stColor[$first['enrollment_status']]??'#6b7280' ?>;font-weight:700;"><?= $stLabel[$first['enrollment_status']]??'' ?></span>
                </div>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div style="text-align:center;color:#9ca3af;font-size:13px;padding:20px;">Loading messages...</div>
        </div>
        <div class="chat-input-area">
            <div class="chat-type-btns">
                <button class="type-btn active" data-type="text"    onclick="setMsgType('text',this)"><i class="fas fa-comment me-1"></i>Text</button>
                <button class="type-btn"        data-type="link"    onclick="setMsgType('link',this)"><i class="fas fa-link me-1"></i>Link</button>
                <button class="type-btn"        data-type="video"   onclick="setMsgType('video',this)"><i class="fab fa-youtube me-1"></i>Video</button>
                <button class="type-btn"        data-type="question"onclick="setMsgType('question',this)"><i class="fas fa-question-circle me-1"></i>Question</button>
                <button class="type-btn"        data-type="file"    onclick="setMsgType('file',this)"><i class="fas fa-file me-1"></i>File/Doc</button>
            </div>
            <div id="attachRow" style="display:none;margin-bottom:4px;">
                <input type="text" id="attachUrl" class="form-control" placeholder="Paste URL here (video link, drive link, etc.)" style="font-size:13px;">
            </div>
            <div class="chat-input-row">
                <textarea id="msgBody" rows="2" placeholder="Type your message..."></textarea>
                <button class="chat-send-btn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
        <?php else: ?>
        <div class="no-student">
            <div>
                <i class="fas fa-comments" style="font-size:48px;opacity:.2;display:block;margin-bottom:16px;"></i>
                <div style="font-size:15px;font-weight:700;color:#374151;margin-bottom:6px;">No students assigned</div>
                <div style="font-size:13px;">Contact admin to assign students to you.</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentAssignment = <?= count($students_arr)>0 ? (int)$students_arr[0]['assignment_id'] : 0 ?>;
let lastMsgId = 0;
let currentMsgType = 'text';
let pollInterval = null;
let isFetching = false;         // Issue #9: prevent stacked requests
let fetchController = null;     // Issue #9: AbortController

const msgTypeIcons = {text:'fa-comment',link:'fa-link',video:'fa-play-circle',question:'fa-question-circle',file:'fa-file'};
const msgTypeColors = {text:'#6b7280',link:'#0094d9',video:'#ef4444',question:'#f07b14',file:'#8b5cf6'};

function setMsgType(type, btn) {
    currentMsgType = type;
    document.querySelectorAll('.type-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    const attach = document.getElementById('attachRow');
    if (type==='link'||type==='video'||type==='file') {
        attach.style.display='block';
        const ph = type==='video'?'Paste YouTube/video URL':type==='file'?'Paste Google Drive or file URL':'Paste link URL';
        document.getElementById('attachUrl').placeholder=ph;
    } else {
        attach.style.display='none';
    }
}

function selectStudent(assignmentId, name, course, idx) {
    currentAssignment = assignmentId;
    lastMsgId = 0;
    // Update sidebar active
    document.querySelectorAll('.chat-student-item').forEach(el=>el.classList.remove('active'));
    document.getElementById('sitem-'+assignmentId)?.classList.add('active');
    // Update header
    document.getElementById('chatStudentName').textContent = name;
    document.getElementById('chatCourseInfo').querySelector('i').nextSibling.textContent = course;
    // Clear chat
    document.getElementById('chatMessages').innerHTML = '<div style="text-align:center;color:#9ca3af;font-size:13px;padding:20px;">Loading...</div>';
    // Reset unread
    const unread = document.getElementById('unread-'+assignmentId);
    if (unread) unread.style.display='none';
    loadMessages();
}

function loadMessages(append=false) {
    if (!currentAssignment) return;
    if (isFetching && append) return; // Issue #9: skip poll if previous still in flight
    isFetching = true;

    // Abort any previous in-flight request
    if (fetchController) fetchController.abort();
    fetchController = new AbortController();

    const fd = new FormData();
    fd.append('action','get_msgs');
    fd.append('assignment_id', currentAssignment);
    fd.append('after_id', lastMsgId);
    fetch('teacher-portal.php', { method:'POST', body:fd, signal: fetchController.signal })
        .then(r=>r.json())
        .then(data=>{
            isFetching = false;
            if (!data.success) return;
            const container = document.getElementById('chatMessages');
            if (!append) container.innerHTML='';
            if (data.messages.length===0 && !append) {
                container.innerHTML='<div style="text-align:center;color:#9ca3af;font-size:13px;padding:40px;">No messages yet. Start the conversation!</div>';
                return;
            }
            data.messages.forEach(m=>{
                if (m.id>lastMsgId) lastMsgId=parseInt(m.id);
                const isSent = m.sender_type==='teacher';
                const time = new Date(m.created_at).toLocaleTimeString('en-PK',{hour:'2-digit',minute:'2-digit'});
                const icon = msgTypeIcons[m.msg_type]||'fa-comment';
                const col  = msgTypeColors[m.msg_type]||'#6b7280';
                let typTag = '';
                if (m.msg_type!=='text') {
                    typTag=`<div class="msg-type-tag" style="background:${col}18;color:${col};"><i class="fas ${icon}"></i>${m.msg_type.charAt(0).toUpperCase()+m.msg_type.slice(1)}</div>`;
                }
                let bodyHtml = esc(m.body);
                if (m.attachment_url) {
                    bodyHtml += `<div style="margin-top:7px;"><a href="${esc(m.attachment_url)}" target="_blank" rel="noopener noreferrer" style="color:${isSent?'rgba(255,255,255,.85)':'#0094d9'};font-size:12.5px;word-break:break-all;"><i class="fas fa-external-link-alt me-1"></i>${esc(m.attachment_url)}</a></div>`;
                }
                const div=document.createElement('div');
                div.style.display='flex';
                div.style.flexDirection='column';
                div.style.alignItems=isSent?'flex-end':'flex-start';
                div.innerHTML=`<div style="font-size:11px;color:#9ca3af;margin-bottom:3px;padding:0 4px;">${esc(m.sender_name||'')}</div>
                    <div class="msg-bubble ${isSent?'sent':'recv'}">${typTag}${bodyHtml}<div class="msg-meta">${time}</div></div>`;
                container.appendChild(div);
            });
            container.scrollTop=container.scrollHeight;
        })
        .catch(err => {
            isFetching = false;
            if (err.name !== 'AbortError') console.warn('Chat fetch error:', err);
        });
}

function sendMessage() {
    if (!currentAssignment) return;
    const body    = document.getElementById('msgBody').value.trim();
    const attach  = document.getElementById('attachUrl')?.value.trim()||'';
    if (!body && !attach) return;
    const fd=new FormData();
    fd.append('action','send_msg');
    fd.append('assignment_id',currentAssignment);
    fd.append('body',body);
    fd.append('msg_type',currentMsgType);
    fd.append('attachment_url',attach);
    fetch('teacher-portal.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
            if(data.success){
                document.getElementById('msgBody').value='';
                if(document.getElementById('attachUrl')) document.getElementById('attachUrl').value='';
                loadMessages(true);
            }
        });
}

function esc(str){ const d=document.createElement('div');d.appendChild(document.createTextNode(str||''));return d.innerHTML; }

// Keyboard send
document.getElementById('msgBody')?.addEventListener('keydown',function(e){
    if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMessage();}
});

// Polling for new messages — pause when tab hidden (Issue #9)
function startPolling(){
    if(pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(() => {
        if (!document.hidden && currentAssignment) loadMessages(true);
    }, 5000);
}

// Init
if (currentAssignment) { loadMessages(); startPolling(); }

// Clean up on unload
window.addEventListener('beforeunload', () => {
    if (pollInterval) clearInterval(pollInterval);
    if (fetchController) fetchController.abort();
});

function toggleSb(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sb-overlay').classList.toggle('show');}
function closeSb(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sb-overlay').classList.remove('show');}
</script>
</body>
</html>
