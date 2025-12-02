<?php
// route.php
// Delivery Route Optimizer with Multi-Driver Support (VRP)
// Includes: Nominatim geocoding + K-Means Clustering + Nearest Neighbor + Leaflet Routing Machine

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
                // If cluster is empty, keep old centroid (or re-init). 
                // For simplicity, we keep old.
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

// --- PROCESSING ---

$driverRoutes = [];
$errors = [];
$startGeo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start = trim($_POST['start']);
    $raw = trim($_POST['addresses']);
    $country = trim($_POST['countrycode']);
    $numDrivers = intval($_POST['drivers']);
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

            // 2. Optimize route for each driver
            $warehouse = ['address' => $start . " (WAREHOUSE)", 'lat' => $startGeo['lat'], 'lon' => $startGeo['lon']];
            
            foreach ($clusters as $i => $clusterPoints) {
                if (empty($clusterPoints)) continue;
                $result = optimizeRoute($warehouse, $clusterPoints);
                $driverRoutes[$i] = $result;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Multi-Driver Route Optimizer</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1400px; margin: 0 auto; padding: 20px; background-color: #f8f9fa; }
    .container { display: flex; gap: 20px; flex-wrap: wrap; }
    .input-panel { flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .map-panel { flex: 2; min-width: 400px; }
    
    h1 { color: #2c3e50; margin-bottom: 20px; }
    label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
    input[type=text], input[type=number], textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; box-sizing: border-box; }
    button { background: #28a745; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
    button:hover { background: #218838; }
    
    #map { height: 700px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid #ddd; }
    
    .driver-card { margin-top: 20px; padding: 15px; border-radius: 6px; border-left: 5px solid #ccc; background: #f9f9f9; }
    .driver-header { font-weight: bold; font-size: 1.1em; margin-bottom: 10px; display: flex; justify-content: space-between; }
    table { width: 100%; border-collapse: collapse; font-size: 0.9em; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #eee; }
    
    .badge-wh { background: #333; color: white; padding: 2px 6px; border-radius: 4px; }
    .badge-stop { background: #6c757d; color: white; padding: 2px 6px; border-radius: 4px; }
</style>
</head>
<body>

<h1><i class="fas fa-users"></i> Multi-Driver Route Optimizer</h1>

<div class="container">
    <div class="input-panel">
        <form method="POST">
            <label>Warehouse Location:</label>
            <input type="text" name="start" value="<?= htmlspecialchars($_POST['start'] ?? 'Addis Ababa, Ethiopia') ?>">

            <label>Number of Drivers:</label>
            <input type="number" name="drivers" min="1" max="10" value="<?= htmlspecialchars($_POST['drivers'] ?? '1') ?>">

            <label>Customer Orders (One per line):</label>
            <textarea name="addresses" rows="10"><?= htmlspecialchars($_POST['addresses'] ?? '') ?></textarea>

            <label>Country Code:</label>
            <input type="text" name="countrycode" value="<?= htmlspecialchars($_POST['countrycode'] ?? 'et') ?>">

            <button type="submit">Optimize Routes</button>
        </form>
        
        <?php if ($errors): ?>
        <div style="color: red; margin-top: 20px;">
            <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
        </div>
        <?php endif; ?>

        <?php if ($driverRoutes): ?>
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
                <table>
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
        <?php endif; ?>
    </div>

    <div class="map-panel">
        <div id="map"></div>
    </div>
</div>

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

</body>
</html>
