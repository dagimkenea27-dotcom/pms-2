<?php
header("Content-Type: application/json");

try {
    require_once dirname(__DIR__) . "/config/database.php";
    require_once dirname(__DIR__) . "/config/auth.php";

    Auth::startSession();
    if (!Auth::isLoggedIn()) {
        echo json_encode(["isOk" => false, "message" => "Unauthorized"]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;
    $status = $_GET['status'] ?? null;
    $source = $_GET['source'] ?? null;

    $where_clauses = [];
    $params = [];

    if ($start_date && $end_date) {
        $where_clauses[] = "DATE(created_at) BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
    }
    if ($status) {
        $where_clauses[] = "status = :status";
        $params[':status'] = $status;
    }
    if ($source) {
        $where_clauses[] = "source = :source";
        $params[':source'] = $source;
    }

    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

    // 1. Overall Summary
    $summary_query = "
        SELECT 
            COUNT(*) as total_orders,
            COUNT(DISTINCT bo.customer_name) as unique_customers,
            (SELECT SUM(v.quantity) FROM branch_order_variations v 
             JOIN branch_order_items i ON v.item_id = i.id 
             JOIN branch_orders bo2 ON i.order_id = bo2.id 
             $where_sql) as total_items
        FROM branch_orders bo
        $where_sql
    ";
    $stmt = $db->prepare($summary_query);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Orders by Source
    $source_query = "
        SELECT COALESCE(source, 'Unknown') as label, COUNT(*) as value 
        FROM branch_orders bo
        $where_sql 
        GROUP BY label 
        ORDER BY value DESC
    ";
    $stmt = $db->prepare($source_query);
    $stmt->execute($params);
    $by_source = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Orders by Status
    $status_query = "
        SELECT status as label, COUNT(*) as value 
        FROM branch_orders bo
        $where_sql 
        GROUP BY label 
        ORDER BY value DESC
    ";
    $stmt = $db->prepare($status_query);
    $stmt->execute($params);
    $by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Daily Trends (Last 30 days if no filter)
    $trend_query = "
        SELECT DATE(created_at) as label, COUNT(*) as value 
        FROM branch_orders bo
        $where_sql 
        GROUP BY label 
        ORDER BY label ASC
    ";
    if (!$start_date) {
        $trend_query .= " LIMIT 30"; // Last 30 unique dates
    }
    $stmt = $db->prepare($trend_query);
    $stmt->execute($params);
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Top Products
    $products_query = "
        SELECT i.product_name as label, SUM(v.quantity) as value 
        FROM branch_order_items i 
        JOIN branch_order_variations v ON i.id = v.item_id 
        JOIN branch_orders bo ON i.order_id = bo.id
        $where_sql 
        GROUP BY i.product_name 
        ORDER BY value DESC 
        LIMIT 10
    ";
    $stmt = $db->prepare($products_query);
    $stmt->execute($params);
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "isOk" => true,
        "summary" => $summary,
        "by_source" => $by_source,
        "by_status" => $by_status,
        "trends" => $trends,
        "top_products" => $top_products
    ]);

} catch (Throwable $e) {
    echo json_encode(["isOk" => false, "message" => $e->getMessage()]);
}
?>
