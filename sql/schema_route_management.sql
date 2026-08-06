-- ============================================
-- Route Management System - Complete Schema
-- ============================================

-- ============================================
-- DRIVERS & VEHICLES
-- ============================================

CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NOT NULL,
    license_number VARCHAR(100) NULL,
    license_expiry DATE NULL,
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    skills JSON NULL COMMENT 'e.g., ["refrigerated", "hazmat", "heavy_load"]',
    max_working_hours DECIMAL(4,2) DEFAULT 8.00,
    hourly_rate DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(50) NOT NULL UNIQUE,
    vehicle_type ENUM('van', 'truck', 'motorcycle', 'car', 'refrigerated') DEFAULT 'van',
    make VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    year YEAR NULL,
    capacity_weight DECIMAL(10,2) NULL COMMENT 'in kg',
    capacity_volume DECIMAL(10,2) NULL COMMENT 'in cubic meters',
    fuel_type ENUM('petrol', 'diesel', 'electric', 'hybrid') DEFAULT 'petrol',
    fuel_efficiency DECIMAL(6,2) NULL COMMENT 'km per liter or km per kWh',
    status ENUM('active', 'maintenance', 'retired') DEFAULT 'active',
    last_maintenance_date DATE NULL,
    next_maintenance_date DATE NULL,
    insurance_expiry DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_type (vehicle_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS driver_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    break_start TIME NULL,
    break_end TIME NULL,
    is_available BOOLEAN DEFAULT TRUE,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_driver_date (driver_id, date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicle_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    route_id INT NULL,
    assignment_date DATE NOT NULL,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE SET NULL,
    INDEX idx_assignment_date (assignment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CUSTOMER MANAGEMENT
-- ============================================

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(50) UNIQUE NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    company_name VARCHAR(255) NULL,
    notification_preference ENUM('email', 'sms', 'both', 'none') DEFAULT 'email',
    default_address_id INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_code (customer_code),
    INDEX idx_email (email),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    address_label VARCHAR(100) NULL COMMENT 'e.g., Home, Office, Warehouse',
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) DEFAULT 'Ethiopia',
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    special_instructions TEXT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_coordinates (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DELIVERY MANAGEMENT
-- ============================================

CREATE TABLE IF NOT EXISTS delivery_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_number VARCHAR(100) UNIQUE NOT NULL,
    customer_id INT NULL,
    address_id INT NULL,
    route_stop_id INT NULL,
    weight DECIMAL(10,2) NULL COMMENT 'in kg',
    volume DECIMAL(10,2) NULL COMMENT 'in cubic meters',
    dimensions VARCHAR(100) NULL COMMENT 'LxWxH in cm',
    package_type VARCHAR(50) NULL COMMENT 'e.g., fragile, perishable, standard',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    value DECIMAL(10,2) NULL COMMENT 'declared value',
    special_handling JSON NULL COMMENT 'e.g., ["refrigerated", "fragile", "signature_required"]',
    status ENUM('pending', 'in_transit', 'delivered', 'failed', 'returned') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (address_id) REFERENCES customer_addresses(id) ON DELETE SET NULL,
    FOREIGN KEY (route_stop_id) REFERENCES route_stops(id) ON DELETE SET NULL,
    INDEX idx_tracking (tracking_number),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS delivery_time_windows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    earliest_time TIME NULL,
    latest_time TIME NULL,
    preferred_date DATE NULL,
    is_flexible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES delivery_packages(id) ON DELETE CASCADE,
    INDEX idx_package (package_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS delivery_proof (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    route_stop_id INT NULL,
    delivered_by INT NULL COMMENT 'driver_id',
    delivery_time DATETIME NOT NULL,
    recipient_name VARCHAR(255) NULL,
    recipient_signature TEXT NULL COMMENT 'base64 encoded signature image',
    photo_path VARCHAR(500) NULL,
    notes TEXT NULL,
    gps_latitude DECIMAL(10,8) NULL,
    gps_longitude DECIMAL(11,8) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES delivery_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (route_stop_id) REFERENCES route_stops(id) ON DELETE SET NULL,
    FOREIGN KEY (delivered_by) REFERENCES drivers(id) ON DELETE SET NULL,
    INDEX idx_package (package_id),
    INDEX idx_delivery_time (delivery_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTIFICATIONS & TRACKING
-- ============================================

CREATE TABLE IF NOT EXISTS delivery_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    customer_id INT NOT NULL,
    notification_type ENUM('dispatch', 'in_transit', 'out_for_delivery', 'delivered', 'failed') NOT NULL,
    channel ENUM('email', 'sms', 'push') NOT NULL,
    recipient VARCHAR(255) NOT NULL COMMENT 'email or phone number',
    message TEXT NOT NULL,
    sent_at DATETIME NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES delivery_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tracking_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    tracking_token VARCHAR(100) UNIQUE NOT NULL,
    expires_at DATETIME NULL,
    view_count INT DEFAULT 0,
    last_viewed_at DATETIME NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES delivery_packages(id) ON DELETE CASCADE,
    INDEX idx_token (tracking_token),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ROUTE TEMPLATES & HISTORY
-- ============================================

CREATE TABLE IF NOT EXISTS route_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    recurrence ENUM('daily', 'weekly', 'monthly', 'custom') NULL,
    recurrence_pattern JSON NULL COMMENT 'e.g., {"days": ["monday", "wednesday"]}',
    warehouse_location VARCHAR(500) NOT NULL,
    warehouse_lat DECIMAL(10,8) NULL,
    warehouse_lon DECIMAL(11,8) NULL,
    addresses TEXT NOT NULL COMMENT 'newline separated addresses',
    driver_count INT DEFAULT 1,
    country_code VARCHAR(2) DEFAULT 'et',
    created_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created_by (created_by),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS route_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    driver_id INT NULL,
    vehicle_id INT NULL,
    planned_distance DECIMAL(10,2) NULL COMMENT 'in km',
    actual_distance DECIMAL(10,2) NULL COMMENT 'in km',
    planned_duration INT NULL COMMENT 'in minutes',
    actual_duration INT NULL COMMENT 'in minutes',
    fuel_consumed DECIMAL(10,2) NULL COMMENT 'in liters',
    fuel_cost DECIMAL(10,2) NULL,
    labor_cost DECIMAL(10,2) NULL,
    total_cost DECIMAL(10,2) NULL,
    deliveries_planned INT DEFAULT 0,
    deliveries_completed INT DEFAULT 0,
    deliveries_failed INT DEFAULT 0,
    on_time_percentage DECIMAL(5,2) NULL,
    customer_rating DECIMAL(3,2) NULL COMMENT 'average rating 1-5',
    carbon_footprint DECIMAL(10,2) NULL COMMENT 'CO2 in kg',
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    INDEX idx_route (route_id),
    INDEX idx_completed_at (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CONFIGURATION & PREFERENCES
-- ============================================

CREATE TABLE IF NOT EXISTS optimization_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS traffic_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin_lat DECIMAL(10,8) NOT NULL,
    origin_lon DECIMAL(11,8) NOT NULL,
    destination_lat DECIMAL(10,8) NOT NULL,
    destination_lon DECIMAL(11,8) NOT NULL,
    distance DECIMAL(10,2) NULL COMMENT 'in km',
    duration INT NULL COMMENT 'in minutes',
    traffic_level ENUM('low', 'moderate', 'heavy', 'severe') NULL,
    cached_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    UNIQUE KEY unique_route (origin_lat, origin_lon, destination_lat, destination_lon),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cost_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) NULL COMMENT 'e.g., per_km, per_hour, per_liter',
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default cost settings
INSERT INTO cost_settings (setting_name, setting_value, unit, description) VALUES
('fuel_cost_per_liter', 50.00, 'ETB', 'Cost of fuel per liter'),
('driver_hourly_rate', 100.00, 'ETB', 'Default driver hourly rate'),
('vehicle_maintenance_per_km', 2.50, 'ETB', 'Average maintenance cost per km'),
('carbon_emission_factor', 2.31, 'kg_CO2_per_liter', 'CO2 emissions per liter of fuel')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
