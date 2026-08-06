<?php
// products/bulk_operations.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/AuditLog.php";

// Only managers can perform bulk operations
Auth::requireRole('manager');

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$audit = new AuditLog($db);

// Get current user ID from session
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // If no user in session, try to get first admin user as fallback
    $stmt = $db->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $admin['id'] ?? 1; // Fallback to ID 1 if no admin found
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? '';
$product_ids = json_decode($_POST['product_ids'] ?? '[]', true);

if (empty($product_ids) || !is_array($product_ids)) {
    $response['message'] = 'No products selected';
    echo json_encode($response);
    exit();
}

// Sanitize product IDs
$product_ids = array_map('intval', $product_ids);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));

try {
    switch ($action) {
        case 'bulk_edit':
            $db->beginTransaction();
            
            $updates = [];
            $params = [];
            
            // Category
            if (!empty($_POST['category_id'])) {
                // Get category name
                $cat_stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
                $cat_stmt->execute([$_POST['category_id']]);
                $cat_name = $cat_stmt->fetchColumn();
                
                $updates[] = "category_id = ?";
                $updates[] = "category = ?";
                $params[] = $_POST['category_id'];
                $params[] = $cat_name;
            }
            
            // Brand
            if (!empty($_POST['brand_id'])) {
                $updates[] = "brand_id = ?";
                $params[] = $_POST['brand_id'];
            }
            
            // Supplier
            if (!empty($_POST['supplier_id'])) {
                $updates[] = "supplier_id = ?";
                $params[] = $_POST['supplier_id'];
            }
            
            // Location
            if (!empty($_POST['location'])) {
                $updates[] = "location = ?";
                $params[] = $_POST['location'];
            }
            
            // Price adjustment
            if (!empty($_POST['price_type']) && !empty($_POST['price_value'])) {
                $price_value = floatval($_POST['price_value']);
                switch ($_POST['price_type']) {
                    case 'increase_percent':
                        $updates[] = "price = price * (1 + ? / 100)";
                        $params[] = $price_value;
                        break;
                    case 'decrease_percent':
                        $updates[] = "price = price * (1 - ? / 100)";
                        $params[] = $price_value;
                        break;
                    case 'set_price':
                        $updates[] = "price = ?";
                        $params[] = $price_value;
                        break;
                }
            }
            
            if (empty($updates)) {
                $response['message'] = 'No fields to update';
                echo json_encode($response);
                exit();
            }
            
            $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id IN ($placeholders)";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge($params, $product_ids));
            
            // Log the action
            $audit->log($user_id, 'bulk_edit_products', 'Bulk edited ' . count($product_ids) . ' products');
            
            $db->commit();
            $response['success'] = true;
            $response['message'] = 'Successfully updated ' . count($product_ids) . ' product(s)';
            break;
            
        case 'bulk_stock':
            $db->beginTransaction();
            
            $operation = $_POST['operation'] ?? '';
            $quantity = intval($_POST['quantity'] ?? 0);
            $notes = $_POST['notes'] ?? 'Bulk stock update';
            
            if ($quantity < 0) {
                $response['message'] = 'Quantity cannot be negative';
                echo json_encode($response);
                exit();
            }
            
            foreach ($product_ids as $product_id) {
                // Get current quantity
                $stmt = $db->prepare("SELECT quantity, name FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) continue;
                
                $old_qty = $product['quantity'];
                $new_qty = $old_qty;
                
                switch ($operation) {
                    case 'add':
                        $new_qty = $old_qty + $quantity;
                        $movement_type = 'IN';
                        break;
                    case 'subtract':
                        $new_qty = max(0, $old_qty - $quantity);
                        $movement_type = 'OUT';
                        break;
                    case 'set':
                        $new_qty = $quantity;
                        $movement_type = $quantity > $old_qty ? 'IN' : 'OUT';
                        break;
                }
                
                // Update product quantity
                $update_stmt = $db->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                $update_stmt->execute([$new_qty, $product_id]);
                
                // Record stock movement
                $movement_qty = abs($new_qty - $old_qty);
                if ($movement_qty > 0) {
                    $movement_stmt = $db->prepare("
                        INSERT INTO stock_movements (product_id, movement_type, quantity, reason, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $movement_stmt->execute([$product_id, $movement_type, $movement_qty, $notes]);
                }
            }
            
            $audit->log($user_id, 'bulk_stock_update', 'Bulk stock update for ' . count($product_ids) . ' products');
            
            $db->commit();
            $response['success'] = true;
            $response['message'] = 'Successfully updated stock for ' . count($product_ids) . ' product(s)';
            break;
            
        case 'bulk_delete':
            $db->beginTransaction();
            
            // Get product images for deletion
            $stmt = $db->prepare("SELECT id, image, name FROM products WHERE id IN ($placeholders)");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Delete products
            $delete_stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
            $delete_stmt->execute($product_ids);
            
            // Delete images
            foreach ($products as $product) {
                if (!empty($product['image']) && file_exists("../" . $product['image'])) {
                    unlink("../" . $product['image']);
                }
            }
            
            $audit->log($user_id, 'bulk_delete_products', 'Bulk deleted ' . count($product_ids) . ' products');
            
            $db->commit();
            $response['success'] = true;
            $response['message'] = 'Successfully deleted ' . count($products) . ' product(s)';
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
