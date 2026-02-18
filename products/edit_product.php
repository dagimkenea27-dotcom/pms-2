<?php
// products/edit_product.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/AuditLog.php";
require_once "../config/auth.php";

// Ensure user is logged in
Auth::requireLogin();
// Only manager/admin can edit products
Auth::requireRole('manager');

$database = new Database();
$db = $database->getConnection();
$audit = new AuditLog($db);

// Helper functions
require_once "../includes/functions.php";

// Get categories, brands, suppliers for dropdowns
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
$brands = $db->query("SELECT id, name FROM brands ORDER BY name ASC");
$suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name ASC");

$message = '';
$message_type = '';
$errors = [];

// Get product data
$product = null;
if (isset($_GET['id'])) {
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get variants if exist
    $variants = [];
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF Token
    if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
        die("Security error: Invalid CSRF token. Please refresh the page and try again.");
    }
    // Validate input
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $cost_price = floatval($_POST['cost_price'] ?? 0);
    $min_stock = intval($_POST['min_stock'] ?? 0);
    $has_variants = isset($_POST['has_variants']) && $_POST['has_variants'] == 1;
    
    // Validation checks
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    
    if (empty($sku)) {
        $errors[] = "SKU is required.";
    } else {
        // Check if new SKU already exists in products (excluding current)
        $check_query = "SELECT id FROM products WHERE sku = :sku AND id != :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":sku", $sku);
        $check_stmt->bindParam(":id", $product['id']);
        $check_stmt->execute();
        
        // Check if SKU exists in variants (any variant)
        $check_v_query = "SELECT id FROM product_variants WHERE sku = :sku";
        $check_v_stmt = $db->prepare($check_v_query);
        $check_v_stmt->bindParam(":sku", $sku);
        $check_v_stmt->execute();
        
        if ($check_stmt->rowCount() > 0 || $check_v_stmt->rowCount() > 0) {
            $errors[] = "SKU '$sku' already exists (in products or variants). Please use a unique SKU.";
        }
    }
    
    if ($price < 0) {
        $errors[] = "Selling price cannot be negative.";
    }
    
    if ($cost_price < 0) {
        $errors[] = "Cost price cannot be negative.";
    }
    
    if ($min_stock < 0) {
        $errors[] = "Minimum stock level cannot be negative.";
    }
    
    // Process Variants Data
    $submitted_variants = [];
    $total_qty = 0;
    $used_skus = [$sku];
    
    if ($has_variants) {
         if (isset($_POST['variant_size']) && is_array($_POST['variant_size'])) {
            for ($i = 0; $i < count($_POST['variant_size']); $i++) {
                $v_id = $_POST['variant_id'][$i] ?? null; // ID for existing variants
                $v_size = trim($_POST['variant_size'][$i]);
                $v_color = trim($_POST['variant_color'][$i]);
                $v_qty = intval($_POST['variant_qty'][$i]);
                $v_price = floatval($_POST['variant_price'][$i]);
                $v_cost = floatval($_POST['variant_cost'][$i] ?? 0);
                $v_location = trim($_POST['variant_location'][$i] ?? '');
                $v_sku = trim($_POST['variant_sku'][$i]);

                if (empty($v_size) && empty($v_color)) {
                    continue; 
                }
                
                if (empty($v_sku)) {
                     $v_sku = generateSKU($db, $used_skus);
                }
                $used_skus[] = $v_sku;

                // Check duplicate SKU among variants in this submit
                foreach ($submitted_variants as $existing_v) {
                     if ($existing_v['sku'] == $v_sku || ($existing_v['size'] == $v_size && $existing_v['color'] == $v_color)) {
                         $errors[] = "Duplicate variant (Option/Color or SKU) within this product: " . $v_sku;
                         break;
                     }
                }

                // Check duplicate SKU in DB (variants table) - exclude current variant if editing
                if ($v_id) {
                    // Editing existing variant - check if SKU exists for OTHER variants
                    $v_check = $db->prepare("SELECT id FROM product_variants WHERE sku = ? AND id != ?");
                    $v_check->execute([$v_sku, $v_id]);
                } else {
                    // Adding new variant - check if SKU exists anywhere in variants
                    $v_check = $db->prepare("SELECT id FROM product_variants WHERE sku = ?");
                    $v_check->execute([$v_sku]);
                }
                
                // Check duplicate SKU in DB (products table) - exclude current parent product IF checking against parent (rare case if inputing same SKU)
                // Actually, a variant SKU should NOT match distinct product SKUs.
                // It specifically shouldn't match ANY product SKU ideally, but technically if it matches its OWN parent that *might* be confusing but is sometimes allowed in some systems. 
                // However, user requested "solid" relationship, usually implies globally unique SKU.
                $p_check = $db->prepare("SELECT id FROM products WHERE sku = ?");
                $p_check->execute([$v_sku]);

                if ($v_check->rowCount() > 0 || $p_check->rowCount() > 0) {
                     $errors[] = "Variant SKU '$v_sku' already exists (in products or variants).";
                }

                $submitted_variants[] = [
                    'id' => $v_id,
                    'size' => $v_size,
                    'color' => $v_color,
                    'qty' => $v_qty,
                    'price' => $v_price > 0 ? $v_price : null,
                    'cost' => $v_cost > 0 ? $v_cost : null,
                    'location' => $v_location,
                    'sku' => $v_sku
                ];
                $total_qty += $v_qty;
            }
        }
    } else {
        // If switching to simple, take quantity from main input
    }
    
    // Handle image upload
    $image_path = $product['image']; // Keep existing image by default
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = '../uploads/products/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_name = $_FILES['product_image']['name'];
        $file_size = $_FILES['product_image']['size'];
        $file_tmp = $_FILES['product_image']['tmp_name'];
        
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
        elseif ($file_size > $max_size) {
            $errors[] = "File size too large. Maximum file size is 5MB.";
        }
        else {
            $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $target_file)) {
                $image_path = 'uploads/products/' . $new_filename;
                // Delete old image
                if (!empty($product['image']) && file_exists('../' . $product['image'])) {
                    unlink('../' . $product['image']);
                }
            } else {
                $errors[] = "Error uploading image file.";
            }
        }
    }
    
    // If no errors, proceed with update
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
            $brand_id = !empty($_POST['brand_id']) ? $_POST['brand_id'] : null;
            $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
            
            $category_name = getName($db, 'categories', $category_id);
            $supplier_name = getName($db, 'suppliers', $supplier_id);

            $final_quantity = $product['quantity']; // Default to current
            if ($has_variants) {
                $final_quantity = $total_qty;
            } else {
                $final_quantity = intval($_POST['quantity']); // Simple product quantity
            }

            $query = "UPDATE products SET 
                      sku = :sku, 
                      name = :name, 
                      description = :description, 
                      category = :category, 
                      category_id = :category_id,
                      brand_id = :brand_id,
                      quantity = :quantity,
                      price = :price, 
                      cost_price = :cost_price, 
                      min_stock = :min_stock, 
                      supplier = :supplier, 
                      supplier_id = :supplier_id,
                      location = :location,
                      image = :image,
                      barcode = :barcode,
                      has_variants = :has_variants,
                      status = :status
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(":sku", $sku);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $_POST['description']);
            $stmt->bindParam(":category", $category_name);
            $stmt->bindParam(":category_id", $category_id);
            $stmt->bindParam(":brand_id", $brand_id);
            $stmt->bindParam(":quantity", $final_quantity);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":cost_price", $cost_price);
            $stmt->bindParam(":min_stock", $min_stock);
            $stmt->bindParam(":supplier", $supplier_name);
            $stmt->bindParam(":supplier_id", $supplier_id);
            $stmt->bindParam(":location", $_POST['location']);
            $stmt->bindParam(":image", $image_path);
            $stmt->bindParam(":barcode", $sku); 
            $has_variants_int = $has_variants ? 1 : 0;
            $stmt->bindParam(":has_variants", $has_variants_int);
            $status = $_POST['status'] ?? 'Active';
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":id", $product['id']);
            
            $stmt->execute();

            // Handle Variants
            if ($has_variants) {
                // Get existing variant IDs to detect deletions
                $existing_ids = [];
                foreach ($variants as $v) $existing_ids[] = $v['id'];
                
                $processed_ids = [];

                $upsert_sql = "INSERT INTO product_variants (id, product_id, sku, size, color, quantity, price, cost_price, location, min_stock) 
                               VALUES (:id, :pid, :sku, :size, :color, :qty, :price, :cost_price, :location, :min_stock)
                               ON DUPLICATE KEY UPDATE 
                               sku = VALUES(sku),
                               size = VALUES(size),
                               color = VALUES(color),
                               quantity = VALUES(quantity),
                               price = VALUES(price),
                               cost_price = VALUES(cost_price),
                               location = VALUES(location),
                               min_stock = VALUES(min_stock)";
                $upsert = $db->prepare($upsert_sql);

                foreach ($submitted_variants as $sv) {
                    // Handle the case where ID might be empty (new variants)
                    $variant_id = !empty($sv['id']) ? $sv['id'] : null;
                    
                    $upsert->execute([
                        ':id' => $variant_id,
                        ':pid' => $product['id'],
                        ':sku' => $sv['sku'],
                        ':size' => $sv['size'],
                        ':color' => $sv['color'],
                        ':qty' => $sv['qty'],
                        ':price' => $sv['price'],
                        ':cost_price' => $sv['cost'],
                        ':location' => $sv['location'],
                        ':min_stock' => $min_stock
                    ]);
                    
                    // Audit Log for stock quantity change in variant
                    if ($variant_id) {
                        // Find original variant for comparison
                        foreach ($variants as $old_v) {
                            if ($old_v['id'] == $variant_id && $old_v['quantity'] != $sv['qty']) {
                                logStockChange($db, $product['id'], $variant_id, Auth::getCurrentUser()['id'], 'adjustment', $old_v['quantity'], $sv['qty'], 'Updated via product edit page');
                                break;
                            }
                        }
                    } else {
                        // New variant - log initial stock as adjustment or in
                        $new_v_id = $db->lastInsertId();
                        if ($sv['qty'] != 0) {
                            logStockChange($db, $product['id'], $new_v_id, Auth::getCurrentUser()['id'], 'in', 0, $sv['qty'], 'Initial stock via product edit');
                        }
                    }
                    
                    if ($variant_id) {
                        $processed_ids[] = $variant_id;
                    } else {
                        $new_id = $db->lastInsertId();
                        $processed_ids[] = $new_id;
                    }
                }

                // Delete removed variants
                $to_delete = array_diff($existing_ids, $processed_ids);
                if (!empty($to_delete)) {
                    $ids_str = implode(',', $to_delete);
                    // Safe because integers
                    $db->exec("DELETE FROM product_variants WHERE id IN ($ids_str)");
                }

            } else {
                // If switched to simple, delete all variants
                if ($product['has_variants']) {
                    $db->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product['id']]);
                    // Logic to handle audit log for the main product's new simple quantity
                }
                
                if ($product['quantity'] != $final_quantity) {
                    logStockChange($db, $product['id'], null, Auth::getCurrentUser()['id'], 'adjustment', $product['quantity'], $final_quantity, 'Updated via product edit page');
                }
            }

            $db->commit();

            $message = "Product updated successfully!";
            $message_type = "success";
            
            // Redirect to view products with the product ID as active
            header("Location: view_products.php?active_id=" . $product['id']);
            exit();
        } catch (PDOException $exception) {
            if ($db->inTransaction()) $db->rollBack();
            $message = "Error: " . $exception->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = "Please correct the following errors:";
        $message_type = "danger";
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
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-edit"></i> Edit Product</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="view_products.php" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
        <button type="submit" form="editProductForm" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-save"></i> Save Product
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show shadow-sm" role="alert">
    <?php echo $message; ?>
    <?php if (!empty($errors)): ?>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row gx-3">
    <!-- Main Content: Identity & Variants -->
    <div class="col-xl-8 col-lg-7">
        <form method="POST" action="" enctype="multipart/form-data" id="editProductForm">
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
                                <input type="text" class="form-control" id="sku" name="sku" required value="<?php echo htmlspecialchars($product['sku']); ?>">
                                <button class="btn btn-outline-secondary" type="button" id="generateSKU"><i class="fas fa-random"></i></button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label fw-bold small text-muted text-uppercase">Product Name *</label>
                            <input type="text" class="form-control form-control-sm" id="name" name="name" required value="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label fw-bold small text-muted text-uppercase">Category</label>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories->execute();
                                    while ($row = $categories->fetch(PDO::FETCH_ASSOC)): 
                                        $selected = ($product['category_id'] == $row['id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row['name']); ?></option>
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
                                    $brands->execute();
                                    while ($row = $brands->fetch(PDO::FETCH_ASSOC)): 
                                        $selected = ($product['brand_id'] == $row['id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <a href="../brands/add.php" class="btn btn-outline-secondary"><i class="fas fa-plus"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="description" class="form-label fw-bold small text-muted text-uppercase">Description</label>
                        <textarea class="form-control form-control-sm" id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    <div class="mt-3">
                        <label for="status" class="form-label fw-bold small text-muted text-uppercase">Status</label>
                        <select class="form-select form-select-sm" id="status" name="status">
                            <option value="Active" <?php echo ($product['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($product['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="Draft" <?php echo ($product['status'] == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Variants Table Card -->
            <div id="variantsSection" class="card shadow-sm mb-4" <?php echo $product['has_variants'] ? '' : 'style="display:none;"'; ?>>
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
                                <?php if (!empty($variants)): ?>
                                    <?php foreach ($variants as $index => $variant): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <input type="hidden" name="variant_id[]" value="<?php echo $variant['id']; ?>">
                                            <input type="text" class="form-control form-control-sm border-0 bg-light" name="variant_size[]" value="<?php echo htmlspecialchars($variant['size']); ?>" placeholder="Size">
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm border-0 bg-light" name="variant_color[]" value="<?php echo htmlspecialchars($variant['color']); ?>" placeholder="Color"></td>
                                        <td><input type="text" class="form-control form-control-sm border-0 bg-light variant-sku" name="variant_sku[]" value="<?php echo htmlspecialchars($variant['sku']); ?>" placeholder="Auto SKU"></td>
                                        <td><input type="number" class="form-control form-control-sm border-0 bg-light" name="variant_qty[]" value="<?php echo $variant['quantity']; ?>" min="0" style="width: 70px;"></td>
                                        <td><input type="number" class="form-control form-control-sm border-0 bg-light" name="variant_cost[]" step="0.01" value="<?php echo $variant['cost_price'] ?? ''; ?>" placeholder="0.00" style="width: 80px;"></td>
                                        <td><input type="number" class="form-control form-control-sm border-0 bg-light" name="variant_price[]" step="0.01" value="<?php echo $variant['price']; ?>" style="width: 80px;"></td>
                                        <td><input type="text" class="form-control form-control-sm border-0 bg-light" name="variant_location[]" value="<?php echo htmlspecialchars($variant['location'] ?? ''); ?>" placeholder="Bin"></td>
                                        <td class="text-center"><button type="button" class="btn btn-link text-danger btn-sm remove-variant"><i class="fas fa-trash"></i></button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                    <input class="form-check-input" type="checkbox" id="has_variants" name="has_variants" value="1" form="editProductForm" <?php echo $product['has_variants'] ? 'checked' : ''; ?>>
                    <label class="form-check-label small fw-bold" for="has_variants">Variants</label>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold small text-muted mb-1">COST PRICE</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" class="form-control" name="cost_price" form="editProductForm" value="<?php echo $product['cost_price']; ?>">
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold small text-muted mb-1 text-primary">SELLING PRICE</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" class="form-control border-primary" name="price" form="editProductForm" value="<?php echo $product['price']; ?>">
                        </div>
                    </div>
                </div>
                <div class="row g-2 mb-3" id="simpleProductFields" <?php echo $product['has_variants'] ? 'style="display:none;"' : ''; ?>>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted mb-1">CURRENT QUANTITY</label>
                        <input type="number" class="form-control form-control-sm border-info" name="quantity" form="editProductForm" value="<?php echo $product['quantity']; ?>">
                    </div>
                </div>
                <div class="row g-2 mb-3 text-center">
                    <div class="col-6 text-start">
                        <label class="form-label fw-bold small text-muted mb-1">LOW STOCK LEVEL</label>
                        <input type="number" class="form-control form-control-sm" name="min_stock" form="editProductForm" value="<?php echo $product['min_stock']; ?>">
                    </div>
                    <div class="col-6 text-start">
                        <label class="form-label fw-bold small text-muted mb-1">WAREHOUSE LOC.</label>
                        <input type="text" class="form-control form-control-sm" name="location" form="editProductForm" value="<?php echo htmlspecialchars($product['location']); ?>" placeholder="A-1">
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold small text-muted mb-1 text-uppercase">Primary Supplier</label>
                    <div class="input-group input-group-sm">
                        <select class="form-select" name="supplier_id" form="editProductForm">
                            <option value="">Select Supplier</option>
                            <?php 
                            $suppliers->execute();
                            while ($row = $suppliers->fetch(PDO::FETCH_ASSOC)): 
                                $selected = ($product['supplier_id'] == $row['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row['name']); ?></option>
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
                <?php if (!empty($product['image'])): ?>
                    <?php 
                        $img_src = (strpos($product['image'], 'http') === 0) ? $product['image'] : '../' . $product['image'];
                    ?>
                    <img src="<?php echo htmlspecialchars($img_src); ?>" class="img-fluid rounded mb-3 border shadow-sm" style="max-height: 180px;" onerror="this.src='../assets/img/noproduct.png'">
                <?php else: ?>
                    <img src="../assets/img/noproduct.png" class="img-fluid rounded mb-3 border shadow-sm" style="max-height: 180px;">
                <?php endif; ?>
                <input type="file" class="form-control form-control-sm" name="product_image" form="editProductForm" accept="image/*">
            </div>
        </div>

        <!-- Meta Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-2 small">
                <div class="d-flex justify-content-between text-muted mb-1"><span>Created:</span><span><?php echo date('M j, Y', strtotime($product['created_at'])); ?></span></div>
                <div class="d-flex justify-content-between text-muted mb-1"><span>Last Update:</span><span><?php echo date('M j, Y', strtotime($product['updated_at'])); ?></span></div>
                <div class="d-flex justify-content-between"><span>Barcode:</span><code><?php echo htmlspecialchars($product['barcode']); ?></code></div>
            </div>
        </div>

        <!-- Sticky Save Button -->
        <div class="sticky-bottom pb-4 shadow-top">
            <div class="card shadow border-0 bg-primary">
                <div class="card-body p-2">
                    <button type="submit" form="editProductForm" class="btn btn-primary w-100 fw-bold border-0 shadow-none">
                        <i class="fas fa-save me-2"></i> UPDATE PRODUCT DATA
                    </button>
                </div>
            </div>
            <a href="view_products.php" class="btn btn-link w-100 btn-sm text-secondary mt-1">Discard Changes</a>
        </div>
    </div>
</div>

<script>
// Toggle variants section
document.querySelectorAll('#has_variants').forEach(el => {
    el.addEventListener('change', function() {
        const variantsSection = document.getElementById('variantsSection');
        const simpleProductFields = document.getElementById('simpleProductFields');
        const isChecked = this.checked;
        
        // Sync both checkboxes if multiple exist
        document.querySelectorAll('#has_variants').forEach(cb => cb.checked = isChecked);
        
        if (isChecked) {
            variantsSection.style.display = 'block';
            simpleProductFields.style.display = 'none';
        } else {
            variantsSection.style.display = 'none';
            simpleProductFields.style.display = 'block';
        }
    });
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

// Add variant row
document.getElementById('addVariantRow').addEventListener('click', function() {
    const tbody = document.querySelector('#variantsTable tbody');
    const newRow = document.createElement('tr');
    
    newRow.innerHTML = `
        <td class="ps-3"><input type="hidden" name="variant_id[]" value=""><input type="text" class="form-control form-control-sm border-0 bg-light" name="variant_size[]" placeholder="Size"></td>
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
    const mainPrice = document.querySelector('input[name="price"]').value;
    if (mainPrice) {
        const pInput = newRow.querySelector('input[name="variant_price[]"]');
        pInput.value = mainPrice;
        pInput.classList.add('auto-filled');
    }
});

// Remove variant row
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-variant')) {
        const tbody = document.querySelector('#variantsTable tbody');
        if (tbody.children.length > 1) {
            e.target.closest('tr').remove();
        } else {
            alert('You must have at least one variant if variants are enabled.');
        }
    }
});

// Bulk SKU generate button
document.getElementById('generateSKU').addEventListener('click', function() {
    if (confirm('Regenerate all SKUs?')) {
        const used = [];
        const main = document.getElementById('sku');
        main.value = generateJS_SKU(used);
        used.push(main.value);
        document.querySelectorAll('.variant-sku').forEach(input => {
            input.value = generateJS_SKU(used);
            used.push(input.value);
        });
    }
});
</script>

<?php require_once "../includes/footer.php"; ?>