<?php
// products/update_stock.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Get product data
$product = null;
$variants = [];
if (isset($_GET['id'])) {
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

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

// Handle form submission
if ($_POST) {
    try {
        $movement_type = $_POST['movement_type'];
        $quantity = intval($_POST['quantity']);
        $reason = $_POST['reason'];
        $reference = $_POST['reference'];
        $variant_id = !empty($_POST['variant_id']) ? $_POST['variant_id'] : null;

        if ($product['has_variants'] && empty($variant_id)) {
            throw new Exception("Please select a variant.");
        }
        
        $db->beginTransaction();

        $current_qty = 0;
        
        // Update logic depending on variant or simple
        if ($variant_id) {
            // Get current variant qty
            $v_stmt = $db->prepare("SELECT quantity FROM product_variants WHERE id = ?");
            $v_stmt->execute([$variant_id]);
            $v_row = $v_stmt->fetch(PDO::FETCH_ASSOC);
            $current_qty = $v_row['quantity'];

            if ($movement_type == 'OUT' && $current_qty < $quantity) {
                 throw new Exception("Not enough stock available for this variant. Current: " . $current_qty);
            }

            // Update variant quantity
            if ($movement_type == 'IN') {
                $db->prepare("UPDATE product_variants SET quantity = quantity + ? WHERE id = ?")->execute([$quantity, $variant_id]);
                // Update master product quantity
                $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")->execute([$quantity, $product['id']]);
                $new_qty = $current_qty + $quantity;
            } else {
                $db->prepare("UPDATE product_variants SET quantity = quantity - ? WHERE id = ?")->execute([$quantity, $variant_id]);
                // Update master product quantity
                $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")->execute([$quantity, $product['id']]);
                $new_qty = $current_qty - $quantity;
            }

        } else {
            // Simple product update
            $current_qty = $product['quantity'];

            if ($movement_type == 'OUT' && $current_qty < $quantity) {
                 throw new Exception("Not enough stock available. Current: " . $current_qty);
            }

            if ($movement_type == 'IN') {
                 $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")->execute([$quantity, $product['id']]);
                 $new_qty = $current_qty + $quantity;
            } else {
                 $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")->execute([$quantity, $product['id']]);
                 $new_qty = $current_qty - $quantity;
            }
        }

        // Log movement
        $movement_query = "INSERT INTO stock_movements 
                          (product_id, variant_id, movement_type, quantity, reason, reference) 
                          VALUES (:product_id, :variant_id, :movement_type, :quantity, :reason, :reference)";
        $movement_stmt = $db->prepare($movement_query);
        $movement_stmt->execute([
            ':product_id' => $product['id'],
            ':variant_id' => $variant_id,
            ':movement_type' => $movement_type,
            ':quantity' => $quantity,
            ':reason' => $reason,
            ':reference' => $reference
        ]);

        $db->commit();
        
        $message = "Stock updated successfully!";
        $message_type = "success";
        
        // Refresh product data
        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(":id", $product['id']);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product['has_variants']) {
            $v_stmt = $db->prepare("SELECT * FROM product_variants WHERE product_id = :pid");
            $v_stmt->execute([':pid' => $product['id']]);
            $variants = $v_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (Exception $exception) {
        if ($db->inTransaction()) $db->rollBack();
        $message = "Error: " . $exception->getMessage();
        $message_type = "danger";
    }
}

// Get stock movement history
$movement_query = "SELECT sm.*, pv.sku as variant_sku, pv.size, pv.color 
                  FROM stock_movements sm
                  LEFT JOIN product_variants pv ON sm.variant_id = pv.id
                  WHERE sm.product_id = :product_id 
                  ORDER BY sm.created_at DESC 
                  LIMIT 20";
$movement_stmt = $db->prepare($movement_query);
$movement_stmt->bindParam(":product_id", $product['id']);
$movement_stmt->execute();
$movement_history = $movement_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-warehouse"></i> Update Stock</h1>
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
    <div class="col-md-4">
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-cube"></i> Product Details</h6>
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
                        <th>Total Stock:</th>
                        <td>
                            <span class="h5 <?php echo $product['quantity'] <= $product['min_stock'] ? 'text-warning' : 'text-success'; ?>">
                                <?php echo $product['quantity']; ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-edit"></i> Update Stock</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="variant_id" class="form-label">Variant *</label>
                        <select class="form-select" id="variant_id" name="variant_id" required>
                            <option value="">Select Variant</option>
                            <?php 
                            $selected_variant = isset($_GET['variant_id']) ? $_GET['variant_id'] : '';
                            foreach ($variants as $v): 
                                $is_selected = ($v['id'] == $selected_variant) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $v['id']; ?>" <?php echo $is_selected; ?>>
                                    <?php echo htmlspecialchars($v['sku'] . ' (' . $v['size'] . '/' . $v['color'] . ') - Qty: ' . $v['quantity']); ?> 
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

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
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history"></i> Stock Movement History</h6>
            </div>
            <div class="card-body">
                <?php if ($movement_history): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Variant</th>
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
                                    <td>
                                        <?php 
                                        if ($movement['variant_id']) {
                                            echo htmlspecialchars($movement['size'] . '/' . $movement['color']);
                                            if ($movement['variant_sku']) echo ' <small class="text-muted">('.$movement['variant_sku'].')</small>';
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
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