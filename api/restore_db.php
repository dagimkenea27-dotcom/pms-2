<?php
// api/restore_db.php
require_once dirname(__DIR__) . '/config/paths.php';
require_once CONFIG_PATH . 'database.php';
require_once CONFIG_PATH . 'auth.php';

// Check if logged in and is admin
if (!Auth::isLoggedIn() || Auth::getCurrentUser()['role'] !== 'admin') {
    http_response_code(403);
    echo "Unauthorized access.";
    exit();
}

$message = "";
$status = "error";

// Increase limits for restoration
set_time_limit(600);
ini_set('memory_limit', '512M');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['backup_file'])) {
    $file = $_FILES['backup_file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

    if ($ext !== 'zip') {
        $message = "Only .zip files are allowed.";
    } else {
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) === TRUE) {
            $extract_path = sys_get_temp_dir() . '/restore_' . uniqid();
            if (!is_dir($extract_path)) mkdir($extract_path, 0777, true);
            
            $zip->extractTo($extract_path);
            $zip->close();

            $sql_file = $extract_path . '/database_backup.sql';
            $uploads_zip_path = $extract_path . '/uploads';

            try {
                $db = (new Database())->getConnection();
                
                // 1. Restore Database
                if (file_exists($sql_file)) {
                    $db->exec("SET FOREIGN_KEY_CHECKS=0;");
                    
                    $handle = fopen($sql_file, "r");
                    if ($handle) {
                        $templine = '';
                        while (($line = fgets($handle)) !== false) {
                            // Skip it if it's a comment
                            if (substr($line, 0, 2) == '--' || $line == '') continue;
                            
                            // Add this line to the current segment
                            $templine .= $line;
                            
                            // If it has a semicolon at the end, it's the end of the query
                            if (substr(trim($line), -1, 1) == ';') {
                                try {
                                    $db->exec($templine);
                                } catch (PDOException $e) {
                                    // Ignore errors like "Table already exists" if it happens, 
                                    // but usually DROP TABLE IF EXISTS is in the backup
                                }
                                // Reset temp variable to empty
                                $templine = '';
                            }
                        }
                        fclose($handle);
                    }
                    
                    $db->exec("SET FOREIGN_KEY_CHECKS=1;");
                } else {
                    throw new Exception("SQL backup file not found in the ZIP archive.");
                }

                // 2. Restore Uploads
                if (is_dir($uploads_zip_path)) {
                    $target_uploads = dirname(__DIR__) . '/uploads';
                    
                    // Recursive function to copy directory
                    function recurse_copy($src,$dst) {
                        $dir = opendir($src);
                        if (!is_dir($dst)) mkdir($dst, 0777, true);
                        while(false !== ( $file = readdir($dir)) ) {
                            if (( $file != '.' ) && ( $file != '..' )) {
                                if ( is_dir($src . '/' . $file) ) {
                                    recurse_copy($src . '/' . $file,$dst . '/' . $file);
                                }
                                else {
                                    copy($src . '/' . $file,$dst . '/' . $file);
                                }
                            }
                        }
                        closedir($dir);
                    }

                    recurse_copy($uploads_zip_path, $target_uploads);
                }

                $status = "success";
                $message = "System restored successfully!";

                // Clean up
                function rrmdir($dir) {
                    if (is_dir($dir)) {
                        $objects = scandir($dir);
                        foreach ($objects as $object) {
                            if ($object != "." && $object != "..") {
                                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                                    rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                                else
                                    unlink($dir. DIRECTORY_SEPARATOR .$object);
                            }
                        }
                        rmdir($dir);
                    }
                }
                rrmdir($extract_path);

            } catch (Exception $e) {
                $message = "Restore failed: " . $e->getMessage();
            }
        } else {
            $message = "Could not open ZIP file.";
        }
    }
} else {
    $message = "No file uploaded.";
}

header("Location: " . BASE_URL . "settings/backup.php?restore=" . $status . "&message=" . urlencode($message));
exit();
?>
