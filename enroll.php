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

// Require logged-in user to submit enrollment
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to enroll. Please login or register.']);
    exit;
}

// --- Required field validation ---
$required = ['fullName', 'email', 'phone', 'courseInterest', 'cnicNumber'];
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
$cnicNumber = trim($_POST['cnicNumber'] ?? '');
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

// Validate CNIC format
if (!preg_match('/^\d{5}-\d{7}-\d$/', $cnicNumber)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CNIC format. Use: XXXXX-XXXXXXX-X']);
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
    $photoFile = handle_upload('passportPhoto', 'photo');
    $cnicFile  = handle_upload('cnic',          'cnic');
} catch (RuntimeException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

$conn = get_db();

$userId = $_SESSION['user_id'] ?? null;

// Check if user_id column exists in enroll table (for live server compatibility)
$col_check  = $conn->query("SHOW COLUMNS FROM enroll LIKE 'user_id'");
$has_uid    = $col_check && $col_check->num_rows > 0;

if ($has_uid) {
    $stmt = $conn->prepare(
        "INSERT INTO enroll
            (user_id, full_name, father_name, email, phone, gender, cnic_number, course_interest, study_mode,
             passport_photo, cnic_file, previous_experience, reason_for_joining, enrollment_status, added_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'online')"
    );
    $stmt->bind_param(
        'issssssssssss',
        $userId, $fullName, $fatherName, $email, $phone, $gender, $cnicNumber, $course,
        $studyMode, $photoFile, $cnicFile, $experience, $reason
    );
} else {
    // Fallback: enroll without user_id column
    $stmt = $conn->prepare(
        "INSERT INTO enroll
            (full_name, father_name, email, phone, gender, cnic_number, course_interest, study_mode,
             passport_photo, cnic_file, previous_experience, reason_for_joining, enrollment_status, added_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'online')"
    );
    $stmt->bind_param(
        'ssssssssssss',
        $fullName, $fatherName, $email, $phone, $gender, $cnicNumber, $course,
        $studyMode, $photoFile, $cnicFile, $experience, $reason
    );
}

if ($stmt->execute()) {
    // Send confirmation email to student (non-blocking — don't fail enrollment if mail fails)
    if (!empty($email)) {
        require_once __DIR__ . '/mailer.php';
        send_enrollment_confirmation($email, $fullName, $course);
        send_admin_enrollment_notification($fullName, $email, $phone, $course);
    }
    echo json_encode(['success' => true, 'message' => 'Enrollment submitted successfully! We will contact you soon.']);
} else {
    error_log('enroll insert error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}

$stmt->close();
$conn->close();
