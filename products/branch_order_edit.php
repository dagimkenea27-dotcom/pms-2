<?php
// Branch Activities - Order Edit Form
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/BranchOrder.php";
require_once "../config/auth.php";

$database = new Database();
$db = $database->getConnection();
$branchOrder = new BranchOrder($db);

$message = '';
$message_type = '';
$order_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header("Location: branch_orders_list.php");
    exit();
}

$order = $branchOrder->getById($order_id);
if (!$order) {
    header("Location: branch_orders_list.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
        $message = "Security Error: Invalid Token";
        $message_type = "danger";
    } else {
        try {
            $customer_name = trim($_POST['customer_name']);
            $product_name = trim($_POST['product_name']);
            $product_color = trim($_POST['product_color']);
            $product_size = trim($_POST['product_size']);
            $quantity = intval($_POST['quantity']);
            $option_available = trim($_POST['option_available']);
            $address = trim($_POST['address']);
            $phone_number = trim($_POST['phone_number']);
            $notes = trim($_POST['notes']);
            
            // Validation
            if (empty($customer_name)) throw new Exception("Customer name is required.");
            if (empty($product_name)) throw new Exception("Product name is required.");
            if ($quantity <= 0) throw new Exception("Quantity must be greater than zero.");
            if (empty($phone_number)) throw new Exception("Phone number is required.");
            
            $data = [
                'customer_name' => $customer_name,
                'product_name' => $product_name,
                'product_color' => $product_color,
                'product_size' => $product_size,
                'quantity' => $quantity,
                'option_available' => $option_available,
                'address' => $address,
                'phone_number' => $phone_number,
                'notes' => $notes,
            ];
            
            $success = $branchOrder->update($order_id, $data);
            
            if ($success) {
                $_SESSION['message'] = "Order updated successfully!";
                $_SESSION['message_type'] = "success";
                header("Location: branch_orders_list.php");
                exit();
            } else {
                throw new Exception("Failed to update order. Please try again.");
            }
            
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-edit text-primary"></i> <?php echo __('edit_order'); ?> #<?php echo htmlspecialchars($order['order_number']); ?></h1>
    <a href="branch_orders_list.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> <?php echo __('back_to_orders'); ?>
    </a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('edit_order_details'); ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
                    
                    <h5 class="mb-3 text-secondary"><?php echo __('customer_information'); ?></h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="customer_name" class="form-label"><?php echo __('customer_name'); ?> *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                   value="<?php echo htmlspecialchars($order['customer_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone_number" class="form-label"><?php echo __('phone_number'); ?> *</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" 
                                   value="<?php echo htmlspecialchars($order['phone_number']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label"><?php echo __('address'); ?></label>
                        <textarea class="form-control" id="address" name="address" rows="2" 
                                  ><?php echo htmlspecialchars($order['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3 text-secondary"><?php echo __('product_details'); ?></h5>
                    
                    <div class="mb-3">
                        <label for="product_name_display" class="form-label"><?php echo __('product_name'); ?> *</label>
                        <input type="text" class="form-control" id="product_name_display" name="product_name" 
                               value="<?php echo htmlspecialchars($order['product_name']); ?>" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="product_color" class="form-label"><?php echo __('product_color'); ?></label>
                            <input type="text" class="form-control" id="product_color" name="product_color" 
                                   value="<?php echo htmlspecialchars($order['product_color'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="product_size" class="form-label"><?php echo __('product_size'); ?></label>
                            <input type="text" class="form-control" id="product_size" name="product_size" 
                                   value="<?php echo htmlspecialchars($order['product_size'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="quantity" class="form-label"><?php echo __('quantity'); ?> *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   min="1" value="<?php echo htmlspecialchars($order['quantity']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="option_available" class="form-label"><?php echo __('option_if_available'); ?></label>
                            <input type="text" class="form-control" id="option_available" name="option_available" 
                                   value="<?php echo htmlspecialchars($order['option_available'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  ><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo __('save_changes'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
