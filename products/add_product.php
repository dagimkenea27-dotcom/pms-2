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

// Get categories, brands, suppliers for dropdowns
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
$brands = $db->query("SELECT id, name FROM brands ORDER BY name ASC");
$suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name ASC");

$message = '';
$message_type = '';
$errors = [];

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
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_name = $_FILES['product_image']['name'];
            $file_size = $_FILES['product_image']['size'];
            $file_tmp = $_FILES['product_image']['tmp_name'];
            $file_type = $_FILES['product_image']['type'];
            
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
                // Get names for backward compatibility
                $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                $brand_id = !empty($_POST['brand_id']) ? $_POST['brand_id'] : null;
                $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
                
                // Helper to get name from ID
                function getName($db, $table, $id) {
                    if (!$id) return null;
                    $stmt = $db->prepare("SELECT name FROM $table WHERE id = ?");
                    $stmt->execute([$id]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $row ? $row['name'] : null;
                }

                $category_name = getName($db, 'categories', $category_id);
                $supplier_name = getName($db, 'suppliers', $supplier_id);

                $query = "INSERT INTO products 
                          (sku, name, description, category, category_id, brand_id, quantity, price, cost_price, min_stock, supplier, supplier_id, location, image, barcode) 
                          VALUES 
                          (:sku, :name, :description, :category, :category_id, :brand_id, :quantity, :price, :cost_price, :min_stock, :supplier, :supplier_id, :location, :image, :barcode)";
                
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
                $stmt->bindParam(":location", $_POST['location']);
                $stmt->bindParam(":image", $image_path);
                $stmt->bindParam(":barcode", $sku); // Use SKU as barcode by default
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Product added successfully!";
                    
                    // Log the initial stock movement
                    $product_id = $db->lastInsertId();
                    $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reason, supplier_id) 
                                      VALUES (:product_id, 'IN', :quantity, 'Initial stock', :supplier_id)";
                    $movement_stmt = $db->prepare($movement_query);
                    $movement_stmt->bindParam(":product_id", $product_id);
                    $movement_stmt->bindParam(":quantity", $quantity);
                    $movement_stmt->bindParam(":supplier_id", $supplier_id);
                    $movement_stmt->execute();
                    
                    // Log to AuditLog
                    if (Auth::isLoggedIn()) {
                        $user = Auth::getCurrentUser();
                        $audit->log($user['id'], "PRODUCT_ADD", "Added product: " . $name . " (SKU: " . $sku . ")");
                    }
                } else {
                    $response['errors'][] = "Error adding product.";
                }
            } catch (PDOException $exception) {
                $response['errors'][] = "Error: " . $exception->getMessage();
            }
        }
        
        echo json_encode($response);
        exit();
    }
}

// Handle regular form submission (fallback for non-AJAX requests)
if ($_POST && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Validate input
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $cost_price = floatval($_POST['cost_price'] ?? 0);
    $min_stock = intval($_POST['min_stock'] ?? 0);
    
    // Validation checks
    if (empty($name)) {
        $errors[] = "Product name is required.";
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
            $errors[] = "SKU already exists. Please use a unique SKU.";
        }
    }
    
    if ($quantity < 0) {
        $errors[] = "Quantity cannot be negative.";
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
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = '../uploads/products/';
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_name = $_FILES['product_image']['name'];
        $file_size = $_FILES['product_image']['size'];
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $file_type = $_FILES['product_image']['type'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file
        if (!in_array($file_ext, $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
        
        if ($file_size > $max_size) {
            $errors[] = "File size too large. Maximum file size is 5MB.";
        }
        
        // If no errors, process the file
        if (empty($errors)) {
            // Generate unique filename
            $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $target_file)) {
                $image_path = 'uploads/products/' . $new_filename;
            } else {
                $errors[] = "Error uploading image file.";
            }
        }
    }
    
    // If no errors, proceed with insertion
    if (empty($errors)) {
        try {
            // Get names for backward compatibility
            $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
            $brand_id = !empty($_POST['brand_id']) ? $_POST['brand_id'] : null;
            $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
            
            // Helper to get name from ID
            function getName($db, $table, $id) {
                if (!$id) return null;
                $stmt = $db->prepare("SELECT name FROM $table WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? $row['name'] : null;
            }

            $category_name = getName($db, 'categories', $category_id);
            $supplier_name = getName($db, 'suppliers', $supplier_id);

            $query = "INSERT INTO products 
                      (sku, name, description, category, category_id, brand_id, quantity, price, cost_price, min_stock, supplier, supplier_id, location, image, barcode) 
                      VALUES 
                      (:sku, :name, :description, :category, :category_id, :brand_id, :quantity, :price, :cost_price, :min_stock, :supplier, :supplier_id, :location, :image, :barcode)";
            
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
            $stmt->bindParam(":location", $_POST['location']);
            $stmt->bindParam(":image", $image_path);
            $stmt->bindParam(":barcode", $sku); // Use SKU as barcode by default
            
            if ($stmt->execute()) {
                $message = "Product added successfully!";
                $message_type = "success";
                
                // Log the initial stock movement
                $product_id = $db->lastInsertId();
                $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reason, supplier_id) 
                                  VALUES (:product_id, 'IN', :quantity, 'Initial stock', :supplier_id)";
                $movement_stmt = $db->prepare($movement_query);
                $movement_stmt->bindParam(":product_id", $product_id);
                $movement_stmt->bindParam(":quantity", $quantity);
                $movement_stmt->bindParam(":supplier_id", $supplier_id);
                $movement_stmt->execute();
                
                // Log to AuditLog
                if (Auth::isLoggedIn()) {
                    $user = Auth::getCurrentUser();
                    $audit->log($user['id'], "PRODUCT_ADD", "Added product: " . $name . " (SKU: " . $sku . ")");
                }
                
                // Clear form data after successful submission
                $_POST = [];
            } else {
                $message = "Error adding product.";
                $message_type = "danger";
            }
        } catch (PDOException $exception) {
            $message = "Error: " . $exception->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = "Please correct the following errors:";
        $message_type = "danger";
    }
}

// Function to generate unique SKU
function generateSKU($db) {
    $prefix = "PRD";
    $timestamp = time();
    $random = rand(100, 999);
    $sku = $prefix . "-" . $timestamp . "-" . $random;
    
    // Check if SKU exists
    $stmt = $db->prepare("SELECT id FROM products WHERE sku = ?");
    $stmt->execute([$sku]);
    
    if ($stmt->fetch()) {
        // Recursively generate new SKU if collision occurs
        return generateSKU($db);
    }
    
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
                                // Reset pointer for reuse
                                $categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
                                while ($row = $categories->fetch(PDO::FETCH_ASSOC)): 
                                    $selected = (isset($_POST['category_id']) && $_POST['category_id'] == $row['id']) ? 'selected' : '';
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
                                // Reset pointer for reuse
                                $brands = $db->query("SELECT id, name FROM brands ORDER BY name ASC");
                                while ($row = $brands->fetch(PDO::FETCH_ASSOC)): 
                                    $selected = (isset($_POST['brand_id']) && $_POST['brand_id'] == $row['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <a href="../brands/add.php" class="btn btn-outline-secondary" title="Add New Brand"><i class="fas fa-plus"></i></a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" placeholder="Product description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="product_image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                        <div class="form-text">Allowed formats: JPG, JPEG, PNG, GIF. Max size: 5MB</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Initial Quantity *</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" 
                               value="<?php echo intval($_POST['quantity'] ?? 0); ?>" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cost_price" class="form-label">Cost Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="cost_price" 
                               name="cost_price" value="<?php echo floatval($_POST['cost_price'] ?? 0); ?>" min="0" placeholder="0.00">
                    </div>
                    
                    <div class="mb-3">
                        <label for="price" class="form-label">Selling Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="price" 
                               name="price" value="<?php echo floatval($_POST['price'] ?? 0); ?>" min="0" placeholder="0.00">
                        <div class="form-text" id="profitMarginText"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="min_stock" class="form-label">Minimum Stock Level</label>
                        <input type="number" class="form-control" id="min_stock" name="min_stock" 
                               value="<?php echo intval($_POST['min_stock'] ?? 5); ?>" min="0">
                        <div class="form-text">Low stock alert will trigger when quantity reaches this level</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <div class="input-group">
                            <select class="form-select" id="supplier_id" name="supplier_id">
                                <option value="">Select Supplier</option>
                                <?php 
                                // Reset pointer for reuse
                                $suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name ASC");
                                while ($row = $suppliers->fetch(PDO::FETCH_ASSOC)): 
                                    $selected = (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $row['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <a href="../suppliers/add_supplier.php" class="btn btn-outline-secondary" title="Add New Supplier"><i class="fas fa-plus"></i></a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                               placeholder="e.g., Aisle 4, Shelf B">
                    </div>
                </div>
            </div>
            
            <div class="row">
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
// Generate SKU
document.getElementById('generateSKU').addEventListener('click', function() {
    const timestamp = Math.floor(Date.now() / 1000);
    const random = Math.floor(Math.random() * 900) + 100;
    document.getElementById('sku').value = 'PRD-' + timestamp + '-' + random;
});

// Calculate profit margin
document.getElementById('price').addEventListener('input', calculateProfitMargin);
document.getElementById('cost_price').addEventListener('input', calculateProfitMargin);

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

// Trigger profit calculation on page load if values exist
window.addEventListener('load', function() {
    calculateProfitMargin();
});

// Form validation
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    let isValid = true;
    const errors = [];
    
    // Get form values
    const name = document.getElementById('name').value.trim();
    const quantity = parseInt(document.getElementById('quantity').value);
    const price = parseFloat(document.getElementById('price').value) || 0;
    const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;
    const minStock = parseInt(document.getElementById('min_stock').value) || 0;
    
    // Validation checks
    if (!name) {
        isValid = false;
        errors.push('Product name is required');
    }
    
    if (isNaN(quantity) || quantity < 0) {
        isValid = false;
        errors.push('Quantity must be a valid positive number');
    }
    
    if (price < 0) {
        isValid = false;
        errors.push('Selling price cannot be negative');
    }
    
    if (costPrice < 0) {
        isValid = false;
        errors.push('Cost price cannot be negative');
    }
    
    if (minStock < 0) {
        isValid = false;
        errors.push('Minimum stock level cannot be negative');
    }
    
    // Check image file
    const imageInput = document.getElementById('product_image');
    if (imageInput.files.length > 0) {
        const file = imageInput.files[0];
        const fileSize = file.size;
        const fileName = file.name;
        const fileExt = fileName.split('.').pop().toLowerCase();
        const allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!allowedTypes.includes(fileExt)) {
            isValid = false;
            errors.push('Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.');
        }
        
        if (fileSize > maxSize) {
            isValid = false;
            errors.push('File size too large. Maximum file size is 5MB.');
        }
    }
    
    if (!isValid) {
        e.preventDefault();
        alert('Please correct the following errors:\n' + errors.join('\n'));
        return false;
    }
    
    // If valid, submit via AJAX
    e.preventDefault();
    
    // Show loading indicator
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
    submitBtn.disabled = true;
    
    // Create FormData object
    const formData = new FormData(this);
    
    // Send AJAX request
    fetch('add_product.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert(data.message);
            // Reset form
            document.getElementById('addProductForm').reset();
            // Redirect to products page
            window.location.href = 'view_products.php';
        } else {
            // Show errors
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
        alert('An error occurred while adding the product. Please try again.');
    })
    .finally(() => {
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>