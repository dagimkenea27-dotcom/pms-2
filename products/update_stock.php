<?php
// products/update_stock.php
session_start();
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Get product data
$product = null;
if (isset($_GET['id'])) {
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$product) {
    $_SESSION['message'] = "Product not found!";
    $_SESSION['message_type'] = 'danger';
    header("Location: view_products.php");
    exit();
}

// Handle form submission
if ($_POST) {
    try {
        $movement_type = $_POST['movement_type'];
        $quantity = intval($_POST['quantity']);
        $reason = $_POST['reason'];
        $reference = $_POST['reference'];
        
        // Update product quantity
        if ($movement_type == 'IN') {
            $new_quantity = $product['quantity'] + $quantity;
            $update_query = "UPDATE products SET quantity = quantity + :quantity WHERE id = :id";
        } else {
            if ($product['quantity'] < $quantity) {
                $message = "Error: Not enough stock available. Current stock: " . $product['quantity'];
                $message_type = "danger";
            } else {
                $new_quantity = $product['quantity'] - $quantity;
                $update_query = "UPDATE products SET quantity = quantity - :quantity WHERE id = :id";
            }
        }
        
        if (!isset($message)) {
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":quantity", $quantity);
            $update_stmt->bindParam(":id", $product['id']);
            
            if ($update_stmt->execute()) {
                // Log the stock movement
                $movement_query = "INSERT INTO stock_movements 
                                  (product_id, movement_type, quantity, reason, reference) 
                                  VALUES (:product_id, :movement_type, :quantity, :reason, :reference)";
                $movement_stmt = $db->prepare($movement_query);
                $movement_stmt->bindParam(":product_id", $product['id']);
                $movement_stmt->bindParam(":movement_type", $movement_type);
                $movement_stmt->bindParam(":quantity", $quantity);
                $movement_stmt->bindParam(":reason", $reason);
                $movement_stmt->bindParam(":reference", $reference);
                $movement_stmt->execute();
                
                $message = "Stock updated successfully! New quantity: " . $new_quantity;
                $message_type = "success";
                
                // Refresh product data
                $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
                $stmt->bindParam(":id", $product['id']);
                $stmt->execute();
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    } catch (PDOException $exception) {
        $message = "Error: " . $exception->getMessage();
        $message_type = "danger";
    }
}

// Get stock movement history
$movement_query = "SELECT * FROM stock_movements 
                  WHERE product_id = :product_id 
                  ORDER BY created_at DESC 
                  LIMIT 20";
$movement_stmt = $db->prepare($movement_query);
$movement_stmt->bindParam(":product_id", $product['id']);
$movement_stmt->execute();
$movement_history = $movement_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-warehouse"></i> Update Stock</h1>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="fas fa-cube"></i> Product Details</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>SKU:</th>
                        <td><?php echo htmlspecialchars($product['sku']); ?></td>
                    </tr>
                    <tr>
                        <th>Name:</th>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                    </tr>
                    <tr>
                        <th>Current Stock:</th>
                        <td>
                            <span class="h5 <?php echo $product['quantity'] <= $product['min_stock'] ? 'text-warning' : 'text-success'; ?>">
                                <?php echo $product['quantity']; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Min Stock:</th>
                        <td><?php echo $product['min_stock']; ?></td>
                    </tr>
                    <tr>
                        <th>Location:</th>
                        <td><?php echo htmlspecialchars($product['location']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="fas fa-edit"></i> Update Stock</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Movement Type *</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="movement_type" 
                                       id="type_in" value="IN" checked>
                                <label class="form-check-label text-success" for="type_in">
                                    <i class="fas fa-download"></i> Stock In
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="movement_type" 
                                       id="type_out" value="OUT">
                                <label class="form-check-label text-danger" for="type_out">
                                    <i class="fas fa-upload"></i> Stock Out
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity *</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" 
                               min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason *</label>
                        <select class="form-select" id="reason" name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="Purchase Order">Purchase Order</option>
                            <option value="Supplier Delivery">Supplier Delivery</option>
                            <option value="Return">Return</option>
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
                               placeholder="PO number, invoice, etc.">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Update Stock
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="fas fa-history"></i> Stock Movement History</h6>
            </div>
            <div class="card-body">
                <?php if ($movement_history): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reason</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movement_history as $movement): ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i A', strtotime($movement['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $movement['movement_type'] == 'IN' ? 'success' : 'danger'; ?>">
                                            <?php echo $movement['movement_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $movement['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($movement['reason']); ?></td>
                                    <td><?php echo htmlspecialchars($movement['reference']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No stock movement history found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>