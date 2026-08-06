<?php
// models/TaxFeeConfig.php
class TaxFeeConfig {
    private $conn;
    private $table_name = "tax_fee_configs";

    public $id;
    public $name;
    public $description;
    public $rate_type;
    public $rate_value;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all active tax/fee configurations
    public function getAllActive() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE is_active = 1 
                  ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get configuration by ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = ? AND is_active = 1 
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->rate_type = $row['rate_type'];
            $this->rate_value = $row['rate_value'];
            $this->is_active = $row['is_active'];
            return true;
        }
        return false;
    }

    // Create new configuration
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET name=:name, description=:description, 
                  rate_type=:rate_type, rate_value=:rate_value, is_active=:is_active";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->rate_type = htmlspecialchars(strip_tags($this->rate_type));
        $this->rate_value = htmlspecialchars(strip_tags($this->rate_value));
        $this->is_active = $this->is_active ? 1 : 0;
        
        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":rate_type", $this->rate_type);
        $stmt->bindParam(":rate_value", $this->rate_value);
        $stmt->bindParam(":is_active", $this->is_active);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update configuration
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET name=:name, description=:description, 
                  rate_type=:rate_type, rate_value=:rate_value, is_active=:is_active
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->rate_type = htmlspecialchars(strip_tags($this->rate_type));
        $this->rate_value = htmlspecialchars(strip_tags($this->rate_value));
        $this->is_active = $this->is_active ? 1 : 0;
        
        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":rate_type", $this->rate_type);
        $stmt->bindParam(":rate_value", $this->rate_value);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":id", $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete configuration (soft delete)
    public function delete() {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = 0 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>