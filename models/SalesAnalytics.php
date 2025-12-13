<?php
// models/SalesAnalytics.php

class SalesAnalytics {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Calculate average daily sales for a product
     * Based on sales history over the lookback period (default 30 days)
     */
    public function calculateDailySales($product_id, $days = 30) {
        try {
            // Calculate total quantity sold in the last X days
            // Note: This assumes stock_movements with type 'OUT' represents sales
            // Adjust if you have a specific sales table or different movement types
            $query = "
                SELECT COALESCE(ABS(SUM(quantity)), 0) as total_sold
                FROM stock_movements 
                WHERE product_id = :product_id 
                AND movement_type = 'OUT'
                AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':days', $days);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_sold = floatval($result['total_sold']);

            // Calculate daily average
            $daily_avg = $total_sold / $days;

            return round($daily_avg, 2);

        } catch (Exception $e) {
            error_log("Error calculating daily sales: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update reorder points for all active products
     */
    public function updateAllReorderPoints() {
        try {
            // Get all products that have reorder enabled
            $query = "
                SELECT p.id, p.name, s.default_lead_time 
                FROM products p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE p.reorder_enabled = 1
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = 0;

            foreach ($products as $product) {
                $this->updateProductReorderSettings($product);
                $count++;
            }

            return $count;

        } catch (Exception $e) {
            error_log("Error updating all reorder points: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update reorder settings for a single product
     */
    public function updateProductReorderSettings($product) {
        try {
            $product_id = $product['id'];
            
            // Get current reorder settings or defaults
            $settings = $this->getReorderSettings($product_id);
            
            // Use supplier lead time if not set specifically for product
            $lead_time = $settings['lead_time_days'] ?? ($product['default_lead_time'] ?? 7);
            $safety_stock = $settings['safety_stock'] ?? 0;
            
            // Calculate daily sales velocity (using 30 day history)
            $avg_daily_sales = $this->calculateDailySales($product_id, 30);
            
            // FORMULA: Reorder Point = (Avg Daily Sales * Lead Time) + Safety Stock
            $reorder_point = ceil(($avg_daily_sales * $lead_time) + $safety_stock);
            
            // Ensure minimum reorder point of 1 if there are any sales
            if ($avg_daily_sales > 0 && $reorder_point < 1) {
                $reorder_point = 1;
            }
            
            // FORMULA: Suggest Order Qty (Simplified EOQ)
            // For now, let's suggest enough to cover 30 days of sales + safety stock
            $target_stock = ceil(($avg_daily_sales * 30) + $safety_stock);
            $reorder_qty = max($target_stock, 10); // Minimum order of 10 units

            // Save calculations
            $this->saveReorderSettings([
                'product_id' => $product_id,
                'reorder_point' => $reorder_point,
                'reorder_quantity' => $reorder_qty,
                'lead_time_days' => $lead_time,
                'safety_stock' => $safety_stock,
                'last_calculated' => date('Y-m-d H:i:s')
            ]);
            
            // Also update main products table cache
            $update_prod = $this->conn->prepare("UPDATE products SET avg_daily_sales = ? WHERE id = ?");
            $update_prod->execute([$avg_daily_sales, $product_id]);

            return true;

        } catch (Exception $e) {
            error_log("Error updating product reorder settings: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reorder settings for a product
     */
    public function getReorderSettings($product_id) {
        $stmt = $this->conn->prepare("SELECT * FROM reorder_settings WHERE product_id = ?");
        $stmt->execute([$product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Save reorder settings (Insert or Update)
     */
    private function saveReorderSettings($data) {
        $query = "
            INSERT INTO reorder_settings 
            (product_id, reorder_point, reorder_quantity, lead_time_days, safety_stock, last_calculated)
            VALUES 
            (:product_id, :reorder_point, :reorder_quantity, :lead_time_days, :safety_stock, :last_calculated)
            ON DUPLICATE KEY UPDATE
            reorder_point = VALUES(reorder_point),
            reorder_quantity = VALUES(reorder_quantity),
            lead_time_days = VALUES(lead_time_days),
            safety_stock = VALUES(safety_stock),
            last_calculated = VALUES(last_calculated)
        ";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }
    
    /**
     * Get active suggestions (products below reorder point)
     */
    public function getReorderSuggestions() {
        $query = "
            SELECT 
                p.id, p.name, p.sku, p.quantity, p.price, p.image,
                s.name as supplier_name,
                rs.reorder_point, rs.reorder_quantity, rs.lead_time_days,
                p.avg_daily_sales
            FROM products p
            JOIN reorder_settings rs ON p.id = rs.product_id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.quantity <= rs.reorder_point
            AND p.reorder_enabled = 1
            ORDER BY (p.quantity / NULLIF(rs.reorder_point, 0)) ASC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
