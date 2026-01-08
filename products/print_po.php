<?php
// products/print_po.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../includes/functions.php";

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    die("PO ID is required.");
}

$po_id = $_GET['id'];

// Get PO basic info
$po_query = "SELECT po.*, s.name as supplier_name, s.address as supplier_address, s.phone as supplier_phone, 
                    s.email as supplier_email, s.contact_person as supplier_contact, u.username as creator_name,
                    u.first_name, u.last_name
             FROM purchase_orders po
             JOIN suppliers s ON po.supplier_id = s.id
             JOIN users u ON po.created_by = u.id
             WHERE po.id = ?";
$po_stmt = $db->prepare($po_query);
$po_stmt->execute([$po_id]);
$po = $po_stmt->fetch(PDO::FETCH_ASSOC);

if (!$po) {
    die("Purchase Order not found.");
}

// Get PO items
$items_query = "SELECT poi.*, p.name as product_name, p.sku as product_sku, pv.size, pv.color, pv.sku as variant_sku
                FROM purchase_order_items poi
                JOIN products p ON poi.product_id = p.id
                LEFT JOIN product_variants pv ON poi.variant_id = pv.id
                WHERE poi.purchase_order_id = ?";
$items_stmt = $db->prepare($items_query);
$items_stmt->execute([$po_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Company Info (Placeholders or from config if available)
$company_name = "GojoShop";
$company_address = "Milkomi City Complex, Office No. 203, Bole, Addis Ababa, Ethiopia";
$company_phone = "+251 982 808 182";
$company_email = "admin@gojo.org.et";
$company_website = "https://gojotech.et/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - <?php echo htmlspecialchars($po['order_number']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #303f9f;
            --text-dark: #212121;
            --text-muted: #757575;
            --border-color: #e0e0e0;
            --bg-light: #f8f9fa;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: var(--text-dark);
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 20px;
        }

        .brand h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-color);
            letter-spacing: -1px;
        }

        .brand p {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .po-meta {
            text-align: right;
        }

        .po-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: var(--primary-color);
        }

        .po-number {
            font-size: 16px;
            font-weight: 600;
            margin: 5px 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .info-section h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 5px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .info-content {
            font-size: 13px;
        }

        .info-content strong {
            display: block;
            font-size: 15px;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .info-content p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            background-color: var(--primary-color);
            color: #fff;
            text-align: left;
            padding: 12px 10px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        .item-details strong {
            display: block;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .item-details small {
            color: var(--text-muted);
            font-size: 11px;
        }

        .amount-col {
            text-align: right;
            white-space: nowrap;
        }

        .totals-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .totals-table {
            width: 250px;
        }

        .totals-table td {
            padding: 8px 10px;
            border-bottom: none;
        }

        .totals-table tr.grand-total td {
            border-top: 2px solid var(--primary-color);
            font-weight: 700;
            font-size: 16px;
            color: var(--primary-color);
        }

        .notes-section {
            margin-top: 40px;
            padding: 15px;
            background-color: var(--bg-light);
            border-radius: 4px;
        }

        .notes-section h4 {
            margin: 0 0 10px;
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .signature-block {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid var(--text-dark);
            margin-bottom: 5px;
        }

        .signature-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                padding: 0;
                max-width: 100%;
            }
            body {
                background: none;
            }
        }

        .actions {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .btn-secondary {
            background: #fff;
            color: var(--primary-color);
            margin-right: 10px;
            border: 1px solid var(--primary-color);
        }
    </style>
</head>
<body>

    <div class="actions no-print">
        <a href="view_po.php?id=<?php echo $po_id; ?>" class="btn btn-secondary">Back to Order</a>
        <button onclick="window.print();" class="btn">Print Order</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="brand">
                <h1><?php echo $company_name; ?></h1>
                <p>GojoShop Inventory Solutions</p>
                <div style="margin-top: 15px; font-size: 11px; color: var(--text-muted);">
                    <?php echo $company_address; ?><br>
                    Tel: <?php echo $company_phone; ?><br>
                    Email: <?php echo $company_email; ?><br>
                    Web: <?php echo $company_website; ?>
                </div>
            </div>
            <div class="po-meta">
                <h2 class="po-title">PURCHASE ORDER</h2>
                <div class="po-number">PO #<?php echo htmlspecialchars($po['order_number']); ?></div>
                <div style="color: var(--text-muted); font-weight: 500;">
                    Date: <?php echo date('F j, Y', strtotime($po['created_at'])); ?>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-section">
                <h3>Supplier</h3>
                <div class="info-content">
                    <strong><?php echo htmlspecialchars($po['supplier_name']); ?></strong>
                    <?php if ($po['supplier_contact']): ?>
                        <p>Attn: <?php echo htmlspecialchars($po['supplier_contact']); ?></p>
                    <?php endif; ?>
                    <p><?php echo nl2br(htmlspecialchars($po['supplier_address'])); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($po['supplier_phone']); ?></p>
                    <p>Email: <?php echo htmlspecialchars($po['supplier_email']); ?></p>
                </div>
            </div>
            <div class="info-section">
                <h3>Ship To</h3>
                <div class="info-content">
                    <strong><?php echo $company_name; ?> Warehouse</strong>
                    <p><?php echo $company_address; ?></p>
                    <p>Contact: Warehouse Manager</p>
                    <p>Tel: +251 982 808 182</p>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Item / Description</th>
                    <th style="width: 15%; text-align: center;">Qty</th>
                    <th style="width: 15%; text-align: right;">Unit Cost</th>
                    <th style="width: 20%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="item-details">
                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                        <small>
                            <?php if ($item['variant_id']): ?>
                                Variant: <?php echo htmlspecialchars($item['size'] . ' / ' . $item['color']); ?> | SKU: <?php echo htmlspecialchars($item['variant_sku']); ?>
                            <?php else: ?>
                                SKU: <?php echo htmlspecialchars($item['product_sku']); ?>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td style="text-align: center; font-weight: 500;"><?php echo $item['quantity_ordered']; ?></td>
                    <td class="amount-col">$<?php echo number_format($item['unit_cost'], 2); ?></td>
                    <td class="amount-col" style="font-weight: 600;">$<?php echo number_format($item['quantity_ordered'] * $item['unit_cost'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-container">
            <table class="totals-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="amount-col">$<?php echo number_format($po['total_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td>Total Quantity</td>
                    <td class="amount-col">
                        <?php 
                        $total_qty = 0;
                        foreach($items as $i) $total_qty += $i['quantity_ordered'];
                        echo $total_qty;
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Tax (0%)</td>
                    <td class="amount-col">$0.00</td>
                </tr>
                <tr>
                    <td>Shipping</td>
                    <td class="amount-col">$0.00</td>
                </tr>
                <tr class="grand-total">
                    <td>TOTAL</td>
                    <td class="amount-col">$<?php echo number_format($po['total_amount'], 2); ?></td>
                </tr>
            </table>
        </div>

        <?php if (!empty($po['notes'])): ?>
        <div class="notes-section">
            <h4>Notes / Special Instructions</h4>
            <div style="font-size: 13px;">
                <?php echo nl2br(htmlspecialchars($po['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <div style="font-size: 10px; color: var(--text-muted); width: 60%;">
                <p><strong>Terms:</strong> Standard Net 30 days unless otherwise agreed.</p>
                <p>Order created by <?php echo htmlspecialchars($po['first_name'] . ' ' . $po['last_name']); ?> (<?php echo htmlspecialchars($po['creator_name']); ?>)</p>
            </div>
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-label">Authorized Signature</div>
                <div style="font-size: 9px; color: var(--text-muted); margin-top: 5px;">Date: ____/____/________</div>
            </div>
        </div>
    </div>

    <!-- Auto-print on load if needed, but usually better to let user click -->
    <script>
        // Optional: window.onload = () => { window.print(); }
    </script>
</body>
</html>
