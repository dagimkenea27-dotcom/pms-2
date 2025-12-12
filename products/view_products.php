<?php
// products/view_products.php
require_once "../config/auth_check.php";
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

// Handle product deletion
if (isset($_GET['delete_id'])) {
    // First get the product to delete its image
    $get_query = "SELECT image FROM products WHERE id = :id";
    $get_stmt = $db->prepare($get_query);
    $get_stmt->bindParam(":id", $_GET['delete_id']);
    $get_stmt->execute();
    $product = $get_stmt->fetch(PDO::FETCH_ASSOC);
    
    $delete_query = "DELETE FROM products WHERE id = :id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(":id", $_GET['delete_id']);
    
    if ($delete_stmt->execute()) {
        // Delete image file if it exists
        if (!empty($product['image']) && file_exists("../" . $product['image'])) {
            unlink("../" . $product['image']);
        }
        
        $_SESSION['message'] = "Product deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting product.";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: view_products.php");
    exit();
}

// Pagination and search variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$active_id = isset($_GET['active_id']) ? (int)$_GET['active_id'] : 0; // Added active_id parameter
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build query based on search and filters
$where_clause = "";
$params = [];

if (!empty($search)) {
    $where_clause .= "(name LIKE :search OR sku LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($category_filter)) {
    $and = !empty($where_clause) ? " AND " : "";
    $where_clause .= "{$and}category = :category";
    $params[':category'] = $category_filter;
}

$where_sql = !empty($where_clause) ? "WHERE $where_clause" : "";

// Get total products count for pagination
$count_query = "SELECT COUNT(*) as total FROM products $where_sql";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_products = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_products / $records_per_page);

// Get products with pagination and search
$query = "SELECT * FROM products $where_sql ORDER BY name ASC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);

// Bind search/filter parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(":limit", $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter dropdown
$category_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
$category_stmt = $db->prepare($category_query);
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock count
$low_stock_query = "SELECT COUNT(*) as count FROM products WHERE quantity <= min_stock AND quantity > 0";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute();
$low_stock_count = $low_stock_stmt->fetch(PDO::FETCH_ASSOC);

$out_of_stock_query = "SELECT COUNT(*) as count FROM products WHERE quantity = 0";
$out_of_stock_stmt = $db->prepare($out_of_stock_query);
$out_of_stock_stmt->execute();
$out_of_stock_count = $out_of_stock_stmt->fetch(PDO::FETCH_ASSOC);

require_once "../includes/header.php";

// Display session messages
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-'.$_SESSION['message_type'].' alert-dismissible fade show" role="alert">
            '.$_SESSION['message'].'
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-list"></i> Product List</h1>
    <div>
        <a href="export_products.php" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm me-2">
            <i class="fas fa-download fa-sm text-white-50"></i> Export CSV
        </a>
        <button type="button" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-upload fa-sm text-white-50"></i> Import CSV
        </button>
        <a href="add_product.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Product
        </a>
    </div>
</div>

<!-- Search and Filter Form -->
<div class="card dashboard-card shadow mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label">Search Products</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by name, SKU, or description..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <label for="category" class="form-label">Filter by Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="btn-group" role="group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search) || !empty($category_filter)): ?>
                        <a href="view_products.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card dashboard-card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Products</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_products; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-box text-primary fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Low Stock</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $low_stock_count['count']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Out of Stock</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $out_of_stock_count['count']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle text-danger fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions Toolbar -->
<div id="bulkActionsToolbar" class="alert alert-info mb-4" style="display: none;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-check-circle"></i>
            <strong id="selectedCount">0</strong> product(s) selected
        </div>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#bulkEditModal">
                <i class="fas fa-edit"></i> Bulk Edit
            </button>
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#bulkStockModal">
                <i class="fas fa-warehouse"></i> Update Stock
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="bulkPrintBarcodes()">
                <i class="fas fa-barcode"></i> Print Barcodes
            </button>
            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                <i class="fas fa-trash"></i> Delete
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                <i class="fas fa-times"></i> Clear Selection
            </button>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="card dashboard-card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Products Inventory</h6>
        <div class="small text-muted">
            Showing <?php echo min($offset + 1, $total_products); ?> 
            to <?php echo min($offset + $records_per_page, $total_products); ?> 
            of <?php echo $total_products; ?> products
        </div>
    </div>
    <div class="card-body">
        <?php if ($products): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="selectAll" class="form-check-input" title="Select All">
                            </th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Storage Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): 
                            $stock_class = '';
                            $stock_status = '';
                            // Check if this is the active product
                            $active_class = ($active_id == $product['id']) ? 'table-active' : '';
                            
                            if ($product['quantity'] == 0) {
                                $stock_class = 'table-danger';
                                $stock_status = '<span class="badge bg-danger">Out of Stock</span>';
                            } elseif ($product['quantity'] <= $product['min_stock']) {
                                $stock_class = 'table-warning';
                                $stock_status = '<span class="badge bg-warning text-dark">Low Stock</span>';
                            } else {
                                $stock_status = '<span class="badge bg-success">In Stock</span>';
                            }
                            
                            // Combine stock class with active class
                            $row_classes = trim($stock_class . ' ' . $active_class);
                        ?>
                        <tr class="<?php echo $row_classes; ?>" id="product-<?php echo $product['id']; ?>">
                            <td>
                                <input type="checkbox" class="form-check-input product-checkbox" 
                                       value="<?php echo $product['id']; ?>" 
                                       data-name="<?php echo htmlspecialchars($product['name']); ?>">
                            </td>
                            <td>
                                <a href="view_product.php?id=<?php echo $product['id']; ?>">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="img-thumbnail" style="max-height: 50px;">
                                    <?php else: ?>
                                        <div class="bg-light text-center" style="width: 50px; height: 50px; line-height: 50px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td>
                                <a href="view_product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                </a>
                                <?php if ($product['description']): ?>
                                    <br><small class="text-muted"><?php echo substr(htmlspecialchars($product['description']), 0, 50); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td>
                                <strong><?php echo $product['quantity']; ?></strong>
                                <?php if ($product['min_stock'] > 0): ?>
                                    <br><small class="text-muted">Min: <?php echo $product['min_stock']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['price']): ?>
                                    $<?php echo number_format($product['price'], 2); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['location']); ?></td>
                            <td><?php echo $stock_status; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="generate_barcode.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-secondary" title="View Barcode">
                                        <i class="fas fa-barcode"></i>
                                    </a>
                                    <a href="update_stock.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-success" title="Update Stock">
                                        <i class="fas fa-warehouse"></i>
                                    </a>
                                    <a href="?delete_id=<?php echo $product['id']; ?>&page=<?php echo $page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" 
                                       class="btn btn-outline-danger" 
                                       title="Delete"
                                       onclick="return confirmDelete('<?php echo addslashes($product['name']); ?>')">
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
            <nav aria-label="Products pagination">
                <ul class="pagination justify-content-center">
                    <!-- Previous Button -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" tabindex="-1">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    // Show first page and ellipsis if needed
                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($category_filter) ? '&category=' . urlencode($category_filter) : '') . '">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Page numbers
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($category_filter) ? '&category=' . urlencode($category_filter) : '') . '">' . $i . '</a></li>';
                    }
                    
                    // Show last page and ellipsis if needed
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($category_filter) ? '&category=' . urlencode($category_filter) : '') . '">' . $total_pages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Next Button -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h4>No products found</h4>
                <?php if (!empty($search) || !empty($category_filter)): ?>
                    <p class="text-muted">No products match your search criteria.</p>
                    <a href="view_products.php" class="btn btn-primary">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                <?php else: ?>
                    <p class="text-muted">Get started by adding your first product.</p>
                    <a href="add_product.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Your First Product
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="import_products.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Products from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Choose CSV File</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <strong>CSV Format:</strong><br>
                            Required columns: SKU, Name<br>
                            Optional: Description, Category, Quantity, Price, Cost Price, Min Stock, Supplier, Location<br>
                            <em>Existing SKUs will be updated.</em>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- BULK OPERATIONS MODALS AND JAVASCRIPT -->
<!-- This content should be inserted before the closing footer tag in view_products.php -->

<!-- Bulk Edit Modal -->
<div class="modal fade" id="bulkEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkEditForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Bulk Edit Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <small><strong>Note:</strong> Only filled fields will be updated. Leave fields empty to keep existing values.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" id="bulk_category">
                            <option value="">-- No Change --</option>
                            <?php
                            $cat_query = "SELECT id, name FROM categories ORDER BY name";
                            $cat_stmt = $db->prepare($cat_query);
                            $cat_stmt->execute();
                            while ($cat = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Brand</label>
                        <select class="form-select" name="brand_id" id="bulk_brand">
                            <option value="">-- No Change --</option>
                            <?php
                            $brand_query = "SELECT id, name FROM brands ORDER BY name";
                            $brand_stmt = $db->prepare($brand_query);
                            $brand_stmt->execute();
                            while ($brand = $brand_stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $brand['id'] . '">' . htmlspecialchars($brand['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-select" name="supplier_id" id="bulk_supplier">
                            <option value="">-- No Change --</option>
                            <?php
                            $supp_query = "SELECT id, name FROM suppliers ORDER BY name";
                            $supp_stmt = $db->prepare($supp_query);
                            $supp_stmt->execute();
                            while ($supp = $supp_stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $supp['id'] . '">' . htmlspecialchars($supp['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Storage Location</label>
                        <input type="text" class="form-control" name="location" id="bulk_location" placeholder="Leave empty for no change">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Price Adjustment</label>
                        <div class="input-group">
                            <select class="form-select" name="price_type" id="bulk_price_type" style="max-width: 150px;">
                                <option value="">No Change</option>
                                <option value="increase_percent">Increase by %</option>
                                <option value="decrease_percent">Decrease by %</option>
                                <option value="set_price">Set Price</option>
                            </select>
                            <input type="number" class="form-control" name="price_value" id="bulk_price_value" step="0.01" min="0" placeholder="Value">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Products
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Stock Update Modal -->
<div class="modal fade" id="bulkStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkStockForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-warehouse"></i> Bulk Stock Update</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Operation</label>
                        <select class="form-select" name="operation" id="stock_operation" required>
                            <option value="add">Add to Stock</option>
                            <option value="subtract">Subtract from Stock</option>
                            <option value="set">Set Stock Level</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="quantity" id="stock_quantity" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason/Notes</label>
                        <textarea class="form-control" name="notes" id="stock_notes" rows="2" placeholder="Optional notes for stock movement"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Delete Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Bulk Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to delete the selected products?</strong></p>
                <p>This action cannot be undone. The following products will be deleted:</p>
                <ul id="deleteProductList" class="mb-0"></ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="executeBulkDelete()">
                    <i class="fas fa-trash"></i> Delete Products
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Bulk Operations JavaScript
let selectedProducts = [];

// Create a function to initialize bulk operations
function initializeBulkOperations() {
    // Select All functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function(e) {
            e.stopPropagation(); // Prevent event bubbling
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            setTimeout(function() {
                updateSelectedProducts();
            }, 10);
        });
    }

    // Individual checkbox change - FIXED EVENT HANDLING
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('product-checkbox')) {
            e.stopPropagation(); // Prevent event bubbling
            setTimeout(function() {
                updateSelectedProducts();
            }, 10); // Small delay to ensure checkbox state is updated
        }
    });

    // Bulk Edit Form Submission
    const bulkEditForm = document.getElementById('bulkEditForm');
    if (bulkEditForm) {
        bulkEditForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'bulk_edit');
            formData.append('product_ids', JSON.stringify(getSelectedIds()));
            
            fetch('bulk_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred: ' + error);
            });
        });
    }

    // Bulk Stock Update Form Submission
    const bulkStockForm = document.getElementById('bulkStockForm');
    if (bulkStockForm) {
        bulkStockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'bulk_stock');
            formData.append('product_ids', JSON.stringify(getSelectedIds()));
            
            fetch('bulk_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred: ' + error);
            });
        });
    }

    // Bulk Delete - Show product list in modal
    const bulkDeleteModal = document.getElementById('bulkDeleteModal');
    if (bulkDeleteModal) {
        bulkDeleteModal.addEventListener('show.bs.modal', function() {
            const list = document.getElementById('deleteProductList');
            list.innerHTML = '';
            selectedProducts.forEach(product => {
                const li = document.createElement('li');
                li.textContent = product.name;
                list.appendChild(li);
            });
        });
    }
    
    // Initialize toolbar visibility on page load
    updateSelectedProducts();
}

// Wait for DOM to be fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeBulkOperations);
} else {
    // DOM is already loaded
    initializeBulkOperations();
}

// Helper functions (outside DOMContentLoaded for global access)
function updateSelectedProducts() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    selectedProducts = Array.from(checkboxes).map(cb => ({
        id: cb.value,
        name: cb.dataset.name
    }));
    
    const count = selectedProducts.length;
    const selectedCountEl = document.getElementById('selectedCount');
    const bulkToolbarEl = document.getElementById('bulkActionsToolbar');
    
    if (selectedCountEl) selectedCountEl.textContent = count;
    if (bulkToolbarEl) bulkToolbarEl.style.display = count > 0 ? 'block' : 'none';
    
    // Update select all checkbox
    const allCheckboxes = document.querySelectorAll('.product-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allCheckboxes.length > 0 && count === allCheckboxes.length;
        selectAllCheckbox.indeterminate = count > 0 && count < allCheckboxes.length;
    }
}

function clearSelection() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = false);
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    updateSelectedProducts();
}

function getSelectedIds() {
    return selectedProducts.map(p => p.id);
}

function executeBulkDelete() {
    const formData = new FormData();
    formData.append('action', 'bulk_delete');
    formData.append('product_ids', JSON.stringify(getSelectedIds()));
    
    fetch('bulk_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('An error occurred: ' + error);
    });
}

function bulkPrintBarcodes() {
    if (selectedProducts.length === 0) {
        alert('Please select at least one product');
        return;
    }
    
    // Open bulk barcode page in new window
    const ids = getSelectedIds().join(',');
    window.open('bulk_barcode.php?ids=' + ids, '_blank');
}



function confirmDelete(productName) {
    return confirm(`Are you sure you want to delete the product "${productName}"? This action cannot be undone.`);
}

// Scroll to active product row if exists
<?php if ($active_id > 0): ?>
window.addEventListener('DOMContentLoaded', function() {
    var activeRow = document.getElementById('product-<?php echo $active_id; ?>');
    if (activeRow) {
        // Scroll to the element with smooth behavior
        activeRow.scrollIntoView({behavior: "smooth", block: "center"});
        
        // Add a temporary highlight effect
        activeRow.classList.add('highlight');
        setTimeout(function() {
            activeRow.classList.remove('highlight');
        }, 3000);
    }
});
<?php endif; ?>
</script>

<style>
/* Add highlight animation */
@keyframes highlightAnimation {
    0% { background-color: #fff3cd; }
    50% { background-color: #fff3cd; }
    100% { background-color: transparent; }
}

.highlight {
    animation: highlightAnimation 3s ease-out;
}
</style>

<?php require_once "../includes/footer.php"; ?>