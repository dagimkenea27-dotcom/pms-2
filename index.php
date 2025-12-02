<?php
// Start session and check authentication
require_once "config/auth.php";
require_once "config/database.php";
require_once "models/User.php";

Auth::requireLogin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get low stock count (products with quantity less than 10)
$low_stock_query = "SELECT COUNT(*) as count FROM products WHERE quantity > 0 AND quantity < 10";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute();
$low_stock = $low_stock_stmt->fetch(PDO::FETCH_ASSOC);

// Get out of stock count
$out_of_stock_query = "SELECT COUNT(*) as count FROM products WHERE quantity = 0";
$out_of_stock_stmt = $db->prepare($out_of_stock_query);
$out_of_stock_stmt->execute();
$out_of_stock = $out_of_stock_stmt->fetch(PDO::FETCH_ASSOC);

// Get total products
$total_products_query = "SELECT COUNT(*) as count FROM products";
$total_products_stmt = $db->prepare($total_products_query);
$total_products_stmt->execute();
$total_products = $total_products_stmt->fetch(PDO::FETCH_ASSOC);

// Get total inventory value
$total_value_query = "SELECT SUM(quantity * cost_price) as total_value FROM products WHERE cost_price IS NOT NULL";
$total_value_stmt = $db->prepare($total_value_query);
$total_value_stmt->execute();
$total_value = $total_value_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent stock movements
$recent_movements_query = "
    SELECT sm.*, p.name as product_name, p.sku 
    FROM stock_movements sm 
    LEFT JOIN products p ON sm.product_id = p.id 
    ORDER BY sm.created_at DESC 
    LIMIT 10";
$recent_movements_stmt = $db->prepare($recent_movements_query);
$recent_movements_stmt->execute();
$recent_movements = $recent_movements_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current user info
$current_user = Auth::getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: #fff;
        }
        .sidebar .nav-link:hover {
            background: #495057;
        }
        .sidebar .nav-link.active {
            background: #007bff;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-boxes"></i> Inventory System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($current_user['full_name']); ?>
                </span>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-none d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products/view_products.php">
                                <i class="fas fa-boxes"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="stock/stock_movements.php">
                                <i class="fas fa-exchange-alt"></i> Stock Movements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports/reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <?php if ($current_user['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users/manage_users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $total_products['count']; ?></h4>
                                        <p>Total Products</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-box fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $low_stock['count']; ?></h4>
                                        <p>Low Stock Alerts</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $out_of_stock['count']; ?></h4>
                                        <p>Out of Stock</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-times-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4>$<?php echo number_format($total_value['total_value'] ?? 0, 2); ?></h4>
                                        <p>Total Inventory Value</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt"></i> Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <a href="products/add_product.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add New Product
                                </a>
                                <a href="products/view_products.php" class="btn btn-success">
                                    <i class="fas fa-list"></i> View All Products
                                </a>
                                <a href="stock/stock_in.php" class="btn btn-warning">
                                    <i class="fas fa-download"></i> Stock In
                                </a>
                                <a href="stock/stock_out.php" class="btn btn-danger">
                                    <i class="fas fa-upload"></i> Stock Out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history"></i> Recent Stock Movements
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_movements && count($recent_movements) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Type</th>
                                                    <th>Quantity</th>
                                                    <th>Reason</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_movements as $movement): ?>
                                                <tr>
                                                    <td><?php echo date('M j, Y g:i A', strtotime($movement['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($movement['product_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($movement['sku'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $movement['movement_type'] == 'IN' ? 'success' : 'danger'; ?>">
                                                            <?php echo $movement['movement_type']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $movement['quantity']; ?></td>
                                                    <td><?php echo htmlspecialchars($movement['reason'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No recent stock movements.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($current_user['full_name']); ?>
                </span>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-none d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products/view_products.php">
                                <i class="fas fa-boxes"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="stock/stock_movements.php">
                                <i class="fas fa-exchange-alt"></i> Stock Movements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports/reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <?php if ($current_user['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users/manage_users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $total_products['count']; ?></h4>
                                        <p>Total Products</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-box fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $low_stock['count']; ?></h4>
                                        <p>Low Stock Alerts</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $out_of_stock['count']; ?></h4>
                                        <p>Out of Stock</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-times-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4>$<?php echo number_format($total_value['total_value'] ?? 0, 2); ?></h4>
                                        <p>Total Inventory Value</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt"></i> Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <a href="products/add_product.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add New Product
                                </a>
                                <a href="products/view_products.php" class="btn btn-success">
                                    <i class="fas fa-list"></i> View All Products
                                </a>
                                <a href="stock/stock_in.php" class="btn btn-warning">
                                    <i class="fas fa-download"></i> Stock In
                                </a>
                                <a href="stock/stock_out.php" class="btn btn-danger">
                                    <i class="fas fa-upload"></i> Stock Out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history"></i> Recent Stock Movements
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_movements && count($recent_movements) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Type</th>
                                                    <th>Quantity</th>
                                                    <th>Reason</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_movements as $movement): ?>
                                                <tr>
                                                    <td><?php echo date('M j, Y g:i A', strtotime($movement['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($movement['product_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($movement['sku'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $movement['movement_type'] == 'IN' ? 'success' : 'danger'; ?>">
                                                            <?php echo $movement['movement_type']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $movement['quantity']; ?></td>
                                                    <td><?php echo htmlspecialchars($movement['reason'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No recent stock movements.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });

        // Confirm delete
        function confirmDelete(productName) {
            return confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.');
        }

        // Confirm stock out
        function confirmStockOut(productName, quantity) {
            return confirm('Are you sure you want to remove ' + quantity + ' units of "' + productName + '" from stock?');
        }
    </script>
</body>
</html>