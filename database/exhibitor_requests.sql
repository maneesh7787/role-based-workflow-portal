-- Exhibitor Sales Requests Table
-- This table stores sales requests for exhibitor booth requirements

CREATE TABLE IF NOT EXISTS exhibitor_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Requirement Type
    requirement_type ENUM('new_booth', 'booth_renovation', 'booth_rental', 'graphics_only') NOT NULL,
    
    -- Client Details
    client_name VARCHAR(200) NOT NULL,
    client_company VARCHAR(200),
    client_email VARCHAR(100) NOT NULL,
    client_phone VARCHAR(20) NOT NULL,
    
    -- Event Details
    event_name VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    event_location VARCHAR(200) NOT NULL,
    
    -- Booth Details
    booth_size VARCHAR(50),
    booth_type ENUM('standard', 'custom', 'island', 'peninsula', 'inline') NOT NULL,
    booth_number VARCHAR(50),
    
    -- Design Requirements (stored as JSON array)
    design_requirements TEXT,
    
    -- Delivery Priority
    delivery_priority ENUM('standard', 'expedited', 'rush') NOT NULL DEFAULT 'standard',
    
    -- Special Requests
    special_requests TEXT,
    
    -- Status and Metadata
    status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_exhibitor_requests_status ON exhibitor_requests(status);
CREATE INDEX idx_exhibitor_requests_created_by ON exhibitor_requests(created_by);
CREATE INDEX idx_exhibitor_requests_event_date ON exhibitor_requests(event_date);
