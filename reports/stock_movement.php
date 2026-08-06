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
        pv.size, pv.color, pv.sku as variant_sku,
        u.username,
        u.first_name,
        u.last_name,
        d.full_name as driver_name
    FROM stock_movements sm
    LEFT JOIN products p ON sm.product_id = p.id
    LEFT JOIN product_variants pv ON sm.variant_id = pv.id
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

$current_user = Auth::getCurrentUser();
$can_edit = in_array($current_user['role'] ?? '', ['admin', 'manager']);
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
                        <th>Size / Color</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reason</th>
                        <th>Reference</th>
                        <th>User</th>
                        <th>Driver</th>
                        <?php if ($can_edit): ?>
                        <th style="width:80px;">Action</th>
                        <?php
endif; ?>
                    </tr>
                </thead>
                <tbody>

                    <?php if (count($movements) > 0): ?>
                        <?php foreach ($movements as $index => $row): ?>
                        <tr id="row-<?php echo $row['id']; ?>">
                            <td><?php echo $from_record_num + $index + 1; ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['variant_sku'] ?: $row['sku']); ?></strong><br>
                                <?php echo htmlspecialchars($row['product_name']); ?>
                            </td>
                            <td>
                                <?php if (!empty($row['size']) || !empty($row['color'])): ?>
                                    <?php if (!empty($row['size'])): ?>
                                        <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars($row['size']); ?></span>
                                    <?php
            endif; ?>
                                    <?php if (!empty($row['color'])): ?>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['color']); ?></span>
                                    <?php
            endif; ?>
                                <?php
        else: ?>
                                    <span class="text-muted">—</span>
                                <?php
        endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $row['movement_type'] == 'IN' ? 'success' : 'danger'; ?>" id="type-badge-<?php echo $row['id']; ?>">
                                    <?php echo $row['movement_type']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="<?php echo $row['movement_type'] == 'IN' ? 'text-success' : 'text-danger'; ?> fw-bold" id="qty-display-<?php echo $row['id']; ?>">
                                    <?php echo $row['movement_type'] == 'IN' ? '+' : '-'; ?><?php echo $row['quantity']; ?>
                                </span>
                            </td>
                            <td id="reason-display-<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['reason']); ?></td>
                            <td id="ref-display-<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['reference'] ?? '-'); ?></td>
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
                            <?php if ($can_edit): ?>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-warning btn-edit-movement"
                                    title="Edit this movement"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-type="<?php echo $row['movement_type']; ?>"
                                    data-qty="<?php echo $row['quantity']; ?>"
                                    data-reason="<?php echo htmlspecialchars($row['reason'], ENT_QUOTES); ?>"
                                    data-reference="<?php echo htmlspecialchars($row['reference'] ?? '', ENT_QUOTES); ?>"
                                    data-product="<?php echo htmlspecialchars($row['product_name'], ENT_QUOTES); ?>"
                                    data-sku="<?php echo htmlspecialchars($row['variant_sku'] ?: $row['sku'], ENT_QUOTES); ?>"
                                    data-size="<?php echo htmlspecialchars($row['size'] ?? '', ENT_QUOTES); ?>"
                                    data-color="<?php echo htmlspecialchars($row['color'] ?? '', ENT_QUOTES); ?>">
                                    <i class="fas fa-pen fa-xs"></i>
                                </button>
                            </td>
                            <?php
        endif; ?>
                        </tr>
                        <?php
    endforeach; ?>
                    <?php
else: ?>
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">No movements found matching your criteria.</td>
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

<!-- Edit Movement Modal -->
<div class="modal fade" id="editMovementModal" tabindex="-1" aria-labelledby="editMovementModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="editMovementModalLabel">
            <i class="fas fa-pen"></i> Correct Stock Movement
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Product info (read-only) -->
        <div class="alert alert-light border mb-3 p-2">
            <div class="small text-muted">Product</div>
            <strong id="modal-product-name"></strong>
            <span class="badge bg-secondary ms-2" id="modal-product-sku"></span>
            <span class="badge bg-light text-dark border ms-1" id="modal-product-size" style="display:none"></span>
            <span class="badge bg-light text-dark border ms-1" id="modal-product-color" style="display:none"></span>
        </div>

        <input type="hidden" id="modal-movement-id">

        <!-- Type toggle -->
        <div class="mb-3">
            <label class="form-label fw-bold">Movement Type <span class="text-danger">*</span></label>
            <div class="d-flex gap-2">
                <div class="form-check form-check-inline flex-fill">
                    <input class="form-check-input" type="radio" name="modal_type" id="typeIn" value="IN">
                    <label class="form-check-label text-success fw-bold" for="typeIn">
                        <i class="fas fa-arrow-down"></i> Stock IN
                    </label>
                </div>
                <div class="form-check form-check-inline flex-fill">
                    <input class="form-check-input" type="radio" name="modal_type" id="typeOut" value="OUT">
                    <label class="form-check-label text-danger fw-bold" for="typeOut">
                        <i class="fas fa-arrow-up"></i> Stock OUT
                    </label>
                </div>
            </div>
        </div>

        <!-- Quantity -->
        <div class="mb-3">
            <label for="modal-qty" class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="modal-qty" min="1" required>
        </div>

        <!-- Reason -->
        <div class="mb-3">
            <label for="modal-reason" class="form-label fw-bold">Reason</label>
            <input type="text" class="form-control" id="modal-reason" maxlength="255">
        </div>

        <!-- Reference -->
        <div class="mb-3">
            <label for="modal-reference" class="form-label fw-bold">Reference</label>
            <input type="text" class="form-control" id="modal-reference" placeholder="Invoice / PO number" maxlength="100">
        </div>

        <!-- Warning notice -->
        <div class="alert alert-warning py-2 mb-0" style="font-size:.85rem;">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Note:</strong> Changing the type or quantity will automatically adjust the product's current stock balance.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning text-dark fw-bold" id="saveMovementBtn">
            <i class="fas fa-save"></i> Save Correction
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Toast notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="movementToast" class="toast align-items-center text-bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toastMessage">Movement updated.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal      = new bootstrap.Modal(document.getElementById('editMovementModal'));
    const toast      = new bootstrap.Toast(document.getElementById('movementToast'), { delay: 3500 });
    const toastEl    = document.getElementById('movementToast');
    const toastMsg   = document.getElementById('toastMessage');

    // Open modal and pre-fill fields
    document.querySelectorAll('.btn-edit-movement').forEach(btn => {
        btn.addEventListener('click', function () {
            const id        = this.dataset.id;
            const type      = this.dataset.type;      // 'IN' or 'OUT'
            const qty       = this.dataset.qty;
            const reason    = this.dataset.reason;
            const reference = this.dataset.reference;
            const product   = this.dataset.product;
            const sku       = this.dataset.sku;
            const size      = this.dataset.size;
            const color     = this.dataset.color;

            document.getElementById('modal-movement-id').value = id;
            document.getElementById('modal-product-name').textContent = product;
            document.getElementById('modal-product-sku').textContent  = sku;

            const sizeEl  = document.getElementById('modal-product-size');
            const colorEl = document.getElementById('modal-product-color');
            if (size)  { sizeEl.textContent  = size;  sizeEl.style.display  = ''; }
            else       { sizeEl.style.display  = 'none'; }
            if (color) { colorEl.textContent = color; colorEl.style.display = ''; }
            else       { colorEl.style.display = 'none'; }

            document.getElementById(type === 'IN' ? 'typeIn' : 'typeOut').checked = true;
            document.getElementById('modal-qty').value        = qty;
            document.getElementById('modal-reason').value    = reason;
            document.getElementById('modal-reference').value = reference;

            modal.show();
        });
    });

    // Save correction via AJAX
    document.getElementById('saveMovementBtn').addEventListener('click', function () {
        const id        = document.getElementById('modal-movement-id').value;
        const typeEl    = document.querySelector('input[name="modal_type"]:checked');
        const qty       = parseInt(document.getElementById('modal-qty').value);
        const reason    = document.getElementById('modal-reason').value.trim();
        const reference = document.getElementById('modal-reference').value.trim();

        if (!typeEl) { alert('Please select a movement type.'); return; }
        if (!qty || qty <= 0) { alert('Please enter a valid quantity.'); return; }

        const saveBtn = this;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

        fetch('<?php echo BASE_URL; ?>api/edit_movement.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: parseInt(id),
                movement_type: typeEl.value,
                quantity: qty,
                reason: reason,
                reference: reference
            })
        })
        .then(r => r.json())
        .then(data => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Correction';

            if (data.success) {
                modal.hide();
                // Update row in-page without full reload
                const newType = typeEl.value;
                const typeBadge  = document.getElementById('type-badge-'  + id);
                const qtyDisplay = document.getElementById('qty-display-' + id);
                const reasonDisplay = document.getElementById('reason-display-' + id);
                const refDisplay    = document.getElementById('ref-display-'    + id);

                if (typeBadge) {
                    typeBadge.textContent = newType;
                    typeBadge.className   = 'badge bg-' + (newType === 'IN' ? 'success' : 'danger');
                }
                if (qtyDisplay) {
                    qtyDisplay.textContent  = (newType === 'IN' ? '+' : '-') + qty;
                    qtyDisplay.className    = (newType === 'IN' ? 'text-success' : 'text-danger') + ' fw-bold';
                }
                if (reasonDisplay) reasonDisplay.textContent = reason || '—';
                if (refDisplay)    refDisplay.textContent    = reference || '-';

                // Flash row highlight
                const row = document.getElementById('row-' + id);
                if (row) {
                    row.style.transition = 'background-color 0.3s';
                    row.style.backgroundColor = '#d4edda';
                    setTimeout(() => { row.style.backgroundColor = ''; }, 2000);
                }

                toastEl.className = 'toast align-items-center text-bg-success border-0';
                toastMsg.textContent = 'Movement corrected successfully! Stock adjusted by ' +
                    (data.adjustment >= 0 ? '+' : '') + data.adjustment + ' units.';
                toast.show();
            } else {
                toastEl.className = 'toast align-items-center text-bg-danger border-0';
                toastMsg.textContent = 'Error: ' + data.message;
                toast.show();
            }
        })
        .catch(err => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Correction';
            alert('Network error. Please try again.');
        });
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>
