<?php
require_once "config/database.php";
$db = (new Database())->getConnection();
$tables = ['route_costs'];
foreach ($tables as $table) {
    $stmt = $db->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "Table $table exists.\n";
    } else {
        echo "Table $table MISSING.\n";
    }
}
?>
