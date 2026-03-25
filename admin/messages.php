<?php
require_once __DIR__ . '/../config.php';
send_security_headers();
require_admin();

$conn     = get_db();
$messages = $conn->query("SELECT * FROM contact ORDER BY id DESC");

if ($messages === false) {
    error_log('messages.php query error: ' . $conn->error);
    $messages = null;
}

log_activity($conn, 'VIEW_MESSAGES', 'Viewed messages list');
$conn->close();

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Raleway:400,500,600,700" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; }
        .sidebar { background: linear-gradient(135deg, #f07b14 0%, #d56e00 100%); min-height: 100vh; display: flex; flex-direction: column; }
        .main-content { background: #f8f9fa; min-height: 100vh; }
        .nav-link.active { background: rgba(255,255,255,.15); border-radius: 8px; }
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
                    <a class="nav-link text-white active" href="messages.php"><i class="fas fa-envelope me-2"></i>Messages</a>
                    <a class="nav-link text-white" href="enrollments.php"><i class="fas fa-users me-2"></i>Enrollments</a>
                </nav>
                <div class="mt-auto pt-3" style="border-top:1px solid rgba(255,255,255,.25)">
                    <a class="nav-link text-white" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 main-content p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Messages</h2>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            <div class="card">
                <div class="card-body">
                    <?php if ($messages === null): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>Could not load messages. Please try again.
                    </div>
                    <?php elseif ($messages->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Subject</th><th>Date</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $messages->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$row['id']; ?></td>
                                <td><?php echo h($row['name']); ?></td>
                                <td><?php echo h($row['email']); ?></td>
                                <td><?php echo h($row['phone']); ?></td>
                                <td><?php echo h(mb_substr($row['subject'], 0, 40)); ?>…</td>
                                <td><?php echo h(date('M d, Y', strtotime($row['created_at']))); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewMessage(<?php echo (int)$row['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteMessage(<?php echo (int)$row['id']; ?>, this)">
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
                        <i class="fas fa-envelope fa-3x mb-3"></i>
                        <h5>No messages found</h5>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="messageContent"></div>
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

function viewMessage(id) {
    fetch(`get_message.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message); return; }
            const m = data.message;
            document.getElementById('messageContent').innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Name:</strong> ${esc(m.name)}<br>
                        <strong>Email:</strong> ${esc(m.email)}<br>
                        <strong>Phone:</strong> ${esc(m.phone)}</div>
                    <div class="col-md-6"><strong>Subject:</strong> ${esc(m.subject)}<br>
                        <strong>Date:</strong> ${esc(m.created_at)}</div>
                </div>
                <hr><strong>Message:</strong><p class="mt-2">${esc(m.message)}</p>`;
            new bootstrap.Modal(document.getElementById('messageModal')).show();
        });
}

function deleteMessage(id, btn) {
    if (!confirm('Delete this message?')) return;
    fetch('delete_message.php', {
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
