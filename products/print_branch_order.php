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
            --primary-light: #f8faff;
            --secondary: #858796;
            --dark: #2c3e50;
            --light: #f8f9fc;
            --border: #e3e6f0;
            --success: #1cc88a;
            --info: #36b9cc;
        }

        * { box-sizing: border-box; -webkit-print-color-adjust: exact; }
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; color: #333; line-height: 1.5; font-size: 11px; background-color: #fff; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; padding: 20px; }
        
        /* Header Styling */
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid var(--primary); }
        .brand-container { display: flex; align-items: center; }
        .brand-logo { width: 50px; height: 50px; margin-right: 15px; overflow: hidden; border: 1px solid var(--border); border-radius: 8px; }
        .brand-info h1 { margin: 0; color: var(--primary); font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
        .brand-info p { margin: 2px 0 0; color: var(--secondary); font-size: 10px; }

        .order-meta { text-align: right; }
        .order-meta h2 { margin: 0; font-size: 18px; font-weight: 700; color: var(--dark); }
        .order-meta .badge { display: inline-block; padding: 4px 12px; border-radius: 50px; background: var(--primary-light); color: var(--primary); font-weight: 700; font-size: 9px; text-transform: uppercase; margin-top: 5px; border: 1px solid var(--border); }

        /* Grid Layout */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .info-card { background: var(--light); border-radius: 8px; padding: 15px; border: 1px solid var(--border); }
        .info-card h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--secondary); margin: 0 0 10px 0; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 5px; }
        .info-card strong { display: block; font-size: 13px; color: var(--dark); margin-bottom: 4px; }
        .info-card p { margin: 0; font-size: 11px; color: #555; }
        .info-card i { width: 14px; margin-right: 6px; color: var(--primary); }

        /* Product Section */
        .section-title { font-size: 12px; font-weight: 700; color: var(--dark); margin-bottom: 15px; display: flex; align-items: center; }
        .section-title::after { content: ""; flex: 1; height: 1px; background: var(--border); margin-left: 15px; }

        .product-card { border: 1px solid var(--border); border-radius: 8px; margin-bottom: 20px; overflow: hidden; page-break-inside: avoid; }
        .product-header { background: var(--primary-light); padding: 10px 15px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .product-header h4 { margin: 0; font-size: 13px; color: var(--primary); font-weight: 700; }
        .product-id { font-size: 9px; color: var(--secondary); font-weight: 500; }

        table { width: 100%; border-collapse: collapse; }
        th { background: #fff; text-align: left; padding: 10px 15px; font-size: 9px; text-transform: uppercase; color: var(--secondary); font-weight: 600; border-bottom: 1px solid var(--border); }
        td { padding: 10px 15px; border-bottom: 1px solid var(--border); font-size: 11px; color: var(--dark); }
        tr:last-child td { border-bottom: none; }
        .qty-cell { font-weight: 700; color: var(--primary); }

        /* Footer */
        .footer { margin-top: 50px; display: flex; justify-content: space-between; align-items: flex-end; border-top: 1px solid var(--border); padding-top: 20px; }
        .metadata { font-size: 9px; color: var(--secondary); }
        .signature-area { display: flex; gap: 40px; }
        .sig-block { text-align: center; width: 140px; }
        .sig-line { border-bottom: 1px solid var(--dark); margin-bottom: 8px; height: 30px; }
        .sig-label { font-size: 9px; text-transform: uppercase; font-weight: 700; color: var(--secondary); }

        .no-print { position: fixed; top: 20px; right: 20px; z-index: 1000; background: rgba(255,255,255,0.9); padding: 10px; border-radius: 50px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); display: flex; gap: 10px; }
        .btn { padding: 8px 18px; border-radius: 50px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; font-size: 12px; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: #3e5fbc; transform: translateY(-1px); }
        .btn-secondary { background: #fff; color: var(--secondary); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--light); }

        @media print {
            .no-print { display: none; }
            .container { padding: 0; width: 100%; max-width: 100%; }
            body { background: none; }
            @page { size: auto; margin: 15mm; }
            .product-card { border: 1px solid #ddd; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="branch_orders_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Document</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="brand-container">
                <div class="brand-logo">
                    <img src="../assets/img/logo.jpg" alt="Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                </div>
                <div class="brand-info">
                    <h1><?php echo $company_name; ?></h1>
                    <p><?php echo $company_address; ?></p>
                    <p><?php echo $company_phone; ?> • <?php echo $company_email; ?></p>
                </div>
            </div>
            <div class="order-meta">
                <h2>BRANCH ORDER</h2>
                <div class="badge">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                <p style="margin: 8px 0 0; font-size: 11px; font-weight: 500;">Issued: <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h3>Customer Details</h3>
                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($order['phone_number']); ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['address'] ?: 'No address provided'); ?></p>
            </div>
            <div class="info-card">
                <h3>Order Status & Logistics</h3>
                <strong>Status: <span style="color: var(--primary);"><?php echo strtoupper($order['status']); ?></span></strong>
                <p><i class="fas fa-user-edit"></i> Handler: <?php echo htmlspecialchars($order['created_by_username']); ?></p>
                <?php if (!empty($order['source'])): ?>
                    <p><i class="fas fa-bullhorn"></i> Source: <?php echo htmlspecialchars($order['source']); ?></p>
                <?php endif; ?>
                <?php if (!empty($order['delivery_person'])): ?>
                    <p><i class="fas fa-truck"></i> Driver: <?php echo htmlspecialchars($order['delivery_person']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="product-section">
            <h3 class="section-title">Requested Items</h3>
            
            <?php 
            $count = 0;
            foreach ($order['products'] as $product): 
                $count++;
            ?>
                <div class="product-card">
                    <div class="product-header">
                        <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                        <span class="product-id">PID-<?php echo str_pad($product['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th width="30%">Color</th>
                                <th width="20%">Size</th>
                                <th width="15%">Qty</th>
                                <th width="35%">Options / Spec</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product['variations'] as $v): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($v['color'] ?: 'Standard'); ?></td>
                                    <td><?php echo htmlspecialchars($v['size'] ?: 'N/A'); ?></td>
                                    <td class="qty-cell"><?php echo $v['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($v['options'] ?: '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>



        <div class="footer">
            <div class="metadata">
                <p>This is an official branch inventory request document.</p>
                <p>Generated on <?php echo date('F j, Y • H:i'); ?></p>
            </div>
            <div class="signature-area">
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Prepared By</div>
                </div>
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Authorized By</div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

