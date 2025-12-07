<?php
// products/add_product.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/AuditLog.php";
require_once "../config/auth.php";

// Ensure user is logged in
Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();
$audit = new AuditLog($db);

$message = '';
$message_type = '';
$errors = [];

// Helper to get name from ID - Moved outside AJAX handler to prevent redefinition errors
function getName($db, $table, $id) {
    if (!$id) return null;
    $stmt = $db->prepare("SELECT name FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['name'] : null;
}

// Handle AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if ($_POST) {
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
        
        if (empty($sku)) {
            $sku = generateSKU($db);
        } else {
            // Check if SKU already exists
            $check_query = "SELECT id FROM products WHERE sku = :sku";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":sku", $sku);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $response['errors'][] = "SKU already exists. Please use a unique SKU.";
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
                         $v_sku = $sku . '-' . strtoupper(substr($v_size ? $v_size : 'X', 0, 3)) . '-' . strtoupper(substr($v_color ? $v_color : 'X', 0, 3)) . '-' . ($i+1);
                    }

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
                    if ($v_check->rowCount() > 0) {
                        $response['errors'][] = "Variant SKU already exists: " . $v_sku;
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
                $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                $brand_id = !empty($_POST['brand_id']) ? $_POST['brand_id'] : null;
                $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
                
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
                $stmt->bindParam(":category_id", $category_id);
                $stmt->bindParam(":brand_id", $brand_id);
                $stmt->bindParam(":quantity", $quantity);
                $stmt->bindParam(":price", $price);
                $stmt->bindParam(":cost_price", $cost_price);
                $stmt->bindParam(":min_stock", $min_stock);
                $stmt->bindParam(":supplier", $supplier_name);
                $stmt->bindParam(":supplier_id", $supplier_id);
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

// Function to generate unique SKU
function generateSKU($db) {
    $prefix = "PRD";
    $timestamp = time();
    $random = rand(100, 999);
    $sku = $prefix . "-" . $timestamp . "-" . $random;
    $stmt = $db->prepare("SELECT id FROM products WHERE sku = ?");
    $stmt->execute([$sku]);
    if ($stmt->fetch()) return generateSKU($db);
    return $sku;
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
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="sku" class="form-label">SKU *</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="sku" name="sku" 
                                   value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>"
                                   placeholder="Unique product identifier">
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
                                $suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name ASC");
                                while ($row = $suppliers->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <a href="../suppliers/add_supplier.php" class="btn btn-outline-secondary" title="Add New Supplier"><i class="fas fa-plus"></i></a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               placeholder="e.g., Aisle 4, Shelf B">
                    </div>
                </div>
            </div>

            <!-- Variants Section -->
            <div id="variantsSection" style="display:none;" class="row mt-3">
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
                                    <th>SKU (Auto/Override)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="variant-row">
                                    <td><input type="text" class="form-control form-control-sm" name="variant_size[]" placeholder="Option/Size"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="variant_color[]" placeholder="Color"></td>
                                    <td><input type="number" class="form-control form-control-sm variant-qty" name="variant_qty[]" value="0" min="0"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm variant-price" name="variant_price[]" placeholder="Uses Main Price"></td>
                                    <td><input type="text" class="form-control form-control-sm variant-sku" name="variant_sku[]" placeholder="Auto-gen"></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-variant"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-success btn-sm" id="addVariantBtn"><i class="fas fa-plus"></i> Add Variant</button>
                    <div class="mt-2 text-muted small">
                         * Price defaults to Main Selling Price. SKU auto-generates if empty.
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Add Product
                    </button>
                    <a href="view_products.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Generate main SKU
document.getElementById('generateSKU').addEventListener('click', function() {
    const timestamp = Math.floor(Date.now() / 1000);
    const random = Math.floor(Math.random() * 900) + 100;
    document.getElementById('sku').value = 'PRD-' + timestamp + '-' + random;
});

// Calculate profit margin
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

document.getElementById('price').addEventListener('input', function() {
    calculateProfitMargin();
    updateVariantPrices();
});

document.getElementById('cost_price').addEventListener('input', calculateProfitMargin);

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

function calculateProfitMargin() {
    const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;
    const sellingPrice = parseFloat(document.getElementById('price').value) || 0;
    
    if (costPrice > 0 && sellingPrice > 0) {
        const profit = sellingPrice - costPrice;
        const margin = (profit / sellingPrice) * 100;
        document.getElementById('profitMarginText').innerHTML = 
            '<span class="text-success">Profit: $' + profit.toFixed(2) + ' (' + margin.toFixed(2) + '% margin)</span>';
    } else {
        document.getElementById('profitMarginText').innerHTML = '';
    }
}

// Toggle Variants
const hasVariantsCheckbox = document.getElementById('has_variants');
const simpleProductFields = document.getElementById('simpleProductFields');
const variantsSection = document.getElementById('variantsSection');

hasVariantsCheckbox.addEventListener('change', function() {
    if (this.checked) {
        simpleProductFields.style.display = 'none';
        variantsSection.style.display = 'block';
    } else {
        simpleProductFields.style.display = 'block';
        variantsSection.style.display = 'none';
    }
});

// Add Variant Row
document.getElementById('addVariantBtn').addEventListener('click', function() {
    const tbody = document.querySelector('#variantsTable tbody');
    const row = tbody.querySelector('.variant-row').cloneNode(true);
    
    // Get main defaults
    const mainPrice = document.getElementById('price').value;
    const mainSku = document.getElementById('sku').value;
    
    // Set inputs
    row.querySelectorAll('input').forEach(input => {
        if (input.name.includes('qty')) {
             input.value = '0';
        } else if (input.name.includes('variant_price')) {
             input.value = mainPrice; // Auto-fill price
             input.classList.add('auto-filled');
             // Add event listener to remove auto-filled class when manually changed
             input.addEventListener('input', function() {
                 this.classList.remove('auto-filled');
             });
        } else if (input.name.includes('variant_sku')) {
             // Generate a temporary SKU suffix for display, actual unique gen happens on backend or user edit
             if (mainSku) input.value = mainSku + '-VAR'; 
             else input.value = '';
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
            alert('You must have at least one variant row.');
        }
    }
});

// Form validation and submission
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    let isValid = true;
    const errors = [];
    
    // Get form values
    const name = document.getElementById('name').value.trim();
    const price = parseFloat(document.getElementById('price').value) || 0;
    
    // Common validation
    if (!name) { isValid = false; errors.push('Product name is required'); }
    if (price < 0) { isValid = false; errors.push('Selling price cannot be negative'); }
    
    // Variants vs Simple validation
    if (hasVariantsCheckbox.checked) {
        const variantRows = document.querySelectorAll('.variant-row');
        let hasValidVariant = false;
        
        variantRows.forEach(row => {
            const size = row.querySelector('input[name="variant_size[]"]').value.trim();
            const color = row.querySelector('input[name="variant_color[]"]').value.trim();
            if (size || color) hasValidVariant = true;
        });

        if (!hasValidVariant) {
            isValid = false;
            errors.push('Please add at least one variant with Option/Size or Color.');
        }
    } else {
        const quantity = parseInt(document.getElementById('quantity').value);
        if (isNaN(quantity) || quantity < 0) {
            isValid = false;
            errors.push('Quantity must be a valid positive number');
        }
    }

    if (!isValid) {
        e.preventDefault();
        alert('Please correct the following errors:\n' + errors.join('\n'));
        return false;
    }
    
    // If valid, submit via AJAX
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';
    submitBtn.disabled = true;
    
    const formData = new FormData(this);
    
    fetch('add_product.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = 'view_products.php';
        } else {
            let errorText = 'Please correct the following errors:\n';
            if (data.errors && data.errors.length > 0) {
                errorText += data.errors.join('\n');
            } else {
                errorText += data.message;
            }
            alert(errorText);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>