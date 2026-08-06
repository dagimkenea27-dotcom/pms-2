<?php
/**
 * Authentication Check
 * Include this file at the top of any page that requires authentication
 * This will check if user is logged in and prevent browser caching
 */

require_once __DIR__ . '/auth.php';

// Check authentication and prevent caching
Auth::checkAuthAndPreventCache();
?>
