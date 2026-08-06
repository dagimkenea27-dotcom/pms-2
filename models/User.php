<?php
// models/User.php
class User
{
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $role;
    public $is_active;
    public $last_login;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create new user
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                SET username=:username, email=:email, password_hash=:password_hash,
                first_name=:first_name, last_name=:last_name, role=:role, is_active=0";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));

        // Hash password
        $this->password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":role", $this->role);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Check if username exists
    public function usernameExists()
    {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Check if email exists
    public function emailExists()
    {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Login user
    public function login()
    {
        $query = "SELECT id, username, password_hash, role, first_name, last_name 
                  FROM " . $this->table_name . " 
                  WHERE username = ? AND is_active = 1 LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($this->password, $row['password_hash'])) {
                $this->id = $row['id'];
                $this->role = $row['role'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];

                // Update last login
                $this->updateLastLogin();

                return true;
            }
            else {
                // Brute-force protection: delay on failure
                sleep(1);
            }
        }
        else {
            // Delay if user doesn't exist to prevent timing attacks
            sleep(1);
        }
        return false;
    }

    // Update last login
    private function updateLastLogin()
    {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
    }

    // Get all users
    public function read()
    {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get single user
    public function readOne()
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->role = $row['role'];
            $this->is_active = $row['is_active'];
            return true;
        }
        return false;
    }

    // Update user
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                SET username=:username, email=:email, first_name=:first_name, 
                last_name=:last_name, role=:role, is_active=:is_active";

        // Add password to query if provided
        if (!empty($this->password)) {
            $query .= ", password_hash=:password_hash";
        }

        $query .= " WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));

        // Bind parameters
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":id", $this->id);

        // Bind password if provided
        if (!empty($this->password)) {
            $this->password_hash = password_hash($this->password, PASSWORD_DEFAULT);
            $stmt->bindParam(":password_hash", $this->password_hash);
        }

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete user
    public function delete()
    {
        // Don't allow deleting the last admin
        if ($this->isLastAdmin()) {
            return false;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Check if this is the last admin user
    private function isLastAdmin()
    {
        $query = "SELECT COUNT(*) as admin_count FROM " . $this->table_name . " WHERE role = 'admin' AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($result['admin_count'] <= 1 && $this->role == 'admin');
    }

    // Get user's full name
    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
?>