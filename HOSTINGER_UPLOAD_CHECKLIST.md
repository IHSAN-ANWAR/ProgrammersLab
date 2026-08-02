# 🚀 Hostinger Upload Checklist
## Sirf Naye/Changed Files — Poora site dobara upload mat karo

---

## ⚠️ STEP 1 — Pehle Database Update karo (SQL)

Hostinger phpMyAdmin kholo:
`https://hpanel.hostinger.com` → Databases → phpMyAdmin

Database select karo: `u896451268_programmerslab`
SQL tab → paste → Execute

```sql
-- ============================================================
-- Run this on Hostinger BEFORE uploading files
-- ============================================================

-- Teacher accounts
CREATE TABLE IF NOT EXISTS teacher_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(255) NOT NULL,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email         VARCHAR(255) DEFAULT '',
    phone         VARCHAR(20)  DEFAULT '',
    subject       VARCHAR(255) DEFAULT '',
    bio           TEXT,
    is_active     TINYINT(1)   DEFAULT 1,
    created_by    VARCHAR(100) DEFAULT 'admin',
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teacher ↔ Student assignments
CREATE TABLE IF NOT EXISTS teacher_assignments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id    INT NOT NULL,
    enrollment_id INT NOT NULL,
    assigned_by   VARCHAR(100) DEFAULT 'admin',
    assigned_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assignment (teacher_id, enrollment_id),
    FOREIGN KEY (teacher_id)    REFERENCES teacher_users(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enroll(id)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin broadcast messages
CREATE TABLE IF NOT EXISTS broadcast_messages (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(255) NOT NULL,
    body           TEXT         NOT NULL,
    msg_type       ENUM('info','warning','success','urgent') DEFAULT 'info',
    target_type    ENUM('all','course','student')            DEFAULT 'all',
    target_id      INT          DEFAULT NULL,
    target_course  VARCHAR(255) DEFAULT NULL,
    sent_by        VARCHAR(100) DEFAULT 'admin',
    is_active      TINYINT(1)   DEFAULT 1,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Read receipts
CREATE TABLE IF NOT EXISTS broadcast_reads (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id    INT NOT NULL,
    read_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_read (message_id, user_id),
    FOREIGN KEY (message_id) REFERENCES broadcast_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teacher ↔ Student chat
CREATE TABLE IF NOT EXISTS chat_messages (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id  INT NOT NULL,
    sender_type    ENUM('teacher','student') NOT NULL,
    sender_id      INT NOT NULL,
    msg_type       ENUM('text','link','video','file','question') DEFAULT 'text',
    body           TEXT NOT NULL,
    attachment_url VARCHAR(500) DEFAULT NULL,
    is_read        TINYINT(1)   DEFAULT 0,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES teacher_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes
CREATE INDEX IF NOT EXISTS idx_chat_assignment ON chat_messages(assignment_id);
CREATE INDEX IF NOT EXISTS idx_broadcast_type  ON broadcast_messages(target_type, is_active);
CREATE INDEX IF NOT EXISTS idx_ta_teacher      ON teacher_assignments(teacher_id);
CREATE INDEX IF NOT EXISTS idx_ta_enrollment   ON teacher_assignments(enrollment_id);
```

---

## 📁 STEP 2 — Files Upload karo (Hostinger File Manager ya FTP)

Root folder: `public_html/pl/` (ya jo aapka main folder hai)

### ✅ NEW FILES — Ye pehle se exist nahi karte, seedha upload karo

```
teacher-login.php          ← Teacher ka login page
teacher-portal.php         ← Teacher dashboard + chat
teacher-logout.php         ← Teacher logout
```

### ✅ NEW ADMIN FILES — admin/ folder mein upload karo

```
admin/teachers.php              ← Teacher accounts manage karo
admin/teacher_assignments.php   ← Teacher-student link
admin/broadcast_messages.php    ← Broadcast messages bhejo
```

### ⚠️ UPDATED FILES — Ye already live hain, REPLACE karo

| File | Kya badla |
|---|---|
| `user-profile.php` | Admin messages + teacher chat + security fixes |
| `admin/admin_layout.php` | Sidebar mein 3 naye links add hue |

---

## 📋 STEP 3 — Upload ke baad verify karo

Browser mein check karo:

1. **Admin Panel sidabar** → Teachers, Assignments, Broadcast links dikhe
   `https://yoursite.com/pl/admin/`

2. **Teacher login page** load ho
   `https://yoursite.com/pl/teacher-login.php`

3. **Student portal** mein Messages section dikhe (agar enrolled student se login karo)
   `https://yoursite.com/pl/user-profile.php`

4. **Admin → Teachers → Add Teacher** → ek test teacher banao → login test karo

---

## ❌ DO NOT UPLOAD these files

```
config.php                      ← Live pe alag credentials hain, mat upload karo
admin/add_messaging_teachers.sql ← SQL already run kar liya upar wala
MESSAGING_README.md             ← Development notes, not needed live
HOSTINGER_UPLOAD_CHECKLIST.md  ← Yeh file bhi nahi chahiye live pe
```

---

## 🔢 Total Files to Upload/Replace

| Action | Count | Files |
|---|---|---|
| NEW upload | 3 | teacher-login.php, teacher-portal.php, teacher-logout.php |
| NEW in admin/ | 3 | teachers.php, teacher_assignments.php, broadcast_messages.php |
| REPLACE existing | 2 | user-profile.php, admin/admin_layout.php |
| **Total** | **8** | |

---

## 💡 Quick Upload Order

```
1. phpMyAdmin → SQL run karo (Step 1)
2. admin/admin_layout.php   REPLACE
3. admin/teachers.php       NEW
4. admin/teacher_assignments.php  NEW
5. admin/broadcast_messages.php   NEW
6. user-profile.php         REPLACE
7. teacher-login.php        NEW
8. teacher-portal.php       NEW
9. teacher-logout.php       NEW
10. Browser pe test karo
```
