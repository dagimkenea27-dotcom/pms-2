<?php
// api/get_delivery_locations.php
require_once "../config/auth_check.php";
require_once "../config/database.php";

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    // Fetch all delivery locations, grouping by parent if possible
    // For now, just fetch all with their names
    $query = "SELECT l.id, l.name, l.description, l.latitude, l.longitude, p.name as parent_name
              FROM delivery_locations l
              LEFT JOIN delivery_locations p ON l.parent_id = p.id
              ORDER BY COALESCE(p.name, ''), l.name ASC";
    
    $stmt = $db->query($query);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $locations]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
