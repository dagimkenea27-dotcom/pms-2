<?php
// config/auth.php
class Auth {
    // Ensure paths are loaded for BASE_URL
    private static function loadPaths() {
        if (!defined('BASE_URL')) {
            require_once __DIR__ . '/paths.php';
        }
    }
    
    public static function startSession() {
        self::loadPaths();
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

    // --- Permissions (Optional - can be expanded) ---
    public static function hasPermission($permission) {
        // Simple implementation: Admin has all
        if (self::hasRole('admin')) return true;
        return false;
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
            'role' => $_SESSION['role'] ?? 'staff', // Default to staff if undefined
            'full_name' => $_SESSION['full_name'] ?? null
        ];
    }

    // --- Role Based Access Control ---

    /**
     * Check if user has a specific role or higher
     * Hierarchy: admin > manager > staff
     */
    public static function hasRole($required_role) {
        $user = self::getCurrentUser();
        $user_role = strtolower($user['role']);
        $required_role = strtolower($required_role);

        // Admin has access to everything
        if ($user_role === 'admin') return true;

        if ($required_role === 'admin') {
            return $user_role === 'admin';
        }

        if ($required_role === 'manager') {
            return $user_role === 'admin' || $user_role === 'manager';
        }

        if ($required_role === 'staff') {
            return true; // Everyone is at least staff
        }

        return false;
    }

    /**
     * Enforce a role requirement. Redirects if failed.
     */
    public static function requireRole($role) {
        self::checkAuthAndPreventCache();
        if (!self::hasRole($role)) {
            // Log the unauthorized attempt?
            header("HTTP/1.1 403 Forbidden");
            include_once __DIR__ . '/../includes/403.php'; // We need to create this
            exit();
        }
    }

    /**
     * Strict check for exact role
     */
    public static function isRole($role) {
        $user = self::getCurrentUser();
        return strtolower($user['role']) === strtolower($role);
    }

    // --- CSRF Protection ---
    public static function generateCSRF() {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRF($token) {
        self::startSession();
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        return true;
    }
}
?>