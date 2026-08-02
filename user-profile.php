<?php
require_once __DIR__ . '/config.php';
session_init();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?redirect=user-profile.php');
    exit;
}

$conn = get_db();
$uid  = (int)$_SESSION['user_id'];

// Get user info
$stmt = $conn->prepare("SELECT id, full_name, email, phone, created_at FROM site_users WHERE id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_unset(); session_destroy();
    header('Location: auth.php'); exit;
}

// Get enrollments — safe with/without user_id column
$enrollments = [];
$col_check   = $conn->query("SHOW COLUMNS FROM enroll LIKE 'user_id'");
$has_uid     = $col_check && $col_check->num_rows > 0;

if ($has_uid) {
    $er = $conn->prepare(
        "SELECT id, course_interest, study_mode, enrollment_status, created_at
         FROM enroll WHERE user_id = ? OR email = ? ORDER BY id DESC"
    );
    $er->bind_param('is', $uid, $user['email']);
} else {
    $er = $conn->prepare(
        "SELECT id, course_interest, study_mode, enrollment_status, created_at
         FROM enroll WHERE email = ? ORDER BY id DESC"
    );
    $er->bind_param('s', $user['email']);
}
$er->execute();
$res = $er->get_result();
while ($row = $res->fetch_assoc()) $enrollments[] = $row;
$er->close();

// ── AJAX: chat messages ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Student ka enrollment IDs list
    $enroll_ids = array_column($enrollments, 'id');

    if ($_POST['action'] === 'send_chat') {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        $body          = trim($_POST['body'] ?? '');
        $attach        = trim($_POST['attachment_url'] ?? '') ?: null;

        // ── Issue #8: message length limit ───────────────────
        if (mb_strlen($body) > 5000) {
            echo json_encode(['success'=>false,'message'=>'Message too long (max 5000 characters).']); $conn->close(); exit;
        }
        // ── Issue #12: URL validation ──────────────────────────
        if ($attach && !preg_match('/^https?:\/\//i', $attach)) {
            echo json_encode(['success'=>false,'message'=>'URL must start with http:// or https://']); $conn->close(); exit;
        }
        if ($attach && mb_strlen($attach) > 500) {
            echo json_encode(['success'=>false,'message'=>'URL too long.']); $conn->close(); exit;
        }

        if (!$body && !$attach) {
            echo json_encode(['success'=>false,'message'=>'Empty message.']); $conn->close(); exit;
        }

        // Verify assignment belongs to this student (by user_id OR email)
        $chk = $conn->prepare(
            "SELECT ta.id FROM teacher_assignments ta
             JOIN enroll e ON e.id=ta.enrollment_id
             WHERE ta.id=? AND (e.email=? OR e.user_id=?)"
        );
        $chk->bind_param('isi', $assignment_id, $user['email'], $uid);
        $chk->execute();
        if (!$chk->get_result()->num_rows) {
            echo json_encode(['success'=>false,'message'=>'Unauthorized.']); $conn->close(); exit;
        }
        $chk->close();

        $mtype = 'text';
        $stype = 'student';
        $stmt2 = $conn->prepare(
            "INSERT INTO chat_messages (assignment_id,sender_type,sender_id,msg_type,body,attachment_url)
             VALUES (?,?,?,?,?,?)"
        );
        $stmt2->bind_param('iissss', $assignment_id, $stype, $uid, $mtype, $body, $attach);
        $ok = $stmt2->execute();
        $new_id = $conn->insert_id;
        $stmt2->close();
        echo json_encode(['success'=>$ok,'id'=>$new_id]);
        $conn->close(); exit;
    }

    if ($_POST['action'] === 'get_chat') {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        $after_id      = (int)($_POST['after_id'] ?? 0);

        // ── Issue #2: Verify this assignment belongs to THIS student ──
        $auth = $conn->prepare(
            "SELECT ta.id FROM teacher_assignments ta
             JOIN enroll e ON e.id = ta.enrollment_id
             WHERE ta.id = ? AND (e.email = ? OR e.user_id = ?)"
        );
        $auth->bind_param('isi', $assignment_id, $user['email'], $uid);
        $auth->execute();
        if (!$auth->get_result()->num_rows) {
            echo json_encode(['success'=>false,'message'=>'Unauthorized.']); $conn->close(); exit;
        }
        $auth->close();

        // Mark teacher messages as read — prepared statement (Issue #4/#5)
        $upd = $conn->prepare(
            "UPDATE chat_messages SET is_read=1
             WHERE assignment_id=? AND sender_type='teacher' AND is_read=0"
        );
        $upd->bind_param('i', $assignment_id);
        $upd->execute();
        $upd->close();

        // ── Issue #14: LIMIT 100 — prepared statement (Issue #4) ──
        $q = $conn->prepare(
            "SELECT cm.id, cm.sender_type, cm.msg_type, cm.body,
                    cm.attachment_url, cm.is_read, cm.created_at,
                    CASE cm.sender_type
                        WHEN 'teacher' THEN tu.full_name
                        ELSE e.full_name
                    END as sender_name
             FROM chat_messages cm
             LEFT JOIN teacher_assignments ta ON ta.id = cm.assignment_id
             LEFT JOIN teacher_users tu ON tu.id = ta.teacher_id
             LEFT JOIN enroll e ON e.id = ta.enrollment_id
             WHERE cm.assignment_id = ? AND cm.id > ?
             ORDER BY cm.created_at ASC
             LIMIT 100"
        );
        $q->bind_param('ii', $assignment_id, $after_id);
        $q->execute();
        $result = $q->get_result();
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        $q->close();
        echo json_encode(['success'=>true,'messages'=>$rows]);
        $conn->close(); exit;
    }

    if ($_POST['action'] === 'mark_read') {
        $msg_id = (int)($_POST['msg_id'] ?? 0);
        if ($msg_id) $conn->query("UPDATE broadcast_reads SET read_at=NOW() WHERE id=$msg_id");
        echo json_encode(['success'=>true]);
        $conn->close(); exit;
    }
    $conn->close(); exit;
}

// ── Broadcast messages for this student (Issue #4: no dynamic concat) ────
$broadcast_msgs = [];
$enroll_ids_arr = array_map('intval', array_column($enrollments, 'id'));
$enroll_ids_str = count($enroll_ids_arr) ? implode(',', $enroll_ids_arr) : '0'; // safe — all intval
$enroll_courses = array_unique(array_filter(array_column($enrollments, 'course_interest')));

// Build IN clauses safely: all values are intval'd or real_escape_string'd
// We use two separate queries to avoid complex dynamic binding
// Query 1: all + student-targeted messages
$bm_result = $conn->query(
    "SELECT bm.*,
        (SELECT read_at FROM broadcast_reads br
         WHERE br.message_id=bm.id AND br.user_id={$uid} LIMIT 1) as read_at
     FROM broadcast_messages bm
     WHERE bm.is_active=1
       AND (
           bm.target_type='all'
           OR (bm.target_type='student' AND bm.target_id IN ({$enroll_ids_str}))
       )
     ORDER BY bm.created_at DESC"
);
while ($bm = $bm_result->fetch_assoc()) $broadcast_msgs[$bm['id']] = $bm;

// Query 2: course-targeted messages (separate query, each course bound properly)
if (count($enroll_courses)) {
    // Build placeholders
    $placeholders = implode(',', array_fill(0, count($enroll_courses), '?'));
    $types        = str_repeat('s', count($enroll_courses));
    $bm2 = $conn->prepare(
        "SELECT bm.*,
            (SELECT read_at FROM broadcast_reads br
             WHERE br.message_id=bm.id AND br.user_id=? LIMIT 1) as read_at
         FROM broadcast_messages bm
         WHERE bm.is_active=1
           AND bm.target_type='course'
           AND bm.target_course IN ({$placeholders})
         ORDER BY bm.created_at DESC"
    );
    $bind_values = array_merge([$uid], array_values($enroll_courses));
    $bind_types  = 'i' . $types;
    $bm2->bind_param($bind_types, ...$bind_values);
    $bm2->execute();
    $res2 = $bm2->get_result();
    while ($bm = $res2->fetch_assoc()) $broadcast_msgs[$bm['id']] = $bm; // dedupe by id
    $bm2->close();
}
// Sort merged results by created_at DESC
usort($broadcast_msgs, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$broadcast_msgs = array_values($broadcast_msgs);

// ── Teacher assignments + chat for this student (Issue #4: prepared stmt) ──
$teacher_chats = [];
if (count($enroll_ids_arr)) {
    $ph  = implode(',', array_fill(0, count($enroll_ids_arr), '?'));
    $tps = str_repeat('i', count($enroll_ids_arr));
    $ta_stmt = $conn->prepare(
        "SELECT ta.id as assignment_id, ta.enrollment_id,
                t.id as teacher_id, t.full_name as teacher_name,
                t.username as teacher_username, t.subject,
                e.course_interest,
                (SELECT COUNT(*) FROM chat_messages cm
                 WHERE cm.assignment_id=ta.id
                   AND cm.sender_type='teacher'
                   AND cm.is_read=0) as unread_count
         FROM teacher_assignments ta
         JOIN teacher_users t ON t.id = ta.teacher_id
         JOIN enroll e ON e.id = ta.enrollment_id
         WHERE ta.enrollment_id IN ({$ph})
         ORDER BY e.course_interest"
    );
    $ta_stmt->bind_param($tps, ...$enroll_ids_arr);
    $ta_stmt->execute();
    $ta_res = $ta_stmt->get_result();
    while ($tc = $ta_res->fetch_assoc()) $teacher_chats[] = $tc;
    $ta_stmt->close();
}

$conn->close();

// Stats
$total     = count($enrollments);
$completed = count(array_filter($enrollments, function($e){ return $e['enrollment_status'] === 'completed'; }));
$active    = count(array_filter($enrollments, function($e){ return in_array($e['enrollment_status'], ['approved','in_progress']); }));
$pending   = count(array_filter($enrollments, function($e){ return $e['enrollment_status'] === 'pending'; }));

$stLabel = ['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','in_progress'=>'In Progress','completed'=>'Completed'];
$stColor = ['pending'=>'#f59e0b','approved'=>'#10b981','rejected'=>'#ef4444','in_progress'=>'#0094d9','completed'=>'#8b5cf6'];
$stBg    = ['pending'=>'rgba(245,158,11,.12)','approved'=>'rgba(16,185,129,.12)','rejected'=>'rgba(239,68,68,.12)','in_progress'=>'rgba(0,148,217,.12)','completed'=>'rgba(139,92,246,.12)'];
$stIcon  = ['pending'=>'fa-clock','approved'=>'fa-check-circle','rejected'=>'fa-times-circle','in_progress'=>'fa-play-circle','completed'=>'fa-graduation-cap'];

// Initials
$name_parts = array_slice(explode(' ', trim($user['full_name'])), 0, 2);
$initials   = strtoupper(implode('', array_map(function($w){ return isset($w[0]) ? $w[0] : ''; }, $name_parts)));
$first_name = explode(' ', trim($user['full_name']))[0];
$now        = date('D, d M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard — Programmers Lab</title>
<meta name="robots" content="noindex,nofollow">
<link href="img/favicon-16x16.png" rel="shortcut icon"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ── Reset & Base ── */
*,*::before,*::after{box-sizing:border-box;}
body{margin:0;font-family:'Inter',sans-serif;background:#f0f2f5;color:#1a1a2e;}

/* ── Sidebar ── */
#sidebar{
    position:fixed;top:0;left:0;bottom:0;width:240px;
    background:#0d1b2a;
    display:flex;flex-direction:column;
    z-index:1000;
    transition:transform .3s ease;
}
.sb-brand{
    padding:22px 20px 18px;
    border-bottom:1px solid rgba(255,255,255,.07);
    display:flex;align-items:center;gap:10px;
}
.sb-brand-icon{
    width:36px;height:36px;border-radius:10px;
    background:#f07b14;
    display:flex;align-items:center;justify-content:center;
    font-size:16px;color:#fff;flex-shrink:0;
}
.sb-brand-name{font-size:14px;font-weight:700;color:#fff;line-height:1.3;}
.sb-brand-name span{color:#f07b14;}
.sb-brand-sub{font-size:11px;color:rgba(255,255,255,.3);font-weight:400;}

/* User card in sidebar */
.sb-user{
    margin:14px 12px;
    padding:14px;
    background:rgba(255,255,255,.05);
    border-radius:12px;
    display:flex;align-items:center;gap:10px;
}
.sb-user-avatar{
    width:40px;height:40px;border-radius:50%;flex-shrink:0;
    background:linear-gradient(135deg,#f07b14,#d96a00);
    display:flex;align-items:center;justify-content:center;
    font-size:15px;font-weight:900;color:#fff;
}
.sb-user-name{font-size:13px;font-weight:700;color:#fff;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sb-user-role{font-size:11px;color:rgba(255,255,255,.35);}

/* Nav */
.sb-nav{padding:4px 12px;flex:1;overflow-y:auto;}
.sb-nav-label{
    font-size:10px;font-weight:700;letter-spacing:1.2px;
    color:rgba(255,255,255,.22);text-transform:uppercase;
    padding:10px 10px 4px;margin-top:4px;
}
.sb-nav a{
    display:flex;align-items:center;gap:10px;
    padding:10px 14px;border-radius:10px;
    color:rgba(255,255,255,.55);font-size:13.5px;font-weight:500;
    text-decoration:none;margin-bottom:2px;
    transition:all .2s;
}
.sb-nav a:hover{background:rgba(255,255,255,.06);color:#fff;}
.sb-nav a.active{background:#f07b14;color:#fff;font-weight:600;}
.sb-nav a.active i,.sb-nav a:hover i{color:inherit;}
.sb-nav a i{width:18px;text-align:center;font-size:13px;color:rgba(255,255,255,.3);transition:color .2s;}

.sb-footer{
    padding:14px 12px;
    border-top:1px solid rgba(255,255,255,.07);
}
.sb-footer a{
    display:flex;align-items:center;gap:10px;
    padding:10px 14px;border-radius:10px;
    color:rgba(255,255,255,.4);font-size:13px;text-decoration:none;
    transition:all .2s;
}
.sb-footer a:hover{background:rgba(239,68,68,.12);color:#ef4444;}

/* ── Topbar ── */
#topbar{
    position:fixed;top:0;left:240px;right:0;height:64px;
    background:#fff;border-bottom:1px solid #e8ecf0;
    display:flex;align-items:center;justify-content:space-between;
    padding:0 28px;z-index:999;
    box-shadow:0 1px 4px rgba(0,0,0,.04);
}
.tb-left{display:flex;align-items:center;gap:12px;}
.tb-hamburger{
    display:none;background:none;border:none;cursor:pointer;
    padding:6px;border-radius:8px;color:#6b7280;font-size:18px;
}
.tb-hamburger:hover{background:#f3f4f6;}
.tb-title{font-size:17px;font-weight:800;color:#0d1b2a;}
.tb-right{display:flex;align-items:center;gap:16px;}
.tb-date{font-size:12.5px;color:#94a3b8;font-weight:500;}
.tb-avatar{
    width:36px;height:36px;border-radius:50%;
    background:linear-gradient(135deg,#f07b14,#d96a00);
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-size:13px;font-weight:800;
}
.tb-name{font-size:13px;font-weight:600;color:#374151;}

/* ── Main ── */
#main{
    margin-left:240px;
    padding:88px 28px 48px;
    min-height:100vh;
}

/* ── Page header ── */
.pg-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;}
.pg-header h1{font-size:20px;font-weight:800;margin:0;color:#0d1b2a;}
.pg-breadcrumb{font-size:12.5px;color:#9ca3af;margin-top:3px;}
.pg-breadcrumb span{color:#f07b14;font-weight:600;}

/* ── Stat cards ── */
.stat-card{
    background:#fff;border-radius:16px;padding:22px;
    border:1px solid #eef0f4;
    display:flex;align-items:center;gap:16px;
    transition:box-shadow .25s,transform .25s;
}
.stat-card:hover{box-shadow:0 8px 30px rgba(0,0,0,.08);transform:translateY(-2px);}
.stat-icon{
    width:52px;height:52px;border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    font-size:20px;flex-shrink:0;
}
.stat-value{font-size:30px;font-weight:900;line-height:1;color:#0d1b2a;}
.stat-label{font-size:12.5px;color:#6b7280;font-weight:500;margin-top:4px;}

/* ── Card ── */
.pl-card{background:#fff;border-radius:16px;border:1px solid #eef0f4;overflow:hidden;margin-bottom:20px;}
.pl-card-header{
    padding:16px 22px;border-bottom:1px solid #f3f4f6;
    display:flex;align-items:center;justify-content:space-between;
}
.pl-card-header h5{margin:0;font-size:15px;font-weight:700;color:#0d1b2a;display:flex;align-items:center;gap:8px;}
.pl-card-body{padding:22px;}

/* ── Course cards ── */
.course-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;padding:20px;}
.course-card{
    border:1.5px solid #f0f2f5;border-radius:14px;padding:18px;
    transition:border-color .2s,box-shadow .2s;
    position:relative;
}
.course-card:hover{border-color:#e5e7eb;box-shadow:0 4px 20px rgba(0,0,0,.08);}
.course-num{font-size:11px;font-weight:700;color:#c4c9d4;text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;}
.course-name{font-size:14.5px;font-weight:700;color:#0d1b2a;margin-bottom:10px;line-height:1.4;}
.course-meta{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:12px;}
.course-meta span{
    display:inline-flex;align-items:center;gap:5px;
    font-size:11.5px;color:#6b7280;background:#f8f9fb;
    padding:3px 9px;border-radius:8px;
}
.course-meta i{font-size:10px;color:#9ca3af;}
.status-pill{
    display:inline-flex;align-items:center;gap:5px;
    padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;
}

/* ── Info rows ── */
.info-row{display:flex;align-items:center;gap:14px;padding:13px 0;border-bottom:1px solid #f3f4f6;}
.info-row:last-child{border-bottom:none;}
.info-ico{
    width:36px;height:36px;border-radius:10px;
    background:rgba(240,123,20,.1);
    display:flex;align-items:center;justify-content:center;
    color:#f07b14;font-size:13px;flex-shrink:0;
}
.info-lbl{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:1px;}
.info-val{font-size:14px;font-weight:600;color:#0d1b2a;}

/* ── CTA banner ── */
.cta-banner{
    background:linear-gradient(135deg,#f07b14,#d96a00);
    border-radius:16px;padding:22px 26px;
    display:flex;align-items:center;justify-content:space-between;
    gap:16px;flex-wrap:wrap;margin-bottom:20px;
}
.cta-banner h4{color:#fff;font-size:16px;font-weight:800;margin:0 0 4px;}
.cta-banner p{color:rgba(255,255,255,.8);font-size:13px;margin:0;}
.cta-btn{
    display:inline-flex;align-items:center;gap:8px;
    padding:11px 22px;background:#fff;color:#f07b14;
    border-radius:50px;font-size:13.5px;font-weight:800;
    text-decoration:none;white-space:nowrap;
    box-shadow:0 4px 16px rgba(0,0,0,.1);
    transition:background .2s;
}
.cta-btn:hover{background:#fff3e8;color:#d96a00;}

/* ── Empty ── */
.pg-empty{text-align:center;padding:52px 20px;}
.pg-empty-ico{
    width:64px;height:64px;border-radius:50%;
    background:#f3f4f6;display:flex;align-items:center;
    justify-content:center;font-size:24px;color:#d1d5db;
    margin:0 auto 16px;
}
.pg-empty h4{font-size:15px;font-weight:700;color:#374151;margin-bottom:6px;}
.pg-empty p{font-size:13px;color:#9ca3af;margin-bottom:20px;}

/* ── Responsive ── */
@media(max-width:768px){
    #sidebar{transform:translateX(-100%);}
    #sidebar.open{transform:translateX(0);}
    #topbar{left:0;}
    #main{margin-left:0;padding:80px 14px 36px;}
    .tb-hamburger{display:flex;}
    .tb-date,.tb-name{display:none;}
    .stat-card{padding:16px;}
    .stat-value{font-size:24px;}
    .cta-banner{flex-direction:column;text-align:center;}
    .course-grid{grid-template-columns:1fr;padding:14px;}
}
/* Sidebar overlay on mobile */
#sb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;}
#sb-overlay.show{display:block;}
</style>
</head>
<body>

<!-- SIDEBAR OVERLAY (mobile) -->
<div id="sb-overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside id="sidebar">
    <div class="sb-brand">
        <div class="sb-brand-icon"><i class="fas fa-code"></i></div>
        <div>
            <div class="sb-brand-name"><span>Programmers</span> Lab</div>
            <div class="sb-brand-sub">Student Portal</div>
        </div>
    </div>

    <!-- User card -->
    <div class="sb-user">
        <div class="sb-user-avatar"><?= htmlspecialchars($initials ?: '?') ?></div>
        <div style="min-width:0;">
            <div class="sb-user-name"><?= h($user['full_name']) ?></div>
            <div class="sb-user-role">Student</div>
        </div>
    </div>

    <nav class="sb-nav">
        <div class="sb-nav-label">Portal</div>
        <a href="user-profile.php" class="active">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="enroll-form.html">
            <i class="fas fa-rocket"></i> Enroll in Course
        </a>
        <div class="sb-nav-label">Info</div>
        <a href="best-computer-courses-rawalpidni.html">
            <i class="fas fa-graduation-cap"></i> All Courses
        </a>
        <a href="student-portal.html">
            <i class="fas fa-certificate"></i> Check Certificate
        </a>
        <a href="prgrammers-lab-contact.html">
            <i class="fas fa-phone"></i> Contact Us
        </a>
        <a href="index.html">
            <i class="fas fa-home"></i> Back to Website
        </a>
    </nav>

    <div class="sb-footer">
        <a href="user-logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<!-- TOPBAR -->
<header id="topbar">
    <div class="tb-left">
        <button class="tb-hamburger" onclick="toggleSidebar()" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div>
            <div class="tb-title">My Dashboard</div>
        </div>
    </div>
    <div class="tb-right">
        <span class="tb-date"><?= $now ?></span>
        <span class="tb-name"><?= h($first_name) ?></span>
        <div class="tb-avatar"><?= htmlspecialchars($initials ?: '?') ?></div>
    </div>
</header>

<!-- MAIN CONTENT -->
<main id="main">

    <!-- Page header -->
    <div class="pg-header">
        <div>
            <h1>Welcome back, <?= h($first_name) ?>!</h1>
            <div class="pg-breadcrumb">Student Portal &rsaquo; <span>Dashboard</span></div>
        </div>
        <a href="enroll-form.html" class="btn btn-sm" style="background:#f07b14;color:#fff;border-radius:50px;padding:9px 20px;font-weight:700;font-size:13px;display:inline-flex;align-items:center;gap:7px;">
            <i class="fas fa-rocket"></i> Enroll Now
        </a>
    </div>

    <!-- STATS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(0,148,217,.1);">
                    <i class="fas fa-layer-group" style="color:#0094d9;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#0094d9;"><?= $total ?></div>
                    <div class="stat-label">Total Enrolled</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,.1);">
                    <i class="fas fa-play-circle" style="color:#10b981;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#10b981;"><?= $active ?></div>
                    <div class="stat-label">Active Courses</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(139,92,246,.1);">
                    <i class="fas fa-graduation-cap" style="color:#8b5cf6;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#8b5cf6;"><?= $completed ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,.1);">
                    <i class="fas fa-clock" style="color:#f59e0b;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#f59e0b;"><?= $pending ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ENROLL CTA -->
    <div class="cta-banner">
        <div>
            <h4>Ready to learn something new?</h4>
            <p>30+ IT courses in Rawalpindi — morning & evening batches available.</p>
        </div>
        <a href="enroll-form.html" class="cta-btn"><i class="fas fa-rocket"></i> Enroll in a Course</a>
    </div>

    <!-- MY COURSES -->
    <div class="pl-card">
        <div class="pl-card-header">
            <h5><i class="fas fa-graduation-cap" style="color:#0094d9;"></i> My Courses</h5>
            <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;">
                <?= $total ?> application<?= $total !== 1 ? 's' : '' ?>
            </span>
        </div>

        <?php if ($total > 0): ?>
        <div class="course-grid">
            <?php foreach ($enrollments as $i => $e):
                $st  = $e['enrollment_status'] ?? 'pending';
                $col = $stColor[$st] ?? '#f59e0b';
                $bg  = $stBg[$st]   ?? 'rgba(245,158,11,.12)';
                $lbl = $stLabel[$st] ?? ucfirst($st);
                $ico = $stIcon[$st]  ?? 'fa-clock';
            ?>
            <div class="course-card" style="border-left:4px solid <?= $col ?>;">
                <div class="course-num">Course #<?= $i + 1 ?></div>
                <div class="course-name"><?= h($e['course_interest']) ?></div>
                <div class="course-meta">
                    <?php if (!empty($e['study_mode'])): ?>
                    <span><i class="fas fa-chalkboard-teacher"></i> <?= h($e['study_mode']) ?></span>
                    <?php endif; ?>
                    <span><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($e['created_at'])) ?></span>
                </div>
                <span class="status-pill" style="background:<?= $bg ?>;color:<?= $col ?>;">
                    <i class="fas <?= $ico ?>"></i> <?= $lbl ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="pg-empty">
            <div class="pg-empty-ico"><i class="fas fa-graduation-cap"></i></div>
            <h4>No Courses Yet</h4>
            <p>You haven't enrolled in any course yet. Start your IT journey today!</p>
            <a href="enroll-form.html" class="btn btn-sm" style="background:#f07b14;color:#fff;border-radius:50px;padding:10px 24px;font-weight:700;display:inline-flex;align-items:center;gap:8px;">
                <i class="fas fa-rocket"></i> Enroll in a Course
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- BROADCAST MESSAGES FROM ADMIN -->
    <?php
    $unread_bcast = count(array_filter($broadcast_msgs, fn($m)=>!$m['read_at']));
    $total_bcast  = count($broadcast_msgs);
    $bmTypeColor  = ['info'=>'#0094d9','warning'=>'#f59e0b','success'=>'#10b981','urgent'=>'#ef4444'];
    $bmTypeBg     = ['info'=>'rgba(0,148,217,.1)','warning'=>'rgba(245,158,11,.1)','success'=>'rgba(16,185,129,.1)','urgent'=>'rgba(239,68,68,.1)'];
    $bmTypeIcon   = ['info'=>'fa-info-circle','warning'=>'fa-exclamation-triangle','success'=>'fa-check-circle','urgent'=>'fa-bell'];
    ?>
    <div class="pl-card" style="margin-bottom:20px;">
        <div class="pl-card-header">
            <h5><i class="fas fa-bell" style="color:#f07b14;"></i>&nbsp; Messages from Admin
                <?php if ($unread_bcast>0): ?>
                <span style="background:#ef4444;color:#fff;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;margin-left:6px;"><?= $unread_bcast ?></span>
                <?php endif; ?>
            </h5>
            <span style="font-size:13px;color:#9ca3af;"><?= $total_bcast ?> message(s)</span>
        </div>
        <div class="pl-card-body" style="padding:0 22px 10px;">
            <?php if ($total_bcast === 0): ?>
            <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                <i class="fas fa-bell-slash" style="font-size:28px;opacity:.3;display:block;margin-bottom:10px;"></i>
                No messages from admin yet.
            </div>
            <?php else: foreach ($broadcast_msgs as $bm):
                $bmt = $bm['msg_type'] ?? 'info';
                $col = $bmTypeColor[$bmt] ?? '#0094d9';
                $bg  = $bmTypeBg[$bmt]   ?? 'rgba(0,148,217,.1)';
                $ico = $bmTypeIcon[$bmt]  ?? 'fa-info-circle';
                $isUnread = empty($bm['read_at']);
            ?>
            <div style="border-left:4px solid <?= $col ?>;background:<?= $isUnread ? $bg : '#f9fafb' ?>;border-radius:0 12px 12px 0;padding:16px 18px;margin:14px 0;position:relative;">
                <?php if ($isUnread): ?>
                <span style="position:absolute;top:10px;right:12px;background:<?= $col ?>;color:#fff;padding:2px 9px;border-radius:20px;font-size:10px;font-weight:700;">NEW</span>
                <?php endif; ?>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                    <i class="fas <?= $ico ?>" style="color:<?= $col ?>;"></i>
                    <span style="font-weight:800;font-size:14.5px;color:#0d1b2a;"><?= h($bm['title']) ?></span>
                </div>
                <div style="font-size:13.5px;color:#374151;line-height:1.65;"><?= nl2br(h($bm['body'])) ?></div>
                <div style="font-size:11.5px;color:#9ca3af;margin-top:8px;">
                    <?= date('d M Y, h:i A', strtotime($bm['created_at'])) ?>
                    · By Admin
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- TEACHER CHAT -->
    <?php if (count($teacher_chats) > 0):
        $total_unread_chat = array_sum(array_column($teacher_chats,'unread_count'));
    ?>
    <div class="pl-card" style="margin-bottom:20px;">
        <div class="pl-card-header">
            <h5><i class="fas fa-chalkboard-teacher" style="color:#8b5cf6;"></i>&nbsp; My Teachers
                <?php if ($total_unread_chat>0): ?>
                <span style="background:#ef4444;color:#fff;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;margin-left:6px;"><?= $total_unread_chat ?></span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="pl-card-body" style="padding:0;">
            <!-- Teacher tabs -->
            <div style="display:flex;gap:8px;padding:14px 18px;border-bottom:1px solid #f3f4f6;flex-wrap:wrap;">
                <?php foreach ($teacher_chats as $idx=>$tc): ?>
                <button class="type-btn-tc <?= $idx===0?'active-tc':'' ?>"
                    onclick="openTeacherChat(<?= $tc['assignment_id'] ?>, this)"
                    id="tcbtn-<?= $tc['assignment_id'] ?>"
                    style="padding:7px 16px;border:1.5px solid <?= $idx===0?'#8b5cf6':'#e5e7eb' ?>;border-radius:20px;font-size:12.5px;font-weight:700;cursor:pointer;background:<?= $idx===0?'rgba(139,92,246,.1)':'#fff' ?>;color:<?= $idx===0?'#6d28d9':'#6b7280' ?>;position:relative;">
                    <i class="fas fa-user-tie me-1"></i><?= h($tc['teacher_name']) ?>
                    <?php if ($tc['unread_count']>0): ?>
                    <span style="position:absolute;top:-5px;right:-5px;background:#ef4444;color:#fff;border-radius:50%;width:16px;height:16px;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;" id="tcunread-<?= $tc['assignment_id'] ?>"><?= min((int)$tc['unread_count'],9) ?></span>
                    <?php else: ?>
                    <span id="tcunread-<?= $tc['assignment_id'] ?>" style="display:none;"></span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
            <!-- Chat window -->
            <div id="teacherChatBox" style="display:flex;flex-direction:column;height:420px;">
                <div id="tcMessages" style="flex:1;overflow-y:auto;padding:16px 18px;background:#f8fafc;display:flex;flex-direction:column;gap:10px;">
                    <div style="text-align:center;color:#9ca3af;font-size:13px;padding:20px;">Loading messages...</div>
                </div>
                <div style="padding:12px 16px;border-top:1px solid #f3f4f6;display:flex;gap:8px;">
                    <textarea id="tcMsgBody" rows="2" placeholder="Type a message to your teacher..."
                        style="flex:1;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font-family:inherit;font-size:13.5px;resize:none;outline:none;"></textarea>
                    <button onclick="sendTeacherMsg()" style="padding:0 18px;background:#8b5cf6;color:#fff;border:none;border-radius:12px;cursor:pointer;font-size:15px;">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ACCOUNT INFO -->
    <div class="pl-card">
        <div class="pl-card-header">
            <h5><i class="fas fa-user-circle" style="color:#f07b14;"></i> Account Information</h5>
        </div>
        <div class="pl-card-body">
            <div class="info-row">
                <div class="info-ico"><i class="fas fa-user"></i></div>
                <div>
                    <span class="info-lbl">Full Name</span>
                    <span class="info-val"><?= h($user['full_name']) ?></span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-ico"><i class="fas fa-envelope"></i></div>
                <div>
                    <span class="info-lbl">Email Address</span>
                    <span class="info-val"><?= h($user['email']) ?></span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-ico"><i class="fas fa-phone"></i></div>
                <div>
                    <span class="info-lbl">Phone Number</span>
                    <span class="info-val"><?= h($user['phone']) ?></span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-ico"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <span class="info-lbl">Member Since</span>
                    <span class="info-val"><?= date('d M Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sb-overlay').classList.toggle('show');
}
function closeSidebar(){
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sb-overlay').classList.remove('show');
}

// ── Teacher Chat ───────────────────────────────────────────
<?php $first_tc = $teacher_chats[0] ?? null; ?>
let tcCurrentAssignment = <?= $first_tc ? (int)$first_tc['assignment_id'] : 0 ?>;
let tcLastMsgId   = 0;
let tcPoll        = null;
let tcFetching    = false;       // Issue #9: prevent stacked requests
let tcController  = null;        // Issue #9: AbortController

const msgTypeIconsTC = {text:'fa-comment',link:'fa-link',video:'fa-play-circle',question:'fa-question-circle',file:'fa-file'};
const msgTypeColorsTC = {text:'#6b7280',link:'#0094d9',video:'#ef4444',question:'#f07b14',file:'#8b5cf6'};

function openTeacherChat(assignId, btn) {
    tcCurrentAssignment = assignId;
    tcLastMsgId = 0;
    document.querySelectorAll('[id^="tcbtn-"]').forEach(b=>{
        b.style.border='1.5px solid #e5e7eb';
        b.style.background='#fff';
        b.style.color='#6b7280';
    });
    btn.style.border='1.5px solid #8b5cf6';
    btn.style.background='rgba(139,92,246,.1)';
    btn.style.color='#6d28d9';
    // Hide unread dot
    const ud=document.getElementById('tcunread-'+assignId);
    if(ud) ud.style.display='none';
    document.getElementById('tcMessages').innerHTML='<div style="text-align:center;color:#9ca3af;font-size:13px;padding:20px;">Loading...</div>';
    loadTeacherMsgs();
}

function loadTeacherMsgs(append=false) {
    if (!tcCurrentAssignment) return;
    if (tcFetching && append) return; // skip poll if in-flight
    tcFetching = true;

    if (tcController) tcController.abort();
    tcController = new AbortController();

    const fd=new FormData();
    fd.append('action','get_chat');
    fd.append('assignment_id',tcCurrentAssignment);
    fd.append('after_id',tcLastMsgId);
    fetch('user-profile.php', { method:'POST', body:fd, signal: tcController.signal })
        .then(r=>r.json())
        .then(data=>{
            tcFetching = false;
            if(!data.success) return;
            const container=document.getElementById('tcMessages');
            if(!append) container.innerHTML='';
            if(data.messages.length===0&&!append){
                container.innerHTML='<div style="text-align:center;color:#9ca3af;font-size:13px;padding:40px;">No messages yet. Send the first message!</div>';
                return;
            }
            const myName = '<?= h(addslashes($user['full_name'])) ?>';
            data.messages.forEach(m=>{
                if(m.id>tcLastMsgId) tcLastMsgId=parseInt(m.id);
                const isSent=m.sender_type==='student';
                const time=new Date(m.created_at).toLocaleTimeString('en-PK',{hour:'2-digit',minute:'2-digit'});
                const icon=msgTypeIconsTC[m.msg_type]||'fa-comment';
                const col=msgTypeColorsTC[m.msg_type]||'#6b7280';
                let typTag='';
                if(m.msg_type!=='text'){
                    typTag=`<div style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;background:${col}18;color:${col};margin-bottom:5px;"><i class="fas ${icon}"></i>${m.msg_type.charAt(0).toUpperCase()+m.msg_type.slice(1)}</div>`;
                }
                let bodyHtml=escTC(m.body);
                if(m.attachment_url){
                    bodyHtml+=`<div style="margin-top:7px;"><a href="${escTC(m.attachment_url)}" target="_blank" rel="noopener noreferrer" style="color:${isSent?'rgba(255,255,255,.85)':'#0094d9'};font-size:12.5px;word-break:break-all;"><i class="fas fa-external-link-alt me-1"></i>${escTC(m.attachment_url)}</a></div>`;
                }
                const div=document.createElement('div');
                div.style.display='flex';div.style.flexDirection='column';div.style.alignItems=isSent?'flex-end':'flex-start';
                div.innerHTML=`<div style="font-size:11px;color:#9ca3af;margin-bottom:3px;padding:0 4px;">${escTC(m.sender_name||'')}</div>
                    <div style="max-width:70%;padding:12px 16px;border-radius:16px;font-size:13.5px;line-height:1.55;word-break:break-word;
                        background:${isSent?'#8b5cf6':'#fff'};color:${isSent?'#fff':'#0d1b2a'};
                        border:${isSent?'none':'1px solid #e5e7eb'};
                        border-bottom-${isSent?'right':'left'}-radius:4px;">
                        ${typTag}${bodyHtml}
                        <div style="font-size:11px;opacity:.6;margin-top:5px;">${time}</div>
                    </div>`;
                container.appendChild(div);
            });
            container.scrollTop=container.scrollHeight;
        })
        .catch(err => {
            tcFetching = false;
            if (err.name !== 'AbortError') console.warn('Chat fetch error:', err);
        });
}

function sendTeacherMsg(){
    if(!tcCurrentAssignment) return;
    const body=document.getElementById('tcMsgBody').value.trim();
    if(!body) return;
    const fd=new FormData();
    fd.append('action','send_chat');
    fd.append('assignment_id',tcCurrentAssignment);
    fd.append('body',body);
    fetch('user-profile.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
            if(data.success){ document.getElementById('tcMsgBody').value=''; loadTeacherMsgs(true); }
        });
}

document.getElementById('tcMsgBody')?.addEventListener('keydown',function(e){
    if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendTeacherMsg();}
});

function escTC(str){ const d=document.createElement('div');d.appendChild(document.createTextNode(str||''));return d.innerHTML; }

// Init & polling — pause when tab hidden (battery/connection friendly)
if(tcCurrentAssignment){
    loadTeacherMsgs();
    tcPoll = setInterval(() => {
        if (!document.hidden) loadTeacherMsgs(true);
    }, 5000);
}

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (tcPoll) clearInterval(tcPoll);
    if (tcController) tcController.abort();
});
</script>
</body>
</html>
