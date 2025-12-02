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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-exclamation-triangle text-warning"></i> Low Stock Report</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<?php if (count($products) > 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i> 
        <strong>Attention Needed:</strong> There are <?php echo count($products); ?> products below their minimum stock level.
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
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
