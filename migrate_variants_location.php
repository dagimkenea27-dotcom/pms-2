<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();
try {
    $db->exec("ALTER TABLE product_variants ADD COLUMN location VARCHAR(100) DEFAULT NULL AFTER cost_price");
    echo "Successfully added location to product_variants\n";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}
?>
