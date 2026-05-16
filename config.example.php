<?php
// ============================================================
// COPY THIS FILE TO config.php AND FILL IN YOUR VALUES
// config.php is gitignored — never commit real credentials
// ============================================================

// --- Database ---
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'programmerslab_db');

// --- Session ---
define('SESSION_TIMEOUT', 1800); // 30 minutes

// --- File Uploads ---
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
]);

// --- Rate Limiting ---
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// --- Environment: change to 'production' on live server ---
define('APP_ENV', 'development');

// --- Error Handling ---
if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
