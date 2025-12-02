<?php
// products/add_product.php
session_start();
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle form submission
if ($_POST) {
    try {
        $query = "INSERT INTO products 
                  (sku, name, description, category, quantity, price, cost_price, min_stock, supplier, location) 
                  VALUES 
                  (:sku, :name, :description, :category, :quantity, :price, :cost_price, :min_stock, :supplier, :location)";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":sku", $_POST['sku']);
        $stmt->bindParam(":name", $_POST['name']);
        $stmt->bindParam(":description", $_POST['description']);
        $stmt->bindParam(":category", $_POST['category']);
        $stmt->bindParam(":quantity", $_POST['quantity']);
        $stmt->bindParam(":price", $_POST['price']);
        $stmt->bindParam(":cost_price", $_POST['cost_price']);
        $stmt->bindParam(":min_stock", $_POST['min_stock']);
        $stmt->bindParam(":supplier", $_POST['supplier']);
        $stmt->bindParam(":location", $_POST['location']);
        
        if ($stmt->execute()) {
            $message = "Product added successfully!";
            $message_type = "success";
            
            // Log the initial stock movement
            $product_id = $db->lastInsertId();
            $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reason) 
                              VALUES (:product_id, 'IN', :quantity, 'Initial stock')";
            $movement_stmt = $db->prepare($movement_query);
            $movement_stmt->bindParam(":product_id", $product_id);
            $movement_stmt->bindParam(":quantity", $_POST['quantity']);
            $movement_stmt->execute();
            
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
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Select Category</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Computers">Computers</option>
                            <option value="Accessories">Accessories</option>
                            <option value="Office Supplies">Office Supplies</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Other">Other</option>
                        </select>
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
                        <label for="supplier" class="form-label">Supplier</label>
                        <input type="text" class="form-control" id="supplier" name="supplier" 
                               placeholder="Supplier name">
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