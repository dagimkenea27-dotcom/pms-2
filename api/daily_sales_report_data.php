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

    $where_clause = "";
    $params = [];

    if ($start_date && $end_date) {
        $where_clause = "WHERE date BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
    }

    // Get Daily Summary
    $summary_query = "
        SELECT 
            date,
            COUNT(*) as total_interactions,
            SUM(CASE WHEN purchased = 1 THEN 1 ELSE 0 END) as total_sold,
            SUM(CASE WHEN product_type = 'Kaki Pants' AND purchased = 1 THEN 1 ELSE 0 END) as kaki_sold,
            SUM(CASE WHEN product_type = 'Jeans' AND purchased = 1 THEN 1 ELSE 0 END) as jeans_sold,
            ROUND(SUM(CASE WHEN purchased = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as conversion_rate
        FROM daily_sales_tracker
        $where_clause
        GROUP BY date
        ORDER BY date DESC
        " . ($where_clause ? "" : "LIMIT 30");
    
    $stmt = $db->prepare($summary_query);
    $stmt->execute($params);
    $daily_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get Overall Stats
    $stats_query = "
        SELECT 
            COUNT(*) as grand_total_interactions,
            SUM(CASE WHEN purchased = 1 THEN 1 ELSE 0 END) as grand_total_sold,
            COUNT(DISTINCT date) as total_days
        FROM daily_sales_tracker
        $where_clause
    ";
    $stmt = $db->prepare($stats_query);
    $stmt->execute($params);
    $overall_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "isOk" => true,
        "daily_summary" => $daily_summary,
        "overall_stats" => $overall_stats
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "isOk" => false, 
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>
