<?php
require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

$cols = $db->query("SHOW COLUMNS FROM stock_movements")->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $cols);
?>
