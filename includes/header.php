<?php
require_once dirname(__DIR__) . '/config/paths.php';
require_once CONFIG_PATH . 'database.php';
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
    <script src="<?php echo BASE_URL; ?>assets/js/theme.js"></script>
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

            <!-- Nav Item - Pricing -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'price') !== false ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePricing"
                    aria-expanded="true" aria-controls="collapsePricing">
                    <i class="fas fa-fw fa-tag"></i>
                    <span>Pricing</span>
                </a>
                <div id="collapsePricing" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'price') !== false ? 'show' : ''; ?>" aria-labelledby="headingPricing" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>price.php">Price Calculator</a>
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>price_analytics.php">Price Analytics</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Catalog -->
            <li class="nav-item <?php echo ((strpos($_SERVER['REQUEST_URI'], 'products/') !== false && strpos($_SERVER['REQUEST_URI'], 'stock_') === false) || strpos($_SERVER['REQUEST_URI'], 'categories/') !== false || strpos($_SERVER['REQUEST_URI'], 'brands/') !== false) ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCatalog"
                    aria-expanded="true" aria-controls="collapseCatalog">
                    <i class="fas fa-fw fa-box-open"></i>
                    <span>Catalog</span>
                </a>
                <div id="collapseCatalog" class="collapse <?php echo ((strpos($_SERVER['REQUEST_URI'], 'products/') !== false && strpos($_SERVER['REQUEST_URI'], 'stock_') === false) || strpos($_SERVER['REQUEST_URI'], 'categories/') !== false || strpos($_SERVER['REQUEST_URI'], 'brands/') !== false) ? 'show' : ''; ?>" aria-labelledby="headingCatalog" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>products/view_products.php">Products</a>
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>categories/">Categories</a>
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>brands/">Brands</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Stock Operations
            </div>

            <!-- Nav Item - Stock Movements -->
            <li class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'products/stock_in') !== false || strpos($_SERVER['REQUEST_URI'], 'products/stock_out') !== false) ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseStock"
                    aria-expanded="true" aria-controls="collapseStock">
                    <i class="fas fa-fw fa-exchange-alt"></i>
                    <span>Stock Operations</span>
                </a>
                <div id="collapseStock" class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'products/stock_in') !== false || strpos($_SERVER['REQUEST_URI'], 'products/stock_out') !== false) ? 'show' : ''; ?>" aria-labelledby="headingStock" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>products/stock_in.php">Stock In</a>
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>products/stock_out.php">Stock Out</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Suppliers -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'suppliers/') !== false ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSuppliers"
                    aria-expanded="true" aria-controls="collapseSuppliers">
                    <i class="fas fa-fw fa-truck"></i>
                    <span>Suppliers</span>
                </a>
                <div id="collapseSuppliers" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'suppliers/') !== false ? 'show' : ''; ?>" aria-labelledby="headingSuppliers" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>suppliers/view_suppliers.php">View Suppliers</a>
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>suppliers/add_supplier.php">Add Supplier</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Drivers & Fleet -->
            <li class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'drivers/') !== false || strpos($_SERVER['REQUEST_URI'], 'vehicles/') !== false) ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseFleet"
                    aria-expanded="true" aria-controls="collapseFleet">
                    <i class="fas fa-fw fa-users-cog"></i>
                    <span>Driver & Fleet</span>
                </a>
                <div id="collapseFleet" class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'drivers/') !== false || strpos($_SERVER['REQUEST_URI'], 'vehicles/') !== false) ? 'show' : ''; ?>" aria-labelledby="headingFleet" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>drivers/index.php">Drivers</a>
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>vehicles/index.php">Vehicles</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Route Optimizer -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'routes/') !== false ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseRoutes"
                    aria-expanded="true" aria-controls="collapseRoutes">
                    <i class="fas fa-fw fa-map-marked-alt"></i>
                    <span>Route Management</span>
                </a>
                <div id="collapseRoutes" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'routes/') !== false ? 'show' : ''; ?>" aria-labelledby="headingRoutes" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'routes/index.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>routes/index.php">Optimize Routes</a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'routes/manage.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>routes/manage.php">Saved Routes</a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'routes/templates.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>routes/templates.php">Templates</a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'customers/address_book.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>customers/address_book.php">Address Book</a>
                        <a class="collapse-item <?php echo strpos($_SERVER['REQUEST_URI'], 'settings/route_preferences.php') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>settings/route_preferences.php">Preferences</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Reports & Analytics
            </div>

            <!-- Nav Item - Reports -->
            <!-- Nav Item - Reports -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/stock_movement.php') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock_movement.php">
                    <i class="fas fa-fw fa-chart-line"></i>
                    <span>Stock Movement</span>
                </a>
            </li>

            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/low_stock.php') !== false ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/low_stock.php">
                    <i class="fas fa-fw fa-exclamation-triangle"></i>
                    <span>Low Stock Items</span>
                </a>
            </li>

            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports/stock_valuation.php') !== false ? 'active' : ''; ?>">
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

            <!-- Nav Item - Admin Tools -->
            <?php if (Auth::isLoggedIn() && Auth::getCurrentUser()['role'] == 'admin'): ?>
            <li class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'users/') !== false || strpos($_SERVER['REQUEST_URI'], 'admin/') !== false || strpos($_SERVER['REQUEST_URI'], 'tax_fee_admin.php') !== false) ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseAdmin"
                    aria-expanded="true" aria-controls="collapseAdmin">
                    <i class="fas fa-fw fa-cogs"></i>
                    <span>Admin Tools</span>
                </a>
                <div id="collapseAdmin" class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'users/') !== false || strpos($_SERVER['REQUEST_URI'], 'admin/') !== false || strpos($_SERVER['REQUEST_URI'], 'tax_fee_admin.php') !== false) ? 'show' : ''; ?>" aria-labelledby="headingAdmin" data-parent="#accordionSidebar">
                    <div class="py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>users/view_users.php">User Management</a>
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>admin/audit_logs.php">Audit Logs</a>
                        <a class="collapse-item" href="<?php echo BASE_URL; ?>tax_fee_admin.php">Tax/Fee Config</a>
                    </div>
                </div>
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
                    <?php if (Auth::isLoggedIn()): 
                        $current_user = Auth::getCurrentUser();
                        
                        // Fetch Notifications
                        require_once dirname(__DIR__) . '/models/Notification.php';
                        $db = (new Database())->getConnection();
                        $notification = new Notification($db);
                        $unread_count = $notification->countUnread($current_user['id']);
                        $recent_notifs = $notification->getRecent($current_user['id']);
                    ?>
                    
                    <!-- Left Side: Notifications -->
                    <ul class="navbar-nav me-auto">
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
                            <div class="dropdown-list dropdown-menu dropdown-menu-start shadow animated--grow-in"
                                aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header bg-primary text-white border-0 py-2">
                                    Alerts Center
                                </h6>
                                <div id="alertList">
                                    <?php if ($recent_notifs->rowCount() > 0): ?>
                                        <?php while ($notif = $recent_notifs->fetch(PDO::FETCH_ASSOC)): ?>
                                        <a class="dropdown-item d-flex align-items-center <?php echo $notif['is_read'] ? '' : 'bg-light'; ?>" 
                                           href="<?php echo $notif['link'] ? BASE_URL . $notif['link'] : '#'; ?>">
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
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <a class="dropdown-item text-center small text-gray-500" href="#">No new alerts</a>
                                    <?php endif; ?>
                                </div>
                                <a class="dropdown-item text-center small text-gray-500 py-2 bg-light border-top" href="<?php echo BASE_URL; ?>notifications.php">Show All Alerts</a>
                            </div>
                        </li>
                    </ul>

                    <!-- Right Side: User Profile -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown no-arrow">
                            
                            <!-- Notification Poller Script -->
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                function fetchNotifications() {
                                    fetch('<?php echo BASE_URL; ?>api/get_notifications.php')
                                        .then(response => response.json())
                                        .then(data => {
                                            // Update Badge
                                            const badge = document.getElementById('alertBadge');
                                            if (data.count > 0) {
                                                badge.style.display = 'inline-block';
                                                badge.textContent = data.count > 9 ? '9+' : data.count;
                                            } else {
                                                badge.style.display = 'none';
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
                                                    <a class="dropdown-item d-flex align-items-center ${bgClass}" href="${notif.link}">
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
                                        .catch(err => console.error('Error fetching notifications:', err));
                                }

                                // Poll every 2 seconds
                                setInterval(fetchNotifications, 2000);
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
                                    Profile
                                </a>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>settings.php">
                                    <i class="fas fa-cog fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                    <?php else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">
                                <i class="fas fa-sign-in-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                Login
                            </a>
                        </li>
                    </ul>
                    <?php endif; ?>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">