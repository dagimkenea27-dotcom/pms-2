<?php
require_once dirname(__DIR__) . '/config/paths.php';
require_once CONFIG_PATH . 'auth.php';
Auth::startSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/custom.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav sidebar accordion" id="accordionSidebar">
            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo BASE_URL; ?>index.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Inventory MS</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Product Management
            </div>

            <!-- Nav Item - Products -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '../products/view_products.php') !== false && strpos($_SERVER['REQUEST_URI'], 'stock_') === false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>products/view_products.php">
                    <i class="fas fa-fw fa-list"></i>
                    <span>View Products</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>products/add_product.php">
                    <i class="fas fa-fw fa-plus"></i>
                    <span>Add Product</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Stock Operations
            </div>

            <!-- Nav Item - Stock Movements -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '../products/stock_in') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>products/stock_in.php">
                    <i class="fas fa-fw fa-download"></i>
                    <span>Stock In</span>
                </a>
            </li>

            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '../products/stock_out') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>products/stock_out.php">
                    <i class="fas fa-fw fa-upload"></i>
                    <span>Stock Out</span>
                </a>
            </li>

            <!-- Nav Item - Suppliers -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'suppliers/') !== false ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSuppliers"
                    aria-expanded="true" aria-controls="collapseSuppliers">
                    <i class="fas fa-fw fa-truck"></i>
                    <span>Suppliers</span>
                </a>
                <div id="collapseSuppliers" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'suppliers/') !== false ? 'show' : ''; ?>" aria-labelledby="headingSuppliers" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>suppliers/view_suppliers.php">View Suppliers</a>
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>suppliers/add_supplier.php">Add Supplier</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Route Optimizer -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'routes/') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>routes/index.php">
                    <i class="fas fa-fw fa-map-marked-alt"></i>
                    <span>Route Optimizer</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Reports & Analytics
            </div>

            <!-- Nav Item - Reports -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock_movement.php">
                    <i class="fas fa-fw fa-chart-line"></i>
                    <span>Stock Movement</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/low_stock.php">
                    <i class="fas fa-fw fa-exclamation-triangle"></i>
                    <span>Low Stock Items</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock_valuation.php">
                    <i class="fas fa-fw fa-chart-pie"></i>
                    <span>Stock Valuation</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Administration
            </div>

            <!-- Nav Item - Users -->
            <?php if (Auth::isLoggedIn() && Auth::getCurrentUser()['role'] == 'admin'): ?>
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'users/') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>users/view_users.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>User Management</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline mt-3">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="topbar navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - User Information -->
                        <?php if (Auth::isLoggedIn()): 
                            $current_user = Auth::getCurrentUser();
                        ?>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($current_user['full_name']); ?>
                                </span>
                                <img class="img-profile rounded-circle"
                                    src="https://ui-avatars.com/api/?name=<?php echo urlencode($current_user['full_name']); ?>&background=random">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">
                                <i class="fas fa-sign-in-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                Login
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">