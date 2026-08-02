<?php
/**
 * Shared layout helper for admin pages.
 * Usage:
 *   admin_head("Page Title");        — outputs <head> + sidebar open
 *   admin_foot();                     — outputs scripts + closing tags
 */

function admin_head(string $pageTitle, string $activePage = ''): void {
    $user = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
    $now  = date('D, d M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Programmers Lab Admin</title>
<meta name="robots" content="noindex,nofollow">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── Reset & Base ── */
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: 'Inter', sans-serif; background: #f0f2f5; color: #1a1a2e; }

/* ── Sidebar ── */
#sidebar {
    position: fixed; top: 0; left: 0; bottom: 0; width: 240px;
    background: #0d1b2a;
    display: flex; flex-direction: column;
    z-index: 1000;
    transition: transform 0.3s ease;
}
.sidebar-brand {
    padding: 22px 20px 18px;
    border-bottom: 1px solid rgba(255,255,255,.07);
    display: flex; align-items: center; gap: 10px;
}
.sidebar-brand .brand-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: #f07b14;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; color: #fff; flex-shrink: 0;
}
.sidebar-brand .brand-text { font-size: 14px; font-weight: 700; color: #fff; line-height: 1.3; }
.sidebar-brand .brand-text span { color: #f07b14; }
.sidebar-brand .brand-sub { font-size: 11px; color: rgba(255,255,255,.35); font-weight: 400; }

.sidebar-nav { padding: 16px 12px; flex: 1; }
.nav-section-label {
    font-size: 10px; font-weight: 700; letter-spacing: 1.2px;
    color: rgba(255,255,255,.25); text-transform: uppercase;
    padding: 8px 10px 6px; margin-top: 6px;
}
.sidebar-nav a {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 10px;
    color: rgba(255,255,255,.55); font-size: 13.5px; font-weight: 500;
    text-decoration: none; margin-bottom: 2px;
    transition: all 0.2s;
}
.sidebar-nav a:hover { background: rgba(255,255,255,.06); color: #fff; }
.sidebar-nav a.active { background: #f07b14; color: #fff; font-weight: 600; }
.sidebar-nav a.active i { color: #fff; }
.sidebar-nav a i { width: 18px; text-align: center; font-size: 13px; color: rgba(255,255,255,.35); transition: color 0.2s; }
.sidebar-nav a:hover i { color: rgba(255,255,255,.7); }

.sidebar-footer {
    padding: 14px 12px;
    border-top: 1px solid rgba(255,255,255,.07);
}
.sidebar-footer a {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 10px;
    color: rgba(255,255,255,.4); font-size: 13px; text-decoration: none;
    transition: all 0.2s;
}
.sidebar-footer a:hover { background: rgba(239,68,68,.12); color: #ef4444; }

/* ── Topbar ── */
#topbar {
    position: fixed; top: 0; left: 240px; right: 0; height: 64px;
    background: #fff; border-bottom: 1px solid #e8ecf0;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 28px; z-index: 999;
    box-shadow: 0 1px 0 #e8ecf0;
}
.topbar-left { display: flex; align-items: center; gap: 12px; }
.topbar-page-title { font-size: 17px; font-weight: 700; color: #0d1b2a; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.topbar-date { font-size: 12.5px; color: #94a3b8; font-weight: 500; }
.topbar-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #f07b14, #d96a00);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 13px; font-weight: 700;
    cursor: pointer;
}
.topbar-user { font-size: 13px; font-weight: 600; color: #374151; }

/* ── Main content ── */
#main {
    margin-left: 240px;
    padding: 88px 28px 40px;
    min-height: 100vh;
}

/* ── Stat cards ── */
.stat-card {
    background: #fff; border-radius: 16px; padding: 24px;
    border: 1px solid #eef0f4;
    display: flex; align-items: center; gap: 18px;
    transition: box-shadow 0.25s, transform 0.25s;
    text-decoration: none; color: inherit;
}
.stat-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,.08); transform: translateY(-2px); color: inherit; text-decoration: none; }
.stat-icon {
    width: 56px; height: 56px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
}
.stat-value { font-size: 28px; font-weight: 800; color: #0d1b2a; line-height: 1; }
.stat-label { font-size: 13px; color: #6b7280; font-weight: 500; margin-top: 4px; }
.stat-trend { font-size: 12px; font-weight: 600; margin-top: 6px; }
.stat-trend.up { color: #10b981; }

/* ── Page header ── */
.pl-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.pl-page-header h1 { font-size: 20px; font-weight: 800; margin: 0; color: #0d1b2a; }
.pl-breadcrumb { font-size: 13px; color: #9ca3af; margin-top: 3px; }
.pl-breadcrumb span { color: #f07b14; font-weight: 600; }

/* ── Cards ── */
.pl-card {
    background: #fff; border-radius: 16px;
    border: 1px solid #eef0f4;
    overflow: hidden;
}
.pl-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex; align-items: center; justify-content: space-between;
}
.pl-card-header h5 { margin: 0; font-size: 15px; font-weight: 700; color: #0d1b2a; }
.pl-card-body { padding: 0; }

/* ── Table ── */
.pl-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.pl-table thead th {
    padding: 13px 16px; text-align: left;
    font-size: 11px; font-weight: 700; letter-spacing: .6px;
    text-transform: uppercase; color: #9ca3af;
    background: #f9fafb; border-bottom: 1px solid #f0f0f0;
}
.pl-table tbody td { padding: 14px 16px; border-bottom: 1px solid #f7f8fa; vertical-align: middle; }
.pl-table tbody tr:last-child td { border-bottom: none; }
.pl-table tbody tr:hover td { background: #fafbfc; }

/* ── Badges ── */
.pl-badge {
    display: inline-flex; align-items: center;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11.5px; font-weight: 600;
}
.pl-badge-orange { background: rgba(240,123,20,.1); color: #c05c00; }
.pl-badge-blue   { background: rgba(59,130,246,.1);  color: #1d4ed8; }
.pl-badge-green  { background: rgba(16,185,129,.1);  color: #065f46; }
.pl-badge-purple { background: rgba(139,92,246,.1);  color: #6d28d9; }
/* ── Action buttons ── */
.pl-btn-icon {
    width: 32px; height: 32px; border-radius: 8px; border: none;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; cursor: pointer; transition: all 0.2s;
}
.pl-btn-view { background: rgba(59,130,246,.1); color: #3b82f6; }
.pl-btn-view:hover { background: #3b82f6; color: #fff; }
.pl-btn-del  { background: rgba(239,68,68,.1); color: #ef4444; }
.pl-btn-del:hover  { background: #ef4444; color: #fff; }

/* ── Empty state ── */
.pl-empty { text-align: center; padding: 60px 20px; color: #9ca3af; }
.pl-empty i { font-size: 40px; margin-bottom: 12px; display: block; opacity: .4; }
.pl-empty p { font-size: 14px; margin: 0; }

/* ── Modal tweaks ── */
.modal-content { border-radius: 16px; border: none; }
.modal-header { border-bottom: 1px solid #f0f0f0; padding: 20px 24px; }
.modal-title { font-weight: 700; font-size: 16px; }
.modal-body { padding: 24px; }
.detail-row { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f5f5f5; font-size: 13.5px; }
.detail-row:last-child { border-bottom: none; }
.detail-label { width: 140px; flex-shrink: 0; color: #6b7280; font-weight: 600; }
.detail-value { color: #0d1b2a; }

/* ── Responsive ── */
@media (max-width: 768px) {
    #sidebar { transform: translateX(-100%); }
    #sidebar.open { transform: translateX(0); }
    #topbar { left: 0; }
    #main { margin-left: 0; padding: 80px 16px 30px; }
}
</style>
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-code"></i></div>
        <div>
            <div class="brand-text"><span>Programmers</span> Lab</div>
            <div class="brand-sub">Admin Panel</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="index.php" class="<?= $activePage === 'dashboard'   ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <div class="nav-section-label">Manage</div>
        <a href="messages.php" class="<?= $activePage === 'messages'    ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i> Messages
        </a>
        <a href="enrollments.php" class="<?= $activePage === 'enrollments' ? 'active' : '' ?>">
            <i class="fas fa-user-graduate"></i> Enrollments
        </a>
        <a href="courses.php" class="<?= $activePage === 'courses' ? 'active' : '' ?>">
            <i class="fas fa-graduation-cap"></i> Courses
        </a>
        <a href="certificates.php" class="<?= $activePage === 'certificates' ? 'active' : '' ?>">
            <i class="fas fa-certificate"></i> Certificates
        </a>
        <a href="users.php" class="<?= $activePage === 'users' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Registered Users
        </a>
        <a href="job_applications.php" class="<?= $activePage === 'job_applications' ? 'active' : '' ?>">
            <i class="fas fa-briefcase"></i> Job Applications
        </a>
        <a href="job_openings.php" class="<?= $activePage === 'job_openings' ? 'active' : '' ?>">
            <i class="fas fa-folder-open"></i> Job Openings
        </a>
        <div class="nav-section-label">Site</div>
        <a href="notices.php" class="<?= $activePage === 'notices' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i> Notices
        </a>
        <div class="nav-section-label">Teachers & Messaging</div>
        <a href="teachers.php" class="<?= $activePage === 'teachers' ? 'active' : '' ?>">
            <i class="fas fa-chalkboard-teacher"></i> Teachers
        </a>
        <a href="teacher_assignments.php" class="<?= $activePage === 'teacher_assignments' ? 'active' : '' ?>">
            <i class="fas fa-link"></i> Assignments
        </a>
        <a href="broadcast_messages.php" class="<?= $activePage === 'broadcast_messages' ? 'active' : '' ?>">
            <i class="fas fa-paper-plane"></i> Broadcast
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>

<!-- Topbar -->
<header id="topbar">
    <div class="topbar-left">
        <div class="topbar-page-title"><?= htmlspecialchars($pageTitle) ?></div>
    </div>
    <div class="topbar-right">
        <span class="topbar-date"><?= $now ?></span>
        <span class="topbar-user"><?= $user ?></span>
        <div class="topbar-avatar"><?= strtoupper(substr($user, 0, 1)) ?></div>
    </div>
</header>

<!-- Main -->
<main id="main">
<?php
}

function admin_foot(): void {
?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggle  = document.getElementById('sidebarToggle');
    if (toggle && sidebar) toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
});
</script>
<?php
}
