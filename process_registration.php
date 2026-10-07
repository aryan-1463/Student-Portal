<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

// verify csrf token
$sessionToken   = $_SESSION['csrf_token'] ?? '';
$submittedToken = $_POST['csrf_token'] ?? '';

if (empty($sessionToken) || empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
    $_SESSION['form_errors'] = ["Session token expired. Please refresh and try again."];
    header("Location: register.php");
    exit;
}

// get form fields
$fullName        = isset($_POST['fullName']) ? trim($_POST['fullName']) : '';
$email           = isset($_POST['email']) ? trim($_POST['email']) : '';
$mobile          = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
$course          = isset($_POST['course']) ? trim($_POST['course']) : '';
$year            = isset($_POST['year']) ? trim($_POST['year']) : '';
$gender          = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$password        = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';
$terms           = isset($_POST['terms']);
$captcha         = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

// validate fields
$errors = [];

if ($fullName === '') {
    $errors[] = "Full Name is required.";
} elseif (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 40) {
    $errors[] = "Full Name must be between 3 and 40 characters.";
} elseif (!preg_match("/^[a-zA-Z\s]+$/", $fullName)) {
    $errors[] = "Full Name can only contain letters and spaces.";
}

if ($email === '') {
    $errors[] = "Email address is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if ($mobile === '') {
    $errors[] = "Mobile number is required.";
} elseif (!preg_match("/^[0-9]{10}$/", $mobile)) {
    $errors[] = "Mobile number must contain 10 digits.";
}

$validCourses = ['B.Tech', 'BCA', 'B.Sc IT'];
if ($course === '' || !in_array($course, $validCourses, true)) {
    $errors[] = "Please select a valid course.";
}

$validYears = ['1', '2', '3', '4'];
if ($year === '' || !in_array($year, $validYears, true)) {
    $errors[] = "Please select your study year.";
}

$validGenders = ['Male', 'Female', 'Other'];
if ($gender === '' || !in_array($gender, $validGenders, true)) {
    $errors[] = "Please select your gender.";
}

if ($password === '') {
    $errors[] = "Password is required.";
} elseif (strlen($password) < 4) {
    $errors[] = "Password must be at least 4 characters.";
}

if ($confirmPassword === '') {
    $errors[] = "Please confirm your password.";
} elseif ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

if (!$terms) {
    $errors[] = "Please accept the terms and conditions.";
}

// verify captcha
if (!empty($_SESSION['captcha_code'])) {
    if ($captcha === '' || strtoupper($captcha) !== strtoupper($_SESSION['captcha_code'])) {
        $errors[] = "Captcha verification failed. Please enter the correct code.";
    }
}

// database connection using mysqli
$dbHost = '127.0.0.1';
$dbPort = 3306;
$dbUser = 'root';
$dbPass = '';
$dbName = 'studenthub_db';

$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($mysqli->connect_errno) {
    $errors[] = "Database connection error. Please ensure MySQL is running.";
} else {
    $mysqli->set_charset("utf8mb4");
}

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// check duplicate email if basic validation passed
if (empty($errors) && $mysqli && !$mysqli->connect_errno) {
    $checkStmt = $mysqli->prepare("SELECT student_id, email FROM students WHERE email = ? LIMIT 1");
    if ($checkStmt) {
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            $errors[] = "This email address is already registered. Please login or use a different email.";
            
            // audit log entry for duplicate attempt
            $auditStmt = $mysqli->prepare("INSERT INTO registration_audit_logs (email, ip_address, status, details) VALUES (?, ?, 'Failed_Duplicate', 'Duplicate email registration attempt')");
            if ($auditStmt) {
                $auditStmt->bind_param("ss", $email, $clientIp);
                $auditStmt->execute();
                $auditStmt->close();
            }
        }
        $checkStmt->close();
    }
}

// if errors exist, save input to session and redirect
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = [
        'fullName' => $fullName,
        'email'    => $email,
        'mobile'   => $mobile,
        'course'   => $course,
        'year'     => $year,
        'gender'   => $gender,
        'terms'    => $terms
    ];

    if ($mysqli && !$mysqli->connect_errno) {
        $mysqli->close();
    }

    header("Location: register.php");
    exit;
}

// hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$createdAt    = date('Y-m-d H:i:s');
$yearInt      = (int)$year;

// generate unique enrollment number
$enrollmentNo = 'D26DCE' . rand(200, 999);
$enrollCheck = $mysqli->prepare("SELECT student_id FROM students WHERE enrollment_no = ?");
if ($enrollCheck) {
    $enrollCheck->bind_param("s", $enrollmentNo);
    $enrollCheck->execute();
    $enrollCheck->store_result();
    if ($enrollCheck->num_rows > 0) {
        $enrollmentNo = 'D26DCE' . rand(1000, 9999);
    }
    $enrollCheck->close();
}

// insert into students table using mysqli prepared statement
$newStudentId = null;
$insertStmt = $mysqli->prepare("INSERT INTO students (enrollment_no, full_name, email, mobile, course, year, gender, password_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
if ($insertStmt) {
    $insertStmt->bind_param("sssssisss", $enrollmentNo, $fullName, $email, $mobile, $course, $yearInt, $gender, $passwordHash, $createdAt);
    if ($insertStmt->execute()) {
        $newStudentId = $insertStmt->insert_id;
    } else {
        $_SESSION['form_errors'] = ["Database error: Failed to insert student record."];
        $insertStmt->close();
        $mysqli->close();
        header("Location: register.php");
        exit;
    }
    $insertStmt->close();
}

// audit log entry for successful registration
$auditStmt = $mysqli->prepare("INSERT INTO registration_audit_logs (student_id, email, ip_address, status, details) VALUES (?, ?, ?, 'Success', 'Registration successful via MySQLi prepared statement')");
if ($auditStmt) {
    $auditStmt->bind_param("iss", $newStudentId, $email, $clientIp);
    $auditStmt->execute();
    $auditStmt->close();
}

$mysqli->close();

// save copy to csv file
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$csvFile   = $dataDir . DIRECTORY_SEPARATOR . 'registrations.csv';
$csvExists = file_exists($csvFile) && filesize($csvFile) > 0;

$fp = fopen($csvFile, 'a');
if ($fp) {
    if (flock($fp, LOCK_EX)) {
        if (!$csvExists) {
            fputcsv($fp, ['Name', 'Email', 'Mobile', 'Course', 'Year', 'Gender', 'PasswordHash', 'CreatedAt']);
        }
        fputcsv($fp, [$fullName, $email, $mobile, $course, $year, $gender, $passwordHash, $createdAt]);
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// save copy to json file
$jsonFile    = $dataDir . DIRECTORY_SEPARATOR . 'registrations.json';
$jsonRecords = [];

if (file_exists($jsonFile) && filesize($jsonFile) > 0) {
    $rawContent = file_get_contents($jsonFile);
    $decoded    = json_decode($rawContent, true);
    if (is_array($decoded)) {
        $jsonRecords = $decoded;
    }
}

$jsonRecords[] = [
    'name'       => $fullName,
    'email'      => $email,
    'mobile'     => $mobile,
    'course'     => $course,
    'year'       => $year,
    'gender'     => $gender,
    'created_at' => $createdAt
];

file_put_contents(
    $jsonFile,
    json_encode($jsonRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    LOCK_EX
);

// show success and clear captcha
<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

// verify csrf token
$sessionToken   = $_SESSION['csrf_token'] ?? '';
$submittedToken = $_POST['csrf_token'] ?? '';

if (empty($sessionToken) || empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
    $_SESSION['form_errors'] = ["Session token expired. Please refresh and try again."];
    header("Location: register.php");
    exit;
}

// get form fields
$fullName        = isset($_POST['fullName']) ? trim($_POST['fullName']) : '';
$email           = isset($_POST['email']) ? trim($_POST['email']) : '';
$mobile          = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
$course          = isset($_POST['course']) ? trim($_POST['course']) : '';
$year            = isset($_POST['year']) ? trim($_POST['year']) : '';
$gender          = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$password        = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';
$terms           = isset($_POST['terms']);
$captcha         = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

// validate fields
$errors = [];

if ($fullName === '') {
    $errors[] = "Full Name is required.";
} elseif (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 40) {
    $errors[] = "Full Name must be between 3 and 40 characters.";
} elseif (!preg_match("/^[a-zA-Z\s]+$/", $fullName)) {
    $errors[] = "Full Name can only contain letters and spaces.";
}

if ($email === '') {
    $errors[] = "Email address is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if ($mobile === '') {
    $errors[] = "Mobile number is required.";
} elseif (!preg_match("/^[0-9]{10}$/", $mobile)) {
    $errors[] = "Mobile number must contain 10 digits.";
}

$validCourses = ['B.Tech', 'BCA', 'B.Sc IT'];
if ($course === '' || !in_array($course, $validCourses, true)) {
    $errors[] = "Please select a valid course.";
}

$validYears = ['1', '2', '3', '4'];
if ($year === '' || !in_array($year, $validYears, true)) {
    $errors[] = "Please select your study year.";
}

$validGenders = ['Male', 'Female', 'Other'];
if ($gender === '' || !in_array($gender, $validGenders, true)) {
    $errors[] = "Please select your gender.";
}

if ($password === '') {
    $errors[] = "Password is required.";
} elseif (strlen($password) < 4) {
    $errors[] = "Password must be at least 4 characters.";
}

if ($confirmPassword === '') {
    $errors[] = "Please confirm your password.";
} elseif ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

if (!$terms) {
    $errors[] = "Please accept the terms and conditions.";
}

// verify captcha
if (!empty($_SESSION['captcha_code'])) {
    if ($captcha === '' || strtoupper($captcha) !== strtoupper($_SESSION['captcha_code'])) {
        $errors[] = "Captcha verification failed. Please enter the correct code.";
    }
}

// database connection using mysqli
$dbHost = '127.0.0.1';
$dbPort = 3306;
$dbUser = 'root';
$dbPass = '';
$dbName = 'studenthub_db';

$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($mysqli->connect_errno) {
    $errors[] = "Database connection error. Please ensure MySQL is running.";
} else {
    $mysqli->set_charset("utf8mb4");
}

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// check duplicate email if basic validation passed
if (empty($errors) && $mysqli && !$mysqli->connect_errno) {
    $checkStmt = $mysqli->prepare("SELECT student_id, email FROM students WHERE email = ? LIMIT 1");
    if ($checkStmt) {
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            $errors[] = "This email address is already registered. Please login or use a different email.";
            
            // audit log entry for duplicate attempt
            $auditStmt = $mysqli->prepare("INSERT INTO registration_audit_logs (email, ip_address, status, details) VALUES (?, ?, 'Failed_Duplicate', 'Duplicate email registration attempt')");
            if ($auditStmt) {
                $auditStmt->bind_param("ss", $email, $clientIp);
                $auditStmt->execute();
                $auditStmt->close();
            }
        }
        $checkStmt->close();
    }
}

// if errors exist, save input to session and redirect
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = [
        'fullName' => $fullName,
        'email'    => $email,
        'mobile'   => $mobile,
        'course'   => $course,
        'year'     => $year,
        'gender'   => $gender,
        'terms'    => $terms
    ];

    if ($mysqli && !$mysqli->connect_errno) {
        $mysqli->close();
    }

    header("Location: register.php");
    exit;
}

// hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$createdAt    = date('Y-m-d H:i:s');
$yearInt      = (int)$year;

// generate unique enrollment number
$enrollmentNo = 'D26DCE' . rand(200, 999);
$enrollCheck = $mysqli->prepare("SELECT student_id FROM students WHERE enrollment_no = ?");
if ($enrollCheck) {
    $enrollCheck->bind_param("s", $enrollmentNo);
    $enrollCheck->execute();
    $enrollCheck->store_result();
    if ($enrollCheck->num_rows > 0) {
        $enrollmentNo = 'D26DCE' . rand(1000, 9999);
    }
    $enrollCheck->close();
}

// insert into students table using mysqli prepared statement
$newStudentId = null;
$insertStmt = $mysqli->prepare("INSERT INTO students (enrollment_no, full_name, email, mobile, course, year, gender, password_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
if ($insertStmt) {
    $insertStmt->bind_param("sssssisss", $enrollmentNo, $fullName, $email, $mobile, $course, $yearInt, $gender, $passwordHash, $createdAt);
    if ($insertStmt->execute()) {
        $newStudentId = $insertStmt->insert_id;
    } else {
        $_SESSION['form_errors'] = ["Database error: Failed to insert student record."];
        $insertStmt->close();
        $mysqli->close();
        header("Location: register.php");
        exit;
    }
    $insertStmt->close();
}

// audit log entry for successful registration
$auditStmt = $mysqli->prepare("INSERT INTO registration_audit_logs (student_id, email, ip_address, status, details) VALUES (?, ?, ?, 'Success', 'Registration successful via MySQLi prepared statement')");
if ($auditStmt) {
    $auditStmt->bind_param("iss", $newStudentId, $email, $clientIp);
    $auditStmt->execute();
    $auditStmt->close();
}

$mysqli->close();

// save copy to csv file
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$csvFile   = $dataDir . DIRECTORY_SEPARATOR . 'registrations.csv';
$csvExists = file_exists($csvFile) && filesize($csvFile) > 0;

$fp = fopen($csvFile, 'a');
if ($fp) {
    if (flock($fp, LOCK_EX)) {
        if (!$csvExists) {
            fputcsv($fp, ['Name', 'Email', 'Mobile', 'Course', 'Year', 'Gender', 'PasswordHash', 'CreatedAt']);
        }
        fputcsv($fp, [$fullName, $email, $mobile, $course, $year, $gender, $passwordHash, $createdAt]);
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// save copy to json file
$jsonFile    = $dataDir . DIRECTORY_SEPARATOR . 'registrations.json';
$jsonRecords = [];

if (file_exists($jsonFile) && filesize($jsonFile) > 0) {
    $rawContent = file_get_contents($jsonFile);
    $decoded    = json_decode($rawContent, true);
    if (is_array($decoded)) {
        $jsonRecords = $decoded;
    }
}

$jsonRecords[] = [
    'name'       => $fullName,
    'email'      => $email,
    'mobile'     => $mobile,
    'course'     => $course,
    'year'       => $year,
    'gender'     => $gender,
    'created_at' => $createdAt
];

file_put_contents(
    $jsonFile,
    json_encode($jsonRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    LOCK_EX
);

// show success and clear captcha
$_SESSION['success_message'] = "Registration completed successfully! Record saved to MySQL database (Student ID: #$newStudentId, Enrollment: $enrollmentNo).";
unset($_SESSION['captcha_code']);

header("Location: register.php");
exit;SESSION['success_message'] = "Registration completed successfully! Your details have been saved to the database.";
unset($_SESSION['captcha_code']);

header("Location: register.php");
exit;