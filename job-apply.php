<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

session_init();
csrf_verify();

// Required fields
$fullName = trim($_POST['fullName'] ?? '');
$email    = trim($_POST['email']    ?? '');
$phone    = trim($_POST['phone']    ?? '');
$position = trim($_POST['position'] ?? '');
$degree   = trim($_POST['degree']   ?? '');

if (!$fullName || !$email || !$phone || !$position || !$degree) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
    exit;
}

if (!validate_email($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

if (!validate_phone($phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number.']);
    exit;
}

// Resume upload (required)
if (empty($_FILES['resume']['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'Please upload your Resume / CV.']);
    exit;
}

$resumeFile    = $_FILES['resume'];
$allowedMimes  = ['application/pdf', 'application/msword',
                  'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$allowedExt    = ['pdf', 'doc', 'docx'];
$fileExt       = strtolower(pathinfo($resumeFile['name'], PATHINFO_EXTENSION));
$fileMime      = mime_content_type($resumeFile['tmp_name']);

if (!in_array($fileMime, $allowedMimes) && !in_array($fileExt, $allowedExt)) {
    echo json_encode(['success' => false, 'message' => 'Resume must be a PDF, DOC, or DOCX file.']);
    exit;
}

if ($resumeFile['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Resume file size must be 5MB or less.']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/resumes/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$safeName   = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($resumeFile['name'], PATHINFO_FILENAME));
$newFileName = time() . '_' . $safeName . '.' . $fileExt;
$uploadPath  = $uploadDir . $newFileName;

if (!move_uploaded_file($resumeFile['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload resume. Please try again.']);
    exit;
}

$resumePath = 'uploads/resumes/' . $newFileName;

// Optional fields
$fatherName       = trim($_POST['fatherName']       ?? '');
$city             = trim($_POST['city']             ?? '');
$gender           = trim($_POST['gender']           ?? '');
$jobType          = trim($_POST['jobType']          ?? '');
$expectedSalary   = trim($_POST['expectedSalary']   ?? '');
$fieldOfStudy     = trim($_POST['fieldOfStudy']     ?? '');
$institute        = trim($_POST['institute']        ?? '');
$graduationYear   = trim($_POST['graduationYear']   ?? '');
$experience       = trim($_POST['experience']       ?? '');
$lastJobTitle     = trim($_POST['lastJobTitle']     ?? '');
$skills           = trim($_POST['skills']           ?? '');
$previousExp      = trim($_POST['previousExperience'] ?? '');
$coverLetter      = trim($_POST['coverLetter']      ?? '');
$portfolioUrl     = trim($_POST['portfolioUrl']     ?? '');

$conn = get_db();

$stmt = $conn->prepare(
    "INSERT INTO job_applications
        (full_name, father_name, email, phone, city, gender,
         position, job_type, expected_salary,
         degree, field_of_study, institute, graduation_year,
         experience, last_job_title, skills, previous_experience,
         cover_letter, portfolio_url, resume_path)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    'ssssssssssssssssssss',
    $fullName, $fatherName, $email, $phone, $city, $gender,
    $position, $jobType, $expectedSalary,
    $degree, $fieldOfStudy, $institute, $graduationYear,
    $experience, $lastJobTitle, $skills, $previousExp,
    $coverLetter, $portfolioUrl, $resumePath
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
} else {
    // Remove uploaded file if DB insert failed
    @unlink($uploadPath);
    error_log('job_apply insert error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}

$stmt->close();
$conn->close();
