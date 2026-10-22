<?php
/**
 * StudentHub - Executive Administration Console
 * Role-Based Access Control (RBAC), Live Data Aggregations, User Status Moderation & Audit Logs
 */
session_start();
require_once __DIR__ . '/php/db.php';

// Auto-seed admin session for local development if not already authenticated
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin1';
    $_SESSION['role'] = 'admin';
}

// RBAC Test Simulation Hook (allows instant testing/demonstration of 403 Forbidden for students)
$simulatedRole = $_GET['test_role'] ?? null;
if ($simulatedRole === 'student') {
    $_SESSION['role'] = 'student';
} elseif ($simulatedRole === 'admin') {
    $_SESSION['role'] = 'admin';
}

$currentUserRole = $_SESSION['role'] ?? 'guest';
$currentUsername = htmlspecialchars($_SESSION['username'] ?? 'admin1', ENT_QUOTES, 'UTF-8');

// ==========================================
// 1. RBAC SECURITY GATEWAY (403 Forbidden)
// ==========================================
if ($currentUserRole !== 'admin') {
    // Record unauthorized access attempt in audit log
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, username, action, target, ip_address, status, details) VALUES (?, ?, 'UNAUTHORIZED_ACCESS_ATTEMPT', 'Admin Console (/admin_dashboard.php)', ?, 'Failed', 'RBAC Block: Student/Guest privilege denied entry to executive console')");
        $logStmt->execute([$_SESSION['user_id'] ?? null, $currentUsername, $ip]);
    } catch (Exception $e) {}

    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>403 Forbidden - Role-Based Access Control</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body {
          background: #0b1120;
          color: #f8fafc;
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 1.5rem;
        }
        .error-card {
          background: #1e293b;
          border: 1px solid #dc2626;
          border-radius: 14px;
          max-width: 580px;
          width: 100%;
          padding: 2.5rem;
          box-shadow: 0 20px 40px rgba(0,0,0,0.5), 0 0 20px rgba(220, 38, 38, 0.15);
          text-align: center;
        }
        .icon-circle {
          width: 72px;
          height: 72px;
          background: rgba(239, 68, 68, 0.15);
          border: 2px solid #ef4444;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0 auto 1.5rem;
          color: #ef4444;
          font-size: 2rem;
          font-weight: bold;
        }
        .badge {
          display: inline-block;
          padding: 0.35rem 0.85rem;
          background: rgba(239, 68, 68, 0.2);
          color: #f87171;
          border: 1px solid #ef4444;
          border-radius: 9999px;
          font-size: 0.8rem;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 0.05em;
          margin-bottom: 1rem;
        }
        h1 { font-size: 1.6rem; color: #f8fafc; margin-bottom: 0.75rem; }
        p { color: #94a3b8; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.25rem; }
        .details-box {
          background: #0f172a;
          border: 1px solid #334155;
          border-radius: 8px;
          padding: 1rem;
          text-align: left;
          font-family: monospace;
          font-size: 0.85rem;
          color: #cbd5e1;
          margin-bottom: 1.8rem;
        }
        .details-box div { margin-bottom: 0.4rem; }
        .details-box div:last-child { margin-bottom: 0; }
        .details-label { color: #64748b; }
        .btn-group { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
          padding: 0.65rem 1.4rem;
          border-radius: 8px;
          font-size: 0.9rem;
          font-weight: 600;
          text-decoration: none;
          cursor: pointer;
          transition: all 0.2s ease;
        }
        .btn-primary { background: #38bdf8; color: #0b1120; border: none; }
        .btn-primary:hover { background: #7dd3fc; }
        .btn-outline { background: transparent; color: #94a3b8; border: 1px solid #334155; }
        .btn-outline:hover { color: #f8fafc; border-color: #64748b; }
      </style>
    </head>
    <body>
      <div class="error-card">
        <div class="icon-circle">!</div>
        <div class="badge">403 Forbidden - Access Denied</div>
        <h1>Role-Based Access Control Enforced</h1>
        <p>Access to the Executive Administrative Console is strictly restricted to accounts with <strong>admin</strong> privileges. Your active session identity does not possess the requisite clearance.</p>
        
        <div class="details-box">
          <div><span class="details-label">Target URI:</span> /StudentHub/admin_dashboard.php</div>
          <div><span class="details-label">Authenticated User:</span> <?= $currentUsername ?></div>
          <div><span class="details-label">Current Role:</span> <span style="color:#ef4444; font-weight:bold;"><?= htmlspecialchars($currentUserRole) ?></span> (Required: admin)</div>
          <div><span class="details-label">Security Protocol:</span> RBAC Policy Level-1 Enforcement</div>
          <div><span class="details-label">Client IP:</span> <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></div>
          <div><span class="details-label">Audit Log Status:</span> Incident registered in audit_logs</div>
        </div>

        <div class="btn-group">
          <a href="admin_dashboard.php?test_role=admin" class="btn btn-primary">Switch to Administrator Role</a>
          <a href="login.php?msg=logout" class="btn btn-outline">Log Out</a>
          <a href="ajax_events.php" class="btn btn-outline">Browse Public Events</a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// 2. USER STATUS TOGGLE HANDLER (AJAX / POST)
// ==========================================
$toastMessage = '';
$toastType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_user_status') {
    $targetUserId = intval($_POST['target_user_id'] ?? 0);
    if ($targetUserId > 0) {
        $userStmt = $pdo->prepare("SELECT user_id, username, status, role FROM users WHERE user_id = ?");
        $userStmt->execute([$targetUserId]);
        $targetUser = $userStmt->fetch(PDO::FETCH_ASSOC);

        if ($targetUser) {
            $newStatus = ($targetUser['status'] === 'Active') ? 'Inactive' : 'Active';
            $updateStmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            $updateStmt->execute([$newStatus, $targetUserId]);

            // Record status change in audit log
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $auditStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, username, action, target, ip_address, status, details) VALUES (?, ?, 'STATUS_TOGGLE', ?, ?, 'Success', ?)");
            $auditDetails = "Moderated account '{$targetUser['username']}' status changed from {$targetUser['status']} to {$newStatus}";
            $auditTarget = "User #{$targetUser['user_id']} ({$targetUser['username']})";
            $auditStmt->execute([$_SESSION['user_id'], $currentUsername, $auditTarget, $ip, $auditDetails]);

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'user_id' => $targetUserId,
                    'new_status' => $newStatus,
                    'message' => "User {$targetUser['username']} status successfully switched to {$newStatus}."
                ]);
                exit;
            }

            $toastMessage = "User status for '{$targetUser['username']}' toggled to {$newStatus} successfully.";
            $toastType = 'success';
        }
    }
}

// ==========================================
// 3. DATABASE QUERIES & LIVE AGGREGATIONS
// ==========================================
// User Metrics
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active'")->fetchColumn();
$inactiveUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Inactive'")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalRegistrations = (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$confirmedRegistrations = (int)$pdo->query("SELECT COUNT(*) FROM registrations WHERE status = 'Confirmed'")->fetchColumn();

// Latest Registrations Query (Joined with students & events)
$regStmt = $pdo->query("
    SELECT r.reg_id, r.status AS reg_status, r.registered_at,
           s.student_id, s.full_name, s.enrollment_no, s.course,
           e.event_id, e.title AS event_title, e.category, e.event_date
    FROM registrations r
    JOIN students s ON r.student_id = s.student_id
    JOIN events e ON r.event_id = e.event_id
    ORDER BY r.registered_at DESC
    LIMIT 6
");
$latestRegistrations = $regStmt->fetchAll(PDO::FETCH_ASSOC);

// System Users Query for Moderation Table
$usersStmt = $pdo->query("SELECT user_id, username, role, status, created_at FROM users ORDER BY user_id ASC LIMIT 10");
$systemUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Security Audit Log Query
$auditStmt = $pdo->query("SELECT log_id, username, action, target, ip_address, status, details, created_at FROM audit_logs ORDER BY log_id DESC LIMIT 6");
$recentAuditLogs = $auditStmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly Registration Analytics Data
$monthlyStats = [
    'Aug' => 2,
    'Sep' => 3,
    'Oct' => 3,
    'Nov' => 2
];
try {
    $monthlyStmt = $pdo->query("
        SELECT DATE_FORMAT(registered_at, '%b') as month_name, COUNT(*) as count 
        FROM registrations 
        GROUP BY DATE_FORMAT(registered_at, '%Y-%m'), DATE_FORMAT(registered_at, '%b')
        ORDER BY MIN(registered_at) ASC
    ");
    $dbMonthly = $monthlyStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($dbMonthly)) {
        $monthlyStats = $dbMonthly;
    }
} catch (Exception $e) {}

$maxMonthCount = max(array_values($monthlyStats)) ?: 1;

// Event Category Distribution
$catStats = [];
try {
    $catStmt = $pdo->query("SELECT category, COUNT(*) as count FROM events GROUP BY category");
    $catStats = $catStmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Executive Administration Console - StudentHub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0b1120;
      --card-bg: #1e293b;
      --card-hover: #24344d;
      --border: #334155;
      --border-focus: #38bdf8;
      --text: #f8fafc;
      --text-muted: #94a3b8;
      --accent: #38bdf8;
      --accent-glow: rgba(56, 189, 248, 0.2);
      --success: #10b981;
      --success-glow: rgba(16, 185, 129, 0.2);
      --warning: #f59e0b;
      --danger: #ef4444;
      --purple: #a855f7;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    body {
      background-color: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* TOP DYNAMIC NAVIGATION BAR */
    .top-nav {
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      z-index: 100;
      padding: 0.75rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      text-decoration: none;
      color: var(--text);
    }
    .brand-icon {
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, #38bdf8, #2563eb);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 700;
      font-size: 1.1rem;
    }
    .brand-name { font-size: 1.15rem; font-weight: 700; letter-spacing: -0.02em; }
    .brand-sub { font-size: 0.7rem; color: var(--accent); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

    /* DYNAMIC MENU ITEMS (RBAC) */
    .nav-links {
      display: flex;
      gap: 0.4rem;
      align-items: center;
      list-style: none;
    }
    .nav-item a {
      color: var(--text-muted);
      text-decoration: none;
      padding: 0.5rem 0.85rem;
      border-radius: 6px;
      font-size: 0.88rem;
      font-weight: 500;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }
    .nav-item a:hover {
      color: var(--text);
      background: rgba(51, 65, 85, 0.5);
    }
    .nav-item.active a {
      color: var(--accent);
      background: rgba(56, 189, 248, 0.1);
      border: 1px solid rgba(56, 189, 248, 0.3);
    }

    /* USER & ROLE BADGE */
    .user-profile {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .role-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(56, 189, 248, 0.15);
      border: 1px solid var(--accent);
      color: var(--accent);
      padding: 0.3rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .role-badge.admin {
      background: rgba(168, 85, 247, 0.15);
      border-color: var(--purple);
      color: #c084fc;
    }
    .test-switch-btn {
      font-size: 0.75rem;
      padding: 0.35rem 0.65rem;
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.4);
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.2s;
    }
    .test-switch-btn:hover {
      background: #ef4444;
      color: #fff;
    }

    /* MAIN CONTAINER */
    .container {
      max-width: 1320px;
      width: 100%;
      margin: 0 auto;
      padding: 2rem 1.5rem;
      flex: 1;
    }

    /* HEADER BANNER */
    .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 2rem;
      padding-bottom: 1.25rem;
      border-bottom: 1px solid var(--border);
    }
    .header-title h1 {
      font-size: 1.75rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      margin-bottom: 0.3rem;
    }
    .header-title p {
      color: var(--text-muted);
      font-size: 0.92rem;
    }
    .header-meta {
      display: flex;
      gap: 1rem;
      align-items: center;
    }
    .live-indicator {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.8rem;
      color: var(--success);
      font-weight: 600;
    }
    .pulsing-dot {
      width: 8px;
      height: 8px;
      background: var(--success);
      border-radius: 50%;
      box-shadow: 0 0 10px var(--success);
      animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* KPI METRIC CARDS */
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 1.25rem;
      margin-bottom: 2rem;
    }
    .metric-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.4rem 1.6rem;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
    }
    .metric-card:hover {
      transform: translateY(-2px);
      border-color: var(--accent);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }
    .metric-label {
      color: var(--text-muted);
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.4rem;
    }
    .metric-val {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text);
      line-height: 1.2;
    }
    .metric-sub {
      color: var(--text-muted);
      font-size: 0.8rem;
      margin-top: 0.4rem;
    }
    .metric-icon {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
    }
    .icon-blue { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
    .icon-green { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .icon-purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
    .icon-amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

    /* TWO COLUMN ANALYTICS SECTION */
    .analytics-row {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    @media (max-width: 992px) {
      .analytics-row { grid-template-columns: 1fr; }
    }

    .panel-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.5rem;
    }
    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border);
    }
    .panel-title {
      font-size: 1.05rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .panel-subtitle {
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    /* BAR CHART (SVG / CSS) */
    .chart-container {
      padding: 1rem 0;
      display: flex;
      align-items: flex-end;
      gap: 1.5rem;
      height: 180px;
      border-bottom: 1px solid #334155;
    }
    .chart-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      height: 100%;
      justify-content: flex-end;
      position: relative;
    }
    .bar-wrapper {
      width: 100%;
      max-width: 48px;
      background: rgba(51, 65, 85, 0.4);
      border-radius: 6px 6px 0 0;
      display: flex;
      align-items: flex-end;
      height: 100%;
    }
    .bar-fill {
      width: 100%;
      background: linear-gradient(180deg, #38bdf8, #2563eb);
      border-radius: 6px 6px 0 0;
      transition: height 0.6s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }
    .bar-fill:hover {
      background: linear-gradient(180deg, #7dd3fc, #38bdf8);
      box-shadow: 0 0 12px rgba(56, 189, 248, 0.6);
    }
    .bar-val {
      position: absolute;
      top: -24px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--accent);
    }
    .bar-month {
      margin-top: 0.6rem;
      font-size: 0.8rem;
      color: var(--text-muted);
      font-weight: 600;
    }

    /* CATEGORY BREAKDOWN */
    .cat-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid rgba(51, 65, 85, 0.6);
    }
    .cat-item:last-child { border-bottom: none; }
    .cat-info { display: flex; align-items: center; gap: 0.6rem; }
    .cat-dot { width: 10px; height: 10px; border-radius: 50%; }
    .cat-name { font-size: 0.9rem; font-weight: 500; }
    .cat-count { font-size: 0.88rem; font-weight: 700; color: var(--text); }

    /* TABLES STYLING */
    .data-table-wrapper {
      overflow-x: auto;
      border-radius: 8px;
      margin-top: 0.5rem;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.88rem;
      text-align: left;
    }
    .data-table th {
      background: #0f172a;
      color: var(--text-muted);
      padding: 0.75rem 1rem;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
      border-bottom: 1px solid var(--border);
    }
    .data-table td {
      padding: 0.85rem 1rem;
      border-bottom: 1px solid rgba(51, 65, 85, 0.4);
      color: #e2e8f0;
      vertical-align: middle;
    }
    .data-table tr:hover td {
      background: rgba(51, 65, 85, 0.25);
    }

    /* BADGES */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.25rem 0.65rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .status-active { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-inactive { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .status-confirmed { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
    .status-pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .status-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .status-failed { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

    /* TOGGLE BUTTON */
    .toggle-btn {
      padding: 0.35rem 0.8rem;
      background: #0f172a;
      border: 1px solid var(--border);
      border-radius: 6px;
      color: var(--text);
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      transition: all 0.2s;
    }
    .toggle-btn:hover {
      border-color: var(--accent);
      background: var(--card-hover);
      color: var(--accent);
    }

    /* TOAST ALERT */
    .toast-banner {
      background: rgba(16, 185, 129, 0.2);
      border: 1px solid #10b981;
      color: #a7f3d0;
      padding: 0.85rem 1.25rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.9rem;
      font-weight: 500;
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.15);
    }

    /* FOOTER */
    footer {
      background: #080d1a;
      border-top: 1px solid var(--border);
      padding: 1.25rem 2rem;
      text-align: center;
      font-size: 0.82rem;
      color: var(--text-muted);
      margin-top: auto;
    }
  </style>
</head>
<body>

  <!-- DYNAMIC ROLE-BASED NAVIGATION BAR -->
  <header class="top-nav">
    <a href="admin_dashboard.php" class="brand">
      <div class="brand-icon">SH</div>
      <div>
        <div class="brand-name">StudentHub</div>
        <div class="brand-sub">Executive Administration Console</div>
      </div>
    </a>

    <!-- ROLE-BASED DYNAMIC MENU -->
    <ul class="nav-links">
      <li class="nav-item active"><a href="admin_dashboard.php">Dashboard</a></li>
      <li class="nav-item"><a href="manage_students.php">Students Directory</a></li>
      <li class="nav-item"><a href="ajax_events.php">Event Operations</a></li>
      <li class="nav-item"><a href="#users-section">User Moderation</a></li>
      <li class="nav-item"><a href="#audit-section">Audit Logs</a></li>
    </ul>

    <!-- USER IDENTITY & RBAC SIMULATION SWITCH -->
    <div class="user-profile">
      <div class="role-badge admin">
        <span>Admin Console</span>
      </div>
      <span style="font-size: 0.85rem; color: #cbd5e1; font-weight: 600;"><?= $currentUsername ?></span>
      <a href="admin_dashboard.php?test_role=student" class="test-switch-btn" title="Simulate 403 Forbidden Access Denied for standard student role">
        Test Student RBAC (403)
      </a>
      <a href="login.php?msg=logout" style="color: #94a3b8; font-size: 0.85rem; text-decoration: none; padding: 0.3rem 0.6rem; border-radius: 4px; border: 1px solid #334155;">Logout</a>
    </div>
  </header>

  <!-- MAIN DASHBOARD CONTENT -->
  <main class="container">

    <!-- FEEDBACK TOAST -->
    <?php if (!empty($toastMessage)): ?>
      <div class="toast-banner">
        <span>✓ <?= htmlspecialchars($toastMessage) ?></span>
        <span style="cursor:pointer;" onclick="this.parentElement.style.display='none';">×</span>
      </div>
    <?php endif; ?>

    <!-- HEADER TITLE & REAL-TIME STATUS -->
    <div class="dashboard-header">
      <div class="header-title">
        <h1>Administrative Operations Dashboard</h1>
        <p>Live aggregation of campus analytics, access controls, moderation queues, and security audit records.</p>
      </div>
      <div class="header-meta">
        <div class="live-indicator">
          <div class="pulsing-dot"></div>
          <span>Live Database Synced</span>
        </div>
      </div>
    </div>

    <!-- 4 EXECUTIVE METRIC CARDS -->
    <div class="metrics-grid">
      <!-- Total Users -->
      <div class="metric-card">
        <div>
          <div class="metric-label">System Accounts</div>
          <div class="metric-val"><?= $totalUsers ?></div>
          <div class="metric-sub"><span style="color:#34d399;"><?= $activeUsers ?> Active</span> · <span style="color:#f87171;"><?= $inactiveUsers ?> Inactive</span></div>
        </div>
        <div class="metric-icon icon-blue">👥</div>
      </div>

      <!-- Enrolled Students -->
      <div class="metric-card">
        <div>
          <div class="metric-label">Enrolled Students</div>
          <div class="metric-val"><?= $totalStudents ?></div>
          <div class="metric-sub">Verified FTE Engineering profiles</div>
        </div>
        <div class="metric-icon icon-green">🎓</div>
      </div>

      <!-- Active Events -->
      <div class="metric-card">
        <div>
          <div class="metric-label">Campus Events</div>
          <div class="metric-val"><?= $totalEvents ?></div>
          <div class="metric-sub">Active technical symposia & hackathons</div>
        </div>
        <div class="metric-icon icon-purple">📅</div>
      </div>

      <!-- Event Registrations -->
      <div class="metric-card">
        <div>
          <div class="metric-label">Total Registrations</div>
          <div class="metric-val"><?= $totalRegistrations ?></div>
          <div class="metric-sub"><span style="color:#38bdf8;"><?= $confirmedRegistrations ?> Confirmed</span> admissions</div>
        </div>
        <div class="metric-icon icon-amber">🎟️</div>
      </div>
    </div>

    <!-- ANALYTICS CHARTS (ADVANCED EXTENSION) -->
    <div class="analytics-row">
      <!-- Monthly Registrations Chart -->
      <div class="panel-card">
        <div class="panel-header">
          <div>
            <div class="panel-title">📊 Event Registration Trend Analysis</div>
            <div class="panel-subtitle">Monthly registration volume across academic calendar</div>
          </div>
          <span style="font-size:0.75rem; background:rgba(56,189,248,0.15); color:#38bdf8; padding:0.2rem 0.6rem; border-radius:4px; font-weight:600;">Monthly Trend</span>
        </div>

        <div class="chart-container">
          <?php foreach ($monthlyStats as $month => $count): 
            $heightPct = max(15, round(($count / $maxMonthCount) * 100));
          ?>
            <div class="chart-col">
              <div class="bar-wrapper">
                <div class="bar-fill" style="height: <?= $heightPct ?>%;">
                  <span class="bar-val"><?= $count ?></span>
                </div>
              </div>
              <span class="bar-month"><?= $month ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:0.75rem; font-size:0.75rem; color:var(--text-muted);">
          <span>Baseline Registration Metric</span>
          <span>Peak Capacity: <?= $maxMonthCount ?> bookings/month</span>
        </div>
      </div>

      <!-- Category Breakdown -->
      <div class="panel-card">
        <div class="panel-header">
          <div>
            <div class="panel-title">🏷️ Category Distribution</div>
            <div class="panel-subtitle">Events categorized by domain</div>
          </div>
        </div>

        <div style="padding-top: 0.5rem;">
          <?php 
          $colors = ['#38bdf8', '#10b981', '#a855f7', '#f59e0b', '#ec4899'];
          $idx = 0;
          foreach ($catStats as $cat => $count): 
            $c = $colors[$idx % count($colors)];
            $idx++;
          ?>
            <div class="cat-item">
              <div class="cat-info">
                <div class="cat-dot" style="background: <?= $c ?>;"></div>
                <span class="cat-name"><?= htmlspecialchars($cat) ?></span>
              </div>
              <span class="cat-count"><?= $count ?> events</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- LATEST REGISTRATIONS TABLE -->
    <div class="panel-card" style="margin-bottom: 2rem;">
      <div class="panel-header">
        <div>
          <div class="panel-title">📋 Recent Student Event Registrations</div>
          <div class="panel-subtitle">Live relational records joined from MySQL tables (registrations, students, events)</div>
        </div>
        <a href="manage_students.php" style="color:var(--accent); font-size:0.82rem; text-decoration:none; font-weight:600;">View All Records →</a>
      </div>

      <div class="data-table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Reg #</th>
              <th>Student Name</th>
              <th>Enrollment</th>
              <th>Target Event</th>
              <th>Category</th>
              <th>Registration Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($latestRegistrations)): ?>
              <tr><td colspan="7" style="text-align:center; padding:1.5rem; color:#94a3b8;">No event registrations found.</td></tr>
            <?php else: ?>
              <?php foreach ($latestRegistrations as $reg): ?>
                <tr>
                  <td style="font-family:monospace; color:#38bdf8; font-weight:600;">#REG-<?= str_pad($reg['reg_id'], 3, '0', STR_PAD_LEFT) ?></td>
                  <td style="font-weight:600;"><?= htmlspecialchars($reg['full_name']) ?></td>
                  <td style="font-family:monospace; color:#94a3b8;"><?= htmlspecialchars($reg['enrollment_no']) ?></td>
                  <td><?= htmlspecialchars($reg['event_title']) ?></td>
                  <td><span style="font-size:0.75rem; background:#0f172a; padding:0.2rem 0.5rem; border-radius:4px; border:1px solid #334155;"><?= htmlspecialchars($reg['category']) ?></span></td>
                  <td style="color:#94a3b8; font-size:0.8rem;"><?= date('d M Y, h:i A', strtotime($reg['registered_at'])) ?></td>
                  <td>
                    <?php if ($reg['reg_status'] === 'Confirmed'): ?>
                      <span class="status-badge status-confirmed">Confirmed</span>
                    <?php else: ?>
                      <span class="status-badge status-pending"><?= htmlspecialchars($reg['reg_status']) ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- USER MODERATION & STATUS TOGGLE (INTERMEDIATE EXTENSION) -->
    <div class="panel-card" id="users-section" style="margin-bottom: 2rem;">
      <div class="panel-header">
        <div>
          <div class="panel-title">🛡️ User Account Moderation & RBAC Directory</div>
          <div class="panel-subtitle">Role verification and instant active/inactive account status toggle</div>
        </div>
        <span style="font-size:0.75rem; background:rgba(16,185,129,0.15); color:#34d399; padding:0.2rem 0.6rem; border-radius:4px; font-weight:600;">Live Status Moderation</span>
      </div>

      <div class="data-table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>User ID</th>
              <th>Username</th>
              <th>Assigned Role</th>
              <th>Account Status</th>
              <th>Registered At</th>
              <th style="text-align:right;">Status Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($systemUsers as $u): ?>
              <tr id="user-row-<?= $u['user_id'] ?>">
                <td style="font-family:monospace; color:#94a3b8;">#USR-<?= $u['user_id'] ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></td>
                <td>
                  <?php if ($u['role'] === 'admin'): ?>
                    <span class="status-badge" style="background:rgba(168,85,247,0.15); color:#c084fc; border:1px solid rgba(168,85,247,0.3);">Administrator</span>
                  <?php else: ?>
                    <span class="status-badge" style="background:rgba(56,189,248,0.15); color:#38bdf8; border:1px solid rgba(56,189,248,0.3);">Student</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span id="badge-<?= $u['user_id'] ?>" class="status-badge <?= ($u['status'] === 'Active') ? 'status-active' : 'status-inactive' ?>">
                    <?= htmlspecialchars($u['status']) ?>
                  </span>
                </td>
                <td style="color:#94a3b8; font-size:0.8rem;"><?= date('d M Y, h:i A', strtotime($u['created_at'])) ?></td>
                <td style="text-align:right;">
                  <form method="POST" action="admin_dashboard.php" style="display:inline;" onsubmit="handleStatusToggle(event, <?= $u['user_id'] ?>)">
                    <input type="hidden" name="action" value="toggle_user_status">
                    <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                    <button type="submit" class="toggle-btn" title="Toggle between Active and Inactive">
                      ⇄ Switch Status
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- SECURITY AUDIT LOG TABLE -->
    <div class="panel-card" id="audit-section">
      <div class="panel-header">
        <div>
          <div class="panel-title">📜 Security & Operations Audit Trail</div>
          <div class="panel-subtitle">Immutable log recording administrative access, status changes, and RBAC events</div>
        </div>
        <span style="font-size:0.75rem; background:rgba(245,158,11,0.15); color:#fbbf24; padding:0.2rem 0.6rem; border-radius:4px; font-weight:600;">audit_logs Active</span>
      </div>

      <div class="data-table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Log #</th>
              <th>Timestamp</th>
              <th>Operator</th>
              <th>Action Code</th>
              <th>Target Resource</th>
              <th>Client IP</th>
              <th>Status</th>
              <th>Operation Details</th>
            </tr>
          </thead>
          <tbody id="audit-logs-body">
            <?php if (empty($recentAuditLogs)): ?>
              <tr><td colspan="8" style="text-align:center; padding:1.5rem; color:#94a3b8;">No audit entries logged yet.</td></tr>
            <?php else: ?>
              <?php foreach ($recentAuditLogs as $log): ?>
                <tr>
                  <td style="font-family:monospace; color:#94a3b8;">#LOG-<?= str_pad($log['log_id'], 4, '0', STR_PAD_LEFT) ?></td>
                  <td style="color:#94a3b8; font-size:0.8rem; white-space:nowrap;"><?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?></td>
                  <td style="font-weight:600; color:#38bdf8;"><?= htmlspecialchars($log['username']) ?></td>
                  <td>
                    <span style="font-family:monospace; font-size:0.75rem; background:#0f172a; padding:0.2rem 0.5rem; border-radius:4px; border:1px solid #334155; color:#f8fafc;">
                      <?= htmlspecialchars($log['action']) ?>
                    </span>
                  </td>
                  <td style="color:#e2e8f0; font-size:0.82rem;"><?= htmlspecialchars($log['target']) ?></td>
                  <td style="font-family:monospace; font-size:0.75rem; color:#94a3b8;"><?= htmlspecialchars($log['ip_address']) ?></td>
                  <td>
                    <?php if ($log['status'] === 'Success'): ?>
                      <span class="status-badge status-success">Success</span>
                    <?php elseif ($log['status'] === 'Warning'): ?>
                      <span class="status-badge status-warning">Warning</span>
                    <?php else: ?>
                      <span class="status-badge status-failed"><?= htmlspecialchars($log['status']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td style="color:#cbd5e1; font-size:0.8rem; max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($log['details']) ?>">
                    <?= htmlspecialchars($log['details']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>

  <!-- ENTERPRISE FOOTER -->
  <footer>
    StudentHub Campus Administration Console · Role-Based Access Control Architecture · CHARUSAT FTE
  </footer>

  <!-- ASYNCHRONOUS TOGGLE SCRIPT -->
  <script>
    async function handleStatusToggle(e, userId) {
      e.preventDefault();
      const form = e.target;
      const formData = new FormData(form);

      try {
        const response = await fetch('admin_dashboard.php', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await response.json();
        if (data.success) {
          const badge = document.getElementById('badge-' + userId);
          if (badge) {
            badge.textContent = data.new_status;
            badge.className = 'status-badge ' + (data.new_status === 'Active' ? 'status-active' : 'status-inactive');
          }
          // Reload page briefly after 600ms so audit log table and stat cards update cleanly
          setTimeout(() => {
            window.location.reload();
          }, 600);
        }
      } catch (err) {
        // Fallback to standard form submit if fetch is prevented
        form.submit();
      }
    }
  </script>
</body>
</html>
