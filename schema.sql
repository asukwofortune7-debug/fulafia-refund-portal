-- =====================================================================
-- FULAFIA NELFUND Refund Portal - Database Schema
-- Run this once in phpMyAdmin (or `mysql -u user -p dbname < schema.sql`)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS fulafia_nelfund CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fulafia_nelfund;

-- ---------------------------------------------------------------------
-- Students
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matric_number VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    account_number VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nelfund_balance_img VARCHAR(255) DEFAULT NULL,
    refund_status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Admins
-- `role` distinguishes SuperAdmins (who can manage other admin
-- accounts) from regular Admins (who can only review/update students).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('SuperAdmin', 'Admin') NOT NULL DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Audit Logs
-- Records every critical admin action: logins, student status changes,
-- and SuperAdmin create/delete of other admin accounts.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    admin_name VARCHAR(255),
    action_performed TEXT,
    affected_student_matric VARCHAR(50),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- No admin account is seeded here on purpose (a hand-typed password
-- hash is easy to get wrong). After importing this schema, run
-- admin/create_admin.php ONCE from your browser to create the first
-- admin account -- it hashes the password correctly with PHP's own
-- password_hash() and is automatically made a SuperAdmin. Delete that
-- file immediately afterwards.
-- ---------------------------------------------------------------------

-- =====================================================================
-- MIGRATION FOR EXISTING INSTALLS
-- Already ran the original schema.sql before this update? Run just the
-- block below instead of the whole file -- it's safe to re-run and
-- won't touch data you already have.
-- =====================================================================

-- ALTER TABLE admins
--     ADD COLUMN IF NOT EXISTS full_name VARCHAR(150) NOT NULL DEFAULT '' AFTER id,
--     ADD COLUMN IF NOT EXISTS role ENUM('SuperAdmin', 'Admin') NOT NULL DEFAULT 'Admin' AFTER password;
--
-- -- Promote your existing admin(s) to SuperAdmin so you aren't locked
-- -- out of the new Admin Management panel (adjust the email):
-- UPDATE admins SET role = 'SuperAdmin', full_name = 'Your Name' WHERE email = 'admin@fulafia.edu.ng';
--
-- CREATE TABLE IF NOT EXISTS audit_logs (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     admin_id INT,
--     admin_name VARCHAR(255),
--     action_performed TEXT,
--     affected_student_matric VARCHAR(50),
--     timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
-- ) ENGINE=InnoDB;
