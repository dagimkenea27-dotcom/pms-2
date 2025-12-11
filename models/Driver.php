<?php
/**
 * Driver Model
 * Manages driver information, schedules, and availability
 */

class Driver {
    private $conn;
    private $table = 'drivers';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all drivers
     */
    public function getAll($status = null) {
        $sql = "SELECT d.*, u.username, u.email as user_email 
                FROM {$this->table} d
                LEFT JOIN users u ON d.user_id = u.id";
        
        if ($status) {
            $sql .= " WHERE d.status = :status";
        }
        
        $sql .= " ORDER BY d.full_name ASC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * Get driver by ID
     */
    public function getById($id) {
        $sql = "SELECT d.*, u.username, u.email as user_email 
                FROM {$this->table} d
                LEFT JOIN users u ON d.user_id = u.id
                WHERE d.id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new driver
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, full_name, email, phone, license_number, license_expiry, 
                 status, skills, max_working_hours, hourly_rate)
                VALUES 
                (:user_id, :full_name, :email, :phone, :license_number, :license_expiry,
                 :status, :skills, :max_working_hours, :hourly_rate)";
        
        $stmt = $this->conn->prepare($sql);
        
        $skills_json = isset($data['skills']) ? json_encode($data['skills']) : null;
        
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':license_number', $data['license_number']);
        $stmt->bindParam(':license_expiry', $data['license_expiry']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':skills', $skills_json);
        $stmt->bindParam(':max_working_hours', $data['max_working_hours']);
        $stmt->bindParam(':hourly_rate', $data['hourly_rate']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update driver
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                full_name = :full_name,
                email = :email,
                phone = :phone,
                license_number = :license_number,
                license_expiry = :license_expiry,
                status = :status,
                skills = :skills,
                max_working_hours = :max_working_hours,
                hourly_rate = :hourly_rate
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        
        $skills_json = isset($data['skills']) ? json_encode($data['skills']) : null;
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':license_number', $data['license_number']);
        $stmt->bindParam(':license_expiry', $data['license_expiry']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':skills', $skills_json);
        $stmt->bindParam(':max_working_hours', $data['max_working_hours']);
        $stmt->bindParam(':hourly_rate', $data['hourly_rate']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete driver
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Check if driver is available on a specific date
     */
    public function isAvailable($driver_id, $date) {
        $sql = "SELECT is_available FROM driver_schedules 
                WHERE driver_id = :driver_id AND date = :date";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':driver_id', $driver_id);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no schedule exists, assume available
        if (!$result) {
            return true;
        }
        
        return (bool)$result['is_available'];
    }
    
    /**
     * Get driver schedule for a date range
     */
    public function getSchedule($driver_id, $start_date, $end_date) {
        $sql = "SELECT * FROM driver_schedules 
                WHERE driver_id = :driver_id 
                AND date BETWEEN :start_date AND :end_date
                ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':driver_id', $driver_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Set driver schedule
     */
    public function setSchedule($data) {
        $sql = "INSERT INTO driver_schedules 
                (driver_id, date, start_time, end_time, break_start, break_end, is_available, notes)
                VALUES 
                (:driver_id, :date, :start_time, :end_time, :break_start, :break_end, :is_available, :notes)
                ON DUPLICATE KEY UPDATE
                start_time = :start_time,
                end_time = :end_time,
                break_start = :break_start,
                break_end = :break_end,
                is_available = :is_available,
                notes = :notes";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':driver_id', $data['driver_id']);
        $stmt->bindParam(':date', $data['date']);
        $stmt->bindParam(':start_time', $data['start_time']);
        $stmt->bindParam(':end_time', $data['end_time']);
        $stmt->bindParam(':break_start', $data['break_start']);
        $stmt->bindParam(':break_end', $data['break_end']);
        $stmt->bindParam(':is_available', $data['is_available']);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    /**
     * Get driver performance metrics
     */
    public function getPerformance($driver_id, $start_date = null, $end_date = null) {
        $sql = "SELECT 
                COUNT(*) as total_routes,
                SUM(deliveries_completed) as total_deliveries,
                SUM(deliveries_failed) as total_failed,
                AVG(on_time_percentage) as avg_on_time,
                AVG(customer_rating) as avg_rating,
                SUM(actual_distance) as total_distance,
                SUM(fuel_cost) as total_fuel_cost,
                SUM(total_cost) as total_cost
                FROM route_performance
                WHERE driver_id = :driver_id";
        
        if ($start_date && $end_date) {
            $sql .= " AND completed_at BETWEEN :start_date AND :end_date";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':driver_id', $driver_id);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get available drivers for a specific date
     */
    public function getAvailableDrivers($date) {
        $sql = "SELECT d.* FROM {$this->table} d
                LEFT JOIN driver_schedules ds ON d.id = ds.driver_id AND ds.date = :date
                WHERE d.status = 'active'
                AND (ds.is_available IS NULL OR ds.is_available = 1)
                ORDER BY d.full_name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        return $stmt;
    }
}
