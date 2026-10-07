<?php
session_start();

$csvFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'registrations.csv';
$jsonFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'registrations.json';

$records = [];

// read student records from MySQL database (Practical 8 & 9)
$dbHost = '127.0.0.1';
$dbPort = 3306;
$dbUser = 'root';
$dbPass = '';
$dbName = 'studenthub_db';

$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($mysqli && !$mysqli->connect_errno) {
    $mysqli->set_charset("utf8mb4");
    $res = $mysqli->query("SELECT enrollment_no, full_name AS name, email, mobile, course, year, gender, created_at FROM students ORDER BY student_id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $records[] = $row;
        }
        $res->free();
    }
    $mysqli->close();
}

// fallback to csv if database has no records
if (empty($records) && file_exists($csvFile) && filesize($csvFile) > 0) {
    if (($handle = fopen($csvFile, "r")) !== false) {
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 6) {
                $records[] = [
                    'enrollment_no' => '-',
                    'name'          => $row[0] ?? '',
                    'email'         => $row[1] ?? '',
                    'mobile'        => $row[2] ?? '',
                    'course'        => $row[3] ?? '',
                    'year'          => $row[4] ?? '',
                    'gender'        => $row[5] ?? '',
                    'created_at'    => $row[7] ?? ($row[6] ?? '')
                ];
            }
        }
        fclose($handle);
    }
} elseif (empty($records) && file_exists($jsonFile) && filesize($jsonFile) > 0) {
    $raw = file_get_contents($jsonFile);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $records = $decoded;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Registered Students Records - StudentHub Portal">
  <title>Stored Records | StudentHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <header>
    <h1>StudentHub Portal</h1>
    <nav aria-label="Main Navigation">
      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="about.html">About</a></li>
        <li><a href="register.php">Register</a></li>
        <li><a href="records.php" aria-current="page">Records</a></li>
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
    <section>
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
          <h2>Registered Students List</h2>
          <p>Live student records retrieved from MySQL database (<code>studenthub_db.students</code>).</p>
        </div>
        <div>
          <span class="badge-tag">Total Records: <?php echo count($records); ?></span>
        </div>
      </div>

      <?php if (empty($records)): ?>
        <div class="alert" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; margin-top: 1.5rem;">
          <p><strong>No student registrations found yet.</strong></p>
          <p style="margin-bottom: 0;">Submit the registration form to create your first record.</p>
        </div>
        <div style="margin-top: 1.5rem;">
          <a href="register.php" style="display: inline-block; padding: 0.6rem 1.4rem; background: var(--primary-color); color: #fff; border-radius: var(--radius-pill); font-weight: 700;">+ Register New Student</a>
        </div>
      <?php else: ?>
        <div class="table-container">
          <table class="records-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Enrollment</th>
                <th>Full Name</th>
                <th>Email Address</th>
                <th>Mobile Number</th>
                <th>Course</th>
                <th>Year</th>
                <th>Gender</th>
                <th>Registered At</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($records as $index => $item): ?>
                <tr>
                  <td><strong><?php echo $index + 1; ?></strong></td>
                  <td><code><?php echo htmlspecialchars($item['enrollment_no'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></code></td>
                  <td><?php echo htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($item['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($item['mobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><span class="badge-tag"><?php echo htmlspecialchars($item['course'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span></td>
                  <td>Year <?php echo htmlspecialchars($item['year'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($item['gender'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><small><?php echo htmlspecialchars($item['created_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="records-actions-bar">
          <a href="register.php" style="display: inline-block; padding: 0.6rem 1.4rem; background: var(--primary-color); color: #fff; border-radius: var(--radius-pill); font-weight: 700;">+ Register Another Student</a>
          <a href="dashboard.html" style="font-weight: 600;">Back to Dashboard &rarr;</a>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <footer>
    <p>&copy; 2026 StudentHub Academic Portal | College Project</p>
  </footer>

  <script src="js/script.js"></script>

</body>
</html>