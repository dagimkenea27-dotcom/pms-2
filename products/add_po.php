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

<!-- Add Select2 CSS or other styles before the form if needed -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

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
                            <th width="45%">Product / Variant</th>
                            <th width="15%">Quantity</th>
                            <th width="15%">Unit Cost ($)</th>
                            <th width="15%">Subtotal</th>
                            <th width="10%">Action</th>
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
                                            $cost = ($row['v_cost'] && $row['v_cost'] > 0) ? $row['v_cost'] : $row['p_cost'];
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

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    let rowCount = 1;

    // Initialize Select2 on existing selectors
    function initSelect2(element) {
        $(element).select2({
            theme: 'bootstrap-5',
            placeholder: 'Search for a product...',
            width: '100%'
        });
    }

    initSelect2('.product-selector');

    $('#addRow').on('click', function() {
        const tbody = $('#poItemsTable tbody');
        const firstRow = tbody.find('tr:first');
        
        // Destroy select2 on the row we're cloning to avoid issues
        // firstRow.find('.product-selector').select2('destroy');
        
        const newRow = firstRow.clone();
        
        // Remove select2 container from the cloned row if it exists
        newRow.find('.select2-container').remove();
        newRow.find('select').show().removeClass('select2-hidden-accessible').removeAttr('data-select2-id').find('option').removeAttr('data-select2-id');
        
        // Update names and reset values
        newRow.find('input, select').each(function() {
            const name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/\[\d+\]/, `[${rowCount}]`));
            }
            if ($(this).is('input')) $(this).val($(this).prop('defaultValue'));
            if ($(this).is('select')) $(this).val('');
        });
        
        newRow.find('.subtotal-text').text('$0.00');
        tbody.append(newRow);
        
        // Re-init Select2 on the new row's selector
        initSelect2(newRow.find('.product-selector'));
        
        rowCount++;
    });

    $(document).on('change', '.product-selector', function() {
        const row = $(this).closest('tr');
        const val = $(this).val();
        if (!val) return;
        
        const cost = $(this).find('option:selected').data('cost') || 0;
        row.find('.cost-input').val(parseFloat(cost).toFixed(2));
        
        if (val.startsWith('v_')) {
            const parts = val.split('_');
            row.find('.h-variant-id').val(parts[1]);
            row.find('.h-product-id').val(parts[3]);
        } else {
            row.find('.h-variant-id').val('');
            row.find('.h-product-id').val(val.split('_')[1]);
        }
        calculateTotal();
    });

    $(document).on('input', '.qty-input, .cost-input', function() {
        calculateTotal();
    });

    $(document).on('click', '.remove-row', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        }
    });

    function calculateTotal() {
        let grandTotal = 0;
        $('.item-row').each(function() {
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            const cost = parseFloat($(this).find('.cost-input').val()) || 0;
            const subtotal = qty * cost;
            $(this).find('.subtotal-text').text('$' + subtotal.toFixed(2));
            grandTotal += subtotal;
        });
        $('#grandTotalText').text('$' + grandTotal.toFixed(2));
    }
});
</script>

<?php require_once "../includes/footer.php"; ?>
