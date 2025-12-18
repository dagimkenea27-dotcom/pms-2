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
            'id' => $row['id'],
            'message' => htmlspecialchars($row['message']),
            'type' => $row['type'],
            'link' => $row['link'] ? BASE_URL . $row['link'] : '#',
            'is_read' => $row['is_read'],
            'date' => date('F j, Y', strtotime($row['created_at']))
        ];
    }

    // Track notifications in session to avoid duplicates
    if (!isset($_SESSION['notifications_last_seen'])) {
        $_SESSION['notifications_last_seen'] = [];
    }
    
    // Filter out notifications that have been seen in this session
    $filtered_notifications = [];
    foreach ($notifications as $notif) {
        // Only include if not seen in current session
        if (!in_array($notif['id'], $_SESSION['notifications_last_seen'])) {
            $filtered_notifications[] = $notif;
        }
    }
    
    // Update session with all notification IDs we're sending
    foreach ($notifications as $notif) {
        if (!in_array($notif['id'], $_SESSION['notifications_last_seen'])) {
            $_SESSION['notifications_last_seen'][] = $notif['id'];
        }
    }
    
    // Limit session storage to prevent it from growing too large
    if (count($_SESSION['notifications_last_seen']) > 50) {
        $_SESSION['notifications_last_seen'] = array_slice($_SESSION['notifications_last_seen'], -50);
    }

    echo json_encode([
        'count' => $count,
        'notifications' => $filtered_notifications
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
