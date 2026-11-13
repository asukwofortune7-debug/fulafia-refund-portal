# 🎓 FULAFIA NELFUND Tuition Refund Portal

A secure, centralized web application built to streamline tuition refund verification and disbursement tracking for Federal University of Lafia (FULAFIA) students following institutional NELFUND releases.

🔗 **Live Demo:** [https://fulafia-refund.kesug.com](https://fulafia-refund.kesug.com)

---
## 🎯 Problem Statement
When NELFUND disburses bulk tuition allocations directly to the university, students often flood the Bursary and ICT offices in person to confirm receipt and submit bank details for refunds. This creates physical congestion, manual paperwork bottlenecks, and delayed reconciliations.

## 💡 Solution Overview
The **FULAFIA NELFUND Refund Portal** acts as a digital verification bridge between students and the University Bursary:
1. **Self-Service Student Onboarding:** Students register online using their Matriculation Number, commercial bank details, and upload their NELFUND disbursement evidence screenshot.
2. **Real-Time Wallet & Status Tracking:** Students log into a personal dashboard to view their live refund status (`PENDING`, `APPROVED`, `DISBURSED`) and track their cleared Naira wallet balance without visiting any office.
3. **Bursary Admin Control Center:** Authorized Bursary administrators review evidence uploads, update disbursement statuses, credit student wallet balances, and export or copy verified payout details in one click.

---

## ✨ Key Features

### Student Portal
* **Secure Registration & Authentication:** Input validation, duplicate matriculation checks, and cryptographic password hashing (`BCrypt`).
* **Commercial Bank Integration:** Standardized dropdown restricted to CBN-licensed Nigerian commercial banks to prevent microfinance transfer delays.
* **Evidence Upload Engine:** Secure MIME-verified image upload (`PNG`/`JPG`, max 5MB) for NELFUND portal proof.
* **Live Wallet & Status Dashboard:** Clear status badges and Naira balance display showing when funds have been cleared by the Bursary.

### Admin & Bursary Dashboard
* **Role-Based Access Control:** Isolated `/admin/` portal protected by session authentication and SuperAdmin management.
* **One-Click Payout Copy:** Instant clipboard copy (`Full Name | Matric Number | Bank Name | Account Number`) formatted for rapid entry into university payment schedules.
* **Wallet & Status Management:** Direct controls to approve/reject claims, update student wallet balances, and export clean CSV reports.
* **Audit Logging:** Tracks administrative actions (`audit_logs`) for financial accountability and institutional transparency.

---

## 🛠️ Technology Stack
* **Backend:** PHP 8.x (Procedural/PDO & MySQLi prepared statements)
* **Database:** MySQL / MariaDB Relational Schema (`students`, `admins`, `audit_logs`)
* **Frontend:** Responsive HTML5, CSS3, Vanilla JavaScript (Clipboard API)
* **Security:** HTTPS/SSL Encryption, BCrypt Password Hashing, Strict File MIME Verification, SQL Injection Prevention

---

## 🚀 Deployment Setup
1. Import `schema.sql` into a MySQL/MariaDB database.
2. Update `config.php` with production database credentials and `BASE_URL`.
3. Run `/admin/create_admin.php` once to initialize the SuperAdmin account, then remove the setup script from production.
## File Structure

```
fulafia-nelfund/
├── config.php              # DB connection + shared helpers (EDIT THIS FIRST)
├── schema.sql               # Run once to create the database
├── index.php                 # Public landing page
├── signup.php                 # Student registration
├── login.php                   # Student login
├── logout.php                   # Shared logout (student + admin)
├── forgot_password.php           # Request reset link
├── reset_password.php             # Set new password via token
├── dashboard.php                   # Student dashboard (status + upload, 5MB/PNG/JPG validated)
├── includes/
│   ├── header.php
│   └── footer.php
├── assets/
│   └── logo.png              # FULAFIA logo (already extracted for you)
├── uploads/                   # Student-submitted screenshots (auto-created)
│   └── .htaccess               # Blocks script execution in this folder
└── admin/
    ├── login.php               # Admin login (logs the login event)
    ├── create_admin.php         # ONE-TIME script to create the first admin (becomes SuperAdmin)
    ├── dashboard.php              # Master student table + status control + CSV export button
    ├── update_status.php           # Handles status update POST (logs the change, emails the student)
    ├── export_csv.php               # Streams Approved Refunds as a downloadable CSV
    ├── manage_admins.php            # SuperAdmin-only: create/delete admin accounts
    └── activity_log.php               # Activity log / audit trail view
```




