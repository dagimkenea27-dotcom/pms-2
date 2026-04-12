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
                <a href="#" id="printOrderBtn" target="_blank" class="btn btn-primary"><i class="fas fa-print"></i> Print Order</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
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
            else if(order.status === 'cancelled') statusClass = 'danger';
            
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
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>${order.order_number}</strong></p>
                        <span class="badge bg-${statusClass} text-uppercase">${order.status}</span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">Date: ${formattedDate}</small>
                    </div>
                </div>
                <div class="card mb-4 bg-light border-0 shadow-none">
                    <div class="card-body py-3">
                        <div class="row">
                            <div class="col-md-6 border-right">
                                <label class="text-xs text-uppercase text-muted font-weight-bold mb-1">Customer</label>
                                <p class="mb-0 font-weight-bold">${order.customer_name}</p>
                                <p class="mb-0 small"><i class="fas fa-phone fa-xs"></i> ${order.phone_number}</p>
                            </div>
                            <div class="col-md-6 ps-md-4">
                                <label class="text-xs text-uppercase text-muted font-weight-bold mb-1">Shipping Address</label>
                                <p class="mb-0 small">${order.address || 'No address provided'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h6 class="font-weight-bold text-primary mb-3">Products & Images</h6>
                ${productsHtml}

                ${order.notes ? `
                <div class="mt-4">
                    <label class="text-xs text-uppercase text-muted font-weight-bold mb-1">Order Notes</label>
                    <div class="p-3 bg-light rounded text-dark small">${order.notes.replace(/\n/g, '<br>')}</div>
                </div>` : ''}
            `;
        })
        .catch(err => {
            modalContent.innerHTML = `<div class="alert alert-danger">Error loading data: ${err.message}</div>`;
        });
}
</script>

<?php require_once "../includes/footer.php"; ?>
