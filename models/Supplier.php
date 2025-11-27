<?php
// models/Supplier.php
class Supplier {
    private $conn;
    private $table_name = "suppliers";

    public $id;
    public $name;
    public $contact_person;
    public $email;
    public $phone;
    public $address;
    public $website;
    public $payment_terms;
    public $notes;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE is_active = 1 
                  ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET name=:name, contact_person=:contact_person, email=:email, 
                  phone=:phone, address=:address, website=:website, 
                  payment_terms=:payment_terms, notes=:notes";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->contact_person = htmlspecialchars(strip_tags($this->contact_person));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->website = htmlspecialchars(strip_tags($this->website));
        $this->payment_terms = htmlspecialchars(strip_tags($this->payment_terms));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        
        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":contact_person", $this->contact_person);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":website", $this->website);
        $stmt->bindParam(":payment_terms", $this->payment_terms);
        $stmt->bindParam(":notes", $this->notes);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET name=:name, contact_person=:contact_person, email=:email,
                  phone=:phone, address=:address, website=:website,
                  payment_terms=:payment_terms, notes=:notes
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->contact_person = htmlspecialchars(strip_tags($this->contact_person));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->website = htmlspecialchars(strip_tags($this->website));
        $this->payment_terms = htmlspecialchars(strip_tags($this->payment_terms));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        
        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":contact_person", $this->contact_person);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":website", $this->website);
        $stmt->bindParam(":payment_terms", $this->payment_terms);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":id", $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        // Soft delete - set is_active to false
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

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->name = $row['name'];
            $this->contact_person = $row['contact_person'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->website = $row['website'];
            $this->payment_terms = $row['payment_terms'];
            $this->notes = $row['notes'];
            return true;
        }
        return false;
    }

    public function getSupplierProducts() {
        $query = "SELECT p.id, p.sku, p.name, p.quantity, p.price 
                  FROM products p 
                  WHERE p.supplier_id = :supplier_id 
                  ORDER BY p.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":supplier_id", $this->id);
        $stmt->execute();
        return $stmt;
    }
}
?>