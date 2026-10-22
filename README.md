# StudentHub Portal - Complete Semester Capstone Project

> **Course:** Web Development Frameworks (ITUE203)  
> **Institution:** Charotar University of Science and Technology (CHARUSAT)  
> **Department:** Department of Computer Engineering (FTE)  
> **Student Name:** Aryan Joshi  
> **Student ID:** D26DCE156  
> **Academic Year:** 2026-2027  
> **GitHub Repository:** [https://github.com/aryan-1463/Student-Portal](https://github.com/aryan-1463/Student-Portal)  

---

## 1. Project Overview

**StudentHub** is an integrated, full-stack academic and campus portal engineered across 15 structured laboratory practicals for the Web Development Frameworks course. It features semantic HTML5 structuring, mobile-first responsive CSS3, interactive DOM scripting, secure server-side PHP processing, relational database modeling in 3NF MySQL, asynchronous AJAX/Fetch API CRUD, Role-Based Access Control (RBAC), and persistent security audit logging.

### Complete Semester Practicals Coverage (1 to 15):
- **Practical 1:** Website Requirement Specification, Sitemap Architecture, and 11 UI Wireframes (`wireframe/`).
- **Practical 2:** Semantic HTML5 Structure (`header`, `nav`, `main`, `section`, `article`, `footer`, `form`) with accessibility attributes (`aria-*`).
- **Practical 3:** Responsive UI Design with CSS3 (Flexbox, CSS Grid, mobile-first media queries).
- **Practical 4:** Dynamic DOM Manipulation, interactive highlights slider, popup modal, and dark/light theme switching.
- **Practical 5 & 6:** Client-Side Form Validation, Live Event Search, Debouncing, and Category Filtering.
- **Practical 7:** Server-Side PHP Validation, CSRF Protection, Captcha Verification, Dual File Storage (`data/registrations.csv` and `data/registrations.json`), and Records Display (`records.php`).
- **Practical 8:** Relational Database Design (`studenthub_db`), 3NF Normalized Schema (`students`, `events`, `registrations`), Stored Procedures, and Secure PDO Database Connectivity (`php/db.php`).
- **Practical 9:** Dynamic Student Directory Management with Server-Side PHP & MySQL CRUD (`manage_students.php`).
- **Practical 10:** Secure User Authentication, Password Hashing (`bcrypt`), Session Management & RBAC (`login.php`, `logout.php`).
- **Practical 11:** Dynamic Event Management with Image Poster Uploads and MIME-Type Validation (`manage_events.php`).
- **Practical 12:** Advanced Relational SQL Querying, Inner/Left Joins, Complex Filtering, and Aggregations.
- **Practical 13:** Asynchronous PHP REST-like JSON API (`api/events_api.php`) and Fetch API CRUD without page reload (`ajax_events.php`).
- **Practical 14:** Executive Admin Dashboard with Role-Based Access Control, Real-Time KPI Aggregations, Dynamic Navigation Menu, and Security Audit Logs (`admin_dashboard.php`).
- **Practical 15:** Final Integration, End-to-End System Testing, Accessibility Audit, Documentation, GitHub Submission, and Local Deployment.

---

## 2. Technology Stack

- **Frontend:** Semantic HTML5, CSS3 (Custom Design System, Flexbox, CSS Grid), Vanilla JavaScript (ES6+, Fetch API, Async/Await).
- **Backend:** PHP 8.x (Session Handling, PDO & MySQLi Core, Password Hashing API, File System Processing).
- **Database:** MySQL 8.x / MariaDB (Normalized 3NF relational schema, Foreign Key Constraints, Cascading Rules, Prepared Statements).
- **Web Server:** Apache HTTP Server (XAMPP Environment / Virtual Hosts).
- **Version Control & Tools:** Git, GitHub, VS Code, Browser Developer Tools (Network, Console, Lighthouse).

---

## 3. Project Architecture & Directory Layout

```text
StudentHub/
├── .vscode/
│   └── tasks.json                 # Fast browser launch tasks (Ctrl+Shift+B)
├── api/
│   └── events_api.php             # RESTful JSON API endpoints for AJAX CRUD
├── css/
│   └── style.css                  # Core CSS design system, typography & responsive layouts
├── data/
│   ├── contacts.json              # Contact submission storage
│   ├── registrations.csv          # CSV export data store
│   └── registrations.json         # JSON structured registration store
├── database/
│   └── studenthub_db.sql          # Complete 3NF database schema & seed dataset
├── js/
│   └── script.js                  # Frontend DOM manipulation, modal & validation scripts
├── json/
│   ├── events.json                # Seed events data
│   ├── faqs.json                  # FAQ content structure
│   └── students.json              # Student profiles dataset
├── php/
│   ├── contact_process.php        # Contact inquiry processor
│   └── db.php                     # Centralized PDO database singleton connection
├── uploads/                       # Verified uploaded event poster images
├── wireframe/                     # Architectural UI mockups & sitemap diagram
├── about.html                     # About College & Portal overview
├── admin.html                     # Static administrative landing reference
├── admin_dashboard.php            # Executive Admin Console with RBAC & Audit Trails (P14)
├── ajax_events.php                # Fetch API Asynchronous Events CRUD (P13)
├── contact.html                   # Contact us & inquiry form
├── dashboard.html                 # Student personalized academic dashboard
├── events.html                    # Public event listing & exploration portal
├── faq.html                       # Academic frequently asked questions
├── feedback.html                  # Student feedback collection module
├── index.html                     # Portal landing page with hero slider & dynamic feed
├── login.html                     # Fast login redirector
├── login.php                      # Secure authentication controller (P10)
├── logout.php                     # Session termination & cookie cleanup
├── manage_events.php              # Event management with multipart image uploads (P11)
├── manage_students.php            # Student directory CRUD and filtering (P9)
├── process_registration.php       # Registration submission & file processor (P7)
├── profile.html                   # Student academic profile view
├── records.php                    # Relational registration records table (P8 & P12)
├── register.html                  # Static registration structure
├── register.php                   # Database-backed student enrollment form
└── README.md                      # Comprehensive project documentation
```

---

## 4. Local Deployment Instructions

### Prerequisites:
- XAMPP / WAMP / LAMP installed with **Apache** and **MySQL** running.
- PHP version 8.0 or higher.
- Modern web browser (Chrome, Firefox, Edge).

### Step-by-Step Installation:
1. **Clone or Copy Project to Webroot:**
   ```bash
   git clone https://github.com/aryan-1463/Student-Portal.git C:/xampp/htdocs/StudentHub
   ```
2. **Start Apache and MySQL Services:**
   - Launch XAMPP Control Panel.
   - Click **Start** for both Apache and MySQL.
3. **Import Database Schema (`studenthub_db`):**
   - Open phpMyAdmin: `http://localhost/phpmyadmin/`.
   - Create a new database named: `studenthub_db` (Collation: `utf8mb4_general_ci`).
   - Click the **Import** tab, browse to `database/studenthub_db.sql`, and click **Go**.
4. **Access the Portal in Web Browser:**
   - **Home Page:** `http://localhost/StudentHub/index.html`
   - **Login Page:** `http://localhost/StudentHub/login.php`
   - **Admin Console:** `http://localhost/StudentHub/admin_dashboard.php`
   - **AJAX Events CRUD:** `http://localhost/StudentHub/ajax_events.php`
   - **Student Directory:** `http://localhost/StudentHub/manage_students.php`

### Default User Credentials for Testing:
| Role | Username / Identity | Password | Target Dashboard |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin1` | `Admin@123` | Executive Admin Console (`admin_dashboard.php`) |
| **Enrolled Student** | `d26dce156@charusat.edu.in` | `Admin@123` | Student Dashboard (`dashboard.html`) |

---

## 5. Security & Verification Checklist

- [x] **SQL Injection Defense:** All database queries utilize Prepared Statements (`PDO` and `mysqli_stmt`) with parameter binding.
- [x] **XSS Mitigation:** All user-supplied data is sanitized using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- [x] **Password Protection:** Passwords securely hashed using one-way cryptographic `password_hash()` with `PASSWORD_BCRYPT`.
- [x] **Role-Based Access Control (RBAC):** Strict session validation on administrative endpoints throwing HTTP `403 Forbidden` for unauthorized students.
- [x] **Security Audit Trail:** Critical operations (logins, status changes, unauthorized access attempts) permanently registered into `audit_logs`.
- [x] **Asynchronous Fetch API:** JSON endpoints implement robust validation, standard HTTP status codes, and debounced search.

---

## 6. Author & License

- **Developer:** Aryan Joshi (D26DCE156)
- **Institution:** Charotar University of Science and Technology (CHARUSAT)
- **License:** Academic Project for ITUE203 Laboratory Evaluation.