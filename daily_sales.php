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

<style>
    /* Scoped styles */
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
    
    <!-- Include other views like presentation and follow-up (hidden by default) -->
    <!-- Include other views like presentation and follow-up (hidden by default) -->
    <div id="presentation-view" class="hidden w-full min-h-screen p-4 md:p-8">
        <div id="presentation-content" class="w-full"></div>
    </div>
    
    <div id="followup-view" class="hidden w-full min-h-screen p-4 md:p-8">
        <div id="followup-content" class="w-full"></div>
    </div>

  </div>
</div>

<script>
    // Constants
    const PRICES = {
        'Kaki Pants': 1550 + 200,
        'Jeans': 1150 + 200
    };
    
    // State
    let entries = [];
    let cartItems = [];
    let editingId = null; 

    // Elements
    const dateInput = document.getElementById('input-date');
    const customerInput = document.getElementById('input-customer');
    const locationInput = document.getElementById('input-location');
    const notesInput = document.getElementById('input-notes');
    
    const productSelect = document.getElementById('input-product-type');
    const sizeSelect = document.getElementById('input-size');
    const colorSelect = document.getElementById('input-color');
    const qtyInput = document.getElementById('input-quantity');
    const displayPrice = document.getElementById('display-price');
    
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const updateBtn = document.getElementById('update-btn');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    const cartList = document.getElementById('cart-list');
    const cartTotalElement = document.getElementById('cart-total');
    const submitOrderBtn = document.getElementById('submit-order-btn');
    const cartPanel = document.getElementById('cart-panel');
    const editModePanel = document.getElementById('edit-mode-panel');
    
    const salesContainer = document.getElementById('sales-container');

    // Data SDK
    window.dataSdk = {
        async init(handler) {
            this.handler = handler;
            this.refresh();
        },
        async create(entry) {
            const res = await fetch('api/daily_sales.php', { method: 'POST', body: JSON.stringify(entry), headers: {'Content-Type': 'application/json'} });
            if(res.ok) this.refresh();
            return res.json();
        },
        async update(entry) {
            const res = await fetch('api/daily_sales.php', { method: 'PUT', body: JSON.stringify(entry), headers: {'Content-Type': 'application/json'} });
            if(res.ok) this.refresh();
            return res.json();
        },
        async delete(entry) {
            const res = await fetch('api/daily_sales.php', { method: 'DELETE', body: JSON.stringify({__backendId: entry.__backendId}), headers: {'Content-Type': 'application/json'} });
            if(res.ok) this.refresh();
            return res.json();
        },
        async refresh() {
            try {
                const res = await fetch('api/daily_sales.php');
                const data = await res.json();
                if(Array.isArray(data)) this.handler.onDataChanged(data);
            } catch(e) { console.error(e); }
        }
    };

    // Initialization
    function init() {
        dateInput.valueAsDate = new Date();
        updateSizeOptions();
        updatePriceDisplay();
        renderCart();
        
        window.dataSdk.init({
            onDataChanged(data) {
                entries = data;
                renderSalesList();
                updateStats();
            }
        });
    }

    // Logic for Product/Size
    productSelect.onchange = () => { updateSizeOptions(); updatePriceDisplay(); };
    sizeSelect.onchange = updatePriceDisplay;
    qtyInput.oninput = updatePriceDisplay;

    function updateSizeOptions() {
        const product = productSelect.value;
        sizeSelect.innerHTML = '<option value="">Select Size...</option>';
        const start = product === 'Kaki Pants' ? 32 : 31;
        const end = 42;
        for(let i=start; i<=end; i++) {
            const isFollowup = i > 38;
            sizeSelect.add(new Option(`${i} ${isFollowup ? '(Follow-up)' : ''}`, i));
        }
    }

    function getUnitPrice() {
        return PRICES[productSelect.value] || 0;
    }

    function updatePriceDisplay() {
        const unit = getUnitPrice();
        const qty = parseInt(qtyInput.value) || 1;
        displayPrice.textContent = (unit * qty) + " Birr";
    }

    // Cart Logic
    addToCartBtn.onclick = () => {
        if(!sizeSelect.value) { alert('Please select a size'); return; }
        
        const item = {
            id: Date.now(), // temp id
            product_type: productSelect.value,
            size: sizeSelect.value,
            color: colorSelect.value,
            quantity: parseInt(qtyInput.value) || 1,
            unitDataPrice: getUnitPrice(),
            needs_followup: parseInt(sizeSelect.value) > 38
        };
        
        cartItems.push(item);
        renderCart();
        
        // Reset Item fields
        qtyInput.value = 1;
        // Optionally reset color/size
        updatePriceDisplay();
    };

    function renderCart() {
        if(cartItems.length === 0) {
            cartList.innerHTML = '<div class="text-center text-slate-400 py-8 text-sm italic">No items added yet</div>';
            cartTotalElement.textContent = '0 Birr';
            submitOrderBtn.disabled = true;
            return;
        }
        
        let total = 0;
        cartList.innerHTML = cartItems.map((item, idx) => {
            const itemTotal = item.unitDataPrice * item.quantity;
            total += itemTotal;
            return `
                <div class="flex justify-between items-start bg-slate-50 p-2 rounded mb-2 border border-slate-100 text-sm">
                    <div>
                        <div class="font-bold text-slate-700">${item.quantity}x ${item.product_type}</div>
                        <div class="text-xs text-slate-500">Size: ${item.size}, ${item.color}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-slate-700">${itemTotal}</div>
                        <button onclick="removeCartItem(${idx})" class="text-red-400 hover:text-red-600 text-xs underline">Remove</button>
                    </div>
                </div>
            `;
        }).join('');
        
        cartTotalElement.textContent = total + " Birr";
        submitOrderBtn.disabled = false;
    }
    
    window.removeCartItem = (idx) => {
        cartItems.splice(idx, 1);
        renderCart();
    };

    // Order Submission
    submitOrderBtn.onclick = async () => {
        if(cartItems.length === 0) return;
        
        // Validation
        if(!customerInput.value.trim()) {
            alert('Please enter Customer Name/Info');
            customerInput.focus();
            return;
        }
        if(!locationInput.value.trim()) {
            alert('Please enter Customer Location');
            locationInput.focus();
            return;
        }

        submitOrderBtn.innerHTML = 'Processing...';
        submitOrderBtn.disabled = true;
        
        const baseEntry = {
            date: dateInput.value,
            customer_info: customerInput.value,
            customer_location: locationInput.value,
            notes: notesInput.value,
            created_at: new Date().toISOString(),
            purchased: false
        };
        
        // Create individual entries for each item * quantity
        // Actually, usually 1 row per item if tracking unique serialized items, but here maybe just 1 row per variant line?
        // The old code created N loops for quantity. Let's stick to that for granularity (tracking individual sold items).
        
        for(const item of cartItems) {
            for(let i=0; i<item.quantity; i++) {
                const entry = {
                    ...baseEntry,
                    product_type: item.product_type,
                    size: item.size,
                    color: item.color,
                    price: item.unitDataPrice + " Birr",
                    needs_followup: item.needs_followup,
                    followup_reason: item.needs_followup ? `Requested size ${item.size}` : ''
                };
                await window.dataSdk.create(entry);
            }
        }
        
        cartItems = [];
        renderCart();
        // Clear customer fields too? Maybe.
        customerInput.value = '';
        locationInput.value = '';
        notesInput.value = '';
        submitOrderBtn.innerHTML = 'Complete Order';
        alert('Order completed successfully!');
    };

    // Sales Listing & Editing
    function renderSalesList() {
        const today = new Date().toISOString().split('T')[0];
        const todays = entries.filter(e => e.date === today).sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
        
        if(todays.length === 0) {
            salesContainer.innerHTML = '<div class="text-center text-slate-400 py-10">No sales today</div>';
            return;
        }

        // Group by Customer Name
        const groups = {};
        todays.forEach(entry => {
            const name = entry.customer_info || 'Unknown Customer';
            if(!groups[name]) groups[name] = [];
            groups[name].push(entry);
        });

        salesContainer.innerHTML = Object.entries(groups).map(([customerName, items]) => {
            const firstItem = items[0];
            const location = firstItem.customer_location || '';
            const totalForCustomer = items.reduce((sum, item) => sum + parseInt(item.price||0), 0);
            const hasUnsold = items.some(i => !i.purchased);
            
            return `
                <div class="mb-4 border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <div>
                                <span class="font-bold text-slate-700 text-sm">👤 ${customerName}</span>
                                ${location ? `<span class="text-xs text-slate-500 ml-2">📍 ${location}</span>` : ''}
                            </div>
                            ${hasUnsold ? 
                                `<button onclick="markAllSold('${customerName.replace(/'/g, "\\'")}')" class="ml-2 text-[9px] uppercase tracking-wide font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded shadow-sm hover:bg-emerald-100 hover:text-emerald-700 transition-all">
                                   Sell All
                                 </button>` : ''
                            }
                        </div>
                        <span class="text-xs font-bold text-slate-600 bg-white px-2 py-1 rounded border border-slate-200">
                             Total: ${totalForCustomer} Birr
                        </span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        ${items.map(entry => `
                            <div class="bg-white py-3 px-4 hover:bg-slate-50 transition-colors flex justify-between items-center group">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold text-[10px] ${entry.product_type==='Jeans'?'bg-blue-100 text-blue-700':'bg-amber-100 text-amber-700'}">
                                        ${entry.product_type === 'Jeans' ? 'J' : 'K'}
                                    </div>
                                    <div>
                                        <div class="text-slate-700 text-sm font-medium flex items-center gap-2">
                                            ${entry.product_type} <span class="text-xs text-slate-500">Size: ${entry.size}</span>
                                        </div>
                                        <div class="text-xs text-slate-500">
                                             ${entry.color} • ${entry.price} 
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-all duration-200">
                                    ${!entry.purchased ? 
                                        `<button onclick="togglePurchased('${entry.__backendId}')" title="Mark as Sold" class="group/btn bg-white border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-200 hover:bg-emerald-50 p-1.5 rounded-lg shadow-sm transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>` : 
                                        `<button onclick="togglePurchased('${entry.__backendId}')" title="Revert to Unsold (Return)" class="text-[9px] font-bold text-emerald-600 px-2 py-1 bg-emerald-50 rounded-lg border border-emerald-100 shadow-sm flex items-center gap-1 hover:bg-red-50 hover:text-red-500 hover:border-red-100 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            SOLD
                                        </button>`
                                    }
                                    <button onclick="startEdit('${entry.__backendId}')" title="Edit Item" class="group/btn bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 hover:bg-indigo-50 p-1.5 rounded-lg shadow-sm transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    <button onclick="deleteEntry('${entry.__backendId}')" title="Delete Item" class="group/btn bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 p-1.5 rounded-lg shadow-sm transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }).join('');
    }

    // Edit Logic
    window.startEdit = (id) => {
        // Use loose equality to handle potentially different types (string vs number)
        const entry = entries.find(e => e.__backendId == id);
        if(!entry) return;
        
        editingId = id;
        
        // Enter Edit Mode
        cartPanel.classList.add('hidden');
        editModePanel.classList.remove('hidden');
        addToCartBtn.classList.add('hidden');
        updateBtn.classList.remove('hidden');
        cancelEditBtn.classList.remove('hidden');
        document.getElementById('item-input-card').classList.add('ring-2', 'ring-purple-500');
        
        // Populate fields
        dateInput.value = entry.date;
        customerInput.value = entry.customer_info || '';
        locationInput.value = entry.customer_location || '';
        notesInput.value = entry.notes || '';
        
        productSelect.value = entry.product_type;
        updateSizeOptions(); 
        sizeSelect.value = entry.size;
        colorSelect.value = entry.color;
        
        qtyInput.value = 1;
        qtyInput.disabled = true; 
        
        updatePriceDisplay();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    updateBtn.onclick = async () => {
         if(!editingId) return;
         if(!sizeSelect.value) { alert('Please select a size'); return; }

         // Use loose equality again for consistency
         const entry = entries.find(e => e.__backendId == editingId);
         
         const unitPrice = getUnitPrice(); 
         const newData = {
             ...entry,
             date: dateInput.value,
             customer_info: customerInput.value,
             customer_location: locationInput.value,
             notes: notesInput.value,
             product_type: productSelect.value,
             size: sizeSelect.value,
             color: colorSelect.value,
             price: unitPrice + " Birr",
             needs_followup: parseInt(sizeSelect.value) > 38
         };
         
         updateBtn.innerHTML = 'Updating...';
         await window.dataSdk.update(newData);
         updateBtn.innerHTML = 'Update Sale';
         exitEditMode();
    };
    
    cancelEditBtn.onclick = exitEditMode;
    
    function exitEditMode() {
        editingId = null;
        cartPanel.classList.remove('hidden');
        editModePanel.classList.add('hidden');
        addToCartBtn.classList.remove('hidden');
        updateBtn.classList.add('hidden');
        cancelEditBtn.classList.add('hidden');
        document.getElementById('item-input-card').classList.remove('ring-2', 'ring-purple-500');
        
        // Fully Reset Form
        qtyInput.disabled = false;
        qtyInput.value = 1;
        customerInput.value = '';
        locationInput.value = '';
        notesInput.value = '';
        dateInput.valueAsDate = new Date(); // Reset date to today
        
        productSelect.selectedIndex = 0;
        updateSizeOptions(); // Reset sizes for default product
        sizeSelect.value = "";
        colorSelect.selectedIndex = 0;
        updatePriceDisplay();
    }

    // Other Actions
    window.togglePurchased = async (id) => {
        const entry = entries.find(e => e.__backendId === id);
        if(entry) await window.dataSdk.update({...entry, purchased: !entry.purchased});
    };
    
    window.markAllSold = async (customerName) => {
        if(!confirm(`Mark all items for ${customerName} as Sold?`)) return;
        
        const today = new Date().toISOString().split('T')[0];
        // Note: encoding/decoding might be safer, but for now strict string matching
        const targets = entries.filter(e => 
            e.date === today && 
            (e.customer_info || 'Unknown Customer') === customerName && 
            !e.purchased
        );
        
        for(const t of targets) {
            await window.dataSdk.update({...t, purchased: true});
        }
    };
    
    window.deleteEntry = async (id) => {
        if(confirm('Delete this sale?')) {
            const entry = entries.find(e => e.__backendId === id);
            if(entry) await window.dataSdk.delete(entry);
        }
    };
    
    function updateStats() {
       const today = new Date().toISOString().split('T')[0];
       const todays = entries.filter(e => e.date === today);
       
       // 1. Basic Stats
       const totalSales = todays.length;
       document.getElementById('stat-today-sales').textContent = totalSales;
       
       const revenue = todays.reduce((acc, curr) => acc + parseInt(curr.price||0), 0);
       document.getElementById('stat-today-revenue').textContent = revenue.toLocaleString() + " Birr";
       
       document.getElementById('stat-khaki').textContent = todays.filter(e => e.product_type==='Kaki Pants').length;
       document.getElementById('stat-jeans').textContent = todays.filter(e => e.product_type==='Jeans').length;

       // 2. Performance Banner
       const perfBanner = document.getElementById('performance-summary');
       const perfScore = document.getElementById('performance-score');
       const perfMsg = document.getElementById('performance-msg');

       if (revenue > 0) {
           perfBanner.classList.remove('hidden');
           if(revenue < 5000) {
               perfScore.textContent = 'Steady';
               perfScore.className = 'text-3xl font-bold mono-font text-white';
               perfMsg.textContent = 'Keep pushing for more sales!';
           } else if (revenue < 15000) {
               perfScore.textContent = 'Good';
               perfScore.className = 'text-3xl font-bold mono-font text-emerald-200';
               perfMsg.textContent = 'Great momentum today!';
           } else {
               perfScore.textContent = 'Excellent';
               perfScore.className = 'text-3xl font-bold mono-font text-amber-200';
               perfMsg.textContent = 'Outstanding performance!';
           }
       } else {
           perfBanner.classList.add('hidden');
       }

       // 3. Follow-up Count
       // We can count follow-ups directly from entries (maybe not just today's?)
       // Let's count ALL active follow-ups in the system if useful, or just today's. 
       // Usually follow-ups are relevant until resolved. Assuming 'purchased' resolves it? 
       // Start with ALL entries requiring follow-up.
       const followups = entries.filter(e => e.needs_followup && !e.purchased); 
       document.getElementById('followup-count').textContent = followups.length;
    }
    
    // Autocomplete
    const locations = ['Bole', 'Piazza', 'Merkato', '6 Kilo', '4 Kilo', 'CMC', 'Gerji', 'Old Airport'];
    locationInput.oninput = function() {
        const val = this.value.toLowerCase();
        const drop = document.getElementById('location-dropdown');
        if(!val) { drop.classList.add('hidden'); return; }
        const matches = locations.filter(l => l.toLowerCase().includes(val));
        if(matches.length>0) {
            drop.innerHTML = matches.map(l => `<div class="p-2 hover:bg-slate-100 cursor-pointer" onclick="document.getElementById('input-location').value='${l}'; this.parentElement.classList.add('hidden');">${l}</div>`).join('');
            drop.classList.remove('hidden');
        } else {
            drop.classList.add('hidden');
        }
    };
    
    // Presentation View Logic
    window.closePresentation = () => {
        document.getElementById('main-view').classList.remove('hidden');
        document.getElementById('presentation-view').classList.add('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    document.getElementById('present-btn').onclick = () => {
         const mainView = document.getElementById('main-view');
         const presView = document.getElementById('presentation-view');
         
         mainView.classList.add('hidden');
         presView.classList.remove('hidden');
         
         // Gather Data
         const today = new Date().toISOString().split('T')[0];
         const todays = entries.filter(e => e.date === today);
         const totalSales = todays.length;
         const totalRevenue = todays.reduce((acc, curr) => acc + parseInt(curr.price||0), 0);
         
         // Product Breakdown
         const products = {};
         todays.forEach(e => { products[e.product_type] = (products[e.product_type] || 0) + 1; });
         
         // Location Breakdown
         const locations = {};
         todays.forEach(e => { 
             const loc = e.customer_location || 'Unknown';
             locations[loc] = (locations[loc] || 0) + 1; 
         });
         const topLocation = Object.entries(locations).sort((a,b) => b[1] - a[1])[0] || ['None', 0];

         document.getElementById('presentation-content').innerHTML = `
            <div class="glass-card max-w-5xl mx-auto rounded-3xl p-10 text-slate-800 shadow-2xl animate-slide bg-white relative overflow-hidden">
                <!-- Decorative Background Elements -->
                <div class="absolute top-0 right-0 w-64 h-full bg-gradient-to-l from-indigo-50 to-transparent opacity-50"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-purple-50 rounded-full blur-3xl -ml-20 -mb-20 opacity-50"></div>
            
                <!-- Close Button -->
                <button onclick="window.closePresentation()" class="absolute top-6 right-6 text-slate-400 hover:text-indigo-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Header -->
                <div class="flex justify-between items-start mb-12 border-b border-slate-200 pb-8 relative z-10">
                    <div>
                        <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 mb-2">Daily Performance Report</h1>
                        <p class="text-slate-500 font-medium">${new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                    <div class="text-right pr-12">
                        <div class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-1">Total Revenue</div>
                        <div class="text-5xl font-black text-slate-800 mono-font tracking-tight">${totalRevenue.toLocaleString()} <span class="text-2xl text-slate-400">Birr</span></div>
                    </div>
                </div>

                <!-- Core Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12 relative z-10">
                    <div class="bg-gradient-to-br from-indigo-50 to-white p-6 rounded-2xl border border-indigo-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <svg class="w-24 h-24 text-indigo-600" fill="currentColor" viewbox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </div>
                        <h3 class="text-indigo-900 font-bold text-lg mb-2">Total Sales</h3>
                        <p class="text-4xl font-bold text-indigo-600">${totalSales}</p>
                        <p class="text-indigo-400 text-sm mt-2">Units Sold Today</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-emerald-50 to-white p-6 rounded-2xl border border-emerald-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <svg class="w-24 h-24 text-emerald-600" fill="currentColor" viewbox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-emerald-900 font-bold text-lg mb-2">Avg. Ticket</h3>
                        <p class="text-4xl font-bold text-emerald-600">${totalSales > 0 ? Math.round(totalRevenue / totalSales) : 0}<span class="text-lg ml-1">Birr</span></p>
                        <p class="text-emerald-400 text-sm mt-2">Revenue per Sale</p>
                    </div>

                    <div class="bg-gradient-to-br from-rose-50 to-white p-6 rounded-2xl border border-rose-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                             <svg class="w-24 h-24 text-rose-600" fill="currentColor" viewbox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <h3 class="text-rose-900 font-bold text-lg mb-2">Top Location</h3>
                        <p class="text-3xl font-bold text-rose-600 truncate" title="${topLocation[0]}">${topLocation[0]}</p>
                        <p class="text-rose-400 text-sm mt-2">${topLocation[1]} orders from here</p>
                    </div>
                </div>

                <!-- Detailed Analysis -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 relative z-10">
                    <div>
                        <h4 class="text-slate-700 font-bold text-xl mb-6 flex items-center gap-2">
                             <span class="bg-slate-100 p-2 rounded-lg">📦</span> Product Mix
                        </h4>
                        <div class="space-y-4">
                            ${Object.entries(products).map(([name, count]) => `
                                <div>
                                    <div class="flex justify-between text-sm font-semibold text-slate-600 mb-1">
                                        <span>${name}</span>
                                        <span>${count} units (${Math.round(count/totalSales*100)}%)</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                                        <div class="bg-indigo-500 h-3 rounded-full" style="width: ${count/totalSales*100}%"></div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <div>
                         <h4 class="text-slate-700 font-bold text-xl mb-6 flex items-center gap-2">
                             <span class="bg-slate-100 p-2 rounded-lg">📈</span> Recent Activity
                        </h4>
                        <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 h-48 overflow-y-auto space-y-3">
                            ${todays.slice(0, 5).map(e => `
                                <div class="flex items-center justify-between text-sm pb-2 border-b border-slate-200 last:border-0">
                                    <div class="font-medium text-slate-700">${e.product_type} <span class="text-slate-400 text-xs">(${e.size})</span></div>
                                    <div class="font-bold text-emerald-600">+${parseInt(e.price)}</div>
                                </div>
                            `).join('')}
                            ${todays.length === 0 ? '<div class="text-center text-slate-400 italic mt-10">No activity yet</div>' : ''}
                        </div>
                    </div>
                </div>
            </div>
         `;
    };
    // Follow-up View Logic
    window.closeFollowupView = () => {
        document.getElementById('main-view').classList.remove('hidden');
        document.getElementById('followup-view').classList.add('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    document.getElementById('followup-btn').onclick = () => {
        const mainView = document.getElementById('main-view');
        const followView = document.getElementById('followup-view');
        
        mainView.classList.add('hidden');
        followView.classList.remove('hidden');
        
        // Filter Follow-ups
        // Any item that is explicitly marked needs_followup AND NOT purchased
        // OR any item that has notes but is NOT purchased (optional heuristic, maybe stuck to explicit flag)
        // Let's stick to explicit flag for now, assuming we will add a checkbox.
        // Actually, for now, let's treat ANY unpaid/unsold item from previous days as a potential follow-up? 
        // No, let's strictly look for 'purchased: false'. 
        // If it's today and purchased: false, it's just in the list.
        // If it's PAST date and purchased: false, it is definitely a follow-up.
        
        const today = new Date().toISOString().split('T')[0];
        const pendings = entries.filter(e => !e.purchased && (e.needs_followup || e.date !== today));
        
        document.getElementById('followup-content').innerHTML = `
            <div class="glass-card max-w-4xl mx-auto rounded-3xl p-8 text-slate-800 shadow-2xl bg-white relative min-h-[500px]">
                <button onclick="window.closeFollowupView()" class="absolute top-6 right-6 text-slate-400 hover:text-indigo-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                
                <h1 class="text-3xl font-extrabold text-slate-800 mb-2">Pending Follow-ups</h1>
                <p class="text-slate-500 mb-8">Items not yet sold from previous days or marked for attention.</p>
                
                <div class="space-y-4">
                    ${pendings.length === 0 ? 
                        `<div class="text-center py-20 text-slate-400 flex flex-col items-center">
                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-lg">No pending follow-ups</span>
                        </div>` 
                        : 
                        pendings.map(e => `
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 hover:shadow-md transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-bold text-white bg-slate-400 px-2 py-0.5 rounded">${e.date}</span>
                                        <span class="font-bold text-slate-700">${e.customer_info || 'Unknown'}</span>
                                        ${e.customer_location ? `<span class="text-xs text-slate-500">📍 ${e.customer_location}</span>` : ''}
                                    </div>
                                    <div class="text-sm text-slate-600">
                                        ${e.product_type} - Size ${e.size} - ${e.color}
                                    </div>
                                    ${e.notes ? `<div class="text-xs text-amber-600 mt-1 italic">📝 ${e.notes}</div>` : ''}
                                </div>
                                <div class="flex items-center gap-2">
                                    <button onclick="togglePurchased('${e.__backendId}'); window.closeFollowupView();" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-4 py-2 rounded-lg text-xs font-bold transition-colors flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Mark Sold
                                    </button>
                                     <button onclick="deleteEntry('${e.__backendId}'); window.closeFollowupView();" class="bg-red-50 text-red-500 hover:bg-red-100 p-2 rounded-lg text-xs font-bold transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        `).join('')
                    }
                </div>
            </div>
        `;
    };
    
    // document.getElementById('close-followup-btn').onclick = ... (Removed)
    
    init();
</script>

<?php require_once "includes/footer.php"; ?>
