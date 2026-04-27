<?php
// products/print_branch_order.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/BranchOrder.php";
require_once "../config/auth.php";
require_once "../includes/functions.php";

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();
$branchOrderModel = new BranchOrder($db);

if (!isset($_GET['id'])) {
    die("Order ID is required.");
}

$id = $_GET['id'];
$order = $branchOrderModel->getById($id);

if (!$order) {
    die("Order not found.");
}

// Company Info
$company_name = "GojoShop";
$company_address = "Milkomi City Complex, Bole, Addis Ababa, Ethiopia";
$company_phone = "+251 982 808 182";
$company_email = "admin@gojo.org.et";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo htmlspecialchars($order['order_number']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --dark: #2c3e50;
            --light: #f8f9fc;
            --border: #e3e6f0;
        }

        * { box-sizing: border-box; -webkit-print-color-adjust: exact; }
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; color: #333; line-height: 1.4; font-size: 11px; }
        .container { width: 100%; margin: 0; padding: 0; background: #fff; }
        
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; }
        .header-left h1 { margin: 0; color: var(--primary); font-size: 18px; }
        .header-right { text-align: right; }
        .order-title { font-size: 16px; font-weight: 700; margin: 0; }
        .order-number { font-size: 14px; font-weight: 600; color: var(--secondary); margin-top: 2px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-block h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--secondary); border-bottom: 1px solid var(--border); padding-bottom: 3px; margin-bottom: 8px; }
        .info-content strong { display: block; font-size: 13px; margin-bottom: 3px; }
        .info-content p { margin: 0; font-size: 11px; }

        .product-section { margin-bottom: 20px; }
        .product-card { border: 1px solid var(--border); border-radius: 6px; padding: 12px; margin-bottom: 15px; }
        .product-info-simple h4 { margin: 0; font-size: 14px; color: var(--dark); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th { background: var(--light); text-align: left; padding: 6px; font-size: 9px; text-transform: uppercase; color: var(--secondary); }
        td { padding: 6px; border-bottom: 1px solid var(--border); font-size: 11px; }

        .footer { margin-top: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
        .signature-block { width: 200px; text-align: center; }
        .signature-line { border-top: 1px solid #333; margin-bottom: 5px; }
        .signature-text { font-size: 10px; font-weight: 600; text-transform: uppercase; color: var(--secondary); }

        .no-print { position: fixed; top: 20px; right: 20px; z-index: 1000; }
        .btn { padding: 10px 20px; border-radius: 30px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; font-size: 14px; }
        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-secondary { background: #fff; color: var(--secondary); border: 1px solid var(--border); margin-right: 10px; }

        @media print {
            .no-print { display: none; }
            .container { padding: 0; margin: 0; width: 100%; }
            body { background: none; -webkit-print-color-adjust: exact; }
            @page { size: A5; margin: 10mm; }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="branch_orders_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Details</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1><?php echo $company_name; ?></h1>
                <p style="font-size: 11px; color: var(--secondary); margin: 5px 0 0;"><?php echo $company_address; ?></p>
                <p style="font-size: 11px; color: var(--secondary); margin: 2px 0 0;"><?php echo $company_phone; ?> | <?php echo $company_email; ?></p>
            </div>
            <div class="header-right">
                <h2 class="order-title">BRANCH ORDER</h2>
                <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                <p style="margin: 5px 0 0; font-size: 12px;">Date: <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <h3>Customer Details</h3>
                <div class="info-content">
                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                    <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($order['phone_number']); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['address'] ?: 'No address provided'); ?></p>
                </div>
            </div>
            <div class="info-block">
                <h3>Order Status</h3>
                <div class="info-content">
                    <strong style="color: var(--primary); text-transform: uppercase;"><?php echo $order['status']; ?></strong>
                    <p>Processed by: <?php echo htmlspecialchars($order['created_by_username']); ?></p>
                    <?php if (!empty($order['delivery_person'])): ?>
                        <p><i class="fas fa-truck"></i> Delivery: <?php echo htmlspecialchars($order['delivery_person']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="product-section">
            <h3 style="font-size: 12px; text-transform: uppercase; color: var(--secondary); margin-bottom: 15px;">Requested Products</h3>
            
            <?php 
            $count = 0;
            foreach ($order['products'] as $product): 
                $count++;
            ?>
                <div class="product-card">
                    <div class="product-info-simple">
                        <h4><i class="fas fa-shopping-bag text-primary"></i> <?php echo htmlspecialchars($product['product_name']); ?></h4>
                        <p style="font-size: 10px; color: var(--secondary); margin-bottom: 5px;">Product ID: #<?php echo $product['id']; ?></p>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Color</th>
                                <th>Size</th>
                                <th>Qty</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product['variations'] as $v): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($v['color'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($v['size'] ?: '-'); ?></td>
                                    <td><strong><?php echo $v['quantity']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['options'] ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($count % 5 == 0 && $count < count($order['products'])): ?>
                    <div class="page-break"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($order['notes']): ?>
            <div class="info-block" style="margin-top: 30px;">
                <h3>Notes / Special Instructions</h3>
                <p style="font-size: 13px; background: var(--light); padding: 15px; border-radius: 4px;">
                    <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="footer">
            <div style="font-size: 10px; color: var(--secondary); max-width: 60%;">
                <p>This is a computer-generated document. No signature required unless specified by company policy.</p>
                <p>Generated on <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-text">Verified By</div>
            </div>
        </div>
    </div>

</body>
</html>
