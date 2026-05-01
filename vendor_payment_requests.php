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

    /* =========================
   Stat Cards
========================= */
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

    /* =========================
   Badges
========================= */
    .vp-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
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

    /* =========================
   Buttons
========================= */
    .vp-action-btn {
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .vp-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .vp-btn-approve {
        background: #D1FAE5;
        color: #065F46;
    }

    .vp-btn-reject {
        background: #FEE2E2;
        color: #991B1B;
    }

    .vp-btn-paid {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .vp-btn-delete {
        background: #FEE2E2;
        color: #991B1B;
    }

    /* =========================
   Filters
========================= */
    .vp-filter-btn {
        padding: 6px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        cursor: pointer;
    }

    .vp-filter-btn.active {
        background: #1e293b;
        color: #fff;
    }

    /* =========================
   Table
========================= */
    #vendor-pay-app .table th {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        color: #64748B;
        background: #f8fafc;
    }

    #vendor-pay-app .table td {
        font-size: 13px;
        color: #334155;
    }

    /* =========================
   Responsive Fixes
========================= */

    /* 📱 Mobile */
    @media (max-width: 576px) {
        .vp-stat-card {
            padding: 12px;
        }

        .vp-stat-card .stat-value {
            font-size: 1.1rem;
        }

        .vp-filter-btn {
            flex: 1;
            font-size: 11px;
            padding: 6px 10px;
        }

        .modal-dialog {
            margin: 10px;
        }
    }

    /* 📲 Tablet */
    @media (min-width: 768px) and (max-width: 1024px) {

        #vendor-pay-app .table th,
        #vendor-pay-app .table td {
            font-size: 12px;
            padding: 10px;
        }

        .vp-stat-card {
            padding: 14px;
        }

        .vp-stat-card .stat-value {
            font-size: 1.3rem;
        }

        .vp-btn-primary {
            padding: 8px 16px;
            font-size: 12px;
        }
    }

    /* 💻 Medium screens */
    @media (max-width: 991px) {
        .vp-filter-scroll-container {
            display: flex;
            overflow-x: auto;
        }

        .vp-action-btn {
            font-size: 10px;
            padding: 4px 8px;
        }

        #vendor-pay-app .table th,
        #vendor-pay-app .table td {
            font-size: 11px;
            padding: 8px;
        }
    }

    /* 🖥 Large screens */
    @media (min-width: 1400px) {
        #vendor-pay-app {
            max-width: 1400px;
            margin: 0 auto;
        }
    }

    @media (max-width: 991px) {
        .row.g-3>div {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }

    @media (max-width: 576px) {
        .row.g-3>div {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .vp-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #1e293b;
        color: #fff;
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 9999;
    }

    .vp-toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .vp-toast-success {
        border-left: 4px solid #10b981;
    }

    .vp-toast-error {
        border-left: 4px solid #ef4444;
    }

    /* =========================
   New Missing Styles
========================= */
    .vp-btn-primary {
        background: #1e293b;
        color: #fff;
        font-weight: 700;
        border-radius: 10px;
        padding: 8px 18px;
        transition: all 0.2s ease;
    }

    .vp-btn-primary:hover {
        background: #334155;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .vp-header-icon {
        background: #fff;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 24px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .vp-fade-in {
        animation: fadeIn 0.5s ease forwards;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .vp-fade-in-1 { animation-delay: 0.1s; }
    .vp-fade-in-2 { animation-delay: 0.2s; }
    .vp-fade-in-3 { animation-delay: 0.3s; }
    .vp-fade-in-4 { animation-delay: 0.4s; }

    .vp-card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
    }

    .vp-modal-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 20px 24px;
    }

    .modal-subtitle {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vp-form-control {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .vp-form-control:focus {
        border-color: #1e293b;
        box-shadow: 0 0 0 3px rgba(30, 41, 59, 0.05);
    }

    .vp-empty-state {
        padding: 60px 20px;
        text-align: center;
        background: #fff;
    }

    .vp-request-row {
        transition: background 0.2s ease;
    }

    .vp-request-row:hover {
        background: #f8fafc !important;
    }

    .vp-row-num {
        font-weight: 800;
        color: #94a3b8;
        font-size: 11px;
    }

    .vp-shop-name {
        font-weight: 700;
        color: #1e293b;
    }

    .vp-order-code {
        background: #f1f5f9;
        padding: 2px 8px;
        border-radius: 6px;
        font-family: 'Monaco', 'Consolas', monospace;
        font-size: 11px;
        color: #475569;
        font-weight: 700;
    }

    .vp-amount {
        font-weight: 800;
        color: #1e293b;
    }

    .vp-amount-unit {
        font-size: 9px;
        color: #94a3b8;
        margin-left: 2px;
    }

    .vp-requested-by {
        font-weight: 600;
        color: #64748b;
    }

    .vp-date {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 600;
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
        <div
            class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3">
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
                    <i class="fas fa-search position-absolute text-muted"
                        style="left: 10px; top: 50%; transform: translateY(-50%); font-size: 10px; z-index: 5;"></i>
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
                        <td colspan="11">
                            <div class="vp-empty-state">
                                <div class="spinner-border text-secondary spinner-border-sm mb-2" role="status"></div>
                                <div class="text-muted small">Loading requests...</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination UI -->
        <div class="card-footer bg-white border-top-0 px-4 py-3">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <div class="small text-muted" id="pagination-info">
                    Showing 0 to 0 of 0 entries
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0" id="pagination-controls">
                        <!-- Pagination buttons will be injected here -->
                    </ul>
                </nav>
            </div>
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
        let currentPage = 1;
        let totalPages = 1;
        let limit = 30;

        const tbody = document.getElementById('requests-tbody');
        const toastEl = document.getElementById('vp-toast');
        const searchInput = document.getElementById('search-input');
        const modalEl = document.getElementById('newRequestModal');
        const paginationControls = document.getElementById('pagination-controls');
        const paginationInfo = document.getElementById('pagination-info');

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
            newRow.classList.remove('vp-fade-in');
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

                const isDuplicateInModal = enteredValues.filter((v, i) => v === val && i !== index).length > 0;
                const isDuplicateInSystem = allRequests.some(r => r.id != editingId && (r.order_id || '').toLowerCase() === val);

                if (isDuplicateInModal || isDuplicateInSystem) {
                    input.classList.add('is-invalid');
                    isAnyDuplicate = true;

                    let feedback = input.parentNode.querySelector('.invalid-feedback');
                    if (!feedback) {
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.style.fontSize = '10px';
                        input.parentNode.appendChild(feedback);
                    }
                    feedback.textContent = isDuplicateInSystem ? 'Order ID already exists (Check global records if not on this page)' : 'Duplicate Order ID in this request';
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            btnSubmit.disabled = isAnyDuplicate;
            if (isAnyDuplicate) {
                btnSubmit.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Resolve Duplicates';
            } else {
                btnSubmit.innerHTML = '<i class="fas fa-paper-plane me-1"></i> ' + (editingId ? 'Save Changes' : 'Submit Request');
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
                fetchRequests(1);
            });
        });

        // Search & Date
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => fetchRequests(1), 300);
        });
        filterFrom.addEventListener('change', () => fetchRequests(1));
        filterTo.addEventListener('change', () => fetchRequests(1));

        // Reset
        btnReset.addEventListener('click', () => {
            filterFrom.value = '';
            filterTo.value = '';
            searchInput.value = '';
            document.querySelectorAll('.vp-filter-btn').forEach(b => b.classList.remove('active'));
            document.querySelector('[data-filter="all"]').classList.add('active');
            currentFilter = 'all';
            fetchRequests(1);
        });

        // Toast
        function showToast(msg, type = 'success') {
            toastEl.textContent = msg;
            toastEl.className = `vp-toast vp-toast-${type} show`;
            setTimeout(() => toastEl.classList.remove('show'), 3000);
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Fetch
        async function fetchRequests(page = 1) {
            currentPage = page;
            const status = currentFilter;
            const search = searchInput.value;
            const from = filterFrom.value;
            const to = filterTo.value;

            const url = new URL(API_URL, window.location.origin);
            url.searchParams.append('page', page);
            url.searchParams.append('limit', limit);
            url.searchParams.append('status', status);
            url.searchParams.append('search', search);
            url.searchParams.append('from_date', from);
            url.searchParams.append('to_date', to);

            tbody.innerHTML = `<tr><td colspan="11">
                <div class="vp-empty-state">
                    <div class="spinner-border text-secondary spinner-border-sm mb-2" role="status"></div>
                    <div class="text-muted small">Loading requests...</div>
                </div>
            </td></tr>`;

            try {
                const res = await fetch(url, {
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                const data = await res.json();
                if (data.isOk) {
                    allRequests = data.data || [];
                    totalPages = data.pagination.total_pages;
                    updateStats(data.stats);
                    renderTable(data.pagination);
                } else {
                    showToast(data.message, 'error');
                    allRequests = [];
                    renderTable();
                }
            } catch (err) {
                showToast('Failed to load data', 'error');
                allRequests = [];
                renderTable();
            }
        }

        // Stats
        function updateStats(stats) {
            if (!stats) return;

            const fmt = (v) => v.toLocaleString('en', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

            document.getElementById('stat-pending-amt').textContent = fmt(stats.pending.net) + ' ETB';
            document.getElementById('stat-pending-count').textContent = stats.pending.count + ' req (' + fmt(stats.pending.gross) + ' Gross)';

            document.getElementById('stat-approved-amt').textContent = fmt(stats.approved.net) + ' ETB';
            document.getElementById('stat-approved-count').textContent = stats.approved.count + ' req (' + fmt(stats.approved.gross) + ' Gross)';

            document.getElementById('stat-paid-amt').textContent = fmt(stats.paid.net) + ' ETB';
            document.getElementById('stat-paid-count').textContent = stats.paid.count + ' req (' + fmt(stats.paid.gross) + ' Gross)';

            document.getElementById('stat-total-amt').textContent = fmt(stats.total.collected_commission) + ' ETB';
            document.getElementById('stat-total-count').textContent = 'Actual Commission | Gross: ' + fmt(stats.total.gross);
        }

        // Render Table and Pagination
        function renderTable(pagination = null) {
            if (allRequests.length === 0) {
                tbody.innerHTML = `<tr><td colspan="11">
                    <div class="vp-empty-state">
                        <div style="font-size: 40px;" class="mb-2">📭</div>
                        <div class="fw-bold text-muted">No payment requests found</div>
                        <div class="text-muted small mt-1">Try adjusting your filters or click "New Request"</div>
                    </div>
                </td></tr>`;
                paginationControls.innerHTML = '';
                paginationInfo.textContent = 'Showing 0 to 0 of 0 entries';
                return;
            }

            tbody.innerHTML = allRequests.map((r, i) => {
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
                if (r.status !== 'paid') {
                    actions += `<button class="vp-action-btn vp-btn-delete ms-1" onclick="vpDeleteRequest(${r.id})" title="Delete"><i class="fas fa-trash fa-xs"></i></button>`;
                    actions = `<button class="vp-action-btn btn-light border ms-1" onclick="vpEditRequest(${r.id})" title="Edit"><i class="fas fa-edit fa-xs"></i> Edit</button>` + actions;
                }

                return `<tr class="vp-request-row">
                    <td class="vp-row-num">${(currentPage - 1) * limit + i + 1}</td>
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

            // Pagination Controls
            if (pagination) {
                const { total_records, total_pages, current_page } = pagination;
                const start = (current_page - 1) * limit + 1;
                const end = Math.min(current_page * limit, total_records);
                paginationInfo.textContent = `Showing ${start} to ${end} of ${total_records} entries`;

                let html = '';
                // Prev
                html += `<li class="page-item ${current_page <= 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); if(${current_page > 1}) fetchRequests(${current_page - 1})"><i class="fas fa-chevron-left"></i></a>
                </li>`;

                // Page numbers (simplified)
                const startPage = Math.max(1, current_page - 2);
                const endPage = Math.min(total_pages, current_page + 2);

                if (startPage > 1) {
                    html += `<li class="page-item"><a class="page-link" href="#" onclick="event.preventDefault(); fetchRequests(1)">1</a></li>`;
                    if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }

                for (let p = startPage; p <= endPage; p++) {
                    html += `<li class="page-item ${p === current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="event.preventDefault(); fetchRequests(${p})">${p}</a>
                    </li>`;
                }

                if (endPage < total_pages) {
                    if (endPage < total_pages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    html += `<li class="page-item"><a class="page-link" href="#" onclick="event.preventDefault(); fetchRequests(${total_pages})">${total_pages}</a></li>`;
                }

                // Next
                html += `<li class="page-item ${current_page >= total_pages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); if(${current_page < total_pages}) fetchRequests(${current_page + 1})"><i class="fas fa-chevron-right"></i></a>
                </li>`;

                paginationControls.innerHTML = html;
            }
        }

        function escHtml(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // Edit
        window.vpEditRequest = function (id) {
            const r = allRequests.find(req => req.id == id);
            if (!r) return;

            resetModal();
            editingId = id;

            document.querySelector('#newRequestModal .modal-title').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Payment Request';
            document.querySelector('#newRequestModal .modal-subtitle').textContent = 'Correct the payment details for this order';
            btnAddOrder.style.display = 'none';

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
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(body)
                    });
                } else {
                    res = await fetch(API_URL, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ shop_name: shopName, orders: orders, notes: notes })
                    });
                }

                const data = await res.json();
                if (data.isOk) {
                    showToast(data.message || (editingId ? 'Request updated!' : 'Payment request created!'));
                    const modal = getModal();
                    if (modal) modal.hide();
                    resetModal();
                    fetchRequests(editingId ? currentPage : 1);
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
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ _method: 'PUT', id, action })
                });
                const data = await res.json();
                if (data.isOk) {
                    showToast(data.message);
                    fetchRequests(currentPage);
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
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ _method: 'DELETE', id })
                });
                const data = await res.json();
                if (data.isOk) {
                    showToast('Request deleted.');
                    // If last item on page, go back one page
                    if (allRequests.length === 1 && currentPage > 1) {
                        fetchRequests(currentPage - 1);
                    } else {
                        fetchRequests(currentPage);
                    }
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Failed to delete request', 'error');
            }
        };

        // Initial fetch
        fetchRequests(1);

        // Expose fetchRequests globally for pagination buttons if needed (though we use onclick handlers now)
        window.fetchRequests = fetchRequests;

    })();
</script>

<?php require_once "includes/footer.php"; ?>