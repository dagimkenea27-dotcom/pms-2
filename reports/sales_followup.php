<?php
require_once "../config/auth.php";
require_once "../config/database.php";

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token for all POST requests
    $token = $_POST['csrf_token'] ?? '';
    if (!Auth::validateCSRF($token)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Security error: Invalid CSRF token']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $product = $_POST['product'] ?? '';
        $size = $_POST['size'] ?? '';
        $location = $_POST['location'] ?? '';
        $call_type = $_POST['call_type'] ?? 'normal';
        $purchased = ($_POST['purchased'] === 'yes') ? 1 : 0;
        $telegram = (isset($_POST['telegram']) && $_POST['telegram'] === 'on') ? 1 : 0;
        $reason = $purchased ? 'N/A' : ($_POST['reason'] ?? '');
        $notes = $_POST['notes'] ?? '';
        $date = date('Y-m-d');

        $query = "INSERT INTO call_tracker (name, phone, product, size, location, call_type, purchased, telegram, reason, notes, date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        try {
            $success = $stmt->execute([$name, $phone, $product, $size, $location, $call_type, $purchased, $telegram, $reason, $notes, $date]);
            echo json_encode(['success' => $success]);
        }
        catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $product = $_POST['product'] ?? '';
        $size = $_POST['size'] ?? '';
        $location = $_POST['location'] ?? '';
        $call_type = $_POST['call_type'] ?? 'normal';
        $purchased = ($_POST['purchased'] === 'yes') ? 1 : 0;
        $telegram = (isset($_POST['telegram']) && $_POST['telegram'] === 'on') ? 1 : 0;
        $reason = $purchased ? 'N/A' : ($_POST['reason'] ?? '');
        $notes = $_POST['notes'] ?? '';
        $date = $_POST['date'] ?? date('Y-m-d');

        $query = "UPDATE call_tracker SET name=?, phone=?, product=?, size=?, location=?, call_type=?, purchased=?, telegram=?, reason=?, notes=?, date=? WHERE id=?";
        $stmt = $db->prepare($query);
        try {
            $success = $stmt->execute([$name, $phone, $product, $size, $location, $call_type, $purchased, $telegram, $reason, $notes, $date, $id]);
            echo json_encode(['success' => $success]);
        }
        catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $query = "DELETE FROM call_tracker WHERE id = ?";
        $stmt = $db->prepare($query);
        $success = $stmt->execute([$id]);
        echo json_encode(['success' => $success]);
        exit;
    }
}

// Ensure call_tracker table exists (auto-create on live server if missing)
try {
    $db->exec("CREATE TABLE IF NOT EXISTS call_tracker (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(255) NOT NULL,
        phone       VARCHAR(50)  DEFAULT '',
        product     VARCHAR(255) NOT NULL,
        size        VARCHAR(50)  DEFAULT '',
        location    VARCHAR(255) DEFAULT '',
        call_type   VARCHAR(50)  DEFAULT 'normal',
        purchased   TINYINT(1)   DEFAULT 0,
        telegram    TINYINT(1)   DEFAULT 0,
        reason      VARCHAR(255) DEFAULT '',
        notes       TEXT,
        date        DATE         NOT NULL,
        created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
catch (Exception $e) {
// Log silently — table likely already exists
}

// Fetch records for the initial page load
$records = [];
try {
    $query = "SELECT * FROM call_tracker ORDER BY date DESC, created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
catch (Exception $e) {
    $records = []; // Graceful fallback — page still renders
}

// Process records for JS
foreach ($records as &$r) {
    $r['purchased'] = (bool)$r['purchased'];
    $r['telegram'] = (bool)$r['telegram'];
}

require_once "../includes/header.php";
?>

<!-- Scoped Tailwind for conflict prevention -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        corePlugins: {
            preflight: false,
        }
    }
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');
    #call-tracker-dashboard {
        font-family: 'DM Sans', sans-serif;
    }
    #call-tracker-dashboard .tab-active { 
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); 
        color: white; 
    }
    #call-tracker-dashboard .stat-card { transition: all 0.3s ease; }
    #call-tracker-dashboard .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    #call-tracker-dashboard .toast { animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s; }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
    #call-tracker-dashboard .loading-spinner { animation: spin 1s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    
    /* Fix Bootstrap z-index issues if any */
    .modal-backdrop { display: none; }
    #call-tracker-dashboard button { border: none; cursor: pointer; }
    #call-tracker-dashboard input, #call-tracker-dashboard select, #call-tracker-dashboard textarea {
        font-family: 'DM Sans', sans-serif;
    }
</style>

<div id="call-tracker-dashboard" class="bg-slate-50 min-h-screen pb-20">
  <div class="h-full w-full flex flex-col">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shadow-sm">
     <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
       <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
      </div>
      <div>
       <h1 class="text-xl font-bold text-slate-800 m-0">Call Tracker</h1>
       <p class="text-xs text-slate-500 m-0">Sales Follow-up Analytics</p>
      </div>
     </div>
     <div class="flex items-center gap-2">
      <span class="text-sm text-slate-500" id="current-date"></span>
      <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
     </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="bg-white border-b border-slate-200 px-6 py-3">
     <div class="flex gap-2">
      <button onclick="setActiveTab('dashboard')" id="tab-dashboard" class="tab-active px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-all cursor-pointer">
       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
       Dashboard
      </button>
      <button onclick="setActiveTab('new-call')" id="tab-new-call" class="px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 text-slate-600 bg-white hover:bg-slate-100 transition-all cursor-pointer">
       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
       New Call
      </button>
      <button onclick="setActiveTab('history')" id="tab-history" class="px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 text-slate-600 bg-white hover:bg-slate-100 transition-all cursor-pointer">
       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
       History
      </button>
     </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 p-6">
     <!-- Dashboard View -->
     <div id="view-dashboard" class="space-y-6">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
       <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200">
        <div class="flex items-center justify-between mb-3 text-sm">
         <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
         </div>
         <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-full">Today</span>
        </div>
        <p class="text-2xl font-bold text-slate-800 m-0" id="stat-calls-today">0</p>
        <p class="text-sm text-slate-500 m-0">Calls Today</p>
       </div>
       <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200">
        <div class="flex items-center justify-between mb-3">
         <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
          <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
         </div>
         <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">Today</span>
        </div>
        <p class="text-2xl font-bold text-slate-800 m-0" id="stat-orders-today">0</p>
        <p class="text-sm text-slate-500 m-0">Orders Today</p>
       </div>
       <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200">
        <div class="flex items-center justify-between mb-3 text-sm">
         <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
          <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
         </div>
        </div>
        <p class="text-2xl font-bold text-slate-800 m-0" id="stat-conversion">0%</p>
        <p class="text-sm text-slate-500 m-0">Conversion Rate</p>
       </div>
       <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200">
        <div class="flex items-center justify-between mb-3">
         <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
          <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
         </div>
         <span class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-1 rounded-full">Total</span>
        </div>
        <p class="text-2xl font-bold text-slate-800 m-0" id="stat-total-calls">0</p>
        <p class="text-sm text-slate-500 m-0">All Time Calls</p>
       </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
       <!-- Performance Chart -->
       <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200">
        <div class="flex items-center justify-between mb-6">
         <h3 class="text-lg font-semibold text-slate-800 m-0">Performance Overview</h3>
         <div class="flex gap-2">
           <button onclick="setReportRange('daily')" id="range-daily" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-500 text-white cursor-pointer">Daily</button> 
           <button onclick="setReportRange('weekly')" id="range-weekly" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer">Weekly</button> 
           <button onclick="setReportRange('monthly')" id="range-monthly" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer">Monthly</button>
         </div>
        </div>
        <div class="h-64">
         <canvas id="performanceChart"></canvas>
        </div>
       </div>
       <!-- Reasons Chart -->
       <div class="bg-white rounded-2xl p-6 border border-slate-200 text-center">
        <h3 class="text-lg font-semibold text-slate-800 mb-6 text-left m-0">Rejection Reasons</h3>
        <div class="h-48 flex items-center justify-center">
         <canvas id="reasonsChart"></canvas>
        </div>
        <div id="reasons-legend" class="mt-4 space-y-2"></div>
       </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200">
       <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-800 m-0">Recent Activity</h3>
        <button onclick="openShareModal()" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-medium rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all flex items-center gap-2 cursor-pointer">
         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C9.589 12.861 10.846 12 12 12c1.154 0 2.411.861 3.316 1.342m0 0a9.005 9.005 0 01-8.632 0m0 0a9.005 9.005 0 001.032-4.19m0 0H21m-21 0a9 9 0 0118.364-4.19m0 0H3.525m21-1.745a9 9 0 00-18.364 0m15.75 0a4.5 4.5 0 11-9 0" /></svg>
         Share Report
        </button>
       </div>
       <div id="recent-activity" class="space-y-3">
        <p class="text-slate-500 text-center py-8">No recent activity</p>
       </div>
      </div>
     </div>

     <!-- New Call View -->
     <div id="view-new-call" class="hidden">
      <div class="max-w-2xl mx-auto">
       <div class="bg-white rounded-2xl p-8 border border-slate-200">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">Log New Call</h2>
        <form id="call-form" class="space-y-6">
         <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div><label class="block text-sm font-medium text-slate-700 mb-2">Customer Name *</label> <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="Enter customer name"></div>
          <div><label class="block text-sm font-medium text-slate-700 mb-2">Phone Number</label> <input type="tel" name="phone" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="Enter phone number"></div>
          <div><label class="block text-sm font-medium text-slate-700 mb-2">Product *</label> <input type="text" name="product" required class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="Enter product name"></div>
          <div><label class="block text-sm font-medium text-slate-700 mb-2">Size</label> <input type="text" name="size" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="e.g. 42, M, L"></div>
          <div><label class="block text-sm font-medium text-slate-700 mb-2">Location</label> <input type="text" name="location" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="City or region"></div>
         </div>
         <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
           <label class="block text-sm font-medium text-slate-700 mb-2">Call Type</label>
           <div class="flex flex-col gap-3">
            <label class="flex items-center gap-2 cursor-pointer"> <input type="radio" name="call_type" value="normal" checked class="w-4 h-4 text-blue-600"> <span class="text-sm text-slate-700">Normal Call</span> </label>
            <label class="flex items-center gap-2 cursor-pointer"> <input type="radio" name="call_type" value="ring_once" class="w-4 h-4 text-blue-600"> <span class="text-sm text-slate-700">Ring Once</span> </label>
            <label class="flex items-center gap-2 cursor-pointer"> <input type="radio" name="call_type" value="info_request" class="w-4 h-4 text-blue-600"> <span class="text-sm text-slate-700">Information Request</span> </label>
           </div>
          </div>
          <div>
           <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
           <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer"> <input type="radio" name="purchased" value="yes" class="w-4 h-4 text-blue-600"> <span class="text-sm text-slate-700">Purchased</span> </label>
            <label class="flex items-center gap-2 cursor-pointer"> <input type="radio" name="purchased" value="no" checked class="w-4 h-4 text-blue-600"> <span class="text-sm text-slate-700">No Purchase</span> </label>
           </div>
          </div>
         </div>
         <div><label class="flex items-center gap-3 cursor-pointer"> <input type="checkbox" name="telegram" class="w-5 h-5 rounded text-blue-600"> <span class="text-sm font-medium text-slate-700">Sent to Telegram</span> </label></div>
         <div id="reason-field" class="hidden">
          <label class="block text-sm font-medium text-slate-700 mb-2">Reason for not purchasing</label>
          <select name="reason" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all">
            <option value="">Select a reason</option>
            <option value="Price too high">Price too high</option>
            <option value="Size out of stock">Size out of stock</option>
            <option value="Changed mind">Changed mind</option>
            <option value="Will think about it">Will think about it</option>
            <option value="Found cheaper elsewhere">Found cheaper elsewhere</option>
            <option value="Other">Other</option>
          </select>
         </div>
         <div><label class="block text-sm font-medium text-slate-700 mb-2">Notes</label> <textarea name="notes" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" rows="4" placeholder="Write down the reason and what the customer said..."></textarea></div>
         <button type="submit" id="submit-btn" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold py-4 rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all flex items-center justify-center gap-2 cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
          Log Call
         </button>
        </form>
       </div>
      </div>
     </div>

     <!-- History View -->
     <div id="view-history" class="hidden">
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
       <div class="p-6 border-b border-slate-200 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-xl font-bold text-slate-800 m-0">Call History</h2>
        <div class="flex flex-wrap gap-2 items-center">
         <select id="filter-date" onchange="renderHistoryTable()" class="px-3 py-2 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
          <option value="all">All Time</option>
          <option value="today">Today</option>
          <option value="7days">Last 7 Days</option>
          <option value="30days">Last 30 Days</option>
         </select>
         <select id="filter-status" onchange="renderHistoryTable()" class="px-3 py-2 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
          <option value="all">All Statuses</option>
          <option value="success">Success</option>
          <option value="nosale">No Sale</option>
         </select>
         <select id="filter-type" onchange="renderHistoryTable()" class="px-3 py-2 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
          <option value="all">All Types</option>
          <option value="normal">Normal Call</option>
          <option value="ring_once">Ring Once</option>
          <option value="info_request">Info Request</option>
         </select>
         <input type="text" id="search-input" oninput="renderHistoryTable()" placeholder="Search..." class="px-4 py-2 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
        </div>
       </div>
       <div class="overflow-x-auto">
        <table class="w-full text-left">
         <thead class="bg-slate-50">
          <tr>
           <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
           <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
           <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Phone</th>
           <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Product</th>
           <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Size</th>
           <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
           <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
           <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
          </tr>
         </thead>
         <tbody id="history-table" class="divide-y divide-slate-200">
          <tr><td colspan="8" class="px-6 py-12 text-center text-slate-500">Loading records...</td></tr>
         </tbody>
        </table>
       </div>
      </div>
     </div>
    </main>
    
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>


  </div>
</div>

<!-- Consolidated Modals -->
<!-- Delete Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-[99999]">
 <div class="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 shadow-2xl text-center">
  <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
   <svg class="w-8 h-8" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
  </div>
  <h3 class="text-xl font-bold text-slate-800 mb-2">Delete Record?</h3>
  <p class="text-slate-500 mb-8 font-sans">This action cannot be undone. This call log will be permanently removed.</p>
  <div class="flex gap-3">
   <button onclick="closeDeleteModal()" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 font-medium rounded-xl hover:bg-slate-200 cursor-pointer font-sans">Cancel</button> 
   <button id="confirm-delete-btn" class="flex-1 px-4 py-3 bg-red-500 text-white font-medium rounded-xl hover:bg-red-600 cursor-pointer font-sans">Delete</button>
  </div>
 </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-[99999] overflow-y-auto">
 <div class="bg-white rounded-xl p-5 max-w-xl w-full mx-4 my-4 shadow-2xl relative">
  <button onclick="closeEditModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 bg-transparent border-0 cursor-pointer p-1">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
  </button>
  <h2 class="text-lg font-bold text-slate-800 mb-4 font-sans">Edit Call Record</h2>
  <form id="edit-form" class="space-y-3">
   <div class="grid grid-cols-2 gap-3 font-sans">
    <div><label class="block text-xs font-medium text-slate-600 mb-1">Customer Name *</label> <input type="text" name="name" required class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all" placeholder="Customer name"></div>
    <div><label class="block text-xs font-medium text-slate-600 mb-1">Phone Number</label> <input type="tel" name="phone" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all" placeholder="Phone number"></div>
    <div><label class="block text-xs font-medium text-slate-600 mb-1">Product *</label> <input type="text" name="product" required class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all" placeholder="Product name"></div>
    <div><label class="block text-xs font-medium text-slate-600 mb-1">Size</label> <input type="text" name="size" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all" placeholder="42, M, L"></div>
    <div><label class="block text-xs font-medium text-slate-600 mb-1">Location</label> <input type="text" name="location" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all" placeholder="City or region"></div>
    <div><label class="block text-xs font-medium text-slate-600 mb-1">Date</label> <input type="date" name="date" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all"></div>
   </div>
   <div class="grid grid-cols-2 gap-3 font-sans">
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">Call Type</label>
      <div class="flex flex-col gap-1.5">
        <label class="flex items-center gap-2 cursor-pointer"> <input type="radio" name="call_type" value="normal" class="w-3.5 h-3.5 text-blue-600"> <span class="text-xs text-slate-700">Normal Call</span> </label>
        <label class="flex items-center gap-2 cursor-pointer"> <input type="radio" name="call_type" value="ring_once" class="w-3.5 h-3.5 text-blue-600"> <span class="text-xs text-slate-700">Ring Once</span> </label>
        <label class="flex items-center gap-2 cursor-pointer"> <input type="radio" name="call_type" value="info_request" class="w-3.5 h-3.5 text-blue-600"> <span class="text-xs text-slate-700">Info Request</span> </label>
      </div>
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
      <div class="flex flex-col gap-1.5">
       <label class="flex items-center gap-2 cursor-pointer"> <input type="radio" name="purchased" value="yes" class="w-3.5 h-3.5 text-blue-600"> <span class="text-xs text-slate-700">Purchased</span> </label>
       <label class="flex items-center gap-2 cursor-pointer"> <input type="radio" name="purchased" value="no" class="w-3.5 h-3.5 text-blue-600"> <span class="text-xs text-slate-700">No Purchase</span> </label>
      </div>
      <div class="mt-2 font-sans">
        <label class="flex items-center gap-2 cursor-pointer"> 
            <input type="checkbox" name="telegram" class="w-3.5 h-3.5 rounded text-blue-600"> 
            <span class="text-xs font-medium text-slate-700">Sent to Telegram</span> 
        </label>
      </div>
    </div>
   </div>
   <div id="edit-reason-field" class="hidden font-sans">
    <label class="block text-xs font-medium text-slate-600 mb-1">Reason for not purchasing</label>
    <select name="reason" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all font-sans">
      <option value="">Select a reason</option>
      <option value="Price too high">Price too high</option>
      <option value="Size out of stock">Size out of stock</option>
      <option value="Changed mind">Changed mind</option>
      <option value="Will think about it">Will think about it</option>
      <option value="Found cheaper elsewhere">Found cheaper elsewhere</option>
      <option value="Other">Other</option>
    </select>
   </div>
   <div class="font-sans"><label class="block text-xs font-medium text-slate-600 mb-1">Notes</label> <textarea name="notes" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all font-sans" rows="2"></textarea></div>
   <input type="hidden" name="id" id="edit-id-input">
   <div class="flex gap-2 pt-1">
    <button type="button" onclick="closeEditModal()" class="flex-1 px-3 py-2 text-sm border border-slate-300 bg-white rounded-lg text-slate-700 font-medium hover:bg-slate-50 transition-all cursor-pointer font-sans">Cancel</button> 
    <button type="submit" id="save-edit-btn" class="flex-1 px-3 py-2 text-sm bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition-all cursor-pointer font-sans">Save Changes</button>
   </div>
  </form>
 </div>
</div>

<!-- Share Report Modal -->
<div id="share-modal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-[99999] overflow-y-auto">
 <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4 my-6 shadow-2xl relative">
  <h2 class="text-2xl font-bold text-slate-800 mb-6 font-sans">Share Report</h2>
  <div id="share-preview" class="bg-slate-50 rounded-xl p-6 border border-slate-200 max-h-96 overflow-y-auto mb-6 text-sm text-slate-700 whitespace-pre-wrap font-mono"></div>
  <div class="flex gap-3">
   <button onclick="copyReportToClipboard()" class="flex-1 px-4 py-3 bg-blue-500 text-white font-medium rounded-xl hover:bg-blue-600 transition-all flex items-center justify-center gap-2 cursor-pointer font-sans">Copy to Clipboard</button> 
   <button onclick="downloadReportCSV()" class="flex-1 px-4 py-3 bg-green-500 text-white font-medium rounded-xl hover:bg-green-600 transition-all flex items-center justify-center gap-2 cursor-pointer font-sans">Download CSV</button> 
   <button onclick="closeShareModal()" class="flex-1 px-4 py-3 border border-slate-300 bg-white rounded-xl text-slate-700 font-medium hover:bg-slate-50 cursor-pointer font-sans">Close</button>
  </div>
 </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    // App State Hydrated from PHP
    let records = <?php echo json_encode($records); ?>;
    let activeTab = 'dashboard';
    let reportRange = 'daily';
    let performanceChart = null;
    let reasonsChart = null;
    let deleteRecordId = null;

    // Toast Utility
    function showToast(message, type = 'success') {
      const container = document.getElementById('toast-container');
      const toast = document.createElement('div');
      toast.className = `toast px-4 py-3 rounded-xl shadow-lg flex items-center gap-2 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
      toast.innerHTML = `<span class="font-medium">${message}</span>`;
      container.appendChild(toast);
      setTimeout(() => toast.remove(), 3000);
    }

    // Navigation
    function setActiveTab(tab) {
      activeTab = tab;
      ['dashboard', 'new-call', 'history'].forEach(t => {
        document.getElementById(`view-${t}`).classList.toggle('hidden', t !== tab);
        const btn = document.getElementById(`tab-${t}`);
        if (t === tab) {
            btn.classList.add('tab-active');
            btn.classList.remove('text-slate-600', 'hover:bg-slate-100');
        } else {
            btn.classList.remove('tab-active');
            btn.classList.add('text-slate-600', 'hover:bg-slate-100');
        }
      });
      if (tab === 'dashboard') updateCharts();
    }

    function setReportRange(range) {
      reportRange = range;
      ['daily', 'weekly', 'monthly'].forEach(r => {
        const btn = document.getElementById(`range-${r}`);
        if (r === range) {
            btn.classList.add('bg-blue-500', 'text-white');
            btn.classList.remove('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
        } else {
            btn.classList.remove('bg-blue-500', 'text-white');
            btn.classList.add('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
        }
      });
      updateCharts();
    }

    // Calculations
    function getStats() {
      const today = new Date().toISOString().split('T')[0];
      const todayRecords = records.filter(r => r.date === today);
      const callsToday = todayRecords.length;
      const ordersToday = todayRecords.filter(r => r.purchased).length;
      const totalCalls = records.length;
      const totalSales = records.filter(r => r.purchased).length;
      const conversionRate = totalCalls > 0 ? ((totalSales / totalCalls) * 100).toFixed(1) : 0;

      return { callsToday, ordersToday, totalCalls, totalSales, conversionRate };
    }

    function updateStats() {
      const stats = getStats();
      document.getElementById('stat-calls-today').textContent = stats.callsToday;
      document.getElementById('stat-orders-today').textContent = stats.ordersToday;
      document.getElementById('stat-conversion').textContent = stats.conversionRate + '%';
      document.getElementById('stat-total-calls').textContent = stats.totalCalls;
    }

    // Charts
    function updateCharts() {
      const stats = getStats();
      updateStats();
      
      const chartData = [];
      if (reportRange === 'daily') {
          // Last 7 days
          for (let i = 6; i >= 0; i--) {
              const d = new Date();
              d.setDate(d.getDate() - i);
              const dateStr = d.toISOString().split('T')[0];
              const dayRecs = records.filter(r => r.date === dateStr);
              chartData.push({
                  label: d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
                  calls: dayRecs.length,
                  sales: dayRecs.filter(r => r.purchased).length
              });
          }
      } else if (reportRange === 'weekly') {
          // Last 4 weeks (7-day blocks)
          for (let i = 3; i >= 0; i--) {
              const start = new Date();
              start.setDate(start.getDate() - (i * 7) - 6);
              const end = new Date();
              end.setDate(end.getDate() - (i * 7));
              
              const weekRecs = records.filter(r => {
                  const rd = new Date(r.date);
                  return rd >= start && rd <= end;
              });
              chartData.push({
                  label: `W${4-i}`,
                  calls: weekRecs.length,
                  sales: weekRecs.filter(r => r.purchased).length
              });
          }
      } else {
          // Last 6 months
          for (let i = 5; i >= 0; i--) {
              const d = new Date();
              d.setMonth(d.getMonth() - i);
              const month = d.getMonth();
              const year = d.getFullYear();
              
              const monthRecs = records.filter(r => {
                  const rd = new Date(r.date);
                  return rd.getMonth() === month && rd.getFullYear() === year;
              });
              chartData.push({
                  label: d.toLocaleDateString('en-US', { month: 'short' }),
                  calls: monthRecs.length,
                  sales: monthRecs.filter(r => r.purchased).length
              });
          }
      }

      const perfCtx = document.getElementById('performanceChart').getContext('2d');
      if (performanceChart) performanceChart.destroy();
      performanceChart = new Chart(perfCtx, {
        type: 'bar',
        data: {
          labels: chartData.map(d => d.label),
          datasets: [
            { label: 'Calls', data: chartData.map(d => d.calls), backgroundColor: '#3b82f6', borderRadius: 4 },
            { label: 'Sales', data: chartData.map(d => d.sales), backgroundColor: '#10b981', borderRadius: 4 }
          ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: {
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
      });

      // Reasons Chart
      const rejectionReasons = {};
      records.filter(r => !r.purchased && r.reason && r.reason !== 'N/A').forEach(r => {
          rejectionReasons[r.reason] = (rejectionReasons[r.reason] || 0) + 1;
      });
      const reasonEntries = Object.entries(rejectionReasons);
      
      const reasonsCtx = document.getElementById('reasonsChart').getContext('2d');
      if (reasonsChart) reasonsChart.destroy();
      
      if (reasonEntries.length > 0) {
          reasonsChart = new Chart(reasonsCtx, {
              type: 'doughnut',
              data: {
                  labels: reasonEntries.map(e => e[0]),
                  datasets: [{
                      data: reasonEntries.map(e => e[1]),
                      backgroundColor: ['#ef4444', '#f59e0b', '#6366f1', '#ec4899', '#8b5cf6'],
                      borderWidth: 0
                  }]
              },
              options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
          });
          
          document.getElementById('reasons-legend').innerHTML = reasonEntries.map((e, i) => `
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-600">${e[0]}</span>
                <span class="font-bold">${e[1]}</span>
              </div>
          `).join('');
      } else {
          document.getElementById('reasons-legend').innerHTML = '<p class="text-slate-400 text-xs text-center m-0">No rejection data</p>';
      }
    }

    // CRUD Handlers
    async function apiRequest(formData) {
        try {
            const resp = await fetch(window.location.href, { method: 'POST', body: formData });
            return await resp.json();
        } catch (e) {
            return { success: false, error: e.message };
        }
    }

    function renderHistoryTable() {
        const tbody = document.getElementById('history-table');
        const search = document.getElementById('search-input').value.toLowerCase();
        const dateFilter = document.getElementById('filter-date').value;
        const statusFilter = document.getElementById('filter-status').value;
        const typeFilter = document.getElementById('filter-type').value;
        const locationFilter = document.getElementById('filter-location').value;
        const productFilter = document.getElementById('filter-product').value;
        
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const filtered = records.filter(r => {
            // Text Search Filter
            const matchesSearch = r.name.toLowerCase().includes(search) || 
                                  r.product.toLowerCase().includes(search) || 
                                  (r.phone && r.phone.includes(search));
            if (!matchesSearch) return false;

            // Date Filter
            if (dateFilter !== 'all') {
                const rDate = new Date(r.date);
                if (dateFilter === 'today') {
                    if (r.date !== today.toISOString().split('T')[0]) return false;
                } else if (dateFilter === '7days') {
                    const diffTime = Math.abs(today - rDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                    if (diffDays > 7) return false;
                } else if (dateFilter === '30days') {
                    const diffTime = Math.abs(today - rDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                    if (diffDays > 30) return false;
                }
            }

            // Status Filter
            if (statusFilter === 'success' && !r.purchased) return false;
            if (statusFilter === 'nosale' && r.purchased) return false;

            // Type Filter
            if (typeFilter !== 'all' && r.call_type !== typeFilter) return false;

            // Location Filter
            if (locationFilter !== 'all' && r.location !== locationFilter) return false;

            // Product Filter
            if (productFilter !== 'all' && r.product !== productFilter) return false;

            return true;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-12 text-center text-slate-500 font-sans">No matching records found</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(r => `
          <tr class="hover:bg-slate-50">
            <td class="px-6 py-4 text-sm text-slate-600 font-sans">${r.date}</td>
            <td class="px-6 py-4 text-sm font-medium text-slate-800 font-sans">${r.name}</td>
            <td class="px-6 py-4 text-sm text-slate-600 font-sans">${r.phone || '-'}</td>
            <td class="px-6 py-4 text-sm text-slate-600 font-sans">${r.product}</td>
            <td class="px-6 py-4 text-sm text-slate-600 font-sans">${r.size || '-'}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 uppercase font-sans">${r.call_type}</span>
            </td>
            <td class="px-6 py-4">
                <span class="px-2 py-0.5 rounded-full text-xs font-bold ${r.purchased ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'} font-sans">${r.purchased ? 'Success' : 'No Sale'}</span>
            </td>
            <td class="px-6 py-4 text-right">
                <button onclick="openEditModal(${r.id})" class="text-blue-500 bg-transparent border-0 cursor-pointer mr-2 hover:text-blue-700 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.364 3.636a2 2 0 112.828 2.828L11.828 15l-3 1 1-3 9.364-9.364z" /></svg></button>
                <button onclick="openDeleteModal(${r.id})" class="text-red-500 bg-transparent border-0 cursor-pointer hover:text-red-700 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
            </td>
          </tr>
        `).join('');
    }

    function renderRecentActivity() {
        const container = document.getElementById('recent-activity');
        const recent = records.slice(0, 5);
        
        if (recent.length === 0) {
            container.innerHTML = '<p class="text-slate-500 text-center py-8">No recent activity</p>';
            return;
        }

        container.innerHTML = recent.map(r => `
          <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-all border border-slate-100 mb-2">
            <div class="w-9 h-9 flex-shrink-0 rounded-full ${r.purchased ? 'bg-green-100 text-green-600' : 'bg-amber-100 text-amber-600'} flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">${r.purchased ? '<path d="M5 13l4 4L19 7" stroke-width="2" />' : '<path d="M6 18L18 6M6 6l12 12" stroke-width="2" />'}</svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 m-0 text-sm font-sans truncate">${r.name}${r.phone ? ' • ' + r.phone : ''}</p>
                <p class="text-xs text-slate-500 m-0 font-sans">${r.product} — <span class="${r.purchased ? 'text-green-600' : 'text-amber-600'} font-medium">${r.purchased ? 'Success' : 'No Purchase'}</span></p>
            </div>
            <span class="text-xs text-slate-400 font-sans flex-shrink-0">${r.date}</span>
            <div class="flex items-center gap-1 flex-shrink-0">
                <button onclick="openEditModal(${r.id})" title="Edit" class="w-7 h-7 flex items-center justify-center rounded-lg text-blue-500 hover:bg-blue-50 bg-transparent border-0 cursor-pointer transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.364 3.636a2 2 0 112.828 2.828L11.828 15l-3 1 1-3 9.364-9.364z" /></svg>
                </button>
                <button onclick="openDeleteModal(${r.id})" title="Delete" class="w-7 h-7 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 bg-transparent border-0 cursor-pointer transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </div>
          </div>
        `).join('');
    }

    // Modal Control
    function closeDeleteModal() { document.getElementById('delete-modal').classList.add('hidden'); deleteRecordId = null; }
    function openDeleteModal(id) { deleteRecordId = id; document.getElementById('delete-modal').classList.remove('hidden'); }
    
    function closeEditModal() { document.getElementById('edit-modal').classList.add('hidden'); }
    function openEditModal(id) {
        const r = records.find(item => item.id == id);
        if (!r) return;
        
        const form = document.getElementById('edit-form');
        form.name.value = r.name || '';
        form.phone.value = r.phone || '';
        form.product.value = r.product || '';
        form.size.value = r.size || '';
        form.location.value = r.location || '';
        form.call_type.value = r.call_type || 'normal';
        form.purchased.value = r.purchased ? 'yes' : 'no';
        form.telegram.checked = r.telegram;
        form.reason.value = r.reason || '';
        form.notes.value = r.notes || '';
        form.date.value = r.date || '';
        document.getElementById('edit-id-input').value = r.id;
        
        document.getElementById('edit-reason-field').classList.toggle('hidden', r.purchased);
        document.getElementById('edit-modal').classList.remove('hidden');
    }

    function openShareModal() {
        const stats = getStats();
        const preview = `CALL TRACKER REPORT - ${new Date().toLocaleDateString()}\n` +
            `Total Calls: ${stats.totalCalls}\n` +
            `Total Sales: ${stats.totalSales}\n` +
            `Conversion: ${stats.conversionRate}%\n\n` +
            `Recent Details:\n` +
            records.slice(0, 10).map(r => `- ${r.date}: ${r.name} (${r.product}) -> ${r.purchased ? 'WON' : 'LOSS'}`).join('\n');
            
        document.getElementById('share-preview').textContent = preview;
        document.getElementById('share-modal').classList.remove('hidden');
    }
    function closeShareModal() { document.getElementById('share-modal').classList.add('hidden'); }

    // CSV Download
    function downloadReportCSV() {
        let csv = "Date,Customer,Phone,Product,Size,Location,Type,Status,Reason,Notes\n";
        records.forEach(r => {
            csv += `"${r.date}","${r.name}","${r.phone}","${r.product}","${r.size}","${r.location}","${r.call_type}","${r.purchased ? 'WON' : 'LOSS'}","${r.reason}","${r.notes}"\n`;
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `call_report_${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        showToast('CSV generated successfully');
    }

    function copyReportToClipboard() {
        const text = document.getElementById('share-preview').textContent;
        navigator.clipboard.writeText(text);
        showToast('Copied to clipboard');
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
        
        // Initial Renders
        updateCharts();
        renderHistoryTable();
        renderRecentActivity();
        
        // Polling (optional) to keep recent activity fresh
        setInterval(() => {
            if (activeTab === 'dashboard') {
                document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
            }
        }, 60000);

        // Form Logic
        const newCallForm = document.getElementById('call-form');
        newCallForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(newCallForm);
            fd.append('action', 'create');
            fd.append('csrf_token', '<?php echo Auth::generateCSRF(); ?>');
            
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.textContent = 'Saving...';
            
            const res = await apiRequest(fd);
            if (res.success) {
                showToast('Call logged successfully!');
                window.location.reload();
            } else {
                showToast(res.error || 'Failed to save call', 'error');
                btn.disabled = false;
                btn.textContent = 'Log Call';
            }
        });

        const editForm = document.getElementById('edit-form');
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(editForm);
            fd.append('action', 'update');
            fd.append('csrf_token', '<?php echo Auth::generateCSRF(); ?>');
            const res = await apiRequest(fd);
            if (res.success) {
                showToast('Record updated!');
                window.location.reload();
            } else {
                showToast('Update failed', 'error');
            }
        });

        document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', deleteRecordId);
            fd.append('csrf_token', '<?php echo Auth::generateCSRF(); ?>');
            const res = await apiRequest(fd);
            if (res.success) {
                showToast('Record deleted');
                window.location.reload();
            } else {
                showToast('Delete failed', 'error');
            }
        });

        // Toggle reason fields
        document.querySelectorAll('input[name="purchased"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                document.getElementById('reason-field').classList.toggle('hidden', e.target.value === 'yes');
                // The edit field needs its own toggle
            });
        });
        
        // Setup toggle for edit form purchased radio
        const editPurchasedRadios = document.querySelectorAll('#edit-form input[name="purchased"]');
        editPurchasedRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                document.getElementById('edit-reason-field').classList.toggle('hidden', e.target.value === 'yes');
            });
        });

        document.getElementById('search-input').addEventListener('input', renderHistoryTable);

        // Initial Renders
        updateCharts();
        renderRecentActivity();
        renderHistoryTable();
    });
</script>

<?php require_once "../includes/footer.php"; ?>