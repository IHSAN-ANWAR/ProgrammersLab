<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

// ── AJAX ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'send') {
        $title       = trim($_POST['title']       ?? '');
        $body        = trim($_POST['body']         ?? '');
        $msg_type    = trim($_POST['msg_type']    ?? 'info');
        $target_type = trim($_POST['target_type'] ?? 'all');
        $target_id   = (int)($_POST['target_id']   ?? 0)  ?: null;
        $target_course = trim($_POST['target_course'] ?? '') ?: null;
        $sent_by     = $_SESSION['admin_username'] ?? 'admin';

        if (!$title || !$body) {
            echo json_encode(['success'=>false,'message'=>'Title and message are required.']); exit;
        }
        $allowed_types = ['info','warning','success','urgent'];
        $allowed_targets = ['all','course','student'];
        if (!in_array($msg_type, $allowed_types)) $msg_type = 'info';
        if (!in_array($target_type, $allowed_targets)) $target_type = 'all';

        $stmt = $conn->prepare(
            "INSERT INTO broadcast_messages (title,body,msg_type,target_type,target_id,target_course,sent_by)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('sssssss', $title,$body,$msg_type,$target_type,$target_id,$target_course,$sent_by);
        $ok = $stmt->execute();
        log_activity($conn,'BROADCAST_MSG',"Title: $title | Target: $target_type");
        echo json_encode(['success'=>$ok,'id'=>$conn->insert_id,'message'=>$ok?'Message sent!':$stmt->error]);
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM broadcast_messages WHERE id=?");
        $stmt->bind_param('i',$id);
        $ok = $stmt->execute();
        echo json_encode(['success'=>$ok]);
        exit;
    }

    if ($_POST['action'] === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID.']); exit; }
        $cur = $conn->prepare("SELECT is_active FROM broadcast_messages WHERE id=?");
        $cur->bind_param('i', $id);
        $cur->execute();
        $row = $cur->get_result()->fetch_assoc();
        $cur->close();
        if (!$row) { echo json_encode(['success'=>false,'message'=>'Message not found.']); exit; }
        $new_state = $row['is_active'] ? 0 : 1;
        $tog = $conn->prepare("UPDATE broadcast_messages SET is_active=? WHERE id=?");
        $tog->bind_param('ii', $new_state, $id);
        $tog->execute();
        $tog->close();
        echo json_encode(['success'=>true,'is_active'=>$new_state]);
        exit;
    }
    exit;
}

// ── Load ─────────────────────────────────────────────────────
$messages = $conn->query(
    "SELECT bm.*,
        (SELECT COUNT(*) FROM broadcast_reads br WHERE br.message_id=bm.id) as read_count
     FROM broadcast_messages bm ORDER BY bm.created_at DESC"
);

// Enrollments for targeting
$enrollments = $conn->query(
    "SELECT id, full_name, course_interest FROM enroll
     WHERE enrollment_status IN ('approved','in_progress','pending','completed')
     ORDER BY full_name"
);
// Courses for targeting
$courses_list = $conn->query("SELECT DISTINCT course_interest FROM enroll ORDER BY course_interest");

log_activity($conn,'VIEW_BROADCASTS','Viewed broadcast messages');
$conn->close();

admin_head('Broadcast Messages','broadcast_messages');
?>

<div class="pl-page-header">
    <div>
        <h1>Broadcast Messages</h1>
        <div class="pl-breadcrumb">Manage → <span>Send Messages to Students</span></div>
    </div>
    <button onclick="openModal()" style="background:#f07b14;border:none;border-radius:10px;padding:10px 22px;font-weight:700;color:#fff;cursor:pointer;">
        <i class="fas fa-paper-plane me-2"></i>Send Message
    </button>
</div>

<!-- Stats quick row -->
<?php
$msg_colors = [
    'info'    => ['#0094d9','rgba(0,148,217,.1)','fa-info-circle'],
    'warning' => ['#f59e0b','rgba(245,158,11,.1)','fa-exclamation-triangle'],
    'success' => ['#10b981','rgba(16,185,129,.1)','fa-check-circle'],
    'urgent'  => ['#ef4444','rgba(239,68,68,.1)','fa-bell'],
];
?>

<!-- Messages Table -->
<div class="pl-card">
    <div class="pl-card-header">
        <h5><i class="fas fa-paper-plane me-2" style="color:#f07b14"></i>All Messages</h5>
    </div>
    <div class="pl-card-body p-0">
        <?php if ($messages && $messages->num_rows > 0): ?>
        <table class="pl-table">
            <thead>
                <tr><th>Title</th><th>Type</th><th>Target</th><th>Read</th><th>Status</th><th>Sent</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while ($m = $messages->fetch_assoc()):
                [$tc,$tbg,$ti] = $msg_colors[$m['msg_type']] ?? $msg_colors['info'];
            ?>
            <tr id="mrow-<?= $m['id'] ?>">
                <td>
                    <div style="font-weight:700;font-size:13.5px;"><?= h($m['title']) ?></div>
                    <div style="font-size:12px;color:#9ca3af;max-width:220px;"><?= h(mb_substr($m['body'],0,55)) ?>…</div>
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:700;background:<?= $tbg ?>;color:<?= $tc ?>;">
                        <i class="fas <?= $ti ?>"></i> <?= ucfirst($m['msg_type']) ?>
                    </span>
                </td>
                <td style="font-size:12.5px;color:#374151;">
                    <?php if ($m['target_type']==='all'): ?>
                        <span style="color:#6366f1;font-weight:700;"><i class="fas fa-users me-1"></i>All Students</span>
                    <?php elseif ($m['target_type']==='course'): ?>
                        <span style="color:#0094d9;font-weight:700;"><i class="fas fa-graduation-cap me-1"></i><?= h($m['target_course']) ?></span>
                    <?php else: ?>
                        <span style="color:#10b981;font-weight:700;"><i class="fas fa-user me-1"></i>Student #<?= $m['target_id'] ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="font-size:13px;font-weight:700;color:#8b5cf6;">
                        <i class="fas fa-eye me-1"></i><?= (int)$m['read_count'] ?>
                    </span>
                </td>
                <td>
                    <button onclick="toggleMsg(<?= $m['id'] ?>, this)" data-active="<?= $m['is_active'] ?>"
                        style="padding:4px 12px;border:none;border-radius:20px;font-size:11.5px;font-weight:700;cursor:pointer;
                            background:<?= $m['is_active'] ? 'rgba(16,185,129,.1)' : 'rgba(239,68,68,.1)' ?>;
                            color:<?= $m['is_active'] ? '#059669' : '#dc2626' ?>;">
                        <?= $m['is_active'] ? '● Visible' : '○ Hidden' ?>
                    </button>
                </td>
                <td style="font-size:12px;color:#9ca3af;"><?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></td>
                <td>
                    <button onclick="deleteMsg(<?= $m['id'] ?>)"
                        style="padding:6px 12px;background:rgba(239,68,68,.1);color:#ef4444;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="pl-empty">
            <i class="fas fa-paper-plane"></i>
            <p>No messages sent yet. Send your first broadcast!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Send Message Modal -->
<div id="msgModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;padding:16px;overflow-y:auto;">
<div style="background:#fff;border-radius:18px;width:100%;max-width:540px;margin:auto;">
    <div style="padding:20px 24px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
        <h4 style="margin:0;font-weight:800;color:#0d1b2a;"><i class="fas fa-paper-plane me-2" style="color:#f07b14;"></i>Send Broadcast Message</h4>
        <button onclick="closeModal()" style="border:none;background:#f5f5f5;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:16px;">✕</button>
    </div>
    <div style="padding:22px 24px;">
        <div style="margin-bottom:15px;">
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Message Title *</label>
            <input type="text" id="mTitle" class="form-control" placeholder="e.g. New Batch Schedule Update">
        </div>
        <div style="margin-bottom:15px;">
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Message *</label>
            <textarea id="mBody" class="form-control" rows="4" placeholder="Write your message here..."></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:15px;">
            <div>
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Message Type</label>
                <select id="mType" class="form-control">
                    <option value="info">Info</option>
                    <option value="success">Success</option>
                    <option value="warning">Warning</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Send To</label>
                <select id="mTarget" class="form-control" onchange="updateTargetFields()">
                    <option value="all">All Students</option>
                    <option value="course">Specific Course</option>
                    <option value="student">Specific Student</option>
                </select>
            </div>
        </div>

        <!-- Target course field -->
        <div id="fieldCourse" style="display:none;margin-bottom:15px;">
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Select Course</label>
            <select id="mCourse" class="form-control">
                <option value="">— Select Course —</option>
                <?php while ($c = $courses_list->fetch_assoc()): ?>
                <option value="<?= h($c['course_interest']) ?>"><?= h($c['course_interest']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Target student field -->
        <div id="fieldStudent" style="display:none;margin-bottom:15px;">
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Select Student</label>
            <select id="mStudent" class="form-control">
                <option value="">— Select Student —</option>
                <?php
                $enrollments->data_seek(0);
                while ($e = $enrollments->fetch_assoc()): ?>
                <option value="<?= $e['id'] ?>"><?= h($e['full_name']) ?> — <?= h($e['course_interest']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Preview -->
        <div style="background:#f8fafc;border:1.5px dashed #e5e7eb;border-radius:12px;padding:14px 16px;margin-bottom:18px;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#9ca3af;margin-bottom:8px;">Preview</div>
            <div id="previewMsg" style="background:#fff;border-radius:10px;padding:14px 16px;border-left:4px solid #0094d9;">
                <div style="font-weight:800;color:#0d1b2a;font-size:14px;" id="prevTitle">Your title here</div>
                <div style="font-size:13px;color:#6b7280;margin-top:5px;line-height:1.6;" id="prevBody">Your message here...</div>
            </div>
        </div>

        <div id="mMsg" style="display:none;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;"></div>
        <div style="display:flex;gap:10px;">
            <button onclick="sendMessage()" style="flex:1;padding:12px;background:#f07b14;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">
                <i class="fas fa-paper-plane me-1"></i>Send Message
            </button>
            <button onclick="closeModal()" style="flex:1;padding:12px;background:#f0f0f0;color:#374151;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">Cancel</button>
        </div>
    </div>
</div>
</div>

<script>
const typeColors = {info:'#0094d9',success:'#10b981',warning:'#f59e0b',urgent:'#ef4444'};

// Live preview
document.getElementById('mTitle').addEventListener('input',updatePreview);
document.getElementById('mBody').addEventListener('input',updatePreview);
document.getElementById('mType').addEventListener('change',updatePreview);

function updatePreview() {
    const title = document.getElementById('mTitle').value || 'Your title here';
    const body  = document.getElementById('mBody').value  || 'Your message here...';
    const type  = document.getElementById('mType').value;
    const color = typeColors[type] || '#0094d9';
    document.getElementById('prevTitle').textContent = title;
    document.getElementById('prevBody').textContent  = body;
    document.getElementById('previewMsg').style.borderLeftColor = color;
}

function updateTargetFields() {
    const v = document.getElementById('mTarget').value;
    document.getElementById('fieldCourse').style.display  = v==='course'  ? 'block' : 'none';
    document.getElementById('fieldStudent').style.display = v==='student' ? 'block' : 'none';
}

function openModal() {
    ['mTitle','mBody'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('mType').value='info';
    document.getElementById('mTarget').value='all';
    document.getElementById('fieldCourse').style.display='none';
    document.getElementById('fieldStudent').style.display='none';
    document.getElementById('mMsg').style.display='none';
    updatePreview();
    document.getElementById('msgModal').style.display='flex';
}
function closeModal() { document.getElementById('msgModal').style.display='none'; }

function sendMessage() {
    const title  = document.getElementById('mTitle').value.trim();
    const body   = document.getElementById('mBody').value.trim();
    const target = document.getElementById('mTarget').value;
    if (!title||!body) { showMMsg('Title and message required.','#ef4444'); return; }
    if (target==='course' && !document.getElementById('mCourse').value) { showMMsg('Select a course.','#ef4444'); return; }
    if (target==='student' && !document.getElementById('mStudent').value) { showMMsg('Select a student.','#ef4444'); return; }

    const fd = new FormData();
    fd.append('action',        'send');
    fd.append('title',         title);
    fd.append('body',          body);
    fd.append('msg_type',      document.getElementById('mType').value);
    fd.append('target_type',   target);
    fd.append('target_course', document.getElementById('mCourse')?.value||'');
    fd.append('target_id',     document.getElementById('mStudent')?.value||0);

    fetch('broadcast_messages.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
            if(data.success){ showMMsg('Message sent successfully!','#10b981'); setTimeout(()=>{closeModal();location.reload();},900); }
            else showMMsg(data.message||'Error sending.','#ef4444');
        })
        .catch(()=>showMMsg('Network error.','#ef4444'));
}

function showMMsg(text,color) {
    const el=document.getElementById('mMsg');
    el.textContent=text; el.style.color=color;
    el.style.background=color+'15'; el.style.border='1px solid '+color+'40';
    el.style.display='block';
}

function toggleMsg(id, btn) {
    const fd=new FormData(); fd.append('action','toggle'); fd.append('id',id);
    fetch('broadcast_messages.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
            if(data.success){
                btn.dataset.active=data.is_active;
                btn.textContent = data.is_active ? '● Visible' : '○ Hidden';
                btn.style.background = data.is_active ? 'rgba(16,185,129,.1)' : 'rgba(239,68,68,.1)';
                btn.style.color      = data.is_active ? '#059669' : '#dc2626';
            }
        });
}

function deleteMsg(id) {
    if(!confirm('Delete this message?')) return;
    const fd=new FormData(); fd.append('action','delete'); fd.append('id',id);
    fetch('broadcast_messages.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{ if(data.success) document.getElementById('mrow-'+id).remove(); });
}

document.getElementById('msgModal').addEventListener('click',function(e){
    if(e.target===this) closeModal();
});
</script>

<?php admin_foot(); ?>
