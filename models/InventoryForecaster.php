<?php
/**
 * InventoryForecaster Model
 * Handles intelligent inventory forecasting and stockout predictions
 */
class InventoryForecaster {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Update forecasts for all products or a specific product
     */
    public function updateAllForecasts($productId = null) {
        $query = "SELECT id, quantity, min_stock FROM products WHERE forecast_enabled = 1";
        if ($productId) {
            $query .= " AND id = " . intval($productId);
        }

        $stmt = $this->db->query($query);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            $this->forecastProduct($product['id'], $product['quantity']);
        }
    }

    /**
     * Calculate forecast for a single product
     */
    public function forecastProduct($productId, $currentQuantity) {
        // 1. Get average daily sales over the last 30, 60, and 90 days
        $velocity = $this->calculateVelocity($productId);
        
        if ($velocity <= 0) {
            // No sales data, cannot forecast accurately
            return false;
        }

        // 2. Predict days remaining
        $daysRemaining = floor($currentQuantity / $velocity);
        $stockoutDate = date('Y-m-d', strtotime("+$daysRemaining days"));

        // 3. Suggest optimized min_stock (e.g., 7 days of stock)
        $suggestedMin = ceil($velocity * 7);

        // 4. Update the database
        $upsertQuery = "INSERT INTO inventory_forecasts 
                        (product_id, avg_daily_sales, forecast_days_remaining, predicted_stockout_date, recommended_min_stock, confidence_score)
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        avg_daily_sales = VALUES(avg_daily_sales),
                        forecast_days_remaining = VALUES(forecast_days_remaining),
                        predicted_stockout_date = VALUES(predicted_stockout_date),
                        recommended_min_stock = VALUES(recommended_min_stock),
                        confidence_score = VALUES(confidence_score)";
        
        $stmt = $this->db->prepare($upsertQuery);
        // Confidence score is higher if we have more data (placeholder logic)
        $confidence = 0.85; 
        
        return $stmt->execute([
            $productId, 
            $velocity, 
            $daysRemaining, 
            $stockoutDate, 
            $suggestedMin, 
            $confidence
        ]);
    }

    /**
     * Calculate sales velocity (units per day)
     */
    private function calculateVelocity($productId) {
        // Look at the last 30 days of 'OUT' movements
        $query = "SELECT SUM(quantity) as total_sold 
                  FROM stock_movements 
                  WHERE product_id = ? 
                  AND movement_type = 'OUT' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalSold = $row['total_sold'] ?? 0;
        
        // Return average daily sales
        return $totalSold / 30;
    }

    /**
     * Get products at risk of stockout
     */
    public function getRiskProducts($daysThreshold = 7) {
        $query = "SELECT f.*, p.name, p.sku, p.quantity 
                  FROM inventory_forecasts f
                  JOIN products p ON f.product_id = p.id
                  WHERE f.forecast_days_remaining <= ?
                  ORDER BY f.forecast_days_remaining ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$daysThreshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
