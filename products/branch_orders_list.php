<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Branch Activities - View Orders List
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/BranchOrder.php";
require_once "../config/auth.php";

$database = new Database();
$db = $database->getConnection();
$branchOrder = new BranchOrder($db);

$message = '';
$message_type = '';

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'customer_name' => $_GET['customer_name'] ?? '',
    'product_name' => $_GET['product_name'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Get orders and total count
$orders = $branchOrder->getAll($filters, $records_per_page, $offset);
$total_records = $branchOrder->getCount($filters);
$total_pages = ceil($total_records / $records_per_page);

// Handle status update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
        $_SESSION['message'] = "Security Error: Invalid Token";
        $_SESSION['message_type'] = "danger";
    } else {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        
        if ($branchOrder->updateStatus($order_id, $new_status)) {
            $_SESSION['message'] = "Order status updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to update order status.";
            $_SESSION['message_type'] = "danger";
        }
        
        header("Location: branch_orders_list.php?page=$page");
        exit();
    }
}

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-clipboard-list text-primary"></i> <?php echo __('branch_orders'); ?></h1>
    <div>
        <a href="branch_order_receive.php" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> <?php echo __('new_order'); ?>
        </a>
    </div>
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

<!-- Statistics Cards -->
<div class="row mb-4">
    <?php
    $stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                    FROM branch_orders";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Orders</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Processing</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['processing']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-spinner fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['completed']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?php echo __('filter_orders'); ?></h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label"><?php echo __('status'); ?></label>
                <select class="form-select" id="status" name="status">
                    <option value=""><?php echo __('all_statuses'); ?></option>
                    <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>><?php echo __('pending'); ?></option>
                    <option value="processing" <?php echo $filters['status'] === 'processing' ? 'selected' : ''; ?>><?php echo __('processing'); ?></option>
                    <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>><?php echo __('completed'); ?></option>
                    <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>><?php echo __('cancelled'); ?></option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="customer_name" class="form-label"><?php echo __('customer_name'); ?></label>
                <input type="text" class="form-control" id="customer_name" name="customer_name" 
                       placeholder="<?php echo __('search_customer'); ?>" value="<?php echo htmlspecialchars($filters['customer_name']); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="product_name" class="form-label"><?php echo __('product_name'); ?></label>
                <input type="text" class="form-control" id="product_name" name="product_name" 
                       placeholder="<?php echo __('search_product'); ?>" value="<?php echo htmlspecialchars($filters['product_name']); ?>">
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> <?php echo __('filter'); ?>
                </button>
                <a href="branch_orders_list.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> <?php echo __('reset'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?php echo __('orders_list'); ?></h6>
        <span class="badge bg-secondary"><?php echo $total_records; ?> <?php echo __('records'); ?></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('order_number'); ?></th>
                        <th><?php echo __('customer'); ?></th>
                        <th><?php echo __('product'); ?></th>
                        <th><?php echo __('color'); ?></th>
                        <th><?php echo __('size'); ?></th>
                        <th><?php echo __('qty'); ?></th>
                        <th><?php echo __('phone'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('date'); ?></th>
                        <th><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars(substr($order['address'] ?? '', 0, 30)); ?><?php echo strlen($order['address'] ?? '') > 30 ? '...' : ''; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['product_color'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($order['product_size'] ?? '-'); ?></td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($order['phone_number']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $order['status'] === 'completed' ? 'success' : 
                                        ($order['status'] === 'processing' ? 'warning' : 
                                        ($order['status'] === 'cancelled' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown">
                                        <?php echo __('options'); ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#viewOrderModal"
                                               data-order='<?php echo htmlspecialchars(json_encode($order), ENT_QUOTES, "UTF-8"); ?>'
                                               onclick="viewOrder(this)">
                                                <i class="fas fa-eye"></i> <?php echo __('view'); ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="branch_order_edit.php?id=<?php echo $order['id']; ?>">
                                                <i class="fas fa-edit"></i> <?php echo __('edit'); ?>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="">
                                                <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="" disabled selected><?php echo __('change_status'); ?></option>
                                                    <option value="pending"><?php echo __('pending'); ?></option>
                                                    <option value="processing"><?php echo __('processing'); ?></option>
                                                    <option value="completed"><?php echo __('completed'); ?></option>
                                                    <option value="cancelled"><?php echo __('cancelled'); ?></option>
                                                </select>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted"><?php echo __('no_orders_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($filters['status']); ?>&customer_name=<?php echo urlencode($filters['customer_name']); ?>&product_name=<?php echo urlencode($filters['product_name']); ?>"><?php echo __('previous'); ?></a>
                </li>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filters['status']); ?>&customer_name=<?php echo urlencode($filters['customer_name']); ?>&product_name=<?php echo urlencode($filters['product_name']); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($filters['status']); ?>&customer_name=<?php echo urlencode($filters['customer_name']); ?>&product_name=<?php echo urlencode($filters['product_name']); ?>"><?php echo __('next'); ?></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('order_details'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
function viewOrder(element) {
    const orderRaw = element.getAttribute('data-order');
    if (!orderRaw) return;
    
    const order = JSON.parse(orderRaw);
    const modalContent = document.getElementById('orderDetailsContent');
    
    // Status Badge Logic
    let statusClass = 'info';
    if(order.status === 'completed') statusClass = 'success';
    else if(order.status === 'processing') statusClass = 'warning';
    else if(order.status === 'cancelled') statusClass = 'danger';
    
    const formattedDate = new Date(order.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit' });

    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <strong><?php echo __('order_number'); ?>:</strong><br>
                <span>${order.order_number}</span>
            </div>
            <div class="col-md-6">
                <strong><?php echo __('status'); ?>:</strong><br>
                <span class="badge bg-${statusClass}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span>
            </div>
        </div>
        <hr>
        <div class="row mb-3">
            <div class="col-md-6">
                <h6 class="text-primary"><?php echo __('customer_info'); ?></h6>
                <strong>${order.customer_name}</strong><br>
                <i class="fas fa-phone fa-sm"></i> ${order.phone_number}<br>
                <i class="fas fa-map-marker-alt fa-sm"></i> ${order.address || 'N/A'}
            </div>
            <div class="col-md-6">
                <h6 class="text-primary"><?php echo __('product_details'); ?></h6>
                <strong>${order.product_name}</strong><br>
                Color: ${order.product_color || '-'}<br>
                Size: ${order.product_size || '-'}<br>
                Qty: ${order.quantity}<br>
                Options: ${order.option_available || '-'}
            </div>
        </div>
        <hr>
        <div class="row mb-3">
            <div class="col-12">
                <strong><?php echo __('notes'); ?>:</strong><br>
                <span>${order.notes || 'None'}</span>
            </div>
        </div>
        <div class="row">
            <div class="col-12 text-muted small">
                Created: ${formattedDate}
            </div>
        </div>
    `;
    
    modalContent.innerHTML = html;
}
</script>

<?php require_once "../includes/footer.php"; ?>
