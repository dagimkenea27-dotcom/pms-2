<?php
// products/branch_order_receive.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/BranchOrder.php";
require_once "../config/auth.php";

$database = new Database();
$db = $database->getConnection();
$branchOrder = new BranchOrder($db);

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
            $source = trim($_POST['source'] ?? '');
            $products_input = isset($_POST['products']) ? $_POST['products'] : [];

            // Validation
            if (empty($customer_name))
                throw new Exception("Customer name is required.");
            if (empty($phone_number))
                throw new Exception("Phone number is required.");
            if (empty($products_input))
                throw new Exception("At least one product must be added.");

            $processed_products = [];
            $upload_dir = "../assets/uploads/branch_orders/";

            // Ensure directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($products_input as $index => $p) {
                if (empty($p['name']))
                    continue;

                $image_path = null;
                // Handle Image Upload
                if (isset($_FILES['products']['name'][$index]['image']) && $_FILES['products']['error'][$index]['image'] == 0) {
                    $file_name = $_FILES['products']['name'][$index]['image'];
                    $file_tmp = $_FILES['products']['tmp_name'][$index]['image'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                    if (in_array($file_ext, $allowed)) {
                        $new_file_name = "bo_" . uniqid() . "." . $file_ext;
                        $target_path = $upload_dir . $new_file_name;
                        if (move_uploaded_file($file_tmp, $target_path)) {
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

                if (empty($variations)) {
                    throw new Exception("Product '" . $p['name'] . "' must have at least one variation with quantity > 0.");
                }

                $processed_products[] = [
                    'name' => trim($p['name']),
                    'image_path' => $image_path,
                    'variations' => $variations
                ];
            }

            $order_number = $branchOrder->generateOrderNumber();
            $order_data = [
                'order_number' => $order_number,
                'customer_name' => $customer_name,
                'address' => $address,
                'phone_number' => $phone_number,
                'status' => 'pending',
                'notes' => $notes,
                'source' => $source,
                'created_by' => Auth::getCurrentUser()['id']
            ];

            $order_id = $branchOrder->create($order_data, $processed_products);

            if ($order_id) {
                $_SESSION['message'] = "Order #$order_number created successfully with " . count($processed_products) . " products!";
                $_SESSION['message_type'] = "success";
                header("Location: branch_orders_list.php");
                exit();
            } else {
                throw new Exception("Failed to save order to database.");
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
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-plus-circle text-primary"></i> Multi-Item Meta Order</h1>
    <a href="branch_orders_list.php" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-list fa-sm"></i> View Orders
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
        <!-- Left: Order Details -->
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">1. Customer Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="Enter name"
                                required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" name="phone_number" class="form-control" placeholder="Enter phone"
                                required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" placeholder="Enter delivery address">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">How did they find us?</label>
                            <select name="source" class="form-select">
                                <option value="">Select Source</option>
                                <option value="TikTok">TikTok</option>
                                <option value="Facebook">Facebook</option>
                                <option value="Instagram">Instagram</option>
                                <option value="Youtube">Youtube</option>
                                <option value="Tv">Tv</option>
                                <option value="Telegram">Telegram</option>
                                <option value="Referral">Referral</option>
                                <option value="Walk-in">Walk-in</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-info text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">2. Product Requests</h6>
                    <button type="button" class="btn btn-sm btn-light" id="addProduct"><i class="fas fa-plus"></i> Add
                        Another Product</button>
                </div>
                <div class="card-body" id="productsContainer">
                    <!-- Products will be injected here -->
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-secondary">3. Additional Notes</h6>
                </div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="3"
                        placeholder="Any special instructions for the whole order..."></textarea>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-save"></i> Save Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Templates for Dynamic Form -->
<template id="productTemplate">
    <div class="product-block border rounded p-3 mb-4 bg-light" data-index="{PRODUCT_INDEX}">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="text-info"><i class="fas fa-shopping-bag"></i> Product #{PRODUCT_NUMBER}</h5>
            <button type="button" class="btn btn-outline-danger btn-sm remove-product"><i class="fas fa-trash"></i>
                Remove Product</button>
        </div>

        <div class="row mb-3">
            <div class="col-md-8">
                <label class="form-label">Product Name *</label>
                <input type="text" name="products[{PRODUCT_INDEX}][name]" class="form-control"
                    placeholder="e.g. Shein Blue Dress" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Product Image</label>
                <input type="file" name="products[{PRODUCT_INDEX}][image]" class="form-control" accept="image/*">
            </div>
        </div>

        <div class="variations-container mb-2">
            <table class="table table-sm table-bordered bg-white">
                <thead class="table-light">
                    <tr>
                        <th width="25%">Color</th>
                        <th width="25%">Size</th>
                        <th width="15%">Qty *</th>
                        <th width="25%">Options/Notes</th>
                        <th width="10%"></th>
                    </tr>
                </thead>
                <tbody class="variation-body">
                    <!-- Variations go here -->
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-info add-variation"><i class="fas fa-plus"></i> Add
                Size/Color</button>
        </div>
    </div>
</template>

<template id="variationTemplate">
    <tr class="variation-row">
        <td><input type="text" name="products[{PRODUCT_INDEX}][variations][{VAR_INDEX}][color]"
                class="form-control form-control-sm" placeholder="e.g. Blue"></td>
        <td><input type="text" name="products[{PRODUCT_INDEX}][variations][{VAR_INDEX}][size]"
                class="form-control form-control-sm" placeholder="e.g. XL"></td>
        <td><input type="number" name="products[{PRODUCT_INDEX}][variations][{VAR_INDEX}][quantity]"
                class="form-control form-control-sm" value="1" min="1"></td>
        <td><input type="text" name="products[{PRODUCT_INDEX}][variations][{VAR_INDEX}][options]"
                class="form-control form-control-sm" placeholder="e.g. Gift wrap"></td>
        <td class="text-center">
            <button type="button" class="btn btn-link text-danger p-0 remove-variation"><i
                    class="fas fa-times"></i></button>
        </td>
    </tr>
</template>

<style>
    .product-block {
        border-left: 5px solid #36b9cc !important;
    }

    .table-sm td,
    .table-sm th {
        padding: 0.3rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let productCount = 0;
        const container = document.getElementById('productsContainer');
        const productTemplate = document.getElementById('productTemplate').innerHTML;
        const variationTemplate = document.getElementById('variationTemplate').innerHTML;

        function addProduct() {
            const index = productCount++;
            let html = productTemplate
                .replace(/{PRODUCT_INDEX}/g, index)
                .replace(/{PRODUCT_NUMBER}/g, index + 1);

            container.insertAdjacentHTML('beforeend', html);

            const lastProduct = container.lastElementChild;
            addVariation(lastProduct, index); // Add one initial variation row

            // Setup internal variation button
            lastProduct.querySelector('.add-variation').addEventListener('click', function () {
                addVariation(lastProduct, index);
            });

            // Setup remove product button
            lastProduct.querySelector('.remove-product').addEventListener('click', function () {
                if (container.children.length > 1) {
                    lastProduct.remove();
                } else {
                    alert("At least one product is required.");
                }
            });
        }

        function addVariation(productBlock, pIndex) {
            const tbody = productBlock.querySelector('.variation-body');
            const vIndex = tbody.children.length;
            let html = variationTemplate
                .replace(/{PRODUCT_INDEX}/g, pIndex)
                .replace(/{VAR_INDEX}/g, vIndex);

            tbody.insertAdjacentHTML('beforeend', html);

            const lastVar = tbody.lastElementChild;
            lastVar.querySelector('.remove-variation').addEventListener('click', function () {
                if (tbody.children.length > 1) {
                    lastVar.remove();
                }
            });
        }

        document.getElementById('addProduct').addEventListener('click', addProduct);

        // Initialize with one product
        addProduct();
    });
</script>

<?php require_once "../includes/footer.php"; ?>