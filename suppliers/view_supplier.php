<?php
// suppliers/view_supplier.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/Supplier.php";

$database = new Database();
$db = $database->getConnection();
$supplier = new Supplier($db);

// Get supplier ID
if (isset($_GET['id'])) {
    $supplier->id = $_GET['id'];
    if (!$supplier->readOne()) {
        $_SESSION['message'] = "Supplier not found!";
        $_SESSION['message_type'] = 'danger';
        header("Location: view_suppliers.php");
        exit();
    }
} else {
    header("Location: view_suppliers.php");
    exit();
}

// Get supplier products
$products_stmt = $supplier->getSupplierProducts();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-truck"></i> Supplier Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="edit_supplier.php?id=<?php echo $supplier->id; ?>" class="btn btn-warning me-2">
            <i class="fas fa-edit"></i> Edit
        </a>
        <a href="view_suppliers.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Contact Information</h5>
            </div>
            <div class="card-body">
                <h4 class="card-title"><?php echo htmlspecialchars($supplier->name); ?></h4>
                <hr>
                <p><strong><i class="fas fa-user"></i> Contact:</strong> <?php echo htmlspecialchars($supplier->contact_person); ?></p>
                <p><strong><i class="fas fa-envelope"></i> Email:</strong> 
                    <?php if ($supplier->email): ?>
                        <a href="mailto:<?php echo $supplier->email; ?>"><?php echo htmlspecialchars($supplier->email); ?></a>
                    <?php else: ?>
                        <span class="text-muted">N/A</span>
                    <?php endif; ?>
                </p>
                <p><strong><i class="fas fa-phone"></i> Phone:</strong> <?php echo htmlspecialchars($supplier->phone ?: 'N/A'); ?></p>
                <p><strong><i class="fas fa-globe"></i> Website:</strong> 
                    <?php if ($supplier->website): ?>
                        <a href="<?php echo $supplier->website; ?>" target="_blank"><?php echo htmlspecialchars($supplier->website); ?></a>
                    <?php else: ?>
                        <span class="text-muted">N/A</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Business Details</h5>
            </div>
            <div class="card-body">
                <p><strong>Payment Terms:</strong><br>
                <?php echo htmlspecialchars($supplier->payment_terms ?: 'Standard'); ?></p>
                
                <p><strong>Address:</strong><br>
                <?php echo nl2br(htmlspecialchars($supplier->address ?: 'No address provided')); ?></p>
                
                <p><strong>Notes:</strong><br>
                <?php echo nl2br(htmlspecialchars($supplier->notes ?: 'No notes')); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100 bg-light">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h1 class="display-4 text-primary"><?php echo count($products); ?></h1>
                <p class="lead">Products Supplied</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-box"></i> Products from this Supplier</h5>
    </div>
    <div class="card-body">
        <?php if ($products): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['sku']); ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <a href="../products/edit_product.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> View Product
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted text-center py-3">No products associated with this supplier yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
