<?php
// products/add_product.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/AuditLog.php";
require_once "../config/auth.php";

// Ensure user is logged in
Auth::requireLogin();
// Only manager/admin can add products
Auth::requireRole('manager');

$database = new Database();
$db = $database->getConnection();
$audit = new AuditLog($db);

$message = '';
$message_type = '';
$errors = [];


// Helper functions
require_once "../includes/functions.php";


// Handle AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if ($_POST) {
        // CSRF Check
        if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'errors' => ['Security token expired. Please refresh the page.']]);
            exit();
        }

        // Validate input
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $cost_price = floatval($_POST['cost_price'] ?? 0);
        $min_stock = intval($_POST['min_stock'] ?? 0);
        $has_variants = isset($_POST['has_variants']) && $_POST['has_variants'] == '1';
        $location = trim($_POST['location'] ?? '');
        
        $response = ['success' => false, 'message' => '', 'errors' => []];
        
        // Validation checks
        if (empty($name)) {
            $response['errors'][] = "Product name is required.";
        }
        
        $used_skus = [];
        if (empty($sku)) {
            $sku = generateSKU($db);
            $used_skus[] = $sku;
        } else {
            $used_skus[] = $sku;
            // Check if SKU already exists in products table
            $check_query = "SELECT id FROM products WHERE sku = :sku";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":sku", $sku);
            $check_stmt->execute();
            
            // Check if SKU exists in product_variants table
            $check_v_query = "SELECT id FROM product_variants WHERE sku = :sku";
            $check_v_stmt = $db->prepare($check_v_query);
            $check_v_stmt->bindParam(":sku", $sku);
            $check_v_stmt->execute();
            
            if ($check_stmt->rowCount() > 0 || $check_v_stmt->rowCount() > 0) {
                $response['errors'][] = "SKU '$sku' already exists (in products or variants). Please use a unique SKU.";
            }
        }

        // Validate variants
        $variants = [];
        if ($has_variants) {
            if (isset($_POST['variant_size']) && is_array($_POST['variant_size'])) {
                for ($i = 0; $i < count($_POST['variant_size']); $i++) {
                    $v_size = trim($_POST['variant_size'][$i]);
                    $v_color = trim($_POST['variant_color'][$i]);
                    $v_qty = intval($_POST['variant_qty'][$i]);
                    $v_price = floatval($_POST['variant_price'][$i]);
                    $v_cost = floatval($_POST['variant_cost'][$i] ?? 0);
                    $v_location = trim($_POST['variant_location'][$i] ?? '');
                    $v_sku = trim($_POST['variant_sku'][$i]);

                    if (empty($v_size) && empty($v_color)) {
                        continue; // Skip empty rows
                    }
                    
                    if (empty($v_sku)) {
                         $v_sku = generateSKU($db, $used_skus);
                    }
                    $used_skus[] = $v_sku;

                    // Check duplicate SKU among variants in this submit
                    foreach ($variants as $existing_v) {
                         if ($existing_v['sku'] == $v_sku || ($existing_v['size'] == $v_size && $existing_v['color'] == $v_color)) {
                             $response['errors'][] = "Duplicate variant (Option/Color or SKU) within this product: " . $v_sku;
                             break;
                         }
                    }

                    // Check duplicate SKU in DB (variants table)
                    $v_check = $db->prepare("SELECT id FROM product_variants WHERE sku = ?");
                    $v_check->execute([$v_sku]);
                    
                    // Check duplicate SKU in DB (products table)
                    $p_check = $db->prepare("SELECT id FROM products WHERE sku = ?");
                    $p_check->execute([$v_sku]);
                    
                    if ($v_check->rowCount() > 0 || $p_check->rowCount() > 0) {
                        $response['errors'][] = "Variant SKU '$v_sku' already exists (in products or variants).";
                    }

                    $variants[] = [
                        'size' => $v_size,
                        'color' => $v_color,
                        'qty' => $v_qty,
                        'price' => $v_price > 0 ? $v_price : null,
                        'cost' => $v_cost > 0 ? $v_cost : null,
                        'location' => $v_location,
                        'sku' => $v_sku
                    ];
                }
            }
            if (empty($variants)) {
                 $response['errors'][] = "Please add at least one variant.";
            }

            // For products with variants, master quantity is sum of variants
            $quantity = 0;
            foreach ($variants as $v) {
                $quantity += $v['qty'];
            }
        }
        
        if ($quantity < 0) {
            $response['errors'][] = "Quantity cannot be negative.";
        }
        
        if ($price < 0) {
            $response['errors'][] = "Selling price cannot be negative.";
        }
        
        if ($cost_price < 0) {
            $response['errors'][] = "Cost price cannot be negative.";
        }
        
        if ($min_stock < 0) {
            $response['errors'][] = "Minimum stock level cannot be negative.";
        }
        
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $upload_dir = '../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_name = $_FILES['product_image']['name'];
            $file_size = $_FILES['product_image']['size'];
            $file_tmp = $_FILES['product_image']['tmp_name'];
            
            // Get file extension
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate file
            if (!in_array($file_ext, $allowed_types)) {
                $response['errors'][] = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
            }
            
            if ($file_size > $max_size) {
                $response['errors'][] = "File size too large. Maximum file size is 5MB.";
            }
            
            // If no errors, process the file
            if (empty($response['errors'])) {
                // Generate unique filename
                $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $image_path = 'uploads/products/' . $new_filename;
                } else {
                    $response['errors'][] = "Error uploading image file.";
                }
            }
        }
        
        // If no errors, proceed with insertion
        if (empty($response['errors'])) {
            try {
                $db->beginTransaction();

                // Get names for backward compatibility
                $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
                $brand_id = !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null;
                $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
                
                $category_name = getName($db, 'categories', $category_id);
                $supplier_name = getName($db, 'suppliers', $supplier_id);

                $query = "INSERT INTO products 
                          (sku, name, description, category, category_id, brand_id, quantity, price, cost_price, min_stock, supplier, supplier_id, location, image, barcode, has_variants) 
                          VALUES 
                          (:sku, :name, :description, :category, :category_id, :brand_id, :quantity, :price, :cost_price, :min_stock, :supplier, :supplier_id, :location, :image, :barcode, :has_variants)";
                
                $stmt = $db->prepare($query);
                
                $stmt->bindParam(":sku", $sku);
                $stmt->bindParam(":name", $name);
                $stmt->bindParam(":description", $_POST['description']);
                $stmt->bindParam(":category", $category_name);
                $stmt->bindParam(":category_id", $category_id, PDO::PARAM_INT);
                $stmt->bindParam(":brand_id", $brand_id, PDO::PARAM_INT);
                $stmt->bindParam(":quantity", $quantity);
                $stmt->bindParam(":price", $price);
                $stmt->bindParam(":cost_price", $cost_price);
                $stmt->bindParam(":min_stock", $min_stock);
                $stmt->bindParam(":supplier", $supplier_name);
                $stmt->bindParam(":supplier_id", $supplier_id, PDO::PARAM_INT);
                $stmt->bindParam(":location", $location);
                $stmt->bindParam(":image", $image_path);
                $stmt->bindParam(":barcode", $sku); // Use SKU as barcode by default
                $has_variants_int = $has_variants ? 1 : 0;
                $stmt->bindParam(":has_variants", $has_variants_int);
                
                if ($stmt->execute()) {
                    $product_id = $db->lastInsertId();

                    if ($has_variants) {
                        $v_stmt = $db->prepare("INSERT INTO product_variants (product_id, sku, size, color, quantity, price, cost_price, location, min_stock) VALUES (:pid, :sku, :size, :color, :qty, :price, :cost_price, :location, :min_stock)");
                        foreach ($variants as $v) {
                            $v_stmt->execute([
                                ':pid' => $product_id,
                                ':sku' => $v['sku'],
                                ':size' => $v['size'],
                                ':color' => $v['color'],
                                ':qty' => $v['qty'],
                                ':price' => $v['price'],
                                ':cost_price' => $v['cost'],
                                ':location' => $v['location'],
                                ':min_stock' => $min_stock
                            ]);
                            $variant_id = $db->lastInsertId();

                            // Log Audit Trail & Movement
                            logStockChange($db, $product_id, $variant_id, Auth::getCurrentUser()['id'], 'in', 0, $v['qty'], 'Initial stock', null, null, $supplier_id);
                        }
                    } else {
                        // Log Audit Trail & Movement
                        logStockChange($db, $product_id, null, Auth::getCurrentUser()['id'], 'in', 0, $quantity, 'Initial stock', null, null, $supplier_id);
                    }

                    $db->commit();

                    $response['success'] = true;
                    $response['message'] = "Product added successfully!";
                    
                    // Log to AuditLog
                    if (Auth::isLoggedIn()) {
                        $user = Auth::getCurrentUser();
                        $audit->log($user['id'], "PRODUCT_ADD", "Added product: " . $name . " (SKU: " . $sku . ")");
                    }
                } else {
                    $db->rollBack();
                    $response['errors'][] = "Error adding product.";
                }
            } catch (PDOException $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $response['errors'][] = "Error: " . $exception->getMessage();
            }
        }
        
        echo json_encode($response);
        exit();
    }
}


require_once "../includes/header.php";
?>
<style>
    .gx-3 > div { transition: all 0.3s ease; }
    .card { border: none; }
    .border-dashed { border-style: dashed !important; }
    .auto-filled { background-color: #e8f0fe !important; border-bottom: 2px solid #0d6efd !important; }
    .shadow-top { box-shadow: 0 -0.5rem 1rem rgba(0,0,0,0.05); }
    .sticky-bottom { position: sticky; bottom: 0; background: #f8f9fc; z-index: 1020; padding-top: 10px; }
    #variantsTable thead th { font-size: 0.7rem; letter-spacing: 0.05em; }
    #variantsTable td { padding: 4px; }
    .form-label.small { font-size: 0.75rem; }
    .profit-margin-badge { font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-plus-circle"></i> Add New Product</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="view_products.php" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
        <button type="submit" form="addProductForm" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-save"></i> Save Product
        </button>
    </div>
</div>

<div id="alertContainer"></div>

<div class="row gx-3">
    <!-- Main Content: Identity & Variants -->
    <div class="col-xl-8 col-lg-7">
        <form method="POST" action="" enctype="multipart/form-data" id="addProductForm">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
            
            <!-- Product Identity Card -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-id-card me-2"></i>Product Identity</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="sku" class="form-label fw-bold small text-muted text-uppercase">SKU *</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="sku" name="sku" placeholder="Auto-gen if empty">
                                <button class="btn btn-outline-secondary start-barcode-scanner" type="button" title="Scan Barcode"><i class="fas fa-barcode"></i></button>
                                <button class="btn btn-outline-secondary" type="button" id="generateSKU"><i class="fas fa-random"></i></button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label fw-bold small text-muted text-uppercase">Product Name *</label>
                            <input type="text" class="form-control form-control-sm" id="name" name="name" required placeholder="Enter product name">
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label fw-bold small text-muted text-uppercase">Category</label>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
                                    while ($row = $categories->fetch(PDO::FETCH_ASSOC)): 
                                    ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <a href="../categories/add.php" class="btn btn-outline-secondary"><i class="fas fa-plus"></i></a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="brand_id" class="form-label fw-bold small text-muted text-uppercase">Brand</label>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="brand_id" name="brand_id">
                                    <option value="">Select Brand</option>
                                    <?php 
                                    $brands = $db->query("SELECT id, name FROM brands ORDER BY name ASC");
                                    while ($row = $brands->fetch(PDO::FETCH_ASSOC)): 
                                    ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <a href="../brands/add.php" class="btn btn-outline-secondary"><i class="fas fa-plus"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label for="description" class="form-label fw-bold small text-muted text-uppercase">Description</label>
                        <textarea class="form-control form-control-sm" id="description" name="description" rows="3" placeholder="Brief product description..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Variants Table Card -->
            <div id="variantsSection" class="card shadow-sm mb-4" style="display:none;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-layer-group me-2"></i>Product Variants</h6>
                    <button type="button" class="btn btn-sm btn-info text-white" id="addVariantRow">
                        <i class="fas fa-plus me-1"></i> Add Variant
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle" id="variantsTable">
                            <thead class="bg-light">
                                <tr class="small text-muted text-uppercase">
                                    <th class="ps-3 border-0">Size/Option</th>
                                    <th class="border-0">Color</th>
                                    <th class="border-0">SKU</th>
                                    <th class="border-0">Qty</th>
                                    <th class="border-0">Cost Price</th>
                                    <th class="border-0">Price</th>
                                    <th class="border-0">Location</th>
                                    <th class="border-0 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Variant rows added via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Sidebar: Pricing & Media -->
    <div class="col-xl-4 col-lg-5">
        <!-- Pricing & Inventory Card -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-money-bill-wave me-2"></i>Pricing & Stock</h6>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="has_variants" name="has_variants" value="1" form="addProductForm">
                    <label class="form-check-label small fw-bold" for="has_variants">Variants</label>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold small text-muted mb-1">COST PRICE</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" form="addProductForm" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold small text-muted mb-1 text-primary">SELLING PRICE</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" class="form-control border-primary" id="price" name="price" form="addProductForm" placeholder="0.00">
                        </div>
                        <div id="profitMarginText" class="mt-1"></div>
                    </div>
                </div>
                
                <div class="row g-2 mb-3" id="simpleProductFields">
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted mb-1">INITIAL QUANTITY</label>
                        <input type="number" class="form-control form-control-sm border-info" name="quantity" form="addProductForm" value="0" min="0">
                    </div>
                </div>
                
                <div class="row g-2 mb-3 text-center">
                    <div class="col-6 text-start">
                        <label class="form-label fw-bold small text-muted mb-1">LOW STOCK LEVEL</label>
                        <input type="number" class="form-control form-control-sm" name="min_stock" form="addProductForm" value="5">
                    </div>
                    <div class="col-6 text-start">
                        <label class="form-label fw-bold small text-muted mb-1">WAREHOUSE LOC.</label>
                        <input type="text" class="form-control form-control-sm" id="location" name="location" form="addProductForm" placeholder="A-1">
                    </div>
                </div>
                
                <div class="mb-0">
                    <label class="form-label fw-bold small text-muted mb-1 text-uppercase">Primary Supplier</label>
                    <div class="input-group input-group-sm">
                        <select class="form-select" id="supplier_id" name="supplier_id" form="addProductForm">
                            <option value="">Select Supplier</option>
                            <?php 
                            $suppliers = $db->query("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name ASC");
                            while ($row = $suppliers->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <a href="../suppliers/add_supplier.php" class="btn btn-outline-secondary"><i class="fas fa-plus"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Media Card -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-image me-2"></i>Product Media</h6>
            </div>
            <div class="card-body text-center">
                <div id="imagePreview" class="bg-light p-4 rounded mb-3 border border-dashed">
                    <i class="fas fa-camera fa-2x text-muted"></i>
                </div>
                <input type="file" class="form-control form-control-sm" id="product_image" name="product_image" form="addProductForm" accept="image/*">
            </div>
        </div>

        <!-- Sticky Save Button -->
        <div class="sticky-bottom pb-4 shadow-top">
            <div class="card shadow border-0 bg-primary">
                <div class="card-body p-2">
                    <button type="submit" form="addProductForm" class="btn btn-primary w-100 fw-bold border-0 shadow-none" id="submitBtn">
                        <i class="fas fa-save me-2"></i> CREATE NEW PRODUCT
                    </button>
                </div>
            </div>
            <a href="view_products.php" class="btn btn-link w-100 btn-sm text-secondary mt-1">Cancel and Exit</a>
        </div>
    </div>
</div>

<script>
// Toggle variants section
document.getElementById('has_variants').addEventListener('change', function() {
    const variantsSection = document.getElementById('variantsSection');
    const simpleProductFields = document.getElementById('simpleProductFields');
    
    if (this.checked) {
        variantsSection.style.display = 'block';
        simpleProductFields.style.display = 'none';
        // Add initial row if empty
        if (document.querySelector('#variantsTable tbody').children.length === 0) {
            addVariantRow();
        }
    } else {
        variantsSection.style.display = 'none';
        simpleProductFields.style.display = 'block';
    }
});

// Helper to get all currently used SKUs in the UI
function getAllUsedSkus() {
    const skus = [];
    const mainSku = document.getElementById('sku').value;
    if (mainSku) skus.push(mainSku);
    document.querySelectorAll('.variant-sku').forEach(input => {
        if (input.value) skus.push(input.value);
    });
    return skus;
}

// SKU generation logic
function generateJS_SKU(excludes = []) {
    const timestamp = Math.floor(Date.now() / 1000).toString().slice(-5); 
    const random = Math.floor(Math.random() * 1000000).toString().padStart(6, '0');
    const code11 = timestamp + '' + random;
    let sum = 0;
    for (let i = 0; i < 11; i++) {
        const digit = parseInt(code11[i]);
        if ((i + 1) % 2 !== 0) sum += digit * 3;
        else sum += digit;
    }
    const mod = sum % 10;
    const checkDigit = (mod === 0) ? 0 : (10 - mod);
    const sku = code11 + '' + checkDigit;
    if (excludes.includes(sku)) return generateJS_SKU(excludes);
    return sku;
}

// Add variant row function
function addVariantRow() {
    const tbody = document.querySelector('#variantsTable tbody');
    const newRow = document.createElement('tr');
    
    newRow.innerHTML = `
        <td class="ps-3"><input type="text" class="form-control form-control-sm border-0 bg-light" name="variant_size[]" placeholder="Size"></td>
        <td><input type="text" class="form-control form-control-sm border-0 bg-light" name="variant_color[]" placeholder="Color"></td>
        <td><input type="text" class="form-control form-control-sm border-0 bg-light variant-sku" name="variant_sku[]" placeholder="Auto SKU"></td>
        <td><input type="number" class="form-control form-control-sm border-0 bg-light" name="variant_qty[]" value="0" min="0" style="width: 70px;"></td>
        <td><input type="number" class="form-control form-control-sm border-0 bg-light" name="variant_cost[]" step="0.01" placeholder="0.00" style="width: 80px;"></td>
        <td><input type="number" class="form-control form-control-sm border-0 bg-light" name="variant_price[]" step="0.01" placeholder="0.00" style="width: 80px;"></td>
        <td><input type="text" class="form-control form-control-sm border-0 bg-light" name="variant_location[]" placeholder="Bin"></td>
        <td class="text-center"><button type="button" class="btn btn-link text-danger btn-sm remove-variant"><i class="fas fa-trash"></i></button></td>
    `;
    
    tbody.appendChild(newRow);
    const usedSkus = getAllUsedSkus();
    newRow.querySelector('.variant-sku').value = generateJS_SKU(usedSkus);
    
    // Auto-fill price
    const mainPrice = document.getElementById('price').value;
    if (mainPrice) {
        const pInput = newRow.querySelector('input[name="variant_price[]"]');
        pInput.value = mainPrice;
        pInput.classList.add('auto-filled');
    }
}

document.getElementById('addVariantRow').addEventListener('click', addVariantRow);

// Remove variant row
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-variant')) {
        const tbody = document.querySelector('#variantsTable tbody');
        if (tbody.children.length > 1) {
            e.target.closest('tr').remove();
        } else {
            alert('At least one variant row is required.');
        }
    }
});

// SKU generate button
document.getElementById('generateSKU').addEventListener('click', function() {
    const used = [];
    const main = document.getElementById('sku');
    main.value = generateJS_SKU(used);
    used.push(main.value);
    document.querySelectorAll('.variant-sku').forEach(input => {
        const val = generateJS_SKU(used);
        input.value = val;
        used.push(val);
    });
});

// Profit Margin Calculation
function calculateProfitMargin() {
    const price = parseFloat(document.getElementById('price').value) || 0;
    const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;
    const marginText = document.getElementById('profitMarginText');
    
    if (costPrice > 0 && price > 0) {
        const profit = price - costPrice;
        const margin = (profit / price) * 100;
        const color = margin > 0 ? 'success' : 'danger';
        marginText.innerHTML = `<span class="badge bg-${color} profit-margin-badge">Profit: $${profit.toFixed(2)} (${margin.toFixed(2)}%)</span>`;
    } else {
        marginText.innerHTML = '';
    }
}

document.getElementById('price').addEventListener('input', calculateProfitMargin);
document.getElementById('cost_price').addEventListener('input', calculateProfitMargin);

// Sync variant prices
document.getElementById('price').addEventListener('input', function() {
    const mainPrice = this.value;
    document.querySelectorAll('input[name="variant_price[]"]').forEach(input => {
        if (!input.value || input.classList.contains('auto-filled')) {
            input.value = mainPrice;
            input.classList.add('auto-filled');
        }
    });
});

// Image Preview
document.getElementById('product_image').addEventListener('change', function() {
    const preview = document.getElementById('imagePreview');
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded shadow-sm" style="max-height: 150px;">`;
            preview.classList.remove('p-4');
        }
        reader.readAsDataURL(file);
    }
});

// Handle AJAX submission
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    const formData = new FormData(this);
    
    fetch('', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        const alertContainer = document.getElementById('alertContainer');
        if (data.success) {
            alertContainer.innerHTML = `<div class="alert alert-success shadow-sm"><strong>Success!</strong> ${data.message}</div>`;
            setTimeout(() => window.location.href = 'view_products.php', 1500);
        } else {
            btn.disabled = false;
            btn.innerHTML = originalText;
            let errorList = data.errors.map(err => `<li>${err}</li>`).join('');
            alertContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                    <strong>Please correct the following:</strong>
                    <ul class="mb-0 mt-2">${errorList}</ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        console.error('Error:', error);
        alert('An unexpected error occurred. Please check console.');
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>
