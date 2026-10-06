<?php
// Contact inquiry form server processing
session_start();

$success = false;
$name = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $email   = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $subject = trim(htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8'));
    $message = trim(htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'));

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);

        $inquiry = [
            "id"        => "INQ-" . rand(1000, 9999),
            "name"      => $name,
            "email"     => $email,
            "subject"   => $subject,
            "message"   => $message,
            "timestamp" => date("Y-m-d H:i:s")
        ];

        // Safe JSON append with LOCK_EX
        $jsonFile = $dataDir . '/contacts.json';
        $list = file_exists($jsonFile) ? (json_decode(file_get_contents($jsonFile), true) ?: []) : [];
        $list[] = $inquiry;
        file_put_contents($jsonFile, json_encode($list, JSON_PRETTY_PRINT), LOCK_EX);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Support - Server Response</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body {
      margin: 0;
      padding: 2rem 1rem;
      background: #0b1120;
      color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .card {
      width: 100%;
      max-width: 580px;
      background: #1e293b;
      border: 1.5px solid rgba(16, 185, 129, 0.4);
      border-radius: 12px;
      padding: 2.2rem;
      box-shadow: 0 15px 30px rgba(0,0,0,0.5);
      text-align: center;
    }
    .icon {
      font-size: 3rem;
      color: #34d399;
      margin-bottom: 0.5rem;
    }
    .alert-success {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #6ee7b7;
      padding: 1rem 1.2rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1.05rem;
      margin: 1rem 0;
    }
    .btn {
      display: inline-block;
      background: #2563eb;
      color: white;
      text-decoration: none;
      padding: 0.6rem 1.4rem;
      border-radius: 6px;
      font-weight: 600;
      margin-top: 1rem;
    }
  </style>
</head>
<body class="dark-theme">
  <div class="card">
    <div class="icon">✓</div>
    <div class="alert-success">
      ✓ Thank you, <?php echo htmlspecialchars($name ?: 'Student'); ?>. Your message has been received!
    </div>
    <p style="color:#94a3b8; font-size:0.95rem; line-height:1.5;">
      Your support inquiry regarding <strong>"<?php echo htmlspecialchars($subject ?: 'General Inquiry'); ?>"</strong> was validated, sanitized on the server, and stored safely in <code>data/contacts.json</code>.
    </p>
    <a href="../contact.html" class="btn">&larr; Return to Contact Page</a>
  </div>
</body>
</html>
