<?php
// api/get_supplier_products.php
require_once "../config/auth.php";
require_once "../config/database.php";

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in again.']);
    exit;
}

if (!isset($_GET['supplier_id']) || empty($_GET['supplier_id'])) {
    echo json_encode(['success' => false, 'message' => 'Supplier ID required']);
    exit;
}

$supplierId = intval($_GET['supplier_id']);
$database = new Database();
$db = $database->getConnection();

try {
    // Fetch products for this supplier, along with their variants
    $query = "SELECT p.id as p_id, p.name as p_name, p.sku as p_sku, 
                     pv.id as v_id, pv.size, pv.color, pv.sku as v_sku, pv.cost_price as v_cost, p.cost_price as p_cost
              FROM products p
              LEFT JOIN product_variants pv ON p.id = pv.product_id
              WHERE p.supplier_id = ?
              ORDER BY p.name ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$supplierId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedProducts = [];
    foreach ($products as $row) {
        $label = $row['p_name'];
        $val = "p_" . $row['p_id'];
        $cost = $row['p_cost'];
        
        if ($row['v_id']) {
            $label .= " - " . $row['size'] . "/" . $row['color'] . " (" . $row['v_sku'] . ")";
            $val = "v_" . $row['v_id'] . "_p_" . $row['p_id'];
            $cost = ($row['v_cost'] && $row['v_cost'] > 0) ? $row['v_cost'] : $row['p_cost'];
        } else {
            $label .= " (" . $row['p_sku'] . ")";
        }
        
        $formattedProducts[] = [
            'id' => $val,
            'text' => $label,
            'cost' => $cost
        ];
    }

    echo json_encode(['success' => true, 'data' => $formattedProducts]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
