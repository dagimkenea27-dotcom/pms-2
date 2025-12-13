<?php
// products/search_by_barcode.php
require_once "../config/auth_check.php";
require_once "../config/database.php";

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$response = ['success' => false, 'message' => '', 'data' => null];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

$barcode = $_GET['barcode'] ?? '';

if (empty($barcode)) {
    $response['message'] = 'Barcode is required';
    echo json_encode($response);
    exit();
}

try {
    // Search for product by SKU or Barcode
    $query = "SELECT * FROM products WHERE sku = :barcode OR barcode = :barcode LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':barcode', $barcode);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        // Check if product has variants
        if ($product['has_variants']) {
            // Search in variants
            $variant_query = "SELECT * FROM product_variants WHERE sku = :barcode AND product_id = :product_id LIMIT 1";
            $variant_stmt = $db->prepare($variant_query);
            $variant_stmt->bindParam(':barcode', $barcode);
            $variant_stmt->bindParam(':product_id', $product['id']);
            $variant_stmt->execute();
            
            $variant = $variant_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($variant) {
                $response['success'] = true;
                $response['message'] = 'Product variant found';
                $response['data'] = [
                    'type' => 'variant',
                    'product' => $product,
                    'variant' => $variant
                ];
            } else {
                $response['success'] = true;
                $response['message'] = 'Product found (parent)';
                $response['data'] = [
                    'type' => 'product',
                    'product' => $product
                ];
            }
        } else {
            $response['success'] = true;
            $response['message'] = 'Product found';
            $response['data'] = [
                'type' => 'product',
                'product' => $product
            ];
        }
    } else {
        // Try searching in variants directly
        $variant_query = "SELECT pv.*, p.* FROM product_variants pv 
                         JOIN products p ON pv.product_id = p.id 
                         WHERE pv.sku = :barcode LIMIT 1";
        $variant_stmt = $db->prepare($variant_query);
        $variant_stmt->bindParam(':barcode', $barcode);
        $variant_stmt->execute();
        
        $result = $variant_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Product variant found';
            $response['data'] = [
                'type' => 'variant',
                'product' => $result,
                'variant' => $result
            ];
        } else {
            $response['success'] = false;
            $response['message'] = 'Product not found';
        }
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
