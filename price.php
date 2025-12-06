<?php
require_once "config/auth.php";
require_once "config/database.php";
require_once "services/PriceCalculator.php";

Auth::checkAuthAndPreventCache();

// Handle exchange rate fetch
$exchangeRateValue = 120.0;
if (isset($_GET['get_rate'])) {
    try {
        $apiUrl = 'https://api.exchangerate-api.com/v4/latest/USD';
        $response = @file_get_contents($apiUrl);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['rates']['ETB'])) {
                $exchangeRateValue = $data['rates']['ETB'];
            }
        }
    } catch (Exception $e) {
        $exchangeRateValue = 120.0;
    }
}

// Handle form submission
$result = null;
$errorMessage = null;
$usdAmount = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usdAmount = isset($_POST['usdAmount']) ? floatval($_POST['usdAmount']) : 0;
    $exchangeRate = isset($_POST['exchangeRate']) ? floatval($_POST['exchangeRate']) : 0;
    
    if ($usdAmount <= 0 || $exchangeRate <= 0) {
        $errorMessage = 'Valid USD amount and exchange rate are required';
    } else {
        $calculator = new PriceCalculator();
        $result = $calculator->calculate($usdAmount, $exchangeRate);
        $exchangeRateValue = $exchangeRate;
    }
}

require_once "includes/header.php";
?>

<link rel="stylesheet" href="assets/css/price.css">

<div class="price-calculator-container">
    <div class="page-header">
        <h1><i class="fas fa-calculator"></i> Price Calculator</h1>
        <p>Calculate the total cost of imported products including taxes and fees</p>
    </div>

    <?php if ($errorMessage): ?>
    <div class="alert-calc alert-error-calc">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header">
            <h2><i class="fas fa-dollar-sign"></i> Enter Product Details</h2>
            <p>Provide the USD amount and current exchange rate to calculate the total cost</p>
        </div>
        
        <div class="form-card-body">
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="usdAmount">USD Amount</label>
                        <input type="number" id="usdAmount" name="usdAmount" class="form-control" 
                               step="0.01" min="0" placeholder="0.00" 
                               value="<?php echo isset($_POST['usdAmount']) ? htmlspecialchars($_POST['usdAmount']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="exchangeRate">Exchange Rate (USD to ETB)</label>
                        <div class="input-with-action">
                            <input type="number" id="exchangeRate" name="exchangeRate" class="form-control" 
                                   step="0.01" min="0" placeholder="0.00" 
                                   value="<?php echo $exchangeRateValue; ?>" required>
                            <a href="?get_rate=1" class="btn-calc btn-outline-calc" title="Get current exchange rate">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </div>
                        <div class="exchange-rate-info">
                            <i class="fas fa-info-circle"></i>
                            <small>Click the refresh button to get the current USD to ETB exchange rate</small>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="btn-calc btn-primary-calc">
                        <i class="fas fa-calculator"></i> Calculate Total Cost
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($result): ?>
    <div class="result-card">
        <div class="result-header">
            <h2><i class="fas fa-receipt"></i> Calculation Results</h2>
            <p>Detailed breakdown of costs and taxes</p>
        </div>
        
        <div class="result-grid">
            <div class="result-item">
                <h3>Base Amount</h3>
                <div class="amount">ETB <?php echo number_format($result['etbAmount'], 2); ?></div>
                <div>USD <?php echo number_format($usdAmount, 2); ?> × <?php echo number_format($exchangeRateValue, 2); ?></div>
            </div>
            
            <div class="result-item">
                <h3>Value Tax (15%)</h3>
                <div class="amount">ETB <?php echo number_format($result['valueTax'], 2); ?></div>
            </div>
            
            <div class="result-item">
                <h3>Shipment Fee (10%)</h3>
                <div class="amount">ETB <?php echo number_format($result['shipmentFee'], 2); ?></div>
            </div>
            
            <div class="result-item">
                <h3>Processing Fee (5%)</h3>
                <div class="amount">ETB <?php echo number_format($result['processingFee'], 2); ?></div>
            </div>
            
            <div class="result-item">
                <h3>Delivery Fee</h3>
                <div class="amount">ETB <?php echo number_format($result['additionalFee'], 2); ?></div>
            </div>
            
            <div class="result-item total">
                <h3>Total Cost</h3>
                <div class="amount">ETB <?php echo number_format($result['totalCost'], 2); ?></div>
            </div>
        </div>
        
        <div class="tax-breakdown">
            <h3><i class="fas fa-info-circle"></i> Tax Breakdown</h3>
            <div class="tax-item">
                <span class="tax-label">Base Conversion:</span>
                <span class="tax-value">ETB <?php echo number_format($result['etbAmount'], 2); ?></span>
            </div>
            <div class="tax-item">
                <span class="tax-label">Value Tax (15%):</span>
                <span class="tax-value">ETB <?php echo number_format($result['valueTax'], 2); ?></span>
            </div>
            <div class="tax-item">
                <span class="tax-label">Shipment Fee (10%):</span>
                <span class="tax-value">ETB <?php echo number_format($result['shipmentFee'], 2); ?></span>
            </div>
            <div class="tax-item">
                <span class="tax-label">Processing Fee (5%):</span>
                <span class="tax-value">ETB <?php echo number_format($result['processingFee'], 2); ?></span>
            </div>
            <div class="tax-item">
                <span class="tax-label">Delivery Fee:</span>
                <span class="tax-value">ETB <?php echo number_format($result['additionalFee'], 2); ?></span>
            </div>
            <div class="tax-item">
                <span class="tax-label">Total Fees:</span>
                <span class="tax-value">ETB <?php echo number_format($result['totalFees'], 2); ?></span>
            </div>
            <div class="tax-item" style="border-top: 2px solid var(--pc-secondary); padding-top: 1rem; margin-top: 0.5rem;">
                <span class="tax-label" style="font-weight: 700;">Total Cost:</span>
                <span class="tax-value" style="font-weight: 700;">ETB <?php echo number_format($result['totalCost'], 2); ?></span>
            </div>
        </div>
    </div>
    
    <div class="receipt-container" id="printableReceipt">
        <div class="receipt-header">
            <div class="receipt-logo">🛍️ PRICE CALCULATOR</div>
            <div class="receipt-title">PRICE CALCULATION RECEIPT</div>
            <div class="receipt-subtitle">Import Cost Estimation</div>
        </div>
        
        <div class="receipt-info">
            <div class="receipt-info-item">
                <span class="receipt-info-label">Receipt No:</span>
                <span class="receipt-info-value"><?php echo 'RCP' . substr(time(), -8); ?></span>
            </div>
            <div class="receipt-info-item">
                <span class="receipt-info-label">Date:</span>
                <span class="receipt-info-value"><?php echo date('F j, Y \a\t g:i A'); ?></span>
            </div>
        </div>
        
        <hr class="receipt-divider">
        
        <div class="receipt-items">
            <div class="receipt-section-title">CALCULATION DETAILS</div>
            
            <div class="receipt-item">
                <span class="receipt-item-label">Product Price in USD:</span>
                <span class="receipt-item-value">$<?php echo number_format($usdAmount, 2); ?></span>
            </div>
            
            <div class="receipt-item">
                <span class="receipt-item-label">Exchange Rate (ETB/USD):</span>
                <span class="receipt-item-value"><?php echo number_format($exchangeRateValue, 2); ?> ETB</span>
            </div>
            
            <div class="receipt-item highlight">
                <span class="receipt-item-label">Base Amount (ETB):</span>
                <span class="receipt-item-value">ETB <?php echo number_format($result['etbAmount'], 2); ?></span>
            </div>
        </div>
        
        <hr class="receipt-divider">
        
        <div class="receipt-items">
            <div class="receipt-section-title">FEES & TAXES BREAKDOWN</div>
            
            <div class="receipt-item">
                <span class="receipt-item-label">Value Tax (15%):</span>
                <span class="receipt-item-value">ETB <?php echo number_format($result['valueTax'], 2); ?></span>
            </div>
            
            <div class="receipt-item">
                <span class="receipt-item-label">Shipment Fee (10%):</span>
                <span class="receipt-item-value">ETB <?php echo number_format($result['shipmentFee'], 2); ?></span>
            </div>
            
            <div class="receipt-item">
                <span class="receipt-item-label">Processing Fee (5%):</span>
                <span class="receipt-item-value">ETB <?php echo number_format($result['processingFee'], 2); ?></span>
            </div>
            <div class="receipt-item">
                <span class="receipt-item-label">Delivery Fee:</span>
                <span class="receipt-item-value">ETB <?php echo number_format($result['additionalFee'], 2); ?></span>
            </div>
            
            <div class="receipt-item highlight">
                <span class="receipt-item-label">Total Fees:</span>
                <span class="receipt-item-value">ETB <?php echo number_format($result['totalFees'], 2); ?></span>
            </div>
        </div>
        
        <div class="receipt-total">
            <div class="receipt-total-row">
                <span>TOTAL COST</span>
                <span>ETB <?php echo number_format($result['totalCost'], 2); ?></span>
            </div>
        </div>
        
        <div class="receipt-footer">
            <div class="barcode-container">
                <svg id="barcode"></svg>
            </div>
            <div class="receipt-footer-text">Thank you for using our Service! Contact us for any inquiries.</div>
            <div class="receipt-timestamp">Generated on <?php echo date('Y-m-d H:i:s'); ?></div>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
        <button onclick="printReceipt()" class="btn-calc btn-primary-calc">
            <i class="fas fa-print"></i> Print Receipt
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- JsBarcode Library -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
// Generate Barcode
<?php if ($result): ?>
document.addEventListener('DOMContentLoaded', function() {
    JsBarcode("#barcode", "<?php echo 'RCP' . substr(time(), -8); ?>", {
        format: "CODE128",
        lineColor: "#000",
        width: 2,
        height: 40,
        displayValue: true
    });
});
<?php endif; ?>

function printReceipt() {
    window.print();
}
</script>

<?php require_once "includes/footer.php"; ?>