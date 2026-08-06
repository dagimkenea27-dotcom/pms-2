<?php
// suppliers/view_suppliers.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/Supplier.php";

$database = new Database();
$db = $database->getConnection();
$supplier = new Supplier($db);

// Pagination and search variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build query based on search
$where_clause = "";
$params = [];

if (!empty($search)) {
    $where_clause = "(name LIKE :search OR contact_person LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = !empty($where_clause) ? "WHERE $where_clause" : "";

// Get total suppliers count for pagination
$count_query = "SELECT COUNT(*) as total FROM suppliers $where_sql";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_suppliers = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_suppliers / $records_per_page);

// Get suppliers with pagination and search
// Get suppliers with pagination and search, including product count
$query = "SELECT s.*, COUNT(p.id) as product_count 
          FROM suppliers s 
          LEFT JOIN products p ON s.id = p.supplier_id 
          $where_sql 
          GROUP BY s.id 
          ORDER BY s.name ASC 
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);

// Bind search parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(":limit", $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle deletion
if (isset($_GET['delete_id'])) {
    $supplier->id = $_GET['delete_id'];
    if ($supplier->delete()) {
        $_SESSION['message'] = "Supplier deleted successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error deleting supplier.";
        $_SESSION['message_type'] = 'danger';
    }
    // Redirect with search parameters
    $redirect_url = "view_suppliers.php?page=$page";
    if (!empty($search)) {
        $redirect_url .= "&search=" . urlencode($search);
    }
    header("Location: $redirect_url");
    exit();
}

require_once "../includes/header.php";
?>

<style>
    /* Mobile & Laptop Optimizations */
    @media (min-width: 768px) and (max-width: 1400px) {
        #suppliersTable>thead>tr>th,
        #suppliersTable>tbody>tr>td {
            padding: 10px 12px;
            font-size: 13px;
        }
    }

    /* Custom Scrollbar */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
        border: 2px solid #f1f5f9;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    @media (max-width: 767.98px) {
        .card-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 5px;
        }

        #suppliersTable thead {
            display: none;
        }
        #suppliersTable, #suppliersTable tbody, #suppliersTable tr, #suppliersTable td {
            display: block;
            width: 100%;
        }
        #suppliersTable tr {
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
        }
        #suppliersTable td {
            text-align: right;
            padding: 8px 10px;
            position: relative;
            border-bottom: 1px solid #f1f5f9;
            min-height: 40px;
        }
        #suppliersTable td:last-child {
            border-bottom: none;
            text-align: center;
            background: #f8fafc;
            margin-top: 10px;
            border-radius: 0 0 8px 8px;
        }
        #suppliersTable td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            top: 50%;
            transform: translateY(-50%);
        }
        #suppliersTable .btn-group {
            width: 100%;
        }
        #suppliersTable .btn-group .btn {
            flex-grow: 1;
        }
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-truck"></i> Supplier Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_supplier.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Supplier
        </a>
    </div>
</div>

<!-- Search Form -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <label for="search" class="form-label">Search Suppliers</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by name, contact person, email, or phone..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="btn-group" role="group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="view_suppliers.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php 
unset($_SESSION['message']);
unset($_SESSION['message_type']);
endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Suppliers List</h6>
        <div class="small text-muted">
            Showing <?php echo min($offset + 1, $total_suppliers); ?> 
            to <?php echo min($offset + $records_per_page, $total_suppliers); ?> 
            of <?php echo $total_suppliers; ?> suppliers
        </div>
    </div>
    <div class="card-body">
        <?php if ($suppliers): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="suppliersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): 
                            // Use pre-calculated count from main query
                            $product_count = $supplier['product_count'];
                        ?>
                        <tr>
                            <td data-label="Name">
                                <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                <?php if ($supplier['website']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($supplier['website']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td data-label="Contact"><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                            <td data-label="Email">
                                <?php if ($supplier['email']): ?>
                                    <a href="mailto:<?php echo $supplier['email']; ?>">
                                        <?php echo htmlspecialchars($supplier['email']); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td data-label="Phone"><?php echo htmlspecialchars($supplier['phone']); ?></td>
                            <td data-label="Products">
                                <span class="badge bg-info"><?php echo $product_count; ?> products</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_supplier.php?id=<?php echo $supplier['id']; ?>" 
                                       class="btn btn-outline-primary" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_supplier.php?id=<?php echo $supplier['id']; ?>" 
                                       class="btn btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete_id=<?php echo $supplier['id']; ?>&page=<?php echo $page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('Delete <?php echo addslashes($supplier['name']); ?>?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Suppliers pagination">
                <ul class="pagination justify-content-center">
                    <!-- Previous Button -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" tabindex="-1">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    // Show first page and ellipsis if needed
                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . '">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Page numbers
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $i . '</a></li>';
                    }
                    
                    // Show last page and ellipsis if needed
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $total_pages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Next Button -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                <h4>No suppliers found</h4>
                <?php if (!empty($search)): ?>
                    <p class="text-muted">No suppliers match your search criteria.</p>
                    <a href="view_suppliers.php" class="btn btn-primary">
                        <i class="fas fa-times"></i> Clear Search
                    </a>
                <?php else: ?>
                    <p class="text-muted">Get started by adding your first supplier.</p>
                    <a href="add_supplier.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Your First Supplier
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>