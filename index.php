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
$low_stock_query = "SELECT COUNT(*) as count FROM products WHERE quantity > 0 AND quantity <= min_stock";
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
    SELECT DISTINCT sm.id, sm.created_at, sm.movement_type, sm.quantity, p.name as product_name 
    FROM stock_movements sm 
    JOIN products p ON sm.product_id = p.id 
    ORDER BY sm.created_at DESC 
    LIMIT 10";
$recent_movements_stmt = $db->prepare($recent_movements_query);
$recent_movements_stmt->execute();
$recent_movements = $recent_movements_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Daily Stock Trends (Last 30 Days)
$daily_trend_query = "
    SELECT DATE(created_at) as date, 
           SUM(CASE WHEN movement_type = 'IN' THEN quantity ELSE 0 END) as stock_in,
           SUM(CASE WHEN movement_type = 'OUT' THEN quantity ELSE 0 END) as stock_out
    FROM stock_movements 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
$daily_trend_stmt = $db->query($daily_trend_query);
$daily_trends = $daily_trend_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for JS
$chart_labels = [];
$chart_data_in = [];
$chart_data_out = [];
foreach ($daily_trends as $day) {
    $chart_labels[] = date('M j', strtotime($day['date']));
    $chart_data_in[] = $day['stock_in'];
    $chart_data_out[] = $day['stock_out'];
}

// Get Top Selling Products (Last 30 Days by Revenue)
$top_products_query = "
    SELECT p.name, SUM(sm.quantity) as total_sold, SUM(sm.quantity * COALESCE(pv.price, p.price)) as total_revenue
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    LEFT JOIN product_variants pv ON sm.variant_id = pv.id
    WHERE sm.movement_type = 'OUT' 
    AND sm.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY sm.product_id, sm.variant_id
    ORDER BY total_revenue DESC
    LIMIT 5";
$top_products_stmt = $db->query($top_products_query);
$top_products = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Top Suppliers (Last 30 Days by Volume In)
$top_suppliers_query = "
    SELECT s.name, SUM(sm.quantity) as total_supplied
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    JOIN suppliers s ON p.supplier_id = s.id
    WHERE sm.movement_type = 'IN'
    AND sm.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY s.id
    ORDER BY total_supplied DESC
    LIMIT 5";
$top_suppliers_stmt = $db->query($top_suppliers_query);
$top_suppliers = $top_suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total sales value (Current Month) - Deducts Returns
$sales_month_query = "
    SELECT (
        SELECT SUM(sm.quantity * COALESCE(pv.price, p.price))
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN product_variants pv ON sm.variant_id = pv.id
        WHERE sm.movement_type = 'OUT' 
        AND (sm.reason = 'Sale' OR sm.reason IS NULL OR sm.reason = '')
        AND sm.created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
    ) - COALESCE((
        SELECT SUM(sm.quantity * COALESCE(pv.price, p.price))
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN product_variants pv ON sm.variant_id = pv.id
        WHERE sm.movement_type = 'IN' 
        AND sm.reason = 'Return'
        AND sm.created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
    ), 0) as net_sales";
$sales_month_stmt = $db->prepare($sales_month_query);
$sales_month_stmt->execute();
$sales_month_val = $sales_month_stmt->fetch(PDO::FETCH_ASSOC)['net_sales'] ?? 0;

// Get today's sales - Deducts Returns
$sales_today_query = "
    SELECT (
        SELECT SUM(sm.quantity * COALESCE(pv.price, p.price))
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN product_variants pv ON sm.variant_id = pv.id
        WHERE sm.movement_type = 'OUT' 
        AND (sm.reason = 'Sale' OR sm.reason IS NULL OR sm.reason = '')
        AND DATE(sm.created_at) = CURDATE()
    ) - COALESCE((
        SELECT SUM(sm.quantity * COALESCE(pv.price, p.price))
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN product_variants pv ON sm.variant_id = pv.id
        WHERE sm.movement_type = 'IN' 
        AND sm.reason = 'Return'
        AND DATE(sm.created_at) = CURDATE()
    ), 0) as net_sales";
$sales_today_stmt = $db->prepare($sales_today_query);
$sales_today_stmt->execute();
$sales_today_val = $sales_today_stmt->fetch(PDO::FETCH_ASSOC)['net_sales'] ?? 0;

// Get Gross Profit (Current Month) - Deducts Returns
$profit_month_query = "
    SELECT (
        SELECT SUM(sm.quantity * (COALESCE(pv.price, p.price) - p.cost_price))
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN product_variants pv ON sm.variant_id = pv.id
        WHERE sm.movement_type = 'OUT' 
        AND (sm.reason = 'Sale' OR sm.reason IS NULL OR sm.reason = '')
        AND sm.created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
    ) - COALESCE((
        SELECT SUM(sm.quantity * (COALESCE(pv.price, p.price) - p.cost_price))
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN product_variants pv ON sm.variant_id = pv.id
        WHERE sm.movement_type = 'IN' 
        AND sm.reason = 'Return'
        AND sm.created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
    ), 0) as net_profit";
$profit_month_stmt = $db->prepare($profit_month_query);
$profit_month_stmt->execute();
$profit_month_val = $profit_month_stmt->fetch(PDO::FETCH_ASSOC)['net_profit'] ?? 0;

// Get current user info
$current_user = Auth::getCurrentUser();

// Get Predictive Stockout Alerts (New Phase 1)
require_once "models/InventoryForecaster.php";
$forecaster = new InventoryForecaster($db);
if ($current_user['role'] === 'admin') {
    $forecaster->updateAllForecasts();
}
$predicted_stockout_query = "SELECT COUNT(*) as count FROM inventory_forecasts WHERE forecast_days_remaining <= 7";
$predicted_stockout_stmt = $db->query($predicted_stockout_query);
$predicted_stockout_count = $predicted_stockout_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

// Get Financial Summary (New Phase 3)
require_once "models/Finance.php";
$finance = new Finance($db);
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-d');
$fin_summary = $finance->getNetProfit($current_month_start, $current_month_end);

require_once "includes/header.php";
?>
<style>
    /* Professional Dashboard Design System */
    :root {
        --dash-primary: #4e73df;
        --dash-success: #1cc88a;
        --dash-info: #36b9cc;
        --dash-warning: #f6c23e;
        --dash-danger: #e74a3b;
        --dash-purple: #6f42c1;
        --glass-bg: rgba(255, 255, 255, 0.95);
        --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }

    .kpi-card {
        border: none;
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        background: var(--glass-bg);
        box-shadow: var(--card-shadow);
    }

    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }

    .kpi-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.75rem;
    }

    .trend-indicator {
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 600;
    }

    .trend-up { background: rgba(28, 200, 138, 0.1); color: var(--dash-success); }
    .trend-down { background: rgba(231, 74, 59, 0.1); color: var(--dash-danger); }

    .quick-action-strip {
        background: #f8f9fc;
        padding: 12px;
        border-radius: 12px;
        margin-bottom: 25px;
        border: 1px solid #e3e6f0;
    }

    .action-pill {
        padding: 6px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.2s;
        border: 1px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none !important;
    }

    .action-pill:hover {
        transform: scale(1.02);
    }

    .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
    .border-left-secondary { border-left: 0.25rem solid #858796 !important; }
    .border-left-danger { border-left: 0.25rem solid #e74a3b !important; }
</style>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo __('dashboard'); ?></h1>
    <div class="dropdown">
        <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm dropdown-toggle" type="button" id="reportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-download fa-sm text-white-50"></i> <?php echo __('generate_report'); ?>
        </button>
        <ul class="dropdown-menu" aria-labelledby="reportDropdown">
            <li><a class="dropdown-item" href="reports/finance.php"><i class="fas fa-wallet mr-2"></i>Net Profit (P&L)</a></li>
            <li><a class="dropdown-item" href="reports/stock_valuation.php"><i class="fas fa-file-invoice-dollar mr-2"></i><?php echo __('stock_valuation'); ?></a></li>
            <li><a class="dropdown-item" href="reports/stock_movement.php"><i class="fas fa-exchange-alt mr-2"></i><?php echo __('stock_movement'); ?></a></li>
            <li><a class="dropdown-item" href="reports/low_stock.php"><i class="fas fa-exclamation-triangle mr-2"></i><?php echo __('low_stock_items'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="products/export_products.php"><i class="fas fa-file-export mr-2"></i><?php echo __('export_products'); ?></a></li>
        </ul>
    </div>
</div>

<!-- Quick Actions Strip -->
<div class="quick-action-strip d-flex flex-wrap gap-3 align-items-center shadow-sm">
    <span class="text-xs font-weight-bold text-uppercase text-muted mr-2">Quick Actions:</span>
    <a href="products/add_product.php" class="action-pill bg-primary text-white">
        <i class="fas fa-plus"></i> New Product
    </a>
    <a href="#" class="action-pill bg-white text-dark shadow-sm border" data-bs-toggle="modal" data-bs-target="#barcodeScannerModal">
        <i class="fas fa-barcode"></i> Scan Stock
    </a>
    <a href="routes/index.php" class="action-pill bg-info text-white">
        <i class="fas fa-truck"></i> Plan Delivery
    </a>
    <a href="reports/marketing_intelligence.php" class="action-pill bg-purple text-white" style="background-color: #6f42c1;">
        <i class="fas fa-magic"></i> AI Insights
    </a>
</div>

<!-- Primary KPIs Row -->
<div class="row">
    <!-- Revenue -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card kpi-card h-100 p-3">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="kpi-icon-box bg-success text-white shadow-sm">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="text-xs font-weight-bold text-muted text-uppercase"><?php echo __('today_sales'); ?></div>
                    <div class="h4 font-weight-bold text-gray-800 mb-0">$<?php echo number_format($sales_today_val, 2); ?></div>
                </div>
                <div class="text-right">
                    <span class="trend-indicator trend-up"><i class="fas fa-arrow-up"></i> Live</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Profit -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card kpi-card h-100 p-3">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="kpi-icon-box bg-info text-white shadow-sm">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="text-xs font-weight-bold text-muted text-uppercase">Monthly Net Profit</div>
                    <div class="h4 font-weight-bold text-gray-800 mb-0">$<?php echo number_format($fin_summary['net_profit'], 2); ?></div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-muted"><?php echo date('F'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock -->
    <div class="col-xl-3 col-md-6 mb-4">
        <a href="products/view_products.php?filter=low_stock" class="text-decoration-none">
            <div class="card kpi-card h-100 p-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="kpi-icon-box bg-warning text-white shadow-sm">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="text-xs font-weight-bold text-muted text-uppercase"><?php echo __('low_stock_alerts'); ?></div>
                        <div class="h4 font-weight-bold text-gray-800 mb-0"><?php echo $low_stock['count']; ?></div>
                    </div>
                    <div class="text-right">
                        <?php if ($low_stock['count'] > 0): ?>
                            <span class="trend-indicator trend-down">Action Required</span>
                        <?php
endif; ?>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- AI Alerts -->
    <div class="col-xl-3 col-md-6 mb-4">
        <a href="reports/marketing_intelligence.php" class="text-decoration-none">
            <div class="card kpi-card h-100 p-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="d-flex justify-content-between text-white">
                    <div>
                        <div class="kpi-icon-box bg-white text-primary shadow-sm">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="text-xs font-weight-bold text-uppercase opacity-75">Predicted Stockouts</div>
                        <div class="h4 font-weight-bold mb-0"><?php echo $predicted_stockout_count; ?></div>
                    </div>
                    <div class="text-right">
                        <i class="fas fa-bolt text-warning animate-pulse"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Secondary Metrics -->
<div class="row mb-4">
    <div class="col-xl-4 col-md-6 mb-2">
        <div class="d-flex align-items-center bg-white p-3 rounded shadow-sm border-left-primary">
            <div class="mr-3 p-3 bg-light rounded">
                <i class="fas fa-box text-primary"></i>
            </div>
            <div>
                <div class="text-xs text-muted text-uppercase"><?php echo __('total_products'); ?></div>
                <div class="h5 font-weight-bold mb-0"><?php echo $total_products['count']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-2">
        <div class="d-flex align-items-center bg-white p-3 rounded shadow-sm border-left-secondary">
            <div class="mr-3 p-3 bg-light rounded">
                <i class="fas fa-warehouse text-secondary"></i>
            </div>
            <div>
                <div class="text-xs text-muted text-uppercase"><?php echo __('inventory_value'); ?></div>
                <div class="h5 font-weight-bold mb-0">$<?php echo number_format($total_value['total_value'] ?? 0, 2); ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-2">
        <div class="d-flex align-items-center bg-white p-3 rounded shadow-sm border-left-danger">
            <div class="mr-3 p-3 bg-light rounded">
                <i class="fas fa-times-circle text-danger"></i>
            </div>
            <div>
                <div class="text-xs text-muted text-uppercase"><?php echo __('out_of_stock'); ?></div>
                <div class="h5 font-weight-bold mb-0"><?php echo $out_of_stock['count']; ?></div>
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
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('top_selling_products'); ?></h6>
            </div>
            <div class="card-body">
                <?php if ($top_products && count($top_products) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <thead>
                                <tr>
                                    <th><?php echo __('product'); ?></th>
                                    <th class="text-center"><?php echo __('quantity'); ?></th>
                                    <th class="text-end"><?php echo __('revenue'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $prod): ?>
                                <tr>
                                    <td><?php echo e($prod['name']); ?></td>
                                    <td class="text-center"><?php echo number_format($prod['total_sold']); ?></td>
                                    <td class="text-end font-weight-bold text-primary">$<?php echo number_format($prod['total_revenue'], 2); ?></td>
                                </tr>
                                <?php
    endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php
else: ?>
                    <p class="text-muted text-center py-3">No sales recorded yet.</p>
                <?php
endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('recent_movements'); ?></h6>
            </div>
            <div class="card-body">
                <?php if ($recent_movements && count($recent_movements) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><?php echo __('date'); ?></th>
                                    <th><?php echo __('product'); ?></th>
                                    <th><?php echo __('type'); ?></th>
                                    <th><?php echo __('quantity'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_movements as $movement): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($movement['created_at'])); ?></td>
                                    <td><?php echo e($movement['product_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $movement['movement_type'] == 'IN' ? 'success' : 'danger'; ?>">
                                            <?php echo e($movement['movement_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($movement['quantity']); ?></td>
                                </tr>
                                <?php
    endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php
else: ?>
                    <p class="text-muted text-center">No recent stock movements.</p>
                <?php
endif; ?>
            </div>
        </div>
    </div>

    <!-- Charts Column -->
    <div class="col-lg-6 mb-4">
        <!-- Daily Stock Trends Chart -->
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('daily_stock_trend'); ?></h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="chartToggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo __('view_options'); ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="chartToggle">
                        <li><a class="dropdown-item" href="#" onclick="showChart('daily')"><?php echo __('daily_trends'); ?></a></li>
                        <li><a class="dropdown-item" href="#" onclick="showChart('monthly')"><?php echo __('monthly_trends'); ?></a></li>
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
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('stock_valuation'); ?></h6>
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
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('top_suppliers_volume'); ?></h6>
            </div>
            <div class="card-body">
                <?php if ($top_suppliers && count($top_suppliers) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th class="text-end">Received (Qty)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_suppliers as $sup): ?>
                                <tr>
                                    <td><?php echo e($sup['name']); ?></td>
                                    <td class="text-end font-weight-bold text-success">+<?php echo number_format($sup['total_supplied']); ?></td>
                                </tr>
                                <?php
    endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php
else: ?>
                    <p class="text-muted text-center py-3">No stock received yet.</p>
                <?php
endif; ?>
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

    // Stock Trends Chart
    var ctxDaily = document.getElementById("dailyStockChart");
    if (ctxDaily) {
        var myDailyChart = new Chart(ctxDaily, {
            type: 'line', // Changed to line for a more professional look
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [
                    {
                        label: "Stock In",
                        fill: true,
                        tension: 0.4, // Curvy lines
                        backgroundColor: "rgba(28, 200, 138, 0.05)",
                        borderColor: "rgba(28, 200, 138, 1)",
                        pointRadius: 3,
                        pointBackgroundColor: "rgba(28, 200, 138, 1)",
                        pointBorderColor: "rgba(28, 200, 138, 1)",
                        data: <?php echo json_encode($chart_data_in); ?>,
                    },
                    {
                        label: "Stock Out",
                        fill: true,
                        tension: 0.4, // Curvy lines
                        backgroundColor: "rgba(231, 74, 59, 0.05)",
                        borderColor: "rgba(231, 74, 59, 1)",
                        pointRadius: 3,
                        pointBackgroundColor: "rgba(231, 74, 59, 1)",
                        pointBorderColor: "rgba(231, 74, 59, 1)",
                        data: <?php echo json_encode($chart_data_out); ?>,
                    }
                ],
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: { left: 10, right: 25, top: 25, bottom: 0 }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { maxTicksLimit: 7 }
                    },
                    y: {
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            callback: function(value) { return value + ' units'; }
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                        }
                    },
                },
                plugins: {
                    legend: { display: true, position: 'top', align: 'end' },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: "rgba(255, 255, 255, 0.9)",
                        titleColor: "#6e707e",
                        bodyColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        displayColors: true,
                        padding: 12
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
                labels: ["<?php echo __('in_stock'); ?>", "<?php echo __('low_stock_items'); ?>", "<?php echo __('out_of_stock'); ?>"],
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