<?php
// products/reorder_suggestions.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/SalesAnalytics.php";

$database = new Database();
$db = $database->getConnection();
$analytics = new SalesAnalytics($db);

// Handle manual recalculation
if (isset($_POST['recalculate'])) {
    $count = $analytics->updateAllReorderPoints();
    $_SESSION['message'] = "Successfully updated reorder points for $count products.";
    $_SESSION['message_type'] = "success";
    header("Location: reorder_suggestions.php");
    exit();
}

// Get suggestions
$suggestions = $analytics->getReorderSuggestions();

require_once "../includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-clipboard-list"></i> Reorder Suggestions</h1>
    <div>
        <form method="POST" class="d-inline">
            <button type="submit" name="recalculate" class="btn btn-sm btn-info shadow-sm me-2">
                <i class="fas fa-sync-alt fa-sm text-white-50"></i> Recalculate Logic
            </button>
        </form>
        <a href="generate_po.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-file-invoice fa-sm text-white-50"></i> Generate Purchase Order
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php 
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    endif; 
?>

<!-- Suggestions Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Products Below Reorder Point</h6>
    </div>
    <div class="card-body">
        <?php if (empty($suggestions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h4>All Stock Levels Healthy</h4>
                <p class="text-muted">No products are currently below their reorder points.</p>
                <form method="POST">
                    <button type="submit" name="recalculate" class="btn btn-outline-primary mt-2">
                        Run Analysis Again
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>Priority</th>
                            <th>Product</th>
                            <th>Supplier</th>
                            <th>Current Stock</th>
                            <th>Reorder Point</th>
                            <th>Avg Daily Sales</th>
                            <th>Suggested Qty</th>
                            <th>Est. Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suggestions as $item): 
                            // Calculate status
                            $stock_ratio = $item['quantity'] / max(1, $item['reorder_point']);
                            $status_class = '';
                            $status_text = '';
                            
                            if ($item['quantity'] == 0) {
                                $status_class = 'danger';
                                $status_text = 'Out of Stock';
                            } elseif ($stock_ratio < 0.5) {
                                $status_class = 'danger';
                                $status_text = 'Critical';
                            } else {
                                $status_class = 'warning';
                                $status_text = 'Low';
                            }
                            
                            $est_cost = $item['reorder_quantity'] * $item['price']; // Should ideally be cost_price
                        ?>
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($item['image']): ?>
                                        <img src="../<?php echo htmlspecialchars($item['image']); ?>" class="img-thumbnail me-2" style="width: 40px; height: 40px;">
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['sku']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'Unknown'); ?></td>
                            <td class="text-center fw-bold text-<?php echo $status_class; ?>">
                                <?php echo $item['quantity']; ?>
                            </td>
                            <td class="text-center">
                                <?php echo $item['reorder_point']; ?>
                            </td>
                            <td class="text-center">
                                <?php echo $item['avg_daily_sales']; ?>
                                <small class="text-muted">/day</small>
                            </td>
                            <td class="text-center fw-bold text-primary">
                                <?php echo $item['reorder_quantity']; ?>
                            </td>
                            <td>$<?php echo number_format($est_cost, 2); ?></td>
                            <td>
                                <a href="view_product.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-secondary" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_product.php?id=<?php echo $item['id']; ?>#inventory" class="btn btn-sm btn-outline-primary" title="Edit Settings">
                                    <i class="fas fa-cog"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Info Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-info-circle"></i> How is this calculated?</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Reorder Point Formula</h5>
                <div class="alert alert-secondary">
                    <code>(Avg Daily Sales × Lead Time) + Safety Stock</code>
                </div>
                <p>When stock falls below this number, the product appears in this list.</p>
            </div>
            <div class="col-md-6">
                <h5>Lead Time</h5>
                <p>The number of days it takes for a supplier to deliver stock. Default is <strong>7 days</strong>.</p>
                <h5>Safety Stock</h5>
                <p>Extra buffer stock to prevent stockouts during demand spikes.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
