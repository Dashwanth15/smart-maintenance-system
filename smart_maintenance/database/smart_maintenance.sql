-- ===================================================================
-- SMART MAINTENANCE SYSTEM DATABASE STRUCTURE
-- ===================================================================
-- Database: smart_maintenance
-- Version: Updated
-- ===================================================================

-- Create database if not exists and use it
CREATE DATABASE IF NOT EXISTS smart_maintenance;
USE smart_maintenance;

-- ===================================================================
-- 🧍 USERS TABLE
-- ===================================================================
-- Stores information about all users (students, technicians, admins)
-- tech_type applies only to technicians.
-- ===================================================================
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(200) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','technician','admin') NOT NULL DEFAULT 'student',
  tech_type ENUM('Plumbing','Carpentry','Cleaning') DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 🛠️ MAINTENANCE REQUESTS TABLE
-- ===================================================================
-- Each record represents a maintenance request created by a student.
-- Assigned to a technician by the admin.
-- Tracks request progress, timings, and duration.
-- ===================================================================
CREATE TABLE IF NOT EXISTS maintenance_requests (
  request_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  technician_id INT DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  tech_type VARCHAR(100) DEFAULT NULL,
  location VARCHAR(255) DEFAULT NULL,
  contact VARCHAR(100) DEFAULT NULL,
  status ENUM('Not Assigned','Pending','Assigned','In Progress','Completed') NOT NULL DEFAULT 'Pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  start_time DATETIME DEFAULT NULL,
  end_time DATETIME DEFAULT NULL,
  time_taken INT DEFAULT NULL COMMENT 'Time taken in minutes',
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (technician_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 🗣️ FEEDBACK TABLE
-- ===================================================================
-- Stores feedback from students about completed work
-- ===================================================================
CREATE TABLE IF NOT EXISTS feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  student_id INT NOT NULL,
  technician_id INT DEFAULT NULL,
  rating TINYINT UNSIGNED,
  comments TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES maintenance_requests(request_id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (technician_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 🔑 LOGIN ACTIVITY TABLE
-- ===================================================================
-- Logs each user login and logout with timestamps.
-- ===================================================================
CREATE TABLE IF NOT EXISTS login_activity (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  logout_time DATETIME DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 📜 SAMPLE DATA (Optional)
-- ===================================================================
INSERT INTO users (name, email, password, role, tech_type) VALUES
('Admin User', 'admin@example.com', 'admin123', 'admin', NULL),
('John Technician', 'tech1@example.com', 'tech123', 'technician', 'Plumbing'),
('Ravi Technician', 'tech2@example.com', 'tech123', 'technician', 'Carpentry'),
('Anita Student', 'student1@example.com', 'student123', 'student', NULL),
('Rahul Student', 'student2@example.com', 'student123', 'student', NULL);

INSERT INTO maintenance_requests (student_id, title, description, tech_type, location, contact, status)
VALUES
(4, 'Leaky faucet in bathroom', 'The faucet in the 2nd floor bathroom is leaking continuously.', 'Plumbing', 'Block A, Room 204', '9876543210', 'Pending'),
(5, 'Door hinge broken', 'Main door hinge needs replacement.', 'Carpentry', 'Block B, Room 101', '9123456780', 'Pending');

CREATE TABLE reviews (
  review_id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  technician_id INT NOT NULL,
  student_id INT NOT NULL,
  rating INT CHECK (rating BETWEEN 1 AND 5),
  feedback TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES maintenance_requests(request_id) ON DELETE CASCADE,
  FOREIGN KEY (technician_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
);

ALTER TABLE users MODIFY tech_type VARCHAR(100) DEFAULT NULL;



-- Optional sample feedback (won't link until request ids exist)
-- INSERT INTO feedback (request_id, student_id, technician_id, rating, comments) VALUES
-- (1, 4, 2, 5, 'Quick and professional.');

-- ===================================================================
-- ✅ END OF SCRIPT
-- ===================================================================
