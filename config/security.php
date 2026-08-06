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
     * Pass $parsedJson when the caller already read php://input (it can only be read once).
     * @param array|null $parsedJson
     * @return bool
     */
    public static function validateRequest($parsedJson = null) {
        $token = '';

        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (!empty($_SERVER['REDIRECT_HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['REDIRECT_HTTP_X_CSRF_TOKEN'];
        } elseif (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } elseif (is_array($parsedJson) && isset($parsedJson['csrf_token'])) {
            $token = $parsedJson['csrf_token'];
        } elseif ($parsedJson === null) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && $raw !== '') {
                $input = json_decode($raw, true);
                if (is_array($input) && isset($input['csrf_token'])) {
                    $token = $input['csrf_token'];
                }
            }
        }

        return self::validateCSRFToken($token);
    }
}
