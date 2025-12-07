<?php
// products/edit_product.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/AuditLog.php";
require_once "../config/auth.php";

// Ensure user is logged in
Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();
$audit = new AuditLog($db);

// Get categories, brands, suppliers for dropdowns
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
$brands = $db->query("SELECT id, name FROM brands ORDER BY name ASC");
$suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name ASC");

$message = '';
$message_type = '';
$errors = [];

// Helper to get name from ID - Moved outside POST handler to prevent redefinition errors
function getName($db, $table, $id) {
    if (!$id) return null;
    $stmt = $db->prepare("SELECT name FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['name'] : null;
}

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
    $has_variants = isset($_POST['has_variants']) && $_POST['has_variants'] == '1';
    
    // Validation checks
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    
    if (empty($sku)) {
        $errors[] = "SKU is required.";
    } else if ($sku != $product['sku']) {
        // Check if new SKU already exists
        $check_query = "SELECT id FROM products WHERE sku = :sku AND id != :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":sku", $sku);
        $check_stmt->bindParam(":id", $product['id']);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $errors[] = "SKU already exists. Please use a unique SKU.";
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
                     $v_sku = $sku . '-' . strtoupper(substr($v_size ? $v_size : 'X', 0, 3)) . '-' . strtoupper(substr($v_color ? $v_color : 'X', 0, 3)) . '-' . ($i+1);
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
                    $upsert->execute([
                        ':id' => $sv['id'], // If null, inserts. If set, updates.
                        ':pid' => $product['id'],
                        ':sku' => $sv['sku'],
                        ':size' => $sv['size'],
                        ':color' => $sv['color'],
                        ':qty' => $sv['qty'],
                        ':price' => $sv['price'],
                        ':min_stock' => $min_stock
                    ]);
                    
                    if ($sv['id']) {
                        $processed_ids[] = $sv['id'];
                    } else {
                        $new_id = $db->lastInsertId();
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
            
            // Refresh product data
            $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->bindParam(":id", $product['id']);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            // Refresh variants
            if ($product['has_variants']) {
                $v_stmt = $db->prepare("SELECT * FROM product_variants WHERE product_id = :pid");
                $v_stmt->execute([':pid' => $product['id']]);
                $variants = $v_stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $variants = [];
            }

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
                                <input type="text" class="form-control" id="sku" name="sku" required 
                                       value="<?php echo htmlspecialchars($product['sku']); ?>">
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

                            <div class="mb-3" id="mainQuantityDiv" style="<?php echo $product['has_variants'] ? 'display:none;' : ''; ?>">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       value="<?php echo $product['quantity']; ?>" <?php echo $product['has_variants'] ? 'readonly' : ''; ?>>
                                <?php if ($product['has_variants']): ?>
                                <div class="form-text">Managed by variants</div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="cost_price" class="form-label">Cost Price ($)</label>
                                <input type="number" step="0.01" class="form-control" id="cost_price" 
                                       name="cost_price" value="<?php echo $product['cost_price']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label">Selling Price ($)</label>
                                <input type="number" step="0.01" class="form-control" id="price" 
                                       name="price" value="<?php echo $product['price']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="min_stock" class="form-label">Minimum Stock Level</label>
                                <input type="number" class="form-control" id="min_stock" name="min_stock" 
                                       value="<?php echo $product['min_stock']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Supplier</label>
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
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($product['location']); ?>">
                            </div>
                        </div>
                    </div>
                     <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <!-- Variants Section -->
                    <div id="variantsSection" style="<?php echo $product['has_variants'] ? '' : 'display:none;'; ?>" class="row mt-3">
                        <div class="col-12">
                            <hr>
                            <h6 class="font-weight-bold text-primary mb-3">Product Variants</h6>
                            <div class="alert alert-info py-2 small">
                                <i class="fas fa-info-circle"></i> Price and SKU will be auto-filled from main product details. You can override them.
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="variantsTable">
                                    <thead>
                                        <tr>
                                            <th>Option (Size)</th>
                                            <th>Color</th>
                                            <th>Quantity</th>
                                            <th>Price (Override)</th>
                                            <th>SKU</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($variants)): ?>
                                            <?php foreach ($variants as $v): ?>
                                                <tr class="variant-row">
                                                    <input type="hidden" name="variant_id[]" value="<?php echo $v['id']; ?>">
                                                    <td><input type="text" class="form-control form-control-sm" name="variant_size[]" value="<?php echo htmlspecialchars($v['size']); ?>" placeholder="Option/Size"></td>
                                                    <td><input type="text" class="form-control form-control-sm" name="variant_color[]" value="<?php echo htmlspecialchars($v['color']); ?>" placeholder="Color"></td>
                                                    <td><input type="number" class="form-control form-control-sm variant-qty" name="variant_qty[]" value="<?php echo $v['quantity']; ?>" min="0"></td>
                                                    <td><input type="number" step="0.01" class="form-control form-control-sm variant-price" name="variant_price[]" value="<?php echo $v['price']; ?>" placeholder="Uses Main Price"></td>
                                                    <td><input type="text" class="form-control form-control-sm variant-sku" name="variant_sku[]" value="<?php echo htmlspecialchars($v['sku']); ?>"></td>
                                                    <td><button type="button" class="btn btn-danger btn-sm remove-variant"><i class="fas fa-trash"></i></button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="variant-row">
                                                <input type="hidden" name="variant_id[]" value="">
                                                <td><input type="text" class="form-control form-control-sm" name="variant_size[]" placeholder="Option/Size"></td>
                                                <td><input type="text" class="form-control form-control-sm" name="variant_color[]" placeholder="Color"></td>
                                                <td><input type="number" class="form-control form-control-sm variant-qty" name="variant_qty[]" value="0" min="0"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm variant-price" name="variant_price[]" placeholder="Uses Main Price"></td>
                                                <td><input type="text" class="form-control form-control-sm variant-sku" name="variant_sku[]" placeholder="Auto-gen"></td>
                                                <td><button type="button" class="btn btn-danger btn-sm remove-variant"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-success btn-sm" id="addVariantBtn"><i class="fas fa-plus"></i> Add Variant</button>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Product
                            </button>
                            <a href="view_products.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Products
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Info cards logic -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="fas fa-info-circle"></i> Info</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Total Quantity:</strong><br>
                    <span class="h4"><?php echo $product['quantity']; ?></span>
                </div>
                 <div class="text-center">
                    <a href="update_stock.php?id=<?php echo $product['id']; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-warehouse"></i> Update Stock
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle Variants
const hasVariantsCheckbox = document.getElementById('has_variants');
const mainQuantityDiv = document.getElementById('mainQuantityDiv');
const variantsSection = document.getElementById('variantsSection');
const quantityInput = document.getElementById('quantity');

hasVariantsCheckbox.addEventListener('change', function() {
    if (this.checked) {
        mainQuantityDiv.style.display = 'none';
        variantsSection.style.display = 'block';
        quantityInput.readOnly = true;
    } else {
        mainQuantityDiv.style.display = 'block';
        variantsSection.style.display = 'none';
        quantityInput.readOnly = false;
    }
});

// Update variant prices when main price changes
function updateVariantPrices() {
    const mainPrice = document.getElementById('price').value;
    const variantPriceInputs = document.querySelectorAll('input[name="variant_price[]"]');
    
    // Set price for all variant rows
    variantPriceInputs.forEach(input => {
        // Only auto-fill if the field is empty or if it's the first row and hasn't been manually changed
        if (!input.value || input.classList.contains('auto-filled')) {
            input.value = mainPrice;
            input.classList.add('auto-filled');
        }
    });
}

document.getElementById('price').addEventListener('input', updateVariantPrices);

// Initialize variant prices on page load
document.addEventListener('DOMContentLoaded', function() {
    updateVariantPrices();
    
    // Add event listeners to variant price inputs to remove auto-filled class when manually changed
    document.querySelectorAll('input[name="variant_price[]"]').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('auto-filled');
        });
    });
});

// Add Variant Row
document.getElementById('addVariantBtn').addEventListener('click', function() {
    const tbody = document.querySelector('#variantsTable tbody');
    let row = tbody.querySelector('.variant-row').cloneNode(true);
    
    // Get main defaults
    const mainPrice = document.getElementById('price').value;
    const mainSku = document.getElementById('sku').value;
    
    // Clear inputs and set defaults
    row.querySelectorAll('input').forEach(input => {
        if (input.name.includes('qty')) {
             input.value = '0';
        } else if (input.name.includes('variant_price')) {
             input.value = mainPrice; // Auto-fill
             input.classList.add('auto-filled');
             // Add event listener to remove auto-filled class when manually changed
             input.addEventListener('input', function() {
                 this.classList.remove('auto-filled');
             });
        } else if (input.name.includes('variant_sku')) {
             if (mainSku) input.value = mainSku + '-VAR'; 
             else input.value = '';
        } else if(input.name.includes('variant_id')) {
             input.value = ''; // Clear ID for new row
        } else {
             input.value = '';
        }
    });
    
    tbody.appendChild(row);
});

// Remove Variant Row
document.querySelector('#variantsTable').addEventListener('click', function(e) {
    if (e.target.closest('.remove-variant')) {
        const tbody = document.querySelector('#variantsTable tbody');
        if (tbody.querySelectorAll('tr').length > 1) {
            e.target.closest('tr').remove();
        } else {
            const row = e.target.closest('tr');
             row.querySelectorAll('input').forEach(input => {
                if (input.name.includes('qty')) input.value = '0';
                else if (input.name.includes('variant_price')) input.value = '';
                else input.value = '';
            });
        }
    }
});
</script>

<?php require_once "../includes/footer.php"; ?>