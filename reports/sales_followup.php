<?php
session_start();

// Define upload directory
define('GOJO_UPLOAD_DIR', dirname(__DIR__) . '/assets/uploads/gojo_orders/');
define('GOJO_UPLOAD_URL', '../assets/uploads/gojo_orders/');

// Create upload directory if it doesn't exist
if (!file_exists(GOJO_UPLOAD_DIR)) {
    mkdir(GOJO_UPLOAD_DIR, 0755, true);
}

// Helper function to save Base64 image to file
function saveBase64Image($base64String, $orderId) {
    if (empty($base64String) || strpos($base64String, 'data:image') !== 0) {
        return null;
    }
    
    // Extract image data
    preg_match('/data:image\/(\w+);base64,(.+)/', $base64String, $matches);
    if (count($matches) !== 3) {
        return null;
    }
    
    $imageType = $matches[1];
    $imageData = base64_decode($matches[2]);
    
    // Generate unique filename
    $filename = 'order_' . $orderId . '_' . time() . '.' . $imageType;
    $filepath = GOJO_UPLOAD_DIR . $filename;
    
    // Save file
    if (file_put_contents($filepath, $imageData)) {
        return $filename;
    }
    
    return null;
}

// Helper function to delete image file
function deleteOrderImage($filename) {
    if (empty($filename)) {
        return;
    }
    
    $filepath = GOJO_UPLOAD_DIR . $filename;
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

// Helper function to get image URL
function getImageUrl($filename) {
    if (empty($filename)) {
        return null;
    }
    return GOJO_UPLOAD_URL . $filename;
}

// Initialize "database" in session if not exists
if (!isset($_SESSION['gojo_orders'])) {
    $_SESSION['gojo_orders'] = [];
}

// --- ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $orderId = substr(uniqid(), -8);
        
        // Handle image upload
        $productImage = null;
        if (!empty($_POST['productImage'])) {
            $productImage = saveBase64Image($_POST['productImage'], $orderId);
        }
        
        $newOrder = [
            'id' => $orderId,
            'customer' => $_POST['customer'],
            'item' => $_POST['item'],
            'phone' => $_POST['phone'],
            'type' => $_POST['type'],
            'price' => (float)$_POST['price'],
            'status' => 'Pending',
            'availability' => 'pending',
            'customerConfirmed' => false,
            'createdAt' => date('Y-m-d H:i:s'),
            'callAttempts' => 0,
            'inventoryChecked' => false,
            'prepaymentPaid' => false,
            'productImage' => $productImage,
            'smsSent' => null
        ];
        $_SESSION['gojo_orders'][] = $newOrder;
    }

    if ($action === 'update_status') {
        foreach ($_SESSION['gojo_orders'] as &$order) {
            if ($order['id'] === $_POST['id']) {
                if (isset($_POST['availability'])) $order['availability'] = $_POST['availability'];
                if (isset($_POST['customerConfirmed'])) $order['customerConfirmed'] = $_POST['customerConfirmed'] === '1';
                if (isset($_POST['inventoryChecked'])) $order['inventoryChecked'] = $_POST['inventoryChecked'] === '1';
                if (isset($_POST['prepaymentPaid'])) $order['prepaymentPaid'] = $_POST['prepaymentPaid'] === '1';
                if (isset($_POST['final_confirm'])) $order['status'] = 'Confirmed';
                if (isset($_POST['call'])) $order['callAttempts']++;
                
                // Handle image update
                if (isset($_POST['productImage']) && !empty($_POST['productImage'])) {
                    // Delete old image if exists
                    if (!empty($order['productImage'])) {
                        deleteOrderImage($order['productImage']);
                    }
                    // Save new image
                    $order['productImage'] = saveBase64Image($_POST['productImage'], $order['id']);
                }
                
                if (isset($_POST['smsSent'])) {
                    $order['smsSent'] = $_POST['smsSent'];
                    if ($_POST['smsSent'] === 'SMS2') {
                        $order['status'] = 'Canceled';
                    }
                }
            }
        }
    }

    if ($action === 'delete') {
        // Find and delete the order's image
        foreach ($_SESSION['gojo_orders'] as $order) {
            if ($order['id'] === $_POST['id']) {
                deleteOrderImage($order['productImage']);
                break;
            }
        }
        
        $_SESSION['gojo_orders'] = array_filter($_SESSION['gojo_orders'], function($o) {
            return $o['id'] !== $_POST['id'];
        });
        $_SESSION['gojo_orders'] = array_values($_SESSION['gojo_orders']);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- HELPERS ---
function calculateTimeLeft($createdAt) {
    $start = strtotime($createdAt);
    $deadline = $start + (48 * 60 * 60);
    $diff = $deadline - time();
    
    if ($diff <= 0) return ['text' => 'EXPIRED', 'urgent' => true];
    
    $h = floor($diff / 3600);
    $m = floor(($diff % 3600) / 60);
    $s = $diff % 60;
    return ['text' => "{$h}h {$m}m {$s}s", 'urgent' => $h < 12];
}

function isPrepaymentRequired($price) {
    return (float)$price >= 3000;
}

$search = $_GET['search'] ?? '';
$filtered_orders = array_filter($_SESSION['gojo_orders'], function($o) use ($search) {
    if (!$search) return true;
    return stripos($o['customer'], $search) !== false || stripos($o['item'], $search) !== false || strpos($o['phone'], $search) !== false;
});

// Count stats
$pending_count = count(array_filter($_SESSION['gojo_orders'], fn($o) => $o['status'] === 'Pending'));
$confirmed_count = count(array_filter($_SESSION['gojo_orders'], fn($o) => $o['status'] === 'Confirmed'));
$total_count = count($_SESSION['gojo_orders']);

// Include header
require_once '../includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');
    
    .gojo-container {
        font-family: 'Inter', sans-serif;
        max-width: 640px;
        margin: 0 auto;
        padding-bottom: 120px;
    }
    
    .gojo-header {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
        color: white;
        padding: 2rem 1.5rem;
        border-radius: 0 0 2.5rem 2.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        margin: -1.5rem -1.5rem 1.5rem -1.5rem;
    }
    
    .gojo-title {
        font-size: 1.75rem;
        font-weight: 900;
        text-transform: uppercase;
        font-style: italic;
        letter-spacing: -0.05em;
        margin: 0;
    }
    
    .gojo-subtitle {
        font-size: 0.625rem;
        font-weight: 700;
        color: #a5b4fc;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-top: 0.25rem;
    }
    
    .gojo-add-btn {
        background: #10b981;
        width: 48px;
        height: 48px;
        border-radius: 1rem;
        border: none;
        color: white;
        font-size: 1.5rem;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        transition: all 0.2s;
    }
    
    .gojo-add-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.5);
    }
    
    .gojo-search {
        position: relative;
        margin-bottom: 1.5rem;
    }
    
    .gojo-search input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border-radius: 1rem;
        border: none;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        font-weight: 500;
        font-size: 0.875rem;
    }
    
    .gojo-search input:focus {
        outline: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .gojo-search i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    .gojo-order-card {
        background: white;
        padding: 0.75rem;
        border-radius: 1.5rem;
        border: 2px solid transparent;
        margin-bottom: 0.75rem;
        display: flex;
        gap: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    
    .gojo-order-card:hover {
        border-color: #4f46e5;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
    }
    
    .gojo-order-card.canceled {
        opacity: 0.6;
        filter: grayscale(1);
    }
    
    .gojo-product-img {
        width: 64px;
        height: 64px;
        border-radius: 0.75rem;
        background: #f1f5f9;
        flex-shrink: 0;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .gojo-product-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .gojo-product-img i {
        color: #cbd5e1;
        font-size: 1.5rem;
    }
    
    .gojo-unavailable-overlay {
        position: absolute;
        inset: 0;
        background: rgba(239, 68, 68, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .gojo-badge {
        font-size: 0.5rem;
        font-weight: 900;
        text-transform: uppercase;
        padding: 0.125rem 0.5rem;
        border-radius: 0.375rem;
        display: inline-block;
    }
    
    .gojo-badge-urgent {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .gojo-badge-normal {
        background: #f1f5f9;
        color: #64748b;
    }
    
    .gojo-badge-confirmed {
        background: #d1fae5;
        color: #059669;
    }
    
    .gojo-badge-canceled {
        background: #e2e8f0;
        color: #64748b;
    }
    
    .gojo-badge-small {
        font-size: 0.5rem;
        font-weight: 900;
        text-transform: uppercase;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
    }
    
    .gojo-badge-available {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .gojo-badge-unavailable {
        background: #fee2e2;
        color: #b91c1c;
    }
    
    .gojo-badge-pending {
        background: #f1f5f9;
        color: #64748b;
    }
    
    .gojo-badge-verified {
        background: #d1fae5;
        color: #059669;
    }
    
    .gojo-stats-bar {
        position: fixed;
        bottom: 1.5rem;
        left: 50%;
        transform: translateX(-32%);
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
        color: white;
        border-radius: 1.5rem;
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        z-index: 1000;
        max-width: 600px;
        width: calc(100% - 3rem);
    }
    
    .gojo-stat {
        text-align: center;
        padding: 0 0.75rem;
    }
    
    .gojo-stat-label {
        font-size: 0.5rem;
        font-weight: 900;
        text-transform: uppercase;
        opacity: 0.7;
        margin-bottom: 0.25rem;
    }
    
    .gojo-stat-value {
        font-size: 1.25rem;
        font-weight: 900;
    }
    
    .gojo-stat-label.pending {
        color: #a5b4fc;
    }
    
    .gojo-stat-label.confirmed {
        color: #6ee7b7;
    }
    
    .gojo-total-badge {
        background: #312e81;
        padding: 0.375rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.625rem;
        font-weight: 900;
        text-transform: uppercase;
        color: #a5b4fc;
    }
    
    .gojo-modal {
        position: fixed;
        inset: 0;
        background: rgba(30, 27, 75, 0.9);
        backdrop-filter: blur(4px);
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        overflow-y: auto;
    }
    
    .gojo-modal-content {
        background: white;
        width: 100%;
        max-width: 28rem;
        border-radius: 2.5rem;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        animation: modalSlideIn 0.3s ease-out;
        margin: auto;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .gojo-modal-header {
        padding: 1.5rem;
        background: #eef2ff;
        border-bottom: 1px solid #e0e7ff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .gojo-modal-title {
        font-size: 1.25rem;
        font-weight: 900;
        text-transform: uppercase;
        font-style: italic;
        letter-spacing: -0.05em;
        color: #1e1b4b;
    }
    
    .gojo-modal-body {
        padding: 1.5rem;
    }
    
    .gojo-drawer {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(4px);
        z-index: 2000;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }
    
    .gojo-drawer-content {
        background: white;
        border-radius: 2.5rem 2.5rem 0 0;
        padding: 1.5rem;
        max-height: 95vh;
        overflow-y: auto;
        animation: drawerSlideUp 0.3s ease-out;
    }
    
    @keyframes drawerSlideUp {
        from {
            transform: translateY(100%);
        }
        to {
            transform: translateY(0);
        }
    }
    
    .gojo-drawer-handle {
        width: 3rem;
        height: 0.375rem;
        background: #e2e8f0;
        border-radius: 9999px;
        margin: 0 auto 1.5rem;
        cursor: pointer;
    }
    
    .gojo-image-upload {
        width: 6rem;
        height: 6rem;
        border-radius: 1rem;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        overflow: hidden;
        position: relative;
        flex-shrink: 0;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .gojo-image-upload:hover .gojo-image-overlay {
        opacity: 1;
    }
    
    .gojo-image-upload img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .gojo-image-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .gojo-step-card {
        padding: 1rem;
        border-radius: 1rem;
        border: 1px solid;
        margin-bottom: 1rem;
    }
    
    .gojo-step-card.indigo {
        background: #eef2ff;
        border-color: #e0e7ff;
    }
    
    .gojo-step-card.emerald {
        background: #ecfdf5;
        border-color: #d1fae5;
    }
    
    .gojo-step-card.slate {
        background: #f8fafc;
        border-color: #f1f5f9;
    }
    
    .gojo-step-card.disabled {
        opacity: 0.4;
        pointer-events: none;
        filter: grayscale(1);
    }
    
    .gojo-step-title {
        font-size: 0.625rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.75rem;
    }
    
    .gojo-btn {
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        font-weight: 900;
        font-size: 0.625rem;
        text-transform: uppercase;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .gojo-btn:active {
        transform: scale(0.95);
    }
    
    .gojo-btn-primary {
        background: #4f46e5;
        color: white;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    
    .gojo-btn-success {
        background: #10b981;
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .gojo-btn-danger {
        background: #ef4444;
        color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .gojo-btn-outline {
        background: white;
        border: 1px solid;
    }
    
    .gojo-btn:disabled {
        background: #f1f5f9;
        color: #cbd5e1;
        box-shadow: none;
        cursor: not-allowed;
    }
    
    .gojo-checkbox {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 0.5rem;
        border: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .gojo-checkbox.checked {
        background: #4f46e5;
        border-color: #4f46e5;
        color: white;
    }
    
    .gojo-image-preview {
        position: fixed;
        inset: 0;
        background: black;
        z-index: 3000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    
    .gojo-image-preview img {
        max-width: 100%;
        max-height: 85vh;
        object-fit: contain;
        border-radius: 0.5rem;
    }
    
    .gojo-close-preview {
        position: absolute;
        top: 2rem;
        right: 2rem;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(12px);
        color: white;
        width: 3rem;
        height: 3rem;
        border-radius: 9999px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .gojo-upload-area {
        width: 8rem;
        height: 8rem;
        border-radius: 1.5rem;
        background: #f1f5f9;
        border: 2px dashed #cbd5e1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        overflow: hidden;
        margin: 0 auto 0.5rem;
    }
    
    .gojo-upload-area img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .gojo-form-input {
        width: 100%;
        padding: 1rem;
        border-radius: 0.75rem;
        background: #f1f5f9;
        border: none;
        font-weight: 700;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }
    
    .gojo-form-input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }
</style>

<div class="gojo-container">
    <!-- Header -->
    <div class="gojo-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="gojo-title">Gojo Shop</h1>
                <p class="gojo-subtitle">SOP Sales Manager</p>
            </div>
            <button class="gojo-add-btn" onclick="showAddModal()">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>

    <!-- Search -->
    <div class="gojo-search">
        <i class="fas fa-search"></i>
        <form method="GET">
            <input type="text" name="search" placeholder="Search customer, phone, or item..." value="<?= htmlspecialchars($search) ?>">
        </form>
    </div>

    <!-- Order List -->
    <div class="gojo-order-list">
        <?php if (empty($filtered_orders)): ?>
            <div class="text-center py-5" style="opacity: 0.2;">
                <i class="fas fa-shopping-bag" style="font-size: 4rem;"></i>
                <p class="font-weight-bold text-uppercase mt-3" style="font-size: 0.875rem;">No orders found</p>
            </div>
        <?php else: ?>
            <?php foreach (array_reverse($filtered_orders) as $order): 
                $time = calculateTimeLeft($order['createdAt']);
                $isPrepay = isPrepaymentRequired($order['price']);
                $isConfirmed = $order['status'] === 'Confirmed';
                $isCanceled = $order['status'] === 'Canceled';
            ?>
                <div class="gojo-order-card <?= $isCanceled ? 'canceled' : '' ?>" onclick='openDrawer(<?= json_encode($order) ?>)'>
                    <div class="gojo-product-img">
                        <?php if ($order['productImage']): 
                            $imageUrl = getImageUrl($order['productImage']);
                        ?>
                            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="Product">
                        <?php else: ?>
                            <i class="fas fa-box"></i>
                        <?php endif; ?>
                        <?php if ($order['availability'] === 'no'): ?>
                            <div class="gojo-unavailable-overlay">
                                <i class="fas fa-ban text-danger"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-between py-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div style="min-width: 0;">
                                <h3 class="font-weight-black mb-0" style="font-size: 0.875rem; line-height: 1.2;"><?= htmlspecialchars($order['customer']) ?></h3>
                                <p class="mb-0 d-flex align-items-center gap-1" style="font-size: 0.625rem; font-weight: 700; color: #64748b;">
                                    <i class="fas fa-tag" style="font-size: 0.625rem;"></i>
                                    <?= htmlspecialchars($order['item']) ?>
                                </p>
                            </div>
                            <div class="text-right" style="flex-shrink: 0;">
                                <p class="mb-1 font-weight-black" style="font-size: 0.75rem; color: #4f46e5;"><?= number_format($order['price']) ?> ETB</p>
                                <span class="gojo-badge <?= $isConfirmed ? 'gojo-badge-confirmed' : ($isCanceled ? 'gojo-badge-canceled' : ($time['urgent'] ? 'gojo-badge-urgent' : 'gojo-badge-normal')) ?>">
                                    <?= $isConfirmed ? 'FINALIZED' : ($isCanceled ? 'CANCELED' : $time['text']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <span class="gojo-badge-small <?= $order['availability'] === 'yes' ? 'gojo-badge-available' : ($order['availability'] === 'no' ? 'gojo-badge-unavailable' : 'gojo-badge-pending') ?>">
                                <?= $order['availability'] === 'yes' ? 'Available' : ($order['availability'] === 'no' ? 'Out of Stock' : 'Checking...') ?>
                            </span>
                            <?php if ($order['customerConfirmed'] && !$isCanceled): ?>
                                <span class="gojo-badge-small gojo-badge-verified d-flex align-items-center gap-1">
                                    <i class="fas fa-phone" style="font-size: 0.5rem;"></i> Verified
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Bar -->
<div class="gojo-stats-bar">
    <div class="d-flex gap-4 px-2">
        <div class="gojo-stat">
            <p class="gojo-stat-label pending">Pending</p>
            <p class="gojo-stat-value"><?= $pending_count ?></p>
        </div>
        <div class="gojo-stat">
            <p class="gojo-stat-label confirmed">Finalized</p>
            <p class="gojo-stat-value"><?= $confirmed_count ?></p>
        </div>
    </div>
    <div class="gojo-total-badge">
        <?= $total_count ?> TOTAL
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="gojo-modal" style="display: none;">
    <div class="gojo-modal-content">
        <div class="gojo-modal-header">
            <h2 class="gojo-modal-title">New Entry</h2>
            <button onclick="hideAddModal()" style="background: none; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer;">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <form action="" method="POST" class="gojo-modal-body">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="productImage" id="modalImageData">
            
            <div class="gojo-upload-area" onclick="document.getElementById('modalImageInput').click()">
                <img id="modalImagePreview" style="display: none;">
                <div id="modalImagePlaceholder">
                    <i class="fas fa-camera text-muted mb-1"></i>
                    <span style="font-size: 0.625rem; font-weight: 900; text-transform: uppercase; color: #94a3b8;">Add Photo</span>
                </div>
                <input type="file" id="modalImageInput" accept="image/*" style="display: none;" onchange="handleModalImageUpload(this)">
            </div>
            
            <input required name="customer" placeholder="Customer Name" class="gojo-form-input">
            <input required name="item" placeholder="Product" class="gojo-form-input">
            <input required name="phone" placeholder="Phone" class="gojo-form-input">
            <input required name="price" type="number" placeholder="Price (ETB)" class="gojo-form-input">
            <select name="type" class="gojo-form-input">
                <option value="In-Stock">In-Stock</option>
                <option value="Pre-Order">Pre-Order</option>
            </select>
            <button type="submit" class="gojo-btn gojo-btn-primary w-100 py-3 mt-2">Create & Start Clock</button>
        </form>
    </div>
</div>

<!-- Drawer (will be populated by JS) -->
<div id="drawer" class="gojo-drawer" style="display: none;">
    <div class="gojo-drawer-content">
        <div class="gojo-drawer-handle" onclick="closeDrawer()"></div>
        <div id="drawerContent"></div>
    </div>
</div>

<!-- Image Preview -->
<div id="imagePreview" class="gojo-image-preview" style="display: none;" onclick="closeImagePreview()">
    <button class="gojo-close-preview" onclick="closeImagePreview()">
        <i class="fas fa-times"></i>
    </button>
    <img id="previewImage" src="">
</div>

<script>
// Global state
let currentOrder = null;
let countdownInterval = null;

// Modal functions
function showAddModal() {
    document.getElementById('addModal').style.display = 'flex';
    document.getElementById('modalImageData').value = '';
    document.getElementById('modalImagePreview').style.display = 'none';
    document.getElementById('modalImagePlaceholder').style.display = 'block';
}

function hideAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

function handleModalImageUpload(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('modalImagePreview').src = e.target.result;
            document.getElementById('modalImagePreview').style.display = 'block';
            document.getElementById('modalImagePlaceholder').style.display = 'none';
            document.getElementById('modalImageData').value = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Drawer functions
function openDrawer(order) {
    currentOrder = order;
    const isPrepay = parseFloat(order.price) >= 3000;
    const deposit = Math.ceil(order.price * 0.3);
    
    // Get proper image URL
    const imageUrl = order.productImage ? '<?= GOJO_UPLOAD_URL ?>' + order.productImage : null;
    
    const content = `
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div class="d-flex gap-3">
                <div class="gojo-image-upload" onclick="handleDrawerImageClick('${order.id}', ${imageUrl ? `'${imageUrl}'` : 'null'})">
                    ${imageUrl ? 
                        `<img src="${imageUrl}">
                         <div class="gojo-image-overlay"><i class="fas fa-camera text-white"></i></div>` :
                        `<i class="fas fa-camera text-muted"></i>
                         <span style="font-size: 0.5rem; font-weight: 900; text-transform: uppercase; color: #94a3b8; margin-top: 0.25rem;">Add Pic</span>`
                    }
                    <input type="file" id="drawerImageInput_${order.id}" accept="image/*" style="display: none;" onchange="handleDrawerImageUpload(this, '${order.id}')">
                </div>
                <div class="flex-grow-1">
                    <h2 class="font-weight-black mb-1" style="font-size: 1.25rem; line-height: 1.2;">${order.customer}</h2>
                    <p class="font-weight-black mb-0" style="font-size: 1rem; color: #4f46e5;">${order.item}</p>
                    <p class="font-weight-bold mb-0" style="font-size: 0.875rem; color: #64748b;">${order.phone}</p>
                </div>
            </div>
            <button onclick="deleteOrder('${order.id}')" style="background: none; border: none; color: #cbd5e1; padding: 0.5rem; cursor: pointer;">
                <i class="fas fa-trash"></i>
            </button>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" value="${order.id}">
            <input type="hidden" name="productImage" id="drawerImageData_${order.id}">

            <!-- Step 1: Availability -->
            <div class="gojo-step-card indigo">
                <h3 class="gojo-step-title" style="color: #a5b4fc;">1. Product Check</h3>
                <div class="d-flex gap-2">
                    <button type="button" onclick="setAvailability('${order.id}', 'yes')" class="gojo-btn flex-grow-1 ${order.availability === 'yes' ? 'gojo-btn-primary' : 'gojo-btn-outline'}" style="${order.availability === 'yes' ? '' : 'border-color: #e0e7ff; color: #1e1b4b;'}">
                        <i class="fas fa-check-circle"></i> Available
                    </button>
                    <button type="button" onclick="setAvailability('${order.id}', 'no')" class="gojo-btn flex-grow-1 ${order.availability === 'no' ? 'gojo-btn-danger' : 'gojo-btn-outline'}" style="${order.availability === 'no' ? '' : 'border-color: #fecaca; color: #7f1d1d;'}">
                        <i class="fas fa-ban"></i> Not Available
                    </button>
                </div>
                ${order.availability === 'no' ? `
                    <button type="button" onclick="sendAvailabilitySMS('${order.phone}', '${order.customer}', '${order.item}')" class="gojo-btn w-100 mt-3" style="background: #fee2e2; color: #b91c1c;">
                        <i class="fas fa-comment"></i> Contact Customer (Not Available)
                    </button>
                ` : ''}
            </div>

            <!-- Step 2: Confirmation -->
            <div class="gojo-step-card emerald ${order.availability !== 'yes' ? 'disabled' : ''}">
                <h3 class="gojo-step-title" style="color: #059669;">2. Verbal Confirmation</h3>
                <div class="d-flex gap-2 mb-3">
                    <a href="tel:${order.phone}" onclick="incrementCallAttempts('${order.id}')" class="gojo-btn flex-grow-1 gojo-btn-outline" style="border-color: #d1fae5; color: #059669; text-decoration: none;">
                        <i class="fas fa-phone"></i> Call Now (${order.callAttempts || 0})
                    </a>
                    <button type="button" onclick="toggleConfirmation('${order.id}')" class="gojo-btn flex-grow-1 ${order.customerConfirmed ? 'gojo-btn-success' : 'gojo-btn-outline'}" style="${order.customerConfirmed ? '' : 'border-color: #d1fae5; color: #047857;'}">
                        <i class="fas fa-check-circle"></i> ${order.customerConfirmed ? 'Confirmed' : 'Set Confirmed'}
                    </button>
                </div>
            </div>

            <!-- Step 3: Checklist -->
            <div class="gojo-step-card slate">
                <h3 class="gojo-step-title" style="color: #94a3b8;">3. Confirmation Checklist</h3>
                <div class="mb-3">
                    <button type="button" onclick="toggleInventory('${order.id}')" class="d-flex align-items-center gap-3 w-100 p-2" style="background: none; border: none; cursor: pointer;">
                        <div class="gojo-checkbox ${order.inventoryChecked ? 'checked' : ''}">
                            ${order.inventoryChecked ? '<i class="fas fa-check-circle"></i>' : ''}
                        </div>
                        <span class="font-weight-bold" style="font-size: 0.875rem; color: ${order.inventoryChecked ? '#0f172a' : '#94a3b8'};">Inventory Verified</span>
                    </button>
                </div>

                ${isPrepay ? `
                    <div class="p-3 rounded-xl" style="background: white; border: 1px solid #f1f5f9; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="gojo-checkbox ${order.prepaymentPaid ? 'checked' : ''}" style="${order.prepaymentPaid ? 'background: #10b981; border-color: #10b981;' : ''}">
                                    ${order.prepaymentPaid ? '<i class="fas fa-wallet"></i>' : ''}
                                </div>
                                <div>
                                    <span class="font-weight-bold d-block" style="font-size: 0.875rem; color: ${order.prepaymentPaid ? '#0f172a' : '#94a3b8'};">30% Deposit Required</span>
                                    <span style="font-size: 0.625rem; font-weight: 900; text-transform: uppercase; color: #dc2626;">Amount: ${deposit} ETB</span>
                                </div>
                            </div>
                            <input type="checkbox" name="prepaymentPaid" value="1" ${order.prepaymentPaid ? 'checked' : ''} style="width: 1.25rem; height: 1.25rem; accent-color: #10b981;">
                        </div>
                    </div>
                ` : ''}
            </div>

            <!-- Final Action -->
            <button type="submit" name="final_confirm" value="1" 
                    ${!order.inventoryChecked || !order.customerConfirmed || order.availability !== 'yes' || (isPrepay && !order.prepaymentPaid) || order.status === 'Confirmed' ? 'disabled' : ''}
                    class="gojo-btn gojo-btn-success w-100 py-4 d-flex flex-column align-items-center justify-content-center">
                <i class="fas fa-check-circle" style="font-size: 1.5rem; margin-bottom: 0.25rem;"></i>
                <span>${order.status === 'Confirmed' ? 'Finalized' : 'Confirm Order'}</span>
            </button>

            <div class="pt-2 pb-4">
                <h3 class="gojo-step-title text-center" style="color: #94a3b8;">No Response Flow</h3>
                <div class="row g-3">
                    <div class="col-6">
                        <button type="button" onclick="sendSOPMessage('${order.phone}', '${order.customer}', '${order.item}', ${order.price}, 'SMS1', '${order.id}')" class="gojo-btn w-100" style="background: #f1f5f9; color: #0f172a;">
                            <i class="fas fa-paper-plane"></i> SMS 1: 48h
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" onclick="sendSOPMessage('${order.phone}', '${order.customer}', '${order.item}', ${order.price}, 'SMS2', '${order.id}')" class="gojo-btn w-100" style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;">
                            <i class="fas fa-times-circle"></i> Cancel
                        </button>
                    </div>
                </div>
            </div>

            <input type="hidden" name="customerConfirmed" id="customerConfirmed_${order.id}" value="${order.customerConfirmed ? '1' : '0'}">
            <input type="hidden" name="inventoryChecked" id="inventoryChecked_${order.id}" value="${order.inventoryChecked ? '1' : '0'}">
            <input type="hidden" name="availability" id="availability_${order.id}" value="${order.availability}">
        </form>
    `;
    
    document.getElementById('drawerContent').innerHTML = content;
    document.getElementById('drawer').style.display = 'flex';
    
    // Start countdown update
    startCountdownUpdate();
}

function closeDrawer() {
    document.getElementById('drawer').style.display = 'none';
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
}

function deleteOrder(id) {
    if (confirm('Delete this order record?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function setAvailability(id, value) {
    document.getElementById(`availability_${id}`).value = value;
    // Submit form to update
    const form = document.querySelector(`input[name="id"][value="${id}"]`).closest('form');
    form.submit();
}

function toggleConfirmation(id) {
    const input = document.getElementById(`customerConfirmed_${id}`);
    input.value = input.value === '1' ? '0' : '1';
    const form = input.closest('form');
    form.submit();
}

function toggleInventory(id) {
    const input = document.getElementById(`inventoryChecked_${id}`);
    input.value = input.value === '1' ? '0' : '1';
    const form = input.closest('form');
    form.submit();
}

function incrementCallAttempts(id) {
    const form = document.querySelector(`input[name="id"][value="${id}"]`).closest('form');
    const callInput = document.createElement('input');
    callInput.type = 'hidden';
    callInput.name = 'call';
    callInput.value = '1';
    form.appendChild(callInput);
    form.submit();
}

function handleDrawerImageClick(id, currentImage) {
    if (currentImage && currentImage !== 'null') {
        showImagePreview(currentImage);
    } else {
        document.getElementById(`drawerImageInput_${id}`).click();
    }
}

function handleDrawerImageUpload(input, id) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(`drawerImageData_${id}`).value = e.target.result;
            const form = input.closest('form');
            form.submit();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function showImagePreview(src) {
    document.getElementById('previewImage').src = src;
    document.getElementById('imagePreview').style.display = 'flex';
}

function closeImagePreview() {
    document.getElementById('imagePreview').style.display = 'none';
}

function sendAvailabilitySMS(phone, customer, item) {
    const message = `Gojo Shop: Hello ${customer}, unfortunately the ${item} you ordered is currently not available. Would you like to check a different size or item? 0988554488`;
    const sep = /iPhone/i.test(navigator.userAgent) ? '&' : '?';
    window.location.href = `sms:${phone}${sep}body=${encodeURIComponent(message)}`;
}

function sendSOPMessage(phone, customer, item, price, type, id) {
    const deposit = Math.ceil(price * 0.3);
    const requiresDeposit = price >= 3000;
    
    let message = "";
    if (type === 'SMS1') {
        message = `Gojo Shop: We tried calling to confirm your order (${item}). ${requiresDeposit ? `Note: 30% deposit (${deposit} ETB) required.` : ''} Please call us back within 48h or it will be canceled. 0988554488`;
    } else {
        message = `Gojo Shop: Order for ${item} canceled due to no confirmation. Re-order anytime! 0988554488`;
    }
    
    // Update SMS sent status
    const form = document.querySelector(`input[name="id"][value="${id}"]`).closest('form');
    const smsInput = document.createElement('input');
    smsInput.type = 'hidden';
    smsInput.name = 'smsSent';
    smsInput.value = type;
    form.appendChild(smsInput);
    
    const sep = /iPhone/i.test(navigator.userAgent) ? '&' : '?';
    window.location.href = `sms:${phone}${sep}body=${encodeURIComponent(message)}`;
    
    // Submit form after a delay to allow SMS app to open
    setTimeout(() => form.submit(), 1000);
}

function startCountdownUpdate() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    countdownInterval = setInterval(() => {
        // Update all countdown badges on the page
        const badges = document.querySelectorAll('.gojo-badge:not(.gojo-badge-confirmed):not(.gojo-badge-canceled)');
        // This would require server-side rendering or storing order data in JS
        // For now, we'll just reload the page periodically
    }, 1000);
}

// Auto-update countdowns every second
setInterval(() => {
    const badges = document.querySelectorAll('.gojo-badge');
    // Would need to store order data in JS to update in real-time
    // For simplicity, we'll reload every minute
}, 60000);
</script>

<?php require_once '../includes/footer.php'; ?>
