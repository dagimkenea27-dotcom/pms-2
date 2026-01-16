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
    tailwind.config = {
        corePlugins: {
            preflight: false,
        }
    }
</script>

<!-- Custom Styles -->
<link rel="stylesheet" href="assets/css/daily_sales.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<div id="daily-sales-app" class="bg-slate-50 rounded-lg shadow-sm p-3">
  <div id="app" class="w-full">
    <!-- Main View -->
    <div id="main-view" class="p-2 md:p-4 max-w-7xl mx-auto mt-12 md:mt-0">
      
      <!-- App Header -->
      <header class="mb-2">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-1">
          <div>
            <h1 id="store-name" class="text-xl font-bold text-slate-800 mb-0">📊 Daily Sales Track</h1>
            <p class="text-slate-500 text-[10px]">Track every sale and results</p>
          </div>
          <div class="flex gap-2">
            <button id="followup-btn" class="bg-white border border-red-200 text-red-600 hover:bg-red-50 font-semibold py-1 px-3 rounded-lg shadow-sm transition-all flex items-center gap-1.5 justify-center text-xs">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              Follow-ups (<span id="followup-count">0</span>)
            </button>
            <button id="present-btn" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-1 px-3 rounded-lg shadow-md transition-all flex items-center gap-1.5 justify-center text-xs">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
              Report View
            </button>
          </div>
        </div>
      </header>

      <!-- Stats Overview -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3">
        <div class="bg-white rounded-lg p-2 shadow-sm border border-slate-200">
          <div class="text-[9px] font-bold text-slate-500 uppercase tracking-wide mb-0">Today's Sales</div>
          <div id="stat-today-sales" class="text-lg font-bold text-slate-800 mono-font">0</div>
        </div>
        <div class="bg-white rounded-lg p-2 shadow-sm border border-slate-200">
          <div class="text-[9px] font-bold text-slate-500 uppercase tracking-wide mb-0">Revenue</div>
          <div id="stat-today-revenue" class="text-lg font-bold text-emerald-600 mono-font">0 Birr</div>
        </div>
        <div class="bg-white rounded-lg p-2 shadow-sm border border-slate-200">
          <div class="text-[9px] font-bold text-slate-500 uppercase tracking-wide mb-0">Kaki Pants</div>
          <div id="stat-khaki" class="text-lg font-bold text-amber-600 mono-font">0</div>
        </div>
        <div class="bg-white rounded-lg p-2 shadow-sm border border-slate-200">
          <div class="text-[9px] font-bold text-slate-500 uppercase tracking-wide mb-0">Jeans</div>
          <div id="stat-jeans" class="text-lg font-bold text-blue-600 mono-font">0</div>
        </div>
      </div>

      <!-- Performance Banner -->
      <div id="performance-summary" class="hidden mb-6">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg text-white p-4 flex items-center justify-between">
           <div>
              <h3 class="font-bold text-lg">🚀 Today's Performance</h3>
              <p id="performance-msg" class="text-indigo-100 text-sm">Keep pushing!</p>
           </div>
           <div class="text-right">
              <div class="text-3xl font-bold mono-font" id="performance-score">Good</div>
           </div>
        </div>
      </div>

      <!-- Order Composer Area -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
          
          <!-- Left Column: Inputs (8 cols) -->
          <div class="lg:col-span-8 space-y-3">
              
              <!-- Unified Order Panel -->
              <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                  <div class="bg-white px-4 py-2 border-b border-slate-100 flex justify-between items-center">
                      <div>
                          <h3 class="font-bold text-slate-800 text-sm">New Sale Entry</h3>
                      </div>
                      <input type="date" id="input-date" class="bg-slate-50 border border-slate-200 text-slate-600 text-[10px] font-semibold rounded px-2 py-1 focus:ring-1 focus:ring-purple-100 outline-none">
                  </div>
                  
                  <div class="p-3">
                      <!-- Customer Section -->
                      <div class="mb-3">
                          <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1">Customer Details</h4>
                          <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                              <div>
                                  <label class="block text-[10px] font-medium text-slate-700 mb-0.5">Customer Name / Phone</label>
                                  <input type="text" id="input-customer" class="w-full text-xs border-slate-200 rounded-md bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-100 transition-all px-2 py-1.5" placeholder="e.g. Abebe +251...">
                              </div>
                              <div class="relative">
                                  <label class="block text-[10px] font-medium text-slate-700 mb-0.5">Location</label>
                                  <input type="text" id="input-location" class="w-full text-xs border-slate-200 rounded-md bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-100 transition-all px-2 py-1.5" placeholder="Search location..." autocomplete="off">
                                  <div id="location-dropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-32 overflow-y-auto text-xs"></div>
                              </div>
                              <div class="md:col-span-2">
                                  <label class="block text-[10px] font-medium text-slate-700 mb-0.5">Notes (Optional)</label>
                                  <input type="text" id="input-notes" class="w-full text-xs border-slate-200 rounded-md bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-100 transition-all px-2 py-1.5" placeholder="Delivery info, preferences...">
                              </div>
                          </div>
                      </div>

                      <!-- Item Section -->
                      <div id="item-input-card">
                          <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1">Item Details</h4>
                          <div class="grid grid-cols-2 md:grid-cols-8 gap-2 mb-2">
                              <div class="col-span-2 md:col-span-3">
                                  <label class="block text-[10px] font-medium text-slate-700 mb-0.5">Product</label>
                                  <select id="input-product-type" class="w-full text-xs border-slate-200 rounded-md bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-100 transition-all px-2 py-1.5">
                                      <option value="Kaki Pants">👖 Kaki Pants</option>
                                      <option value="Jeans">👖 Jeans</option>
                                  </select>
                              </div>
                              <div class="col-span-1 md:col-span-2">
                                  <label class="block text-[10px] font-medium text-slate-700 mb-0.5">Size</label>
                                  <select id="input-size" class="w-full text-xs border-slate-200 rounded-md bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-100 transition-all px-2 py-1.5">
                                      <option value="">Select...</option>
                                  </select>
                              </div>
                              <div class="col-span-1 md:col-span-2">
                                  <label class="block text-[10px] font-medium text-slate-700 mb-0.5">Color</label>
                                  <select id="input-color" class="w-full text-xs border-slate-200 rounded-md bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-100 transition-all px-2 py-1.5">
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
                              <div class="col-span-2 md:col-span-1">
                                  <label class="block text-[10px] font-medium text-slate-700 mb-0.5">Qty</label>
                                  <input type="number" id="input-quantity" value="1" min="1" max="50" class="w-full text-xs border-slate-200 rounded-md bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-100 text-center font-bold px-1 py-1.5">
                              </div>
                          </div>
                          
                          <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-3 bg-slate-50 p-2 rounded-lg border border-slate-100">
                              <div class="flex items-center gap-2">
                                  <div>
                                      <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Est. Price</span>
                                      <div id="display-price" class="text-sm font-bold text-slate-800 mono-font">0 Birr</div>
                                  </div>
                              </div>
                              
                              <div class="flex gap-2 w-full sm:w-auto">
                                  <button id="add-to-cart-btn" class="flex-1 sm:flex-none bg-slate-800 hover:bg-slate-900 text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-all flex items-center justify-center gap-1.5 active:scale-95">
                                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                      Add
                                  </button>
                                  <button id="update-btn" class="hidden flex-1 sm:flex-none bg-purple-600 hover:bg-purple-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-all flex items-center justify-center gap-1.5 active:scale-95">
                                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                      Update
                                  </button>
                                  <button id="cancel-edit-btn" class="hidden px-3 py-1.5 text-slate-500 hover:text-slate-800 font-semibold text-xs transition-colors">
                                      Cancel
                                  </button>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Right Column: Cart/Summary (4 cols) -->
          <div class="lg:col-span-4">
              <div id="cart-panel" class="bg-white rounded-xl shadow-lg border border-purple-100 sticky top-4 flex flex-col h-full max-h-[400px]">
                  <div class="bg-slate-900 p-3 text-white rounded-t-xl relative overflow-hidden">
                      <div class="absolute top-0 right-0 p-2 opacity-10">
                          <svg class="w-16 h-16" fill="currentColor" viewbox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                      </div>
                      <h3 class="font-bold text-sm flex items-center gap-1.5 z-10 relative">
                          Current Order
                      </h3>
                      <p class="text-slate-400 text-[10px] mt-0.5 z-10 relative">Review items</p>
                  </div>
                  
                  <div id="cart-list" class="flex-1 overflow-y-auto p-3 space-y-2 min-h-[120px] bg-slate-50/50">
                      <div class="flex flex-col items-center justify-center h-full text-slate-400 space-y-2 py-6 opacity-60">
                          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                          <span class="text-xs font-medium">Empty</span>
                      </div>
                  </div>

                  <div class="p-3 bg-white border-t border-slate-100 rounded-b-xl shadow-[0_-5px_15px_rgba(0,0,0,0.02)]">
                      <div class="flex justify-between items-end mb-3">
                          <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Total</span>
                          <span id="cart-total" class="text-xl font-bold text-slate-800 mono-font tracking-tight">0 <span class="text-xs text-slate-500 font-normal">Birr</span></span>
                      </div>
                      <button id="submit-order-btn" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white py-2 rounded-lg font-bold text-sm shadow-md shadow-purple-200 transition-all flex justify-center items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none transform active:scale-[0.98]">
                          Complete
                      </button>
                  </div>
              </div>
              
              <!-- Edit Mode Placeholder -->
              <div id="edit-mode-panel" class="hidden bg-amber-50 rounded-xl p-4 border-2 border-dashed border-amber-200 text-center h-full flex flex-col justify-center items-center">
                  <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mb-2 text-lg">✏️</div>
                  <h3 class="text-amber-800 font-bold text-sm mb-1">Editing</h3>
                  <p class="text-amber-600 text-xs max-w-xs mx-auto">Update details on the left.</p>
              </div>
          </div>
      </div>

      <!-- Sales List -->
      <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
          <h3 class="font-bold text-slate-800 text-lg">Today's Sales</h3>
          <div class="text-xs text-slate-500">Most recent first</div>
        </div>
        <div id="sales-container" class="p-6"></div>
      </div>
    </div>
    
    <!-- Includes -->
    <div id="presentation-view" class="hidden w-full min-h-screen p-4 md:p-8">
        <div id="presentation-content" class="w-full"></div>
    </div>
    
    <div id="followup-view" class="hidden w-full min-h-screen p-4 md:p-8">
        <div id="followup-content" class="w-full"></div>
    </div>

  </div>
</div>

<!-- Custom Script -->
<script src="assets/js/daily_sales.js"></script>

<?php require_once "includes/footer.php"; ?>
