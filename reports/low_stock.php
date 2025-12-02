<?php
// reports/low_stock.php
session_start();
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

// Get low stock products
$query = "
    SELECT 
        p.*,
        s.name as supplier_name,
        s.contact_person,
        s.phone as supplier_phone
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.quantity <= p.min_stock
    ORDER BY p.quantity ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-exclamation-triangle text-warning"></i> Low Stock Report</h1>
    <button onclick="window.print()" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-print fa-sm text-white-50"></i> Print Report
    </button>
</div>

<?php if (count($products) > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle"></i> 
        <strong>Attention Needed:</strong> There are <?php echo count($products); ?> products below their minimum stock level.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="card dashboard-card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Low Stock Items</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Current Stock</th>
                            <th>Min Stock</th>
                            <th>Status</th>
                            <th>Supplier</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): 
                            $status_class = $product['quantity'] == 0 ? 'bg-danger' : 'bg-warning text-dark';
                            $status_text = $product['quantity'] == 0 ? 'Out of Stock' : 'Low Stock';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['sku']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>
                                <span class="h5 text-danger font-weight-bold">
                                    <?php echo $product['quantity']; ?>
                                </span>
                            </td>
                            <td><?php echo $product['min_stock']; ?></td>
                            <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td>
                                <?php if ($product['supplier_name']): ?>
                                    <?php echo htmlspecialchars($product['supplier_name']); ?>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($product['supplier_phone']); ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../products/update_stock.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-plus"></i> Restock
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="text-center py-5">
        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
        <h4>All Good!</h4>
        <p class="text-muted">No products are currently below their minimum stock levels.</p>
        <a href="../index.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
<?php endif; ?>

<?php require_once "../includes/footer.php"; ?>