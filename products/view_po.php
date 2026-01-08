<?php
// products/view_po.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../includes/functions.php";

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    header("Location: purchase_orders.php");
    exit();
}

$po_id = $_GET['id'];

// Handle Receive Action
if (isset($_POST['action']) && $_POST['action'] === 'receive' && Auth::hasRole('manager')) {
    try {
        $db->beginTransaction();
        
        // Get PO status
        $status_check = $db->prepare("SELECT status FROM purchase_orders WHERE id = ?");
        $status_check->execute([$po_id]);
        $current_status = $status_check->fetchColumn();
        
        if ($current_status === 'received') {
            throw new Exception("This PO has already been received.");
        }
        
        // Update PO Items status (optional) and get items to update stock
        $items_query = "SELECT poi.*, p.has_variants, p.quantity as p_current_qty, pv.quantity as v_current_qty
                       FROM purchase_order_items poi
                       JOIN products p ON poi.product_id = p.id
                       LEFT JOIN product_variants pv ON poi.variant_id = pv.id
                       WHERE poi.purchase_order_id = ?";
        $items_stmt = $db->prepare($items_query);
        $items_stmt->execute([$po_id]);
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $received_qty = $item['quantity_ordered']; // For simplicity, receiving full amount
            
            if ($item['variant_id']) {
                // Update Variant
                $db->prepare("UPDATE product_variants SET quantity = quantity + ? WHERE id = ?")
                   ->execute([$received_qty, $item['variant_id']]);
                // Update Total in Products
                $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")
                   ->execute([$received_qty, $item['product_id']]);
                
                logStockChange($db, $item['product_id'], $item['variant_id'], Auth::getCurrentUser()['id'], 'in', $item['v_current_qty'], $item['v_current_qty'] + $received_qty, "PO Received: " . $_GET['id']);
            } else {
                // Update Product
                $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")
                   ->execute([$received_qty, $item['product_id']]);
                
                logStockChange($db, $item['product_id'], null, Auth::getCurrentUser()['id'], 'in', $item['p_current_qty'], $item['p_current_qty'] + $received_qty, "PO Received: " . $_GET['id']);
            }
            
            // Mark items as received in PO items table
            $db->prepare("UPDATE purchase_order_items SET quantity_received = quantity_ordered WHERE id = ?")
               ->execute([$item['id']]);
        }
        
        // Update PO Status
        $db->prepare("UPDATE purchase_orders SET status = 'received' WHERE id = ?")
           ->execute([$po_id]);
        
        $db->commit();
        $_SESSION['message'] = "Stock has been successfully received and updated!";
        $_SESSION['message_type'] = "success";
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}

// Get PO basic info
$po_query = "SELECT po.*, s.name as supplier_name, u.username as creator_name
             FROM purchase_orders po
             JOIN suppliers s ON po.supplier_id = s.id
             JOIN users u ON po.created_by = u.id
             WHERE po.id = ?";
$po_stmt = $db->prepare($po_query);
$po_stmt->execute([$po_id]);
$po = $po_stmt->fetch(PDO::FETCH_ASSOC);

if (!$po) {
    header("Location: purchase_orders.php");
    exit();
}

// Get PO items
$items_query = "SELECT poi.*, p.name as product_name, p.sku as product_sku, pv.size, pv.color, pv.sku as variant_sku
                FROM purchase_order_items poi
                JOIN products p ON poi.product_id = p.id
                LEFT JOIN product_variants pv ON poi.variant_id = pv.id
                WHERE poi.purchase_order_id = ?";
$items_stmt = $db->prepare($items_query);
$items_stmt->execute([$po_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-invoice"></i> Order Details: <?php echo htmlspecialchars($po['order_number']); ?></h1>
    <div>
        <a href="purchase_orders.php" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
        <?php if ($po['status'] !== 'received' && $po['status'] !== 'cancelled'): ?>
            <a href="edit_po.php?id=<?php echo $po_id; ?>" class="btn btn-sm btn-warning shadow-sm ms-2">
                <i class="fas fa-edit fa-sm"></i> Edit
            </a>
        <?php endif; ?>
        <a href="print_po.php?id=<?php echo $po_id; ?>" class="btn btn-sm btn-outline-primary shadow-sm ms-2" target="_blank">
            <i class="fas fa-print fa-sm"></i> Print PO
        </a>
        
        <div class="btn-group ms-2">
            <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-share-alt fa-sm"></i> Share
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php
                $share_text = "📦 *Purchase Order: " . $po['order_number'] . "*\n";
                $share_text .= "Supplier: " . $po['supplier_name'] . "\n";
                $share_text .= "Total: $" . number_format($po['total_amount'], 2) . "\n";
                $share_text .= "Status: " . strtoupper($po['status']) . "\n";
                
                $share_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . BASE_URL . "products/print_po.php?id=" . $po_id;
                $telegram_url = "https://t.me/share/url?url=" . urlencode($share_url) . "&text=" . urlencode($share_text);
                
                $email_subject = "Purchase Order #" . $po['order_number'] . " - GojoShop";
                $email_body = "Hi,\n\nPlease find the details of Purchase Order #" . $po['order_number'] . " below:\n\n" .
                             "Order Number: " . $po['order_number'] . "\n" .
                             "Supplier: " . $po['supplier_name'] . "\n" .
                             "Total Amount: $" . number_format($po['total_amount'], 2) . "\n" .
                             "Status: " . strtoupper($po['status']) . "\n\n" .
                             "Printable PO: " . $share_url . "\n\n" .
                             "Regards,\n" . Auth::getCurrentUser()['full_name'];
                $mailto_url = "mailto:?subject=" . urlencode($email_subject) . "&body=" . urlencode($email_body);
                ?>
                <li>
                    <a class="dropdown-item" href="<?php echo $telegram_url; ?>" target="_blank">
                        <i class="fab fa-telegram text-info me-2"></i> Send via Telegram
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo $mailto_url; ?>">
                        <i class="fas fa-envelope text-primary me-2"></i> Send via Email
                    </a>
                </li>
            </ul>
        </div>

        <?php if ($po['status'] !== 'received' || Auth::hasRole('admin')): ?>
            <a href="delete_po.php?id=<?php echo $po_id; ?>" class="btn btn-sm btn-outline-danger shadow-sm ms-2" onclick="return confirm('Are you sure you want to delete this Purchase Order?')">
                <i class="fas fa-trash fa-sm"></i> Delete
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Summary</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><th>Supplier:</th><td><?php echo htmlspecialchars($po['supplier_name']); ?></td></tr>
                    <tr><th>Date:</th><td><?php echo date('Y-m-d', strtotime($po['created_at'])); ?></td></tr>
                    <tr><th>Status:</th><td>
                        <span class="badge bg-<?php echo $po['status'] == 'received' ? 'success' : 'primary'; ?>">
                            <?php echo strtoupper($po['status']); ?>
                        </span>
                    </td></tr>
                    <tr><th>Created By:</th><td><?php echo htmlspecialchars($po['creator_name']); ?></td></tr>
                    <?php 
                    $total_qty = 0;
                    foreach($items as $i) $total_qty += $i['quantity_ordered'];
                    ?>
                    <tr><th>Total Items:</th><td><?php echo $total_qty; ?></td></tr>
                    <tr><th class="h5">Total:</th><td class="h5 text-primary">$<?php echo number_format($po['total_amount'], 2); ?></td></tr>
                </table>
                <hr>
                <div class="mb-3">
                    <label class="fw-bold">Notes:</label>
                    <p class="text-muted small"><?php echo nl2br(htmlspecialchars($po['notes'] ?? 'No notes.')); ?></p>
                </div>
                
                <?php if ($po['status'] !== 'received'): ?>
                    <form method="POST" onsubmit="return confirm('Do you want to receive all items and update stock?');">
                        <input type="hidden" name="action" value="receive">
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-check-circle"></i> Receive Stock
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success text-center py-2 mb-0">
                        <i class="fas fa-check-double"></i> Documented & Received
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Ordered Items</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Product Details</th>
                                <th class="text-center">Ordered</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                    <small class="text-muted">
                                        <?php if ($item['variant_id']): ?>
                                            <?php echo htmlspecialchars($item['size'] . ' / ' . $item['color']); ?> (<?php echo htmlspecialchars($item['variant_sku']); ?>)
                                        <?php else: ?>
                                            SKU: <?php echo htmlspecialchars($item['product_sku']); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td class="text-center"><?php echo $item['quantity_ordered']; ?></td>
                                <td class="text-end">$<?php echo number_format($item['unit_cost'], 2); ?></td>
                                <td class="text-end fw-bold">$<?php echo number_format($item['quantity_ordered'] * $item['unit_cost'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th class="text-end">$<?php echo number_format($po['total_amount'], 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
