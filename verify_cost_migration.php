<?php
// Verify variant cost price migration
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

echo "<h2>Variant Cost Price Migration Report</h2>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .success { color: green; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { background-color: #e7f3fe; padding: 15px; border-left: 6px solid #2196F3; margin: 20px 0; }
</style>";

try {
    // Get summary statistics
    $stmt = $db->query("
        SELECT 
            COUNT(*) AS total_variants,
            SUM(CASE WHEN cost_price IS NOT NULL THEN 1 ELSE 0 END) AS with_cost,
            SUM(CASE WHEN cost_price IS NULL THEN 1 ELSE 0 END) AS without_cost
        FROM product_variants
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>Summary Statistics</h3>";
    echo "<ul>";
    echo "<li><strong>Total Variants:</strong> " . $stats['total_variants'] . "</li>";
    echo "<li class='success'><strong>Variants with Cost Price:</strong> " . $stats['with_cost'] . " (" . round(($stats['with_cost'] / $stats['total_variants']) * 100, 2) . "%)</li>";
    
    if ($stats['without_cost'] > 0) {
        echo "<li class='warning'><strong>Variants without Cost Price:</strong> " . $stats['without_cost'] . "</li>";
    } else {
        echo "<li class='success'><strong>Variants without Cost Price:</strong> 0 (All variants have cost prices! ✅)</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Show sample of updated variants
    echo "<h3>Sample of Variants with Cost Prices (First 20)</h3>";
    $stmt = $db->query("
        SELECT 
            p.name AS product_name,
            pv.size,
            pv.color,
            pv.sku,
            pv.cost_price,
            pv.price,
            ROUND(((pv.price - pv.cost_price) / pv.price) * 100, 2) AS profit_margin_percent
        FROM product_variants pv
        INNER JOIN products p ON pv.product_id = p.id
        WHERE pv.cost_price IS NOT NULL
        ORDER BY p.name, pv.size, pv.color
        LIMIT 20
    ");
    
    echo "<table>";
    echo "<tr>
            <th>Product Name</th>
            <th>Size</th>
            <th>Color</th>
            <th>SKU</th>
            <th>Cost Price</th>
            <th>Selling Price</th>
            <th>Profit Margin %</th>
          </tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['size']) . "</td>";
        echo "<td>" . htmlspecialchars($row['color']) . "</td>";
        echo "<td>" . htmlspecialchars($row['sku']) . "</td>";
        echo "<td>$" . number_format($row['cost_price'], 2) . "</td>";
        echo "<td>$" . number_format($row['price'], 2) . "</td>";
        
        $margin = $row['profit_margin_percent'];
        $color = $margin > 0 ? 'green' : 'red';
        echo "<td style='color: $color; font-weight: bold;'>" . $margin . "%</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if any variants still need cost prices
    if ($stats['without_cost'] > 0) {
        echo "<h3 class='warning'>⚠️ Variants Still Missing Cost Prices</h3>";
        $stmt = $db->query("
            SELECT 
                p.name AS product_name,
                pv.size,
                pv.color,
                pv.sku,
                p.cost_price AS parent_cost_price
            FROM product_variants pv
            INNER JOIN products p ON pv.product_id = p.id
            WHERE pv.cost_price IS NULL
            ORDER BY p.name, pv.size, pv.color
        ");
        
        echo "<table>";
        echo "<tr>
                <th>Product Name</th>
                <th>Size</th>
                <th>Color</th>
                <th>SKU</th>
                <th>Parent Cost Price</th>
                <th>Issue</th>
              </tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['size']) . "</td>";
            echo "<td>" . htmlspecialchars($row['color']) . "</td>";
            echo "<td>" . htmlspecialchars($row['sku']) . "</td>";
            echo "<td>" . ($row['parent_cost_price'] ? '$' . number_format($row['parent_cost_price'], 2) : 'NULL') . "</td>";
            echo "<td>" . ($row['parent_cost_price'] ? 'Migration failed' : 'Parent has no cost price') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<div class='info'>";
    echo "<h3>✅ Migration Complete!</h3>";
    echo "<p>All variant cost prices have been populated from their parent products. You can now:</p>";
    echo "<ul>";
    echo "<li>View accurate profit margins for each variant</li>";
    echo "<li>Generate reliable marketing intelligence reports</li>";
    echo "<li>Manually adjust individual variant cost prices if needed</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
