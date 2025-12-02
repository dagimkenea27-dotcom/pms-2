<?php
// Define absolute paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// Calculate BASE_URL automatically
$project_root = str_replace('\\', '/', realpath(ROOT_PATH));
$doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$base_url = str_replace($doc_root, '', $project_root);
define('BASE_URL', $base_url . '/');
?>