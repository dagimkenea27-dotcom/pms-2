<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

function describeTable($db, $table) {
    echo "Table: $table\n";
    try {
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

describeTable($db, 'cost_settings');
describeTable($db, 'optimization_preferences');
?>
