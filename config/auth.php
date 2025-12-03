<?php
// config/auth.php
class Auth {
    public static function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login($user) {
        self::startSession();
        
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['role'] = $user->role;
        $_SESSION['full_name'] = $user->getFullName();
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
    }

    public static function logout() {
        self::startSession();
        session_unset();
        session_destroy();
    }

    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }

    public static function requireRole($required_role) {
        self::requireLogin();
        
        if ($_SESSION['role'] != $required_role && $_SESSION['role'] != 'admin') {
            header("Location: unauthorized.php");
            exit();
        }
    }

    public static function hasPermission($permission) {
        self::startSession();
        
        // Admin has all permissions
        if ($_SESSION['role'] == 'admin') {
            return true;
        }
        
        // Check specific permissions from database
        // This is a simplified version - you might want to implement a more complex permission system
        $allowed_permissions = self::getUserPermissions($_SESSION['user_id']);
        return in_array($permission, $allowed_permissions);
    }

    private static function getUserPermissions($user_id) {
        // This would query the user_permissions table
        // For now, return basic permissions based on role
        $permissions = [
            'staff' => ['products.view', 'stock.view'],
            'manager' => ['products.view', 'products.create', 'products.edit', 'stock.view', 'stock.manage', 'reports.view', 'suppliers.view'],
            'admin' => ['all']
        ];
        
        return $permissions[$_SESSION['role']] ?? [];
    }

    public static function preventCache() {
        // Prevent browser from caching pages
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
    }

    public static function checkAuthAndPreventCache() {
        self::preventCache();
        self::requireLogin();
        self::checkSessionTimeout();
    }

    public static function checkSessionTimeout($timeout = 3600) {
        // Check if session has timed out (default 1 hour)
        self::startSession();
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            // Session has timed out
            self::logout();
            header("Location: " . (defined('BASE_URL') ? BASE_URL : '/') . "login.php?timeout=1");
            exit();
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }

    public static function getCurrentUser() {
        self::startSession();
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null
        ];
    }
}
?>