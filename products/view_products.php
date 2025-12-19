<?php
// products/view_products.php
require_once "../config/auth_check.php";
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

// Handle product deletion
// Handle product deletion
if (isset($_GET['delete_id'])) {
    Auth::requireRole('manager'); // Only manager/admin can delete
    // First get the product to delete its image
    $get_query = "SELECT image FROM products WHERE id = :id";
    $get_stmt = $db->prepare($get_query);
    $get_stmt->bindParam(":id", $_GET['delete_id']);
    $get_stmt->execute();
    $product = $get_stmt->fetch(PDO::FETCH_ASSOC);
    
    $delete_query = "DELETE FROM products WHERE id = :id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(":id", $_GET['delete_id']);
    
    // Explicitly delete variants first to ensure cleanup
    $delete_variants = "DELETE FROM product_variants WHERE product_id = :id";
    $dv_stmt = $db->prepare($delete_variants);
    $dv_stmt->bindParam(":id", $_GET['delete_id']);
    $dv_stmt->execute();

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
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : ''; // Added filter parameter
$active_id = isset($_GET['active_id']) ? (int)$_GET['active_id'] : 0;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Build query based on search and filters
$where_clause = "";
$params = [];

if (!empty($search)) {
    // Check if the search term looks like a barcode/SKU (numeric or alphanumeric without spaces)
    $is_exact_match = preg_match('/^[a-zA-Z0-9]+$/', $search) && strlen($search) > 3;
    
    if ($is_exact_match) {
        // For exact barcode/SKU matches, prioritize exact matches first
        $where_clause .= "(sku = :exact_search OR barcode = :exact_search OR id = :exact_id OR name LIKE :search OR description LIKE :search OR id IN (SELECT product_id FROM product_variants WHERE sku = :exact_search OR id = :exact_variant_id))";
        $params[':exact_search'] = $search;
        $params[':exact_id'] = $search;
        $params[':exact_variant_id'] = $search;
        $params[':search'] = "%$search%";
    } else {
        // For general searches, use LIKE with wildcards
        $where_clause .= "(name LIKE :search OR sku LIKE :search OR barcode LIKE :search OR description LIKE :search OR id IN (SELECT product_id FROM product_variants WHERE sku LIKE :search))";
        $params[':search'] = "%$search%";
    }
}

if (!empty($category_filter)) {
    $and = !empty($where_clause) ? " AND " : "";
    $where_clause .= "{$and}category = :category";
    $params[':category'] = $category_filter;
}

if (!empty($filter_type)) {
    $and = !empty($where_clause) ? " AND " : "";
    if ($filter_type == 'low_stock') {
        $where_clause .= "{$and}quantity <= min_stock AND quantity > 0";
    } elseif ($filter_type == 'out_of_stock') {
        $where_clause .= "{$and}quantity = 0";
    }
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

// Get absolute total for dashboard card
$total_all_stmt = $db->query("SELECT COUNT(*) as total FROM products");
$total_products_count_all = $total_all_stmt->fetch(PDO::FETCH_ASSOC)['total'];

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

// Get out of stock count
$out_of_stock_query = "SELECT COUNT(*) as count FROM products WHERE quantity = 0";
$out_of_stock_stmt = $db->prepare($out_of_stock_query);
$out_of_stock_stmt->execute();
$out_of_stock_count = $out_of_stock_stmt->fetch(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>


<?php
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
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-list"></i> <?php echo __('all_products'); ?></h1>
    <div>
        <?php if (Auth::hasRole('manager')): ?>
        <a href="export_products.php" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm me-2">
            <i class="fas fa-download fa-sm text-white-50"></i> <?php echo __('export_products'); ?>
        </a>
        <button type="button" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-upload fa-sm text-white-50"></i> <?php echo __('import_products'); ?>
        </button>
        <a href="add_product.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> <?php echo __('add_product'); ?>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Search and Filter Form -->
<div class="card dashboard-card shadow mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label"><?php echo __('search_products'); ?></label>
                <div class="input-group">
                    <input type="text" class="form-control barcode-input" id="search" name="search" 
                           placeholder="<?php echo __('search_products'); ?>" 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary start-barcode-scanner" type="button" id="barcode-scan-btn" title="Scan Barcode (Ctrl+B)">
                        <i class="fas fa-barcode"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <label for="category" class="form-label"><?php echo __('filter_category'); ?></label>
                <select class="form-select" id="category" name="category">
                    <option value=""><?php echo __('all_categories'); ?></option>
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
                        <i class="fas fa-search"></i> <?php echo __('search'); ?>
                    </button>
                    <?php if (!empty($search) || !empty($category_filter)): ?>
                        <a href="view_products.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> <?php echo __('clear'); ?>
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
        <a href="view_products.php" class="text-decoration-none">
            <div class="card dashboard-card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                <?php echo __('total_products'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_products_count_all; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4">
        <a href="view_products.php?filter=low_stock" class="text-decoration-none">
            <div class="card dashboard-card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                <?php echo __('low_stock_alerts'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $low_stock_count['count']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4">
        <a href="view_products.php?filter=out_of_stock" class="text-decoration-none">
            <div class="card dashboard-card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                <?php echo __('out_of_stock'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $out_of_stock_count['count']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Products Table -->
<!-- Bulk Actions Toolbar (Initially Hidden) -->
<div id="bulkActionsToolbar" class="alert alert-secondary mb-4 sticky-top shadow-sm" style="display: none; z-index: 1000; top: 80px;">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <span class="fw-bold me-2"><span id="selectedCount">0</span> Selected</span>
            <button type="button" class="btn btn-sm btn-outline-danger me-2" id="bulkDeleteBtn">
                <i class="fas fa-trash"></i> Delete
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary me-2" id="bulkEditBtn" data-bs-toggle="modal" data-bs-target="#bulkEditModal">
                <i class="fas fa-edit"></i> Edit
            </button>
            <button type="button" class="btn btn-sm btn-outline-success me-2" id="bulkStockBtn" data-bs-toggle="modal" data-bs-target="#bulkStockModal">
                <i class="fas fa-boxes"></i> Update Stock
            </button>
            <button type="button" class="btn btn-sm btn-outline-dark" id="bulkBarcodeBtn">
                <i class="fas fa-barcode"></i> Generate Barcodes
            </button>
        </div>
        <button type="button" class="btn-close" id="closeBulkToolbar"></button>
    </div>
</div>

<div class="card dashboard-card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><?php echo __('products'); ?></h6>
        <div class="dropdown no-arrow">
            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download fa-sm text-white-50"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow animated--fade-in" aria-labelledby="exportDropdown">
                <li><a class="dropdown-item" href="export_products.php?format=csv">CSV</a></li>
                <li><a class="dropdown-item" href="export_products.php?format=excel">Excel</a></li>
                <li><a class="dropdown-item" href="export_products.php?format=pdf">PDF</a></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                            </div>
                        </th>
                        <th>#</th>
                        <th><?php echo __('image'); ?></th>
                        <th><?php echo __('product_name'); ?></th>
                        <th><?php echo __('category'); ?></th>
                        <th><?php echo __('location'); ?></th>
                        <th><?php echo __('stock'); ?></th>
                        <th><?php echo __('price'); ?></th>
                        <th><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $index => $product): ?>
                            <tr <?php echo ($active_id == $product['id']) ? 'class="table-active"' : ''; ?> id="product-row-<?php echo $product['id']; ?>">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input product-select" type="checkbox" value="<?php echo $product['id']; ?>">
                                    </div>
                                </td>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td>
                                    <a href="view_product.php?id=<?php echo $product['id']; ?>">
                                        <?php if (!empty($product['image']) && file_exists("../" . $product['image'])): ?>
                                            <img src="../<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image-small">
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-image fa-2x"></i></span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="view_product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none font-weight-bold text-dark">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo htmlspecialchars($product['location'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($product['quantity'] <= $product['min_stock'] && $product['quantity'] > 0): ?>
                                        <span class="badge bg-warning"><?php echo $product['quantity']; ?></span>
                                    <?php elseif ($product['quantity'] == 0): ?>
                                        <span class="badge bg-danger"><?php echo __('out_of_stock'); ?></span>
                                    <?php else: ?>
                                        <?php echo $product['quantity']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view_product.php?id=<?php echo $product['id']; ?>" class="btn btn-info btn-sm" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (Auth::hasRole('manager')): ?>
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete_id=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirmDelete('<?php echo addslashes($product['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center"><?php echo __('no_products_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="import_products.php" method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Select CSV File</label>
                        <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        <div class="form-text">Download <a href="sample_products.csv">sample CSV template</a></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form for barcode search -->
<form id="barcode-search-form" method="GET" style="display: none;">
    <input type="hidden" name="search" id="barcode-search-input">
</form>


<!-- Bulk Edit Modal -->
<div class="modal fade" id="bulkEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Edit Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkEditForm">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <option value="">No Change</option>
                             <!-- Dynamic categories -->
                             <?php
                                $cat_ids = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                                foreach($cat_ids as $c) {
                                    echo '<option value="'.$c['id'].'">'.htmlspecialchars($c['name']).'</option>';
                                }
                             ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" placeholder="No Change">
                    </div>
                    <!-- Add more fields as needed -->
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBulkEdit">Apply Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Stock Modal -->
<div class="modal fade" id="bulkStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Stock Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkStockForm">
                    <div class="mb-3">
                        <label class="form-label">Operation</label>
                        <select class="form-select" name="operation">
                            <option value="add">Add to Stock</option>
                            <option value="subtract">Subtract from Stock</option>
                            <option value="set">Set Quantity</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="quantity" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason/Notes</label>
                        <input type="text" class="form-control" name="notes" placeholder="Bulk update reason">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBulkStock">Update Stock</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const productCheckboxes = document.querySelectorAll('.product-select');
        const bulkToolbar = document.getElementById('bulkActionsToolbar');
        const selectedCountSpan = document.getElementById('selectedCount');
        const toolbarClose = document.getElementById('closeBulkToolbar');

        function updateToolbar() {
            const selected = document.querySelectorAll('.product-select:checked');
            const count = selected.length;
            selectedCountSpan.textContent = count;
            
            if (count > 0) {
                bulkToolbar.style.display = 'block';
            } else {
                bulkToolbar.style.display = 'none';
            }
        }

        selectAll.addEventListener('change', function() {
            productCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            updateToolbar();
        });

        productCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateToolbar();
                selectAll.checked = document.querySelectorAll('.product-select:checked').length === productCheckboxes.length;
            });
        });

        toolbarClose.addEventListener('click', function() {
            selectAll.checked = false;
            productCheckboxes.forEach(cb => cb.checked = false);
            updateToolbar();
        });

        // Bulk Actions Logic
        function getSelectedIds() {
            return Array.from(document.querySelectorAll('.product-select:checked')).map(cb => cb.value);
        }

        // Delete
        document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
            const ids = getSelectedIds();
            if (confirm(`Are you sure you want to delete ${ids.length} products?`)) {
                postBulkAction('bulk_delete', { product_ids: JSON.stringify(ids) });
            }
        });

        // Edit Confirm
        document.getElementById('confirmBulkEdit').addEventListener('click', function() {
            const ids = getSelectedIds();
            const formData = new FormData(document.getElementById('bulkEditForm'));
            formData.append('action', 'bulk_edit');
            formData.append('product_ids', JSON.stringify(ids));
            
            // Convert FormData to object for JSON body if needed, or just send FormData
            // Since our backend expects POST, let's use a helper that handles it
            submitBulkForm(formData);
        });

        // Stock Confirm
        document.getElementById('confirmBulkStock').addEventListener('click', function() {
            const ids = getSelectedIds();
            const formData = new FormData(document.getElementById('bulkStockForm'));
            formData.append('action', 'bulk_stock');
            formData.append('product_ids', JSON.stringify(ids));
            submitBulkForm(formData);
        });

        // Barcode Generation
        document.getElementById('bulkBarcodeBtn').addEventListener('click', function() {
            const ids = getSelectedIds();
            window.location.href = `bulk_barcode.php?ids=${ids.join(',')}`;
        });

        function postBulkAction(action, data) {
            const formData = new FormData();
            formData.append('action', action);
            for (const key in data) {
                formData.append(key, data[key]);
            }
            submitBulkForm(formData);
        }

        function submitBulkForm(formData) {
            fetch('bulk_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            })
            .catch(e => {
                console.error(e);
                alert('An error occurred');
            });
        }
    });
</script>

<script>
    // Highlight active row if active_id is set
    <?php if ($active_id > 0): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const activeRow = document.getElementById('product-row-<?php echo $active_id; ?>');
        if (activeRow) {
            // Scroll to the highlighted row
            activeRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Add animation effect
            activeRow.classList.add('highlight-animation');
            setTimeout(() => {
                activeRow.classList.remove('highlight-animation');
            }, 3000);
        }
    });
    <?php endif; ?>
</script>

<style>
.highlight-animation {
    animation: highlight 2s ease-in-out;
}

@keyframes highlight {
    0% { background-color: yellow; }
    100% { background-color: transparent; }
}

.product-image-small {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}
</style>

<?php require_once "../includes/footer.php"; ?>