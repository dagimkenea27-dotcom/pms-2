-- Add missing cost_price and location columns to product_variants table
-- This ensures variants can have their own cost prices for accurate profit margin calculations

USE inventory_system;

-- Add cost_price column if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = "product_variants";
SET @columnname = "cost_price";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE product_variants ADD COLUMN cost_price DECIMAL(10,2) DEFAULT NULL AFTER price;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add location column if it doesn't exist
SET @columnname = "location";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE product_variants ADD COLUMN location VARCHAR(100) DEFAULT NULL AFTER cost_price;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SELECT 'Migration completed: cost_price and location columns added to product_variants table' AS status;
