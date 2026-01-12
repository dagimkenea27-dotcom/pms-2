-- sql/update_schema_variants.sql

-- Add has_variants column to products table if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = "products";
SET @columnname = "has_variants";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE products ADD COLUMN has_variants BOOLEAN DEFAULT FALSE;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Create product_variants table
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL, -- Variant SKU
    size VARCHAR(50),
    color VARCHAR(50),
    quantity INT DEFAULT 0,
    price DECIMAL(10,2), -- Optional override
    cost_price DECIMAL(10,2), -- Variant cost price
    location VARCHAR(100), -- Variant location
    min_stock INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_sku (sku)
);

-- Add variant_id to stock_movements
SET @tablename = "stock_movements";
SET @columnname = "variant_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE stock_movements ADD COLUMN variant_id INT DEFAULT NULL, ADD FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
