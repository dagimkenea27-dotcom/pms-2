<?php
// products/branch_order_edit.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/BranchOrder.php";
require_once "../config/auth.php";

$database = new Database();
$db = $database->getConnection();
$branchOrderModel = new BranchOrder($db);

if (!isset($_GET['id'])) {
    header("Location: branch_orders_list.php");
    exit();
}

$id = $_GET['id'];
$order = $branchOrderModel->getById($id);

if (!$order) {
    header("Location: branch_orders_list.php");
    exit();
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
        $message = "Security Error: Invalid Token";
        $message_type = "danger";
    } else {
        try {
            $customer_name = trim($_POST['customer_name']);
            $phone_number = trim($_POST['phone_number']);
            $address = trim($_POST['address']);
            $notes = trim($_POST['notes']);
            $status = $_POST['status'];
            $products_input = isset($_POST['products']) ? $_POST['products'] : [];

            if (empty($customer_name)) throw new Exception("Customer name is required.");
            if (empty($phone_number)) throw new Exception("Phone number is required.");

            $processed_products = [];
            $upload_dir = "../assets/uploads/branch_orders/";

            foreach ($products_input as $index => $p) {
                if (empty($p['name'])) continue;

                // Keep existing image if no new one is uploaded
                $image_path = isset($p['existing_image']) ? $p['existing_image'] : null;

                // Handle New Image Upload
                if (isset($_FILES['products']['name'][$index]['image']) && $_FILES['products']['error'][$index]['image'] == 0) {
                    $file_name = $_FILES['products']['name'][$index]['image'];
                    $file_tmp = $_FILES['products']['tmp_name'][$index]['image'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                    if (in_array($file_ext, $allowed)) {
                        $new_file_name = "bo_" . uniqid() . "." . $file_ext;
                        if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                            // Delete old image if it exists and changed
                            if ($image_path && file_exists("../" . $image_path)) {
                                unlink("../" . $image_path);
                            }
                            $image_path = "assets/uploads/branch_orders/" . $new_file_name;
                        }
                    }
                }

                $variations = [];
                if (isset($p['variations'])) {
                    foreach ($p['variations'] as $v) {
                        if (intval($v['quantity']) > 0) {
                            $variations[] = [
                                'color' => trim($v['color']),
                                'size' => trim($v['size']),
                                'quantity' => intval($v['quantity']),
                                'options' => trim($v['options'] ?? '')
                            ];
                        }
                    }
                }

                $processed_products[] = [
                    'name' => trim($p['name']),
                    'image_path' => $image_path,
                    'variations' => $variations
                ];
            }

            $order_data = [
                'customer_name' => $customer_name,
                'address' => $address,
                'phone_number' => $phone_number,
                'status' => $status,
                'notes' => $notes
            ];

            if ($branchOrderModel->update($id, $order_data, $processed_products)) {
                $_SESSION['message'] = "Order updated successfully!";
                $_SESSION['message_type'] = "success";
                header("Location: branch_orders_list.php");
                exit();
            } else {
                throw new Exception("Failed to update order.");
            }

        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-edit text-primary"></i> Edit Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
    <a href="branch_orders_list.php" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back to List
    </a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" id="orderForm">
    <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">1. Customer Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" name="customer_name" class="form-control" value="<?php echo htmlspecialchars($order['customer_name']); ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($order['phone_number']); ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="returned" <?php echo $order['status'] == 'returned' ? 'selected' : ''; ?>>Returned</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($order['address']); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-info text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">2. Product Requests</h6>
                    <button type="button" class="btn btn-sm btn-light" id="addProduct"><i class="fas fa-plus"></i> Add Another Product</button>
                </div>
                <div class="card-body" id="productsContainer">
                    <!-- Products will be injected here by JS -->
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-secondary">3. Additional Notes</h6>
                </div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($order['notes']); ?></textarea>
                    
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-save"></i> Update Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- JS Templates (Same as receive form) -->
<template id="productTemplate">
    <div class="product-block border rounded p-3 mb-4 bg-light" data-index="{PRODUCT_INDEX}">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="text-info"><i class="fas fa-shopping-bag"></i> Product #{PRODUCT_NUMBER}</h5>
            <button type="button" class="btn btn-outline-danger btn-sm remove-product"><i class="fas fa-trash"></i> Remove Product</button>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Product Name *</label>
                <input type="text" name="products[{PRODUCT_INDEX}][name]" class="form-control product-name-input" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Product Image</label>
                <div class="d-flex gap-2 align-items-center">
                    <div class="current-image-preview"></div>
                    <input type="file" name="products[{PRODUCT_INDEX}][image]" class="form-control" accept="image/*">
                    <input type="hidden" name="products[{PRODUCT_INDEX}][existing_image]" class="existing-image-input">
                </div>
            </div>
        </div>

        <div class="variations-container mb-2">
            <table class="table table-sm table-bordered bg-white">
                <thead class="table-light">
                    <tr>
                        <th width="25%">Color</th>
                        <th width="25%">Size</th>
                        <th width="15%">Qty *</th>
                        <th width="25%">Options</th>
                        <th width="10%"></th>
                    </tr>
                </thead>
                <tbody class="variation-body"></tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-info add-variation"><i class="fas fa-plus"></i> Add Size/Color</button>
        </div>
    </div>
</template>

<template id="variationTemplate">
    <tr class="variation-row">
        <td><input type="text" name="products[{PRODUCT_INDEX}][variations][{VAR_INDEX}][color]" class="form-control form-control-sm var-color"></td>
        <td><input type="text" name="products[{PRODUCT_INDEX}][variations][{VAR_INDEX}][size]" class="form-control form-control-sm var-size"></td>
        <td><input type="number" name="products[{PRODUCT_INDEX}][variations][{VAR_INDEX}][quantity]" class="form-control form-control-sm var-qty" min="1"></td>
        <td><input type="text" name="products[{PRODUCT_INDEX}][variations][{VAR_INDEX}][options]" class="form-control form-control-sm var-options"></td>
        <td class="text-center">
            <button type="button" class="btn btn-link text-danger p-0 remove-variation"><i class="fas fa-times"></i></button>
        </td>
    </tr>
</template>

<script>
const existingData = <?php echo json_encode($order['products']); ?>;

document.addEventListener('DOMContentLoaded', function() {
    let productCount = 0;
    const container = document.getElementById('productsContainer');
    const productTemplate = document.getElementById('productTemplate').innerHTML;
    const variationTemplate = document.getElementById('variationTemplate').innerHTML;

    function addProduct(pData = null) {
        const index = productCount++;
        let html = productTemplate
            .replace(/{PRODUCT_INDEX}/g, index)
            .replace(/{PRODUCT_NUMBER}/g, index + 1);
        
        container.insertAdjacentHTML('beforeend', html);
        const lastProduct = container.lastElementChild;

        if (pData) {
            lastProduct.querySelector('.product-name-input').value = pData.product_name;
            if (pData.image_path) {
                lastProduct.querySelector('.existing-image-input').value = pData.image_path;
                lastProduct.querySelector('.current-image-preview').innerHTML = `<img src="../${pData.image_path}" style="height: 38px; width: 38px; object-fit: cover;" class="rounded border">`;
            }
            
            pData.variations.forEach(v => addVariation(lastProduct, index, v));
        } else {
            addVariation(lastProduct, index);
        }
        
        lastProduct.querySelector('.add-variation').addEventListener('click', () => addVariation(lastProduct, index));
        lastProduct.querySelector('.remove-product').addEventListener('click', () => {
            if (container.children.length > 1) lastProduct.remove();
            else alert("At least one product is required.");
        });
    }

    function addVariation(productBlock, pIndex, vData = null) {
        const tbody = productBlock.querySelector('.variation-body');
        const vIndex = tbody.children.length;
        let html = variationTemplate
            .replace(/{PRODUCT_INDEX}/g, pIndex)
            .replace(/{VAR_INDEX}/g, vIndex);
        
        tbody.insertAdjacentHTML('beforeend', html);
        const lastVar = tbody.lastElementChild;

        if (vData) {
            lastVar.querySelector('.var-color').value = vData.color;
            lastVar.querySelector('.var-size').value = vData.size;
            lastVar.querySelector('.var-qty').value = vData.quantity;
            lastVar.querySelector('.var-options').value = vData.options;
        }

        lastVar.querySelector('.remove-variation').addEventListener('click', () => {
            if (tbody.children.length > 1) lastVar.remove();
        });
    }

    document.getElementById('addProduct').addEventListener('click', () => addProduct());
    
    // Load existing data
    if (existingData.length > 0) {
        existingData.forEach(p => addProduct(p));
    } else {
        addProduct();
    }
});
</script>

<style>
    .product-block { border-left: 5px solid #36b9cc !important; }
    .table-sm td, .table-sm th { padding: 0.3rem; }
</style>

<?php require_once "../includes/footer.php"; ?>
