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
    async create(entry, options = {}) {
        const res = await fetch('api/daily_sales.php', { method: 'POST', body: JSON.stringify(entry), headers: { 'Content-Type': 'application/json' } });
        const data = await res.json();
        if (res.ok && !options.skipRefresh) this.refresh();
        return data;
    },
    async update(entry, options = {}) {
        // Tunnel PUT via POST
        try {
            const res = await fetch('api/daily_sales.php', {
                method: 'POST',
                body: JSON.stringify({ ...entry, _method: 'PUT' }),
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            if (data.isOk) {
                if (!options.skipRefresh) this.refresh();
            } else {
                alert("Update Failed: " + (data.message || 'Unknown Error'));
                console.error("Update Error:", data);
            }
            return data;
        } catch (e) { alert("Connection Error: " + e.message); }
    },
    async delete(entry, options = {}) {
        // Tunnel DELETE via POST
        try {
            const res = await fetch('api/daily_sales.php', {
                method: 'POST',
                body: JSON.stringify({ __backendId: entry.__backendId, _method: 'DELETE' }),
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            if (data.isOk) {
                if (!options.skipRefresh) this.refresh();
            } else {
                alert("Delete Failed: " + (data.message || 'Unknown Error'));
                console.error("Delete Error:", data);
            }
            return data;
        } catch (e) { alert("Connection Error: " + e.message); }
    },
    async refresh() {
        try {
            const res = await fetch('api/daily_sales.php');
            const data = await res.json();
            if (Array.isArray(data)) this.handler.onDataChanged(data);
        } catch (e) { console.error(e); }
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
    for (let i = start; i <= end; i++) {
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

// SHEIN Screenshot Integration
const sheinBtn = document.getElementById('shein-screenshot-btn');
const sheinFile = document.getElementById('shein-file-input');

// Camera Elements
const cameraModal = document.getElementById('cameraModal');
const videoElement = document.getElementById('camera-feed');
const canvasElement = document.getElementById('camera-canvas');
const captureBtn = document.getElementById('capture-btn');
const closeCameraBtn = document.getElementById('close-camera-btn');
let cameraStream = null;

async function performSheinSearch(blob) {
    if (!blob) return;

    const originalText = sheinBtn.innerHTML;
    sheinBtn.innerHTML = '<span class="animate-pulse">Searching...</span>';
    sheinBtn.disabled = true;

    const formData = new FormData();
    formData.append('screenshot', blob, 'capture.jpg');

    try {
        const res = await fetch('api/shein_search.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();

        if (result.success) {
            const data = result.data;

            // Auto-fill form
            if (data.product_type) productSelect.value = data.product_type;
            updateSizeOptions();

            // Try to find a matching size from the available ones
            if (data.available_sizes && data.available_sizes.length > 0) {
                // Find first match in our dropdown
                for (let i = 0; i < data.available_sizes.length; i++) {
                    // logic to match size? for now just set value
                    // check if option exists
                    const size = data.available_sizes[i];
                    if (document.querySelector(`#input-size option[value="${size}"]`)) {
                        sizeSelect.value = size;
                        break;
                    }
                }
                // Fallback to first available from API if exact match logic is too strict
                if (!sizeSelect.value && data.available_sizes[0]) {
                    // Maybe add it if not exists? Or just ignore
                }
            }

            if (data.color) colorSelect.value = data.color;

            // Highlight fields that were changed
            [productSelect, sizeSelect, colorSelect].forEach(el => {
                el.classList.add('ring-2', 'ring-indigo-500', 'transition-all');
                setTimeout(() => el.classList.remove('ring-2', 'ring-indigo-500'), 2000);
            });

            updatePriceDisplay();
        } else {
            alert('Search Error: ' + result.message);
        }
    } catch (err) {
        console.error('SHEIN Search Error:', err);
        alert('Failed to connect to search service.');
    } finally {
        sheinBtn.innerHTML = originalText;
        sheinBtn.disabled = false;
        if (sheinFile) sheinFile.value = ''; // Reset file input
    }
}

async function startCamera() {
    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error("Camera API not available");
        }
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment', // Use back camera on mobile
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        });
        videoElement.srcObject = cameraStream;
    } catch (err) {
        console.error("Camera Error:", err);
        alert("Camera Error: " + err.message);
        cameraModal.classList.add('hidden');
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
        videoElement.srcObject = null;
    }
}

if (sheinBtn) {
    sheinBtn.onclick = (e) => {
        e.preventDefault();
        if (cameraModal) {
            cameraModal.classList.remove('hidden');
            startCamera();
        } else {
            // Fallback if modal missing
            sheinFile.click();
        }
    };
}

if (closeCameraBtn) {
    closeCameraBtn.onclick = () => {
        stopCamera();
        cameraModal.classList.add('hidden');
    };
}

if (captureBtn) {
    captureBtn.onclick = () => {
        if (!videoElement.srcObject) return;

        const context = canvasElement.getContext('2d');
        canvasElement.width = videoElement.videoWidth;
        canvasElement.height = videoElement.videoHeight;
        context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);

        canvasElement.toBlob(blob => {
            stopCamera();
            cameraModal.classList.add('hidden');
            performSheinSearch(blob);
        }, 'image/jpeg', 0.8);
    };
}

if (sheinFile) {
    sheinFile.onchange = async () => {
        if (sheinFile.files.length) {
            performSheinSearch(sheinFile.files[0]);
        }
    };
}

// Cart Logic
addToCartBtn.onclick = () => {
    if (!sizeSelect.value) { alert('Please select a size'); return; }

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
    if (cartItems.length === 0) {
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
    if (cartItems.length === 0) return;

    // Validation
    if (!customerInput.value.trim()) {
        alert('Please enter Customer Name/Info');
        customerInput.focus();
        return;
    }
    if (!locationInput.value.trim()) {
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

    const promises = [];
    for (const item of cartItems) {
        for (let i = 0; i < item.quantity; i++) {
            const entry = {
                ...baseEntry,
                product_type: item.product_type,
                size: item.size,
                color: item.color,
                price: item.unitDataPrice + " Birr",
                needs_followup: item.needs_followup,
                followup_reason: item.needs_followup ? `Requested size ${item.size}` : ''
            };
            promises.push(window.dataSdk.create(entry, { skipRefresh: true }));
        }
    }

    await Promise.all(promises);
    await window.dataSdk.refresh();

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
    const todays = entries.filter(e => e.date === today).sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

    if (todays.length === 0) {
        salesContainer.innerHTML = '<div class="text-center text-slate-400 py-10">No sales today</div>';
        return;
    }

    // Group by Customer Name
    const groups = {};
    todays.forEach(entry => {
        const name = entry.customer_info || 'Unknown Customer';
        if (!groups[name]) groups[name] = [];
        groups[name].push(entry);
    });

    salesContainer.innerHTML = Object.entries(groups).map(([customerName, items]) => {
        const firstItem = items[0];
        const location = firstItem.customer_location || '';
        const totalForCustomer = items.reduce((sum, item) => sum + parseInt(item.price || 0), 0);
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
                        <button onclick="deleteAll('${customerName.replace(/'/g, "\\'")}')" class="ml-2 text-[9px] uppercase tracking-wide font-bold text-red-600 bg-red-50 border border-red-200 px-2 py-0.5 rounded shadow-sm hover:bg-red-100 hover:text-red-700 transition-all">
                           Delete All
                        </button>
                    </div>
                    <span class="text-xs font-bold text-slate-600 bg-white px-2 py-1 rounded border border-slate-200">
                         Total: ${totalForCustomer} Birr
                    </span>
                </div>
                <div class="divide-y divide-slate-100">
                    ${items.map(entry => `
                        <div class="bg-white py-3 px-4 hover:bg-slate-50 transition-colors flex justify-between items-center group">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold text-[10px] ${entry.product_type === 'Jeans' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'}">
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
// Edit Logic
window.startEdit = (id) => {
    // Use loose equality to handle potentially different types (string vs number)
    const entry = entries.find(e => e.__backendId == id);
    if (!entry) return;

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
    qtyInput.disabled = false; // Allow editing quantity

    updatePriceDisplay();
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

updateBtn.onclick = async () => {
    if (!editingId) return;
    if (!sizeSelect.value) { alert('Please select a size'); return; }

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
    updateBtn.disabled = true;

    // 1. Update the original entry
    await window.dataSdk.update(newData, { skipRefresh: true });

    // 2. Handle Quantity Increase
    const extraQty = (parseInt(qtyInput.value) || 1) - 1;
    if (extraQty > 0) {
        const { __backendId, ...copyData } = newData; // Remove ID to create new
        // Create copies (Parallel Optimization)
        await Promise.all(Array.from({ length: extraQty }).map(() =>
            window.dataSdk.create({
                ...copyData,
                created_at: new Date().toISOString()
            }, { skipRefresh: true })
        ));
    }

    await window.dataSdk.refresh();

    updateBtn.innerHTML = 'Update Sale';
    updateBtn.disabled = false;
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
    // Use loose equality for ID matching to handle string/number differences
    const entry = entries.find(e => e.__backendId == id);
    if (entry) await window.dataSdk.update({ ...entry, purchased: !entry.purchased });
};

window.markAllSold = async (customerName) => {
    if (!confirm(`Mark all items for ${customerName} as Sold?`)) return;

    const today = new Date().toISOString().split('T')[0];
    // Note: encoding/decoding might be safer, but for now strict string matching
    const targets = entries.filter(e =>
        e.date === today &&
        (e.customer_info || 'Unknown Customer') === customerName &&
        !e.purchased
    );

    // Optimize: Parallel Execution using Promise.all
    // This prevents the UI from blocking/lagging for ~1s for multiple items
    await Promise.all(targets.map(t => window.dataSdk.update({ ...t, purchased: true }, { skipRefresh: true })));
    await window.dataSdk.refresh();
};

window.deleteAll = async (customerName) => {
    if (!confirm(`Delete all items for ${customerName}? This cannot be undone.`)) return;

    const today = new Date().toISOString().split('T')[0];
    const targets = entries.filter(e =>
        e.date === today &&
        (e.customer_info || 'Unknown Customer') === customerName
    );

    if (targets.length === 0) return;

    // Optimize: Parallel Execution using Promise.all
    await Promise.all(targets.map(t => window.dataSdk.delete(t, { skipRefresh: true })));
    await window.dataSdk.refresh();
};

window.deleteEntry = async (id) => {
    if (confirm('Delete this sale?')) {
        // Use loose equality for ID matching
        const entry = entries.find(e => e.__backendId == id);
        if (entry) {
            await window.dataSdk.delete(entry);
        } else {
            console.error("Entry not found for ID:", id);
            alert("Error: Item not found. Please refresh the page.");
        }
    }
};

function updateStats() {
    const today = new Date().toISOString().split('T')[0];
    const todays = entries.filter(e => e.date === today);

    // 1. Basic Stats
    const totalSales = todays.length;
    document.getElementById('stat-today-sales').textContent = totalSales;

    const revenue = todays.reduce((acc, curr) => acc + parseInt(curr.price || 0), 0);
    document.getElementById('stat-today-revenue').textContent = revenue.toLocaleString() + " Birr";

    document.getElementById('stat-khaki').textContent = todays.filter(e => e.product_type === 'Kaki Pants').length;
    document.getElementById('stat-jeans').textContent = todays.filter(e => e.product_type === 'Jeans').length;

    // 2. Performance Banner
    const perfBanner = document.getElementById('performance-summary');
    const perfScore = document.getElementById('performance-score');
    const perfMsg = document.getElementById('performance-msg');

    if (revenue > 0) {
        perfBanner.classList.remove('hidden');
        if (revenue < 5000) {
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
locationInput.oninput = function () {
    const val = this.value.toLowerCase();
    const drop = document.getElementById('location-dropdown');
    if (!val) { drop.classList.add('hidden'); return; }
    const matches = locations.filter(l => l.toLowerCase().includes(val));
    if (matches.length > 0) {
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
    const totalRevenue = todays.reduce((acc, curr) => acc + parseInt(curr.price || 0), 0);

    // Product Breakdown
    const products = {};
    todays.forEach(e => { products[e.product_type] = (products[e.product_type] || 0) + 1; });

    // Location Breakdown
    const locations = {};
    todays.forEach(e => {
        const loc = e.customer_location || 'Unknown';
        locations[loc] = (locations[loc] || 0) + 1;
    });
    const topLocation = Object.entries(locations).sort((a, b) => b[1] - a[1])[0] || ['None', 0];

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
                                    <span>${count} units (${Math.round(count / totalSales * 100)}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                                    <div class="bg-indigo-500 h-3 rounded-full" style="width: ${count / totalSales * 100}%"></div>
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
    const today = new Date().toISOString().split('T')[0];
    const pendings = entries.filter(e => !e.purchased && (e.needs_followup || e.date !== today));

    document.getElementById('followup-content').innerHTML = `
        <div class="glass-card max-w-4xl mx-auto rounded-3xl p-8 text-slate-800 shadow-2xl bg-white relative min-h-[500px]">
            <button onclick="window.closeFollowupView()" class="absolute top-6 right-6 text-slate-400 hover:text-indigo-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 mb-2">Pending Follow-ups</h1>
                    <p class="text-slate-500">Items not yet sold from previous days or marked for attention.</p>
                </div>
                <button onclick="window.closeFollowupView(); document.getElementById('shein-screenshot-btn').click();" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Add from Screenshot
                </button>
            </div>
            
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

init();
