# ProgrammersLab — Live Production Website

> **Live at [programmerslabs.com](https://www.programmerslabs.com)**
> Google-indexed · Active enrollments · PHP/MySQL backend · Secure admin panel

ProgrammersLab is a computer training institute in Rawalpindi/Islamabad offering professional IT short courses. This repository is the full source code of the live production website — not a demo, not a template.

---

## What This Is

- A **live, Google-indexed website** serving real students and enrollments
- A **PHP/MySQL backend** handling contact form submissions and course enrollments
- A **secure admin panel** for managing messages and enrollments
- **30+ course pages** individually optimized for local SEO (Rawalpindi/Islamabad)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Bootstrap, JavaScript |
| Backend | PHP 8 |
| Database | MySQL (InnoDB, utf8mb4) |
| Auth | bcrypt (cost 12) + session fingerprinting |
| Security | CSRF tokens, IP rate limiting, prepared statements |
| Server | Apache (.htaccess hardened) |

---

## Backend Features

### Contact Form
- AJAX submission — no page reload
- Server-side validation (email, phone, length)
- CSRF protected
- Saves to `contact` table in MySQL

### Course Enrollment System
- Full enrollment form: name, father's name, email, phone, gender, course, study mode
- File uploads: qualification doc, passport photo, CNIC
- MIME-type validation via PHP `finfo` (not file extension)
- Files stored with random `bin2hex` names, PHP execution blocked in `uploads/`

### Admin Panel (`/admin`)
- Login with bcrypt + IP-based rate limiting (5 attempts → 15 min lockout)
- Session fingerprinting — detects and kills hijacked sessions
- 30-minute inactivity timeout
- View, inspect (modal), and delete messages and enrollments
- Full audit log of every admin action (IP + user agent recorded)
- Password reset page that self-deletes after use

### Security
- Every query uses prepared statements — zero raw SQL
- CSRF tokens on all forms and AJAX calls, verified with `hash_equals()`
- Security headers on every page: CSP, X-Frame-Options, X-Content-Type-Options, XSS Protection
- `config.php` blocked from web access via `.htaccess`
- `uploads/` and `logs/` directories blocked from PHP execution and directory listing

---

## Project Structure

```
/
├── index.html                    # Homepage
├── config.php                    # DB credentials, session, CSRF, helpers (gitignored)
├── config.example.php            # Template — copy to config.php and fill in values
├── connect.php                   # Contact form POST handler
├── enroll.php                    # Enrollment form POST handler + file uploads
├── .htaccess                     # Blocks config.php, logs/, directory listing
│
├── admin/
│   ├── login.php                 # Rate-limited login
│   ├── index.php                 # Dashboard
│   ├── messages.php              # Contact message management
│   ├── enrollments.php           # Enrollment management
│   ├── setup_database.php        # One-time DB setup (delete after use)
│   └── database_setup.sql        # SQL schema
│
├── best-*.html                   # 30+ SEO-optimized course pages
├── css/ js/ img/ fonts/          # Frontend assets
├── uploads/                      # Student file uploads (web-blocked)
└── logs/                         # PHP error logs (web-blocked)
```

---

## Local Setup

```bash
# 1. Clone into XAMPP htdocs
git clone https://github.com/IHSAN-ANWAR/ProgrammersLab.git

# 2. Copy config and fill in your DB credentials
cp config.example.php config.php

# 3. Start Apache + MySQL in XAMPP

# 4. Run DB setup (one time only)
# Visit: http://localhost/ProgrammersLab/admin/setup_database.php

# 5. Login at:
# http://localhost/ProgrammersLab/admin/login.php
# Default: admin / Admin@1234  ← change immediately
```

> `config.php` is gitignored — it never gets committed. Real credentials stay local.

---

## Database

**Database:** `programmerslab_db`

| Table | Purpose |
|---|---|
| `contact` | Contact form submissions |
| `enroll` | Course enrollment records + file paths |
| `admin_users` | Admin accounts (bcrypt hashed) |
| `login_attempts` | Failed login tracking per IP |
| `admin_logs` | Full audit trail of admin actions |

---

## Production Checklist

- [ ] Set `APP_ENV = production` in `config.php`
- [ ] Use a limited MySQL user — not `root`
- [ ] Delete `admin/setup_database.php` after first run
- [ ] Change default admin password (reset page self-deletes after use)
- [ ] Enable HTTPS — set `'secure' => true` in session cookie params
- [ ] Move `config.php` above `public_html`

---

## Institute Info

**Programmers Lab** — Office No. 8, First Floor, Mian Plaza, Chandni Chowk, Rawalpindi
📞 +92 333 1912898 | ✉️ infoprogrammerslabs@gmail.com
🌐 [programmerslabs.com](https://www.programmerslabs.com)
