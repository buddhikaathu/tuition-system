-- =========================================================
-- Tuition Class Management System - Database Schema
-- Import this file in phpMyAdmin (or `mysql -u root -p < schema.sql`)
-- =========================================================

CREATE DATABASE IF NOT EXISTS tuition_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tuition_system;

-- ---------------------------------------------------------
-- Teachers / Users (login accounts)
-- ---------------------------------------------------------
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    subject ENUM('Math','Science','English') NOT NULL,
    role ENUM('admin','teacher') NOT NULL DEFAULT 'teacher',
    phone VARCHAR(20) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Students (one row per physical student)
-- ---------------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL UNIQUE,   -- e.g. TUT-0001, encoded in the QR code
    full_name VARCHAR(100) NOT NULL,
    grade TINYINT NOT NULL,                     -- 4 - 11
    parent_name VARCHAR(100) DEFAULT NULL,
    contact_number VARCHAR(20) NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    photo VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Enrollments (a student can be enrolled in Math / Science / English
-- separately, each with its own teacher and monthly fee)
-- ---------------------------------------------------------
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject ENUM('Math','Science','English') NOT NULL,
    teacher_id INT NOT NULL,
    monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    start_date DATE NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_subject (student_id, subject),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Payments (one row per subject, per month, per student)
-- ---------------------------------------------------------
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    month TINYINT NOT NULL,      -- 1-12
    year SMALLINT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE DEFAULT NULL,
    payment_method ENUM('Cash','Bank Transfer','Online','Other') DEFAULT 'Cash',
    status ENUM('paid','pending') NOT NULL DEFAULT 'pending',
    recorded_by INT DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_enrollment_month (enrollment_id, month, year),
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Seed data: change these passwords immediately after first login!
-- Default password for all 3 accounts below is:  changeme123
-- ---------------------------------------------------------
INSERT INTO teachers (username, password, full_name, subject, role, phone) VALUES
('admin',   '$2y$10$FOrQ.PYAp0U0wCtEf53.OuQCXOqmUG7K6cbgiLQjRWq99LNxIOh6.', 'Math Teacher (Admin)', 'Math',    'admin',   '0770000001'),
('science', '$2y$10$FOrQ.PYAp0U0wCtEf53.OuQCXOqmUG7K6cbgiLQjRWq99LNxIOh6.', 'Science Teacher',      'Science', 'teacher', '0770000002'),
('english', '$2y$10$FOrQ.PYAp0U0wCtEf53.OuQCXOqmUG7K6cbgiLQjRWq99LNxIOh6.', 'English Teacher',      'English', 'teacher', '0770000003');
