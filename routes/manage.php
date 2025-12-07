<?php
// routes/manage.php
session_start();
require_once "../config/database.php";
require_once "../models/Route.php";
require_once "../config/auth.php";

// Check authentication
Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$routeModel = new Route();
$savedRoutes = $routeModel->getUserRoutes($currentUser['id']);

// Handle route deletion
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    $routeId = intval($_GET['delete']);
    $savedRoute = $routeModel->getRouteWithStops($routeId);
    
    if ($savedRoute && $savedRoute['created_by'] == $currentUser['id']) {
        if ($routeModel->deleteRoute($routeId)) {
            $successMessage = "Route deleted successfully!";
            // Refresh saved routes list
            $savedRoutes = $routeModel->getUserRoutes($currentUser['id']);
        } else {
            $errorMessage = "Failed to delete route.";
        }
    } else {
        $errorMessage = "Route not found or you don't have permission to delete it.";
    }
}

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-bookmark"></i> Saved Routes</h1>
    <a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Create New Route
    </a>
</div>

<?php if (isset($successMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?= $successMessage ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?= $errorMessage ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card dashboard-card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Your Saved Routes</h6>
    </div>
    <div class="card-body">
        <?php if (!empty($savedRoutes)): ?>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Route Name</th>
                            <th>Warehouse</th>
                            <th>Drivers</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($savedRoutes as $route): ?>
                        <tr>
                            <td><?= htmlspecialchars($route['name']) ?></td>
                            <td><?= htmlspecialchars(substr($route['warehouse_location'], 0, 30)) ?><?= strlen($route['warehouse_location']) > 30 ? '...' : '' ?></td>
                            <td><?= $route['driver_count'] ?></td>
                            <td><?= date('M j, Y', strtotime($route['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?load_route=<?= $route['id'] ?>" 
                                       class="btn btn-outline-primary" 
                                       title="Load Route">
                                        <i class="fas fa-folder-open"></i>
                                    </a>
                                    <a href="?delete=<?= $route['id'] ?>" 
                                       class="btn btn-outline-danger" 
                                       title="Delete Route"
                                       onclick="return confirm('Are you sure you want to delete this route?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-route fa-3x text-muted mb-3"></i>
                <h4>No saved routes found</h4>
                <p class="text-muted">You don't have any saved routes yet.</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Your First Route
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>