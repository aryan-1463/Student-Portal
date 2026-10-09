<?php
// manage_events.php - StudentHub Event Management CRUD Module
// Practical 12: Event Management CRUD with Poster Upload and File Validation (MySQLi Prepared Statements)

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

// Helper function: Secure file upload with size & type validation
function handle_poster_upload($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error (Code: ' . $file['error'] . ')'];
    }

    // 1. File Size Validation (Max 2MB)
    $maxSize = 2 * 1024 * 1024; // 2 Megabytes
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds the 2MB limit! Please upload an image under 2MB.'];
    }

    // 2. File Type & MIME Validation
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($ext, $allowedExts) || !in_array($mimeType, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid file format! Only JPG, JPEG, PNG, and WEBP image files are accepted.'];
    }

    // 3. Generate safe unique filename
    $uploadDir = __DIR__ . '/uploads/events/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uniqueName = 'poster_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $uniqueName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $uniqueName];
    } else {
        return ['success' => false, 'error' => 'Failed to save uploaded poster to server directory.'];
    }
}

// Handle POST: Add Event (Create / INSERT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? 'Technical');
    $eventDate   = trim($_POST['event_date'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $capacity    = (int)($_POST['max_capacity'] ?? 100);

    if (empty($title) || empty($description) || empty($eventDate) || empty($location)) {
        $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Please fill in all required event details.'];
    } else {
        $uploadResult = handle_poster_upload($_FILES['poster'] ?? null);

        if (!$uploadResult['success']) {
            $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $uploadResult['error']];
        } else {
            $posterFilename = $uploadResult['filename'];

            $stmt = $mysqli->prepare("INSERT INTO events (title, description, category, event_date, location, poster_image, max_capacity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $title, $description, $category, $eventDate, $location, $posterFilename, $capacity);

            if ($stmt->execute()) {
                $_SESSION['flash_msg'] = ['type' => 'success', 'text' => "Event '{$title}' created successfully!"];
            } else {
                $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Failed to add event: ' . $mysqli->error];
            }
            $stmt->close();
        }
    }
    header("Location: manage_events.php");
    exit;
}

// Handle POST: Edit Event (Update / UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $eventId     = (int)($_POST['event_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? 'Technical');
    $eventDate   = trim($_POST['event_date'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $capacity    = (int)($_POST['max_capacity'] ?? 100);

    if ($eventId <= 0 || empty($title) || empty($eventDate) || empty($location)) {
        $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Invalid event update data provided.'];
    } else {
        // Fetch current poster
        $currStmt = $mysqli->prepare("SELECT poster_image FROM events WHERE event_id = ?");
        $currStmt->bind_param("i", $eventId);
        $currStmt->execute();
        $currEvent = $currStmt->get_result()->fetch_assoc();
        $currStmt->close();

        $posterFilename = $currEvent['poster_image'] ?? null;

        // Check if new poster uploaded
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = handle_poster_upload($_FILES['poster']);
            if (!$uploadResult['success']) {
                $_SESSION['flash_msg'] = ['type' => 'error', 'text' => $uploadResult['error']];
                header("Location: manage_events.php");
                exit;
            } else {
                // Delete previous poster if exists
                if (!empty($posterFilename)) {
                    $oldPath = __DIR__ . '/uploads/events/' . $posterFilename;
                    if (file_exists($oldPath)) { @unlink($oldPath); }
                }
                $posterFilename = $uploadResult['filename'];
            }
        }

        $stmt = $mysqli->prepare("UPDATE events SET title = ?, description = ?, category = ?, event_date = ?, location = ?, poster_image = ?, max_capacity = ? WHERE event_id = ?");
        $stmt->bind_param("ssssssii", $title, $description, $category, $eventDate, $location, $posterFilename, $capacity, $eventId);

        if ($stmt->execute()) {
            $_SESSION['flash_msg'] = ['type' => 'success', 'text' => "Event #{$eventId} '{$title}' updated successfully!"];
        } else {
            $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Failed to update event: ' . $mysqli->error];
        }
        $stmt->close();
    }
    header("Location: manage_events.php");
    exit;
}

// Handle GET: Delete Event (Delete / DELETE)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $eventId = (int)$_GET['id'];
    if ($eventId > 0) {
        // Fetch poster filename to delete from disk
        $currStmt = $mysqli->prepare("SELECT poster_image, title FROM events WHERE event_id = ?");
        $currStmt->bind_param("i", $eventId);
        $currStmt->execute();
        $ev = $currStmt->get_result()->fetch_assoc();
        $currStmt->close();

        if ($ev) {
            if (!empty($ev['poster_image'])) {
                $filePath = __DIR__ . '/uploads/events/' . $ev['poster_image'];
                if (file_exists($filePath)) { @unlink($filePath); }
            }
            $delStmt = $mysqli->prepare("DELETE FROM events WHERE event_id = ?");
            $delStmt->bind_param("i", $eventId);
            if ($delStmt->execute()) {
                $_SESSION['flash_msg'] = ['type' => 'success', 'text' => "Event '{$ev['title']}' deleted successfully!"];
            } else {
                $_SESSION['flash_msg'] = ['type' => 'error', 'text' => 'Failed to delete event: ' . $mysqli->error];
            }
            $delStmt->close();
        }
    }
    header("Location: manage_events.php");
    exit;
}

// Search and Filter Handling (Read / SELECT)
$searchTerm = trim($_GET['search'] ?? '');
$catFilter  = trim($_GET['category'] ?? '');

$whereClauses = ["1=1"];
$params       = [];
$types        = "";

if (!empty($searchTerm)) {
    $whereClauses[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $like = "%" . $searchTerm . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if (!empty($catFilter)) {
    $whereClauses[] = "category = ?";
    $params[] = $catFilter;
    $types .= "s";
}

$sql = "SELECT * FROM events WHERE " . implode(" AND ", $whereClauses) . " ORDER BY event_date ASC";
$queryStmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $queryStmt->bind_param($types, ...$params);
}
$queryStmt->execute();
$events = $queryStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$queryStmt->close();

$countRes = $mysqli->query("SELECT COUNT(*) AS cnt FROM events");
$totalCount = $countRes->fetch_assoc()['cnt'];

$flash = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StudentHub - Event Management CRUD</title>
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

    .events-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    .event-card {
      background: var(--card-bg, #1e293b);
      border: 1px solid var(--border-color, #334155);
      border-radius: 12px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .event-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }
    .event-poster-container {
      width: 100%;
      height: 180px;
      background: #0f172a;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    .event-poster-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .poster-placeholder {
      color: #64748b;
      font-size: 0.9rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.3rem;
    }
    .category-tag {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(15, 23, 42, 0.85);
      border: 1px solid #38bdf8;
      color: #38bdf8;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 0.25rem 0.6rem;
      border-radius: 6px;
      backdrop-filter: blur(4px);
    }
    .event-body {
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    .event-title {
      font-size: 1.15rem;
      color: #f8fafc;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    .event-desc {
      color: #94a3b8;
      font-size: 0.88rem;
      line-height: 1.45;
      margin-bottom: 1rem;
      flex: 1;
    }
    .event-meta {
      font-size: 0.82rem;
      color: #cbd5e1;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
      padding-top: 0.75rem;
      border-top: 1px solid #334155;
      margin-bottom: 1rem;
    }
    .event-actions {
      display: flex;
      justify-content: flex-end;
      gap: 0.5rem;
    }

    /* Modal Styling */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(4px);
      z-index: 999;
      justify-content: center;
      align-items: center;
      padding: 1rem;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
      background: #1e293b;
      border: 1px solid #475569;
      border-radius: 12px;
      width: 100%;
      max-width: 520px;
      padding: 1.8rem;
      box-shadow: 0 15px 35px rgba(0,0,0,0.5);
      animation: modalSlide 0.2s ease-out;
      max-height: 90vh;
      overflow-y: auto;
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
    .modal-header h3 { color: #38bdf8; font-size: 1.25rem; margin: 0; }
    .modal-close {
      background: none; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer;
    }
    .form-row { margin-bottom: 1rem; }
    .form-row label {
      display: block; margin-bottom: 0.35rem; font-size: 0.85rem; color: #cbd5e1; font-weight: 600;
    }
    .form-row input, .form-row select, .form-row textarea {
      width: 100%; padding: 0.65rem 0.85rem; border-radius: 6px;
      border: 1px solid #475569; background: #0f172a; color: #f8fafc; font-size: 0.92rem; outline: none;
    }
    .form-row textarea { resize: vertical; min-height: 70px; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    .file-input-help {
      font-size: 0.75rem; color: #94a3b8; margin-top: 0.3rem; display: block;
    }
    .modal-footer {
      display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;
      padding-top: 1rem; border-top: 1px solid #334155;
    }

    .alert {
      padding: 0.85rem 1.2rem; border-radius: 8px; margin-bottom: 1.5rem;
      font-size: 0.95rem; display: flex; align-items: center; gap: 0.6rem;
    }
    .alert-success {
      background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e;
      border-left: 5px solid #22c55e; color: #86efac;
    }
    .alert-error {
      background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444;
      border-left: 5px solid #ef4444; color: #fca5a5;
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
        <li><a href="manage_students.php">Manage Students</a></li>
        <li><a href="manage_events.php" aria-current="page">Manage Events</a></li>
        <li><a href="events.html">Public Events</a></li>
        <li><a href="admin.html">Admin</a></li>
      </ul>
    </nav>
  </header>

  <main id="main-content" style="max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem;">

    <div class="crud-header-actions">
      <div>
        <h2>Event Management (CRUD &amp; Poster Upload)</h2>
        <p style="color: #94a3b8; font-size: 0.92rem; margin-top: 0.2rem;">
          Add, view, edit, and delete events with validated poster upload (Max 2MB, JPG/PNG/WEBP).
        </p>
      </div>
      <div style="display: flex; gap: 0.75rem; align-items: center;">
        <span class="stats-badge">Total Events: <?php echo $totalCount; ?></span>
        <button type="button" class="btn btn-success" onclick="openAddModal()">+ Add New Event</button>
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
      <form method="GET" action="manage_events.php" class="filter-form">
        <input 
          type="text" 
          name="search" 
          placeholder="Search by Title, Location, or Description..." 
          value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>"
        >
        
        <select name="category">
          <option value="">All Categories</option>
          <option value="Technical" <?php echo $catFilter === 'Technical' ? 'selected' : ''; ?>>Technical</option>
          <option value="Academic" <?php echo $catFilter === 'Academic' ? 'selected' : ''; ?>>Academic</option>
          <option value="Competition" <?php echo $catFilter === 'Competition' ? 'selected' : ''; ?>>Competition</option>
          <option value="Workshop" <?php echo $catFilter === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
          <option value="Cultural" <?php echo $catFilter === 'Cultural' ? 'selected' : ''; ?>>Cultural</option>
          <option value="Sports" <?php echo $catFilter === 'Sports' ? 'selected' : ''; ?>>Sports</option>
        </select>

        <button type="submit" class="btn btn-primary">&#128269; Filter Events</button>
        <?php if (!empty($searchTerm) || !empty($catFilter)): ?>
          <a href="manage_events.php" class="btn btn-secondary">&#10005; Reset</a>
        <?php endif; ?>
      </form>
    </section>

    <!-- Events Grid Cards -->
    <div class="events-grid">
      <?php if (empty($events)): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: #1e293b; border-radius: 12px; color: #94a3b8;">
          No events found matching your filter criteria. Click <strong>+ Add New Event</strong> to create one!
        </div>
      <?php else: ?>
        <?php foreach ($events as $ev): ?>
          <article class="event-card">
            <div class="event-poster-container">
              <?php if (!empty($ev['poster_image']) && file_exists(__DIR__ . '/uploads/events/' . $ev['poster_image'])): ?>
                <img src="uploads/events/<?php echo htmlspecialchars($ev['poster_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($ev['title'], ENT_QUOTES, 'UTF-8'); ?> Poster" class="event-poster-img">
              <?php else: ?>
                <div class="poster-placeholder">
                  <span style="font-size: 2rem;">&#128197;</span>
                  <span>No Poster Uploaded</span>
                </div>
              <?php endif; ?>
              <span class="category-tag"><?php echo htmlspecialchars($ev['category'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="event-body">
              <h3 class="event-title"><?php echo htmlspecialchars($ev['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p class="event-desc"><?php echo htmlspecialchars($ev['description'], ENT_QUOTES, 'UTF-8'); ?></p>

              <div class="event-meta">
                <div>&#128197; <strong>Date:</strong> <?php echo date('d M Y', strtotime($ev['event_date'])); ?></div>
                <div>&#128205; <strong>Location:</strong> <?php echo htmlspecialchars($ev['location'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div>&#128101; <strong>Max Capacity:</strong> <?php echo (int)$ev['max_capacity']; ?> seats</div>
              </div>

              <div class="event-actions">
                <button 
                  type="button" 
                  class="btn btn-edit"
                  onclick='openEditModal(<?php echo json_encode($ev, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                >&#9998; Edit</button>
                <a 
                  href="manage_events.php?action=delete&id=<?php echo urlencode($ev['event_id']); ?>" 
                  class="btn btn-danger"
                  onclick="return confirm('Are you sure you want to permanently delete event <?php echo htmlspecialchars(addslashes($ev['title']), ENT_QUOTES, 'UTF-8'); ?>?');"
                >&#128465; Delete</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>

  <!-- Add Event Modal -->
  <div id="addModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>+ Add New Event</h3>
        <button type="button" class="modal-close" onclick="closeAddModal()">&times;</button>
      </div>
      <form method="POST" action="manage_events.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <div class="form-row">
          <label for="add_title">Event Title *</label>
          <input type="text" id="add_title" name="title" placeholder="e.g. Annual Tech Symposium 2026" required>
        </div>

        <div class="form-row">
          <label for="add_desc">Event Description *</label>
          <textarea id="add_desc" name="description" placeholder="Brief details about the event..." required></textarea>
        </div>

        <div class="form-grid-2">
          <div class="form-row">
            <label for="add_category">Category *</label>
            <select id="add_category" name="category" required>
              <option value="Technical">Technical</option>
              <option value="Academic">Academic</option>
              <option value="Competition">Competition</option>
              <option value="Workshop">Workshop</option>
              <option value="Cultural">Cultural</option>
              <option value="Sports">Sports</option>
            </select>
          </div>
          <div class="form-row">
            <label for="add_date">Event Date *</label>
            <input type="date" id="add_date" name="event_date" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-row">
            <label for="add_location">Location *</label>
            <input type="text" id="add_location" name="location" placeholder="e.g. Auditorium A" required>
          </div>
          <div class="form-row">
            <label for="add_capacity">Capacity (Seats)</label>
            <input type="number" id="add_capacity" name="max_capacity" value="100" min="10">
          </div>
        </div>

        <div class="form-row">
          <label for="add_poster">Event Poster Image (Max 2MB)</label>
          <input type="file" id="add_poster" name="poster" accept=".jpg,.jpeg,.png,.webp">
          <span class="file-input-help">&#128206; Allowed: JPG, JPEG, PNG, WEBP. Maximum file size: 2MB.</span>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
          <button type="submit" class="btn btn-success">&#10003; Create Event</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Event Modal -->
  <div id="editModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>&#9998; Edit Event Record</h3>
        <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
      </div>
      <form method="POST" action="manage_events.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" id="edit_id" name="event_id" value="">

        <div class="form-row">
          <label for="edit_title">Event Title *</label>
          <input type="text" id="edit_title" name="title" required>
        </div>

        <div class="form-row">
          <label for="edit_desc">Event Description *</label>
          <textarea id="edit_desc" name="description" required></textarea>
        </div>

        <div class="form-grid-2">
          <div class="form-row">
            <label for="edit_category">Category *</label>
            <select id="edit_category" name="category" required>
              <option value="Technical">Technical</option>
              <option value="Academic">Academic</option>
              <option value="Competition">Competition</option>
              <option value="Workshop">Workshop</option>
              <option value="Cultural">Cultural</option>
              <option value="Sports">Sports</option>
            </select>
          </div>
          <div class="form-row">
            <label for="edit_date">Event Date *</label>
            <input type="date" id="edit_date" name="event_date" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-row">
            <label for="edit_location">Location *</label>
            <input type="text" id="edit_location" name="location" required>
          </div>
          <div class="form-row">
            <label for="edit_capacity">Capacity (Seats)</label>
            <input type="number" id="edit_capacity" name="max_capacity" min="10">
          </div>
        </div>

        <div class="form-row">
          <label for="edit_poster">Change Poster Image (Optional, Max 2MB)</label>
          <input type="file" id="edit_poster" name="poster" accept=".jpg,.jpeg,.png,.webp">
          <span class="file-input-help" id="current_poster_status">&#128206; Leave blank to keep existing poster image.</span>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">&#10003; Update Event</button>
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

    function openEditModal(ev) {
      document.getElementById('edit_id').value = ev.event_id;
      document.getElementById('edit_title').value = ev.title;
      document.getElementById('edit_desc').value = ev.description;
      document.getElementById('edit_category').value = ev.category;
      document.getElementById('edit_date').value = ev.event_date;
      document.getElementById('edit_location').value = ev.location;
      document.getElementById('edit_capacity').value = ev.max_capacity || 100;

      var posterStatus = document.getElementById('current_poster_status');
      if (ev.poster_image) {
        posterStatus.innerHTML = '&#128206; Current poster: <strong>' + ev.poster_image + '</strong>. Choose new file to replace.';
      } else {
        posterStatus.innerHTML = '&#128206; No poster attached currently. Allowed: JPG, PNG, WEBP (Max 2MB).';
      }

      document.getElementById('editModal').classList.add('active');
    }
    function closeEditModal() {
      document.getElementById('editModal').classList.remove('active');
    }

    window.onclick = function(e) {
      if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
      }
    };
  </script>

</body>
</html>
