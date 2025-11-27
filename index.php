<?php
// index.php (updated)
session_start();
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

// Get low stock alert count
$low_stock_query = "SELECT COUNT(*) as count FROM products WHERE quantity <= min_stock AND quantity > 0";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute();
$low_stock = $low_stock_stmt->fetch(PDO::FETCH_ASSOC);

// Get out of stock count
$out_of_stock_query = "SELECT COUNT(*) as count FROM products WHERE quantity = 0";
$out_of_stock_stmt = $db->prepare($out_of_stock_query);
$out_of_stock_stmt->execute();
$out_of_stock = $out_of_stock_stmt->fetch(PDO::FETCH_ASSOC);

// Get total products
$total_products_query = "SELECT COUNT(*) as count FROM products";
$total_products_stmt = $db->prepare($total_products_query);
$total_products_stmt->execute();
$total_products = $total_products_stmt->fetch(PDO::FETCH_ASSOC);

// Get total inventory value
$total_value_query = "SELECT SUM(quantity * cost_price) as total_value FROM products WHERE cost_price IS NOT NULL";
$total_value_stmt = $db->prepare($total_value_query);
$total_value_stmt->execute();
$total_value = $total_value_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent stock movements
$recent_movements_query = "
    SELECT sm.*, p.name as product_name, p.sku 
    FROM stock_movements sm 
    LEFT JOIN products p ON sm.product_id = p.id 
    ORDER BY sm.created_at DESC 
    LIMIT 10";
$recent_movements_stmt = $db->prepare($recent_movements_query);
$recent_movements_stmt->execute();
$recent_movements = $recent_movements_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<!-- Dashboard Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $total_products['count']; ?></h4>
                        <p>Total Products</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-box fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $low_stock['count']; ?></h4>
                        <p>Low Stock Alerts</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $out_of_stock['count']; ?></h4>
                        <p>Out of Stock</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>$<?php echo number_format($total_value['total_value'] ?? 0, 2); ?></h4>
                        <p>Total Inventory Value</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <a href="products/add_product.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
                <a href="products/view_products.php" class="btn btn-success">
                    <i class="fas fa-list"></i> View All Products
                </a>
                <a href="#" class="btn btn-warning">
                    <i class="fas fa-download"></i> Stock In
                </a>
                <a href="#" class="btn btn-danger">
                    <i class="fas fa-upload"></i> Stock Out
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history"></i> Recent Stock Movements
                </h5>
            </div>
            <div class="card-body">
                <?php if ($recent_movements): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_movements as $movement): ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i A', strtotime($movement['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($movement['sku']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $movement['movement_type'] == 'IN' ? 'success' : 'danger'; ?>">
                                            <?php echo $movement['movement_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $movement['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($movement['reason']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No recent stock movements.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>