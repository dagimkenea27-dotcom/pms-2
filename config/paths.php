<?php
// Define absolute paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// Calculate BASE_URL automatically
$project_root = str_replace('\\', '/', realpath(ROOT_PATH));
$doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';

if ($doc_root && stripos($project_root, $doc_root) === 0) {
    $base_url = substr($project_root, strlen($doc_root));
} else {
    // Fallback if doc_root is not a prefix (e.g. Aliases, Symlinks, Shared Hosts)
    $script_filename = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
    $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    
    // Find where the project root matches the script path (case-insensitive for Windows)
    $pos = stripos($script_filename, $project_root);
    if ($pos !== false) {
        $relative_to_root = substr($script_filename, $pos + strlen($project_root));
        
        // Strip this relative part from the end of SCRIPT_NAME to get BASE_URL
        if ($relative_to_root) {
            // Case-insensitive search for the relative part at the end of script_name
            $base_url = substr($script_name, 0, -strlen($relative_to_root));
        } else {
            $base_url = $script_name; // Should be the root script
        }
    } else {
        // Absolute fallback - can't determine, assume root or guess
        $base_url = '/';
    }
}

// Ensure it starts with / and ends with /
$base_url = '/' . trim($base_url, '/') . '/';
// If the project is in the root, base_url might become '//', fix it
if ($base_url === '//') $base_url = '/';

define('BASE_URL', $base_url);
?>