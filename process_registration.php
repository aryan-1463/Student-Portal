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
} elseif (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters.";
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

    header("Location: register.php");
    exit;
}

// hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$createdAt    = date('Y-m-d H:i:s');

// save to csv file
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

// save to json file
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
$_SESSION['success_message'] = "Registration completed successfully.";
unset($_SESSION['captcha_code']);

header("Location: register.php");
exit;