<?php
// test_db.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/database.php";

echo "<h1>Database Test</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color:green'>Database connection successful!</p>";
    
    // Check users table
    $query = "SELECT count(*) as count FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p style='color:green'>Users table exists. User count: " . $row['count'] . "</p>";
    
    // Check admin user
    $query = "SELECT * FROM users WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>Admin user found.</p>";
    } else {
        echo "<p style='color:red'>Admin user NOT found.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>General Error: " . $e->getMessage() . "</p>";
}
?>
