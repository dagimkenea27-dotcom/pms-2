<?php
// products/stock_out.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/AuditLog.php";
require_once "../config/auth.php";

$database = new Database();
$db = $database->getConnection();
$audit = new AuditLog($db);

// Get all products for selection
$query = "SELECT id, sku, name, quantity FROM products WHERE quantity > 0 ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$message_type = '';

// Handle form submission
if ($_POST) {
    try {
        $product_id = $_POST['product_id'];
        $quantity = intval($_POST['quantity']);
        $reason = $_POST['reason'];
        $reference = $_POST['reference'];
        
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }
        
        // Check if enough stock is available
        $check_query = "SELECT quantity FROM products WHERE id = :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":id", $product_id);
        $check_stmt->execute();
        $current_quantity = $check_stmt->fetch(PDO::FETCH_ASSOC)['quantity'];
        
        if ($current_quantity < $quantity) {
            throw new Exception("Not enough stock available. Current stock: " . $current_quantity);
        }
        
        // Update product quantity
        $update_query = "UPDATE products SET quantity = quantity - :quantity WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":quantity", $quantity);
        $update_stmt->bindParam(":id", $product_id);
        
        if ($update_stmt->execute()) {
            // Log the stock movement
            $movement_query = "INSERT INTO stock_movements 
                              (product_id, movement_type, quantity, reason, reference) 
                              VALUES (:product_id, 'OUT', :quantity, :reason, :reference)";
            $movement_stmt = $db->prepare($movement_query);
            $movement_stmt->bindParam(":product_id", $product_id);
            $movement_stmt->bindParam(":quantity", $quantity);
            $movement_stmt->bindParam(":reason", $reason);
            $movement_stmt->bindParam(":reference", $reference);
            
            if ($movement_stmt->execute()) {
                $message = "Stock removed successfully!";
                $message_type = "success";
                
                // Log to AuditLog
                if (Auth::isLoggedIn()) {
                    $user = Auth::getCurrentUser();
                    $audit->log($user['id'], "STOCK_OUT", "Removed $quantity from product ID $product_id. Reason: $reason");
                }
                
                // Refresh product list
                $stmt = $db->prepare($query);
                $stmt->execute();
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                throw new Exception("Failed to log stock movement.");
            }
        } else {
            throw new Exception("Failed to update product quantity.");
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-upload text-danger"></i> Stock Out</h1>
    <a href="view_products.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Products
    </a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Remove Stock from Product</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Select Product *</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Choose a product</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['sku']); ?> - 
                                <?php echo htmlspecialchars($product['name']); ?> 
                                (Available: <?php echo $product['quantity']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity to Remove *</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" 
                               min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason *</label>
                        <select class="form-select" id="reason" name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="Sale">Sale</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Lost">Lost</option>
                            <option value="Adjustment">Adjustment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reference" class="form-label">Reference</label>
                        <input type="text" class="form-control" id="reference" name="reference" 
                               placeholder="Invoice number, etc.">
                    </div>
                    
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-upload"></i> Remove Stock
                    </button>
                    <a href="view_products.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Instructions</h6>
            </div>
            <div class="card-body">
                <p>Use this form to remove stock from existing products:</p>
                <ol>
                    <li>Select the product you want to remove stock from</li>
                    <li>Enter the quantity you want to remove</li>
                    <li>Specify the reason for removing stock</li>
                    <li>Add any relevant reference numbers</li>
                    <li>Click "Remove Stock" to complete the process</li>
                </ol>
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> This action will decrease the product's quantity 
                    and create a record in the stock movement history.
                </p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Note:</strong> You cannot remove more stock than is currently available.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>