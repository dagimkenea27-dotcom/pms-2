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

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-invoice"></i> Purchase Orders</h1>
    <a href="add_po.php" class="btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Create New PO
    </a>
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
