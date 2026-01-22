-- Demo Data for Role-Based Workflow Portal
-- This file contains sample data for testing the application

USE workflow_portal;

-- Create sample users for each role (passwords are all 'password123')
-- Sales Users
INSERT INTO users (username, email, password, full_name, role, is_active) VALUES
('john_sales', 'john@sales.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', 'Sales', 1),
('sarah_sales', 'sarah@sales.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', 'Sales', 1);

-- Design Users
INSERT INTO users (username, email, password, full_name, role, is_active) VALUES
('mike_design', 'mike@design.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Wilson', 'Design', 1),
('lisa_design', 'lisa@design.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa Anderson', 'Design', 1);

-- Approval Users
INSERT INTO users (username, email, password, full_name, role, is_active) VALUES
('david_approval', 'david@approval.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Brown', 'Approval', 1),
('emily_approval', 'emily@approval.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emily Davis', 'Approval', 1);

-- Sample requests from sales users
INSERT INTO requests (event_name, location, event_date, event_time, description, status, created_by) VALUES
('Corporate Annual Meeting 2024', 'New York Convention Center', '2024-06-15', '09:00:00', 
 'Annual corporate meeting with 500+ attendees. Need professional stage design with company branding.', 
 'Pending Design', 2),

('Product Launch Event', 'San Francisco Tech Hub', '2024-07-20', '14:00:00', 
 'Product launch event for our new software. Modern tech-themed design required.', 
 'Pending Design', 3),

('Wedding Reception', 'Grand Hotel Ballroom', '2024-08-10', '18:00:00', 
 'Elegant wedding reception for 200 guests. Classic and romantic theme.', 
 'Pending Approval', 2),

('Charity Gala', 'Metropolitan Museum', '2024-09-05', '19:00:00', 
 'Charity fundraising gala. Sophisticated and elegant design needed.', 
 'Sent to Sales', 3);

-- Note: In a real scenario, you would also need to:
-- 1. Add sample design images to the uploads folder
-- 2. Insert records into request_designs table pointing to those images
-- 3. Insert sample approval records
-- 4. Create sample notifications

-- Example notifications (optional)
INSERT INTO notifications (user_id, request_id, type, message, is_read) VALUES
(4, 1, 'New Request', 'New design request created: Corporate Annual Meeting 2024', 0),
(4, 2, 'New Request', 'New design request created: Product Launch Event', 0),
(6, 3, 'Design Uploaded', 'Design images uploaded for Wedding Reception request', 0),
(2, 4, 'Approval Completed', 'Your request for Charity Gala has been approved', 0);

-- Example activity logs
INSERT INTO activity_logs (user_id, request_id, action, details, ip_address) VALUES
(1, NULL, 'User Login', 'User logged in successfully', '127.0.0.1'),
(2, 1, 'Request Created', 'Created request: Corporate Annual Meeting 2024', '127.0.0.1'),
(3, 2, 'Request Created', 'Created request: Product Launch Event', '127.0.0.1'),
(4, 1, 'Design Uploaded', 'Uploaded 3 images for request', '127.0.0.1');

-- Display summary
SELECT 'Demo data inserted successfully!' as Status;
SELECT CONCAT('Total Users: ', COUNT(*)) as Summary FROM users;
SELECT CONCAT('Total Requests: ', COUNT(*)) as Summary FROM requests;
SELECT CONCAT('Total Notifications: ', COUNT(*)) as Summary FROM notifications;
