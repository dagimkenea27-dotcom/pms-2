<?php
/**
 * reports/marketing_intelligence.php
 * AI-driven marketing suggestions for discounts and advertisements
 */
require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../models/PromotionEngine.php";
require_once "../models/InventoryForecaster.php";

Auth::requireLogin();
if (!Auth::isAdmin() && !Auth::isManager()) {
    die("Access denied.");
}

$database = new Database();
$db = $database->getConnection();

// Handle manual recalculation trigger
if (isset($_POST['recalculate'])) {
    $forecaster = new InventoryForecaster($db);
    $forecaster->updateAllForecasts();
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit;
}

// Ensure forecasts are up to date
$forecaster = new InventoryForecaster($db);
$forecaster->updateAllForecasts();

$promo = new PromotionEngine($db);
$discounts = $promo->getDiscountSuggestions();

// Prevent repetition: Exclude discount candidates from ad suggestions
$discountIds = array_column($discounts, 'id');
$ads = $promo->getAdSuggestions($discountIds);

require_once "../includes/header.php";
?>

<div class="container-fluid">
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
        <i class="fas fa-check-circle me-2"></i> Suggestions have been successfully recalculated based on current inventory and sales data.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-magic text-primary me-2"></i>Marketing Intelligence</h1>
            <p class="text-muted small mb-0">AI-driven marketing suggestions based on stock levels and sales velocity.</p>
        </div>
        <div>
            <button class="btn btn-sm btn-outline-info me-2" type="button" data-bs-toggle="collapse" data-bs-target="#formulaDetails" aria-expanded="false" aria-controls="formulaDetails">
                <i class="fas fa-info-circle me-1"></i> View Formula Details
            </button>
            <form method="POST" class="d-inline">
                <button type="submit" name="recalculate" class="btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-sync-alt fa-sm text-white-50 me-1"></i> Recalculate Suggestions
                </button>
            </form>
        </div>
    </div>

    <!-- Formula Details (Collapsed by default) -->
    <div class="collapse mb-4" id="formulaDetails">
        <div class="card card-body shadow-sm border-0 bg-light">
            <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-calculator me-2"></i>Calculation Formulas</h6>
            <div class="row">
                <div class="col-md-6 border-right">
                    <h7 class="font-weight-bold text-danger">Discount Suggestions Logic:</h7>
                    <ul class="small mt-2">
                        <li><strong>Dead Stock:</strong> 0 sales in last 30 days AND quantity > 5. (Suggested: 30% OFF)</li>
                        <li><strong>Overstocked:</strong> Predicted stock lasts > 90 days. (Suggested: 15% OFF)</li>
                        <li><strong>Low Velocity:</strong> Low sales volume. (Suggested: 10% OFF)</li>
                        <li><em>Price Formula:</em> $Suggested = Price \times (1 - Discount\%)$</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h7 class="font-weight-bold text-success">Ad Suggestions Logic:</h7>
                    <ul class="small mt-2">
                        <li><strong>High Margin:</strong> Gross profit margin is > 25%.</li>
                        <li><strong>High Velocity:</strong> Daily sales > store average daily sales.</li>
                        <li><strong>Inventory Buffer:</strong> Must have > 14 days of stock remaining.</li>
                        <li><em>Margin Formula:</em> $Margin\% = \frac{Price - Cost}{Price} \times 100$</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Discount Suggestions -->
        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="card shadow border-left-danger">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-danger">Liquidation & Discount Suggestions</h6>
                    <i class="fas fa-percentage text-danger"></i>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-4">These products are overstocked or not moving. Consider a clearance sale to free up cash.</p>
                    
                    <?php if (empty($discounts)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <p>Great! No overstocked items found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Reason</th>
                                        <th>Stock / Vel</th>
                                        <th>Current</th>
                                        <th>Suggested</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($discounts as $item): 
                                        $suggested = $promo->suggestDiscountPrice($item['price'], $item['cost_price'], $item['reason']);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="small text-muted"><?php echo $item['sku']; ?></div>
                                        </td>
                                        <td><span class="badge bg-light text-danger"><?php echo $item['reason']; ?></span></td>
                                        <td>
                                            <div><?php echo $item['quantity']; ?> units</div>
                                            <div class="small text-muted"><?php echo number_format($item['avg_daily_sales'], 2); ?>/day</div>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td class="text-success font-weight-bold">
                                            $<?php echo number_format($suggested, 2); ?>
                                            <i class="fas fa-info-circle small text-muted ms-1" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-html="true"
                                               title="<b>Formula:</b> <?php 
                                                    if ($item['reason'] === 'Dead Stock') echo 'Price &times; 0.70 (30% off)';
                                                    elseif ($item['reason'] === 'Overstocked') echo 'Price &times; 0.85 (15% off)';
                                                    else echo 'Price &times; 0.90 (10% off)';
                                               ?><br>Base: $<?php echo number_format($item['price'], 2); ?>"></i>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ad Suggestions -->
        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="card shadow border-left-success">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">Advertisement Recommendations</h6>
                    <i class="fas fa-bullhorn text-success"></i>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-4">High margin products with proven sales velocity. Increasing visibility could significantly boost profits.</p>
                    
                    <?php if (empty($ads)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle fa-2x text-warning mb-2"></i>
                            <p>Not enough sales data to suggest ads yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Margin</th>
                                        <th>Velocity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ads as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="small text-muted"><?php echo $item['sku']; ?></div>
                                        </td>
                                        <td>
                                            <span class="text-success font-weight-bold" 
                                                  data-bs-toggle="tooltip" 
                                                  title="Margin = ((Price - Cost) / Price) * 100">
                                                <?php echo number_format($item['margin_percent'], 1); ?>%
                                            </span>
                                        </td>
                                        <td><?php echo number_format($item['avg_daily_sales'], 2); ?> units/day</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-rocket me-1"></i> Boost
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>
