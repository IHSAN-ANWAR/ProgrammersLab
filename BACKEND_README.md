# Programmers Lab — Backend README

## Overview

PHP + MySQL backend for the Programmers Lab website. Handles contact form submissions, course enrollments, and a secured admin panel to manage both.

---

## Quick Setup

1. Start Apache + MySQL in XAMPP
2. Visit: `http://localhost/pl/admin/setup_database.php`
3. Log in at: `http://localhost/pl/admin/login.php`
   - Username: `admin` | Password: `Admin@1234`
4. Immediately change the password at: `http://localhost/pl/admin/reset_admin_password.php`
5. Delete `admin/setup_database.php` after setup (or it auto-prompts you)

---

## File Structure

```
pl/
├── config.php                    # Central config — DB, session, CSRF, helpers
├── connect.php                   # Contact form POST handler
├── enroll.php                    # Enrollment form POST handler
├── .htaccess                     # Blocks config.php, logs/, directory listing
├── uploads/                      # Uploaded files (qual docs, photos, CNICs)
│   └── .htaccess                 # Blocks PHP execution inside uploads
├── logs/                         # PHP error logs (web access blocked)
│   └── .htaccess
└── admin/
    ├── login.php                 # Login with rate limiting + bcrypt
    ├── logout.php                # Destroys session, clears cookie, logs action
    ├── index.php                 # Dashboard (message + enrollment counts)
    ├── messages.php              # View all contact messages
    ├── enrollments.php           # View all course enrollments
    ├── get_message.php           # Fetch single message (JSON, for modal)
    ├── get_enrollment.php        # Fetch single enrollment (JSON, for modal)
    ├── delete_message.php        # Delete message by ID (POST + CSRF)
    ├── delete_enrollment.php     # Delete enrollment by ID (POST + CSRF)
    ├── reset_admin_password.php  # Change admin password — self-deletes after use
    ├── setup_database.php        # One-time DB + table creation + admin seed
    └── database_setup.sql        # Raw SQL — import via phpMyAdmin or CLI
```

---

## config.php

Single source of truth for the entire backend. Every file starts with:
```php
require_once __DIR__ . '/../config.php'; // from admin/
require_once __DIR__ . '/config.php';    // from root
```

### Constants

| Constant | Default | Purpose |
|---|---|---|
| `DB_HOST` | `localhost` | MySQL host |
| `DB_USER` | `root` | MySQL user |
| `DB_PASS` | `` | MySQL password |
| `DB_NAME` | `programmerslab_db` | Database name |
| `SESSION_TIMEOUT` | `1800` | 30-min session expiry (seconds) |
| `MAX_FILE_SIZE` | `5242880` | 5MB upload limit (bytes) |
| `ALLOWED_MIME_TYPES` | array | JPEG, PNG, GIF, PDF |
| `MAX_LOGIN_ATTEMPTS` | `5` | Failed attempts before lockout |
| `LOCKOUT_TIME` | `900` | 15-min IP lockout (seconds) |
| `APP_ENV` | `development` | Set to `production` on live server |

### Helper Functions

| Function | Purpose |
|---|---|
| `get_db()` | Returns a MySQLi connection, dies safely on failure |
| `send_security_headers()` | Sends CSP, X-Frame-Options, XSS headers |
| `csrf_token()` | Generates + stores CSRF token in session |
| `csrf_verify()` | Validates CSRF token, returns 403 on mismatch |
| `session_init()` | Starts session with secure cookie settings |
| `check_session_timeout()` | Redirects to login after 30 min inactivity |
| `check_session_fingerprint()` | Detects session hijacking via IP + User Agent hash |
| `require_admin()` | Combines session init + timeout + fingerprint checks |
| `log_activity()` | Writes admin actions to `admin_logs` table |
| `validate_email()` | `filter_var` email format check |
| `validate_phone()` | Regex phone check (7–20 chars) |
| `h()` | `htmlspecialchars()` shorthand — use on all output |

---

## Database Schema

### `contact`
Messages from the contact form.

| Column | Type |
|---|---|
| id | INT PK AI |
| name | VARCHAR(255) |
| email | VARCHAR(255) |
| phone | VARCHAR(20) |
| subject | VARCHAR(500) |
| message | TEXT |
| created_at | TIMESTAMP |

### `enroll`
Full enrollment form submissions.

| Column | Type |
|---|---|
| id | INT PK AI |
| full_name | VARCHAR(255) |
| father_name | VARCHAR(255) |
| email | VARCHAR(255) |
| phone | VARCHAR(20) |
| gender | VARCHAR(20) |
| course_interest | VARCHAR(255) |
| study_mode | VARCHAR(50) |
| qualification_file | VARCHAR(255) |
| passport_photo | VARCHAR(255) |
| cnic_file | VARCHAR(255) |
| previous_experience | TEXT |
| reason_for_joining | TEXT |
| created_at | TIMESTAMP |

### `admin_users`
Admin accounts with bcrypt-hashed passwords.

| Column | Type |
|---|---|
| id | INT PK AI |
| username | VARCHAR(100) UNIQUE |
| password_hash | VARCHAR(255) |
| created_at | TIMESTAMP |

### `login_attempts`
Tracks failed login attempts per IP for rate limiting.

| Column | Type |
|---|---|
| id | INT PK AI |
| ip_address | VARCHAR(45) |
| attempted_at | TIMESTAMP |

### `admin_logs`
Audit trail of all admin actions.

| Column | Type |
|---|---|
| id | INT PK AI |
| admin_username | VARCHAR(100) |
| action | VARCHAR(255) |
| details | TEXT |
| ip_address | VARCHAR(45) |
| user_agent | VARCHAR(500) |
| created_at | TIMESTAMP |

---

## Security Implementation

### Prepared Statements
Every query uses `$conn->prepare()` + `bind_param()`. No raw `$_POST` or `$_GET` ever touches SQL.

### Admin Authentication
- Passwords stored as bcrypt hashes (`PASSWORD_BCRYPT`, cost 12)
- `password_verify()` used for login — no plain-text comparison
- Auto-rehash if cost factor is upgraded in future
- Session ID regenerated on login to prevent session fixation

### Session Security
- 30-minute inactivity timeout via `check_session_timeout()`
- Fingerprint = `sha256(User-Agent + IP)` — mismatch destroys session immediately
- Secure, HttpOnly, SameSite=Strict cookie flags set on session start

### CSRF Protection
- All POST forms include `<input type="hidden" name="csrf_token">`
- AJAX delete calls send token in request body
- Server verifies with `hash_equals()` (timing-safe)

### Rate Limiting
- Failed logins recorded in `login_attempts` table
- IP blocked for 15 minutes after 5 failures
- 500ms `usleep()` delay added on each failed attempt to slow brute force

### File Upload Security
- MIME type validated via PHP `finfo` — not file extension
- Max 5MB enforced before processing
- Files saved with `bin2hex(random_bytes(16))` random names
- PHP execution blocked inside `uploads/` via `.htaccess`

### Password Reset (`reset_admin_password.php`)
- Requires active admin session — unauthenticated requests redirect to login
- CSRF protected form
- Minimum 8-character password enforced
- Calls `@unlink(__FILE__)` to self-delete after a successful reset
- Action logged to `admin_logs`

### Security Headers
Sent on every admin page via `send_security_headers()`:
- `Content-Security-Policy`
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`

### Activity Logging
Every login, logout, password reset, dashboard view, messages view, enrollments view, and every delete is recorded in `admin_logs` with IP and user agent.

### Error Handling
- `APP_ENV = development` — errors shown on screen
- `APP_ENV = production` — errors logged to `logs/error.log`, nothing exposed to user
- All DB queries check return value and call `error_log()` on failure

---

## Production Checklist

- [ ] Set `APP_ENV` to `production` in `config.php`
- [ ] Set a strong `DB_PASS` in `config.php`
- [ ] Create a MySQL user with only `SELECT, INSERT, DELETE` — don't use `root`
- [ ] Delete `admin/setup_database.php` after first run
- [ ] Change default admin password via `reset_admin_password.php` (self-deletes after use)
- [ ] Move `config.php` above `public_html` and update `require_once` paths
- [ ] Enable HTTPS and set `'secure' => true` in session cookie params in `config.php`
- [ ] Confirm `logs/` and `uploads/` are not publicly accessible
