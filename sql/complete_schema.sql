-- =============================================
-- Stock Management System - Complete Database Schema
-- Version: 1.0
-- Date: 2025-12-07
-- =============================================

-- Create database
CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

-- =============================================
-- Core Tables
-- =============================================

-- Users table
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

-- Suppliers table
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

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Brands table
CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
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
    supplier VARCHAR(255),
    location VARCHAR(100),
    category_id INT,
    brand_id INT,
    image VARCHAR(255) NULL,
    barcode VARCHAR(100) NULL,
    has_variants BOOLEAN DEFAULT FALSE,
    forecast_enabled BOOLEAN DEFAULT TRUE,
    avg_daily_sales DECIMAL(10,2) DEFAULT 0.00,
    reorder_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
);

-- Product variants table
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL,
    size VARCHAR(50),
    color VARCHAR(50),
    quantity INT DEFAULT 0,
    price DECIMAL(10,2),
    cost_price DECIMAL(10,2),
    location VARCHAR(100),
    min_stock INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_sku (sku)
);

-- Stock movements table
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    variant_id INT DEFAULT NULL,
    supplier_id INT,
    movement_type ENUM('IN', 'OUT'),
    quantity INT,
    reason VARCHAR(255),
    reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tax and fee configurations table
CREATE TABLE IF NOT EXISTS tax_fee_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('tax', 'fee') NOT NULL,
    value DECIMAL(5,2) NOT NULL,
    is_percentage BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Price calculation history table
CREATE TABLE IF NOT EXISTS price_calculation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    variant_id INT DEFAULT NULL,
    base_price DECIMAL(10,2),
    calculated_price DECIMAL(10,2),
    tax_amount DECIMAL(10,2),
    fee_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

-- Routes table for delivery routes
CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    warehouse_locations JSON,
    driver_count INT DEFAULT 1,
    country_code VARCHAR(10) DEFAULT 'et',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Route stops table
CREATE TABLE IF NOT EXISTS route_stops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT,
    address TEXT NOT NULL,
    coordinates JSON,
    stop_number INT,
    driver_id INT,
    distance_from_previous DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
);

-- Route optimization settings table
CREATE TABLE IF NOT EXISTS route_optimization_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT,
    algorithm VARCHAR(50) DEFAULT 'nearest_neighbor',
    time_windows JSON,
    vehicle_capacity INT,
    constraints JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
);

-- Daily sales tracker table
CREATE TABLE IF NOT EXISTS daily_sales_tracker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    product_type VARCHAR(100),
    size VARCHAR(20),
    color VARCHAR(50),
    price VARCHAR(50),
    customer_info TEXT,
    customer_location VARCHAR(255),
    purchased BOOLEAN DEFAULT FALSE,
    notes TEXT,
    needs_followup BOOLEAN DEFAULT FALSE,
    followup_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Marketing attribution for Gojo Shop Analysis
CREATE TABLE IF NOT EXISTS marketing_attribution (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    product_ordered VARCHAR(255) NOT NULL,
    product_interested VARCHAR(255),
    dormant_days INT,
    incentive_used VARCHAR(100),
    last_touch_point VARCHAR(100),
    channel_combination VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory forecasting table
CREATE TABLE IF NOT EXISTS inventory_forecasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    avg_daily_sales DECIMAL(10,4) DEFAULT 0.0000,
    forecast_days_remaining INT DEFAULT NULL,
    predicted_stockout_date DATE NULL,
    recommended_min_stock INT DEFAULT NULL,
    confidence_score DECIMAL(3,2) DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_stockout (predicted_stockout_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Expense categories
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-money-bill',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Business expenses
CREATE TABLE IF NOT EXISTS business_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ETB',
    expense_date DATE NOT NULL,
    description TEXT,
    reference_number VARCHAR(100),
    payment_method ENUM('cash', 'bank_transfer', 'check', 'credit_card') DEFAULT 'cash',
    attachment_path VARCHAR(255),
    vehicle_id INT DEFAULT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_date (expense_date),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attributes table (for product attributes)
CREATE TABLE IF NOT EXISTS attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('text', 'number', 'date', 'boolean') DEFAULT 'text',
    is_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Attribute values table
CREATE TABLE IF NOT EXISTS attribute_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attribute_id INT NOT NULL,
    value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE
);

-- Product attributes junction table
CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute_id INT NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_attribute (product_id, attribute_id)
);

-- =============================================
-- Initial Data
-- =============================================

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (username, email, password_hash, first_name, last_name, role) 
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Admin', 'admin');

-- Insert sample suppliers
INSERT IGNORE INTO suppliers (name, contact_person, email, phone, payment_terms) VALUES
('TechSupplier Inc', 'John Smith', 'john@techsupplier.com', '+1-555-0101', 'Net 30'),
('DisplayCo', 'Sarah Johnson', 'sarah@displayco.com', '+1-555-0102', 'Net 15'),
('KeyTech Ltd', 'Mike Brown', 'mike@keytech.com', '+1-555-0103', 'Immediate');

-- Insert sample categories
INSERT IGNORE INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and accessories'),
('Accessories', 'Various accessories for products'),
('Clothing', 'Apparel and fashion items'),
('Home & Garden', 'Home improvement and garden supplies');

-- Insert sample brands
INSERT IGNORE INTO brands (name, description) VALUES
('TechBrand', 'High-quality technology products'),
('FashionStyle', 'Trendy fashion items'),
('HomePro', 'Professional home improvement tools');

-- Insert sample products
INSERT IGNORE INTO products (sku, name, description, category, quantity, price, cost_price, min_stock, supplier, supplier_id, category_id, brand_id) VALUES
('LAP-001', 'Gaming Laptop', 'High-performance gaming laptop', 'Electronics', 15, 1299.99, 899.99, 3, 'TechSupplier Inc', 1, 1, 1),
('MON-002', '27-inch Monitor', '4K UHD Monitor', 'Electronics', 8, 399.99, 249.99, 5, 'DisplayCo', 2, 1, 1),
('KEY-003', 'Mechanical Keyboard', 'RGB Mechanical Keyboard', 'Accessories', 25, 89.99, 45.99, 10, 'KeyTech Ltd', 3, 2, 1);

-- =============================================
-- Indexes for Performance
-- =============================================

-- Add indexes for better query performance
CREATE INDEX idx_products_sku ON products(sku);
CREATE INDEX idx_products_category_id ON products(category_id);
CREATE INDEX idx_products_brand_id ON products(brand_id);
CREATE INDEX idx_products_supplier_id ON products(supplier_id);
CREATE INDEX idx_product_variants_product_id ON product_variants(product_id);
CREATE INDEX idx_product_variants_sku ON product_variants(sku);
CREATE INDEX idx_stock_movements_product_id ON stock_movements(product_id);
CREATE INDEX idx_stock_movements_variant_id ON stock_movements(variant_id);
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);


