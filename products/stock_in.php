<?php
// products/stock_in.php
session_start();
require_once "../config/database.php";
require_once "../models/AuditLog.php";
require_once "../config/auth.php";

$database = new Database();
$db = $database->getConnection();
$audit = new AuditLog($db);

// Get all products for selection
$query = "SELECT id, sku, name, quantity FROM products ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all suppliers
$suppliers_query = "SELECT id, name FROM suppliers ORDER BY name ASC";
$suppliers_stmt = $db->prepare($suppliers_query);
$suppliers_stmt->execute();
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$message_type = '';

// Handle form submission
if ($_POST) {
    try {
        $product_id = $_POST['product_id'];
        $quantity = intval($_POST['quantity']);
        $reason = $_POST['reason'];
        $reference = $_POST['reference'];
        $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
        
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }
        
        // Update product quantity
        $update_query = "UPDATE products SET quantity = quantity + :quantity WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":quantity", $quantity);
        $update_stmt->bindParam(":id", $product_id);
        
        if ($update_stmt->execute()) {
            // Log the stock movement
            $movement_query = "INSERT INTO stock_movements 
                              (product_id, movement_type, quantity, reason, reference, supplier_id) 
                              VALUES (:product_id, 'IN', :quantity, :reason, :reference, :supplier_id)";
            $movement_stmt = $db->prepare($movement_query);
            $movement_stmt->bindParam(":product_id", $product_id);
            $movement_stmt->bindParam(":quantity", $quantity);
            $movement_stmt->bindParam(":reason", $reason);
            $movement_stmt->bindParam(":reference", $reference);
            $movement_stmt->bindParam(":supplier_id", $supplier_id);
            
            if ($movement_stmt->execute()) {
                $message = "Stock added successfully!";
                $message_type = "success";
                
                // Log to AuditLog
                if (Auth::isLoggedIn()) {
                    $user = Auth::getCurrentUser();
                    $audit->log($user['id'], "STOCK_IN", "Added $quantity to product ID $product_id. Reason: $reason");
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
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-download text-success"></i> Stock In</h1>
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
                <h6 class="m-0 font-weight-bold text-primary">Add Stock to Product</h6>
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
                                (Current: <?php echo $product['quantity']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity to Add *</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" 
                               min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Select Supplier</label>
                        <select class="form-select" id="supplier_id" name="supplier_id">
                            <option value="">Choose a supplier (Optional)</option>
                            <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>">
                                <?php echo htmlspecialchars($supplier['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason *</label>
                        <select class="form-select" id="reason" name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="Purchase Order">Purchase Order</option>
                            <option value="Supplier Delivery">Supplier Delivery</option>
                            <option value="Return">Return</option>
                            <option value="Adjustment">Adjustment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reference" class="form-label">Reference</label>
                        <input type="text" class="form-control" id="reference" name="reference" 
                               placeholder="PO number, invoice, etc.">
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-download"></i> Add Stock
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
                <p>Use this form to add stock to existing products:</p>
                <ol>
                    <li>Select the product you want to add stock to</li>
                    <li>Enter the quantity you want to add</li>
                    <li>Select the supplier (optional)</li>
                    <li>Specify the reason for adding stock</li>
                    <li>Add any relevant reference numbers</li>
                    <li>Click "Add Stock" to complete the process</li>
                </ol>
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> This action will increase the product's quantity 
                    and create a record in the stock movement history.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>