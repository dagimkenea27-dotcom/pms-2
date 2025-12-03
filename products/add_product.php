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

// Handle form submission
if ($_POST) {
    try {
        // Get names for backward compatibility (optional, but good for now)
        // We can look them up or just store the IDs. 
        // The schema has `category` (text) and `supplier` (text).
        // Let's try to find the names from the selected IDs.
        
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        $brand_id = !empty($_POST['brand_id']) ? $_POST['brand_id'] : null;
        $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
        
        // Helper to get name from ID (simple query)
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
                  (sku, name, description, category, category_id, brand_id, quantity, price, cost_price, min_stock, supplier, supplier_id, location) 
                  VALUES 
                  (:sku, :name, :description, :category, :category_id, :brand_id, :quantity, :price, :cost_price, :min_stock, :supplier, :supplier_id, :location)";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":sku", $_POST['sku']);
        $stmt->bindParam(":name", $_POST['name']);
        $stmt->bindParam(":description", $_POST['description']);
        $stmt->bindParam(":category", $category_name);
        $stmt->bindParam(":category_id", $category_id);
        $stmt->bindParam(":brand_id", $brand_id);
        $stmt->bindParam(":quantity", $_POST['quantity']);
        $stmt->bindParam(":price", $_POST['price']);
        $stmt->bindParam(":cost_price", $_POST['cost_price']);
        $stmt->bindParam(":min_stock", $_POST['min_stock']);
        $stmt->bindParam(":supplier", $supplier_name);
        $stmt->bindParam(":supplier_id", $supplier_id);
        $stmt->bindParam(":location", $_POST['location']);
        
        if ($stmt->execute()) {
            $message = "Product added successfully!";
            $message_type = "success";
            
            // Log the initial stock movement
            $product_id = $db->lastInsertId();
            $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reason, supplier_id) 
                              VALUES (:product_id, 'IN', :quantity, 'Initial stock', :supplier_id)";
            $movement_stmt = $db->prepare($movement_query);
            $movement_stmt->bindParam(":product_id", $product_id);
            $movement_stmt->bindParam(":quantity", $_POST['quantity']);
            $movement_stmt->bindParam(":supplier_id", $supplier_id);
            $movement_stmt->execute();
            
            // Log to AuditLog
            if (Auth::isLoggedIn()) {
                $user = Auth::getCurrentUser();
                $audit->log($user['id'], "PRODUCT_ADD", "Added product: " . $_POST['name'] . " (SKU: " . $_POST['sku'] . ")");
            }
            
        } else {
            $message = "Error adding product.";
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

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-plus"></i> Add New Product</h1>
    <a href="view_products.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Products
    </a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card dashboard-card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Product Details</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="sku" class="form-label">SKU *</label>
                        <input type="text" class="form-control" id="sku" name="sku" required 
                               placeholder="Unique product identifier">
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required 
                               placeholder="Enter product name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <div class="input-group">
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php while ($row = $categories->fetch(PDO::FETCH_ASSOC)): ?>
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
                                <?php while ($row = $brands->fetch(PDO::FETCH_ASSOC)): ?>
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
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Initial Quantity *</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" 
                               value="0" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cost_price" class="form-label">Cost Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="cost_price" 
                               name="cost_price" placeholder="0.00">
                    </div>
                    
                    <div class="mb-3">
                        <label for="price" class="form-label">Selling Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="price" 
                               name="price" placeholder="0.00">
                    </div>
                    
                    <div class="mb-3">
                        <label for="min_stock" class="form-label">Minimum Stock Level</label>
                        <input type="number" class="form-control" id="min_stock" name="min_stock" 
                               value="5" min="0">
                        <div class="form-text">Low stock alert will trigger when quantity reaches this level</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <div class="input-group">
                            <select class="form-select" id="supplier_id" name="supplier_id">
                                <option value="">Select Supplier</option>
                                <?php while ($row = $suppliers->fetch(PDO::FETCH_ASSOC)): ?>
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
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
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

<?php require_once "../includes/footer.php"; ?>