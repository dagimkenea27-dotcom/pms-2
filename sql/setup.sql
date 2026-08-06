-- sql/setup.sql
CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

-- Users table (New)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Suppliers table (Updated)
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    website VARCHAR(255),
    payment_terms VARCHAR(100),
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    quantity INT DEFAULT 0,
    price DECIMAL(10,2),
    cost_price DECIMAL(10,2),
    min_stock INT DEFAULT 5,
    supplier_id INT,
    supplier VARCHAR(255), -- Kept for backward compatibility, but supplier_id should be used
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- Stock movements table
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    movement_type ENUM('IN', 'OUT'),
    quantity INT,
    reason VARCHAR(255),
    reference VARCHAR(100),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Vendor payment requests table
CREATE TABLE IF NOT EXISTS vendor_payment_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_name VARCHAR(255) NOT NULL,
    order_id VARCHAR(100) NOT NULL,
    order_amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
    requested_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    pays_commission TINYINT(1) DEFAULT 0,
    commission_rate DECIMAL(5,2) DEFAULT 0.00,
    commission_amount DECIMAL(15,2) DEFAULT 0.00,
    net_amount DECIMAL(15,2) DEFAULT 0.00,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (username, email, password_hash, first_name, last_name, role) 
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Admin', 'admin');

-- Insert sample suppliers
INSERT IGNORE INTO suppliers (name, contact_person, email, phone, payment_terms) VALUES
('TechSupplier Inc', 'John Smith', 'john@techsupplier.com', '+1-555-0101', 'Net 30'),
('DisplayCo', 'Sarah Johnson', 'sarah@displayco.com', '+1-555-0102', 'Net 15'),
('KeyTech Ltd', 'Mike Brown', 'mike@keytech.com', '+1-555-0103', 'Immediate');

-- Insert sample products
INSERT IGNORE INTO products (sku, name, description, category, quantity, price, cost_price, min_stock, supplier, supplier_id) VALUES
('LAP-001', 'Gaming Laptop', 'High-performance gaming laptop', 'Electronics', 15, 1299.99, 899.99, 3, 'TechSupplier Inc', 1),
('MON-002', '27-inch Monitor', '4K UHD Monitor', 'Electronics', 8, 399.99, 249.99, 5, 'DisplayCo', 2),
('KEY-003', 'Mechanical Keyboard', 'RGB Mechanical Keyboard', 'Accessories', 25, 89.99, 45.99, 10, 'KeyTech Ltd', 3);