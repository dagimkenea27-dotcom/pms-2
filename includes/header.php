<?php
// includes/header.php (updated)
// Determine base path based on current directory depth
$current_dir = dirname($_SERVER['PHP_SELF']);
$depth = substr_count($current_dir, '/') - substr_count('/stock_management', '/');
// Adjust depth calculation if running in root or subfolder
$base_path = './';
if (strpos($current_dir, 'stock_management/') !== false) {
    $subdir_count = substr_count(substr($current_dir, strpos($current_dir, 'stock_management/') + 17), '/');
    if ($subdir_count > 0) {
        $base_path = str_repeat('../', $subdir_count);
    }
} else {
    // Fallback for different setups
    $path_parts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
    $key = array_search('stock_management', $path_parts);
    if ($key !== false) {
        $count = count($path_parts) - 1 - $key;
        $base_path = $count > 0 ? str_repeat('../', $count) : './';
    }
}

require_once $base_path . "config/auth.php";
Auth::startSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 10px 15px;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .card { transition: transform 0.2s; }
        .card:hover { transform: translateY(-2px); }
        .low-stock { border-left: 4px solid #dc3545; background-color: #fff5f5; }
        .out-of-stock { border-left: 4px solid #6c757d; background-color: #f8f9fa; }
        .navbar { box-shadow: 0 2px 4px rgba(0,0,0,.1); }
        .user-role {
            font-size: 0.8em;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $base_path; ?>index.php">
                <i class="fas fa-boxes"></i> Inventory System
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (Auth::isLoggedIn()): 
                    $current_user = Auth::getCurrentUser();
                ?>
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo $current_user['full_name']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text user-role">
                            Role: <?php echo ucfirst($current_user['role']); ?>
                        </span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>users/profile.php">
                            <i class="fas fa-user"></i> My Profile
                        </a></li>
                        <?php if ($current_user['role'] == 'admin'): ?>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>users/view_users.php">
                            <i class="fas fa-users"></i> Manage Users
                        </a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo $base_path; ?>logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </div>
                <?php else: ?>
                <a class="nav-link text-white" href="<?php echo $base_path; ?>login.php">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if (Auth::isLoggedIn()): ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <nav class="nav flex-column p-3">
                    <a class="nav-link" href="<?php echo $base_path; ?>index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="px-3 text-muted small">PRODUCT MANAGEMENT</h6>
                    <a class="nav-link" href="<?php echo $base_path; ?>products/view_products.php">
                        <i class="fas fa-list"></i> View Products
                    </a>
                    <?php if (Auth::hasPermission('products.create')): ?>
                    <a class="nav-link" href="<?php echo $base_path; ?>products/add_product.php">
                        <i class="fas fa-plus"></i> Add Product
                    </a>
                    <?php endif; ?>
                    
                    <div class="dropdown-divider"></div>
                    <h6 class="px-3 text-muted small">STOCK MANAGEMENT</h6>
                    <a class="nav-link" href="<?php echo $base_path; ?>products/stock_in.php">
                        <i class="fas fa-download"></i> Stock In
                    </a>
                    <a class="nav-link" href="<?php echo $base_path; ?>products/stock_out.php">
                        <i class="fas fa-upload"></i> Stock Out
                    </a>
                    
                    <?php if (Auth::hasPermission('suppliers.view')): ?>
                    <div class="dropdown-divider"></div>
                    <h6 class="px-3 text-muted small">SUPPLIER MANAGEMENT</h6>
                    <a class="nav-link" href="<?php echo $base_path; ?>suppliers/view_suppliers.php">
                        <i class="fas fa-truck"></i> Manage Suppliers
                    </a>
                    <?php endif; ?>
                    
                    <?php if (Auth::hasPermission('reports.view')): ?>
                    <div class="dropdown-divider"></div>
                    <h6 class="px-3 text-muted small">REPORTS</h6>
                    <a class="nav-link" href="<?php echo $base_path; ?>reports/stock_report.php">
                        <i class="fas fa-chart-bar"></i> Stock Report
                    </a>
                    <a class="nav-link" href="<?php echo $base_path; ?>reports/low_stock.php">
                        <i class="fas fa-exclamation-triangle"></i> Low Stock
                    </a>
                    <?php endif; ?>
                    
                    <?php if (Auth::hasPermission('users.manage')): ?>
                    <div class="dropdown-divider"></div>
                    <h6 class="px-3 text-muted small">ADMIN</h6>
                    <a class="nav-link" href="<?php echo $base_path; ?>users/view_users.php">
                        <i class="fas fa-users"></i> User Management
                    </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="p-4">
    <?php endif; ?>