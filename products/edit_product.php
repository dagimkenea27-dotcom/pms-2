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

// Get product data
$product = null;
if (isset($_GET['id'])) {
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
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
    
    // Handle image upload
    $image_path = $product['image']; // Keep existing image by default
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
                
                // Delete old image if it exists and is not the default
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

            $query = "UPDATE products SET 
                      sku = :sku, 
                      name = :name, 
                      description = :description, 
                      category = :category, 
                      category_id = :category_id,
                      brand_id = :brand_id,
                      price = :price, 
                      cost_price = :cost_price, 
                      min_stock = :min_stock, 
                      supplier = :supplier, 
                      supplier_id = :supplier_id,
                      location = :location,
                      image = :image,
                      barcode = :barcode
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(":sku", $sku);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $_POST['description']);
            $stmt->bindParam(":category", $category_name);
            $stmt->bindParam(":category_id", $category_id);
            $stmt->bindParam(":brand_id", $brand_id);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":cost_price", $cost_price);
            $stmt->bindParam(":min_stock", $min_stock);
            $stmt->bindParam(":supplier", $supplier_name);
            $stmt->bindParam(":supplier_id", $supplier_id);
            $stmt->bindParam(":location", $_POST['location']);
            $stmt->bindParam(":image", $image_path);
            $stmt->bindParam(":barcode", $sku); // Use SKU as barcode
            $stmt->bindParam(":id", $product['id']);
            
            if ($stmt->execute()) {
                $message = "Product updated successfully!";
                $message_type = "success";
                
                // Log to AuditLog
                if (Auth::isLoggedIn()) {
                    $user = Auth::getCurrentUser();
                    $audit->log($user['id'], "PRODUCT_UPDATE", "Updated product: " . $name . " (ID: " . $product['id'] . ")");
                }
                
                // Refresh product data
                $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
                $stmt->bindParam(":id", $product['id']);
                $stmt->execute();
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = "Error updating product.";
                $message_type = "danger";
            }
        } catch (PDOException $exception) {
            if ($exception->getCode() == 23000) {
                $message = "Error: SKU already exists. Please use a unique SKU.";
            } else {
                $message = "Error: " . $exception->getMessage();
            }
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
                <form method="POST" action="" enctype="multipart/form-data">
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
                                        // Reset pointer
                                        $categories->execute();
                                        while ($row = $categories->fetch(PDO::FETCH_ASSOC)): 
                                            $selected = ($product['category_id'] == $row['id']) ? 'selected' : '';
                                            // Fallback: if category_id is null but name matches
                                            if (!$selected && empty($product['category_id']) && $product['category'] == $row['name']) {
                                                $selected = 'selected';
                                            }
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
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="product_image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                                <div class="form-text">Allowed formats: JPG, JPEG, PNG, GIF. Max size: 5MB</div>
                                <?php if (!empty($product['image'])): ?>
                                    <div class="mt-2">
                                        <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="img-thumbnail" style="max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cost_price" class="form-label">Cost Price ($)</label>
                                <input type="number" step="0.01" class="form-control" id="cost_price" 
                                       name="cost_price" value="<?php echo $product['cost_price']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label">Selling Price ($)</label>
                                <input type="number" step="0.01" class="form-control" id="price" 
                                       name="price" value="<?php echo $product['price']; ?>">
                                <div class="form-text" id="profitMarginText"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="min_stock" class="form-label">Minimum Stock Level</label>
                                <input type="number" class="form-control" id="min_stock" name="min_stock" 
                                       value="<?php echo $product['min_stock']; ?>">
                                <div class="form-text">Low stock alert will trigger when quantity reaches this level</div>
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
                                            // Fallback
                                            if (!$selected && empty($product['supplier_id']) && $product['supplier'] == $row['name']) {
                                                $selected = 'selected';
                                            }
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
                                       value="<?php echo htmlspecialchars($product['location']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Barcode</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($product['sku']); ?>" readonly>
                                    <a href="generate_barcode.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary" title="View Barcode">
                                        <i class="fas fa-barcode"></i> View
                                    </a>
                                </div>
                                <div class="form-text">Barcode is automatically generated from SKU</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
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
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="fas fa-info-circle"></i> Product Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Current Quantity:</strong><br>
                    <span class="h4 <?php echo $product['quantity'] <= $product['min_stock'] ? 'text-warning' : 'text-success'; ?>">
                        <?php echo $product['quantity']; ?>
                    </span>
                </div>
                <div class="mb-3">
                    <strong>Created:</strong><br>
                    <?php echo date('M j, Y g:i A', strtotime($product['created_at'])); ?>
                </div>
                <div class="mb-3">
                    <strong>Last Updated:</strong><br>
                    <?php echo date('M j, Y g:i A', strtotime($product['updated_at'])); ?>
                </div>
                <div class="text-center">
                    <a href="update_stock.php?id=<?php echo $product['id']; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-warehouse"></i> Update Stock
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="fas fa-history"></i> Quick Stock Update</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="quick_stock_update.php">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="mb-2">
                        <select class="form-select form-select-sm" name="movement_type" required>
                            <option value="IN">Stock In</option>
                            <option value="OUT">Stock Out</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <input type="number" class="form-control form-control-sm" name="quantity" 
                               placeholder="Quantity" min="1" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm" name="reason" 
                               placeholder="Reason" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-sync"></i> Update
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
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
document.querySelector('form').addEventListener('submit', function(e) {
    let isValid = true;
    const errors = [];
    
    // Get form values
    const name = document.getElementById('name').value.trim();
    const sku = document.getElementById('sku').value.trim();
    const price = parseFloat(document.getElementById('price').value) || 0;
    const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;
    const minStock = parseInt(document.getElementById('min_stock').value) || 0;
    
    // Validation checks
    if (!name) {
        isValid = false;
        errors.push('Product name is required');
    }
    
    if (!sku) {
        isValid = false;
        errors.push('SKU is required');
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
    
    return true;
});
</script>

<?php require_once "../includes/footer.php"; ?>