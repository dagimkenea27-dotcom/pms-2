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

    // Admin/Manager access
    if (!Auth::hasRole('admin') && !Auth::hasRole('manager')) {
        echo json_encode(["isOk" => false, "message" => "Access denied. Admin or Manager only."]);
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

    // CSRF Validation for state-changing methods
    if ($method !== 'GET') {
        if (!Security::validateRequest()) {
            echo json_encode(["isOk" => false, "message" => "CSRF token validation failed."]);
            exit;
        }
    }

    function ensureArrivalColumn($db)
    {
        $check = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'customer_prepayments' AND column_name = 'is_arrived'");
        $check->execute();
        if ((int) $check->fetchColumn() === 0) {
            $db->exec("ALTER TABLE customer_prepayments ADD COLUMN is_arrived TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    function getArrivalPrepaymentTarget($record)
    {
        $amountDue = (float) ($record['amount_due'] ?? 0);
        $isArrived = (int) ($record['is_arrived'] ?? 0) === 1;
        $notArrivedCost = $isArrived ? 0 : $amountDue;
        return $notArrivedCost * 0.3;
    }

    function getArrivalPrepaymentStatus($record, $paid = null)
    {
        $amountPaid = $paid === null ? (float) ($record['amount_paid'] ?? 0) : (float) $paid;
        $target = getArrivalPrepaymentTarget($record);
        if ($target <= 0) {
            return 'unpaid';
        }
        return $amountPaid >= $target ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid');
    }

    function prepaymentItemsTableExists($db)
    {
        $check = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'prepayment_items'");
        $check->execute();
        return (int) $check->fetchColumn() > 0;
    }

    function getArrivalPrepaymentTargetFromDb($db, $record)
    {
        $amountDue = (float) ($record['amount_due'] ?? 0);
        if (prepaymentItemsTableExists($db)) {
            $stmt = $db->prepare("SELECT COUNT(*) as item_count, COALESCE(SUM(CASE WHEN delivered_qty IS NULL OR delivered_qty <= 0 THEN qty * unit_price ELSE 0 END), 0) as not_arrived_cost FROM prepayment_items WHERE prepayment_id = :id");
            $stmt->execute([':id' => (int) ($record['id'] ?? 0)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ((int) ($row['item_count'] ?? 0) > 0) {
                $notArrivedCost = (float) ($row['not_arrived_cost'] ?? 0);
                return $notArrivedCost * 0.3;
            }
        }

        return getArrivalPrepaymentTarget($record);
    }

    function getArrivalPrepaymentStatusFromDb($db, $record, $paid = null)
    {
        $amountPaid = $paid === null ? (float) ($record['amount_paid'] ?? 0) : (float) $paid;
        $target = getArrivalPrepaymentTargetFromDb($db, $record);
        if ($target <= 0) {
            return 'unpaid';
        }
        return $amountPaid >= $target ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid');
    }

    function ensurePrepaymentItemsTable($db)
    {
        $createItems = "CREATE TABLE IF NOT EXISTS prepayment_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prepayment_id INT NOT NULL,
            product_sku VARCHAR(128) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            unit_price DECIMAL(12,2) DEFAULT 0,
            qty INT DEFAULT 0,
            prepayment_amount DECIMAL(12,2) DEFAULT 0,
            amount_paid DECIMAL(12,2) DEFAULT 0,
            delivered_qty INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($createItems);
    }

    function ensurePrepaymentReceiptsTable($db)
    {
        $createReceipts = "CREATE TABLE IF NOT EXISTS prepayment_receipts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prepayment_id INT NOT NULL,
            image_data LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($createReceipts);
    }

    function fetchReceiptsForPrepaymentIds($db, $ids)
    {
        $receipts = [];
        if (empty($ids)) {
            return $receipts;
        }

        ensurePrepaymentReceiptsTable($db);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT id, prepayment_id, image_data, created_at FROM prepayment_receipts WHERE prepayment_id IN ($placeholders) ORDER BY created_at ASC";
        $stmt = $db->prepare($query);
        foreach ($ids as $k => $v) {
            $stmt->bindValue($k + 1, $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $pid = (int) $row['prepayment_id'];
            if (!isset($receipts[$pid])) {
                $receipts[$pid] = [];
            }
            $receipts[$pid][] = [
                'id' => (int) $row['id'],
                'image_data' => $row['image_data'],
                'created_at' => $row['created_at']
            ];
        }
        return $receipts;
    }

    function normalizeOrderIds($items)
    {
        $orderIds = [];
        if (!is_array($items)) {
            return $orderIds;
        }

        foreach ($items as $item) {
            $name = trim($item['name'] ?? ($item['product_name'] ?? ''));
            if ($name !== '') {
                $orderIds[] = $name;
            }
        }

        return array_values(array_unique($orderIds));
    }

    function hasDuplicateOrderIdsInPayload($items)
    {
        $seen = [];
        if (!is_array($items)) {
            return false;
        }

        foreach ($items as $item) {
            $name = trim($item['name'] ?? ($item['product_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = strtolower($name);
            if (isset($seen[$key])) {
                return true;
            }
            $seen[$key] = true;
        }

        return false;
    }

    function findDuplicateOrderIds($db, $orderIds, $excludePrepaymentId = 0)
    {
        $orderIds = array_values(array_filter(array_unique($orderIds), function ($id) {
            return trim($id) !== '';
        }));
        if (empty($orderIds)) {
            return [];
        }

        ensurePrepaymentItemsTable($db);
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $query = "SELECT pi.product_name as order_id, pi.prepayment_id, cp.customer_name, cp.details
                  FROM prepayment_items pi
                  LEFT JOIN customer_prepayments cp ON cp.id = pi.prepayment_id
                  WHERE pi.product_name IN ($placeholders)";
        if ($excludePrepaymentId > 0) {
            $query .= " AND pi.prepayment_id <> ?";
        }

        $stmt = $db->prepare($query);
        $idx = 1;
        foreach ($orderIds as $orderId) {
            $stmt->bindValue($idx++, $orderId);
        }
        if ($excludePrepaymentId > 0) {
            $stmt->bindValue($idx, $excludePrepaymentId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    ensureArrivalColumn($db);

    switch ($method) {
        case 'GET':
            $exportAll = !empty($_GET['export_all']);
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 30;
            if ($exportAll) {
                $page = 1;
                $limit = 10000;
                $offset = 0;
            } else {
                $offset = ($page - 1) * $limit;
            }

            $status = $_GET['status'] ?? 'all'; // 'all', 'paid', 'partial', 'unpaid'
            $search = $_GET['search'] ?? '';
            $from_date = $_GET['from_date'] ?? '';
            $to_date = $_GET['to_date'] ?? '';
            $exact_customer_name = $_GET['exact_customer_name'] ?? '';
            $fetch_details = $_GET['fetch_details'] ?? '';
            $fetch_customer_id = trim($_GET['fetch_customer_id'] ?? '');
            $exact_order_id = trim($_GET['exact_order_id'] ?? '');
            $customer_history = trim($_GET['customer_history'] ?? '');
            $check_order_ids = trim($_GET['check_order_ids'] ?? '');

            // 1. Auto-fetch details of past deals for a repeating customer
            if (!empty($fetch_details)) {
                $query = "SELECT details FROM customer_prepayments 
                          WHERE customer_name = :cust_name AND details IS NOT NULL AND details != '' 
                          ORDER BY created_at DESC LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->execute([':cust_name' => $fetch_details]);
                $pastDetails = $stmt->fetchColumn();

                echo json_encode([
                    "isOk" => true,
                    "details" => $pastDetails ?: ""
                ]);
                exit;
            }

            if (!empty($fetch_customer_id)) {
                $query = "SELECT *
                          FROM customer_prepayments
                          WHERE details = :customer_id
                          ORDER BY created_at DESC LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->execute([':customer_id' => $fetch_customer_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($record) {
                    try {
                        ensurePrepaymentItemsTable($db);
                        $itemStmt = $db->prepare("SELECT * FROM prepayment_items WHERE prepayment_id = :id ORDER BY id");
                        $itemStmt->execute([':id' => (int) $record['id']]);
                        $record['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $record['items'] = [];
                    }

                    try {
                        ensurePrepaymentReceiptsTable($db);
                        $record['receipts'] = fetchReceiptsForPrepaymentIds($db, [(int) $record['id']])[$record['id']] ?? [];
                        if (empty($record['screenshot']) && !empty($record['receipts'])) {
                            $record['screenshot'] = $record['receipts'][0]['image_data'];
                        }
                    } catch (Exception $e) {
                        $record['receipts'] = [];
                    }
                }

                echo json_encode([
                    "isOk" => true,
                    "customer_name" => $record['customer_name'] ?? "",
                    "record" => $record ?: null
                ]);
                exit;
            }

            if (!empty($exact_order_id)) {
                $duplicates = findDuplicateOrderIds($db, [$exact_order_id], (int) ($_GET['exclude_id'] ?? 0));
                echo json_encode([
                    "isOk" => true,
                    "data" => $duplicates
                ]);
                exit;
            }

            if (!empty($customer_history)) {
                $query = "SELECT id, customer_name, details, amount_due, amount_paid, total_items, delivered_items, is_arrived, created_at
                          FROM customer_prepayments
                          WHERE details = :customer_id OR customer_name = :customer_id
                          ORDER BY created_at DESC
                          LIMIT 10";
                $stmt = $db->prepare($query);
                $stmt->execute([':customer_id' => $customer_history]);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    "isOk" => true,
                    "data" => $history,
                    "summary" => [
                        "count" => count($history),
                        "total_due" => array_sum(array_map(function ($row) { return (float) ($row['amount_due'] ?? 0); }, $history)),
                        "total_paid" => array_sum(array_map(function ($row) { return (float) ($row['amount_paid'] ?? 0); }, $history))
                    ]
                ]);
                exit;
            }

            if (!empty($check_order_ids)) {
                $orderIds = array_values(array_filter(array_map('trim', explode(',', $check_order_ids))));
                $excludeId = (int) ($_GET['exclude_id'] ?? 0);
                $duplicates = findDuplicateOrderIds($db, $orderIds, $excludeId);

                echo json_encode([
                    "isOk" => true,
                    "duplicates" => $duplicates
                ]);
                exit;
            }

            // 2. Fetch Audit History Timeline for a specific record
            $history_id = $_GET['history_id'] ?? '';
            if (!empty($history_id)) {
                $query = "SELECT al.*, u.username as actor_name 
                          FROM audit_logs al
                          LEFT JOIN users u ON al.user_id = u.id
                          WHERE al.table_name = 'customer_prepayments' 
                          AND al.record_id = :id
                          ORDER BY al.created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $history_id]);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Decode details log content
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

            // 3. Build Filters
            $whereConditions = [];
            $params = [];
            if (prepaymentItemsTableExists($db)) {
                $notArrivedBaseExpr = "(CASE
                    WHEN EXISTS (SELECT 1 FROM prepayment_items pi_check WHERE pi_check.prepayment_id = cp.id)
                    THEN COALESCE((SELECT SUM(pi.qty * pi.unit_price) FROM prepayment_items pi WHERE pi.prepayment_id = cp.id AND (pi.delivered_qty IS NULL OR pi.delivered_qty <= 0)), 0)
                    WHEN cp.is_arrived = 1 THEN 0
                    ELSE cp.amount_due
                END)";
            } else {
                $notArrivedBaseExpr = "(CASE WHEN cp.is_arrived = 1 THEN 0 ELSE cp.amount_due END)";
            }
            $prepaymentBaseExpr = "GREATEST(cp.amount_due - $notArrivedBaseExpr, 0)";
            $prepaymentTargetExpr = "($notArrivedBaseExpr * 0.3)";

            if ($status !== 'all') {
                if ($status === 'paid') {
                    $whereConditions[] = "(cp.amount_paid >= $prepaymentTargetExpr AND $prepaymentTargetExpr > 0)";
                } elseif ($status === 'partial') {
                    $whereConditions[] = "cp.amount_paid > 0 AND cp.amount_paid < $prepaymentTargetExpr";
                } elseif ($status === 'unpaid') {
                    $whereConditions[] = "($prepaymentTargetExpr <= 0 OR cp.amount_paid IS NULL OR cp.amount_paid = 0)";
                }
            }

            if (!empty($search)) {
                $whereConditions[] = "(cp.customer_name LIKE :search OR cp.details LIKE :search)";
                $params[':search'] = "%$search%";
            }

            if (!empty($from_date)) {
                $whereConditions[] = "cp.created_at >= :from_date";
                $params[':from_date'] = $from_date . " 00:00:00";
            }

            if (!empty($to_date)) {
                $whereConditions[] = "cp.created_at <= :to_date";
                $params[':to_date'] = $to_date . " 23:59:59";
            }

            if (!empty($exact_customer_name)) {
                $whereConditions[] = "cp.customer_name = :exact_name";
                $params[':exact_name'] = $exact_customer_name;
            }

            if (!empty($_GET['exact_details'])) {
                $whereConditions[] = "cp.details = :exact_details";
                $params[':exact_details'] = $_GET['exact_details'];
            }

            $whereSql = count($whereConditions) > 0 ? "WHERE " . implode(" AND ", $whereConditions) : "";

            // 4. Calculate stats (across all pages)
            $statsQuery = "SELECT 
                            COUNT(*) as total_count,
                            SUM(amount_due) as total_expected,
                            SUM(amount_paid) as total_collected,
                            SUM(CASE WHEN amount_paid >= $prepaymentTargetExpr AND $prepaymentTargetExpr > 0 THEN 1 ELSE 0 END) as paid_count,
                            SUM(CASE WHEN amount_paid > 0 AND amount_paid < $prepaymentTargetExpr THEN 1 ELSE 0 END) as partial_count,
                            SUM(CASE WHEN $prepaymentTargetExpr <= 0 OR amount_paid = 0 OR amount_paid IS NULL THEN 1 ELSE 0 END) as unpaid_count,
                            SUM($prepaymentBaseExpr) as total_arrived_cost,
                            SUM($notArrivedBaseExpr) as total_pending_cost,
                            SUM(CASE WHEN $prepaymentBaseExpr > 0 THEN 1 ELSE 0 END) as arrived_count,
                            SUM(CASE WHEN $prepaymentBaseExpr < cp.amount_due THEN 1 ELSE 0 END) as pending_count
                          FROM customer_prepayments cp
                          $whereSql";

            $statsStmt = $db->prepare($statsQuery);
            foreach ($params as $key => $val) {
                $statsStmt->bindValue($key, $val);
            }
            $statsStmt->execute();
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            // 5. Get Paginated Ledger Records
            $query = "SELECT cp.*, u.username as requested_by_name 
                      FROM customer_prepayments cp 
                      LEFT JOIN users u ON cp.requested_by = u.id 
                      $whereSql
                      ORDER BY cp.created_at DESC 
                      LIMIT :limit OFFSET :offset";

            $stmt = $db->prepare($query);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Attach items to results if prepayment_items table exists
            if (!empty($results)) {
                $ids = array_map(function ($r) {
                    return (int) $r['id']; }, $results);
                $in = implode(',', array_fill(0, count($ids), '?'));
                $itemQuery = "SELECT * FROM prepayment_items WHERE prepayment_id IN ($in) ORDER BY id";
                $itemStmt = $db->prepare($itemQuery);
                foreach ($ids as $k => $v) {
                    $itemStmt->bindValue($k + 1, $v, PDO::PARAM_INT);
                }
                try {
                    $itemStmt->execute();
                    $allItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                    $itemsMap = [];
                    foreach ($allItems as $it) {
                        $pid = (int) $it['prepayment_id'];
                        if (!isset($itemsMap[$pid]))
                            $itemsMap[$pid] = [];
                        $itemsMap[$pid][] = $it;
                    }
                    foreach ($results as &$r) {
                        $rid = (int) $r['id'];
                        $r['items'] = $itemsMap[$rid] ?? [];
                    }
                } catch (Exception $e) {
                    // ignore if table does not exist or query fails
                }

                try {
                    $receiptsMap = fetchReceiptsForPrepaymentIds($db, $ids);
                    foreach ($results as &$r) {
                        $rid = (int) $r['id'];
                        $r['receipts'] = $receiptsMap[$rid] ?? [];
                        if (empty($r['screenshot']) && !empty($r['receipts'])) {
                            $r['screenshot'] = $r['receipts'][0]['image_data'];
                        }
                    }
                } catch (Exception $e) {
                    foreach ($results as &$r) {
                        $r['receipts'] = [];
                    }
                }
            }

            $totalRecords = (int) ($stats['total_count'] ?? 0);
            $totalPages = (int) max(1, ceil($totalRecords / max(1, $limit)));

            // Transform stats to match frontend expectations
            $transformedStats = [
                'expected' => (float) ($stats['total_expected'] ?? 0),
                'collected' => (float) ($stats['total_collected'] ?? 0),
                'required' => (float) (($stats['total_pending_cost'] ?? 0) * 0.3),
                'arrived_cost' => (float) ($stats['total_arrived_cost'] ?? 0),
                'pending_cost' => (float) ($stats['total_pending_cost'] ?? 0),
                'arrived_count' => (int) ($stats['arrived_count'] ?? 0),
                'pending_count' => (int) ($stats['pending_count'] ?? 0),
                'paid_count' => (int) ($stats['paid_count'] ?? 0),
                'partial_count' => (int) ($stats['partial_count'] ?? 0),
                'unpaid_count' => (int) ($stats['unpaid_count'] ?? 0)
            ];

            echo json_encode([
                "isOk" => true,
                "data" => $results,
                "stats" => $transformedStats,
                "pagination" => [
                    "page" => $page,
                    "limit" => $limit,
                    "total_pages" => $totalPages,
                    "total_records" => $totalRecords
                ]
            ]);
            break;

        case 'POST':
            if (!$input)
                throw new Exception("Invalid input data.");

            $customer_name = trim($input['customer_name'] ?? '');
            $details = trim($input['details'] ?? '');
            $amount_due = floatval($input['amount_due'] ?? 0);
            $amount_paid = floatval($input['amount_paid'] ?? 0);
            $total_items = intval($input['total_items'] ?? 0);
            $delivered_items = intval($input['delivered_items'] ?? 0);
            $screenshot = $input['screenshot'] ?? '';
            $receipts = is_array($input['receipts']) ? $input['receipts'] : [];
            if (empty($screenshot) && !empty($receipts)) {
                $screenshot = trim($receipts[0]) ?: '';
            }

            if (empty($customer_name))
                throw new Exception("Customer name is required.");
            if ($amount_due <= 0)
                throw new Exception("Total cost must be greater than zero.");
            if ($amount_paid < 0)
                throw new Exception("Amount paid cannot be negative.");
            if ($delivered_items > $total_items)
                throw new Exception("Delivered items cannot exceed total ordered.");

            $orderIds = normalizeOrderIds($input['items'] ?? []);
            if (hasDuplicateOrderIdsInPayload($input['items'] ?? [])) {
                throw new Exception("Duplicate order ID found in this form. Each item name/order ID must be unique.");
            }
            $duplicateOrderIds = findDuplicateOrderIds($db, $orderIds);
            if (!empty($duplicateOrderIds)) {
                $duplicateNames = array_values(array_unique(array_map(function ($row) {
                    return $row['order_id'];
                }, $duplicateOrderIds)));
                throw new Exception("Order ID already exists: " . implode(', ', $duplicateNames) . ". Use the existing record instead of creating a duplicate.");
            }

            $query = "INSERT INTO customer_prepayments 
                      (customer_name, details, amount_due, amount_paid, total_items, delivered_items, is_arrived, screenshot, requested_by) 
                      VALUES (:customer_name, :details, :amount_due, :amount_paid, :total_items, :delivered_items, :is_arrived, :screenshot, :requested_by)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                ':customer_name' => $customer_name,
                ':details' => $details,
                ':amount_due' => $amount_due,
                ':amount_paid' => $amount_paid,
                ':total_items' => $total_items,
                ':delivered_items' => $delivered_items,
                ':is_arrived' => intval($input['is_arrived'] ?? 0),
                ':screenshot' => $screenshot,
                ':requested_by' => $currentUser['id']
            ]);
            $newId = $db->lastInsertId();

            if (!empty($receipts) && is_array($receipts)) {
                ensurePrepaymentReceiptsTable($db);
                $insReceipt = $db->prepare("INSERT INTO prepayment_receipts (prepayment_id, image_data) VALUES (:prepayment_id, :image_data)");
                foreach ($receipts as $receiptData) {
                    $receiptData = trim($receiptData);
                    if ($receiptData === '') {
                        continue;
                    }
                    $insReceipt->execute([':prepayment_id' => $newId, ':image_data' => $receiptData]);
                }
            }

            // If items provided, ensure table exists and insert rows
            if (!empty($input['items']) && is_array($input['items'])) {
                ensurePrepaymentItemsTable($db);

                $insItem = $db->prepare("INSERT INTO prepayment_items (prepayment_id, product_sku, product_name, unit_price, qty, prepayment_amount, amount_paid, delivered_qty) VALUES (:prepayment_id, :sku, :name, :unit_price, :qty, :prepayment_amount, :amount_paid, :delivered_qty)");
                foreach ($input['items'] as $it) {
                    $sku = $it['sku'] ?? null;
                    $pname = $it['name'] ?? ($it['product_name'] ?? null);
                    $qty = intval($it['qty'] ?? ($it['quantity'] ?? 0));
                    $price = floatval($it['price'] ?? ($it['unit_price'] ?? 0));
                    $prepay = floatval($it['prepay'] ?? ($qty * $price * 0.3));
                    $paid = floatval($it['paid'] ?? 0);
                    $del = intval($it['delivered'] ?? 0);
                    $insItem->execute([
                        ':prepayment_id' => $newId,
                        ':sku' => $sku,
                        ':name' => $pname,
                        ':unit_price' => $price,
                        ':qty' => $qty,
                        ':prepayment_amount' => $prepay,
                        ':amount_paid' => $paid,
                        ':delivered_qty' => $del
                    ]);
                }
            }

            // Create Audit Log
            $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                           VALUES (:user_id, 'CREATE', 'customer_prepayments', :record_id, :details, :ip_address)";
            $auditStmt = $db->prepare($auditQuery);
            $auditStmt->execute([
                ':user_id' => $currentUser['id'],
                ':record_id' => $newId,
                ':details' => json_encode([
                    'customer_name' => $customer_name,
                    'details' => $details,
                    'amount_due' => $amount_due,
                    'amount_paid' => $amount_paid,
                    'total_items' => $total_items,
                    'delivered_items' => $delivered_items,
                    'items' => $input['items'] ?? []
                ]),
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            echo json_encode(["isOk" => true, "message" => "Customer prepayment record created successfully.", "id" => $newId]);
            break;

        case 'PUT':
            if (!$input || (!isset($input['id']) && empty($input['ids'])))
                throw new Exception("Missing prepayment ID(s).");

            $action = $input['action'] ?? 'update';
            $validActions = ['update', 'quick_pay', 'bulk_paid', 'bulk_clear', 'arrival_status'];
            if (!in_array($action, $validActions)) {
                throw new Exception("Invalid action: " . $action);
            }

            if ($action === 'update') {
                $id = intval($input['id']);

                // Get old record for audit trail logs
                $oldStmt = $db->prepare("SELECT * FROM customer_prepayments WHERE id = :id");
                $oldStmt->execute([':id' => $id]);
                $oldRecord = $oldStmt->fetch(PDO::FETCH_ASSOC);

                if (!$oldRecord)
                    throw new Exception("Record not found.");

                $customer_name = trim($input['customer_name'] ?? '');
                $details = trim($input['details'] ?? '');
                $amount_due = floatval($input['amount_due'] ?? 0);
                $amount_paid = floatval($input['amount_paid'] ?? 0);
                $total_items = intval($input['total_items'] ?? 0);
                $delivered_items = intval($input['delivered_items'] ?? 0);
                $is_arrived = intval($input['is_arrived'] ?? 0) ? 1 : 0;
                $screenshot = $input['screenshot'] ?? '';
                $receipts = is_array($input['receipts']) ? $input['receipts'] : [];
                if (empty($screenshot) && !empty($receipts)) {
                    $screenshot = trim($receipts[0]) ?: '';
                }

                if (empty($customer_name))
                    throw new Exception("Customer name is required.");
                if ($amount_due <= 0)
                    throw new Exception("Total cost must be greater than zero.");
                if ($amount_paid < 0)
                    throw new Exception("Amount paid cannot be negative.");
                if ($delivered_items > $total_items)
                    throw new Exception("Delivered items cannot exceed total ordered.");

                $orderIds = normalizeOrderIds($input['items'] ?? []);
                if (hasDuplicateOrderIdsInPayload($input['items'] ?? [])) {
                    throw new Exception("Duplicate order ID found in this form. Each item name/order ID must be unique.");
                }
                $duplicateOrderIds = findDuplicateOrderIds($db, $orderIds, $id);
                if (!empty($duplicateOrderIds)) {
                    $duplicateNames = array_values(array_unique(array_map(function ($row) {
                        return $row['order_id'];
                    }, $duplicateOrderIds)));
                    throw new Exception("Order ID already exists: " . implode(', ', $duplicateNames) . ". Use the existing record instead of creating a duplicate.");
                }

                $query = "UPDATE customer_prepayments SET 
                            customer_name = :customer_name,
                            details = :details,
                            amount_due = :amount_due,
                            amount_paid = :amount_paid,
                            total_items = :total_items,
                            delivered_items = :delivered_items,
                            is_arrived = :is_arrived,
                            screenshot = :screenshot
                          WHERE id = :id";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    ':customer_name' => $customer_name,
                    ':details' => $details,
                    ':amount_due' => $amount_due,
                    ':amount_paid' => $amount_paid,
                    ':total_items' => $total_items,
                    ':delivered_items' => $delivered_items,
                    ':is_arrived' => $is_arrived,
                    ':screenshot' => $screenshot ?: $oldRecord['screenshot'],
                    ':id' => $id
                ]);

                if (!$result)
                    throw new Exception("Failed to update record.");

                // Update items if provided: recreate items for this prepayment
                if (!empty($input['items']) && is_array($input['items'])) {
                    ensurePrepaymentItemsTable($db);

                    // remove existing then insert new
                    $delStmt = $db->prepare("DELETE FROM prepayment_items WHERE prepayment_id = :pid");
                    $delStmt->execute([':pid' => $id]);

                    $insItem = $db->prepare("INSERT INTO prepayment_items (prepayment_id, product_sku, product_name, unit_price, qty, prepayment_amount, amount_paid, delivered_qty) VALUES (:prepayment_id, :sku, :name, :unit_price, :qty, :prepayment_amount, :amount_paid, :delivered_qty)");
                    foreach ($input['items'] as $it) {
                        $sku = $it['sku'] ?? null;
                        $pname = $it['name'] ?? ($it['product_name'] ?? null);
                        $qty = intval($it['qty'] ?? ($it['quantity'] ?? 0));
                        $price = floatval($it['price'] ?? ($it['unit_price'] ?? 0));
                        $prepay = floatval($it['prepay'] ?? ($qty * $price * 0.3));
                        $paid = floatval($it['paid'] ?? 0);
                        $del = intval($it['delivered'] ?? 0);
                        $insItem->execute([
                            ':prepayment_id' => $id,
                            ':sku' => $sku,
                            ':name' => $pname,
                            ':unit_price' => $price,
                            ':qty' => $qty,
                            ':prepayment_amount' => $prepay,
                            ':amount_paid' => $paid,
                            ':delivered_qty' => $del
                        ]);
                    }
                }

                if (!empty($receipts) && is_array($receipts)) {
                    ensurePrepaymentReceiptsTable($db);
                    $insReceipt = $db->prepare("INSERT INTO prepayment_receipts (prepayment_id, image_data) VALUES (:prepayment_id, :image_data)");
                    foreach ($receipts as $receiptData) {
                        $receiptData = trim($receiptData);
                        if ($receiptData === '') {
                            continue;
                        }
                        $insReceipt->execute([':prepayment_id' => $id, ':image_data' => $receiptData]);
                    }
                }

                // Log audit trail
                $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                               VALUES (:user_id, 'UPDATE_DATA', 'customer_prepayments', :record_id, :details, :ip_address)";
                $auditStmt = $db->prepare($auditQuery);
                $auditStmt->execute([
                    ':user_id' => $currentUser['id'],
                    ':record_id' => $id,
                    ':details' => json_encode([
                        'old' => [
                            'customer_name' => $oldRecord['customer_name'],
                            'details' => $oldRecord['details'],
                            'amount_due' => (float) $oldRecord['amount_due'],
                            'amount_paid' => (float) $oldRecord['amount_paid'],
                            'total_items' => (int) $oldRecord['total_items'],
                            'delivered_items' => (int) $oldRecord['delivered_items'],
                            'is_arrived' => (int) ($oldRecord['is_arrived'] ?? 0)
                        ],
                        'new' => [
                            'customer_name' => $customer_name,
                            'details' => $details,
                            'amount_due' => $amount_due,
                            'amount_paid' => $amount_paid,
                            'total_items' => $total_items,
                            'delivered_items' => $delivered_items,
                            'is_arrived' => $is_arrived,
                            'items' => $input['items'] ?? []
                        ]
                    ]),
                    ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                ]);

                echo json_encode(["isOk" => true, "message" => "Prepayment record updated successfully."]);

            } elseif ($action === 'quick_pay') {
                $id = intval($input['id']);
                $addedAmount = floatval($input['amount'] ?? 0);

                if ($addedAmount <= 0)
                    throw new Exception("Payment amount must be greater than zero.");

                $oldStmt = $db->prepare("SELECT * FROM customer_prepayments WHERE id = :id");
                $oldStmt->execute([':id' => $id]);
                $oldRecord = $oldStmt->fetch(PDO::FETCH_ASSOC);

                if (!$oldRecord)
                    throw new Exception("Record not found.");

                $currentPaid = (float) ($oldRecord['amount_paid'] ?? 0);
                $newPaid = $currentPaid + $addedAmount;

                $query = "UPDATE customer_prepayments SET amount_paid = :amount_paid WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([':amount_paid' => $newPaid, ':id' => $id]);

                // Audit Log Status change
                $oldStatus = getArrivalPrepaymentStatusFromDb($db, $oldRecord, $currentPaid);
                $newStatus = getArrivalPrepaymentStatusFromDb($db, $oldRecord, $newPaid);

                $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                               VALUES (:user_id, 'UPDATE_STATUS', 'customer_prepayments', :record_id, :details, :ip_address)";
                $auditStmt = $db->prepare($auditQuery);
                $auditStmt->execute([
                    ':user_id' => $currentUser['id'],
                    ':record_id' => $id,
                    ':details' => json_encode([
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'added_amount' => $addedAmount,
                        'new_paid_total' => $newPaid,
                        'customer_name' => $oldRecord['customer_name']
                    ]),
                    ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                ]);

                echo json_encode(["isOk" => true, "message" => "Log payment of $" . number_format($addedAmount, 2) . " registered successfully."]);

            } elseif ($action === 'arrival_status') {
                $id = intval($input['id'] ?? 0);
                $isArrived = intval($input['is_arrived'] ?? 0) ? 1 : 0;
                if ($id <= 0)
                    throw new Exception('Invalid record id for arrival status.');

                $oldStmt = $db->prepare('SELECT * FROM customer_prepayments WHERE id = :id');
                $oldStmt->execute([':id' => $id]);
                $oldRecord = $oldStmt->fetch(PDO::FETCH_ASSOC);
                if (!$oldRecord)
                    throw new Exception('Record not found.');

                // When marking arrived, if delivered_items is less than total_items, set delivered_items = total_items.
                // When marking not arrived, reset delivered_items to 0.
                $total = intval($oldRecord['total_items'] ?? 0);
                $delivered = intval($oldRecord['delivered_items'] ?? 0);
                if ($isArrived) {
                    $newDelivered = $total > 0 ? $total : $delivered;
                } else {
                    $newDelivered = 0;
                }

                $upd = $db->prepare('UPDATE customer_prepayments SET is_arrived = :is_arrived, delivered_items = :delivered_items WHERE id = :id');
                $upd->execute([':is_arrived' => $isArrived, ':delivered_items' => $newDelivered, ':id' => $id]);

                try {
                    $itemUpd = $db->prepare('UPDATE prepayment_items SET delivered_qty = :delivered_qty WHERE prepayment_id = :id');
                    $itemUpd->execute([':delivered_qty' => $isArrived ? 1 : 0, ':id' => $id]);
                } catch (Exception $e) {
                    // Item-level arrival details are optional for older installs.
                }

                $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                               VALUES (:user_id, 'UPDATE_STATUS', 'customer_prepayments', :record_id, :details, :ip_address)";
                $auditStmt = $db->prepare($auditQuery);
                $auditStmt->execute([
                    ':user_id' => $currentUser['id'],
                    ':record_id' => $id,
                    ':details' => json_encode([
                        'field' => 'is_arrived',
                        'old' => $oldRecord['is_arrived'] ?? 0,
                        'new' => $isArrived,
                        'old_delivered' => $delivered,
                        'new_delivered' => $newDelivered
                    ]),
                    ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                ]);

                echo json_encode(['isOk' => true, 'message' => 'Arrival status updated.']);

            } else {
                // Bulk Status Updates: bulk_paid (meets 30% baseline) or bulk_clear (meets 100% total)
                $ids = !empty($input['ids']) && is_array($input['ids']) ? $input['ids'] : [$input['id']];

                $db->beginTransaction();
                try {
                    $updatedCount = 0;
                    foreach ($ids as $id) {
                        $id = intval($id);
                        $oldStmt = $db->prepare("SELECT * FROM customer_prepayments WHERE id = :id");
                        $oldStmt->execute([':id' => $id]);
                        $oldRecord = $oldStmt->fetch(PDO::FETCH_ASSOC);

                        if (!$oldRecord)
                            continue;

                        $due = (float) $oldRecord['amount_due'];
                        $paid = (float) $oldRecord['amount_paid'];
                        $oldStatus = getArrivalPrepaymentStatusFromDb($db, $oldRecord, $paid);

                        if ($action === 'bulk_paid') {
                            $targetPaid = getArrivalPrepaymentTargetFromDb($db, $oldRecord);
                            if ($targetPaid <= 0)
                                continue; // No not-arrived prepayment target remains.
                            if ($paid >= $targetPaid)
                                continue; // Skip if already meets 30% baseline

                            $newPaid = $targetPaid;
                            $newStatus = 'paid';
                        } else { // bulk_clear
                            if ($paid >= $due)
                                continue; // Skip if already 100% paid

                            $newPaid = $due;
                            $newStatus = 'paid';
                        }

                        $query = "UPDATE customer_prepayments SET amount_paid = :amount_paid WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $result = $stmt->execute([':amount_paid' => $newPaid, ':id' => $id]);

                        if ($result) {
                            $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                                           VALUES (:user_id, 'UPDATE_STATUS', 'customer_prepayments', :record_id, :details, :ip_address)";
                            $auditStmt = $db->prepare($auditQuery);
                            $auditStmt->execute([
                                ':user_id' => $currentUser['id'],
                                ':record_id' => $id,
                                ':details' => json_encode([
                                    'old_status' => $oldStatus,
                                    'new_status' => $newStatus,
                                    'old_paid' => $paid,
                                    'new_paid' => $newPaid,
                                    'action' => $action,
                                    'customer_name' => $oldRecord['customer_name']
                                ]),
                                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                            ]);
                            $updatedCount++;
                        }
                    }
                    $db->commit();
                    echo json_encode(["isOk" => true, "message" => "Successfully updated $updatedCount customer record(s)."]);
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
            }
            break;

        case 'DELETE':
            if (!empty($input['purge_all']) && ($input['confirm'] ?? '') === 'DELETE_ALL_PREPAYMENTS') {
                $db->beginTransaction();
                try {
                    ensurePrepaymentItemsTable($db);
                    ensurePrepaymentReceiptsTable($db);
                    $countStmt = $db->query("SELECT COUNT(*) FROM customer_prepayments");
                    $deletedCount = (int) $countStmt->fetchColumn();
                    $db->exec("DELETE FROM prepayment_receipts");
                    $db->exec("DELETE FROM prepayment_items");
                    $db->exec("DELETE FROM customer_prepayments");
                    $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                                   VALUES (:user_id, 'DELETE', 'customer_prepayments', 0, :details, :ip_address)";
                    $auditStmt = $db->prepare($auditQuery);
                    $auditStmt->execute([
                        ':user_id' => $currentUser['id'],
                        ':details' => json_encode(['purge_all' => true, 'deleted_count' => $deletedCount]),
                        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                    ]);
                    $db->commit();
                    echo json_encode(["isOk" => true, "message" => "Successfully deleted all $deletedCount customer record(s)."]);
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                break;
            }

            if (!$input || (!isset($input['id']) && empty($input['ids'])))
                throw new Exception("Missing prepayment ID(s) for deletion.");

            $ids = !empty($input['ids']) && is_array($input['ids']) ? $input['ids'] : [$input['id']];

            $db->beginTransaction();
            try {
                $deletedCount = 0;
                foreach ($ids as $id) {
                    $id = intval($id);
                    // Fetch record for audit logs
                    $oldStmt = $db->prepare("SELECT * FROM customer_prepayments WHERE id = :id");
                    $oldStmt->execute([':id' => $id]);
                    $oldRecord = $oldStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$oldRecord)
                        continue;

                    $query = "DELETE FROM customer_prepayments WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $result = $stmt->execute([':id' => $id]);

                    if ($result) {
                        try {
                            ensurePrepaymentItemsTable($db);
                            $db->prepare("DELETE FROM prepayment_items WHERE prepayment_id = :id")->execute([':id' => $id]);
                        } catch (Exception $e) {
                            // ignore item cleanup failures
                        }
                        try {
                            ensurePrepaymentReceiptsTable($db);
                            $db->prepare("DELETE FROM prepayment_receipts WHERE prepayment_id = :id")->execute([':id' => $id]);
                        } catch (Exception $e) {
                            // ignore receipt cleanup failures
                        }

                        // Audit Log Deletion
                        $auditQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address) 
                                       VALUES (:user_id, 'DELETE', 'customer_prepayments', :record_id, :details, :ip_address)";
                        $auditStmt = $db->prepare($auditQuery);
                        $auditStmt->execute([
                            ':user_id' => $currentUser['id'],
                            ':record_id' => $id,
                            ':details' => json_encode([
                                'customer_name' => $oldRecord['customer_name'],
                                'details' => $oldRecord['details'],
                                'amount_due' => (float) $oldRecord['amount_due'],
                                'amount_paid' => (float) $oldRecord['amount_paid'],
                                'total_items' => (int) $oldRecord['total_items'],
                                'delivered_items' => (int) $oldRecord['delivered_items']
                            ]),
                            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                        ]);
                        $deletedCount++;
                    }
                }
                $db->commit();
                echo json_encode(["isOk" => true, "message" => "Successfully deleted $deletedCount customer record(s)."]);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception("Method " . $method . " not allowed.");
    }

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        "isOk" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>
