<?php
// api/get_notifications.php
require_once "../config/paths.php"; // Added to ensure BASE_URL is defined
require_once "../config/auth.php"; // Changed from auth_check.php
require_once "../config/database.php";
require_once "../models/Notification.php";

Auth::startSession(); // Manually start session since we're not using auth_check

header('Content-Type: application/json');

try {
    if (!Auth::isLoggedIn()) {
        echo json_encode(['count' => 0, 'html' => '']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    $notification = new Notification($db);
    $user_id = Auth::getCurrentUser()['id'];

    $count = $notification->countUnread($user_id);
    $recent = $notification->getRecent($user_id, 5);
    
    $notifications = [];
    while ($row = $recent->fetch(PDO::FETCH_ASSOC)) {
        $notifications[] = [
            'message' => htmlspecialchars($row['message']),
            'type' => $row['type'],
            'link' => $row['link'] ? BASE_URL . $row['link'] : '#',
            'is_read' => $row['is_read'],
            'date' => date('F j, Y', strtotime($row['created_at']))
        ];
    }

    echo json_encode([
        'count' => $count,
        'notifications' => $notifications
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
