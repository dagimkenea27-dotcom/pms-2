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
                  location = :location 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":sku", $_POST['sku']);
        $stmt->bindParam(":name", $_POST['name']);
        $stmt->bindParam(":description", $_POST['description']);
        $stmt->bindParam(":category", $category_name);
        $stmt->bindParam(":category_id", $category_id);
        $stmt->bindParam(":brand_id", $brand_id);
        $stmt->bindParam(":price", $_POST['price']);
        $stmt->bindParam(":cost_price", $_POST['cost_price']);
        $stmt->bindParam(":min_stock", $_POST['min_stock']);
        $stmt->bindParam(":supplier", $supplier_name);
        $stmt->bindParam(":supplier_id", $supplier_id);
        $stmt->bindParam(":location", $_POST['location']);
        $stmt->bindParam(":id", $product['id']);
        
        if ($stmt->execute()) {
            $message = "Product updated successfully!";
            $message_type = "success";
            
            // Log to AuditLog
            if (Auth::isLoggedIn()) {
                $user = Auth::getCurrentUser();
                $audit->log($user['id'], "PRODUCT_UPDATE", "Updated product: " . $_POST['name'] . " (ID: " . $product['id'] . ")");
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
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-edit"></i> Edit Product</h1>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
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

<?php require_once "../includes/footer.php"; ?>