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

// Set time limit for large databases
set_time_limit(300);

// Get credentials
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'inventory_system';

try {
    $mysqli = new mysqli($host, $user, $pass, $name);
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    $mysqli->set_charset("utf8mb4");

    $tables = array();
    $result = $mysqli->query('SHOW TABLES');
    while($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $sql = "-- Inventory Management System Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Host: " . $host . "\n";
    $sql .= "-- Database: " . $name . "\n\n";
    
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "SET time_zone = \"+00:00\";\n\n";

    foreach($tables as $table) {
        $result = $mysqli->query('SELECT * FROM `'.$table.'`');
        $num_fields = $result->field_count;

        $sql .= "-- \n-- Table structure for table `".$table."` --\n--\n\n";
        $sql .= 'DROP TABLE IF EXISTS `'.$table.'`;';
        $row2 = $mysqli->query('SHOW CREATE TABLE `'.$table.'`')->fetch_row();
        $sql .= "\n\n".$row2[1].";\n\n";

        $sql .= "-- \n-- Dumping data for table `".$table."` --\n--\n\n";
        
        while($row = $result->fetch_row()) {
            $sql .= 'INSERT INTO `'.$table.'` VALUES(';
            for($j=0; $j<$num_fields; $j++) {
                if (isset($row[$j])) {
                    $val = $mysqli->real_escape_string($row[$j]);
                    $sql .= '"'.$val.'"' ;
                } else {
                    $sql .= 'NULL';
                }
                if ($j<($num_fields-1)) { $sql .= ','; }
            }
            $sql .= ");\n";
        }
        $sql.="\n\n\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;";
    
    $mysqli->close();

    $filename_sql = 'database_backup.sql';
    $filename_zip = 'backup_' . $name . '_' . date('Y-m-d_H_i_s') . '.zip';
    
    // Create ZIP archive
    $zip = new ZipArchive();
    $tmp_file = tempnam(sys_get_temp_dir(), 'db_zip');

    if ($zip->open($tmp_file, ZipArchive::CREATE) !== TRUE) {
        // Fallback to plain SQL if ZIP creation fails
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . str_replace('.zip', '.sql', $filename_zip) . '"');
        echo $sql;
        exit();
    }

    // Add SQL file to ZIP
    $zip->addFromString($filename_sql, $sql);

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
    unlink($tmp_file); // Delete the temporary file
    exit();

} catch (Exception $e) {
    http_response_code(500);
    echo "Error generating backup: " . $e->getMessage();
    exit();
}
