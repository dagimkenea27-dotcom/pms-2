<?php
// test_notif.php
require_once "config/paths.php";
require_once "config/database.php";
require_once "models/Notification.php";

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

// Simulate "countUnread" call for current user (assuming ID 1 for test or check session)
session_start();
$user_id = $_SESSION['user_id'] ?? 1; // Fallback to 1 if not logged in

$count = $notification->countUnread($user_id);
echo "Unread Count for User ID $user_id: " . $count . "<br>";

$recent = $notification->getRecent($user_id);
echo "Recent Notifications:<br>";
while($row = $recent->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
    echo "<br>";
}
?>
