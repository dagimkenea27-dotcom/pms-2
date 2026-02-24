<?php
require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

$sqlFile = "products/delivery_location.sql";
if (!file_exists($sqlFile)) {
    die("SQL file missing: $sqlFile");
}

$sql = file_get_contents($sqlFile);

try {
    // Split SQL by semicolon and execute each part
    // Simple approach for this specific file
    $db->exec($sql);
    echo "SUCCESS";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
