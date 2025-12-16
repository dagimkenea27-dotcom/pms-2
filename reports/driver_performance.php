<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../models/Driver.php';

Auth::requireLogin();
Auth::requireRole('manager');
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$driver = new Driver($db);

// Get driver ID
$driver_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$driver_id) {
    header('Location: ../drivers/index.php');
    exit;
}

// Get driver info
$driver_info = $driver->getById($driver_id);

if (!$driver_info) {
    header('Location: ../drivers/index.php?error=Driver not found');
    exit;
}

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Get performance metrics
$performance = $driver->getPerformance($driver_id, $start_date, $end_date);

require_once '../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-chart-line"></i> Driver Performance: <?= htmlspecialchars($driver_info['full_name']) ?>
    </h1>
    <a href="../drivers/index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Drivers
    </a>
</div>

<!-- Date Range Filter -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Select Date Range</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="id" value="<?= $driver_id ?>">
            <div class="col-md-4">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Performance Summary Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Routes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $performance['total_routes'] ?? 0 ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-route fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Deliveries Completed</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $performance['total_deliveries'] ?? 0 ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-box fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">On-Time Rate</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $performance['avg_on_time'] ? round($performance['avg_on_time'], 1) . '%' : 'N/A' ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Customer Rating</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $performance['avg_rating'] ? round($performance['avg_rating'], 2) . '/5.0' : 'N/A' ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-star fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Metrics -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Distance & Efficiency</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Total Distance Traveled:</strong></td>
                        <td class="text-end"><?= $performance['total_distance'] ? number_format($performance['total_distance'], 2) . ' km' : 'N/A' ?></td>
                    </tr>
                    <tr>
                        <td><strong>Average per Route:</strong></td>
                        <td class="text-end">
                            <?php
                            if ($performance['total_routes'] && $performance['total_distance']) {
                                echo number_format($performance['total_distance'] / $performance['total_routes'], 2) . ' km';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Deliveries per Route:</strong></td>
                        <td class="text-end">
                            <?php
                            if ($performance['total_routes'] && $performance['total_deliveries']) {
                                echo number_format($performance['total_deliveries'] / $performance['total_routes'], 1);
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Failed Deliveries:</strong></td>
                        <td class="text-end text-danger"><?= $performance['total_failed'] ?? 0 ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Cost Analysis</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Total Fuel Cost:</strong></td>
                        <td class="text-end"><?= $performance['total_fuel_cost'] ? number_format($performance['total_fuel_cost'], 2) . ' ETB' : 'N/A' ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Cost:</strong></td>
                        <td class="text-end"><?= $performance['total_cost'] ? number_format($performance['total_cost'], 2) . ' ETB' : 'N/A' ?></td>
                    </tr>
                    <tr>
                        <td><strong>Cost per Delivery:</strong></td>
                        <td class="text-end">
                            <?php
                            if ($performance['total_deliveries'] && $performance['total_cost']) {
                                echo number_format($performance['total_cost'] / $performance['total_deliveries'], 2) . ' ETB';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Cost per Kilometer:</strong></td>
                        <td class="text-end">
                            <?php
                            if ($performance['total_distance'] && $performance['total_cost']) {
                                echo number_format($performance['total_cost'] / $performance['total_distance'], 2) . ' ETB/km';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Driver Information -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Driver Information</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Name:</strong> <?= htmlspecialchars($driver_info['full_name']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($driver_info['phone']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($driver_info['email'] ?? 'N/A') ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> <span class="badge bg-<?= $driver_info['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($driver_info['status']) ?></span></p>
                <p><strong>License:</strong> <?= htmlspecialchars($driver_info['license_number'] ?? 'N/A') ?></p>
                <p><strong>Hourly Rate:</strong> <?= $driver_info['hourly_rate'] ? number_format($driver_info['hourly_rate'], 2) . ' ETB' : 'N/A' ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
