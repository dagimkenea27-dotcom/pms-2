<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../models/Vehicle.php';

Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$vehicle = new Vehicle($db);

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_vehicle'])) {
        $vehicle_id = intval($_POST['vehicle_id']);
        if ($vehicle->delete($vehicle_id)) {
            $message = "Vehicle deleted successfully!";
        } else {
            $error = "Failed to delete vehicle.";
        }
    }
}

// Get filter
$status_filter = $_GET['status'] ?? null;

// Get all vehicles
$vehicles_result = $vehicle->getAll($status_filter);

// Get vehicles needing maintenance
$maintenance_needed = $vehicle->getNeedingMaintenance();

require_once '../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-truck"></i> Fleet Management</h1>
    <div>
        <a href="add_vehicle.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus"></i> Add New Vehicle
        </a>
        <a href="maintenance.php" class="btn btn-warning shadow-sm">
            <i class="fas fa-tools"></i> Maintenance Schedule
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Maintenance Alerts -->
<?php if ($maintenance_needed->rowCount() > 0): ?>
    <div class="alert alert-warning">
        <h5><i class="fas fa-exclamation-triangle"></i> Maintenance Required</h5>
        <p><?= $maintenance_needed->rowCount() ?> vehicle(s) need attention:</p>
        <ul>
            <?php while ($v = $maintenance_needed->fetch(PDO::FETCH_ASSOC)): ?>
                <li>
                    <strong><?= htmlspecialchars($v['vehicle_number']) ?></strong> - 
                    <?php if ($v['next_maintenance_date'] && strtotime($v['next_maintenance_date']) <= strtotime('+7 days')): ?>
                        Maintenance due: <?= date('M d, Y', strtotime($v['next_maintenance_date'])) ?>
                    <?php endif; ?>
                    <?php if ($v['insurance_expiry'] && strtotime($v['insurance_expiry']) <= strtotime('+30 days')): ?>
                        Insurance expires: <?= date('M d, Y', strtotime($v['insurance_expiry'])) ?>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Filter -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filter Vehicles</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="maintenance" <?= $status_filter === 'maintenance' ? 'selected' : '' ?>>In Maintenance</option>
                    <option value="retired" <?= $status_filter === 'retired' ? 'selected' : '' ?>>Retired</option>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Vehicles Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Fleet Overview</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="vehiclesTable">
                <thead class="table-light">
                    <tr>
                        <th>Vehicle #</th>
                        <th>Type</th>
                        <th>Make/Model</th>
                        <th>Capacity</th>
                        <th>Fuel</th>
                        <th>Status</th>
                        <th>Next Maintenance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($v = $vehicles_result->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($v['vehicle_number']) ?></strong></td>
                            <td>
                                <span class="badge bg-secondary"><?= ucfirst($v['vehicle_type']) ?></span>
                            </td>
                            <td>
                                <?= htmlspecialchars($v['make'] ?? '-') ?> <?= htmlspecialchars($v['model'] ?? '') ?>
                                <?php if ($v['year']): ?>
                                    <br><small class="text-muted"><?= $v['year'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($v['capacity_weight']): ?>
                                    <i class="fas fa-weight"></i> <?= number_format($v['capacity_weight'], 0) ?> kg<br>
                                <?php endif; ?>
                                <?php if ($v['capacity_volume']): ?>
                                    <i class="fas fa-cube"></i> <?= number_format($v['capacity_volume'], 2) ?> m³
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= ucfirst($v['fuel_type']) ?>
                                <?php if ($v['fuel_efficiency']): ?>
                                    <br><small class="text-muted"><?= $v['fuel_efficiency'] ?> km/L</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badge_class = [
                                    'active' => 'success',
                                    'maintenance' => 'warning',
                                    'retired' => 'secondary'
                                ];
                                $class = $badge_class[$v['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $class ?>"><?= ucfirst($v['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($v['next_maintenance_date']): ?>
                                    <?php
                                    $days_until = (strtotime($v['next_maintenance_date']) - time()) / 86400;
                                    $text_class = $days_until <= 7 ? 'text-danger' : 'text-muted';
                                    ?>
                                    <span class="<?= $text_class ?>">
                                        <?= date('M d, Y', strtotime($v['next_maintenance_date'])) ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_vehicle.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this vehicle?');">
                                    <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
                                    <button type="submit" name="delete_vehicle" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Initialize DataTable
if (typeof $.fn.DataTable !== 'undefined') {
    $('#vehiclesTable').DataTable({
        "pageLength": 25,
        "order": [[0, "asc"]]
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
