-- Database migration for reorder suggestions
-- products/sql/reorder_migration.sql

-- 1. Create reorder_settings table
CREATE TABLE IF NOT EXISTS reorder_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    reorder_point INT DEFAULT 0,
    reorder_quantity INT DEFAULT 0,
    lead_time_days INT DEFAULT 7,
    safety_stock INT DEFAULT 0,
    auto_generate_po BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_calculated TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product (product_id)
);

-- 2. Add columns to products table if they don't exist
SET @dbname = DATABASE();
SET @tablename = "products";
SET @columnname = "avg_daily_sales";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE products ADD COLUMN avg_daily_sales DECIMAL(10,2) DEFAULT 0"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "reorder_enabled";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE products ADD COLUMN reorder_enabled BOOLEAN DEFAULT TRUE"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Add columns to suppliers table if they don't exist
SET @tablename = "suppliers";
SET @columnname = "default_lead_time";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE suppliers ADD COLUMN default_lead_time INT DEFAULT 7"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
