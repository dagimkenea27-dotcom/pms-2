<?php
// api/mark_notification_read.php
require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../models/Notification.php";

Auth::startSession();

header('Content-Type: application/json');

try {
    if (!Auth::isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $notification_id = $data['notification_id'] ?? null;

    if (!$notification_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing notification_id']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    $notification = new Notification($db);
    $user_id = Auth::getCurrentUser()['id'];

    if ($notification->markAsRead($notification_id, $user_id)) {
        // Get updated count
        $unread_count = $notification->countUnread($user_id);
        
        echo json_encode([
            'success' => true,
            'unread_count' => $unread_count
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to mark as read']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
