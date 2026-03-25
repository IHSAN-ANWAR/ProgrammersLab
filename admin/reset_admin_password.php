<?php
/**
 * Admin Password Reset Utility
 * - Requires current admin session to run
 * - Auto-deletes itself after successful password change
 * - DELETE this file manually if you no longer need it
 */
require_once __DIR__ . '/../config.php';
send_security_headers();
session_init();

// Must be logged in as admin to use this
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $new_password     = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $conn = get_db();

        $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE username = 'admin'");
        $stmt->bind_param('s', $hash);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            log_activity($conn, 'PASSWORD_RESET', 'Admin password was reset');
            $stmt->close();
            $conn->close();

            // Self-delete after successful reset
            @unlink(__FILE__);

            $success = true;
        } else {
            $error = 'Failed to update password. Make sure the admin user exists.';
            $stmt->close();
            $conn->close();
        }
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: sans-serif; background: #f8f9fa; padding: 60px 0; }
        .card { border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,.1); }
        .btn-brand { background: #f07b14; border-color: #f07b14; color: #fff; }
        .btn-brand:hover { background: #d56e00; border-color: #d56e00; color: #fff; }
    </style>
</head>
<body>
<div class="container" style="max-width:460px">
    <div class="card p-4">
        <h4 class="mb-4">Reset Admin Password</h4>

        <?php if ($success): ?>
            <div class="alert alert-success">
                Password updated successfully. This file has been deleted.
            </div>
            <a href="index.php" class="btn btn-brand w-100">Back to Dashboard</a>

        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control"
                           minlength="8" required autocomplete="new-password">
                    <div class="form-text">Minimum 8 characters.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control"
                           minlength="8" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-brand w-100">Update Password</button>
            </form>

            <div class="mt-3 text-center">
                <a href="index.php" class="text-muted small">Cancel — Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
