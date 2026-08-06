-- Strategic Enhancements Migration
-- Phases: Inventory Forecasting, Advanced Logistics, and Financial P&L

-- ============================================
-- PHASE 1: INVENTORY FORECASTING
-- ============================================

CREATE TABLE IF NOT EXISTS inventory_forecasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    avg_daily_sales DECIMAL(10,4) DEFAULT 0.0000,
    forecast_days_remaining INT DEFAULT NULL,
    predicted_stockout_date DATE NULL,
    recommended_min_stock INT DEFAULT NULL,
    confidence_score DECIMAL(3,2) DEFAULT 0.00, -- 0.00 to 1.00
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_stockout (predicted_stockout_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE products ADD COLUMN IF NOT EXISTS forecast_enabled BOOLEAN DEFAULT TRUE;

-- ============================================
-- PHASE 3: FINANCIAL TRACKING
-- ============================================

CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-money-bill',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    vehicle_id INT DEFAULT NULL, -- Optional: link to fleet
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (vehicle_id) REFERENCES route_vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_date (expense_date),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default categories
INSERT IGNORE INTO expense_categories (name, description, icon) VALUES
('Fuel', 'Vehicle fuel costs', 'fas fa-gas-pump'),
('Maintenance', 'Vehicle and equipment repairs', 'fas fa-tools'),
('Rent', 'Warehouse or office rent', 'fas fa-building'),
('Salaries', 'Staff and driver wages', 'fas fa-user-tie'),
('Utilities', 'Electricity, water, internet', 'fas fa-bolt'),
('Marketing', 'Advertising and promotions', 'fas fa-ad'),
('Other', 'Miscellaneous expenses', 'fas fa-dot-circle');

-- ============================================
-- SCHEMA VERSION UPDATE
-- ============================================

INSERT INTO schema_version (version, description) VALUES 
('3.0.0', 'Strategic Enhancements - Forecasting, Financials, and Logistics Infrastructure')
ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP;
