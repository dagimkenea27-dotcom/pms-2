<?php
// Test database connection
require_once "config/database.php";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test a simple query
    $query = "SELECT COUNT(*) as count FROM products";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Database connection successful!<br>";
    echo "Total products: " . $result['count'];
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
}
?>