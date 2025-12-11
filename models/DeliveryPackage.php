<?php
/**
 * DeliveryPackage Model
 * Manages delivery packages, tracking, and proof of delivery
 */

class DeliveryPackage {
    private $conn;
    private $table = 'delivery_packages';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all packages
     */
    public function getAll($status = null) {
        $sql = "SELECT dp.*, c.name as customer_name, ca.address_line1, ca.city
                FROM {$this->table} dp
                LEFT JOIN customers c ON dp.customer_id = c.id
                LEFT JOIN customer_addresses ca ON dp.address_id = ca.id";
        
        if ($status) {
            $sql .= " WHERE dp.status = :status";
        }
        
        $sql .= " ORDER BY dp.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * Get package by ID
     */
    public function getById($id) {
        $sql = "SELECT dp.*, c.name as customer_name, c.email, c.phone,
                ca.address_line1, ca.address_line2, ca.city, ca.latitude, ca.longitude
                FROM {$this->table} dp
                LEFT JOIN customers c ON dp.customer_id = c.id
                LEFT JOIN customer_addresses ca ON dp.address_id = ca.id
                WHERE dp.id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get package by tracking number
     */
    public function getByTracking($tracking_number) {
        $sql = "SELECT dp.*, c.name as customer_name, c.email, c.phone,
                ca.address_line1, ca.address_line2, ca.city, ca.latitude, ca.longitude
                FROM {$this->table} dp
                LEFT JOIN customers c ON dp.customer_id = c.id
                LEFT JOIN customer_addresses ca ON dp.address_id = ca.id
                WHERE dp.tracking_number = :tracking_number";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tracking_number', $tracking_number);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new package
     */
    public function create($data) {
        // Generate tracking number if not provided
        if (empty($data['tracking_number'])) {
            $data['tracking_number'] = $this->generateTrackingNumber();
        }
        
        $sql = "INSERT INTO {$this->table} 
                (tracking_number, customer_id, address_id, weight, volume, dimensions,
                 package_type, priority, value, special_handling, status)
                VALUES 
                (:tracking_number, :customer_id, :address_id, :weight, :volume, :dimensions,
                 :package_type, :priority, :value, :special_handling, :status)";
        
        $stmt = $this->conn->prepare($sql);
        
        $special_handling_json = isset($data['special_handling']) ? json_encode($data['special_handling']) : null;
        
        $stmt->bindParam(':tracking_number', $data['tracking_number']);
        $stmt->bindParam(':customer_id', $data['customer_id']);
        $stmt->bindParam(':address_id', $data['address_id']);
        $stmt->bindParam(':weight', $data['weight']);
        $stmt->bindParam(':volume', $data['volume']);
        $stmt->bindParam(':dimensions', $data['dimensions']);
        $stmt->bindParam(':package_type', $data['package_type']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':value', $data['value']);
        $stmt->bindParam(':special_handling', $special_handling_json);
        $stmt->bindParam(':status', $data['status']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update package
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                customer_id = :customer_id,
                address_id = :address_id,
                weight = :weight,
                volume = :volume,
                dimensions = :dimensions,
                package_type = :package_type,
                priority = :priority,
                value = :value,
                special_handling = :special_handling,
                status = :status
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        
        $special_handling_json = isset($data['special_handling']) ? json_encode($data['special_handling']) : null;
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':customer_id', $data['customer_id']);
        $stmt->bindParam(':address_id', $data['address_id']);
        $stmt->bindParam(':weight', $data['weight']);
        $stmt->bindParam(':volume', $data['volume']);
        $stmt->bindParam(':dimensions', $data['dimensions']);
        $stmt->bindParam(':package_type', $data['package_type']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':value', $data['value']);
        $stmt->bindParam(':special_handling', $special_handling_json);
        $stmt->bindParam(':status', $data['status']);
        
        return $stmt->execute();
    }
    
    /**
     * Update package status
     */
    public function updateStatus($id, $status) {
        $sql = "UPDATE {$this->table} SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    }
    
    /**
     * Delete package
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Add time window
     */
    public function addTimeWindow($package_id, $data) {
        $sql = "INSERT INTO delivery_time_windows 
                (package_id, earliest_time, latest_time, preferred_date, is_flexible)
                VALUES 
                (:package_id, :earliest_time, :latest_time, :preferred_date, :is_flexible)";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':package_id', $package_id);
        $stmt->bindParam(':earliest_time', $data['earliest_time']);
        $stmt->bindParam(':latest_time', $data['latest_time']);
        $stmt->bindParam(':preferred_date', $data['preferred_date']);
        $stmt->bindParam(':is_flexible', $data['is_flexible']);
        
        return $stmt->execute();
    }
    
    /**
     * Add proof of delivery
     */
    public function addProof($data) {
        $sql = "INSERT INTO delivery_proof 
                (package_id, route_stop_id, delivered_by, delivery_time, recipient_name,
                 recipient_signature, photo_path, notes, gps_latitude, gps_longitude)
                VALUES 
                (:package_id, :route_stop_id, :delivered_by, :delivery_time, :recipient_name,
                 :recipient_signature, :photo_path, :notes, :gps_latitude, :gps_longitude)";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':package_id', $data['package_id']);
        $stmt->bindParam(':route_stop_id', $data['route_stop_id']);
        $stmt->bindParam(':delivered_by', $data['delivered_by']);
        $stmt->bindParam(':delivery_time', $data['delivery_time']);
        $stmt->bindParam(':recipient_name', $data['recipient_name']);
        $stmt->bindParam(':recipient_signature', $data['recipient_signature']);
        $stmt->bindParam(':photo_path', $data['photo_path']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':gps_latitude', $data['gps_latitude']);
        $stmt->bindParam(':gps_longitude', $data['gps_longitude']);
        
        if ($stmt->execute()) {
            // Update package status to delivered
            $this->updateStatus($data['package_id'], 'delivered');
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Generate unique tracking number
     */
    private function generateTrackingNumber() {
        $prefix = 'TRK';
        $timestamp = time();
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $timestamp . $random;
    }
    
    /**
     * Get packages by route
     */
    public function getByRoute($route_id) {
        $sql = "SELECT dp.*, c.name as customer_name, ca.address_line1
                FROM {$this->table} dp
                LEFT JOIN customers c ON dp.customer_id = c.id
                LEFT JOIN customer_addresses ca ON dp.address_id = ca.id
                INNER JOIN route_stops rs ON dp.route_stop_id = rs.id
                WHERE rs.route_id = :route_id
                ORDER BY rs.stop_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':route_id', $route_id);
        $stmt->execute();
        
        return $stmt;
    }
}
