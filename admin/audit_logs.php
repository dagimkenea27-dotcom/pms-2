<?php
// admin/audit_logs.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/AuditLog.php";
require_once "../config/auth.php";

// Ensure user is logged in
Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();
$audit = new AuditLog($db);

// Fetch logs
$stmt = $audit->read(100); // Get last 100 logs

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-history"></i> Audit Logs</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">System Activity Log</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td>
                            <i class="fas fa-user-circle text-secondary"></i> 
                            <?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?>
                        </td>
                        <td>
                            <?php 
                            $badgeClass = 'secondary';
                            if (strpos($row['action'], 'LOGIN') !== false) $badgeClass = 'success';
                            if (strpos($row['action'], 'LOGOUT') !== false) $badgeClass = 'warning';
                            if (strpos($row['action'], 'PRODUCT') !== false) $badgeClass = 'info';
                            if (strpos($row['action'], 'STOCK') !== false) $badgeClass = 'primary';
                            if (strpos($row['action'], 'DELETE') !== false) $badgeClass = 'danger';
                            ?>
                            <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['action']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($row['details']); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
