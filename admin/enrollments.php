<?php
require_once __DIR__ . '/../config.php';
send_security_headers();
require_admin();

$conn        = get_db();
$enrollments = $conn->query("SELECT * FROM enroll ORDER BY id DESC");
log_activity($conn, 'VIEW_ENROLLMENTS', 'Viewed enrollments list');
$conn->close();

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollments - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Raleway:400,500,600,700" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; }
        .sidebar { background: linear-gradient(135deg, #f07b14 0%, #d56e00 100%); min-height: 100vh; display: flex; flex-direction: column; }
        .main-content { background: #f8f9fa; min-height: 100vh; }
        .nav-link.active { background: rgba(255,255,255,.15); border-radius: 8px; }
        .badge.bg-brand { background-color: #f07b14 !important; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar p-0">
            <div class="p-3 d-flex flex-column" style="height:100vh">
                <h4 class="text-white mb-4"><i class="fas fa-code me-2"></i>Admin Panel</h4>
                <nav class="nav flex-column">
                    <a class="nav-link text-white" href="index.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a class="nav-link text-white" href="messages.php"><i class="fas fa-envelope me-2"></i>Messages</a>
                    <a class="nav-link text-white active" href="enrollments.php"><i class="fas fa-users me-2"></i>Enrollments</a>
                </nav>
                <div class="mt-auto pt-3" style="border-top:1px solid rgba(255,255,255,.25)">
                    <a class="nav-link text-white" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 main-content p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Course Enrollments</h2>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            <div class="card">
                <div class="card-body">
                    <?php if ($enrollments && $enrollments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Course</th><th>Mode</th><th>Date</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $enrollments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$row['id']; ?></td>
                                <td><?php echo h($row['full_name']); ?></td>
                                <td><?php echo h($row['email']); ?></td>
                                <td><?php echo h($row['phone']); ?></td>
                                <td><span class="badge bg-brand"><?php echo h($row['course_interest']); ?></span></td>
                                <td><?php echo h($row['study_mode']); ?></td>
                                <td><?php echo h(date('M d, Y', strtotime($row['created_at']))); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info text-white" onclick="viewEnrollment(<?php echo (int)$row['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteEnrollment(<?php echo (int)$row['id']; ?>, this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h5>No enrollments found</h5>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enrollment Modal -->
<div class="modal fade" id="enrollmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enrollment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="enrollmentContent"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CSRF = <?php echo json_encode($csrf); ?>;

function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str ?? ''));
    return d.innerHTML;
}

function viewEnrollment(id) {
    fetch(`get_enrollment.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message); return; }
            const e = data.enrollment;
            document.getElementById('enrollmentContent').innerHTML = `
                <dl class="row">
                    <dt class="col-sm-4">Full Name</dt><dd class="col-sm-8">${esc(e.full_name)}</dd>
                    <dt class="col-sm-4">Father Name</dt><dd class="col-sm-8">${esc(e.father_name)}</dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">${esc(e.email)}</dd>
                    <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">${esc(e.phone)}</dd>
                    <dt class="col-sm-4">Gender</dt><dd class="col-sm-8">${esc(e.gender)}</dd>
                    <dt class="col-sm-4">Course</dt><dd class="col-sm-8">${esc(e.course_interest)}</dd>
                    <dt class="col-sm-4">Study Mode</dt><dd class="col-sm-8">${esc(e.study_mode)}</dd>
                    ${e.previous_experience ? `<dt class="col-sm-4">Experience</dt><dd class="col-sm-8">${esc(e.previous_experience)}</dd>` : ''}
                    ${e.reason_for_joining ? `<dt class="col-sm-4">Reason</dt><dd class="col-sm-8">${esc(e.reason_for_joining)}</dd>` : ''}
                    ${e.qualification_file ? `<dt class="col-sm-4">Qualification</dt><dd class="col-sm-8"><a href="../uploads/${esc(e.qualification_file)}" target="_blank" rel="noopener">View File</a></dd>` : ''}
                    ${e.passport_photo ? `<dt class="col-sm-4">Photo</dt><dd class="col-sm-8"><a href="../uploads/${esc(e.passport_photo)}" target="_blank" rel="noopener">View Photo</a></dd>` : ''}
                    ${e.cnic_file ? `<dt class="col-sm-4">CNIC</dt><dd class="col-sm-8"><a href="../uploads/${esc(e.cnic_file)}" target="_blank" rel="noopener">View CNIC</a></dd>` : ''}
                    <dt class="col-sm-4">Enrolled On</dt><dd class="col-sm-8">${esc(e.created_at)}</dd>
                </dl>`;
            new bootstrap.Modal(document.getElementById('enrollmentModal')).show();
        });
}

function deleteEnrollment(id, btn) {
    if (!confirm('Delete this enrollment?')) return;
    fetch('delete_enrollment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&csrf_token=${encodeURIComponent(CSRF)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) btn.closest('tr').remove();
        else alert(data.message);
    });
}
</script>
</body>
</html>
