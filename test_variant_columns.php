<?php
// Test script to verify product_variants table has cost_price and location columns
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

echo "<h2>Product Variants Table Structure Verification</h2>";

try {
    // Get table structure
    $stmt = $db->query("DESCRIBE product_variants");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasCostPrice = false;
    $hasLocation = false;
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'cost_price') $hasCostPrice = true;
        if ($col['Field'] === 'location') $hasLocation = true;
    }
    
    echo "</table>";
    
    echo "<h3>Verification Results:</h3>";
    echo "<ul>";
    echo "<li><strong>cost_price column:</strong> " . ($hasCostPrice ? "✅ EXISTS" : "❌ MISSING") . "</li>";
    echo "<li><strong>location column:</strong> " . ($hasLocation ? "✅ EXISTS" : "❌ MISSING") . "</li>";
    echo "</ul>";
    
    if ($hasCostPrice && $hasLocation) {
        echo "<p style='color: green; font-weight: bold;'>✅ All required columns are present! The database is ready.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Missing columns detected. Please run the migration script.</p>";
        echo "<p>Run: <code>Get-Content \"sql/add_variant_cost_location.sql\" | c:\\xampp\\mysql\\bin\\mysql.exe -u root inventory_system</code></p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
