<?php
// products/export_products.php
require_once "../config/auth_check.php";
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

// Fetch products
$query = "SELECT sku, name, description, category, quantity, price, cost_price, min_stock, supplier, location FROM products ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->execute();

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=products_export_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel compatibility
fputs($output, "\xEF\xBB\xBF");

// Add CSV headers
fputcsv($output, array('SKU', 'Name', 'Description', 'Category', 'Quantity', 'Price', 'Cost Price', 'Min Stock', 'Supplier', 'Location'));

// Add data rows
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
