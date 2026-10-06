# StudentHub Portal

> **Course:** Web Development Framework (ITUE203)  
> **Student:** Aryan Joshi (D26DCE156)  
> **Project:** Integrated College Student Information & Academic Portal  

---

## 1. Project Overview

**StudentHub** is a comprehensive college web portal developed across lab practicals (Practical 1 through Practical 8) for ITUE203. It covers frontend structure, responsive styling, interactive DOM scripts, server-side PHP processing, and relational database management.

### Practicals Covered:
- **Practical 1:** Requirement Analysis, Website Sitemap, and 11 Page Wireframes (`wireframe/`).
- **Practical 2:** Semantic HTML5 Structure (`header`, `nav`, `main`, `section`, `article`, `footer`, `form`) with accessibility attributes (`aria-*`).
- **Practical 3:** Responsive UI Design with CSS3 (Flexbox, CSS Grid, mobile-first responsive layouts).
- **Practical 4:** Dynamic DOM Manipulation, interactive highlights slider, popup modal, and dark/light theme switching.
- **Practical 5 & 6:** Client-Side Form Validations, Dynamic Event Search and Filtering.
- **Practical 7:** Server-Side PHP Validation, CSRF Protection, Captcha Verification, Dual File Storage (`data/registrations.csv` and `data/registrations.json`), and Records Display (`records.php`).
- **Practical 8:** Relational Database Design (`studenthub_db`), 3NF Normalized Schema (`students`, `events`, `registrations`), Stored Procedures, and Secure PDO Database Connectivity (`php/db.php`).

---

## 2. Project Folder Structure

```text
StudentHub/
│
├── css/
│   └── style.css
│
├── data/
│   ├── contacts.json
│   ├── registrations.csv
│   └── registrations.json
│
├── database/
│   └── studenthub_db.sql
│
├── js/
│   └── script.js
│
├── json/
│   ├── events.json
│   ├── faqs.json
│   └── students.json
│
├── php/
│   ├── contact_process.php
│   └── db.php
│
├── wireframe/
│   ├── 01-home-wireframe.drawio
│   ├── 02-about-wireframe.drawio
│   ├── 03-login-wireframe.drawio
│   ├── 04-register-wireframe.drawio
│   ├── 05-dashboard-wireframe.drawio
│   ├── 06-profile-wireframe.drawio
│   ├── 07-events-wireframe.drawio
│   ├── 08-contact-wireframe.drawio
│   ├── 09-faq-wireframe.drawio
│   ├── 10-feedback-wireframe.drawio
│   ├── 11-admin-wireframe.drawio
│   └── studenthub-sitemap.drawio
│
├── about.html
├── admin.html
├── contact.html
├── dashboard.html
├── events.html
├── faq.html
├── feedback.html
├── index.html
├── login.html
├── process_registration.php
├── profile.html
├── README.md
├── records.php
├── register.html
└── register.php
```

---

## 3. How to Run Locally

1. Place the `StudentHub` folder inside your XAMPP web root: `C:\xampp\htdocs\StudentHub`.
2. Start **Apache** and **MySQL** from XAMPP Control Panel.
3. Import the database dump:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`).
   - Create database `studenthub_db`.
   - Import `database/studenthub_db.sql`.
4. Open the portal in browser:
   - Home Page: `http://localhost/StudentHub/index.html`
   - Registration: `http://localhost/StudentHub/register.php`
   - Stored Records: `http://localhost/StudentHub/records.php`
   - Database Connection Test: `http://localhost/StudentHub/php/db.php`

---

© 2026 StudentHub Academic Portal | Aryan Joshi (D26DCE156)