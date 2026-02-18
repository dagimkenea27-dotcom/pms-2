<?php
// jimma/view_requests.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/AuditLog.php";
require_once "../config/auth.php";
require_once "../includes/functions.php";

// Auth check
Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();

// Handle Status Updates (Manager only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    Auth::requireRole('manager');
    header('Content-Type: application/json');
    
    $reqId = intval($_POST['request_id']);
    $newStatus = $_POST['status'];
    
    try {
        $stmt = $db->prepare("UPDATE product_requests SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $reqId]);
        
        $audit = new AuditLog($db);
        $audit->log($currentUser['id'], "STOCK_REQUEST_UPDATE", "Updated request #$reqId to $newStatus");
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch Filters
$status_filter = $_GET['status'] ?? '';
$branch_filter = $_GET['branch'] ?? 'Jimma';

// Data Fetching with robust PDO
try {
    $query = "SELECT r.*, p.name as product_name, p.sku as product_sku, v.size, v.color, u.first_name, u.last_name 
              FROM product_requests r 
              LEFT JOIN products p ON r.product_id = p.id 
              LEFT JOIN product_variants v ON r.variant_id = v.id 
              LEFT JOIN users u ON r.requester_id = u.id 
              WHERE 1=1";
    
    $params = [];
    if ($status_filter) {
        $query .= " AND r.status = ?";
        $params[] = $status_filter;
    }
    if ($branch_filter) {
        $query .= " AND r.branch = ?";
        $params[] = $branch_filter;
    }
    
    $query .= " ORDER BY r.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $requests = [];
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-list-check text-primary me-2"></i> Stock Requests Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="sales_request.php" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus"></i> New Request
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted text-uppercase">Branch</label>
                <select name="branch" class="form-select form-select-sm">
                    <option value="Jimma" <?= $branch_filter == 'Jimma' ? 'selected' : '' ?>>Jimma Branch</option>
                    <option value="Main" <?= $branch_filter == 'Main' ? 'selected' : '' ?>>Main Warehouse</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted text-uppercase">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="dispatched" <?= $status_filter == 'dispatched' ? 'selected' : '' ?>>Dispatched</option>
                    <option value="received" <?= $status_filter == 'received' ? 'selected' : '' ?>>Received</option>
                    <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-dark w-100"><i class="fas fa-filter me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-table me-2"></i>Request Log</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-muted text-uppercase">
                        <th class="ps-3">ID</th>
                        <th>Item Details</th>
                        <th>Qty</th>
                        <th>Requester</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted italic">No stock requests found matching criteria</td></tr>
                    <?php else: foreach ($requests as $req): 
                        $statusClass = 'bg-secondary';
                        if($req['status'] === 'pending') $statusClass = 'bg-warning text-dark';
                        if($req['status'] === 'approved') $statusClass = 'bg-info';
                        if($req['status'] === 'dispatched') $statusClass = 'bg-primary';
                        if($req['status'] === 'received') $statusClass = 'bg-success';
                        if($req['status'] === 'cancelled') $statusClass = 'bg-danger';

                        $priorityBadge = 'bg-light text-dark';
                        if($req['priority'] === 'high') $priorityBadge = 'bg-orange text-white';
                        if($req['priority'] === 'urgent') $priorityBadge = 'bg-danger text-white';
                    ?>
                        <tr>
                            <td class="ps-3"><span class="fw-bold text-muted">#<?= $req['id'] ?></span></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($req['product_name'] ?? $req['custom_item_name']) ?></div>
                                <div class="small text-muted fw-monospace" style="font-size: 0.7rem;">
                                    <?= $req['product_sku'] ? 'SKU: '.$req['product_sku'] : 'Custom Item' ?>
                                    <?= $req['size'] ? ' | '.$req['size'].' / '.$req['color'] : '' ?>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border fw-bold"><?= $req['quantity'] ?></span></td>
                            <td>
                                <div class="small fw-bold"><?= htmlspecialchars($req['first_name'].' '.$req['last_name']) ?></div>
                                <div class="small text-muted"><?= $req['branch'] ?></div>
                            </td>
                            <td><span class="badge <?= $priorityBadge ?> shadow-none"><?= strtoupper($req['priority']) ?></span></td>
                            <td><span class="badge <?= $statusClass ?> py-1 rounded-pill" style="font-size: 0.7rem;"><?= strtoupper($req['status']) ?></span></td>
                            <td>
                                <div class="small"><?= date('M j, Y', strtotime($req['created_at'])) ?></div>
                                <div class="small text-muted"><?= date('g:i A', strtotime($req['created_at'])) ?></div>
                            </td>
                            <td class="text-end pe-3">
                                <?php if (Auth::hasRole('manager')): ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown">Manage</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            <li><a class="dropdown-item small" href="javascript:void(0)" onclick="updateStatus(<?= $req['id'] ?>, 'approved')">Approve</a></li>
                                            <li><a class="dropdown-item small" href="javascript:void(0)" onclick="updateStatus(<?= $req['id'] ?>, 'dispatched')">Mark Dispatched</a></li>
                                            <li><a class="dropdown-item small text-success" href="javascript:void(0)" onclick="updateStatus(<?= $req['id'] ?>, 'received')">Mark Received</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item small text-danger" href="javascript:void(0)" onclick="updateStatus(<?= $req['id'] ?>, 'cancelled')">Cancel Request</a></li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function updateStatus(id, status) {
    Swal.fire({
        title: 'Update Status?',
        text: `Are you sure you want to mark this request as ${status}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4e73df'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('request_id', id);
            formData.append('status', status);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Updated!', 'The request status has been updated.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Update failed', 'error');
                }
            });
        }
    });
}
</script>

<?php require_once "../includes/footer.php"; ?>
