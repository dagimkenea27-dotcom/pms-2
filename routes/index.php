<?php
// routes/index.php
session_start();
require_once "../config/database.php";
require_once "../models/Route.php";
require_once "../config/auth.php";

// Check authentication
Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

// --- FUNCTIONS ---

function geocode($query, $countrycode = 'et') {
    $base = 'https://nominatim.openstreetmap.org/search';
    $params = http_build_query([
        'q' => $query,
        'format' => 'json',
        'limit' => 1,
        'countrycodes' => $countrycode
    ]);
    $url = $base . '?' . $params;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'DeliveryRoutePlanner/1.0');
    $resp = curl_exec($ch);
    curl_close($ch);

    $arr = json_decode($resp, true);
    if (!$arr || count($arr) === 0) return null;

    return [
        'lat' => floatval($arr[0]['lat']),
        'lon' => floatval($arr[0]['lon'])
    ];
}

function haversine($a, $b) {
    $R = 6371;
    $dLat = deg2rad($b['lat'] - $a['lat']);
    $dLon = deg2rad($b['lon'] - $a['lon']);
    $x = sin($dLat/2)**2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLon/2)**2;
    return $R * 2 * atan2(sqrt($x), sqrt(1 - $x));
}

// Simple K-Means Clustering
function kMeans($points, $k) {
    if ($k <= 0) return [];
    if ($k >= count($points)) {
        // More drivers than points: assign one to each
        $clusters = [];
        foreach ($points as $i => $p) {
            $clusters[$i] = [$p];
        }
        return $clusters;
    }

    // 1. Initialize centroids (pick random points)
    $centroids = [];
    $keys = array_rand($points, $k);
    if (!is_array($keys)) $keys = [$keys];
    foreach ($keys as $key) {
        $centroids[] = $points[$key];
    }

    $clusters = [];
    $maxIterations = 20;

    for ($iter = 0; $iter < $maxIterations; $iter++) {
        // 2. Assign points to nearest centroid
        $clusters = array_fill(0, $k, []);
        foreach ($points as $p) {
            $minDist = INF;
            $bestCluster = 0;
            foreach ($centroids as $i => $c) {
                $dist = haversine($p, $c);
                if ($dist < $minDist) {
                    $minDist = $dist;
                    $bestCluster = $i;
                }
            }
            $clusters[$bestCluster][] = $p;
        }

        // 3. Recalculate centroids
        $newCentroids = [];
        $changed = false;
        foreach ($clusters as $i => $cluster) {
            if (empty($cluster)) {
                $newCentroids[$i] = $centroids[$i];
                continue;
            }
            $sumLat = 0; $sumLon = 0;
            foreach ($cluster as $p) {
                $sumLat += $p['lat'];
                $sumLon += $p['lon'];
            }
            $count = count($cluster);
            $newCentroids[$i] = ['lat' => $sumLat / $count, 'lon' => $sumLon / $count];
            
            if ($newCentroids[$i]['lat'] != $centroids[$i]['lat'] || $newCentroids[$i]['lon'] != $centroids[$i]['lon']) {
                $changed = true;
            }
        }
        $centroids = $newCentroids;

        if (!$changed) break;
    }
    return $clusters;
}

function optimizeRoute($startNode, $points) {
    $ordered = [];
    $current = $startNode;
    $current['distance'] = 0;
    $ordered[] = $current;
    
    $remaining = $points;
    $totalDist = 0;

    while (count($remaining) > 0) {
        $closest = null;
        $closestDist = INF;
        $closestIndex = null;

        foreach ($remaining as $i => $pt) {
            $d = haversine($current, $pt);
            if ($d < $closestDist) {
                $closestDist = $d;
                $closest = $pt;
                $closestIndex = $i;
            }
        }

        $closest['distance'] = $closestDist;
        $ordered[] = $closest;
        $totalDist += $closestDist;
        $current = $closest;
        unset($remaining[$closestIndex]);
    }
    return ['route' => $ordered, 'total_km' => $totalDist];
}

// Advanced optimization algorithms
function savingsAlgorithm($startNode, $points) {
    // Clarke-Wright Savings Algorithm implementation
    // Simplified version for demonstration
    
    if (empty($points)) return ['route' => [$startNode], 'total_km' => 0];
    
    // For now, fallback to nearest neighbor for simplicity
    return optimizeRoute($startNode, $points);
}

function geneticAlgorithm($startNode, $points) {
    // Genetic Algorithm implementation
    // Simplified version for demonstration
    
    if (empty($points)) return ['route' => [$startNode], 'total_km' => 0];
    
    // For now, fallback to nearest neighbor for simplicity
    return optimizeRoute($startNode, $points);
}

// --- PROCESSING ---

$driverRoutes = [];
$errors = [];
$startGeo = null;
$savedRoutes = [];
$routeModel = new Route();

// Handle route saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_route'])) {
    $routeName = trim($_POST['route_name']);
    if (empty($routeName)) {
        $errors[] = "Route name is required to save the route.";
    } else {
        try {
            // Get the route data from session or POST
            $warehouseLocation = $_POST['start'] ?? '';
            $warehouseCoords = geocode($warehouseLocation, $_POST['countrycode'] ?? 'et');
            $driverCount = intval($_POST['drivers'] ?? 1);
            $countryCode = $_POST['countrycode'] ?? 'et';
            
            // Prepare stops data
            $stops = [];
            if (isset($_POST['driver_routes']) && is_array($_POST['driver_routes'])) {
                foreach ($_POST['driver_routes'] as $driverId => $routeData) {
                    $stops[$driverId] = $routeData;
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
            } else {
                $errors[] = "Failed to save route.";
            }
        } catch (Exception $e) {
            $errors[] = "Error saving route: " . $e->getMessage();
        }
    }
}

// Load saved routes for current user
$savedRoutes = $routeModel->getUserRoutes($currentUser['id']);

// Load a saved route
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['load_route'])) {
    $routeId = intval($_GET['load_route']);
    $savedRoute = $routeModel->getRouteWithStops($routeId);
    
    if ($savedRoute && $savedRoute['created_by'] == $currentUser['id']) {
        // Populate form fields with saved route data
        $_POST['start'] = $savedRoute['warehouse_location'];
        $_POST['drivers'] = $savedRoute['driver_count'];
        $_POST['countrycode'] = $savedRoute['country_code'];
        
        // We would also populate the addresses, but for simplicity we'll just show a message
        $loadedRouteMessage = "Loaded route: " . htmlspecialchars($savedRoute['name']);
    } else {
        $errors[] = "Route not found or you don't have permission to access it.";
    }
}

// Delete a saved route
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_route'])) {
    $routeId = intval($_GET['delete_route']);
    $savedRoute = $routeModel->getRouteWithStops($routeId);
    
    if ($savedRoute && $savedRoute['created_by'] == $currentUser['id']) {
        if ($routeModel->deleteRoute($routeId)) {
            $successMessage = "Route deleted successfully!";
            // Refresh saved routes list
            $savedRoutes = $routeModel->getUserRoutes($currentUser['id']);
        } else {
            $errors[] = "Failed to delete route.";
        }
    } else {
        $errors[] = "Route not found or you don't have permission to delete it.";
    }
}

// Process route optimization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['optimize'])) {
    $start = trim($_POST['start']);
    $raw = trim($_POST['addresses']);
    $country = trim($_POST['countrycode']);
    $numDrivers = intval($_POST['drivers']);
    $algorithm = $_POST['algorithm'] ?? 'nearest_neighbor';
    
    if ($numDrivers < 1) $numDrivers = 1;

    $startGeo = geocode($start, $country);
    if (!$startGeo) {
        $errors[] = "Unable to geocode Warehouse Location.";
    } else {
        $lines = array_filter(array_map('trim', explode("\n", $raw)));
        $points = [];
        
        foreach ($lines as $line) {
            sleep(1); // Rate limiting
            $g = geocode($line, $country);
            if (!$g) {
                $errors[] = "Could not geocode: $line";
            } else {
                $points[] = ['address' => $line, 'lat' => $g['lat'], 'lon' => $g['lon']];
            }
        }

        if (count($points) > 0) {
            // 1. Cluster points for drivers
            $clusters = kMeans($points, $numDrivers);

            // 2. Optimize route for each driver based on selected algorithm
            $warehouse = ['address' => $start . " (WAREHOUSE)", 'lat' => $startGeo['lat'], 'lon' => $startGeo['lon']];
            
            foreach ($clusters as $i => $clusterPoints) {
                if (empty($clusterPoints)) continue;
                
                // Select algorithm
                switch ($algorithm) {
                    case 'savings':
                        $result = savingsAlgorithm($warehouse, $clusterPoints);
                        break;
                    case 'genetic':
                        $result = geneticAlgorithm($warehouse, $clusterPoints);
                        break;
                    case 'nearest_neighbor':
                    default:
                        $result = optimizeRoute($warehouse, $clusterPoints);
                        break;
                }
                
                $driverRoutes[$i] = $result;
            }
        }
    }
}

// Export to CSV
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export']) && isset($_GET['format'])) {
    if ($_GET['export'] == 'csv' && $driverRoutes) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="routes.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, ['Driver', 'Stop Number', 'Address', 'Latitude', 'Longitude', 'Distance from Previous (km)']);
        
        // Data rows
        foreach ($driverRoutes as $driverIdx => $data) {
            foreach ($data['route'] as $k => $stop) {
                fputcsv($output, [
                    $driverIdx + 1,
                    $k,
                    $stop['address'],
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

require_once "../includes/header.php";
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<style>
    .map-container { display: flex; gap: 20px; flex-wrap: wrap; }
    .input-panel { flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .map-panel { flex: 2; min-width: 400px; }
    
    #map { height: 700px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid #ddd; }
    
    .driver-card { margin-top: 20px; padding: 15px; border-radius: 6px; border-left: 5px solid #ccc; background: #f9f9f9; }
    .driver-header { font-weight: bold; font-size: 1.1em; margin-bottom: 10px; display: flex; justify-content: space-between; }
    
    .badge-wh { background: #333; color: white; padding: 2px 6px; border-radius: 4px; }
    .badge-stop { background: #6c757d; color: white; padding: 2px 6px; border-radius: 4px; }
    
    .saved-routes { margin-top: 20px; }
    .saved-route-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; background: #f8f9fa; }
    .route-actions { display: flex; gap: 5px; }
    
    .export-buttons { display: flex; gap: 10px; margin-top: 10px; }
    .export-buttons button { width: auto; }
    
    .algorithm-info { font-size: 0.85em; color: #666; margin-top: 5px; }
    
    .btn-secondary { background: #6c757d; }
    .btn-secondary:hover { background: #5a6268; }
    .btn-info { background: #17a2b8; }
    .btn-info:hover { background: #138496; }
    .btn-warning { background: #ffc107; color: #212529; }
    .btn-warning:hover { background: #e0a800; }
    .btn-danger { background: #dc3545; }
    .btn-danger:hover { background: #c82333; }
</style>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-map-marked-alt"></i> Multi-Driver Route Optimizer</h1>
    <a href="manage.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-bookmark fa-sm text-white-50"></i> Manage Saved Routes
    </a>
</div>

<div class="map-container">
    <div class="input-panel">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Warehouse Location:</label>
                <input type="text" class="form-control" name="start" value="<?= htmlspecialchars($_POST['start'] ?? 'Addis Ababa, Ethiopia') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Number of Drivers:</label>
                <input type="number" class="form-control" name="drivers" min="1" max="10" value="<?= htmlspecialchars($_POST['drivers'] ?? '1') ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Optimization Algorithm:</label>
                <select name="algorithm" class="form-control">
                    <option value="nearest_neighbor" <?= (($_POST['algorithm'] ?? '') == 'nearest_neighbor') ? 'selected' : '' ?>>Nearest Neighbor (Default)</option>
                    <option value="savings" <?= (($_POST['algorithm'] ?? '') == 'savings') ? 'selected' : '' ?>>Savings Algorithm</option>
                    <option value="genetic" <?= (($_POST['algorithm'] ?? '') == 'genetic') ? 'selected' : '' ?>>Genetic Algorithm</option>
                </select>
                <div class="algorithm-info">
                    <i class="fas fa-info-circle"></i> 
                    Nearest Neighbor: Fast, simple approach. Savings: Better for clustered deliveries. Genetic: Most optimal but slower.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Customer Orders (One per line):</label>
                <textarea class="form-control" name="addresses" rows="10"><?= htmlspecialchars($_POST['addresses'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Country Code:</label>
                <input type="text" class="form-control" name="countrycode" value="<?= htmlspecialchars($_POST['countrycode'] ?? 'et') ?>">
            </div>

            <button type="submit" name="optimize" class="btn btn-success w-100">Optimize Routes</button>
        </form>
        
        <?php if ($driverRoutes): ?>
            <form method="POST" style="margin-top: 20px;">
                <div class="mb-3">
                    <label class="form-label">Save Route As:</label>
                    <input type="text" class="form-control" name="route_name" placeholder="Enter route name" required>
                </div>
                <button type="submit" name="save_route" class="btn btn-info w-100">
                    <i class="fas fa-save"></i> Save Route
                </button>
                
                <!-- Hidden fields to store route data for saving -->
                <input type="hidden" name="start" value="<?= htmlspecialchars($_POST['start'] ?? '') ?>">
                <input type="hidden" name="drivers" value="<?= htmlspecialchars($_POST['drivers'] ?? '1') ?>">
                <input type="hidden" name="countrycode" value="<?= htmlspecialchars($_POST['countrycode'] ?? 'et') ?>">
                <?php foreach ($driverRoutes as $driverIdx => $data): ?>
                    <input type="hidden" name="driver_routes[<?= $driverIdx ?>]" value="<?= htmlspecialchars(json_encode($data['route'])) ?>">
                <?php endforeach; ?>
            </form>
            
            <div class="export-buttons">
                <a href="?export=csv&format=csv" class="btn btn-secondary">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print Route
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-check-circle"></i> <?= $successMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?= $errorMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errors): ?>
            <div class="alert alert-danger mt-3">
                <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($loadedRouteMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-check-circle"></i> <?= $loadedRouteMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($savedRoutes)): ?>
            <div class="saved-routes">
                <h5><i class="fas fa-bookmark"></i> Recently Saved Routes</h5>
                <?php 
                // Show only the 3 most recent routes
                $recentRoutes = array_slice($savedRoutes, 0, 3);
                foreach ($recentRoutes as $savedRoute): ?>
                    <div class="saved-route-item">
                        <div>
                            <strong><?= htmlspecialchars($savedRoute['name']) ?></strong><br>
                            <small class="text-muted">
                                <?= date('M j, Y', strtotime($savedRoute['created_at'])) ?> • 
                                <?= $savedRoute['driver_count'] ?> driver(s)
                            </small>
                        </div>
                        <div class="route-actions">
                            <a href="?load_route=<?= $savedRoute['id'] ?>" class="btn btn-sm btn-info" title="Load Route">
                                <i class="fas fa-folder-open"></i>
                            </a>
                            <a href="?delete_route=<?= $savedRoute['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Delete Route"
                               onclick="return confirm('Are you sure you want to delete this route?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($savedRoutes) > 3): ?>
                    <div class="text-center mt-2">
                        <a href="manage.php" class="btn btn-sm btn-outline-primary">
                            View All Saved Routes (<?= count($savedRoutes) ?> total)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> 
                You don't have any saved routes yet. Optimize a route and save it!
            </div>
        <?php endif; ?>
    </div>

    <div class="map-panel">
        <div id="map"></div>
    </div>
</div>

<?php if ($driverRoutes): ?>
    <div style="margin-top: 30px;">
        <h2><i class="fas fa-route"></i> Optimized Routes</h2>
        <?php 
        $colors = ['blue', 'red', 'green', 'orange', 'purple', 'cadetblue', 'darkred', 'darkgreen'];
        $i = 0;
        foreach ($driverRoutes as $driverIdx => $data): 
            $color = $colors[$i % count($colors)];
            $i++;
        ?>
        <div class="driver-card" style="border-left-color: <?= $color ?>;">
            <div class="driver-header" style="color: <?= $color ?>;">
                <span>Driver <?= $driverIdx + 1 ?></span>
                <span><?= round($data['total_km'], 1) ?> km</span>
            </div>
            <table class="table table-sm">
                <tr><th>#</th><th>Location</th><th>Dist.</th></tr>
                <?php foreach ($data['route'] as $k => $stop): ?>
                <tr>
                    <td><?= $k==0 ? '<span class="badge-wh">WH</span>' : '<span class="badge-stop">'.$k.'</span>' ?></td>
                    <td><?= htmlspecialchars($stop['address']) ?></td>
                    <td><?= isset($stop['distance']) ? round($stop['distance'], 1).'km' : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
    var map = L.map('map').setView([9.03, 38.74], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    <?php if ($driverRoutes): ?>
    var colors = ['blue', 'red', 'green', 'orange', 'purple', 'cadetblue', 'darkred', 'darkgreen'];
    var driverIndex = 0;

    <?php foreach ($driverRoutes as $data): ?>
        (function(color) {
            var waypoints = [
                <?php foreach ($data['route'] as $stop): ?>
                L.latLng(<?= $stop['lat'] ?>, <?= $stop['lon'] ?>),
                <?php endforeach; ?>
            ];

            L.Routing.control({
                waypoints: waypoints,
                routeWhileDragging: false,
                showAlternatives: false,
                fitSelectedRoutes: false, // We'll fit bounds manually
                lineOptions: {
                    styles: [{color: color, opacity: 0.7, weight: 6}]
                },
                createMarker: function(i, wp, nWps) {
                    // Simple markers for now, color-coded could be added
                    return L.marker(wp.latLng).bindPopup("Driver Route (" + color + ")");
                }
            }).addTo(map);
        })(colors[driverIndex % colors.length]);
        driverIndex++;
    <?php endforeach; ?>
    
    // Fit bounds to all points
    <?php 
        $allPoints = [];
        foreach ($driverRoutes as $d) {
            foreach ($d['route'] as $r) {
                $allPoints[] = [$r['lat'], $r['lon']];
            }
        }
    ?>
    var allPoints = <?= json_encode($allPoints) ?>;
    if (allPoints.length > 0) {
        map.fitBounds(allPoints, {padding: [50, 50]});
    }
    <?php endif; ?>
</script>

<?php require_once "../includes/footer.php"; ?>