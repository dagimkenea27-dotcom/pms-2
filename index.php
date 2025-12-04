<?php
// Start session and check authentication
require_once "config/auth.php";
require_once "config/database.php";
require_once "models/User.php";

Auth::checkAuthAndPreventCache();

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

require_once "includes/header.php";
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
    </a>
</div>

<!-- Content Row -->
<div class="row">
    <!-- Total Products Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Products</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_products['count']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-box fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Low Stock Alerts</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $low_stock['count']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Out of Stock Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Out of Stock</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $out_of_stock['count']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Value Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Inventory Value</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_value['total_value'] ?? 0, 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row">
    <!-- Quick Actions -->
    <div class="col-lg-6 mb-4">
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <a href="products/add_product.php" class="btn quick-action-btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Add Product
                        </a>
                        <a href="products/view_products.php" class="btn quick-action-btn btn-success">
                            <i class="fas fa-list mr-2"></i>View Products
                        </a>
                        <a href="price.php" class="btn quick-action-btn btn-primary">
                            <i class="fas fa-calculator mr-2"></i>price Calculator
                        </a>
                        <a href="products/stock_in.php" class="btn quick-action-btn btn-warning">
                            <i class="fas fa-download mr-2"></i>Stock In
                        </a>
                        <a href="products/stock_out.php" class="btn quick-action-btn btn-danger">
                            <i class="fas fa-upload mr-2"></i>Stock Out
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Stock Movements</h6>
            </div>
            <div class="card-body">
                <?php if ($recent_movements && count($recent_movements) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_movements as $movement): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($movement['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($movement['product_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $movement['movement_type'] == 'IN' ? 'success' : 'danger'; ?>">
                                            <?php echo $movement['movement_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $movement['quantity']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No recent stock movements.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Charts Column -->
    <div class="col-lg-6 mb-4">
        <!-- Stock Overview Chart -->
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Stock Overview</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="stockOverviewChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Stock Status Chart -->
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Stock Status Distribution</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <canvas id="stockStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>

<?php
// Prepare data for charts
// 1. Stock Status Distribution
$stock_status_data = [
    'Out of Stock' => $out_of_stock['count'],
    'Low Stock' => $low_stock['count'],
    'In Stock' => $total_products['count'] - $out_of_stock['count'] - $low_stock['count']
];

// 2. Recent Stock Movements (for Overview - simplified to last 7 days)
$dates = [];
$ins = [];
$outs = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M j', strtotime($date));
    
    // Stock In
    $stmt = $db->prepare("SELECT SUM(quantity) as count FROM stock_movements WHERE movement_type = 'IN' AND DATE(created_at) = ?");
    $stmt->execute([$date]);
    $ins[] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Stock Out
    $stmt = $db->prepare("SELECT SUM(quantity) as count FROM stock_movements WHERE movement_type = 'OUT' AND DATE(created_at) = ?");
    $stmt->execute([$date]);
    $outs[] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
}
?>

<script>
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.font.family = 'Nunito, -apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.color = '#858796';

    // Stock Overview Chart
    var ctx = document.getElementById("stockOverviewChart");
    if (ctx) {
        var myLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: "Stock In",
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: <?php echo json_encode($ins); ?>,
                }, {
                    label: "Stock Out",
                    lineTension: 0.3,
                    backgroundColor: "rgba(231, 74, 59, 0.05)",
                    borderColor: "rgba(231, 74, 59, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(231, 74, 59, 1)",
                    pointBorderColor: "rgba(231, 74, 59, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(231, 74, 59, 1)",
                    pointHoverBorderColor: "rgba(231, 74, 59, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: <?php echo json_encode($outs); ?>,
                }],
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    },
                    y: {
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    },
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        titleMarginBottom: 10,
                        titleColor: '#6e707e',
                        titleFont: {
                            size: 14,
                        },
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        intersect: false,
                        mode: 'index',
                        caretPadding: 10,
                    }
                }
            }
        });
    }

    // Stock Status Pie Chart
    var ctxPie = document.getElementById("stockStatusChart");
    if (ctxPie) {
        var myPieChart = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ["In Stock", "Low Stock", "Out of Stock"],
                datasets: [{
                    data: [
                        <?php echo $stock_status_data['In Stock']; ?>, 
                        <?php echo $stock_status_data['Low Stock']; ?>, 
                        <?php echo $stock_status_data['Out of Stock']; ?>
                    ],
                    backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                    hoverBackgroundColor: ['#17a673', '#dda20a', '#be2617'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                },
                legend: {
                    display: false
                },
                cutout: '80%',
            },
        });
    }
</script>