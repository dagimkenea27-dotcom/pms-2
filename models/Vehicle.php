<?php
/**
 * Vehicle Model
 * Manages vehicle information, assignments, and maintenance
 */

class Vehicle {
    private $conn;
    private $table = 'vehicles';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all vehicles
     */
    public function getAll($status = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($status) {
            $sql .= " WHERE status = :status";
        }
        
        $sql .= " ORDER BY vehicle_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * Get vehicle by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new vehicle
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (vehicle_number, vehicle_type, make, model, year, 
                 capacity_weight, capacity_volume, fuel_type, fuel_efficiency,
                 status, last_maintenance_date, next_maintenance_date, insurance_expiry)
                VALUES 
                (:vehicle_number, :vehicle_type, :make, :model, :year,
                 :capacity_weight, :capacity_volume, :fuel_type, :fuel_efficiency,
                 :status, :last_maintenance_date, :next_maintenance_date, :insurance_expiry)";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':vehicle_number', $data['vehicle_number']);
        $stmt->bindParam(':vehicle_type', $data['vehicle_type']);
        $stmt->bindParam(':make', $data['make']);
        $stmt->bindParam(':model', $data['model']);
        $stmt->bindParam(':year', $data['year']);
        $stmt->bindParam(':capacity_weight', $data['capacity_weight']);
        $stmt->bindParam(':capacity_volume', $data['capacity_volume']);
        $stmt->bindParam(':fuel_type', $data['fuel_type']);
        $stmt->bindParam(':fuel_efficiency', $data['fuel_efficiency']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':last_maintenance_date', $data['last_maintenance_date']);
        $stmt->bindParam(':next_maintenance_date', $data['next_maintenance_date']);
        $stmt->bindParam(':insurance_expiry', $data['insurance_expiry']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update vehicle
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                vehicle_number = :vehicle_number,
                vehicle_type = :vehicle_type,
                make = :make,
                model = :model,
                year = :year,
                capacity_weight = :capacity_weight,
                capacity_volume = :capacity_volume,
                fuel_type = :fuel_type,
                fuel_efficiency = :fuel_efficiency,
                status = :status,
                last_maintenance_date = :last_maintenance_date,
                next_maintenance_date = :next_maintenance_date,
                insurance_expiry = :insurance_expiry
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':vehicle_number', $data['vehicle_number']);
        $stmt->bindParam(':vehicle_type', $data['vehicle_type']);
        $stmt->bindParam(':make', $data['make']);
        $stmt->bindParam(':model', $data['model']);
        $stmt->bindParam(':year', $data['year']);
        $stmt->bindParam(':capacity_weight', $data['capacity_weight']);
        $stmt->bindParam(':capacity_volume', $data['capacity_volume']);
        $stmt->bindParam(':fuel_type', $data['fuel_type']);
        $stmt->bindParam(':fuel_efficiency', $data['fuel_efficiency']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':last_maintenance_date', $data['last_maintenance_date']);
        $stmt->bindParam(':next_maintenance_date', $data['next_maintenance_date']);
        $stmt->bindParam(':insurance_expiry', $data['insurance_expiry']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete vehicle
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Check if vehicle is available on a specific date
     */
    public function isAvailable($vehicle_id, $date) {
        // Check if vehicle is assigned on this date
        $sql = "SELECT COUNT(*) as count FROM vehicle_assignments 
                WHERE vehicle_id = :vehicle_id 
                AND assignment_date = :date
                AND status IN ('scheduled', 'in_progress')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':vehicle_id', $vehicle_id);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] == 0;
    }
    
    /**
     * Get vehicle assignments
     */
    public function getAssignments($vehicle_id, $start_date = null, $end_date = null) {
        $sql = "SELECT va.*, d.full_name as driver_name, r.name as route_name
                FROM vehicle_assignments va
                LEFT JOIN drivers d ON va.driver_id = d.id
                LEFT JOIN routes r ON va.route_id = r.id
                WHERE va.vehicle_id = :vehicle_id";
        
        if ($start_date && $end_date) {
            $sql .= " AND va.assignment_date BETWEEN :start_date AND :end_date";
        }
        
        $sql .= " ORDER BY va.assignment_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':vehicle_id', $vehicle_id);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * Assign vehicle to driver and route
     */
    public function assign($data) {
        $sql = "INSERT INTO vehicle_assignments 
                (driver_id, vehicle_id, route_id, assignment_date, start_time, end_time, status)
                VALUES 
                (:driver_id, :vehicle_id, :route_id, :assignment_date, :start_time, :end_time, :status)";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':driver_id', $data['driver_id']);
        $stmt->bindParam(':vehicle_id', $data['vehicle_id']);
        $stmt->bindParam(':route_id', $data['route_id']);
        $stmt->bindParam(':assignment_date', $data['assignment_date']);
        $stmt->bindParam(':start_time', $data['start_time']);
        $stmt->bindParam(':end_time', $data['end_time']);
        $stmt->bindParam(':status', $data['status']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get available vehicles for a specific date
     */
    public function getAvailableVehicles($date) {
        $sql = "SELECT v.* FROM {$this->table} v
                WHERE v.status = 'active'
                AND v.id NOT IN (
                    SELECT vehicle_id FROM vehicle_assignments
                    WHERE assignment_date = :date
                    AND status IN ('scheduled', 'in_progress')
                )
                ORDER BY v.vehicle_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Get vehicles needing maintenance
     */
    public function getNeedingMaintenance() {
        $sql = "SELECT * FROM {$this->table}
                WHERE status = 'active'
                AND (
                    next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    OR insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                )
                ORDER BY next_maintenance_date ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Calculate remaining capacity
     */
    public function getRemainingCapacity($vehicle_id, $route_id = null) {
        $vehicle = $this->getById($vehicle_id);
        
        if (!$vehicle || !$route_id) {
            return [
                'weight' => $vehicle['capacity_weight'] ?? 0,
                'volume' => $vehicle['capacity_volume'] ?? 0
            ];
        }
        
        // Calculate used capacity from assigned packages
        $sql = "SELECT 
                COALESCE(SUM(dp.weight), 0) as used_weight,
                COALESCE(SUM(dp.volume), 0) as used_volume
                FROM delivery_packages dp
                INNER JOIN route_stops rs ON dp.route_stop_id = rs.id
                WHERE rs.route_id = :route_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':route_id', $route_id);
        $stmt->execute();
        
        $used = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'weight' => ($vehicle['capacity_weight'] ?? 0) - $used['used_weight'],
            'volume' => ($vehicle['capacity_volume'] ?? 0) - $used['used_volume']
        ];
    }
}
