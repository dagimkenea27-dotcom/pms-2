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
        $ordered = $_POST['ordered'] ?? '';
        $interested = $_POST['interested'] ?? '';
        $lastTouch = $_POST['last_touch'] ?? '';
        $combination = $_POST['combination'] ?? '';

        $query = "INSERT INTO marketing_attribution (customer_name, product_ordered, product_interested, last_touch_point, channel_combination) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $success = $stmt->execute([$name, $ordered, $interested, $lastTouch, $combination]);

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

// Map internal names to display names if needed (though the JS handles it mostly)
$js_records = [];
foreach ($records as $row) {
    $js_records[] = [
        'id' => $row['id'],
        'name' => $row['customer_name'],
        'ordered' => $row['product_ordered'],
        'interested' => $row['product_interested'],
        'lastTouch' => $row['last_touch_point'],
        'combination' => $row['channel_combination']
    ];
}

require_once "../includes/header.php";
?>

<!-- Include Tailwind CSS via CDN - scoped to avoid conflict with Bootstrap -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    // Configure Tailwind to avoid conflicts if possible
    tailwind.config = {
        corePlugins: {
            preflight: false, // Disable preflight as it resets Bootstrap styles
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
        border-radius: 0.375rem; 
        padding: 0.5rem 0.75rem; 
        width: 100%; 
    }
    
    #gojo-analysis-dashboard .form-input:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        border-color: #3b82f6;
    }
    
    #gojo-analysis-dashboard .btn-primary-gojo {
        background-color: #2563eb;
        color: white;
        font-weight: 700;
        padding: 0.75rem;
        border-radius: 0.75rem;
        transition: background-color 0.2s;
        width: 100%;
        border: none;
    }
    
    #gojo-analysis-dashboard .btn-primary-gojo:hover {
        background-color: #1d4ed8;
    }
    
    #gojo-analysis-dashboard .card-gojo {
        background-color: white;
        border-radius: 1rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border: 1px solid #f3f4f6;
    }
</style>

<div id="gojo-analysis-dashboard" class="bg-gray-50 min-h-screen p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Gojo Shop Analysis</h1>
                <p class="text-gray-500">First-Time Purchase Analysis Dashboard</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                <span class="text-sm text-gray-500 block">Total Responses</span>
                <span id="totalCount" class="text-2xl font-bold text-blue-600">0</span>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Data Entry Form -->
            <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-fit sticky top-8">
                <h2 class="text-xl font-semibold mb-4 border-b pb-2">Add New Record</h2>
                <form id="entryForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                        <input type="text" id="custName" class="form-input" placeholder="e.g. John Doe" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Ordered</label>
                        <input type="text" id="orderedProduct" class="form-input" placeholder="e.g. Nike Shoes" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Interested In</label>
                        <input type="text" id="interestedProduct" class="form-input" placeholder="e.g. Apple Watch">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Touch Point</label>
                        <select id="lastTouch" class="form-input" required>
                            <option value="TikTok Ad">TikTok Ad</option>
                            <option value="Instagram Ad">Instagram Ad</option>
                            <option value="SMS">SMS Notification</option>
                            <option value="In-App">In-App Popup</option>
                            <option value="Email">Email</option>
                            <option value="Referral">Referral</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Channel Combination</label>
                        <select id="combination" class="form-input" required>
                            <option value="TikTok + SMS">TikTok + SMS</option>
                            <option value="IG + SMS">IG + SMS</option>
                            <option value="TikTok + In-App">TikTok + In-App</option>
                            <option value="Facebook + SMS">Facebook + SMS</option>
                            <option value="Organic/Direct">Organic/Direct</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary-gojo">
                        Register Entry
                    </button>
                </form>
            </div>

            <!-- Visualization & Data Section -->
            <div class="lg:col-span-3 space-y-8">
                <!-- Graph Card -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-semibold mb-6">Channel Performance Analysis</h2>
                    <div class="h-64">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <!-- Spreadsheet Card -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <h2 class="text-xl font-semibold mb-4">Data Sheet</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-4 py-3 font-semibold text-gray-600">Customer</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600">Ordered</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600">Interested</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600">Last Touch</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600">Combo</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody id="dataTableBody" class="divide-y divide-gray-100">
                                <!-- Data rows go here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let records = <?php echo json_encode($js_records); ?>;
    let myChart;

    function initChart() {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const dataCounts = getCounts();
        
        myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(dataCounts),
                datasets: [{
                    label: 'Purchases per Channel',
                    data: Object.values(dataCounts),
                    backgroundColor: '#2563eb',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true, 
                        ticks: { stepSize: 1 },
                        title: { display: true, text: 'Number of Purchases' }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    function getCounts() {
        const counts = {};
        records.forEach(r => {
            counts[r.lastTouch] = (counts[r.lastTouch] || 0) + 1;
        });
        return counts;
    }

    function updateUI() {
        const tbody = document.getElementById('dataTableBody');
        tbody.innerHTML = '';
        
        records.forEach((record, index) => {
            const row = `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 font-medium">${record.name}</td>
                    <td class="px-4 py-3 text-gray-800 font-semibold">${record.ordered}</td>
                    <td class="px-4 py-3 text-gray-500 italic">${record.interested || '-'}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold whitespace-nowrap">${record.lastTouch}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs">${record.combination}</td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="deleteRecord(${record.id}, ${index})" class="text-red-500 hover:text-red-700 font-medium">Remove</button>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });

        document.getElementById('totalCount').innerText = records.length;
        
        if (myChart) {
            const newCounts = getCounts();
            myChart.data.labels = Object.keys(newCounts);
            myChart.data.datasets[0].data = Object.values(newCounts);
            myChart.update();
        }
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

    document.getElementById('entryForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const name = document.getElementById('custName').value;
        const ordered = document.getElementById('orderedProduct').value;
        const interested = document.getElementById('interestedProduct').value;
        const lastTouch = document.getElementById('lastTouch').value;
        const combination = document.getElementById('combination').value;

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('name', name);
        formData.append('ordered', ordered);
        formData.append('interested', interested);
        formData.append('last_touch', lastTouch);
        formData.append('combination', combination);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // To get the ID from the server would require returning it in the JSON
                // For now, let's just refresh to be safe or push with a dummy ID
                // But it's better to reload to get the real ID from DB
                window.location.reload();
            } else {
                alert('Failed to register entry');
            }
        });
    });

    window.onload = () => {
        initChart();
        updateUI();
    };
</script>

<?php require_once "../includes/footer.php"; ?>
