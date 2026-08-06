<?php
/**
 * Customer Model
 * Manages customer information and addresses
 */

class Customer {
    private $conn;
    private $table = 'customers';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all customers
     */
    public function getAll() {
        $sql = "SELECT c.*, ca.address_line1, ca.city 
                FROM {$this->table} c
                LEFT JOIN customer_addresses ca ON c.default_address_id = ca.id
                ORDER BY c.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * Get customer by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new customer
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (customer_code, name, email, phone, company_name, notification_preference, notes)
                VALUES 
                (:customer_code, :name, :email, :phone, :company_name, :notification_preference, :notes)";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':customer_code', $data['customer_code']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':company_name', $data['company_name']);
        $stmt->bindParam(':notification_preference', $data['notification_preference']);
        $stmt->bindParam(':notes', $data['notes']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update customer
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                name = :name,
                email = :email,
                phone = :phone,
                company_name = :company_name,
                notification_preference = :notification_preference,
                notes = :notes
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':company_name', $data['company_name']);
        $stmt->bindParam(':notification_preference', $data['notification_preference']);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete customer
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Get customer addresses
     */
    public function getAddresses($customer_id) {
        $sql = "SELECT * FROM customer_addresses 
                WHERE customer_id = :customer_id
                ORDER BY is_default DESC, address_label ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Add customer address
     */
    public function addAddress($data) {
        // If this is set as default, unset other defaults
        if ($data['is_default']) {
            $sql = "UPDATE customer_addresses SET is_default = 0 WHERE customer_id = :customer_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':customer_id', $data['customer_id']);
            $stmt->execute();
        }
        
        $sql = "INSERT INTO customer_addresses 
                (customer_id, address_label, address_line1, address_line2, city, state, 
                 postal_code, country, latitude, longitude, special_instructions, is_default)
                VALUES 
                (:customer_id, :address_label, :address_line1, :address_line2, :city, :state,
                 :postal_code, :country, :latitude, :longitude, :special_instructions, :is_default)";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':customer_id', $data['customer_id']);
        $stmt->bindParam(':address_label', $data['address_label']);
        $stmt->bindParam(':address_line1', $data['address_line1']);
        $stmt->bindParam(':address_line2', $data['address_line2']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':state', $data['state']);
        $stmt->bindParam(':postal_code', $data['postal_code']);
        $stmt->bindParam(':country', $data['country']);
        $stmt->bindParam(':latitude', $data['latitude']);
        $stmt->bindParam(':longitude', $data['longitude']);
        $stmt->bindParam(':special_instructions', $data['special_instructions']);
        $stmt->bindParam(':is_default', $data['is_default']);
        
        if ($stmt->execute()) {
            $address_id = $this->conn->lastInsertId();
            
            // Update customer's default address if this is default
            if ($data['is_default']) {
                $sql = "UPDATE {$this->table} SET default_address_id = :address_id WHERE id = :customer_id";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':address_id', $address_id);
                $stmt->bindParam(':customer_id', $data['customer_id']);
                $stmt->execute();
            }
            
            return $address_id;
        }
        
        return false;
    }
    
    /**
     * Search customers
     */
    public function search($query) {
        $sql = "SELECT c.*, ca.address_line1, ca.city 
                FROM {$this->table} c
                LEFT JOIN customer_addresses ca ON c.default_address_id = ca.id
                WHERE c.name LIKE :query 
                OR c.email LIKE :query 
                OR c.phone LIKE :query
                OR c.customer_code LIKE :query
                ORDER BY c.name ASC
                LIMIT 50";
        
        $stmt = $this->conn->prepare($sql);
        $search_term = "%{$query}%";
        $stmt->bindParam(':query', $search_term);
        $stmt->execute();
        
        return $stmt;
    }
}
