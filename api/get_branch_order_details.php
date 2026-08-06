<?php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/BranchOrder.php";

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID missing']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$branchOrder = new BranchOrder($db);

$order = $branchOrder->getById($_GET['id']);

if ($order) {
    echo json_encode(['success' => true, 'data' => $order]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
}
?>
