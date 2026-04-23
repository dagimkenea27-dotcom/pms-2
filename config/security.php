<?php
// config/security.php

class Security {
    /**
     * Generate a CSRF token and store it in the session if it doesn't exist.
     * @return string
     */
    public static function getCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate a CSRF token against the one stored in the session.
     * @param string $token
     * @return bool
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Validate CSRF token from request headers or POST data.
     * @return bool
     */
    public static function validateRequest() {
        $token = '';
        
        // Check headers (common for AJAX)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } 
        // Check POST data
        elseif (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        }
        // Check JSON body
        else {
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['csrf_token'])) {
                $token = $input['csrf_token'];
            }
        }
        
        return self::validateCSRFToken($token);
    }
}
