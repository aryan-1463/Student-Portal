# 🎓 StudentHub Portal - Practical 1, Practical 2 & Practical 3

> **Course:** Web Development Framework (ITUE203)  
> **Academic Session:** 2026–2027  
> **Project Scope:**  
> - **Practical 1:** Requirement Analysis, Sitemap (`studenthub-sitemap.drawio`), 11 Page Wireframes (`01-home-wireframe.drawio` to `11-admin-wireframe.drawio`), README.md  
> - **Practical 2:** Semantic HTML5 & Accessibility Structure  
> - **Practical 3:** Responsive UI Design using Pure CSS3 (CSS Grid + Flexbox + Mobile-First Media Queries)  

---

## 📌 1. Project Overview & Scope

**StudentHub** is an integrated college student information portal developed as a practical project for ITUE203.

### Scope Covered:
- **Practical 1:** Detailed sitemap, 11 individual page wireframes in `wireframe/`, project folder tree structure.
- **Practical 2:** Semantic HTML5 page layout (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>`, `<form>`), form label associations, accessibility attributes (`aria-*`).
- **Practical 3:** Responsive UI design built with standard CSS3 without external frameworks:
  - **Flexbox:** Header, navigation bar (`<ul>` / `<li>`), button groups, footer, form action rows.
  - **CSS Grid:** Home features section, Dashboard stats & main+sidebar grid, Events grid, Contact layout (details + form), Profile card.
  - **Mobile-First Responsive Design:** Clean layout adaptivity for Desktop (1024px+), Tablet (768px+), and Mobile (<768px).

---

## 👥 2. User Roles & Privileges

| User Role | Description | Core Accessible Pages |
| :--- | :--- | :--- |
| **Guest User** | Unauthenticated visitor browsing campus info, event listings, FAQs, contact, and registration. | `index.html`, `about.html`, `login.html`, `register.html`, `events.html`, `contact.html`, `faq.html` |
| **Student** | Registered student managing academic progress, profile settings, event RSVPs, and feedback. | `dashboard.html`, `profile.html`, `events.html`, `feedback.html` |
| **Administrator** | System admin managing student records, campus announcements, and feedback logs. | `admin.html`, `dashboard.html` |

---

## 🎨 3. Low-Fidelity Wireframes & Sitemap

All 11 low-fidelity wireframe `.drawio` files and the sitemap are stored together inside [`wireframe/`](./wireframe/):

- [`wireframe/studenthub-sitemap.drawio`](./wireframe/studenthub-sitemap.drawio) - Website Sitemap
- [`wireframe/01-home-wireframe.drawio`](./wireframe/01-home-wireframe.drawio) - Home Page
- [`wireframe/02-about-wireframe.drawio`](./wireframe/02-about-wireframe.drawio) - About Us
- [`wireframe/03-login-wireframe.drawio`](./wireframe/03-login-wireframe.drawio) - Login Form
- [`wireframe/04-register-wireframe.drawio`](./wireframe/04-register-wireframe.drawio) - Register Form
- [`wireframe/05-dashboard-wireframe.drawio`](./wireframe/05-dashboard-wireframe.drawio) - Student Dashboard
- [`wireframe/06-profile-wireframe.drawio`](./wireframe/06-profile-wireframe.drawio) - Student Profile
- [`wireframe/07-events-wireframe.drawio`](./wireframe/07-events-wireframe.drawio) - Events Listing
- [`wireframe/08-contact-wireframe.drawio`](./wireframe/08-contact-wireframe.drawio) - Contact Form
- [`wireframe/09-faq-wireframe.drawio`](./wireframe/09-faq-wireframe.drawio) - FAQ Section
- [`wireframe/10-feedback-wireframe.drawio`](./wireframe/10-feedback-wireframe.drawio) - Feedback Form
- [`wireframe/11-admin-wireframe.drawio`](./wireframe/11-admin-wireframe.drawio) - Admin Panel

---

## 📄 4. Core HTML5 Pages & Student Details

- `profile.html`: Name: **Aryan Joshi** | Enrollment Number: **D26DCE156** | Department: **B.Tech** | Semester: **3rd Semester** | Year: **2nd Year** | Email: **`aryanjoshi1463@gmail.com`**

---

## 📁 5. Final Project Structure

```text
StudentHub/
│
├── css/
│   └── style.css
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
├── index.html
├── about.html
├── login.html
├── register.html
├── dashboard.html
├── profile.html
├── events.html
├── contact.html
├── faq.html
├── feedback.html
├── admin.html
└── README.md
```

---

&copy; 2026 StudentHub Academic Portal | Prepared for Practical 1, 2 & 3
