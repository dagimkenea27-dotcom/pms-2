<?php
// products/delete_po.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../includes/functions.php";

Auth::requireLogin();

if (!isset($_GET['id'])) {
    header("Location: purchase_orders.php");
    exit();
}

$po_id = $_GET['id'];
$database = new Database();
$db = $database->getConnection();

try {
    // Check if PO exists and its status
    $check_stmt = $db->prepare("SELECT status FROM purchase_orders WHERE id = ?");
    $check_stmt->execute([$po_id]);
    $po = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$po) {
        throw new Exception("Purchase Order not found.");
    }

    // Business Logic: Only allow deleting non-received POs unless admin
    if ($po['status'] === 'received' && !Auth::hasRole('admin')) {
        throw new Exception("Received Purchase Orders cannot be deleted for audit purposes. Please contact an administrator.");
    }

    $db->beginTransaction();

    // Delete PO items first (due to foreign key)
    $db->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id = ?")->execute([$po_id]);

    // Delete PO
    $db->prepare("DELETE FROM purchase_orders WHERE id = ?")->execute([$po_id]);

    $db->commit();
    $_SESSION['message'] = "Purchase Order deleted successfully.";
    $_SESSION['message_type'] = "success";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

header("Location: purchase_orders.php");
exit();
