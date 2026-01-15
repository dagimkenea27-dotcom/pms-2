<?php
header("Content-Type: application/json");

try {
    // Manually include config files with error checking
    $dbConfig = dirname(__DIR__) . "/config/database.php";
    $authConfig = dirname(__DIR__) . "/config/auth.php";

    if (!file_exists($dbConfig)) throw new Exception("Database config missing: $dbConfig");
    if (!file_exists($authConfig)) throw new Exception("Auth config missing: $authConfig");

    require_once $dbConfig;
    require_once $authConfig;

    Auth::startSession();
    if (!Auth::isLoggedIn()) {
        echo json_encode(["isOk" => false, "message" => "Unauthorized"]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed. Please check your .env or database.php config.");
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents("php://input"), true);

    // Support Method Tunneling for servers that block PUT/DELETE
    if ($method === 'POST' && isset($input['_method'])) {
        $method = strtoupper($input['_method']);
    }

    switch ($method) {
        case 'GET':
            $query = "SELECT *, id as __backendId FROM daily_sales_tracker ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as &$row) {
                $row['purchased'] = (bool)$row['purchased'];
                $row['needs_followup'] = (bool)$row['needs_followup'];
                $row['color'] = $row['color'] ?? '';
            }
            
            echo json_encode($results);
            break;

        case 'POST':
            if (!$input) throw new Exception("Invalid input data received.");
            
            $query = "INSERT INTO daily_sales_tracker 
                      (date, product_type, size, color, price, customer_info, customer_location, purchased, notes, needs_followup, followup_reason, created_at) 
                      VALUES (:date, :product_type, :size, :color, :price, :customer_info, :customer_location, :purchased, :notes, :needs_followup, :followup_reason, :created_at)";
            
            $stmt = $db->prepare($query);
            $params = [
                ':date' => $input['date'] ?? date('Y-m-d'),
                ':product_type' => $input['product_type'] ?? '',
                ':size' => $input['size'] ?? '',
                ':color' => $input['color'] ?? '',
                ':price' => $input['price'] ?? '',
                ':customer_info' => $input['customer_info'] ?? '',
                ':customer_location' => $input['customer_location'] ?? '',
                ':purchased' => isset($input['purchased']) ? (int)$input['purchased'] : 0,
                ':notes' => $input['notes'] ?? '',
                ':needs_followup' => isset($input['needs_followup']) ? (int)$input['needs_followup'] : 0,
                ':followup_reason' => $input['followup_reason'] ?? '',
                ':created_at' => isset($input['created_at']) ? date('Y-m-d H:i:s', strtotime($input['created_at'])) : date('Y-m-d H:i:s')
            ];
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Database Error: " . ($errorInfo[2] ?? 'Unknown error'));
            }
            
            echo json_encode(["isOk" => true]);
            break;

        case 'PUT':
            if (!$input || !isset($input['__backendId'])) throw new Exception("Missing ID for update");
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
                ':purchased' => isset($input['purchased']) ? (int)$input['purchased'] : 0,
                ':needs_followup' => isset($input['needs_followup']) ? (int)$input['needs_followup'] : 0,
                ':date' => $input['date'] ?? date('Y-m-d'),
                ':product_type' => $input['product_type'] ?? '',
                ':size' => $input['size'] ?? '',
                ':color' => $input['color'] ?? '',
                ':price' => $input['price'] ?? '',
                ':customer_info' => $input['customer_info'] ?? '',
                ':customer_location' => $input['customer_location'] ?? '',
                ':notes' => $input['notes'] ?? '',
                ':followup_reason' => $input['followup_reason'] ?? '',
                ':id' => $id
            ]);
            
            echo json_encode(["isOk" => $result]);
            break;

        case 'DELETE':
            if (!$input || !isset($input['__backendId'])) throw new Exception("Missing ID for delete");
            $id = $input['__backendId'];
            
            $query = "DELETE FROM daily_sales_tracker WHERE id = :id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([':id' => $id]);
            
            echo json_encode(["isOk" => $result]);
            break;

        default:
            throw new Exception("Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    }
} catch (Throwable $e) {
    // Return 200 but with isOk: false so client can read the message easily
    http_response_code(200); 
    echo json_encode([
        "isOk" => false, 
        "message" => "Error: " . $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
}
?>
