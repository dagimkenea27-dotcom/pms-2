<?php
require_once "config/auth.php";
require_once "config/database.php";

Auth::checkAuthAndPreventCache();

$page_title = "Daily Sales Tracker";
require_once "includes/header.php";
?>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    // Configure Tailwind to avoid clashing with Bootstrap if possible
    // though here we'll just use it as is for the specific section
    tailwind.config = {
        corePlugins: {
            preflight: false, // Disable base resets to avoid breaking Bootstrap
        }
    }
</script>

<style>
    /* Scoped styles to mimic the user's design but fit in the project */
    #daily-sales-app {
        font-family: 'Poppins', sans-serif;
    }
    
    #daily-sales-app .mono-font {
        font-family: 'Roboto Mono', monospace;
    }
    
    #daily-sales-app .sale-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    
    #daily-sales-app .sale-card {
        transition: all 0.2s ease;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    #daily-sales-app .animate-slide {
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    #daily-sales-app .fade-in {
        animation: fadeIn 0.3s ease;
    }
    
    #daily-sales-app .presentation-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    #daily-sales-app .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
    }
    
    #daily-sales-app input:focus, 
    #daily-sales-app select:focus, 
    #daily-sales-app textarea:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
    }
    
    #daily-sales-app .size-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        background: #e0e7ff;
        color: #4338ca;
    }
    
    #daily-sales-app .product-badge-khaki {
        background: #fef3c7;
        color: #92400e;
    }
    
    #daily-sales-app .product-badge-jeans {
        background: #dbeafe;
        color: #1e3a8a;
    }
    
    #daily-sales-app .followup-badge {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<div id="daily-sales-app" class="bg-slate-50 rounded-lg shadow-sm p-2">
  <div id="app" class="w-full">
    <!-- Main View (Add Sales) -->
    <div id="main-view" class="p-2 md:p-4 max-w-7xl mx-auto">
      <!-- Header -->
      <header class="mb-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
          <div>
            <h1 id="store-name" class="text-2xl font-bold text-slate-800 mb-0">📊 Daily Sales Track</h1>
            <p class="text-slate-500 text-xs">Track every sale and results</p>
          </div>
          <div class="flex gap-2">
            <button id="followup-btn" class="bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white font-semibold py-2 px-4 rounded-xl shadow-md transition-all flex items-center gap-2 justify-center text-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              Follow-ups (<span id="followup-count">0</span>)
            </button>
            <button id="present-btn" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-2 px-4 rounded-xl shadow-md transition-all flex items-center gap-2 justify-center text-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
              Present Day
            </button>
          </div>
        </div>
      </header>

      <!-- Today Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="bg-white rounded-xl p-3 shadow-sm border border-slate-200">
          <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-0">Today's Sales</div>
          <div id="stat-today-sales" class="text-xl font-bold text-slate-800 mono-font">0</div>
        </div>
        <div class="bg-white rounded-xl p-3 shadow-sm border border-slate-200">
          <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-0">Purchased</div>
          <div id="stat-today-purchased" class="text-xl font-bold text-emerald-600 mono-font">0</div>
        </div>
        <div class="bg-white rounded-xl p-3 shadow-sm border border-slate-200">
          <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-0">Kaki Pants</div>
          <div id="stat-khaki" class="text-xl font-bold text-amber-600 mono-font">0</div>
        </div>
        <div class="bg-white rounded-xl p-3 shadow-sm border border-slate-200">
          <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-0">Jeans</div>
          <div id="stat-jeans" class="text-xl font-bold text-blue-600 mono-font">0</div>
        </div>
      </div>

      <!-- Performance Summary -->
      <div id="performance-summary" class="hidden mb-6">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl shadow-xl overflow-hidden">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-white font-bold text-xl flex items-center gap-2">📊 Today's Performance</h3>
              <span id="performance-time" class="text-purple-100 text-sm font-medium mono-font"></span>
            </div>
            <div id="performance-content" class="bg-white/10 backdrop-blur-sm rounded-xl p-6"></div>
          </div>
        </div>
      </div>

      <!-- Add New Sale Form -->
      <div class="bg-white rounded-xl shadow-md border border-slate-200 mb-4 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-4 py-2">
          <h2 class="text-white font-bold text-base flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add New Sale
          </h2>
        </div>
        <form id="add-form" class="p-4">
          <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div class="md:col-span-1">
              <label for="input-date" class="block text-[11px] font-semibold text-slate-700 mb-1">Date</label>
              <input type="date" id="input-date" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-xs bg-slate-50 focus:bg-white focus:border-purple-500 transition-all" required>
            </div>
            <div class="md:col-span-2">
              <label class="block text-[11px] font-semibold text-slate-700 mb-1">Product Type</label>
              <div class="flex gap-2">
                <label class="flex items-center gap-1 bg-slate-50 hover:bg-slate-100 px-2 py-2 rounded-lg border-2 border-slate-200 cursor-pointer transition-all text-xs">
                  <input type="checkbox" id="checkbox-khaki" class="w-4 h-4 text-purple-600 rounded" value="Kaki Pants">
                  <span>👖 Kaki</span>
                </label>
                <input type="number" id="khaki-quantity" min="1" max="10" value="1" class="w-12 px-1 py-2 rounded-lg border-2 border-slate-200 text-center text-xs bg-slate-50" style="display: none;">
                <label class="flex items-center gap-1 bg-slate-50 hover:bg-slate-100 px-2 py-2 rounded-lg border-2 border-slate-200 cursor-pointer transition-all text-xs">
                  <input type="checkbox" id="checkbox-jeans" class="w-4 h-4 text-purple-600 rounded" value="Jeans">
                  <span>👖 Jeans</span>
                </label>
                <input type="number" id="jeans-quantity" min="1" max="10" value="1" class="w-12 px-1 py-2 rounded-lg border-2 border-slate-200 text-center text-xs bg-slate-50" style="display: none;">
              </div>
            </div>
            <div class="md:col-span-1">
              <label for="input-color" class="block text-[11px] font-semibold text-slate-700 mb-1">Color</label>
              <select id="input-color" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-xs bg-slate-50 focus:bg-white focus:border-purple-500 transition-all">
                <option value="">Select color...</option>
                <option value="Black">Black</option>
                <option value="Blue">Blue</option>
                <option value="Grey">Grey</option>
                <option value="Beige">Beige</option>
                <option value="Khaki">Khaki</option>
                <option value="Olive">Olive</option>
                <option value="Navy">Navy</option>
                <option value="Brown">Brown</option>
                <option value="White">White</option>    
                <option value="Yellow">Yellow</option>
                <option value="Pink">Pink</option>
                <option value="Green">Green</option>
                <option value="DarkFade">Dark Fade</option>
                <option value="LightFade">Light Fade</option>   
                
              </select>
            </div>
            <div class="md:col-span-1" id="khaki-size-container" style="display: none;">
              <label for="input-khaki-size" class="block text-[11px] font-semibold text-slate-700 mb-1">Kaki Size</label>
              <select id="input-khaki-size" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-xs bg-slate-50 focus:bg-white focus:border-purple-500 transition-all">
                <option value="">Size...</option>
              </select>
            </div>
            <div class="md:col-span-1" id="jeans-size-container" style="display: none;">
              <label for="input-jeans-size" class="block text-[11px] font-semibold text-slate-700 mb-1">Jeans Size</label>
              <select id="input-jeans-size" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-xs bg-slate-50 focus:bg-white focus:border-purple-500 transition-all">
                <option value="">Size...</option>
              </select>
            </div>
            <div class="md:col-span-1">
              <label for="input-price" class="block text-[11px] font-semibold text-slate-700 mb-1">Price</label>
              <input type="text" id="input-price" placeholder="0 Birr" readonly class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-xs mono-font bg-slate-100 text-slate-700 font-bold">
            </div>
            <div class="md:col-span-1 flex items-end">
              <button type="submit" id="add-btn" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition-all shadow-md flex items-center justify-center gap-2 text-sm">
                Add
              </button>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
            <div>
              <label for="input-customer" class="block text-[11px] font-semibold text-slate-700 mb-1">Customer Info</label>
              <input type="text" id="input-customer" placeholder="Name, phone..." class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-xs bg-slate-50 focus:bg-white transition-all">
            </div>
            <div class="relative">
              <label for="input-location" class="block text-[11px] font-semibold text-slate-700 mb-1">Location</label>
              <input type="text" id="input-location" placeholder="e.g., Bole..." class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-xs bg-slate-50 focus:bg-white transition-all" required autocomplete="off">
              <div id="location-dropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-48 overflow-y-auto text-xs"></div>
            </div>
            <div>
              <label for="input-notes" class="block text-[11px] font-semibold text-slate-700 mb-1">Notes</label>
              <input type="text" id="input-notes" placeholder="Additional notes..." class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-xs bg-slate-50 focus:bg-white transition-all">
            </div>
          </div>
        </form>
      </div>

      <!-- Today's Sales List -->
      <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
          <h3 class="font-bold text-slate-800 text-lg">Today's Sales</h3>
        </div>
        <div id="sales-container" class="p-6"></div>
        <!-- Empty State -->
        <div id="empty-state" class="py-16 text-center hidden">
          <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center">
            <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
          </div>
          <h3 class="text-slate-600 font-semibold text-lg mb-2">No sales yet today</h3>
          <p class="text-slate-400 text-sm">Add your first sale above to get started</p>
        </div>
      </div>
    </div>

    <!-- Follow-up View -->
    <div id="followup-view" class="hidden p-4 md:p-6 max-w-7xl mx-auto">
      <button id="close-followup-btn" class="mb-6 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 px-6 rounded-xl transition-all flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Back to Sales
      </button>
      <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-red-500 to-rose-600 px-6 py-5">
          <h2 class="text-white font-bold text-2xl flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Follow-up Customers
          </h2>
          <p class="text-red-100 text-sm mt-1">Customers who requested sizes above 38</p>
        </div>
        <div id="followup-container" class="p-6"></div>
        <div id="followup-empty-state" class="py-16 text-center hidden">
          <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-emerald-100 flex items-center justify-center">
            <svg class="w-10 h-10 text-emerald-500" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <h3 class="text-slate-600 font-semibold text-lg mb-2">No follow-ups needed</h3>
          <p class="text-slate-400 text-sm">All customers found their sizes!</p>
        </div>
      </div>
    </div>

    <!-- Presentation View -->
    <div id="presentation-view" class="hidden h-full w-full presentation-bg p-8 overflow-auto">
      <div class="max-w-6xl mx-auto">
        <button id="close-present-btn" class="mb-6 bg-white/20 hover:bg-white/30 text-white font-semibold py-2 px-6 rounded-xl backdrop-blur-sm transition-all flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
          Back to Tracking
        </button>
        <div class="glass-card rounded-3xl p-8 mb-6 text-center shadow-2xl relative overflow-hidden">
          <div class="absolute top-0 right-0 p-4">
              <a href="reports/daily_sales_report.php" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-4 rounded-lg shadow-sm transition-all no-underline inline-flex items-center gap-2">
                  <i class="fas fa-chart-line"></i> View History
              </a>
          </div>
          <h1 id="present-store-name" class="text-4xl font-bold text-slate-800 mb-2">Daily Sales Report</h1>
          <p id="present-date" class="text-xl text-slate-600 font-medium mono-font"></p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div class="glass-card rounded-3xl p-6 text-center shadow-xl">
            <div class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-2">Total Sales</div>
            <div id="present-total" class="text-5xl font-bold text-slate-800 mono-font">0</div>
          </div>
          <div class="glass-card rounded-3xl p-6 text-center shadow-xl">
            <div class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-2">Completed</div>
            <div id="present-purchased" class="text-5xl font-bold text-emerald-600 mono-font">0</div>
          </div>
          <div class="glass-card rounded-3xl p-6 text-center shadow-xl">
            <div class="text-sm font-bold text-amber-700 uppercase tracking-wide mb-2">Kaki Pants</div>
            <div id="present-khaki" class="text-5xl font-bold text-amber-600 mono-font">0</div>
          </div>
          <div class="glass-card rounded-3xl p-6 text-center shadow-xl">
            <div class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-2">Jeans</div>
            <div id="present-jeans" class="text-5xl font-bold text-blue-600 mono-font">0</div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div class="glass-card rounded-3xl p-6 shadow-xl">
            <h3 class="text-2xl font-bold text-amber-700 mb-4 flex items-center gap-2">👖 Kaki Pants</h3>
            <div id="khaki-breakdown" class="space-y-2"></div>
          </div>
          <div class="glass-card rounded-3xl p-6 shadow-xl">
            <h3 class="text-2xl font-bold text-blue-700 mb-4 flex items-center gap-2">👖 Jeans</h3>
            <div id="jeans-breakdown" class="space-y-2"></div>
          </div>
        </div>
        <div class="glass-card rounded-3xl p-6 shadow-xl">
          <h3 class="text-2xl font-bold text-slate-800 mb-4">All Sales Details</h3>
          <div id="present-sales-list" class="space-y-3"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
    // Mock Element SDK for project compatibility
    window.elementSdk = {
        init: (impl) => {
            impl.onConfigChange(impl.defaultConfig);
        },
        setConfig: (config) => {
            console.log('Config updated:', config);
        }
    };

    // Data SDK implementation talking to our PHP API
    window.dataSdk = {
        async init(handler) {
            this.handler = handler;
            try {
                const response = await fetch('api/daily_sales.php');
                const data = await response.json();
                if (data.isOk === false) throw new Error(data.message);
                this.handler.onDataChanged(data);
                return { isOk: true };
            } catch (e) {
                console.error('Data SDK Init Error:', e);
                return { isOk: false };
            }
        },
        async create(entry) {
            try {
                const response = await fetch('api/daily_sales.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(entry)
                });
                const result = await response.json();
                if (result.isOk) {
                    this.refresh();
                } else {
                    console.error('API Error:', result.message);
                    showToast('Error: ' + (result.message || 'Action failed'), 'error');
                }
                return result;
            } catch (e) {
                console.error('Network Error:', e);
                showToast('Network error, please check console', 'error');
                return { isOk: false };
            }
        },
        async update(entry) {
            try {
                const response = await fetch('api/daily_sales.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(entry)
                });
                const result = await response.json();
                if (result.isOk) {
                    this.refresh();
                } else {
                    console.error('API Error:', result.message);
                    showToast('Error: ' + (result.message || 'Action failed'), 'error');
                }
                return result;
            } catch (e) {
                console.error('Network Error:', e);
                showToast('Network error, please check console', 'error');
                return { isOk: false };
            }
        },
        async delete(entry) {
            try {
                const response = await fetch('api/daily_sales.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ __backendId: entry.__backendId })
                });
                const result = await response.json();
                if (result.isOk) {
                    this.refresh();
                } else {
                    console.error('API Error:', result.message);
                    showToast('Error: ' + (result.message || 'Action failed'), 'error');
                }
                return result;
            } catch (e) {
                console.error('Network Error:', e);
                showToast('Network error, please check console', 'error');
                return { isOk: false };
            }
        },
        async refresh() {
            try {
                const response = await fetch('api/daily_sales.php');
                const data = await response.json();
                if (Array.isArray(data)) {
                    this.handler.onDataChanged(data);
                } else if (data.isOk === false) {
                    console.error('Refresh failed:', data.message);
                }
            } catch (e) {
                console.error('Refresh error:', e);
            }
        }
    };
</script>

<script>
    // UI Logic (adapted from user's provided code)
    const defaultConfig = {
        store_name: 'Daily Sales Track',
        background_color: '#f8fafc',
        surface_color: '#ffffff',
        text_color: '#1e293b',
        primary_action_color: '#7c3aed',
        secondary_action_color: '#4f46e5'
    };

    let entries = [];
    let currentView = 'main';

    const mainView = document.getElementById('main-view');
    const presentationView = document.getElementById('presentation-view');
    const followupView = document.getElementById('followup-view');
    const salesContainer = document.getElementById('sales-container');
    const emptyState = document.getElementById('empty-state');
    const addForm = document.getElementById('add-form');
    const addBtn = document.getElementById('add-btn');
    const presentBtn = document.getElementById('present-btn');
    const closePresentBtn = document.getElementById('close-present-btn');
    const followupBtn = document.getElementById('followup-btn');
    const closeFollowupBtn = document.getElementById('close-followup-btn');
    const followupContainer = document.getElementById('followup-container');
    const followupEmptyState = document.getElementById('followup-empty-state');
    
    const khakiCheckbox = document.getElementById('checkbox-khaki');
    const jeansCheckbox = document.getElementById('checkbox-jeans');
    const khakiSizeInput = document.getElementById('input-khaki-size');
    const jeansSizeInput = document.getElementById('input-jeans-size');
    const khakiSizeContainer = document.getElementById('khaki-size-container');
    const jeansSizeContainer = document.getElementById('jeans-size-container');
    const khakiQuantityInput = document.getElementById('khaki-quantity');
    const jeansQuantityInput = document.getElementById('jeans-quantity');

    const dataHandler = {
        onDataChanged(data) {
            entries = data;
            renderSales();
            updateStats();
            updateFollowupCount();
        }
    };

    const elementImpl = {
        defaultConfig,
        onConfigChange: async (config) => {
            const storeName = config.store_name || defaultConfig.store_name;
            document.getElementById('store-name').textContent = `📊 ${storeName}`;
            document.getElementById('present-store-name').textContent = storeName;
        }
    };

    function getTodayDate() {
        return new Date().toISOString().split('T')[0];
    }

    function getTodayEntries() {
        const today = getTodayDate();
        return entries.filter(e => e.date === today);
    }

    function getFollowupEntries() {
        return entries.filter(e => e.needs_followup && !e.purchased);
    }

    function updateFollowupCount() {
        const count = getFollowupEntries().length;
        document.getElementById('followup-count').textContent = count;
    }

    function renderSales() {
        const todayEntries = getTodayEntries();
        if (todayEntries.length === 0) {
            salesContainer.innerHTML = '';
            emptyState.classList.remove('hidden');
            return;
        }
        emptyState.classList.add('hidden');
        const sortedEntries = [...todayEntries].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        salesContainer.innerHTML = sortedEntries.map(entry => createSaleCard(entry)).join('');
        attachSaleCardEvents();
    }

    function createSaleCard(entry) {
        const productBadgeClass = entry.product_type === 'Kaki Pants' ? 'product-badge-khaki' : 'product-badge-jeans';
        const time = new Date(entry.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        
        return `
            <div class="sale-card bg-slate-50 rounded-xl p-4 mb-3 border-2 border-slate-200 ${entry.purchased ? 'opacity-60' : ''}" data-entry-id="${entry.__backendId}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <span class="px-3 py-1 rounded-lg text-xs font-bold ${productBadgeClass}">${escapeHtml(entry.product_type || '')}</span>
                            <span class="size-badge">${escapeHtml(entry.size || '')}</span>
                            ${entry.color ? `<span class="bg-slate-200 text-slate-700 px-2 py-1 rounded-lg text-xs font-bold">${escapeHtml(entry.color)}</span>` : ''}
                            ${entry.needs_followup ? '<span class="followup-badge px-2 py-1 rounded-lg text-xs font-bold">📞 Follow-up</span>' : ''}
                            <span class="text-xs text-slate-400 mono-font">${time}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            ${entry.price ? `<div><span class="text-slate-500">Price:</span> <span class="font-semibold text-slate-700 mono-font">${escapeHtml(entry.price)}</span></div>` : ''}
                            ${entry.customer_info ? `<div><span class="text-slate-500">Customer:</span> <span class="font-medium text-slate-700">${escapeHtml(entry.customer_info)}</span></div>` : ''}
                            ${entry.customer_location ? `<div class="col-span-2"><span class="text-slate-500">📍 Location:</span> <span class="font-medium text-slate-700">${escapeHtml(entry.customer_location)}</span></div>` : ''}
                        </div>
                        ${entry.followup_reason ? `<div class="mt-2 text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg"><span class="font-semibold">Follow-up:</span> ${escapeHtml(entry.followup_reason)}</div>` : ''}
                        ${entry.notes ? `<div class="mt-2 text-sm text-slate-600"><span class="text-slate-500">Notes:</span> ${escapeHtml(entry.notes)}</div>` : ''}
                    </div>
                    <div class="flex flex-col gap-2">
                        <button class="purchase-btn ${entry.purchased ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'} hover:bg-emerald-200 font-semibold py-2 px-4 rounded-lg text-sm transition-all" title="Toggle Purchased">
                            ${entry.purchased ? '✓ Sold' : 'Mark Sold'}
                        </button>
                        <button class="delete-btn text-slate-400 hover:text-red-500 transition-colors p-2 rounded-lg hover:bg-red-50" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>`;
    }

    function renderFollowups() {
        const followupEntries = getFollowupEntries();
        if (followupEntries.length === 0) {
            followupContainer.innerHTML = '';
            followupEmptyState.classList.remove('hidden');
            return;
        }
        followupEmptyState.classList.add('hidden');
        const sortedEntries = [...followupEntries].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        followupContainer.innerHTML = sortedEntries.map(entry => createFollowupCard(entry)).join('');
        attachFollowupCardEvents();
    }

    function createFollowupCard(entry) {
        const productBadgeClass = entry.product_type === 'Kaki Pants' ? 'product-badge-khaki' : 'product-badge-jeans';
        const dateStr = new Date(entry.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        return `
            <div class="followup-card bg-red-50 rounded-xl p-5 mb-3 border-2 border-red-200" data-entry-id="${entry.__backendId}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-3 flex-wrap">
                            <span class="px-3 py-1 rounded-lg text-xs font-bold ${productBadgeClass}">${escapeHtml(entry.product_type || '')}</span>
                            <span class="size-badge">${escapeHtml(entry.size || '')}</span>
                            ${entry.color ? `<span class="bg-slate-200 text-slate-700 px-2 py-1 rounded-lg text-xs font-bold">${escapeHtml(entry.color)}</span>` : ''}
                            <span class="text-xs text-slate-500 mono-font">${dateStr}</span>
                        </div>
                        ${entry.customer_info ? `<div class="mb-2"><span class="text-sm font-semibold text-slate-700">Customer:</span> <span class="text-sm text-slate-600 ml-1">${escapeHtml(entry.customer_info)}</span></div>` : ''}
                        ${entry.customer_location ? `<div class="mb-3"><span class="text-sm font-semibold text-slate-700">📍 Location:</span> <span class="text-sm text-slate-600 ml-1">${escapeHtml(entry.customer_location)}</span></div>` : ''}
                        <div class="bg-white px-4 py-3 rounded-lg border border-red-200">
                            <div class="text-sm font-semibold text-red-700 mb-1">Follow-up Reason:</div>
                            <div class="text-sm text-slate-700">${escapeHtml(entry.followup_reason || '')}</div>
                        </div>
                        ${entry.notes ? `<div class="mt-3 text-sm text-slate-600"><span class="text-slate-500">Notes:</span> ${escapeHtml(entry.notes)}</div>` : ''}
                    </div>
                    <div class="flex flex-col gap-2">
                        <button class="purchase-followup-btn bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-2 px-5 rounded-lg text-sm transition-all">✓ Purchased</button>
                        <button class="cancel-followup-btn bg-slate-300 hover:bg-slate-400 text-slate-700 font-semibold py-2 px-5 rounded-lg text-sm transition-all">✗ Not Purchased</button>
                        <button class="delete-followup-btn text-slate-400 hover:text-red-500 transition-colors p-2 rounded-lg hover:bg-red-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                    </div>
                </div>
            </div>`;
    }

    function attachSaleCardEvents() {
        document.querySelectorAll('.sale-card').forEach(card => {
            const entryId = card.dataset.entryId;
            const entry = entries.find(e => e.__backendId == entryId);
            if (!entry) return;
            card.querySelector('.purchase-btn').onclick = () => togglePurchased(entry);
            card.querySelector('.delete-btn').onclick = () => handleDelete(entry);
        });
    }

    function attachFollowupCardEvents() {
        document.querySelectorAll('.followup-card').forEach(card => {
            const entryId = card.dataset.entryId;
            const entry = entries.find(e => e.__backendId == entryId);
            if (!entry) return;
            card.querySelector('.purchase-followup-btn').onclick = () => markFollowupPurchased(entry);
            card.querySelector('.cancel-followup-btn').onclick = () => markFollowupNotPurchased(entry);
            card.querySelector('.delete-followup-btn').onclick = () => handleDelete(entry);
        });
    }

    async function markFollowupPurchased(entry) {
        const updated = { ...entry, purchased: true, needs_followup: false };
        const result = await window.dataSdk.update(updated);
        if (result.isOk) showToast('✓ Customer purchased!', 'success');
    }

    async function markFollowupNotPurchased(entry) {
        const updated = { ...entry, purchased: false, needs_followup: false };
        const result = await window.dataSdk.update(updated);
        if (result.isOk) showToast('Follow-up marked as not purchased', 'info');
    }

    async function togglePurchased(entry) {
        const updated = { ...entry, purchased: !entry.purchased };
        const result = await window.dataSdk.update(updated);
    }

    async function handleDelete(entry) {
        if (!confirm('Are you sure you want to delete this sale?')) return;
        const result = await window.dataSdk.delete(entry);
        if (result.isOk) showToast('Sale deleted', 'success');
    }

    function updateStats() {
        const todayEntries = getTodayEntries();
        const purchased = todayEntries.filter(e => e.purchased).length;
        const khaki = todayEntries.filter(e => e.product_type === 'Kaki Pants' && e.purchased).length;
        const jeans = todayEntries.filter(e => e.product_type === 'Jeans' && e.purchased).length;

        document.getElementById('stat-today-sales').textContent = todayEntries.length;
        document.getElementById('stat-today-purchased').textContent = purchased;
        document.getElementById('stat-khaki').textContent = khaki;
        document.getElementById('stat-jeans').textContent = jeans;
        
        updatePerformanceSummary();
    }

    function updatePerformanceSummary() {
        const now = new Date();
        const performanceSummary = document.getElementById('performance-summary');
        // Simplified check (always show in this version if entries exist)
        if (entries.length > 0) {
            performanceSummary.classList.remove('hidden');
            renderPerformanceSummary();
        } else {
            performanceSummary.classList.add('hidden');
        }
    }

    function renderPerformanceSummary() {
        const todayEntries = getTodayEntries();
        const purchased = todayEntries.filter(e => e.purchased).length;
        const totalSales = todayEntries.length;
        const performanceContent = document.getElementById('performance-content');
        const performanceTime = document.getElementById('performance-time');
        
        performanceTime.textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        
        let rating = 'NEEDS IMPROVEMENT', msg = 'Today was challenging.', emoji = '📊', color = 'text-red-300';
        if (purchased >= 15) { rating = 'EXCELLENT'; msg = 'Outstanding work!'; emoji = '🌟'; color = 'text-yellow-300'; }
        else if (purchased >= 10) { rating = 'GREAT'; msg = 'Great job!'; emoji = '✨'; color = 'text-green-300'; }
        else if (purchased >= 7) { rating = 'GOOD'; msg = 'Good work!'; emoji = '👏'; color = 'text-blue-300'; }
        else if (purchased >= 4) { rating = 'FAIR'; msg = 'Room for improvement.'; emoji = '📈'; color = 'text-orange-300'; }

        performanceContent.innerHTML = `
            <div class="text-center mb-6">
                <div class="text-6xl mb-3">${emoji}</div>
                <div class="${color} text-3xl font-bold mb-2">${rating}</div>
                <div class="text-white text-lg font-medium">${msg}</div>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="bg-white/20 rounded-lg p-4 text-center">
                    <div class="text-white/80 text-sm font-semibold mb-1">Total Interactions</div>
                    <div class="text-white text-3xl font-bold mono-font">${totalSales}</div>
                </div>
                <div class="bg-white/20 rounded-lg p-4 text-center">
                    <div class="text-white/80 text-sm font-semibold mb-1">Completed Sales</div>
                    <div class="text-white text-3xl font-bold mono-font">${purchased}</div>
                </div>
            </div>`;
    }

    function showFollowup() {
        renderFollowups();
        mainView.classList.add('hidden');
        presentationView.classList.add('hidden');
        followupView.classList.remove('hidden');
        currentView = 'followup';
    }

    function closeFollowup() {
        followupView.classList.add('hidden');
        mainView.classList.remove('hidden');
        currentView = 'main';
    }

    function showPresentation() {
        const todayEntries = getTodayEntries();
        const purchased = todayEntries.filter(e => e.purchased);
        const khakiSales = purchased.filter(e => e.product_type === 'Kaki Pants');
        const jeansSales = purchased.filter(e => e.product_type === 'Jeans');

        document.getElementById('present-date').textContent = new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        document.getElementById('present-total').textContent = todayEntries.length;
        document.getElementById('present-purchased').textContent = purchased.length;
        document.getElementById('present-khaki').textContent = khakiSales.length;
        document.getElementById('present-jeans').textContent = jeansSales.length;

        renderSizeBreakdown('khaki-breakdown', khakiSales);
        renderSizeBreakdown('jeans-breakdown', jeansSales);
        renderPresentationSalesList(purchased);

        mainView.classList.add('hidden');
        presentationView.classList.remove('hidden');
        currentView = 'presentation';
    }

    function renderSizeBreakdown(elementId, sales) {
        const sizeCount = {};
        sales.forEach(sale => { sizeCount[sale.size] = (sizeCount[sale.size] || 0) + 1; });
        const container = document.getElementById(elementId);
        if (Object.keys(sizeCount).length === 0) { container.innerHTML = '<p class="text-slate-500 text-sm">No sales yet</p>'; return; }
        container.innerHTML = Object.entries(sizeCount).sort((a,b) => b[1]-a[1]).map(([size, count]) => `
            <div class="flex items-center justify-between bg-white/50 rounded-lg px-4 py-2">
                <span class="font-semibold text-slate-700">Size ${size}</span>
                <span class="text-2xl font-bold text-slate-800 mono-font">${count}</span>
            </div>`).join('');
    }

    function renderPresentationSalesList(sales) {
        const container = document.getElementById('present-sales-list');
        if (sales.length === 0) { container.innerHTML = '<p class="text-slate-500 text-center">No completed sales yet</p>'; return; }
        container.innerHTML = [...sales].sort((a,b) => new Date(b.created_at) - new Date(a.created_at)).map(sale => {
            const time = new Date(sale.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            return `
                <div class="bg-white/50 rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 rounded-lg text-xs font-bold ${sale.product_type === 'Kaki Pants' ? 'product-badge-khaki' : 'product-badge-jeans'}">${escapeHtml(sale.product_type)}</span>
                        <span class="size-badge">${escapeHtml(sale.size)}</span>
                        ${sale.color ? `<span class="bg-slate-200 text-slate-700 px-2 py-1 rounded-lg text-xs font-bold">${escapeHtml(sale.color)}</span>` : ''}
                        <span class="text-sm text-slate-600">${sale.price || ''}</span>
                    </div>
                    <span class="text-sm text-slate-400 mono-font">${time}</span>
                </div>`;
        }).join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-xl shadow-2xl text-white text-sm font-semibold z-[9999] animate-slide ${type === 'error' ? 'bg-red-500' : 'bg-emerald-500'}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    addForm.onsubmit = async (e) => {
        e.preventDefault();
        const dateInput = document.getElementById('input-date');
        const colorInput = document.getElementById('input-color');
        const locationInput = document.getElementById('input-location');
        if (!khakiCheckbox.checked && !jeansCheckbox.checked) { showToast('Please select at least one product', 'error'); return; }
        if (khakiCheckbox.checked && !khakiSizeInput.value) { showToast('Please select a size for Kaki Pants', 'error'); return; }
        if (jeansCheckbox.checked && !jeansSizeInput.value) { showToast('Please select a size for Jeans', 'error'); return; }

        addBtn.disabled = true;
        addBtn.innerHTML = 'Adding...';

        const entriesToCreate = [];
        if (khakiCheckbox.checked) {
            const qty = parseInt(khakiQuantityInput.value) || 1;
            const size = parseInt(khakiSizeInput.value);
            const needsFollowup = size > 38;
            for (let i=0; i<qty; i++) {
                entriesToCreate.push({
                    date: dateInput.value, product_type: 'Kaki Pants', size: size.toString(),
                    color: colorInput.value,
                    price: `${1550 + 200} Birr`, customer_info: document.getElementById('input-customer').value,
                    customer_location: locationInput.value, purchased: false, notes: document.getElementById('input-notes').value,
                    created_at: new Date().toISOString(), needs_followup: needsFollowup,
                    followup_reason: needsFollowup ? `Customer requested size ${size} (currently not in stock)` : ''
                });
            }
        }
        if (jeansCheckbox.checked) {
            const qty = parseInt(jeansQuantityInput.value) || 1;
            const size = parseInt(jeansSizeInput.value);
            const needsFollowup = size > 38;
            for (let i=0; i<qty; i++) {
                entriesToCreate.push({
                    date: dateInput.value, product_type: 'Jeans', size: size.toString(),
                    color: colorInput.value,
                    price: `${1150 + 200} Birr`, customer_info: document.getElementById('input-customer').value,
                    customer_location: locationInput.value, purchased: false, notes: document.getElementById('input-notes').value,
                    created_at: new Date().toISOString(), needs_followup: needsFollowup,
                    followup_reason: needsFollowup ? `Customer requested size ${size} (currently not in stock)` : ''
                });
            }
        }

        for (const entry of entriesToCreate) { await window.dataSdk.create(entry); }
        addBtn.disabled = false;
        addBtn.innerHTML = 'Add';
        addForm.reset();
        khakiSizeContainer.style.display = 'none';
        jeansSizeContainer.style.display = 'none';
        khakiQuantityInput.style.display = 'none';
        jeansQuantityInput.style.display = 'none';
        showToast('Sales added successfully!', 'success');
    };

    function populateSizes() {
        khakiSizeInput.innerHTML = '<option value="">Select size...</option>';
        for (let s=32; s<=42; s++) khakiSizeInput.add(new Option(s + (s>38?' (Follow-up)':''), s));
        jeansSizeInput.innerHTML = '<option value="">Select size...</option>';
        for (let s=31; s<=42; s++) jeansSizeInput.add(new Option(s + (s>38?' (Follow-up)':''), s));
    }

    function calculatePrice() {
        let total = 0;
        if (khakiCheckbox.checked) total += (1550 + 200) * (parseInt(khakiQuantityInput.value) || 1);
        if (jeansCheckbox.checked) total += (1150 + 200) * (parseInt(jeansQuantityInput.value) || 1);
        document.getElementById('input-price').value = total > 0 ? `${total} Birr` : '0 Birr';
    }

    khakiCheckbox.onchange = () => { 
        khakiSizeContainer.style.display = khakiCheckbox.checked ? 'block' : 'none'; 
        khakiQuantityInput.style.display = khakiCheckbox.checked ? 'block' : 'none'; 
        calculatePrice(); 
    };
    jeansCheckbox.onchange = () => { 
        jeansSizeContainer.style.display = jeansCheckbox.checked ? 'block' : 'none'; 
        jeansQuantityInput.style.display = jeansCheckbox.checked ? 'block' : 'none'; 
        calculatePrice(); 
    };
    khakiQuantityInput.oninput = calculatePrice;
    jeansQuantityInput.oninput = calculatePrice;

    presentBtn.onclick = showPresentation;
    closePresentBtn.onclick = () => { presentationView.classList.add('hidden'); mainView.classList.remove('hidden'); };
    followupBtn.onclick = showFollowup;
    closeFollowupBtn.onclick = closeFollowup;

    // Autocomplete Logic
    const locations = ['Bole', 'Piazza', 'Merkato', '6 Kilo', '4 Kilo', 'CMC', 'Gerji', 'Old Airport'];
    const locationInput = document.getElementById('input-location');
    const locationDropdown = document.getElementById('location-dropdown');
    locationInput.oninput = function() {
        const val = this.value.trim().toLowerCase();
        if (!val) { locationDropdown.classList.add('hidden'); return; }
        const matched = locations.filter(l => l.toLowerCase().startsWith(val));
        if (matched.length === 0) { locationDropdown.classList.add('hidden'); return; }
        locationDropdown.innerHTML = matched.map(l => `<div class="p-3 hover:bg-purple-50 cursor-pointer" onclick="document.getElementById('input-location').value='${l}'; document.getElementById('location-dropdown').classList.add('hidden')">${l}</div>`).join('');
        locationDropdown.classList.remove('hidden');
    };

    async function init() {
        document.getElementById('input-date').valueAsDate = new Date();
        populateSizes();
        window.elementSdk.init(elementImpl);
        await window.dataSdk.init(dataHandler);
    }
    init();
</script>

<?php require_once "includes/footer.php"; ?>
