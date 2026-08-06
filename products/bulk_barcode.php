<?php
// products/bulk_barcode.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../lib/barcode_generator.php";

$database = new Database();
$db = $database->getConnection();

// Get product IDs from query string
$ids_param = $_GET['ids'] ?? '';
$product_ids = array_filter(array_map('intval', explode(',', $ids_param)));

if (empty($product_ids)) {
    die('No products selected');
}

// Fetch products and their variants
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$query = "SELECT id, name, sku, price, has_variants FROM products WHERE id IN ($placeholders) ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute($product_ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Collect all barcodes to generate
$barcodes = [];

foreach ($products as $product) {
    if ($product['has_variants']) {
        // Get all variants for this product
        $variant_query = "SELECT id, sku, size, color, price, quantity FROM product_variants WHERE product_id = ? ORDER BY size, color";
        $variant_stmt = $db->prepare($variant_query);
        $variant_stmt->execute([$product['id']]);
        $variants = $variant_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($variants as $variant) {
            $variant_label = trim($variant['size'] . ' ' . $variant['color']);
            $barcodes[] = [
                'sku' => $variant['sku'],
                'name' => $product['name'],
                'variant' => $variant_label,
                'price' => $variant['price'] ?? $product['price'],
                'quantity' => $variant['quantity']
            ];
        }
    } else {
        // Regular product without variants
        $barcodes[] = [
            'sku' => $product['sku'],
            'name' => $product['name'],
            'variant' => '',
            'price' => $product['price'],
            'quantity' => null
        ];
    }
}

$generator = new BarcodeGenerator();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Barcode Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
        
        .barcode-page {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm;
            margin: 0 auto;
            background: white;
            page-break-after: always;
        }
        
        .barcode-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5mm;
        }
        
        .barcode-label {
            border: 1px dashed #ccc;
            padding: 3mm;
            text-align: center;
            page-break-inside: avoid;
            height: 70mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .barcode-label .product-name {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 2mm;
            line-height: 1.2;
        }
        
        .barcode-label .variant-info {
            font-size: 9pt;
            color: #666;
            margin-bottom: 2mm;
        }
        
        .barcode-label .barcode-container {
            margin: 3mm 0;
        }
        
        .barcode-label .price {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 2mm;
        }
        
        .barcode-label .sku {
            font-size: 8pt;
            color: #666;
            margin-top: 1mm;
        }
        
        @page {
            size: A4;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="no-print p-3 bg-light">
        <div class="container">
            <h3><i class="fas fa-barcode"></i> Bulk Barcode Labels</h3>
            <p>Total labels: <strong><?php echo count($barcodes); ?></strong></p>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Labels
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
    
    <?php
    $labels_per_page = 8; // 2 columns x 4 rows
    $page_count = ceil(count($barcodes) / $labels_per_page);
    
    for ($page = 0; $page < $page_count; $page++):
        $start = $page * $labels_per_page;
        $end = min($start + $labels_per_page, count($barcodes));
        $page_barcodes = array_slice($barcodes, $start, $labels_per_page);
    ?>
    
    <div class="barcode-page">
        <div class="barcode-grid">
            <?php foreach ($page_barcodes as $barcode): ?>
            <div class="barcode-label">
                <div class="product-name">
                    <?php echo htmlspecialchars($barcode['name']); ?>
                </div>
                
                <?php if (!empty($barcode['variant'])): ?>
                <div class="variant-info">
                    <?php echo htmlspecialchars($barcode['variant']); ?>
                </div>
                <?php endif; ?>
                
                <div class="barcode-container mb-2">
                    <?php echo $generator->generateSVG($barcode['sku'], 180, 60); ?>
                </div>
                
                <div class="price">
                    $<?php echo number_format($barcode['price'], 2); ?>
                </div>
                
                <div class="sku">
                    SKU: <?php echo htmlspecialchars($barcode['sku']); ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php
            // Fill remaining cells with empty labels to maintain grid
            $remaining = $labels_per_page - count($page_barcodes);
            for ($i = 0; $i < $remaining; $i++):
            ?>
            <div class="barcode-label" style="border: none;"></div>
            <?php endfor; ?>
        </div>
    </div>
    
    <?php endfor; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
