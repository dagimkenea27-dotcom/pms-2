<?php
require_once "../config/auth.php";
require_once "../config/database.php";

Auth::requireLogin();

$page_title = "Daily Sales Report";
require_once "../includes/header.php";
?>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        corePlugins: {
            preflight: false,
        }
    }
</script>

<style>
    #daily-report-app {
        font-family: 'Poppins', sans-serif;
    }
    .report-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px -10px rgba(0,0,0,0.1);
    }
    .gradient-bg-1 { background: linear-gradient(135deg, #073b74 0%, #0a4d96 100%); }
    .gradient-bg-2 { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .gradient-bg-3 { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    
    .text-primary-custom { color: #073b74; }
    .bg-primary-custom { background-color: #073b74; }
    
    .glass-effect {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }
</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto+Mono&display=swap" rel="stylesheet">

<div id="daily-report-app" class="pb-10 pt-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                    <span class="p-2 bg-blue-100 text-blue-800 rounded-xl">📊</span>
                    Daily Sales Performance
                </h1>
                <p class="mt-1 text-slate-500">Comprehensive overview of tracking and conversion metrics</p>
            </div>
            
            <div class="flex flex-wrap items-end gap-3 glass-effect p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex flex-col">
                    <label for="filter-start-date" class="text-[10px] font-bold text-slate-500 uppercase mb-1 ml-1">Start Date</label>
                    <input type="date" id="filter-start-date" class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div class="flex flex-col">
                    <label for="filter-end-date" class="text-[10px] font-bold text-slate-500 uppercase mb-1 ml-1">End Date</label>
                    <input type="date" id="filter-end-date" class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div class="flex gap-2">
                    <button onclick="fetchReportData(true)" class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-md text-sm font-semibold text-white bg-primary-custom hover:opacity-90 transition-all">
                        <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 8.293A1 1 0 013 7.586V4z"></path></svg>
                        Filter
                    </button>
                    <button onclick="clearFilters()" class="inline-flex items-center px-3 py-2 border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 bg-white hover:bg-slate-50 transition-all">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="report-card gradient-bg-1 rounded-3xl p-6 text-white shadow-lg overflow-hidden relative">
                <div class="relative z-10">
                    <p class="text-indigo-100 text-sm font-semibold uppercase tracking-wider mb-1">Total Interactions</p>
                    <h3 id="stat-total-interactions" class="text-4xl font-bold">0</h3>
                    <p class="text-indigo-100 text-xs mt-4">Across active tracking history</p>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-20">
                    <svg class="h-32 w-32" fill="currentColor" viewBox="0 0 24 24"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path></svg>
                </div>
            </div>

            <div class="report-card gradient-bg-2 rounded-3xl p-6 text-white shadow-lg overflow-hidden relative">
                <div class="relative z-10">
                    <p class="text-emerald-100 text-sm font-semibold uppercase tracking-wider mb-1">Total Sales (Sold)</p>
                    <h3 id="stat-total-sold" class="text-4xl font-bold">0</h3>
                    <p id="stat-overall-conversion" class="text-emerald-100 text-xs mt-4">0% conversion rate</p>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-20">
                    <svg class="h-32 w-32" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
            </div>

            <div class="report-card gradient-bg-3 rounded-3xl p-6 text-white shadow-lg overflow-hidden relative">
                <div class="relative z-10">
                    <p class="text-rose-100 text-sm font-semibold uppercase tracking-wider mb-1">Avg Sales / Day</p>
                    <h3 id="stat-avg-sales" class="text-4xl font-bold">0</h3>
                    <p id="stat-total-days" class="text-rose-100 text-xs mt-4">Based on 0 active days</p>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-20">
                    <svg class="h-32 w-32" fill="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Sales Trend Chart -->
            <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-200 p-6 overflow-hidden">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-slate-800">Sales Trends (Last 30 Days)</h3>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Interactions vs Sold</span>
                </div>
                <div class="h-80 relative">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Product Breakdown -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Sold Breakdown</h3>
                <div class="space-y-6">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-slate-600">👖 Kaki Pants</span>
                            <span id="kaki-count" class="text-sm font-bold text-slate-900">0</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3">
                            <div id="kaki-progress" class="bg-amber-500 h-3 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-slate-600">👖 Jeans</span>
                            <span id="jeans-count" class="text-sm font-bold text-slate-900">0</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3">
                            <div id="jeans-progress" class="bg-blue-500 h-3 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-10 p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
                    <p class="text-xs text-indigo-700 font-semibold mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                        Performance Tip
                    </p>
                    <p class="text-xs text-indigo-600 leading-relaxed">
                        Consistency in tracking every lead—including those who didn't buy—provides the most accurate conversion data.
                    </p>
                </div>
            </div>
        </div>

        <!-- Daily Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-800">Daily History</h3>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span class="text-[10px] font-bold text-slate-500 uppercase">Conversion Rate</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Interactions</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Sold</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Kaki</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Jeans</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Conversion</th>
                        </tr>
                    </thead>
                    <tbody id="daily-summary-table" class="divide-y divide-slate-100">
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">Loading sales data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let salesChart = null;

    async function fetchReportData(isManual = false) {
        const startDate = document.getElementById('filter-start-date').value;
        const endDate = document.getElementById('filter-end-date').value;
        
        let url = '../api/daily_sales_report_data.php';
        if (startDate && endDate) {
            url += `?start_date=${startDate}&end_date=${endDate}`;
        }

        try {
            const tableBody = document.getElementById('daily-summary-table');
            if (isManual) tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">Filtering data...</td></tr>';
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.isOk) {
                updateStats(data);
                renderChart(data.daily_summary);
                renderTable(data.daily_summary);
                
                // Update chart title
                const titleEl = document.querySelector('#daily-report-app h3.text-lg.font-bold.text-slate-800');
                if (startDate && endDate) {
                    titleEl.textContent = `Sales Trends (${startDate} to ${endDate})`;
                } else {
                    titleEl.textContent = 'Sales Trends (Last 30 Days)';
                }
            } else {
                console.error('API Error:', data.message);
                alert('Failed to load report: ' + data.message);
            }
        } catch (error) {
            console.error('Fetch Error:', error);
        }
    }

    function clearFilters() {
        document.getElementById('filter-start-date').value = '';
        document.getElementById('filter-end-date').value = '';
        fetchReportData();
    }

    function updateStats(data) {
        const stats = data.overall_stats;
        const summaries = data.daily_summary;
        
        document.getElementById('stat-total-interactions').textContent = stats.grand_total_interactions || 0;
        document.getElementById('stat-total-sold').textContent = stats.grand_total_sold || 0;
        
        const conversion = stats.grand_total_interactions > 0 
            ? ((stats.grand_total_sold / stats.grand_total_interactions) * 100).toFixed(1) 
            : 0;
        document.getElementById('stat-overall-conversion').textContent = `${conversion}% overall conversion rate`;
        
        const avg = stats.total_days > 0 
            ? (stats.grand_total_sold / stats.total_days).toFixed(1) 
            : 0;
        document.getElementById('stat-avg-sales').textContent = avg;
        document.getElementById('stat-total-days').textContent = `Based on ${stats.total_days || 0} active days`;
        
        // Product breakdown
        let totalKaki = 0;
        let totalJeans = 0;
        summaries.forEach(s => {
            totalKaki += parseInt(s.kaki_sold || 0);
            totalJeans += parseInt(s.jeans_sold || 0);
        });
        
        document.getElementById('kaki-count').textContent = totalKaki;
        document.getElementById('jeans-count').textContent = totalJeans;
        
        const totalSold = totalKaki + totalJeans;
        const kakiPercent = totalSold > 0 ? (totalKaki / totalSold) * 100 : 0;
        const jeansPercent = totalSold > 0 ? (totalJeans / totalSold) * 100 : 0;
        
        document.getElementById('kaki-progress').style.width = `${kakiPercent}%`;
        document.getElementById('jeans-progress').style.width = `${jeansPercent}%`;
    }

    function renderChart(summaries) {
        const ctx = document.getElementById('salesChart').getContext('2d');
        const reversedData = [...summaries].reverse();
        
        const labels = reversedData.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const interactions = reversedData.map(d => d.total_interactions);
        const sold = reversedData.map(d => d.total_sold);

        if (salesChart) {
            salesChart.destroy();
        }

        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Interactions',
                        data: interactions,
                        borderColor: '#073b74',
                        backgroundColor: 'rgba(7, 59, 116, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    },
                    {
                        label: 'Sold',
                        data: sold,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            font: { family: 'Poppins', size: 12 }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { family: 'Poppins', size: 13 },
                        bodyFont: { family: 'Poppins', size: 12 },
                        padding: 12,
                        borderRadius: 12
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [5, 5], color: '#e2e8f0' },
                        ticks: { font: { family: 'Roboto Mono', size: 11 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Roboto Mono', size: 11 } }
                    }
                }
            }
        });
    }

    function renderTable(summaries) {
        const tableBody = document.getElementById('daily-summary-table');
        if (summaries.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No data available</td></tr>';
            return;
        }

        tableBody.innerHTML = summaries.map(d => {
            const date = new Date(d.date);
            const dateStr = date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
            const conv = d.conversion_rate || 0;
            
            letBadgeClass = 'bg-slate-100 text-slate-600';
            if (conv >= 50) letBadgeClass = 'bg-emerald-100 text-emerald-700';
            else if (conv >= 30) letBadgeClass = 'bg-blue-100 text-blue-700';
            else if (conv > 0) letBadgeClass = 'bg-amber-100 text-amber-700';

            return `
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">${dateStr}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 text-center mono-font">${d.total_interactions}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-emerald-600 font-bold text-center mono-font">${d.total_sold}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-amber-600 text-center mono-font">${d.kaki_sold || 0}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 text-center mono-font">${d.jeans_sold || 0}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold ${letBadgeClass}">${conv}%</span>
                            <div class="ml-2 w-16 bg-slate-100 rounded-full h-1.5 hidden sm:block">
                                <div class="${conv >= 50 ? 'bg-emerald-500' : (conv >= 30 ? 'bg-blue-500' : 'bg-amber-500')} h-1.5 rounded-full" style="width: ${conv}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    document.addEventListener('DOMContentLoaded', fetchReportData);
</script>

<?php require_once "../includes/footer.php"; ?>
