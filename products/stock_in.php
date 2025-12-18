<?php
// products/stock_in.php
require_once "../config/auth_check.php";
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
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-download text-success"></i> <?php echo __('add_stock'); ?></h1>
    <a href="view_products.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> <?php echo __('back_to_products'); ?>
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
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('add_stock'); ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="product_id" class="form-label"><?php echo __('select_product'); ?> *</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value=""><?php echo __('choose_product'); ?></option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['sku']); ?> - 
                                <?php echo htmlspecialchars($product['name']); ?> 
                                (<?php echo __('current_qty'); ?>: <?php echo $product['quantity']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity" class="form-label"><?php echo __('quantity_to_add'); ?> *</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" 
                               min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="supplier_id" class="form-label"><?php echo __('select_supplier'); ?></label>
                        <select class="form-select" id="supplier_id" name="supplier_id">
                            <option value=""><?php echo __('choose_supplier'); ?></option>
                            <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>">
                                <?php echo htmlspecialchars($supplier['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label"><?php echo __('reason'); ?> *</label>
                        <select class="form-select" id="reason" name="reason" required>
                            <option value=""><?php echo __('reason'); ?></option>
                            <option value="Purchase Order"><?php echo __('reason_purchase_order'); ?></option>
                            <option value="Supplier Delivery"><?php echo __('reason_supplier_delivery'); ?></option>
                            <option value="Return"><?php echo __('reason_return'); ?></option>
                            <option value="Adjustment"><?php echo __('reason_adjustment'); ?></option>
                            <option value="Other"><?php echo __('reason_other'); ?></option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reference" class="form-label"><?php echo __('reference'); ?></label>
                        <input type="text" class="form-control" id="reference" name="reference" 
                               placeholder="<?php echo __('reference'); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-download"></i> <?php echo __('add_stock'); ?>
                    </button>
                    <a href="view_products.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> <?php echo __('cancel'); ?>
                    </a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('instructions'); ?></h6>
            </div>
            <div class="card-body">
                <p><?php echo __('stock_in_instructions'); ?></p>
                <ol>
                    <li><?php echo __('step_1_product'); ?></li>
                    <li><?php echo __('step_2_qty'); ?></li>
                    <li><?php echo __('step_3_supplier'); ?></li>
                    <li><?php echo __('step_4_reason'); ?></li>
                    <li><?php echo __('reference'); ?></li>
                    <li><?php echo __('add_stock'); ?></li>
                </ol>
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> <?php echo __('stock_in_note'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>