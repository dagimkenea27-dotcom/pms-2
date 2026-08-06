<?php
// products/stock_out.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/AuditLog.php";
require_once "../config/auth.php";

$database = new Database();
$db = $database->getConnection();
$audit = new AuditLog($db);

// Handle AJAX request for variants
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_variants' && isset($_GET['product_id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $db->prepare("SELECT id, size, color, sku, quantity FROM product_variants WHERE product_id = ? AND quantity > 0");
        $stmt->execute([$_GET['product_id']]);
        $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'variants' => $variants]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get all products for selection
$query = "SELECT id, sku, name, quantity, has_variants FROM products WHERE quantity > 0 ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all drivers
$drivers_query = "SELECT id, full_name FROM drivers WHERE status = 'active' ORDER BY full_name ASC";
$drivers_stmt = $db->prepare($drivers_query);
$drivers_stmt->execute();
$drivers = $drivers_stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
        $message = "Security Error: Invalid Token";
        $message_type = "danger";
    } else {
    try {
        $product_id = $_POST['product_id'];
        $quantity = intval($_POST['quantity']);
        $reason = $_POST['reason'];
        $reference = $_POST['reference'];
        $variant_id = !empty($_POST['variant_id']) ? $_POST['variant_id'] : null;
        
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }
        
        // Get product details
        $check_query = "SELECT quantity, has_variants FROM products WHERE id = :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":id", $product_id);
        $check_stmt->execute();
        $product = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("Product not found.");
        }
        
        $db->beginTransaction();

        if ($product['has_variants']) {
            if (empty($variant_id)) {
                throw new Exception("Please select a variant for this product.");
            }
            
            // Validate variant stock
            $v_stmt = $db->prepare("SELECT quantity, sku, size, color FROM product_variants WHERE id = ?");
            $v_stmt->execute([$variant_id]);
            $variant = $v_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$variant) {
                throw new Exception("Variant not found.");
            }
            
            if ($variant['quantity'] < $quantity) {
                throw new Exception("Not enough stock for this variant. Available: " . $variant['quantity']);
            }
            
            // Deduct from variant
            $update_v = $db->prepare("UPDATE product_variants SET quantity = quantity - ? WHERE id = ?");
            $update_v->execute([$quantity, $variant_id]);
            
            // Deduct from main product (aggregate)
            $update_p = $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $update_p->execute([$quantity, $product_id]);
            
            // Log movement
            $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
            // logStockChange handles the insertion into stock_movements
            
            logStockChange($db, $product_id, $variant_id, Auth::getCurrentUser()['id'], 'out', $variant['quantity'], $variant['quantity'] - $quantity, "Stock Out: $reason ($reference)", $reference, $driver_id);
            
            $log_desc = "Removed $quantity from product ID $product_id (Variant: {$variant['size']} {$variant['color']}). Reason: $reason";

        } else {
            // Simple product logic
            if ($product['quantity'] < $quantity) {
                throw new Exception("Not enough stock available. Current stock: " . $product['quantity']);
            }
            
            // Update product quantity
            $update_query = "UPDATE products SET quantity = quantity - :quantity WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":quantity", $quantity);
            $update_stmt->bindParam(":id", $product_id);
            $update_stmt->execute();
            
            // Log stock movement
            $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
            // logStockChange handles the insertion into stock_movements
            
            logStockChange($db, $product_id, null, Auth::getCurrentUser()['id'], 'out', $product['quantity'], $product['quantity'] - $quantity, "Stock Out: $reason ($reference)", $reference, $driver_id);
            
            $log_desc = "Removed $quantity from product ID $product_id. Reason: $reason";
        }

        $db->commit();
        
        // Check low stock (threshold: 10) - treating aggregate for now
        $new_quantity = $product['quantity'] - $quantity;
        if ($new_quantity <= 10) {
            require_once "../models/Notification.php";
            $notification = new Notification($db);
            $notifMsg = "Low Stock Alert: Product ID $product_id is down to $new_quantity units.";
            $notification->notifyAdmins($notifMsg, "products/view_products.php", "warning");
        }
        
        $message = "Stock removed successfully!";
        $message_type = "success";
        
        if (Auth::isLoggedIn()) {
            $user = Auth::getCurrentUser();
            $audit->log($user['id'], "STOCK_OUT", $log_desc);
        }
        
        // PRG Pattern
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
        header("Location: stock_out.php");
        exit();

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
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

<?php 
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
if ($message): ?>
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
                    <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Select Product *</label>
                        <div class="position-relative">
                            <select class="form-select" id="product_id" name="product_id" required style="display: none;">
                                <option value="">Choose a product</option>
                                <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-has-variants="<?php echo $product['has_variants']; ?>"
                                        data-search="<?php echo htmlspecialchars(strtolower($product['sku'] . ' ' . $product['name'])); ?>">
                                    <?php echo htmlspecialchars($product['sku']); ?> - 
                                    <?php echo htmlspecialchars($product['name']); ?> 
                                    (Total: <?php echo $product['quantity']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="searchable-select-container">
                                <input type="text" class="form-control" id="product_search" placeholder="<?php echo __('search_products'); ?>" autocomplete="off">
                                <div id="product_dropdown" class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto; position: absolute; z-index: 1000; display: none;"></div>
                            </div>
                            <input type="hidden" id="selected_product_id" name="product_id" required>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="variant_container" style="display:none;">
                        <label for="variant_id" class="form-label">Select Variant *</label>
                        <select class="form-select" id="variant_id" name="variant_id">
                            <option value="">Choose a variant</option>
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

                    <div class="mb-3">
                        <label for="driver_id" class="form-label">Driver Name</label>
                        <select class="form-select" id="driver_id" name="driver_id">
                            <option value="">Choose a driver</option>
                            <?php foreach ($drivers as $driver): ?>
                            <option value="<?php echo $driver['id']; ?>">
                                <?php echo htmlspecialchars($driver['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
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
                    <li>Select the product you want to remove stock from.</li>
                    <li><strong>If the product has variants</strong> (Size/Color), a dropdown will appear to select the specific item.</li>
                    <li>Enter the quantity you want to remove.</li>
                    <li>Specify the reason for removing stock.</li>
                    <li>Click "Remove Stock" to complete the process.</li>
                </ol>
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> This action will decrease the specific variant's stock AND the total product quantity.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Searchable dropdown functionality
(function() {
    const searchInput = document.getElementById('product_search');
    const dropdown = document.getElementById('product_dropdown');
    const hiddenInput = document.getElementById('selected_product_id');
    const originalSelect = document.getElementById('product_id');
    const variantContainer = document.getElementById('variant_container');
    const variantSelect = document.getElementById('variant_id');
    
    // Store all options for searching
    const options = [];
    for (let i = 1; i < originalSelect.options.length; i++) { // Skip first option
        const option = originalSelect.options[i];
        options.push({
            value: option.value,
            text: option.text,
            search: option.getAttribute('data-search'),
            hasVariants: option.getAttribute('data-has-variants')
        });
    }
    
    // Show dropdown with filtered options
    searchInput.addEventListener('focus', function() {
        filterOptions('');
        dropdown.style.display = 'block';
    });
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
    
    // Filter options based on search term
    searchInput.addEventListener('input', function() {
        filterOptions(this.value);
        dropdown.style.display = 'block';
    });
    
    function filterOptions(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        dropdown.innerHTML = '';
        
        const filtered = options.filter(option => 
            !term || option.search.includes(term)
        );
        
        if (filtered.length === 0) {
            dropdown.innerHTML = '<div class="dropdown-item disabled">No products found</div>';
        } else {
            filtered.forEach(option => {
                const item = document.createElement('div');
                item.className = 'dropdown-item';
                item.textContent = option.text;
                item.addEventListener('click', function() {
                    searchInput.value = option.text;
                    hiddenInput.value = option.value;
                    dropdown.style.display = 'none';
                    
                    // Handle variant logic
                    const hasVariants = option.hasVariants == '1';
                    
                    // Reset variant select
                    variantSelect.innerHTML = '<option value="">Choose a variant</option>';
                    
                    if (hasVariants && option.value) {
                        // Show variant dropdown
                        variantContainer.style.display = 'block';
                        variantSelect.setAttribute('required', 'required');
                        
                        // Fetch variants
                        fetch(`stock_out.php?ajax=get_variants&product_id=${option.value}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    data.variants.forEach(v => {
                                        const variantOption = document.createElement('option');
                                        variantOption.value = v.id;
                                        variantOption.textContent = `${v.size} ${v.color} (Qty: ${v.quantity})`;
                                        variantSelect.appendChild(variantOption);
                                    });
                                }
                            })
                            .catch(err => console.error('Error fetching variants:', err));
                    } else {
                        // Hide variant dropdown
                        variantContainer.style.display = 'none';
                        variantSelect.removeAttribute('required');
                    }
                    
                    // Dispatch change event for form validation
                    const event = new Event('change', { bubbles: true });
                    hiddenInput.dispatchEvent(event);
                });
                dropdown.appendChild(item);
            });
        }
    }
    
    // Initialize with empty search to show all options
    filterOptions('');
})();
</script>

<?php require_once "../includes/footer.php"; ?>