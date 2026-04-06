<?php
// Branch Activities - Order Receiving Form
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/BranchOrder.php";
require_once "../config/auth.php";

$database = new Database();
$db = $database->getConnection();
$branchOrder = new BranchOrder($db);

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
        $message = "Security Error: Invalid Token";
        $message_type = "danger";
    } else {
        try {
            $order_number = $branchOrder->generateOrderNumber();
            $customer_name = trim($_POST['customer_name']);
            $product_id = !empty($_POST['product_id']) ? $_POST['product_id'] : null;
            $product_name = trim($_POST['product_name']);
            $product_color = trim($_POST['product_color']);
            $product_size = trim($_POST['product_size']);
            $quantity = intval($_POST['quantity']);
            $option_available = trim($_POST['option_available']);
            $address = trim($_POST['address']);
            $phone_number = trim($_POST['phone_number']);
            $notes = trim($_POST['notes']);
            
            // Validation
            if (empty($customer_name)) {
                throw new Exception("Customer name is required.");
            }
            
            if (empty($product_name)) {
                throw new Exception("Product name is required.");
            }
            
            if ($quantity <= 0) {
                throw new Exception("Quantity must be greater than zero.");
            }
            
            if (empty($phone_number)) {
                throw new Exception("Phone number is required.");
            }
            
            $data = [
                'order_number' => $order_number,
                'customer_name' => $customer_name,
                'product_id' => null, // No database connection
                'variant_id' => null, // No database connection
                'product_name' => $product_name,
                'product_color' => $product_color,
                'product_size' => $product_size,
                'quantity' => $quantity,
                'option_available' => $option_available,
                'address' => $address,
                'phone_number' => $phone_number,
                'status' => 'pending',
                'notes' => $notes,
                'created_by' => Auth::getCurrentUser()['id']
            ];
            
            $order_id = $branchOrder->create($data);
            
            if ($order_id) {
                $message = "Order received successfully! Order Number: " . $order_number;
                $message_type = "success";
                
                // PRG Pattern
                $_SESSION['message'] = $message;
                $_SESSION['message_type'] = $message_type;
                header("Location: branch_order_receive.php");
                exit();
            } else {
                throw new Exception("Failed to create order. Please try again.");
            }
            
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// No AJAX endpoints needed since product lookups are removed

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-clipboard-list text-primary"></i> <?php echo __('branch_order_receiving'); ?></h1>
    <a href="branch_orders_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-list fa-sm text-white-50"></i> <?php echo __('view_orders'); ?>
    </a>
</div>

<?php 
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('new_branch_order'); ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
                    
                    <h5 class="mb-3 text-secondary"><?php echo __('customer_information'); ?></h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="customer_name" class="form-label"><?php echo __('customer_name'); ?> *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                   placeholder="<?php echo __('enter_customer_name'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone_number" class="form-label"><?php echo __('phone_number'); ?> *</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" 
                                   placeholder="<?php echo __('enter_phone_number'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label"><?php echo __('address'); ?></label>
                        <textarea class="form-control" id="address" name="address" rows="2" 
                                  placeholder="<?php echo __('enter_delivery_address'); ?>"></textarea>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3 text-secondary"><?php echo __('product_details'); ?></h5>
                    
                    <div class="mb-3">
                        <label for="product_name_display" class="form-label"><?php echo __('product_name'); ?> *</label>
                        <input type="text" class="form-control" id="product_name_display" name="product_name" 
                               placeholder="<?php echo __('enter_product_name'); ?>" required>
                        <small class="text-muted">Enter the name of the product manually.</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="product_color" class="form-label"><?php echo __('product_color'); ?></label>
                            <input type="text" class="form-control" id="product_color" name="product_color" 
                                   placeholder="<?php echo __('e.g.'); ?> Red, Blue, Black">
                        </div>
                        <div class="col-md-6">
                            <label for="product_size" class="form-label"><?php echo __('product_size'); ?></label>
                            <input type="text" class="form-control" id="product_size" name="product_size" 
                                   placeholder="<?php echo __('e.g.'); ?> S, M, L, XL">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="quantity" class="form-label"><?php echo __('quantity'); ?> *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   min="1" value="1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="option_available" class="form-label"><?php echo __('option_if_available'); ?></label>
                            <input type="text" class="form-control" id="option_available" name="option_available" 
                                   placeholder="<?php echo __('any_special_options'); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="<?php echo __('additional_notes'); ?>"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo __('receive_order'); ?>
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> <?php echo __('reset_form'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card dashboard-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info"><?php echo __('instructions'); ?></h6>
            </div>
            <div class="card-body">
                <p><strong><?php echo __('how_to_receive_order'); ?></strong></p>
                <ol class="small">
                    <li><?php echo __('enter_customer_info'); ?></li>
                    <li><?php echo __('select_or_enter_product'); ?></li>
                    <li><?php echo __('specify_details'); ?></li>
                    <li><?php echo __('add_quantity_options'); ?></li>
                    <li><?php echo __('click_receive'); ?></li>
                </ol>
                <hr>
                <p class="text-muted small mb-0">
                    <i class="fas fa-info-circle"></i> 
                    <?php echo __('order_will_assigned'); ?>
                </p>
            </div>
        </div>
        
        <div class="card dashboard-card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success"><?php echo __('recent_orders'); ?></h6>
            </div>
            <div class="card-body p-0">
                <?php
                $recent_query = "SELECT order_number, customer_name, product_name, status, created_at 
                                 FROM branch_orders 
                                 ORDER BY created_at DESC 
                                 LIMIT 5";
                $recent_stmt = $db->prepare($recent_query);
                $recent_stmt->execute();
                $recent_orders = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($recent_orders) > 0):
                ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_orders as $order): ?>
                    <div class="list-group-item small">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                            </div>
                            <span class="badge bg-<?php 
                                echo $order['status'] === 'completed' ? 'success' : 
                                    ($order['status'] === 'processing' ? 'warning' : 
                                    ($order['status'] === 'cancelled' ? 'danger' : 'info')); 
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="mt-1 text-muted small">
                            <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted text-center my-3"><?php echo __('no_recent_orders'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- No complex JavaScript needed for simple forms -->

<?php require_once "../includes/footer.php"; ?>
