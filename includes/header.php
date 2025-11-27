<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 10px 15px;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .card { transition: transform 0.2s; }
        .card:hover { transform: translateY(-2px); }
        .low-stock { border-left: 4px solid #dc3545; background-color: #fff5f5; }
        .out-of-stock { border-left: 4px solid #6c757d; background-color: #f8f9fa; }
        .navbar { box-shadow: 0 2px 4px rgba(0,0,0,.1); }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-boxes"></i> Inventory System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white">
                    <i class="fas fa-user"></i> Admin
                </span>
            </div>
        </div>
    </nav>

    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <nav class="nav flex-column p-3">
                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="px-3 text-muted small">PRODUCT MANAGEMENT</h6>
                    <a class="nav-link" href="../products/view_products.php">
                        <i class="fas fa-list"></i> View Products
                    </a>
                    <!-- Add this after PRODUCT MANAGEMENT section in the sidebar -->
            <div class="dropdown-divider"></div>
<h6 class="px-3 text-muted small">SUPPLIER MANAGEMENT</h6>
<a class="nav-link" href="../suppliers/view_suppliers.php">
    <i class="fas fa-truck"></i> Manage Suppliers
</a>
<a class="nav-link" href="../suppliers/add_supplier.php">
    <i class="fas fa-plus-circle"></i> Add Supplier
</a>
                    <a class="nav-link" href="../products/add_product.php">
                        <i class="fas fa-plus"></i> Add Product
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="px-3 text-muted small">STOCK MANAGEMENT</h6>
                    <a class="nav-link" href="../products/stock_in.php">
                        <i class="fas fa-download"></i> Stock In
                    </a>
                    <a class="nav-link" href="../products/stock_out.php">
                        <i class="fas fa-upload"></i> Stock Out
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="px-3 text-muted small">REPORTS</h6>
                    <a class="nav-link" href="../reports/stock_report.php">
                        <i class="fas fa-chart-bar"></i> Stock Report
                    </a>
                    <a class="nav-link" href="../reports/low_stock.php">
                        <i class="fas fa-exclamation-triangle"></i> Low Stock
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="p-4">