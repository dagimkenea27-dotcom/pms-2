<?php
// api/edit_movement.php
// Allows managers/admins to correct a stock movement record and
// automatically adjusts the affected product/variant quantities.

require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../models/AuditLog.php";

header('Content-Type: application/json');

Auth::requireLogin();

// Only managers and admins may edit movements
$user = Auth::getCurrentUser();
if (!in_array($user['role'], ['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id = isset($data['id']) ? (int)$data['id'] : 0;
$new_type = isset($data['movement_type']) ? strtoupper(trim($data['movement_type'])) : '';
$new_quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
$new_reason = isset($data['reason']) ? trim($data['reason']) : '';
$new_reference = isset($data['reference']) ? trim($data['reference']) : '';

// Basic validation
if (!$id || !in_array($new_type, ['IN', 'OUT']) || $new_quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // ── Fetch original movement ────────────────────────────────────────────
    $orig_stmt = $db->prepare("SELECT * FROM stock_movements WHERE id = ?");
    $orig_stmt->execute([$id]);
    $orig = $orig_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orig) {
        throw new Exception("Movement record not found.");
    }

    $old_type = $orig['movement_type']; // 'IN' or 'OUT'
    $old_quantity = (int)$orig['quantity'];
    $product_id = $orig['product_id'];
    $variant_id = $orig['variant_id'];

    // ── Calculate net stock adjustment ────────────────────────────────────
    // Effect is positive for IN, negative for OUT
    $old_effect = ($old_type === 'IN' ? $old_quantity : -$old_quantity);
    $new_effect = ($new_type === 'IN' ? $new_quantity : -$new_quantity);
    $adjustment = $new_effect - $old_effect; // net change to apply to qty

    // ── Update product quantity ────────────────────────────────────────────
    if ($adjustment !== 0) {
        $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")
            ->execute([$adjustment, $product_id]);

        // Validate product qty doesn't go negative
        $new_prod_qty = $db->query("SELECT quantity FROM products WHERE id = $product_id")->fetchColumn();
        if ($new_prod_qty < 0) {
            throw new Exception("Correction would make product stock negative (current stock too low).");
        }

        // Update variant if applicable
        if ($variant_id) {
            $db->prepare("UPDATE product_variants SET quantity = quantity + ? WHERE id = ?")
                ->execute([$adjustment, $variant_id]);

            $new_var_qty = $db->query("SELECT quantity FROM product_variants WHERE id = $variant_id")->fetchColumn();
            if ($new_var_qty < 0) {
                throw new Exception("Correction would make variant stock negative.");
            }
        }
    }

    // ── Update the movement record ─────────────────────────────────────────
    $update_stmt = $db->prepare("UPDATE stock_movements 
                                  SET movement_type = ?, quantity = ?, reason = ?, reference = ?
                                  WHERE id = ?");
    $update_stmt->execute([$new_type, $new_quantity, $new_reason, $new_reference, $id]);

    // ── Write audit log ────────────────────────────────────────────────────
    $audit = new AuditLog($db);
    $audit->log(
        $user['id'],
        'MOVEMENT_EDIT',
        "Edited movement ID $id: {$old_type} x{$old_quantity} → {$new_type} x{$new_quantity}. Adj: $adjustment. Reason: $new_reason"
    );

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Movement updated successfully.',
        'adjustment' => $adjustment,
        'old' => ['type' => $old_type, 'quantity' => $old_quantity],
        'new' => ['type' => $new_type, 'quantity' => $new_quantity],
    ]);

}
catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
