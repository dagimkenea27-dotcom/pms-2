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
if ($_POST) {
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
                      has_variants = :has_variants
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
            $stmt->bindParam(":id", $product['id']);
            
            $stmt->execute();

            // Handle Variants
            if ($has_variants) {
                // Get existing variant IDs to detect deletions
                $existing_ids = [];
                foreach ($variants as $v) $existing_ids[] = $v['id'];
                
                $processed_ids = [];

                $upsert_sql = "INSERT INTO product_variants (id, product_id, sku, size, color, quantity, price, min_stock) 
                               VALUES (:id, :pid, :sku, :size, :color, :qty, :price, :min_stock)
                               ON DUPLICATE KEY UPDATE 
                               sku = VALUES(sku),
                               size = VALUES(size),
                               color = VALUES(color),
                               quantity = VALUES(quantity),
                               price = VALUES(price),
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
                        ':min_stock' => $min_stock
                    ]);
                    
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
                $db->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product['id']]);
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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-edit"></i> Edit Product</h1>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
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

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="editProductForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="sku" name="sku" required 
                                           value="<?php echo htmlspecialchars($product['sku']); ?>">
                                    <button class="btn btn-outline-secondary" type="button" id="generateSKU" title="Generate New Numeric SKU">
                                        <i class="fas fa-random"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <div class="input-group">
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">Select Category</option>
                                        <?php 
                                        $categories->execute();
                                        while ($row = $categories->fetch(PDO::FETCH_ASSOC)): 
                                            $selected = ($product['category_id'] == $row['id']) ? 'selected' : '';
                                            if (!$selected && empty($product['category_id']) && $product['category'] == $row['name']) $selected = 'selected';
                                        ?>
                                            <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <a href="../categories/add.php" class="btn btn-outline-secondary" title="Add New Category"><i class="fas fa-plus"></i></a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Brand</label>
                                <div class="input-group">
                                    <select class="form-select" id="brand_id" name="brand_id">
                                        <option value="">Select Brand</option>
                                        <?php 
                                        $brands->execute();
                                        while ($row = $brands->fetch(PDO::FETCH_ASSOC)): 
                                            $selected = ($product['brand_id'] == $row['id']) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <a href="../brands/add.php" class="btn btn-outline-secondary" title="Add New Brand"><i class="fas fa-plus"></i></a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="product_image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                                <?php if (!empty($product['image'])): ?>
                                    <div class="mt-2">
                                        <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="img-thumbnail" style="max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="has_variants" name="has_variants" value="1" <?php echo $product['has_variants'] ? 'checked' : ''; ?>>
                                <label class="form-check-label font-weight-bold" for="has_variants">Product has variants (Option/Color)</label>
                            </div>

                            <!-- Simple Product Fields -->
                            <div id="simpleProductFields" <?php echo $product['has_variants'] ? 'style="display:none;"' : ''; ?>>
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity *</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" 
                                           value="<?php echo $product['quantity']; ?>" min="0">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="cost_price" class="form-label">Cost Price ($)</label>
                                <input type="number" step="0.01" class="form-control" id="cost_price" 
                                       name="cost_price" value="<?php echo $product['cost_price']; ?>" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label">Selling Price ($)</label>
                                <input type="number" step="0.01" class="form-control" id="price" 
                                       name="price" value="<?php echo $product['price']; ?>" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="min_stock" class="form-label">Minimum Stock Level</label>
                                <input type="number" class="form-control" id="min_stock" name="min_stock" 
                                       value="<?php echo $product['min_stock']; ?>" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Supplier</label>
                                <div class="input-group">
                                    <select class="form-select" id="supplier_id" name="supplier_id">
                                        <option value="">Select Supplier</option>
                                        <?php 
                                        $suppliers->execute();
                                        while ($row = $suppliers->fetch(PDO::FETCH_ASSOC)): 
                                            $selected = ($product['supplier_id'] == $row['id']) ? 'selected' : '';
                                            if (!$selected && empty($product['supplier_id']) && $product['supplier'] == $row['name']) $selected = 'selected';
                                        ?>
                                            <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <a href="../suppliers/add_supplier.php" class="btn btn-outline-secondary" title="Add New Supplier"><i class="fas fa-plus"></i></a>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label">Storage Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($product['location']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Variants Section -->
                    <div id="variantsSection" <?php echo $product['has_variants'] ? '' : 'style="display:none;"'; ?>>
                        <hr>
                        <h5 class="mb-3">Product Variants</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="variantsTable">
                                <thead>
                                    <tr>
                                        <th>Size/Option</th>
                                        <th>Color</th>
                                        <th>SKU</th>
                                        <th>Quantity</th>
                                        <th>Price ($)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($variants)): ?>
                                        <?php foreach ($variants as $index => $variant): ?>
                                        <tr>
                                            <td><input type="hidden" name="variant_id[]" value="<?php echo $variant['id']; ?>"><input type="text" class="form-control" name="variant_size[]" value="<?php echo htmlspecialchars($variant['size']); ?>" placeholder="e.g., Small"></td>
                                            <td><input type="text" class="form-control" name="variant_color[]" value="<?php echo htmlspecialchars($variant['color']); ?>" placeholder="e.g., Red"></td>
                                            <td><input type="text" class="form-control" name="variant_sku[]" value="<?php echo htmlspecialchars($variant['sku']); ?>" placeholder="Auto-generated"></td>
                                            <td><input type="number" class="form-control" name="variant_qty[]" value="<?php echo $variant['quantity']; ?>" min="0"></td>
                                            <td><input type="number" class="form-control" name="variant_price[]" step="0.01" value="<?php echo $variant['price']; ?>" placeholder="Same as main"></td>
                                            <td><button type="button" class="btn btn-danger remove-variant"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td><input type="hidden" name="variant_id[]" value=""><input type="text" class="form-control" name="variant_size[]" placeholder="e.g., Small"></td>
                                            <td><input type="text" class="form-control" name="variant_color[]" placeholder="e.g., Red"></td>
                                            <td><input type="text" class="form-control" name="variant_sku[]" placeholder="Auto-generated"></td>
                                            <td><input type="number" class="form-control" name="variant_qty[]" value="0" min="0"></td>
                                            <td><input type="number" class="form-control" name="variant_price[]" step="0.01" placeholder="Same as main"></td>
                                            <td><button type="button" class="btn btn-danger remove-variant"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6">
                                            <button type="button" class="btn btn-outline-primary" id="addVariantRow">
                                                <i class="fas fa-plus"></i> Add Another Variant
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                        <a href="view_products.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Product Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($product['created_at'])); ?></p>
                <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($product['updated_at'])); ?></p>
                <?php if (!empty($product['barcode'])): ?>
                    <p><strong>Barcode:</strong> <?php echo htmlspecialchars($product['barcode']); ?></p>
                <?php endif; ?>
            </div>
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
    } else {
        variantsSection.style.display = 'none';
        simpleProductFields.style.display = 'block';
    }
});

// Add variant row
document.getElementById('addVariantRow').addEventListener('click', function() {
    const tbody = document.querySelector('#variantsTable tbody');
    const newRow = document.createElement('tr');
    
    newRow.innerHTML = `
        <td><input type="hidden" name="variant_id[]" value=""><input type="text" class="form-control" name="variant_size[]" placeholder="e.g., Small"></td>
        <td><input type="text" class="form-control" name="variant_color[]" placeholder="e.g., Red"></td>
        <td><input type="text" class="form-control" name="variant_sku[]" placeholder="Auto-generated"></td>
        <td><input type="number" class="form-control" name="variant_qty[]" value="0" min="0"></td>
        <td><input type="number" class="form-control" name="variant_price[]" step="0.01" placeholder="Same as main"></td>
        <td><button type="button" class="btn btn-danger remove-variant"><i class="fas fa-trash"></i></button></td>
    `;
    
    tbody.appendChild(newRow);
    
    // Add event listener to the new remove button
    newRow.querySelector('.remove-variant').addEventListener('click', function() {
        if (tbody.children.length > 1) {
            tbody.removeChild(newRow);
        } else {
            alert('You must have at least one variant row.');
        }
    });
    
    // Apply auto-fill to the new price input
    const newPriceInput = newRow.querySelector('input[name="variant_price[]"]');
    if (newPriceInput) {
        // Auto-fill with main price if it's empty
        const mainPrice = document.getElementById('price').value;
        if (!newPriceInput.value && mainPrice) {
            newPriceInput.value = mainPrice;
            newPriceInput.classList.add('auto-filled');
        }
        
        // Add event listener to remove auto-filled class on manual input
        newPriceInput.addEventListener('input', function() {
            this.classList.remove('auto-filled');
        });
    }
});

// Remove variant row
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-variant')) {
        const button = e.target.closest('.remove-variant');
        const row = button.closest('tr');
        const tbody = document.querySelector('#variantsTable tbody');
        
        if (tbody.children.length > 1) {
            tbody.removeChild(row);
        } else {
            alert('You must have at least one variant row.');
        }
    }
});

// Auto-fill variant prices from main product price
function updateVariantPrices() {
    const mainPrice = document.getElementById('price').value;
    const variantPriceInputs = document.querySelectorAll('input[name="variant_price[]"]');
    
    // Set price for all variant rows that don't have a manual value
    variantPriceInputs.forEach(input => {
        // Only auto-fill if the field is empty or if it was auto-filled before
        if (!input.value || input.classList.contains('auto-filled')) {
            input.value = mainPrice;
            // Only add the class if the field was empty (not for existing values)
            if (!input.value) {
                input.classList.add('auto-filled');
            }
        }
    });
}

// Call once on page load to populate initial values
document.addEventListener('DOMContentLoaded', function() {
    // Only auto-fill if we're adding new variants (no existing prices)
    const hasExistingPrices = Array.from(document.querySelectorAll('input[name="variant_price[]"]'))
        .some(input => input.value && !isNaN(parseFloat(input.value)));
    
    if (!hasExistingPrices) {
        updateVariantPrices();
    }
});

// Update when main price changes
document.getElementById('price').addEventListener('input', updateVariantPrices);

// Remove auto-fill class when user manually changes variant price
document.addEventListener('input', function(e) {
    if (e.target.name === 'variant_price[]') {
        e.target.classList.remove('auto-filled');
    }
});

// Add event listeners to existing variant price inputs
document.addEventListener('DOMContentLoaded', function() {
    const variantPriceInputs = document.querySelectorAll('input[name="variant_price[]"]');
    variantPriceInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('auto-filled');
        });
    });
});
// Generate SKU
document.getElementById('generateSKU').addEventListener('click', function() {
    if (confirm('Are you sure you want to generate a new SKU? This will replace the existing one.')) {
        const skuInput = document.getElementById('sku');
        // Generate 11 digits: Last 5 of Timestamp + 6 Random
        const timestamp = Math.floor(Date.now() / 1000).toString().slice(-5); 
        const random = Math.floor(Math.random() * 1000000).toString().padStart(6, '0');
        const code11 = timestamp + '' + random;
        
        // Calculate Check Digit
        let sum = 0;
        for (let i = 0; i < 11; i++) {
            const digit = parseInt(code11[i]);
            if ((i + 1) % 2 !== 0) { // Odd position
                sum += digit * 3;
            } else {
                sum += digit;
            }
        }
        const mod = sum % 10;
        const checkDigit = (mod === 0) ? 0 : (10 - mod);
        
        skuInput.value = code11 + '' + checkDigit;
    }
});
</script>

<?php require_once "../includes/footer.php"; ?>