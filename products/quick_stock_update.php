<?php
// products/quick_stock_update.php
require_once "../config/auth_check.php";
require_once "../config/database.php";

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $product_id = $_POST['product_id'];
        $movement_type = $_POST['movement_type'];
        $quantity = intval($_POST['quantity']);
        $reason = $_POST['reason'];
        
        // Update product quantity
        if ($movement_type == 'IN') {
            $update_query = "UPDATE products SET quantity = quantity + :quantity WHERE id = :id";
        } else {
            // Check if enough stock is available
            $check_query = "SELECT quantity FROM products WHERE id = :id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":id", $product_id);
            $check_stmt->execute();
            $current_quantity = $check_stmt->fetch(PDO::FETCH_ASSOC)['quantity'];
            
            if ($current_quantity < $quantity) {
                $_SESSION['message'] = "Error: Not enough stock available. Current stock: " . $current_quantity;
                $_SESSION['message_type'] = "danger";
                header("Location: edit_product.php?id=" . $product_id);
                exit();
            }
            
            $update_query = "UPDATE products SET quantity = quantity - :quantity WHERE id = :id";
        }
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":quantity", $quantity);
        $update_stmt->bindParam(":id", $product_id);
        
        if ($update_stmt->execute()) {
            // Log the stock movement
            $movement_query = "INSERT INTO stock_movements 
                              (product_id, movement_type, quantity, reason) 
                              VALUES (:product_id, :movement_type, :quantity, :reason)";
            $movement_stmt = $db->prepare($movement_query);
            $movement_stmt->bindParam(":product_id", $product_id);
            $movement_stmt->bindParam(":movement_type", $movement_type);
            $movement_stmt->bindParam(":quantity", $quantity);
            $movement_stmt->bindParam(":reason", $reason);
            $movement_stmt->execute();
            
            $_SESSION['message'] = "Stock updated successfully!";
            $_SESSION['message_type'] = "success";
        }
    } catch (PDOException $exception) {
        $_SESSION['message'] = "Error: " . $exception->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}

header("Location: edit_product.php?id=" . $product_id);
exit();
?>