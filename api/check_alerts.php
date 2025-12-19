<?php
// api/check_alerts.php
header('Content-Type: application/json');
require_once "../config/auth_check.php";
require_once "../config/database.php";


// Auth::checkAuthAndPreventCache() was called which includes session timeout checking

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
    // Session is already started by auth_check.php
    
    // Initialize session start time if not set
    if (!isset($_SESSION['session_start_time'])) {
        $_SESSION['session_start_time'] = time();
    }
    
    // Calculate time since session start
    $session_duration = time() - $_SESSION['session_start_time'];
    
    // Show alert immediately on first login, then every hour (3600 seconds)
    $hours_since_start = floor($session_duration / 3600);
    
    // Check if we've already shown an alert for this hour
    $last_alert_hour = isset($_SESSION['last_alert_hour']) ? $_SESSION['last_alert_hour'] : -1;
    
    // Only send alerts if we haven't shown one for this hour yet
    if ($last_alert_hour >= $hours_since_start) {
        // Return empty response if we've already shown alert for this hour
        echo json_encode([
            'alert_count' => 0,
            'items' => [],
            'status' => 'success'
        ]);
        exit;
    }
    
    // Update last alert hour if we're sending alerts
    if ($total_count > 0) {
        $_SESSION['last_alert_hour'] = $hours_since_start;
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
