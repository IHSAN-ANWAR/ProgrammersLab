<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

$msg_count = (int)($conn->query("SELECT COUNT(*) AS c FROM contact")->fetch_assoc()['c'] ?? 0);
$enr_count = (int)($conn->query("SELECT COUNT(*) AS c FROM enroll")->fetch_assoc()['c']  ?? 0);

// Recent 5 enrollments
$recent_enroll = $conn->query("SELECT full_name, course_interest, created_at FROM enroll ORDER BY id DESC LIMIT 5");
// Recent 5 messages
$recent_msgs   = $conn->query("SELECT name, subject, created_at FROM contact ORDER BY id DESC LIMIT 5");

log_activity($conn, 'VIEW_DASHBOARD', 'Viewed admin dashboard');
$conn->close();

admin_head('Dashboard', 'dashboard');
?>

<div class="pl-page-header">
    <div>
        <h1>Dashboard</h1>
        <div class="pl-breadcrumb">Welcome back, <span><?= h($_SESSION['admin_username'] ?? 'Admin') ?></span></div>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6">
        <a href="messages.php" class="stat-card">
            <div class="stat-icon" style="background:rgba(240,123,20,.1);color:#f07b14;">
                <i class="fas fa-envelope"></i>
            </div>
            <div>
                <div class="stat-value"><?= $msg_count ?></div>
                <div class="stat-label">Total Messages</div>
                <div class="stat-trend up"><i class="fas fa-arrow-up"></i> View all</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6">
        <a href="enrollments.php" class="stat-card">
            <div class="stat-icon" style="background:rgba(16,185,129,.1);color:#10b981;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div>
                <div class="stat-value"><?= $enr_count ?></div>
                <div class="stat-label">Enrollments</div>
                <div class="stat-trend up"><i class="fas fa-arrow-up"></i> View all</div>
            </div>
        </a>
    </div>

</div>

<!-- Recent Tables -->
<div class="row g-3">
    <!-- Recent Enrollments -->
    <div class="col-lg-6">
        <div class="pl-card">
            <div class="pl-card-header">
                <h5><i class="fas fa-user-graduate me-2" style="color:#10b981"></i>Recent Enrollments</h5>
                <a href="enrollments.php" style="font-size:13px;color:#f07b14;text-decoration:none;font-weight:600;">View All →</a>
            </div>
            <div class="pl-card-body">
                <?php if ($recent_enroll && $recent_enroll->num_rows > 0): ?>
                <table class="pl-table">
                    <thead>
                        <tr><th>Student</th><th>Course</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($r = $recent_enroll->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= h($r['full_name']) ?></strong></td>
                        <td><span class="pl-badge pl-badge-green"><?= h(mb_substr($r['course_interest'], 0, 22)) ?></span></td>
                        <td style="color:#9ca3af;font-size:12px"><?= h(date('d M Y', strtotime($r['created_at']))) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="pl-empty"><i class="fas fa-user-graduate"></i><p>No enrollments yet</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Messages -->
    <div class="col-lg-6">
        <div class="pl-card">
            <div class="pl-card-header">
                <h5><i class="fas fa-envelope me-2" style="color:#f07b14"></i>Recent Messages</h5>
                <a href="messages.php" style="font-size:13px;color:#f07b14;text-decoration:none;font-weight:600;">View All →</a>
            </div>
            <div class="pl-card-body">
                <?php if ($recent_msgs && $recent_msgs->num_rows > 0): ?>
                <table class="pl-table">
                    <thead>
                        <tr><th>Name</th><th>Subject</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($r = $recent_msgs->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= h($r['name']) ?></strong></td>
                        <td style="color:#6b7280"><?= h(mb_substr($r['subject'], 0, 28)) ?>…</td>
                        <td style="color:#9ca3af;font-size:12px"><?= h(date('d M Y', strtotime($r['created_at']))) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="pl-empty"><i class="fas fa-envelope"></i><p>No messages yet</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php admin_foot(); ?>
