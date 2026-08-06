<?php
require_once "config/auth.php";
Auth::startSession();

$lang = $_GET['lang'] ?? 'en';
$allowed_langs = ['en', 'ja'];

if (in_array($lang, $allowed_langs)) {
    $_SESSION['lang'] = $lang;
}

// Redirect back
$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $redirect");
exit;
?>
