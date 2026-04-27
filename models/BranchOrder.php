<?php
/**
 * BranchOrder Model
 * Manages branch order receiving activities with multi-item and multi-variant support
 */

class BranchOrder {
    private $conn;
    private $table = 'branch_orders';
    private $items_table = 'branch_order_items';
    private $variations_table = 'branch_order_variations';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Generate unique order number
     */
    public function generateOrderNumber() {
        $prefix = 'BO-';
        $date = date('Ymd');
        
        $sql = "SELECT order_number FROM {$this->table} 
                WHERE order_number LIKE :prefix 
                ORDER BY id DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $search_prefix = $prefix . $date . '%';
        $stmt->bindParam(':prefix', $search_prefix);
        $stmt->execute();
        
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last) {
            $last_num = intval(substr($last['order_number'], strlen($prefix . $date)));
            $new_num = $last_num + 1;
        } else {
            $new_num = 1;
        }
        
        return $prefix . $date . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create new branch order with nested items and variations
     */
    public function create($data, $products = []) {
        try {
            $this->conn->beginTransaction();

            // 1. Insert Parent Order
            $sql = "INSERT INTO {$this->table} 
                    (order_number, customer_name, address, phone_number, status, notes, created_by)
                    VALUES 
                    (:order_number, :customer_name, :address, :phone_number, :status, :notes, :created_by)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':order_number', $data['order_number']);
            $stmt->bindParam(':customer_name', $data['customer_name']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':phone_number', $data['phone_number']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':notes', $data['notes']);
            $stmt->bindParam(':created_by', $data['created_by']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert parent order.");
            }
            
            $order_id = $this->conn->lastInsertId();

            // 2. Insert Products and Variations
            foreach ($products as $product) {
                $sql_item = "INSERT INTO {$this->items_table} (order_id, product_name, image_path) 
                             VALUES (:order_id, :product_name, :image_path)";
                $stmt_item = $this->conn->prepare($sql_item);
                $stmt_item->bindParam(':order_id', $order_id);
                $stmt_item->bindParam(':product_name', $product['name']);
                $stmt_item->bindParam(':image_path', $product['image_path']);
                
                if (!$stmt_item->execute()) {
                    throw new Exception("Failed to insert product item.");
                }
                
                $item_id = $this->conn->lastInsertId();

                // 3. Insert Variations for this product
                if (!empty($product['variations'])) {
                    foreach ($product['variations'] as $v) {
                        $sql_v = "INSERT INTO {$this->variations_table} (item_id, color, size, quantity, options) 
                                  VALUES (:item_id, :color, :size, :quantity, :options)";
                        $stmt_v = $this->conn->prepare($sql_v);
                        $stmt_v->bindParam(':item_id', $item_id);
                        $stmt_v->bindParam(':color', $v['color']);
                        $stmt_v->bindParam(':size', $v['size']);
                        $stmt_v->bindParam(':quantity', $v['quantity']);
                        $stmt_v->bindParam(':options', $v['options']);
                        
                        if (!$stmt_v->execute()) {
                            throw new Exception("Failed to insert variation.");
                        }
                    }
                }
            }

            $this->conn->commit();
            return $order_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error creating branch order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get order by ID with all nested items and variations
     */
    public function getById($id) {
        // Get order details
        $sql = "SELECT bo.*, u.username as created_by_username
                FROM {$this->table} bo
                LEFT JOIN users u ON bo.created_by = u.id
                WHERE bo.id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) return false;

        // Get items
        $sql_items = "SELECT * FROM {$this->items_table} WHERE order_id = :order_id";
        $stmt_items = $this->conn->prepare($sql_items);
        $stmt_items->bindParam(':order_id', $id);
        $stmt_items->execute();
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        // Get variations for each item
        foreach ($items as &$item) {
            $sql_v = "SELECT * FROM {$this->variations_table} WHERE item_id = :item_id";
            $stmt_v = $this->conn->prepare($sql_v);
            $stmt_v->bindParam(':item_id', $item['id']);
            $stmt_v->execute();
            $item['variations'] = $stmt_v->fetchAll(PDO::FETCH_ASSOC);
        }

        $order['products'] = $items;
        return $order;
    }
    
    /**
     * Get all orders with summary of items
     */
    public function getAll($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT bo.*, u.username as created_by_username,
                (SELECT GROUP_CONCAT(product_name SEPARATOR ', ') FROM {$this->items_table} WHERE order_id = bo.id) as product_summary,
                (SELECT SUM(quantity) FROM {$this->variations_table} v JOIN {$this->items_table} i ON v.item_id = i.id WHERE i.order_id = bo.id) as total_quantity
                FROM {$this->table} bo
                LEFT JOIN users u ON bo.created_by = u.id
                WHERE 1=1";
        
        $conditions = [];
        if (!empty($filters['status'])) $conditions[] = "bo.status = :status";
        if (!empty($filters['customer_name'])) $conditions[] = "(bo.customer_name LIKE :customer_name OR bo.order_number LIKE :customer_name)";
        if (!empty($filters['date_from'])) $conditions[] = "DATE(bo.created_at) >= :date_from";
        if (!empty($filters['date_to'])) $conditions[] = "DATE(bo.created_at) <= :date_to";
        
        if (!empty($conditions)) $sql .= " AND " . implode(" AND ", $conditions);
        
        $sql .= " ORDER BY bo.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($filters['status'])) $stmt->bindParam(':status', $filters['status']);
        if (!empty($filters['customer_name'])) {
            $search_term = "%{$filters['customer_name']}%";
            $stmt->bindParam(':customer_name', $search_term);
        }
        if (!empty($filters['date_from'])) $stmt->bindParam(':date_from', $filters['date_from']);
        if (!empty($filters['date_to'])) $stmt->bindParam(':date_to', $filters['date_to']);
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $conditions = [];
        if (!empty($filters['status'])) $conditions[] = "status = :status";
        if (!empty($filters['customer_name'])) $conditions[] = "(customer_name LIKE :customer_name OR order_number LIKE :customer_name)";
        if (!empty($filters['date_from'])) $conditions[] = "DATE(created_at) >= :date_from";
        if (!empty($filters['date_to'])) $conditions[] = "DATE(created_at) <= :date_to";
        
        if (!empty($conditions)) $sql .= " AND " . implode(" AND ", $conditions);
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($filters['status'])) $stmt->bindParam(':status', $filters['status']);
        if (!empty($filters['customer_name'])) {
            $search_term = "%{$filters['customer_name']}%";
            $stmt->bindParam(':customer_name', $search_term);
        }
        if (!empty($filters['date_from'])) $stmt->bindParam(':date_from', $filters['date_from']);
        if (!empty($filters['date_to'])) $stmt->bindParam(':date_to', $filters['date_to']);
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    public function updateStatus($id, $status, $reason = null, $delivery_person = null) {
        $sql = "UPDATE {$this->table} SET status = :status";
        if ($status === 'cancelled' && $reason !== null) {
            $sql .= ", cancellation_reason = :reason";
        }
        if (($status === 'processing' || $status === 'shipped') && $delivery_person !== null) {
            $sql .= ", delivery_person = :delivery_person";
        }
        $sql .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        if ($status === 'cancelled' && $reason !== null) {
            $stmt->bindParam(':reason', $reason);
        }
        if (($status === 'processing' || $status === 'shipped') && $delivery_person !== null) {
            $stmt->bindParam(':delivery_person', $delivery_person);
        }
        return $stmt->execute();
    }
    
    /**
     * Delete order and its children (using CASCADE in DB, but manually here for safety)
     */
    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            
            // Get images to delete from filesystem
            $sql = "SELECT image_path FROM {$this->items_table} WHERE order_id = :order_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':order_id' => $id]);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Delete from database (Cascade will handle items and variations)
            $sql_del = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt_del = $this->conn->prepare($sql_del);
            $stmt_del->execute([':id' => $id]);

            // Delete physical files
            foreach ($images as $img) {
                if ($img && file_exists("../" . $img)) {
                    unlink("../" . $img);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Update order with full nested structure
     */
    public function update($id, $data, $products = []) {
        try {
            $this->conn->beginTransaction();

            // 1. Update Parent Order
            $sql = "UPDATE {$this->table} SET 
                    customer_name = :customer_name,
                    address = :address,
                    phone_number = :phone_number,
                    notes = :notes,
                    status = :status
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':customer_name', $data['customer_name']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':phone_number', $data['phone_number']);
            $stmt->bindParam(':notes', $data['notes']);
            $stmt->bindParam(':status', $data['status']);
            
            $stmt->execute();

            // 2. Manage Items and Variations
            // Delete existing (Cascade will handle variations)
            $sql_del = "DELETE FROM {$this->items_table} WHERE order_id = :order_id";
            $stmt_del = $this->conn->prepare($sql_del);
            $stmt_del->execute([':order_id' => $id]);

            // 3. Re-insert new Products and Variations
            foreach ($products as $product) {
                $sql_item = "INSERT INTO {$this->items_table} (order_id, product_name, image_path) 
                             VALUES (:order_id, :product_name, :image_path)";
                $stmt_item = $this->conn->prepare($sql_item);
                $stmt_item->execute([
                    ':order_id' => $id,
                    ':product_name' => $product['name'],
                    ':image_path' => $product['image_path']
                ]);
                
                $item_id = $this->conn->lastInsertId();

                if (!empty($product['variations'])) {
                    foreach ($product['variations'] as $v) {
                        $sql_v = "INSERT INTO {$this->variations_table} (item_id, color, size, quantity, options) 
                                  VALUES (:item_id, :color, :size, :quantity, :options)";
                        $stmt_v = $this->conn->prepare($sql_v);
                        $stmt_v->execute([
                            ':item_id' => $item_id,
                            ':color' => $v['color'],
                            ':size' => $v['size'],
                            ':quantity' => $v['quantity'],
                            ':options' => $v['options']
                        ]);
                    }
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error updating branch order: " . $e->getMessage());
            return false;
        }
    }
}
