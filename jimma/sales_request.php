<?php
// jimma/sales_request.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/AuditLog.php";
require_once "../config/auth.php";
require_once "../includes/functions.php";

// Standard Auth check
Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$audit = new AuditLog($db);

// Initialize table if missing (Standard practice for new modules)
$db->exec("CREATE TABLE IF NOT EXISTS product_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT,
    branch VARCHAR(100) DEFAULT 'Jimma',
    product_id INT NULL,
    variant_id INT NULL,
    custom_item_name VARCHAR(255) NULL,
    quantity INT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'approved', 'dispatched', 'received', 'cancelled') DEFAULT 'pending',
    request_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// --- API LAYER: Handle AJAX Submissions ---
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'errors' => []];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF Verification
        if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
            $response['errors'][] = "Security token mismatch. Please refresh.";
            echo json_encode($response);
            exit();
        }

        try {
            $productId = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
            $variantId = !empty($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
            $customName = !empty($_POST['custom_item_name']) ? trim($_POST['custom_item_name']) : null;
            $qty = intval($_POST['quantity'] ?? 0);
            $priority = $_POST['priority'] ?? 'normal';
            $notes = $_POST['notes'] ?? '';

            if ($qty <= 0) $response['errors'][] = "Quantity must be a positive integer.";
            
            if (empty($response['errors'])) {
                $db->beginTransaction();
                
                $sql = "INSERT INTO product_requests (requester_id, branch, product_id, variant_id, custom_item_name, quantity, priority, request_notes) 
                        VALUES (:uid, 'Jimma', :pid, :vid, :cname, :qty, :priority, :notes)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':uid' => $currentUser['id'],
                    ':pid' => $productId,
                    ':vid' => $variantId,
                    ':cname' => $customName,
                    ':qty' => $qty,
                    ':priority' => $priority,
                    ':notes' => $notes
                ]);
                
                // Audit the action
                $itemName = $customName ?? "Product #$productId";
                $audit->log($currentUser['id'], "JIMMA_REQUEST_CREATE", "Requested $qty units of $itemName");
                
                $db->commit();
                $response['success'] = true;
                $response['message'] = "Stock request sent to warehouse successfully.";
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $response['errors'][] = "Database Error: " . $e->getMessage();
        }
    }
    echo json_encode($response);
    exit();
}

// --- DATA LAYER: Fetch Page Content ---
try {
    $products = $db->query("SELECT id, name, sku, image, has_variants FROM products WHERE status = 'active' ORDER BY name ASC LIMIT 250")->fetchAll(PDO::FETCH_ASSOC);
    
    $req_stmt = $db->prepare("SELECT r.*, p.name as product_name, p.sku, v.size, v.color 
                              FROM product_requests r 
                              LEFT JOIN products p ON r.product_id = p.id 
                              LEFT JOIN product_variants v ON r.variant_id = v.id 
                              WHERE r.requester_id = ? 
                              ORDER BY r.created_at DESC LIMIT 15");
    $req_stmt->execute([$currentUser['id']]);
    $recent_requests = $req_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $products = []; $recent_requests = [];
}

require_once "../includes/header.php";
?>

<style>
    .search-results-item { cursor: pointer; transition: all 0.15s ease-in-out; }
    .search-results-item:hover { background-color: #f8fbff; border-left: 4px solid #4e73df; }
    .thumb-preview { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }
    #searchDropdown { position: absolute; width: 100%; max-height: 450px; overflow-y: auto; z-index: 1050; display: none; background: #fff; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
    .status-pending { background-color: #f6c23e; }
    .status-approved { background-color: #36b9cc; }
    .status-received { background-color: #1cc88a; }
</style>

<!-- Standard Header Layout (Matches add_product.php) -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-paper-plane me-2 text-primary"></i>Jimma Stock Request</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="view_requests.php" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-list-ul"></i> View All Requests
        </a>
        <button type="submit" form="requestForm" class="btn btn-sm btn-primary shadow-sm" id="topSubmitBtn">
            <i class="fas fa-check"></i> Submit Request
        </button>
    </div>
</div>

<div class="row gx-3">
    <!-- Main Content (8 Columns) -->
    <div class="col-xl-8 col-lg-7">
        <form id="requestForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCSRF(); ?>">
            <input type="hidden" name="product_id" id="selectedProductId">
            <input type="hidden" name="variant_id" id="selectedVariantId">

            <!-- Card: Item Selection -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-shopping-basket me-2"></i>Select Inventory Item</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4 position-relative" id="productSearch">
                        <label class="form-label fw-bold small text-muted text-uppercase">Scan or Search Catalog</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchBox" placeholder="Start typing name or SKU..." autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" onclick="resetSelection()" title="Clear selection"><i class="fas fa-times"></i></button>
                        </div>
                        
                        <div id="searchDropdown" class="shadow border rounded-2 mt-1">
                            <?php foreach($products as $p): ?>
                                <div class="search-results-item border-bottom px-3 py-2 d-flex align-items-center" 
                                     data-id="<?= $p['id']; ?>" data-name="<?= htmlspecialchars($p['name']); ?>" data-variants="<?= $p['has_variants']; ?>">
                                    <?php $img = !empty($p['image']) ? (strpos($p['image'], 'http') === 0 ? $p['image'] : "../" . $p['image']) : "../assets/img/noproduct.png"; ?>
                                    <img src="<?= $img; ?>" class="thumb-preview border me-3" onerror="this.src='../assets/img/noproduct.png'">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($p['name']); ?></div>
                                        <div class="small text-muted fw-monospace" style="font-size: 0.75rem;"><?= htmlspecialchars($p['sku']); ?></div>
                                    </div>
                                    <span class="badge bg-light text-primary border small">Select</span>
                                </div>
                            <?php endforeach; ?>
                            <div class="search-results-item px-3 py-3 bg-light d-flex align-items-center" data-id="" data-name="Custom Item">
                                <div class="thumb-preview d-flex align-items-center justify-content-center bg-white border me-3"><i class="fas fa-plus text-muted"></i></div>
                                <div class="flex-grow-1"><div class="fw-bold">Other / Custom Item</div><div class="small text-muted italic">Manual name entry</div></div>
                            </div>
                        </div>
                    </div>

                    <div id="selectionPreview" class="p-3 border rounded bg-light-soft d-none">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-check-circle text-success me-2"></i><span id="labelSelected">Item Name</span></h6>
                            <button type="button" class="btn btn-sm btn-link text-muted py-0" onclick="resetSelection()">Change</button>
                        </div>
                        
                        <div id="variantZone" class="mt-3" style="display:none;">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-1">Select Size/Variation</label>
                            <select class="form-select form-select-sm" id="variantSelect" name="variant_id"></select>
                        </div>

                        <div id="customNameZone" class="mt-3" style="display:none;">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-1">Specify Name</label>
                            <input type="text" class="form-control form-control-sm" name="custom_item_name" id="customInput">
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Card: Recent Requests -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-clock-rotate-left me-2"></i>My Recent Requests</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light">
                            <tr class="small text-muted text-uppercase">
                                <th class="ps-3 border-0">Product/Item</th>
                                <th class="border-0">Qty</th>
                                <th class="border-0">Priority</th>
                                <th class="border-0">Status</th>
                                <th class="border-0 text-end pe-3">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recent_requests)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted small">No requests in history</td></tr>
                            <?php else: foreach($recent_requests as $r): 
                                $sDot = 'status-' . $r['status'];
                            ?>
                                <tr class="align-middle">
                                    <td class="ps-3 py-2">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($r['product_name'] ?? $r['custom_item_name']) ?></div>
                                        <?php if($r['variant_id']): ?><div class="small text-muted font-monospace" style="font-size:0.7rem;"><?= htmlspecialchars($r['size'].' / '.$r['color']) ?></div><?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= $r['quantity'] ?></td>
                                    <td class="small text-capitalize"><?= $r['priority'] ?></td>
                                    <td><span class="status-dot <?= $sDot ?>"></span><span class="small"><?= ucfirst($r['status']) ?></span></td>
                                    <td class="text-end pe-3 small text-muted"><?= date('M j, H:i', strtotime($r['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar (4 Columns) -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-clipboard-check me-2"></i>Request Details</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">Quantity *</label>
                    <input type="number" class="form-control form-control-sm border-success fw-bold p-2" name="quantity" form="requestForm" min="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">Urgency</label>
                    <select class="form-select form-select-sm" name="priority" form="requestForm">
                        <option value="low">Low Priority</option>
                        <option value="normal" selected>Normal Priority</option>
                        <option value="high">High Priority</option>
                        <option value="urgent">Critical Urgency</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">Internal Notes</label>
                    <textarea class="form-control form-control-sm" name="notes" form="requestForm" rows="4" placeholder="Specific instructions for warehouse..."></textarea>
                </div>
            </div>
        </div>

        <!-- Standard Bottom Save Bar (Matches add_product.php) -->
        <div class="sticky-top" style="top: 1rem;">
            <div class="card shadow border-0 bg-primary">
                <div class="card-body p-2">
                    <button type="submit" form="requestForm" class="btn btn-primary w-100 fw-bold border-0" id="mainSubmitBtn">
                        <i class="fas fa-share-square me-2"></i> SEND TO WAREHOUSE
                    </button>
                </div>
            </div>
            <a href="../index.php" class="btn btn-link w-100 btn-sm text-secondary mt-2">Discard and Return</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rForm = document.getElementById('requestForm');
    const sBox = document.getElementById('searchBox');
    const dd = document.getElementById('searchDropdown');
    const items = dd.querySelectorAll('.search-results-item');
    const vZone = document.getElementById('variantZone');
    const cZone = document.getElementById('customNameZone');
    
    window.resetSelection = function() {
        document.getElementById('selectedProductId').value = '';
        sBox.value = ''; sBox.disabled = false;
        document.getElementById('selectionPreview').classList.add('d-none');
        vZone.style.display = 'none'; cZone.style.display = 'none';
        sBox.focus();
    };

    sBox.addEventListener('click', () => { if(!sBox.disabled) dd.style.display = 'block'; });
    sBox.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        let found = 0;
        items.forEach(it => {
            if(it.textContent.toLowerCase().includes(q)) { it.style.display = 'flex'; found++; }
            else it.style.display = 'none';
        });
        dd.style.display = found > 0 ? 'block' : 'none';
    });

    items.forEach(it => {
        it.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const hasVariants = this.dataset.variants === '1';

            document.getElementById('selectedProductId').value = id;
            sBox.value = name; sBox.disabled = true; dd.style.display = 'none';
            document.getElementById('selectionPreview').classList.remove('d-none');
            document.getElementById('labelSelected').textContent = name;

            if(name === 'Custom Item') {
                cZone.style.display = 'block'; vZone.style.display = 'none';
                document.getElementById('customInput').focus();
            } else {
                cZone.style.display = 'none';
                if(hasVariants) fetchVariants(id);
                else vZone.style.display = 'none';
            }
        });
    });

    function fetchVariants(pid) {
        const vSel = document.getElementById('variantSelect');
        vSel.innerHTML = '<option>Loading...</option>'; vZone.style.display = 'block';
        fetch(`../api/get_variants.php?product_id=${pid}`)
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    vSel.innerHTML = res.data.map(v => `<option value="${v.id}">${v.size} / ${v.color} (${v.sku})</option>`).join('');
                }
            });
    }

    rForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const fData = new FormData(this);
        // Sync manual form fields
        document.querySelectorAll(`[form="requestForm"]`).forEach(el => fData.append(el.name, el.value));

        const btns = [document.getElementById('mainSubmitBtn'), document.getElementById('topSubmitBtn')];
        btns.forEach(b => { b.disabled = true; b.innerHTML = '<i class="fas fa-spin fa-spinner"></i> Sending...'; });

        fetch(window.location.href, { method: 'POST', body: fData, headers: {'X-Requested-With': 'XMLHttpRequest'} })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({icon:'success', title:'Success', text: data.message}).then(() => location.reload());
                } else {
                    Swal.fire({icon:'error', title:'Error', html: data.errors.join('<br>')});
                    btns.forEach((b, i) => { 
                        b.disabled = false; 
                        b.innerHTML = i === 0 ? '<i class="fas fa-share-square me-2"></i> SEND TO WAREHOUSE' : '<i class="fas fa-check"></i> Submit Request';
                    });
                }
            })
            .catch(() => Swal.fire('Error', 'Network error', 'error'));
    });

    document.addEventListener('click', e => { if(!e.target.closest('#productSearch')) dd.style.display = 'none'; });
});
</script>

<?php require_once "../includes/footer.php"; ?>
