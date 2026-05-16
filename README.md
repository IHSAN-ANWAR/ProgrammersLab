# Programmers Lab — Website

A full-stack website for **Programmers Lab**, a computer institute in Rawalpindi/Islamabad offering IT short courses. Built on a static HTML foundation and extended with a PHP/MySQL backend, course enrollment system, contact form backend, and a secure admin panel.

---

## What Was Built / Updated

### 1. Course Cards (Enhanced UI)
- Course cards now show course image, fee badge, 5-star rating overlay, and a "Click for details" CTA
- Cards are filterable by category: All, CIT, Web, Mobile App, Design, Digital Marketing, Programming, Freelancing
- Each card links to its own dedicated course detail page

### 2. Course Enrollment Form (`enroll.php`)
Students can enroll directly from the website. The form collects:
- Full name, father's name, email, phone, gender
- Course interest, study mode
- Previous experience, reason for joining
- File uploads: qualification document, passport photo, CNIC

All data is saved to the `enroll` table in MySQL. Files are validated by MIME type (not extension) and capped at 5MB.

### 3. Contact Form Backend (`connect.php`)
The contact page (`prgrammers-lab-contact.html`) now submits via AJAX to `connect.php` which:
- Validates name, email, phone, subject, message
- Saves the submission to the `contact` table
- Returns a JSON response (success/error)
- Is CSRF protected

### 4. PHP/MySQL Backend (`config.php`)
Central configuration and helper layer powering the entire backend:
- Database connection (`programmerslab_db`)
- Session management with 30-min timeout and fingerprint hijack detection
- CSRF token generation and verification
- Security headers (CSP, X-Frame-Options, XSS protection)
- Input validation helpers (`validate_email`, `validate_phone`, `h()`)
- File upload constants (5MB limit, allowed MIME types)
- Rate limiting constants (5 attempts, 15-min lockout)
- Activity logging to `admin_logs` table

### 5. Admin Panel (`admin/`)
A full management interface for the institute to manage enrollments and messages.

| Page | Purpose |
|---|---|
| `login.php` | Secure login with bcrypt + rate limiting |
| `index.php` | Dashboard showing message and enrollment counts |
| `messages.php` | View, inspect (modal), and delete contact messages |
| `enrollments.php` | View, inspect (modal), and delete course enrollments |
| `get_message.php` | JSON endpoint — fetches single message for modal |
| `get_enrollment.php` | JSON endpoint — fetches single enrollment for modal |
| `delete_message.php` | CSRF-protected delete for messages |
| `delete_enrollment.php` | CSRF-protected delete for enrollments |
| `logout.php` | Destroys session, clears cookie, logs action |
| `reset_admin_password.php` | Change admin password — self-deletes after use |
| `setup_database.php` | One-time DB setup + admin user seed |
| `database_setup.sql` | Raw SQL schema (import via phpMyAdmin or CLI) |

---

## Database

Database name: `programmerslab_db`

| Table | Purpose |
|---|---|
| `contact` | Contact form submissions |
| `enroll` | Course enrollment records + uploaded file paths |
| `admin_users` | Admin accounts (bcrypt hashed passwords) |
| `login_attempts` | Tracks failed logins per IP for rate limiting |
| `admin_logs` | Full audit trail of all admin actions |

---

## Quick Setup (XAMPP / Local)

1. Start Apache + MySQL in XAMPP
2. Place the project folder in `htdocs/` (e.g. `htdocs/pl/`)
3. Visit: `http://localhost/pl/admin/setup_database.php`
4. Log in at: `http://localhost/pl/admin/login.php`
   - Username: `admin` | Password: `Admin@1234`
5. Change the password immediately at: `http://localhost/pl/admin/reset_admin_password.php`
6. Delete `admin/setup_database.php` after setup

---

## Project Structure

```
pl/
├── README.md
├── BACKEND_README.md             # Detailed backend/security docs
├── config.php                    # Central config — DB, session, CSRF, helpers
├── connect.php                   # Contact form POST handler
├── enroll.php                    # Enrollment form POST handler + file uploads
├── .htaccess                     # Blocks config.php, logs/, directory listing
├── uploads/                      # Student uploaded files (blocked from PHP exec)
├── logs/                         # PHP error logs (web access blocked)
│
├── admin/
│   ├── login.php
│   ├── logout.php
│   ├── index.php                 # Dashboard
│   ├── messages.php
│   ├── enrollments.php
│   ├── get_message.php
│   ├── get_enrollment.php
│   ├── delete_message.php
│   ├── delete_enrollment.php
│   ├── reset_admin_password.php
│   ├── setup_database.php
│   └── database_setup.sql
│
├── best-computer-courses-rawalpidni.html   # Course listing with enhanced cards
├── prgrammers-lab-contact.html             # Contact page with backend form
├── [course-detail-pages].html              # Individual course pages
└── [assets: css/, js/, img/, courses-images/]
```

---

## Security Highlights

- Prepared statements on every query — no SQL injection possible
- CSRF tokens on all forms and AJAX delete calls
- Bcrypt password hashing (cost 12) with `password_verify()`
- Session fingerprinting (IP + User Agent hash) to detect hijacking
- Login rate limiting — IP locked for 15 min after 5 failed attempts
- File uploads validated by MIME type via PHP `finfo`, not file extension
- PHP execution blocked inside `uploads/` via `.htaccess`
- Security headers sent on every page (CSP, X-Frame-Options, XSS, nosniff)
- All admin actions logged to `admin_logs` with IP and user agent
- `reset_admin_password.php` self-deletes after successful use

For full security and backend details see [BACKEND_README.md](BACKEND_README.md).

---

## Production Checklist

- [ ] Set `APP_ENV` to `production` in `config.php`
- [ ] Set a strong `DB_PASS` and use a limited MySQL user (not `root`)
- [ ] Delete `admin/setup_database.php` after first run
- [ ] Change default admin password (script self-deletes after use)
- [ ] Enable HTTPS and set `'secure' => true` in session cookie params
- [ ] Move `config.php` above `public_html` and update `require_once` paths
- [ ] Confirm `logs/` and `uploads/` are not publicly accessible
