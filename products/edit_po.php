<?php
// products/edit_po.php
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

// Fetch PO Details
$po_stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
$po_stmt->execute([$po_id]);
$po = $po_stmt->fetch(PDO::FETCH_ASSOC);

if (!$po) {
    echo "Purchase Order not found.";
    exit();
}

// Check if editable (only draft/ordered, or admin)
if (($po['status'] == 'received' || $po['status'] == 'cancelled') && !Auth::hasRole('admin')) {
    echo "This Purchase Order cannot be edited.";
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'];
    $notes = $_POST['notes'];
    $products = $_POST['products']; // Array of items
    
    try {
        $db->beginTransaction();
        
        $total_amount = 0;
        foreach ($products as $p) {
            $total_amount += ($p['qty'] * $p['cost']);
        }
        
        // Update PO
        $po_query = "UPDATE purchase_orders SET supplier_id = ?, notes = ?, total_amount = ? WHERE id = ?";
        $po_stmt = $db->prepare($po_query);
        $po_stmt->execute([$supplier_id, $notes, $total_amount, $po_id]);
        
        // Delete existing items
        $del_stmt = $db->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id = ?");
        $del_stmt->execute([$po_id]);
        
        // Insert new items
        $item_query = "INSERT INTO purchase_order_items (purchase_order_id, product_id, variant_id, quantity_ordered, unit_cost) 
                       VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $db->prepare($item_query);
        
        foreach ($products as $p) {
            if ($p['qty'] <= 0) continue;
            $item_stmt->execute([$po_id, $p['product_id'], $p['variant_id'] ?: null, $p['qty'], $p['cost']]);
        }
        
        $db->commit();
        $_SESSION['message'] = "Purchase Order updated successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: purchase_orders.php");
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Fetch Existing Items
$items_stmt = $db->prepare("
    SELECT poi.*, 
           p.name as p_name, p.sku as p_sku,
           pv.size, pv.color, pv.sku as v_sku
    FROM purchase_order_items poi
    JOIN products p ON poi.product_id = p.id
    LEFT JOIN product_variants pv ON poi.variant_id = pv.id
    WHERE poi.purchase_order_id = ?
");
$items_stmt->execute([$po_id]);
$existing_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Suppliers
$suppliers = $db->query("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-edit"></i> Edit Purchase Order #<?php echo htmlspecialchars($po['order_number']); ?></h1>
    <a href="purchase_orders.php" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back to POs
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" id="poForm">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Supplier *</label>
                    <select name="supplier_id" id="supplier_id" class="form-select" required>
                        <option value="">Choose Supplier</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo ($s['id'] == $po['supplier_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="1"><?php echo htmlspecialchars($po['notes']); ?></textarea>
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
                        <?php foreach ($existing_items as $index => $item): ?>
                        <tr class="item-row">
                            <td>
                                <select name="products[<?php echo $index; ?>][product_selector]" class="form-select product-selector" required>
                                    <option value="">Search Product...</option>
                                    <?php
                                    // Current Item Value
                                    $current_val = $item['variant_id'] 
                                        ? "v_" . $item['variant_id'] . "_p_" . $item['product_id'] 
                                        : "p_" . $item['product_id'];
                                    
                                    $label = $item['p_name'];
                                    if ($item['variant_id']) {
                                        $label .= " - " . $item['size'] . "/" . $item['color'] . " (" . $item['v_sku'] . ")";
                                    } else {
                                        $label .= " (" . $item['p_sku'] . ")";
                                    }
                                    ?>
                                    <option value="<?php echo $current_val; ?>" data-cost="<?php echo $item['unit_cost']; ?>" selected>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                </select>
                                <input type="hidden" name="products[<?php echo $index; ?>][product_id]" class="h-product-id" value="<?php echo $item['product_id']; ?>">
                                <input type="hidden" name="products[<?php echo $index; ?>][variant_id]" class="h-variant-id" value="<?php echo $item['variant_id']; ?>">
                            </td>
                            <td><input type="number" name="products[<?php echo $index; ?>][qty]" class="form-control qty-input" value="<?php echo $item['quantity_ordered']; ?>" min="1"></td>
                            <td><input type="number" name="products[<?php echo $index; ?>][cost]" step="0.01" class="form-control cost-input" value="<?php echo $item['unit_cost']; ?>"></td>
                            <td class="subtotal-text">$<?php echo number_format($item['quantity_ordered'] * $item['unit_cost'], 2); ?></td>
                            <td><button type="button" class="btn btn-danger remove-row"><i class="fas fa-trash"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <?php 
                        $initial_total_qty = 0;
                        foreach ($existing_items as $item) $initial_total_qty += $item['quantity_ordered'];
                        ?>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total Quantity:</td>
                            <td id="totalQtyText" class="fw-bold"><?php echo $initial_total_qty; ?></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                            <td id="grandTotalText" class="fw-bold text-primary">$<?php echo number_format($po['total_amount'], 2); ?></td>
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
                    <i class="fas fa-save"></i> Update Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    let rowCount = <?php echo count($existing_items); ?>;
    let currentSupplierProducts = [];

    // Initialize Select2 on existing selectors
    function initSelect2(element) {
        $(element).select2({
            theme: 'bootstrap-5',
            placeholder: 'Search for a product...',
            width: '100%'
        });
    }

    initSelect2('.product-selector');

    // Load initial products if supplier is selected
    const initialSupplierId = $('#supplier_id').val();
    if (initialSupplierId) {
        fetchProducts(initialSupplierId, false);
    }

    // Handle Supplier Change
    $('#supplier_id').on('change', function() {
        const supplierId = $(this).val();
        if (!supplierId) {
            currentSupplierProducts = [];
            updateAllProductSelectors(true);
            return;
        }

        if (!confirm('Changing the supplier will clear current items. Continue?')) {
            // Reverting would be nice, but for now we proceed or they refresh
            return;
        }
        
        fetchProducts(supplierId, true);
    });

    function fetchProducts(supplierId, clearItems) {
        $.ajax({
            url: '../api/get_supplier_products.php',
            data: { supplier_id: supplierId },
            success: function(res) {
                if (res.success) {
                    currentSupplierProducts = res.data;
                    updateAllProductSelectors(clearItems);
                } else {
                    alert('Error fetching products: ' + res.message);
                }
            }
        });
    }

    function updateAllProductSelectors(clearItems) {
        $('.product-selector').each(function() {
            const selector = $(this);
            const currentValue = selector.val();
            
            selector.empty().append('<option value="">Search Product...</option>');
            
            currentSupplierProducts.forEach(p => {
                const option = new Option(p.text, p.id, false, p.id === currentValue);
                $(option).attr('data-cost', p.cost);
                selector.append(option);
            });
            
            if (clearItems) {
                selector.val('').trigger('change');
            } else {
                selector.trigger('change.select2'); // Update display
            }
        });
        
        if (clearItems) {
            // Remove extra rows if any
            $('#poItemsTable tbody tr:not(:first)').remove();
            calculateTotal();
        }
    }

    $('#addRow').on('click', function() {
        if (!$('#supplier_id').val()) {
            alert('Please select a supplier first.');
            return;
        }

        const tbody = $('#poItemsTable tbody');
        const firstRow = tbody.find('tr:first');
        const newRow = firstRow.clone();
        
        newRow.find('.select2-container').remove();
        newRow.find('select').show().removeClass('select2-hidden-accessible').removeAttr('data-select2-id').find('option').removeAttr('data-select2-id');
        
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
        
        initSelect2(newRow.find('.product-selector'));
        rowCount++;
    });

    $(document).on('change', '.product-selector', function() {
        const row = $(this).closest('tr');
        const val = $(this).val();
        if (!val) {
            row.find('.h-variant-id').val('');
            row.find('.h-product-id').val('');
            row.find('.cost-input').val('0.00');
            calculateTotal();
            return;
        };
        
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
        } else {
            alert("You must have at least one item.");
        }
    });

    function calculateTotal() {
        let grandTotal = 0;
        let totalQty = 0;
        $('.item-row').each(function() {
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            const cost = parseFloat($(this).find('.cost-input').val()) || 0;
            const subtotal = qty * cost;
            $(this).find('.subtotal-text').text('$' + subtotal.toFixed(2));
            grandTotal += subtotal;
            totalQty += qty;
        });
        $('#grandTotalText').text('$' + grandTotal.toFixed(2));
        $('#totalQtyText').text(totalQty);
    }
});
</script>
