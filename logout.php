<?php
// logout.php
require_once "config/paths.php"; // Ensure paths are loaded
require_once "config/database.php";
require_once "models/AuditLog.php";
require_once "config/auth.php";

Auth::startSession();

if (Auth::isLoggedIn()) {
    $database = new Database();
    $db = $database->getConnection();
    $audit = new AuditLog($db);
    $user = Auth::getCurrentUser();
    $audit->log($user['id'], "LOGOUT", "User logged out.");
}

Auth::logout();
header("Location: login.php");
exit();
?>