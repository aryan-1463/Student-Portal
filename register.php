<?php
session_start();

// AJAX endpoint to refresh CAPTCHA in session
if (isset($_GET['refresh_captcha'])) {
    header('Content-Type: application/json');
    $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $_SESSION['captcha_code'] = substr(str_shuffle($chars), 0, 5);
    echo json_encode(['captcha' => $_SESSION['captcha_code']]);
    exit;
}


// csrf token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// generate captcha
if (empty($_SESSION['captcha_code'])) {
    $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $_SESSION['captcha_code'] = substr(str_shuffle($chars), 0, 5);
}

// get session messages and old inputs
$successMessage = $_SESSION['success_message'] ?? null;
$formErrors     = $_SESSION['form_errors'] ?? [];
$oldInput       = $_SESSION['old_input'] ?? [];

// clear flash session data
unset($_SESSION['success_message'], $_SESSION['form_errors'], $_SESSION['old_input']);

// helper function for old input
function old($field, $default = '') {
    global $oldInput;
    return htmlspecialchars($oldInput[$field] ?? $default, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Student Registration - StudentHub Portal">
  <title>Register | StudentHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <header>
    <h1>StudentHub Portal</h1>
    <nav aria-label="Main Navigation">
      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="about.html">About</a></li>
        <li><a href="register.php" aria-current="page">Register</a></li>
        <li><a href="records.php">Records</a></li>
        <li><a href="login.html">Login</a></li>
        <li><a href="dashboard.html">Dashboard</a></li>
        <li><a href="profile.html">Profile</a></li>
        <li><a href="events.html">Events</a></li>
        <li><a href="contact.html">Contact</a></li>
        <li><a href="faq.html">FAQ</a></li>
        <li><a href="feedback.html">Feedback</a></li>
        <li><a href="admin.html">Admin</a></li>
      </ul>
    </nav>
  </header>

  <main id="main-content">
    <section class="form-container">
      <h2>Student Registration</h2>

      <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success" role="status" aria-live="polite">
          <strong>Registration Successful!</strong> <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
          <div style="margin-top: 0.5rem;">
            <a href="records.php" style="color: #065f46; text-decoration: underline; font-weight: 700;">View All Registered Records &rarr;</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($formErrors)): ?>
        <div class="alert alert-error" role="alert" aria-live="assertive">
          <strong>Please correct the following errors:</strong>
          <ul>
            <?php foreach ($formErrors as $err): ?>
              <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form id="registrationForm" action="process_registration.php" method="POST" novalidate>
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <div class="field-group">
          <label for="fullName">Full Name:</label>
          <input type="text" id="fullName" name="fullName" value="<?php echo old('fullName'); ?>" autocomplete="name" aria-describedby="fullNameError" required>
          <small id="fullNameError" class="error-message" aria-live="polite"></small>
        </div>

        <div class="field-group">
          <label for="email">Email Address:</label>
          <input type="email" id="email" name="email" value="<?php echo old('email'); ?>" autocomplete="email" aria-describedby="emailError" required>
          <small id="emailError" class="error-message" aria-live="polite"></small>
        </div>

        <div class="field-group">
          <label for="mobile">Mobile Number:</label>
          <input type="tel" id="mobile" name="mobile" value="<?php echo old('mobile'); ?>" inputmode="numeric" maxlength="10" placeholder="10 digit mobile number" aria-describedby="mobileError" required>
          <small id="mobileError" class="error-message" aria-live="polite"></small>
        </div>

        <div class="field-group">
          <label for="course">Course:</label>
          <select id="course" name="course" aria-describedby="courseError" required>
            <option value="">Select course</option>
            <option value="B.Tech" <?php echo old('course') === 'B.Tech' ? 'selected' : ''; ?>>B.Tech</option>
            <option value="BCA" <?php echo old('course') === 'BCA' ? 'selected' : ''; ?>>BCA</option>
            <option value="B.Sc IT" <?php echo old('course') === 'B.Sc IT' ? 'selected' : ''; ?>>B.Sc IT</option>
          </select>
          <small id="courseError" class="error-message" aria-live="polite"></small>
        </div>

        <div class="field-group">
          <label for="year">Year:</label>
          <select id="year" name="year" aria-describedby="yearError" required>
            <option value="">Select year</option>
            <option value="1" <?php echo old('year') === '1' ? 'selected' : ''; ?>>First Year</option>
            <option value="2" <?php echo old('year') === '2' ? 'selected' : ''; ?>>Second Year</option>
            <option value="3" <?php echo old('year') === '3' ? 'selected' : ''; ?>>Third Year</option>
            <option value="4" <?php echo old('year') === '4' ? 'selected' : ''; ?>>Fourth Year</option>
          </select>
          <small id="yearError" class="error-message" aria-live="polite"></small>
        </div>

        <fieldset class="field-group"><legend>Gender:</legend>
          <label class="radio-label"><input type="radio" name="gender" value="Male" <?php echo old('gender') === 'Male' ? 'checked' : ''; ?>> Male</label>
          <label class="radio-label"><input type="radio" name="gender" value="Female" <?php echo old('gender') === 'Female' ? 'checked' : ''; ?>> Female</label>
          <label class="radio-label"><input type="radio" name="gender" value="Other" <?php echo old('gender') === 'Other' ? 'checked' : ''; ?>> Other</label>
          <input type="hidden" id="gender" aria-describedby="genderError">
          <small id="genderError" class="error-message" aria-live="polite"></small>
        </fieldset>

        <div class="field-group">
          <label for="password">Password:</label>
          
          <input type="password" id="password" name="password" autocomplete="new-password" aria-describedby="passwordError passwordStrength" required>
          <small id="passwordError" class="error-message" aria-live="polite"></small>
          <span id="passwordStrength" class="strength-text"></span>
        </div>

        <div class="field-group">
          <label for="confirmPassword">Confirm Password:</label>
          
          <input type="password" id="confirmPassword" name="confirmPassword" autocomplete="new-password" aria-describedby="confirmPasswordError" required>
          <small id="confirmPasswordError" class="error-message" aria-live="polite"></small>
        </div>

        <div class="field-group captcha-group">
          <label for="captcha">Captcha:</label>
          <canvas id="captchaCanvas" width="150" height="45" data-captcha="<?php echo htmlspecialchars($_SESSION['captcha_code'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="Captcha code"></canvas>
          <button type="button" id="newCaptcha" class="small-button">New code</button>
          <input type="text" id="captcha" name="captcha" placeholder="Enter code shown" aria-describedby="captchaError" required>
          <small id="captchaError" class="error-message" aria-live="polite"></small>
        </div>

        <div class="field-group terms-group">
          <label class="check-label">
            <input type="checkbox" id="terms" name="terms" value="1" <?php echo !empty($oldInput['terms']) ? 'checked' : ''; ?> aria-describedby="termsError">
            I accept the terms and conditions.
          </label>
          <small id="termsError" class="error-message" aria-live="polite"></small>
        </div>

        <div class="form-actions">
          <button type="submit">Register</button>
          <button type="reset">Reset</button>
        </div>
        <p id="formResult" class="form-result" role="status" aria-live="polite"></p>
      </form>

      <p style="margin-top: 1rem;">Already have an account? <a href="login.html">Login here</a>.</p>
      <p>View registered students: <a href="records.php"><strong>View Stored Records &rarr;</strong></a></p>
    </section>
  </main>

  <footer>
    <p>&copy; 2026 StudentHub Academic Portal | College Project</p>
  </footer>

  <script src="js/script.js"></script>

</body>
</html>
