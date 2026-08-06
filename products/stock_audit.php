<?php
// products/stock_audit.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../includes/functions.php";

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

// ── Filters ────────────────────────────────────────────────────────────────
$product_id = isset($_GET['product_id']) ? trim($_GET['product_id']) : '';
$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
$driver_id = isset($_GET['driver_id']) ? trim($_GET['driver_id']) : '';
$supplier_id = isset($_GET['supplier_id']) ? trim($_GET['supplier_id']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';

$where_clauses = [];
$params = [];

if ($product_id) {
    $where_clauses[] = "sm.product_id = :product_id";
    $params[':product_id'] = $product_id;
}
if ($user_id) {
    $where_clauses[] = "sm.user_id = :user_id";
    $params[':user_id'] = $user_id;
}
if ($driver_id) {
    $where_clauses[] = "sm.driver_id = :driver_id";
    $params[':driver_id'] = $driver_id;
}
if ($supplier_id) {
    $where_clauses[] = "sm.supplier_id = :supplier_id";
    $params[':supplier_id'] = $supplier_id;
}
if ($type) {
    $where_clauses[] = "sm.movement_type = :type";
    $params[':type'] = strtoupper($type);
}
if ($date_from) {
    $where_clauses[] = "DATE(sm.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}
if ($date_to) {
    $where_clauses[] = "DATE(sm.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}
if ($reference) {
    $where_clauses[] = "sm.reference LIKE :reference";
    $params[':reference'] = '%' . $reference . '%';
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// ── Summary Stats ──────────────────────────────────────────────────────────
$stats_query = "SELECT
    COUNT(*) as total_txn,
    SUM(CASE WHEN sm.movement_type = 'IN'  THEN sm.quantity ELSE 0 END) as total_in,
    SUM(CASE WHEN sm.movement_type = 'OUT' THEN sm.quantity ELSE 0 END) as total_out
  FROM stock_movements sm
  $where_sql";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// ── CSV Export ─────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_query = "SELECT
        sm.created_at,
        COALESCE(CONCAT(u.first_name, ' ', u.last_name), u.username, 'System') as user_name,
        u.username,
        p.sku as product_sku,
        p.name as product_name,
        pv.size,
        pv.color,
        pv.sku as variant_sku,
        sm.movement_type,
        sm.quantity,
        sm.reason,
        sm.reference,
        d.full_name as driver_name,
        s.name as supplier_name
      FROM stock_movements sm
      JOIN products p ON sm.product_id = p.id
      LEFT JOIN product_variants pv ON sm.variant_id = pv.id
      LEFT JOIN users u ON sm.user_id = u.id
      LEFT JOIN drivers d ON sm.driver_id = d.id
      LEFT JOIN suppliers s ON sm.supplier_id = s.id
      $where_sql
      ORDER BY sm.created_at DESC";
    $export_stmt = $db->prepare($export_query);
    $export_stmt->execute($params);
    $rows = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="stock_audit_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Timestamp', 'User', 'Username', 'Product SKU', 'Product Name', 'Size', 'Color', 'Variant SKU', 'Type', 'Quantity', 'Reason', 'Reference', 'Driver', 'Supplier']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['created_at'],
            $row['user_name'],
            $row['username'],
            $row['product_sku'],
            $row['product_name'],
            $row['size'] ?? '',
            $row['color'] ?? '',
            $row['variant_sku'] ?? '',
            $row['movement_type'],
            $row['quantity'],
            $row['reason'],
            $row['reference'] ?? '',
            $row['driver_name'] ?? '',
            $row['supplier_name'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ── Pagination ─────────────────────────────────────────────────────────────
$limit = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) FROM stock_movements sm $where_sql";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// ── Main Query ─────────────────────────────────────────────────────────────
$query = "SELECT
    sm.*,
    p.name  as product_name,
    p.sku   as product_sku,
    pv.size, pv.color, pv.sku as variant_sku,
    COALESCE(CONCAT(u.first_name, ' ', u.last_name), u.username, 'System') as user_name,
    u.username,
    d.full_name as driver_name,
    s.name      as supplier_name
  FROM stock_movements sm
  JOIN products p ON sm.product_id = p.id
  LEFT JOIN product_variants pv ON sm.variant_id = pv.id
  LEFT JOIN users u ON sm.user_id = u.id
  LEFT JOIN drivers d ON sm.driver_id = d.id
  LEFT JOIN suppliers s ON sm.supplier_id = s.id
  $where_sql
  ORDER BY sm.created_at DESC
  LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Filter dropdown data ───────────────────────────────────────────────────
$all_products = $db->query("SELECT id, sku, name FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$all_users = $db->query("SELECT id, username, first_name, last_name FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$all_drivers = $db->query("SELECT id, full_name FROM drivers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$all_suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Build query string for pagination links
$filter_params = $_GET;
unset($filter_params['page'], $filter_params['export']);
$filter_qs = http_build_query($filter_params);

require_once "../includes/header.php";
?>

<style>
.stat-card       { border-left: 4px solid; }
.stat-in         { border-color: #1cc88a; }
.stat-out        { border-color: #e74a3b; }
.stat-net        { border-color: #4e73df; }
.stat-txn        { border-color: #f6c23e; }
.row-in          { background-color: rgba(28,200,138,.05); }
.row-out         { background-color: rgba(231,74,59,.05);  }
.badge-in        { background:#1cc88a; }
.badge-out       { background:#e74a3b; }
.badge-adj       { background:#36b9cc; }
.filter-card     { border-top: 3px solid #4e73df; }
</style>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-clipboard-list text-primary"></i> Stock Audit Log
    </h1>
    <div class="d-flex gap-2">
        <a href="<?php echo 'stock_audit.php?' . ($filter_qs ? $filter_qs . '&' : '') . 'export=csv'; ?>"
           class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-file-csv fa-sm"></i> Download CSV
        </a>
        <a href="view_products.php" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-box fa-sm"></i> Products
        </a>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card stat-in shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Stock IN</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">+<?php echo number_format((int)$stats['total_in']); ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-arrow-down fa-2x text-success opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card stat-out shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Stock OUT</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">-<?php echo number_format((int)$stats['total_out']); ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-arrow-up fa-2x text-danger opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <?php $net = (int)$stats['total_in'] - (int)$stats['total_out']; ?>
        <div class="card stat-card stat-net shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Net Change</div>
                        <div class="h4 mb-0 font-weight-bold <?php echo $net >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo($net >= 0 ? '+' : '') . number_format($net); ?>
                        </div>
                    </div>
                    <div class="col-auto"><i class="fas fa-balance-scale fa-2x text-primary opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card stat-txn shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Transactions</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format((int)$stats['total_txn']); ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-exchange-alt fa-2x text-warning opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow mb-4 filter-card">
    <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Filters</h6>
        <?php if (!empty($filter_params)): ?>
            <a href="stock_audit.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times-circle"></i> Clear All
            </a>
        <?php
endif; ?>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Product</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">All Products</option>
                    <?php foreach ($all_products as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $product_id == $p['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['sku'] . ' – ' . $p['name']); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="IN"  <?php echo strtoupper($type) == 'IN' ? 'selected' : ''; ?>>Stock IN</option>
                    <option value="OUT" <?php echo strtoupper($type) == 'OUT' ? 'selected' : ''; ?>>Stock OUT</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All Users</option>
                    <?php foreach ($all_users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $user_id == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['first_name'] ? ($u['first_name'] . ' ' . $u['last_name']) : $u['username']); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Driver</label>
                <select name="driver_id" class="form-select form-select-sm">
                    <option value="">All Drivers</option>
                    <?php foreach ($all_drivers as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $driver_id == $d['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['full_name']); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">All Suppliers</option>
                    <?php foreach ($all_suppliers as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $supplier_id == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Reference</label>
                <input type="text" name="reference" class="form-control form-control-sm"
                       placeholder="Invoice / PO number…"
                       value="<?php echo htmlspecialchars($reference); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Log Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list-alt"></i> Audit Entries
            <span class="badge bg-secondary ms-2"><?php echo number_format($total_rows); ?> records</span>
        </h6>
        <small class="text-muted">Page <?php echo $page; ?> of <?php echo max(1, $total_pages); ?></small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem;">
                <thead class="table-dark">
                    <tr>
                        <th style="width:140px;">Timestamp</th>
                        <th>User</th>
                        <th>Product</th>
                        <th style="width:60px;">Size</th>
                        <th style="width:80px;">Color</th>
                        <th style="width:70px;" class="text-center">Type</th>
                        <th style="width:80px;" class="text-center">Qty</th>
                        <th>Reason / Reference</th>
                        <th>Driver</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs): ?>
                        <?php foreach ($logs as $log): ?>
                        <?php
        $isIn = strtoupper($log['movement_type']) === 'IN';
        $rowClass = $isIn ? 'row-in' : 'row-out';
?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td class="text-muted small">
                                <?php echo date('d M Y', strtotime($log['created_at'])); ?><br>
                                <span class="text-secondary"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></span>
                            </td>
                            <td>
                                <span class="fw-semibold text-primary"><?php echo htmlspecialchars($log['user_name']); ?></span>
                                <?php if ($log['username']): ?>
                                    <br><small class="text-muted">@<?php echo htmlspecialchars($log['username']); ?></small>
                                <?php
        endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($log['product_name']); ?></div>
                                <small class="text-muted font-monospace">
                                    SKU: <?php echo htmlspecialchars($log['variant_sku'] ?: $log['product_sku']); ?>
                                </small>
                            </td>
                            <td>
                                <?php if (!empty($log['size'])): ?>
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($log['size']); ?></span>
                                <?php
        else: ?>
                                    <span class="text-muted small">—</span>
                                <?php
        endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($log['color'])): ?>
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($log['color']); ?></span>
                                <?php
        else: ?>
                                    <span class="text-muted small">—</span>
                                <?php
        endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge text-white <?php echo $isIn ? 'badge-in' : 'badge-out'; ?>">
                                    <?php echo $isIn ? 'IN' : 'OUT'; ?>
                                </span>
                            </td>
                            <td class="text-center fw-bold <?php echo $isIn ? 'text-success' : 'text-danger'; ?>">
                                <?php echo($isIn ? '+' : '−') . number_format($log['quantity']); ?>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($log['reason'] ?? '—'); ?></div>
                                <?php if (!empty($log['reference'])): ?>
                                    <small class="text-muted"><i class="fas fa-hashtag fa-xs"></i> <?php echo htmlspecialchars($log['reference']); ?></small>
                                <?php
        endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($log['driver_name'])): ?>
                                    <small class="text-info"><i class="fas fa-user-tie fa-xs"></i> <?php echo htmlspecialchars($log['driver_name']); ?></small>
                                <?php
        else: ?>
                                    <span class="text-muted small">—</span>
                                <?php
        endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($log['supplier_name'])): ?>
                                    <small class="text-secondary"><i class="fas fa-truck fa-xs"></i> <?php echo htmlspecialchars($log['supplier_name']); ?></small>
                                <?php
        else: ?>
                                    <span class="text-muted small">—</span>
                                <?php
        endif; ?>
                            </td>
                        </tr>
                        <?php
    endforeach; ?>
                    <?php
else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                                No audit entries found matching your criteria.
                                <?php if (!empty($filter_params)): ?>
                                    <br><a href="stock_audit.php" class="btn btn-sm btn-outline-secondary mt-2">
                                        <i class="fas fa-undo"></i> Clear filters
                                    </a>
                                <?php
    endif; ?>
                            </td>
                        </tr>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-white">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?php echo $filter_qs . ($filter_qs ? '&' : '') . 'page=' . ($page - 1); ?>">
                        <i class="fas fa-chevron-left fa-xs"></i> Prev
                    </a>
                </li>
                <?php
    // Smart pagination: show at most 7 page links
    $start_page = max(1, $page - 3);
    $end_page = min($total_pages, $page + 3);
    if ($start_page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo $filter_qs . ($filter_qs ? '&' : '') . 'page=1'; ?>">1</a></li>
                    <?php if ($start_page > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php
        endif; ?>
                <?php
    endif; ?>
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo $filter_qs . ($filter_qs ? '&' : '') . 'page=' . $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php
    endfor; ?>
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php
        endif; ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo $filter_qs . ($filter_qs ? '&' : '') . 'page=' . $total_pages; ?>"><?php echo $total_pages; ?></a></li>
                <?php
    endif; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?php echo $filter_qs . ($filter_qs ? '&' : '') . 'page=' . ($page + 1); ?>">
                        Next <i class="fas fa-chevron-right fa-xs"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php
endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
