<?php
require_once "config/auth.php";
require_once "config/database.php";
require_once "services/PriceCalculator.php";

Auth::checkAuthAndPreventCache();

// Initialize calculator
$calculator = new PriceCalculator();

// Get supported currencies
$supportedCurrencies = $calculator->getSupportedCurrencies();

// Default values
$exchangeRateValue = $calculator->getDefaultExchangeRate('USD');
$selectedCurrency = 'USD';

// Get current user
$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

// Handle form submission
$result = null;
$errorMessage = null;
$amount = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $exchangeRate = isset($_POST['exchangeRate']) ? floatval($_POST['exchangeRate']) : 0;
    $selectedCurrency = isset($_POST['currency']) ? $_POST['currency'] : 'USD';
    $selectedLocation = isset($_POST['location']) ? $_POST['location'] : 'addis_ababa';
    
    // Validate currency
    if (!in_array($selectedCurrency, $supportedCurrencies)) {
        $errorMessage = 'Unsupported currency selected';
    } elseif ($amount <= 0 || $exchangeRate <= 0) {
        $errorMessage = 'Valid amount and exchange rate are required';
    } else {
        $result = $calculator->calculate($amount, $exchangeRate, $selectedCurrency, $selectedLocation, $userId);
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
            <p>Provide the amount and current exchange rate to calculate the total cost</p>
        </div>
        
        <div class="form-card-body">
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="currency">Currency</label>
                        <select id="currency" name="currency" class="form-control" required>
                            <?php foreach ($supportedCurrencies as $currency): ?>
                            <option value="<?php echo $currency; ?>" <?php echo ($selectedCurrency === $currency) ? 'selected' : ''; ?>>
                                <?php echo $currency; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" id="amount" name="amount" class="form-control" 
                               step="0.01" min="0" placeholder="0.00" 
                               value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location">Delivery Location</label>
                    <select id="location" name="location" class="form-control" required>
                        <option value="addis_ababa" <?php echo (isset($_POST['location']) && $_POST['location'] === 'addis_ababa') ? 'selected' : ''; ?>>Addis Ababa (ETB 200)</option>
                        <option value="jimma" <?php echo (isset($_POST['location']) && $_POST['location'] === 'jimma') ? 'selected' : ''; ?>>Jimma (ETB 400)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="exchangeRate">Exchange Rate (Selected Currency to ETB)</label>
                    <div class="input-with-action">
                        <input type="number" id="exchangeRate" name="exchangeRate" class="form-control" 
                               step="0.01" min="0" placeholder="0.00" 
                               value="<?php echo $exchangeRateValue; ?>" required>
                        <button type="button" class="btn-calc btn-outline-calc" id="refreshRateBtn" title="Get current exchange rate">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="exchange-rate-info">
                        <i class="fas fa-info-circle"></i>
                        <small id="rateStatus">Rate loaded from default. Click refresh to update.</small>
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
                <div class="amount"><?php echo $result['sourceCurrency']; ?> <?php echo number_format($result['sourceAmount'], 2); ?></div>
                <div>ETB <?php echo number_format($result['etbAmount'], 2); ?></div>
                <div>(Exchange Rate: <?php echo number_format($result['exchangeRate'], 2); ?>)</div>
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
                <div class="amount">ETB <?php echo number_format($result['deliveryFee'], 2); ?></div>
                <div>(<?php 
                    $locationNames = [
                        'addis_ababa' => 'Addis Ababa',
                        'jimma' => 'Jimma'
                    ];
                    echo isset($locationNames[$result['location']]) ? $locationNames[$result['location']] : ucfirst($result['location']);
                ?>)</div>
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
                <span class="tax-value"><?php echo $result['sourceCurrency']; ?> <?php echo number_format($result['sourceAmount'], 2); ?> = ETB <?php echo number_format($result['etbAmount'], 2); ?></span>
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
                <span class="tax-value">ETB <?php echo number_format($result['deliveryFee'], 2); ?></span>
                <span class="tax-value">(<?php 
                    $locationNames = [
                        'addis_ababa' => 'Addis Ababa',
                        'jimma' => 'Jimma'
                    ];
                    echo isset($locationNames[$result['location']]) ? $locationNames[$result['location']] : ucfirst($result['location']);
                ?>)</span>
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
                <span class="receipt-item-label">Product Price in <?php echo $result['sourceCurrency']; ?>:</span>
                <span class="receipt-item-value"><?php echo $result['sourceCurrency']; ?><?php echo number_format($result['sourceAmount'], 2); ?></span>
            </div>
            
            <div class="receipt-item">
                <span class="receipt-item-label">Exchange Rate (ETB/<?php echo $result['sourceCurrency']; ?>):</span>
                <span class="receipt-item-value"><?php echo number_format($result['exchangeRate'], 2); ?> ETB</span>
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
                <span class="receipt-item-value">ETB <?php echo number_format($result['deliveryFee'], 2); ?></span>
                <span class="receipt-item-value">(<?php 
                    $locationNames = [
                        'addis_ababa' => 'Addis Ababa',
                        'jimma' => 'Jimma'
                    ];
                    echo isset($locationNames[$result['location']]) ? $locationNames[$result['location']] : ucfirst($result['location']);
                ?>)</span>
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
        
        <div class="receipt-barcode">
            <!-- Barcode generated with Receipt No -->
            <img src="https://barcode.tec-it.com/barcode.ashx?data=<?php echo 'RCP' . substr(time(), -8); ?>&code=Code128&translate-esc=on" alt="Barcode">
        </div>
        
        <div class="receipt-footer">
            <p>Thank you for using our calculator!</p>
            <p>Generated by Stock Management System</p>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 2rem; margin-bottom: 2rem;">
        <button onclick="printReceipt()" class="btn-calc btn-outline-calc">
            <i class="fas fa-print"></i> Print Receipt
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
function printReceipt() {
    window.print();
}

document.addEventListener('DOMContentLoaded', function() {
    const currencySelect = document.getElementById('currency');
    const rateInput = document.getElementById('exchangeRate');
    const refreshBtn = document.getElementById('refreshRateBtn');
    const statusText = document.getElementById('rateStatus');
    const icon = refreshBtn.querySelector('i');
    
    function fetchRate() {
        // UI Loading State
        refreshBtn.disabled = true;
        icon.className = 'fas fa-sync-alt fa-spin';
        statusText.textContent = 'Fetching current rate...';
        
        const selectedCurrency = currencySelect.value;
        
        fetch('api/get_exchange_rate.php?currency=' + encodeURIComponent(selectedCurrency))
            .then(response => response.json())
            .then(data => {
                if (data.rate) {
                    rateInput.value = data.rate;
                    statusText.textContent = 'Rate updated successfully for ' + data.currency;
                    statusText.style.color = '#1cc88a';
                } else {
                    throw new Error('Invalid data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusText.textContent = 'Failed to fetch rate. Using default.';
                statusText.style.color = '#e74a3b';
            })
            .finally(() => {
                // UI Reset State
                refreshBtn.disabled = false;
                icon.className = 'fas fa-sync-alt';
                setTimeout(() => {
                    statusText.style.color = '';
                }, 3000);
            });
    }
    
    // Attach event listeners
    currencySelect.addEventListener('change', fetchRate);
    refreshBtn.addEventListener('click', fetchRate);
    
    // Auto-fetch on load (only if not a POST result)
    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    fetchRate();
    <?php endif; ?>
});
</script>

<?php require_once "includes/footer.php"; ?>