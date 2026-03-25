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

// --- Required field validation ---
$required = ['fullName', 'email', 'phone', 'courseInterest'];
foreach ($required as $field) {
    if (empty(trim($_POST[$field] ?? ''))) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit;
    }
}

$fullName   = trim($_POST['fullName']);
$fatherName = trim($_POST['fatherName'] ?? '');
$email      = trim($_POST['email']);
$phone      = trim($_POST['phone']);
$gender     = trim($_POST['gender'] ?? '');
$course     = trim($_POST['courseInterest']);
$studyMode  = trim($_POST['studyMode'] ?? '');
$experience = trim($_POST['previousExperience'] ?? '');
$reason     = trim($_POST['reasonForJoining'] ?? '');

if (!validate_email($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

if (!validate_phone($phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number.']);
    exit;
}

// --- File upload helper ---
function handle_upload(string $field, string $prefix): string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Upload error on $field.");
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new RuntimeException("File $field exceeds 5MB limit.");
    }

    // Validate MIME type using finfo (not extension)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
        throw new RuntimeException("Invalid file type for $field. Allowed: JPEG, PNG, GIF, PDF.");
    }

    // Map MIME to safe extension
    $extMap = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/gif'       => 'gif',
        'application/pdf' => 'pdf',
    ];
    $ext      = $extMap[$mimeType];
    $fileName = bin2hex(random_bytes(16)) . '_' . $prefix . '.' . $ext;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0750, true);
    }

    $dest = UPLOAD_DIR . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException("Failed to save $field.");
    }

    return $fileName;
}

try {
    $qualFile  = handle_upload('qualificationFile', 'qual');
    $photoFile = handle_upload('passportPhoto',     'photo');
    $cnicFile  = handle_upload('cnic',              'cnic');
} catch (RuntimeException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

$conn = get_db();

$stmt = $conn->prepare(
    "INSERT INTO enroll
        (full_name, father_name, email, phone, gender, course_interest, study_mode,
         qualification_file, passport_photo, cnic_file, previous_experience, reason_for_joining)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    'ssssssssssss',
    $fullName, $fatherName, $email, $phone, $gender, $course,
    $studyMode, $qualFile, $photoFile, $cnicFile, $experience, $reason
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Enrollment submitted successfully! We will contact you soon.']);
} else {
    error_log('enroll insert error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}

$stmt->close();
$conn->close();
