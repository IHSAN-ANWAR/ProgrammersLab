<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

// ── AJAX Handler ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'assign') {
        $teacher_id    = (int)($_POST['teacher_id']    ?? 0);
        $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
        if (!$teacher_id || !$enrollment_id) {
            echo json_encode(['success'=>false,'message'=>'Select teacher and student.']); exit;
        }
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO teacher_assignments (teacher_id, enrollment_id, assigned_by)
             VALUES (?, ?, ?)"
        );
        $admin = $_SESSION['admin_username'] ?? 'admin';
        $stmt->bind_param('iis', $teacher_id, $enrollment_id, $admin);
        $ok = $stmt->execute();
        log_activity($conn, 'ASSIGN_TEACHER', "Teacher $teacher_id → Enrollment $enrollment_id");
        echo json_encode(['success'=>$ok, 'message'=> $ok ? 'Assigned!' : 'Already assigned or DB error.']);
        exit;
    }

    if ($_POST['action'] === 'unassign') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM teacher_assignments WHERE id=?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        echo json_encode(['success'=>$ok]);
        exit;
    }
    exit;
}

// ── Load data ─────────────────────────────────────────────────
$filter_teacher = (int)($_GET['teacher_id'] ?? 0);

// All teachers
$teachers = $conn->query("SELECT id, full_name, username, subject FROM teacher_users WHERE is_active=1 ORDER BY full_name");

// All approved/in_progress/completed enrollments
$enrollments = $conn->query(
    "SELECT id, full_name, course_interest, phone,
            IFNULL(NULLIF(enrollment_status,''),'pending') as enrollment_status
     FROM enroll
     WHERE enrollment_status IN ('approved','in_progress','completed','pending')
     ORDER BY full_name"
);

// Current assignments — prepared statement (Issue #5)
if ($filter_teacher) {
    $as = $conn->prepare(
        "SELECT ta.id, ta.teacher_id, ta.enrollment_id, ta.assigned_at,
                t.full_name as teacher_name, t.username as teacher_username, t.subject,
                e.full_name as student_name, e.phone as student_phone, e.course_interest,
                IFNULL(NULLIF(e.enrollment_status,''),'pending') as enrollment_status
         FROM teacher_assignments ta
         JOIN teacher_users t ON t.id = ta.teacher_id
         JOIN enroll e ON e.id = ta.enrollment_id
         WHERE ta.teacher_id = ?
         ORDER BY ta.assigned_at DESC"
    );
    $as->bind_param('i', $filter_teacher);
} else {
    $as = $conn->prepare(
        "SELECT ta.id, ta.teacher_id, ta.enrollment_id, ta.assigned_at,
                t.full_name as teacher_name, t.username as teacher_username, t.subject,
                e.full_name as student_name, e.phone as student_phone, e.course_interest,
                IFNULL(NULLIF(e.enrollment_status,''),'pending') as enrollment_status
         FROM teacher_assignments ta
         JOIN teacher_users t ON t.id = ta.teacher_id
         JOIN enroll e ON e.id = ta.enrollment_id
         ORDER BY ta.assigned_at DESC"
    );
}
$as->execute();
$assignments = $as->get_result();
$as->close();

$conn->close();
admin_head('Teacher Assignments', 'teacher_assignments');
?>

<div class="pl-page-header">
    <div>
        <h1>Teacher Assignments</h1>
        <div class="pl-breadcrumb">Manage → <span>Assign Teachers to Students</span></div>
    </div>
    <button onclick="openAssignModal()" style="background:#f07b14;border:none;border-radius:10px;padding:10px 22px;font-weight:700;color:#fff;cursor:pointer;">
        <i class="fas fa-link me-2"></i>New Assignment
    </button>
</div>

<!-- Filter by teacher -->
<div style="background:#fff;border-radius:14px;border:1px solid #eef0f4;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <label style="font-size:13px;font-weight:700;color:#374151;">Filter by Teacher:</label>
    <select onchange="location='?teacher_id='+this.value" style="padding:8px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13.5px;color:#374151;min-width:200px;">
        <option value="0" <?= !$filter_teacher ? 'selected' : '' ?>>All Teachers</option>
        <?php
        $teachers->data_seek(0);
        while ($t = $teachers->fetch_assoc()): ?>
        <option value="<?= $t['id'] ?>" <?= $filter_teacher == $t['id'] ? 'selected' : '' ?>>
            <?= h($t['full_name']) ?> (@<?= h($t['username']) ?>)
        </option>
        <?php endwhile; ?>
    </select>
    <a href="teachers.php" style="font-size:13px;color:#f07b14;font-weight:600;text-decoration:none;">
        <i class="fas fa-cog me-1"></i>Manage Teachers
    </a>
</div>

<!-- Assignments Table -->
<div class="pl-card">
    <div class="pl-card-header">
        <h5><i class="fas fa-link me-2" style="color:#8b5cf6"></i>Current Assignments</h5>
        <span style="font-size:13px;color:#9ca3af;"><?= $assignments->num_rows ?> assignment(s)</span>
    </div>
    <div class="pl-card-body p-0">
        <?php if ($assignments->num_rows > 0): ?>
        <table class="pl-table">
            <thead>
                <tr><th>Teacher</th><th>Student</th><th>Course</th><th>Status</th><th>Assigned On</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php
            $stColor = ['pending'=>'#f59e0b','approved'=>'#10b981','rejected'=>'#ef4444','in_progress'=>'#0094d9','completed'=>'#8b5cf6'];
            $stBg    = ['pending'=>'rgba(245,158,11,.12)','approved'=>'rgba(16,185,129,.12)','rejected'=>'rgba(239,68,68,.12)','in_progress'=>'rgba(0,148,217,.12)','completed'=>'rgba(139,92,246,.12)'];
            $stLabel = ['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','in_progress'=>'In Progress','completed'=>'Completed'];
            while ($a = $assignments->fetch_assoc()):
                $st = $a['enrollment_status'] ?? 'pending';
            ?>
            <tr id="arow-<?= $a['id'] ?>">
                <td>
                    <div style="font-weight:700;font-size:13.5px;"><?= h($a['teacher_name']) ?></div>
                    <div style="font-size:11.5px;color:#9ca3af;">@<?= h($a['teacher_username']) ?> · <?= h($a['subject']) ?></div>
                </td>
                <td>
                    <div style="font-weight:700;font-size:13.5px;"><?= h($a['student_name']) ?></div>
                    <div style="font-size:11.5px;color:#9ca3af;"><?= h($a['student_phone']) ?></div>
                </td>
                <td><span class="pl-badge pl-badge-green"><?= h(mb_substr($a['course_interest'],0,28)) ?></span></td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:700;
                        background:<?= $stBg[$st]??$stBg['pending'] ?>;color:<?= $stColor[$st]??$stColor['pending'] ?>;">
                        <?= $stLabel[$st]??ucfirst($st) ?>
                    </span>
                </td>
                <td style="font-size:12px;color:#9ca3af;"><?= date('d M Y', strtotime($a['assigned_at'])) ?></td>
                <td>
                    <button onclick="unassign(<?= $a['id'] ?>)"
                        style="padding:6px 14px;background:rgba(239,68,68,.1);color:#ef4444;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-unlink me-1"></i>Remove
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="pl-empty">
            <i class="fas fa-link"></i>
            <p>No assignments yet. Assign a teacher to a student to start messaging.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Assign Modal -->
<div id="assignModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;padding:16px;">
<div style="background:#fff;border-radius:18px;width:100%;max-width:480px;">
    <div style="padding:20px 24px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
        <h4 style="margin:0;font-weight:800;color:#0d1b2a;"><i class="fas fa-link me-2" style="color:#8b5cf6;"></i>Assign Teacher to Student</h4>
        <button onclick="closeAssignModal()" style="border:none;background:#f5f5f5;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:16px;">✕</button>
    </div>
    <div style="padding:22px 24px;">
        <div style="margin-bottom:16px;">
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Select Teacher *</label>
            <select id="aTeacher" class="form-control">
                <option value="">— Select Teacher —</option>
                <?php
                $teachers->data_seek(0);
                while ($t = $teachers->fetch_assoc()): ?>
                <option value="<?= $t['id'] ?>"><?= h($t['full_name']) ?> (@<?= h($t['username']) ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div style="margin-bottom:16px;">
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Select Student *</label>
            <select id="aEnrollment" class="form-control">
                <option value="">— Select Student —</option>
                <?php
                $enrollments->data_seek(0);
                while ($e = $enrollments->fetch_assoc()): ?>
                <option value="<?= $e['id'] ?>"><?= h($e['full_name']) ?> — <?= h($e['course_interest']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:12px 14px;font-size:13px;color:#0369a1;margin-bottom:18px;">
            <i class="fas fa-info-circle me-1"></i>
            Once assigned, teacher and student can message each other from their portals.
        </div>
        <div id="aMsg" style="display:none;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;"></div>
        <div style="display:flex;gap:10px;">
            <button onclick="doAssign()" style="flex:1;padding:12px;background:#8b5cf6;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;">
                <i class="fas fa-link me-1"></i>Assign
            </button>
            <button onclick="closeAssignModal()" style="flex:1;padding:12px;background:#f0f0f0;color:#374151;border:none;border-radius:10px;font-weight:700;cursor:pointer;">Cancel</button>
        </div>
    </div>
</div>
</div>

<script>
function openAssignModal() { document.getElementById('assignModal').style.display='flex'; }
function closeAssignModal() { document.getElementById('assignModal').style.display='none'; }

function doAssign() {
    const teacher    = document.getElementById('aTeacher').value;
    const enrollment = document.getElementById('aEnrollment').value;
    if (!teacher || !enrollment) { showAMsg('Select both teacher and student.','#ef4444'); return; }
    const fd = new FormData();
    fd.append('action',        'assign');
    fd.append('teacher_id',    teacher);
    fd.append('enrollment_id', enrollment);
    fetch('teacher_assignments.php', { method:'POST', body:fd })
        .then(r=>r.json())
        .then(data => {
            if (data.success) { closeAssignModal(); location.reload(); }
            else showAMsg(data.message||'Error.','#ef4444');
        });
}

function unassign(id) {
    if (!confirm('Remove this teacher-student assignment?')) return;
    const fd = new FormData();
    fd.append('action','unassign');
    fd.append('id', id);
    fetch('teacher_assignments.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data => { if(data.success) document.getElementById('arow-'+id).remove(); });
}

function showAMsg(text,color) {
    const el=document.getElementById('aMsg');
    el.textContent=text; el.style.color=color;
    el.style.background=color+'15'; el.style.border='1px solid '+color+'40';
    el.style.display='block';
}

document.getElementById('assignModal').addEventListener('click',function(e){
    if(e.target===this) closeAssignModal();
});
</script>

<?php admin_foot(); ?>
