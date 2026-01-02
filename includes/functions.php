<?php
/**
 * Helper function to get name from ID from a specified table.
 * 
 * @param PDO $db The database connection
 * @param string $table The table name
 * @param int $id The ID to search for
 * @return string|null The name or null if not found
 */
// Helper function to get name from ID from a specified table.
// ... existing code ...
function getName($db, $table, $id) {
    if (!$id || $id === '') return null;
    $stmt = $db->prepare("SELECT name FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['name'] : null;
}

/**
 * Get current language
 */
function get_current_lang() {
    if (session_status() === PHP_SESSION_NONE) {
        // Avoid starting if headers already sent? 
        // Best to assume session is handled by Auth::startSession() mainly, 
        // but this is safe fallback if called early.
        @session_start();
    }
    return $_SESSION['lang'] ?? 'en';
}

/**
 * Translate a key
 */
function __($key) {
    global $lang_strings;
    
    // Load strings if not loaded
    if (!isset($lang_strings)) {
        $lang = get_current_lang();
        $lang_file = dirname(__DIR__) . "/lang/{$lang}.php";
        if (file_exists($lang_file)) {
            $lang_strings = require $lang_file;
        } else {
            $lang_strings = [];
        }
    }

    // Return translation or key if not found
    return $lang_strings[$key] ?? $key;
}

/**
 * Generate unique 12-digit numeric SKU with valid Check Digit
 * 
 * @param PDO $db The database connection
 * @param array $extra_excludes Additional SKUs to exclude (e.g., from current batch)
 * @return string The generated SKU
 */
function generateSKU($db, $extra_excludes = []) {
    // Increase entropy: Use last 5 digits of timestamp + 6 random digits
    // This provides 1,000,000 possible SKUs per 100,000 seconds (approx 27.7 hours)
    $timestamp_part = substr(time(), -5); 
    $random_part = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $code11 = $timestamp_part . $random_part;
    
    // Calculate UPC/EAN-13 Check Digit
    $sum = 0;
    for ($i = 0; $i < 11; $i++) {
        if (($i + 1) % 2 != 0) { // Odd position (1-based index)
            $sum += (int)$code11[$i] * 3;
        } else {
            $sum += (int)$code11[$i];
        }
    }
    $mod = $sum % 10;
    $checkDigit = ($mod == 0) ? 0 : (10 - $mod);
    
    $sku = $code11 . $checkDigit;
    
    // Check if in current batch list
    if (in_array($sku, $extra_excludes)) {
        return generateSKU($db, $extra_excludes);
    }

    // Check uniqueness in products table
    $stmt = $db->prepare("SELECT id FROM products WHERE sku = ?");
    $stmt->execute([$sku]);
    if ($stmt->fetch()) return generateSKU($db, $extra_excludes);
    
    // Check uniqueness in variants table
    $stmt_v = $db->prepare("SELECT id FROM product_variants WHERE sku = ?");
    $stmt_v->execute([$sku]);
    if ($stmt_v->fetch()) return generateSKU($db, $extra_excludes);
    
    return $sku;
}

