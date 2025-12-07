<?php
// products/generate_barcode.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../lib/barcode_generator.php";

// Ensure user is logged in
Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

$product = null;
$variant = null;
$barcode_svg = '';
$message = '';
$message_type = '';

// Get product data
if (isset($_GET['id'])) {
    // Check if we're looking for a variant barcode
    if (isset($_GET['variant_id'])) {
        // Get variant data
        $query = "SELECT pv.*, p.name as product_name, p.sku as product_sku FROM product_variants pv 
                  JOIN products p ON pv.product_id = p.id 
                  WHERE pv.id = :variant_id AND pv.product_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":variant_id", $_GET['variant_id']);
        $stmt->bindParam(":id", $_GET['id']);
        $stmt->execute();
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($variant) {
            $product_query = "SELECT * FROM products WHERE id = :id";
            $product_stmt = $db->prepare($product_query);
            $product_stmt->bindParam(":id", $_GET['id']);
            $product_stmt->execute();
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate barcode from variant SKU
            $barcode_data = $variant['sku'];
            $generator = new BarcodeGenerator();
            $barcode_svg = $generator->generateSVG($barcode_data, 300, 100);
        } else {
            $message = "Variant not found!";
            $message_type = "danger";
        }
    } else {
        // Get main product data
        $query = "SELECT * FROM products WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $_GET['id']);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Generate or retrieve barcode
            if (empty($product['barcode'])) {
                // Generate barcode from SKU
                $barcode_data = $product['sku'];
            } else {
                // Use existing barcode
                $barcode_data = $product['barcode'];
            }
            
            $generator = new BarcodeGenerator();
            $barcode_svg = $generator->generateSVG($barcode_data, 300, 100);
        } else {
            $message = "Product not found!";
            $message_type = "danger";
        }
    }
} else {
    $message = "No product specified!";
    $message_type = "warning";
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-barcode"></i> Product Barcode</h1>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($product): ?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    Barcode for <?php echo htmlspecialchars($variant ? $product['name'] . ' - ' . $variant['size'] . ' ' . $variant['color'] : $product['name']); ?>
                </h6>
            </div>
            <div class="card-body text-center">
                <?php if ($barcode_svg): ?>
                    <div class="mb-4">
                        <?php echo $barcode_svg; ?>
                    </div>
                    <div class="mb-3">
                        <p><strong>Barcode Data:</strong> <?php echo htmlspecialchars($variant ? $variant['sku'] : $product['sku']); ?></p>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Barcode
                        </button>
                        <a href="view_products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                <?php else: ?>
                    <p>No barcode data available for this product.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Product Information</h6>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($product['name']); ?></p>
                <p><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?></p>
                <?php if ($variant): ?>
                    <p><strong>Variant:</strong> <?php echo htmlspecialchars($variant['size'] . ' ' . $variant['color']); ?></p>
                    <p><strong>Variant SKU:</strong> <?php echo htmlspecialchars($variant['sku']); ?></p>
                <?php endif; ?>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                <p><strong>Quantity:</strong> <?php echo $variant ? $variant['quantity'] : $product['quantity']; ?></p>
                <p><strong>Price:</strong> $<?php echo number_format($variant && $variant['price'] ? $variant['price'] : $product['price'], 2); ?></p>
                
                <?php if (!empty($product['image'])): ?>
                    <div class="text-center mt-3">
                        <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="img-fluid rounded">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .card, .card * {
        visibility: visible;
    }
    .card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
<?php endif; ?>

<?php require_once "../includes/footer.php"; ?>