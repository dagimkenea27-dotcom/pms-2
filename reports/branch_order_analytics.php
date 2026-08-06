<?php
require_once "../config/auth.php";
require_once "../config/database.php";

Auth::requireLogin();

$page_title = "Meta Orders Analytics";
require_once "../includes/header.php";
?>

<div class="container-fluid py-4">
    <!-- Header & Filters -->
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-1 text-gray-800"><i class="fas fa-chart-line text-info me-2"></i> Meta Orders Analytics</h1>
            <p class="text-muted small">Marketing insights and operational performance for branch orders.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-sm btn-white shadow-sm border me-2" onclick="fetchAnalyticsData()">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
            <button class="btn btn-sm btn-primary shadow-sm" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterPanel">
                <i class="fas fa-filter me-1"></i> Advanced Filters
            </button>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="collapse mb-4" id="filterPanel">
        <div class="card card-body shadow-sm border-0">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small font-weight-bold">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small font-weight-bold">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small font-weight-bold">Status</label>
                    <select name="status" id="status_filter" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small font-weight-bold">Source</label>
                    <select name="source" id="source_filter" class="form-select form-select-sm">
                        <option value="">All Sources</option>
                        <option value="TikTok">TikTok</option>
                        <option value="Facebook">Facebook</option>
                        <option value="Instagram">Instagram</option>
                        <option value="Youtube">Youtube</option>
                        <option value="Tv">Tv</option>
                        <option value="Telegram">Telegram</option>
                        <option value="Referral">Referral</option>
                        <option value="Walk-in">Walk-in</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-info w-100" onclick="fetchAnalyticsData()">Apply
                        Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 bg-gradient-primary text-white" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">Total Orders</div>
                            <div class="h3 mb-0 font-weight-bold" id="total_orders_stat">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 bg-gradient-info text-white" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">Total Items Requested
                            </div>
                            <div class="h3 mb-0 font-weight-bold" id="total_items_stat">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 bg-gradient-success text-white" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">Unique Customers</div>
                            <div class="h3 mb-0 font-weight-bold" id="unique_customers_stat">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Source & Status -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="m-0 font-weight-bold text-gray-800">Order Trends Over Time</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="m-0 font-weight-bold text-gray-800">Orders by Source</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie" style="height: 300px;">
                        <canvas id="sourceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Status & Top Products -->
    <div class="row">
        <div class="col-xl-5 col-lg-6">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="m-0 font-weight-bold text-gray-800">Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie" style="height: 250px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7 col-lg-6">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="m-0 font-weight-bold text-gray-800">Top 10 Requested Products</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar" style="height: 250px;">
                        <canvas id="productsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let trendChart, sourceChart, statusChart, productsChart;

    async function fetchAnalyticsData() {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }

        try {
            const response = await fetch(`../api/branch_order_analytics_data.php?${params.toString()}`);
            const data = await response.json();

            if (data.isOk) {
                updateKPIs(data.summary);
                renderTrends(data.trends);
                renderSource(data.by_source);
                renderStatus(data.by_status);
                renderProducts(data.top_products);
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Fetch Error:', error);
        }
    }

    function updateKPIs(summary) {
        document.getElementById('total_orders_stat').textContent = summary.total_orders || 0;
        document.getElementById('total_items_stat').textContent = summary.total_items || 0;
        document.getElementById('unique_customers_stat').textContent = summary.unique_customers || 0;
    }

    function renderTrends(trends) {
        const ctx = document.getElementById('trendChart').getContext('2d');
        if (trendChart) trendChart.destroy();

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trends.map(t => t.label),
                datasets: [{
                    label: 'Orders',
                    data: trends.map(t => t.value),
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#4e73df'
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 2] } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function renderSource(sources) {
        const ctx = document.getElementById('sourceChart').getContext('2d');
        if (sourceChart) sourceChart.destroy();

        sourceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: sources.map(s => s.label),
                datasets: [{
                    data: sources.map(s => s.value),
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'],
                    hoverOffset: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                },
                cutout: '70%'
            }
        });
    }

    function renderStatus(statuses) {
        const ctx = document.getElementById('statusChart').getContext('2d');
        if (statusChart) statusChart.destroy();

        statusChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: statuses.map(s => s.label),
                datasets: [{
                    data: statuses.map(s => s.value),
                    backgroundColor: ['#f6c23e', '#36b9cc', '#4e73df', '#1cc88a', '#e74a3b'],
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } }
                }
            }
        });
    }

    function renderProducts(products) {
        const ctx = document.getElementById('productsChart').getContext('2d');
        if (productsChart) productsChart.destroy();

        productsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: products.map(p => p.label),
                datasets: [{
                    label: 'Units Requested',
                    data: products.map(p => p.value),
                    backgroundColor: '#36b9cc',
                    borderRadius: 5
                }]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', fetchAnalyticsData);
</script>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #36b9cc 0%, #1a8a9a 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
    }

    .btn-white {
        background: #fff;
        color: #555;
    }

    .btn-white:hover {
        background: #f8f9fc;
    }
</style>

<?php require_once "../includes/footer.php"; ?>