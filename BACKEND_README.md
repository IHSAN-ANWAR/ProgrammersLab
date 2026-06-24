# Programmers Lab — Backend Documentation

## Project Overview
PHP + MySQL backend for the Programmers Lab website.  
Handles contact form messages and course enrollment submissions with a secure admin panel.

---

## Folder Structure

```
pl/
├── config.php              # DB credentials, helpers, security functions
├── connect.php             # Contact form handler (POST → contact table)
├── enroll.php              # Enrollment form handler (POST → enroll table)
├── get_csrf.php            # Returns CSRF token for AJAX forms
├── .htaccess               # Apache security rules
├── uploads/                # Student uploaded files (qual, photo, cnic)
│
├── admin/
│   ├── login.php           # Admin login page
│   ├── logout.php          # Session destroy + redirect
│   ├── index.php           # Dashboard (message + enrollment counts)
│   ├── messages.php        # View/delete contact messages
│   ├── enrollments.php     # View/delete course enrollments
│   ├── get_message.php     # AJAX: fetch single message
│   ├── get_enrollment.php  # AJAX: fetch single enrollment
│   ├── delete_message.php  # AJAX: delete message (POST + CSRF)
│   ├── delete_enrollment.php # AJAX: delete enrollment (POST + CSRF)
│   ├── admin_layout.php    # Shared sidebar/topbar layout
│   ├── create_admin.php    # ⚠️ One-time admin user setup (DELETE after use)
│   └── database_setup.sql  # Full DB schema — run once in phpMyAdmin
```

---

## Admin Panel Access

```
Login:        /admin/login.php
Dashboard:    /admin/index.php
Messages:     /admin/messages.php
Enrollments:  /admin/enrollments.php
```

**Admin user kaise banayein:**
1. `http://yourdomain.com/admin/create_admin.php` open karo
2. Username aur password set karo
3. **File turant delete karo** after use

---

## Security Features

| Feature | Status |
|---------|--------|
| CSRF token on all forms | ✅ |
| Bcrypt password hashing (cost 12) | ✅ |
| Prepared statements (SQL injection safe) | ✅ |
| Session timeout (30 min) | ✅ |
| Session fingerprinting (hijack protection) | ✅ |
| Login rate limiting (5 attempts, 15 min lockout) | ✅ |
| Input validation (email, phone, length) | ✅ |
| File upload MIME validation (not extension) | ✅ |
| PHP execution blocked in uploads/ | ✅ |
| Directory listing disabled | ✅ |
| config.php direct access blocked | ✅ |
| Security headers (X-Frame, XSS, nosniff) | ✅ |

---

## Live Server Pe Jane Se Pehle — Checklist

### ✅ Must Do (Required)

- [ ] `config.php` mein `APP_ENV` → `'production'` kar do
- [ ] `config.php` mein `DB_USER` → root ki bajaye limited user banao
- [ ] `config.php` mein `DB_PASS` → strong password set karo
- [ ] `admin/create_admin.php` **DELETE** karo
- [ ] `test_forms.php` **DELETE** karo (agar exist kare)
- [ ] Database `programmerslab_db` hosting pe import karo (`database_setup.sql`)
- [ ] `uploads/` folder create karo aur chmod `750` set karo
- [ ] SSL certificate lagao (HTTPS)
- [ ] `.htaccess` mein HTTPS redirect uncomment karo

### ⚠️ Recommended

- [ ] Strong admin password use karo (12+ chars, mixed)
- [ ] Regular DB backups set karo
- [ ] Error logs monitor karo (`logs/error.log`)

---

## Database Tables

| Table | Purpose |
|-------|---------|
| `contact` | Contact form submissions |
| `enroll` | Course enrollment submissions |
| `admin_users` | Admin login credentials (bcrypt) |
| `login_attempts` | Rate limiting tracking |
| `admin_logs` | Admin activity audit trail |

---

## Forms Flow

```
Contact Form (prgrammers-lab-contact.html)
  → fetch get_csrf.php   (get token)
  → POST connect.php     (validate + insert contact table)
  → JSON response        (success/error shown to user)

Enrollment Form (enroll-form.html)
  → fetch get_csrf.php   (get token)
  → POST enroll.php      (validate + file upload + insert enroll table)
  → JSON response        (success/error shown to user)
```

---

## Local Development

Requirements: XAMPP (Apache + MySQL + PHP 8+)

```
1. XAMPP start karo (Apache + MySQL)
2. phpMyAdmin → database_setup.sql import karo
3. http://localhost/pl/admin/create_admin.php → admin user banao
4. http://localhost/pl/ → website test karo
5. http://localhost/pl/admin/ → admin panel
```
