<?php
// reports/stock_movement.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";

// Ensure user is logged in
Auth::requireLogin();
Auth::requireRole('manager');

$database = new Database();
$db = $database->getConnection();

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Build query with filters
$where_clauses = [];
$params = [];

if (isset($_GET['type']) && !empty($_GET['type'])) {
    $where_clauses[] = "sm.movement_type = :type";
    $params[':type'] = $_GET['type'];
}

if (isset($_GET['product_id']) && !empty($_GET['product_id'])) {
    $where_clauses[] = "sm.product_id = :product_id";
    $params[':product_id'] = $_GET['product_id'];
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $where_clauses[] = "DATE(sm.created_at) >= :date_from";
    $params[':date_from'] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $where_clauses[] = "DATE(sm.created_at) <= :date_to";
    $params[':date_to'] = $_GET['date_to'];
}

if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $where_clauses[] = "p.category_id = :category_id";
    $params[':category_id'] = $_GET['category_id'];
}

if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $where_clauses[] = "sm.user_id = :user_id";
    $params[':user_id'] = $_GET['user_id'];
}

if (isset($_GET['driver_id']) && !empty($_GET['driver_id'])) {
    $where_clauses[] = "sm.driver_id = :driver_id";
    $params[':driver_id'] = $_GET['driver_id'];
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total rows for pagination
$count_query = "SELECT COUNT(*) as total_rows FROM stock_movements sm LEFT JOIN products p ON sm.product_id = p.id " . $where_sql;
$stmt = $db->prepare($count_query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_rows = $row['total_rows'];
$total_pages = ceil($total_rows / $records_per_page);

// Main query
$query = "
    SELECT 
        sm.*,
        p.name as product_name,
        p.sku,
        u.username,
        u.first_name,
        u.last_name,
        d.full_name as driver_name
    FROM stock_movements sm
    LEFT JOIN products p ON sm.product_id = p.id
    LEFT JOIN users u ON sm.user_id = u.id
    LEFT JOIN drivers d ON sm.driver_id = d.id
    $where_sql
    ORDER BY sm.created_at DESC
    LIMIT :from_record_num, :records_per_page";

$stmt = $db->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':from_record_num', $from_record_num, PDO::PARAM_INT);
$stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all products for filter dropdown
$products_query = "SELECT id, name, sku FROM products ORDER BY name";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();
$all_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get users for filter
$users = $db->query("SELECT id, username, first_name, last_name FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get drivers for filter
$drivers = $db->query("SELECT id, full_name FROM drivers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-history"></i> Stock Movement Report</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    <?php foreach ($all_products as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo(isset($_GET['product_id']) && $_GET['product_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['sku'] . ' - ' . $p['name']); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo(isset($_GET['category_id']) && $_GET['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="IN" <?php echo(isset($_GET['type']) && $_GET['type'] == 'IN') ? 'selected' : ''; ?>>Stock IN</option>
                    <option value="OUT" <?php echo(isset($_GET['type']) && $_GET['type'] == 'OUT') ? 'selected' : ''; ?>>Stock OUT</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($_GET['date_from'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($_GET['date_to'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo(isset($_GET['user_id']) && $_GET['user_id'] == $u['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['first_name'] ? ($u['first_name'] . ' ' . $u['last_name']) : $u['username']); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Driver</label>
                <select name="driver_id" class="form-select">
                    <option value="">All Drivers</option>
                    <?php foreach ($drivers as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo(isset($_GET['driver_id']) && $_GET['driver_id'] == $d['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['full_name']); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i> Filter</button>
                <a href="stock_movement.php" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reason</th>
                        <th>Reference</th>
                        <th>User</th>
                        <th>Driver</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($movements) > 0): ?>
                        <?php foreach ($movements as $index => $row): ?>
                        <tr>
                            <td><?php echo $from_record_num + $index + 1; ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['sku']); ?></strong><br>
                                <?php echo htmlspecialchars($row['product_name']); ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $row['movement_type'] == 'IN' ? 'success' : 'danger'; ?>">
                                    <?php echo $row['movement_type']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="<?php echo $row['movement_type'] == 'IN' ? 'text-success' : 'text-danger'; ?> fw-bold">
                                    <?php echo $row['movement_type'] == 'IN' ? '+' : '-'; ?><?php echo $row['quantity']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['reason']); ?></td>
                            <td><?php echo htmlspecialchars($row['reference'] ?? '-'); ?></td>
                            <td>
                                <small>
                                <?php
        if (!empty($row['first_name'])) {
            echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
        }
        else {
            echo htmlspecialchars($row['username'] ?? 'System');
        }
?>
                                </small>
                            </td>
                            <td>
                                <small class="text-info">
                                    <?php echo htmlspecialchars($row['driver_name'] ?? '-'); ?>
                                </small>
                            </td>
                        </tr>
                        <?php
    endforeach; ?>
                    <?php
else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No movements found matching your criteria.</td>
                        </tr>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>"><?php echo $i; ?></a>
                </li>
                <?php
    endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php
endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
