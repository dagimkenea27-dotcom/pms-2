<?php
/**
 * routes/update_logistics.php
 * API endpoint for status updates from Driver Center
 */
require_once "../config/auth.php";
require_once "../config/database.php";

Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['success' => false];

try {
    if ($action === 'update_route') {
        $routeId = intval($_POST['route_id']);
        $status = $_POST['status'];
        
        $query = "UPDATE routes SET route_status = ? ";
        $params = [$status];
        
        if ($status === 'in_progress') {
            $query .= ", started_at = NOW() ";
        } elseif ($status === 'completed') {
            $query .= ", completed_at = NOW() ";
        }
        
        $query .= " WHERE id = ? AND (assigned_driver_id IN (SELECT id FROM route_drivers WHERE user_id = ?) OR ? = 'admin')";
        $params[] = $routeId;
        $params[] = $currentUser['id'];
        $params[] = $currentUser['role'];
        
        $stmt = $db->prepare($query);
        $response['success'] = $stmt->execute($params);
    } 
    elseif ($action === 'confirm_delivery') {
        $deliveryId = intval($_POST['delivery_id']);
        $signature = $_POST['signature']; // Base64
        $notes = $_POST['notes'];
        
        // Handle Signature Storage
        $signaturePath = null;
        if (!empty($signature)) {
            $folder = "../uploads/signatures/";
            if (!file_exists($folder)) mkdir($folder, 0777, true);
            
            $filename = "sig_" . $deliveryId . "_" . time() . ".png";
            $data = explode(',', $signature);
            if (isset($data[1])) {
                file_put_contents($folder . $filename, base64_decode($data[1]));
                $signaturePath = "uploads/signatures/" . $filename;
            }
        }
        
        $query = "UPDATE route_deliveries 
                  SET status = 'delivered', 
                      actual_arrival = NOW(), 
                      signature_image_path = ?, 
                      special_instructions = ? 
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $response['success'] = $stmt->execute([$signaturePath, $notes, $deliveryId]);
        
        // Update driver stats
        $db->query("UPDATE route_drivers d 
                    JOIN routes r ON r.assigned_driver_id = d.id 
                    JOIN route_deliveries rd ON rd.route_id = r.id 
                    SET d.successful_deliveries = d.successful_deliveries + 1 
                    WHERE rd.id = $deliveryId");
    }
    elseif ($action === 'update_stop') {
        $stopId = intval($_POST['stop_id']);
        $status = $_POST['status'];
        
        $query = "UPDATE route_deliveries SET status = ? ";
        $params = [$status];
        
        if ($status === 'delivered') {
            $query .= ", actual_arrival = NOW() ";
        }
        
        $query .= " WHERE id = ?";
        $params[] = $stopId;
        
        $stmt = $db->prepare($query);
        $response['success'] = $stmt->execute($params);
        
        // Also update performance metrics if route is complete
        // (Simplified logic for now)
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
