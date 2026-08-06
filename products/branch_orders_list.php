<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Branch Activities - View Orders List
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/BranchOrder.php";
require_once "../models/User.php";
require_once "../config/auth.php";

$database = new Database();
$db = $database->getConnection();
$branchOrder = new BranchOrder($db);

$userModel = new User($db);
$users_stmt = $userModel->read();
$all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    'date_to' => $_GET['date_to'] ?? '',
    'created_by' => $_GET['created_by'] ?? ''
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
        $cancellation_reason = $_POST['cancellation_reason'] ?? null;
        $delivery_person = $_POST['delivery_person'] ?? null;
        
        if ($branchOrder->updateStatus($order_id, $new_status, $cancellation_reason, $delivery_person)) {
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

// Handle delete order via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
        $_SESSION['message'] = "Security Error: Invalid Token";
        $_SESSION['message_type'] = "danger";
    } else {
        $order_id = $_POST['order_id'];
        
        if ($branchOrder->delete($order_id)) {
            $_SESSION['message'] = "Order deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to delete order.";
            $_SESSION['message_type'] = "danger";
        }
        
        header("Location: branch_orders_list.php?page=$page");
        exit();
    }
}

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-clipboard-list text-primary"></i> <?php echo __('Meta Order Management'); ?></h1>
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
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                    FROM branch_orders";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Processing</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['processing']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Shipped</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['shipped']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['completed']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Returned</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['returned']; ?></div>
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
                    <option value="shipped" <?php echo $filters['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>><?php echo __('completed'); ?></option>
                    <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>><?php echo __('cancelled'); ?></option>
                    <option value="returned" <?php echo $filters['status'] === 'returned' ? 'selected' : ''; ?>>Returned</option>
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
            
            <div class="col-md-3">
                <label for="created_by" class="form-label">Received By</label>
                <select class="form-select" id="created_by" name="created_by">
                    <option value="">All Users</option>
                    <?php foreach($all_users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $filters['created_by'] == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end mt-3">
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
                        <th>Items Summary</th>
                        <th class="text-center">Total Qty</th>
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
                            <td>
                                <span class="small"><?php echo htmlspecialchars($order['product_summary'] ?: 'No items'); ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><?php echo $order['total_quantity'] ?: 0; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($order['phone_number']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $order['status'] === 'completed' ? 'success' : 
                                        ($order['status'] === 'processing' ? 'warning' : 
                                        ($order['status'] === 'returned' ? 'danger' : 
                                        ($order['status'] === 'shipped' ? 'secondary' : 
                                        ($order['status'] === 'cancelled' ? 'danger' : 'info')))); 
                                ?>" <?php echo ($order['status'] === 'cancelled' && !empty($order['cancellation_reason'])) ? 'title="Reason: ' . htmlspecialchars($order['cancellation_reason']) . '" data-bs-toggle="tooltip"' : ''; ?>
                                    <?php echo (($order['status'] === 'shipped' || $order['status'] === 'completed') && !empty($order['delivery_person'])) ? 'title="Delivery: ' . htmlspecialchars($order['delivery_person']) . '" data-bs-toggle="tooltip"' : ''; ?>>
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <?php if (!empty($order['delivery_person'])): ?>
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                        <i class="fas fa-truck fa-xs"></i> <?php echo htmlspecialchars($order['delivery_person']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                <?php if (!empty($order['created_by_username'])): ?>
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                        <i class="fas fa-user-edit fa-xs"></i> <?php echo htmlspecialchars($order['created_by_username']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
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
                                               data-id="<?php echo $order['id']; ?>"
                                               onclick="viewOrder(this)">
                                                <i class="fas fa-eye text-info"></i> <?php echo __('view'); ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="branch_order_edit.php?id=<?php echo $order['id']; ?>">
                                                <i class="fas fa-edit"></i> <?php echo __('edit'); ?>
                                            </a>
                                        </li>
                                        <li>
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to completely delete this order?');" class="m-0 p-0">
                                                <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
                                                <input type="hidden" name="delete_order" value="1">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash-alt"></i> <?php echo __('delete'); ?>
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="">
                                                <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="new_status" class="form-select form-select-sm" onchange="handleStatusChange(this)">
                                                    <option value="" disabled selected><?php echo __('change_status'); ?></option>
                                                    <option value="pending"><?php echo __('pending'); ?></option>
                                                    <option value="processing"><?php echo __('processing'); ?></option>
                                                    <option value="shipped">Shipped</option>
                                                    <option value="completed"><?php echo __('completed'); ?></option>
                                                    <option value="cancelled"><?php echo __('cancelled'); ?></option>
                                                    <option value="returned"><?php echo __('returned'); ?></option>
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
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($filters); ?>"><?php echo __('previous'); ?></a>
                </li>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($filters); ?>"><?php echo __('next'); ?></a>
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
            <div class="modal-header bg-gradient-primary text-white border-0 py-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-file-invoice me-2"></i> <?php echo __('order_details'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <a href="#" id="printOrderBtn" target="_blank" class="btn btn-primary"><i class="fas fa-print"></i> Print Order</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Select Delivery Person Modal -->
<div class="modal fade" id="deliveryPersonModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title small text-uppercase font-weight-bold">Select Delivery Person</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="cancelStatusChange()"></button>
            </div>
            <div class="modal-body p-0">
                <div class="list-group list-group-flush">
                    <button type="button" class="list-group-item list-group-item-action py-3" onclick="setDeliveryPerson('Abinet Mathewos')">
                        <i class="fas fa-user-circle text-primary me-2"></i> Abinet Mathewos
                    </button>
                    <button type="button" class="list-group-item list-group-item-action py-3" onclick="setDeliveryPerson('Abebe Ayiza')">
                        <i class="fas fa-user-circle text-primary me-2"></i> Abebe Ayiza
                    </button>
                    <button type="button" class="list-group-item list-group-item-action py-3" onclick="setDeliveryPerson('Tamirat Bekalu')">
                        <i class="fas fa-user-circle text-primary me-2"></i> Tamirat Bekalu
                    </button>
                    <button type="button" class="list-group-item list-group-item-action py-3 bg-light text-primary" onclick="customDeliveryPerson()">
                        <i class="fas fa-plus-circle me-2"></i> Other Person...
                    </button>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" onclick="cancelStatusChange()">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentStatusSelect = null;

function cancelStatusChange() {
    if (currentStatusSelect) {
        currentStatusSelect.selectedIndex = 0;
    }
}

function setDeliveryPerson(name) {
    if (!currentStatusSelect) return;
    
    let personInput = currentStatusSelect.form.querySelector('input[name="delivery_person"]');
    if (!personInput) {
        personInput = document.createElement('input');
        personInput.type = 'hidden';
        personInput.name = 'delivery_person';
        currentStatusSelect.form.appendChild(personInput);
    }
    personInput.value = name;
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('deliveryPersonModal'));
    if (modal) modal.hide();
    currentStatusSelect.form.submit();
}

function customDeliveryPerson() {
    const person = prompt("Please enter the Delivery Person's Name:");
    if (person) {
        setDeliveryPerson(person);
    } else {
        cancelStatusChange();
    }
}
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});

function handleStatusChange(selectElement) {
    const status = selectElement.value;
    if (status === 'cancelled') {
        const reason = prompt("Please enter the reason for cancellation:");
        if (reason === null) {
            selectElement.selectedIndex = 0; // Reset
            return;
        }
        
        // Add reason to a hidden input
        let reasonInput = selectElement.form.querySelector('input[name="cancellation_reason"]');
        if (!reasonInput) {
            reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'cancellation_reason';
            selectElement.form.appendChild(reasonInput);
        }
        reasonInput.value = reason;
    } else if (status === 'shipped') {
        currentStatusSelect = selectElement;
        const deliveryModal = new bootstrap.Modal(document.getElementById('deliveryPersonModal'));
        deliveryModal.show();
        return; // Don't submit yet
    }
    selectElement.form.submit();
}

function viewOrder(element) {
    const orderId = element.getAttribute('data-id');
    const modalContent = document.getElementById('orderDetailsContent');
    const printBtn = document.getElementById('printOrderBtn');
    
    printBtn.href = `print_branch_order.php?id=${orderId}`;
    modalContent.innerHTML = '<div class="text-center my-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading details...</p></div>';
    
    fetch(`../api/get_branch_order_details.php?id=${orderId}`)
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                modalContent.innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
                return;
            }
            const order = res.data;
            
            let statusClass = 'info';
            if(order.status === 'completed') statusClass = 'success';
            else if(order.status === 'processing') statusClass = 'warning';
            else if(order.status === 'shipped') statusClass = 'secondary';
            else if(order.status === 'cancelled') statusClass = 'danger';
            else if(order.status === 'returned') statusClass = 'danger';
            
            const formattedDate = new Date(order.created_at).toLocaleDateString();

            let productsHtml = '';
            order.products.forEach(p => {
                let variationsHtml = '';
                p.variations.forEach(v => {
                    variationsHtml += `
                        <tr>
                            <td>${v.color || '-'}</td>
                            <td>${v.size || '-'}</td>
                            <td><strong>${v.quantity}</strong></td>
                            <td>${v.options || '-'}</td>
                        </tr>`;
                });

                productsHtml += `
                <div class="card mb-3 border-left-info shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                ${p.image_path ? `<img src="../${p.image_path}" class="img-fluid rounded border shadow-sm" alt="Product">` : `<div class="bg-light rounded border p-3 text-center"><i class="fas fa-image fa-2x text-gray-300"></i></div>`}
                            </div>
                            <div class="col-md-9">
                                <h6 class="font-weight-bold mb-3">${p.product_name}</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr class="small text-uppercase">
                                                <th>Color</th>
                                                <th>Size</th>
                                                <th>Qty</th>
                                                <th>Options</th>
                                            </tr>
                                        </thead>
                                        <tbody>${variationsHtml}</tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            });

            modalContent.innerHTML = `
                <div class="row align-items-center mb-4">
                    <div class="col-md-7">
                        <h4 class="font-weight-bold text-primary mb-1">${order.order_number}</h4>
                        <div class="d-flex align-items-center flex-wrap">
                            <span class="badge bg-${statusClass} px-3 py-2 text-uppercase shadow-sm mb-1 me-2" style="font-size: 0.7rem; border-radius: 50px;">${order.status}</span>
                            ${order.source ? `<span class="badge bg-light text-primary border px-3 py-2 shadow-sm mb-1 me-2" style="font-size: 0.7rem; border-radius: 50px;"><i class="fas fa-bullhorn me-1"></i> ${order.source}</span>` : ''}
                            ${order.delivery_person ? `<span class="text-muted small mb-1"><i class="fas fa-truck me-1"></i> ${order.delivery_person}</span>` : ''}
                        </div>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <div class="text-xs text-uppercase text-muted font-weight-bold mb-0">Order Date</div>
                        <div class="font-weight-bold text-gray-800">${formattedDate}</div>
                    </div>
                </div>
                
                ${order.status === 'cancelled' && order.cancellation_reason ? `
                <div class="card border-left-danger shadow-sm mb-4">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center text-danger mb-1">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <h6 class="mb-0 font-weight-bold text-uppercase small">Cancellation Reason</h6>
                        </div>
                        <p class="mb-0 text-gray-800 small">${order.cancellation_reason}</p>
                    </div>
                </div>
                ` : ''}

                ${(order.status === 'shipped' || order.status === 'completed') && order.delivery_person ? `
                <div class="card border-left-info shadow-sm mb-4 bg-light">
                    <div class="card-body py-3">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="bg-info text-white rounded-circle p-2 shadow-sm">
                                    <i class="fas fa-truck fa-sm"></i>
                                </div>
                            </div>
                            <div class="col">
                                <div class="text-xs text-uppercase text-info font-weight-bold mb-0">Delivery Partner</div>
                                <div class="font-weight-bold text-gray-800">${order.delivery_person}</div>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100 border-0 bg-light shadow-none">
                            <div class="card-body p-3">
                                <div class="text-xs text-uppercase text-muted font-weight-bold mb-2"><i class="fas fa-user me-1 text-primary"></i> Customer</div>
                                <h6 class="font-weight-bold mb-1 text-gray-800">${order.customer_name}</h6>
                                <p class="mb-0 small text-muted"><i class="fas fa-phone fa-xs me-1"></i> ${order.phone_number}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 border-0 bg-light shadow-none">
                            <div class="card-body p-3">
                                <div class="text-xs text-uppercase text-muted font-weight-bold mb-2"><i class="fas fa-map-marker-alt me-1 text-primary"></i> Shipping Address</div>
                                <p class="mb-0 small text-gray-800">${order.address || 'No address provided'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="font-weight-bold text-primary mb-0">Products & Inventory</h6>
                    <span class="badge bg-light text-dark border small">${order.products.length} Products</span>
                </div>
                ${productsHtml}

                ${order.notes ? `
                <div class="mt-4 p-3 bg-light rounded border-left-secondary shadow-sm">
                    <div class="text-xs text-uppercase text-muted font-weight-bold mb-2">Order Notes</div>
                    <div class="text-gray-800 small italic" style="font-style: italic;">"${order.notes.replace(/\n/g, '<br>')}"</div>
                </div>` : ''}
            `;
        })
        .catch(err => {
            modalContent.innerHTML = `<div class="alert alert-danger">Error loading data: ${err.message}</div>`;
        });
}
</script>

<?php require_once "../includes/footer.php"; ?>
