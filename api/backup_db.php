<?php
require_once dirname(__DIR__) . '/config/paths.php';
require_once CONFIG_PATH . 'database.php';
require_once CONFIG_PATH . 'auth.php';

// Check if logged in and is admin
if (!Auth::isLoggedIn() || Auth::getCurrentUser()['role'] !== 'admin') {
    http_response_code(403);
    echo "Unauthorized access.";
    exit();
}

// Disable error reporting for cleaner output in file
error_reporting(0);
ini_set('display_errors', 0);

// Set time and memory limit for large databases
set_time_limit(600);
ini_set('memory_limit', '512M');

// Get credentials
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'inventory_system';

try {
    $db_obj = new Database();
    $db = $db_obj->getConnection();
    $db->exec("SET NAMES utf8mb4");

    $tables = array();
    $stmt = $db->query('SHOW TABLES');
    while($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    // Create a temporary file for the SQL
    $tmp_sql_file = tempnam(sys_get_temp_dir(), 'db_sql');
    $sql_handle = fopen($tmp_sql_file, 'w');

    fwrite($sql_handle, "-- Inventory Management System Database Backup\n");
    fwrite($sql_handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
    fwrite($sql_handle, "SET FOREIGN_KEY_CHECKS=0;\n");
    fwrite($sql_handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($sql_handle, "SET time_zone = \"+00:00\";\n\n");

    foreach($tables as $table) {
        fwrite($sql_handle, "-- Structure for table `$table` --\n");
        fwrite($sql_handle, "DROP TABLE IF EXISTS `$table`;\n");
        
        $create_stmt = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        fwrite($sql_handle, $create_stmt[1] . ";\n\n");

        fwrite($sql_handle, "-- Dumping data for table `$table` --\n");
        $data_stmt = $db->query("SELECT * FROM `$table`", PDO::FETCH_ASSOC);
        
        while($row = $data_stmt->fetch()) {
            $keys = array_keys($row);
            $values = array_values($row);
            
            $escaped_values = array_map(function($v) use ($db) {
                if ($v === null) return 'NULL';
                return $db->quote($v);
            }, $values);
            
            fwrite($sql_handle, "INSERT INTO `$table` VALUES(" . implode(',', $escaped_values) . ");\n");
        }
        fwrite($sql_handle, "\n\n");
    }

    fwrite($sql_handle, "SET FOREIGN_KEY_CHECKS=1;");
    fclose($sql_handle);

    $filename_zip = 'backup_' . $name . '_' . date('Y-m-d_H_i_s') . '.zip';
    
    // Create ZIP archive
    $zip = new ZipArchive();
    $tmp_file = tempnam(sys_get_temp_dir(), 'db_zip');

    if ($zip->open($tmp_file, ZipArchive::CREATE) !== TRUE) {
        // Fallback to plain SQL if ZIP creation fails
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . str_replace('.zip', '.sql', $filename_zip) . '"');
        readfile($tmp_sql_file);
        unlink($tmp_sql_file);
        exit();
    }

    // Add SQL file to ZIP
    $zip->addFile($tmp_sql_file, 'database_backup.sql');

    // Add uploads folder to ZIP
    $uploads_path = realpath(dirname(__DIR__) . '/uploads');
    if ($uploads_path && is_dir($uploads_path)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploads_path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $file_path = $file->getRealPath();
                $relative_path = 'uploads/' . str_replace('\\', '/', substr($file_path, strlen($uploads_path) + 1));

                // Add current file to archive
                $zip->addFile($file_path, $relative_path);
            }
        }
    }

    $zip->close();

    // Send ZIP file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename_zip . '"');
    header('Content-Length: ' . filesize($tmp_file));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    readfile($tmp_file);
    unlink($tmp_file); // Delete the temporary ZIP file
    unlink($tmp_sql_file); // Delete the temporary SQL file
    exit();

} catch (Exception $e) {
    http_response_code(500);
    echo "Error generating backup: " . $e->getMessage();
    exit();
}
