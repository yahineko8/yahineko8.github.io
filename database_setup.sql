-- ============================================
-- Motor Component Management System - Database Setup
-- With RFID Support
-- ============================================

CREATE DATABASE IF NOT EXISTS components_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE components_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Components table WITH RFID support
CREATE TABLE IF NOT EXISTS components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_id VARCHAR(50) UNIQUE NOT NULL,
    rfid_tag VARCHAR(100) UNIQUE,           -- RFID tag number
    rfid_epc VARCHAR(100),                  -- RFID Electronic Product Code
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    specs TEXT,
    compatibility VARCHAR(255),
    image_url VARCHAR(500),
    location VARCHAR(100),                  -- Physical storage location
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rfid (rfid_tag),              -- Index for RFID lookups
    INDEX idx_component_id (component_id)
);

-- RFID Scan Logs table
CREATE TABLE IF NOT EXISTS rfid_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfid_tag VARCHAR(100) NOT NULL,
    component_id INT,
    scan_type ENUM('check_in', 'check_out', 'inventory', 'sale') DEFAULT 'inventory',
    scanned_by INT,
    location VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE SET NULL,
    FOREIGN KEY (scanned_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_rfid_tag (rfid_tag),
    INDEX idx_scan_date (created_at)
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    component_id INT NOT NULL,
    rfid_tag VARCHAR(100),                  -- Store RFID at time of transaction
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('credit_card', 'debit_card', 'digital_wallet', 'bank_transfer', 'rfid_cashless') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE CASCADE,
    INDEX idx_rfid_transaction (rfid_tag)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@motorparts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample components WITH RFID tags
INSERT INTO components (component_id, rfid_tag, rfid_epc, name, brand, price, stock_quantity, specs, compatibility, image_url, location) VALUES
('ENG-001', 'RFID-001-ABC', 'EPC-1234567890', 'High Performance Engine Block', 'Honda', 15000.00, 5, '2.0L VTEC, Aluminum alloy, 200HP', 'Honda Civic 2016-2021', 'https://via.placeholder.com/300x200/1e3c72/ffffff?text=Engine+Block', 'Warehouse A-Shelf-1'),
('BRK-002', 'RFID-002-DEF', 'EPC-2345678901', 'Ceramic Brake Pads Set', 'Brembo', 3500.00, 20, 'Ceramic compound, Low dust, High performance', 'Universal fit for most sedans', 'https://via.placeholder.com/300x200/2a5298/ffffff?text=Brake+Pads', 'Warehouse B-Shelf-3'),
('SUS-003', 'RFID-003-GHI', 'EPC-3456789012', 'Sport Suspension Kit', 'KYB', 8500.00, 8, 'Adjustable height, Gas shock absorbers', 'Toyota Vios, Honda City', 'https://via.placeholder.com/300x200/1e3c72/ffffff?text=Suspension', 'Warehouse A-Shelf-2'),
('FIL-004', 'RFID-004-JKL', 'EPC-4567890123', 'High Flow Air Filter', 'K&N', 1200.00, 15, 'Washable, Reusable, Increased airflow', 'Universal cone filter', 'https://via.placeholder.com/300x200/2a5298/ffffff?text=Air+Filter', 'Warehouse C-Shelf-1'),
('EXH-005', 'RFID-005-MNO', 'EPC-5678901234', 'Stainless Steel Exhaust System', 'MagnaFlow', 12000.00, 3, 'Mandrel bent, 3-inch piping, Sport sound', 'Honda Civic Type R', 'https://via.placeholder.com/300x200/1e3c72/ffffff?text=Exhaust', 'Warehouse A-Shelf-5');

