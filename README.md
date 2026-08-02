# Programmers Lab — Complete Project Documentation

> IT Training Institute & Software House — Rawalpindi, Pakistan  
> Website + Admin Panel + Student & Teacher Portal — Full Developer Guide

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Full Folder Structure](#3-full-folder-structure)
4. [File Connections Map](#4-file-connections-map)
5. [How Each Flow Works](#5-how-each-flow-works)
6. [Database Schema](#6-database-schema)
7. [Admin Panel Guide](#7-admin-panel-guide)
8. [Student Portal Guide](#8-student-portal-guide)
9. [Teacher Portal Guide](#9-teacher-portal-guide)
10. [CSS Files Reference](#10-css-files-reference)
11. [Security Summary](#11-security-summary)
12. [Local Development Setup](#12-local-development-setup)
13. [Live Deployment Checklist](#13-live-deployment-checklist)
14. [Quick Reference — All Links](#14-quick-reference--all-links)

---

## 1. Project Overview

Programmers Lab website ek **static HTML + PHP hybrid** project hai:

- **Frontend:** Pure HTML5 pages (no JS framework)
- **Backend:** PHP 8+ with MySQL — forms, admin panel, portals
- **Local Dev:** XAMPP (Apache + MySQL + PHP)
- **Hosting:** Hostinger shared hosting

**Website ke 4 main systems:**
1. Public website — students ko courses dikhana + enrollment lena
2. Admin Panel — sab kuch manage karna (enrollments, teachers, messages)
3. Student Portal — student apni courses, admin messages, teacher chat dekhe
4. Teacher Portal — teacher apne assigned students ko messages/links bheje

---

## 2. Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Bootstrap 4/5 |
| Backend | PHP 8+ |
| Database | MySQL (MySQLi, prepared statements) |
| Icons | Font Awesome 6 |
| Fonts | Inter, Raleway (Google Fonts) + Futura (local) |
| JS Libraries | jQuery, Owl Carousel, MixItUp |
| Local Server | XAMPP |
| Live Hosting | Hostinger |
| Version Control | Git + GitHub |

---

## 3. Full Folder Structure

```
pl/                                         ← Root (= public_html on Hostinger)
│
├── ── Core PHP ──
├── config.php                              ← DB config + all security helpers
├── connect.php                             ← Contact form POST handler
├── enroll.php                              ← Enrollment form POST handler
├── enroll-form.php                         ← Enrollment form with file uploads
├── get_csrf.php                            ← AJAX CSRF token endpoint
├── auth.php                                ← Student login/register page (combined)
├── auth_status.php                         ← AJAX: check if student logged in
├── user-login.php                          ← Student login POST handler
├── user-register.php                       ← Student register POST handler
├── user-logout.php                         ← Student session destroy
├── user-profile.php                        ← Student portal dashboard
├── user-session.php                        ← Session helper for student
├── student-portal.html                     ← Certificate verification page
├── student-lookup.php                      ← AJAX: CNIC lookup for certificate
├── teacher-login.php                       ← Teacher login page
├── teacher-portal.php                      ← Teacher portal dashboard + chat
├── teacher-logout.php                      ← Teacher session destroy
├── job-apply.php                           ← Job application POST handler
├── .htaccess                               ← Apache security + URL rules
├── robots.txt                              ← SEO crawler rules
├── sitemap.xml                             ← SEO sitemap
│
├── ── Static Pages ──
├── index.html                              ← Homepage
├── prgrammers-lab-contact.html             ← Contact page
├── enroll-form.html                        ← Enrollment form page
├── services.html                           ← Services page
├── career.html / programmerslab-jobs.html  ← Jobs / Career page
├── blog.html                               ← Blog listing
├── faqs.html                               ← FAQs
├── internships-rawalpindi.html             ← Internships page
├── page-404.html                           ← 404 error page
│
├── ── Course Pages (30+) ──
├── best-computer-courses-rawalpidni.html
├── best-web-development-courses-in-rawalpindi.html
├── [... all individual course pages ...]
│
├── admin/                                  ← Admin panel (session-protected)
│   ├── login.php                           ← Admin login (rate-limited, CSRF)
│   ├── logout.php                          ← Session destroy + redirect
│   ├── index.php                           ← Dashboard
│   ├── admin_layout.php                    ← Shared sidebar + topbar
│   ├── messages.php                        ← Contact messages
│   ├── enrollments.php                     ← Student enrollments
│   ├── courses.php                         ← Course management
│   ├── certificates.php                    ← Certificate status management
│   ├── users.php                           ← Registered site users
│   ├── notices.php                         ← Site-wide popup notices
│   ├── job_openings.php                    ← Job openings management
│   ├── job_applications.php                ← Job applications list
│   ├── teachers.php                        ← Teacher accounts management
│   ├── teacher_assignments.php             ← Assign teachers to students
│   ├── broadcast_messages.php              ← Send messages to students
│   ├── get_enrollment.php                  ← AJAX: fetch enrollment detail
│   ├── get_message.php                     ← AJAX: fetch message detail
│   ├── delete_enrollment.php               ← AJAX: delete enrollment
│   ├── delete_message.php                  ← AJAX: delete message
│   └── database_setup.sql                  ← Full DB schema (local)
│
├── api/
│   ├── courses.php                         ← Public API: course list
│   ├── job_openings.php                    ← Public API: job listings
│   └── notice.php                          ← Public API: active notice
│
├── css/ js/ img/ fonts/ icon-fonts/        ← Static assets
├── courses-images/                         ← Course cover images
├── uploads/                                ← Student files (runtime, gitignored)
└── logs/                                   ← PHP error logs (gitignored)
```

---

## 4. File Connections Map

```
PUBLIC WEBSITE
──────────────────────────────────────────────────────────
HTML Pages → enroll-form.html
  └──► get_csrf.php → enroll-form.php / enroll.php

contact page → get_csrf.php → connect.php → MySQL: contact

STUDENT PORTAL
──────────────────────────────────────────────────────────
auth.php (login/register UI)
  ├── user-login.php     → session set → user-profile.php
  └── user-register.php  → session set → user-profile.php

user-profile.php
  ├── Shows: enrolled courses, status, stats
  ├── Shows: broadcast messages from admin (read/unread)
  ├── Shows: teacher chat (per course assignment)
  ├── AJAX POST → get_chat     → chat_messages table
  └── AJAX POST → send_chat    → chat_messages table

student-portal.html (certificate check — no login needed)
  └──► student-lookup.php → enroll table (by CNIC)

TEACHER PORTAL
──────────────────────────────────────────────────────────
teacher-login.php → session set → teacher-portal.php

teacher-portal.php
  ├── Shows: assigned students list
  ├── Chat window per student
  ├── AJAX POST → send_msg  → chat_messages table
  └── AJAX POST → get_msgs  → chat_messages table

ADMIN PANEL
──────────────────────────────────────────────────────────
admin/login.php (CSRF + rate limit + bcrypt)
  └──► Session → admin/index.php (dashboard)

admin_layout.php (included by all admin pages)
  ├── enrollments.php       → enroll table
  ├── messages.php          → contact table
  ├── courses.php           → courses table
  ├── certificates.php      → enroll table (status update)
  ├── notices.php           → notices table
  ├── users.php             → site_users table
  ├── job_openings.php      → job_openings table
  ├── job_applications.php  → job_applications table
  ├── teachers.php          → teacher_users table
  ├── teacher_assignments.php → teacher_assignments table
  └── broadcast_messages.php → broadcast_messages table

ALL PHP → config.php
  ├── get_db()              DB connection (MySQLi)
  ├── h()                   XSS-safe output
  ├── csrf_token()          CSRF generate
  ├── csrf_verify()         CSRF validate
  ├── session_init()        Secure session
  ├── require_admin()       Admin auth guard
  ├── log_activity()        Audit log
  ├── validate_email()
  └── validate_phone()
```

---

## 5. How Each Flow Works

### Contact Form Flow
```
1. prgrammers-lab-contact.html
2. GET  get_csrf.php   → token receive
3. POST connect.php    → validate → INSERT contact → JSON response
```

### Enrollment Form Flow
```
1. enroll-form.html
2. GET  get_csrf.php      → token receive
3. POST enroll-form.php   → validate → file upload check → INSERT enroll → JSON
```

### Student Registration & Login
```
1. auth.php (login/register tabs)
2. POST user-register.php → validate → bcrypt password → INSERT site_users
   POST user-login.php    → verify hash → session set
3. Redirect → user-profile.php
```

### Student Portal — Broadcast Messages
```
Admin sends message (all / specific course / specific student)
  → broadcast_messages table

Student logs in → user-profile.php
  → Queries broadcast_messages WHERE target matches student's enrollments
  → Shows messages with read/unread status (colored badge)
  → FA icons used — info (blue), success (green), warning (yellow), urgent (red)
```

### Student Portal — Teacher Chat
```
Admin assigns teacher to student (teacher_assignments table)

Student sees "My Teachers" section on user-profile.php
  → Each assigned teacher has a chat tab
  → Student sends text message → chat_messages (sender_type='student')
  → Teacher reply appears in same window
  → Polling every 5 seconds (AbortController, pauses when tab hidden)
```

### Teacher Portal Flow
```
1. teacher-login.php (rate-limited, fingerprinted)
2. teacher-portal.php:
   - Left panel: list of assigned students
   - Right panel: chat window
   - Message types: Text / Link / Video / Question / File
   - Unread count badge per student
   - Auto-poll every 5s (AbortController used)
```

### Admin Broadcast Flow
```
1. admin/broadcast_messages.php → "Send Message" button
2. Select: Type (Info/Success/Warning/Urgent)
           Target (All / Specific Course / Specific Student)
3. Preview shown live
4. INSERT broadcast_messages
5. Student sees it on next portal load
```

### Teacher Assignment Flow
```
1. admin/teachers.php → Add Teacher (set username + password)
2. admin/teacher_assignments.php → New Assignment
   → Select teacher + select student → INSERT teacher_assignments
3. Teacher logs in → sees assigned students → chat opens
4. Student logs in → sees teacher under "My Teachers" → can reply
```

### Admin Login Flow
```
1. admin/login.php
2. IP-based rate limit check (login_attempts table, 5/15min)
3. bcrypt password_verify()
4. Session set with fingerprint hash(User-Agent + IP)
5. Redirect → index.php
```

---

## 6. Database Schema

**Local DB:** `programmerslab_db`  
**Live DB:** `u896451268_programmerslab`

### Original Tables

| Table | Purpose |
|-------|---------|
| `contact` | Contact form messages |
| `enroll` | Student enrollment applications |
| `admin_users` | Admin accounts |
| `login_attempts` | Rate limiting for admin + teacher login |
| `admin_logs` | Admin activity audit trail |
| `courses` | Course list (admin managed) |
| `notices` | Site-wide popup notices |
| `job_openings` | Job/career listings |
| `job_applications` | Job applications |
| `site_users` | Registered student accounts |

### New Tables (Messaging & Teacher System)

### `teacher_users`
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | |
| full_name | VARCHAR(255) | |
| username | VARCHAR(100) UNIQUE | Login ID |
| password_hash | VARCHAR(255) | bcrypt |
| email | VARCHAR(255) | |
| phone | VARCHAR(20) | |
| subject | VARCHAR(255) | e.g. Web Development |
| bio | TEXT | Optional |
| is_active | TINYINT(1) | 1=can login, 0=blocked |
| created_by | VARCHAR(100) | Admin username |
| created_at | TIMESTAMP | |

### `teacher_assignments`
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | |
| teacher_id | INT FK | → teacher_users.id |
| enrollment_id | INT FK | → enroll.id |
| assigned_by | VARCHAR(100) | Admin username |
| assigned_at | TIMESTAMP | |

### `broadcast_messages`
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | |
| title | VARCHAR(255) | |
| body | TEXT | |
| msg_type | ENUM | info / warning / success / urgent |
| target_type | ENUM | all / course / student |
| target_id | INT | enrollment_id (if student-specific) |
| target_course | VARCHAR(255) | course name (if course-specific) |
| sent_by | VARCHAR(100) | Admin username |
| is_active | TINYINT(1) | 1=visible, 0=hidden |
| created_at | TIMESTAMP | |

### `broadcast_reads`
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | |
| message_id | INT FK | → broadcast_messages.id |
| user_id | INT | site_users.id |
| read_at | TIMESTAMP | |

### `chat_messages`
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | |
| assignment_id | INT FK | → teacher_assignments.id |
| sender_type | ENUM | teacher / student |
| sender_id | INT | teacher_users.id OR site_users.id |
| msg_type | ENUM | text / link / video / file / question |
| body | TEXT | Max 5000 chars |
| attachment_url | VARCHAR(500) | Must be http/https |
| is_read | TINYINT(1) | 0=unread, 1=read |
| created_at | TIMESTAMP | |

---

## 7. Admin Panel Guide

| Page | URL | Kya Karta Hai |
|------|-----|---------------|
| Login | `/admin/login.php` | Admin login |
| Dashboard | `/admin/index.php` | Stats + recent entries |
| Messages | `/admin/messages.php` | Contact form messages |
| Enrollments | `/admin/enrollments.php` | Student enrollments + status |
| Courses | `/admin/courses.php` | Course list manage |
| Certificates | `/admin/certificates.php` | Mark courses completed |
| Notices | `/admin/notices.php` | Site-wide popup notices |
| Users | `/admin/users.php` | Registered student accounts |
| Job Openings | `/admin/job_openings.php` | Manage job listings |
| Job Applications | `/admin/job_applications.php` | View applications |
| **Teachers** | `/admin/teachers.php` | Add/edit teacher accounts |
| **Assignments** | `/admin/teacher_assignments.php` | Link teachers to students |
| **Broadcast** | `/admin/broadcast_messages.php` | Send messages to students |

---

## 8. Student Portal Guide

**URL:** `/user-profile.php`  
**Access:** Login required via `/auth.php`

**Features:**
- Dashboard with stats (total courses, active, completed, pending)
- My Courses — all enrolled courses with status
- **Messages from Admin** — broadcast messages (unread badge, color-coded by type)
- **My Teachers** — chat with assigned teacher per course
- Account info section

**Certificate Check** (no login needed):  
`/student-portal.html` — enter CNIC to see enrollment status + certificate

---

## 9. Teacher Portal Guide

**URL:** `/teacher-portal.php`  
**Access:** Login via `/teacher-login.php` (credentials from admin)

**Features:**
- Student list (left panel) — all assigned students with unread count
- Chat window (right panel) — full message history
- Message types: Text, Link, Video, Question, File/Doc
- Auto-refresh every 5 seconds (pauses when tab hidden)
- Session fingerprinting + rate-limited login

**Setup flow:**
1. Admin → Teachers → Add Teacher → set username + password
2. Admin → Assignments → New Assignment → link teacher to student
3. Teacher logs in → chats with students

---

## 10. CSS Files Reference

| CSS File | Used On |
|----------|---------|
| `css/style.css` | index.html + general pages |
| `css/course-detail.css` | All individual course pages |
| `css/coursesstyles.css` | Course category pages |
| `css/extrastyles.css` | Contact page + overrides |
| `css/content-styles.css` | Blog, content pages |
| `css/caree.css` | career.html |
| `css/Faqss.css` | faqs.html |
| `css/bootstrap.min.css` | All pages |
| `css/font-awesome.min.css` | All pages |
| `css/owl.carousel.css` | Slider pages |

---

## 11. Security Summary

| Feature | File | Status |
|---------|------|--------|
| CSRF Token (all forms) | config.php + get_csrf.php | ✅ |
| Bcrypt passwords (cost 12) | config.php | ✅ |
| Prepared statements everywhere | All PHP files | ✅ |
| Session timeout (30 min) | config.php | ✅ |
| Session fingerprinting | config.php, teacher-portal.php | ✅ |
| Admin login rate limiting | admin/login.php | ✅ 5/15min |
| Teacher login rate limiting | teacher-login.php | ✅ same table |
| Teacher session fingerprint | teacher-portal.php | ✅ |
| Chat ownership verification | user-profile.php, teacher-portal.php | ✅ |
| Message length limit | Both portals | ✅ 5000 chars |
| URL validation (attachments) | Both portals | ✅ http/https only |
| LIMIT on chat queries | Both portals | ✅ LIMIT 100 |
| AbortController on polling | JS — both portals | ✅ No stacked requests |
| File MIME validation | enroll.php | ✅ |
| PHP blocked in uploads/ | .htaccess | ✅ |
| Security headers (CSP, X-Frame etc.) | config.php | ✅ |
| Admin activity audit log | config.php | ✅ |
| XSS safe output h() | All PHP views | ✅ |

---

## 12. Local Development Setup

```
1. XAMPP → Apache + MySQL start karo
2. Project folder: C:\xampp\htdocs\pl\
3. phpMyAdmin → http://localhost/phpmyadmin
4. Database banao: programmerslab_db
5. Import: admin/database_setup.sql
6. Import: admin/add_messaging_teachers.sql   ← NEW
7. config.php auto-detects localhost → no change needed
8. Admin user banao:
   INSERT INTO admin_users (username, password_hash)
   VALUES ('admin', 'BCRYPT_HASH');
9. Website:        http://localhost/pl/
10. Admin:         http://localhost/pl/admin/login.php
11. Student portal: http://localhost/pl/user-profile.php
12. Teacher portal: http://localhost/pl/teacher-portal.php
```

---

## 13. Live Deployment Checklist

### Step 1 — Database (phpMyAdmin on Hostinger)
- [ ] `database_setup_hostinger.sql` already imported (existing tables)
- [ ] Run `admin/add_messaging_teachers.sql` to add new tables

### Step 2 — Upload Files (File Manager / FTP)
Upload these — everything except:
- `.git/` folder
- `logs/` folder content
- `uploads/` folder content (live files hain)
- `config.php` only if credentials changed

| New Files | Path |
|-----------|------|
| teacher-login.php | root |
| teacher-portal.php | root |
| teacher-logout.php | root |
| admin/teachers.php | admin/ |
| admin/teacher_assignments.php | admin/ |
| admin/broadcast_messages.php | admin/ |

| Updated Files | Change |
|---------------|--------|
| user-profile.php | Messaging + teacher chat + security fixes |
| admin/admin_layout.php | New sidebar links |

### Step 3 — Verify Live
- [ ] Admin panel → Teachers, Assignments, Broadcast links in sidebar
- [ ] `teacher-login.php` loads
- [ ] Student portal → Messages section visible
- [ ] Send a test broadcast → student sees it

---

## 14. Quick Reference — All Links

### Local

| Page | URL |
|------|-----|
| Homepage | http://localhost/pl/ |
| Enroll | http://localhost/pl/enroll-form.html |
| Contact | http://localhost/pl/prgrammers-lab-contact.html |
| Certificate Check | http://localhost/pl/student-portal.html |
| Student Login | http://localhost/pl/auth.php |
| Student Portal | http://localhost/pl/user-profile.php |
| Teacher Login | http://localhost/pl/teacher-login.php |
| Teacher Portal | http://localhost/pl/teacher-portal.php |
| Admin Login | http://localhost/pl/admin/login.php |
| Admin Dashboard | http://localhost/pl/admin/index.php |
| Teachers | http://localhost/pl/admin/teachers.php |
| Assignments | http://localhost/pl/admin/teacher_assignments.php |
| Broadcast | http://localhost/pl/admin/broadcast_messages.php |

### Live (Hostinger)

| Page | URL |
|------|-----|
| Homepage | https://www.programmerslabs.com/ |
| Student Portal | https://www.programmerslabs.com/user-profile.php |
| Teacher Login | https://www.programmerslabs.com/teacher-login.php |
| Admin Login | https://www.programmerslabs.com/admin/login.php |

---

*Last Updated: August 2026 | Programmers Lab — Rawalpindi, Pakistan*
