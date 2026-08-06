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
$query = "SELECT id, sku, name, quantity, has_variants FROM products ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all suppliers
$suppliers_query = "SELECT id, name FROM suppliers ORDER BY name ASC";
$suppliers_stmt = $db->prepare($suppliers_query);
$suppliers_stmt->execute();
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX request for variants (Added for consistency)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_variants' && isset($_GET['product_id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $db->prepare("SELECT id, size, color, sku, quantity FROM product_variants WHERE product_id = ?");
        $stmt->execute([$_GET['product_id']]);
        $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'variants' => $variants]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

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
        $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
        
        $variant_id = !empty($_POST['variant_id']) ? $_POST['variant_id'] : null;
        
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }
        
        $db->beginTransaction();

        // Get current quantity for audit
        $check_stmt = $db->prepare("SELECT quantity, has_variants FROM products WHERE id = ?");
        $check_stmt->execute([$product_id]);
        $p_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($p_data['has_variants']) {
            if (empty($variant_id)) throw new Exception("Please select a variant.");
            
            $v_stmt = $db->prepare("SELECT quantity FROM product_variants WHERE id = ?");
            $v_stmt->execute([$variant_id]);
            $v_qty_before = $v_stmt->fetchColumn();

            $db->prepare("UPDATE product_variants SET quantity = quantity + ? WHERE id = ?")->execute([$quantity, $variant_id]);
            $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")->execute([$quantity, $product_id]);
            
            $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
            logStockChange($db, $product_id, $variant_id, Auth::getCurrentUser()['id'], 'in', $v_qty_before, $v_qty_before + $quantity, "Stock In: $reason ($reference)", $reference, $driver_id, $supplier_id);
        } else {
            $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")->execute([$quantity, $product_id]);
            $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
            logStockChange($db, $product_id, null, Auth::getCurrentUser()['id'], 'in', $p_data['quantity'], $p_data['quantity'] + $quantity, "Stock In: $reason ($reference)", $reference, $driver_id, $supplier_id);
        }
        
        // Duplicate log removed as it is now handled by logStockChange
        
        $db->commit();
        $message = "Stock added successfully!";
        $message_type = "success";
        
        // PRG Pattern
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
        header("Location: stock_in.php");
        exit();

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
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
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('add_stock'); ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
                    <div class="mb-3">
                        <label for="product_id" class="form-label"><?php echo __('select_product'); ?> *</label>
                        <div class="position-relative">
                            <select class="form-select" id="product_id" name="product_id" required style="display: none;">
                                <option value=""><?php echo __('choose_product'); ?></option>
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

<script>
// Searchable dropdown functionality
(function() {
    const searchInput = document.getElementById('product_search');
    const dropdown = document.getElementById('product_dropdown');
    const hiddenInput = document.getElementById('selected_product_id');
    const originalSelect = document.getElementById('product_id');
    
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

                    const hasVariants = option.hasVariants == '1';
                    const variantContainer = document.getElementById('variant_container');
                    const variantSelect = document.getElementById('variant_id');
                    
                    variantSelect.innerHTML = '<option value="">Choose a variant</option>';
                    
                    if (hasVariants && option.value) {
                        variantContainer.style.display = 'block';
                        variantSelect.setAttribute('required', 'required');
                        fetch(`stock_in.php?ajax=get_variants&product_id=${option.value}`)
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
                            });
                    } else {
                        variantContainer.style.display = 'none';
                        variantSelect.removeAttribute('required');
                    }
                    
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