<?php
// api/get_variants.php
require_once "../config/auth.php";
require_once "../config/database.php";

header('Content-Type: application/json');

if (!isset($_GET['product_id'])) {
    echo json_encode(['error' => 'Product ID required']);
    exit;
}

$productId = intval($_GET['product_id']);
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT id, size, color, quantity, price, sku FROM product_variants WHERE product_id = ? ORDER BY size, color");
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $variants]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
