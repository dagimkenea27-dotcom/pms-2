<?php
require_once dirname(__DIR__) . '/config/paths.php';
require_once CONFIG_PATH . 'database.php';
require_once CONFIG_PATH . 'auth.php';
require_once CONFIG_PATH . 'security.php';
require_once dirname(__DIR__) . '/includes/functions.php';
Auth::startSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <meta name="csrf-token" content="<?php echo Security::getCSRFToken(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="<?php echo BASE_URL; ?>assets/js/theme.js?v=<?php echo filemtime(ROOT_PATH . 'assets/js/theme.js'); ?>"></script>
    <link href="<?php echo BASE_URL; ?>assets/css/custom.css?v=<?php echo filemtime(ROOT_PATH . 'assets/css/custom.css'); ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="<?php echo BASE_URL; ?>assets/img/logo.jpg">
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?php echo BASE_URL; ?>sw.js');
        }
    </script>
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav sidebar accordion" id="accordionSidebar">
            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo BASE_URL; ?>index.php">
                <div class="sidebar-brand-icon">
                    <img src="<?php echo BASE_URL; ?>assets/img/logo.jpg" alt="Logo" style="width: 40px; height: 40px; border-radius: 8px;">
                </div>
                <!-- <div class="sidebar-brand-text mx-3">Inventory MS</div> -->
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span><?php echo __('dashboard'); ?></span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <!-- Heading -->
            <div class="sidebar-heading">
                <?php echo __('product_management'); ?>
            </div>

            <!-- Nav Item - Pricing -->
            <!-- <?php
$isPricingActive = strpos($_SERVER['REQUEST_URI'], 'price') !== false;
?>
            <li class="nav-item <?php echo $isPricingActive ? 'active' : ''; ?>">
                <a class="nav-link <?php echo $isPricingActive ? '' : 'collapsed'; ?>" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapsePricing"
                    aria-expanded="<?php echo $isPricingActive ? 'true' : 'false'; ?>" aria-controls="collapsePricing">
                    <i class="fas fa-fw fa-tag"></i>
                    <span><?php echo __('pricing'); ?></span>
                </a>
                <div id="collapsePricing" class="collapse <?php echo $isPricingActive ? 'show' : ''; ?>" aria-labelledby="headingPricing" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'price.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>price.php"><?php echo __('price_calculator'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'price_analytics.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>price_analytics.php"><?php echo __('price_analytics'); ?></a>
                    </div>
                </div>
            </li> -->

            <!-- Nav Item - Catalog -->
            <?php
$isCatalogActive = (strpos($_SERVER['REQUEST_URI'], 'products/') !== false && strpos($_SERVER['REQUEST_URI'], 'stock_') === false && strpos($_SERVER['REQUEST_URI'], 'purchase_orders') === false && strpos($_SERVER['REQUEST_URI'], 'edit_po.php') === false) || strpos($_SERVER['REQUEST_URI'], 'categories/') !== false || strpos($_SERVER['REQUEST_URI'], 'brands/') !== false;
?>
            <li class="nav-item <?php echo $isCatalogActive ? 'active' : ''; ?>">
                <a class="nav-link <?php echo $isCatalogActive ? '' : 'collapsed'; ?>" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseCatalog"
                    aria-expanded="<?php echo $isCatalogActive ? 'true' : 'false'; ?>" aria-controls="collapseCatalog">
                    <i class="fas fa-fw fa-box-open"></i>
                    <span><?php echo __('catalog'); ?></span>
                </a>
                <div id="collapseCatalog" class="collapse <?php echo $isCatalogActive ? 'show' : ''; ?>" aria-labelledby="headingCatalog" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'products/view_products.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>products/view_products.php"><?php echo __('products'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'categories/') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>categories/"><?php echo __('categories'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'brands/') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>brands/"><?php echo __('brands'); ?></a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                <?php echo __('stock_operations'); ?>
            </div>

            <!-- Nav Item - Stock Movements -->
            <?php
$isStockActive = strpos($_SERVER['REQUEST_URI'], 'products/stock_in') !== false || strpos($_SERVER['REQUEST_URI'], 'products/stock_out') !== false || strpos($_SERVER['REQUEST_URI'], 'products/purchase_orders') !== false || strpos($_SERVER['REQUEST_URI'], 'products/edit_po.php') !== false || strpos($_SERVER['REQUEST_URI'], 'products/stock_audit') !== false;
?>
            <li class="nav-item <?php echo $isStockActive ? 'active' : ''; ?>">
                <a class="nav-link <?php echo $isStockActive ? '' : 'collapsed'; ?>" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseStock"
                    aria-expanded="<?php echo $isStockActive ? 'true' : 'false'; ?>" aria-controls="collapseStock">
                    <i class="fas fa-fw fa-exchange-alt"></i>
                    <span><?php echo __('stock_operations'); ?></span>
                </a>
                <div id="collapseStock" class="collapse <?php echo $isStockActive ? 'show' : ''; ?>" aria-labelledby="headingStock" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'products/stock_in.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>products/stock_in.php"><?php echo __('stock_in'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'products/stock_out.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>products/stock_out.php"><?php echo __('stock_out'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'products/purchase_orders.php') !== false || strpos($_SERVER['REQUEST_URI'], 'products/edit_po.php') !== false ? 'active' : ''; ?> fw-bold" href="<?php echo BASE_URL; ?>products/purchase_orders.php">
                            <i class="fas fa-file-invoice fa-sm"></i> Purchase Orders
                        </a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'products/stock_audit.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>products/stock_audit.php">
                            <i class="fas fa-clipboard-list fa-sm"></i> Stock Audit Log
                        </a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Suppliers -->
            <?php
$isSupplierActive = strpos($_SERVER['REQUEST_URI'], 'suppliers/') !== false;
?>
            <li class="nav-item <?php echo $isSupplierActive ? 'active' : ''; ?>">
                <a class="nav-link <?php echo $isSupplierActive ? '' : 'collapsed'; ?>" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseSuppliers"
                    aria-expanded="<?php echo $isSupplierActive ? 'true' : 'false'; ?>" aria-controls="collapseSuppliers">
                    <i class="fas fa-fw fa-truck"></i>
                    <span><?php echo __('suppliers'); ?></span>
                </a>
                <div id="collapseSuppliers" class="collapse <?php echo $isSupplierActive ? 'show' : ''; ?>" aria-labelledby="headingSuppliers" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'suppliers/view_suppliers.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>suppliers/view_suppliers.php"><?php echo __('view_suppliers'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'suppliers/add_supplier.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>suppliers/add_supplier.php"><?php echo __('add_supplier'); ?></a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Branch Operations -->
            <?php
$isBranchActive = strpos($_SERVER['REQUEST_URI'], 'jimma/') !== false || strpos($_SERVER['REQUEST_URI'], 'branch_order') !== false;
?>
            <li class="nav-item <?php echo $isBranchActive ? 'active' : ''; ?>">
                <a class="nav-link <?php echo $isBranchActive ? '' : 'collapsed'; ?>" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseBranch"
                    aria-expanded="<?php echo $isBranchActive ? 'true' : 'false'; ?>" aria-controls="collapseBranch">
                    <i class="fa-brands fa-meta"></i>
                    <span><?php echo __('Meta Operations'); ?></span>
                </a>
                <div id="collapseBranch" class="collapse <?php echo $isBranchActive ? 'show' : ''; ?>" aria-labelledby="headingBranch" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'products/branch_order_receive.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>products/branch_order_receive.php"><?php echo __('Add New Order'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'products/branch_orders_list.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>products/branch_orders_list.php"><?php echo __('View All Orders'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/branch_order_analytics.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/branch_order_analytics.php"><?php echo __('Meta Analytics'); ?></a>
                        <hr class="sidebar-divider">
                        <!-- <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'jimma/sales_request.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>jimma/sales_request.php">Jimma Stock Request</a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'jimma/view_requests.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>jimma/view_requests.php">View All Requests</a> -->
                    </div>
                </div>
            </li>

            <!-- Nav Item - Drivers & Fleet -->
            <?php
$isFleetActive = strpos($_SERVER['REQUEST_URI'], 'drivers/') !== false || strpos($_SERVER['REQUEST_URI'], 'vehicles/') !== false;
?>
            <li class="nav-item <?php echo $isFleetActive ? 'active' : ''; ?>">
                <a class="nav-link <?php echo $isFleetActive ? '' : 'collapsed'; ?>" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseFleet"
                    aria-expanded="<?php echo $isFleetActive ? 'true' : 'false'; ?>" aria-controls="collapseFleet">
                    <i class="fas fa-fw fa-users-cog"></i>
                    <span><?php echo __('driver_fleet'); ?></span>
                </a>
                <div id="collapseFleet" class="collapse <?php echo $isFleetActive ? 'show' : ''; ?>" aria-labelledby="headingFleet" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'drivers/index.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>drivers/index.php"><?php echo __('drivers'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'vehicles/index.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>vehicles/index.php"><?php echo __('vehicles'); ?></a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Route Optimizer -->
            <?php
$isRouteActive = strpos($_SERVER['REQUEST_URI'], 'routes/') !== false;
?>
            <li class="nav-item <?php echo $isRouteActive ? 'active' : ''; ?>">
                <a class="nav-link <?php echo $isRouteActive ? '' : 'collapsed'; ?>" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseRoutes"
                    aria-expanded="<?php echo $isRouteActive ? 'true' : 'false'; ?>" aria-controls="collapseRoutes">
                    <i class="fas fa-fw fa-map-marked-alt"></i>
                    <span><?php echo __('route_management'); ?></span>
                </a>
                <div id="collapseRoutes" class="collapse <?php echo $isRouteActive ? 'show' : ''; ?>" aria-labelledby="headingRoutes" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'routes/index.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>routes/index.php"><?php echo __('optimize_routes'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'routes/manage.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>routes/manage.php"><?php echo __('saved_routes'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'routes/templates.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>routes/templates.php"><?php echo __('templates'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'customers/address_book.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>customers/address_book.php"><?php echo __('address_book'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'settings/route_preferences.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>settings/route_preferences.php"><?php echo __('preferences'); ?></a>
                        <div class="dropdown-divider"></div>
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>routes/driver_center.php">
                            <i class="fas fa-mobile-alt fa-sm"></i> Driver Mobile App
                        </a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                <?php echo __('reports_analytics'); ?>
            </div>

            <!-- Nav Item - Reports -->
            <!-- Nav Item - Reports -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/stock_movement.php') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock_movement.php">
                    <i class="fas fa-fw fa-chart-line"></i>
                    <span><?php echo __('stock_movement'); ?></span>
                </a>
            </li>

            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/low_stock.php') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/low_stock.php">
                    <i class="fas fa-fw fa-exclamation-triangle"></i>
                    <span><?php echo __('low_stock_items'); ?></span>
                </a>
            </li>

            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/stock_valuation.php') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock_valuation.php">
                    <i class="fas fa-fw fa-chart-pie"></i>
                    <span><?php echo __('stock_valuation'); ?></span>
                </a>
            </li>

            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/marketing_intelligence.php') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/marketing_intelligence.php">
                    <i class="fas fa-fw fa-magic"></i>
                    <span>Marketing Intel</span>
                </a>
            </li>

            <!-- <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/gojo_analysis.php') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/gojo_analysis.php">
                    <i class="fas fa-fw fa-chart-bar"></i>
                    <span>Gojo Analysis</span>
                </a>
            </li>

            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/sales_followup.php') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/sales_followup.php">
                    <i class="fas fa-fw fa-headset"></i>
                    <span>Sales Follow-up</span>
                </a>
            </li> -->


            <!-- Nav Item - Daily Sales -->
           <!-- <?php
            $isDailySalesActive = strpos($_SERVER['REQUEST_URI'], 'daily_sales.php') !== false || strpos($_SERVER['REQUEST_URI'], 'reports/daily_sales_report.php') !== false;
            ?>
            <li class="nav-item <?php echo $isDailySalesActive ? 'active' : ''; ?>">
                <a class="nav-link <?php echo $isDailySalesActive ? '' : 'collapsed'; ?>" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseDailySales"
                    aria-expanded="<?php echo $isDailySalesActive ? 'true' : 'false'; ?>" aria-controls="collapseDailySales">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>Daily Sales</span>
                </a>
                <div id="collapseDailySales" class="collapse <?php echo $isDailySalesActive ? 'show' : ''; ?>" aria-labelledby="headingDailySales" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'daily_sales.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>daily_sales.php">Sales Tracker</a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/daily_sales_report.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/daily_sales_report.php">Performance Report</a>
                    </div>
                </div>
            </li> -->

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                <?php echo __('administration'); ?>
            </div>

            <!-- Nav Item - Admin Tools -->
            <?php if (Auth::isLoggedIn() && Auth::getCurrentUser()['role'] == 'admin'): ?>
            <?php
    $isAdminActive = strpos($_SERVER['REQUEST_URI'], 'users/') !== false || strpos($_SERVER['REQUEST_URI'], 'admin/') !== false || strpos($_SERVER['REQUEST_URI'], 'tax_fee_admin.php') !== false || strpos($_SERVER['REQUEST_URI'], 'settings/backup.php') !== false;
?>
            <li class="nav-item <?php echo $isAdminActive ? 'active' : ''; ?>">
                <a class="nav-link <?php echo $isAdminActive ? '' : 'collapsed'; ?>" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseAdmin"
                    aria-expanded="<?php echo $isAdminActive ? 'true' : 'false'; ?>" aria-controls="collapseAdmin">
                    <i class="fas fa-fw fa-cogs"></i>
                    <span><?php echo __('admin_tools'); ?></span>
                </a>
                <div id="collapseAdmin" class="collapse <?php echo $isAdminActive ? 'show' : ''; ?>" aria-labelledby="headingAdmin" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'users/view_users.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>users/view_users.php"><?php echo __('user_management'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'admin/audit_logs.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/audit_logs.php"><?php echo __('audit_logs'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'tax_fee_admin.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>tax_fee_admin.php"><?php echo __('tax_fee_config'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'settings/backup.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>settings/backup.php"><?php echo __('database_backup'); ?></a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'vendor_payment_requests.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>vendor_payment_requests.php">
                            <i class="fas fa-money-check-alt fa-sm"></i> Vendor Payments
                        </a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'pre_payment.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>pre_payment.php">
                            <i class="fas fa-receipt fa-sm"></i> Customer Prepayments
                        </a>
                    </div>
                </div>
            </li>
            <?php
endif; ?>

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
                    <?php if (Auth::isLoggedIn()):
    $current_user = Auth::getCurrentUser();

    // Fetch Notifications
    require_once dirname(__DIR__) . '/models/Notification.php';
    $db = (new Database())->getConnection();
    $notification = new Notification($db);
    $unread_count = $notification->countUnread($current_user['id']);
    $recent_notifs = $notification->getRecent($current_user['id']);
?>
                    
                    <!-- Right Side: Toggles and User Profile -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Dark Mode Toggle -->
                        <li class="nav-item mx-1">
                            <a class="nav-link" href="#" onclick="toggleTheme(); return false;" title="Toggle Dark Mode">
                                <i class="fas fa-moon" id="darkModeIcon"></i>
                            </a>
                        </li>
                        <!-- Install PWA Button (Hidden by default, shown via JS) -->
                        <li class="nav-item mx-1 d-none" id="installPwaContainer">
                            <a class="nav-link text-primary" href="#" id="installPwaBtn" title="Install App">
                                <i class="fas fa-download"></i> <span class="d-none d-sm-inline ms-1 font-weight-bold">Install</span>
                            </a>
                        </li>
                        <script>
                            let deferredPrompt;
                            window.addEventListener('beforeinstallprompt', (e) => {
                                // Prevent the mini-infobar from appearing on mobile
                                e.preventDefault();
                                deferredPrompt = e;
                                // Update UI notify the user they can install the PWA
                                document.getElementById('installPwaContainer').classList.remove('d-none');
                            });

                            document.getElementById('installPwaBtn')?.addEventListener('click', async (e) => {
                                e.preventDefault();
                                if (deferredPrompt) {
                                    // Show the install prompt
                                    deferredPrompt.prompt();
                                    // Wait for the user to respond to the prompt
                                    const { outcome } = await deferredPrompt.userChoice;
                                    if (outcome === 'accepted') {
                                        console.log('User accepted the install prompt');
                                        document.getElementById('installPwaContainer').classList.add('d-none');
                                    }
                                    deferredPrompt = null;
                                }
                            });
                            window.addEventListener('appinstalled', (evt) => {
                                document.getElementById('installPwaContainer').classList.add('d-none');
                            });
                        </script>

                        <!-- Language Switcher -->
                        <li class="nav-item dropdown no-arrow mx-1">
                             <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-globe"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="langDropdown">
                                <a class="dropdown-item <?php echo get_current_lang() == 'en' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>language_switch.php?lang=en">English</a>
                                <a class="dropdown-item <?php echo get_current_lang() == 'ja' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>language_switch.php?lang=ja">日本語</a>
                            </div>
                        </li>

                        <!-- Notifications Dropdown -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw" style="color: #87CEEB;"></i>
                                <!-- Counter - Alerts -->
                                <span class="badge badge-counter" id="alertBadge" style="background-color: #fc0915ff; color: #fff; position: relative; top: -8px; right: 0px; font-size: 0.65rem; padding: 3px 5px; display: <?php echo $unread_count > 0 ? 'inline-block' : 'none'; ?>;">
                                    <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                                </span>
                            </a>
                            <!-- Dropdown - Alerts -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-end shadow animated--grow-in"
                                aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header bg-primary text-white border-0 py-2">
                                    <?php echo __('alerts_center'); ?>
                                </h6>
                                <div id="alertList">
                                    <?php if ($recent_notifs->rowCount() > 0): ?>
                                        <?php while ($notif = $recent_notifs->fetch(PDO::FETCH_ASSOC)): ?>
                                        <a class="dropdown-item d-flex align-items-center <?php echo $notif['is_read'] ? '' : 'bg-light'; ?>" 
                                           href="<?php echo $notif['link'] ? BASE_URL . $notif['link'] : '#'; ?>"
                                           data-notification-id="<?php echo $notif['id']; ?>"
                                           data-is-read="<?php echo $notif['is_read']; ?>"
                                           onclick="markNotificationAsRead(event, this)">
                                            <div class="mr-3">
                                                <div class="icon-circle bg-<?php echo $notif['type'] == 'info' ? 'primary' : ($notif['type'] == 'success' ? 'success' : 'warning'); ?> text-white p-2 rounded-circle">
                                                    <i class="fas fa-<?php echo $notif['type'] == 'info' ? 'file-alt' : 'exclamation-triangle'; ?>"></i>
                                                </div>
                                            </div>
                                            <div class="ms-2">
                                                <div class="small text-gray-500"><?php echo date('F j, Y', strtotime($notif['created_at'])); ?></div>
                                                <span class="font-weight-<?php echo $notif['is_read'] ? 'normal' : 'bold'; ?>"><?php echo htmlspecialchars($notif['message']); ?></span>
                                            </div>
                                        </a>
                                        <?php
        endwhile; ?>
                                    <?php
    else: ?>
                                        <a class="dropdown-item text-center small text-gray-500" href="#"><?php echo __('no_new_alerts'); ?></a>
                                    <?php
    endif; ?>
                                </div>
                                <a class="dropdown-item text-center small text-gray-500 py-2 bg-light border-top" href="<?php echo BASE_URL; ?>notifications.php"><?php echo __('show_all_alerts'); ?></a>
                            </div>
                        </li>


                        <li class="nav-item dropdown no-arrow">
                            
                            <!-- Notification Poller Script -->
                            <script>
                            // Function to mark notification as read
                            function markNotificationAsRead(event, element) {
                                const notificationId = element.getAttribute('data-notification-id');
                                const isRead = element.getAttribute('data-is-read');
                                
                                // Only mark as read if it's unread
                                if (isRead == '0') {
                                    // Send AJAX request to mark as read
                                    fetch('<?php echo BASE_URL; ?>api/mark_notification_read.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify({
                                            notification_id: notificationId
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            // Update badge count
                                            const badge = document.getElementById('alertBadge');
                                            if (data.unread_count > 0) {
                                                badge.style.display = 'inline-block';
                                                badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                                            } else {
                                                badge.style.display = 'none';
                                            }
                                            
                                            // Update the element's appearance
                                            element.classList.remove('bg-light');
                                            element.setAttribute('data-is-read', '1');
                                            const textSpan = element.querySelector('span');
                                            if (textSpan) {
                                                textSpan.classList.remove('font-weight-bold');
                                                textSpan.classList.add('font-weight-normal');
                                            }
                                        }
                                    })
                                    .catch(err => console.error('Error marking notification as read:', err));
                                }
                                
                                // Allow the link to navigate
                                return true;
                            }
                            
                            document.addEventListener('DOMContentLoaded', function() {
                                // Track notifications shown in this session
                                const sessionNotifications = new Set(JSON.parse(sessionStorage.getItem('shownNotifications') || '[]'));
                                
                                function fetchNotifications() {
                                    fetch('<?php echo BASE_URL; ?>api/get_notifications.php')
                                        .then(response => {
                                            // Check if response is OK (2xx status)
                                            if (!response.ok) {
                                                // If unauthorized (401) or redirected to login page, redirect user
                                                if (response.status === 401) {
                                                    window.location.href = '<?php echo BASE_URL; ?>login.php';
                                                    return;
                                                }
                                                throw new Error(`HTTP error! status: ${response.status}`);
                                            }
                                            
                                            // Check content type to see if it's JSON
                                            const contentType = response.headers.get('content-type');
                                            if (!contentType || !contentType.includes('application/json')) {
                                                // Likely redirected to login page
                                                window.location.href = '<?php echo BASE_URL; ?>login.php';
                                                return;
                                            }
                                            
                                            return response.json();
                                        })
                                        .then(data => {
                                            if (!data) return;
                                            
                                            // Update Badge
                                            const badge = document.getElementById('alertBadge');
                                            if (data.count !== undefined) {
                                                badge.style.display = data.count > 0 ? 'inline-block' : 'none';
                                                badge.textContent = data.count > 9 ? '9+' : data.count;
                                            }

                                            // Update List contents if needed
                                            const list = document.getElementById('alertList');
                                            if (data.notifications && data.notifications.length > 0) {
                                                let html = '';
                                                data.notifications.forEach(notif => {
                                                    const bgClass = notif.is_read == 1 ? '' : 'bg-light';
                                                    const fontWeight = notif.is_read == 1 ? 'font-weight-normal' : 'font-weight-bold';
                                                    const icon = notif.type === 'info' ? 'file-alt' : 'exclamation-triangle';
                                                    const iconBg = notif.type === 'info' ? 'bg-primary' : (notif.type === 'success' ? 'bg-success' : 'bg-warning');
                                                    
                                                    html += `
                                                    <a class="dropdown-item d-flex align-items-center ${bgClass}" 
                                                       href="${notif.link}"
                                                       data-notification-id="${notif.id}"
                                                       data-is-read="${notif.is_read}"
                                                       onclick="markNotificationAsRead(event, this)">
                                                        <div class="mr-3">
                                                            <div class="icon-circle ${iconBg} text-white p-2 rounded-circle">
                                                                <i class="fas fa-${icon}"></i>
                                                            </div>
                                                        </div>
                                                        <div class="ms-2">
                                                            <div class="small text-gray-500">${notif.date}</div>
                                                            <span class="${fontWeight}">${notif.message}</span>
                                                        </div>
                                                    </a>`;
                                                });
                                                list.innerHTML = html;
                                            }
                                        })
                                        .catch(err => {
                                            console.warn('Silent notification update failure:', err);
                                        });
                                }

                                // Poll every hour
                                setInterval(fetchNotifications, 3600000); // 1 hour in milliseconds
                                
                                // Also check on page focus/visibility change to catch updates
                                document.addEventListener('visibilitychange', function() {
                                    if (!document.hidden) {
                                        fetchNotifications();
                                    }
                                });
                                
                                // Check when window regains focus
                                window.addEventListener('focus', fetchNotifications);
                            });
                            </script>
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img class="img-profile rounded-circle"
                                    src="https://ui-avatars.com/api/?name=<?php echo urlencode($current_user['full_name']); ?>&background=4e73df&color=fff&size=128">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <div class="dropdown-header">
                                    <strong><?php echo htmlspecialchars($current_user['full_name']); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars($current_user['email']); ?></div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    <?php echo __('profile'); ?>
                                </a>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>settings.php">
                                    <i class="fas fa-cog fa-sm fa-fw mr-2 text-gray-400"></i>
                                    <?php echo __('settings'); ?>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    <?php echo __('logout'); ?>
                                </a>
                            </div>
                        </li>
                    </ul>
                    <?php
else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">
                                <i class="fas fa-sign-in-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                <?php echo __('login'); ?>
                            </a>
                        </li>
                    </ul>
                    <?php
endif; ?>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">