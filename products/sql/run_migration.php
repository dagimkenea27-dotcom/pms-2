<?php
// Run migration
require_once "../../config/database.php";

$database = new Database();
$db = $database->getConnection();

echo "Running reorder migration...\n";

try {
    $sql = file_get_contents('reorder_migration.sql');
    
    // Split into individual statements (simple split by ;)
    // note: this is a basic split, typically migrations need robust parsing
    // but for this specific file it should work as we don't have ; inside strings
    
    // Actually, PREPARE statements in MySQL via PDO can be tricky with multiple statements.
    // Let's run the CREATE TABLE directly first
    
    $create_sql = "
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
    )";
    
    $db->exec($create_sql);
    echo "✅ Table 'reorder_settings' checked/created.\n";
    
    // Helper function to add column if not exists
    function addColumnIfNotExists($db, $table, $column, $definition) {
        $check = $db->query("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($check->rowCount() == 0) {
            $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            echo "✅ Column '$column' added to '$table'.\n";
        } else {
            echo "ℹ️  Column '$column' already exists in '$table'.\n";
        }
    }
    
    addColumnIfNotExists($db, 'products', 'avg_daily_sales', 'DECIMAL(10,2) DEFAULT 0');
    addColumnIfNotExists($db, 'products', 'reorder_enabled', 'BOOLEAN DEFAULT TRUE');
    addColumnIfNotExists($db, 'suppliers', 'default_lead_time', 'INT DEFAULT 7');
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
