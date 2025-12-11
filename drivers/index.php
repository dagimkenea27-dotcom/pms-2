<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../models/Driver.php';

Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$driver = new Driver($db);

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_driver'])) {
        $driver_id = intval($_POST['driver_id']);
        if ($driver->delete($driver_id)) {
            $message = "Driver deleted successfully!";
        } else {
            $error = "Failed to delete driver.";
        }
    }
}

// Get filter
$status_filter = $_GET['status'] ?? null;

// Get all drivers
$drivers_result = $driver->getAll($status_filter);

require_once '../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-users"></i> Driver Management</h1>
    <div>
        <a href="add_driver.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus"></i> Add New Driver
        </a>
        <a href="schedule.php" class="btn btn-info shadow-sm">
            <i class="fas fa-calendar"></i> View Schedule
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

<!-- Filter -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filter Drivers</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="on_leave" <?= $status_filter === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Drivers Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Drivers</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="driversTable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>License</th>
                        <th>Status</th>
                        <th>Skills</th>
                        <th>Hourly Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = $drivers_result->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= $d['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($d['full_name']) ?></strong>
                                <?php if ($d['user_email']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($d['user_email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($d['phone']) ?></td>
                            <td><?= htmlspecialchars($d['email'] ?? '-') ?></td>
                            <td>
                                <?= htmlspecialchars($d['license_number'] ?? '-') ?>
                                <?php if ($d['license_expiry']): ?>
                                    <br><small class="text-muted">Exp: <?= date('M d, Y', strtotime($d['license_expiry'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badge_class = [
                                    'active' => 'success',
                                    'inactive' => 'secondary',
                                    'on_leave' => 'warning'
                                ];
                                $class = $badge_class[$d['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $class ?>"><?= ucfirst($d['status']) ?></span>
                            </td>
                            <td>
                                <?php
                                $skills = json_decode($d['skills'], true);
                                if ($skills && is_array($skills)):
                                    foreach ($skills as $skill):
                                ?>
                                    <span class="badge bg-info"><?= htmlspecialchars($skill) ?></span>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </td>
                            <td><?= $d['hourly_rate'] ? number_format($d['hourly_rate'], 2) . ' ETB' : '-' ?></td>
                            <td>
                                <a href="edit_driver.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../reports/driver_performance.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-info" title="Performance">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this driver?');">
                                    <input type="hidden" name="driver_id" value="<?= $d['id'] ?>">
                                    <button type="submit" name="delete_driver" class="btn btn-sm btn-danger" title="Delete">
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
// Initialize DataTable if available
if (typeof $.fn.DataTable !== 'undefined') {
    $('#driversTable').DataTable({
        "pageLength": 25,
        "order": [[1, "asc"]]
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
