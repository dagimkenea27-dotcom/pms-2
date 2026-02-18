<?php
header('Content-Type: application/json');
require_once "../config/auth.php";
require_once "../config/database.php";

// Simple Auth Check
Auth::checkAuthAndPreventCache();

// This is a bridge for SHEIN Visual Search
// In a real scenario, you would use an API key from Apify, Retailed, or similar.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['screenshot'])) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded']);
    exit;
}

$file = $_FILES['screenshot'];
$uploadDir = '../uploads/shein_searches/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileName = time() . '_' . basename($file['name']);
$targetPath = $uploadDir . $fileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    
    // --- API INTEGRATION POINT ---
    // Here you would call the SHEIN Visual Search API.
    // Example using Apify SHEIN Product Image Search:
    /*
    $apiKey = 'YOUR_APIFY_KEY';
    $ch = curl_init('https://api.apify.com/v2/acts/apify~shein-product-image-search/run-sync-get-dataset-items?token=' . $apiKey);
    // ... post the image ...
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    */
    
    // FOR DEMO: Let's mock a successful response after a brief sleep
    usleep(1500000); // 1.5s delay to feel real
    
    // Determine random mock data
    $isJeans = rand(0, 1);
    $mockData = [
        'success' => true,
        'data' => [
            'product_name' => $isJeans ? 'Straight Leg Jeans' : 'Classic Kaki Pants',
            'product_type' => $isJeans ? 'Jeans' : 'Kaki Pants',
            'price' => rand(950, 1850),
            'currency' => 'Birr',
            'available_sizes' => $isJeans ? [31, 32, 33, 34, 36, 40] : [32, 34, 36, 38, 42],
            'color' => $isJeans ? 'Blue' : 'Khaki',
            'image_url' => 'uploads/shein_searches/' . $fileName
        ]
    ];

    echo json_encode($mockData);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save image']);
}
