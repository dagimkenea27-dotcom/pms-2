<?php
// users/toggle_status.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/User.php";
require_once "../config/auth.php";

// Only admin can access this page
Auth::requireRole('admin');

if (isset($_GET['id']) && isset($_GET['status'])) {
    // Validate CSRF token
    if (!isset($_GET['csrf_token']) || !Auth::validateCSRF($_GET['csrf_token'])) {
        $_SESSION['message'] = "Security error: Invalid CSRF token.";
        $_SESSION['message_type'] = 'danger';
        header("Location: view_users.php");
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    // Prevent SQL injection by using prepared statements in a quick update or using the model
    // Using direct query for simplicity of a single field toggle, but using bindings
    $query = "UPDATE users SET is_active = :status WHERE id = :id";
    $stmt = $db->prepare($query);

    $status = $_GET['status'] == 1 ? 1 : 0;
    $id = $_GET['id'];

    // Safety check: Don't deactivate yourself
    if ($id == $_SESSION['user_id'] && $status == 0) {
        $_SESSION['message'] = "Error: You cannot deactivate your own account.";
        $_SESSION['message_type'] = 'danger';
    }
    else {
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "User status updated successfully!";
            $_SESSION['message_type'] = 'success';
        }
        else {
            $_SESSION['message'] = "Error updating user status.";
            $_SESSION['message_type'] = 'danger';
        }
    }
}

header("Location: view_users.php");
exit();
?>
