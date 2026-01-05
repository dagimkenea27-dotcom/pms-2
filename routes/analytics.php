<?php
// routes/analytics.php
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../models/RouteAnalytics.php";

// Check authentication
Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$analytics = new RouteAnalytics();

// Get date range from request or default to last 30 days
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get KPIs
$kpis = $analytics->getDashboardKPIs($currentUser['id'], $startDate, $endDate);

// Get trends
$trends = $analytics->getRouteTrends('daily', 30);

// Get driver leaderboard
$leaderboard = $analytics->getDriverLeaderboard(10, 'efficiency');

// Get cost breakdown
$costBreakdown = $analytics->getCostBreakdown($startDate, $endDate);

// Get algorithm performance
$algorithmPerf = $analytics->getAlgorithmPerformance();

// Get time window compliance
$timeCompliance = $analytics->getTimeWindowCompliance($startDate, $endDate);

// Get efficiency distribution
$efficiencyDist = $analytics->getEfficiencyDistribution();

// Get carbon emissions
$emissions = $analytics->getCarbonEmissionsReport($startDate, $endDate);

// Get predictions
$predictions = $analytics->getPredictiveAnalytics(7);

require_once "../includes/header.php";
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        --success-gradient: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
        --warning-gradient: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
        --danger-gradient: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
        --info-gradient: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
        --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        --hover-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.25);
    }

    body {
        background-color: #f8f9fc;
    }

    .page-header {
        background: white;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 2rem;
        border-left: 5px solid #4e73df;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .kpi-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        border-left: 4px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, rgba(78, 115, 223, 0.1) 0%, transparent 100%);
        border-radius: 0 1rem 0 100%;
    }

    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }

    .kpi-card.primary { border-left-color: #4e73df; }
    .kpi-card.success { border-left-color: #1cc88a; }
    .kpi-card.warning { border-left-color: #f6c23e; }
    .kpi-card.danger { border-left-color: #e74a3b; }
    .kpi-card.info { border-left-color: #36b9cc; }

    .kpi-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .kpi-icon.primary { background: rgba(78, 115, 223, 0.1); color: #4e73df; }
    .kpi-icon.success { background: rgba(28, 200, 138, 0.1); color: #1cc88a; }
    .kpi-icon.warning { background: rgba(246, 194, 62, 0.1); color: #f6c23e; }
    .kpi-icon.danger { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
    .kpi-icon.info { background: rgba(54, 185, 204, 0.1); color: #36b9cc; }

    .kpi-value {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }

    .kpi-label {
        font-size: 0.875rem;
        color: #858796;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .kpi-change {
        font-size: 0.75rem;
        margin-top: 0.5rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.5rem;
        display: inline-block;
    }

    .kpi-change.positive {
        background: rgba(28, 200, 138, 0.1);
        color: #1cc88a;
    }

    .kpi-change.negative {
        background: rgba(231, 74, 59, 0.1);
        color: #e74a3b;
    }

    .chart-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8f9fc;
    }

    .chart-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .chart-title i {
        color: #4e73df;
    }

    .leaderboard-table {
        width: 100%;
    }

    .leaderboard-table thead th {
        background: #f8f9fc;
        color: #858796;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.75rem;
        border: none;
    }

    .leaderboard-table tbody td {
        padding: 1rem 0.75rem;
        border-bottom: 1px solid #f8f9fc;
        vertical-align: middle;
    }

    .leaderboard-table tbody tr:hover {
        background: #f8f9fc;
    }

    .rank-badge {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.875rem;
    }

    .rank-badge.gold {
        background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
        color: #856404;
    }

    .rank-badge.silver {
        background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
        color: #383d41;
    }

    .rank-badge.bronze {
        background: linear-gradient(135deg, #cd7f32 0%, #e8a87c 100%);
        color: #fff;
    }

    .rank-badge.default {
        background: #e3e6f0;
        color: #858796;
    }

    .rating-stars {
        color: #f6c23e;
    }

    .progress-bar-custom {
        height: 8px;
        border-radius: 10px;
        background: #e3e6f0;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .progress-fill.excellent { background: var(--success-gradient); }
    .progress-fill.good { background: var(--info-gradient); }
    .progress-fill.average { background: var(--warning-gradient); }
    .progress-fill.poor { background: var(--danger-gradient); }

    .date-filter {
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 1rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .date-filter label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #858796;
        margin: 0;
    }

    .date-filter input {
        border-radius: 0.5rem;
        border: 1px solid #d1d3e2;
        padding: 0.5rem 1rem;
    }

    .export-btn {
        margin-left: auto;
    }

    @media (max-width: 768px) {
        .kpi-grid {
            grid-template-columns: 1fr;
        }
        
        .date-filter {
            flex-direction: column;
            align-items: stretch;
        }
        
        .export-btn {
            margin-left: 0;
        }
    }
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <i class="fas fa-chart-line text-primary me-2"></i>
                Route Analytics Dashboard
            </h1>
            <p class="mb-0 text-muted small">Comprehensive insights and performance metrics</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-primary shadow-sm">
                <i class="fas fa-route me-1"></i> Back to Routes
            </a>
        </div>
    </div>
</div>

<!-- Date Filter -->
<form method="GET" class="date-filter">
    <label for="start_date">From:</label>
    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-control form-control-sm">
    
    <label for="end_date">To:</label>
    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-control form-control-sm">
    
    <button type="submit" class="btn btn-primary btn-sm">
        <i class="fas fa-filter me-1"></i> Apply
    </button>
    
    <div class="export-btn">
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-download me-1"></i> Export
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="?export=routes&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>">
                    <i class="fas fa-file-csv me-2"></i> Routes CSV
                </a></li>
                <li><a class="dropdown-item" href="?export=drivers&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>">
                    <i class="fas fa-file-csv me-2"></i> Drivers CSV
                </a></li>
                <li><a class="dropdown-item" href="?export=costs&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>">
                    <i class="fas fa-file-csv me-2"></i> Costs CSV
                </a></li>
            </ul>
        </div>
    </div>
</form>

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card primary">
        <div class="kpi-icon primary">
            <i class="fas fa-route"></i>
        </div>
        <div class="kpi-value"><?= number_format($kpis['total_routes'] ?? 0) ?></div>
        <div class="kpi-label">Total Routes</div>
    </div>

    <div class="kpi-card success">
        <div class="kpi-icon success">
            <i class="fas fa-road"></i>
        </div>
        <div class="kpi-value"><?= number_format($kpis['total_distance'] ?? 0, 1) ?> <span style="font-size: 1rem;">km</span></div>
        <div class="kpi-label">Total Distance</div>
    </div>

    <div class="kpi-card info">
        <div class="kpi-icon info">
            <i class="fas fa-gauge-high"></i>
        </div>
        <div class="kpi-value"><?= number_format($kpis['avg_efficiency'] ?? 0, 1) ?>%</div>
        <div class="kpi-label">Avg Efficiency</div>
    </div>

    <div class="kpi-card warning">
        <div class="kpi-icon warning">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="kpi-value">$<?= number_format($kpis['total_cost'] ?? 0, 2) ?></div>
        <div class="kpi-label">Total Cost</div>
    </div>

    <div class="kpi-card success">
        <div class="kpi-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="kpi-value"><?= number_format($kpis['completion_rate'] ?? 0, 1) ?>%</div>
        <div class="kpi-label">Completion Rate</div>
    </div>

    <div class="kpi-card danger">
        <div class="kpi-icon danger">
            <i class="fas fa-leaf"></i>
        </div>
        <div class="kpi-value"><?= number_format($emissions['total_emissions'] ?? 0, 1) ?> <span style="font-size: 1rem;">kg</span></div>
        <div class="kpi-label">CO₂ Emissions</div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row">
    <div class="col-lg-8">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-chart-area"></i>
                    Route Trends
                </div>
            </div>
            <canvas id="trendsChart" height="80"></canvas>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Cost Breakdown
                </div>
            </div>
            <canvas id="costsChart"></canvas>
        </div>
    </div>
</div>

<!-- Driver Leaderboard -->
<div class="chart-card">
    <div class="chart-header">
        <div class="chart-title">
            <i class="fas fa-trophy"></i>
            Driver Performance Leaderboard
        </div>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary active" onclick="updateLeaderboard('efficiency')">Efficiency</button>
            <button class="btn btn-outline-primary" onclick="updateLeaderboard('distance')">Distance</button>
            <button class="btn btn-outline-primary" onclick="updateLeaderboard('rating')">Rating</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Driver</th>
                    <th>Rating</th>
                    <th>Routes</th>
                    <th>Distance</th>
                    <th>Efficiency</th>
                    <th>On-Time %</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard as $index => $driver): ?>
                <tr>
                    <td>
                        <span class="rank-badge <?= $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : 'default')) ?>">
                            <?= $index + 1 ?>
                        </span>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($driver['driver_code']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($driver['username']) ?></small>
                    </td>
                    <td>
                        <span class="rating-stars">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star<?= $i < floor($driver['rating']) ? '' : '-half-alt' ?>"></i>
                            <?php endfor; ?>
                        </span>
                        <small class="text-muted">(<?= number_format($driver['rating'], 2) ?>)</small>
                    </td>
                    <td><?= number_format($driver['total_routes']) ?></td>
                    <td><?= number_format($driver['total_distance'], 1) ?> km</td>
                    <td>
                        <div class="progress-bar-custom">
                            <div class="progress-fill <?= $driver['avg_efficiency'] >= 90 ? 'excellent' : ($driver['avg_efficiency'] >= 75 ? 'good' : ($driver['avg_efficiency'] >= 60 ? 'average' : 'poor')) ?>" 
                                 style="width: <?= $driver['avg_efficiency'] ?>%"></div>
                        </div>
                        <small><?= number_format($driver['avg_efficiency'], 1) ?>%</small>
                    </td>
                    <td><?= number_format($driver['on_time_percentage'], 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row">
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-chart-bar"></i>
                    Algorithm Performance
                </div>
            </div>
            <canvas id="algorithmChart"></canvas>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-chart-donut"></i>
                    Efficiency Distribution
                </div>
            </div>
            <canvas id="efficiencyChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Chart.js defaults
Chart.defaults.font.family = "'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
Chart.defaults.color = '#858796';

// Trends Chart
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_reverse(array_column($trends, 'period'))) ?>,
        datasets: [{
            label: 'Routes',
            data: <?= json_encode(array_reverse(array_column($trends, 'route_count'))) ?>,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Efficiency',
            data: <?= json_encode(array_reverse(array_column($trends, 'avg_efficiency'))) ?>,
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28, 200, 138, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Routes'
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Efficiency %'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});

// Costs Chart
const costsCtx = document.getElementById('costsChart').getContext('2d');
new Chart(costsCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($costBreakdown, 'cost_type')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($costBreakdown, 'total_amount')) ?>,
            backgroundColor: [
                '#4e73df',
                '#1cc88a',
                '#36b9cc',
                '#f6c23e',
                '#e74a3b',
                '#858796'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Algorithm Performance Chart
const algorithmCtx = document.getElementById('algorithmChart').getContext('2d');
new Chart(algorithmCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($algorithmPerf, 'optimization_algorithm')) ?>,
        datasets: [{
            label: 'Avg Efficiency',
            data: <?= json_encode(array_column($algorithmPerf, 'avg_efficiency')) ?>,
            backgroundColor: '#4e73df'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});

// Efficiency Distribution Chart
const efficiencyCtx = document.getElementById('efficiencyChart').getContext('2d');
new Chart(efficiencyCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($efficiencyDist, 'efficiency_category')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($efficiencyDist, 'route_count')) ?>,
            backgroundColor: [
                '#1cc88a',
                '#36b9cc',
                '#f6c23e',
                '#e74a3b',
                '#858796'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

function updateLeaderboard(metric) {
    window.location.href = `?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&metric=${metric}`;
}
</script>

<?php require_once "../includes/footer.php"; ?>
