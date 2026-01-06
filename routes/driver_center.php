<?php
/**
 * routes/driver_center.php
 * Mobile-friendly interface for drivers to manage their deliveries
 */
require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../models/Route.php";

Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();

// Check if user is a registered driver
$driverQuery = "SELECT id, driver_code, status FROM route_drivers WHERE user_id = ?";
$driverStmt = $db->prepare($driverQuery);
$driverStmt->execute([$currentUser['id']]);
$driver = $driverStmt->fetch(PDO::FETCH_ASSOC);

if (!$driver && $currentUser['role'] !== 'admin') {
    die("Access denied. You are not registered as a driver.");
}

$driverId = $driver['id'] ?? null;

// Get assigned routes
$routesQuery = "SELECT r.*, v.license_plate, v.vehicle_type 
                FROM routes r 
                LEFT JOIN route_vehicles v ON r.assigned_vehicle_id = v.id 
                WHERE r.assigned_driver_id = ? AND r.route_status IN ('assigned', 'in_progress', 'planned')
                ORDER BY r.created_at DESC";
$routesStmt = $db->prepare($routesQuery);
$routesStmt->execute([$driverId]);
$assignedRoutes = $routesStmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<style>
    .driver-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 10px;
    }
    .route-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        transition: transform 0.2s;
    }
    .status-badge {
        font-size: 0.8rem;
        padding: 5px 10px;
        border-radius: 20px;
    }
    .stop-item {
        border-left: 3px solid #dee2e6;
        padding-left: 15px;
        margin-bottom: 20px;
        position: relative;
    }
    .stop-item.completed {
        border-left-color: #1cc88a;
    }
    .stop-item.active {
        border-left-color: #4e73df;
    }
    .stop-number {
        position: absolute;
        left: -12px;
        top: 0;
        background: #fff;
        border: 2px solid #dee2e6;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    .btn-mobile {
        padding: 12px;
        border-radius: 10px;
        font-weight: bold;
    }
</style>

<div class="driver-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Driver Center</h1>
        <?php if ($driver): ?>
            <span class="badge bg-success">ID: <?php echo $driver['driver_code']; ?></span>
        <?php endif; ?>
    </div>

    <?php if (empty($assignedRoutes)): ?>
        <div class="text-center py-5">
            <i class="fas fa-truck-loading fa-3x text-gray-300 mb-3"></i>
            <p class="text-muted">No routes currently assigned to you.</p>
            <a href="../index.php" class="btn btn-primary btn-sm">Return to Dashboard</a>
        </div>
    <?php else: ?>
        <?php foreach ($assignedRoutes as $route): ?>
            <div class="card route-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title font-weight-bold mb-0"><?php echo htmlspecialchars($route['name']); ?></h5>
                        <span class="status-badge bg-primary text-white"><?php echo strtoupper($route['route_status']); ?></span>
                    </div>
                    
                    <div class="small text-muted mb-3">
                        <i class="fas fa-truck-pickup me-2"></i> Vehicle: <?php echo $route['license_plate'] ?? 'Not Assigned'; ?> 
                        (<?php echo $route['vehicle_type'] ?? '-'; ?>)
                    </div>

                    <div class="mb-4">
                        <div class="d-grid gap-2">
                            <?php if ($route['route_status'] !== 'in_progress'): ?>
                                <button class="btn btn-primary btn-mobile" onclick="updateRouteStatus(<?php echo $route['id']; ?>, 'in_progress')">
                                    <i class="fas fa-play me-2"></i> START ROUTE
                                </button>
                            <?php else: ?>
                                <button class="btn btn-success btn-mobile" onclick="updateRouteStatus(<?php echo $route['id']; ?>, 'completed')">
                                    <i class="fas fa-check-double me-2"></i> COMPLETE ROUTE
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h6 class="font-weight-bold mb-3">Stops & Deliveries</h6>
                    <div class="stops-list">
                        <?php
                        // Fetch stops for this route
                        $stopsQuery = "SELECT * FROM route_deliveries WHERE route_id = ? ORDER BY sequence_number ASC";
                        $stopsStmt = $db->prepare($stopsQuery);
                        $stopsStmt->execute([$route['id']]);
                        $stops = $stopsStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($stops as $stop):
                            $stopStatusClass = '';
                            $statusIcon = 'fa-circle';
                            if ($stop['status'] == 'delivered') {
                                $stopStatusClass = 'completed';
                                $statusIcon = 'fa-check-circle text-success';
                            }
                            if ($stop['status'] == 'in_transit') {
                                $stopStatusClass = 'active';
                                $statusIcon = 'fa-shipping-fast text-primary';
                            }
                        ?>
                            <a href="delivery_detail.php?id=<?php echo $stop['id']; ?>" class="text-decoration-none text-dark">
                                <div class="stop-item <?php echo $stopStatusClass; ?>">
                                    <div class="stop-number"><?php echo $stop['sequence_number']; ?></div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="font-weight-bold"><?php echo htmlspecialchars($stop['customer_name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($stop['address']); ?></div>
                                        </div>
                                        <i class="fas <?php echo $statusIcon; ?>"></i>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Bottom Navigation -->
<style>
    .bottom-nav {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        background: white;
        display: flex;
        justify-content: space-around;
        padding: 12px 0;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        z-index: 1000;
    }
    .nav-item {
        text-align: center;
        color: #858796;
        text-decoration: none;
        font-size: 0.7rem;
    }
    .nav-item.active { color: #4e73df; }
    .nav-item i { font-size: 1.2rem; display: block; margin-bottom: 2px; }
</style>

<div class="bottom-nav">
    <a href="driver_center.php" class="nav-item active">
        <i class="fas fa-th-list"></i>
        <span>My Routes</span>
    </a>
    <a href="driver_center.php" class="nav-item">
        <i class="fas fa-map-marked-alt"></i>
        <span>Map View</span>
    </a>
    <a href="../profile.php" class="nav-item">
        <i class="fas fa-user-circle"></i>
        <span>Profile</span>
    </a>
</div>

<script>
function updateRouteStatus(routeId, status) {
    if (!confirm('Are you sure you want to update the route status to ' + status + '?')) return;
    
    // Simple AJAX post (you should implement a proper API endpoint)
    fetch('update_logistics.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update_route&route_id=' + routeId + '&status=' + status
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert(data.error || 'Failed to update status');
    });
}

function updateStopStatus(stopId, status) {
    fetch('update_logistics.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update_stop&stop_id=' + stopId + '&status=' + status
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert(data.error || 'Failed to update stop status');
    });
}
</script>

<?php require_once "../includes/footer.php"; ?>
