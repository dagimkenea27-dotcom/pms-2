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

    .vp-toast-warning {
        border-left: 4px solid #f59e0b;
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

    /* Bulk Actions Bar */
    .vp-bulk-bar {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 20px;
        display: none; /* hidden by default */
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .vp-bulk-bar.show {
        display: flex;
        animation: slideDown 0.3s ease forwards;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Result Modal Professional Styling */
    .result-modal-content {
        border: none;
        border-radius: 24px !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    }
    .result-icon-circle {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 10px auto 20px;
        font-size: 28px;
        transition: all 0.3s ease;
    }
    .bg-res-success { background-color: #ecfdf5; color: #10b981; border: 4px solid #d1fae5; }
    .bg-res-warning { background-color: #fffbeb; color: #f59e0b; border: 4px solid #fef3c7; }
    .bg-res-error { background-color: #fef2f2; color: #ef4444; border: 4px solid #fee2e2; }

    /* Timeline Styles */
    .vp-timeline {
        position: relative;
        padding-left: 30px;
        margin-top: 10px;
    }

    .vp-timeline::before {
        content: '';
        position: absolute;
        left: 4px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }

    .vp-timeline-item {
        position: relative;
        margin-bottom: 25px;
    }

    .vp-timeline-dot {
        position: absolute;
        left: -30px;
        top: 4px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #94a3b8;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #e2e8f0;
        z-index: 1;
    }

    .vp-timeline-dot.status-change { background: #3b82f6; box-shadow: 0 0 0 2px #dbeafe; }
    .vp-timeline-dot.create { background: #10b981; box-shadow: 0 0 0 2px #d1fae5; }
    .vp-timeline-dot.delete { background: #ef4444; box-shadow: 0 0 0 2px #fee2e2; }

    .vp-timeline-content {
        background: #f8fafc;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 13px;
        border: 1px solid #e2e8f0;
    }

    .vp-timeline-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .vp-timeline-actor {
        font-weight: 700;
        color: #1e293b;
    }

    .vp-timeline-time {
        font-size: 10px;
        color: #94a3b8;
        font-weight: 600;
    }

    .vp-timeline-action {
        font-weight: 600;
        color: #475569;
        margin-bottom: 3px;
    }

    .vp-timeline-details {
        font-size: 12px;
        color: #64748b;
        background: #fff;
        padding: 8px;
        border-radius: 6px;
        border: 1px dashed #e2e8f0;
        margin-top: 5px;
    }

    .vp-btn-history {
        background: #f1f5f9;
        color: #475569;
    }

    .vp-btn-export {
        background: #ecfdf5;
        color: #065f46;
        font-weight: 700;
        border-radius: 10px;
        padding: 8px 18px;
        border: 1px solid #a7f3d0;
        transition: all 0.2s ease;
        font-size: 13px;
    }

    .vp-btn-export:hover {
        background: #d1fae5;
        color: #064e3b;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16,185,129,0.15);
    }

    .vp-btn-export::after {
        border-top-color: #065f46;
    }

    #exportDropdown + .dropdown-menu {
        z-index: 9999;
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
                <div class="dropdown">
                    <button class="btn vp-btn-export d-flex align-items-center gap-2 dropdown-toggle" id="exportDropdown" data-bs-toggle="dropdown" data-bs-strategy="fixed" aria-expanded="false">
                        <i class="fas fa-file-csv"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); vpExportCSV(false)">
                            <i class="fas fa-download me-2 text-muted"></i>Export All (CSV)
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); vpExportCSV(true)">
                            <i class="fas fa-filter me-2 text-success"></i>Export Filtered (CSV)
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div id="bulk-actions-bar" class="vp-bulk-bar vp-fade-in">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 10px;">
                <span id="selected-count">0</span>
            </div>
            <span class="small fw-bold text-muted">Selected</span>
        </div>
        <div class="vr mx-1"></div>
        <div class="d-flex flex-wrap gap-2">
            <button class="vp-action-btn vp-btn-approve" onclick="vpBulkAction('approve')">
                <i class="fas fa-check"></i> Approve
            </button>
            <button class="vp-action-btn vp-btn-reject" onclick="vpBulkAction('reject')">
                <i class="fas fa-times"></i> Reject
            </button>
            <button class="vp-action-btn vp-btn-paid" onclick="vpBulkAction('mark_paid')">
                <i class="fas fa-money-bill"></i> Mark Paid
            </button>
            <button class="vp-action-btn vp-btn-delete" onclick="vpBulkAction('delete')">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
        <button class="btn btn-link btn-sm text-muted ms-auto p-0" onclick="vpClearSelection()">Clear</button>
    </div>

    <!-- Table Card -->
    <div class="card vp-card shadow-sm vp-fade-in vp-fade-in-4">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                        </th>
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
                        <td colspan="12">
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
    <div class="modal fade" id="newRequestModal" tabindex="-1" aria-labelledby="newRequestModalLabel">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="vp-modal-header">
                    <div class="d-flex justify-content-between align-items-start w-100">
                        <div>
                            <h5 class="modal-title" id="newRequestModalLabel"><i class="fas fa-money-check-alt me-2"></i>New Payment Request</h5>
                            <div class="modal-subtitle">Fill in the vendor payment details</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4">
                    <form id="request-form">
                        <!-- Draft Recovery Alert -->
                        <div id="draft-alert" class="alert alert-info py-2 px-3 mb-3 small d-none" style="border-radius: 10px;">
                            <div class="d-flex align-items-center justify-content-between">
                                <span><i class="fas fa-edit me-1"></i> You have an unsaved draft.</span>
                                <div>
                                    <button type="button" class="btn btn-link text-decoration-none p-0 fw-bold me-2 btn-load-draft" style="font-size: 11px;">Load</button>
                                    <button type="button" class="btn btn-link text-decoration-none p-0 text-danger fw-bold btn-discard-draft" style="font-size: 11px;">Discard</button>
                                </div>
                            </div>
                        </div>
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

    <!-- Result Modal -->
    <div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content result-modal-content p-4 text-center">
                <div id="result-icon-container" class="result-icon-circle">
                    <i id="result-fa-icon" class="fas"></i>
                </div>
                <h5 id="resultModalLabel" class="fw-bold mb-2" style="color: #1e293b; letter-spacing: -0.5px;"></h5>
                <p id="result-message" class="text-muted small mb-4 px-2" style="line-height: 1.5;"></p>
                <button type="button" class="btn vp-btn-primary w-100 py-2 fw-bold" data-bs-dismiss="modal" style="border-radius: 12px; font-size: 13px;">
                    Dismiss
                </button>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 20px; overflow: hidden;">
                <div class="vp-modal-header">
                    <div class="d-flex justify-content-between align-items-start w-100">
                        <div>
                            <h5 class="modal-title" id="historyModalLabel"><i class="fas fa-history me-2"></i>Status History</h5>
                            <div class="modal-subtitle" id="history-modal-subtitle">Tracking changes for request</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4" style="max-height: 500px; overflow-y: auto;">
                    <div id="history-timeline" class="vp-timeline">
                        <!-- History items will be injected here -->
                    </div>
                    <div id="history-empty" class="text-center py-4 d-none">
                        <div class="text-muted small">No history found for this request.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light fw-bold w-100" data-bs-dismiss="modal">Close</button>
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

        // Bulk Operation Elements
        const bulkBar = document.getElementById('bulk-actions-bar');
        const selectAllCb = document.getElementById('selectAll');
        const selectedCountLabel = document.getElementById('selected-count');

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

        // Draft Auto-Save Functions
        function saveFormDraft() {
            if (editingId) return; // Do not auto-save when editing existing records
            if (!modalEl.classList.contains('show')) return; // Guard against events during modal hide/reset

            const shopName = document.getElementById('form-shop-name').value.trim();
            const notes = document.getElementById('form-notes').value.trim();

            const orders = [];
            ordersContainer.querySelectorAll('.order-row').forEach(row => {
                const id = row.querySelector('.form-order-id').value.trim();
                const amt = row.querySelector('.form-order-amount').value;
                const paysComm = row.querySelector('.form-pays-comm').checked;
                orders.push({ order_id: id, order_amount: amt, pays_commission: paysComm });
            });

            // Only save if some content exists
            const hasContent = shopName || notes || orders.some(o => o.order_id || o.order_amount);
            if (hasContent) {
                const draft = { shopName, notes, orders };
                localStorage.setItem('vp_request_draft', JSON.stringify(draft));
            } else {
                localStorage.removeItem('vp_request_draft');
            }
        }

        function loadFormDraft() {
            const raw = localStorage.getItem('vp_request_draft');
            if (!raw) return;

            try {
                const draft = JSON.parse(raw);

                // Restore basic inputs
                document.getElementById('form-shop-name').value = draft.shopName || '';
                document.getElementById('form-notes').value = draft.notes || '';

                // Clear extra rows
                const firstRow = ordersContainer.querySelector('.order-row');
                const rows = ordersContainer.querySelectorAll('.order-row');
                rows.forEach((row, i) => { if (i > 0) row.remove(); });

                // Restore rows
                draft.orders.forEach((ord, index) => {
                    let row;
                    if (index === 0) {
                        row = firstRow;
                    } else {
                        row = firstRow.cloneNode(true);
                        row.classList.remove('vp-fade-in');
                        row.querySelectorAll('input').forEach(input => {
                            input.classList.remove('is-invalid');
                        });
                        ordersContainer.appendChild(row);
                    }
                    row.querySelector('.form-order-id').value = ord.order_id || '';
                    row.querySelector('.form-order-amount').value = ord.order_amount || '';
                    row.querySelector('.form-pays-comm').checked = !!ord.pays_commission;
                });

                updateRemoveButtons();
                validateOrderIDs();
                document.getElementById('draft-alert').classList.add('d-none');
                showToast('Draft restored successfully', 'success');
            } catch (e) {
                console.error('Failed to restore draft', e);
            }
        }

        function discardFormDraft() {
            localStorage.removeItem('vp_request_draft');
            document.getElementById('draft-alert').classList.add('d-none');
            showToast('Draft discarded', 'info');
        }

        // Register draft load/discard button actions via event delegation
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-load-draft')) {
                loadFormDraft();
            }
            if (e.target.classList.contains('btn-discard-draft')) {
                discardFormDraft();
            }
        });

        // Listen for input and change events to trigger save
        const requestForm = document.getElementById('request-form');
        if (requestForm) {
            requestForm.addEventListener('input', saveFormDraft);
            requestForm.addEventListener('change', saveFormDraft);
        }

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
            saveFormDraft(); // Auto-save after row addition
        });

        ordersContainer.addEventListener('click', (e) => {
            if (e.target.closest('.btn-remove-row')) {
                const rows = ordersContainer.querySelectorAll('.order-row');
                if (rows.length > 1) {
                    e.target.closest('.order-row').remove();
                    updateRemoveButtons();
                    validateOrderIDs();
                    saveFormDraft(); // Auto-save after row removal
                }
            }
        });

        const resetModal = () => {
            editingId = null;
            document.querySelector('#newRequestModal .modal-title').innerHTML = '<i class="fas fa-money-check-alt me-2"></i>New Payment Request';
            document.querySelector('#newRequestModal .modal-subtitle').textContent = 'Fill in the vendor payment details';
            btnAddOrder.style.display = 'block';

            document.getElementById('request-form').reset();
            
            // Clear validation states
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            updateSubmitButton(false);

            const rows = ordersContainer.querySelectorAll('.order-row');
            rows.forEach((row, i) => { if (i > 0) row.remove(); });
            ordersContainer.querySelectorAll('.form-pays-comm').forEach(chk => chk.checked = false);
            updateRemoveButtons();
        };

        modalEl.addEventListener('hidden.bs.modal', () => {
            resetModal();
            document.getElementById('draft-alert').classList.add('d-none');
        });

        modalEl.addEventListener('show.bs.modal', () => {
            if (!editingId) {
                const draft = localStorage.getItem('vp_request_draft');
                if (draft) {
                    document.getElementById('draft-alert').classList.remove('d-none');
                } else {
                    document.getElementById('draft-alert').classList.add('d-none');
                }
            } else {
                document.getElementById('draft-alert').classList.add('d-none');
            }
        });

        const filterFrom = document.getElementById('filter-from');
        const filterTo = document.getElementById('filter-to');
        const btnReset = document.getElementById('btn-reset-filters');
        const btnSubmit = document.getElementById('btn-submit');

        let validationTimeout;
        async function validateOrderIDs() {
            clearTimeout(validationTimeout);
            
            const currentInputs = Array.from(ordersContainer.querySelectorAll('.form-order-id'));
            const enteredValues = currentInputs.map(input => input.value.trim().toLowerCase());
            let isAnyLocalDuplicate = false;

            // 1. Immediate validation for duplicates WITHIN the modal
            currentInputs.forEach((input, index) => {
                const val = input.value.trim().toLowerCase();
                if (!val) {
                    input.classList.remove('is-invalid');
                    return;
                }

                const isDuplicateInModal = enteredValues.filter((v, i) => v === val && i !== index).length > 0;
                if (isDuplicateInModal) {
                    input.classList.add('is-invalid');
                    isAnyLocalDuplicate = true;
                    setInvalidFeedback(input, 'Duplicate Order ID in this request');
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            updateSubmitButton(isAnyLocalDuplicate);

            // 2. Debounced Global Validation
            validationTimeout = setTimeout(async () => {
                let isAnyGlobalDuplicate = false;

                for (const input of currentInputs) {
                    const val = input.value.trim().toLowerCase();
                    if (!val || input.classList.contains('is-invalid')) continue;

                    // First check current page cache (efficiency)
                    const isCachedDuplicate = allRequests.some(r => r.id != editingId && (r.order_id || '').toLowerCase() === val);

                    if (isCachedDuplicate) {
                        input.classList.add('is-invalid');
                        isAnyGlobalDuplicate = true;
                        setInvalidFeedback(input, 'Order ID already exists (Found on the Database)');
                        continue;
                    }

                    // Then check global system via API
                    try {
                        const res = await fetch(`${API_URL}?exact_order_id=${encodeURIComponent(val)}&limit=1`);
                        const data = await res.json();
                        const existsGlobally = data.isOk && data.data.some(r => r.id != editingId);

                        if (existsGlobally) {
                            input.classList.add('is-invalid');
                            isAnyGlobalDuplicate = true;
                            setInvalidFeedback(input, 'Order ID already exists in the system');
                        }
                    } catch (err) {
                        console.error('Global check failed', err);
                    }
                }

                if (isAnyGlobalDuplicate) updateSubmitButton(true);
            }, 400);
        }

        function setInvalidFeedback(input, msg) {
            let feedback = input.parentNode.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.style.fontSize = '10px';
                input.parentNode.appendChild(feedback);
            }
            feedback.textContent = msg;
        }

        function updateSubmitButton(hasError) {
            btnSubmit.disabled = hasError;
            if (hasError) {
                btnSubmit.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Resolve Duplicates';
            } else {
                btnSubmit.innerHTML = (editingId ? '<i class="fas fa-save me-1"></i> Save Changes' : '<i class="fas fa-paper-plane me-1"></i> Submit Request');
            }
        }

        ordersContainer.addEventListener('input', (e) => {
            if (e.target.classList.contains('form-order-id')) {
                validateOrderIDs();
            }
        });

        const shopNameInput = document.getElementById('form-shop-name');
        const notesInput = document.getElementById('form-notes');

        let fetchTimeout;
        const autoFetchNote = async () => {
            const name = shopNameInput.value.trim();
            if (!name || editingId) return; // Don't auto-fetch if editing an existing request

            clearTimeout(fetchTimeout);
            fetchTimeout = setTimeout(async () => {
                try {
                    const res = await fetch(`${API_URL}?fetch_account=${encodeURIComponent(name)}`, {
                        headers: { 'X-CSRF-TOKEN': csrfToken }
                    });
                    const data = await res.json();
                    if (data.isOk && data.notes && !notesInput.value.trim()) {
                        notesInput.value = data.notes;
                        showToast('Auto-fetched account details for ' + name, 'success');
                        
                        // Highlight the notes field briefly
                        notesInput.style.transition = 'background-color 0.5s';
                        notesInput.style.backgroundColor = '#ecfdf5';
                        setTimeout(() => notesInput.style.backgroundColor = '', 1500);
                    }
                } catch (err) {
                    console.error('Fetch account failed', err);
                }
            }, 500);
        };

        shopNameInput.addEventListener('input', autoFetchNote);

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

        function showResultModal(title, message, type = 'success') {
            const containerEl = document.getElementById('result-icon-container');
            const iconEl = document.getElementById('result-fa-icon');
            const titleEl = document.getElementById('resultModalLabel');
            const msgEl = document.getElementById('result-message');
            
            // Reset classes
            containerEl.className = 'result-icon-circle';
            iconEl.className = 'fas';

            if (type === 'success') {
                containerEl.classList.add('bg-res-success');
                iconEl.classList.add('fa-check-circle');
            } else if (type === 'warning') {
                containerEl.classList.add('bg-res-warning');
                iconEl.classList.add('fa-exclamation-circle');
            } else {
                containerEl.classList.add('bg-res-error');
                iconEl.classList.add('fa-times-circle');
            }
            
            titleEl.textContent = title;
            msgEl.textContent = message;
            
            const modalEl = document.getElementById('resultModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
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

            tbody.innerHTML = `<tr><td colspan="12">
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
            if (window.vpClearSelection) window.vpClearSelection();
            if (allRequests.length === 0) {
                tbody.innerHTML = `<tr><td colspan="12">
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
                actions += `<button class="vp-action-btn vp-btn-history ms-1" onclick="vpShowHistory(${r.id})" title="View History"><i class="fas fa-history fa-xs"></i></button>`;

                return `<tr class="vp-request-row">
                    <td class="text-center">
                        <input class="form-check-input row-checkbox" type="checkbox" value="${r.id}">
                    </td>
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
                    if (!editingId) {
                        localStorage.removeItem('vp_request_draft'); // Clear draft on successful submit
                    }
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
                    const type = data.updatedCount > 0 ? 'success' : (data.skippedCount > 0 ? 'warning' : 'success');
                    showToast(data.message, type);
                    if (data.skippedCount > 0) {
                        showResultModal('Update Result', data.message, type);
                    }
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
                    const type = data.updatedCount > 0 ? 'success' : (data.skippedCount > 0 ? 'warning' : 'success');
                    showToast(data.message || 'Request deleted.', type);
                    if (data.skippedCount > 0) {
                        showResultModal('Delete Result', data.message, type);
                    }
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

        // History
        window.vpShowHistory = async function (id) {
            const r = allRequests.find(req => req.id == id);
            const timeline = document.getElementById('history-timeline');
            const empty = document.getElementById('history-empty');
            const subtitle = document.getElementById('history-modal-subtitle');
            
            subtitle.textContent = `Tracking changes for order ${r ? r.order_id : '#' + id}`;
            timeline.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';
            empty.classList.add('d-none');
            
            const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
            historyModal.show();

            try {
                const res = await fetch(`${API_URL}?history_id=${id}`, {
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                const data = await res.json();
                
                if (data.isOk && data.data && data.data.length > 0) {
                    timeline.innerHTML = data.data.map(item => {
                        let dotClass = '';
                        let actionText = item.action;
                        let detailsHtml = '';

                        if (item.action === 'UPDATE_STATUS') {
                            dotClass = 'status-change';
                            const d = item.details_decoded || {};
                            actionText = `Status updated to <span class="badge bg-light text-dark border">${d.new_status || 'N/A'}</span>`;
                            if (d.old_status) {
                                detailsHtml = `<div class="vp-timeline-details">Changed from <b>${d.old_status}</b> to <b>${d.new_status}</b></div>`;
                            }
                        } else if (item.action === 'CREATE_BATCH' || item.action === 'CREATE') {
                            dotClass = 'create';
                            actionText = 'Request Created';
                        } else if (item.action === 'DELETE') {
                            dotClass = 'delete';
                            actionText = 'Request Deleted';
                        } else if (item.action === 'UPDATE_DATA') {
                            actionText = 'Data Updated';
                        }

                        const time = new Date(item.created_at).toLocaleString('en-GB', { 
                            day: '2-digit', month: 'short', year: 'numeric', 
                            hour: '2-digit', minute: '2-digit' 
                        });

                        return `
                        <div class="vp-timeline-item">
                            <div class="vp-timeline-dot ${dotClass}"></div>
                            <div class="vp-timeline-content">
                                <div class="vp-timeline-header">
                                    <span class="vp-timeline-actor">${escHtml(item.actor_name || 'System')}</span>
                                    <span class="vp-timeline-time">${time}</span>
                                </div>
                                <div class="vp-timeline-action">${actionText}</div>
                                ${detailsHtml}
                            </div>
                        </div>`;
                    }).join('');
                } else {
                    timeline.innerHTML = '';
                    empty.classList.remove('d-none');
                }
            } catch (err) {
                timeline.innerHTML = '<div class="text-danger small text-center">Failed to load history.</div>';
            }
        };

        // Initial fetch
        fetchRequests(1);

        // Fix Export dropdown clipping — force position:fixed so it escapes
        // any overflow:hidden / stacking context from parent containers
        const exportDropdownEl = document.getElementById('exportDropdown');
        if (exportDropdownEl) {
            exportDropdownEl.addEventListener('show.bs.dropdown', function () {
                const menu = this.nextElementSibling;
                const rect = this.getBoundingClientRect();
                menu.style.position   = 'fixed';
                menu.style.top        = (rect.bottom + 4) + 'px';
                menu.style.left       = 'auto';
                menu.style.right      = (window.innerWidth - rect.right) + 'px';
                menu.style.zIndex     = '99999';
                menu.style.margin     = '0';
            });
            exportDropdownEl.addEventListener('hide.bs.dropdown', function () {
                const menu = this.nextElementSibling;
                menu.style.position = '';
                menu.style.top      = '';
                menu.style.right    = '';
                menu.style.zIndex   = '';
                menu.style.margin   = '';
            });
        }

        // --- Bulk Operations Logic ---
        
        function updateBulkUI() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const count = checked.length;
            selectedCountLabel.textContent = count;
            
            if (count > 0) {
                bulkBar.classList.add('show');
            } else {
                bulkBar.classList.remove('show');
                if (selectAllCb) selectAllCb.checked = false;
            }
        }

        if (selectAllCb) {
            selectAllCb.addEventListener('change', () => {
                const isChecked = selectAllCb.checked;
                document.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = isChecked;
                });
                updateBulkUI();
            });
        }

        tbody.addEventListener('change', (e) => {
            if (e.target.classList.contains('row-checkbox')) {
                updateBulkUI();
                
                // Update selectAll state
                const total = document.querySelectorAll('.row-checkbox').length;
                const checked = document.querySelectorAll('.row-checkbox:checked').length;
                if (selectAllCb) selectAllCb.checked = (total > 0 && total === checked);
            }
        });

        window.vpClearSelection = function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
            if (selectAllCb) selectAllCb.checked = false;
            updateBulkUI();
        };

        window.vpBulkAction = async function(action) {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const ids = Array.from(checked).map(cb => cb.value);
            
            if (ids.length === 0) return;

            const labels = { approve: 'Approve', reject: 'Reject', mark_paid: 'Mark as Paid', delete: 'Delete' };
            let msg = `Are you sure you want to ${labels[action]} ${ids.length} selected request(s)?`;
            if (action === 'delete') msg += " This action cannot be undone.";
            
            if (!confirm(msg)) return;

            try {
                let payload = { action: action, ids: ids };
                if (action === 'delete') {
                    payload = { _method: 'DELETE', ids: ids };
                } else {
                    payload._method = 'PUT';
                }

                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await res.json();
                if (data.isOk) {
                    let type = 'success';
                    if (data.updatedCount === 0) type = 'error';
                    else if (data.skippedCount > 0) type = 'warning';
                    
                    showToast(data.message, type);
                    showResultModal('Bulk Operation Summary', data.message, type);
                    
                    vpClearSelection();
                    fetchRequests(currentPage);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Bulk operation failed', 'error');
            }
        };

        // Expose fetchRequests globally
        window.fetchRequests = fetchRequests;

        // Export CSV (filtered or all)
        window.vpExportCSV = function(filtered = false) {
            const url = new URL(API_URL, window.location.origin);
            url.searchParams.set('export', 'csv');

            if (filtered) {
                const status = currentFilter !== 'all' ? currentFilter : '';
                const search = searchInput.value.trim();
                const from   = filterFrom.value;
                const to     = filterTo.value;

                if (status)  url.searchParams.set('status',    status);
                if (search)  url.searchParams.set('search',    search);
                if (from)    url.searchParams.set('from_date', from);
                if (to)      url.searchParams.set('to_date',   to);
            }

            // Trigger download without navigating
            const a = document.createElement('a');
            a.href = url.toString();
            a.download = '';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            showToast(filtered ? 'Exporting filtered results...' : 'Exporting all records...', 'success');
        };

    })();
</script>

<?php require_once "includes/footer.php"; ?>
