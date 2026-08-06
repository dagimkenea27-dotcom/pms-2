-- Route Optimizer Enhancements Schema
-- Phase 1: Database Enhancements

-- ============================================
-- 1. DRIVER MANAGEMENT TABLES
-- ============================================

-- Driver profiles with skills and availability
CREATE TABLE IF NOT EXISTS route_drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    driver_code VARCHAR(20) UNIQUE NOT NULL,
    license_number VARCHAR(50),
    license_expiry DATE,
    phone VARCHAR(20),
    email VARCHAR(100),
    status ENUM('active', 'inactive', 'on_leave', 'suspended') DEFAULT 'active',
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_deliveries INT DEFAULT 0,
    successful_deliveries INT DEFAULT 0,
    on_time_percentage DECIMAL(5,2) DEFAULT 0.00,
    preferred_vehicle_type VARCHAR(50),
    max_hours_per_day DECIMAL(4,2) DEFAULT 8.00,
    hourly_rate DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_rating (rating),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Driver skills and certifications
CREATE TABLE IF NOT EXISTS route_driver_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    skill_type ENUM('hazmat', 'refrigerated', 'oversized', 'fragile', 'express', 'international') NOT NULL,
    certification_number VARCHAR(50),
    certified_date DATE,
    expiry_date DATE,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES route_drivers(id) ON DELETE CASCADE,
    INDEX idx_driver_skill (driver_id, skill_type),
    INDEX idx_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Driver availability schedule
CREATE TABLE IF NOT EXISTS route_driver_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES route_drivers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_driver_date (driver_id, date),
    INDEX idx_date (date),
    INDEX idx_availability (driver_id, date, is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. VEHICLE MANAGEMENT
-- ============================================

CREATE TABLE IF NOT EXISTS route_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_code VARCHAR(20) UNIQUE NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type ENUM('van', 'truck', 'motorcycle', 'car', 'cargo_bike') NOT NULL,
    make VARCHAR(50),
    model VARCHAR(50),
    year INT,
    capacity_weight DECIMAL(10,2) DEFAULT 0.00, -- in kg
    capacity_volume DECIMAL(10,2) DEFAULT 0.00, -- in cubic meters
    fuel_type ENUM('gasoline', 'diesel', 'electric', 'hybrid') DEFAULT 'gasoline',
    fuel_efficiency DECIMAL(5,2) DEFAULT 0.00, -- km per liter or kWh
    status ENUM('active', 'maintenance', 'retired') DEFAULT 'active',
    has_refrigeration BOOLEAN DEFAULT FALSE,
    has_lift_gate BOOLEAN DEFAULT FALSE,
    gps_device_id VARCHAR(50),
    insurance_expiry DATE,
    last_maintenance DATE,
    next_maintenance DATE,
    odometer_reading DECIMAL(10,2) DEFAULT 0.00,
    cost_per_km DECIMAL(10,4) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_type (vehicle_type),
    INDEX idx_maintenance (next_maintenance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Driver-Vehicle assignments
CREATE TABLE IF NOT EXISTS route_driver_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES route_drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES route_vehicles(id) ON DELETE CASCADE,
    INDEX idx_driver (driver_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_primary (driver_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. ENHANCED ROUTE TRACKING
-- ============================================

-- Add columns to existing routes table
ALTER TABLE routes 
ADD COLUMN IF NOT EXISTS assigned_driver_id INT NULL,
ADD COLUMN IF NOT EXISTS assigned_vehicle_id INT NULL,
ADD COLUMN IF NOT EXISTS route_status ENUM('draft', 'planned', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
ADD COLUMN IF NOT EXISTS estimated_duration_minutes INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS estimated_cost DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS actual_duration_minutes INT NULL,
ADD COLUMN IF NOT EXISTS actual_cost DECIMAL(10,2) NULL,
ADD COLUMN IF NOT EXISTS started_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS optimization_algorithm VARCHAR(50) DEFAULT 'nearest_neighbor',
ADD COLUMN IF NOT EXISTS traffic_considered BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS notes TEXT NULL,
ADD INDEX idx_status (route_status),
ADD INDEX idx_driver (assigned_driver_id),
ADD INDEX idx_vehicle (assigned_vehicle_id),
ADD INDEX idx_created_date (created_at);

-- Route performance metrics
CREATE TABLE IF NOT EXISTS route_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    total_distance_km DECIMAL(10,2) DEFAULT 0.00,
    total_duration_minutes INT DEFAULT 0,
    total_stops INT DEFAULT 0,
    completed_stops INT DEFAULT 0,
    failed_stops INT DEFAULT 0,
    on_time_stops INT DEFAULT 0,
    late_stops INT DEFAULT 0,
    fuel_cost DECIMAL(10,2) DEFAULT 0.00,
    driver_cost DECIMAL(10,2) DEFAULT 0.00,
    total_cost DECIMAL(10,2) DEFAULT 0.00,
    efficiency_score DECIMAL(5,2) DEFAULT 0.00, -- 0-100
    customer_satisfaction DECIMAL(3,2) DEFAULT 0.00, -- 0-5
    carbon_emissions_kg DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    INDEX idx_route (route_id),
    INDEX idx_efficiency (efficiency_score),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. DELIVERY TRACKING
-- ============================================

CREATE TABLE IF NOT EXISTS route_deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    stop_id INT NOT NULL,
    sequence_number INT NOT NULL,
    customer_name VARCHAR(255),
    customer_phone VARCHAR(20),
    customer_email VARCHAR(100),
    address TEXT NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    delivery_type ENUM('standard', 'express', 'scheduled', 'same_day') DEFAULT 'standard',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    time_window_start TIME NULL,
    time_window_end TIME NULL,
    estimated_arrival DATETIME NULL,
    actual_arrival DATETIME NULL,
    estimated_departure DATETIME NULL,
    actual_departure DATETIME NULL,
    status ENUM('pending', 'in_transit', 'arrived', 'delivered', 'failed', 'cancelled') DEFAULT 'pending',
    failure_reason TEXT NULL,
    package_count INT DEFAULT 1,
    total_weight_kg DECIMAL(10,2) DEFAULT 0.00,
    total_volume_m3 DECIMAL(10,2) DEFAULT 0.00,
    special_instructions TEXT NULL,
    requires_signature BOOLEAN DEFAULT FALSE,
    signature_image_path VARCHAR(255) NULL,
    proof_of_delivery_path VARCHAR(255) NULL,
    customer_rating INT NULL,
    customer_feedback TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (stop_id) REFERENCES route_stops(id) ON DELETE CASCADE,
    INDEX idx_route (route_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_time_window (time_window_start, time_window_end),
    INDEX idx_sequence (route_id, sequence_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. ROUTE TEMPLATES
-- ============================================

CREATE TABLE IF NOT EXISTS route_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    category VARCHAR(50),
    warehouse_locations JSON, -- Store multiple warehouses
    typical_stop_count INT DEFAULT 0,
    typical_distance_km DECIMAL(10,2) DEFAULT 0.00,
    typical_duration_minutes INT DEFAULT 0,
    days_of_week JSON, -- ["monday", "tuesday", ...]
    frequency ENUM('daily', 'weekly', 'biweekly', 'monthly', 'custom') DEFAULT 'custom',
    use_count INT DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created_by (created_by),
    INDEX idx_public (is_public),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template stops
CREATE TABLE IF NOT EXISTS route_template_stops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    sequence_number INT,
    typical_time_window_start TIME NULL,
    typical_time_window_end TIME NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES route_templates(id) ON DELETE CASCADE,
    INDEX idx_template (template_id),
    INDEX idx_sequence (template_id, sequence_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. COST TRACKING
-- ============================================

CREATE TABLE IF NOT EXISTS route_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    cost_type ENUM('fuel', 'driver', 'vehicle', 'toll', 'parking', 'maintenance', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    description TEXT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_route (route_id),
    INDEX idx_type (cost_type),
    INDEX idx_date (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. TRAFFIC & EXTERNAL DATA CACHE
-- ============================================

CREATE TABLE IF NOT EXISTS route_traffic_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin_lat DECIMAL(10,8) NOT NULL,
    origin_lon DECIMAL(11,8) NOT NULL,
    destination_lat DECIMAL(10,8) NOT NULL,
    destination_lon DECIMAL(11,8) NOT NULL,
    distance_km DECIMAL(10,2),
    duration_minutes INT,
    traffic_duration_minutes INT,
    traffic_level ENUM('low', 'moderate', 'heavy', 'severe') DEFAULT 'moderate',
    data_source VARCHAR(50), -- 'google', 'mapbox', etc.
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    INDEX idx_coordinates (origin_lat, origin_lon, destination_lat, destination_lon),
    INDEX idx_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. AUDIT LOG
-- ============================================

CREATE TABLE IF NOT EXISTS route_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT,
    user_id INT,
    action VARCHAR(50) NOT NULL, -- 'created', 'updated', 'deleted', 'assigned', etc.
    entity_type VARCHAR(50), -- 'route', 'driver', 'vehicle', etc.
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_route (route_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. NOTIFICATIONS
-- ============================================

CREATE TABLE IF NOT EXISTS route_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT,
    driver_id INT,
    user_id INT,
    notification_type ENUM('assignment', 'update', 'cancellation', 'delay', 'completion', 'alert') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    sent_via JSON, -- ["email", "sms", "push"]
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES route_drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_driver (driver_id, is_read),
    INDEX idx_user (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. SETTINGS & PREFERENCES
-- ============================================

CREATE TABLE IF NOT EXISTS route_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_setting (user_id, setting_key),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. VIEWS FOR COMMON QUERIES
-- ============================================

-- Driver performance view
CREATE OR REPLACE VIEW vw_driver_performance AS
SELECT 
    d.id,
    d.driver_code,
    d.rating,
    d.total_deliveries,
    d.successful_deliveries,
    d.on_time_percentage,
    u.username,
    u.email,
    COUNT(DISTINCT r.id) as total_routes,
    AVG(rp.efficiency_score) as avg_efficiency,
    SUM(rp.total_distance_km) as total_distance,
    SUM(rp.total_cost) as total_cost
FROM route_drivers d
LEFT JOIN users u ON d.user_id = u.id
LEFT JOIN routes r ON r.assigned_driver_id = d.id
LEFT JOIN route_performance rp ON rp.route_id = r.id
WHERE d.status = 'active'
GROUP BY d.id;

-- Route summary view
CREATE OR REPLACE VIEW vw_route_summary AS
SELECT 
    r.id,
    r.name,
    r.route_status,
    r.created_at,
    r.assigned_driver_id,
    d.driver_code,
    r.assigned_vehicle_id,
    v.license_plate,
    rp.total_distance_km,
    rp.total_duration_minutes,
    rp.total_stops,
    rp.completed_stops,
    rp.efficiency_score,
    rp.total_cost
FROM routes r
LEFT JOIN route_drivers d ON r.assigned_driver_id = d.id
LEFT JOIN route_vehicles v ON r.assigned_vehicle_id = v.id
LEFT JOIN route_performance rp ON rp.route_id = r.id;

-- ============================================
-- 12. SAMPLE DATA (Optional - for testing)
-- ============================================

-- Insert sample fuel efficiency data
INSERT INTO route_settings (user_id, setting_key, setting_value, setting_type) VALUES
(1, 'default_fuel_price_per_liter', '1.50', 'number'),
(1, 'default_driver_hourly_rate', '15.00', 'number'),
(1, 'traffic_api_provider', 'google', 'string'),
(1, 'enable_traffic_optimization', 'true', 'boolean'),
(1, 'max_route_duration_hours', '8', 'number'),
(1, 'default_vehicle_speed_kmh', '50', 'number')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Add composite indexes for common queries
ALTER TABLE routes ADD INDEX idx_status_created (route_status, created_at);
ALTER TABLE route_stops ADD INDEX idx_route_sequence (route_id, sequence_number);
ALTER TABLE route_deliveries ADD INDEX idx_status_priority (status, priority);

-- ============================================
-- COMPLETION
-- ============================================

-- Log schema version
CREATE TABLE IF NOT EXISTS schema_version (
    version VARCHAR(20) PRIMARY KEY,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT
);

INSERT INTO schema_version (version, description) VALUES 
('2.0.0', 'Route Optimizer Enhancements - Complete schema with driver management, analytics, and cost tracking')
ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP;
