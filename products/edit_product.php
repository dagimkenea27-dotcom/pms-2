<?php
// products/edit_product.php
session_start();
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

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
        $query = "UPDATE products SET 
                  sku = :sku, 
                  name = :name, 
                  description = :description, 
                  category = :category, 
                  price = :price, 
                  cost_price = :cost_price, 
                  min_stock = :min_stock, 
                  supplier = :supplier, 
                  location = :location 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":sku", $_POST['sku']);
        $stmt->bindParam(":name", $_POST['name']);
        $stmt->bindParam(":description", $_POST['description']);
        $stmt->bindParam(":category", $_POST['category']);
        $stmt->bindParam(":price", $_POST['price']);
        $stmt->bindParam(":cost_price", $_POST['cost_price']);
        $stmt->bindParam(":min_stock", $_POST['min_stock']);
        $stmt->bindParam(":supplier", $_POST['supplier']);
        $stmt->bindParam(":location", $_POST['location']);
        $stmt->bindParam(":id", $product['id']);
        
        if ($stmt->execute()) {
            $message = "Product updated successfully!";
            $message_type = "success";
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
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Select Category</option>
                                    <option value="Electronics" <?php echo $product['category'] == 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                                    <option value="Computers" <?php echo $product['category'] == 'Computers' ? 'selected' : ''; ?>>Computers</option>
                                    <option value="Accessories" <?php echo $product['category'] == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                                    <option value="Office Supplies" <?php echo $product['category'] == 'Office Supplies' ? 'selected' : ''; ?>>Office Supplies</option>
                                    <option value="Furniture" <?php echo $product['category'] == 'Furniture' ? 'selected' : ''; ?>>Furniture</option>
                                    <option value="Other" <?php echo $product['category'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
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
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" class="form-control" id="supplier" name="supplier" 
                                       value="<?php echo htmlspecialchars($product['supplier']); ?>">
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