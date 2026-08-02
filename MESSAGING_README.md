# 📚 Programmers Lab — Messaging & Teacher System

Ye document naye messaging aur teacher system ko explain karta hai.
Baad mein parh ke samajh sako ke kia kia cheez kaise kaam kar rahi hai.

> **Security Audit:** Sare issues senior code review ke baad fix ho gaye hain. Status table neeche hai.

---

## 🗄️ Database Tables (Naye)

### 1. `teacher_users`
Teacher accounts yahan store hote hain.
| Column | Kaam |
|---|---|
| id | Unique teacher ID |
| full_name | Teacher ka naam |
| username | Login username (admin deta hai) |
| password_hash | Bcrypt hashed password |
| email | Teacher email |
| phone | Teacher phone |
| subject | Expertise e.g. "Web Development" |
| is_active | 1=login kar sakta hai, 0=blocked |
| created_by | Kis admin ne banaya |

### 2. `teacher_assignments`
Konsa teacher konse student (enrollment) ka instructor hai.
| Column | Kaam |
|---|---|
| id | Assignment ID |
| teacher_id | teacher_users.id |
| enrollment_id | enroll.id |
| assigned_by | Admin username |

### 3. `broadcast_messages`
Admin ki taraf se students ko message.
| Column | Kaam |
|---|---|
| title | Message ka title |
| body | Message text |
| msg_type | info / warning / success / urgent |
| target_type | all / course / student |
| target_id | enrollment_id (agar student specific) |
| target_course | Course naam (agar course specific) |
| is_active | 1=visible, 0=hidden |

### 4. `broadcast_reads`
Student ne kaunsa message parha — tracking ke liye.
| Column | Kaam |
|---|---|
| message_id | broadcast_messages.id |
| user_id | site_users.id |
| read_at | Parhe ka waqt |

### 5. `chat_messages`
Teacher aur student ke beech real chat.
| Column | Kaam |
|---|---|
| assignment_id | teacher_assignments.id |
| sender_type | 'teacher' ya 'student' |
| sender_id | Teacher ya student ka ID |
| msg_type | text / link / video / file / question |
| body | Message text |
| attachment_url | Video link, drive link wagera |
| is_read | 0=unread, 1=read |

---

## 📁 Naye Files

### SQL Migration
```
admin/add_messaging_teachers.sql
```
Pehle yeh run karo phpMyAdmin mein. Saari tables banenge.

### Admin Panel Pages
| File | Kaam |
|---|---|
| `admin/teachers.php` | Teacher accounts manage karo (add/edit/delete) |
| `admin/teacher_assignments.php` | Teacher ko student se link karo |
| `admin/broadcast_messages.php` | Students ko message bhejo |

### Teacher Portal
| File | Kaam |
|---|---|
| `teacher-login.php` | Teacher ka login page |
| `teacher-portal.php` | Teacher dashboard — students list + chat |
| `teacher-logout.php` | Logout |

### Updated Files
| File | Kya badla |
|---|---|
| `user-profile.php` | Messages tab aur Teacher chat add hua |
| `admin/admin_layout.php` | Sidebar mein Teachers/Assignments/Broadcast links |

---

## 🔄 System Ka Flow

```
1. ADMIN: teacher-ko account banata hai
   → admin/teachers.php → "Add Teacher" button
   → Username + password dalo
   → Credentials teacher ko share karo

2. ADMIN: teacher ko student se assign karta hai
   → admin/teacher_assignments.php → "New Assignment"
   → Teacher select karo + Student select karo → Assign

3. ADMIN: students ko broadcast message bhejna
   → admin/broadcast_messages.php → "Send Message"
   → Title, message, type (info/urgent etc.) dalo
   → Target chuno: All Students / Specific Course / Specific Student

4. TEACHER: portal pe login karta hai
   → teacher-login.php (admin ne diya username/password)
   → teacher-portal.php pe apne students dikhte hain
   → Kisi student pe click karo → chat window khuljata hai
   → Message types: Text / Link / Video / Question / File

5. STUDENT: user-profile.php pe
   → "Messages from Admin" section: admin ke broadcast messages dikhte hain
   → "My Teachers" section: assigned teacher se chat kar sakta hai
   → Student teacher ko reply kar sakta hai
```

---

## 🚀 Setup Steps

### Step 1: SQL Run Karo
phpMyAdmin kholo → programmerslab_db select karo → SQL tab → paste karo:
```
admin/add_messaging_teachers.sql ka content
```
Execute karo.

### Step 2: Teacher Add Karo
Admin panel → Teachers → Add Teacher → naam, username, password bharo → Save

### Step 3: Student Assign Karo  
Admin panel → Assignments → New Assignment → Teacher + Student select → Assign

### Step 4: Broadcast Test Karo
Admin panel → Broadcast → Send Message → "All Students" → Send

### Step 5: Portals Test Karo
- Teacher login: `http://localhost/pl/teacher-login.php`
- Student portal: `http://localhost/pl/user-profile.php`

---

## 💡 Message Types (Teacher se Student)

| Type | Kab Use Karo |
|---|---|
| **Text** | Normal message, instruction |
| **Link** | Website link, article |
| **Video** | YouTube ya koi video link |
| **Question** | Assignment ya question bhejte waqt |
| **File** | Google Drive link ya koi file URL |

---

## 🔒 Security Features

- Teacher session timeout: 30 min (SESSION_TIMEOUT config se)
- Assignment verify hota hai — teacher sirf apne students ko message kar sakta hai
- Student sirf apne assigned teacher se chat kar sakta hai
- BCRYPT password hashing for teachers
- SQL injection prevention (prepared statements)

---

## 📊 Admin Broadcast Message Types

| Type | Color | Use Case |
|---|---|---|
| Info (ℹ️) | Blue | General updates, schedule info |
| Success (✅) | Green | Certificates ready, approvals |
| Warning (⚠️) | Yellow | Fee reminder, deadline |
| Urgent (🚨) | Red | Emergency announcement |

---

*Made by Kiro — Programmers Lab Project*

---

## ✅ Security Fix Log (Senior Review)

| # | Issue | File | Status |
|---|---|---|---|
| 1 | DB password in git | config.php | ✅ Already gitignored |
| 2 | Unauthorized `is_read` update in `get_chat` | user-profile.php | ✅ Fixed — auth check added |
| 3 | No auth on `get_msgs` in teacher portal | teacher-portal.php | ✅ Fixed — ownership verified |
| 4 | Dynamic SQL string concat in broadcast query | user-profile.php | ✅ Fixed — split into 2 prepared queries |
| 5 | Direct `$id` interpolation in toggle actions | teachers.php, broadcast_messages.php, teacher_assignments.php | ✅ Fixed — all prepared statements |
| 6 | No session fingerprint for teacher | teacher-portal.php | ✅ Fixed — fingerprint check added |
| 7 | No rate limiting on teacher login | teacher-login.php | ✅ Fixed — uses same login_attempts table |
| 8 | No message length limit | user-profile.php, teacher-portal.php | ✅ Fixed — 5000 char limit |
| 9 | Polling without AbortController | JS in both portals | ✅ Fixed — AbortController + tab-hidden pause |
| 10 | Complex conn lifecycle in user-profile | user-profile.php | ✅ Acceptable for solo dev — noted |
| 11 | Password plaintext in DOM | teachers.php | ✅ Fixed — masked with Show/Copy buttons |
| 12 | No URL validation on attachments | user-profile.php, teacher-portal.php | ✅ Fixed — https?:// regex check |
| 13 | God file anti-pattern | user-profile.php | ⚠️ Noted — acceptable now, split later |
| 14 | No LIMIT on chat history query | user-profile.php, teacher-portal.php | ✅ Fixed — LIMIT 100 |
| 15 | Direct interpolation with (int) cast | teacher_assignments.php | ✅ Fixed — full prepared statements |
