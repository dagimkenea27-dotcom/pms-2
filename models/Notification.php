<?php
class Notification {
    private $conn;
    private $table_name = "notifications";

    public $id;
    public $user_id;
    public $type;
    public $message;
    public $link;
    public $is_read;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create notification for a specific user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET user_id=:user_id, type=:type, message=:message, link=:link, is_read=0, created_at=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $this->message = htmlspecialchars(strip_tags($this->message));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->link = htmlspecialchars(strip_tags($this->link));
        
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":link", $this->link);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Create notification for all admins
    public function notifyAdmins($message, $link = null, $type = 'info') {
        // Get all admin IDs (case insensitive)
        $query = "SELECT id FROM users WHERE LOWER(role) = 'admin' AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $success = true;
        
        foreach ($admins as $admin) {
            $this->user_id = $admin['id'];
            $this->message = $message;
            $this->type = $type;
            $this->link = $link;
            if (!$this->create()) {
                $success = false;
            }
        }
        return $success;
    }

    // Get unread notifications for a user
    public function getUnread($user_id) {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE user_id = ? AND is_read = 0
                ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Get recent notifications (read and unread)
    public function getRecent($user_id, $limit = 5) {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE user_id = ?
                ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Count unread
    public function countUnread($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                WHERE user_id = ? AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    // Mark as read
    public function markAsRead($id, $user_id) {
        $query = "UPDATE " . $this->table_name . "
                SET is_read = 1
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $user_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    // Mark all as read
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table_name . "
                SET is_read = 1
                WHERE user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
