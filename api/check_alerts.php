<?php
// api/check_alerts.php
header('Content-Type: application/json');
require_once "../config/auth_check.php";
require_once "../config/database.php";

// Simple auth check - ensure user is logged in
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Query for products below reorder point
    // Logic: Quantity <= Reorder Point AND Reorder is Enabled
    $query = "SELECT p.name, p.quantity, rs.reorder_point 
              FROM products p
              JOIN reorder_settings rs ON p.id = rs.product_id
              WHERE p.quantity <= rs.reorder_point 
              AND p.reorder_enabled = 1
              LIMIT 5";

    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count (separate query to account for LIMIT in items)
    $count_query = "SELECT COUNT(*) as count 
                    FROM products p
                    JOIN reorder_settings rs ON p.id = rs.product_id
                    WHERE p.quantity <= rs.reorder_point 
                    AND p.reorder_enabled = 1";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Session-based tracking to avoid duplicate alerts
    session_start();
    
    // Check if we've already sent alerts in this session
    $last_alert_time = isset($_SESSION['last_stock_alert_time']) ? $_SESSION['last_stock_alert_time'] : 0;
    $current_time = time();
    
    // Only send alerts if it's been more than 1 hour since last alert
    if ($current_time - $last_alert_time < 3600) {
        // Return empty response if it's too soon
        echo json_encode([
            'alert_count' => 0,
            'items' => [],
            'status' => 'success'
        ]);
        exit;
    }
    
    // Update last alert time if we're sending alerts
    if ($total_count > 0) {
        $_SESSION['last_stock_alert_time'] = $current_time;
    }

    echo json_encode([
        'alert_count' => (int)$total_count,
        'items' => $items,
        'status' => 'success'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
