<?php
/**
 * routes/delivery_detail.php
 * Detailed delivery view for drivers with Signature and Photo capture
 */
require_once "../config/auth.php";
require_once "../config/database.php";

Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();

$deliveryId = $_GET['id'] ?? null;
if (!$deliveryId) {
    header("Location: driver_center.php");
    exit;
}

// Fetch delivery info
$query = "SELECT d.*, r.name as route_name, r.route_status 
          FROM route_deliveries d 
          JOIN routes r ON d.route_id = r.id 
          WHERE d.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$deliveryId]);
$delivery = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$delivery) {
    die("Delivery not found.");
}

require_once "../includes/header.php";
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<!-- Signature Pad JS -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<style>
    .mobile-app-container {
        max-width: 600px;
        margin: 0 auto;
        padding-bottom: 80px; /* Space for bottom nav */
    }
    .detail-card {
        border-radius: 20px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-top: 15px;
    }
    .signature-wrapper {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        background: #f8f9fc;
        position: relative;
        height: 200px;
        margin-bottom: 15px;
    }
    canvas {
        width: 100%;
        height: 100%;
        touch-action: none;
    }
    .status-stepper {
        display: flex;
        justify-content: space-between;
        margin-bottom: 25px;
        padding: 0 10px;
    }
    .step {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #dee2e6;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        position: relative;
    }
    .step.completed { background: #1cc88a; }
    .step.active { background: #4e73df; ring: 3px solid #bac8f3; }
    .step::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 2px;
        background: #dee2e6;
        left: 100%;
        top: 50%;
        z-index: -1;
    }
    .step:last-child::after { display: none; }

    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        display: flex;
        justify-content: space-around;
        padding: 10px 0;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        z-index: 1000;
    }
    .nav-item {
        text-align: center;
        color: #858796;
        text-decoration: none;
        font-size: 0.7rem;
    }
    .nav-item.active { color: #4e73df; }
    .nav-item i { font-size: 1.2rem; display: block; }
</style>

<div class="mobile-app-container">
    <div class="px-3 pt-3 d-flex align-items-center">
        <a href="driver_center.php" class="btn btn-light btn-circle me-3">
            <i class="fas fa-chevron-left"></i>
        </a>
        <h5 class="mb-0 font-weight-bold">Delivery Details</h5>
    </div>

    <div class="card detail-card mx-2">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <span class="badge bg-light text-primary">Stop #<?php echo $delivery['sequence_number']; ?></span>
                <span class="badge bg-soft-info text-info"><?php echo strtoupper($delivery['status']); ?></span>
            </div>

            <h4 class="font-weight-bold mb-1"><?php echo htmlspecialchars($delivery['customer_name']); ?></h4>
            <p class="text-muted small mb-4"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($delivery['address']); ?></p>

            <div class="row mb-4">
                <div class="col-6">
                    <div class="text-xs text-uppercase text-muted font-weight-bold">Phone</div>
                    <div class="font-weight-bold"><a href="tel:<?php echo $delivery['customer_phone']; ?>"><?php echo $delivery['customer_phone'] ?: 'N/A'; ?></a></div>
                </div>
                <div class="col-6">
                    <div class="text-xs text-uppercase text-muted font-weight-bold">Priority</div>
                    <div class="font-weight-bold text-<?php echo ($delivery['priority'] == 'high') ? 'danger' : 'success'; ?>">
                        <?php echo strtoupper($delivery['priority']); ?>
                    </div>
                </div>
            </div>

            <hr>

            <?php if ($delivery['status'] == 'delivered'): ?>
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h5 class="font-weight-bold">Delivery Completed</h5>
                    <p class="text-muted">Signature and timestamp successfully recorded.</p>
                </div>
            <?php else: ?>
                <div id="action-panel">
                    <h6 class="font-weight-bold mb-3">Proof of Delivery</h6>
                    
                    <div class="mb-3">
                        <label class="small font-weight-bold">Customer Signature</label>
                        <div class="signature-wrapper">
                            <canvas id="signature-pad"></canvas>
                        </div>
                        <button type="button" class="btn btn-sm btn-light w-100" id="clear-signature">
                            <i class="fas fa-eraser me-2"></i>Clear Signature
                        </button>
                    </div>

                    <div class="mb-4">
                        <label class="small font-weight-bold">Notes / Feedback</label>
                        <textarea id="delivery-notes" class="form-control" rows="2" placeholder="Any issues or comments?"></textarea>
                    </div>

                    <button class="btn btn-primary btn-lg w-100 py-3 font-weight-bold shadow" id="complete-delivery-btn">
                        <i class="fas fa-check-double me-2"></i> CONFIRM DELIVERY
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bottom Navigation for Mobile Feel -->
<div class="bottom-nav">
    <a href="driver_center.php" class="nav-item">
        <i class="fas fa-th-list"></i>
        <span>My Routes</span>
    </a>
    <a href="#" class="nav-item active">
        <i class="fas fa-shipping-fast"></i>
        <span>Active</span>
    </a>
    <a href="../profile.php" class="nav-item">
        <i class="fas fa-user-circle"></i>
        <span>Profile</span>
    </a>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const canvas = document.getElementById('signature-pad');
        if (canvas) {
            const signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(248, 249, 252)',
                penColor: 'rgb(0, 0, 0)'
            });

            // Adjust canvas size
            function resizeCanvas() {
                const ratio =  Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                signaturePad.clear();
            }
            window.onresize = resizeCanvas;
            resizeCanvas();

            document.getElementById('clear-signature').addEventListener('click', () => signaturePad.clear());

            document.getElementById('complete-delivery-btn').addEventListener('click', function() {
                if (signaturePad.isEmpty()) {
                    alert("Please ask the customer to sign.");
                    return;
                }

                const signatureData = signaturePad.toDataURL(); // Base64 image
                const notes = document.getElementById('delivery-notes').value;
                const btn = this;
                
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                fetch('update_logistics.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=confirm_delivery&delivery_id=<?php echo $deliveryId; ?>&signature=${encodeURIComponent(signatureData)}&notes=${encodeURIComponent(notes)}`
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || "Failed to confirm delivery");
                        btn.disabled = false;
                        btn.innerHTML = 'CONFIRM DELIVERY';
                    }
                });
            });
        }
    });
</script>

<?php require_once "../includes/footer.php"; ?>
