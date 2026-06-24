# Programmers Lab — Complete Project Documentation

> IT Training Institute & Software House — Rawalpindi, Pakistan  
> Website + Admin Panel complete guide

---

## 📌 Table of Contents

1. [Project Overview](#project-overview)
2. [Tech Stack](#tech-stack)
3. [Full Folder Structure](#full-folder-structure)
4. [File Connections Map](#file-connections-map)
5. [How Each Flow Works](#how-each-flow-works)
6. [Database Schema](#database-schema)
7. [Admin Panel Guide](#admin-panel-guide)
8. [CSS Files — Kaunsi File Kahan Use Hoti Hai](#css-files)
9. [Images & Assets](#images--assets)
10. [Security Summary](#security-summary)
11. [Live Deployment Checklist](#live-deployment-checklist)
12. [Quick Reference — All Links](#quick-reference--all-links)

---

## Project Overview

Programmers Lab ka yeh website ek **static HTML + PHP hybrid** project hai:

- **Frontend:** Pure HTML pages (no framework)
- **Backend:** PHP + MySQL (forms, admin panel)
- **Hosting Target:** Hostinger shared hosting
- **Local Dev:** XAMPP (Apache + MySQL + PHP)

Website 2 main kaam karti hai:
1. **Students ko courses show karna** aur enrollment lena
2. **Contact messages receive karna** — admin panel se manage karna


---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Bootstrap 4/5 |
| Backend | PHP 8+ |
| Database | MySQL (via MySQLi) |
| CSS Framework | Bootstrap + Custom CSS |
| Icons | Font Awesome 4 + Font Awesome 6 |
| Fonts | Raleway (Google), Inter (Google) |
| JS Libraries | jQuery 3.2.1, Owl Carousel, MixItUp |
| Local Server | XAMPP |
| Live Hosting | Hostinger |
| Version Control | Git |

---

## Full Folder Structure

```
pl/                                  ← Root (public_html on Hostinger)
│
├── 📄 index.html                    ← Homepage (main landing page)
├── 📄 config.php                    ← DB config + all helper functions
├── 📄 connect.php                   ← Contact form POST handler
├── 📄 enroll.php                    ← Enrollment form POST handler
├── 📄 get_csrf.php                  ← AJAX CSRF token endpoint
├── 📄 .htaccess                     ← Apache security rules
├── 📄 robots.txt                    ← SEO crawler rules
├── 📄 sitemap.xml                   ← SEO sitemap
│
├── 📄 prgrammers-lab-contact.html   ← Contact page (with form)
├── 📄 enroll-form.html              ← Enrollment form page
├── 📄 services.html                 ← Services page
├── 📄 career.html                   ← Career/jobs page
├── 📄 blog.html                     ← Blog listing
├── 📄 faqs.html                     ← FAQs page
├── 📄 internships-rawalpindi.html   ← Internships page
├── 📄 page-404.html                 ← 404 error page
│
├── 📁 Course Category Pages
│   ├── best-computer-courses-rawalpidni.html        ← All courses listing
│   ├── best-web-development-courses-in-rawalpindi.html
│   ├── best-mobile-app-development-courses-in-rawalpindi.html
│   ├── best-designing-courses-rawalpidni.html
│   ├── best-digital-marketing-course-rawalpidni.html
│   ├── best-programming-courses-institute-in-rawalpidni-islamabad.html
│   ├── best-freelancing-institute-in-rawalpindi-islamabd.html
│   └── best-database-courses-institute-in-rawalpidni-islamabad.html
│
├── 📁 Individual Course Pages (30+)
│   ├── best-institute-full-stack-web-development-course-rawalpindi-islamabad.html
│   ├── best-institute-for-front-end-web-development-course-rawalpidni.html
│   ├── best-institute-react-js-course-rawalpidni.html
│   ├── best-institute-php-mysql-course-rawalpidni.html
│   ├── best-institute-for-asp.net-course-rawalpidni.html
│   ├── best-Java-course-institute-in-rawalpindi-islamabd.html
│   ├── best-institute-Cplus-plus-course-rawalpidni.html
│   ├── best-institute-python-course-rawalpidni .html
│   ├── best-Javascript-course-institute-in-rawalpindi-islamabd.html
│   ├── best-flutter-course-institute-in-rawalpindi-islamabd.html
│   ├── best-react-native-course-institute-rawalpindi.html
│   ├── best-institute-for-android-app-develiomnet-course-rawalpinidi-islamabad.html
│   ├── best-institute-graphics-designing-course-rawalpidni.html
│   ├── best-institute-canva-course-rawalpidni-islamabad.html
│   ├── best-institute-ui-ux-course-rawalpidni.html
│   ├── best-institute-digital-marketing-course-rawalpidni.html
│   ├── best-institute-seo-course-rawalpidni.html
│   ├── best-institute-social-media-marketing-course-rawalpidni.html
│   ├── best-institute-full-digital-marketing-course-rawalpidni.html
│   ├── best-institute-for-MS-office-course-rawalpidni.html
│   ├── best-institute-for-basic-it-course-in-rawalpidni-islamabad.html
│   ├── best-csharp-course-institute-in-rawalpindi-islamabd.html
│   ├── best-database-courses-institute-in-rawalpidni-islamabad.html
│   ├── best-MYSQL-course-institute-in-rawalpindi-islamabd.html
│   ├── best-sql-course-institute-rawalpidni-islamabad.html
│   ├── quality-assurance-course-rawalpidni.html
│   ├── video-editing-course-rawalpidni.html
│   └── wordpress.html
│
├── 📁 admin/                        ← Admin panel (password protected)
│   ├── login.php                    ← Login page
│   ├── logout.php                   ← Session destroy
│   ├── index.php                    ← Dashboard
│   ├── messages.php                 ← Contact messages list
│   ├── enrollments.php              ← Enrollments list
│   ├── get_message.php              ← AJAX: single message fetch
│   ├── get_enrollment.php           ← AJAX: single enrollment fetch
│   ├── delete_message.php           ← AJAX: delete message
│   ├── delete_enrollment.php        ← AJAX: delete enrollment
│   ├── admin_layout.php             ← Shared sidebar + topbar layout
│   └── database_setup.sql           ← Full DB schema (run once)
│
├── 📁 css/
│   ├── style.css                    ← Main global styles
│   ├── course-detail.css            ← Shared styles for all course pages
│   ├── extrastyles.css              ← Extra/override styles
│   ├── coursesstyles.css            ← Course listing page styles
│   ├── content-styles.css           ← Blog/content page styles
│   ├── caree.css                    ← Career page styles
│   ├── Faqss.css                    ← FAQ page styles
│   ├── bootstrap.min.css            ← Bootstrap 4 framework
│   ├── font-awesome.min.css         ← Font Awesome 4 icons
│   └── owl.carousel.css             ← Owl Carousel slider
│
├── 📁 js/
│   ├── main.js                      ← Custom JS (navbar, scroll, animations)
│   ├── jquery-3.2.1.min.js          ← jQuery library
│   ├── bootstrap.min.js             ← Bootstrap JS
│   ├── owl.carousel.min.js          ← Carousel/slider
│   ├── mixitup.min.js               ← Course filter animations
│   ├── Faqs.js                      ← FAQ accordion JS
│   ├── circle-progress.min.js       ← Progress circle animations
│   └── map.js                       ← Google Maps helper
│
├── 📁 img/                          ← General images
├── 📁 courses-images/               ← Course category images
├── 📁 Best-Computer-Institute-.../  ← SEO-named course images
├── 📁 uploads/                      ← Student uploaded files (form submissions)
├── 📁 logs/                         ← PHP error logs
├── 📁 fonts/                        ← Custom fonts (Futura)
└── 📁 icon-fonts/                   ← Font Awesome font files
```


---

## File Connections Map

Yeh diagram dikhata hai kaunsi file kis file se connected hai:

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (HTML Pages)                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  index.html ──────────────────────────────────────────────► enroll-form.html
│      │                                                      │
│      └──► best-computer-courses-rawalpidni.html             │
│                    │                                        │
│                    └──► [30+ individual course pages]       │
│                              │                              │
│                              └──► enroll-form.html ─────────┘
│                                        │
│  prgrammers-lab-contact.html           │
│      │                                 │
│      ▼                                 ▼
├──────────────────────────────────────────────────────────────┤
│                    PHP BACKEND                               │
├──────────────────────────────────────────────────────────────┤
│                                                             │
│  Contact Form                Enrollment Form                │
│  ─────────────               ─────────────────              │
│  HTML Form                   HTML Form                      │
│      │                           │                          │
│      ▼                           ▼                          │
│  get_csrf.php ◄──────────── get_csrf.php                    │
│      │                           │                          │
│      ▼                           ▼                          │
│  connect.php                 enroll.php                     │
│      │                           │                          │
│      └──────────┐   ┌────────────┘                          │
│                 ▼   ▼                                       │
│              config.php                                     │
│            (DB + helpers)                                   │
│                 │                                           │
│                 ▼                                           │
│           MySQL Database                                    │
│         programmerslab_db                                   │
│           │           │                                     │
│       contact        enroll                                 │
│       table          table                                  │
│                                                             │
├──────────────────────────────────────────────────────────────┤
│                    ADMIN PANEL                               │
├──────────────────────────────────────────────────────────────┤
│                                                             │
│  login.php                                                  │
│      │ (session set)                                        │
│      ▼                                                      │
│  admin_layout.php ◄─── index.php (dashboard)                │
│         │              messages.php                         │
│         │              enrollments.php                      │
│         │                   │                               │
│         └───────────────────┘                               │
│                             │                               │
│                    AJAX calls ──► get_message.php           │
│                             │    get_enrollment.php         │
│                             │    delete_message.php         │
│                             └──► delete_enrollment.php      │
│                                        │                    │
│                                   config.php                │
│                                        │                    │
│                                  MySQL Database             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## How Each Flow Works

### 1. Contact Form Flow

```
User fills form on:
  prgrammers-lab-contact.html

JavaScript steps:
  Step 1 → fetch('get_csrf.php')         ← CSRF token manga
  Step 2 → POST to connect.php           ← Form data + CSRF token bheja
  
connect.php kya karta hai:
  ✔ Method check (POST only)
  ✔ CSRF token verify
  ✔ Fields validate (name, email, phone, subject, message)
  ✔ Email format validate
  ✔ Phone format validate
  ✔ INSERT INTO contact table
  ✔ JSON response return karta hai {success: true/false}

User ko dikhai deta hai:
  ✅ Green success message
  ❌ Red error message (agar validation fail ya DB error)
```

### 2. Enrollment Form Flow

```
User fills form on:
  enroll-form.html

JavaScript steps:
  Step 1 → fetch('get_csrf.php')         ← CSRF token manga
  Step 2 → POST to enroll.php            ← FormData + files + CSRF token

enroll.php kya karta hai:
  ✔ Method check (POST only)
  ✔ CSRF token verify
  ✔ Required fields check (fullName, email, phone, courseInterest)
  ✔ Email + phone validate
  ✔ File upload handle karta hai (optional):
      - MIME type check (not extension)
      - Max 5MB per file
      - Saves to uploads/ with random filename
  ✔ INSERT INTO enroll table
  ✔ JSON response return karta hai

Files save hoti hain:
  uploads/[random_hex]_qual.jpg   ← qualification
  uploads/[random_hex]_photo.jpg  ← passport photo
  uploads/[random_hex]_cnic.pdf   ← CNIC
```

### 3. Admin Login Flow

```
Admin opens:
  admin/login.php

login.php kya karta hai:
  ✔ CSRF token verify
  ✔ IP se login attempts count karta hai (last 15 min)
  ✔ Agar 5+ attempts → lockout (15 min)
  ✔ Database se username fetch karta hai
  ✔ password_verify() se bcrypt check
  ✔ Session set karta hai:
      $_SESSION['admin_logged_in'] = true
      $_SESSION['admin_username']
      $_SESSION['fingerprint']     ← Browser + IP hash
      $_SESSION['last_activity']
  ✔ Redirect → index.php
```

### 4. Admin Panel Flow

```
Har admin page pe:
  require_admin() call hoti hai → config.php mein defined

require_admin() check karta hai:
  ✔ Session exist karta hai?
  ✔ 30 min se zyada idle? → logout
  ✔ Browser fingerprint match? → logout (hijack protection)

Dashboard (index.php):
  → Messages count
  → Enrollments count
  → Recent 5 entries of each

Messages (messages.php):
  → All contact table rows
  → Eye button → AJAX → get_message.php → modal
  → Trash button → AJAX → delete_message.php

Enrollments (enrollments.php):
  → All enroll table rows
  → Eye button → AJAX → get_enrollment.php → modal
  → Trash button → AJAX → delete_enrollment.php
```


---

## Database Schema

**Database Name:** `programmerslab_db`

### Table: `contact`
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT PK | Unique ID |
| name | VARCHAR(255) | Sender ka naam |
| email | VARCHAR(255) | Email address |
| phone | VARCHAR(20) | Phone number |
| subject | VARCHAR(500) | Subject (dropdown se) |
| message | TEXT | Message content |
| created_at | TIMESTAMP | Auto set on insert |

### Table: `enroll`
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT PK | Unique ID |
| full_name | VARCHAR(255) | Student ka naam |
| father_name | VARCHAR(255) | Optional |
| email | VARCHAR(255) | Email |
| phone | VARCHAR(20) | Phone |
| gender | VARCHAR(20) | Male/Female/Other |
| course_interest | VARCHAR(255) | Selected course |
| study_mode | VARCHAR(50) | Online / Onsite |
| qualification_file | VARCHAR(255) | uploads/ mein filename |
| passport_photo | VARCHAR(255) | uploads/ mein filename |
| cnic_file | VARCHAR(255) | uploads/ mein filename |
| previous_experience | TEXT | Optional |
| reason_for_joining | TEXT | Optional |
| created_at | TIMESTAMP | Auto set on insert |

### Table: `admin_users`
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT PK | Unique ID |
| username | VARCHAR(100) UNIQUE | Login username |
| password_hash | VARCHAR(255) | Bcrypt hash (cost 12) |
| created_at | TIMESTAMP | Account creation |

### Table: `login_attempts`
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT PK | Unique ID |
| ip_address | VARCHAR(45) | Attacker IP |
| attempted_at | TIMESTAMP | Attempt time |

### Table: `admin_logs`
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT PK | Unique ID |
| admin_username | VARCHAR(100) | Who did the action |
| action | VARCHAR(255) | e.g. LOGIN, DELETE_MESSAGE |
| details | TEXT | Extra info |
| ip_address | VARCHAR(45) | Admin IP |
| user_agent | VARCHAR(500) | Browser info |
| created_at | TIMESTAMP | When it happened |

---

## CSS Files

Kaunsi CSS file kahan use hoti hai:

| CSS File | Pages |
|----------|-------|
| `css/style.css` | index.html + most pages (global base) |
| `css/course-detail.css` | Saare individual course pages (navbar, footer, tiles, hero) |
| `css/extrastyles.css` | Contact page + some others |
| `css/coursesstyles.css` | Course category listing pages |
| `css/content-styles.css` | Blog, content-heavy pages |
| `css/caree.css` | career.html |
| `css/Faqss.css` | faqs.html |
| `css/bootstrap.min.css` | Har page (grid system) |
| `css/font-awesome.min.css` | Har page (icons) |
| `css/owl.carousel.css` | Slider/carousel wale pages |

---

## Images & Assets

| Folder | Contents |
|--------|---------|
| `img/` | Logo, favicon, team photos, background images |
| `img/courses/` | Course-specific images |
| `img/blog/` | Blog post images |
| `courses-images/` | Course category cover images |
| `Best-Computer-Institute-.../` | SEO-named individual course images |
| `uploads/` | Student uploaded files (runtime, gitignored) |
| `update images/` | Updated logo file |
| `fonts/` | Futura custom font |

---

## Security Summary

| Feature | File | Status |
|---------|------|--------|
| CSRF Token | config.php + get_csrf.php | ✅ All forms |
| Bcrypt Password | config.php | ✅ Cost 12 |
| Prepared Statements | All PHP files | ✅ SQL injection safe |
| Session Timeout | config.php | ✅ 30 minutes |
| Session Fingerprint | config.php | ✅ Hijack protection |
| Login Rate Limit | login.php | ✅ 5 attempts, 15 min lockout |
| File MIME Validation | enroll.php | ✅ Real type check |
| PHP blocked in uploads | .htaccess + uploads/.htaccess | ✅ |
| Directory listing off | .htaccess | ✅ |
| config.php blocked | .htaccess | ✅ Direct access denied |
| Security Headers | config.php | ✅ X-Frame, XSS, nosniff |
| Activity Logging | config.php | ✅ All admin actions logged |

---

## Live Deployment Checklist

### Step 1 — Hostinger Database
- [ ] hPanel → Databases → MySQL → New Database banao
- [ ] Username + Password note karo
- [ ] phpMyAdmin mein `admin/database_setup.sql` import karo

### Step 2 — config.php Update Karo
```php
define('DB_USER', 'u123456_username');   // Hostinger DB user
define('DB_PASS', 'StrongPassword123');  // Hostinger DB pass
define('DB_NAME', 'u123456_dbname');     // Hostinger DB name
define('APP_ENV', 'production');         // production karo
```

### Step 3 — Admin User Banao
phpMyAdmin mein SQL tab mein run karo:
```sql
-- Password hash PHP se generate karo: password_hash('yourpass', PASSWORD_BCRYPT, ['cost'=>12])
INSERT INTO admin_users (username, password_hash)
VALUES ('admin', '$2y$12$...');
```

### Step 4 — Files Upload
- File Manager ya FTP se `pl/` ka sara content → `public_html/` mein upload karo
- `uploads/` folder banao manually

### Step 5 — SSL + HTTPS
- hPanel → SSL → Force HTTPS enable karo
- `.htaccess` mein HTTPS redirect uncomment karo

### Step 6 — Verify
- Website open karo → form fill karo → admin mein check karo

---

## Quick Reference — All Links

### Local (XAMPP)
| Page | URL |
|------|-----|
| Homepage | http://localhost/pl/ |
| All Courses | http://localhost/pl/best-computer-courses-rawalpidni.html |
| Contact | http://localhost/pl/prgrammers-lab-contact.html |
| Enroll Form | http://localhost/pl/enroll-form.html |
| Admin Login | http://localhost/pl/admin/login.php |
| Admin Dashboard | http://localhost/pl/admin/index.php |
| Messages | http://localhost/pl/admin/messages.php |
| Enrollments | http://localhost/pl/admin/enrollments.php |

### Live (Hostinger)
| Page | URL |
|------|-----|
| Homepage | https://www.programmerslabs.com/ |
| Admin Login | https://www.programmerslabs.com/admin/login.php |

---

## Solid Detail Summary

```
┌─────────────────────────────────────────────────────────────────┐
│              PROGRAMMERS LAB — SYSTEM AT A GLANCE               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  VISITOR                                                        │
│     │                                                           │
│     ├──► Views courses (30+ HTML pages)                         │
│     │         └── Uses: css/course-detail.css                   │
│     │                   js/main.js                              │
│     │                   img/ + courses-images/                  │
│     │                                                           │
│     ├──► Fills Contact Form                                     │
│     │         └── HTML → get_csrf.php → connect.php             │
│     │                         └── MySQL: contact table          │
│     │                                                           │
│     └──► Fills Enrollment Form                                  │
│               └── HTML → get_csrf.php → enroll.php              │
│                               └── MySQL: enroll table           │
│                               └── File → uploads/ folder        │
│                                                                 │
│  ADMIN                                                          │
│     │                                                           │
│     ├──► login.php → Session → Dashboard                        │
│     │                                                           │
│     ├──► messages.php → contact table → View/Delete             │
│     │         └── get_message.php (AJAX view)                   │
│     │         └── delete_message.php (AJAX delete)              │
│     │                                                           │
│     └──► enrollments.php → enroll table → View/Delete           │
│               └── get_enrollment.php (AJAX view)                │
│               └── delete_enrollment.php (AJAX delete)           │
│                                                                 │
│  EVERY PHP FILE uses:                                           │
│     └── config.php                                              │
│               ├── get_db()         DB connection                │
│               ├── csrf_token()     CSRF generate                │
│               ├── csrf_verify()    CSRF check                   │
│               ├── session_init()   Session start                │
│               ├── require_admin()  Auth check                   │
│               ├── validate_email() Email check                  │
│               ├── validate_phone() Phone check                  │
│               ├── log_activity()   Audit log                    │
│               └── h()              XSS safe output              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

*Last Updated: June 2026 | Programmers Lab — Rawalpindi*
