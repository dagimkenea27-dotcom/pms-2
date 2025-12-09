<?php
/**
 * Geocoding API endpoint for location search autocomplete
 */

header('Content-Type: application/json');

// Allow CORS for local development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$countryCode = isset($_GET['country']) ? trim($_GET['country']) : 'et';

// Validate input
if (empty($query) || strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

// Call Nominatim API
$baseUrl = 'https://nominatim.openstreetmap.org/search';
$params = http_build_query([
    'q' => $query,
    'format' => 'json',
    'limit' => 5,
    'countrycodes' => $countryCode,
    'addressdetails' => 1
]);

$url = $baseUrl . '?' . $params;

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'StockManagementRouteOptimizer/1.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check for errors
if ($httpCode !== 200 || !$response) {
    echo json_encode([]);
    exit;
}

// Parse response
$results = json_decode($response, true);

// Format results for autocomplete
$formattedResults = [];
foreach ($results as $result) {
    $formattedResults[] = [
        'display_name' => $result['display_name'],
        'lat' => floatval($result['lat']),
        'lon' => floatval($result['lon'])
    ];
}

echo json_encode($formattedResults);
?>