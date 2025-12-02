<?php
// run_migration.php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = file_get_contents('sql/update_schema.sql');

    // Split SQL by command (simple split by ;) - Note: This is a basic parser and might fail with complex stored procs if not handled carefully.
    // However, since we used DELIMITER in the SQL file, standard PDO exec might choke on it if we don't parse it right.
    // Actually, PDO can execute multiple queries if emulation is on, but stored procedures are tricky.
    // Let's try a simpler approach for the columns since we are in PHP.
    
    echo "Creating tables...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS brands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $db->exec("
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
        )
    ");

    echo "Checking columns...\n";
    
    // Check and add category_id
    $stmt = $db->query("SHOW COLUMNS FROM products LIKE 'category_id'");
    if ($stmt->rowCount() == 0) {
        echo "Adding category_id to products...\n";
        $db->exec("ALTER TABLE products ADD COLUMN category_id INT");
        $db->exec("ALTER TABLE products ADD CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
    }

    // Check and add brand_id
    $stmt = $db->query("SHOW COLUMNS FROM products LIKE 'brand_id'");
    if ($stmt->rowCount() == 0) {
        echo "Adding brand_id to products...\n";
        $db->exec("ALTER TABLE products ADD COLUMN brand_id INT");
        $db->exec("ALTER TABLE products ADD CONSTRAINT fk_product_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL");
    }

    echo "Migration completed successfully!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
