<?php
// products/import_external.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/Category.php";
require_once "../models/Brand.php";
require_once "../includes/functions.php";

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $importData = [];
    
    // Check if it's a JSON post (from the frontend XLSX parser)
    if (isset($_POST['import_json'])) {
        $importData = json_decode($_POST['import_json'], true);
    } 
    // Otherwise check for CSV file upload
    elseif (isset($_FILES['external_csv'])) {
        $file = $_FILES['external_csv'];
        $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if (strtolower($fileType) == 'csv') {
            $handle = fopen($file['tmp_name'], "r");
            if ($handle !== FALSE) {
                // Skip BOM
                $bom = fread($handle, 3);
                if ($bom != "\xEF\xBB\xBF") rewind($handle);
                
                $headers = fgetcsv($handle);
                $headerMap = [];
                if ($headers) {
                    foreach ($headers as $index => $header) {
                        $headerMap[strtolower(trim($header))] = $index;
                    }
                    
                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $item = [];
                        foreach ($headerMap as $key => $index) {
                            $item[$key] = $row[$index] ?? null;
                        }
                        $importData[] = $item;
                    }
                }
                fclose($handle);
            }
        }
    }

    if (empty($importData)) {
        $_SESSION['message'] = "No data found to import.";
        $_SESSION['message_type'] = "danger";
        header("Location: view_products.php");
        exit();
    }

    $imported = 0;
    $updated = 0;
    $errors = 0;

    try {
        $db->beginTransaction();

        $checkStmt = $db->prepare("SELECT id, sku FROM products WHERE name = :name");
        
        $insertStmt = $db->prepare("INSERT INTO products 
            (sku, name, description, category, price, image, category_id, brand_id, status) 
            VALUES (:sku, :name, :description, :category, :price, :image, :category_id, :brand_id, :status)");
        
        $updateStmt = $db->prepare("UPDATE products SET 
            sku = :sku, 
            description = :description, 
            category = :category, 
            price = :price, 
            image = :image, 
            category_id = :category_id, 
            brand_id = :brand_id,
            status = :status 
            WHERE id = :id");

        $getCategoryId = function($name) use ($db) {
            if (empty($name)) return null;
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = :name");
            $stmt->execute([':name' => $name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row['id'];
            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            return $db->lastInsertId();
        };

        $getBrandId = function($name) use ($db) {
            if (empty($name) || $name == 'No Brand') return null;
            $stmt = $db->prepare("SELECT id FROM brands WHERE name = :name");
            $stmt->execute([':name' => $name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row['id'];
            $stmt = $db->prepare("INSERT INTO brands (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            return $db->lastInsertId();
        };

        $allKeysFound = [];
        $autoImageKey = null;
        if (!empty($importData)) {
            $allKeysFound = array_keys($importData[0]);
            
            // Pre-scan to identify columns by content (identify image column by looking for URLs)
            $firstRows = array_slice($importData, 0, 10);
            foreach ($firstRows as $row) {
                foreach ($row as $k => $v) {
                    $cleanVal = trim((string)$v);
                    if (preg_match('/^https?:\/\//i', $cleanVal) && preg_match('/\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i', $cleanVal)) {
                        $autoImageKey = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$k)));
                        break 2;
                    }
                }
            }
        }

        $batchExcludes = [];
        foreach ($importData as $index => $data) {
            // Ultra-robust key normalization
            $item = [];
            foreach ($data as $k => $v) {
                $cleanKey = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$k)));
                $item[$cleanKey] = $v;
            }
            
            // Flexible key lookup helper with content-match support
            $findValue = function($possibleKeys, $isName = false, $contentMatchKey = null) use ($item) {
                if ($contentMatchKey && isset($item[$contentMatchKey])) {
                    return trim((string)$item[$contentMatchKey]);
                }
                
                foreach ($possibleKeys as $rawKey) {
                    $key = preg_replace('/[^a-z0-9]/', '', strtolower($rawKey));
                    if (isset($item[$key]) && $item[$key] !== '') {
                        $val = trim((string)$item[$key]);
                        if ($isName) {
                            $isUrl = preg_match('/^https?:\/\//i', $val);
                            $isImg = preg_match('/\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i', $val);
                            if ($isUrl || $isImg) continue; 
                        }
                        return $val;
                    }
                }
                return null;
            };

            $name = $findValue(['product name', 'item name', 'product_name', 'name', 'title', 'item_name', 'item', 'product'], true);
            
            if ($index === 0 && ($name === 'Product Name' || $name === 'Name')) {
                continue; // Skip header row
            }

            if (empty($name)) {
                // Last ditch effort
                foreach ($item as $k => $v) {
                    if (is_string($v) && strlen($v) > 2 && !is_numeric($v)) {
                        $isUrl = preg_match('/^https?:\/\//i', $v);
                        $isImg = preg_match('/\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i', $v);
                        if (!$isUrl && !$isImg) {
                            $name = $v;
                            break;
                        }
                    }
                }
            }

            if (empty($name)) {
                $errors++;
                continue;
            }

            $sku = $findValue(['product sku', 'sku', 'product_sku', 'code', 'barcode', 'art', 'article', 'model']) ?? '';
            $description = $findValue(['description', 'desc', 'product description', 'details', 'summary', 'about']) ?? '';
            $categoryName = $findValue(['category name', 'category', 'category_name', 'dept', 'group', 'collection', 'type']) ?? 'Uncategorized';
            $brandName = $findValue(['brand', 'brand name', 'manufacturer', 'make', 'vendor', 'supplier']) ?? 'No Brand';
            $rawPrice = $findValue(['price', 'selling price', 'unit price', 'rate', 'cost', 'retail', 'sale price', 'regular price', 'current price']) ?? '0';
            $image = $findValue(['product image', 'image', 'img', 'product_image', 'image_url', 'photo', 'picture', 'thumbnail', 'media', 'gallery'], false, $autoImageKey) ?? '';
            $status = $findValue(['status', 'product status', 'availability', 'state', 'active', 'stock status']) ?? 'Active';

            $priceString = (string)$rawPrice;
            $price = (float)preg_replace('/[^\d.]/', '', $priceString);
            
            $categoryId = $getCategoryId($categoryName);
            $brandId = $getBrandId($brandName);

            // Check if exists by NAME
            $checkStmt->execute([':name' => (string)$name]);
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                // Use the SKU from file if provided, otherwise keep the existing one
                $finalSku = !empty($sku) ? (string)$sku : $exists['sku'];
                if (empty($finalSku)) $finalSku = generateSKU($db, $batchExcludes);
                
                // Determine if we should update the image (only if we found a new one)
                $finalImage = !empty($image) ? (string)$image : $exists['image'];
                
                $params = [
                    ':sku' => (string)$finalSku,
                    ':description' => (string)$description,
                    ':category' => (string)$categoryName,
                    ':price' => $price,
                    ':image' => (string)$finalImage,
                    ':category_id' => $categoryId,
                    ':brand_id' => $brandId,
                    ':status' => (string)$status,
                    ':id' => $exists['id']
                ];
                $updateStmt->execute($params);
                $updated++;
            } else {
                // For new products, generate a SKU if the file's SKU is empty or already exists
                $finalSku = (string)$sku;
                if (empty($finalSku)) {
                    $finalSku = generateSKU($db, $batchExcludes);
                } else {
                    $stmt_s = $db->prepare("SELECT id FROM products WHERE sku = ?");
                    $stmt_s->execute([$finalSku]);
                    if ($stmt_s->fetch() || in_array($finalSku, $batchExcludes)) {
                        $finalSku = generateSKU($db, $batchExcludes);
                    }
                }
                $batchExcludes[] = $finalSku;

                $params = [
                    ':sku' => (string)$finalSku,
                    ':name' => (string)$name,
                    ':description' => (string)$description,
                    ':category' => (string)$categoryName,
                    ':price' => $price,
                    ':image' => (string)$image,
                    ':category_id' => $categoryId,
                    ':brand_id' => $brandId,
                    ':status' => (string)$status
                ];
                $insertStmt->execute($params);
                $imported++;
            }
        }

        $db->commit();
        $msg = "External Import successful! Added: $imported, Updated: $updated. Skipped: $errors.";
        if ($imported == 0 && $updated == 0 && !empty($allKeysFound)) {
            $msg .= " Checked columns: " . implode(", ", $allKeysFound);
        }
        $_SESSION['message'] = $msg;
        $_SESSION['message_type'] = ($imported > 0 || $updated > 0) ? "success" : "warning";

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $_SESSION['message'] = "Import failed: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }

    header("Location: view_products.php");
    exit();

} else {
    header("Location: view_products.php");
    exit();
}
?>
