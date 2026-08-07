# Programmers Lab — Backend Documentation

## Project Overview
PHP + MySQL backend for the Programmers Lab website (Rawalpindi, Pakistan).  
Handles student registration/login, course enrollments, job applications, admin panel, teacher portal, and a real-time student–teacher chat system.

---

## Folder Structure

```
pl/
├── config.php              # DB config, security helpers, session init
├── env_loader.php          # Loads .env file into getenv()
├── .env                    # ⚠️ NOT committed — create from .env.example
├── .env.example            # Template — copy to .env and fill values
├── auth.php                # Student login/register page (tabbed UI)
├── user-login.php          # AJAX login handler (POST)
├── user-register.php       # AJAX register handler (POST)
├── user-logout.php         # Session destroy + CSRF-protected redirect
├── user-profile.php        # Student dashboard (enrollments, chat, jobs)
├── auth_status.php         # JSON endpoint — check login state
├── forgot-password.php     # Password reset Step 1 (email → OTP)
├── reset-password.php      # Password reset Step 2 (OTP → new password)
├── otp_helper.php          # OTP generate / send / verify lifecycle
├── mailer.php              # PHPMailer SMTP wrapper + all email templates
├── connect.php             # Contact form handler
├── enroll.php              # Enrollment form handler
├── job-apply.php           # Job application handler (with resume upload)
├── get_csrf.php            # Returns CSRF token for AJAX forms
├── .htaccess               # Apache security rules (HTTPS, headers, CORS)
│
├── api/
│   ├── courses.php         # Public course list (CORS-restricted)
│   ├── job_openings.php    # Public job openings (CORS-restricted)
│   └── notice.php          # Active site notice (CORS-restricted)
│
├── admin/
│   ├── login.php           # Admin login (Step 1: username + password)
│   ├── otp-verify.php      # Admin login (Step 2: 6-digit OTP via email)
│   ├── logout.php          # Admin session destroy
│   ├── index.php           # Dashboard
│   ├── messages.php        # Contact form submissions
│   ├── enrollments.php     # Course enrollments
│   ├── teachers.php        # Teacher account management
│   ├── teacher_assignments.php # Assign teachers to students
│   ├── courses.php         # Manage course list
│   ├── job_openings.php    # Manage job postings
│   ├── job_applications.php# View job applications
│   ├── broadcast_messages.php # Send announcements to students
│   ├── notices.php         # Manage site notices
│   ├── users.php           # Student account list
│   ├── certificates.php    # Certificate management
│   ├── admin_layout.php    # Shared sidebar/topbar
│   └── database_setup.sql  # Full DB schema — run once in phpMyAdmin
│
├── uploads/
│   ├── .htaccess           # Blocks ALL script execution + PDF direct access
│   └── resumes/            # Uploaded job application CVs
```

---

## Environment Setup

All credentials live in `.env` — **never in source code**.

```bash
# 1. Copy the example file
cp .env.example .env

# 2. Fill in your values
DB_HOST=localhost
DB_USER=your_db_user
DB_PASS=your_strong_password
DB_NAME=your_db_name
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your@gmail.com
MAIL_PASS=your_gmail_app_password
MAIL_FROM=your@gmail.com
```

> On Hostinger: set these as environment variables in the hosting panel instead of using a `.env` file.

---

## Admin Panel

```
Login:           /admin/login.php   (password → OTP email → dashboard)
Dashboard:       /admin/index.php
Messages:        /admin/messages.php
Enrollments:     /admin/enrollments.php
Teachers:        /admin/teachers.php
Job Applications:/admin/job_applications.php
```

**Create first admin user:**
1. Open `http://yourdomain.com/admin/create_admin.php`
2. Set username and password
3. **Delete the file immediately** after use

---

## Security Features

| Feature | Status |
|---------|--------|
| CSRF token on all POST forms (timing-safe `hash_equals`) | ✅ |
| Bcrypt passwords — cost 12, auto-rehash on login | ✅ |
| Two-factor authentication (OTP) for Admin + Teacher login | ✅ |
| OTP hashed in DB, 10-min expiry, 5-attempt lockout | ✅ |
| Prepared statements everywhere — no raw SQL concatenation | ✅ |
| Rate limiting on **both** admin and user login (5 attempts / 15 min) | ✅ |
| Session hardening — httponly, samesite=Strict, secure flag on HTTPS | ✅ |
| `session_regenerate_id(true)` before writing session on login/register | ✅ |
| Session timeout (30 min inactivity) | ✅ |
| Session fingerprinting (IP + User-Agent) — hijack detection | ✅ |
| Security headers on all PHP pages (X-Frame, nosniff, XSS, CSP) | ✅ |
| HSTS header via `.htaccess` | ✅ |
| HTTPS redirect enforced in `.htaccess` | ✅ |
| CORS restricted to own domain on all API endpoints | ✅ |
| Logout CSRF protection | ✅ |
| File upload — MIME + extension + size validation (PDF only for resumes) | ✅ |
| All script types blocked in `uploads/` — not just `.php` | ✅ |
| Direct PDF access blocked in `uploads/` | ✅ |
| Directory listing disabled | ✅ |
| `config.php` + `.env` direct access blocked via `.htaccess` | ✅ |
| Credentials in `.env` only — nothing hardcoded in source | ✅ |
| Production: `display_errors=0`, errors logged to file | ✅ |
| Activity logging for all admin actions | ✅ |
| XSS output escaping via `h()` helper on all dynamic output | ✅ |

---

## Database Tables

| Table | Purpose |
|-------|---------|
| `contact` | Contact form submissions |
| `enroll` | Course enrollment submissions |
| `admin_users` | Admin credentials (bcrypt) |
| `site_users` | Student accounts |
| `teacher_users` | Teacher accounts |
| `teacher_assignments` | Teacher ↔ student enrollment links |
| `chat_messages` | Student–teacher messages |
| `broadcast_messages` | Admin announcements to students |
| `broadcast_reads` | Read receipts for broadcasts |
| `job_openings` | Published job postings |
| `job_applications` | Job application submissions |
| `courses` | Course catalogue |
| `notices` | Site-wide notices |
| `otp_verifications` | OTP codes (hashed, expiring) |
| `login_attempts` | IP-based rate limit tracking |
| `admin_logs` | Admin activity audit trail |

---

## Local Development

Requirements: XAMPP (Apache + MySQL + PHP 8.1+)

```bash
# 1. Start XAMPP — Apache + MySQL
# 2. Import schema
#    phpMyAdmin → import admin/database_setup.sql
# 3. Create .env from template
cp .env.example .env   # then edit with local DB values
# 4. Create first admin
#    http://localhost/pl/admin/create_admin.php
# 5. Browse site
#    http://localhost/pl/
#    http://localhost/pl/admin/
```

---

## Live Deployment Checklist

### ✅ Required

- [ ] Set all credentials as environment variables on Hostinger (not in `.env` file)
- [ ] Confirm `.env` is NOT uploaded / committed
- [ ] Import `admin/database_setup.sql` in Hostinger phpMyAdmin
- [ ] Delete `admin/create_admin.php` after creating admin user
- [ ] Verify HTTPS is active and `.htaccess` HTTPS redirect is working
- [ ] Test OTP email delivery (SMTP app password must be active)

### ⚠️ Recommended

- [ ] Use a dedicated limited DB user (not root)
- [ ] Set up automated daily DB backups on Hostinger
- [ ] Monitor error log at `sys_get_temp_dir()/pl_error.log`
- [ ] Rotate Gmail app password every 6 months
