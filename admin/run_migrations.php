<?php
/**
 * ONE-TIME Migration Script
 * Run once: yourdomain.com/admin/run_migrations.php
 * DELETE after running!
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$DB_HOST = 'localhost';
$DB_USER = 'u896451268_ProgrammersLab';
$DB_PASS = '@ProgrammersLab1';
$DB_NAME = 'u896451268_programmerslab';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) die('DB Error: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

$results = [];

$queries = [

    // 1. Courses table
    "CREATE TABLE IF NOT EXISTS courses (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        name          VARCHAR(255) NOT NULL,
        category      VARCHAR(100) NOT NULL,
        is_active     TINYINT(1)   DEFAULT 1,
        sort_order    INT          DEFAULT 0,
        price         INT          DEFAULT 0,
        original_price INT         DEFAULT 0,
        duration      VARCHAR(50)  DEFAULT '',
        created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 2. Add price columns if courses table already existed without them
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS price          INT DEFAULT 0",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS original_price INT DEFAULT 0",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS duration       VARCHAR(50) DEFAULT ''",

    // 3. Insert default courses (ignore duplicates)
    "INSERT IGNORE INTO courses (name, category, is_active, sort_order, price, original_price, duration) VALUES
    ('Full Stack Web Development',  'Web Development',        1,  1, 22000, 28000, '3-4 Months'),
    ('Front-End Web Development',   'Web Development',        1,  2, 15000, 20000, '2-3 Months'),
    ('PHP & MySQL',                 'Web Development',        1,  3, 18000, 22000, '2-3 Months'),
    ('WordPress',                   'Web Development',        1,  4, 10000, 15000, '1-2 Months'),
    ('React Native',                'Mobile App Development', 1,  5, 30000, 35000, '4-5 Months'),
    ('Flutter',                     'Mobile App Development', 1,  6, 32000, 38000, '4-5 Months'),
    ('Android Development',         'Mobile App Development', 1,  7, 25000, 30000, '3-4 Months'),
    ('iOS Development',             'Mobile App Development', 1,  8, 28000, 35000, '3-4 Months'),
    ('Graphic Designing',           'Graphic Designing',      1,  9, 20000, 25000, '2-3 Months'),
    ('UI/UX Design',                'Graphic Designing',      1, 10, 22000, 28000, '2-3 Months'),
    ('Canva Course',                'Graphic Designing',      1, 11,  5000,  8000, '1 Month'),
    ('Digital Marketing',           'Digital Marketing',      1, 12, 25000, 30000, '3-4 Months'),
    ('SEO Course',                  'Digital Marketing',      1, 13, 15000, 20000, '2-3 Months'),
    ('Social Media Marketing',      'Digital Marketing',      1, 14, 18000, 22000, '2-3 Months'),
    ('Python',                      'Programming',            1, 15, 15000, 20000, '2-3 Months'),
    ('C++ Course',                  'Programming',            1, 16, 12000, 15000, '2-3 Months'),
    ('Java Course',                 'Programming',            1, 17, 15000, 18000, '2-3 Months'),
    ('Database Management',         'Other',                  1, 18, 12000, 15000, '2 Months'),
    ('Freelancing',                 'Other',                  1, 19, 15000, 20000, '3-4 Months'),
    ('Video Editing',               'Other',                  1, 20,  8000, 12000, '1-2 Months'),
    ('MS Office / CIT',             'Other',                  1, 21,  8000, 12000, '1-2 Months')",

    // 4. Add UNIQUE index on courses.name to support INSERT IGNORE
    "ALTER TABLE courses ADD UNIQUE INDEX IF NOT EXISTS idx_course_name (name)",
];

foreach ($queries as $i => $sql) {
    $label = "Query " . ($i + 1);
    if ($conn->query($sql)) {
        $results[] = ['status' => 'ok', 'label' => $label, 'affected' => $conn->affected_rows];
    } else {
        // Some ALTER TABLE errors are ok (column already exists)
        $results[] = ['status' => 'warn', 'label' => $label, 'error' => $conn->error];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head><title>Migration</title></head>
<body style="font-family:sans-serif;max-width:700px;margin:40px auto;padding:20px;">
<h2 style="color:#0d1b2a;">🔧 Migration Results</h2>
<?php foreach ($results as $r): ?>
<div style="padding:10px 16px;margin-bottom:8px;border-radius:8px;
    background:<?= $r['status']==='ok' ? '#f0fdf4' : '#fffbeb' ?>;
    border:1px solid <?= $r['status']==='ok' ? '#bbf7d0' : '#fde68a' ?>;">
    <strong style="color:<?= $r['status']==='ok' ? '#15803d' : '#92400e' ?>;">
        <?= $r['status']==='ok' ? '✅' : '⚠️' ?> <?= $r['label'] ?>
    </strong>
    <?php if ($r['status']==='ok'): ?>
        <span style="color:#6b7280;font-size:13px;"> — <?= $r['affected'] ?> rows affected</span>
    <?php else: ?>
        <span style="color:#92400e;font-size:13px;"> — <?= htmlspecialchars($r['error']) ?></span>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<div style="margin-top:24px;padding:16px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;">
    <strong style="color:#dc2626;">⚠️ DELETE THIS FILE NOW!</strong><br>
    <span style="font-size:13px;color:#7f1d1d;">File Manager → public_html/admin/run_migrations.php → Delete</span>
</div>
<p style="margin-top:20px;"><a href="courses.php" style="background:#f07b14;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:700;">→ Go to Courses</a></p>
</body>
</html>
