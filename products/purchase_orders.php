<?php
// products/purchase_orders.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../includes/functions.php";

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get PO list
$query = "SELECT po.*, s.name as supplier_name, u.username as creator_name
          FROM purchase_orders po
          JOIN suppliers s ON po.supplier_id = s.id
          JOIN users u ON po.created_by = u.id
          ORDER BY po.created_at DESC";
$stmt = $db->query($query);
$pos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dashboard Metrics
$total_pos = count($pos);
$ordered_pos = 0;
$received_pos = 0;
$draft_pos = 0;

$total_amount_all = 0;
$total_amount_ordered = 0;
$total_amount_received = 0;
$total_amount_draft = 0;

foreach ($pos as $po) {
    $amount = (float)$po['total_amount'];
    $total_amount_all += $amount;
    
    if ($po['status'] == 'ordered') {
        $ordered_pos++;
        $total_amount_ordered += $amount;
    } elseif ($po['status'] == 'received') {
        $received_pos++;
        $total_amount_received += $amount;
    } elseif ($po['status'] == 'draft') {
        $draft_pos++;
        $total_amount_draft += $amount;
    }
}

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-invoice"></i> Purchase Orders</h1>
    <a href="add_po.php" class="btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Create New PO
    </a>
</div>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Orders</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_pos; ?></div>
                        <div class="text-xs font-weight-bold text-muted mt-1">$<?php echo number_format($total_amount_all, 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Ordered (Waiting)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $ordered_pos; ?></div>
                        <div class="text-xs font-weight-bold text-muted mt-1">$<?php echo number_format($total_amount_ordered, 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-truck-loading fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Received</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $received_pos; ?></div>
                        <div class="text-xs font-weight-bold text-muted mt-1">$<?php echo number_format($total_amount_received, 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Drafts</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $draft_pos; ?></div>
                        <div class="text-xs font-weight-bold text-muted mt-1">$<?php echo number_format($total_amount_draft, 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-pencil-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Supplier</th>
                        <th>Created At</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pos): ?>
                        <?php foreach ($pos as $po): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($po['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($po['supplier_name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($po['created_at'])); ?></td>
                            <td>$<?php echo number_format($po['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $po['status'] == 'received' ? 'success' : 
                                        ($po['status'] == 'ordered' ? 'primary' : 
                                        ($po['status'] == 'cancelled' ? 'danger' : 'secondary')); 
                                ?>">
                                    <?php echo strtoupper($po['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($po['creator_name']); ?></td>
                            <td>
                                <a href="view_po.php?id=<?php echo $po['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($po['status'] !== 'received' && $po['status'] !== 'cancelled'): ?>
                                    <a href="edit_po.php?id=<?php echo $po['id']; ?>" class="btn btn-sm btn-warning" title="Edit PO">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="print_po.php?id=<?php echo $po['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Print PO" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <?php if ($po['status'] !== 'received' || Auth::hasRole('admin')): ?>
                                    <a href="delete_po.php?id=<?php echo $po['id']; ?>" class="btn btn-sm btn-danger" title="Delete PO" onclick="return confirm('Are you sure you want to delete this Purchase Order? This will also remove all item records associated with it.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-share-alt"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php
                                        $s_text = "📦 *PO: " . $po['order_number'] . "*\nSupplier: " . $po['supplier_name'] . "\nTotal: $" . number_format($po['total_amount'], 2);
                                        $s_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . BASE_URL . "products/print_po.php?id=" . $po['id'];
                                        ?>
                                        <li><a class="dropdown-item" href="https://t.me/share/url?url=<?php echo urlencode($s_url); ?>&text=<?php echo urlencode($s_text); ?>" target="_blank"><i class="fab fa-telegram text-info"></i> Telegram</a></li>
                                        <li><a class="dropdown-item" href="mailto:?subject=Purchase Order <?php echo $po['order_number']; ?>&body=<?php echo urlencode("PO: " . $po['order_number'] . "\nSupplier: " . $po['supplier_name'] . "\nTotal: $" . number_format($po['total_amount'], 2) . "\nPrintable PO: " . $s_url); ?>"><i class="fas fa-envelope text-primary"></i> Email</a></li>
                                    </ul>
                                </div>
                                <?php if ($po['status'] == 'draft'): ?>
                                    <button class="btn btn-sm btn-success mark-ordered" data-id="<?php echo $po['id']; ?>" title="Mark as Ordered">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">No purchase orders found. Click "Create New PO" to start.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
