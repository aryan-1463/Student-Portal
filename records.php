<?php
// records.php - Practical 7 Flat-File Storage Viewer (CSV & JSON) + MySQL Relational Database Comparison
session_start();

// Enforce Admin Access (RBAC) - Keep flat-file student records private
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php?msg=unauthorized");
    exit;
}

$csvFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'registrations.csv';
$jsonFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'registrations.json';

// 1. Read CSV Records
$csvRecords = [];
$csvRaw = "";
if (file_exists($csvFile) && filesize($csvFile) > 0) {
    $csvRaw = file_get_contents($csvFile);
    if (($handle = fopen($csvFile, "r")) !== false) {
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 6) {
                $csvRecords[] = [
                    'name'       => $row[0] ?? '',
                    'email'      => $row[1] ?? '',
                    'mobile'     => $row[2] ?? '',
                    'course'     => $row[3] ?? '',
                    'year'       => $row[4] ?? '',
                    'gender'     => $row[5] ?? '',
                    'created_at' => $row[7] ?? ($row[6] ?? '-')
                ];
            }
        }
        fclose($handle);
    }
}

// 2. Read JSON Records
$jsonRecords = [];
$jsonRaw = "";
if (file_exists($jsonFile) && filesize($jsonFile) > 0) {
    $jsonRaw = file_get_contents($jsonFile);
    $decoded = json_decode($jsonRaw, true);
    if (is_array($decoded)) {
        $jsonRecords = $decoded;
    }
}

// 3. Read MySQL DB Records
$dbRecords = [];
$mysqli = @new mysqli('127.0.0.1', 'root', '', 'studenthub_db');
if ($mysqli && !$mysqli->connect_errno) {
    $mysqli->set_charset("utf8mb4");
    $res = $mysqli->query("SELECT enrollment_no, full_name, email, mobile, course, year_of_study AS year, gender, created_at FROM students ORDER BY student_id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $dbRecords[] = $row;
        }
        $res->free();
    }
    $mysqli->close();
}

$activeTab = $_GET['tab'] ?? 'csv';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Practical 7 File Storage Viewer | StudentHub</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
      background: #0f172a;
      color: #e2e8f0;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .p7-container {
      max-width: 1100px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }
    .header-box {
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 12px;
      padding: 1.5rem 2rem;
      margin-bottom: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .badge-p7 {
      background: rgba(56, 189, 248, 0.15);
      border: 1px solid #38bdf8;
      color: #38bdf8;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      display: inline-block;
      margin-bottom: 0.4rem;
    }
    .tab-bar {
      display: flex;
      gap: 0.5rem;
      border-bottom: 2px solid #334155;
      margin-bottom: 1.5rem;
    }
    .tab-btn {
      padding: 0.75rem 1.4rem;
      background: transparent;
      border: none;
      border-bottom: 3px solid transparent;
      color: #94a3b8;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
      transition: all 0.2s;
    }
    .tab-btn:hover {
      color: #f1f5f9;
    }
    .tab-btn.active {
      color: #38bdf8;
      border-bottom-color: #38bdf8;
      background: rgba(56, 189, 248, 0.08);
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
    }
    .pill-counter {
      background: #334155;
      color: #f8fafc;
      font-size: 0.75rem;
      padding: 0.15rem 0.5rem;
      border-radius: 9999px;
    }
    .tab-btn.active .pill-counter {
      background: #0284c7;
      color: #ffffff;
    }
    .meta-card {
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 8px;
      padding: 1rem 1.25rem;
      margin-bottom: 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.8rem;
      font-size: 0.9rem;
    }
    .meta-path {
      font-family: Consolas, monospace;
      color: #38bdf8;
      background: #0f172a;
      padding: 0.2rem 0.6rem;
      border-radius: 4px;
      border: 1px solid #334155;
    }
    .raw-viewer {
      background: #0b1120;
      border: 1px solid #334155;
      border-radius: 8px;
      padding: 1rem;
      color: #a5f3fc;
      font-family: Consolas, monospace;
      font-size: 0.85rem;
      line-height: 1.5;
      overflow-x: auto;
      max-height: 250px;
      margin-top: 1rem;
      white-space: pre;
    }
    table.data-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 0.5rem;
      font-size: 0.9rem;
    }
    table.data-table th, table.data-table td {
      padding: 0.75rem 1rem;
      border-bottom: 1px solid #334155;
      text-align: left;
    }
    table.data-table th {
      background: #1e293b;
      color: #94a3b8;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
    }
    table.data-table tr:hover {
      background: rgba(30, 41, 59, 0.6);
    }
    .badge-course {
      display: inline-block;
      padding: 0.2rem 0.55rem;
      border-radius: 4px;
      background: #0284c7;
      color: #ffffff;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .nav-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      background: #334155;
      color: #ffffff;
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 600;
      transition: background 0.2s;
    }
    .nav-btn:hover { background: #475569; }
    .nav-btn-primary { background: #0284c7; }
    .nav-btn-primary:hover { background: #0369a1; }
  </style>
</head>
<body>

  <header style="background: #1e293b; padding: 1rem 1.5rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
    <h1 style="font-size: 1.25rem; color: #f8fafc;">StudentHub <span style="font-size: 0.85rem; color: #94a3b8; font-weight: normal;">| Practical 7 File Storage Viewer</span></h1>
    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
      <a href="index.html" class="nav-btn">&larr; Home</a>
      <a href="register.php" class="nav-btn nav-btn-primary">+ Register Student</a>
      <a href="login.php" class="nav-btn">Login &rarr;</a>
    </div>
  </header>

  <main class="p7-container">

    <div class="header-box">
      <div>
        <span class="badge-p7">Practical 7 Evaluation Hub</span>
        <h2 style="color: #f8fafc; font-size: 1.5rem;">Form Processing with CSV & JSON Storage</h2>
        <p style="color: #94a3b8; font-size: 0.95rem; margin-top: 0.25rem;">
          Demonstrating server-side flat-file persistence without database dependency, compared against live MySQL records.
        </p>
      </div>
      <div>
        <a href="register.php" class="nav-btn nav-btn-primary" style="padding: 0.65rem 1.2rem;">+ Test Form Submission</a>
      </div>
    </div>

    <!-- Tab Bar -->
    <div class="tab-bar">
      <a href="records.php?tab=csv" class="tab-btn <?php echo ($activeTab === 'csv') ? 'active' : ''; ?>">
        📊 CSV File (registrations.csv)
        <span class="pill-counter"><?php echo count($csvRecords); ?></span>
      </a>
      <a href="records.php?tab=json" class="tab-btn <?php echo ($activeTab === 'json') ? 'active' : ''; ?>">
        📦 JSON File (registrations.json)
        <span class="pill-counter"><?php echo count($jsonRecords); ?></span>
      </a>
      <a href="records.php?tab=mysql" class="tab-btn <?php echo ($activeTab === 'mysql') ? 'active' : ''; ?>">
        🗄️ MySQL Database (P8/P9)
        <span class="pill-counter"><?php echo count($dbRecords); ?></span>
      </a>
    </div>

    <?php if ($activeTab === 'csv'): ?>
      <!-- TAB 1: CSV FILE -->
      <div class="meta-card">
        <div>
          <strong>Storage File:</strong> <span class="meta-path">StudentHub/data/registrations.csv</span>
        </div>
        <div>
          <span>Size: <strong><?php echo file_exists($csvFile) ? filesize($csvFile) : 0; ?> bytes</strong></span> &bull; 
          <span>Records: <strong><?php echo count($csvRecords); ?> entries</strong></span>
        </div>
      </div>

      <div style="background: #1e293b; border: 1px solid #334155; border-radius: 8px; overflow: hidden;">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Full Name</th>
              <th>Email Address</th>
              <th>Mobile</th>
              <th>Course</th>
              <th>Year</th>
              <th>Gender</th>
              <th>Logged Timestamp</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($csvRecords)): ?>
              <tr><td colspan="8" style="text-align:center; padding: 2rem; color: #94a3b8;">No records found in CSV file.</td></tr>
            <?php else: ?>
              <?php foreach ($csvRecords as $idx => $r): ?>
                <tr>
                  <td><strong><?php echo $idx + 1; ?></strong></td>
                  <td style="color: #f8fafc; font-weight: 600;"><?php echo htmlspecialchars($r['name']); ?></td>
                  <td><?php echo htmlspecialchars($r['email']); ?></td>
                  <td><?php echo htmlspecialchars($r['mobile']); ?></td>
                  <td><span class="badge-course"><?php echo htmlspecialchars($r['course']); ?></span></td>
                  <td>Year <?php echo htmlspecialchars($r['year']); ?></td>
                  <td><?php echo htmlspecialchars($r['gender']); ?></td>
                  <td><small style="color: #94a3b8;"><?php echo htmlspecialchars($r['created_at']); ?></small></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <h3 style="margin-top: 2rem; margin-bottom: 0.5rem; font-size: 1.05rem; color: #f8fafc;">Raw CSV Content on Server Disk:</h3>
      <div class="raw-viewer"><?php echo htmlspecialchars($csvRaw ?: '// Empty file'); ?></div>

    <?php elseif ($activeTab === 'json'): ?>
      <!-- TAB 2: JSON FILE -->
      <div class="meta-card">
        <div>
          <strong>Storage File:</strong> <span class="meta-path">StudentHub/data/registrations.json</span>
        </div>
        <div>
          <span>Size: <strong><?php echo file_exists($jsonFile) ? filesize($jsonFile) : 0; ?> bytes</strong></span> &bull; 
          <span>Records: <strong><?php echo count($jsonRecords); ?> entries</strong></span>
        </div>
      </div>

      <div style="background: #1e293b; border: 1px solid #334155; border-radius: 8px; overflow: hidden;">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Full Name</th>
              <th>Email Address</th>
              <th>Mobile</th>
              <th>Course</th>
              <th>Year</th>
              <th>Gender</th>
              <th>Saved Timestamp</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($jsonRecords)): ?>
              <tr><td colspan="8" style="text-align:center; padding: 2rem; color: #94a3b8;">No records found in JSON file.</td></tr>
            <?php else: ?>
              <?php foreach ($jsonRecords as $idx => $r): ?>
                <tr>
                  <td><strong><?php echo $idx + 1; ?></strong></td>
                  <td style="color: #f8fafc; font-weight: 600;"><?php echo htmlspecialchars($r['name'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r['email'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r['mobile'] ?? ''); ?></td>
                  <td><span class="badge-course"><?php echo htmlspecialchars($r['course'] ?? ''); ?></span></td>
                  <td>Year <?php echo htmlspecialchars($r['year'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r['gender'] ?? ''); ?></td>
                  <td><small style="color: #94a3b8;"><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></small></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <h3 style="margin-top: 2rem; margin-bottom: 0.5rem; font-size: 1.05rem; color: #f8fafc;">Raw Formatted JSON on Server Disk:</h3>
      <div class="raw-viewer"><?php echo htmlspecialchars($jsonRaw ?: '// Empty file'); ?></div>

    <?php else: ?>
      <!-- TAB 3: MYSQL DATABASE -->
      <div class="meta-card">
        <div>
          <strong>MySQL Table:</strong> <span class="meta-path">studenthub_db.students</span>
        </div>
        <div>
          <span>Engine: <strong>InnoDB</strong></span> &bull; 
          <span>Total Seeded/Live Records: <strong><?php echo count($dbRecords); ?></strong></span>
        </div>
      </div>

      <div style="background: #1e293b; border: 1px solid #334155; border-radius: 8px; overflow: hidden;">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Enrollment</th>
              <th>Full Name</th>
              <th>Email Address</th>
              <th>Mobile</th>
              <th>Course</th>
              <th>Gender</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dbRecords as $idx => $r): ?>
              <tr>
                <td><strong><?php echo $idx + 1; ?></strong></td>
                <td><code><?php echo htmlspecialchars($r['enrollment_no']); ?></code></td>
                <td style="color: #f8fafc; font-weight: 600;"><?php echo htmlspecialchars($r['full_name']); ?></td>
                <td><?php echo htmlspecialchars($r['email']); ?></td>
                <td><?php echo htmlspecialchars($r['mobile']); ?></td>
                <td><span class="badge-course"><?php echo htmlspecialchars($r['course']); ?></span></td>
                <td><?php echo htmlspecialchars($r['gender']); ?></td>
                <td><small style="color: #94a3b8;"><?php echo htmlspecialchars($r['created_at']); ?></small></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </main>

</body>
</html>