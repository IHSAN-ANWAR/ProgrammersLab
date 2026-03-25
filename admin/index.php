<?php
require_once __DIR__ . '/../config.php';
send_security_headers();
require_admin();

$conn = get_db();

// Count queries with error handling
$result = $conn->query("SELECT COUNT(*) AS c FROM contact");
$msg_count = $result ? (int)$result->fetch_assoc()['c'] : 0;
if (!$result) error_log('Dashboard msg count error: ' . $conn->error);

$result = $conn->query("SELECT COUNT(*) AS c FROM enroll");
$enr_count = $result ? (int)$result->fetch_assoc()['c'] : 0;
if (!$result) error_log('Dashboard enr count error: ' . $conn->error);

// Audit log this dashboard view
log_activity($conn, 'VIEW_DASHBOARD', 'Viewed admin dashboard');

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Raleway:400,500,600,700" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; }
        .sidebar { background: linear-gradient(135deg, #f07b14 0%, #d56e00 100%); min-height: 100vh; display: flex; flex-direction: column; }
        .main-content { background: #f8f9fa; min-height: 100vh; }
        .dashboard-card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,.1); transition: transform .3s; }
        .dashboard-card:hover { transform: translateY(-5px); }
        .bg-brand { background-color: #f07b14 !important; }
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
                    <a class="nav-link text-white active" href="index.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a class="nav-link text-white" href="messages.php"><i class="fas fa-envelope me-2"></i>Messages</a>
                    <a class="nav-link text-white" href="enrollments.php"><i class="fas fa-users me-2"></i>Enrollments</a>
                </nav>
                <div class="mt-auto pt-3" style="border-top:1px solid rgba(255,255,255,.25)">
                    <a class="nav-link text-white" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 main-content p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Dashboard</h2>
                <span class="text-muted">Welcome, <?php echo h($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card dashboard-card bg-brand text-white">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h3><?php echo (int)$msg_count; ?></h3>
                                <p class="mb-0">Total Messages</p>
                            </div>
                            <i class="fas fa-envelope fa-2x opacity-75"></i>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="messages.php" class="btn btn-light btn-sm">View Messages</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card dashboard-card bg-success text-white">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h3><?php echo (int)$enr_count; ?></h3>
                                <p class="mb-0">Course Enrollments</p>
                            </div>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="enrollments.php" class="btn btn-light btn-sm">View Enrollments</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
