<?php
header("Content-Type: application/json");

try {
    $dbConfig = dirname(__DIR__) . "/config/database.php";
    $authConfig = dirname(__DIR__) . "/config/auth.php";

    if (!file_exists($dbConfig))
        throw new Exception("Database config missing");
    if (!file_exists($authConfig))
        throw new Exception("Auth config missing");

    require_once $dbConfig;
    require_once $authConfig;

    Auth::startSession();
    if (!Auth::isLoggedIn()) {
        echo json_encode(["isOk" => false, "message" => "Unauthorized"]);
        exit;
    }

    // Admin-only access
    if (!Auth::hasRole('admin')) {
        echo json_encode(["isOk" => false, "message" => "Access denied. Admin only."]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed.");
    }

    $currentUser = Auth::getCurrentUser();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents("php://input"), true);

    // Support method tunneling
    if ($method === 'POST' && isset($input['_method'])) {
        $method = strtoupper($input['_method']);
    }

    switch ($method) {
        case 'GET':
            $query = "SELECT vpr.*, u.username as requested_by_name 
                      FROM vendor_payment_requests vpr 
                      LEFT JOIN users u ON vpr.requested_by = u.id 
                      ORDER BY vpr.created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["isOk" => true, "data" => $results]);
            break;

        case 'POST':
            if (!$input)
                throw new Exception("Invalid input data.");

            $shop_name = trim($input['shop_name'] ?? '');
            $notes = trim($input['notes'] ?? '');

            if (empty($shop_name))
                throw new Exception("Shop name is required.");

            // Support both batch ('orders' array) and single legacy submission
            $orders = $input['orders'] ?? [];
            if (empty($orders)) {
                $order_id = trim($input['order_id'] ?? '');
                $order_amount = floatval($input['order_amount'] ?? 0);
                if (empty($order_id) && empty($input['orders']))
                    throw new Exception("Order ID is required.");
                if ($order_amount > 0 || !empty($order_id)) {
                    $orders[] = ['order_id' => $order_id, 'order_amount' => $order_amount];
                }
            }

            if (empty($orders))
                throw new Exception("No orders provided.");

            $db->beginTransaction();
            try {
                $insertedIds = [];
                foreach ($orders as $order) {
                    $o_id = trim($order['order_id'] ?? '');
                    $o_amt = floatval($order['order_amount'] ?? 0);
                    $pays_comm = !empty($order['pays_commission']) ? 1 : 0;

                    if (empty($o_id))
                        throw new Exception("Order ID is required for all items.");
                    if ($o_amt <= 0)
                        throw new Exception("Order amount must be greater than 0.");

                    // Redundancy check
                    $checkStmt = $db->prepare("SELECT COUNT(*) FROM vendor_payment_requests WHERE order_id = :oid");
                    $checkStmt->execute([':oid' => $o_id]);
                    if ($checkStmt->fetchColumn() > 0) {
                        throw new Exception("Redundancy Error: Order ID '$o_id' already exists in the system.");
                    }

                    // Calculate commission logic
                    $comm_rate = 0;
                    $comm_amt = 0;
                    $net_amt = $o_amt;

                    if ($pays_comm) {
                        if ($o_amt <= 2500) {
                            $comm_rate = 8.00;
                        } elseif ($o_amt <= 6000) {
                            $comm_rate = 6.00;
                        } else {
                            $comm_rate = 5.00;
                        }
                        $comm_amt = round($o_amt * ($comm_rate / 100), 2);
                        $net_amt = $o_amt - $comm_amt;
                    }

                    $query = "INSERT INTO vendor_payment_requests 
                              (shop_name, order_id, order_amount, status, requested_by, notes, pays_commission, commission_rate, commission_amount, net_amount) 
                              VALUES (:shop_name, :order_id, :order_amount, 'pending', :requested_by, :notes, :pays_comm, :comm_rate, :comm_amt, :net_amt)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':shop_name' => $shop_name,
                        ':order_id' => $o_id,
                        ':order_amount' => $o_amt,
                        ':requested_by' => $currentUser['id'],
                        ':notes' => $notes,
                        ':pays_comm' => $pays_comm,
                        ':comm_rate' => $comm_rate,
                        ':comm_amt' => $comm_amt,
                        ':net_amt' => $net_amt
                    ]);
                    $insertedIds[] = $db->lastInsertId();
                }

                // Audit log
                $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                               VALUES (:user_id, :action, :table_name, :record_id, :details, :ip_address)";
                $auditStmt = $db->prepare($auditQuery);
                $auditStmt->execute([
                    ':user_id' => $currentUser['id'],
                    ':action' => 'CREATE_BATCH',
                    ':table_name' => 'vendor_payment_requests',
                    ':record_id' => $insertedIds[0] ?? 0,
                    ':details' => json_encode([
                        'shop_name' => $shop_name,
                        'batch_count' => count($orders),
                        'orders' => $orders,
                        'notes' => $notes
                    ]),
                    ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                ]);

                $db->commit();
                echo json_encode(["isOk" => true, "message" => count($orders) . " payment request(s) created.", "ids" => $insertedIds]);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'PUT':
            if (!$input || !isset($input['id']))
                throw new Exception("Missing request ID.");

            $id = intval($input['id']);
            $action = $input['action'] ?? '';

            $validActions = ['approve', 'reject', 'mark_paid'];
            if (!in_array($action, $validActions)) {
                throw new Exception("Invalid action. Must be: " . implode(', ', $validActions));
            }

            // Map action to status
            $statusMap = [
                'approve' => 'approved',
                'reject' => 'rejected',
                'mark_paid' => 'paid'
            ];
            $newStatus = $statusMap[$action];

            // Get old record for audit
            $oldStmt = $db->prepare("SELECT * FROM vendor_payment_requests WHERE id = :id");
            $oldStmt->execute([':id' => $id]);
            $oldRecord = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldRecord)
                throw new Exception("Payment request not found.");

            $query = "UPDATE vendor_payment_requests SET status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':status' => $newStatus,
                ':id' => $id
            ]);

            if (!$result)
                throw new Exception("Failed to update payment request.");

            // Audit log
            $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                           VALUES (:user_id, :action, :table_name, :record_id, :details, :ip_address)";
            $auditStmt = $db->prepare($auditQuery);
            $auditStmt->execute([
                ':user_id' => $currentUser['id'],
                ':action' => 'UPDATE_STATUS',
                ':table_name' => 'vendor_payment_requests',
                ':record_id' => $id,
                ':details' => json_encode([
                    'old_status' => $oldRecord['status'],
                    'new_status' => $newStatus,
                    'action' => $action,
                    'shop_name' => $oldRecord['shop_name'],
                    'order_id' => $oldRecord['order_id'],
                    'order_amount' => $oldRecord['order_amount']
                ]),
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            echo json_encode(["isOk" => true, "message" => "Status updated to '$newStatus'."]);
            break;

        case 'DELETE':
            if (!$input || !isset($input['id']))
                throw new Exception("Missing request ID for delete.");

            $id = intval($input['id']);

            // Get record for audit before deleting
            $oldStmt = $db->prepare("SELECT * FROM vendor_payment_requests WHERE id = :id");
            $oldStmt->execute([':id' => $id]);
            $oldRecord = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldRecord)
                throw new Exception("Payment request not found.");

            $query = "DELETE FROM vendor_payment_requests WHERE id = :id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([':id' => $id]);

            if (!$result)
                throw new Exception("Failed to delete payment request.");

            // Audit log
            $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                           VALUES (:user_id, :action, :table_name, :record_id, :details, :ip_address)";
            $auditStmt = $db->prepare($auditQuery);
            $auditStmt->execute([
                ':user_id' => $currentUser['id'],
                ':action' => 'DELETE',
                ':table_name' => 'vendor_payment_requests',
                ':record_id' => $id,
                ':details' => json_encode($oldRecord),
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            echo json_encode(["isOk" => true, "message" => "Payment request deleted."]);
            break;

        default:
            throw new Exception("Method not allowed: " . $method);
    }
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        "isOk" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>