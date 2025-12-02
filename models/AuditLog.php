<?php
// models/AuditLog.php
class AuditLog {
    private $conn;
    private $table_name = "audit_logs";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Log an action
    public function log($user_id, $action, $details = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, action, details, created_at) 
                  VALUES (:user_id, :action, :details, NOW())";

        $stmt = $this->conn->prepare($query);

        $action = htmlspecialchars(strip_tags($action));
        $details = $details ? htmlspecialchars(strip_tags($details)) : null;

        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":details", $details);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read logs (for admin view)
    public function read($limit = 100) {
        $query = "SELECT a.*, u.username 
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.id
                  ORDER BY a.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }
}
?>
