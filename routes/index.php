<?php
// routes/index.php
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

require_once "../config/database.php";
require_once "../models/Route.php";
require_once "../config/auth.php";

// Check authentication
Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

// --- CONSTANTS AND CONFIGURATION ---
define('GEOCODE_RATE_LIMIT', 0.5); // seconds between geocoding requests
define('MAX_DRIVERS', 20);
define('MAX_ADDRESSES', 500);

// --- CLASSES ---

class RateLimiter {
    private static $lastCall = 0;
    
    public static function wait($minInterval = GEOCODE_RATE_LIMIT) {
        $now = microtime(true);
        $elapsed = $now - self::$lastCall;
        
        if ($elapsed < $minInterval) {
            usleep(($minInterval - $elapsed) * 1000000);
        }
        
        self::$lastCall = microtime(true);
    }
}

class LocationSearch {
    private $cache = [];
    private $cacheFile = '../cache/locations.json';
    
    public function __construct() {
        $this->loadCache();
    }
    
    private function loadCache() {
        if (file_exists($this->cacheFile)) {
            $data = file_get_contents($this->cacheFile);
            $this->cache = json_decode($data, true) ?? [];
        }
    }
    
    private function saveCache() {
        // Keep only last 1000 cached items to prevent file from growing too large
        if (count($this->cache) > 1000) {
            $this->cache = array_slice($this->cache, -1000, 1000, true);
        }
        file_put_contents($this->cacheFile, json_encode($this->cache));
    }
    
    public function geocode($query, $countrycode = 'et', $useCache = true) {
        $cacheKey = md5(strtolower(trim($query)) . '|' . $countrycode);
        
        // Check cache first
        if ($useCache && isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            // Cache valid for 30 days
            if (time() - $cached['timestamp'] < 2592000) {
                return $cached['data'];
            }
        }
        
        RateLimiter::wait();
        
        $base = 'https://nominatim.openstreetmap.org/search';
        $params = http_build_query([
            'q' => $query,
            'format' => 'json',
            'limit' => 5, // Get more results for better accuracy
            'countrycodes' => $countrycode,
            'addressdetails' => 1,
            'accept-language' => 'en'
        ]);
        $url = $base . '?' . $params;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'DeliveryRoutePlanner/2.0 (contact@example.com)',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);
        
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($resp)) {
            error_log("Geocoding failed for query: $query, HTTP Code: $httpCode");
            return null;
        }
        
        $results = json_decode($resp, true);
        if (!$results || empty($results)) {
            return null;
        }
        
        // Return first result and cache it
        $result = [
            'lat' => floatval($results[0]['lat']),
            'lon' => floatval($results[0]['lon']),
            'display_name' => $results[0]['display_name'],
            'address' => $results[0]['address'] ?? []
        ];
        
        // Cache the result
        $this->cache[$cacheKey] = [
            'data' => $result,
            'timestamp' => time()
        ];
        $this->saveCache();
        
        return $result;
    }
    
    public function search($query, $countrycode = 'et', $limit = 10) {
        if (strlen(trim($query)) < 2) {
            return [];
        }
        
        RateLimiter::wait(0.2); // Faster search rate limit
        
        $base = 'https://nominatim.openstreetmap.org/search';
        $params = http_build_query([
            'q' => $query,
            'format' => 'json',
            'limit' => $limit,
            'countrycodes' => $countrycode,
            'addressdetails' => 1,
            'accept-language' => 'en',
            'dedupe' => 1
        ]);
        $url = $base . '?' . $params;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'DeliveryRoutePlanner/2.0 Search',
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $resp = curl_exec($ch);
        curl_close($ch);
        
        $results = json_decode($resp, true);
        if (!$results) {
            return [];
        }
        
        // Format results with more details
        $formatted = [];
        foreach ($results as $item) {
            $formatted[] = [
                'lat' => floatval($item['lat']),
                'lon' => floatval($item['lon']),
                'display_name' => $item['display_name'],
                'type' => $item['type'] ?? 'unknown',
                'importance' => $item['importance'] ?? 0,
                'address' => $item['address'] ?? []
            ];
        }
        
        // Sort by importance
        usort($formatted, function($a, $b) {
            return $b['importance'] <=> $a['importance'];
        });
        
        return $formatted;
    }
    
    public function reverseGeocode($lat, $lon) {
        $cacheKey = md5("reverse|{$lat}|{$lon}");
        
        if (isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if (time() - $cached['timestamp'] < 2592000) {
                return $cached['data'];
            }
        }
        
        RateLimiter::wait();
        
        $base = 'https://nominatim.openstreetmap.org/reverse';
        $params = http_build_query([
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'json',
            'zoom' => 18,
            'addressdetails' => 1
        ]);
        $url = $base . '?' . $params;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'DeliveryRoutePlanner/2.0 Reverse',
            CURLOPT_TIMEOUT => 8
        ]);
        
        $resp = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($resp, true);
        if (!$result) {
            return null;
        }
        
        $formatted = [
            'lat' => $lat,
            'lon' => $lon,
            'display_name' => $result['display_name'] ?? "Location at $lat, $lon",
            'address' => $result['address'] ?? []
        ];
        
        $this->cache[$cacheKey] = [
            'data' => $formatted,
            'timestamp' => time()
        ];
        $this->saveCache();
        
        return $formatted;
    }
}

// --- HELPER FUNCTIONS ---

/**
 * Calculate Haversine distance between two points
 */
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Earth's radius in kilometers
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

/**
 * Optimize route using nearest neighbor algorithm
 */
function optimizeRoute($warehouse, $points) {
    if (empty($points)) {
        return ['route' => [$warehouse, $warehouse], 'total_distance' => 0];
    }
    
    $route = [$warehouse];
    $current = $warehouse;
    $remaining = $points;
    $totalDistance = 0;
    
    // Nearest neighbor algorithm
    while (!empty($remaining)) {
        $nearest = null;
        $nearestDistance = INF;
        $nearestIndex = null;
        
        foreach ($remaining as $index => $point) {
            $distance = haversineDistance(
                $current['lat'], $current['lon'],
                $point['lat'], $point['lon']
            );
            
            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearest = $point;
                $nearestIndex = $index;
            }
        }
        
        if ($nearest) {
            $nearest['distance'] = $nearestDistance;
            $route[] = $nearest;
            $totalDistance += $nearestDistance;
            $current = $nearest;
            unset($remaining[$nearestIndex]);
        }
    }
    
    // Note: We don't add return-to-warehouse point anymore as it creates unwanted markers
    // The distance calculation still includes the return trip for reporting purposes
    $returnDistance = haversineDistance(
        $current['lat'], $current['lon'],
        $warehouse['lat'], $warehouse['lon']
    );
    $totalDistance += $returnDistance;
    
    // Return the route without the return-to-warehouse point
    // This prevents unwanted markers from appearing on the map
    
    return [
        'route' => $route,
        'total_km' => $totalDistance
    ];
}

// --- FUNCTIONS ---

function haversine($a, $b) {
    $R = 6371;
    $dLat = deg2rad($b['lat'] - $a['lat']);
    $dLon = deg2rad($b['lon'] - $a['lon']);
    $x = sin($dLat/2)**2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLon/2)**2;
    return $R * 2 * atan2(sqrt($x), sqrt(1 - $x));
}

function validateInput($data, $type = 'string') {
    switch($type) {
        case 'int':
            $value = intval($data);
            return max(1, min($value, MAX_DRIVERS));
        case 'country':
            return preg_match('/^[a-z]{2}$/', $data) ? strtolower($data) : 'et';
        case 'address':
            $clean = strip_tags(trim($data));
            return substr($clean, 0, 500); // Limit length
        case 'coordinate':
            $value = floatval($data);
            return ($value >= -180 && $value <= 180) ? $value : null;
        default:
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// --- PROCESSING ---

$driverRoutes = [];
$errors = [];
$warnings = [];
$successMessage = '';
$loadedRouteMessage = '';
$routeModel = new Route();
$locationSearch = new LocationSearch();

// Load saved routes for current user
$savedRoutes = $routeModel->getUserRoutes($currentUser['id']);

// Handle route saving
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_route'])) {
        $routeName = validateInput($_POST['route_name'] ?? '');
        if (empty($routeName)) {
            $errors[] = "Route name is required to save the route.";
        } else {
            try {
                $warehouseLocation = validateInput($_POST['start'] ?? '', 'address');
                $driverCount = validateInput($_POST['drivers'] ?? 1, 'int');
                $countryCode = validateInput($_POST['countrycode'] ?? 'et', 'country');
                
                // Get coordinates if not already provided
                $warehouseCoords = null;
                if (isset($_POST['warehouse_lat']) && isset($_POST['warehouse_lon'])) {
                    $lat = validateInput($_POST['warehouse_lat'], 'coordinate');
                    $lon = validateInput($_POST['warehouse_lon'], 'coordinate');
                    if ($lat !== null && $lon !== null) {
                        $warehouseCoords = ['lat' => $lat, 'lon' => $lon];
                    }
                }
                
                if (!$warehouseCoords) {
                    $warehouseCoords = $locationSearch->geocode($warehouseLocation, $countryCode);
                }
                
                // Prepare stops data
                $stops = [];
                if (isset($_POST['driver_routes']) && is_array($_POST['driver_routes'])) {
                    foreach ($_POST['driver_routes'] as $driverId => $routeData) {
                        $stops[$driverId] = json_decode($routeData, true) ?? [];
                    }
                }
                
                // Save route to database
                $routeId = $routeModel->saveRoute(
                    $routeName,
                    $warehouseLocation,
                    $warehouseCoords,
                    $driverCount,
                    $countryCode,
                    $currentUser['id'],
                    $stops
                );
                
                if ($routeId) {
                    $successMessage = "Route saved successfully!";
                    $savedRoutes = $routeModel->getUserRoutes($currentUser['id']); // Refresh list
                } else {
                    $errors[] = "Failed to save route.";
                }
            } catch (Exception $e) {
                error_log("Route save error: " . $e->getMessage());
                $errors[] = "Error saving route. Please try again.";
            }
        }
    } 
    elseif (isset($_POST['optimize'])) {
        $start = validateInput($_POST['start'] ?? '', 'address');
        $raw = validateInput($_POST['addresses'] ?? '', 'address');
        $country = validateInput($_POST['countrycode'] ?? 'et', 'country');
        $numDrivers = validateInput($_POST['drivers'] ?? 1, 'int');
        $algorithm = validateInput($_POST['algorithm'] ?? 'nearest_neighbor');
        $useCoordinates = false;
        
        // Check if start is coordinates
        if (preg_match('/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/', $start, $matches)) {
            $startGeo = [
                'lat' => floatval($matches[1]),
                'lon' => floatval($matches[2]),
                'display_name' => "Location at {$matches[1]}, {$matches[2]}"
            ];
            $useCoordinates = true;
        } else {
            $startGeo = $locationSearch->geocode($start, $country);
        }
        
        if (!$startGeo) {
            $errors[] = "Unable to geocode Warehouse Location. Please check the address.";
        } else {
            $lines = array_filter(array_map('trim', explode("\n", $raw)));
            
            // Validate number of addresses
            if (count($lines) > MAX_ADDRESSES) {
                $errors[] = "Maximum " . MAX_ADDRESSES . " addresses allowed. You entered " . count($lines) . ".";
            } else {
                $points = [];
                $failedAddresses = [];
                
                foreach ($lines as $index => $line) {
                    if (empty($line)) continue;
                    
                    // Check if line contains coordinates
                    if (preg_match('/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/', $line, $coords)) {
                        $points[] = [
                            'address' => "Coordinates: {$coords[1]}, {$coords[2]}",
                            'lat' => floatval($coords[1]),
                            'lon' => floatval($coords[2]),
                            'display_name' => "Coordinates: {$coords[1]}, {$coords[2]}"
                        ];
                    } else {
                        try {
                            $g = $locationSearch->geocode($line, $country);
                            if (!$g) {
                                $failedAddresses[] = $line;
                            } else {
                                $points[] = [
                                    'address' => $line,
                                    'lat' => $g['lat'],
                                    'lon' => $g['lon'],
                                    'display_name' => $g['display_name'] ?? $line
                                ];
                            }
                        } catch (Exception $e) {
                            $failedAddresses[] = $line;
                            error_log("Geocoding error for '$line': " . $e->getMessage());
                        }
                    }
                }
                
                if (!empty($failedAddresses)) {
                    $warnings[] = "Could not geocode " . count($failedAddresses) . " address(es): " . 
                                 implode(", ", array_slice($failedAddresses, 0, 5)) . 
                                 (count($failedAddresses) > 5 ? "..." : "");
                }
                
                if (count($points) > 0) {
                    // Simple clustering based on number of drivers
                    $driverRoutes = [];
                    $pointsPerDriver = ceil(count($points) / $numDrivers);
                    
                    for ($i = 0; $i < $numDrivers; $i++) {
                        $driverPoints = array_slice($points, $i * $pointsPerDriver, $pointsPerDriver);
                        
                        if (!empty($driverPoints)) {
                            // Create warehouse point for this driver
                            $warehouse = [
                                'address' => $start . " (WAREHOUSE)",
                                'lat' => $startGeo['lat'],
                                'lon' => $startGeo['lon'],
                                'display_name' => $startGeo['display_name'] . " (WAREHOUSE)",
                                'is_warehouse' => true
                            ];
                            
                            // Optimize route (simplified nearest neighbor)
                            $result = optimizeRoute($warehouse, $driverPoints);
                            $driverRoutes[$i] = $result;
                        }
                    }
                } else {
                    $errors[] = "No valid addresses could be geocoded.";
                }
            }
        }
    }
}

// Load a saved route
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['load_route'])) {
    $routeId = intval($_GET['load_route']);
    if ($routeId > 0) {
        $savedRoute = $routeModel->getRouteWithStops($routeId);
        
        if ($savedRoute && $savedRoute['created_by'] == $currentUser['id']) {
            // Populate form fields with saved route data
            $_POST['start'] = $savedRoute['warehouse_location'];
            $_POST['drivers'] = $savedRoute['driver_count'];
            $_POST['countrycode'] = $savedRoute['country_code'];
            $_POST['warehouse_lat'] = $savedRoute['warehouse_lat'] ?? '';
            $_POST['warehouse_lon'] = $savedRoute['warehouse_lon'] ?? '';
            
            // Build addresses from stops
            $addresses = [];
            foreach ($savedRoute['stops'] as $driver => $stops) {
                foreach ($stops as $stop) {
                    if (!$stop['is_warehouse']) {
                        $addresses[] = $stop['address'];
                    }
                }
            }
            $_POST['addresses'] = implode("\n", array_unique($addresses));
            
            $loadedRouteMessage = "Loaded route: " . htmlspecialchars($savedRoute['name']);
        } else {
            $errors[] = "Route not found or you don't have permission to access it.";
        }
    }
}

// Delete a saved route
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_route'])) {
    $routeId = intval($_GET['delete_route']);
    if ($routeId > 0) {
        $savedRoute = $routeModel->getRouteWithStops($routeId);
        
        if ($savedRoute && $savedRoute['created_by'] == $currentUser['id']) {
            if ($routeModel->deleteRoute($routeId)) {
                $successMessage = "Route deleted successfully!";
                $savedRoutes = $routeModel->getUserRoutes($currentUser['id']);
            } else {
                $errors[] = "Failed to delete route.";
            }
        } else {
            $errors[] = "Route not found or you don't have permission to delete it.";
        }
    }
}

// Export to CSV
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export']) && isset($_GET['format'])) {
    if ($_GET['export'] == 'csv' && !empty($driverRoutes)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="routes_' . date('Y-m-d_H-i') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility
        
        // Header row
        fputcsv($output, [
            'Driver', 
            'Stop Number', 
            'Type', 
            'Address', 
            'Display Name', 
            'Latitude', 
            'Longitude', 
            'Distance from Previous (km)'
        ]);
        
        // Data rows
        foreach ($driverRoutes as $driverIdx => $data) {
            foreach ($data['route'] as $k => $stop) {
                fputcsv($output, [
                    $driverIdx + 1,
                    $k + 1,
                    isset($stop['is_warehouse']) && $stop['is_warehouse'] ? 'Warehouse' : 'Stop',
                    $stop['address'] ?? '',
                    $stop['display_name'] ?? $stop['address'] ?? '',
                    $stop['lat'],
                    $stop['lon'],
                    isset($stop['distance']) ? round($stop['distance'], 2) : 0
                ]);
            }
        }
        
        fclose($output);
        exit;
    }
}

// AJAX endpoint for search (called from JavaScript)
if (isset($_GET['ajax']) && $_GET['ajax'] == 'search') {
    $query = $_GET['q'] ?? '';
    $country = $_GET['country'] ?? 'et';
    $type = $_GET['type'] ?? 'forward';
    
    header('Content-Type: application/json');
    
    if ($type == 'reverse' && isset($_GET['lat']) && isset($_GET['lon'])) {
        $lat = floatval($_GET['lat']);
        $lon = floatval($_GET['lon']);
        $result = $locationSearch->reverseGeocode($lat, $lon);
        echo json_encode($result ? [$result] : []);
    } else {
        $results = $locationSearch->search($query, $country, 10);
        echo json_encode($results);
    }
    exit;
}

// --- VIEW ---
require_once "../includes/header.php";
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    .map-container { display: flex; flex-direction: column; gap: 20px; margin-bottom: 20px; }
    .input-panel { width: 100%; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
    .map-panel { width: 100%; }
    
    #map { height: 700px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); border: 1px solid #ddd; }
    
    /* Search functionality */
    .search-container { position: relative; margin-bottom: 15px; }
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: none;
    }
    .search-result-item {
        padding: 12px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
    }
    .search-result-item:hover {
        background: #f8f9fa;
    }
    .search-result-item:last-child {
        border-bottom: none;
    }
    .search-result-name {
        font-weight: 500;
        color: #333;
    }
    .search-result-details {
        font-size: 12px;
        color: #666;
        margin-top: 3px;
    }
    .search-result-type {
        display: inline-block;
        padding: 2px 6px;
        background: #e9ecef;
        border-radius: 3px;
        font-size: 11px;
        margin-left: 5px;
    }
    
    /* Improved card layout */
    .routes-container { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); 
        gap: 20px; 
        margin-top: 30px; 
    }
    .driver-card { 
        padding: 20px; 
        border-radius: 8px; 
        border-left: 5px solid #ccc; 
        background: white; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .driver-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .driver-header { 
        font-weight: bold; 
        font-size: 1.2em; 
        margin-bottom: 15px; 
        display: flex; 
        justify-content: space-between;
        align-items: center;
    }
    
    .badge-wh { 
        background: #2c3e50; 
        color: white; 
        padding: 3px 8px; 
        border-radius: 4px; 
        font-size: 0.85em;
    }
    .badge-stop { 
        background: #6c757d; 
        color: white; 
        padding: 3px 8px; 
        border-radius: 4px;
        font-size: 0.85em;
    }
    
    /* Route actions */
    .route-actions { display: flex; gap: 8px; }
    .action-buttons { display: flex; gap: 10px; margin-top: 15px; }
    .action-buttons .btn { flex: 1; }
    
    /* Form enhancements */
    .form-label { font-weight: 500; margin-bottom: 5px; }
    .form-control:focus { border-color: #4a90e2; box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25); }
    
    /* Map controls */
    .map-controls {
        position: relative;
        top: 30px;
        right: 30px;
        z-index: 1000;
        background: white;
        padding: 10px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    /* Loading indicator */
    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-left: 10px;
        vertical-align: middle;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .map-container { flex-direction: column; }
        .input-panel, .map-panel { min-width: 100%; }
        .routes-container { grid-template-columns: 1fr; }
    }
</style>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-map-marked-alt"></i> Multi-Driver Route Optimizer</h1>
    <div>
        <a href="manage.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-bookmark"></i> Manage Saved Routes
        </a>
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#helpModal">
            <i class="fas fa-question-circle"></i> Help
        </button>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-question-circle"></i> Route Optimizer Help</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>How to use:</h6>
                <ol>
                    <li><strong>Search Warehouse</strong>: Type and search for your warehouse location or click on the map</li>
                    <li><strong>Add Addresses</strong>: Enter one delivery address per line or paste a list</li>
                    <li><strong>Set Drivers</strong>: Enter number of available drivers</li>
                    <li><strong>Optimize</strong>: Click "Optimize Routes" to generate optimal delivery paths</li>
                    <li><strong>Save/Export</strong>: Save your route or export to CSV</li>
                </ol>
                <h6>Tips:</h6>
                <ul>
                    <li>You can enter coordinates directly: <code>latitude, longitude</code></li>
                    <li>Click on the map to set exact warehouse location</li>
                    <li>Use the search for accurate address finding</li>
                    <li>Maximum <?= MAX_ADDRESSES ?> addresses per optimization</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="map-container">
    <div class="input-panel">
        <form method="POST" id="routeForm">
            <div class="mb-4">
                <label class="form-label">Warehouse Location:</label>
                <div class="search-container">
                    <div class="input-group">
                        <input type="text" class="form-control" 
                               name="start" 
                               id="warehouse-location" 
                               value="<?= htmlspecialchars($_POST['start'] ?? '') ?>" 
                               placeholder="Search for location or enter coordinates..."
                               autocomplete="off">
                        <button class="btn btn-outline-primary" type="button" id="search-location-btn">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="btn btn-outline-secondary" type="button" id="use-current-location" title="Use Current Location">
                            <i class="fas fa-location-arrow"></i>
                        </button>
                    </div>
                    <div id="search-results" class="search-results"></div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Type to search, click map, or enter coordinates: <code>latitude, longitude</code>
                    </small>
                </div>
                <input type="hidden" name="warehouse_lat" id="warehouse-lat" value="<?= htmlspecialchars($_POST['warehouse_lat'] ?? '') ?>">
                <input type="hidden" name="warehouse_lon" id="warehouse-lon" value="<?= htmlspecialchars($_POST['warehouse_lon'] ?? '') ?>">
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Number of Drivers:</label>
                    <input type="number" class="form-control" name="drivers" min="1" max="<?= MAX_DRIVERS ?>" 
                           value="<?= htmlspecialchars($_POST['drivers'] ?? '1') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Optimization Algorithm:</label>
                    <select name="algorithm" class="form-control">
                        <option value="nearest_neighbor" <?= (($_POST['algorithm'] ?? '') == 'nearest_neighbor') ? 'selected' : '' ?>>Nearest Neighbor</option>
                        <option value="savings" <?= (($_POST['algorithm'] ?? '') == 'savings') ? 'selected' : '' ?>>Savings Algorithm</option>
                        <option value="genetic" <?= (($_POST['algorithm'] ?? '') == 'genetic') ? 'selected' : '' ?>>Genetic Algorithm</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Customer Addresses <small class="text-muted">(One per line, max <?= MAX_ADDRESSES ?>)</small>:</label>
                <textarea class="form-control" name="addresses" rows="12" 
                          placeholder="Enter delivery addresses, one per line&#10;Example:&#10;123 Main St, Addis Ababa&#10;Bole Road, Addis Ababa&#10;Or use coordinates: 9.032, 38.763"><?= htmlspecialchars($_POST['addresses'] ?? '') ?></textarea>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="sample-addresses">
                        <i class="fas fa-vial"></i> Load Sample Addresses
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-addresses">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Country Code:</label>
                <input type="text" class="form-control" name="countrycode" maxlength="2" 
                       value="<?= htmlspecialchars($_POST['countrycode'] ?? 'et') ?>" 
                       placeholder="et for Ethiopia, us for USA, etc.">
            </div>

            <div class="action-buttons">
                <button type="submit" name="optimize" class="btn btn-success btn-lg">
                    <i class="fas fa-route"></i> Optimize Routes
                </button>
            </div>
        </form>
        
        <?php if ($driverRoutes): ?>
            <hr class="my-4">
            <form method="POST" id="saveForm">
                <div class="mb-3">
                    <label class="form-label">Save Route As:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="route_name" 
                               placeholder="Enter route name (e.g., 'Monday Morning Deliveries')" required>
                        <button type="submit" name="save_route" class="btn btn-info">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>
                </div>
                
                <!-- Hidden fields to store route data -->
                <input type="hidden" name="start" value="<?= htmlspecialchars($_POST['start'] ?? '') ?>">
                <input type="hidden" name="drivers" value="<?= htmlspecialchars($_POST['drivers'] ?? '1') ?>">
                <input type="hidden" name="countrycode" value="<?= htmlspecialchars($_POST['countrycode'] ?? 'et') ?>">
                <?php foreach ($driverRoutes as $driverIdx => $data): ?>
                    <input type="hidden" name="driver_routes[<?= $driverIdx ?>]" 
                           value="<?= htmlspecialchars(json_encode($data['route'])) ?>">
                <?php endforeach; ?>
            </form>
            
            <div class="export-buttons mt-3">
                <a href="?export=csv&format=csv" class="btn btn-secondary">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print Route
                </button>
                <button onclick="shareRoute()" class="btn btn-secondary">
                    <i class="fas fa-share-alt"></i> Share
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Messages -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-check-circle"></i> <?= $successMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($loadedRouteMessage): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-check-circle"></i> <?= $loadedRouteMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($warnings): ?>
            <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-exclamation-triangle"></i> 
                <ul class="mb-0"><?php foreach ($warnings as $w) echo "<li>$w</li>"; ?></ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errors): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Saved Routes -->
        <?php if (!empty($savedRoutes)): ?>
            <div class="saved-routes mt-4">
                <h5><i class="fas fa-history"></i> Recent Routes</h5>
                <div class="list-group">
                    <?php foreach (array_slice($savedRoutes, 0, 3) as $savedRoute): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($savedRoute['name']) ?></strong><br>
                                    <small class="text-muted">
                                        <?= date('M j, Y g:i A', strtotime($savedRoute['created_at'])) ?> • 
                                        <?= $savedRoute['driver_count'] ?> driver(s) • 
                                        <?= $savedRoute['stop_count'] ?? 0 ?> stops
                                    </small>
                                </div>
                                <div class="route-actions">
                                    <a href="?load_route=<?= $savedRoute['id'] ?>" class="btn btn-sm btn-info" title="Load">
                                        <i class="fas fa-folder-open"></i>
                                    </a>
                                    <a href="../reports/route_report.php?id=<?= $savedRoute['id'] ?>" 
                                       class="btn btn-sm btn-warning" title="View Report" target="_blank">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                    <a href="?delete_route=<?= $savedRoute['id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirm('Delete this route?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($savedRoutes) > 3): ?>
                    <div class="text-center mt-2">
                        <a href="manage.php" class="btn btn-sm btn-outline-primary">
                            View All (<?= count($savedRoutes) ?>)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="map-panel">
        <div id="map"></div>
        <div class="map-controls">
            <div class="btn-group-vertical">
                <button class="btn btn-sm btn-light" onclick="map.zoomIn()" title="Zoom In">
                    <i class="fas fa-plus"></i>
                </button>
                <button class="btn btn-sm btn-light" onclick="map.zoomOut()" title="Zoom Out">
                    <i class="fas fa-minus"></i>
                </button>
                <button class="btn btn-sm btn-light" onclick="locateUser()" title="My Location">
                    <i class="fas fa-location-arrow"></i>
                </button>
                <button class="btn btn-sm btn-light" onclick="clearMap()" title="Clear Map">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($driverRoutes): ?>
    <div class="routes-container">
        <h2><i class="fas fa-route"></i> Optimized Routes</h2>
        <p class="text-muted">Total distance: <?= 
            round(array_sum(array_column($driverRoutes, 'total_km')), 2) ?> km | 
            Total stops: <?= 
            count($driverRoutes, COUNT_RECURSIVE) - count($driverRoutes) * 2 ?> | 
            Average per driver: <?= 
            round(array_sum(array_column($driverRoutes, 'total_km')) / count($driverRoutes), 2) ?> km
        </p>
        
        <?php 
        $colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#34495e'];
        $i = 0;
        foreach ($driverRoutes as $driverIdx => $data): 
            $color = $colors[$i % count($colors)];
            $i++;
        ?>
        <div class="driver-card" style="border-left-color: <?= $color ?>;">
            <div class="driver-header" style="color: <?= $color ?>;">
                <span><i class="fas fa-truck"></i> Driver <?= $driverIdx + 1 ?></span>
                <span class="badge bg-light text-dark">
                    <?= round($data['total_km'], 1) ?> km • <?= count($data['route']) ?> stops
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr><th>#</th><th>Location</th><th>Distance</th><th>Cumulative</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $cumulative = 0;
                        foreach ($data['route'] as $k => $stop): 
                            $cumulative += isset($stop['distance']) ? $stop['distance'] : 0;
                        ?>
                        <tr>
                            <td>
                                <?php if ($k == 0): ?>
                                    <span class="badge-wh">WH</span>
                                <?php else: ?>
                                    <span class="badge-stop" style="background: <?= $color ?>"><?= $k ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-medium"><?= htmlspecialchars($stop['display_name'] ?? $stop['address']) ?></div>
                                <?php if ($stop['address'] != ($stop['display_name'] ?? '')): ?>
                                    <small class="text-muted"><?= htmlspecialchars($stop['address']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= isset($stop['distance']) ? round($stop['distance'], 1).' km' : '–' ?></td>
                            <td><?= round($cumulative, 1) ?> km</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-polylinedecorator@1.6.0/dist/leaflet.polylineDecorator.min.js"></script>

<script>
// Debug script loading
console.log('Scripts loaded');
console.log('L:', typeof L);
if (typeof L !== 'undefined') {
    console.log('Leaflet version:', L.version);
    console.log('L.Routing:', typeof L.Routing);
    if (typeof L.Routing !== 'undefined') {
        console.log('Leaflet Routing Machine loaded');
    } else {
        console.error('Leaflet Routing Machine not loaded');
    }
} else {
    console.error('Leaflet not loaded');
}

// Global variables
let map, warehouseMarker = null, searchMarkers = [], routeLayers = [];
let searchTimeout = null;
let currentSearchQuery = '';

// Initialize map
function initMap() {
    try {
        console.log('Initializing map...');
        
        // Check map container
        const mapContainer = document.getElementById('map');
        if (!mapContainer) {
            console.error('Map container not found');
            return;
        }
        
        console.log('Map container dimensions:', mapContainer.offsetWidth, 'x', mapContainer.offsetHeight);
        
        if (mapContainer.offsetWidth === 0 || mapContainer.offsetHeight === 0) {
            console.warn('Map container has zero dimensions, this may cause display issues');
        }
        
        // Default to Addis Ababa if no coordinates
        const defaultLat = <?= is_numeric($_POST['warehouse_lat'] ?? '') ? $_POST['warehouse_lat'] : 9.032 ?>;
        const defaultLon = <?= is_numeric($_POST['warehouse_lon'] ?? '') ? $_POST['warehouse_lon'] : 38.763 ?>;
        
        console.log('Creating map with center:', [defaultLat, defaultLon]);
        
        map = L.map('map').setView([defaultLat, defaultLon], 12);
        
        console.log('Map created:', map);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19
        }).addTo(map);
        
        console.log('Tile layer added');
        
        // Add geocoder control
        L.Control.geocoder({
            defaultMarkGeocode: false,
            position: 'topleft',
            placeholder: 'Search locations...',
            errorMessage: 'Nothing found.'
        }).on('markgeocode', function(e) {
            const latlng = e.geocode.center;
            setWarehouseLocation(latlng.lat, latlng.lng, e.geocode.name);
            map.setView(latlng, 15);
        }).addTo(map);
        
        console.log('Geocoder control added');
        
        // Add click event
        map.on('click', function(e) {
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });
        
        console.log('Click event handler added');
        
        // Initialize if we have warehouse coordinates
        const whLat = document.getElementById('warehouse-lat').value;
        const whLon = document.getElementById('warehouse-lon').value;
        if (whLat && whLon) {
            console.log('Setting warehouse location:', whLat, whLon);
            setWarehouseLocation(whLat, whLon);
        }
        
        console.log('Map initialization complete');
        
        // Force map resize in case of initial sizing issues
        setTimeout(function() {
            if (map) {
                map.invalidateSize();
                console.log('Map resized');
            }
        }, 100);
    } catch (error) {
        console.error('Error initializing map:', error);
    }
}
// Set warehouse location
function setWarehouseLocation(lat, lon, name = null) {
    try {
        console.log('Setting warehouse location:', lat, lon, name);
        
        // Remove existing marker
        if (warehouseMarker) {
            map.removeLayer(warehouseMarker);
        }
        
        // Add new marker with custom icon
        const warehouseIcon = L.divIcon({
            className: 'warehouse-icon',
            html: '<div style="background: #2c3e50; color: white; padding: 8px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-warehouse"></i></div>',
            iconSize: [40, 40],
            iconAnchor: [20, 40]
        });
        
        warehouseMarker = L.marker([lat, lon], {icon: warehouseIcon})
            .addTo(map)
            .bindPopup("<strong>Warehouse Location</strong><br>" + (name || `Coordinates: ${lat}, ${lon}`))
            .openPopup();
        
        console.log('Warehouse marker added to map');
        
        // Update form fields
        document.getElementById('warehouse-lat').value = lat;
        document.getElementById('warehouse-lon').value = lon;
        if (name) {
            document.getElementById('warehouse-location').value = name;
        }
        
        // Center map
        map.setView([lat, lon], 15);
        
        console.log('Warehouse location set successfully');
    } catch (error) {
        console.error('Error setting warehouse location:', error);
    }
}
// Search functionality
document.getElementById('warehouse-location').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();
    
    if (query.length < 2) {
        hideSearchResults();
        return;
    }
    
    if (query === currentSearchQuery) return;
    currentSearchQuery = query;
    
    // Debounce search
    searchTimeout = setTimeout(() => {
        performSearch(query);
    }, 300);
});

// Search button
document.getElementById('search-location-btn').addEventListener('click', function() {
    const query = document.getElementById('warehouse-location').value.trim();
    if (query.length < 2) {
        alert('Please enter at least 2 characters to search');
        return;
    }
    performSearch(query);
});

// Perform search
function performSearch(query) {
    showLoading(true);
    
    fetch(`?ajax=search&q=${encodeURIComponent(query)}&country=<?= $_POST['countrycode'] ?? 'et' ?>`)
        .then(response => {
            if (!response.ok) throw new Error('Search failed');
            return response.json();
        })
        .then(data => {
            showSearchResults(data, query);
            showLoading(false);
        })
        .catch(error => {
            console.error('Search error:', error);
            showSearchResults([], query);
            showLoading(false);
        });
}

// Show search results
function showSearchResults(results, query) {
    const container = document.getElementById('search-results');
    container.innerHTML = '';
    
    if (results.length === 0) {
        container.innerHTML = `
            <div class="search-result-item">
                <div class="search-result-name">No results found for "${query}"</div>
                <div class="search-result-details">Try a different search term</div>
            </div>`;
        container.style.display = 'block';
        return;
    }
    
    results.forEach(result => {
        const item = document.createElement('div');
        item.className = 'search-result-item';
        
        const type = result.type ? `<span class="search-result-type">${result.type}</span>` : '';
        const addressParts = [];
        if (result.address.road) addressParts.push(result.address.road);
        if (result.address.city) addressParts.push(result.address.city);
        if (result.address.country) addressParts.push(result.address.country);
        
        item.innerHTML = `
            <div class="search-result-name">
                ${result.display_name} ${type}
            </div>
            <div class="search-result-details">
                ${addressParts.join(', ')}<br>
                <small>Lat: ${result.lat.toFixed(6)}, Lon: ${result.lon.toFixed(6)}</small>
            </div>`;
        
        item.addEventListener('click', function(e) {
            e.preventDefault();
            setWarehouseLocation(result.lat, result.lon, result.display_name);
            hideSearchResults();
        });
        
        container.appendChild(item);
    });
    
    container.style.display = 'block';
}

// Hide search results
function hideSearchResults() {
    document.getElementById('search-results').style.display = 'none';
}

// Reverse geocode (click on map)
function reverseGeocode(lat, lon) {
    showLoading(true);
    
    fetch(`?ajax=search&type=reverse&lat=${lat}&lon=${lon}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                setWarehouseLocation(lat, lon, data[0].display_name);
            } else {
                setWarehouseLocation(lat, lon, `Location at ${lat.toFixed(6)}, ${lon.toFixed(6)}`);
            }
            showLoading(false);
        })
        .catch(error => {
            console.error('Reverse geocode error:', error);
            setWarehouseLocation(lat, lon, `Location at ${lat.toFixed(6)}, ${lon.toFixed(6)}`);
            showLoading(false);
        });
}

// Current location
document.getElementById('use-current-location').addEventListener('click', function() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser');
        return;
    }
    
    showLoading(true, this);
    const button = this;
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            reverseGeocode(lat, lon);
            button.innerHTML = originalHtml;
            button.disabled = false;
            showLoading(false);
        },
        function(error) {
            alert('Unable to retrieve your location. Error: ' + error.message);
            button.innerHTML = originalHtml;
            button.disabled = false;
            showLoading(false);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
});

// Locate user on map
function locateUser() {
    if (!navigator.geolocation) {
        alert('Geolocation not supported');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(function(position) {
        const latlng = L.latLng(position.coords.latitude, position.coords.longitude);
        map.setView(latlng, 15);
        
        // Add temporary marker
        L.marker(latlng, {
            icon: L.divIcon({
                className: 'current-location-icon',
                html: '<div style="background: #27ae60; color: white; padding: 8px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-user"></i></div>',
                iconSize: [40, 40]
            })
        }).addTo(map)
        .bindPopup('Your Current Location')
        .openPopup();
    });
}

// Sample addresses
document.getElementById('sample-addresses').addEventListener('click', function() {
    const sample = `Bole Medhanealem, Addis Ababa
Mexico Square, Addis Ababa
22 Mazoria, Addis Ababa
Kirkos Subcity, Addis Ababa
Nifas Silk Lafto, Addis Ababa
Bole Airport, Addis Ababa
Megenagna, Addis Ababa
Gerji, Addis Ababa
CMC, Addis Ababa
Saris, Addis Ababa`;
    
    document.querySelector('textarea[name="addresses"]').value = sample;
});

// Clear addresses
document.getElementById('clear-addresses').addEventListener('click', function() {
    if (confirm('Clear all addresses?')) {
        document.querySelector('textarea[name="addresses"]').value = '';
    }
});

// Clear map
function clearMap() {
    searchMarkers.forEach(marker => map.removeLayer(marker));
    searchMarkers = [];
    routeLayers.forEach(layer => map.removeLayer(layer));
    routeLayers = [];
}

// Show/hide loading
function showLoading(show, element = null) {
    if (element) {
        element.innerHTML = show ? '<i class="fas fa-spinner fa-spin"></i>' : '<i class="fas fa-search"></i>';
        element.disabled = show;
    }
}

// Share route
function shareRoute() {
    if (navigator.share) {
        navigator.share({
            title: 'Optimized Route',
            text: 'Check out this optimized delivery route!',
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    const searchContainer = document.querySelector('.search-container');
    if (searchContainer && !searchContainer.contains(e.target)) {
        hideSearchResults();
    }
});

// Initialize map when DOM is loaded and scripts are ready
function initializeApp() {
    try {
        console.log('Initializing app...');
        console.log('L:', typeof L);
        console.log('L.Routing:', typeof L.Routing);
        
        if (typeof L === 'undefined') {
            console.error('Leaflet not loaded yet, waiting...');
            setTimeout(initializeApp, 100);
            return;
        }
        
        if (typeof L.Routing === 'undefined') {
            console.error('Leaflet Routing Machine not loaded yet, waiting...');
            setTimeout(initializeApp, 100);
            return;
        }
        
        initMap();
        
        <?php if ($driverRoutes): ?>
        // Check if Leaflet Routing Machine is available
        if (typeof L !== 'undefined' && typeof L.Routing !== 'undefined') {
            console.log('Leaflet Routing Machine is available');
            // Draw routes on map after map is initialized
            // Wait for map to be fully ready
            if (typeof map !== 'undefined' && map) {
                drawRoutesOnMap();
            } else {
                // Fallback: try again after a short delay
                setTimeout(function() {
                    if (typeof map !== 'undefined' && map) {
                        drawRoutesOnMap();
                    } else {
                        console.error('Map failed to initialize');
                    }
                }, 500);
            }
        } else {
            console.error('Leaflet Routing Machine is not available');
        }
        <?php endif; ?>
    } catch (error) {
        console.error('Error initializing app:', error);
    }
}

// Wait for DOM to be loaded and then initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    // DOM is already loaded
    setTimeout(initializeApp, 100);
}

<?php if ($driverRoutes): ?>
// Store all route data for interaction
let allRouteData = [];
let allMarkers = [];
let allPolylines = [];
let focusedRouteIndex = null;

// Function to draw routes on map
function drawRoutesOnMap() {
    try {
        console.log('Drawing routes on map...');
        
        const colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#34495e'];
        let driverIndex = 0;
        
        // Fit bounds to include all points
        const bounds = L.latLngBounds();
        
        console.log('Driver routes data:', <?= json_encode($driverRoutes) ?>);
        
        <?php foreach ($driverRoutes as $driverIdx => $data): ?>
            (function(color, index) {
                console.log('Processing driver', index, 'with data:', <?= json_encode($data) ?>);
                
                const waypoints = [];
                const routeStops = <?= json_encode($data['route']) ?>;
                
                // Store route data
                allRouteData[index] = {
                    color: color,
                    waypoints: [],
                    stops: routeStops,
                    markers: [],
                    polyline: null,
                    control: null
                };
                
                <?php foreach ($data['route'] as $stopIdx => $stop): ?>
                waypoints.push(L.latLng(<?php echo $stop['lat']; ?>, <?php echo $stop['lon']; ?>));
                bounds.extend([<?php echo $stop['lat']; ?>, <?php echo $stop['lon']; ?>]);
                <?php endforeach; ?>
                
                allRouteData[index].waypoints = waypoints;
                
                console.log('Waypoints for driver', index, ':', waypoints);
                
                // Custom markers - create markers for all waypoints
                const createMarker = function(i, wp, nWps) {
                    
                    const isWarehouse = i === 0;
                    const icon = L.divIcon({
                        className: 'route-marker',
                        html: `
                            <div style="
                                background: ${color};
                                color: white;
                                width: 30px;
                                height: 30px;
                                border-radius: 50%;
                                border: 3px solid white;
                                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-weight: bold;
                            ">
                                ${isWarehouse ? '<i class="fas fa-warehouse"></i>' : (i)}
                            </div>`,
                        iconSize: [30, 30],
                        iconAnchor: [15, 30]
                    });
                    
                    const marker = L.marker(wp.latLng, {icon: icon});
                    const stop = routeStops[i] || {};
                    const popupContent = `
                        <div style="min-width: 200px;">
                            <strong>Driver ${index + 1}</strong><br>
                            <strong>${stop.display_name || stop.address}</strong><br>
                            ${stop.address && stop.address !== stop.display_name ? `<small>${stop.address}</small><br>` : ''}
                            <small>Stop: ${i + 1} of ${nWps}</small><br>
                            ${i > 0 ? `<small>Distance: ${stop.distance ? stop.distance.toFixed(1) : '0'} km</small>` : ''}
                        </div>`;
                    
                    marker.bindPopup(popupContent);
                    allRouteData[index].markers.push(marker);
                    allMarkers.push(marker);
                    return marker;
                };
                
                // Use all waypoints for display (return-to-warehouse point already removed in PHP)
                const control = L.Routing.control({
                    waypoints: waypoints,
                    routeWhileDragging: false,
                    showAlternatives: false,
                    fitSelectedRoutes: false,
                    lineOptions: {
                        styles: [
                            {
                                color: color,
                                opacity: 0.7,
                                weight: 5
                            }
                        ],
                        addWaypoints: false
                    },
                    createMarker: createMarker
                }).addTo(map);
                
                // Add arrow decorators after route is drawn
                control.on('routesfound', function(e) {
                    const routes = e.routes;
                    const route = routes[0];
                    
                    // Hide the default routing line immediately
                    const routeLine = control._line;
                    if (routeLine) {
                        map.removeLayer(routeLine);
                    }
                    
                    // No need to remove last marker as we've already excluded the return-to-warehouse point
                    
                    // Use coordinates as-is (don't reverse)
                    const coordinates = route.coordinates;
                    
                    // Create polyline with arrows
                    const polyline = L.polyline(coordinates, {
                        color: color,
                        weight: 5,
                        opacity: 0.7
                    }).addTo(map);
                    
                    // Add arrow decorators
                    const arrowDecorator = L.polylineDecorator(polyline, {
                        patterns: [
                            {
                                offset: '10%',
                                repeat: 100,
                                symbol: L.Symbol.arrowHead({
                                    pixelSize: 12,
                                    polygon: false,
                                    pathOptions: {
                                        stroke: true,
                                        weight: 3,
                                        color: color,
                                        opacity: 0.9
                                    }
                                })
                            }
                        ]
                    }).addTo(map);
                    
                    allRouteData[index].polyline = polyline;
                    allRouteData[index].arrowDecorator = arrowDecorator;
                    allPolylines.push(polyline);
                    allPolylines.push(arrowDecorator);
                    
                    // Make polyline clickable to focus on this route
                    polyline.on('click', function() {
                        focusOnRoute(index);
                    });
                    
                    // Hide the default routing line and container
                    const routingContainer = control.getContainer();
                    if (routingContainer) {
                        routingContainer.style.display = 'none';
                    }
                });
                
                allRouteData[index].control = control;
                routeLayers.push(control);
                console.log('Added route control for driver', index);
            })(colors[driverIndex % colors.length], <?= $driverIdx ?>);
            driverIndex++;
        <?php endforeach; ?>
        
        // Fit bounds with padding
        if (bounds.isValid()) {
            map.fitBounds(bounds, {padding: [50, 50]});
            console.log('Map bounds fitted');
        }
        
        console.log('Routes drawing complete');
    } catch (error) {
        console.error('Error drawing routes on map:', error);
    }
}

// Focus on a specific route
function focusOnRoute(routeIndex) {
    if (focusedRouteIndex === routeIndex) {
        // Already focused, do nothing
        return;
    }
    
    focusedRouteIndex = routeIndex;
    
    // Hide all other routes
    allRouteData.forEach((route, index) => {
        if (index !== routeIndex) {
            // Hide markers
            route.markers.forEach(marker => {
                map.removeLayer(marker);
            });
            
            // Dim polyline
            if (route.polyline) {
                route.polyline.setStyle({
                    opacity: 0.1,
                    weight: 2
                });
            }
            if (route.arrowDecorator) {
                map.removeLayer(route.arrowDecorator);
            }
        } else {
            // Highlight focused route
            if (route.polyline) {
                route.polyline.setStyle({
                    opacity: 0.9,
                    weight: 6
                });
            }
        }
    });
    
    // Fit bounds to focused route
    const focusedRoute = allRouteData[routeIndex];
    if (focusedRoute && focusedRoute.waypoints.length > 0) {
        const routeBounds = L.latLngBounds(focusedRoute.waypoints);
        map.fitBounds(routeBounds, {padding: [50, 50]});
    }
}

// Reset view to show all routes
function resetRouteView() {
    if (focusedRouteIndex === null) {
        return; // Already showing all
    }
    
    focusedRouteIndex = null;
    
    // Show all routes
    allRouteData.forEach((route, index) => {
        // Show markers
        route.markers.forEach(marker => {
            if (!map.hasLayer(marker)) {
                map.addLayer(marker);
            }
        });
        
        // Restore polyline
        if (route.polyline) {
            route.polyline.setStyle({
                opacity: 0.7,
                weight: 5
            });
        }
        
        // Restore arrows
        if (route.arrowDecorator && !map.hasLayer(route.arrowDecorator)) {
            map.addLayer(route.arrowDecorator);
        }
    });
    
    // Fit bounds to all routes
    const bounds = L.latLngBounds();
    allRouteData.forEach(route => {
        route.waypoints.forEach(wp => {
            bounds.extend(wp);
        });
    });
    
    if (bounds.isValid()) {
        map.fitBounds(bounds, {padding: [50, 50]});
    }
}

// ESC key handler
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
        resetRouteView();
    }
});
<?php endif; ?>
</script>

<?php require_once "../includes/footer.php"; ?>