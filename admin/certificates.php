<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

// Counts
$count_inprogress = (int)($conn->query("SELECT COUNT(*) c FROM enroll WHERE enrollment_status='in_progress'")->fetch_assoc()['c'] ?? 0);
$count_completed  = (int)($conn->query("SELECT COUNT(*) c FROM enroll WHERE enrollment_status='completed'")->fetch_assoc()['c'] ?? 0);

// Filter
$filter  = $_GET['status'] ?? 'in_progress';
if (!in_array($filter, ['in_progress','completed'])) $filter = 'in_progress';

$students = $conn->query(
    "SELECT id, full_name, phone, cnic_number, course_interest, study_mode,
            enrollment_status, created_at
     FROM enroll
     WHERE enrollment_status = '" . $conn->real_escape_string($filter) . "'
     ORDER BY id DESC"
);

log_activity($conn, 'VIEW_CERTIFICATES', 'Viewed certificates page');
$conn->close();

admin_head('Certificates', 'certificates');
?>

<div class="pl-page-header">
    <div>
        <h1>Certificates</h1>
        <div class="pl-breadcrumb">Students → <span>In Progress & Completed</span></div>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-6">
        <a href="?status=in_progress" style="text-decoration:none;">
        <div class="stat-card" style="border:2px solid <?= $filter==='in_progress' ? '#0094d9' : 'transparent' ?>;">
            <div class="stat-icon" style="background:rgba(0,148,217,.1);color:#0094d9;">
                <i class="fas fa-spinner"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#0094d9;"><?= $count_inprogress ?></div>
                <div class="stat-label">In Progress</div>
                <div class="stat-trend" style="color:#0094d9;">Currently enrolled</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-sm-6">
        <a href="?status=completed" style="text-decoration:none;">
        <div class="stat-card" style="border:2px solid <?= $filter==='completed' ? '#8b5cf6' : 'transparent' ?>;">
            <div class="stat-icon" style="background:rgba(139,92,246,.1);color:#8b5cf6;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#8b5cf6;"><?= $count_completed ?></div>
                <div class="stat-label">Completed</div>
                <div class="stat-trend" style="color:#8b5cf6;">Certificate issued</div>
            </div>
        </div>
        </a>
    </div>
</div>

<!-- Table -->
<div class="pl-card">
    <div class="pl-card-header">
        <h5>
            <?php if($filter==='in_progress'): ?>
            <i class="fas fa-spinner me-2" style="color:#0094d9"></i>Students In Progress
            <?php else: ?>
            <i class="fas fa-graduation-cap me-2" style="color:#8b5cf6"></i>Completed — Certificates Ready
            <?php endif; ?>
        </h5>
        <span style="font-size:13px;color:#9ca3af;"><?= $students->num_rows ?> student(s)</span>
    </div>
    <div class="pl-card-body p-0">
    <?php if ($students->num_rows > 0): ?>
    <table class="pl-table">
        <thead><tr>
            <th>Student</th>
            <th>CNIC</th>
            <th>Course</th>
            <th>Mode</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php while ($row = $students->fetch_assoc()):
            $isCompleted = $row['enrollment_status'] === 'completed';
        ?>
        <tr id="cert-row-<?= $row['id'] ?>">
            <td>
                <div style="display:flex;align-items:center;gap:9px;">
                    <div style="width:34px;height:34px;border-radius:50%;
                        background:<?= $isCompleted ? 'linear-gradient(135deg,#8b5cf6,#6d28d9)' : 'linear-gradient(135deg,#0094d9,#0070a8)' ?>;
                        display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;flex-shrink:0;">
                        <?= strtoupper(substr(h($row['full_name']),0,1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13.5px;"><?= h($row['full_name']) ?></div>
                        <div style="font-size:11.5px;color:#9ca3af;"><?= h($row['phone']) ?></div>
                    </div>
                </div>
            </td>
            <td style="font-family:monospace;font-size:12px;color:#374151;">
                <?= $row['cnic_number'] ? h($row['cnic_number']) : '<span style="color:#d1d5db;font-size:12px;">Not set</span>' ?>
            </td>
            <td><span class="pl-badge pl-badge-<?= $isCompleted ? 'purple' : 'blue' ?>" style="font-size:11.5px;"><?= h(mb_substr($row['course_interest'],0,26)) ?></span></td>
            <td style="font-size:12px;color:#6b7280;"><?= h($row['study_mode'] ?: '—') ?></td>
            <td style="font-size:12px;color:#9ca3af;white-space:nowrap;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
            <td>
                <?php if($isCompleted): ?>
                <span style="padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:700;background:rgba(139,92,246,.1);color:#8b5cf6;">
                    <i class="fas fa-graduation-cap me-1"></i>Completed
                </span>
                <?php else: ?>
                <span style="padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:700;background:rgba(0,148,217,.1);color:#0094d9;">
                    ▶ In Progress
                </span>
                <?php endif; ?>
            </td>
            <td>
                <div style="display:flex;gap:6px;align-items:center;">
                    <?php if(!$isCompleted): ?>
                    <button onclick="markCompleted(<?= $row['id'] ?>, this)"
                        style="padding:5px 12px;background:rgba(139,92,246,.1);color:#8b5cf6;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">
                        <i class="fas fa-graduation-cap me-1"></i>Mark Complete
                    </button>
                    <?php else: ?>
                    <span style="font-size:12px;color:#10b981;font-weight:600;"><i class="fas fa-check me-1"></i>Certificate Ready</span>
                    <?php endif; ?>
                    <button onclick="markInProgress(<?= $row['id'] ?>, this)"
                        style="padding:5px 10px;background:rgba(0,148,217,.1);color:#0094d9;border:none;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;"
                        title="Set back to In Progress">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="pl-empty">
        <i class="fas fa-graduation-cap"></i>
        <p>No students <?= $filter==='in_progress' ? 'currently in progress' : 'completed yet' ?></p>
        <a href="../admin/enrollments.php" style="color:#f07b14;font-weight:600;font-size:13px;">Go to Enrollments →</a>
    </div>
    <?php endif; ?>
    </div>
</div>

<script>
function markCompleted(id, btn) {
    if (!confirm('Mark this student as Completed? A certificate will be issued.')) return;
    updateCertStatus(id, 'completed', btn);
}
function markInProgress(id, btn) {
    updateCertStatus(id, 'in_progress', btn);
}
function updateCertStatus(id, status, btn) {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('id',     id);
    fd.append('status', status);
    fetch('enrollments.php', {method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(()=>alert('Network error.'));
}
</script>

<?php admin_foot(); ?>
