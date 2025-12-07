<?php
// run_migration.php
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

echo "Starting migration...\n";

try {
    // 1. Check/Add has_variants to products
    $check = $db->query("SHOW COLUMNS FROM products LIKE 'has_variants'");
    if ($check->rowCount() == 0) {
        echo "Adding has_variants column to products...\n";
        $db->exec("ALTER TABLE products ADD COLUMN has_variants BOOLEAN DEFAULT FALSE");
    } else {
        echo "Column has_variants already exists.\n";
    }

    // 2. Create product_variants table
    echo "Creating product_variants table...\n";
    $createTable = "CREATE TABLE IF NOT EXISTS product_variants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        sku VARCHAR(50) NOT NULL,
        size VARCHAR(50),
        color VARCHAR(50),
        quantity INT DEFAULT 0,
        price DECIMAL(10,2),
        min_stock INT DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_sku (sku)
    )";
    $db->exec($createTable);

    // 3. Add variant_id to stock_movements
    $check = $db->query("SHOW COLUMNS FROM stock_movements LIKE 'variant_id'");
    if ($check->rowCount() == 0) {
        echo "Adding variant_id column to stock_movements...\n";
        $db->exec("ALTER TABLE stock_movements ADD COLUMN variant_id INT DEFAULT NULL");
        $db->exec("ALTER TABLE stock_movements ADD FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL");
    } else {
        echo "Column variant_id already exists in stock_movements.\n";
    }

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
