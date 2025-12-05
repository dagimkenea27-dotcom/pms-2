<?php
require_once "config/auth.php";
require_once "config/database.php";

Auth::checkAuthAndPreventCache();

// Handle exchange rate fetch
if (isset($_GET['get_rate'])) {
    try {
        $apiUrl = 'https://api.exchangerate-api.com/v4/latest/USD';
        $response = @file_get_contents($apiUrl);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['rates']['ETB'])) {
                $exchangeRateValue = $data['rates']['ETB'];
            } else {
                $exchangeRateValue = 120.0;
            }
        } else {
            $exchangeRateValue = 120.0;
        }
    } catch (Exception $e) {
        $exchangeRateValue = 120.0;
    }
} else {
    $exchangeRateValue = 120.0;
}

// Handle form submission
$result = null;
$errorMessage = null;
$usdAmount = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple validation
    $usdAmount = isset($_POST['usdAmount']) ? floatval($_POST['usdAmount']) : 0;
    $exchangeRate = isset($_POST['exchangeRate']) ? floatval($_POST['exchangeRate']) : 0;
    
    if ($usdAmount <= 0 || $exchangeRate <= 0) {
        $errorMessage = 'Valid USD amount and exchange rate are required';
    } else {
        // Calculate base ETB amount
        $etbAmount = $usdAmount * $exchangeRate;
        
        // Calculate fees and taxes
        $valueTax = $etbAmount * 0.15;      // 15% value tax
        $shipmentFee = $etbAmount * 0.10;   // 10% shipment fee
        $processingFee = $etbAmount * 0.05; // 5% processing fee
        $additionalFee = 50;                // Fixed additional fee
        
        $totalFees = $valueTax + $shipmentFee + $processingFee + $additionalFee;
        $totalCost = $etbAmount + $totalFees;
        
        $result = [
            'etbAmount' => $etbAmount,
            'valueTax' => $valueTax,
            'shipmentFee' => $shipmentFee,
            'processingFee' => $processingFee,
            'additionalFee' => $additionalFee,
            'totalFees' => $totalFees,
            'totalCost' => $totalCost
        ];
        
        $exchangeRateValue = $exchangeRate;
    }
}

require_once "includes/header.php";
?>

<style>
    /* Scoped Styles for Price Calculator */
    .price-calculator-container {
        /* Use app's font if possible, or fallback */
        /* font-family: 'Inter', -apple-system, sans-serif; */
    }

    /* Custom variables for this page */
    .price-calculator-container {
        --pc-primary: #6366f1;
        --pc-primary-dark: #4f46e5;
        --pc-secondary: #8b5cf6;
        --pc-success: #10b981;
        --pc-danger: #ef4444;
        --pc-warning: #f59e0b;
        --pc-info: #3b82f6;
        --pc-white: #ffffff;
        --pc-gray-50: #f9fafb;
        --pc-gray-100: #f3f4f6;
        --pc-gray-200: #e5e7eb;
        --pc-gray-300: #d1d5db;
        --pc-gray-500: #6b7280;
        --pc-gray-700: #374151;
        --pc-gray-900: #111827;
        --pc-radius-md: 0.5rem;
        --pc-radius-lg: 0.75rem;
        --pc-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --pc-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .price-calculator-container .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .price-calculator-container .page-header h1 {
        font-size: 2.5rem;
        color: var(--pc-gray-900);
        margin-bottom: 0.5rem;
    }

    .price-calculator-container .page-header p {
        font-size: 1.125rem;
        color: var(--pc-gray-500);
    }

    .price-calculator-container .form-card {
        background: white;
        border-radius: var(--pc-radius-lg);
        box-shadow: var(--pc-shadow-md);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .price-calculator-container .form-card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--pc-gray-200);
        background: linear-gradient(to right, var(--pc-primary), var(--pc-secondary));
        color: white;
    }

    .price-calculator-container .form-card-header h2 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: white;
    }

    .price-calculator-container .form-card-header p {
        opacity: 0.9;
        color: rgba(255, 255, 255, 0.9);
    }

    .price-calculator-container .form-card-body {
        padding: 1.5rem;
    }

    .price-calculator-container .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .price-calculator-container .form-row {
            grid-template-columns: 1fr;
        }
    }

    .price-calculator-container .form-group {
        margin-bottom: 1rem;
    }

    .price-calculator-container .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--pc-gray-700);
    }

    .price-calculator-container .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--pc-gray-300);
        border-radius: var(--pc-radius-md);
        font-size: 1rem;
        transition: border-color 0.2s;
    }

    .price-calculator-container .form-control:focus {
        outline: none;
        border-color: var(--pc-primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .price-calculator-container .input-with-action {
        display: flex;
        gap: 0.5rem;
    }

    .price-calculator-container .input-with-action .form-control {
        flex: 1;
    }

    .price-calculator-container .btn-calc {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: var(--pc-radius-md);
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        /* font-family: 'Inter', sans-serif; */
    }

    .price-calculator-container .btn-primary-calc {
        background: linear-gradient(135deg, var(--pc-primary), var(--pc-secondary));
        color: white;
    }

    .price-calculator-container .btn-primary-calc:hover {
        transform: translateY(-2px);
        box-shadow: var(--pc-shadow-lg);
        color: white;
    }

    .price-calculator-container .btn-outline-calc {
        background: transparent;
        border: 2px solid var(--pc-gray-300);
        color: var(--pc-gray-700);
    }

    .price-calculator-container .btn-outline-calc:hover {
        border-color: var(--pc-primary);
        color: var(--pc-primary);
    }

    .price-calculator-container .exchange-rate-info {
        margin-top: 0.5rem;
        padding: 0.75rem;
        background: var(--pc-gray-100);
        border-radius: var(--pc-radius-md);
        font-size: 0.875rem;
    }

    .price-calculator-container .exchange-rate-info small {
        color: var(--pc-gray-500);
    }

    .price-calculator-container .alert-calc {
        padding: 1rem;
        border-radius: var(--pc-radius-md);
        margin-bottom: 1.5rem;
    }

    .price-calculator-container .alert-error-calc {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .price-calculator-container .result-card {
        background: white;
        border-radius: var(--pc-radius-lg);
        box-shadow: var(--pc-shadow-md);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .price-calculator-container .result-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--pc-gray-200);
    }

    .price-calculator-container .result-header h2 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: var(--pc-gray-900);
    }

    .price-calculator-container .result-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        padding: 1.5rem;
    }

    .price-calculator-container .result-item {
        text-align: center;
        padding: 1.5rem;
        border-radius: var(--pc-radius-md);
        background: var(--pc-gray-50);
    }

    .price-calculator-container .result-item.total {
        background: linear-gradient(135deg, var(--pc-primary), var(--pc-secondary));
        color: white;
    }

    .price-calculator-container .result-item h3 {
        font-size: 1rem;
        margin-bottom: 1rem;
        color: var(--pc-gray-700);
    }

    .price-calculator-container .result-item.total h3 {
        color: white;
    }

    .price-calculator-container .amount {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .price-calculator-container .tax-breakdown {
        padding: 1.5rem;
        border-top: 1px solid var(--pc-gray-200);
    }

    .price-calculator-container .tax-breakdown h3 {
        margin-bottom: 1rem;
        color: var(--pc-gray-900);
    }

    .price-calculator-container .tax-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    /* Receipt Styles */
    @media print {
        @page {
            size: A4;
            margin: 0;
        }
        
        body * {
            visibility: hidden;
        }
        
        .receipt-container {
            visibility: visible;
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            margin: 0 auto; /* Center horizontally */
            
            width: 100%;
            max-width: 210mm;
            padding: 1cm;
            box-shadow: none;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            background: white;
            box-sizing: border-box;
            
            /* Zoom for print scaling */
            zoom: 0.75;
            -moz-transform: scale(0.75);
            -moz-transform-origin: center top;
            
            page-break-inside: avoid;
        }
        
        .receipt-container * {
            visibility: visible;
        }
        
        .receipt-items {
            flex: 0 0 auto;
        }
        
        /* Push footer to bottom */
        .receipt-footer {
            margin-top: auto;
            border-top: 3px double #333;
            padding-top: 1rem;
        }
        
        .no-print {
            display: none !important;
        }
    }

    .receipt-container {
        max-width: 800px;
        margin: 2rem auto;
        background: white;
        padding: 2rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        font-family: 'Courier New', monospace;
        display: flex;
        flex-direction: column;
    }

    .receipt-header {
        text-align: center;
        border-bottom: 3px double #333;
        padding-bottom: 1.5rem;
        margin-bottom: 1.5rem;
        flex: 0 0 auto;
    }

    .receipt-logo {
        font-size: 2rem;
        font-weight: bold;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
    }

    .receipt-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #111827;
        margin-bottom: 0.5rem;
    }

    .receipt-subtitle {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .receipt-info {
        display: grid;
        grid-template-columns: 6fr 8fr;
        gap: 8rem;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 8px;
        flex: 0 0 auto;
    }

    .receipt-info-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    .receipt-info-label {
        font-weight: 600;
        color: #374151;
    }

    .receipt-info-value {
        color: #111827;
    }

    .receipt-divider {
        border: none;
        border-top: 2px dashed #d1d5db;
        margin: 1.5rem 0;
        flex: 0 0 auto;
    }

    .receipt-items {
        margin-bottom: 1.5rem;
        flex: 0 0 auto;
    }

    .receipt-section-title {
        font-size: 1.125rem;
        font-weight: bold;
        color: #111827;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e5e7eb;
    }

    .receipt-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px dotted #d1d5db;
    }

    .receipt-item:last-child {
        border-bottom: none;
    }

    .receipt-item-label {
        color: #374151;
        font-weight: 500;
    }

    .receipt-item-value {
        color: #111827;
        font-weight: 600;
    }

    .receipt-item.highlight {
        background: #f3f4f6;
        padding: 0.75rem 1rem;
        margin: 0 -1rem;
        border-radius: 6px;
    }

    .receipt-total {
        margin-top: 1.5rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        border: 2px solid #6366f1;
        border-radius: 8px;
        flex: 0 0 auto;
    }

    .receipt-total-row {
        display: flex;
        justify-content: space-between;
        font-size: 1.5rem;
        font-weight: bold;
        color: #111827;
    }

    .receipt-footer {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 3px double #333;
        text-align: center;
        flex: 0 0 auto;
    }

    .receipt-footer-text {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }

    .receipt-timestamp {
        color: #9ca3af;
        font-size: 0.75rem;
        font-style: italic;
    }
    
    .barcode-container {
        text-align: center;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }
    
</style>

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
                <h3>Additional Fee</h3>
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