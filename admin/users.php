<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

// Check table exists
$tbl = $conn->query("SHOW TABLES LIKE 'site_users'");
$table_exists = ($tbl && $tbl->num_rows > 0);

$users = null;
$total = 0;
if ($table_exists) {
    $users = $conn->query("SELECT id, full_name, email, phone, created_at FROM site_users ORDER BY id DESC");
    $total = $users ? $users->num_rows : 0;
}

log_activity($conn, 'VIEW_USERS', 'Viewed registered users');
$conn->close();

admin_head('Registered Users', 'users');
?>

<div class="pl-page-header">
    <div>
        <h1>Registered Users</h1>
        <div class="pl-breadcrumb">Students who registered on the website</div>
    </div>
    <div class="pl-badge pl-badge-blue" style="padding:8px 16px;font-size:13px;">
        <i class="fas fa-users me-1"></i> <?= $total ?> User<?= $total !== 1 ? 's' : '' ?>
    </div>
</div>

<?php if (!$table_exists): ?>
<div class="pl-card">
    <div class="pl-card-body" style="padding:40px;text-align:center;">
        <i class="fas fa-database" style="font-size:36px;color:#d1d5db;margin-bottom:14px;display:block;"></i>
        <p style="color:#6b7280;font-size:14px;">The <code>site_users</code> table does not exist yet.<br>Run the migration SQL to create it.</p>
    </div>
</div>
<?php else: ?>

<div class="pl-card">
    <div class="pl-card-header">
        <h5><i class="fas fa-users me-2" style="color:#0094d9"></i>All Registered Users</h5>
    </div>
    <div class="pl-card-body p-0">
    <?php if ($total > 0): ?>
    <table class="pl-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Registered On</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($u = $users->fetch_assoc()): ?>
        <tr>
            <td style="color:#9ca3af;font-size:12px;"><?= $u['id'] ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:9px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#0094d9,#0070a8);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0;">
                        <?= strtoupper(substr(h($u['full_name']),0,1)) ?>
                    </div>
                    <strong><?= h($u['full_name']) ?></strong>
                </div>
            </td>
            <td style="color:#6b7280;"><?= h($u['email']) ?></td>
            <td style="color:#6b7280;"><?= h($u['phone']) ?></td>
            <td style="color:#9ca3af;font-size:12px;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="pl-empty">
        <i class="fas fa-users"></i>
        <p>No registered users yet</p>
    </div>
    <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php admin_foot(); ?>
