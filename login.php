<?php
// login.php - Secure Authentication Controller (Practical 10 & Integrated Portal)
session_start();

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'studenthub_db';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die("Database Connection Error: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

$errorMsg = '';
$successMsg = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'logout') {
        $successMsg = "You have been logged out successfully.";
        $_SESSION = [];
        setcookie("studentHubRole", "", time() - 3600, "/");
        setcookie("studentHubLoggedIn", "", time() - 3600, "/");
        setcookie("studentHubUserName", "", time() - 3600, "/");
        setcookie("studentHubUserId", "", time() - 3600, "/");
    } elseif ($_GET['msg'] === 'unauthorized') {
        $errorMsg = "Please log in to access that page.";
    } elseif ($_GET['msg'] === 'timeout') {
        $errorMsg = "Session expired due to inactivity. Please log in again.";
    }
}

// Handle POST: Login Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errorMsg = "Please enter both username and password.";
    } else {
        $stmt = $mysqli->prepare("SELECT user_id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // If not in users table, check registered students by Email, Enrollment Number, or Full Name
        if (!$user) {
            $sStmt = $mysqli->prepare("SELECT student_id, full_name, email, enrollment_no, password_hash FROM students WHERE email = ? OR enrollment_no = ? OR full_name = ?");
            $sStmt->bind_param("sss", $username, $username, $username);
            $sStmt->execute();
            $student = $sStmt->get_result()->fetch_assoc();
            $sStmt->close();

            if ($student && !empty($student['password_hash'])) {
                $user = [
                    'user_id'       => $student['student_id'],
                    'username'      => $student['full_name'],
                    'password_hash' => $student['password_hash'],
                    'role'          => 'student',
                    'enrollment_no' => $student['enrollment_no']
                ];
            }
        }

        if ($user && password_verify($password, $user['password_hash'])) {
            // Password correct! Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['user_id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['last_activity'] = time();

            // Store in cookie for frontend JS
            setcookie("studentHubRole", $user['role'], time() + 86400, "/");
            setcookie("studentHubLoggedIn", "true", time() + 86400, "/");
            setcookie("studentHubUserName", $user['username'], time() + 86400, "/");
            $studentIdDisplay = $user['enrollment_no'] ?? ('#' . $user['user_id']);
            setcookie("studentHubUserId", $studentIdDisplay, time() + 86400, "/");

            $targetUrl = ($user['role'] === 'admin') ? "admin_dashboard.php" : "dashboard.html";
            $dispName = addslashes($user['username']);
            $dispId = addslashes($studentIdDisplay);
            $dispRole = addslashes($user['role']);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
              <meta charset="UTF-8">
              <title>Logging In...</title>
            </head>
            <body style="background:#0f172a; color:#f8fafc; font-family:sans-serif; display:flex; align-items:center; justify-content:center; height:100vh;">
              <p>Authenticating... Redirecting to portal...</p>
              <script>
                localStorage.setItem("studentHubLoggedIn", "true");
                localStorage.setItem("studentHubUser", JSON.stringify({
                  name: "<?php echo $dispName; ?>",
                  id: "<?php echo $dispId; ?>",
                  role: "<?php echo $dispRole; ?>"
                }));
                window.location.replace("<?php echo $targetUrl; ?>");
              </script>
            </body>
            </html>
            <?php
            exit;
        } else {
            // Invalid credentials
            $errorMsg = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | StudentHub Portal</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
      background: #0f172a;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
      margin: 0;
      padding: 1rem;
    }
    .auth-container {
      width: 100%;
      max-width: 420px;
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 16px;
      padding: 2.2rem;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }
    .auth-header {
      text-align: center;
      margin-bottom: 1.8rem;
    }
    .auth-header h1 {
      color: #38bdf8;
      font-size: 1.8rem;
      font-weight: 800;
      margin-bottom: 0.3rem;
    }
    .auth-header p {
      color: #94a3b8;
      font-size: 0.95rem;
      margin: 0;
    }
    .auth-alert {
      padding: 0.85rem 1rem;
      border-radius: 8px;
      margin-bottom: 1.4rem;
      font-size: 0.92rem;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .alert-danger {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid #ef4444;
      color: #fca5a5;
    }
    .alert-success {
      background: rgba(34, 197, 94, 0.15);
      border: 1px solid #22c55e;
      color: #86efac;
    }
    .form-group {
      margin-bottom: 1.25rem;
    }
    .form-group label {
      display: block;
      color: #cbd5e1;
      font-size: 0.88rem;
      font-weight: 600;
      margin-bottom: 0.45rem;
    }
    .form-group input {
      width: 100%;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      border: 1px solid #475569;
      background: #0f172a;
      color: #f8fafc;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s;
      box-sizing: border-box;
    }
    .form-group input:focus {
      border-color: #38bdf8;
    }
    .form-check {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 1.4rem;
      color: #94a3b8;
      font-size: 0.88rem;
    }
    .form-check input {
      accent-color: #0284c7;
      width: 16px;
      height: 16px;
    }
    .btn-submit {
      width: 100%;
      padding: 0.85rem;
      border-radius: 8px;
      background: #0284c7;
      color: #ffffff;
      font-size: 1rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      transition: background 0.2s, transform 0.1s;
    }
    .btn-submit:hover {
      background: #0369a1;
    }
    .btn-submit:active {
      transform: scale(0.99);
    }
    .auth-footer {
      text-align: center;
      margin-top: 1.5rem;
      color: #94a3b8;
      font-size: 0.88rem;
    }
    .auth-footer a {
      color: #38bdf8;
      text-decoration: underline;
      font-weight: 600;
    }
    .home-link {
      margin-top: 1.2rem;
      text-align: center;
    }
    .home-link a {
      color: #64748b;
      font-size: 0.85rem;
      text-decoration: none;
    }
    .home-link a:hover {
      color: #94a3b8;
    }
  </style>
</head>
<body>

  <div class="auth-container">
    <div class="auth-header">
      <h1>StudentHub Portal</h1>
      <p>Sign in to your academic account</p>
    </div>

    <?php if ($errorMsg): ?>
      <div class="auth-alert alert-danger" role="alert">
        <span>&#9888;</span>
        <strong><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></strong>
      </div>
    <?php endif; ?>

    <?php if ($successMsg): ?>
      <div class="auth-alert alert-success" role="alert">
        <span>&#10003;</span>
        <strong><?php echo htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></strong>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label for="username">Username, Email, or Enrollment No</label>
        <input 
          type="text" 
          id="username" 
          name="username" 
          placeholder="e.g. admin1, your email, or enrollment no" 
          value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
          required 
          autocomplete="username"
        >
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input 
          type="password" 
          id="password" 
          name="password" 
          placeholder="Enter password" 
          required 
          autocomplete="current-password"
        >
      </div>

      <div class="form-check">
        <input type="checkbox" id="remember" name="remember" value="1">
        <label for="remember">Remember me (30 days)</label>
      </div>

      <button type="submit" class="btn-submit">Login &rarr;</button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="register.php">Register here</a>
    </div>

    <div class="home-link">
      <a href="index.html">&larr; Back to Home Page</a>
    </div>
  </div>

</body>
</html>
