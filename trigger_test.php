<?php
// trigger_test.php
require_once "config/auth.php";
require_once "config/database.php";
require_once "models/Notification.php";

Auth::startSession();

if (!Auth::isLoggedIn()) {
    die("Please login first, then access this page.");
}

$db = (new Database())->getConnection();
$notification = new Notification($db);

$user_id = Auth::getCurrentUser()['id'];
$notification->user_id = $user_id;
$notification->message = "Test Notification " . date('H:i:s');
$notification->type = "success";
$notification->link = "#";

if ($notification->create()) {
    echo "Notification Created for User ID: $user_id. Check the bell icon.";
} else {
    echo "Failed to create notification.";
}
?>
