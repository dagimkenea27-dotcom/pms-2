<?php
// products/view_product.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";

// Ensure user is logged in
Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get product data
$product = null;
$variants = [];
if (isset($_GET['id'])) {
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get variants if exist
    if ($product && $product['has_variants']) {
        $v_stmt = $db->prepare("SELECT * FROM product_variants WHERE product_id = :pid");
        $v_stmt->execute([':pid' => $product['id']]);
        $variants = $v_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!$product) {
    $_SESSION['message'] = "Product not found!";
    $_SESSION['message_type'] = 'danger';
    header("Location: view_products.php");
    exit();
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-eye"></i> View Product Details</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Product Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">SKU:</dt>
                            <dd class="col-sm-8"><?php echo e($product['sku']); ?></dd>
                            
                            <dt class="col-sm-4">Name:</dt>
                            <dd class="col-sm-8"><?php echo e($product['name']); ?></dd>
                            
                            <dt class="col-sm-4">Category:</dt>
                            <dd class="col-sm-8"><?php echo e($product['category']); ?></dd>
                            
                            <dt class="col-sm-4">Brand:</dt>
                            <dd class="col-sm-8">
                                <?php
if ($product['brand_id']) {
    $brand_stmt = $db->prepare("SELECT name FROM brands WHERE id = ?");
    $brand_stmt->execute([$product['brand_id']]);
    $brand = $brand_stmt->fetch(PDO::FETCH_ASSOC);
    echo $brand ? e($brand['name']) : 'N/A';
}
else {
    echo 'N/A';
}
?>
                            </dd>
                            
                            <dt class="col-sm-4">Supplier:</dt>
                            <dd class="col-sm-8">
                                <?php
if ($product['supplier_id']) {
    $supplier_stmt = $db->prepare("SELECT name FROM suppliers WHERE id = ?");
    $supplier_stmt->execute([$product['supplier_id']]);
    $supplier = $supplier_stmt->fetch(PDO::FETCH_ASSOC);
    echo $supplier ? e($supplier['name']) : 'N/A';
}
else {
    echo 'N/A';
}
?>
                            </dd>
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Quantity:</dt>
                            <dd class="col-sm-8"><?php echo $product['quantity']; ?></dd>
                            
                            <dt class="col-sm-4">Min Stock:</dt>
                            <dd class="col-sm-8"><?php echo $product['min_stock']; ?></dd>
                            
                            <dt class="col-sm-4">Cost Price:</dt>
                            <dd class="col-sm-8">$<?php echo number_format($product['cost_price'], 2); ?></dd>
                            
                            <dt class="col-sm-4">Selling Price:</dt>
                            <dd class="col-sm-8">$<?php echo number_format($product['price'], 2); ?></dd>
                            
                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
                                <?php
$status = $product['status'] ?? 'Active';
$badge_class = ($status == 'Active') ? 'bg-success' : 'bg-secondary';
echo "<span class='badge $badge_class'>" . e($status) . "</span>";
?>
                            </dd>

                            <dt class="col-sm-4">Location:</dt>
                            <dd class="col-sm-8"><?php echo e($product['location']); ?></dd>
                        </dl>
                    </div>
                </div>
                
                <?php if ($product['description']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Description:</h6>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                </div>
                <?php
endif; ?>
                
                <?php if ($product['has_variants'] && !empty($variants)): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Variants:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Size/Option</th>
                                        <th>Color</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($variants as $variant): ?>
                                    <tr>
                                        <td><?php echo e($variant['sku']); ?></td>
                                        <td><?php echo e($variant['size']); ?></td>
                                        <td><?php echo e($variant['color']); ?></td>
                                        <td><?php echo number_format($variant['quantity']); ?></td>
                                        <td><?php echo $variant['price'] ? '$' . number_format($variant['price'], 2) : 'Same as main'; ?></td>
                                    </tr>
                                    <?php
    endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php
endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Product Image</h5>
            </div>
            <div class="card-body text-center">
                <?php if (!empty($product['image'])): ?>
                    <?php
    $img_src = (strpos($product['image'], 'http') === 0) ? $product['image'] : '../' . $product['image'];
?>
                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Product Image" class="img-fluid rounded" onerror="this.src='../assets/img/noproduct.png'">
                <?php
else: ?>
                    <img src="../assets/img/noproduct.png" alt="No Image" class="img-fluid rounded">
                <?php
endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Actions</h5>
            </div>
            <div class="card-body">
                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-edit"></i> Edit Product
                </a>
                <a href="update_stock.php?id=<?php echo $product['id']; ?>" class="btn btn-success w-100 mb-2">
                    <i class="fas fa-warehouse"></i> Update Stock
                </a>
                <a href="generate_barcode.php?id=<?php echo $product['id']; ?>" class="btn btn-info w-100 mb-2">
                    <i class="fas fa-barcode"></i> Generate Barcode
                </a>
                <a href="stock_audit.php?product_id=<?php echo $product['id']; ?>" class="btn btn-outline-info w-100">
                    <i class="fas fa-history"></i> View Audit Log
                </a>
                <a href="../reports/sales_followup.php?sku=<?php echo urlencode($product['sku']); ?>" class="btn btn-warning w-100 mb-2">
                    <i class="fas fa-phone"></i> Follow-up Orders
                </a>
                <a href="view_products.php" class="btn btn-secondary w-100 mt-3">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>