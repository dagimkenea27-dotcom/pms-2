<?php
// products/search_by_barcode.php
require_once "../config/auth_check.php";
require_once "../config/database.php";

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$response = ['success' => false, 'message' => '', 'data' => null];

// Handle both GET and POST requests
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $barcode = $_GET['barcode'] ?? '';
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $barcode = $input['barcode'] ?? '';
} else {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

// Sanitize and validate barcode
$barcode = trim($barcode);

if (empty($barcode)) {
    $response['message'] = 'Barcode is required';
    echo json_encode($response);
    exit();
}

// Validate barcode format (alphanumeric, reasonable length)
if (!preg_match('/^[a-zA-Z0-9\-\.\/]{1,50}$/', $barcode)) {
    $response['message'] = 'Invalid barcode format';
    echo json_encode($response);
    exit();
}

try {
    // Search for product by SKU or Barcode
    $query = "SELECT * FROM products WHERE sku = :barcode OR barcode = :barcode OR id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':barcode', $barcode);
    // Only bind ID if barcode is numeric to avoid loose type matching
    $id_param = is_numeric($barcode) ? $barcode : -1;
    $stmt->bindParam(':id', $id_param);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        // Check if product has variants
        if ($product['has_variants']) {
            // Search in variants
            $variant_query = "SELECT * FROM product_variants WHERE (sku = :barcode OR id = :id) AND product_id = :product_id LIMIT 1";
            $variant_stmt = $db->prepare($variant_query);
            $variant_stmt->bindParam(':barcode', $barcode);
            $variant_stmt->bindParam(':id', $barcode);
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
                         WHERE (pv.sku = :barcode OR pv.id = :id) LIMIT 1";
        $variant_stmt = $db->prepare($variant_query);
        $variant_stmt->bindParam(':barcode', $barcode);
        $variant_stmt->bindParam(':id', $barcode);
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
            $response['message'] = 'Product not found for barcode: ' . $barcode;
        }
    }
    
} catch (Exception $e) {
    error_log("Barcode search error: " . $e->getMessage());
    $response['message'] = 'Error searching for product: ' . $e->getMessage();
}

echo json_encode($response);
?>