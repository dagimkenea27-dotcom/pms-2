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
                        $v_stmt = $db->prepare("INSERT INTO product_variants (product_id, sku, size, color, quantity, price, min_stock) VALUES (:pid, :sku, :size, :color, :qty, :price, :min_stock)");
                        foreach ($variants as $v) {
                            $v_stmt->execute([
                                ':pid' => $product_id,
                                ':sku' => $v['sku'],
                                ':size' => $v['size'],
                                ':color' => $v['color'],
                                ':qty' => $v['qty'],
                                ':price' => $v['price'], // Can be null
                                ':min_stock' => $min_stock // default to product min_stock
                            ]);
                            $variant_id = $db->lastInsertId();

                            // Log stock movement for variant
                            $movement_query = "INSERT INTO stock_movements (product_id, variant_id, movement_type, quantity, reason, supplier_id) 
                                              VALUES (:product_id, :variant_id, 'IN', :quantity, 'Initial stock', :supplier_id)";
                            $m_stmt = $db->prepare($movement_query);
                            $m_stmt->execute([
                                ':product_id' => $product_id,
                                ':variant_id' => $variant_id,
                                ':quantity' => $v['qty'],
                                ':supplier_id' => $supplier_id
                            ]);
                        }
                    } else {
                        // Log the initial stock movement for simple product
                        $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reason, supplier_id) 
                                          VALUES (:product_id, 'IN', :quantity, 'Initial stock', :supplier_id)";
                        $movement_stmt = $db->prepare($movement_query);
                        $movement_stmt->bindParam(":product_id", $product_id);
                        $movement_stmt->bindParam(":quantity", $quantity);
                        $movement_stmt->bindParam(":supplier_id", $supplier_id);
                        $movement_stmt->execute();
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

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-plus"></i> Add New Product</h1>
    <a href="view_products.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Products
    </a>
</div>

<div class="card dashboard-card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Product Details</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="addProductForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="sku" class="form-label">SKU *</label>
                        <div class="input-group">
                            <input type="text" class="form-control barcode-input" id="sku" name="sku" 
                                   value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>"
                                   placeholder="Unique product identifier or Scan Barcode">
                            <button class="btn btn-outline-secondary start-barcode-scanner" type="button" title="Scan Barcode">
                                <i class="fas fa-barcode"></i>
                            </button>
                            <button class="btn btn-outline-secondary" type="button" id="generateSKU">
                                <i class="fas fa-random"></i> Generate
                            </button>
                        </div>
                        <div class="form-text">Leave blank to auto-generate SKU</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               placeholder="Enter product name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <div class="input-group">
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php 
                                $categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
                                while ($row = $categories->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
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
                                $brands = $db->query("SELECT id, name FROM brands ORDER BY name ASC");
                                while ($row = $brands->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <a href="../brands/add.php" class="btn btn-outline-secondary" title="Add New Brand"><i class="fas fa-plus"></i></a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" placeholder="Product description"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="product_image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                        <div class="form-text">Allowed formats: JPG, JPEG, PNG, GIF. Max size: 5MB</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="has_variants" name="has_variants" value="1">
                        <label class="form-check-label font-weight-bold" for="has_variants">Product has variants (Option/Color)</label>
                    </div>

                    <!-- Simple Product Fields -->
                    <div id="simpleProductFields">
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Initial Quantity *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   value="0" min="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cost_price" class="form-label">Cost Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="cost_price" 
                               name="cost_price" value="" min="0" placeholder="0.00">
                    </div>
                    
                    <div class="mb-3">
                        <label for="price" class="form-label">Selling Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="price" 
                               name="price" value="" min="0" placeholder="0.00">
                        <div class="form-text" id="profitMarginText"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="min_stock" class="form-label">Minimum Stock Level</label>
                        <input type="number" class="form-control" id="min_stock" name="min_stock" 
                               value="5" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <div class="input-group">
                            <select class="form-select" id="supplier_id" name="supplier_id">
                                <option value="">Select Supplier</option>
                                <?php 
                                $suppliers = $db->query("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name ASC");
                                while ($row = $suppliers->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <a href="../suppliers/add_supplier.php" class="btn btn-outline-secondary" title="Add New Supplier"><i class="fas fa-plus"></i></a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Storage Location</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               placeholder="Warehouse A, Shelf 5, etc.">
                    </div>
                </div>
            </div>
            
            <!-- Variants Section -->
            <div id="variantsSection" style="display: none;">
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
                            <tr>
                                <td><input type="text" class="form-control" name="variant_size[]" placeholder="e.g., Small"></td>
                                <td><input type="text" class="form-control" name="variant_color[]" placeholder="e.g., Red"></td>
                                <td><input type="text" class="form-control variant-sku" name="variant_sku[]" placeholder="Loading SKU..."></td>
                                <td><input type="number" class="form-control" name="variant_qty[]" value="0" min="0"></td>
                                <td><input type="number" class="form-control" name="variant_price[]" step="0.01" placeholder="Same as main"></td>
                                <td><button type="button" class="btn btn-danger remove-variant"><i class="fas fa-trash"></i></button></td>
                            </tr>
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
                    <i class="fas fa-save"></i> Add Product
                </button>
                <a href="view_products.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
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
        <td><input type="text" class="form-control" name="variant_size[]" placeholder="e.g., Small"></td>
        <td><input type="text" class="form-control" name="variant_color[]" placeholder="e.g., Red"></td>
        <td><input type="text" class="form-control variant-sku" name="variant_sku[]" placeholder="Loading SKU..."></td>
        <td><input type="number" class="form-control" name="variant_qty[]" value="0" min="0"></td>
        <td><input type="number" class="form-control" name="variant_price[]" step="0.01" placeholder="Same as main"></td>
        <td><button type="button" class="btn btn-danger remove-variant"><i class="fas fa-trash"></i></button></td>
    `;
    
    tbody.appendChild(newRow);
    
    // Auto-generate SKU for new row
    const usedSkus = getAllUsedSkus();
    newRow.querySelector('.variant-sku').value = generateJS_SKU(usedSkus);
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

// Helper to get all currently used SKUs in the UI (to avoid collisions)
function getAllUsedSkus() {
    const skus = [];
    const mainSku = document.getElementById('sku').value;
    if (mainSku) skus.push(mainSku);
    
    document.querySelectorAll('.variant-sku').forEach(input => {
        if (input.value) skus.push(input.value);
    });
    return skus;
}

// Reusable SKU generation logic matching functions.php
function generateJS_SKU(excludes = []) {
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
    const sku = code11 + '' + checkDigit;
    
    // Simple collision check against current page state
    if (excludes.includes(sku)) {
        return generateJS_SKU(excludes);
    }
    return sku;
}

// Generate SKU
document.getElementById('generateSKU').addEventListener('click', function() {
    const usedSkus = [];
    const mainSkuInput = document.getElementById('sku');
    
    const newMainSku = generateJS_SKU(usedSkus);
    mainSkuInput.value = newMainSku;
    usedSkus.push(newMainSku);
    
    // Also regenerate for variants if they are empty or placeholder
    document.querySelectorAll('.variant-sku').forEach(input => {
        const val = generateJS_SKU(usedSkus);
        input.value = val;
        usedSkus.push(val);
    });
});

// Populate initial variant SKU on load
document.addEventListener('DOMContentLoaded', function() {
    const variantSkus = document.querySelectorAll('.variant-sku');
    const used = getAllUsedSkus();
    variantSkus.forEach(input => {
        if (!input.value) {
            const sku = generateJS_SKU(used);
            input.value = sku;
            used.push(sku);
        }
    });
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

// Calculate profit margin
document.getElementById('price').addEventListener('input', calculateProfitMargin);
document.getElementById('cost_price').addEventListener('input', calculateProfitMargin);

function calculateProfitMargin() {
    const price = parseFloat(document.getElementById('price').value) || 0;
    const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;
    
    if (costPrice > 0 && price > 0) {
        const profit = price - costPrice;
        const margin = (profit / price) * 100;
        document.getElementById('profitMarginText').textContent = 
            `Profit: $${profit.toFixed(2)} (${margin.toFixed(2)}%)`;
    } else {
        document.getElementById('profitMarginText').textContent = '';
    }
}

// Handle form submission
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = 'view_products.php';
        } else {
            let errorHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            errorHtml += '<strong>Error!</strong><ul>';
            data.errors.forEach(error => {
                errorHtml += `<li>${error}</li>`;
            });
            errorHtml += '</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            
            // Remove existing error alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Add new error alert
            document.querySelector('.card-body').insertAdjacentHTML('afterbegin', errorHtml);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting the form.');
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>