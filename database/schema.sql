-- Role-Based Workflow Portal Database Schema
-- MySQL Database

-- Create Database
CREATE DATABASE IF NOT EXISTS workflow_portal;
USE workflow_portal;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Sales', 'Design', 'Approval') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Requests Table
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(200) NOT NULL,
    location VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    description TEXT,
    status ENUM('Pending Design', 'Pending Approval', 'Sent to Sales', 'Approved', 'Closed', 'Returned to Design') DEFAULT 'Pending Design',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Request Designs Table (uploaded by Design team)
CREATE TABLE IF NOT EXISTS request_designs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Request Attachments Table (uploaded by Sales team)
CREATE TABLE IF NOT EXISTS request_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    uploaded_by INT NOT NULL,
    is_client_update TINYINT(1) DEFAULT 0,
    client_update_remarks TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Approvals Table
CREATE TABLE IF NOT EXISTS approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    pricing DECIMAL(10, 2),
    remarks TEXT,
    approved_by INT NOT NULL,
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_id INT NOT NULL,
    type ENUM('New Request', 'Design Uploaded', 'Approval Completed', 'Returned for Changes', 'Approved', 'Closed') NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_requests_status ON requests(status);
CREATE INDEX idx_requests_created_by ON requests(created_by);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);

-- Insert default admin user (password: admin123)
-- Hash generated using: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (username, email, password, full_name, role, is_active) 
VALUES ('admin', 'admin@workflowportal.com', '$2y$10$HAT1AWw2du48OPdOrH9J1OoAqhAs/AElZBYZ7cxp2hq43svUuoz0m', 'System Admin', 'Admin', 1);
