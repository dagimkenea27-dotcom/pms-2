<?php
/**
 * reports/finance.php
 * Comprehensive financial dashboard for Profit & Loss tracking
 */
require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../models/Finance.php";

Auth::requireLogin();
if (!Auth::isAdmin()) {
    die("Access denied. Admin privileges required.");
}

$database = new Database();
$db = $database->getConnection();
$finance = new Finance($db);

// Get date range (default to current month)
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$summary = $finance->getNetProfit($startDate, $endDate);
$expensesByCategory = $finance->getExpenseBreakdown($startDate, $endDate);

require_once "../includes/header.php";
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Financial Reports (P&L)</h1>
        <form class="form-inline d-flex gap-2">
            <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo $startDate; ?>">
            <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo $endDate; ?>">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <!-- Revenue Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">ETB <?php echo number_format($summary['revenue'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gross Profit Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Gross Profit</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">ETB <?php echo number_format($summary['gross_profit'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Expenses</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">ETB <?php echo number_format($summary['total_expenses'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-receipt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Profit Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Net Profit</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">ETB <?php echo number_format($summary['net_profit'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Expense Breakdown Chart -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Expense Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4">
                        <canvas id="expenseChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense Table -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Expenses by Category</h6>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                        <i class="fas fa-plus"></i> Add Expense
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expensesByCategory as $exp): 
                                    $percent = ($summary['total_expenses'] > 0) ? ($exp['total'] / $summary['total_expenses']) * 100 : 0;
                                ?>
                                <tr>
                                    <td><i class="<?php echo $exp['icon']; ?> me-2"></i> <?php echo $exp['name']; ?></td>
                                    <td>ETB <?php echo number_format($exp['total'], 2); ?></td>
                                    <td><?php echo number_format($percent, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="expenseForm" action="process_expense.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <?php
                            $catStmt = $db->query("SELECT id, name FROM expense_categories");
                            while ($cat = $catStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (ETB)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Expense Pie Chart
    var ctx = document.getElementById("expenseChart");
    var myPieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($expensesByCategory, 'name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($expensesByCategory, 'total')); ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            cutout: '70%',
        },
    });
</script>

<?php require_once "../includes/footer.php"; ?>
