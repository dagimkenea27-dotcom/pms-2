-- sql/setup.sql
CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    quantity INT DEFAULT 0,
    price DECIMAL(10,2),
    cost_price DECIMAL(10,2),
    min_stock INT DEFAULT 5,
    supplier VARCHAR(255),
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    movement_type ENUM('IN', 'OUT'),
    quantity INT,
    reason VARCHAR(255),
    reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT
);

-- Insert sample data
INSERT INTO products (sku, name, description, category, quantity, price, cost_price, min_stock, supplier) VALUES
('LAP-001', 'Gaming Laptop', 'High-performance gaming laptop', 'Electronics', 15, 1299.99, 899.99, 3, 'TechSupplier Inc'),
('MON-002', '27-inch Monitor', '4K UHD Monitor', 'Electronics', 8, 399.99, 249.99, 5, 'DisplayCo'),
('KEY-003', 'Mechanical Keyboard', 'RGB Mechanical Keyboard', 'Accessories', 25, 89.99, 45.99, 10, 'KeyTech Ltd');

INSERT INTO suppliers (name, contact_person, email, phone) VALUES
('TechSupplier Inc', 'John Smith', 'john@techsupplier.com', '+1-555-0101'),
('DisplayCo', 'Sarah Johnson', 'sarah@displayco.com', '+1-555-0102'),
('KeyTech Ltd', 'Mike Brown', 'mike@keytech.com', '+1-555-0103');
-- Add reference field to stock_movements if not exists
ALTER TABLE stock_movements ADD COLUMN reference VARCHAR(100) AFTER reason;

-- Create suppliers table if not exists
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample suppliers if table is empty
INSERT IGNORE INTO suppliers (name, contact_person, email, phone) VALUES
('TechSupplier Inc', 'John Smith', 'john@techsupplier.com', '+1-555-0101'),
('DisplayCo', 'Sarah Johnson', 'sarah@displayco.com', '+1-555-0102'),
('KeyTech Ltd', 'Mike Brown', 'mike@keytech.com', '+1-555-0103');