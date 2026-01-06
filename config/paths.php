<?php
// Define absolute paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// Calculate BASE_URL automatically
$project_root = str_replace('\\', '/', realpath(ROOT_PATH));
$doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));

// Use case-insensitive replace for Windows environments
$base_url = str_ireplace($doc_root, '', $project_root);

// Ensure it starts with / and ends with /
$base_url = '/' . trim($base_url, '/') . '/';
// If the project is in the root, base_url might become '//', fix it
if ($base_url === '//') $base_url = '/';

define('BASE_URL', $base_url);
?>