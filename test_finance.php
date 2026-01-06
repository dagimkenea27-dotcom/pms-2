<?php
require_once "config/database.php";
require_once "models/Finance.php";

try {
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $finance = new Finance($db);
    
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-d');
    
    echo "Testing getGrossProfit...\n";
    print_r($finance->getGrossProfit($startDate, $endDate));
    
    echo "\nTesting getTotalExpenses...\n";
    print_r($finance->getTotalExpenses($startDate, $endDate));
    
    echo "\nTesting getNetProfit...\n";
    print_r($finance->getNetProfit($startDate, $endDate));
    
    echo "\nTesting getExpenseBreakdown...\n";
    print_r($finance->getExpenseBreakdown($startDate, $endDate));
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
