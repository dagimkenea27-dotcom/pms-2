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

// Prevent caching so back button doesn't work
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("Location: " . (defined('BASE_URL') ? BASE_URL : '') . "login.php");
exit();
?>