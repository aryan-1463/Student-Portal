<?php
// api/events_api.php - StudentHub JSON REST API for Events
// Practical 13: Asynchronous CRUD Endpoints consumed via JavaScript Fetch API

// Set HTTP headers for REST JSON API
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Centralized JSON response helper function
function sendResponse($statusCode, $success, $message, $data = null, $meta = []) {
    http_response_code($statusCode);
    echo json_encode([
        'status'    => $statusCode,
        'success'   => $success,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'data'      => $data,
        'meta'      => $meta
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Database Connection using PDO with Exception Handling
$host = '127.0.0.1';
$dbname = 'studenthub_db';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    sendResponse(500, false, "Database connection failed: " . $e->getMessage());
}

// Parse input payload (Supports JSON Body, Form-Data, and Query Strings)
$requestMethod = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$jsonBody = json_decode($rawInput, true) ?? [];
$inputData = array_merge($_GET, $_POST, $jsonBody);

$action = $inputData['action'] ?? '';

// Determine action based on HTTP method or action parameter
if (empty($action)) {
    if ($requestMethod === 'GET') {
        $action = isset($inputData['id']) ? 'get' : 'list';
    } elseif ($requestMethod === 'POST') {
        $action = 'create';
    } elseif ($requestMethod === 'PUT') {
        $action = 'update';
    } elseif ($requestMethod === 'DELETE') {
        $action = 'delete';
    }
}

// -------------------------------------------------------------
// ROUTE 1: GET /api/events_api.php?action=list (Supports Search Filter)
// -------------------------------------------------------------
if ($action === 'list' && ($requestMethod === 'GET' || $requestMethod === 'POST')) {
    $search = trim($inputData['search'] ?? '');
    $category = trim($inputData['category'] ?? '');

    try {
        $sql = "SELECT event_id, title, description, category, event_date, location, poster_image, max_capacity, created_at 
                FROM events WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
            $wildcard = "%{$search}%";
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        if (!empty($category) && $category !== 'All') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY event_date ASC, event_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        sendResponse(200, true, "Events retrieved successfully", $events, [
            'total_count'   => count($events),
            'search_query'  => $search,
            'category_filter' => $category ?: 'All'
        ]);
    } catch (PDOException $e) {
        sendResponse(500, false, "Failed to retrieve events: " . $e->getMessage());
    }
}

// -------------------------------------------------------------
// ROUTE 2: GET /api/events_api.php?action=get&id=X (Single Event)
// -------------------------------------------------------------
if ($action === 'get') {
    $id = (int)($inputData['id'] ?? 0);
    if ($id <= 0) {
        sendResponse(400, false, "Validation Error: A valid numeric Event ID is required.");
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $event = $stmt->fetch();

        if ($event) {
            sendResponse(200, true, "Event details retrieved", $event);
        } else {
            sendResponse(404, false, "Event with ID {$id} was not found.");
        }
    } catch (PDOException $e) {
        sendResponse(500, false, "Database query error: " . $e->getMessage());
    }
}

// -------------------------------------------------------------
// ROUTE 3: POST /api/events_api.php (action=create) (Create Event)
// -------------------------------------------------------------
if ($action === 'create') {
    $title       = trim($inputData['title'] ?? '');
    $description = trim($inputData['description'] ?? '');
    $category    = trim($inputData['category'] ?? 'Technical');
    $eventDate   = trim($inputData['event_date'] ?? '');
    $location    = trim($inputData['location'] ?? '');
    $capacity    = (int)($inputData['max_capacity'] ?? 100);
    $posterImage = trim($inputData['poster_image'] ?? 'default_event.jpg');

    // Server-side validation
    $errors = [];
    if (strlen($title) < 3) {
        $errors[] = "Title must be at least 3 characters long.";
    }
    if (empty($description)) {
        $errors[] = "Event description is required.";
    }
    if (empty($eventDate)) {
        $errors[] = "Event date is required.";
    }
    if (empty($location)) {
        $errors[] = "Event location / venue is required.";
    }
    if ($capacity < 1) {
        $errors[] = "Maximum capacity must be at least 1.";
    }

    if (!empty($errors)) {
        sendResponse(400, false, "Validation Failed", ['errors' => $errors]);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, description, category, event_date, location, poster_image, max_capacity) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $category, $eventDate, $location, $posterImage, $capacity]);
        $newId = $pdo->lastInsertId();

        sendResponse(201, true, "Event '{$title}' created successfully!", [
            'event_id'     => (int)$newId,
            'title'        => $title,
            'category'     => $category,
            'event_date'   => $eventDate,
            'location'     => $location,
            'max_capacity' => $capacity
        ]);
    } catch (PDOException $e) {
        sendResponse(500, false, "Failed to insert event: " . $e->getMessage());
    }
}

// -------------------------------------------------------------
// ROUTE 4: POST /api/events_api.php (action=update) (Update Event)
// -------------------------------------------------------------
if ($action === 'update') {
    $eventId     = (int)($inputData['event_id'] ?? $inputData['id'] ?? 0);
    $title       = trim($inputData['title'] ?? '');
    $description = trim($inputData['description'] ?? '');
    $category    = trim($inputData['category'] ?? 'Technical');
    $eventDate   = trim($inputData['event_date'] ?? '');
    $location    = trim($inputData['location'] ?? '');
    $capacity    = (int)($inputData['max_capacity'] ?? 100);

    if ($eventId <= 0) {
        sendResponse(400, false, "Validation Error: Valid Event ID is required for update.");
    }

    // Verify record exists
    $checkStmt = $pdo->prepare("SELECT event_id FROM events WHERE event_id = ?");
    $checkStmt->execute([$eventId]);
    if (!$checkStmt->fetch()) {
        sendResponse(404, false, "Cannot update: Event ID {$eventId} does not exist.");
    }

    // Validation
    $errors = [];
    if (strlen($title) < 3) $errors[] = "Title must be at least 3 characters long.";
    if (empty($eventDate)) $errors[] = "Event date is required.";
    if (empty($location)) $errors[] = "Event location is required.";

    if (!empty($errors)) {
        sendResponse(400, false, "Validation Failed", ['errors' => $errors]);
    }

    try {
        $stmt = $pdo->prepare("UPDATE events 
                               SET title = ?, description = ?, category = ?, event_date = ?, location = ?, max_capacity = ? 
                               WHERE event_id = ?");
        $stmt->execute([$title, $description, $category, $eventDate, $location, $capacity, $eventId]);

        sendResponse(200, true, "Event #{$eventId} '{$title}' updated successfully!", [
            'event_id'     => $eventId,
            'title'        => $title,
            'category'     => $category,
            'event_date'   => $eventDate,
            'location'     => $location,
            'max_capacity' => $capacity
        ]);
    } catch (PDOException $e) {
        sendResponse(500, false, "Failed to update event: " . $e->getMessage());
    }
}

// -------------------------------------------------------------
// ROUTE 5: POST /api/events_api.php (action=delete) (Delete Event)
// -------------------------------------------------------------
if ($action === 'delete') {
    $eventId = (int)($inputData['event_id'] ?? $inputData['id'] ?? 0);

    if ($eventId <= 0) {
        sendResponse(400, false, "Validation Error: Valid Event ID is required for deletion.");
    }

    // Check existence
    $checkStmt = $pdo->prepare("SELECT event_id, title, poster_image FROM events WHERE event_id = ?");
    $checkStmt->execute([$eventId]);
    $event = $checkStmt->fetch();

    if (!$event) {
        sendResponse(404, false, "Cannot delete: Event ID {$eventId} not found.");
    }

    try {
        $delStmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
        $delStmt->execute([$eventId]);

        sendResponse(200, true, "Event #{$eventId} '{$event['title']}' deleted successfully!", [
            'deleted_id' => $eventId,
            'title'      => $event['title']
        ]);
    } catch (PDOException $e) {
        sendResponse(500, false, "Failed to delete event: " . $e->getMessage());
    }
}

// Fallback for unsupported endpoints or invalid actions
sendResponse(400, false, "Bad Request: Unrecognized action '{$action}' or unsupported HTTP method.");
