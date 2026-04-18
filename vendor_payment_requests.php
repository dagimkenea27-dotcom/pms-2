<?php
require_once "config/auth.php";
require_once "config/database.php";

// Admin-only page
Auth::requireRole('admin');

$page_title = "Vendor Payment Requests";
require_once "includes/header.php";
?>

<style>
    #vendor-pay-app {
        font-family: 'Nunito', sans-serif;
    }

    /* Stat Cards */
    .vp-stat-card {
        border-radius: 14px;
        padding: 18px 22px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .vp-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .vp-stat-card .stat-icon {
        position: absolute;
        top: 12px;
        right: 16px;
        font-size: 28px;
        opacity: 0.15;
    }

    .vp-stat-card .stat-label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 4px;
    }

    .vp-stat-card .stat-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.2;
    }

    /* Status Badges */
    .vp-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vp-badge-pending {
        background: #FEF3C7;
        color: #92400E;
    }

    .vp-badge-approved {
        background: #D1FAE5;
        color: #065F46;
    }

    .vp-badge-rejected {
        background: #FEE2E2;
        color: #991B1B;
    }

    .vp-badge-paid {
        background: #DBEAFE;
        color: #1E40AF;
    }

    /* Action Buttons */
    .vp-action-btn {
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .vp-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .vp-btn-approve {
        background: #D1FAE5;
        color: #065F46;
    }

    .vp-btn-approve:hover {
        background: #A7F3D0;
        color: #065F46;
    }

    .vp-btn-reject {
        background: #FEE2E2;
        color: #991B1B;
    }

    .vp-btn-reject:hover {
        background: #FECACA;
        color: #991B1B;
    }

    .vp-btn-paid {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .vp-btn-paid:hover {
        background: #BFDBFE;
        color: #1E40AF;
    }

    .vp-btn-delete {
        background: #FEE2E2;
        color: #991B1B;
    }

    .vp-btn-delete:hover {
        background: #FECACA;
        color: #991B1B;
    }

    /* Filter Pills */
    .vp-filter-btn {
        padding: 6px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vp-filter-btn:hover {
        background: #f8fafc;
    }

    .vp-filter-btn.active {
        background: #1e293b;
        color: #fff;
        border-color: #1e293b;
    }

    /* Order ID code style */
    .vp-order-code {
        background: #f1f5f9;
        color: #7c3aed;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        font-family: 'Courier New', monospace;
    }

    /* Table styling */
    #vendor-pay-app .table>thead>tr>th {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748B;
        border-bottom: 2px solid #e2e8f0;
        padding: 12px 14px;
        background: #f8fafc;
    }

    #vendor-pay-app .table>tbody>tr>td {
        padding: 14px;
        font-size: 13px;
        color: #334155;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    #vendor-pay-app .table>tbody>tr:hover {
        background: #f8fafc;
    }

    /* Header icon */
    .vp-header-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        color: #fff;
        box-shadow: 0 6px 16px rgba(124, 58, 237, 0.3);
    }

    /* Modal header gradient */
    .vp-modal-header {
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        border-radius: 0;
        padding: 20px 24px;
    }

    .vp-modal-header .modal-title {
        color: #fff;
        font-weight: 800;
        font-size: 16px;
    }

    .vp-modal-header .btn-close {
        filter: brightness(0) invert(1);
    }

    .vp-modal-header .modal-subtitle {
        color: rgba(255, 255, 255, 0.7);
        font-size: 12px;
        margin-top: 2px;
    }

    /* Toast notification */
    .vp-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 99999;
        padding: 14px 22px;
        border-radius: 12px;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.35s ease;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }

    .vp-toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .vp-toast-success {
        background: linear-gradient(135deg, #059669, #10B981);
    }

    .vp-toast-error {
        background: linear-gradient(135deg, #DC2626, #EF4444);
    }

    /* Empty state */
    .vp-empty-state {
        text-align: center;
        padding: 48px 16px;
    }

    /* Card border radius */
    .vp-card {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    /* Animations */
    @keyframes vpFadeIn {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .vp-fade-in {
        animation: vpFadeIn 0.4s ease forwards;
    }

    .vp-fade-in-1 {
        animation-delay: 0.05s;
        opacity: 0;
    }

    .vp-fade-in-2 {
        animation-delay: 0.1s;
        opacity: 0;
    }

    .vp-fade-in-3 {
        animation-delay: 0.15s;
        opacity: 0;
    }

    .vp-fade-in-4 {
        animation-delay: 0.2s;
        opacity: 0;
    }

    /* Primary gradient button */
    .vp-btn-primary {
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        border: none;
        color: #fff;
        font-weight: 700;
        padding: 10px 22px;
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3);
        transition: all 0.2s ease;
    }

    .vp-btn-primary:hover {
        background: linear-gradient(135deg, #6d28d9, #4338ca);
        color: #fff;
        box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        transform: translateY(-1px);
    }

    .vp-btn-primary:active {
        transform: scale(0.97);
    }

    /* Form inputs */
    .vp-form-control {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        padding: 10px 14px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .vp-form-control:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }

    /* Amount styling */
    .vp-amount {
        font-weight: 800;
        color: #1e293b;
    }

    .vp-amount-unit {
        font-size: 11px;
        font-weight: 400;
        color: #94a3b8;
    }

    /* Shop name */
    .vp-shop-name {
        font-weight: 700;
        color: #1e293b;
    }

    /* Row number */
    .vp-row-num {
        font-weight: 800;
        color: #cbd5e1;
        font-size: 12px;
    }

    /* Requested by */
    .vp-requested-by {
        font-size: 12px;
        color: #94a3b8;
    }

    /* Mobile Responsiveness */
    @media (max-width: 767.98px) {
        /* Stats Cards - Make them more compact */
        .vp-stat-card {
            padding: 12px 14px;
        }
        .vp-stat-card .stat-value {
            font-size: 1.1rem;
        }
        .vp-stat-card .stat-icon {
            font-size: 20px;
        }

        /* Filter buttons - center them */
        .vp-filter-btn {
            padding: 6px 12px;
            font-size: 11px;
            flex-grow: 1;
            text-align: center;
        }

        .vp-filter-scroll-container {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 5px;
            -webkit-overflow-scrolling: touch;
        }

        /* Card layout for table on mobile */
        #vendor-pay-app .table-responsive {
            border: none;
        }
        #vendor-pay-app .table thead {
            display: none; /* Hide headers */
        }
        #vendor-pay-app .table, 
        #vendor-pay-app .table tbody, 
        #vendor-pay-app .table tr, 
        #vendor-pay-app .table td {
            display: block;
            width: 100%;
        }
        #vendor-pay-app .table tr {
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        #vendor-pay-app .table td {
            text-align: right;
            padding: 8px 10px;
            position: relative;
            border-bottom: 1px solid #f1f5f9;
        }
        #vendor-pay-app .table td:last-child {
            border-bottom: none;
            text-align: center;
            margin-top: 10px;
            background: #f8fafc;
            border-radius: 0 0 10px 10px;
        }
        #vendor-pay-app .table td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            width: 120px;
            text-align: left;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            top: 50%;
            transform: translateY(-50%);
        }
        #vendor-pay-app .vp-row-num {
            display: none !important;
        }
        #vendor-pay-app .vp-shop-name {
            font-size: 16px;
            color: #1e293b;
            border-bottom: 2px solid #7c3aed !important;
            margin-bottom: 5px;
        }
        #vendor-pay-app .vp-shop-name::before {
            display: none;
        }
        #vendor-pay-app .vp-shop-name {
            text-align: left !important;
            padding-left: 10px !important;
        }
        
        /* Adjust action buttons container for mobile */
        .vp-action-btn {
            width: 100%;
            justify-content: center;
            padding: 8px;
            font-size: 12px;
        }
    }
</style>

<div id="vendor-pay-app">

    <!-- Header -->
    <div
        class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4 vp-fade-in">
        <div class="d-flex align-items-center gap-3 mb-2 mb-md-0">
            <div class="vp-header-icon">💳</div>
            <div>
                <h1 class="h4 fw-bold mb-0" style="color: #1e293b;">Vendor Payment Requests</h1>
                <p class="mb-0 small text-muted">Manage and track all vendor payment requests</p>
            </div>
        </div>
        <button class="btn vp-btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal"
            data-bs-target="#newRequestModal">
            <i class="fas fa-plus"></i> New Request
        </button>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 vp-fade-in vp-fade-in-1">
            <div class="vp-stat-card bg-white shadow-sm" style="border-left: 4px solid #D97706;">
                <i class="fas fa-clock stat-icon" style="color: #D97706;"></i>
                <div class="stat-label" style="color: #D97706;">Pending Total</div>
                <div class="stat-value" id="stat-pending-amt" style="color: #92400E; font-size: 1.2rem;">0 ETB</div>
                <div class="small text-muted" id="stat-pending-count" style="font-size: 10px; font-weight: 700;">0
                    requests</div>
            </div>
        </div>
        <div class="col-6 col-md-3 vp-fade-in vp-fade-in-2">
            <div class="vp-stat-card shadow-sm" style="background: #ECFDF5; border-left: 4px solid #059669;">
                <i class="fas fa-check-circle stat-icon" style="color: #059669;"></i>
                <div class="stat-label" style="color: #059669;">Approved Total</div>
                <div class="stat-value" id="stat-approved-amt" style="color: #065F46; font-size: 1.2rem;">0 ETB</div>
                <div class="small text-muted" id="stat-approved-count"
                    style="font-size: 10px; font-weight: 700; color: #059669 !important; opacity: 0.7;">0 requests</div>
            </div>
        </div>
        <div class="col-6 col-md-3 vp-fade-in vp-fade-in-3">
            <div class="vp-stat-card shadow-sm" style="background: #EFF6FF; border-left: 4px solid #2563EB;">
                <i class="fas fa-coins stat-icon" style="color: #2563EB;"></i>
                <div class="stat-label" style="color: #2563EB;">Paid Total</div>
                <div class="stat-value" id="stat-paid-amt" style="color: #1E40AF; font-size: 1.2rem;">0 ETB</div>
                <div class="small text-muted" id="stat-paid-count"
                    style="font-size: 10px; font-weight: 700; color: #2563EB !important; opacity: 0.7;">0 requests</div>
            </div>
        </div>
        <div class="col-6 col-md-3 vp-fade-in vp-fade-in-4">
            <div class="vp-stat-card shadow-sm" style="background: #F5F3FF; border-left: 4px solid #7C3AED;">
                <i class="fas fa-hand-holding-usd stat-icon" style="color: #7C3AED;"></i>
                <div class="stat-label" style="color: #7C3AED;">Actual Commission</div>
                <div class="stat-value" id="stat-total-amt" style="color: #5B21B6; font-size: 1.2rem;">0 ETB</div>
                <div class="small text-muted" id="stat-total-count"
                    style="font-size: 10px; font-weight: 700; color: #7C3AED !important; opacity: 0.7;">Actual
                    Commission Collected</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="d-flex flex-column gap-3 mb-4 vp-fade-in vp-fade-in-3">
        <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3">
            <div class="d-flex flex-wrap gap-2 vp-filter-scroll-container">
                <button data-filter="all" class="vp-filter-btn active">All</button>
                <button data-filter="pending" class="vp-filter-btn">Pending</button>
                <button data-filter="approved" class="vp-filter-btn">Approved</button>
                <button data-filter="rejected" class="vp-filter-btn">Rejected</button>
                <button data-filter="paid" class="vp-filter-btn">Paid</button>
            </div>
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-md-center gap-2">
                <div class="input-group input-group-sm w-100" style="min-width: 250px;">
                    <span class="input-group-text bg-white border-end-0"><i
                            class="fas fa-calendar-alt text-muted fa-xs"></i></span>
                    <input type="date" id="filter-from" class="form-control vp-form-control border-start-0"
                        title="From Date" style="font-size: 11px;">
                    <span class="input-group-text bg-white px-1">to</span>
                    <input type="date" id="filter-to" class="form-control vp-form-control" title="To Date"
                        style="font-size: 11px;">
                    <button id="btn-reset-filters" class="btn btn-outline-secondary" title="Reset Filters"><i
                            class="fas fa-undo fa-xs"></i></button>
                </div>
                <div class="position-relative w-100">
                    <i class="fas fa-search position-absolute text-muted" style="left: 10px; top: 50%; transform: translateY(-50%); font-size: 10px; z-index: 5;"></i>
                    <input id="search-input" type="text" class="form-control form-control-sm vp-form-control"
                        placeholder="Search Shop, Order..." style="font-size: 11px; padding-left: 28px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card vp-card shadow-sm vp-fade-in vp-fade-in-4">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Shop Name</th>
                        <th>Order ID</th>
                        <th>Gross Amt</th>
                        <th>Commission</th>
                        <th>Net Payout</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Notes</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="requests-tbody">
                    <tr>
                        <td colspan="8">
                            <div class="vp-empty-state">
                                <div class="spinner-border text-secondary spinner-border-sm mb-2" role="status"></div>
                                <div class="text-muted small">Loading requests...</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- New Request Modal -->
    <div class="modal fade" id="newRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="vp-modal-header">
                    <div class="d-flex justify-content-between align-items-start w-100">
                        <div>
                            <h5 class="modal-title"><i class="fas fa-money-check-alt me-2"></i>New Payment Request</h5>
                            <div class="modal-subtitle">Fill in the vendor payment details</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4">
                    <form id="request-form">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Shop Name <span class="text-danger">*</span></label>
                            <input type="text" id="form-shop-name" required placeholder="e.g. Zara, H&M, AliExpress..."
                                class="form-control vp-form-control">
                        </div>
                        <div id="orders-container">
                            <div class="order-row border-bottom pb-3 mb-3">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold small">Order ID <span
                                                class="text-danger">*</span></label>
                                        <input type="text" required placeholder="e.g. ORD-12345"
                                            class="form-control vp-form-control form-order-id">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold small">Amount (ETB) <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" required min="0.01" step="0.01" placeholder="0.00"
                                                class="form-control vp-form-control form-order-amount">
                                            <button type="button" class="btn btn-outline-danger border-0 btn-remove-row"
                                                style="display: none;"><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-1">
                                        <div class="form-check">
                                            <input class="form-check-input form-pays-comm" type="checkbox">
                                            <label class="form-check-label small text-muted">Collect Commission (8% /
                                                6.5% / 5% Tiered)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="btn-add-order"
                            class="btn btn-sm btn-link text-decoration-none fw-bold p-0 mb-3">
                            <i class="fas fa-plus-circle me-1"></i> Add Another Order
                        </button>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Notes <span
                                    class="text-muted fw-normal">(optional)</span></label>
                            <textarea id="form-notes" rows="2" placeholder="Any additional details..."
                                class="form-control vp-form-control" style="resize: none;"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="btn-submit" class="btn vp-btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Submit Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="vp-toast" class="vp-toast"></div>
</div>

<script>
    (function () {
        const API_URL = '<?php echo BASE_URL; ?>api/vendor_payment_requests_api.php';
        let allRequests = [];
        let currentFilter = 'all';

        const tbody = document.getElementById('requests-tbody');
        const toastEl = document.getElementById('vp-toast');
        const searchInput = document.getElementById('search-input');
        const modalEl = document.getElementById('newRequestModal');
        let bsModal = null;
        let editingId = null;

        function getModal() {
            if (!bsModal && typeof bootstrap !== 'undefined' && modalEl) {
                bsModal = new bootstrap.Modal(modalEl);
            }
            return bsModal;
        }

        const ordersContainer = document.getElementById('orders-container');
        const btnAddOrder = document.getElementById('btn-add-order');

        function updateRemoveButtons() {
            const rows = ordersContainer.querySelectorAll('.order-row');
            rows.forEach(row => {
                const btn = row.querySelector('.btn-remove-row');
                if (btn) btn.style.display = rows.length > 1 ? 'block' : 'none';
            });
        }

        btnAddOrder.addEventListener('click', () => {
            const firstRow = ordersContainer.querySelector('.order-row');
            const newRow = firstRow.cloneNode(true);
            newRow.classList.remove('vp-fade-in'); // Avoid re-triggering animation if any
            newRow.querySelectorAll('input').forEach(input => {
                input.value = '';
                input.classList.remove('is-invalid');
            });
            ordersContainer.appendChild(newRow);
            updateRemoveButtons();
            validateOrderIDs();
        });

        ordersContainer.addEventListener('click', (e) => {
            if (e.target.closest('.btn-remove-row')) {
                const rows = ordersContainer.querySelectorAll('.order-row');
                if (rows.length > 1) {
                    e.target.closest('.order-row').remove();
                    updateRemoveButtons();
                    validateOrderIDs();
                }
            }
        });

        const resetModal = () => {
            editingId = null;
            document.querySelector('#newRequestModal .modal-title').innerHTML = '<i class="fas fa-money-check-alt me-2"></i>New Payment Request';
            document.querySelector('#newRequestModal .modal-subtitle').textContent = 'Fill in the vendor payment details';
            btnAddOrder.style.display = 'block';
            
            document.getElementById('request-form').reset();
            const rows = ordersContainer.querySelectorAll('.order-row');
            rows.forEach((row, i) => { if (i > 0) row.remove(); });
            ordersContainer.querySelectorAll('.form-pays-comm').forEach(chk => chk.checked = false);
            updateRemoveButtons();
        };

        // Reset state when modal is hidden
        modalEl.addEventListener('hidden.bs.modal', resetModal);

        const filterFrom = document.getElementById('filter-from');
        const filterTo = document.getElementById('filter-to');
        const btnReset = document.getElementById('btn-reset-filters');
        const btnSubmit = document.getElementById('btn-submit');

        function validateOrderIDs() {
            let isAnyDuplicate = false;
            const currentInputs = Array.from(ordersContainer.querySelectorAll('.form-order-id'));
            const enteredValues = currentInputs.map(input => input.value.trim().toLowerCase());

            currentInputs.forEach((input, index) => {
                const val = input.value.trim().toLowerCase();
                if (!val) {
                    input.classList.remove('is-invalid');
                    return;
                }

                // Check against other entries in the same modal
                const isDuplicateInModal = enteredValues.filter((v, i) => v === val && i !== index).length > 0;

                // Check against all existing requests in the system
                const isDuplicateInSystem = allRequests.some(r => (r.order_id || '').toLowerCase() === val);

                if (isDuplicateInModal || isDuplicateInSystem) {
                    input.classList.add('is-invalid');
                    isAnyDuplicate = true;

                    // Add tooltip or label if not exists
                    let feedback = input.parentNode.querySelector('.invalid-feedback');
                    if (!feedback) {
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.style.fontSize = '10px';
                        input.parentNode.appendChild(feedback);
                    }
                    feedback.textContent = isDuplicateInSystem ? 'Order ID already exists in the system' : 'Duplicate Order ID in this request';
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            btnSubmit.disabled = isAnyDuplicate;
            if (isAnyDuplicate) {
                btnSubmit.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Resolve Duplicates';
            } else {
                btnSubmit.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Request';
            }
        }

        ordersContainer.addEventListener('input', (e) => {
            if (e.target.classList.contains('form-order-id')) {
                validateOrderIDs();
            }
        });

        const shopNameInput = document.getElementById('form-shop-name');
        const notesInput = document.getElementById('form-notes');

        const autoFetchNote = () => {
            const name = shopNameInput.value.trim().toLowerCase();
            if (!name) return;

            // Try to find the MOST RECENT and NON-EMPTY note for this shop
            // allRequests is already sorted by date DESC from API
            const previousRequest = allRequests.find(r =>
                (r.shop_name || '').trim().toLowerCase() === name &&
                (r.notes || '').trim() !== ''
            );

            if (previousRequest && previousRequest.notes && !notesInput.value.trim()) {
                notesInput.value = previousRequest.notes;
                showToast('Auto-fetched previous account details', 'success');
            }
        };

        shopNameInput.addEventListener('change', autoFetchNote);
        shopNameInput.addEventListener('blur', autoFetchNote);

        // Filters
        document.querySelectorAll('.vp-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.vp-filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.filter;
                renderTable();
            });
        });

        // Search & Date
        searchInput.addEventListener('input', () => renderTable());
        filterFrom.addEventListener('change', () => renderTable());
        filterTo.addEventListener('change', () => renderTable());

        // Reset
        btnReset.addEventListener('click', () => {
            filterFrom.value = '';
            filterTo.value = '';
            searchInput.value = '';
            document.querySelectorAll('.vp-filter-btn').forEach(b => b.classList.remove('active'));
            document.querySelector('[data-filter="all"]').classList.add('active');
            currentFilter = 'all';
            renderTable();
        });

        // Toast
        function showToast(msg, type = 'success') {
            toastEl.textContent = msg;
            toastEl.className = `vp-toast vp-toast-${type} show`;
            setTimeout(() => toastEl.classList.remove('show'), 3000);
        }

        // Fetch
        async function fetchRequests() {
            try {
                const res = await fetch(API_URL);
                const data = await res.json();
                if (data.isOk) {
                    allRequests = data.data || [];
                    updateStats();
                    renderTable();
                } else {
                    showToast(data.message, 'error');
                    allRequests = [];
                    updateStats();
                    renderTable();
                }
            } catch (err) {
                showToast('Failed to load data', 'error');
                allRequests = [];
                updateStats();
                renderTable();
            }
        }

        // Stats
        function updateStats(data = allRequests) {
            const stats = {
                pending: { gross: 0, net: 0, count: 0 },
                approved: { gross: 0, net: 0, count: 0 },
                paid: { gross: 0, net: 0, count: 0 },
                total: { gross: 0, net: 0, comm: 0, paidComm: 0, count: data.length }
            };

            data.forEach(r => {
                const gross = parseFloat(r.order_amount || 0);
                const net = parseFloat(r.net_amount || 0);
                const comm = parseFloat(r.commission_amount || 0);

                stats.total.gross += gross;
                stats.total.net += net;
                stats.total.comm += comm;
                if (r.status === 'paid') stats.total.paidComm += comm;

                if (stats[r.status]) {
                    stats[r.status].gross += gross;
                    stats[r.status].net += net;
                    stats[r.status].count++;
                }
            });

            const fmt = (v) => v.toLocaleString('en', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

            document.getElementById('stat-pending-amt').textContent = fmt(stats.pending.net) + ' ETB';
            document.getElementById('stat-pending-count').textContent = stats.pending.count + ' req (' + fmt(stats.pending.gross) + ' Gross)';

            document.getElementById('stat-approved-amt').textContent = fmt(stats.approved.net) + ' ETB';
            document.getElementById('stat-approved-count').textContent = stats.approved.count + ' req (' + fmt(stats.approved.gross) + ' Gross)';

            document.getElementById('stat-paid-amt').textContent = fmt(stats.paid.net) + ' ETB';
            document.getElementById('stat-paid-count').textContent = stats.paid.count + ' req (' + fmt(stats.paid.gross) + ' Gross)';

            document.getElementById('stat-total-amt').textContent = fmt(stats.total.paidComm) + ' ETB';
            document.getElementById('stat-total-count').textContent = 'Actual Commission | Gross: ' + fmt(stats.total.gross);
        }

        // Render
        function renderTable() {
            const search = searchInput.value.toLowerCase();
            const from = filterFrom.value;
            const to = filterTo.value;
            let filtered = allRequests;

            if (currentFilter !== 'all') {
                filtered = filtered.filter(r => r.status === currentFilter);
            }
            if (search) {
                filtered = filtered.filter(r =>
                    (r.shop_name || '').toLowerCase().includes(search) ||
                    (r.order_id || '').toLowerCase().includes(search) ||
                    (r.notes || '').toLowerCase().includes(search)
                );
            }
            if (from) {
                filtered = filtered.filter(r => r.created_at >= from + ' 00:00:00');
            }
            if (to) {
                filtered = filtered.filter(r => r.created_at <= to + ' 23:59:59');
            }

            updateStats(filtered);

            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8">
                <div class="vp-empty-state">
                    <div style="font-size: 40px;" class="mb-2">📭</div>
                    <div class="fw-bold text-muted">No payment requests found</div>
                    <div class="text-muted small mt-1">Click "New Request" to create one</div>
                </div>
            </td></tr>`;
                return;
            }

            tbody.innerHTML = filtered.map((r, i) => {
                const date = new Date(r.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                const gross = parseFloat(r.order_amount).toLocaleString('en', { minimumFractionDigits: 2 });
                const net = parseFloat(r.net_amount).toLocaleString('en', { minimumFractionDigits: 2 });
                const comm = parseFloat(r.commission_amount).toLocaleString('en', { minimumFractionDigits: 2 });
                const dots = { pending: '🟡', approved: '🟢', rejected: '🔴', paid: '🔵' };

                let commHtml = `<span class="text-muted">-${comm}</span>`;
                if (r.pays_commission == 1) {
                    commHtml = `<span class="text-danger fw-bold">-${comm}</span> <br><small class="text-muted" style="font-size:9px;">(${r.commission_rate}%)</small>`;
                }

                let actions = '';
                if (r.status === 'pending') {
                    actions = `
                    <button class="vp-action-btn vp-btn-approve" onclick="vpUpdateStatus(${r.id}, 'approve')"><i class="fas fa-check fa-xs"></i> Approve</button>
                    <button class="vp-action-btn vp-btn-reject" onclick="vpUpdateStatus(${r.id}, 'reject')"><i class="fas fa-times fa-xs"></i> Reject</button>`;
                } else if (r.status === 'approved') {
                    actions = `<button class="vp-action-btn vp-btn-paid" onclick="vpUpdateStatus(${r.id}, 'mark_paid')"><i class="fas fa-money-bill fa-xs"></i> Mark Paid</button>`;
                }
                actions += `<button class="vp-action-btn vp-btn-delete ms-1" onclick="vpDeleteRequest(${r.id})" title="Delete"><i class="fas fa-trash fa-xs"></i></button>`;
                
                if (r.status !== 'paid') {
                    actions = `<button class="vp-action-btn btn-light border ms-1" onclick="vpEditRequest(${r.id})" title="Edit"><i class="fas fa-edit fa-xs"></i> Edit</button>` + actions;
                }

                return `<tr class="vp-request-row">
                <td class="vp-row-num">${i + 1}</td>
                <td class="vp-shop-name" data-label="Shop">${escHtml(r.shop_name)}</td>
                <td data-label="Order ID"><span class="vp-order-code">${escHtml(r.order_id)}</span></td>
                <td class="vp-amount text-muted" data-label="Gross Amt" style="font-size:11px;">${gross}</td>
                <td data-label="Commission">${commHtml}</td>
                <td class="vp-amount" data-label="Net Payout">${net} <span class="vp-amount-unit">ETB</span></td>
                <td data-label="Status"><span class="vp-badge vp-badge-${r.status}">${dots[r.status] || ''} ${r.status}</span></td>
                <td class="vp-requested-by" data-label="By">${escHtml(r.requested_by_name || 'N/A')}</td>
                <td class="small text-muted" data-label="Notes" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escHtml(r.notes || '')}">
                    ${escHtml(r.notes || '-')}
                </td>
                <td class="vp-date" data-label="Date">${date}</td>
                <td class="text-center"><div class="d-flex align-items-center justify-content-center gap-1 flex-wrap">${actions}</div></td>
            </tr>`;
            }).join('');
        }

        function escHtml(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // Edit
        window.vpEditRequest = function(id) {
            const r = allRequests.find(req => req.id == id);
            if (!r) return;

            resetModal();
            editingId = id;
            
            // UI adjustments for edit mode
            document.querySelector('#newRequestModal .modal-title').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Payment Request';
            document.querySelector('#newRequestModal .modal-subtitle').textContent = 'Correct the payment details for this order';
            btnAddOrder.style.display = 'none'; // Editing is per-row

            // Populate form
            document.getElementById('form-shop-name').value = r.shop_name;
            document.getElementById('form-notes').value = r.notes || '';
            
            const firstRow = ordersContainer.querySelector('.order-row');
            firstRow.querySelector('.form-order-id').value = r.order_id;
            firstRow.querySelector('.form-order-amount').value = r.order_amount;
            firstRow.querySelector('.form-pays-comm').checked = (r.pays_commission == 1);

            const modal = getModal();
            if (modal) modal.show();
        };

        // Submit
        document.getElementById('btn-submit').addEventListener('click', async () => {
            const shopName = document.getElementById('form-shop-name').value.trim();
            const notes = document.getElementById('form-notes').value.trim();

            const orders = [];
            let valid = true;
            ordersContainer.querySelectorAll('.order-row').forEach(row => {
                const id = row.querySelector('.form-order-id').value.trim();
                const amt = row.querySelector('.form-order-amount').value;
                const paysComm = row.querySelector('.form-pays-comm').checked;
                if (!id || !amt) valid = false;
                orders.push({ order_id: id, order_amount: amt, pays_commission: paysComm });
            });

            if (!shopName || !valid || orders.length === 0) {
                showToast('Please fill all required fields for all orders', 'error');
                return;
            }

            const btn = document.getElementById('btn-submit');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Submitting...';

            try {
                let res, body;
                if (editingId) {
                    // Update flow (Single order)
                    body = {
                        _method: 'PUT',
                        id: editingId,
                        action: 'update',
                        shop_name: shopName,
                        order_id: orders[0].order_id,
                        order_amount: orders[0].order_amount,
                        pays_commission: orders[0].pays_commission,
                        notes: notes
                    };
                    res = await fetch(API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body)
                    });
                } else {
                    // Create flow (Batch support)
                    res = await fetch(API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ shop_name: shopName, orders: orders, notes: notes })
                    });
                }

                const data = await res.json();
                if (data.isOk) {
                    showToast(data.message || (editingId ? 'Request updated!' : 'Payment request created!'));
                    const modal = getModal();
                    if (modal) modal.hide();
                    resetModal();
                    fetchRequests();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast(editingId ? 'Failed to update request' : 'Failed to create request', 'error');
            }
            btn.disabled = false;
            btn.innerHTML = editingId ? '<i class="fas fa-save me-1"></i> Save Changes' : '<i class="fas fa-paper-plane me-1"></i> Submit Request';
        });

        // Update status
        window.vpUpdateStatus = async function (id, action) {
            const labels = { approve: 'Approve', reject: 'Reject', mark_paid: 'Mark as Paid' };
            if (!confirm(`Are you sure you want to ${labels[action]} this request?`)) return;

            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ _method: 'PUT', id, action })
                });
                const data = await res.json();
                if (data.isOk) {
                    showToast(data.message);
                    fetchRequests();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Failed to update status', 'error');
            }
        };

        // Delete
        window.vpDeleteRequest = async function (id) {
            if (!confirm('Are you sure you want to permanently delete this request?')) return;

            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ _method: 'DELETE', id })
                });
                const data = await res.json();
                if (data.isOk) {
                    showToast('Request deleted.');
                    fetchRequests();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Failed to delete request', 'error');
            }
        };

        // Init
        fetchRequests();
    })();
</script>

<?php require_once "includes/footer.php"; ?>