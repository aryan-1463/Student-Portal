<?php
// manage_students.php - StudentHub Student Management CRUD Module
// Practical 11: Add, View, Edit, Delete, Search, and Filter using MySQLi Prepared Statements

session_start();

// Database configuration
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'studenthub_db';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die("Database Connection Error: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

// Handle POST: Add Student (Create / INSERT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $enrollment = trim($_POST['enrollment_no'] ?? '');
    $fullName   = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $mobile     = trim($_POST['mobile'] ?? '');
    $course     = trim($_POST['course'] ?? '');
    $year       = (int)($_POST['year'] ?? 1);
    $gender     = trim($_POST['gender'] ?? 'Male');

    if (empty($enrollment) || empty($fullName) || empty($email) || empty($course)) {
        $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Please fill in all required fields.'];
    } else {
        // Check duplicate email or enrollment
        $checkStmt = $mysqli->prepare("SELECT student_id FROM students WHERE email = ? OR enrollment_no = ? LIMIT 1");
        $checkStmt->bind_param("ss", $email, $enrollment);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($checkRes->num_rows > 0) {
            $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Duplicate Error: Student with this Email or Enrollment No already exists.'];
        } else {
            $defPasswordHash = password_hash('Student@123', PASSWORD_DEFAULT);
            $insertStmt = $mysqli->prepare("INSERT INTO students (enrollment_no, full_name, email, mobile, course, year, gender, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("sssssiss", $enrollment, $fullName, $email, $mobile, $course, $year, $gender, $defPasswordHash);

            if ($insertStmt->execute()) {
                $_SESSION['flash_msg'] = ['type' => 'success', 'text' => "Student '{$fullName}' added successfully!"];
            } else {
                $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Failed to add student: ' . $mysqli->error];
            }
            $insertStmt->close();
        }
        $checkStmt->close();
    }
    header("Location: manage_students.php");
    exit;
}

// Handle POST: Edit Student (Update / UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $studentId  = (int)($_POST['student_id'] ?? 0);
    $fullName   = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $mobile     = trim($_POST['mobile'] ?? '');
    $course     = trim($_POST['course'] ?? '');
    $year       = (int)($_POST['year'] ?? 1);
    $gender     = trim($_POST['gender'] ?? 'Male');

    if ($studentId <= 0 || empty($fullName) || empty($email) || empty($course)) {
        $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Invalid update data provided.'];
    } else {
        // Check duplicate email for another student
        $checkStmt = $mysqli->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ? LIMIT 1");
        $checkStmt->bind_param("si", $email, $studentId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Error: Email is already in use by another student.'];
        } else {
            $updateStmt = $mysqli->prepare("UPDATE students SET full_name = ?, email = ?, mobile = ?, course = ?, year = ?, gender = ? WHERE student_id = ?");
            $updateStmt->bind_param("ssssisi", $fullName, $email, $mobile, $course, $year, $gender, $studentId);

            if ($updateStmt->execute()) {
                $_SESSION['flash_msg'] = ['type' => 'success', 'text' => "Student ID #{$studentId} updated successfully!"];
            } else {
                $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Failed to update student: ' . $mysqli->error];
            }
            $updateStmt->close();
        }
        $checkStmt->close();
    }
    header("Location: manage_students.php");
    exit;
}

// Handle GET: Delete Student (Delete / DELETE)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $studentId = (int)$_GET['id'];
    if ($studentId > 0) {
        $delStmt = $mysqli->prepare("DELETE FROM students WHERE student_id = ?");
        $delStmt->bind_param("i", $studentId);
        if ($delStmt->execute()) {
            $_SESSION['flash_msg'] = ['type' => 'success', 'text' => "Student record #{$studentId} deleted successfully!"];
        } else {
            $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Failed to delete student: ' . $mysqli->error];
        }
        $delStmt->close();
    }
    header("Location: manage_students.php");
    exit;
}

// Search and Filter Handling (Read / SELECT)
$searchTerm   = trim($_GET['search'] ?? '');
$courseFilter = trim($_GET['course'] ?? '');
$yearFilter   = trim($_GET['year'] ?? '');

$whereClauses = ["1=1"];
$params       = [];
$types        = "";

if (!empty($searchTerm)) {
    $whereClauses[] = "(full_name LIKE ? OR email LIKE ? OR course LIKE ? OR enrollment_no LIKE ?)";
    $likePattern = "%" . $searchTerm . "%";
    $params[] = $likePattern;
    $params[] = $likePattern;
    $params[] = $likePattern;
    $params[] = $likePattern;
    $types .= "ssss";
}

if (!empty($courseFilter)) {
    $whereClauses[] = "course = ?";
    $params[] = $courseFilter;
    $types .= "s";
}

if (!empty($yearFilter)) {
    $whereClauses[] = "year = ?";
    $params[] = (int)$yearFilter;
    $types .= "i";
}

$sql = "SELECT * FROM students WHERE " . implode(" AND ", $whereClauses) . " ORDER BY student_id DESC";
$queryStmt = $mysqli->prepare($sql);

if (!empty($params)) {
    $queryStmt->bind_param($types, ...$params);
}
$queryStmt->execute();
$result   = $queryStmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$queryStmt->close();

// Total count
$countRes = $mysqli->query("SELECT COUNT(*) AS cnt FROM students");
$totalCount = $countRes->fetch_assoc()['cnt'];

$flash = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StudentHub - Student Management CRUD</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .crud-header-actions {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .filter-card {
      background: var(--card-bg, #1e293b);
      border: 1px solid var(--border-color, #334155);
      border-radius: 10px;
      padding: 1.25rem;
      margin-bottom: 1.75rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    .filter-form {
      display: flex;
      flex-wrap: wrap;
      gap: 0.85rem;
      align-items: center;
    }
    .filter-form input, .filter-form select {
      padding: 0.65rem 0.9rem;
      border-radius: 6px;
      border: 1px solid var(--border-color, #475569);
      background: var(--bg-color, #0f172a);
      color: var(--text-color, #f8fafc);
      font-size: 0.92rem;
      outline: none;
    }
    .filter-form input[type="text"] {
      flex: 1 1 240px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.65rem 1.15rem;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.92rem;
      cursor: pointer;
      border: none;
      text-decoration: none;
      transition: background 0.2s, transform 0.1s;
    }
    .btn:active { transform: scale(0.98); }
    .btn-primary { background: #0284c7; color: #fff; }
    .btn-primary:hover { background: #0369a1; }
    .btn-success { background: #16a34a; color: #fff; }
    .btn-success:hover { background: #15803d; }
    .btn-secondary { background: #475569; color: #fff; }
    .btn-secondary:hover { background: #334155; }
    .btn-danger { background: #dc2626; color: #fff; padding: 0.35rem 0.65rem; font-size: 0.82rem; }
    .btn-danger:hover { background: #b91c1c; }
    .btn-edit { background: #0284c7; color: #fff; padding: 0.35rem 0.65rem; font-size: 0.82rem; }
    .btn-edit:hover { background: #0369a1; }
    
    .stats-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.45rem 0.95rem;
      border-radius: 9999px;
      font-size: 0.85rem;
      font-weight: 700;
      background: rgba(56, 189, 248, 0.15);
      color: #38bdf8;
      border: 1px solid #0284c7;
    }

    .data-table-container {
      background: var(--card-bg, #1e293b);
      border: 1px solid var(--border-color, #334155);
      border-radius: 10px;
      overflow-x: auto;
      box-shadow: 0 4px 15px rgba(0,0,0,0.15);
      margin-bottom: 2rem;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.9rem;
    }
    .data-table th, .data-table td {
      padding: 0.85rem 1rem;
      border-bottom: 1px solid var(--border-color, #334155);
    }
    .data-table th {
      background: rgba(15, 23, 42, 0.7);
      color: #94a3b8;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.78rem;
      letter-spacing: 0.04em;
    }
    .data-table tr:hover {
      background: rgba(56, 189, 248, 0.05);
    }
    .actions-cell {
      display: flex;
      gap: 0.4rem;
      align-items: center;
    }

    /* Modals */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(4px);
      z-index: 999;
      justify-content: center;
      align-items: center;
      padding: 1rem;
    }
    .modal-overlay.active {
      display: flex;
    }
    .modal-box {
      background: #1e293b;
      border: 1px solid #475569;
      border-radius: 12px;
      width: 100%;
      max-width: 500px;
      padding: 1.8rem;
      box-shadow: 0 15px 35px rgba(0,0,0,0.5);
      animation: modalSlide 0.2s ease-out;
    }
    @keyframes modalSlide {
      from { transform: translateY(-20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
      border-bottom: 1px solid #334155;
      padding-bottom: 0.75rem;
    }
    .modal-header h3 {
      color: #38bdf8;
      font-size: 1.25rem;
      margin: 0;
    }
    .modal-close {
      background: none;
      border: none;
      color: #94a3b8;
      font-size: 1.5rem;
      cursor: pointer;
      padding: 0;
      line-height: 1;
    }
    .modal-close:hover { color: #f8fafc; }
    .form-row {
      margin-bottom: 1rem;
    }
    .form-row label {
      display: block;
      margin-bottom: 0.35rem;
      font-size: 0.85rem;
      color: #cbd5e1;
      font-weight: 600;
    }
    .form-row input, .form-row select {
      width: 100%;
      padding: 0.65rem 0.85rem;
      border-radius: 6px;
      border: 1px solid #475569;
      background: #0f172a;
      color: #f8fafc;
      font-size: 0.92rem;
      outline: none;
    }
    .form-row input:focus, .form-row select:focus {
      border-color: #38bdf8;
    }
    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem;
    }
    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
      margin-top: 1.5rem;
      padding-top: 1rem;
      border-top: 1px solid #334155;
    }

    .alert {
      padding: 0.85rem 1.2rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .alert-success {
      background: rgba(34, 197, 94, 0.15);
      border: 1px solid #22c55e;
      border-left: 5px solid #22c55e;
      color: #86efac;
    }
    .alert-error {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid #ef4444;
      border-left: 5px solid #ef4444;
      color: #fca5a5;
    }
  </style>
</head>
<body>

  <!-- Standard StudentHub Navigation Header -->
  <header>
    <div class="logo">
      <h1>StudentHub</h1>
      <p class="tagline">Academic Student Information Portal</p>
    </div>
    <nav aria-label="Main Navigation">
      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="dashboard.html">Dashboard</a></li>
        <li><a href="manage_students.php" aria-current="page">Manage Students</a></li>
        <li><a href="events.html">Events</a></li>
        <li><a href="records.php">Live Records</a></li>
        <li><a href="admin.html">Admin</a></li>
      </ul>
    </nav>
  </header>

  <main id="main-content" style="max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem;">

    <div class="crud-header-actions">
      <div>
        <h2>Student Management (CRUD)</h2>
        <p style="color: #94a3b8; font-size: 0.92rem; margin-top: 0.2rem;">
          View, search, filter, add, edit, and delete student records using MySQLi prepared statements.
        </p>
      </div>
      <div style="display: flex; gap: 0.75rem; align-items: center;">
        <span class="stats-badge">Total Records: <?php echo $totalCount; ?></span>
        <button type="button" class="btn btn-success" onclick="openAddModal()">+ Add New Student</button>
      </div>
    </div>

    <!-- Feedback Message -->
    <?php if ($flash): ?>
      <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
        <span><?php echo $flash['type'] === 'success' ? '&#10003;' : '&#9888;'; ?></span>
        <strong><?php echo htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8'); ?></strong>
      </div>
    <?php endif; ?>

    <!-- Search & Filter Card -->
    <section class="filter-card">
      <form method="GET" action="manage_students.php" class="filter-form">
        <input 
          type="text" 
          name="search" 
          placeholder="Search by Name, Email, Course, or Enrollment No..." 
          value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>"
        >
        
        <select name="course">
          <option value="">All Courses</option>
          <option value="B.Tech IT" <?php echo $courseFilter === 'B.Tech IT' ? 'selected' : ''; ?>>B.Tech IT</option>
          <option value="B.Tech CSE" <?php echo $courseFilter === 'B.Tech CSE' ? 'selected' : ''; ?>>B.Tech CSE</option>
          <option value="B.Tech CE" <?php echo $courseFilter === 'B.Tech CE' ? 'selected' : ''; ?>>B.Tech CE</option>
          <option value="B.Tech" <?php echo $courseFilter === 'B.Tech' ? 'selected' : ''; ?>>B.Tech</option>
          <option value="BCA" <?php echo $courseFilter === 'BCA' ? 'selected' : ''; ?>>BCA</option>
          <option value="MCA" <?php echo $courseFilter === 'MCA' ? 'selected' : ''; ?>>MCA</option>
        </select>

        <select name="year">
          <option value="">All Years</option>
          <option value="1" <?php echo $yearFilter === '1' ? 'selected' : ''; ?>>1st Year</option>
          <option value="2" <?php echo $yearFilter === '2' ? 'selected' : ''; ?>>2nd Year</option>
          <option value="3" <?php echo $yearFilter === '3' ? 'selected' : ''; ?>>3rd Year</option>
          <option value="4" <?php echo $yearFilter === '4' ? 'selected' : ''; ?>>4th Year</option>
        </select>

        <button type="submit" class="btn btn-primary">&#128269; Search &amp; Filter</button>
        <?php if (!empty($searchTerm) || !empty($courseFilter) || !empty($yearFilter)): ?>
          <a href="manage_students.php" class="btn btn-secondary">&#10005; Reset</a>
        <?php endif; ?>
      </form>
    </section>

    <!-- Data Table -->
    <div class="data-table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Enrollment No</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Mobile</th>
            <th>Course</th>
            <th>Year</th>
            <th>Gender</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr>
              <td colspan="9" style="text-align: center; padding: 2rem; color: #94a3b8;">
                No students found matching the selected filter criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($students as $s): ?>
              <tr>
                <td><strong>#<?php echo htmlspecialchars($s['student_id'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td><code><?php echo htmlspecialchars($s['enrollment_no'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                <td><strong><?php echo htmlspecialchars($s['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td><?php echo htmlspecialchars($s['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($s['mobile'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span style="color: #38bdf8; font-weight: 600;"><?php echo htmlspecialchars($s['course'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td>Year <?php echo htmlspecialchars($s['year'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($s['gender'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <div class="actions-cell">
                    <button 
                      type="button" 
                      class="btn btn-edit"
                      onclick='openEditModal(<?php echo json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                    >&#9998; Edit</button>
                    <a 
                      href="manage_students.php?action=delete&id=<?php echo urlencode($s['student_id']); ?>" 
                      class="btn btn-danger"
                      onclick="return confirm('Are you sure you want to permanently delete student record <?php echo htmlspecialchars(addslashes($s['full_name']), ENT_QUOTES, 'UTF-8'); ?> (#<?php echo $s['student_id']; ?>)?');"
                    >&#128465; Delete</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>

  <!-- Add Student Modal -->
  <div id="addModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>+ Add New Student</h3>
        <button type="button" class="modal-close" onclick="closeAddModal()">&times;</button>
      </div>
      <form method="POST" action="manage_students.php">
        <input type="hidden" name="action" value="add">

        <div class="form-grid-2">
          <div class="form-row">
            <label for="add_enrollment">Enrollment No *</label>
            <input type="text" id="add_enrollment" name="enrollment_no" placeholder="e.g. D26DCE180" required>
          </div>
          <div class="form-row">
            <label for="add_name">Full Name *</label>
            <input type="text" id="add_name" name="full_name" placeholder="Full Name" required>
          </div>
        </div>

        <div class="form-row">
          <label for="add_email">Email Address *</label>
          <input type="email" id="add_email" name="email" placeholder="student@charusat.edu.in" required>
        </div>

        <div class="form-grid-2">
          <div class="form-row">
            <label for="add_mobile">Mobile Number</label>
            <input type="tel" id="add_mobile" name="mobile" placeholder="10-digit Mobile" pattern="[0-9]{10}">
          </div>
          <div class="form-row">
            <label for="add_gender">Gender</label>
            <select id="add_gender" name="gender">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-row">
            <label for="add_course">Course *</label>
            <select id="add_course" name="course" required>
              <option value="B.Tech IT">B.Tech IT</option>
              <option value="B.Tech CSE">B.Tech CSE</option>
              <option value="B.Tech CE">B.Tech CE</option>
              <option value="BCA">BCA</option>
              <option value="MCA">MCA</option>
            </select>
          </div>
          <div class="form-row">
            <label for="add_year">Academic Year *</label>
            <select id="add_year" name="year" required>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3" selected>3rd Year</option>
              <option value="4">4th Year</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
          <button type="submit" class="btn btn-success">&#10003; Save Student</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Student Modal -->
  <div id="editModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>&#9998; Edit Student Record</h3>
        <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
      </div>
      <form method="POST" action="manage_students.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" id="edit_id" name="student_id" value="">

        <div class="form-grid-2">
          <div class="form-row">
            <label>Enrollment No (Read Only)</label>
            <input type="text" id="edit_enrollment" readonly style="opacity: 0.7; cursor: not-allowed;">
          </div>
          <div class="form-row">
            <label for="edit_name">Full Name *</label>
            <input type="text" id="edit_name" name="full_name" required>
          </div>
        </div>

        <div class="form-row">
          <label for="edit_email">Email Address *</label>
          <input type="email" id="edit_email" name="email" required>
        </div>

        <div class="form-grid-2">
          <div class="form-row">
            <label for="edit_mobile">Mobile Number</label>
            <input type="tel" id="edit_mobile" name="mobile" pattern="[0-9]{10}">
          </div>
          <div class="form-row">
            <label for="edit_gender">Gender</label>
            <select id="edit_gender" name="gender">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-row">
            <label for="edit_course">Course *</label>
            <select id="edit_course" name="course" required>
              <option value="B.Tech IT">B.Tech IT</option>
              <option value="B.Tech CSE">B.Tech CSE</option>
              <option value="B.Tech CE">B.Tech CE</option>
              <option value="B.Tech">B.Tech</option>
              <option value="BCA">BCA</option>
              <option value="MCA">MCA</option>
            </select>
          </div>
          <div class="form-row">
            <label for="edit_year">Academic Year *</label>
            <select id="edit_year" name="year" required>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">&#10003; Update Changes</button>
        </div>
      </form>
    </div>
  </div>

  <footer style="margin-top: 3rem; text-align: center; padding: 1.5rem; color: #64748b; font-size: 0.88rem; border-top: 1px solid #334155;">
    <p>&copy; 2026 StudentHub Academic Portal | College Project</p>
  </footer>

  <script>
    function openAddModal() {
      document.getElementById('addModal').classList.add('active');
    }
    function closeAddModal() {
      document.getElementById('addModal').classList.remove('active');
    }

    function openEditModal(student) {
      document.getElementById('edit_id').value = student.student_id;
      document.getElementById('edit_enrollment').value = student.enrollment_no;
      document.getElementById('edit_name').value = student.full_name;
      document.getElementById('edit_email').value = student.email;
      document.getElementById('edit_mobile').value = student.mobile || '';
      document.getElementById('edit_gender').value = student.gender || 'Male';
      document.getElementById('edit_course').value = student.course;
      document.getElementById('edit_year').value = student.year;
      document.getElementById('editModal').classList.add('active');
    }
    function closeEditModal() {
      document.getElementById('editModal').classList.remove('active');
    }

    // Close on backdrop click
    window.onclick = function(e) {
      if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
      }
    };
  </script>

</body>
</html>
