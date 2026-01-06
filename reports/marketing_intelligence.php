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
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-magic text-primary me-2"></i>Marketing Intelligence</h1>
        <span class="badge bg-info text-white p-2">AI powered suggestions</span>
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
                                        <td class="text-success font-weight-bold">$<?php echo number_format($suggested, 2); ?></td>
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
                                        <td><span class="text-success font-weight-bold"><?php echo number_format($item['margin_percent'], 1); ?>%</span></td>
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
