<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'update_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['new', 'reviewed', 'shortlisted', 'rejected'];
        if ($id && in_array($status, $allowed)) {
            $stmt = $conn->prepare("UPDATE job_applications SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $id);
            $ok = $stmt->execute();
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM job_applications WHERE id = ?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
    $conn->close();
    exit;
}

// Handle single view
$view = null;
if (isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM job_applications WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $view = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    // Mark as reviewed
    if ($view && $view['status'] === 'new') {
        $conn->query("UPDATE job_applications SET status='reviewed' WHERE id=$id");
        $view['status'] = 'reviewed';
    }
}

// Stats
$total       = (int)$conn->query("SELECT COUNT(*) AS c FROM job_applications")->fetch_assoc()['c'];
$new_count   = (int)$conn->query("SELECT COUNT(*) AS c FROM job_applications WHERE status='new'")->fetch_assoc()['c'];
$short_count = (int)$conn->query("SELECT COUNT(*) AS c FROM job_applications WHERE status='shortlisted'")->fetch_assoc()['c'];

// List
$applications = $conn->query("SELECT id, full_name, email, phone, position, job_type, degree, experience, status, created_at FROM job_applications ORDER BY id DESC");

log_activity($conn, 'VIEW_JOB_APPLICATIONS', 'Viewed job applications');
$conn->close();

$statusColors = [
    'new'         => ['bg' => 'rgba(240,123,20,0.1)',  'color' => '#c05c00'],
    'reviewed'    => ['bg' => 'rgba(59,130,246,0.1)',  'color' => '#1d4ed8'],
    'shortlisted' => ['bg' => 'rgba(16,185,129,0.1)',  'color' => '#065f46'],
    'rejected'    => ['bg' => 'rgba(239,68,68,0.1)',   'color' => '#b91c1c'],
];

admin_head('Job Applications', 'job_applications');
?>

<div class="pl-page-header">
    <div>
        <h1>Job Applications</h1>
        <div class="pl-breadcrumb">Career page form submissions</div>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(240,123,20,.1);color:#f07b14;"><i class="fas fa-file-alt"></i></div>
            <div><div class="stat-value"><?= $total ?></div><div class="stat-label">Total Applications</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(239,68,68,.1);color:#ef4444;"><i class="fas fa-bell"></i></div>
            <div><div class="stat-value"><?= $new_count ?></div><div class="stat-label">New (Unread)</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(16,185,129,.1);color:#10b981;"><i class="fas fa-user-check"></i></div>
            <div><div class="stat-value"><?= $short_count ?></div><div class="stat-label">Shortlisted</div></div>
        </div>
    </div>
</div>

<?php if ($view): ?>
<!-- ===== DETAIL VIEW ===== -->
<div class="pl-card mb-4">
    <div class="pl-card-header">
        <h5><i class="fas fa-user me-2" style="color:#f07b14"></i><?= h($view['full_name']) ?> — Application Detail</h5>
        <a href="job_applications.php" style="font-size:13px;color:#6b7280;text-decoration:none;">← Back to list</a>
    </div>
    <div class="pl-card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;">
            <div>
                <h6 style="color:#f07b14;font-weight:700;margin-bottom:12px;">Personal Info</h6>
                <table style="width:100%;font-size:13.5px;border-collapse:collapse;">
                    <tr><td style="padding:6px 0;color:#9ca3af;width:140px;">Full Name</td><td style="font-weight:600;"><?= h($view['full_name']) ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Father's Name</td><td><?= h($view['father_name'] ?: '—') ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Email</td><td><a href="mailto:<?= h($view['email']) ?>"><?= h($view['email']) ?></a></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Phone</td><td><a href="tel:<?= h($view['phone']) ?>"><?= h($view['phone']) ?></a></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">City</td><td><?= h($view['city'] ?: '—') ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Gender</td><td><?= h($view['gender'] ?: '—') ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Applied On</td><td><?= date('d M Y, h:i A', strtotime($view['created_at'])) ?></td></tr>
                </table>

                <h6 style="color:#0094d9;font-weight:700;margin:20px 0 12px;">Position</h6>
                <table style="width:100%;font-size:13.5px;border-collapse:collapse;">
                    <tr><td style="padding:6px 0;color:#9ca3af;width:140px;">Position</td><td style="font-weight:600;"><?= h($view['position']) ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Job Type</td><td><?= h($view['job_type'] ?: '—') ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Expected Salary</td><td><?= h($view['expected_salary'] ?: '—') ?></td></tr>
                </table>
            </div>
            <div>
                <h6 style="color:#10b981;font-weight:700;margin-bottom:12px;">Education</h6>
                <table style="width:100%;font-size:13.5px;border-collapse:collapse;">
                    <tr><td style="padding:6px 0;color:#9ca3af;width:140px;">Degree</td><td style="font-weight:600;"><?= h($view['degree']) ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Field</td><td><?= h($view['field_of_study'] ?: '—') ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Institute</td><td><?= h($view['institute'] ?: '—') ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Grad Year</td><td><?= h($view['graduation_year'] ?: '—') ?></td></tr>
                </table>

                <h6 style="color:#8b5cf6;font-weight:700;margin:20px 0 12px;">Experience & Skills</h6>
                <table style="width:100%;font-size:13.5px;border-collapse:collapse;">
                    <tr><td style="padding:6px 0;color:#9ca3af;width:140px;">Experience</td><td><?= h($view['experience'] ?: '—') ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Last Job Title</td><td><?= h($view['last_job_title'] ?: '—') ?></td></tr>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Skills</td><td><?= h($view['skills'] ?: '—') ?></td></tr>
                    <?php if ($view['portfolio_url']): ?>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Portfolio</td><td><a href="<?= h($view['portfolio_url']) ?>" target="_blank" style="color:#0094d9;"><?= h($view['portfolio_url']) ?></a></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($view['resume_path'])): ?>
                    <tr><td style="padding:6px 0;color:#9ca3af;">Resume / CV</td><td><a href="../<?= h($view['resume_path']) ?>" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(16,185,129,0.1);color:#065f46;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;"><i class="fas fa-download"></i> Download Resume</a></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <?php if ($view['previous_experience']): ?>
        <div style="margin-top:24px;">
            <h6 style="color:#374151;font-weight:700;margin-bottom:8px;">Previous Experience</h6>
            <p style="font-size:14px;color:#4b5563;line-height:1.7;background:#f9fafb;padding:16px;border-radius:10px;margin:0;"><?= nl2br(h($view['previous_experience'])) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($view['cover_letter']): ?>
        <div style="margin-top:20px;">
            <h6 style="color:#374151;font-weight:700;margin-bottom:8px;">Cover Letter</h6>
            <p style="font-size:14px;color:#4b5563;line-height:1.7;background:#f9fafb;padding:16px;border-radius:10px;margin:0;"><?= nl2br(h($view['cover_letter'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Status Update -->
        <div style="margin-top:28px;padding-top:20px;border-top:1px solid #f0f0f0;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span style="font-size:13px;font-weight:600;color:#374151;">Update Status:</span>
            <?php foreach(['new','reviewed','shortlisted','rejected'] as $s): ?>
            <button onclick="updateStatus(<?= $view['id'] ?>, '<?= $s ?>')"
                style="padding:7px 16px;border:none;border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;
                       background:<?= $statusColors[$s]['bg'] ?>;color:<?= $statusColors[$s]['color'] ?>;">
                <?= ucfirst($s) ?>
            </button>
            <?php endforeach; ?>
            <span id="statusMsg" style="font-size:13px;color:#10b981;display:none;">✓ Updated</span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== LIST TABLE ===== -->
<div class="pl-card">
    <div class="pl-card-header">
        <h5><i class="fas fa-list me-2" style="color:#f07b14"></i>All Applications</h5>
        <span style="font-size:13px;color:#9ca3af;"><?= $total ?> total</span>
    </div>
    <div class="pl-card-body p-0">
        <table class="pl-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Applicant</th>
                    <th>Position</th>
                    <th>Degree</th>
                    <th>Experience</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($applications && $applications->num_rows > 0): ?>
            <?php while ($a = $applications->fetch_assoc()): ?>
            <tr id="app-row-<?= $a['id'] ?>">
                <td style="color:#9ca3af;font-size:12px;"><?= $a['id'] ?></td>
                <td>
                    <strong style="display:block;"><?= h($a['full_name']) ?></strong>
                    <span style="color:#9ca3af;font-size:12px;"><?= h($a['email']) ?></span>
                </td>
                <td>
                    <span style="font-size:13px;font-weight:600;"><?= h($a['position']) ?></span>
                    <?php if ($a['job_type']): ?>
                    <span style="display:block;font-size:11px;color:#9ca3af;"><?= h($a['job_type']) ?></span>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px;"><?= h($a['degree']) ?></td>
                <td style="font-size:13px;color:#6b7280;"><?= h($a['experience'] ?: '—') ?></td>
                <td>
                    <span style="padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:700;
                                 background:<?= $statusColors[$a['status']]['bg'] ?>;
                                 color:<?= $statusColors[$a['status']]['color'] ?>;">
                        <?= ucfirst($a['status']) ?>
                        <?php if ($a['status'] === 'new'): ?> 🔴<?php endif; ?>
                    </span>
                </td>
                <td style="color:#9ca3af;font-size:12px;"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                <td>
                    <a href="job_applications.php?id=<?= $a['id'] ?>"
                        style="padding:5px 12px;background:rgba(0,148,217,0.1);color:#0094d9;border:none;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;margin-right:4px;display:inline-block;">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <button onclick="deleteApp(<?= $a['id'] ?>)"
                        style="padding:5px 12px;background:rgba(239,68,68,0.1);color:#ef4444;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="8" style="text-align:center;padding:48px;color:#9ca3af;">No applications yet</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('id', id);
    fd.append('status', status);
    fetch('job_applications.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const msg = document.getElementById('statusMsg');
                if (msg) { msg.style.display = 'inline'; setTimeout(()=>msg.style.display='none', 2000); }
            }
        });
}

function deleteApp(id) {
    if (!confirm('Delete this application? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('job_applications.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('app-row-' + id);
                if (row) row.remove();
            }
        });
}
</script>

<?php admin_foot(); ?>
