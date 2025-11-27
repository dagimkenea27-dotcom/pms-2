<?php
// products/view_products.php
session_start();
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

// Handle product deletion
if (isset($_GET['delete_id'])) {
    $delete_query = "DELETE FROM products WHERE id = :id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(":id", $_GET['delete_id']);
    
    if ($delete_stmt->execute()) {
        $_SESSION['message'] = "Product deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting product.";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: view_products.php");
    exit();
}

// Get all products
$query = "SELECT * FROM products ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-list"></i> Product List</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_product.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo count($products); ?></h4>
                        <small class="text-muted">Total Products</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-box text-primary fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $low_stock_count['count']; ?></h4>
                        <small class="text-muted">Low Stock</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $out_of_stock_count['count']; ?></h4>
                        <small class="text-muted">Out of Stock</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle text-danger fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body">
        <?php if ($products): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
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
                                $stock_class = 'out-of-stock';
                                $stock_status = '<span class="badge bg-secondary">Out of Stock</span>';
                            } elseif ($product['quantity'] <= $product['min_stock']) {
                                $stock_class = 'low-stock';
                                $stock_status = '<span class="badge bg-warning">Low Stock</span>';
                            } else {
                                $stock_status = '<span class="badge bg-success">In Stock</span>';
                            }
                        ?>
                        <tr class="<?php echo $stock_class; ?>">
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
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="update_stock.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-success">
                                        <i class="fas fa-warehouse"></i>
                                    </a>
                                    <a href="?delete_id=<?php echo $product['id']; ?>" 
                                       class="btn btn-outline-danger" 
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
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                <h4>No products found</h4>
                <p class="text-muted">Get started by adding your first product.</p>
                <a href="add_product.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Your First Product
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>