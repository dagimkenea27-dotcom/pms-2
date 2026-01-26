<?php
require_once "../config/auth.php";
require_once "../config/database.php";

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle form submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $ordered = $_POST['ordered'] ?? '';
        $interested = $_POST['interested'] ?? '';
        $dormant = $_POST['dormant'] ?? 0;
        $incentive = $_POST['incentive'] ?? '';
        $lastTouch = $_POST['last_touch'] ?? '';

        $query = "INSERT INTO marketing_attribution (customer_name, phone_number, product_ordered, product_interested, dormant_days, incentive_used, last_touch_point) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $success = $stmt->execute([$name, $phone, $ordered, $interested, $dormant, $incentive, $lastTouch]);

        echo json_encode(['success' => $success]);
        exit;
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? 0;
        $query = "DELETE FROM marketing_attribution WHERE id = ?";
        $stmt = $db->prepare($query);
        $success = $stmt->execute([$id]);

        echo json_encode(['success' => $success]);
        exit;
    }
}

// Fetch existing records
$query = "SELECT * FROM marketing_attribution ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$js_records = [];
foreach ($records as $row) {
    $js_records[] = [
        'id' => $row['id'],
        'name' => $row['customer_name'],
        'phone' => $row['phone_number'],
        'ordered' => $row['product_ordered'],
        'interested' => $row['product_interested'],
        'dormant' => $row['dormant_days'],
        'incentive' => $row['incentive_used'],
        'lastTouch' => $row['last_touch_point']
    ];
}

require_once "../includes/header.php";
?>

<!-- Include Tailwind CSS via CDN - scoped to avoid conflict with Bootstrap -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        corePlugins: {
            preflight: false,
        }
    }
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    
    #gojo-analysis-dashboard {
        font-family: 'Inter', sans-serif;
    }
    
    #gojo-analysis-dashboard .form-input { 
        border: 1px solid #d1d5db; 
        border-radius: 0.5rem; 
        padding: 0.5rem 0.75rem; 
        width: 100%; 
        background-color: white;
    }
    
    #gojo-analysis-dashboard .form-input:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        border-color: #3b82f6;
    }
    
    #gojo-analysis-dashboard .card-gojo {
        background-color: white;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border: 1px solid #f3f4f6;
    }

    #gojo-analysis-dashboard .btn-primary-gojo {
        background-color: #2563eb;
        color: white;
        font-weight: 900;
        padding: 1rem;
        border-radius: 0.75rem;
        transition: background-color 0.2s;
        width: 100%;
        border: none;
        cursor: pointer;
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2);
    }
    
    #gojo-analysis-dashboard .btn-primary-gojo:hover {
        background-color: #1d4ed8;
    }

    #gojo-analysis-dashboard .btn-export-gojo {
        background-color: #059669;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
        text-decoration: none;
    }

    #gojo-analysis-dashboard .btn-export-gojo:hover {
        background-color: #047857;
    }
</style>

<div id="gojo-analysis-dashboard" class="bg-gray-50 min-h-screen p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Gojo Shop Strategy</h1>
                <p class="text-gray-500 font-medium">Consumer Behavioral Analysis & Purchase Triggers</p>
            </div>
            <div class="flex gap-4">
                <button onclick="exportToCSV()" class="btn-export-gojo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export CSV
                </button>
                <div class="bg-white px-6 py-2 rounded-xl shadow-sm border border-gray-200">
                    <span class="text-xs text-gray-500 uppercase font-bold tracking-wider block">Total Sample</span>
                    <span id="totalCount" class="text-2xl font-black text-blue-600">0</span>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Data Entry Form -->
            <div class="lg:col-span-1 space-y-4">
                <div class="card-gojo h-fit sticky top-8">
                    <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">New Entry</h2>
                    <form id="entryForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Customer Name</label>
                            <input type="text" id="custName" class="form-input" placeholder="e.g. John Doe" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone Number</label>
                            <input type="tel" id="custPhone" class="form-input" placeholder="e.g. +251..." required>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ordered</label>
                                <input type="text" id="orderedProduct" class="form-input" placeholder="Product" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Interested</label>
                                <input type="text" id="interestedProduct" class="form-input" placeholder="Browsed">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Days App was Dormant</label>
                            <input type="number" id="dormantDays" class="form-input" placeholder="Days since install" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Incentive Used</label>
                            <select id="incentive" class="form-input" required>
                                <option value="None / Full Price">None / Full Price</option>
                                <option value="Welcome Discount">Welcome Discount</option>
                                <option value="Flash Sale">Flash Sale</option>
                                <option value="Free Delivery">Free Delivery</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Last Touch Point</label>
                            <select id="lastTouch" class="form-input" required>
                                <option value="TikTok Ad">TikTok Ad</option>
                                <option value="Instagram Ad">Instagram Ad</option>
                                <option value="SMS">SMS Notification</option>
                                <option value="In-App Popup">In-App Popup</option>
                                <option value="Email">Email</option>
                                <option value="Referral">Referral</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary-gojo">
                            REGISTER DATA
                        </button>
                    </form>
                </div>
            </div>

            <!-- Analysis Section -->
            <div class="lg:col-span-3 space-y-8">
                <!-- Visualizations -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="card-gojo">
                        <h2 class="text-sm font-black text-gray-400 uppercase mb-4 tracking-widest">Touch Point Performance</h2>
                        <div class="h-64">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                    <div class="card-gojo">
                        <h2 class="text-sm font-black text-gray-400 uppercase mb-4 tracking-widest">Incentive Influence</h2>
                        <div class="h-64 flex justify-center">
                            <canvas id="incentiveChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Sample Analysis Summary Table -->
                <div class="card-gojo overflow-hidden">
                    <h2 class="text-sm font-black text-gray-400 uppercase mb-4 tracking-widest">Sample Analysis Summary</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-xs font-bold text-gray-500 uppercase mb-2">Channel Breakdown</h3>
                            <table class="w-full text-left text-xs bg-gray-50 rounded-lg overflow-hidden">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2">Channel</th>
                                        <th class="px-3 py-2 text-right">Count</th>
                                        <th class="px-3 py-2 text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody id="channelSummaryBody">
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-gray-500 uppercase mb-2">Incentive Breakdown</h3>
                            <table class="w-full text-left text-xs bg-gray-50 rounded-lg overflow-hidden">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2">Incentive</th>
                                        <th class="px-3 py-2 text-right">Count</th>
                                        <th class="px-3 py-2 text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody id="incentiveSummaryBody">
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Spreadsheet Card -->
                <div class="card-gojo overflow-hidden">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Master Data Sheet</h2>
                        <div class="flex flex-wrap gap-2 w-full md:w-auto">
                            <!-- Search -->
                            <div class="relative flex-grow md:flex-grow-0">
                                <input type="text" id="searchInput" placeholder="Search customer or order..." class="text-xs border border-gray-200 rounded-lg px-3 py-2 pl-8 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full md:w-48">
                                <svg class="absolute left-2.5 top-2.5 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            </div>
                            <!-- Filter Channel -->
                            <select id="filterTouch" class="text-xs border border-gray-200 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Channels</option>
                                <option value="TikTok Ad">TikTok Ad</option>
                                <option value="Instagram Ad">Instagram Ad</option>
                                <option value="SMS">SMS Notification</option>
                                <option value="In-App Popup">In-App Popup</option>
                                <option value="Email">Email</option>
                                <option value="Referral">Referral</option>
                            </select>
                            <!-- Filter Incentive -->
                            <select id="filterIncentive" class="text-xs border border-gray-200 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Incentives</option>
                                <option value="None / Full Price">None / Full Price</option>
                                <option value="Welcome Discount">Welcome Discount</option>
                                <option value="Flash Sale">Flash Sale</option>
                                <option value="Free Delivery">Free Delivery</option>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-4 py-3 font-bold text-gray-600">Customer</th>
                                    <th class="px-4 py-3 font-bold text-gray-600">Phone</th>
                                    <th class="px-4 py-3 font-bold text-gray-600">Order</th>
                                    <th class="px-4 py-3 font-bold text-gray-600">Dormant</th>
                                    <th class="px-4 py-3 font-bold text-gray-600">Incentive</th>
                                    <th class="px-4 py-3 font-bold text-gray-600">Last Touch</th>
                                    <th class="px-4 py-3 font-bold text-gray-600 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody id="dataTableBody" class="divide-y divide-gray-100">
                                <!-- Data rows -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination Controls -->
                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                        <div class="text-xs text-gray-500 font-medium">
                            Showing <span id="paginationInfo">0 - 0 of 0</span> entries
                        </div>
                        <div class="flex gap-2" id="paginationButtons">
                            <!-- Page buttons will be injected here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let records = <?php echo json_encode($js_records); ?>;
    let barChart, pieChart;
    let currentPage = 1;
    let rowsPerPage = 10;

    function initCharts() {
        const barCtx = document.getElementById('performanceChart').getContext('2d');
        barChart = new Chart(barCtx, {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Sales', data: [], backgroundColor: '#2563eb', borderRadius: 6 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        const pieCtx = document.getElementById('incentiveChart').getContext('2d');
        pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#ef4444', '#f59e0b', '#10b981', '#6366f1'] }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    function updateCharts() {
        const total = records.length;

        // Last Touch Data
        const touchCounts = {};
        records.forEach(r => {
            if (r.lastTouch) {
                touchCounts[r.lastTouch] = (touchCounts[r.lastTouch] || 0) + 1;
            }
        });
        barChart.data.labels = Object.keys(touchCounts);
        barChart.data.datasets[0].data = Object.values(touchCounts);
        barChart.update();

        // Incentive Data
        const incentiveCounts = {};
        records.forEach(r => {
            if (r.incentive) {
                incentiveCounts[r.incentive] = (incentiveCounts[r.incentive] || 0) + 1;
            }
        });
        pieChart.data.labels = Object.keys(incentiveCounts);
        pieChart.data.datasets[0].data = Object.values(incentiveCounts);
        pieChart.update();

        // Update Summary Tables
        const channelBody = document.getElementById('channelSummaryBody');
        channelBody.innerHTML = '';
        Object.entries(touchCounts).sort((a,b) => b[1] - a[1]).forEach(([channel, count]) => {
            const percent = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
            channelBody.innerHTML += `
                <tr class="border-b border-gray-100 last:border-0 hover:bg-white transition-colors">
                    <td class="px-3 py-2 font-medium text-gray-700">${channel}</td>
                    <td class="px-3 py-2 text-right font-bold text-blue-600">${count}</td>
                    <td class="px-3 py-2 text-right text-gray-400 font-medium">${percent}%</td>
                </tr>
            `;
        });

        const incentiveBody = document.getElementById('incentiveSummaryBody');
        incentiveBody.innerHTML = '';
        Object.entries(incentiveCounts).sort((a,b) => b[1] - a[1]).forEach(([incentive, count]) => {
            const percent = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
            incentiveBody.innerHTML += `
                <tr class="border-b border-gray-100 last:border-0 hover:bg-white transition-colors">
                    <td class="px-3 py-2 font-medium text-gray-700">${incentive}</td>
                    <td class="px-3 py-2 text-right font-bold text-emerald-600">${count}</td>
                    <td class="px-3 py-2 text-right text-gray-400 font-medium">${percent}%</td>
                </tr>
            `;
        });
    }

    function updateUI() {
        const tbody = document.getElementById('dataTableBody');
        tbody.innerHTML = '';
        
        // --- Filtering Logic ---
        const searchQuery = document.getElementById('searchInput').value.toLowerCase();
        const filterTouch = document.getElementById('filterTouch').value;
        const filterIncentive = document.getElementById('filterIncentive').value;

        const filteredRecords = records.filter(r => {
            const matchesSearch = !searchQuery || 
                (r.name && r.name.toLowerCase().includes(searchQuery)) || 
                (r.ordered && r.ordered.toLowerCase().includes(searchQuery)) ||
                (r.phone && r.phone.toLowerCase().includes(searchQuery));
            
            const matchesTouch = !filterTouch || r.lastTouch === filterTouch;
            const matchesIncentive = !filterIncentive || r.incentive === filterIncentive;
            
            return matchesSearch && matchesTouch && matchesIncentive;
        });
        
        const total = filteredRecords.length;
        const totalPages = Math.ceil(total / rowsPerPage);
        
        // Ensure currentPage is within bounds
        if (currentPage > totalPages) currentPage = Math.max(1, totalPages);
        
        const start = (currentPage - 1) * rowsPerPage;
        const end = Math.min(start + rowsPerPage, total);
        const paginatedRecords = filteredRecords.slice(start, end);
        
        paginatedRecords.forEach((record) => {
            // Find actual index in global records for deletion
            const actualIndex = records.findIndex(r => r.id === record.id);
            const row = `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 font-semibold">${record.name}</td>
                    <td class="px-4 py-3 text-gray-500 font-medium">${record.phone || '-'}</td>
                    <td class="px-4 py-3">
                        <span class="block text-gray-900">${record.ordered}</span>
                        <span class="text-xs text-gray-400">Target: ${record.interested || '-'}</span>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-600">${record.dormant} days</td>
                    <td class="px-4 py-3 text-xs font-bold text-emerald-600">${record.incentive}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-bold whitespace-nowrap">${record.lastTouch}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="deleteRecord(${record.id}, ${actualIndex})" class="text-gray-300 hover:text-red-500 border-none bg-transparent cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });

        document.getElementById('totalCount').innerText = records.length;
        document.getElementById('paginationInfo').innerText = `${total > 0 ? start + 1 : 0} - ${end} of ${total}`;
        
        renderPaginationButtons(totalPages);
        updateCharts();
    }

    function renderPaginationButtons(totalPages) {
        const container = document.getElementById('paginationButtons');
        container.innerHTML = '';

        if (totalPages <= 1) return;

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.innerText = 'Prev';
        prevBtn.disabled = currentPage === 1;
        prevBtn.className = `px-3 py-1 text-xs font-bold rounded-lg border ${currentPage === 1 ? 'text-gray-300 bg-gray-50 border-gray-100' : 'text-blue-600 border-blue-100 hover:bg-blue-50 transition-colors'}`;
        prevBtn.onclick = () => { currentPage--; updateUI(); };
        container.appendChild(prevBtn);

        // Page numbers (simplified: show current, first, and last)
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                const pageBtn = document.createElement('button');
                pageBtn.innerText = i;
                pageBtn.className = `px-3 py-1 text-xs font-bold rounded-lg border ${i === currentPage ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'text-gray-500 border-gray-100 hover:bg-gray-50 transition-colors'}`;
                pageBtn.onclick = () => { currentPage = i; updateUI(); };
                container.appendChild(pageBtn);
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                const dots = document.createElement('span');
                dots.innerText = '...';
                dots.className = 'text-gray-300 px-1';
                container.appendChild(dots);
            }
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.innerText = 'Next';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.className = `px-3 py-1 text-xs font-bold rounded-lg border ${currentPage === totalPages ? 'text-gray-300 bg-gray-50 border-gray-100' : 'text-blue-600 border-blue-100 hover:bg-blue-50 transition-colors'}`;
        nextBtn.onclick = () => { currentPage++; updateUI(); };
        container.appendChild(nextBtn);
    }

    function deleteRecord(id, index) {
        if (!confirm('Are you sure you want to remove this record?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                records.splice(index, 1);
                updateUI();
            } else {
                alert('Failed to delete record');
            }
        });
    }

    function exportToCSV() {
        let csv = "Customer,Phone,Ordered,Interested,DormancyDays,Incentive,LastTouch\n";
        records.forEach(r => {
            const name = (r.name || "").replace(/,/g, "");
            const phone = (r.phone || "").replace(/,/g, "");
            const ordered = (r.ordered || "").replace(/,/g, "");
            const interested = (r.interested || "").replace(/,/g, "");
            const incentive = (r.incentive || "").replace(/,/g, "");
            const lastTouch = (r.lastTouch || "").replace(/,/g, "");
            
            csv += `${name},${phone},${ordered},${interested},${r.dormant},${incentive},${lastTouch}\n`;
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('href', url);
        a.setAttribute('download', 'GojoShop_Analysis.csv');
        a.click();
    }

    document.getElementById('entryForm').addEventListener('submit', (e) => {
        e.preventDefault();
        
        const name = document.getElementById('custName').value;
        const phone = document.getElementById('custPhone').value;
        const ordered = document.getElementById('orderedProduct').value;
        const interested = document.getElementById('interestedProduct').value;
        const dormant = document.getElementById('dormantDays').value;
        const incentive = document.getElementById('incentive').value;
        const lastTouch = document.getElementById('lastTouch').value;

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('ordered', ordered);
        formData.append('interested', interested);
        formData.append('dormant', dormant);
        formData.append('incentive', incentive);
        formData.append('last_touch', lastTouch);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Failed to register entry');
            }
        });
    });
    
    // Search and Filter Listeners
    document.getElementById('searchInput').addEventListener('input', () => {
        currentPage = 1;
        updateUI();
    });
    
    document.getElementById('filterTouch').addEventListener('change', () => {
        currentPage = 1;
        updateUI();
    });
    
    document.getElementById('filterIncentive').addEventListener('change', () => {
        currentPage = 1;
        updateUI();
    });

    window.onload = () => {
        initCharts();
        updateUI();
    };
</script>

<?php require_once "../includes/footer.php"; ?>
