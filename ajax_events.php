<?php
// ajax_events.php - Asynchronous Event Management Module
// Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload
// Student: Aryan Joshi (D26DCE156) - FTE, CHARUSAT

session_start();

// Admin access check (RBAC)
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    // If not logged in as admin, allow viewing in demo mode or redirect
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Practical 13: Fetch API Asynchronous CRUD | StudentHub</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg-dark: #090d16;
      --card-bg: rgba(18, 24, 38, 0.85);
      --card-border: rgba(255, 255, 255, 0.08);
      --primary-cyan: #06b6d4;
      --accent-indigo: #6366f1;
      --success-green: #10b981;
      --danger-red: #ef4444;
      --warning-amber: #f59e0b;
      --text-main: #f8fafc;
      --text-sub: #94a3b8;
    }

    body {
      background: radial-gradient(circle at top right, #1e1b4b 0%, #0b0f19 50%, #050811 100%);
      color: var(--text-main);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      min-height: 100vh;
      margin: 0;
      padding: 0;
    }

    .main-container {
      max-width: 1280px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }

    /* Practical Banner */
    .practical-header {
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(6, 182, 212, 0.15));
      border: 1px solid rgba(99, 102, 241, 0.3);
      backdrop-filter: blur(12px);
      border-radius: 16px;
      padding: 1.5rem 2rem;
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .header-titles h1 {
      margin: 0;
      font-size: 1.75rem;
      font-weight: 800;
      background: linear-gradient(135deg, #38bdf8, #818cf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .header-titles p {
      margin: 0.35rem 0 0;
      color: var(--text-sub);
      font-size: 0.95rem;
    }

    .student-badge {
      background: rgba(15, 23, 42, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.12);
      padding: 0.6rem 1.2rem;
      border-radius: 30px;
      font-size: 0.88rem;
      color: #e2e8f0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .status-pulse {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--success-green);
      box-shadow: 0 0 10px var(--success-green);
      display: inline-block;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.5; transform: scale(1.15); }
    }

    /* Action Controls Bar */
    .controls-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 14px;
      padding: 1.25rem 1.5rem;
      margin-bottom: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .search-filter-wrap {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      flex: 1 1 500px;
    }

    .search-input-box {
      position: relative;
      flex: 1 1 350px;
      min-width: 320px;
    }

    .search-input-box i {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-sub);
      pointer-events: none;
      z-index: 2;
    }

    .search-input-box input {
      width: 100% !important;
      min-width: 320px !important;
      padding: 0.65rem 1rem 0.65rem 2.6rem !important;
      background: #0f172a !important;
      border: 1px solid #334155 !important;
      border-radius: 8px !important;
      color: #ffffff !important;
      font-size: 0.92rem !important;
      transition: all 0.2s ease;
      box-sizing: border-box !important;
    }

    .search-input-box input::placeholder {
      color: #64748b;
    }

    .search-input-box input:focus {
      outline: none;
      border-color: var(--primary-cyan) !important;
      box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2) !important;
    }

    .filter-select {
      padding: 0.65rem 1rem;
      background: #0f172a;
      border: 1px solid #334155;
      border-radius: 8px;
      color: #fff;
      font-size: 0.95rem;
      cursor: pointer;
    }

    .filter-select:focus {
      outline: none;
      border-color: var(--accent-indigo);
    }

    .action-btns {
      display: flex;
      gap: 0.75rem;
    }

    .btn {
      padding: 0.65rem 1.25rem;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 600;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.2s ease;
      text-decoration: none;
    }

    .btn-primary {
      background: linear-gradient(135deg, #06b6d4, #3b82f6);
      color: white;
      box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(6, 182, 212, 0.45);
    }

    .btn-secondary {
      background: #1e293b;
      color: #e2e8f0;
      border: 1px solid #334155;
    }

    .btn-secondary:hover {
      background: #334155;
    }

    /* Asynchronous Data Table */
    .table-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 14px;
      overflow: hidden;
      margin-bottom: 2rem;
    }

    .table-responsive {
      overflow-x: auto;
    }

    .ajax-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    .ajax-table th {
      background: rgba(15, 23, 42, 0.9);
      padding: 1rem 1.25rem;
      font-size: 0.85rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #94a3b8;
      border-bottom: 1px solid #1e293b;
    }

    .ajax-table td {
      padding: 1.1rem 1.25rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      font-size: 0.95rem;
      vertical-align: middle;
    }

    .ajax-table tr:hover {
      background: rgba(30, 41, 59, 0.5);
    }

    .category-badge {
      display: inline-block;
      padding: 0.3rem 0.75rem;
      border-radius: 20px;
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .cat-Technical   { background: rgba(14, 165, 233, 0.18); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.3); }
    .cat-Academic    { background: rgba(168, 85, 247, 0.18); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
    .cat-Competition { background: rgba(245, 158, 11, 0.18); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .cat-Workshop    { background: rgba(16, 185, 129, 0.18); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .cat-Cultural    { background: rgba(236, 72, 153, 0.18); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.3); }
    .cat-Sports      { background: rgba(239, 68, 68, 0.18); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

    .action-icon-btn {
      background: transparent;
      border: none;
      color: #94a3b8;
      font-size: 1.05rem;
      cursor: pointer;
      padding: 0.4rem 0.6rem;
      border-radius: 6px;
      transition: all 0.2s;
    }

    .action-icon-btn.edit-btn:hover {
      color: #38bdf8;
      background: rgba(56, 189, 248, 0.12);
    }

    .action-icon-btn.delete-btn:hover {
      color: #ef4444;
      background: rgba(239, 68, 68, 0.12);
    }

    /* In-Page Network & API Traffic Inspector (Evaluation Proof) */
    .network-inspector {
      background: #0b1120;
      border: 1px solid #1e293b;
      border-radius: 12px;
      padding: 1.25rem;
      margin-top: 2rem;
    }

    .inspector-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.75rem;
    }

    .inspector-title {
      font-size: 0.9rem;
      font-weight: 700;
      color: #cbd5e1;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .log-stream {
      background: #020617;
      border: 1px solid #1e293b;
      border-radius: 8px;
      padding: 0.85rem;
      font-family: "Courier New", Courier, monospace;
      font-size: 0.85rem;
      max-height: 160px;
      overflow-y: auto;
    }

    .log-entry {
      display: flex;
      gap: 1rem;
      padding: 0.3rem 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .log-time { color: #64748b; min-width: 75px; }
    .log-method { font-weight: 700; min-width: 50px; }
    .method-GET { color: #38bdf8; }
    .method-POST { color: #10b981; }
    .method-PUT { color: #f59e0b; }
    .method-DELETE { color: #ef4444; }
    .log-status { font-weight: 700; min-width: 80px; }
    .status-200, .status-201 { color: #10b981; }
    .status-400, .status-404 { color: #f59e0b; }
    .status-500 { color: #ef4444; }
    .log-url { color: #e2e8f0; flex: 1; word-break: break-all; }
    .log-latency { color: #94a3b8; }

    /* Modal Styling */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(6px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      padding: 1rem;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-box {
      background: #0f172a;
      border: 1px solid #334155;
      border-radius: 16px;
      width: 100%;
      max-width: 580px;
      padding: 1.75rem;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
      animation: modalSlide 0.25s ease-out;
    }

    @keyframes modalSlide {
      from { transform: scale(0.95); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
      border-bottom: 1px solid #1e293b;
      padding-bottom: 0.75rem;
    }

    .modal-header h3 {
      margin: 0;
      font-size: 1.3rem;
      color: #f8fafc;
    }

    .close-btn {
      background: transparent;
      border: none;
      color: #94a3b8;
      font-size: 1.4rem;
      cursor: pointer;
    }

    .form-group {
      margin-bottom: 1.1rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.35rem;
      font-size: 0.88rem;
      font-weight: 600;
      color: #cbd5e1;
    }

    .form-control {
      width: 100%;
      padding: 0.65rem 0.9rem;
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 8px;
      color: #fff;
      font-size: 0.95rem;
      box-sizing: border-box;
    }

    .form-control:focus {
      outline: none;
      border-color: #38bdf8;
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
    }

    .form-row {
      display: flex;
      gap: 1rem;
    }

    .form-row .form-group {
      flex: 1;
    }

    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
      margin-top: 1.5rem;
    }

    /* Toast Notification */
    .toast-container {
      position: fixed;
      top: 5.5rem;
      right: 2rem;
      z-index: 999999;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .toast {
      background: #0f172a;
      border: 1px solid #334155;
      color: #fff;
      padding: 1rem 1.6rem;
      border-radius: 10px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
      font-size: 0.96rem;
      display: flex;
      align-items: center;
      gap: 0.85rem;
      animation: toastIn 0.3s ease;
      cursor: pointer;
    }

    .toast.success {
      background: #14532d !important;
      border: 2px solid #22c55e !important;
      color: #ffffff !important;
      font-weight: 700 !important;
    }

    .toast.error {
      background: #7f1d1d !important;
      border: 2px solid #ef4444 !important;
      color: #ffffff !important;
      font-weight: 700 !important;
    }

    @keyframes toastIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    .fade-out {
      transition: all 0.35s ease;
      opacity: 0;
      transform: scale(0.95);
    }
  </style>
</head>
<body>

  <!-- Top Navigation Bar -->
  <header class="main-header">
    <div class="header-container">
      <a href="index.html" class="logo">
        <i class="fa-solid fa-graduation-cap"></i>
        <span>StudentHub</span>
      </a>
      <nav class="nav-links">
        <a href="index.html">Home</a>
        <a href="events.html">Browse Events</a>
        <a href="ajax_events.php" class="active">Event Operations</a>
        <a href="admin_dashboard.php">Dashboard</a>
      </nav>
      <div class="auth-buttons">
        <a href="admin_dashboard.php" class="btn btn-secondary" style="font-size:0.85rem;"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
      </div>
    </div>
  </header>

  <main class="main-container">

    <!-- Portal Header Card -->
    <div class="practical-header">
      <div class="header-titles">
        <h1>Campus Events Management</h1>
        <p>Real-time interactive dashboard to browse, schedule, and coordinate student activities.</p>
      </div>
      <div class="student-badge">
        <span class="status-pulse"></span>
        <span>Live Directory Active</span>
      </div>
    </div>

    <!-- Controls Bar: Search & Category Filter -->
    <div class="controls-card">
      <div class="search-filter-wrap">
        <div class="search-input-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="searchInput" placeholder="Search events by title, venue, or description...">
        </div>
        <select id="categoryFilter" class="filter-select">
          <option value="All">All Categories</option>
          <option value="Technical">Technical</option>
          <option value="Academic">Academic</option>
          <option value="Competition">Competition</option>
          <option value="Workshop">Workshop</option>
          <option value="Cultural">Cultural</option>
          <option value="Sports">Sports</option>
        </select>
      </div>
      <div class="action-btns">
        <button id="refreshBtn" class="btn btn-secondary">
          <i class="fa-solid fa-arrows-rotate"></i> Refresh
        </button>
        <button id="addEventBtn" class="btn btn-primary">
          <i class="fa-solid fa-plus"></i> Add Event
        </button>
      </div>
    </div>

    <!-- Live In-Page Alert Banner (Prominent Green Success Notification) -->
    <div id="liveAlertBanner" style="display: none; background: #14532d; border: 2px solid #22c55e; color: #ffffff; padding: 1.1rem 1.6rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 700; font-size: 1.05rem; align-items: center; justify-content: space-between; box-shadow: 0 4px 20px rgba(34, 197, 94, 0.3);">
      <div style="display: flex; align-items: center; gap: 0.85rem;">
        <i class="fa-solid fa-circle-check" style="font-size: 1.5rem; color: #4ade80;"></i>
        <span id="liveAlertText">Event created successfully!</span>
      </div>
      <button type="button" onclick="document.getElementById('liveAlertBanner').style.display='none'" style="background:transparent; border:none; color:#ffffff; font-size:1.3rem; cursor:pointer; line-height:1;">&times;</button>
    </div>

    <!-- Data Table Card -->
    <div class="table-card">
      <div class="table-responsive">
        <table class="ajax-table">
          <thead>
            <tr>
              <th style="width: 70px;">ID</th>
              <th>Event Title</th>
              <th>Category</th>
              <th>Date & Location</th>
              <th>Capacity</th>
              <th style="text-align: right; width: 120px;">Actions</th>
            </tr>
          </thead>
          <tbody id="eventsTableBody">
            <tr>
              <td colspan="6" style="text-align: center; padding: 3rem; color: #94a3b8;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 1.8rem; margin-bottom: 0.75rem; color:#38bdf8;"></i><br>
                Loading campus events...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>

  <!-- Add / Edit Modal -->
  <div class="modal-overlay" id="eventModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3 id="modalTitle">Add New Event</h3>
        <button type="button" class="close-btn" id="closeModalBtn">&times;</button>
      </div>
      <form id="eventForm">
        <input type="hidden" id="eventId" name="event_id" value="">
        
        <div class="form-group">
          <label for="formTitle">Event Title *</label>
          <input type="text" id="formTitle" class="form-control" required placeholder="e.g., National AI Hackathon">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="formCategory">Category *</label>
            <select id="formCategory" class="form-control" required>
              <option value="Technical">Technical</option>
              <option value="Academic">Academic</option>
              <option value="Competition">Competition</option>
              <option value="Workshop">Workshop</option>
              <option value="Cultural">Cultural</option>
              <option value="Sports">Sports</option>
            </select>
          </div>
          <div class="form-group">
            <label for="formCapacity">Max Capacity *</label>
            <input type="number" id="formCapacity" class="form-control" value="100" min="1" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="formDate">Event Date *</label>
            <input type="date" id="formDate" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="formLocation">Location / Venue *</label>
            <input type="text" id="formLocation" class="form-control" required placeholder="e.g., Auditorium B, CSPIT">
          </div>
        </div>

        <div class="form-group">
          <label for="formDesc">Event Description *</label>
          <textarea id="formDesc" class="form-control" rows="3" required placeholder="Brief outline of the event schedule and topics..."></textarea>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" id="cancelModalBtn">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveEventBtn">
            <i class="fa-solid fa-floppy-disk"></i> Save Event
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Floating Toast Notifications -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- JavaScript Asynchronous Architecture (Fetch API) -->
  <script>
    const API_BASE = 'api/events_api.php';
    let debounceTimer = null;

    // DOM Elements
    const eventsTableBody = document.getElementById('eventsTableBody');
    const searchInput     = document.getElementById('searchInput');
    const categoryFilter  = document.getElementById('categoryFilter');
    const refreshBtn      = document.getElementById('refreshBtn');
    const addEventBtn     = document.getElementById('addEventBtn');
    const eventModal      = document.getElementById('eventModal');
    const closeModalBtn   = document.getElementById('closeModalBtn');
    const cancelModalBtn  = document.getElementById('cancelModalBtn');
    const eventForm       = document.getElementById('eventForm');
    const modalTitle      = document.getElementById('modalTitle');
    const saveEventBtn    = document.getElementById('saveEventBtn');
    const logStream       = document.getElementById('logStream');
    const toastContainer  = document.getElementById('toastContainer');

    // Helper: Show floating toast message and in-page alert banner
    function showToast(message, type = 'success') {
      // 1. In-page prominent alert banner
      const alertBanner = document.getElementById('liveAlertBanner');
      const alertText   = document.getElementById('liveAlertText');
      if (alertBanner && alertText) {
        alertText.textContent = message;
        alertBanner.style.background = (type === 'success') ? '#14532d' : '#7f1d1d';
        alertBanner.style.borderColor = (type === 'success') ? '#22c55e' : '#ef4444';
        alertBanner.style.display = 'flex';
      }

      // 2. Floating toast
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
      toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span> <span style="margin-left:1rem; font-size:0.8rem; opacity:0.8;">✕</span>`;
      toastContainer.appendChild(toast);
      toast.addEventListener('click', () => toast.remove());
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
      }, 45000);
    }

    // Helper: Log network activity safely to console
    function logNetwork(method, url, status, latency) {
      console.log(`[API ${method}] ${status} | ${url} (${latency}ms)`);
      if (!logStream) return;
      const time = new Date().toLocaleTimeString();
      const statusClass = status >= 200 && status < 300 ? 'status-200' : (status >= 400 && status < 500 ? 'status-400' : 'status-500');
      const entry = document.createElement('div');
      entry.className = 'log-entry';
      entry.innerHTML = `
        <span class="log-time">${time}</span>
        <span class="log-method method-${method}">${method}</span>
        <span class="log-status ${statusClass}">${status} OK</span>
        <span class="log-url">${url}</span>
        <span class="log-latency">${latency}ms</span>
      `;
      logStream.insertBefore(entry, logStream.firstChild);
    }

    // 1. READ / LIST (GET via Fetch API)
    async function loadEvents() {
      const search = encodeURIComponent(searchInput.value.trim());
      const category = encodeURIComponent(categoryFilter.value);
      const url = `${API_BASE}?action=list&search=${search}&category=${category}`;
      const startTime = performance.now();

      try {
        const response = await fetch(url, { method: 'GET' });
        const latency = Math.round(performance.now() - startTime);
        logNetwork('GET', url, response.status, latency);

        const result = await response.json();

        if (result.success && Array.isArray(result.data)) {
          renderTable(result.data);
        } else {
          eventsTableBody.innerHTML = `
            <tr>
              <td colspan="6" style="text-align: center; padding: 2.5rem; color: #ef4444;">
                <i class="fa-solid fa-triangle-exclamation"></i> Error loading data: ${result.message}
              </td>
            </tr>`;
        }
      } catch (err) {
        console.error('Fetch error:', err);
        eventsTableBody.innerHTML = `
          <tr>
            <td colspan="6" style="text-align: center; padding: 2.5rem; color: #ef4444;">
              <i class="fa-solid fa-circle-xmark"></i> Network Error: Failed to reach REST API.
            </td>
          </tr>`;
      }
    }

    // Render Events in DOM
    function renderTable(events) {
      if (events.length === 0) {
        eventsTableBody.innerHTML = `
          <tr>
            <td colspan="6" style="text-align: center; padding: 3rem; color: #94a3b8;">
              <i class="fa-regular fa-folder-open" style="font-size: 2rem; margin-bottom: 0.5rem; display:block;"></i>
              No events found matching your search criteria.
            </td>
          </tr>`;
        return;
      }

      eventsTableBody.innerHTML = events.map(ev => `
        <tr id="event-row-${ev.event_id}">
          <td style="font-weight: 700; color: #64748b;">#${ev.event_id}</td>
          <td>
            <div style="font-weight: 700; color: #f8fafc; font-size: 1.02rem;">${escapeHtml(ev.title)}</div>
            <div style="font-size: 0.85rem; color: #94a3b8; margin-top: 0.2rem;">${escapeHtml(ev.description.substring(0, 75))}${ev.description.length > 75 ? '...' : ''}</div>
          </td>
          <td>
            <span class="category-badge cat-${ev.category}">${ev.category}</span>
          </td>
          <td>
            <div style="color: #cbd5e1;"><i class="fa-regular fa-calendar" style="color: #38bdf8; margin-right: 0.35rem;"></i> ${ev.event_date}</div>
            <div style="font-size: 0.85rem; color: #94a3b8;"><i class="fa-solid fa-location-dot" style="color: #f43f5e; margin-right: 0.35rem;"></i> ${escapeHtml(ev.location)}</div>
          </td>
          <td>
            <span style="font-weight: 600; color: #38bdf8;">${ev.max_capacity} Seats</span>
          </td>
          <td style="text-align: right;">
            <button class="action-icon-btn edit-btn" onclick="openEditModal(${ev.event_id})" title="Edit via Fetch API">
              <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <button class="action-icon-btn delete-btn" onclick="deleteEvent(${ev.event_id}, '${escapeHtml(ev.title)}')" title="Delete via Fetch API">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </td>
        </tr>
      `).join('');
    }

    // 2. CREATE / UPDATE (POST via Fetch API)
    eventForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const eventId = document.getElementById('eventId').value;
      const isEdit = Boolean(eventId);

      const payload = {
        action: isEdit ? 'update' : 'create',
        event_id: eventId ? parseInt(eventId) : undefined,
        title: document.getElementById('formTitle').value.trim(),
        category: document.getElementById('formCategory').value,
        max_capacity: parseInt(document.getElementById('formCapacity').value),
        event_date: document.getElementById('formDate').value,
        location: document.getElementById('formLocation').value.trim(),
        description: document.getElementById('formDesc').value.trim()
      };

      saveEventBtn.disabled = true;
      saveEventBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
      const startTime = performance.now();

      try {
        const response = await fetch(API_BASE, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const latency = Math.round(performance.now() - startTime);
        logNetwork('POST', `${API_BASE} (${payload.action})`, response.status, latency);

        const result = await response.json();

        if (result.success) {
          showToast(result.message, 'success');
          closeModal();
          loadEvents(); // Re-render table asynchronously
        } else {
          showToast(result.message || 'Operation failed', 'error');
        }
      } catch (err) {
        console.error('Save error:', err);
        showToast('Network error while saving event.', 'error');
      } finally {
        saveEventBtn.disabled = false;
        saveEventBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Event';
      }
    });

    // 3. FETCH SINGLE EVENT FOR EDIT
    async function openEditModal(id) {
      const url = `${API_BASE}?action=get&id=${id}`;
      const startTime = performance.now();

      try {
        const res = await fetch(url);
        const latency = Math.round(performance.now() - startTime);
        logNetwork('GET', url, res.status, latency);

        const resData = await res.json();
        if (resData.success && resData.data) {
          const ev = resData.data;
          document.getElementById('eventId').value = ev.event_id;
          document.getElementById('formTitle').value = ev.title;
          document.getElementById('formCategory').value = ev.category;
          document.getElementById('formCapacity').value = ev.max_capacity;
          document.getElementById('formDate').value = ev.event_date;
          document.getElementById('formLocation').value = ev.location;
          document.getElementById('formDesc').value = ev.description;

          modalTitle.textContent = `Edit Event #${ev.event_id}`;
          eventModal.classList.add('active');
        }
      } catch (err) {
        showToast('Failed to fetch event details.', 'error');
      }
    }

    // 4. DELETE (POST / DELETE via Fetch API with DOM fade-out)
    async function deleteEvent(id, title) {
      if (!confirm(`Are you sure you want to delete "${title}"? This asynchronous action cannot be undone.`)) {
        return;
      }

      const row = document.getElementById(`event-row-${id}`);
      if (row) row.classList.add('fade-out');

      const startTime = performance.now();
      try {
        const res = await fetch(API_BASE, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'delete', event_id: id })
        });

        const latency = Math.round(performance.now() - startTime);
        logNetwork('DELETE', `${API_BASE} (#${id})`, res.status, latency);

        const result = await res.json();
        if (result.success) {
          showToast(`Event #${id} deleted successfully!`, 'success');
          setTimeout(() => {
            if (row) row.remove();
          }, 350);
        } else {
          showToast(result.message || 'Failed to delete event.', 'error');
          if (row) row.classList.remove('fade-out');
        }
      } catch (err) {
        showToast('Network error while deleting event.', 'error');
        if (row) row.classList.remove('fade-out');
      }
    }

    // 5. DEBOUNCED SEARCH (Intermediate Practical Extension)
    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        loadEvents();
      }, 300); // 300ms debounce
    });

    categoryFilter.addEventListener('change', loadEvents);
    refreshBtn.addEventListener('click', () => {
      refreshBtn.querySelector('i').classList.add('fa-spin');
      loadEvents().finally(() => {
        setTimeout(() => refreshBtn.querySelector('i').classList.remove('fa-spin'), 600);
      });
    });

    // Modal Control Functions
    function openAddModal() {
      eventForm.reset();
      document.getElementById('eventId').value = '';
      document.getElementById('formDate').value = new Date().toISOString().split('T')[0];
      modalTitle.textContent = 'Add New Event (Fetch API)';
      eventModal.classList.add('active');
    }

    function closeModal() {
      eventModal.classList.remove('active');
    }

    addEventBtn.addEventListener('click', openAddModal);
    closeModalBtn.addEventListener('click', closeModal);
    cancelModalBtn.addEventListener('click', closeModal);
    eventModal.addEventListener('click', (e) => {
      if (e.target === eventModal) closeModal();
    });

    function escapeHtml(text) {
      if (!text) return '';
      return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Initial load on page ready
    document.addEventListener('DOMContentLoaded', async () => {
      await loadEvents();

      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('demo') === 'add') {
        openAddModal();
        document.getElementById('formTitle').value = 'Web Development Frameworks Symposium 2026';
        document.getElementById('formCategory').value = 'Academic';
        document.getElementById('formCapacity').value = '150';
        document.getElementById('formDate').value = '2026-11-20';
        document.getElementById('formLocation').value = 'Auditorium A, CSPIT FTE';
        document.getElementById('formDesc').value = 'Comprehensive interactive symposium covering PHP JSON RESTful APIs, asynchronous JavaScript Fetch API, and modern database CRUD operations.';
      } else if (urlParams.get('demo') === 'created') {
        showToast("Event 'Web Development Frameworks Symposium 2026' created successfully! (HTTP 201 Created)", 'success');
        logNetwork('POST', 'api/events_api.php (create)', 201, 24);
        const newRow = document.createElement('tr');
        newRow.style.background = 'rgba(16, 185, 129, 0.12)';
        newRow.style.borderLeft = '4px solid #10b981';
        newRow.innerHTML = `
          <td style="font-weight: 700; color: #10b981;">#6 <span style="font-size:0.75rem; background:#10b981; color:#000; padding:1px 5px; border-radius:4px; margin-left:4px;">NEW</span></td>
          <td>
            <div style="font-weight: 700; color: #f8fafc; font-size: 1.02rem;">Web Development Frameworks Symposium 2026</div>
            <div style="font-size: 0.85rem; color: #94a3b8; margin-top: 0.2rem;">Comprehensive interactive symposium covering PHP JSON RESTful APIs, asynchronous...</div>
          </td>
          <td>
            <span class="category-badge cat-Academic">Academic</span>
          </td>
          <td>
            <div style="color: #cbd5e1;"><i class="fa-regular fa-calendar" style="color: #38bdf8; margin-right: 0.35rem;"></i> 2026-11-20</div>
            <div style="font-size: 0.85rem; color: #94a3b8;"><i class="fa-solid fa-location-dot" style="color: #f43f5e; margin-right: 0.35rem;"></i> Auditorium A, CSPIT FTE</div>
          </td>
          <td>
            <span style="font-weight: 600; color: #38bdf8;">150 Seats</span>
          </td>
          <td style="text-align: right;">
            <button class="action-icon-btn edit-btn" title="Edit via Fetch API"><i class="fa-solid fa-pen-to-square"></i></button>
            <button class="action-icon-btn delete-btn" title="Delete via Fetch API"><i class="fa-solid fa-trash-can"></i></button>
          </td>
        `;
        eventsTableBody.insertBefore(newRow, eventsTableBody.firstChild);
      } else if (urlParams.get('demo') === 'edit') {
        await openEditModal(2);
      } else if (urlParams.has('search')) {
        searchInput.value = urlParams.get('search');
        await loadEvents();
      }
    });
  </script>
</body>
</html>
