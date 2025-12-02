<?php
// logout.php
require_once "config/auth.php";
Auth::logout();
header("Location: login.php");
exit();
?>