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

// --- CLASSES ---
require_once "../lib/RateLimiter.php";
require_once "../lib/LocationSearch.php";

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

/**
 * Group points into spatial clusters (K-means algorithm)
 */
function clusterPoints($points, $numClusters) {
    if ($numClusters <= 1 || count($points) <= $numClusters) {
        if ($numClusters <= 1) return [$points];
        $result = [];
        foreach ($points as $p) $result[] = [$p];
        return $result;
    }

    // 1. Initial Centroids (Pick points that are spread out)
    $centroids = [];
    $centroids[] = $points[array_rand($points)];
    
    for ($i = 1; $i < $numClusters; $i++) {
        $maxDist = -1;
        $nextCentroid = null;
        
        foreach ($points as $p) {
            $minDistToCentroid = INF;
            foreach ($centroids as $c) {
                $d = haversineDistance($p['lat'], $p['lon'], $c['lat'], $c['lon']);
                if ($d < $minDistToCentroid) $minDistToCentroid = $d;
            }
            if ($minDistToCentroid > $maxDist) {
                $maxDist = $minDistToCentroid;
                $nextCentroid = $p;
            }
        }
        $centroids[] = $nextCentroid;
    }

    // 2. Iterative optimization (K-means)
    for ($iter = 0; $iter < 12; $iter++) {
        $clusters = array_fill(0, $numClusters, []);
        
        // Assignment phase: Each point to nearest centroid
        foreach ($points as $p) {
            $bestDist = INF;
            $bestIdx = 0;
            foreach ($centroids as $idx => $c) {
                $d = haversineDistance($p['lat'], $p['lon'], $c['lat'], $c['lon']);
                if ($d < $bestDist) {
                    $bestDist = $d;
                    $bestIdx = $idx;
                }
            }
            $clusters[$bestIdx][] = $p;
        }
        
        // Update phase: Centroid moves to center of cluster
        $changed = false;
        foreach ($clusters as $idx => $clusterPoints) {
            if (empty($clusterPoints)) {
                // If a cluster is empty, jump its centroid to a random point to keep it active
                $newCentroid = $points[array_rand($points)];
                $centroids[$idx] = ['lat' => $newCentroid['lat'], 'lon' => $newCentroid['lon']];
                $changed = true;
                continue;
            }
            
            $meanLat = array_sum(array_column($clusterPoints, 'lat')) / count($clusterPoints);
            $meanLon = array_sum(array_column($clusterPoints, 'lon')) / count($clusterPoints);
            
            if (abs($centroids[$idx]['lat'] - $meanLat) > 0.0001 || abs($centroids[$idx]['lon'] - $meanLon) > 0.0001) {
                $centroids[$idx] = ['lat' => $meanLat, 'lon' => $meanLon];
                $changed = true;
            }
        }
        if (!$changed) break;
    }
    
    return $clusters;
}

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
                // Handle multiple warehouses for saving
                $warehouseLocations = [];
                if (isset($_POST['warehouses']) && is_array($_POST['warehouses'])) {
                    // Multiple warehouses mode
                    foreach ($_POST['warehouses'] as $warehouseData) {
                        if (!empty($warehouseData['name']) && !empty($warehouseData['lat']) && !empty($warehouseData['lon'])) {
                            $warehouseLocations[] = [
                                'name' => validateInput($warehouseData['name'], 'address'),
                                'lat' => validateInput($warehouseData['lat'], 'coordinate'),
                                'lon' => validateInput($warehouseData['lon'], 'coordinate')
                            ];
                        }
                    }
                } else {
                    // Single warehouse mode (backward compatibility)
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
                    
                    // Convert to new format
                    if (!empty($warehouseLocation) && $warehouseCoords) {
                        $warehouseLocations[] = [
                            'name' => $warehouseLocation,
                            'lat' => $warehouseCoords['lat'],
                            'lon' => $warehouseCoords['lon']
                        ];
                    }
                }
                
                // Prepare stops data
                $stops = [];
                if (isset($_POST['driver_routes']) && is_array($_POST['driver_routes'])) {
                    foreach ($_POST['driver_routes'] as $driverId => $routeData) {
                        $stops[$driverId] = json_decode($routeData, true) ?? [];
                    }
                }
                
                // Save route to database (pass warehouse locations as array)
                $routeId = $routeModel->saveRoute(
                    $routeName,
                    $warehouseLocations,
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
        // Handle multiple warehouses
        $warehouses = [];
        if (isset($_POST['warehouses']) && is_array($_POST['warehouses'])) {
            // Multiple warehouses mode
            foreach ($_POST['warehouses'] as $warehouseData) {
                if (!empty($warehouseData['name']) && !empty($warehouseData['lat']) && !empty($warehouseData['lon'])) {
                    $warehouses[] = [
                        'name' => validateInput($warehouseData['name'], 'address'),
                        'lat' => validateInput($warehouseData['lat'], 'coordinate'),
                        'lon' => validateInput($warehouseData['lon'], 'coordinate')
                    ];
                }
            }
        } else {
            // Single warehouse mode (backward compatibility)
            $start = validateInput($_POST['start'] ?? '', 'address');
            if (!empty($start)) {
                $useCoordinates = false;
                
                // Check if start is coordinates
                if (preg_match('/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/', $start, $matches)) {
                    $warehouses[] = [
                        'name' => "Location at {$matches[1]}, {$matches[2]}",
                        'lat' => floatval($matches[1]),
                        'lon' => floatval($matches[2])
                    ];
                    $useCoordinates = true;
                } else {
                    $startGeo = $locationSearch->geocode($start, validateInput($_POST['countrycode'] ?? 'et', 'country'));
                    if ($startGeo) {
                        $warehouses[] = [
                            'name' => $startGeo['display_name'] ?? $start,
                            'lat' => $startGeo['lat'],
                            'lon' => $startGeo['lon']
                        ];
                    }
                }
            }
        }
        
        if (empty($warehouses)) {
            $errors[] = "Please provide at least one warehouse location.";
        } else {
            $raw = validateInput($_POST['addresses'] ?? '', 'address');
            $country = validateInput($_POST['countrycode'] ?? 'et', 'country');
            $numDrivers = validateInput($_POST['drivers'] ?? 1, 'int');
            $algorithm = validateInput($_POST['algorithm'] ?? 'nearest_neighbor');
            
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
                    $driverRoutes = [];
                    
                    if ($numDrivers > 1 && count($points) > $numDrivers) {
                        // Use Spatial Clustering for multiple drivers
                        $clusters = clusterPoints($points, $numDrivers);
                        
                        foreach ($clusters as $i => $driverPoints) {
                            if (empty($driverPoints)) continue;
                            
                            // Assign nearest warehouse to this cluster center
                            $clusterLat = array_sum(array_column($driverPoints, 'lat')) / count($driverPoints);
                            $clusterLon = array_sum(array_column($driverPoints, 'lon')) / count($driverPoints);
                            
                            $bestWarehouse = $warehouses[0];
                            $minWhDist = INF;
                            foreach ($warehouses as $wh) {
                                $d = haversineDistance($clusterLat, $clusterLon, $wh['lat'], $wh['lon']);
                                if ($d < $minWhDist) {
                                    $minWhDist = $d;
                                    $bestWarehouse = $wh;
                                }
                            }
                            
                            $warehouse = [
                                'address' => $bestWarehouse['name'] . " (WAREHOUSE)",
                                'lat' => $bestWarehouse['lat'],
                                'lon' => $bestWarehouse['lon'],
                                'display_name' => $bestWarehouse['name'] . " (WAREHOUSE)",
                                'is_warehouse' => true
                            ];
                            
                            $driverRoutes[] = optimizeRoute($warehouse, $driverPoints);
                        }
                    } else {
                        // Single driver or too few points - simple logic
                        $warehouseData = $warehouses[0];
                        $warehouse = [
                            'address' => $warehouseData['name'] . " (WAREHOUSE)",
                            'lat' => $warehouseData['lat'],
                            'lon' => $warehouseData['lon'],
                            'display_name' => $warehouseData['name'] . " (WAREHOUSE)",
                            'is_warehouse' => true
                        ];
                        
                        if ($numDrivers > 1) {
                            // Split points sequentially if clustering isn't viable
                            $pointsPerDriver = ceil(count($points) / $numDrivers);
                            for ($i = 0; $i < $numDrivers; $i++) {
                                $driverPoints = array_slice($points, $i * $pointsPerDriver, $pointsPerDriver);
                                if (!empty($driverPoints)) {
                                    $driverRoutes[] = optimizeRoute($warehouse, $driverPoints);
                                }
                            }
                        } else {
                            $driverRoutes[] = optimizeRoute($warehouse, $points);
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Premium UI Styles */
    :root {
        --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        --glass-bg: rgba(255, 255, 255, 0.95);
        --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        --hover-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.25);
    }

    body {
        background-color: #f8f9fc;
    }

    .page-header {
        background: white;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 2rem;
        border-left: 5px solid #4e73df;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .map-container { 
        display: grid; 
        grid-template-columns: 350px 1fr; 
        gap: 1.5rem; 
        margin-bottom: 2rem;
        height: calc(100vh - 200px);
        min-height: 700px;
    }

    .input-panel { 
        background: white; 
        padding: 0; 
        border-radius: 1rem; 
        box-shadow: var(--card-shadow); 
        display: flex;
        flex-direction: column;
        overflow: hidden;
        height: 100%;
    }

    .input-panel-header {
        padding: 1.25rem;
        background: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        font-weight: 700;
        color: #4e73df;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .input-panel-body {
        padding: 1.25rem;
        overflow-y: auto;
        flex: 1;
    }
    
    .map-panel { 
        width: 100%; 
        height: 100%;
        position: relative;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }
    
    #map { 
        height: 100%; 
        width: 100%; 
        z-index: 1;
    }
    
    /* Search functionality */
    .search-container { position: relative; margin-bottom: 1rem; }
    .search-input-group {
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .search-input-group .form-control {
        border: none;
        padding: 0.75rem 1rem;
    }
    .search-input-group .btn {
        border: none;
        padding: 0.75rem 1rem;
        background: white;
        color: #4e73df;
    }
    .search-results {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: var(--hover-shadow);
        display: none;
    }
    .search-result-item {
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fc;
        transition: all 0.2s;
    }
    .search-result-item:hover {
        background: #f1f3f9;
        padding-left: 1.25rem;
    }
    .search-result-name { font-weight: 600; color: #2c3e50; }
    .search-result-details { font-size: 0.8rem; color: #858796; }
    
    /* Cards and Routes */
    .routes-header {
        background: white;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
    }

    .routes-stats {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
    }
    
    .stat-item {
        flex: 1;
        min-width: 80px;
        text-align: center;
        padding: 0.5rem;
        background: #f8f9fc;
        border-radius: 0.75rem;
        transition: transform 0.2s;
    }

    .stat-item:hover {
        transform: translateY(-2px);
        background: #f1f3f9;
    }

    .stat-value {
        font-size: 1.1rem;
        font-weight: 800;
        color: #4e73df;
        display: block;
    }
    
    .stat-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #858796;
        font-weight: 700;
        margin-top: 2px;
    }

    @media (max-width: 576px) {
        .routes-header { padding: 1rem; }
        .routes-stats { gap: 0.75rem; }
        .stat-item { padding: 0.4rem; min-width: 70px; }
        .stat-value { font-size: 1rem; }
        .stop-card { padding: 0.6rem; gap: 0.5rem; }
        .stop-badge { width: 28px; height: 28px; font-size: 0.75rem; }
        .stop-distance { padding-left: 0.4rem; }
        .dist-val { font-size: 0.75rem; }
        .driver-header { padding: 0.75rem 1rem; }
    }

    .routes-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 400px), 1fr)); 
        gap: 1.5rem; 
        margin-top: 1.5rem; 
    }
    .driver-card { 
        border-radius: 1rem; 
        border: none;
        background: white; 
        box-shadow: var(--card-shadow);
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .driver-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }
    .driver-header { 
        padding: 1rem 1.25rem;
        background: #fff;
        border-bottom: 1px solid #e3e6f0;
        font-weight: 700; 
        display: flex; 
        justify-content: space-between;
        align-items: center;
    }
    
    .stops-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
        max-height: 400px;
        overflow-y: auto;
        background: #fcfcfd;
    }
    
    .stop-card {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.8rem;
        padding: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
        text-decoration: none !important;
    }
    
    .stop-card:hover {
        border-color: #bac8f3;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateX(5px);
        z-index: 1;
    }
    
    .stop-badge {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.8rem;
        color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .stop-content {
        flex-grow: 1;
        min-width: 0;
    }
    
    .stop-title {
        font-weight: 700;
        font-size: 0.85rem;
        color: #4e73df;
        margin-bottom: 0.1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .stop-address {
        font-size: 0.75rem;
        color: #858796;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    
    .stop-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.25rem;
        font-size: 0.7rem;
        color: #b7b9cc;
    }
    
    .stop-distance {
        text-align: right;
        flex-shrink: 0;
        padding-left: 0.5rem;
        border-left: 1px solid #eaecf4;
    }
    
    .dist-val {
        font-weight: 800;
        font-size: 0.85rem;
        color: #1a1a1a;
        line-height: 1;
    }
    
    .dist-total {
        font-size: 0.65rem;
        color: #858796;
        margin-top: 2px;
    }

    .badge-wh { background: #1a1a1a; color: white; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
    .badge-stop { background: #6c757d; color: white; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; min-width: 24px; text-align: center; }
    
    /* Form enhancements */
    .form-group-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #858796;
        font-weight: 700;
        margin-bottom: 0.75rem;
        letter-spacing: 0.5px;
    }
    .form-control, .form-select {
        border-radius: 0.5rem;
        padding: 0.6rem 1rem;
        border: 1px solid #d1d3e2;
        font-size: 0.9rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    textarea.form-control {
        resize: none;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
    }
    
    /* Map controls */
    .map-controls-custom {
        position: absolute;
        top: 1rem;
        right: 1rem;
        z-index: 1000;
        background: white;
        padding: 0.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .map-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: white;
        color: #4e73df;
        border-radius: 0.35rem;
        transition: all 0.2s;
        cursor: pointer;
    }
    .map-btn:hover { background: #f1f3f9; color: #224abe; }
    
    /* Loading */
    .loading-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        backdrop-filter: blur(2px);
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .map-container { display: flex; flex-direction: column; height: auto; }
        .input-panel { height: auto; max-height: 600px; }
        .map-panel { height: 500px; }
    }
</style>

<div class="page-header">
    <div>
        <h1 class="h3 mb-1 text-gray-800"><i class="fas fa-route text-primary me-2"></i> Route Optimizer</h1>
        <p class="mb-0 text-muted small">Intelligent routing for optimized delivery reliability</p>
    </div>
    <div>
        <a href="manage.php" class="btn btn-outline-primary shadow-sm me-2">
            <i class="fas fa-bookmark me-1"></i> Saved Routes
        </a>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#helpModal">
            <i class="fas fa-question-circle me-1"></i> Help
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
        <div class="input-panel-header">
            <i class="fas fa-sliders-h"></i> Configuration
        </div>
        <div class="input-panel-body">
            <form method="POST" id="routeForm">
                <div class="mb-4">
                    <div class="form-group-title">Warehouse Locations</div>
                    <div id="warehouses-container">
                        <div class="warehouse-item mb-3">
                            <div class="search-container">
                                <div class="search-input-group d-flex">
                                    <input type="text" class="form-control warehouse-location" 
                                           id="warehouse-location"
                                           name="warehouses[0][name]" 
                                           placeholder="Search warehouse..."
                                           autocomplete="off">
                                    <button class="btn search-location-btn" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="btn use-current-location" type="button" title="Use Current Location">
                                        <i class="fas fa-location-arrow"></i>
                                    </button>
                                </div>
                                <div class="search-results" style="display:none;"></div>
                                <input type="hidden" name="warehouses[0][lat]" id="warehouse-lat" class="warehouse-lat">
                                <input type="hidden" name="warehouses[0][lon]" id="warehouse-lon" class="warehouse-lon">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-warehouse">
                        <i class="fas fa-plus me-1"></i> Add Another Warehouse
                    </button>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="form-group-title">Drivers</div>
                        <input type="number" class="form-control" name="drivers" min="1" max="<?= MAX_DRIVERS ?>" 
                               value="<?= htmlspecialchars($_POST['drivers'] ?? '1') ?>">
                    </div>
                    <div class="col-6">
                        <div class="form-group-title">Algorithm</div>
                        <select name="algorithm" class="form-select">
                            <option value="nearest_neighbor" <?= (($_POST['algorithm'] ?? '') == 'nearest_neighbor') ? 'selected' : '' ?>>Nearest Neighbor</option>
                            <option value="savings" <?= (($_POST['algorithm'] ?? '') == 'savings') ? 'selected' : '' ?>>Savings</option>
                            <option value="genetic" <?= (($_POST['algorithm'] ?? '') == 'genetic') ? 'selected' : '' ?>>Genetic</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="form-group-title mb-0">Destinations</div>
                        <div>
                            <button type="button" class="btn btn-xs btn-link text-primary text-decoration-none p-0 me-2" id="pick-locations-btn">
                                <i class="fas fa-list-ul"></i> Pick Locations
                            </button>
                            <button type="button" class="btn btn-xs btn-link text-decoration-none p-0 me-2" id="sample-addresses">Sample</button>
                            <button type="button" class="btn btn-xs btn-link text-danger text-decoration-none p-0" id="clear-addresses">Clear</button>
                        </div>
                    </div>
                    <textarea class="form-control" name="addresses" rows="8" 
                              placeholder="Enter addresses (one per line)&#10;Addis Ababa, Bole&#10;Addis Ababa, Piassa"><?= htmlspecialchars($_POST['addresses'] ?? '') ?></textarea>
                </div>

                <div class="mb-4">
                    <div class="form-group-title">Region</div>
                    <input type="text" class="form-control" name="countrycode" maxlength="2" 
                           value="<?= htmlspecialchars($_POST['countrycode'] ?? 'et') ?>" 
                           placeholder="Country Code (e.g. et)">
                </div>

                <div class="d-grid">
                    <button type="submit" name="optimize" class="btn btn-primary py-2 fw-bold" style="background: var(--primary-gradient); border: none;">
                        <i class="fas fa-magic me-2"></i> Optimize Routes
                    </button>
                </div>
            </form>
            
            <?php if ($driverRoutes): ?>
                <hr class="my-4">
                <form method="POST" id="saveForm">
                    <div class="mb-3">
                        <div class="form-group-title">Save Route</div>
                        <div class="search-input-group d-flex">
                            <input type="text" class="form-control" name="route_name" 
                                   placeholder="Route Name" required>
                            <button type="submit" name="save_route" class="btn text-success">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                    
                    <?php if (isset($_POST['warehouses']) && is_array($_POST['warehouses'])): ?>
                        <?php foreach ($_POST['warehouses'] as $index => $warehouse): ?>
                            <input type="hidden" name="warehouses[<?= $index ?>][name]" value="<?= htmlspecialchars($warehouse['name'] ?? '') ?>">
                            <input type="hidden" name="warehouses[<?= $index ?>][lat]" value="<?= htmlspecialchars($warehouse['lat'] ?? '') ?>">
                            <input type="hidden" name="warehouses[<?= $index ?>][lon]" value="<?= htmlspecialchars($warehouse['lon'] ?? '') ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <input type="hidden" name="start" value="<?= htmlspecialchars($_POST['start'] ?? '') ?>">
                        <input type="hidden" name="warehouse_lat" value="<?= htmlspecialchars($_POST['warehouse_lat'] ?? '') ?>">
                        <input type="hidden" name="warehouse_lon" value="<?= htmlspecialchars($_POST['warehouse_lon'] ?? '') ?>">
                    <?php endif; ?>
                    <input type="hidden" name="drivers" value="<?= htmlspecialchars($_POST['drivers'] ?? '1') ?>">
                    <input type="hidden" name="countrycode" value="<?= htmlspecialchars($_POST['countrycode'] ?? 'et') ?>">
                    <?php foreach ($driverRoutes as $driverIdx => $data): ?>
                        <input type="hidden" name="driver_routes[<?= $driverIdx ?>]" 
                               value="<?= htmlspecialchars(json_encode($data['route'])) ?>">
                    <?php endforeach; ?>
                </form>
                
                <div class="d-grid gap-2 mt-3">
                    <a href="?export=csv&format=csv" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-csv me-1"></i> Export CSV
                    </a>
                    <div class="d-flex gap-2">
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm flex-fill">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                        <button onclick="shareRoute()" class="btn btn-outline-secondary btn-sm flex-fill">
                            <i class="fas fa-share-alt me-1"></i> Share
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div id="messages-area" class="mt-3">
                <!-- Messages will be moved here -->
                <?php if ($successMessage): ?>
                    <div class="alert alert-success py-2 fs-7"><i class="fas fa-check-circle me-1"></i> <?= $successMessage ?></div>
                <?php endif; ?>
                <?php if ($loadedRouteMessage): ?>
                    <div class="alert alert-info py-2 fs-7"><i class="fas fa-info-circle me-1"></i> <?= $loadedRouteMessage ?></div>
                <?php endif; ?>
                <?php if ($warnings): ?>
                    <div class="alert alert-warning py-2 fs-7">
                        <ul class="mb-0 ps-3"><?php foreach ($warnings as $w) echo "<li>$w</li>"; ?></ul>
                    </div>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="alert alert-danger py-2 fs-7">
                        <ul class="mb-0 ps-3"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Saved Routes Mini List -->
            <?php if (!empty($savedRoutes)): ?>
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-group-title mb-0">Recent Routes</div>
                        <a href="manage.php" class="text-decoration-none small">View All</a>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach (array_slice($savedRoutes, 0, 3) as $savedRoute): ?>
                            <div class="p-2 border rounded bg-light hover-bg-white transition-all">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-truncate me-2">
                                        <div class="fw-bold small text-truncate"><?= htmlspecialchars($savedRoute['name']) ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;">
                                            <?= date('M j', strtotime($savedRoute['created_at'])) ?> • <?= $savedRoute['driver_count'] ?> Dr
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?load_route=<?= $savedRoute['id'] ?>" class="btn btn-light border py-0 px-2" title="Load"><i class="fas fa-folder-open text-primary"></i></a>
                                        <a href="?delete_route=<?= $savedRoute['id'] ?>" class="btn btn-light border py-0 px-2" onclick="return confirm('Delete?')" title="Delete"><i class="fas fa-trash text-danger"></i></a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="map-panel">
        <div id="map"></div>
        <div class="map-controls-custom">
            <button class="map-btn" onclick="map.zoomIn()" title="Zoom In"><i class="fas fa-plus"></i></button>
            <button class="map-btn" onclick="map.zoomOut()" title="Zoom Out"><i class="fas fa-minus"></i></button>
            <button class="map-btn" onclick="locateUser()" title="My Location"><i class="fas fa-crosshairs"></i></button>
            <button class="map-btn" onclick="resetRouteView()" title="Fit Route"><i class="fas fa-expand-arrows-alt"></i></button>
            <button class="map-btn text-danger" onclick="clearMap()" title="Clear Map"><i class="fas fa-trash"></i></button>
        </div>
        <div id="loading-overlay" class="loading-overlay" style="display: none;">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 fw-bold text-gray-800">Processing...</div>
            </div>
        </div>
    </div>
</div>

<?php if ($driverRoutes): ?>
    <div class="routes-header d-block">
        <div class="d-md-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-1 text-gray-800"><i class="fas fa-check-circle text-success me-2"></i>Optimized Delivery Plan</h2>
                <div class="text-muted small">Generated on <?= date('M d, Y g:i A') ?></div>
            </div>
            <div class="routes-stats mt-3 mt-md-0">
                <div class="stat-item">
                    <div class="stat-value"><?= count($driverRoutes) ?></div>
                    <div class="stat-label">Drivers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= count($driverRoutes, COUNT_RECURSIVE) - count($driverRoutes) * 2 ?></div>
                    <div class="stat-label">Stops</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= round(array_sum(array_column($driverRoutes, 'total_km')), 1) ?> <span class="small text-muted">km</span></div>
                    <div class="stat-label">Total Dist</div>
                </div>
            </div>
        </div>
        
        <div class="routes-grid">
            <?php 
            $colors = ['#4e73df', '#e74a3b', '#1cc88a', '#f6c23e', '#36b9cc', '#858796', '#6610f2', '#fd7e14'];
            $i = 0;
            foreach ($driverRoutes as $driverIdx => $data): 
                $color = $colors[$i % count($colors)];
                $i++;
            ?>
            <div class="driver-card">
                <div class="driver-header" style="border-left: 5px solid <?= $color ?>;">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-2" 
                             style="width: 32px; height: 32px; background: <?= $color ?>;">
                            <i class="fas fa-truck fa-sm"></i>
                        </div>
                        <div>
                            <div class="text-xs text-uppercase text-gray-500 font-weight-bold">Driver <?= $driverIdx + 1 ?></div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800"><?= count($data['route']) ?> Stops</div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="h6 mb-0 font-weight-bold text-primary"><?= round($data['total_km'], 1) ?> km</div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="stops-list">
                        <?php 
                        $cumulative = 0;
                        foreach ($data['route'] as $k => $stop): 
                            $cumulative += isset($stop['distance']) ? $stop['distance'] : 0;
                        ?>
                        <div class="stop-card" onclick="focusOnRoute(<?= $driverIdx ?>); map.setView([<?= $stop['lat'] ?>, <?= $stop['lon'] ?>], 16);">
                            <div class="stop-badge" style="background: <?= ($k == 0) ? '#1a1a1a' : $color ?>;">
                                <?= ($k == 0) ? '<i class="fas fa-warehouse"></i>' : $k ?>
                            </div>
                            <div class="stop-content">
                                <div class="stop-title">
                                    <span><?= ($k == 0) ? 'Origin' : 'Stop ' . $k ?></span>
                                    <?php if ($k == 0): ?>
                                        <span class="badge bg-success-soft text-success text-xs fw-normal px-2">Warehouse</span>
                                    <?php endif; ?>
                                </div>
                                <div class="stop-address" title="<?= htmlspecialchars($stop['display_name'] ?? $stop['address']) ?>">
                                    <?= htmlspecialchars($stop['display_name'] ?? $stop['address']) ?>
                                </div>
                                <div class="stop-meta">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= isset($stop['lat']) ? round($stop['lat'], 3) : '' ?>, <?= isset($stop['lon']) ? round($stop['lon'], 3) : '' ?></span>
                                </div>
                            </div>
                            <div class="stop-distance">
                                <div class="dist-val"><?= isset($stop['distance']) ? round($stop['distance'], 1) : '0' ?> <span class="text-xs fw-normal">km</span></div>
                                <div class="dist-total"><?= round($cumulative, 1) ?> km cum.</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer bg-white border-top p-2 text-center">
                        <button class="btn btn-sm btn-link text-primary text-decoration-none fw-bold" onclick="focusOnRoute(<?= $driverIdx ?>)">
                            <i class="fas fa-expand-arrows-alt me-1"></i> View Entire Route
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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
        const whLatEl = document.getElementById('warehouse-lat') || document.querySelector('.warehouse-lat');
        const whLonEl = document.getElementById('warehouse-lon') || document.querySelector('.warehouse-lon');
        
        const whLat = whLatEl ? whLatEl.value : null;
        const whLon = whLonEl ? whLonEl.value : null;

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
function setWarehouseLocation(lat, lon, name = null, inputElement = null) {
    try {
        console.log('Setting warehouse location:', lat, lon, name);
        
        // For single warehouse mode (backward compatibility)
        if (!inputElement) {
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
            const latEl = document.getElementById('warehouse-lat') || document.querySelector('.warehouse-lat');
            const lonEl = document.getElementById('warehouse-lon') || document.querySelector('.warehouse-lon');
            const locEl = document.getElementById('warehouse-location') || document.querySelector('.warehouse-location');
            
            if (latEl) latEl.value = lat;
            if (lonEl) lonEl.value = lon;
            if (name && locEl) locEl.value = name;
            
            // Center map
            map.setView([lat, lon], 15);
        } else {
            // For multiple warehouse mode
            const searchContainer = inputElement.closest('.search-container');
            searchContainer.querySelector('.warehouse-lat').value = lat;
            searchContainer.querySelector('.warehouse-lon').value = lon;
            inputElement.value = name || `${lat}, ${lon}`;
            
            // Add marker to map
            const warehouseIcon = L.divIcon({
                className: 'warehouse-icon',
                html: '<div style="background: #2c3e50; color: white; padding: 8px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-warehouse"></i></div>',
                iconSize: [40, 40],
                iconAnchor: [20, 40]
            });
            
            const marker = L.marker([lat, lon], {icon: warehouseIcon})
                .addTo(map)
                .bindPopup(`<strong>Warehouse Location</strong><br>${name || `Coordinates: ${lat}, ${lon}`}`);
        }
        
        console.log('Warehouse location set successfully');
    } catch (error) {
        console.error('Error setting warehouse location:', error);
    }
}
// Search functionality for all warehouse inputs
function attachWarehouseSearchEvents() {
    // Handle dynamically added warehouse inputs
    document.querySelectorAll('.warehouse-location').forEach(input => {
        input.removeEventListener('input', handleWarehouseInput);
        input.addEventListener('input', handleWarehouseInput);
    });
    
    document.querySelectorAll('.search-location-btn').forEach(button => {
        button.removeEventListener('click', handleSearchButtonClick);
        button.addEventListener('click', handleSearchButtonClick);
    });
}

function handleWarehouseInput(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();
    
    if (query.length < 2) {
        hideSearchResults(e.target.nextElementSibling.nextElementSibling); // search-results div
        return;
    }
    
    const currentQuery = e.target.dataset.currentQuery || '';
    if (query === currentQuery) return;
    e.target.dataset.currentQuery = query;
    
    // Store reference to search results container
    const searchResults = e.target.nextElementSibling.nextElementSibling;
    
    // Debounce search
    searchTimeout = setTimeout(() => {
        performSearch(query, searchResults);
    }, 300);
}

function handleSearchButtonClick(e) {
    const container = e.target.closest('.search-container');
    const input = container.querySelector('.warehouse-location');
    const query = input.value.trim();
    
    if (query.length < 2) {
        alert('Please enter at least 2 characters to search');
        return;
    }
    
    const searchResults = container.querySelector('.search-results');
    performSearch(query, searchResults);
}

// Add warehouse button
document.getElementById('add-warehouse').addEventListener('click', function() {
    const container = document.getElementById('warehouses-container');
    const warehouseCount = container.querySelectorAll('.warehouse-item').length;
    
    const warehouseItem = document.createElement('div');
    warehouseItem.className = 'warehouse-item mb-3';
    warehouseItem.innerHTML = `
        <div class="search-container">
            <div class="search-input-group d-flex">
                <input type="text" class="form-control warehouse-location" 
                       name="warehouses[${warehouseCount}][name]" 
                       placeholder="Search warehouse..."
                       autocomplete="off">
                <button class="btn search-location-btn" type="button">
                    <i class="fas fa-search"></i>
                </button>
                <button class="btn use-current-location" type="button" title="Use Current Location">
                    <i class="fas fa-location-arrow"></i>
                </button>
            </div>
            <div class="search-results" style="display:none;"></div>
            <input type="hidden" name="warehouses[${warehouseCount}][lat]" class="warehouse-lat">
            <input type="hidden" name="warehouses[${warehouseCount}][lon]" class="warehouse-lon">
        </div>
    `;
    
    container.appendChild(warehouseItem);
    
    // Attach events to new elements
    attachWarehouseSearchEvents();
    
    // Attach current location event
    warehouseItem.querySelector('.use-current-location').addEventListener('click', function() {
        if (!navigator.geolocation) {
            alert('Geolocation not supported');
            return;
        }
        
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            const container = warehouseItem.querySelector('.search-container');
            container.querySelector('.warehouse-lat').value = lat;
            container.querySelector('.warehouse-lon').value = lon;
            
            // Reverse geocode to get address
            fetch(`?ajax=reverse_geocode&lat=${lat}&lon=${lon}`)
                .then(response => response.json())
                .then(data => {
                    if (data.display_name) {
                        container.querySelector('.warehouse-location').value = data.display_name;
                    } else {
                        container.querySelector('.warehouse-location').value = `${lat}, ${lon}`;
                    }
                })
                .catch(() => {
                    container.querySelector('.warehouse-location').value = `${lat}, ${lon}`;
                });
        });
    });
});

// Initial attachment of events
attachWarehouseSearchEvents();

// Perform search
function performSearch(query, searchResultsElement) {
    showLoading(true, searchResultsElement.previousElementSibling.querySelector('button'));
    
    fetch(`?ajax=search&q=${encodeURIComponent(query)}&country=<?= $_POST['countrycode'] ?? 'et' ?>`)
        .then(response => {
            if (!response.ok) throw new Error('Search failed');
            return response.json();
        })
        .then(data => {
            showSearchResults(data, query, searchResultsElement);
            showLoading(false, searchResultsElement.previousElementSibling.querySelector('button'));
        })
        .catch(error => {
            console.error('Search error:', error);
            showSearchResults([], query, searchResultsElement);
            showLoading(false, searchResultsElement.previousElementSibling.querySelector('button'));
        });
}

// Show search results
function showSearchResults(results, query, searchResultsElement) {
    // Use the passed element or fall back to the default one
    const container = searchResultsElement || document.getElementById('search-results');
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
            // Find the container for this search result
            const searchContainer = container.closest('.search-container');
            const locationInput = searchContainer.querySelector('.warehouse-location');
            const latInput = searchContainer.querySelector('.warehouse-lat');
            const lonInput = searchContainer.querySelector('.warehouse-lon');
            
            // Set values
            locationInput.value = result.display_name;
            latInput.value = result.lat;
            lonInput.value = result.lon;
            
            hideSearchResults(container);
        });
        
        container.appendChild(item);
    });
    
    container.style.display = 'block';
}

// Hide search results
function hideSearchResults(element) {
    if (element) {
        element.style.display = 'none';
    } else {
        document.getElementById('search-results').style.display = 'none';
    }
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

// Current location buttons for all warehouses
function attachCurrentLocationEvents() {
    document.querySelectorAll('.use-current-location').forEach(button => {
        button.addEventListener('click', function() {
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
                    
                    // Find the input field for this button
                    const container = button.closest('.search-container');
                    const input = container.querySelector('.warehouse-location');
                    
                    reverseGeocode(lat, lon, input);
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
    });
}

// Initial attachment
attachCurrentLocationEvents();

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

<!-- Locations Modal -->
<div class="modal fade" id="locationsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="fas fa-map-marker-alt me-2"></i> Select Delivery Locations</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom bg-light">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="modal-location-search" class="form-control border-start-0" placeholder="Search by name or neighborhood...">
                    </div>
                </div>
                <div id="locations-list" style="max-height: 450px; overflow-y: auto;" class="list-group list-group-flush">
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-circle-notch fa-spin fa-2x mb-3 text-primary"></i>
                        <p>Loading delivery locations...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <span id="selected-count" class="badge bg-info text-dark">0 locations selected</span>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirm-locations">Add to Route</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pick Locations Functionality
document.addEventListener('DOMContentLoaded', function() {
    const pickBtn = document.getElementById('pick-locations-btn');
    if (pickBtn) {
        pickBtn.addEventListener('click', function() {
            const modalEl = document.getElementById('locationsModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            loadDeliveryLocations();
        });
    }
});

let availableLocations = [];
let selectedLocations = new Set();

function loadDeliveryLocations() {
    const list = document.getElementById('locations-list');
    
    // Check if we already loaded data to avoid re-fetching unnecessarily
    if (availableLocations.length > 0) {
        renderLocations(availableLocations);
        return;
    }
    
    fetch('../api/get_delivery_locations.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                availableLocations = data.data;
                renderLocations(availableLocations);
            } else {
                list.innerHTML = `<div class="p-3 text-danger text-center">Error: ${data.message}</div>`;
            }
        })
        .catch(err => {
            console.error(err);
            list.innerHTML = `<div class="p-3 text-danger text-center">Load failed. Please check your connection.</div>`;
        });
}

function renderLocations(locations) {
    const list = document.getElementById('locations-list');
    list.innerHTML = '';
    
    if (locations.length === 0) {
        list.innerHTML = '<div class="p-4 text-center text-muted italic">No locations found.</div>';
        return;
    }
    
    let currentParent = '';
    
    locations.forEach(loc => {
        if (loc.parent_name !== currentParent) {
            currentParent = loc.parent_name || 'Main Locations';
            const header = document.createElement('div');
            header.className = 'list-group-item bg-light fw-bold py-2 small text-uppercase text-primary sticky-top';
            header.style.top = '0';
            header.style.zIndex = '10';
            header.style.letterSpacing = '1px';
            header.textContent = currentParent;
            list.appendChild(header);
        }
        
        const isSelected = selectedLocations.has(loc.name);
        const item = document.createElement('div');
        item.className = `list-group-item list-group-item-action d-flex align-items-center py-3 ${isSelected ? 'bg-light' : ''}`;
        item.style.cursor = 'pointer';
        item.innerHTML = `
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" ${isSelected ? 'checked' : ''} style="pointer-events: none;">
            </div>
            <div class="ms-3 flex-fill">
                <div class="fw-bold">${loc.name}</div>
                <div class="small text-muted">${loc.description || ''}</div>
            </div>
            ${loc.latitude ? '<span class="badge bg-success opacity-50"><i class="fas fa-map-marker-alt"></i></span>' : ''}
        `;
        
        item.onclick = function() {
            const cb = this.querySelector('input');
            cb.checked = !cb.checked;
            
            if (cb.checked) {
                selectedLocations.add(loc.name);
                this.classList.add('bg-light');
                this.style.backgroundColor = '#f8f9fc';
            } else {
                selectedLocations.delete(loc.name);
                this.classList.remove('bg-light');
                this.style.backgroundColor = '';
            }
            updateSelectedCount();
        };
        
        list.appendChild(item);
    });
}

function updateSelectedCount() {
    document.getElementById('selected-count').textContent = `${selectedLocations.size} locations selected`;
}

const modalSearchInput = document.getElementById('modal-location-search');
if (modalSearchInput) {
    modalSearchInput.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const filtered = availableLocations.filter(loc => 
            loc.name.toLowerCase().includes(term) || 
            (loc.description && loc.description.toLowerCase().includes(term)) ||
            (loc.parent_name && loc.parent_name.toLowerCase().includes(term))
        );
        renderLocations(filtered);
    });
}

const confirmBtn = document.getElementById('confirm-locations');
if (confirmBtn) {
    confirmBtn.addEventListener('click', function() {
        if (selectedLocations.size === 0) {
            alert('Please select at least one location.');
            return;
        }
        
        const textarea = document.querySelector('textarea[name="addresses"]');
        let currentVal = textarea.value.trim();
        
        const newItems = Array.from(selectedLocations).join('\n');
        
        if (currentVal) {
            textarea.value = currentVal + '\n' + newItems;
        } else {
            textarea.value = newItems;
        }
        
        const modalEl = document.getElementById('locationsModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
        
        // Show success feedback
        const btn = document.getElementById('pick-locations-btn');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Added!';
        setTimeout(() => {
            btn.innerHTML = originalHtml;
        }, 2000);
    });
}
</script>

<?php require_once "../includes/footer.php"; ?>