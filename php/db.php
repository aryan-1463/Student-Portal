<?php
// Database configuration
$host = '127.0.0.1';
$port = 3306;
$dbname = 'studenthub_db';
$username = 'root';
$password = '';

// PDO connection
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $connectionError = $e->getMessage();
}

function getDBConnection() {
    global $pdo;
    return $pdo;
}

if (basename($_SERVER['SCRIPT_FILENAME']) === 'db.php'):
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StudentHub Data Hub | Live Connection</title>
  <style>
    :root {
      --bg: #0b1120;
      --card: #1e293b;
      --border: #334155;
      --text: #f8fafc;
      --text-muted: #94a3b8;
      --accent: #38bdf8;
      --success: #10b981;
      --error: #ef4444;
    }
    body {
      margin: 0;
      padding: 2.5rem 1rem;
      background: var(--bg);
      color: var(--text);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      box-sizing: border-box;
    }
    .test-card {
      width: 100%;
      max-width: 780px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 2.2rem;
      box-shadow: 0 20px 40px rgba(0,0,0,0.45);
    }
    .header-box {
      border-bottom: 1px solid var(--border);
      padding-bottom: 1.2rem;
      margin-bottom: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .header-titles h1 {
      margin: 0;
      font-size: 1.45rem;
      color: var(--text);
    }
    .header-titles p {
      margin: 4px 0 0;
      font-size: 0.88rem;
      color: var(--text-muted);
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 0.4rem 0.9rem;
      border-radius: 9999px;
      font-size: 0.82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .badge-success {
      background: rgba(16, 185, 129, 0.15);
      color: #34d399;
      border: 1px solid rgba(16, 185, 129, 0.4);
    }
    .badge-error {
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.4);
    }
    .grid-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
      gap: 1rem;
      margin-bottom: 1.8rem;
    }
    .stat-box {
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1rem 1.2rem;
    }
    .stat-label {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-muted);
      margin-bottom: 4px;
    }
    .stat-val {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text);
    }
    .sec-title {
      font-size: 1rem;
      color: var(--accent);
      margin: 1.6rem 0 0.8rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 0.6rem 0 1.2rem;
      font-size: 0.88rem;
      background: rgba(15, 23, 42, 0.4);
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid var(--border);
    }
    th, td {
      padding: 0.7rem 1rem;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }
    th {
      background: rgba(56, 189, 248, 0.1);
      color: var(--accent);
      font-size: 0.76rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    tr:last-child td { border-bottom: none; }
    .code-box {
      background: #020617;
      border: 1px solid var(--border);
      border-radius: 6px;
      padding: 0.85rem 1rem;
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      font-size: 0.85rem;
      color: #cbd5e1;
      overflow-x: auto;
      margin: 0.6rem 0;
    }
    .btn-row {
      margin-top: 1.5rem;
      display: flex;
      gap: 0.8rem;
      flex-wrap: wrap;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #2563eb;
      color: white;
      text-decoration: none;
      padding: 0.65rem 1.2rem;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.88rem;
      transition: background 0.2s;
    }
    .btn:hover { background: #1d4ed8; }
    .btn-secondary { background: #334155; }
    .btn-secondary:hover { background: #475569; }
  </style>
</head>
<body>

  <div class="test-card">
    <div class="header-box">
      <div class="header-titles">
        <h1>StudentHub Data Hub</h1>
        <p>Live MySQL Connection &amp; Student Records</p>
      </div>
      <div>
        <?php if ($dbConnected): ?>
          <span class="badge badge-success">&#10003; PDO Connected</span>
        <?php else: ?>
          <span class="badge badge-error">&#10007; Connection Failed</span>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($dbConnected): ?>
      <?php
        $driverVer = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        $studentCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $eventCount = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
      ?>

      <div class="grid-info">
        <div class="stat-box">
          <div class="stat-label">Database Name</div>
          <div class="stat-val"><?php echo htmlspecialchars($dbname); ?></div>
        </div>
        <div class="stat-box">
          <div class="stat-label">RDBMS Server</div>
          <div class="stat-val"><?php echo htmlspecialchars(substr($driverVer, 0, 18)); ?></div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Total Students</div>
          <div class="stat-val"><?php echo $studentCount; ?> Records</div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Active Events</div>
          <div class="stat-val"><?php echo $eventCount; ?> Events</div>
        </div>
      </div>

      <div class="sec-title">&#128101; All Enrolled Students in Database (<?php echo $studentCount; ?> Records)</div>
      <?php
        $allStudentsStmt = $pdo->query("SELECT student_id, enrollment_no, full_name, email, course, year FROM students ORDER BY student_id ASC");
        $allStudentsList = $allStudentsStmt->fetchAll();
      ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Enrollment No</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Course</th>
            <th>Year</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allStudentsList as $s): ?>
            <tr>
              <td><strong><?php echo $s['student_id']; ?></strong></td>
              <td><code style="color:var(--accent); font-weight:700;"><?php echo htmlspecialchars($s['enrollment_no']); ?></code></td>
              <td><?php echo htmlspecialchars($s['full_name']); ?></td>
              <td><?php echo htmlspecialchars($s['email']); ?></td>
              <td><?php echo htmlspecialchars($s['course']); ?></td>
              <td>Year <?php echo htmlspecialchars($s['year']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="sec-title">&#128269; Prepared Statement Query (Safe Parameter Binding)</div>
      <div class="code-box">
        $stmt = $pdo-&gt;prepare("SELECT student_id, enrollment_no, full_name, email, course FROM students WHERE enrollment_no = :enrollment");<br>
        $stmt-&gt;execute([':enrollment' =&gt; 'D26DCE156']);
      </div>

      <?php
        $prepStmt = $pdo->prepare("SELECT student_id, enrollment_no, full_name, email, course, year FROM students WHERE enrollment_no = :enrollment");
        $prepStmt->execute([':enrollment' => 'D26DCE156']);
        $testedStudent = $prepStmt->fetch();
      ?>

      <?php if ($testedStudent): ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Enrollment No</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Course</th>
              <th>Year</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong><?php echo $testedStudent['student_id']; ?></strong></td>
              <td><code style="color:var(--accent); font-weight:700;"><?php echo $testedStudent['enrollment_no']; ?></code></td>
              <td><?php echo htmlspecialchars($testedStudent['full_name']); ?></td>
              <td><?php echo htmlspecialchars($testedStudent['email']); ?></td>
              <td><?php echo htmlspecialchars($testedStudent['course']); ?></td>
              <td>Year <?php echo htmlspecialchars($testedStudent['year']); ?></td>
            </tr>
          </tbody>
        </table>
      <?php endif; ?>

      <div class="sec-title">&#9881; Stored Procedure Execution (sp_GetStudentRegistrations)</div>
      <div class="code-box">
        $spStmt = $pdo-&gt;prepare("CALL sp_GetStudentRegistrations(:student_id)");<br>
        $spStmt-&gt;execute([':student_id' =&gt; 1]);
      </div>

      <?php
        $spStmt = $pdo->prepare("CALL sp_GetStudentRegistrations(:student_id)");
        $spStmt->execute([':student_id' => 1]);
        $registeredEvents = $spStmt->fetchAll();
      ?>

      <table>
        <thead>
          <tr>
            <th>Event Title</th>
            <th>Category</th>
            <th>Date</th>
            <th>Location</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($registeredEvents as $row): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($row['event_title']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['event_category']); ?></td>
              <td><?php echo htmlspecialchars($row['event_date']); ?></td>
              <td><?php echo htmlspecialchars($row['location']); ?></td>
              <td><span style="color:#34d399; font-weight:700;">&#10003; <?php echo htmlspecialchars($row['registration_status']); ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="btn-row">
        <a href="../register.php" class="btn">&larr; Back to StudentHub Portal</a>
        <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=studenthub_db" target="_blank" class="btn btn-secondary">&#128450; Open in phpMyAdmin</a>
      </div>

    <?php else: ?>
      <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:1.2rem; color:#fca5a5;">
        <strong style="display:block; margin-bottom:6px; font-size:1.05rem;">PDO Connection Failed!</strong>
        Error: <code><?php echo htmlspecialchars($connectionError); ?></code>
        <p style="margin:10px 0 0; font-size:0.88rem; color:#cbd5e1;">
          Ensure MySQL is running in XAMPP on port 3306 and database <code>studenthub_db</code> exists.
        </p>
      </div>
    <?php endif; ?>
  </div>

</body>
</html>
<?php endif; ?>
