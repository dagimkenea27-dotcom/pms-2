<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config/database.php";

echo "Database connection initiated.<br>";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Connection successful.<br>";
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3>Users List</h3>";
    $stmt = $db->query("SELECT id, username, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "No users found in table 'users'.<br>";
    } else {
        echo "Count: " . count($users) . "<br>";
        foreach($users as $u) {
            echo "ID: " . $u['id'] . " | Name: " . $u['username'] . " | Role: " . $u['role'] . "<br>";
        }
    }

    echo "<h3>Notifications List</h3>";
    $stmt = $db->query("SELECT id, user_id, message, is_read FROM notifications ORDER BY id DESC LIMIT 5");
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($notifs)) {
        echo "No notifications found in table 'notifications'.<br>";
    } else {
        echo "Count: " . count($notifs) . "<br>";
        foreach($notifs as $n) {
            echo "ID: " . $n['id'] . " | UserID: " . $n['user_id'] . " | Msg: " . $n['message'] . " | Read: " . $n['is_read'] . "<br>";
        }
    }

} catch(PDOException $e) {
    echo "PDO Error: " . $e->getMessage();
} catch(Exception $e) {
    echo "General Error: " . $e->getMessage();
}
?>
