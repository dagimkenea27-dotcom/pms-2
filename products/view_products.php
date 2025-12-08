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
                            <th>Image</th>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): 
                            $stock_class = '';
                            $stock_status = '';
                            if ($product['quantity'] == 0) {
                                $stock_class = 'table-danger';
                                $stock_status = '<span class="badge bg-danger">Out of Stock</span>';
                            } elseif ($product['quantity'] <= $product['min_stock']) {
                                $stock_class = 'table-warning';
                                $stock_status = '<span class="badge bg-warning text-dark">Low Stock</span>';
                            } else {
                                $stock_status = '<span class="badge bg-success">In Stock</span>';
                            }
                        ?>
                        <tr class="<?php echo $stock_class; ?>">
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="img-thumbnail" style="max-height: 50px;">
                                <?php else: ?>
                                    <div class="bg-light text-center" style="width: 50px; height: 50px; line-height: 50px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($product['sku']); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
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

<script>
function confirmDelete(productName) {
    return confirm(`Are you sure you want to delete the product "${productName}"? This action cannot be undone.`);
}
</script>

<?php require_once "../includes/footer.php"; ?>