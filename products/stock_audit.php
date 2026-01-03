<?php
// products/stock_audit.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../includes/functions.php";

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';

$where_clauses = [];
$params = [];

if ($product_id) {
    $where_clauses[] = "sh.product_id = :product_id";
    $params[':product_id'] = $product_id;
}
if ($user_id) {
    $where_clauses[] = "sh.user_id = :user_id";
    $params[':user_id'] = $user_id;
}
if ($type) {
    $where_clauses[] = "sh.change_type = :type";
    $params[':type'] = $type;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total for pagination
$count_query = "SELECT COUNT(*) FROM stock_history sh $where_sql";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Get main query
$query = "SELECT sh.*, p.name as product_name, p.sku as product_sku, 
                 pv.size, pv.color, pv.sku as variant_sku,
                 u.username, u.first_name, u.last_name
          FROM stock_history sh
          JOIN products p ON sh.product_id = p.id
          LEFT JOIN product_variants pv ON sh.variant_id = pv.id
          JOIN users u ON sh.user_id = u.id
          $where_sql
          ORDER BY sh.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-history"></i> Stock Audit Log</h1>
    <div>
        <a href="view_products.php" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-box fa-sm"></i> Products
        </a>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Filters</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Sort by Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="in" <?php echo $type == 'in' ? 'selected' : ''; ?>>Stock In</option>
                    <option value="out" <?php echo $type == 'out' ? 'selected' : ''; ?>>Stock Out</option>
                    <option value="adjustment" <?php echo $type == 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                    <option value="correction" <?php echo $type == 'correction' ? 'selected' : ''; ?>>Correction</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="stock_audit.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="table-light">
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Qty Change</th>
                        <th>Final Stock</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td>
                                <small class="text-primary fw-bold"><?php echo htmlspecialchars($log['username']); ?></small>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($log['product_name']); ?></div>
                                <?php if ($log['variant_id']): ?>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($log['size'] . ' / ' . $log['color']); ?> 
                                        (<?php echo htmlspecialchars($log['variant_sku']); ?>)
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($log['product_sku']); ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $log['change_type'] == 'in' ? 'success' : 
                                        ($log['change_type'] == 'out' ? 'danger' : 'info'); 
                                ?>">
                                    <?php echo strtoupper($log['change_type']); ?>
                                </span>
                            </td>
                            <td class="fw-bold <?php echo $log['quantity_change'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $log['quantity_change'] > 0 ? '+' : ''; echo $log['quantity_change']; ?>
                            </td>
                            <td class="text-center bg-light"><?php echo $log['quantity_after']; ?></td>
                            <td><small><?php echo htmlspecialchars($log['notes']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">No audit logs found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&type=<?php echo $type; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $type; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&type=<?php echo $type; ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
