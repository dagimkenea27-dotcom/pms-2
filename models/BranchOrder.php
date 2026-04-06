<?php
/**
 * BranchOrder Model
 * Manages branch order receiving activities
 */

class BranchOrder {
    private $conn;
    private $table = 'branch_orders';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Generate unique order number
     */
    public function generateOrderNumber() {
        $prefix = 'BO-';
        $date = date('Ymd');
        
        // Get last order number for today
        $sql = "SELECT order_number FROM {$this->table} 
                WHERE order_number LIKE :prefix 
                ORDER BY id DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $search_prefix = $prefix . $date . '%';
        $stmt->bindParam(':prefix', $search_prefix);
        $stmt->execute();
        
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last) {
            // Extract sequence number
            $last_num = intval(substr($last['order_number'], strlen($prefix . $date)));
            $new_num = $last_num + 1;
        } else {
            $new_num = 1;
        }
        
        return $prefix . $date . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create new branch order
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (order_number, customer_name, product_id, variant_id, product_name, 
                 product_color, product_size, quantity, option_available, address, 
                 phone_number, status, notes, created_by)
                VALUES 
                (:order_number, :customer_name, :product_id, :variant_id, :product_name,
                 :product_color, :product_size, :quantity, :option_available, :address,
                 :phone_number, :status, :notes, :created_by)";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':order_number', $data['order_number']);
        $stmt->bindParam(':customer_name', $data['customer_name']);
        $stmt->bindParam(':product_id', $data['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':variant_id', $data['variant_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_name', $data['product_name']);
        $stmt->bindParam(':product_color', $data['product_color']);
        $stmt->bindParam(':product_size', $data['product_size']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':option_available', $data['option_available']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':phone_number', $data['phone_number']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get order by ID
     */
    public function getById($id) {
        $sql = "SELECT bo.*, u.username as created_by_username
                FROM {$this->table} bo
                LEFT JOIN users u ON bo.created_by = u.id
                WHERE bo.id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get order by order number
     */
    public function getByOrderNumber($order_number) {
        $sql = "SELECT * FROM {$this->table} WHERE order_number = :order_number";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_number', $order_number);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all orders with pagination and filtering
     */
    public function getAll($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT bo.*, u.username as created_by_username
                FROM {$this->table} bo
                LEFT JOIN users u ON bo.created_by = u.id
                WHERE 1=1";
        
        $conditions = [];
        
        if (!empty($filters['status'])) {
            $conditions[] = "bo.status = :status";
        }
        
        if (!empty($filters['customer_name'])) {
            $conditions[] = "(bo.customer_name LIKE :customer_name OR bo.order_number LIKE :customer_name)";
        }
        
        if (!empty($filters['product_name'])) {
            $conditions[] = "bo.product_name LIKE :product_name";
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(bo.created_at) >= :date_from";
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(bo.created_at) <= :date_to";
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY bo.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        
        if (!empty($filters['customer_name'])) {
            $search_term = "%{$filters['customer_name']}%";
            $stmt->bindParam(':customer_name', $search_term);
        }
        
        if (!empty($filters['product_name'])) {
            $search_term = "%{$filters['product_name']}%";
            $stmt->bindParam(':product_name', $search_term);
        }
        
        if (!empty($filters['date_from'])) {
            $stmt->bindParam(':date_from', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $stmt->bindParam(':date_to', $filters['date_to']);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get total count with filters
     */
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        
        $conditions = [];
        
        if (!empty($filters['status'])) {
            $conditions[] = "status = :status";
        }
        
        if (!empty($filters['customer_name'])) {
            $conditions[] = "(customer_name LIKE :customer_name OR order_number LIKE :customer_name)";
        }
        
        if (!empty($filters['product_name'])) {
            $conditions[] = "product_name LIKE :product_name";
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(created_at) >= :date_from";
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(created_at) <= :date_to";
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        
        if (!empty($filters['customer_name'])) {
            $search_term = "%{$filters['customer_name']}%";
            $stmt->bindParam(':customer_name', $search_term);
        }
        
        if (!empty($filters['product_name'])) {
            $search_term = "%{$filters['product_name']}%";
            $stmt->bindParam(':product_name', $search_term);
        }
        
        if (!empty($filters['date_from'])) {
            $stmt->bindParam(':date_from', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $stmt->bindParam(':date_to', $filters['date_to']);
        }
        
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Update order status
     */
    public function updateStatus($id, $status) {
        $sql = "UPDATE {$this->table} SET status = :status WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        
        return $stmt->execute();
    }
    
    /**
     * Update order
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                customer_name = :customer_name,
                product_name = :product_name,
                product_color = :product_color,
                product_size = :product_size,
                quantity = :quantity,
                option_available = :option_available,
                address = :address,
                phone_number = :phone_number,
                notes = :notes
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':customer_name', $data['customer_name']);
        $stmt->bindParam(':product_name', $data['product_name']);
        $stmt->bindParam(':product_color', $data['product_color']);
        $stmt->bindParam(':product_size', $data['product_size']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':option_available', $data['option_available']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':phone_number', $data['phone_number']);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete order
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Get pending orders count
     */
    public function getPendingCount() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'pending'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
