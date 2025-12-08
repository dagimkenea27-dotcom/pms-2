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
<style>
    .quick-action-btn {
        margin: 5px;
        min-width: 150px;
    }
    
    .dashboard-card {
        border-radius: 0.5rem;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .dashboard-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }
    
    .border-left-danger {
        border-left: 0.25rem solid #e74a3b !important;
    }
    
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
</style>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    <div class="dropdown">
        <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm dropdown-toggle" type="button" id="reportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
        </button>
        <ul class="dropdown-menu" aria-labelledby="reportDropdown">
            <li><a class="dropdown-item" href="reports/stock_valuation.php"><i class="fas fa-file-invoice-dollar mr-2"></i>Stock Valuation</a></li>
            <li><a class="dropdown-item" href="reports/stock_movement.php"><i class="fas fa-exchange-alt mr-2"></i>Stock Movement</a></li>
            <li><a class="dropdown-item" href="reports/low_stock.php"><i class="fas fa-exclamation-triangle mr-2"></i>Low Stock Report</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="products/export_products.php"><i class="fas fa-file-export mr-2"></i>Export Products</a></li>
        </ul>
    </div>
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
    <div class="col-lg-6 mb-4">

        <!-- Top Selling Products -->
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Top Selling Products (Last 30 Days)</h6>
            </div>
            <div class="card-body">
                <p class="text-muted text-center">Data visualization features temporarily disabled.</p>
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
        <!-- Daily Stock Trends Chart -->
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Daily Stock Trends (Last 30 Days)</h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="chartToggle" data-bs-toggle="dropdown" aria-expanded="false">
                        View Options
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="chartToggle">
                        <li><a class="dropdown-item" href="#" onclick="showChart('daily')">Daily Trends</a></li>
                        <li><a class="dropdown-item" href="#" onclick="showChart('monthly')">Monthly Trends</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="dailyStockChart"></canvas>
                    <canvas id="monthlyStockChart" style="display: none;"></canvas>
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

        <!-- Supplier Performance -->
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Top Suppliers (Last 30 Days)</h6>
            </div>
            <div class="card-body">
                <p class="text-muted text-center">Data visualization features temporarily disabled.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>

<script>
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.font.family = 'Nunito, -apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.color = '#858796';

    // Function to switch between daily and monthly charts
    function showChart(type) {
        const dailyChart = document.getElementById('dailyStockChart');
        const monthlyChart = document.getElementById('monthlyStockChart');
        
        if (type === 'daily') {
            dailyChart.style.display = 'block';
            monthlyChart.style.display = 'none';
        } else {
            dailyChart.style.display = 'none';
            monthlyChart.style.display = 'block';
        }
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
                        <?php echo $total_products['count'] - $out_of_stock['count'] - $low_stock['count']; ?>, 
                        <?php echo $low_stock['count']; ?>, 
                        <?php echo $out_of_stock['count']; ?>
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