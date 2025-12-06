<?php
// debug_session.php
require_once "config/auth.php";
Auth::startSession();

echo "Session ID: " . session_id() . "<br>";
echo "User Logged In: " . (Auth::isLoggedIn() ? "Yes" : "No") . "<br>";
if (Auth::isLoggedIn()) {
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "Session Dump: ";
    print_r($_SESSION);
}
?>
