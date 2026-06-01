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
    require_once dirname(__DIR__) . "/config/security.php";

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

    // CSRF Validation for state-changing methods (pass $input — php://input already consumed above)
    if ($method !== 'GET') {
        if (!Security::validateRequest(is_array($input) ? $input : null)) {
            echo json_encode(["isOk" => false, "message" => "CSRF token validation failed."]);
            exit;
        }
    }

    switch ($method) {
        case 'GET':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
            $offset = ($page - 1) * $limit;

            $status = $_GET['status'] ?? 'all';
            $search = $_GET['search'] ?? '';
            $from_date = $_GET['from_date'] ?? '';
            $to_date = $_GET['to_date'] ?? '';
            $exact_order_id = $_GET['exact_order_id'] ?? '';
            $fetch_account = $_GET['fetch_account'] ?? '';

            if (!empty($fetch_account)) {
                $query = "SELECT notes FROM vendor_payment_requests 
                          WHERE shop_name = :shop_name AND notes IS NOT NULL AND notes != '' 
                          ORDER BY created_at DESC LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->execute([':shop_name' => $fetch_account]);
                $accountNotes = $stmt->fetchColumn();

                echo json_encode([
                    "isOk" => true,
                    "notes" => $accountNotes ?: ""
                ]);
                exit;
            }

            $history_id = $_GET['history_id'] ?? '';
            if (!empty($history_id)) {
                $query = "SELECT al.*, u.username as actor_name 
                          FROM audit_logs al
                          LEFT JOIN users u ON al.user_id = u.id
                          WHERE al.table_name = 'vendor_payment_requests' 
                          AND al.record_id = :id
                          ORDER BY al.created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $history_id]);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Decode details
                foreach ($history as &$item) {
                    if ($item['details']) {
                        $item['details_decoded'] = json_decode($item['details'], true);
                    }
                }

                echo json_encode([
                    "isOk" => true,
                    "data" => $history
                ]);
                exit;
            }

            $whereConditions = [];
            $params = [];

            if ($status !== 'all') {
                $whereConditions[] = "vpr.status = :status";
                $params[':status'] = $status;
            }

            if (!empty($search)) {
                $whereConditions[] = "(vpr.shop_name LIKE :search OR vpr.order_id LIKE :search OR vpr.notes LIKE :search)";
                $params[':search'] = "%$search%";
            }

            if (!empty($from_date)) {
                $whereConditions[] = "vpr.created_at >= :from_date";
                $params[':from_date'] = $from_date . " 00:00:00";
            }

            if (!empty($to_date)) {
                $whereConditions[] = "vpr.created_at <= :to_date";
                $params[':to_date'] = $to_date . " 23:59:59";
            }
            
            if (!empty($exact_order_id)) {
                $whereConditions[] = "vpr.order_id = :exact_oid";
                $params[':exact_oid'] = $exact_order_id;
            }

            $whereSql = count($whereConditions) > 0 ? "WHERE " . implode(" AND ", $whereConditions) : "";

            // 1. Get stats for the filtered set (across all pages)
            $statsQuery = "SELECT 
                            COUNT(*) as total_count,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                            SUM(CASE WHEN status = 'pending' THEN order_amount ELSE 0 END) as pending_gross,
                            SUM(CASE WHEN status = 'pending' THEN net_amount ELSE 0 END) as pending_net,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                            SUM(CASE WHEN status = 'approved' THEN order_amount ELSE 0 END) as approved_gross,
                            SUM(CASE WHEN status = 'approved' THEN net_amount ELSE 0 END) as approved_net,
                            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                            SUM(CASE WHEN status = 'paid' THEN order_amount ELSE 0 END) as paid_gross,
                            SUM(CASE WHEN status = 'paid' THEN net_amount ELSE 0 END) as paid_net,
                            SUM(order_amount) as total_gross,
                            SUM(commission_amount) as total_commission,
                            SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as collected_commission
                          FROM vendor_payment_requests vpr
                          $whereSql";
            
            $statsStmt = $db->prepare($statsQuery);
            foreach ($params as $key => $val) {
                $statsStmt->bindValue($key, $val);
            }
            $statsStmt->execute();
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            // 2. Get paginated data
            $query = "SELECT vpr.*, u.username as requested_by_name 
                      FROM vendor_payment_requests vpr 
                      LEFT JOIN users u ON vpr.requested_by = u.id 
                      $whereSql
                      ORDER BY vpr.created_at DESC 
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalRecords = (int)($stats['total_count'] ?? 0);
            $totalPages = ceil($totalRecords / $limit);

            echo json_encode([
                "isOk" => true, 
                "data" => $results,
                "pagination" => [
                    "total_records" => $totalRecords,
                    "total_pages" => $totalPages,
                    "current_page" => $page,
                    "limit" => $limit
                ],
                "stats" => [
                    "pending" => [
                        "count" => (int)($stats['pending_count'] ?? 0),
                        "gross" => (float)($stats['pending_gross'] ?? 0),
                        "net" => (float)($stats['pending_net'] ?? 0)
                    ],
                    "approved" => [
                        "count" => (int)($stats['approved_count'] ?? 0),
                        "gross" => (float)($stats['approved_gross'] ?? 0),
                        "net" => (float)($stats['approved_net'] ?? 0)
                    ],
                    "paid" => [
                        "count" => (int)($stats['paid_count'] ?? 0),
                        "gross" => (float)($stats['paid_gross'] ?? 0),
                        "net" => (float)($stats['paid_net'] ?? 0)
                    ],
                    "total" => [
                        "count" => $totalRecords,
                        "gross" => (float)($stats['total_gross'] ?? 0),
                        "commission" => (float)($stats['total_commission'] ?? 0),
                        "collected_commission" => (float)($stats['collected_commission'] ?? 0)
                    ]
                ]
            ]);
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
            if (!$input || (!isset($input['id']) && empty($input['ids'])))
                throw new Exception("Missing request ID(s).");

            $action = $input['action'] ?? '';
            $validActions = ['approve', 'reject', 'mark_paid', 'update'];
            if (!in_array($action, $validActions)) {
                throw new Exception("Invalid action. Must be: " . implode(', ', $validActions));
            }

            if ($action === 'update') {
                if (!isset($input['id'])) throw new Exception("Update action requires a single ID.");
                $id = intval($input['id']);

                // Get old record for audit and validation
                $oldStmt = $db->prepare("SELECT * FROM vendor_payment_requests WHERE id = :id");
                $oldStmt->execute([':id' => $id]);
                $oldRecord = $oldStmt->fetch(PDO::FETCH_ASSOC);

                if (!$oldRecord)
                    throw new Exception("Payment request not found.");

                if ($oldRecord['status'] === 'paid') {
                    throw new Exception("Forbidden: Cannot edit a request that has already been marked as 'paid'.");
                }

                $shop_name = trim($input['shop_name'] ?? '');
                $order_id = trim($input['order_id'] ?? '');
                $order_amount = floatval($input['order_amount'] ?? 0);
                $pays_comm = !empty($input['pays_commission']) ? 1 : 0;
                $notes = trim($input['notes'] ?? '');

                if (empty($shop_name)) throw new Exception("Shop name is required.");
                if (empty($order_id)) throw new Exception("Order ID is required.");
                if ($order_amount <= 0) throw new Exception("Order amount must be greater than 0.");

                // Check for order ID redundancy (excluding current record)
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM vendor_payment_requests WHERE order_id = :oid AND id != :id");
                $checkStmt->execute([':oid' => $order_id, ':id' => $id]);
                if ($checkStmt->fetchColumn() > 0) {
                    throw new Exception("Redundancy Error: Order ID '$order_id' already exists in the system.");
                }

                // Calculate commission logic
                $comm_rate = 0;
                $comm_amt = 0;
                $net_amt = $order_amount;

                if ($pays_comm) {
                    if ($order_amount <= 2500) {
                        $comm_rate = 8.00;
                    } elseif ($order_amount <= 6000) {
                        $comm_rate = 6.00;
                    } else {
                        $comm_rate = 5.00;
                    }
                    $comm_amt = round($order_amount * ($comm_rate / 100), 2);
                    $net_amt = $order_amount - $comm_amt;
                }

                $query = "UPDATE vendor_payment_requests SET 
                            shop_name = :shop_name,
                            order_id = :order_id,
                            order_amount = :order_amount,
                            notes = :notes,
                            pays_commission = :pays_comm,
                            commission_rate = :comm_rate,
                            commission_amount = :comm_amt,
                            net_amount = :net_amt
                          WHERE id = :id";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    ':shop_name' => $shop_name,
                    ':order_id' => $order_id,
                    ':order_amount' => $order_amount,
                    ':notes' => $notes,
                    ':pays_comm' => $pays_comm,
                    ':comm_rate' => $comm_rate,
                    ':comm_amt' => $comm_amt,
                    ':net_amt' => $net_amt,
                    ':id' => $id
                ]);

                if (!$result) throw new Exception("Failed to update payment request.");

                // Audit log for update
                $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                               VALUES (:user_id, :action, :table_name, :record_id, :details, :ip_address)";
                $auditStmt = $db->prepare($auditQuery);
                $auditStmt->execute([
                    ':user_id' => $currentUser['id'],
                    ':action' => 'UPDATE_DATA',
                    ':table_name' => 'vendor_payment_requests',
                    ':record_id' => $id,
                    ':details' => json_encode([
                        'old' => $oldRecord,
                        'new' => [
                            'shop_name' => $shop_name,
                            'order_id' => $order_id,
                            'order_amount' => $order_amount,
                            'pays_commission' => $pays_comm,
                            'notes' => $notes
                        ]
                    ]),
                    ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                ]);

                echo json_encode(["isOk" => true, "message" => "Payment request updated successfully."]);
            } else {
                // Bulk Status Update
                $ids = !empty($input['ids']) && is_array($input['ids']) ? $input['ids'] : [$input['id']];
                $statusMap = [
                    'approve' => 'approved',
                    'reject' => 'rejected',
                    'mark_paid' => 'paid'
                ];
                $newStatus = $statusMap[$action];

                $db->beginTransaction();
                try {
                    $updatedCount = 0;
                    $skippedCount = 0;
                    foreach ($ids as $id) {
                        $id = intval($id);
                        $oldStmt = $db->prepare("SELECT * FROM vendor_payment_requests WHERE id = :id");
                        $oldStmt->execute([':id' => $id]);
                        $oldRecord = $oldStmt->fetch(PDO::FETCH_ASSOC);

                        if (!$oldRecord) {
                            $skippedCount++;
                            continue;
                        }
                        
                        if ($oldRecord['status'] === $newStatus) {
                            $skippedCount++;
                            continue;
                        }

                        // Enforce Transition Rules
                        $isValid = true;
                        if ($action === 'approve' && $oldRecord['status'] !== 'pending') $isValid = false;
                        if ($action === 'reject' && $oldRecord['status'] !== 'pending') $isValid = false;
                        if ($action === 'mark_paid' && $oldRecord['status'] !== 'approved') $isValid = false;

                        if (!$isValid) {
                            $skippedCount++;
                            continue;
                        }

                        $query = "UPDATE vendor_payment_requests SET status = :status WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $result = $stmt->execute([
                            ':status' => $newStatus,
                            ':id' => $id
                        ]);

                        if ($result) {
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
                                    'order_amount' => $oldRecord['order_amount'],
                                    'is_bulk' => count($ids) > 1
                                ]),
                                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                            ]);
                            $updatedCount++;
                        }
                    }
                    $db->commit();
                    
                    $msg = "Successfully updated $updatedCount request(s).";
                    if ($skippedCount > 0) {
                        $msg .= " $skippedCount were skipped due to status rules.";
                    }
                    
                    echo json_encode([
                        "isOk" => true, 
                        "message" => $msg, 
                        "updatedCount" => $updatedCount, 
                        "skippedCount" => $skippedCount
                    ]);
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
            }
            break;

        case 'DELETE':
            if (!$input || (!isset($input['id']) && empty($input['ids'])))
                throw new Exception("Missing request ID(s) for delete.");

            $ids = !empty($input['ids']) && is_array($input['ids']) ? $input['ids'] : [$input['id']];

            $db->beginTransaction();
            try {
                $deletedCount = 0;
                $skippedCount = 0;
                foreach ($ids as $id) {
                    $id = intval($id);
                    // Get record for audit before deleting
                    $oldStmt = $db->prepare("SELECT * FROM vendor_payment_requests WHERE id = :id");
                    $oldStmt->execute([':id' => $id]);
                    $oldRecord = $oldStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$oldRecord) {
                        $skippedCount++;
                        continue;
                    }

                    // Enforce Deletion Rule: Cannot delete 'paid' requests
                    if ($oldRecord['status'] === 'paid') {
                        $skippedCount++;
                        continue;
                    }

                    $query = "DELETE FROM vendor_payment_requests WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $result = $stmt->execute([':id' => $id]);

                    if ($result) {
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
                        $deletedCount++;
                    }
                }
                $db->commit();
                
                $msg = "Successfully deleted $deletedCount request(s).";
                if ($skippedCount > 0) {
                    $msg .= " $skippedCount were skipped (either not found or already paid).";
                }
                echo json_encode([
                    "isOk" => true, 
                    "message" => $msg, 
                    "updatedCount" => $deletedCount, 
                    "skippedCount" => $skippedCount
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
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