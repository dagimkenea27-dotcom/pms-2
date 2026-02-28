<?php
// products/import_products.php
require_once "../config/auth_check.php";
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    $token = $_POST['csrf_token'] ?? '';
    if (!Auth::validateCSRF($token)) {
        $_SESSION['message'] = "Security error: Invalid CSRF token.";
        $_SESSION['message_type'] = "danger";
        header("Location: view_products.php");
        exit();
    }

    if (isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
    
    // Validate file type
    $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($fileType) != 'csv') {
        $_SESSION['message'] = "Invalid file type. Please upload a CSV file.";
        $_SESSION['message_type'] = "danger";
        header("Location: view_products.php");
        exit();
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error uploading file.";
        $_SESSION['message_type'] = "danger";
        header("Location: view_products.php");
        exit();
    }

    $handle = fopen($file['tmp_name'], "r");
    if ($handle === FALSE) {
        $_SESSION['message'] = "Error reading file.";
        $_SESSION['message_type'] = "danger";
        header("Location: view_products.php");
        exit();
    }

    // Skip BOM if present
    $bom = fread($handle, 3);
    if ($bom != "\xEF\xBB\xBF") {
        rewind($handle);
    }

    // Get headers
    $headers = fgetcsv($handle);
    if (!$headers) {
        $_SESSION['message'] = "Empty CSV file.";
        $_SESSION['message_type'] = "danger";
        header("Location: view_products.php");
        exit();
    }

    // Map headers to column indices (case-insensitive)
    $headerMap = [];
    foreach ($headers as $index => $header) {
        $headerMap[strtolower(trim($header))] = $index;
    }

    // Check required columns
    if (!isset($headerMap['sku']) || !isset($headerMap['name'])) {
        $_SESSION['message'] = "Missing required columns: SKU and Name are mandatory.";
        $_SESSION['message_type'] = "danger";
        header("Location: view_products.php");
        exit();
    }

    $imported = 0;
    $updated = 0;
    $errors = 0;
    $rowNum = 1;

    try {
        $db->beginTransaction();

        $checkStmt = $db->prepare("SELECT id FROM products WHERE sku = :sku");
        
        $insertStmt = $db->prepare("INSERT INTO products (sku, name, description, category, quantity, price, cost_price, min_stock, supplier, location) VALUES (:sku, :name, :description, :category, :quantity, :price, :cost_price, :min_stock, :supplier, :location)");
        
        $updateStmt = $db->prepare("UPDATE products SET name = :name, description = :description, category = :category, quantity = :quantity, price = :price, cost_price = :cost_price, min_stock = :min_stock, supplier = :supplier, location = :location WHERE sku = :sku");

        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNum++;
            
            // Helper to get value safely
            $getValue = function($key) use ($data, $headerMap) {
                return isset($headerMap[$key]) && isset($data[$headerMap[$key]]) ? trim($data[$headerMap[$key]]) : null;
            };

            $sku = $getValue('sku');
            $name = $getValue('name');
            
            if (empty($sku) || empty($name)) {
                $errors++;
                continue; // Skip invalid rows
            }

            $description = $getValue('description') ?? '';
            $category = $getValue('category') ?? 'Uncategorized';
            $quantity = (int)($getValue('quantity') ?? 0);
            $price = (float)($getValue('price') ?? 0.00);
            $cost_price = (float)($getValue('cost price') ?? 0.00); // Handle space in header
            if ($cost_price == 0.00) $cost_price = (float)($getValue('cost_price') ?? 0.00); // Try underscore too
            
            $min_stock = (int)($getValue('min stock') ?? 5);
            if ($min_stock == 5 && isset($headerMap['min_stock'])) $min_stock = (int)($getValue('min_stock') ?? 5);

            $supplier = $getValue('supplier') ?? '';
            $location = $getValue('location') ?? '';

            // Check if exists
            $checkStmt->execute([':sku' => $sku]);
            $exists = $checkStmt->fetch();

            $params = [
                ':sku' => $sku,
                ':name' => $name,
                ':description' => $description,
                ':category' => $category,
                ':quantity' => $quantity,
                ':price' => $price,
                ':cost_price' => $cost_price,
                ':min_stock' => $min_stock,
                ':supplier' => $supplier,
                ':location' => $location
            ];

            if ($exists) {
                // Update
                // Remove sku from params for update query as it's in WHERE clause, but we need it for binding
                // Actually my update query uses :sku in WHERE, so params are fine, just need to match order if using positional, but I'm using named.
                $updateStmt->execute($params);
                $updated++;
            } else {
                // Insert
                $insertStmt->execute($params);
                $imported++;
            }
        }

        $db->commit();
        fclose($handle);

        $_SESSION['message'] = "Import successful! Added: $imported, Updated: $updated. Errors/Skipped: $errors";
        $_SESSION['message_type'] = "success";

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['message'] = "Import failed: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }

    header("Location: view_products.php");
    exit();

}
?>
