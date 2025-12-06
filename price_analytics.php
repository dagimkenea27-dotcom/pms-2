<?php
// price_analytics.php
require_once "config/auth.php";
require_once "config/database.php";

Auth::checkAuthAndPreventCache();

// Get current user
$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

$database = new Database();
$db = $database->getConnection();

// Get analytics data
// 1. Total calculations
$totalCalculationsQuery = "SELECT COUNT(*) as count FROM price_calculation_history WHERE user_id = ?";
$totalCalculationsStmt = $db->prepare($totalCalculationsQuery);
$totalCalculationsStmt->execute([$userId]);
$totalCalculations = $totalCalculationsStmt->fetch(PDO::FETCH_ASSOC)['count'];

// 2. Total value calculated
$totalValueQuery = "SELECT SUM(total_cost) as total FROM price_calculation_history WHERE user_id = ?";
$totalValueStmt = $db->prepare($totalValueQuery);
$totalValueStmt->execute([$userId]);
$totalValue = $totalValueStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 3. Most used currency
$popularCurrencyQuery = "SELECT source_currency, COUNT(*) as count 
                         FROM price_calculation_history 
                         WHERE user_id = ? 
                         GROUP BY source_currency 
                         ORDER BY count DESC 
                         LIMIT 1";
$popularCurrencyStmt = $db->prepare($popularCurrencyQuery);
$popularCurrencyStmt->execute([$userId]);
$popularCurrency = $popularCurrencyStmt->fetch(PDO::FETCH_ASSOC);

// 4. Recent calculations
$recentCalculationsQuery = "SELECT * FROM price_calculation_history 
                            WHERE user_id = ? 
                            ORDER BY created_at DESC 
                            LIMIT 10";
$recentCalculationsStmt = $db->prepare($recentCalculationsQuery);
$recentCalculationsStmt->execute([$userId]);
$recentCalculations = $recentCalculationsStmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Monthly trend data
$monthlyTrendQuery = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as count,
                        AVG(total_cost) as avg_cost
                      FROM price_calculation_history 
                      WHERE user_id = ? 
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                      ORDER BY month";
$monthlyTrendStmt = $db->prepare($monthlyTrendQuery);
$monthlyTrendStmt->execute([$userId]);
$monthlyTrendData = $monthlyTrendStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for charts
$months = [];
$calcCounts = [];
$avgCosts = [];

foreach ($monthlyTrendData as $data) {
    $months[] = date('M Y', strtotime($data['month']));
    $calcCounts[] = $data['count'];
    $avgCosts[] = round($data['avg_cost'], 2);
}

require_once "includes/header.php";
?>

<link rel="stylesheet" href="assets/css/price.css">

<div class="price-calculator-container">
    <div class="page-header">
        <h1><i class="fas fa-chart-line"></i> Price Calculation Analytics</h1>
        <p>Insights and trends from your price calculations</p>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Calculations</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalCalculations; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calculator fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card dashboard-card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Value Processed</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">ETB <?php echo number_format($totalValue, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card dashboard-card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Most Used Currency</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $popularCurrency ? $popularCurrency['source_currency'] . ' (' . $popularCurrency['count'] . ' times)' : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card dashboard-card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Calculations Over Time</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="calculationsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card dashboard-card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Average Cost Trend</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="costTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Calculations -->
    <div class="card dashboard-card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Calculations</h6>
        </div>
        <div class="card-body">
            <?php if (count($recentCalculations) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Currency</th>
                            <th>Amount</th>
                            <th>Exchange Rate</th>
                            <th>Total Cost (ETB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentCalculations as $calc): 
                            $data = json_decode($calc['calculation_data'], true);
                        ?>
                        <tr>
                            <td><?php echo date('M j, Y g:i A', strtotime($calc['created_at'])); ?></td>
                            <td><?php echo $calc['source_currency']; ?></td>
                            <td><?php echo number_format($calc['source_amount'], 2); ?></td>
                            <td><?php echo number_format($calc['exchange_rate'], 4); ?></td>
                            <td>ETB <?php echo number_format($calc['total_cost'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center">No calculations found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.font.family = 'Nunito, -apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.color = '#858796';

// Calculations Chart
var ctx1 = document.getElementById("calculationsChart");
if (ctx1) {
    var calculationsChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: "Calculations",
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
                data: <?php echo json_encode($calcCounts); ?>,
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
                    display: false
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

// Cost Trend Chart
var ctx2 = document.getElementById("costTrendChart");
if (ctx2) {
    var costTrendChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: "Average Cost (ETB)",
                backgroundColor: "rgba(28, 200, 138, 0.8)",
                hoverBackgroundColor: "rgba(28, 200, 138, 1)",
                borderColor: "rgba(28, 200, 138, 1)",
                data: <?php echo json_encode($avgCosts); ?>,
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
                    display: false
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
                    caretPadding: 10,
                }
            }
        }
    });
}
</script>

<?php require_once "includes/footer.php"; ?>