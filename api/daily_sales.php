<?php
header("Content-Type: application/json");
require_once "../config/database.php";
require_once "../config/auth.php";

Auth::startSession();
if (!Auth::isLoggedIn()) {
    echo json_encode(["isOk" => false, "message" => "Unauthorized"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $query = "SELECT *, id as __backendId FROM daily_sales_tracker ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert purchased and needs_followup to boolean for JS
        foreach ($results as &$row) {
            $row['purchased'] = (bool)$row['purchased'];
            $row['needs_followup'] = (bool)$row['needs_followup'];
            $row['color'] = $row['color'] ?? '';
        }
        
        echo json_encode($results);
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);
        
        $query = "INSERT INTO daily_sales_tracker 
                  (date, product_type, size, color, price, customer_info, customer_location, purchased, notes, needs_followup, followup_reason, created_at) 
                  VALUES (:date, :product_type, :size, :color, :price, :customer_info, :customer_location, :purchased, :notes, :needs_followup, :followup_reason, :created_at)";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':date' => $input['date'],
            ':product_type' => $input['product_type'],
            ':size' => $input['size'],
            ':color' => $input['color'] ?? '',
            ':price' => $input['price'],
            ':customer_info' => $input['customer_info'],
            ':customer_location' => $input['customer_location'],
            ':purchased' => (int)$input['purchased'],
            ':notes' => $input['notes'],
            ':needs_followup' => (int)$input['needs_followup'],
            ':followup_reason' => $input['followup_reason'],
            ':created_at' => $input['created_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(["isOk" => $result]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input['__backendId'];
        
        $query = "UPDATE daily_sales_tracker 
                  SET purchased = :purchased, 
                      needs_followup = :needs_followup,
                      date = :date,
                      product_type = :product_type,
                      size = :size,
                      color = :color,
                      price = :price,
                      customer_info = :customer_info,
                      customer_location = :customer_location,
                      notes = :notes,
                      followup_reason = :followup_reason
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':purchased' => (int)$input['purchased'],
            ':needs_followup' => (int)$input['needs_followup'],
            ':date' => $input['date'],
            ':product_type' => $input['product_type'],
            ':size' => $input['size'],
            ':color' => $input['color'] ?? '',
            ':price' => $input['price'],
            ':customer_info' => $input['customer_info'],
            ':customer_location' => $input['customer_location'],
            ':notes' => $input['notes'],
            ':followup_reason' => $input['followup_reason'],
            ':id' => $id
        ]);
        
        echo json_encode(["isOk" => $result]);
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input['__backendId'];
        
        $query = "DELETE FROM daily_sales_tracker WHERE id = :id";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([':id' => $id]);
        
        echo json_encode(["isOk" => $result]);
        break;

    default:
        echo json_encode(["isOk" => false, "message" => "Method not allowed"]);
        break;
}
?>
