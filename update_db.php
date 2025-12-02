<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $sql = "ALTER TABLE stock_movements ADD COLUMN supplier_id INT NULL AFTER product_id";
    $db->exec($sql);
    echo "Successfully added supplier_id column to stock_movements table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column supplier_id already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
