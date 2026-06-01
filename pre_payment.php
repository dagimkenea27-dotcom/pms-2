<?php
require_once "config/auth.php";
require_once "config/database.php";
require_once "config/security.php";
Auth::requireRole('manager');
require_once "includes/header.php";

$prepayApiUrl = rtrim(BASE_URL, '/') . '/api/customer_prepayments_api.php';
if (!empty($_SERVER['HTTP_HOST'])) {
    $prepayScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $prepayApiUrl = $prepayScheme . '://' . $_SERVER['HTTP_HOST'] . $prepayApiUrl;
}
$prepayCsrfToken = Security::getCSRFToken();
?>

<style>
    /* ── Page-level custom styles (no Tailwind) ─────────────────────── */
    .cp-stat-card {
        border-left: 4px solid transparent;
        transition: transform .15s;
    }

    .cp-stat-card:hover {
        transform: translateY(-2px);
    }

    .cp-stat-primary {
        border-left-color: #4e73df;
    }

    .cp-stat-success {
        border-left-color: #1cc88a;
    }

    .cp-stat-warning {
        border-left-color: #f6c23e;
    }

    .cp-stat-info {
        border-left-color: #36b9cc;
    }

    .cp-badge-paid {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .cp-badge-partial {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .cp-badge-unpaid {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .cp-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 4px;
    }

    .cp-dot-paid {
        background: #1cc88a;
    }

    .cp-dot-partial {
        background: #f6c23e;
    }

    .cp-dot-unpaid {
        background: #e74a3b;
    }

    /* Filter tab strip */
    .cp-tab-strip {
        background: #f0f0f5;
        border-radius: .5rem;
        padding: 4px;
        gap: 4px;
        display: flex;
    }

    .cp-tab {
        flex: 1;
        border: 0;
        background: transparent;
        border-radius: .4rem;
        padding: .35rem .6rem;
        font-size: .75rem;
        font-weight: 700;
        color: #6c757d;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }

    .cp-tab.active {
        background: #fff;
        color: #212529;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
    }

    .cp-tab.active-paid {
        background: #1cc88a;
        color: #fff;
    }

    .cp-tab.active-partial {
        background: #f6c23e;
        color: #fff;
    }

    .cp-tab.active-unpaid {
        background: #e74a3b;
        color: #fff;
    }

    /* Bulk toolbar */
    #bulkActionsToolbar {
        display: none;
    }

    #bulkActionsToolbar.show {
        display: flex;
    }

    /* History timeline */
    .cp-timeline {
        position: relative;
        padding-left: 28px;
    }

    .cp-timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e3e6f0;
    }

    .cp-timeline-item {
        position: relative;
        margin-bottom: 1.25rem;
    }

    .cp-timeline-dot {
        position: absolute;
        left: -24px;
        top: 4px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #e3e6f0;
        background: #adb5bd;
    }

    .cp-timeline-dot.create {
        background: #1cc88a;
    }

    .cp-timeline-dot.update {
        background: #4e73df;
    }

    .cp-timeline-dot.delete {
        background: #e74a3b;
    }

    .cp-timeline-dot.status {
        background: #f6c23e;
    }

    /* Receipt uploader drop area */
    .cp-drop-area {
        border: 2px dashed #d1d3e2;
        border-radius: .5rem;
        padding: 1rem;
        cursor: pointer;
        text-align: center;
        transition: border-color .2s;
    }

    .cp-drop-area:hover {
        border-color: #4e73df;
        background: #f8f9fc;
    }

    /* Table action btns */
    .cp-action-btn {
        border: 0;
        background: transparent;
        padding: 6px 9px;
        border-radius: .4rem;
        font-size: .9rem;
        line-height: 1;
        transition: background .12s, transform .08s;
    }

    .cp-action-btn:hover {
        background: #f0f0f5;
    }

    .cp-action-btn:focus {
        outline: 2px solid rgba(78,115,223,.18);
        outline-offset: 2px;
        box-shadow: 0 0 0 3px rgba(78,115,223,.06);
    }

    .cp-action-btn.text-danger:hover {
        background: #fee2e2;
    }

    .cp-action-btn.text-success:hover {
        background: #d1fae5;
    }

    .cp-action-btn.text-primary:hover {
        background: #dbeafe;
    }

    .cp-action-btn.text-warning:hover {
        background: #fef3c7;
    }

    /* Table hover and spacing */
    .table tbody tr:hover {
        background: #fbfdff;
        transform: translateY(-1px);
    }

    .table td, .table th {
        vertical-align: middle;
        padding-top: .85rem;
        padding-bottom: .85rem;
    }

    /* Progress in stat card */
    .cp-progress-thin {
        height: 6px;
        border-radius: 3px;
    }

    /* Live status preview box in form */
    #liveStatusPreview {
        border-radius: .5rem;
        padding: .75rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        border: 1px solid #d1d3e2;
        background: #f8f9fc;
    }

    /* Empty state spacing */
    #emptyState {
        padding: 3rem 1rem;
    }
</style>

<!-- ════════════════════════════════════════════
     PAGE CONTENT
════════════════════════════════════════════ -->
<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 mb-0 text-gray-800 fw-bold">
                <i class="fas fa-receipt me-2 text-primary"></i> Prepayment Ledger
                <span class="badge bg-success ms-2" style="font-size:.65rem;">30% Prepay Rule</span>
            </h1>
            <p class="text-muted small mb-0 mt-1">Manage customer prepayments, quick-pay, and delivery status.</p>
        </div>
            <div class="d-flex gap-2 mt-2 mt-sm-0">
            <button class="btn btn-sm btn-outline-success" onclick="exportToCSV()" title="Export to CSV">
                <i class="fas fa-file-csv me-1"></i> Export CSV
            </button>
            <button class="btn btn-primary btn-sm shadow-sm" onclick="openRecordModal()" aria-label="Add new prepayment">
                <i class="fas fa-plus me-1"></i> New Prepayment
            </button>
        </div>
    </div>

    <!-- ── KPI Cards ─────────────────────────────── -->
    <div class="row mb-4">
        <!-- Total Deal Expected -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-0 cp-stat-card cp-stat-primary h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-xs text-uppercase fw-bold text-muted"
                            style="font-size:.65rem; letter-spacing:.05em;">Total Deal Expected</span>
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                            style="width:36px;height:36px;">
                            <i class="fas fa-sack-dollar text-primary" style="font-size:.85rem;"></i>
                        </div>
                    </div>
                    <div class="h4 fw-bolder mb-0" id="statExpected">ETB 0.00</div>
                    <div class="text-muted mt-1" style="font-size:.7rem;">Sum of all active order values</div>
                </div>
            </div>
        </div>

        <!-- Prepayments Due (30%) -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-0 cp-stat-card cp-stat-info h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-xs text-uppercase fw-bold text-muted"
                            style="font-size:.65rem; letter-spacing:.05em;">Total Due (30%)</span>
                        <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center"
                            style="width:36px;height:36px;">
                            <i class="fas fa-percentage text-info" style="font-size:.85rem;"></i>
                        </div>
                    </div>
                    <div class="h4 fw-bolder text-info mb-0" id="statPrepaymentRequired">ETB 0.00</div>
                    <div class="text-muted mt-1" style="font-size:.7rem;">Target deposits to approve shipping</div>
                </div>
            </div>
        </div>

        <!-- Prepayments Collected -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-0 cp-stat-card cp-stat-success h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-xs text-uppercase fw-bold text-muted"
                            style="font-size:.65rem; letter-spacing:.05em;">Prepayments Collected</span>
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                            style="width:36px;height:36px;">
                            <i class="fas fa-piggy-bank text-success" style="font-size:.85rem;"></i>
                        </div>
                    </div>
                    <div class="h4 fw-bolder text-success mb-0" id="statCollected">ETB 0.00</div>
                    <div class="progress cp-progress-thin mt-2">
                        <div class="progress-bar bg-success" id="statCollectionBar" role="progressbar" style="width:0%"
                            aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="text-muted mt-1" style="font-size:.7rem;">
                        Collection rate: <strong id="statCollectionPercent" class="text-success">0%</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Mix -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-0 cp-stat-card cp-stat-warning h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-xs text-uppercase fw-bold text-muted"
                            style="font-size:.65rem; letter-spacing:.05em;">Prepayment Status Mix</span>
                        <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center"
                            style="width:36px;height:36px;">
                            <i class="fas fa-users text-warning" style="font-size:.85rem;"></i>
                        </div>
                    </div>
                    <div class="row gx-2 mt-1 text-center">
                        <div class="col-4">
                            <div class="rounded-2 py-1 cp-badge-paid">
                                <div class="fw-bolder" style="font-size:.9rem;" id="countPaid">0</div>
                                <div
                                    style="font-size:.6rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase;">
                                    Paid</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rounded-2 py-1 cp-badge-partial">
                                <div class="fw-bolder" style="font-size:.9rem;" id="countPartial">0</div>
                                <div
                                    style="font-size:.6rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase;">
                                    Partial</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rounded-2 py-1 cp-badge-unpaid">
                                <div class="fw-bolder" style="font-size:.9rem;" id="countUnpaid">0</div>
                                <div
                                    style="font-size:.6rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase;">
                                    Unpaid</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /KPI Cards -->

    <!-- Arrival cost strip removed (duplicate of KPI cards) -->

    <!-- ── Main Content Row ───────────────────── -->
    <div class="row">

        <!-- Left: Filters + Table (full-width) -->
        <div class="col-12 mb-4">

            <!-- Filters Card -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body py-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted" style="font-size:.75rem;"></i>
                                </span>
                                <input type="text" id="searchInput" class="form-control border-start-0 ps-0"
                                    placeholder="Search customer or details…" oninput="debounceFetch()">
                            </div>
                        </div>
                        <div class="col-md-auto d-flex align-items-center gap-1">
                            <input type="date" id="filterFrom" class="form-control form-control-sm"
                                onchange="fetchPrepayments(1)" title="From date">
                            <span class="text-muted small">to</span>
                            <input type="date" id="filterTo" class="form-control form-control-sm"
                                onchange="fetchPrepayments(1)" title="To date">
                            <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()"
                                title="Reset filters">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                        <div class="col-md-auto ms-auto d-flex gap-2">
                            <button class="btn btn-sm btn-outline-danger" onclick="clearAllDataConfirm()"><i
                                    class="fas fa-trash me-1"></i>Clear All</button>
                            <button class="btn btn-sm btn-outline-success" onclick="exportJSON()"><i
                                    class="fas fa-download me-1"></i>Backup</button>
                            <label class="btn btn-sm btn-outline-warning mb-0">
                                <i class="fas fa-upload me-1"></i>Restore
                                <input type="file" id="importFile" accept=".json" onchange="importJSON(event)"
                                    class="d-none">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status filter tabs -->
            <div class="cp-tab-strip mb-3">
                <button class="cp-tab active" id="filterBtn-all" onclick="setStatusFilter('all')">All</button>
                <button class="cp-tab" id="filterBtn-paid" onclick="setStatusFilter('paid')">Paid (30%+)</button>
                <button class="cp-tab" id="filterBtn-partial" onclick="setStatusFilter('partial')">Partial</button>
                <button class="cp-tab" id="filterBtn-unpaid" onclick="setStatusFilter('unpaid')">Unpaid</button>
            </div>

            <!-- Bulk actions (shown when rows are selected) -->
            <div id="bulkActionsToolbar" class="alert alert-light border shadow-sm mb-3 align-items-center justify-content-between flex-wrap gap-2 py-2 px-3" role="toolbar" aria-label="Bulk actions">
                <span class="small text-muted"><strong id="selected-count">0</strong> selected</span>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-success" onclick="vpBulkAction('bulk_paid')">
                        <i class="fas fa-percent me-1"></i> Mark 30% Paid
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="vpBulkAction('bulk_clear')">
                        <i class="fas fa-check-double me-1"></i> Mark Fully Paid
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="vpBulkAction('bulk_delete')">
                        <i class="fas fa-trash me-1"></i> Delete Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="vpClearSelection()">Cancel</button>
                </div>
            </div>

            <!-- Ledger Table Card -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0">Prepayment Ledger</h6>
                        <small class="text-muted">Showing <span id="displayedCount"
                                class="fw-bold text-primary">0</span> records</small>
                    </div>
                    <span class="badge bg-light text-muted border" style="font-size:.65rem;">
                        <i class="fas fa-circle text-primary" style="font-size:.4rem;"></i> Live Sync
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="paymentTable">
                        <thead class="table-light">
                            <tr style="font-size:.72rem;">
                                <th class="ps-3" style="width:36px;">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </th>
                                <th>Customer &amp; Details</th>
                                <th>Total Cost</th>
                                <th>Paid / 30% Due</th>
                                <th>Delivery</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- injected by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div id="emptyState" class="d-none py-5 text-center text-muted">
                    <div class="mx-auto" style="max-width:420px;">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-4">
                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-60"></i>
                                <h5 class="fw-bold">No prepayments yet</h5>
                                <p class="small text-muted">Start by adding a customer prepayment or import existing data.</p>
                                <div class="d-flex justify-content-center gap-2 mt-3">
                                    <button class="btn btn-primary btn-sm" onclick="openRecordModal()">
                                        <i class="fas fa-plus me-1"></i> New Prepayment
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('importFile').click()">
                                        <i class="fas fa-upload me-1"></i> Restore
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 py-2">
                    <small class="text-muted" id="paginationInfo">Showing 0 to 0 of 0 entries</small>
                    <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
                </div>
            </div>
        </div>
    </div><!-- /main row -->
</div><!-- /container-fluid -->


<!-- ════════════════════════════════════════════
     MODALS (Bootstrap 5)
════════════════════════════════════════════ -->

<!-- ── Modal: Add / Edit Record ──────────────── -->
<div class="modal fade" id="recordModal" tabindex="-1" aria-labelledby="recordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="recordModalLabel">
                        <i class="fas fa-receipt me-2 text-primary"></i>Add Customer Prepayment
                    </h5>
                    <small class="text-muted">Configure expected cost, deposit amount, and deliveries.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="recordForm" onsubmit="handleFormSubmit(event)" novalidate>
                    <input type="hidden" id="recordId">

                    <!-- Draft Recovery -->
                    <div id="prepay-draft-alert" class="alert alert-primary alert-dismissible d-none py-2 px-3 mb-3"
                        role="alert" style="font-size:.8rem;">
                        <i class="fas fa-file-pen me-1"></i> You have an <strong>unsaved draft</strong>.
                        <a href="#" class="alert-link ms-2" onclick="loadFormDraft(); return false;">Restore</a>
                        &nbsp;|&nbsp;
                        <a href="#" class="alert-link text-danger"
                            onclick="discardFormDraft(); return false;">Discard</a>
                    </div>

                    <!-- Customer Name -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="customerName" class="form-label fw-bold small text-uppercase">Customer Name
                                <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text" class="form-control form-control-sm" id="customerName" required
                                    placeholder="e.g. Acme Corp, John Doe" oninput="handleCustomerNameInput()">
                                <span id="duplicate-warning"
                                    class="d-none position-absolute top-50 end-0 translate-middle-y me-3 badge bg-danger"
                                    style="font-size:.65rem;">
                                    <i class="fas fa-triangle-exclamation me-1"></i>Already Exists
                                </span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="dealDetails" class="form-label fw-bold small text-uppercase">Customer ID /
                                Details</label>
                            <input type="text" class="form-control form-control-sm" id="dealDetails"
                                placeholder="e.g. Shipment batch A, custom pre-orders"
                                oninput="handleDealDetailsInput()">
                        </div>
                    </div>

                    <div id="customerHistoryPanel" class="alert alert-info d-none py-2 px-3 mb-3"
                        style="font-size:.78rem;"></div>

                    <div class="card border-light shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="fw-bold small text-primary text-uppercase">
                                    <i class="fas fa-boxes-stacked me-1"></i> Order Items & Delivery Status
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" onclick="addBlankItemRow()" class="btn btn-outline-primary">
                                        <i class="fas fa-plus me-1"></i> Add Item
                                    </button>
                                    <button type="button" onclick="quickPopulateItems(20)"
                                        class="btn btn-outline-secondary">
                                        <i class="fas fa-magic me-1"></i> Quick Add
                                    </button>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="totalItems" class="form-label fw-bold small">Total Ordered</label>
                                    <input type="number" class="form-control form-control-sm" id="totalItems" min="0"
                                        step="1" placeholder="e.g. 50" oninput="validatePaymentsInModal()">
                                </div>
                                <div class="col-6">
                                    <label for="deliveredItems" class="form-label fw-bold small">Items Arrived</label>
                                    <input type="number" class="form-control form-control-sm" id="deliveredItems"
                                        min="0" step="1" placeholder="0" oninput="validatePaymentsInModal()">
                                </div>
                                <!-- <div class="col-6">
                                    <label class="form-label fw-bold small">Order Arrival Status</label>
                                    <div class="btn-group w-100" role="group" aria-label="Order arrival status">
                                        <button type="button" id="arrivalYesBtn" class="btn btn-sm btn-outline-success"
                                            onclick="setOrderArrivalState(true)">Arrived</button>
                                        <button type="button" id="arrivalNoBtn" class="btn btn-sm btn-outline-danger"
                                            onclick="setOrderArrivalState(false)">Not Arrived</button>
                                    </div>
                                    <input type="hidden" id="orderArrived" value="0">
                                </div> -->
                            </div>
                            <!-- Items container: dynamically populated by addBlankItemRow() -->
                            <div id="itemsContainer" class="mb-2"></div>
                            <div id="orderIdWarning" class="alert alert-danger d-none py-2 px-3 mb-2"
                                style="font-size:.78rem;"></div>

                            <div id="liveDeliveryStatusBox"
                                class="d-none mt-2 rounded-3 p-2 bg-primary bg-opacity-10 text-primary small fw-bold d-flex justify-content-between">
                                <span>Delivery status:</span>
                                <span id="liveDeliveryStatus">0 arrived, 0 left</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="amountDue" class="form-label fw-bold small text-uppercase">Total Product Cost
                                (ETB) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">ETB</span>
                                <input type="number" class="form-control" id="amountDue" required min="1" step="0.01"
                                    placeholder="0.00" oninput="validatePaymentsInModal()">
                            </div>
                            <div class="text-muted" style="font-size:.72rem;">Auto-calculated from item rows (qty ×
                                price)</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="amountPaid" class="form-label fw-bold small text-uppercase">Amount Actually Paid
                                (ETB)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">ETB</span>
                                <input type="number" class="form-control" id="amountPaid" min="0" step="0.01"
                                    placeholder="0.00" oninput="validatePaymentsInModal()">
                            </div>
                        </div>
                    </div>

                    <!-- Cost Breakdown by Arrival Status -->
                    <div id="costBreakdownCard" class="d-none mb-3">
                        <div class="card border-0 shadow-sm rounded-3"
                            style="background:linear-gradient(135deg,#f0fdf4 0%,#fefce8 100%);">
                            <div class="card-body p-3">
                                <div class="fw-bold small text-uppercase text-secondary mb-2">
                                    <i class="fas fa-chart-pie me-1"></i> Cost Breakdown by Arrival Status
                                </div>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="flex-fill rounded-3 p-2"
                                        style="background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.25);">
                                        <div class="text-success fw-bold" style="font-size:.68rem;">ARRIVED ITEMS COST
                                        </div>
                                        <div class="fw-bold" id="costArrivedDisplay" style="font-size:1rem;">ETB 0.00
                                        </div>
                                        <div class="text-muted" style="font-size:.68rem;">Items marked as arrived</div>
                                    </div>
                                    <div class="flex-fill rounded-3 p-2"
                                        style="background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.2);">
                                        <div class="text-danger fw-bold" style="font-size:.68rem;">PENDING ITEMS COST
                                        </div>
                                        <div class="fw-bold" id="costPendingDisplay" style="font-size:1rem;">ETB 0.00
                                        </div>
                                        <div class="text-muted" style="font-size:.68rem;">Items not yet arrived</div>
                                    </div>
                                    <div class="flex-fill rounded-3 p-2"
                                        style="background:rgba(59,130,246,.08); border:1px solid rgba(59,130,246,.2);">
                                        <div class="text-primary fw-bold" style="font-size:.68rem;">30% PREPAYMENT
                                            TARGET</div>
                                        <div class="fw-bold" id="prepayTargetDisplay" style="font-size:1rem;">ETB 0.00
                                        </div>
                                        <div class="text-muted" style="font-size:.68rem;">Based on total product cost
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-light shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <div class="fw-bold small text-uppercase text-secondary mb-2">
                                <i class="fas fa-image me-1"></i> Attach Receipt Images (Optional)
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <label
                                    class="flex-grow-1 d-flex flex-column align-items-center justify-content-center rounded-3 border border-secondary py-4 text-center mb-0"
                                    style="cursor:pointer;">
                                    <i class="fas fa-cloud-arrow-up text-primary mb-2"></i>
                                    <span class="small fw-bold text-primary">Upload Payment Proof</span>
                                                            <input type="file" id="screenshotInput" accept="image/*" multiple class="d-none"
                                        onchange="handleModalScreenshotUpload(event)">
                                </label>
                                <div id="modalReceiptPreviewContainer" class="d-flex flex-wrap gap-2 align-items-center">
                                    <div id="modalScreenshotPlaceholder"
                                        class="rounded-3 border bg-white overflow-hidden d-flex align-items-center justify-content-center"
                                        style="width:72px; height:72px;">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="text-muted mt-2" style="font-size:.82rem;">Stores image proofs. Helps managers
                                verify receipts immediately.</div>
                        </div>
                    </div>

                    <div class="card border-light shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <div class="fw-bold small text-uppercase text-secondary mb-2">
                                <i class="fas fa-magnifying-glass me-1"></i> Extract Pending Orders from Screenshot
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <label
                                    class="flex-grow-1 d-flex flex-column align-items-center justify-content-center rounded-3 border border-secondary py-4 text-center mb-0"
                                    style="cursor:pointer;">
                                    <i class="fas fa-image-polaroid text-primary mb-2"></i>
                                    <span class="small fw-bold text-primary">Upload Screenshot for Extraction</span>
                                                            <input type="file" id="extractScreenshotInput" accept="image/*" multiple class="d-none"
                                        onchange="handleExtractScreenshotUpload(event)">
                                </label>
                                <div id="modalExtractPreviewContainer" class="d-flex flex-wrap gap-2 align-items-center">
                                    <div id="modalExtractPlaceholder"
                                        class="rounded-3 border bg-white overflow-hidden d-flex align-items-center justify-content-center"
                                        style="width:72px; height:72px;">
                                        <i class="fas fa-file-image"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                                <button type="button" class="btn btn-sm btn-info" id="scanReceiptOrdersBtn" onclick="scanReceiptOrders()">
                                    <i class="fas fa-magnifying-glass"></i> Extract Pending Orders
                                </button>
                                <small class="text-muted">Upload screenshot(s) here only for order extraction.</small>
                            </div>
                            <div class="text-muted mt-2" style="font-size:.82rem;">This upload is separate from proof images and is used only for OCR extraction.</div>
                        </div>
                    </div>

                    <div id="liveStatusPreview"
                        class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white mb-3">
                        <div id="liveStatusIcon"
                            class="d-flex align-items-center justify-content-center rounded-circle bg-secondary text-white"
                            style="width:44px; height:44px;">
                            <i class="fas fa-circle-question"></i>
                        </div>
                        <div>
                            <div class="text-uppercase fw-bold small text-muted">Prepayment Status</div>
                            <div id="liveStatusText" class="fw-bold small">Enter numbers to calculate…</div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSubmit" onclick="handleFormSubmit(event)">
                    <i class="fas fa-save me-1"></i> Save Record
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ── Modal: Quick-Pay ────────────────────── -->
<div class="modal fade" id="quickPayModal" tabindex="-1" aria-labelledby="quickPayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="quickPayModalLabel">
                    <i class="fas fa-cash-register me-2 text-success"></i>Quick Add Payment
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="quickPayCustomerName"></p>
                <form id="quickPayForm" onsubmit="handleQuickPaySubmit(event)" novalidate>
                    <input type="hidden" id="quickPayId">
                    <label class="form-label fw-bold small">Add Payment Amount (ETB)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">ETB</span>
                        <input type="number" class="form-control" id="quickPayAmount" required min="0.01" step="0.01"
                            placeholder="0.00">
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" onclick="handleQuickPaySubmit(event)">
                    <i class="fas fa-check me-1"></i> Log Payment
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ── Modal: Confirm Action ──────────────── -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold text-danger" id="confirmModalLabel">
                    <i class="fas fa-triangle-exclamation me-2"></i><span id="confirmTitle">Confirm Action</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 small" id="confirmMessage">This action cannot be undone.</p>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmActionBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>


<!-- ── Modal: Share Slip ────────────── -->
<div class="modal fade" id="shareCardModal" tabindex="-1" aria-labelledby="shareCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content border-0 shadow" style="background:#0f172a; color:#f1f5f9;">
            <div class="modal-header border-0 py-3" style="background:#1e293b;">
                <h6 class="modal-title fw-bold text-success" id="shareCardModalLabel">
                    <i class="fas fa-camera me-2"></i>Prepayment Status Slip
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" id="reportCardCaptureArea">
                <div class="rounded-3 p-3" style="background:#1e293b; border:1px solid rgba(255,255,255,.08);">
                    <!-- Header line -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary"><i class="fas fa-receipt"></i></span>
                            <small class="fw-bold text-uppercase text-secondary"
                                style="letter-spacing:.07em; font-size:.6rem;">Prepayment Status Slip</small>
                        </div>
                        <small class="text-secondary" id="shareCardDate" style="font-size:.65rem;"></small>
                    </div>
                    <!-- Customer -->
                    <div class="mb-3">
                        <div class="text-secondary fw-bold"
                            style="font-size:.6rem; text-transform:uppercase; letter-spacing:.07em;">Customer</div>
                        <h5 class="fw-bolder mb-0 text-white" id="shareCardCustomerName">—</h5>
                        <div class="text-primary fst-italic small" id="shareCardDetails">—</div>
                    </div>
                    <!-- Cost grid -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="rounded-2 p-2" style="background:#0f172a;">
                                <div class="text-secondary fw-bold" style="font-size:.6rem; text-transform:uppercase;">
                                    Total Cost</div>
                                <div class="fw-bolder text-white" id="shareCardTotalCost">ETB 0.00</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-2 p-2" style="background:#0f172a;">
                                <div class="text-secondary fw-bold" style="font-size:.6rem; text-transform:uppercase;">
                                    30% Threshold</div>
                                <div class="fw-bolder text-info" id="shareCardThreshold">ETB 0.00</div>
                            </div>
                        </div>
                    </div>
                    <!-- Status pill row -->
                    <div id="shareCardStatusBox"
                        class="rounded-2 p-2 mb-3 d-flex justify-content-between align-items-center"
                        style="background:#0f172a;">
                        <div>
                            <div class="text-secondary fw-bold" style="font-size:.6rem; text-transform:uppercase;"
                                id="shareCardStatusHeader">Amount Paid</div>
                            <div class="fw-bolder text-white" id="shareCardAmountPaid">ETB 0.00</div>
                        </div>
                        <span id="shareCardStatusPill" class="badge bg-secondary">—</span>
                    </div>
                    <!-- Delivery bar -->
                    <div class="rounded-2 p-2 mb-3" style="background:#0f172a;">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-secondary fw-bold"
                                style="font-size:.6rem; text-transform:uppercase;">Delivery Progress</small>
                            <small id="shareCardDeliveryPercent" class="text-secondary"
                                style="font-size:.65rem;">0%</small>
                        </div>
                        <div class="progress mb-1" style="height:5px; background:#1e293b;">
                            <div class="progress-bar bg-primary" id="shareCardDeliveryBar" style="width:0%"></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small id="shareCardDeliveryArrived" class="fw-bold text-white"
                                style="font-size:.65rem;">Arrived: 0</small>
                            <small id="shareCardDeliveryLeft" class="fw-bold text-warning" style="font-size:.65rem;">0
                                left</small>
                        </div>
                    </div>
                    <!-- Screenshot proof -->
                    <div id="shareCardScreenshotContainer" class="d-none">
                        <div class="text-secondary fw-bold mb-2" style="font-size:.6rem; text-transform:uppercase;"><i
                                class="fas fa-paperclip me-1"></i>Attached Receipt(s)</div>
                        <div id="shareCardReceiptList" class="d-flex flex-wrap gap-2"></div>
                        <div id="shareCardReceiptCount" class="text-secondary small mt-2"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 py-2 text-center d-block" style="background:#1e293b;">
                <small class="text-success fw-bold">
                    <i class="fas fa-camera me-1"></i>Take a screenshot now to share this slip.
                </small>
                <div class="text-secondary" style="font-size:.65rem;">Win + Shift + S &nbsp;|&nbsp; Cmd + Shift + 4
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ── Modal: History Timeline ─────────────── -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:480px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="historyModalLabel">
                        <i class="fas fa-history me-2 text-secondary"></i>Status Change History
                    </h6>
                    <small class="text-muted" id="history-modal-subtitle">Tracking modifications for customer
                        record</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3" style="max-height:55vh; overflow-y:auto;">
                <div class="cp-timeline" id="history-timeline">
                    <!-- injected dynamically -->
                </div>
                <div id="history-empty" class="d-none text-center text-muted py-4">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <p class="small">No history logs found for this record.</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button class="btn btn-secondary btn-sm w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Toast container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100;" id="toastContainer"></div>


<!-- ════════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════ -->
<script>
    (function () {
        const API_URL = <?php echo json_encode($prepayApiUrl); ?>;
        const PREPAY_RATE = 0.3;
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = <?php echo json_encode($prepayCsrfToken); ?> || (csrfMeta ? csrfMeta.getAttribute('content') : '');
        let tesseractLoadPromise = null;

        if (!csrfToken) {
            console.error('CSRF token is missing. Reload the page or log in again.');
        }

        function withCsrf(payload) {
            if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
                return payload;
            }
            return { ...payload, csrf_token: csrfToken };
        }

        async function apiFetch(url, options = {}) {
            const res = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrfToken, ...(options.headers || {}) },
                ...options
            });
            if (!res.ok) {
                throw new Error(`API request failed (${res.status} ${res.statusText}): ${url}`);
            }
            return res.json();
        }

        async function apiPost(payload) {
            return apiFetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(withCsrf(payload))
            });
        }

        function buildListQueryParams() {
            const url = new URL(API_URL);
            url.searchParams.set('status', currentFilter);
            url.searchParams.set('search', searchInput.value);
            url.searchParams.set('from_date', filterFrom.value);
            url.searchParams.set('to_date', filterTo.value);
            return url;
        }

        async function fetchAllFilteredRecords() {
            const url = buildListQueryParams();
            url.searchParams.set('export_all', '1');
            url.searchParams.set('limit', '10000');
            const data = await apiFetch(url);
            if (!data.isOk) {
                throw new Error(data.message || 'Failed to load records');
            }
            return data.data || [];
        }

        // OCR: pin paths to avoid CSP/CDN surprises on live servers
        const TESSERACT_OCR_OPTIONS = {
            langPath: 'https://cdn.jsdelivr.net/npm/@tesseract.js-data/eng@4.0.0',
            workerPath: 'https://cdn.jsdelivr.net/npm/tesseract.js@2.1.5/dist/worker.min.js',
            corePath: 'https://cdn.jsdelivr.net/npm/tesseract.js-core@2.2.0/tesseract-core.wasm.js'
        };

        function loadTesseract() {
            if (window.Tesseract) {
                return Promise.resolve(window.Tesseract);
            }
            if (!tesseractLoadPromise) {
                tesseractLoadPromise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@2.1.5/dist/tesseract.min.js';
                    script.async = true;
                    script.onload = () => resolve(window.Tesseract);
                    script.onerror = () => reject(new Error('Failed to load OCR engine'));
                    document.head.appendChild(script);
                });
            }
            return tesseractLoadPromise;
        }

        let ocrWorkerPromise = null;
        async function getOcrWorker() {
            if (ocrWorkerPromise) return ocrWorkerPromise;
            ocrWorkerPromise = (async () => {
                const Tesseract = await loadTesseract();
                const worker = await Tesseract.createWorker({
                    langPath: TESSERACT_OCR_OPTIONS.langPath,
                    workerPath: TESSERACT_OCR_OPTIONS.workerPath,
                    corePath: TESSERACT_OCR_OPTIONS.corePath
                });
                await worker.load();
                await worker.loadLanguage('eng');
                await worker.initialize('eng');
                return worker;
            })();
            return ocrWorkerPromise;
        }

        /* ── Bootstrap modal instances ── */
        let bsRecord, bsQuickPay, bsConfirm, bsShare, bsHistory;

        function getBootstrapModal(id) {
            const el = document.getElementById(id);
            if (!el || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                console.error(`Bootstrap modal is not ready for #${id}`);
                return null;
            }
            return bootstrap.Modal.getOrCreateInstance(el);
        }

        // Re-wire close actions when draft-save depends on modal visibility
        document.getElementById('recordModal').addEventListener('hidden.bs.modal', () => {
            editingId = null;
            tempReceiptBase64List = [];
            extractScreenshotBase64List = [];
            existingReceiptImages = [];
            const proofInput = document.getElementById('screenshotInput');
            const extractInput = document.getElementById('extractScreenshotInput');
            if (proofInput) proofInput.value = '';
            if (extractInput) extractInput.value = '';
        });

        /* ── State ── */
        let prepayments = [];
        let currentPage = 1;
        let totalPages = 1;
        let limit = 20;
        let currentFilter = 'all';
        let tempReceiptBase64List = [];
        let extractScreenshotBase64List = [];
        let existingReceiptImages = [];
        let editingId = null;
        let onConfirmCallback = null;

        /* ── Element cache ── */
        const tbody = document.getElementById('tableBody');
        const searchInput = document.getElementById('searchInput');
        const filterFrom = document.getElementById('filterFrom');
        const filterTo = document.getElementById('filterTo');
        const selectAllCb = document.getElementById('selectAll');
        const bulkBar = document.getElementById('bulkActionsToolbar');
        const selectedCountLbl = document.getElementById('selected-count');

        /* ── Init ── */
        window.addEventListener('load', () => {
            fetchPrepayments(1);

            if (typeof bootstrap !== 'undefined') {
                bsRecord = getBootstrapModal('recordModal');
                bsQuickPay = getBootstrapModal('quickPayModal');
                bsConfirm = getBootstrapModal('confirmModal');
                bsShare = getBootstrapModal('shareCardModal');
                bsHistory = getBootstrapModal('historyModal');
            } else {
                console.error('Bootstrap is not defined. Ensure bootstrap.bundle.min.js loads before pre_payment inline script.');
            }

            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-cp-action]');
                if (!btn) return;
                const id = btn.dataset.id;
                const action = btn.dataset.cpAction;
                if (action === 'quickpay') {
                    const item = prepayments.find(p => String(p.id) === String(id));
                    if (item) {
                        openQuickPayModal(id, item.customer_name, parseFloat(btn.dataset.diff) || 0);
                    }
                } else if (action === 'share') {
                    openShareCardModal(id);
                } else if (action === 'arrival') {
                    toggleArrivalStatus(id, btn.dataset.arrived === '1');
                } else if (action === 'edit') {
                    openRecordModal(id);
                } else if (action === 'history') {
                    openHistoryModal(id);
                } else if (action === 'delete') {
                    deleteRecordConfirm(id);
                }
            });
        });

        // ══════════════════════════════════════════
        // 1. FETCH & QUERY
        // ══════════════════════════════════════════
        async function fetchPrepayments(page = 1) {
            currentPage = page;
            const url = new URL(API_URL);
            url.searchParams.set('page', page);
            url.searchParams.set('limit', limit);
            url.searchParams.set('status', currentFilter);
            url.searchParams.set('search', searchInput.value);
            url.searchParams.set('from_date', filterFrom.value);
            url.searchParams.set('to_date', filterTo.value);

            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted">
            <i class="fas fa-spinner fa-spin me-2"></i> Loading prepayment records…
        </td></tr>`;

            try {
                const data = await apiFetch(url);
                if (data.isOk) {
                    prepayments = data.data || [];
                    totalPages = data.pagination.total_pages;
                    renderTable();
                    renderStatsAndAnalytics(data.stats, data.pagination);
                    vpClearSelection();
                } else {
                    showToast(data.message, 'danger');
                }
            } catch (e) {
                console.error('fetchPrepayments error', e, url.toString());
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger small">
                    <i class="fas fa-exclamation-circle me-1"></i> Failed to load records. ${escapeHTML(e.message)}
                </td></tr>`;
                showToast(`Failed to connect to backend API. ${e.message}`, 'danger');
            }
        }

        let fetchTimeout;
        window.debounceFetch = () => { clearTimeout(fetchTimeout); fetchTimeout = setTimeout(() => fetchPrepayments(1), 300); };

        window.resetFilters = () => {
            searchInput.value = filterFrom.value = filterTo.value = '';
            fetchPrepayments(1);
        };

        window.setStatusFilter = (status) => {
            currentFilter = status;
            ['all', 'paid', 'partial', 'unpaid'].forEach(s => {
                const btn = document.getElementById(`filterBtn-${s}`);
                if (!btn) return;
                btn.className = 'cp-tab';
                if (s === status) {
                    if (s === 'all') btn.className += ' active';
                    if (s === 'paid') btn.className += ' active-paid';
                    if (s === 'partial') btn.className += ' active-partial';
                    if (s === 'unpaid') btn.className += ' active-unpaid';
                }
            });
            fetchPrepayments(1);
        };

        window.fetchPrepayments = fetchPrepayments;

        function isRecordArrived(item) {
            return item && (item.is_arrived == 1 || item.is_arrived === true || item.is_arrived === '1');
        }

        function getRecordArrivedCost(item) {
            if (!item) return 0;
            const total = parseFloat(item.amount_due) || 0;
            return Math.max(total - getRecordPendingCost(item), 0);
        }

        function getRecordPendingCost(item) {
            if (!item) return 0;
            if (Array.isArray(item.items) && item.items.length > 0) {
                return item.items.reduce((sum, row) => {
                    const qty = parseInt(row.qty) || 0;
                    const price = parseFloat(row.unit_price) || 0;
                    const rowArrived = parseInt(row.delivered_qty) > 0;
                    return sum + (rowArrived ? 0 : qty * price);
                }, 0);
            }
            return isRecordArrived(item) ? 0 : (parseFloat(item.amount_due) || 0);
        }

        function getRecordPrepaymentTarget(item) {
            return getRecordPendingCost(item) * PREPAY_RATE;
        }

        function getRecordPrepaymentStatus(item) {
            const paid = parseFloat(item?.amount_paid) || 0;
            const target = getRecordPrepaymentTarget(item);
            if (target <= 0) return 'unpaid';
            return paid >= target ? 'paid' : paid > 0 ? 'partial' : 'unpaid';
        }

        // ══════════════════════════════════════════
        // 2. TABLE RENDERING
        // ══════════════════════════════════════════
        function renderTable() {
            tbody.innerHTML = '';
            const emptyState = document.getElementById('emptyState');
            const table = document.getElementById('paymentTable');

            if (prepayments.length === 0) {
                emptyState.classList.remove('d-none');
                table.classList.add('d-none');
                document.getElementById('displayedCount').innerText = 0;
                return;
            }
            emptyState.classList.add('d-none');
            table.classList.remove('d-none');
            document.getElementById('displayedCount').innerText = prepayments.length;

            const fmt = (v) => `ETB ${parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            prepayments.forEach(item => {
                const total = parseFloat(item.amount_due) || 0;
                const paid = parseFloat(item.amount_paid) || 0;
                const required = getRecordPrepaymentTarget(item);
                const diff = required - paid;

                const status = getRecordPrepaymentStatus(item);
                const badgeCls = status === 'paid' ? 'cp-badge-paid' : status === 'partial' ? 'cp-badge-partial' : 'cp-badge-unpaid';
                const dotCls = status === 'paid' ? 'cp-dot-paid' : status === 'partial' ? 'cp-dot-partial' : 'cp-dot-unpaid';
                const label = status === 'paid' ? 'Paid Prepay' : status === 'partial' ? 'Partial' : 'Unpaid';

                /* sub-label */
                let subLabel = '';
                if (status === 'paid') {
                    subLabel = `<span class="badge cp-badge-paid" style="font-size:.6rem;">Standard met</span>`;
                } else if (status === 'partial') {
                    subLabel = `<span class="badge cp-badge-partial" style="font-size:.6rem;">Needs ${fmt(diff)} to clear 30%</span>`;
                } else if (required <= 0) {
                    subLabel = `<span class="badge cp-badge-unpaid" style="font-size:.6rem;">No not-arrived target</span>`;
                } else {
                    subLabel = `<span class="badge cp-badge-unpaid" style="font-size:.6rem;">Target: ${fmt(required)}</span>`;
                }

                /* delivery */
                const totalQty = parseInt(item.total_items) || 0;
                const arrivedQty = parseInt(item.delivered_items) || 0;
                const leftQty = totalQty - arrivedQty;
                const delPct = totalQty > 0 ? Math.round(arrivedQty / totalQty * 100) : 0;

                let delHtml = '<span class="text-muted" style="font-size:.7rem;">—</span>';
                if (totalQty > 0) {
                    const cls = leftQty <= 0 ? 'success' : arrivedQty === 0 ? 'danger' : 'warning';
                    const icon = leftQty <= 0 ? 'check-square' : 'truck';
                    const txt = leftQty <= 0 ? `All ${totalQty} arrived` : arrivedQty === 0 ? `None (${leftQty} left)` : `${arrivedQty}/${totalQty} (${leftQty} left)`;
                    delHtml = `
                    <span class="badge bg-${cls} bg-opacity-10 text-${cls} border border-${cls} border-opacity-25" style="font-size:.65rem;">
                        <i class="fas fa-${icon} me-1"></i>${txt}
                    </span>
                    <div class="progress mt-1" style="height:3px; width:70px;">
                        <div class="progress-bar bg-${cls}" style="width:${delPct}%"></div>
                    </div>`;
                }

                /* slip button */
                const arrived = isRecordArrived(item);

                // per-row cost breakdown by arrival status
                const rowTotal = parseFloat(item.amount_due) || 0;
                const rowArrivedCost = getRecordArrivedCost(item);
                const rowPendingCost = getRecordPendingCost(item);
                let costBreakHtml = '';
                if (rowTotal > 0) {
                    const costLines = [];
                    if (rowArrivedCost > 0) {
                        costLines.push(`<div class="text-success" style="font-size:.65rem;"><i class="fas fa-check-circle me-1"></i>Arrived: ${fmt(rowArrivedCost)}</div>`);
                    }
                    if (rowPendingCost > 0) {
                        costLines.push(`<div class="text-danger" style="font-size:.65rem;"><i class="fas fa-clock me-1"></i>Pending: ${fmt(rowPendingCost)}</div>`);
                    }
                    costBreakHtml = costLines.join('');
                }

                const arrivalBadge = arrived
                    ? `<span class="badge bg-success bg-opacity-10 text-success rounded-pill" style="font-size:.65rem;"><i class="fas fa-truck-moving me-1"></i>Arrived</span>`
                    : `<span class="badge bg-danger bg-opacity-10 text-danger rounded-pill" style="font-size:.65rem;"><i class="fas fa-truck-clock me-1"></i>Not Arrived</span>`;
                const arrivalAction = arrived
                    ? `<button type="button" class="cp-action-btn text-warning" data-cp-action="arrival" data-id="${item.id}" data-arrived="0" title="Mark not arrived"><i class="fas fa-truck-clock"></i></button>`
                    : `<button type="button" class="cp-action-btn text-success" data-cp-action="arrival" data-id="${item.id}" data-arrived="1" title="Mark arrived"><i class="fas fa-truck-moving"></i></button>`;

                const hasReceipts = Array.isArray(item.receipts) ? item.receipts.length > 0 : false;
                const hasReceiptFallback = hasReceipts || item.screenshot;
                const slipBtnClass = hasReceiptFallback ? 'text-success' : 'text-secondary';
                const slipBtnTitle = hasReceiptFallback ? 'View receipt / Slip' : 'Manager Slip';
                const slipBtnIcon = hasReceiptFallback ? 'image-portrait' : 'camera';
                const slipBtn = `<button type="button" class="cp-action-btn ${slipBtnClass}" data-cp-action="share" data-id="${item.id}" title="${slipBtnTitle}"><i class="fas fa-${slipBtnIcon}"></i></button>`;

                const qpBtn = status !== 'paid'
                    ? `<button type="button" class="cp-action-btn text-primary" data-cp-action="quickpay" data-id="${item.id}" data-diff="${Math.max(diff, 0)}" title="Quick Add Payment"><i class="fas fa-cash-register"></i></button>`
                    : '';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td class="ps-3">
                    <input class="form-check-input row-checkbox" type="checkbox" value="${item.id}">
                </td>
                <td>
                    <div class="fw-bold small">${escapeHTML(item.customer_name)}</div>
                    <div class="text-muted" style="font-size:.72rem; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        ${escapeHTML(item.details || 'No details specified')}
                    </div>
                </td>
                <td class="fw-bold small">
                    ${fmt(total)}
                    ${costBreakHtml}
                </td>
                <td>
                    <div class="fw-bold small">${fmt(paid)}</div>
                    <div>${subLabel}</div>
                </td>
                <td>${delHtml}<div class="mt-2">${arrivalBadge}</div></td>
                <td class="text-center">
                    <span class="${badgeCls} badge rounded-pill" style="font-size:.65rem;">
                        <span class="cp-dot ${dotCls}"></span>${label}
                    </span>
                </td>
                <td class="text-end pe-3 text-nowrap">
                    ${slipBtn}
                    ${qpBtn}
                    ${arrivalAction}
                    <button type="button" class="cp-action-btn text-secondary" data-cp-action="edit" data-id="${item.id}" title="Edit">
                        <i class="fas fa-pen-to-square"></i>
                    </button>
                    <button type="button" class="cp-action-btn text-secondary" data-cp-action="history" data-id="${item.id}" title="History">
                        <i class="fas fa-history"></i>
                    </button>
                    <button type="button" class="cp-action-btn text-danger" data-cp-action="delete" data-id="${item.id}" title="Delete">
                        <i class="fas fa-trash-can"></i>
                    </button>
                </td>`;
                tbody.appendChild(tr);
            });
        }

        // ══════════════════════════════════════════
        // 3. STATS & PAGINATION
        // ══════════════════════════════════════════
        function renderStatsAndAnalytics(stats, pagination) {
            if (!stats) return;
            const currentPageNum = pagination.current_page || pagination.page || 1;
            const fmt = v => {
                const num = parseFloat(v) || 0;
                return `ETB ${(!isFinite(num) ? '0.00' : num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}`;
            };
            document.getElementById('statExpected').innerText = fmt(stats.expected);
            document.getElementById('statPrepaymentRequired').innerText = fmt(stats.required);
            document.getElementById('statCollected').innerText = fmt(stats.collected);

            // Arrival cost breakdown strip
            const arrivedCostEl = document.getElementById('statArrivedCost');
            const pendingCostEl = document.getElementById('statPendingCost');
            const arrivedCntEl = document.getElementById('statArrivedCount');
            const pendingCntEl = document.getElementById('statPendingCount');
            const prepay2El = document.getElementById('statPrepayRequired2');
            if (arrivedCostEl) arrivedCostEl.innerText = fmt(stats.arrived_cost || 0);
            if (pendingCostEl) pendingCostEl.innerText = fmt(stats.pending_cost || 0);
            if (arrivedCntEl) arrivedCntEl.innerText = stats.arrived_count || 0;
            if (pendingCntEl) pendingCntEl.innerText = stats.pending_count || 0;
            if (prepay2El) prepay2El.innerText = fmt(stats.required || 0);
            const pct = stats.required > 0 ? Math.min(Math.round(stats.collected / stats.required * 100), 100) : 0;
            const bar = document.getElementById('statCollectionBar');
            bar.style.width = pct + '%';
            bar.setAttribute('aria-valuenow', pct);
            document.getElementById('statCollectionPercent').innerText = pct + '%';

            document.getElementById('countPaid').innerText = stats.paid_count;
            document.getElementById('countPartial').innerText = stats.partial_count;
            document.getElementById('countUnpaid').innerText = stats.unpaid_count;

            /* pagination */
            const tot = pagination.total_records;
            const start = (currentPageNum - 1) * limit + 1;
            const end = Math.min(currentPageNum * limit, tot);
            document.getElementById('paginationInfo').innerText = tot > 0 ? `Showing ${start} to ${end} of ${tot} entries` : 'Showing 0 to 0 of 0 entries';

            const controls = document.getElementById('paginationControls');
            controls.innerHTML = '';
            if (pagination.total_pages <= 1) return;

            const makeLi = (label, page, disabled, active) => {
                const li = document.createElement('li');
                li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
                const a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.innerHTML = label;
                if (!disabled) a.addEventListener('click', e => { e.preventDefault(); fetchPrepayments(page); });
                li.appendChild(a);
                return li;
            };

            controls.appendChild(makeLi('&laquo;', currentPageNum - 1, currentPageNum <= 1, false));
            const sp = Math.max(1, currentPageNum - 2);
            const ep = Math.min(pagination.total_pages, currentPageNum + 2);
            for (let p = sp; p <= ep; p++) {
                controls.appendChild(makeLi(p, p, false, p === currentPageNum));
            }
            controls.appendChild(makeLi('&raquo;', currentPageNum + 1, currentPageNum >= pagination.total_pages, false));
        }

        // ══════════════════════════════════════════
        // 4. DRAFT AUTO-SAVE
        // ══════════════════════════════════════════
        function saveFormDraft() {
            if (editingId) return;
            const modal = document.getElementById('recordModal');
            if (!modal.classList.contains('show')) return;
            const draft = {
                name: document.getElementById('customerName').value,
                details: document.getElementById('dealDetails').value,
                due: document.getElementById('amountDue').value,
                paid: document.getElementById('amountPaid').value,
                totalQty: document.getElementById('totalItems').value,
                arrivedQty: document.getElementById('deliveredItems').value,
                receipts: [...existingReceiptImages, ...tempReceiptBase64List].slice(0, 2)
            };
            const hasContent = Object.values(draft).some(v => v);
            if (hasContent) {
                try {
                    localStorage.setItem('prepay_form_draft', JSON.stringify(draft));
                } catch (e) {
                    const slim = { ...draft, receipts: [] };
                    localStorage.setItem('prepay_form_draft', JSON.stringify(slim));
                }
            }
            else localStorage.removeItem('prepay_form_draft');
        }

        window.loadFormDraft = () => {
            try {
                const draft = JSON.parse(localStorage.getItem('prepay_form_draft') || 'null');
                if (!draft) return;
                document.getElementById('customerName').value = draft.name || '';
                document.getElementById('dealDetails').value = draft.details || '';
                document.getElementById('amountDue').value = draft.due || '';
                document.getElementById('amountPaid').value = draft.paid || '';
                document.getElementById('totalItems').value = draft.totalQty || '';
                document.getElementById('deliveredItems').value = draft.arrivedQty || '';
                if (Array.isArray(draft.receipts) && draft.receipts.length > 0) {
                    tempReceiptBase64List = draft.receipts.filter(Boolean);
                    renderReceiptPreviews();
                }
                validatePaymentsInModal();
                const el = document.getElementById('prepay-draft-alert');
                if (el) el.classList.add('d-none');
                showToast('Draft restored!', 'success');
            } catch (e) { }
        };

        window.discardFormDraft = () => {
            localStorage.removeItem('prepay_form_draft');
            const el = document.getElementById('prepay-draft-alert');
            if (el) el.classList.add('d-none');
            showToast('Draft discarded.', 'secondary');
        };

        document.getElementById('recordForm').addEventListener('input', saveFormDraft);
        document.getElementById('recordForm').addEventListener('change', saveFormDraft);

        // ══════════════════════════════════════════
        // 5. DUPLICATE CHECK & PAST NOTES
        // ══════════════════════════════════════════
        let dealDetailsTimeout;
        let orderIdCheckTimeout;
        let duplicateOrderIds = [];

        window.handleCustomerNameInput = () => {
            saveFormDraft();
            const badge = document.getElementById('duplicate-warning');
            badge.classList.add('d-none');
        };

        window.handleDealDetailsInput = () => {
            saveFormDraft();
            const details = document.getElementById('dealDetails').value.trim();
            const panel = document.getElementById('customerHistoryPanel');
            if (!details || editingId) {
                if (panel) panel.classList.add('d-none');
                return;
            }

            clearTimeout(dealDetailsTimeout);
            dealDetailsTimeout = setTimeout(async () => {
                try {
                    const accountRes = await fetch(`${API_URL}?fetch_customer_id=${encodeURIComponent(details)}`, { headers: { 'X-CSRF-TOKEN': csrfToken } });
                    const accountData = await accountRes.json();
                    if (accountData.isOk && accountData.record) {
                        populateContinuationRecord(accountData.record);
                        showToast('Loaded existing prepayment data for ID ' + details, 'success');
                    } else if (!editingId) {
                        document.getElementById('recordId').value = '';
                        document.getElementById('recordModalLabel').innerHTML = `<i class="fas fa-receipt me-2 text-primary"></i>Add Customer Prepayment`;
                    }

                    const res = await fetch(`${API_URL}?customer_history=${encodeURIComponent(details)}`, { headers: { 'X-CSRF-TOKEN': csrfToken } });
                    const data = await res.json();
                    if (data.isOk && data.data && data.data.length > 0 && panel) {
                        const fmt = v => `ETB ${parseFloat(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        const latest = data.data[0];
                        const summary = data.summary || {};
                        if (!document.getElementById('customerName').value.trim() && latest.customer_name) {
                            document.getElementById('customerName').value = latest.customer_name;
                        }
                        panel.innerHTML = `
                            <div class="fw-bold mb-1"><i class="fas fa-clock-rotate-left me-1"></i>Customer history found</div>
                            <div>${data.data.length} previous record(s). Total paid: <strong>${fmt(summary.total_paid)}</strong> / Total cost: <strong>${fmt(summary.total_due)}</strong>.</div>
                            <div class="text-muted mt-1">Latest: ${escapeHTML(latest.customer_name || 'Unknown')} - ${escapeHTML((latest.created_at || '').split(' ')[0] || '')}</div>`;
                        panel.classList.remove('d-none');
                    } else if (panel) {
                        panel.classList.add('d-none');
                    }
                } catch (e) { }
            }, 500);
        };

        function populateContinuationRecord(record) {
            if (!record) return;

            document.getElementById('recordId').value = record.id || '';
            document.getElementById('recordModalLabel').innerHTML = `<i class="fas fa-clock-rotate-left me-2 text-primary"></i>Continue Customer Prepayment`;
            document.getElementById('customerName').value = record.customer_name || '';
            document.getElementById('dealDetails').value = record.details || document.getElementById('dealDetails').value;
            document.getElementById('totalItems').value = record.total_items || '';
            document.getElementById('deliveredItems').value = record.delivered_items || '';
            document.getElementById('amountDue').value = record.amount_due || '';
            document.getElementById('amountPaid').value = record.amount_paid || '';
            setOrderArrivalState(record.is_arrived == 1 || record.is_arrived === true || record.is_arrived === '1');

            const container = document.getElementById('itemsContainer');
            if (container) container.innerHTML = '';
            if (Array.isArray(record.items) && record.items.length > 0) {
                record.items.forEach(it => {
                    addBlankItemRow({
                        name: it.product_name || '',
                        qty: it.qty || 0,
                        price: it.unit_price || 0,
                        arrived: parseInt(it.delivered_qty) > 0
                    });
                });
            } else {
                addBlankItemRow();
            }

            const nameInput = document.getElementById('customerName');
            nameInput.style.transition = 'background-color .5s';
            nameInput.style.backgroundColor = '#ecfdf5';
            setTimeout(() => nameInput.style.backgroundColor = '', 1500);
            validatePaymentsInModal();
            checkOrderIdsLive();
        }

        function getOrderIdsFromForm() {
            return Array.from(document.querySelectorAll('input[name="item_name[]"]'))
                .map(input => input.value.trim())
                .filter(Boolean);
        }

        function getDuplicateOrderIdsInForm(orderIds) {
            const seen = new Set();
            const duplicates = new Set();
            orderIds.forEach(id => {
                const key = id.toLowerCase();
                if (seen.has(key)) duplicates.add(id);
                seen.add(key);
            });
            return Array.from(duplicates);
        }

        function setOrderIdInvalid(input, message) {
            input.classList.add('is-invalid');
            let feedback = input.parentNode.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.style.fontSize = '10px';
                input.parentNode.appendChild(feedback);
            }
            feedback.textContent = message;
        }

        function clearOrderIdInvalid(input) {
            input.classList.remove('is-invalid');
            const feedback = input.parentNode.querySelector('.invalid-feedback');
            if (feedback) feedback.remove();
        }

        function renderOrderIdWarning(formDuplicates = [], dbDuplicates = []) {
            const warning = document.getElementById('orderIdWarning');
            const btn = document.getElementById('btnSubmit');
            if (!warning || !btn) return;

            const dbIds = dbDuplicates.map(row => row.order_id).filter(Boolean);
            duplicateOrderIds = [...new Set([...formDuplicates, ...dbIds])];

            if (duplicateOrderIds.length === 0) {
                warning.classList.add('d-none');
                warning.innerHTML = '';
                btn.disabled = false;
                return;
            }

            warning.innerHTML = `<i class="fas fa-triangle-exclamation me-1"></i>Duplicate order ID${duplicateOrderIds.length > 1 ? 's' : ''}: <strong>${duplicateOrderIds.map(escapeHTML).join(', ')}</strong>. Use a unique item/order ID.`;
            warning.classList.remove('d-none');
            btn.disabled = true;
        }

        window.checkOrderIdsLive = () => {
            const inputs = Array.from(document.querySelectorAll('input[name="item_name[]"]'));
            inputs.forEach(clearOrderIdInvalid);
            const orderIds = inputs.map(input => input.value.trim()).filter(Boolean);
            const formDuplicates = getDuplicateOrderIdsInForm(orderIds);
            if (orderIds.length === 0) {
                renderOrderIdWarning();
                return;
            }

            inputs.forEach(input => {
                const val = input.value.trim();
                if (val && formDuplicates.some(id => id.toLowerCase() === val.toLowerCase())) {
                    setOrderIdInvalid(input, 'Order ID is duplicated in this form');
                }
            });

            clearTimeout(orderIdCheckTimeout);
            orderIdCheckTimeout = setTimeout(async () => {
                let dbDuplicates = [];
                try {
                    const excludeId = document.getElementById('recordId').value || '';
                    for (const input of inputs) {
                        const val = input.value.trim();
                        if (!val || input.classList.contains('is-invalid')) continue;

                        const cachedDuplicate = prepayments.some(record => {
                            if (record.id == excludeId || !Array.isArray(record.items)) return false;
                            return record.items.some(row => (row.product_name || '').toLowerCase() === val.toLowerCase());
                        });
                        if (cachedDuplicate) {
                            setOrderIdInvalid(input, 'Order ID already exists on this page');
                            dbDuplicates.push({ order_id: val });
                            continue;
                        }

                        const url = `${API_URL}?exact_order_id=${encodeURIComponent(val)}&exclude_id=${encodeURIComponent(excludeId)}`;
                        const res = await fetch(url, { headers: { 'X-CSRF-TOKEN': csrfToken } });
                        const data = await res.json();
                        if (data.isOk && data.data && data.data.length > 0) {
                            setOrderIdInvalid(input, 'Order ID already exists in the system');
                            dbDuplicates.push({ order_id: val });
                        }
                    }
                    renderOrderIdWarning(formDuplicates, dbDuplicates);
                } catch (e) {
                    renderOrderIdWarning(formDuplicates, []);
                }
            }, 400);
        };

        // ══════════════════════════════════════════
        // 6. RECORD MODAL (Add / Edit)
        // ══════════════════════════════════════════
        window.openRecordModal = (id = null) => {
            editingId = id || null;

            const recordForm = document.getElementById('recordForm');
            const recordIdInput = document.getElementById('recordId');
            const duplicateWarning = document.getElementById('duplicate-warning');
            const customerHistoryPanel = document.getElementById('customerHistoryPanel');
            const orderIdWarning = document.getElementById('orderIdWarning');
            const liveDeliveryStatusBox = document.getElementById('liveDeliveryStatusBox');
            const receiptContainer = document.getElementById('modalReceiptPreviewContainer');
            const title = document.getElementById('recordModalLabel');
            const draftAlert = document.getElementById('prepay-draft-alert');

            // Robust check: draftAlert is optional and won't block modal opening if not found
            if (!recordForm || !recordIdInput || !duplicateWarning || !liveDeliveryStatusBox || !receiptContainer || !title) {
                console.error('openRecordModal missing required modal elements', {
                    recordForm, recordIdInput, duplicateWarning, liveDeliveryStatusBox, receiptContainer, title
                });
                return;
            }

            recordForm.reset();
            recordIdInput.value = '';
            duplicateWarning.classList.add('d-none');
            if (customerHistoryPanel) customerHistoryPanel.classList.add('d-none');
            if (orderIdWarning) orderIdWarning.classList.add('d-none');
            duplicateOrderIds = [];
            liveDeliveryStatusBox.classList.add('d-none');
            existingReceiptImages = [];
            tempReceiptBase64List = [];
            renderReceiptPreviews();

            // Clear previous item rows
            const container = document.getElementById('itemsContainer');
            if (container) {
                container.innerHTML = '';
            }

            if (id) {
                const item = prepayments.find(p => p.id == id);
                if (item) {
                    title.innerHTML = `<i class="fas fa-pen-to-square me-2 text-primary"></i>Edit Customer Prepayment`;
                    recordIdInput.value = item.id;
                    document.getElementById('customerName').value = item.customer_name;
                    document.getElementById('dealDetails').value = item.details || '';
                    document.getElementById('totalItems').value = item.total_items || '';
                    document.getElementById('deliveredItems').value = item.delivered_items || '';
                    document.getElementById('amountDue').value = item.amount_due;
                    document.getElementById('amountPaid').value = item.amount_paid;
                    setOrderArrivalState(item.is_arrived == 1 || item.is_arrived === true || item.is_arrived === '1');
                    existingReceiptImages = Array.isArray(item.receipts) ? item.receipts.map(r => r.image_data || '') : [];
                    if (existingReceiptImages.length === 0 && item.screenshot) {
                        existingReceiptImages = [item.screenshot];
                    }
                    renderReceiptPreviews();

                    // Populate items for this prepayment
                    if (item.items && Array.isArray(item.items) && item.items.length > 0) {
                        item.items.forEach(it => {
                            addBlankItemRow({
                                name: it.product_name || '',
                                qty: it.qty || 0,
                                price: it.unit_price || 0,
                                arrived: parseInt(it.delivered_qty) > 0
                            });
                        });
                    } else {
                        addBlankItemRow();
                    }
                }
                if (draftAlert) draftAlert.classList.add('d-none');
            } else {
                title.innerHTML = `<i class="fas fa-receipt me-2 text-primary"></i>Add Customer Prepayment`;
                const hasDraft = !!localStorage.getItem('prepay_form_draft');
                if (draftAlert) draftAlert.classList.toggle('d-none', !hasDraft);
                setOrderArrivalState(false);
                existingReceiptImages = [];
                tempReceiptBase64List = [];
                renderReceiptPreviews();

                // Active one item-row by default for new prepayment
                if (!hasDraft) {
                    addBlankItemRow();
                }
            }

            validatePaymentsInModal();
            renderReceiptPreviews();

            bsRecord = bsRecord || getBootstrapModal('recordModal');
            if (bsRecord && typeof bsRecord.show === 'function') {
                bsRecord.show();
            } else {
                console.error('Bootstrap modal instance is not available for recordModal');
            }
        };

        window.addBlankItemRow = (opts = {}) => {
            const container = document.getElementById('itemsContainer');
            if (!container) {
                console.error('itemsContainer not found in modal');
                return;
            }

            const name = opts.name || '';
            const qty = (typeof opts.qty !== 'undefined') ? opts.qty : 1;
            const price = (typeof opts.price !== 'undefined') ? opts.price : '';
            const arrived = !!opts.arrived;

            const row = document.createElement('div');
            row.className = 'item-row d-flex gap-2 mb-2 align-items-center';
            row.innerHTML = `
            <input name="item_name[]" class="form-control form-control-sm" placeholder="Item name" value="${escapeAttr(name)}">
            <input name="item_qty[]" type="number" min="0" class="form-control form-control-sm item-qty" style="width:90px" value="${qty}">
            <input name="item_price[]" type="number" step="0.01" min="0" class="form-control form-control-sm item-price" style="width:110px" value="${price}">
            <input name="item_prepayment[]" type="number" step="0.01" class="form-control form-control-sm item-prepay" style="width:120px" value="" readonly title="30% prepayment">
            
            <!-- Arrive & Not Arrived action switch buttons -->
            <div class="btn-group btn-group-sm item-delivery-group" role="group" style="width:200px">
                <button type="button" class="btn btn-sm ${arrived ? 'btn-success' : 'btn-outline-success'} item-arrive-btn" title="Mark Arrived">
                    Arrived
                </button>
                <button type="button" class="btn btn-sm ${!arrived ? 'btn-danger' : 'btn-outline-danger'} item-not-arrive-btn" title="Mark Not Arrived">
                    Not Arrived
                </button>
            </div>
            <input type="hidden" name="item_delivered[]" class="item-delivered" value="${arrived ? '1' : '0'}">
            
            <button type="button" class="btn btn-sm btn-outline-danger ms-auto remove-item-btn"><i class="fas fa-trash-alt"></i></button>
        `;
            container.appendChild(row);

            const qtyInput = row.querySelector('.item-qty');
            const priceInput = row.querySelector('.item-price');
            const nameInput = row.querySelector('input[name="item_name[]"]');
            const prepayInput = row.querySelector('.item-prepay');
            const btn = row.querySelector('.remove-item-btn');
            const arriveBtn = row.querySelector('.item-arrive-btn');
            const notArriveBtn = row.querySelector('.item-not-arrive-btn');
            const deliveredInput = row.querySelector('.item-delivered');

            const updateBtnStates = (isArrived) => {
                deliveredInput.value = isArrived ? '1' : '0';
                if (isArrived) {
                    arriveBtn.classList.remove('btn-outline-success');
                    arriveBtn.classList.add('btn-success');
                    notArriveBtn.classList.remove('btn-danger');
                    notArriveBtn.classList.add('btn-outline-danger');
                } else {
                    arriveBtn.classList.remove('btn-success');
                    arriveBtn.classList.add('btn-outline-success');
                    notArriveBtn.classList.remove('btn-outline-danger');
                    notArriveBtn.classList.add('btn-danger');
                }
                calculateItemsSummary();
            };

            arriveBtn.addEventListener('click', () => updateBtnStates(true));
            notArriveBtn.addEventListener('click', () => updateBtnStates(false));

            const refresh = () => {
                const q = parseInt(qtyInput.value) || 0;
                const p = parseFloat(priceInput.value) || 0;
                const prepay = +(q * p * PREPAY_RATE).toFixed(2);
                if (prepayInput) prepayInput.value = prepay;
                calculateItemsSummary();
            };

            if (qtyInput) qtyInput.addEventListener('input', refresh);
            if (priceInput) priceInput.addEventListener('input', refresh);
            if (nameInput) nameInput.addEventListener('input', () => { saveFormDraft(); checkOrderIdsLive(); });
            if (btn) btn.addEventListener('click', () => { row.remove(); calculateItemsSummary(); checkOrderIdsLive(); });

            // compute immediately
            setTimeout(() => { refresh(); checkOrderIdsLive(); }, 0);
            return row;
        };

        window.quickPopulateItems = (count = 3) => {
            const container = document.getElementById('itemsContainer');
            if (!container) return;
            for (let i = 0; i < (count || 1); i++) {
                const idx = container.children.length + 1;
                const name = `Sample item ${idx}`;
                addBlankItemRow({ name, qty: 1, price: (Math.random() * 200).toFixed(2) });
            }
            calculateItemsSummary();
        };

        window.setOrderArrivalState = (arrived) => {
            const yesBtn = document.getElementById('arrivalYesBtn');
            const noBtn = document.getElementById('arrivalNoBtn');
            const hidden = document.getElementById('orderArrived');
            if (!yesBtn || !noBtn || !hidden) return;
            hidden.value = arrived ? '1' : '0';
            yesBtn.classList.toggle('btn-success', arrived);
            yesBtn.classList.toggle('btn-outline-success', !arrived);
            noBtn.classList.toggle('btn-danger', !arrived);
            noBtn.classList.toggle('btn-outline-danger', arrived);
            validatePaymentsInModal();
        };

        function getOrderArrivalState() {
            return document.getElementById('orderArrived')?.value === '1';
        }

        function calculateItemsSummary() {
            try {
                const qtys = Array.from(document.querySelectorAll('input[name="item_qty[]"]'));
                const prices = Array.from(document.querySelectorAll('input[name="item_price[]"]'));
                const prepays = Array.from(document.querySelectorAll('input[name="item_prepayment[]"]'));
                const deliveredInputList = Array.from(document.querySelectorAll('.item-delivered'));

                let totalQty = 0;
                let totalDelivered = 0;
                let totalCost = 0;   // full cost of ALL items (qty × price)
                let costArrived = 0;   // cost of rows marked Arrived
                let costPending = 0;   // cost of rows NOT yet arrived

                for (let i = 0; i < qtys.length; i++) {
                    const q = parseInt(qtys[i].value) || 0;
                    const p = parseFloat((prices[i] && prices[i].value) || 0) || 0;
                    const rowCost = +(q * p).toFixed(2);
                    const pre = +(rowCost * PREPAY_RATE).toFixed(2);

                    // write 30%-prepay column for this row
                    if (prepays[i]) prepays[i].value = pre;

                    totalQty += q;
                    totalCost += rowCost;

                    const isArrived = deliveredInputList[i] && deliveredInputList[i].value === '1';
                    if (isArrived) {
                        totalDelivered += q;
                        costArrived += rowCost;
                    } else {
                        costPending += rowCost;
                    }
                }

                const totalItemsEl = document.getElementById('totalItems');
                const amountDueEl = document.getElementById('amountDue');   // = total product cost
                const deliveredItemsEl = document.getElementById('deliveredItems');

                if (totalItemsEl) {
                    totalItemsEl.value = totalQty;
                    totalItemsEl.readOnly = qtys.length > 0;
                }
                // amountDue = full product cost (not 30%)
                if (amountDueEl && qtys.length > 0) {
                    amountDueEl.value = totalCost.toFixed(2);
                    amountDueEl.readOnly = true;
                } else if (amountDueEl) {
                    amountDueEl.readOnly = false;
                }
                if (deliveredItemsEl) {
                    deliveredItemsEl.value = totalDelivered;
                    deliveredItemsEl.readOnly = qtys.length > 0;
                }

                // ── Delivery status badge ──
                const dBox = document.getElementById('liveDeliveryStatusBox');
                const dLbl = document.getElementById('liveDeliveryStatus');
                if (totalQty > 0) {
                    dBox.classList.remove('d-none');
                    const rem = totalQty - totalDelivered;
                    dLbl.innerText = rem <= 0
                        ? `All ${totalQty} items arrived. Complete!`
                        : `${totalDelivered} arrived, ${rem} left`;
                } else {
                    dBox.classList.add('d-none');
                }

                // ── Cost breakdown card ──
                const fv = v => `ETB ${v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                const breakCard = document.getElementById('costBreakdownCard');
                if (breakCard) {
                    if (totalCost > 0) {
                        breakCard.classList.remove('d-none');
                        const arrivedEl = document.getElementById('costArrivedDisplay');
                        const pendingEl = document.getElementById('costPendingDisplay');
                        const prepayTgt = document.getElementById('prepayTargetDisplay');
                        if (arrivedEl) arrivedEl.innerText = fv(costArrived);
                        if (pendingEl) pendingEl.innerText = fv(costPending);
                        if (prepayTgt) prepayTgt.innerText = fv(costPending * PREPAY_RATE);
                    } else {
                        breakCard.classList.add('d-none');
                    }
                }

                // ── Auto-set overall arrival state ──
                const overallArrived = totalQty > 0 && totalDelivered === totalQty;
                setOrderArrivalState(overallArrived);

                validatePaymentsInModal();

            } catch (e) { console.error('calculateItemsSummary error', e); }
        }

        function getModalPendingCost() {
            const qtys = Array.from(document.querySelectorAll('input[name="item_qty[]"]'));
            const prices = Array.from(document.querySelectorAll('input[name="item_price[]"]'));
            const deliveredInputList = Array.from(document.querySelectorAll('.item-delivered'));

            if (qtys.length > 0) {
                return qtys.reduce((sum, qtyEl, i) => {
                    const q = parseInt(qtyEl.value) || 0;
                    const p = parseFloat((prices[i] && prices[i].value) || 0) || 0;
                    const isArrived = deliveredInputList[i] && deliveredInputList[i].value === '1';
                    return sum + (isArrived ? 0 : q * p);
                }, 0);
            }

            const due = parseFloat(document.getElementById('amountDue').value) || 0;
            return getOrderArrivalState() ? 0 : due;
        }

        function getModalPrepaymentTarget() {
            return getModalPendingCost() * PREPAY_RATE;
        }

        window.validatePaymentsInModal = () => {
            const due = parseFloat(document.getElementById('amountDue').value) || 0;
            const paid = parseFloat(document.getElementById('amountPaid').value) || 0;
            const totalQty = parseInt(document.getElementById('totalItems').value) || 0;
            const arrivedQty = parseInt(document.getElementById('deliveredItems').value) || 0;

            const dBox = document.getElementById('liveDeliveryStatusBox');
            const dLbl = document.getElementById('liveDeliveryStatus');
            if (totalQty > 0) {
                dBox.classList.remove('d-none');
                const rem = totalQty - arrivedQty;
                dLbl.innerText = rem <= 0 ? `All ${totalQty} items arrived. Complete!` : `${arrivedQty} arrived, ${rem} left`;
            } else {
                dBox.classList.add('d-none');
            }

            const box = document.getElementById('liveStatusPreview');
            const icon = document.getElementById('liveStatusIcon');
            const txt = document.getElementById('liveStatusText');
            const fv = v => `ETB ${v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            const arrived = getOrderArrivalState();

            if (due <= 0) {
                box.style.background = '#f8f9fc'; box.style.borderColor = '#d1d3e2';
                icon.innerHTML = `<i class="fas fa-circle-question text-secondary"></i>`;
                txt.innerText = 'Enter numbers to calculate…'; txt.className = 'fw-bold small text-secondary';
            } else {
                const req = getModalPrepaymentTarget();
                if (req <= 0) {
                    box.style.background = '#fee2e2'; box.style.borderColor = '#fecaca';
                    icon.innerHTML = `<i class="fas fa-truck-clock text-danger fs-5"></i>`;
                    txt.innerText = 'No prepayment target because all items have arrived.'; txt.className = 'fw-bold small text-danger';
                } else if (paid >= req) {
                    box.style.background = '#d1fae5'; box.style.borderColor = '#a7f3d0';
                    icon.innerHTML = `<i class="fas fa-circle-check text-success fs-5"></i>`;
                    txt.innerText = `Cleared: prepayment threshold met! Paid: ${fv(paid)}`; txt.className = 'fw-bold small text-success';
                } else if (paid > 0) {
                    box.style.background = '#fef3c7'; box.style.borderColor = '#fde68a';
                    icon.innerHTML = `<i class="fas fa-circle-exclamation text-warning fs-5"></i>`;
                    txt.innerText = `Partial: needs ${fv(req - paid)} more to clear 30% (${fv(req)})`; txt.className = 'fw-bold small text-warning';
                } else {
                    box.style.background = '#fee2e2'; box.style.borderColor = '#fecaca';
                    icon.innerHTML = `<i class="fas fa-circle-xmark text-danger fs-5"></i>`;
                    txt.innerText = `Outstanding: target prepayment of ${fv(req)} is fully unpaid.`; txt.className = 'fw-bold small text-danger';
                }
            }

            if (!arrived && getModalPrepaymentTarget() <= 0) {
                box.style.background = '#fee2e2';
                box.style.borderColor = '#fecaca';
                icon.innerHTML = `<i class="fas fa-truck-field-unlocked text-danger fs-5"></i>`;
                txt.innerText += ' Order not arrived yet.';
                txt.className = 'fw-bold small text-danger';
            }
        };

        window.handleModalScreenshotUpload = async (event) => {
            const files = Array.from(event.target.files || []);
            if (files.length === 0) {
                showToast('No image selected.', 'danger');
                return;
            }

            const readers = files.map(file => new Promise((resolve, reject) => {
                if (!file.type.startsWith('image/')) {
                    reject(new Error('Only image files accepted.'));
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => resolve(e.target.result);
                reader.onerror = () => reject(new Error('Failed to read file.'));
                reader.readAsDataURL(file);
            }));

            try {
                const images = await Promise.all(readers);
                images.forEach(image => {
                    if (image) tempReceiptBase64List.push(image);
                });
                renderReceiptPreviews();
                saveFormDraft();
                showToast('Receipt image(s) attached.', 'success');
            } catch (e) {
                showToast(e.message || 'Unable to attach receipt.', 'danger');
            }
        };

        window.handleExtractScreenshotUpload = async (event) => {
            const files = Array.from(event.target.files || []);
            if (files.length === 0) {
                showToast('No image selected for extraction.', 'danger');
                return;
            }

            const readers = files.map(file => new Promise((resolve, reject) => {
                if (!file.type.startsWith('image/')) {
                    reject(new Error('Only image files accepted.'));
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => resolve(e.target.result);
                reader.onerror = () => reject(new Error('Failed to read file.'));
                reader.readAsDataURL(file);
            }));

            try {
                const images = await Promise.all(readers);
                images.forEach(image => {
                    if (image) extractScreenshotBase64List.push(image);
                });
                renderExtractPreviews();
                showToast('Extraction screenshot(s) attached.', 'success');
            } catch (e) {
                showToast(e.message || 'Unable to attach extraction screenshot.', 'danger');
            }
        };

        function parsePendingOrdersFromText(text) {
            const lines = (text || '').split(/\r?\n/).map(l => l.trim()).filter(Boolean);
            const rows = [];
            for (const line of lines) {
                const statusMatch = line.match(/\b(pending|confirmed|canceled|cancelled|paid)\b/i);
                if (!statusMatch) continue;
                const status = statusMatch[1].toLowerCase();
                if (status !== 'pending') continue;

                const idMatch = line.match(/\b(\d{4,})\b/);
                const amountMatch = line.match(/([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{2})?|[0-9]+(?:\.[0-9]{2})?)\s*ETB/i)
                    || line.match(/ETB\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{2})?|[0-9]+(?:\.[0-9]{2})?)/i);

                if (!idMatch || !amountMatch) continue;

                const orderId = idMatch[1];
                const amountText = amountMatch[1] || amountMatch[2] || '';
                const amountValue = parseFloat((amountText || '').replace(/,/g, ''));
                if (Number.isNaN(amountValue)) continue;

                rows.push({ orderId, amount: amountValue });
            }
            return rows;
        }

        function addExtractedOrderRows(rows) {
            if (!Array.isArray(rows) || rows.length === 0) return 0;
            const existingNames = Array.from(document.querySelectorAll('input[name="item_name[]"]')).map(el => el.value.trim());
            let added = 0;
            rows.forEach(({ orderId, amount }) => {
                const label = `${orderId}`;
                if (existingNames.includes(label)) return;
                addBlankItemRow({ name: label, qty: 1, price: amount.toFixed(2) });
                added += 1;
            });
            if (added > 0) {
                calculateItemsSummary();
                saveFormDraft();
            }
            return added;
        }

        window.scanReceiptOrders = async () => {
            const scanButton = document.getElementById('scanReceiptOrdersBtn');
            const images = [...extractScreenshotBase64List].filter(Boolean);
            if (images.length === 0) {
                showToast('Upload a screenshot in the extraction section first to extract pending orders.', 'warning');
                return;
            }
            scanButton.disabled = true;
            const originalText = scanButton.innerHTML;
            scanButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Extracting...';

            try {
                const worker = await getOcrWorker();
                let allRows = [];
                for (const imageSrc of images) {
                    const ocrResult = await worker.recognize(imageSrc);
                    const text = ocrResult?.data?.text || '';
                    const rows = parsePendingOrdersFromText(text);
                    allRows = allRows.concat(rows);
                }
                if (allRows.length === 0) {
                    showToast('No pending order rows found in the screenshot.', 'warning');
                    return;
                }
                const uniqueRows = [];
                const seen = new Set();
                allRows.forEach(r => {
                    const key = `${r.orderId}-${r.amount}`;
                    if (!seen.has(key)) {
                        seen.add(key);
                        uniqueRows.push(r);
                    }
                });
                const added = addExtractedOrderRows(uniqueRows);
                if (added > 0) {
                    showToast(`Added ${added} pending order${added > 1 ? 's' : ''} from receipt.`, 'success');
                } else {
                    showToast('Pending orders were found, but they already exist in the list.', 'info');
                }
            } catch (err) {
                console.error('OCR extract error', err);
                showToast('Failed to extract pending orders from screenshot.', 'danger');
            } finally {
                scanButton.disabled = false;
                scanButton.innerHTML = originalText;
            }
        };

        function renderReceiptPreviews() {
            const container = document.getElementById('modalReceiptPreviewContainer');
            if (!container) return;
            const allReceipts = [...existingReceiptImages, ...tempReceiptBase64List];
            if (allReceipts.length === 0) {
                container.innerHTML = `<div id="modalScreenshotPlaceholder" class="rounded-3 border bg-white overflow-hidden d-flex align-items-center justify-content-center" style="width:72px; height:72px;"><i class="fas fa-camera"></i></div>`;
                return;
            }
            container.innerHTML = allReceipts.map((src, index) => {
                if (!src) return '';
                return `
                    <div class="position-relative rounded-3 overflow-hidden border" style="width:72px; height:72px;">
                        <img src="${escapeAttr(src)}" class="w-100 h-100" style="object-fit:cover;" alt="Receipt ${index + 1}">
                        ${index >= existingReceiptImages.length ? `<button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" style="z-index:2;" onclick="removeTempReceipt(${index - existingReceiptImages.length})"><i class="fas fa-times" style="font-size:.7rem;"></i></button>` : ''}
                    </div>`;
            }).join('');
        }

        function renderExtractPreviews() {
            const container = document.getElementById('modalExtractPreviewContainer');
            if (!container) return;
            if (extractScreenshotBase64List.length === 0) {
                container.innerHTML = `<div id="modalExtractPlaceholder" class="rounded-3 border bg-white overflow-hidden d-flex align-items-center justify-content-center" style="width:72px; height:72px;"><i class="fas fa-file-image"></i></div>`;
                return;
            }
            container.innerHTML = extractScreenshotBase64List.map((src, index) => {
                if (!src) return '';
                return `
                    <div class="position-relative rounded-3 overflow-hidden border" style="width:72px; height:72px;">
                        <img src="${escapeAttr(src)}" class="w-100 h-100" style="object-fit:cover;" alt="Extraction Screenshot ${index + 1}">
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" style="z-index:2;" onclick="removeExtractReceipt(${index})"><i class="fas fa-times" style="font-size:.7rem;"></i></button>
                    </div>`;
            }).join('');
        }

        window.removeExtractReceipt = (index) => {
            if (index < 0 || index >= extractScreenshotBase64List.length) return;
            extractScreenshotBase64List.splice(index, 1);
            renderExtractPreviews();
        };

        window.removeTempReceipt = (index) => {
            if (index < 0 || index >= tempReceiptBase64List.length) return;
            tempReceiptBase64List.splice(index, 1);
            renderReceiptPreviews();
            saveFormDraft();
        };

        window.handleFormSubmit = async (event) => {
            if (event && event.preventDefault) event.preventDefault();
            const recordId = document.getElementById('recordId').value;
            const name = document.getElementById('customerName').value.trim();
            const due = parseFloat(document.getElementById('amountDue').value) || 0;
            if (!name || due <= 0) return;

            const formDuplicateIds = getDuplicateOrderIdsInForm(getOrderIdsFromForm());
            if (formDuplicateIds.length > 0 || duplicateOrderIds.length > 0) {
                renderOrderIdWarning(formDuplicateIds, duplicateOrderIds.map(id => ({ order_id: id })));
                showToast('Please fix duplicate order IDs before saving.', 'danger');
                return;
            }

            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving…';

            // Collect item rows (if any)
            const items = [];
            try {
                const names = Array.from(document.querySelectorAll('input[name="item_name[]"]'));
                const qtys = Array.from(document.querySelectorAll('input[name="item_qty[]"]'));
                const prices = Array.from(document.querySelectorAll('input[name="item_price[]"]'));
                const deliveredInputList = Array.from(document.querySelectorAll('.item-delivered'));
                for (let i = 0; i < names.length; i++) {
                    const nm = names[i].value.trim();
                    if (!nm) continue;
                    const q = parseInt((qtys[i] && qtys[i].value) || 0) || 0;
                    const p = parseFloat((prices[i] && prices[i].value) || 0) || 0;
                    const isArrived = deliveredInputList[i] && deliveredInputList[i].value === '1';
                    const deliveredQty = isArrived ? q : 0;
                    items.push({ name: nm, qty: q, price: p, delivered: deliveredQty });
                }
            } catch (e) { /* ignore */ }

            const payload = {
                customer_name: name,
                details: document.getElementById('dealDetails').value.trim(),
                amount_due: due,
                amount_paid: parseFloat(document.getElementById('amountPaid').value) || 0,
                total_items: parseInt(document.getElementById('totalItems').value) || 0,
                delivered_items: parseInt(document.getElementById('deliveredItems').value) || 0,
                is_arrived: getOrderArrivalState() ? 1 : 0,
                screenshot: tempReceiptBase64List.length > 0 ? tempReceiptBase64List[0] : '',
                receipts: tempReceiptBase64List,
                items: items
            };
            if (recordId) { payload._method = 'PUT'; payload.id = recordId; payload.action = 'update'; }

            try {
                const data = await apiPost(payload);
                if (data.isOk) {
                    showToast(data.message || 'Record saved!', 'success');
                    (bsRecord || getBootstrapModal('recordModal'))?.hide();
                    if (!recordId) localStorage.removeItem('prepay_form_draft');
                    fetchPrepayments(recordId ? currentPage : 1);
                } else {
                    showToast(data.message, 'danger');
                }
            } catch (e) { showToast('Error saving record.', 'danger'); }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Save Record';
        };

        // ══════════════════════════════════════════
        // 7. QUICK-PAY
        // ══════════════════════════════════════════
        window.openQuickPayModal = (id, customerName, diff) => {
            document.getElementById('quickPayId').value = id;
            document.getElementById('quickPayCustomerName').innerText = `Adding payment for: ${customerName}`;
            document.getElementById('quickPayAmount').value = diff > 0 ? parseFloat(diff.toFixed(2)) : '';
            bsQuickPay = bsQuickPay || getBootstrapModal('quickPayModal');
            if (bsQuickPay) bsQuickPay.show();
            setTimeout(() => document.getElementById('quickPayAmount').focus(), 300);
        };

        window.handleQuickPaySubmit = async (event) => {
            if (event && event.preventDefault) event.preventDefault();
            const id = document.getElementById('quickPayId').value;
            const amount = parseFloat(document.getElementById('quickPayAmount').value) || 0;
            if (amount <= 0) return;
            try {
                const data = await apiPost({ _method: 'PUT', action: 'quick_pay', id, amount });
                if (data.isOk) { showToast(data.message, 'success'); (bsQuickPay || getBootstrapModal('quickPayModal'))?.hide(); fetchPrepayments(currentPage); }
                else showToast(data.message, 'danger');
            } catch (e) { showToast('Failed to log payment.', 'danger'); }
        };

        window.toggleArrivalStatus = async (id, arrived) => {
            try {
                const data = await apiPost({
                    _method: 'PUT',
                    action: 'arrival_status',
                    id,
                    is_arrived: arrived ? 1 : 0
                });
                if (data.isOk) {
                    showToast(data.message || `Order marked ${arrived ? 'arrived' : 'not arrived'}.`, 'success');
                    fetchPrepayments(currentPage);
                } else {
                    showToast(data.message || 'Failed to update arrival status.', 'danger');
                }
            } catch (e) {
                showToast('Unable to update arrival state.', 'danger');
            }
        };

        // ══════════════════════════════════════════
        // 8. SHARE SLIP
        // ══════════════════════════════════════════
        window.openShareCardModal = (id) => {
            const item = prepayments.find(p => p.id == id);
            if (!item) return;

            const total = parseFloat(item.amount_due) || 0;
            const paid = parseFloat(item.amount_paid) || 0;
            const threshold = getRecordPrepaymentTarget(item);
            const fmt = v => `ETB ${parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
            const status = getRecordPrepaymentStatus(item);

            document.getElementById('shareCardCustomerName').innerText = item.customer_name;
            const detailsEl = document.getElementById('shareCardDetails');
            const detailsText = (item.details || '').trim();
            detailsEl.innerHTML = '';
            if (detailsText) {
                const link = document.createElement('a');
                if (/^https?:\/\//i.test(detailsText)) {
                    link.href = detailsText;
                } else {
                    const targetUrl = new URL('customers/address_book.php', window.location.href);
                    targetUrl.searchParams.set('search', detailsText);
                    link.href = targetUrl.toString();
                }
                link.textContent = detailsText;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.style.color = '#7dd3fc';
                link.style.textDecoration = 'underline';
                detailsEl.appendChild(link);
            } else {
                detailsEl.innerText = 'No details provided';
            }
            document.getElementById('shareCardDate').innerText = (item.created_at || '').split(' ')[0];
            document.getElementById('shareCardTotalCost').innerText = fmt(total);
            document.getElementById('shareCardThreshold').innerText = fmt(threshold);
            document.getElementById('shareCardAmountPaid').innerText = fmt(paid);

            const pilEl = document.getElementById('shareCardStatusPill');
            const boxEl = document.getElementById('shareCardStatusBox');
            const hdrEl = document.getElementById('shareCardStatusHeader');
            if (status === 'paid') {
                pilEl.className = 'badge bg-success'; pilEl.innerText = 'PAID PREPAY';
                hdrEl.innerText = 'Prepayment Cleared (30%+)';
                boxEl.style.borderColor = 'rgba(28,200,138,.3)';
            } else if (status === 'partial') {
                pilEl.className = 'badge bg-warning text-dark'; pilEl.innerText = 'PARTIAL';
                hdrEl.innerText = 'Amount Paid (Partial)';
                boxEl.style.borderColor = 'rgba(246,194,62,.3)';
            } else {
                pilEl.className = 'badge bg-danger'; pilEl.innerText = 'UNPAID';
                hdrEl.innerText = threshold <= 0 ? 'Amount Paid (No Arrival Target)' : 'Amount Paid (Outstanding)';
                boxEl.style.borderColor = 'rgba(231,74,59,.3)';
            }

            const totalQty = parseInt(item.total_items) || 0;
            const arrQty = parseInt(item.delivered_items) || 0;
            const leftQty = totalQty - arrQty;
            const delPct = totalQty > 0 ? Math.round(arrQty / totalQty * 100) : 0;
            document.getElementById('shareCardDeliveryPercent').innerText = delPct + '%';
            document.getElementById('shareCardDeliveryBar').style.width = delPct + '%';
            document.getElementById('shareCardDeliveryArrived').innerText = `Arrived: ${arrQty}`;
            document.getElementById('shareCardDeliveryLeft').innerText = leftQty > 0 ? `${leftQty} left` : 'Fully Arrived';

            const sc = document.getElementById('shareCardScreenshotContainer');
            const list = document.getElementById('shareCardReceiptList');
            const count = document.getElementById('shareCardReceiptCount');
            const receipts = Array.isArray(item.receipts) ? item.receipts.map(r => r.image_data || '').filter(Boolean) : [];
            if (receipts.length === 0 && item.screenshot) {
                receipts.push(item.screenshot);
            }
            if (receipts.length > 0) {
                list.innerHTML = receipts.map((imgSrc, index) => `
                    <div class="rounded-3 overflow-hidden border" style="width:120px; height:100px;">
                        <img src="${escapeAttr(imgSrc)}" class="w-100 h-100" style="object-fit:cover;" alt="Receipt ${index + 1}">
                    </div>
                `).join('');
                count.innerText = `${receipts.length} receipt${receipts.length > 1 ? 's' : ''} attached.`;
                sc.classList.remove('d-none');
            } else {
                sc.classList.add('d-none');
            }

        bsShare = bsShare || getBootstrapModal('shareCardModal');
        if (bsShare) bsShare.show();
        };

        // ══════════════════════════════════════════
        // 9. HISTORY TIMELINE
        // ══════════════════════════════════════════
        window.openHistoryModal = async (id) => {
            const subtitle = document.getElementById('history-modal-subtitle');
            const timeline = document.getElementById('history-timeline');
            const empty = document.getElementById('history-empty');

            subtitle.innerText = `Loading history for record #${id}…`;
            timeline.innerHTML = `<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading…</div>`;
            empty.classList.add('d-none');
        bsHistory = bsHistory || getBootstrapModal('historyModal');
        if (bsHistory) bsHistory.show();

            try {
                const res = await fetch(`${API_URL}?history_id=${id}`, { headers: { 'X-CSRF-TOKEN': csrfToken } });
                const data = await res.json();
                if (data.isOk && data.data && data.data.length > 0) {
                    timeline.innerHTML = data.data.map(item => {
                        let dotCls = '', actionText = item.action, detailsHtml = '';
                        const time = new Date(item.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

                        if (item.action === 'CREATE') {
                            dotCls = 'create'; actionText = 'Record Created';
                        } else if (item.action === 'UPDATE_STATUS') {
                            dotCls = 'status';
                            const d = item.details_decoded || {};
                            actionText = `Status → <span class="badge bg-light text-dark border" style="font-size:.65rem;">${escapeHTML(d.new_status || '—')}</span>`;
                            if (d.old_status) {
                                detailsHtml = `<div class="text-muted" style="font-size:.72rem;">From <strong>${escapeHTML(d.old_status)}</strong> to <strong>${escapeHTML(d.new_status || '')}</strong></div>`;
                            }
                        } else if (item.action === 'UPDATE_DATA') {
                            dotCls = 'update'; actionText = 'Data Updated';
                        } else if (item.action === 'DELETE') {
                            dotCls = 'delete'; actionText = 'Record Deleted';
                        }

                        return `<div class="cp-timeline-item">
                        <div class="cp-timeline-dot ${dotCls}"></div>
                        <div class="border rounded-2 p-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold small">${escapeHTML(item.actor_name || 'System')}</span>
                                <span class="text-muted" style="font-size:.65rem;">${time}</span>
                            </div>
                            <div class="small">${actionText}</div>
                            ${detailsHtml}
                        </div>
                    </div>`;
                    }).join('');
                    subtitle.innerText = `Timeline for prepayment #${id}`;
                } else {
                    timeline.innerHTML = '';
                    empty.classList.remove('d-none');
                    subtitle.innerText = `No history found for record #${id}`;
                }
            } catch (e) {
                timeline.innerHTML = `<div class="text-danger text-center small py-3">Failed to load history.</div>`;
            }
        };

        // ══════════════════════════════════════════
        // 10. CONFIRM DIALOG
        // ══════════════════════════════════════════
        function showConfirmModal(title, msg, onConfirm) {
            document.getElementById('confirmTitle').innerText = title;
            document.getElementById('confirmMessage').innerText = msg;
            onConfirmCallback = onConfirm;
            document.getElementById('confirmActionBtn').onclick = () => { if (onConfirmCallback) onConfirmCallback(); (bsConfirm || getBootstrapModal('confirmModal'))?.hide(); };
            bsConfirm = bsConfirm || getBootstrapModal('confirmModal');
            if (bsConfirm) bsConfirm.show();
        }

        window.deleteRecordConfirm = (id) => {
            const item = prepayments.find(p => p.id == id);
            if (!item) return;
            showConfirmModal(
                'Delete Prepayment Record',
                `Permanently delete prepayment for "${item.customer_name}"? This cannot be undone.`,
                async () => {
                    try {
                        const data = await apiPost({ _method: 'DELETE', id });
                        if (data.isOk) { showToast(data.message || 'Deleted.', 'success'); fetchPrepayments(prepayments.length === 1 && currentPage > 1 ? currentPage - 1 : currentPage); }
                        else showToast(data.message, 'danger');
                    } catch (e) { showToast('Failed to delete.', 'danger'); }
                }
            );
        };

        // ══════════════════════════════════════════
        // 11. BULK OPERATIONS
        // ══════════════════════════════════════════
        function updateBulkUI() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const count = checked.length;
            if (selectedCountLbl) selectedCountLbl.innerText = count;
            if (bulkBar) count > 0 ? bulkBar.classList.add('show') : bulkBar.classList.remove('show');
            if (!count && selectAllCb) selectAllCb.checked = false;
        }

        if (selectAllCb) {
            selectAllCb.addEventListener('change', () => {
                document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = selectAllCb.checked);
                updateBulkUI();
            });
        }

        tbody.addEventListener('change', e => {
            if (e.target.classList.contains('row-checkbox')) {
                updateBulkUI();
                const total = document.querySelectorAll('.row-checkbox').length;
                const checked = document.querySelectorAll('.row-checkbox:checked').length;
                if (selectAllCb) selectAllCb.checked = total > 0 && total === checked;
            }
        });

        window.vpClearSelection = () => {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
            if (selectAllCb) selectAllCb.checked = false;
            updateBulkUI();
        };

        window.vpBulkAction = (action) => {
            const ids = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            if (!ids.length) return;

            const labels = { bulk_paid: 'Prepay 30%', bulk_clear: 'Clear 100%', bulk_delete: 'Delete' };
            showConfirmModal(
                `Bulk: ${labels[action]}`,
                `Apply "${labels[action]}" to ${ids.length} selected record(s)?${action === 'bulk_delete' ? ' This cannot be undone.' : ''}`,
                async () => {
                    let payload = action === 'bulk_delete'
                        ? { _method: 'DELETE', ids }
                        : { _method: 'PUT', action, ids };
                    try {
                        const data = await apiPost(payload);
                        if (data.isOk) { showToast(data.message, 'success'); vpClearSelection(); fetchPrepayments(currentPage); }
                        else showToast(data.message, 'danger');
                    } catch (e) { showToast('Bulk operation failed.', 'danger'); }
                }
            );
        };

        // ══════════════════════════════════════════
        // 12. DEMO / BACKUP / RESTORE
        // ══════════════════════════════════════════
        window.clearAllDataConfirm = () => {
            showConfirmModal(
                'Clear ALL Records',
                'Permanently delete every customer prepayment record? This cannot be undone.',
                async () => {
                    try {
                        const data = await apiPost({ _method: 'DELETE', purge_all: true, confirm: 'DELETE_ALL_PREPAYMENTS' });
                        if (data.isOk) {
                            showToast(data.message || 'All records cleared.', 'success');
                            fetchPrepayments(1);
                        } else {
                            showToast(data.message || 'Failed to clear.', 'danger');
                        }
                    } catch (e) {
                        showToast('Failed to clear.', 'danger');
                    }
                }
            );
        };

        window.exportToCSV = async () => {
            try {
                const rows = await fetchAllFilteredRecords();
                if (!rows.length) {
                    showToast('No records to export.', 'secondary');
                    return;
                }
                let csv = 'Customer,Details,Total Cost,Amount Paid,30% Target,Status,Total Ordered,Arrived,Delivery%\n';
                rows.forEach(item => {
                    const total = parseFloat(item.amount_due) || 0;
                    const paid = parseFloat(item.amount_paid) || 0;
                    const target = getRecordPrepaymentTarget(item);
                    const status = getRecordPrepaymentStatus(item);
                    const tq = parseInt(item.total_items) || 0;
                    const aq = parseInt(item.delivered_items) || 0;
                    const dpct = tq > 0 ? ((aq / tq) * 100).toFixed(1) : 0;
                    csv += `"${(item.customer_name || '').replace(/"/g, '""')}","${(item.details || '').replace(/"/g, '""')}",${total},${paid},${target.toFixed(2)},${status},${tq},${aq},${dpct}%\n`;
                });
                const a = document.createElement('a');
                a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
                a.download = 'Prepayment_Ledger.csv';
                a.click();
                showToast(`CSV exported (${rows.length} records).`, 'success');
            } catch (e) {
                showToast('Export failed.', 'danger');
            }
        };

        window.exportJSON = async () => {
            try {
                const rows = await fetchAllFilteredRecords();
                if (!rows.length) {
                    showToast('No records to backup.', 'secondary');
                    return;
                }
                const a = document.createElement('a');
                a.href = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(rows, null, 2));
                a.download = 'Prepayments_Backup.json';
                a.click();
                showToast(`JSON backup saved (${rows.length} records).`, 'success');
            } catch (e) {
                showToast('Backup failed.', 'danger');
            }
        };

        window.importJSON = (event) => {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = async e => {
                try {
                    const arr = JSON.parse(e.target.result);
                    if (!Array.isArray(arr)) throw new Error('Invalid JSON');
                    let n = 0;
                    for (const item of arr) {
                        if (!item.customer_name || !item.amount_due) continue;
                        try {
                            const data = await apiPost({
                                customer_name: item.customer_name, details: item.details || '',
                                amount_due: parseFloat(item.amount_due), amount_paid: parseFloat(item.amount_paid || 0),
                                total_items: parseInt(item.total_items || 0), delivered_items: parseInt(item.delivered_items || 0),
                                screenshot: item.screenshot || ''
                            });
                            if (data.isOk) n++;
                        } catch (e2) { }
                    }
                    showToast(`Restore complete: ${n} records loaded.`, 'success');
                    fetchPrepayments(1);
                } catch (e) { showToast('Failed to parse JSON file.', 'danger'); }
            };
            reader.readAsText(file);
            event.target.value = '';
        };

        // ══════════════════════════════════════════
        // 13. TOAST HELPER
        // ══════════════════════════════════════════
        function showToast(msg, type = 'primary') {
            const container = document.getElementById('toastContainer');
            const icons = { success: 'check-circle', danger: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle', secondary: 'bell', primary: 'info-circle' };
            const iconName = icons[type] || 'info-circle';
            const el = document.createElement('div');
            el.className = `toast align-items-center text-bg-${type} border-0 show`;
            el.role = 'alert';
            el.setAttribute('aria-live', 'assertive');
            const row = document.createElement('div');
            row.className = 'd-flex';
            const body = document.createElement('div');
            body.className = 'toast-body d-flex align-items-center gap-2';
            body.style.fontSize = '.82rem';
            const icon = document.createElement('i');
            icon.className = `fas fa-${iconName}`;
            const text = document.createElement('span');
            text.textContent = msg || '';
            body.appendChild(icon);
            body.appendChild(text);
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close btn-close-white me-2 m-auto';
            closeBtn.setAttribute('data-bs-dismiss', 'toast');
            row.appendChild(body);
            row.appendChild(closeBtn);
            el.appendChild(row);
            container.appendChild(el);
            setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 400); }, 3500);
        }

        // ── Helpers ────────────────────────────────
        function escapeHTML(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }
        function escapeAttr(str) {
            return escapeHTML(str);
        }
        function escapeJS(str) { return (str || '').replace(/'/g, "\\'"); }

    })();
</script>

<?php require_once "includes/footer.php"; ?>
