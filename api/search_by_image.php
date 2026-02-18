<?php
// api/search_by_image.php
header('Content-Type: application/json');
require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../lib/ImageSearch.php";

// Auth check
Auth::checkAuthAndPreventCache();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded']);
    exit;
}

$file = $_FILES['image'];
$tempDir = '../uploads/temp_searches/';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

$fileName = time() . '_' . basename($file['name']);
$targetPath = $tempDir . $fileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    
    // Calculate hash for the search image
    $searchHash = ImageSearch::getDHash($targetPath);
    
    if (!$searchHash) {
        @unlink($targetPath);
        echo json_encode(['success' => false, 'message' => 'Failed to process search image']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Fetch products with images
    $query = "SELECT id, name, sku, image, price, quantity FROM products WHERE image IS NOT NULL AND image != ''";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $matches = [];
    $basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR;

    foreach ($products as $product) {
        $productImagePath = $basePath . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $product['image']);
        
        if (file_exists($productImagePath)) {
            $productHash = ImageSearch::getDHash($productImagePath);
            if ($productHash) {
                $distance = ImageSearch::hammingDistance($searchHash, $productHash);
                
                // Typical threshold for dHash is around 10-12 for "similar"
                // For exact or near-exact, it's < 5
                if ($distance <= 15) { // Relaxed threshold for better hit rate
                    $product['distance'] = $distance;
                    $product['similarity'] = round((1 - ($distance / 64)) * 100, 1);
                    $matches[] = $product;
                }
            }
        }
    }

    // Sort by distance (lowest first)
    usort($matches, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    // Clean up temp file
    @unlink($targetPath);

    echo json_encode([
        'success' => true,
        'matches' => array_slice($matches, 0, 10) // Return top 10 matches
    ]);

} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded image']);
}
