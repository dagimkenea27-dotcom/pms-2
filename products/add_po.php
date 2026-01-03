<?php
// products/add_po.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../includes/functions.php";

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'];
    $order_number = $_POST['order_number'] ?: 'PO-' . date('YmdHis');
    $notes = $_POST['notes'];
    $products = $_POST['products']; // Array of [id, variant_id, qty, cost]
    
    try {
        $db->beginTransaction();
        
        $total_amount = 0;
        foreach ($products as $p) {
            $total_amount += ($p['qty'] * $p['cost']);
        }
        
        // Insert PO
        $po_query = "INSERT INTO purchase_orders (supplier_id, order_number, notes, total_amount, created_by) 
                     VALUES (?, ?, ?, ?, ?)";
        $po_stmt = $db->prepare($po_query);
        $po_stmt->execute([$supplier_id, $order_number, $notes, $total_amount, Auth::getCurrentUser()['id']]);
        $po_id = $db->lastInsertId();
        
        // Insert Items
        $item_query = "INSERT INTO purchase_order_items (purchase_order_id, product_id, variant_id, quantity_ordered, unit_cost) 
                       VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $db->prepare($item_query);
        
        foreach ($products as $p) {
            if ($p['qty'] <= 0) continue;
            $item_stmt->execute([$po_id, $p['product_id'], $p['variant_id'] ?: null, $p['qty'], $p['cost']]);
        }
        
        $db->commit();
        $_SESSION['message'] = "Purchase Order created successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: purchase_orders.php");
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Get Suppliers
$suppliers = $db->query("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-plus"></i> Create Purchase Order</h1>
    <a href="purchase_orders.php" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back to POs
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" id="poForm">
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Supplier *</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">Choose Supplier</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Order Number (Auto-gen if empty)</label>
                    <input type="text" name="order_number" class="form-control" placeholder="PO-2023001">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="1"></textarea>
                </div>
            </div>

            <h5>Order Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered" id="poItemsTable">
                    <thead class="bg-light">
                        <tr>
                            <th width="40%">Product / Variant</th>
                            <th width="15%">Quantity</th>
                            <th width="15%">Unit Cost ($)</th>
                            <th width="15%">Subtotal</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="item-row">
                            <td>
                                <select name="products[0][product_selector]" class="form-select product-selector" required>
                                    <option value="">Search Product...</option>
                                    <?php
                                    // Fetch products with their variants
                                    $p_query = "SELECT p.id as p_id, p.name as p_name, p.sku as p_sku, 
                                                       pv.id as v_id, pv.size, pv.color, pv.sku as v_sku, pv.cost_price as v_cost, p.cost_price as p_cost
                                                FROM products p
                                                LEFT JOIN product_variants pv ON p.id = pv.product_id
                                                ORDER BY p.name ASC";
                                    $p_stmt = $db->query($p_query);
                                    while ($row = $p_stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $label = $row['p_name'];
                                        $val = "p_" . $row['p_id'];
                                        $cost = $row['p_cost'];
                                        
                                        if ($row['v_id']) {
                                            $label .= " - " . $row['size'] . "/" . $row['color'] . " (" . $row['v_sku'] . ")";
                                            $val = "v_" . $row['v_id'] . "_p_" . $row['p_id'];
                                            $cost = $row['v_cost'] ?: $row['p_cost'];
                                        } else {
                                            $label .= " (" . $row['p_sku'] . ")";
                                        }
                                        
                                        echo "<option value='$val' data-cost='$cost'>$label</option>";
                                    }
                                    ?>
                                </select>
                                <input type="hidden" name="products[0][product_id]" class="h-product-id">
                                <input type="hidden" name="products[0][variant_id]" class="h-variant-id">
                            </td>
                            <td><input type="number" name="products[0][qty]" class="form-control qty-input" value="1" min="1"></td>
                            <td><input type="number" name="products[0][cost]" step="0.01" class="form-control cost-input" value="0.00"></td>
                            <td class="subtotal-text">$0.00</td>
                            <td><button type="button" class="btn btn-danger remove-row"><i class="fas fa-trash"></i></button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                            <td id="grandTotalText" class="fw-bold text-primary">$0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary mb-4" id="addRow">
                <i class="fas fa-plus"></i> Add Another Item
            </button>

            <div class="mt-4 border-top pt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let rowCount = 1;

document.getElementById('addRow').addEventListener('click', function() {
    const tbody = document.querySelector('#poItemsTable tbody');
    const firstRow = tbody.querySelector('tr');
    const newRow = firstRow.cloneNode(true);
    
    // Clear and update names
    newRow.querySelectorAll('input, select').forEach(input => {
        input.name = input.name.replace(/\[\d+\]/, `[${rowCount}]`);
        if (input.tagName === 'INPUT') input.value = input.defaultValue;
    });
    newRow.querySelector('.subtotal-text').textContent = '$0.00';
    
    tbody.appendChild(newRow);
    rowCount++;
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('product-selector')) {
        const row = e.target.closest('tr');
        const val = e.target.value;
        const cost = e.target.options[e.target.selectedIndex].dataset.cost || 0;
        
        row.querySelector('.cost-input').value = cost;
        
        if (val.startsWith('v_')) {
            const parts = val.split('_');
            row.querySelector('.h-variant-id').value = parts[1];
            row.querySelector('.h-product-id').value = parts[3];
        } else {
            row.querySelector('.h-variant-id').value = '';
            row.querySelector('.h-product-id').value = val.split('_')[1];
        }
        calculateTotal();
    }
});

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input') || e.target.classList.contains('cost-input')) {
        calculateTotal();
    }
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) {
        const row = e.target.closest('tr');
        if (document.querySelectorAll('.item-row').length > 1) {
            row.remove();
            calculateTotal();
        }
    }
});

function calculateTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
        const subtotal = qty * cost;
        row.querySelector('.subtotal-text').textContent = '$' + subtotal.toFixed(2);
        grandTotal += subtotal;
    });
    document.getElementById('grandTotalText').textContent = '$' + grandTotal.toFixed(2);
}
</script>

<?php require_once "../includes/footer.php"; ?>
