<?php
/**
 * PromotionEngine Model
 * Analyzes inventory and sales data to suggest marketing actions:
 * - Discounts for overstocked or slow-moving items
 * - Advertisements for high-performing, high-margin items
 */
class PromotionEngine {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get products suggested for DISCOUNTS (Overstock / Dead stock)
     */
    public function getDiscountSuggestions() {
        // Criteria: 
        // 1. Dead stock: No sales in 30 days and quantity > 5
        // 2. Overstock: More than 90 days of inventory based on current velocity
        $query = "
            SELECT p.id, p.name, p.sku, p.quantity, p.price, p.cost_price,
                   MAX(f.avg_daily_sales) as avg_daily_sales, 
                   MAX(f.forecast_days_remaining) as forecast_days_remaining,
                   CASE 
                       WHEN MAX(f.avg_daily_sales) = 0 THEN 'Dead Stock'
                       WHEN MAX(f.forecast_days_remaining) > 90 THEN 'Overstocked'
                       ELSE 'Low Velocity'
                   END as reason
            FROM products p
            LEFT JOIN inventory_forecasts f ON p.id = f.product_id
            WHERE (f.avg_daily_sales = 0 AND p.quantity > 5)
               OR (f.forecast_days_remaining > 90)
            GROUP BY p.id, p.name, p.sku, p.quantity, p.price, p.cost_price
            ORDER BY forecast_days_remaining DESC
            LIMIT 10";
        
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get products suggested for ADVERTISEMENTS (High Margin & High Potential)
     */
    public function getAdSuggestions($excludeIds = []) {
        // Criteria: 
        // 1. Good velocity (Top performers)
        // 2. High Margin (Price - Cost) / Price > 30%
        // 3. Sufficient stock (> 14 days remaining)
        $excludeClause = "";
        if (!empty($excludeIds)) {
            $ids = implode(',', array_map('intval', $excludeIds));
            $excludeClause = " AND p.id NOT IN ($ids)";
        }

        $query = "
            SELECT p.id, p.name, p.sku, p.quantity, p.price, p.cost_price,
                   MAX(f.avg_daily_sales) as avg_daily_sales,
                   ((p.price - p.cost_price) / NULLIF(p.price, 0)) * 100 as margin_percent
            FROM products p
            JOIN inventory_forecasts f ON p.id = f.product_id
            WHERE ((p.price - p.cost_price) / NULLIF(p.price, 0)) > 0.25
              AND f.avg_daily_sales > (SELECT AVG(avg_daily_sales) FROM inventory_forecasts)
              AND f.forecast_days_remaining > 14
              $excludeClause
            GROUP BY p.id, p.name, p.sku, p.quantity, p.price, p.cost_price
            ORDER BY margin_percent DESC, avg_daily_sales DESC
            LIMIT 10";
        
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Suggest specific discount percentages
     */
    public function suggestDiscountPrice($price, $cost, $reason) {
        $margin = $price - $cost;
        if ($reason === 'Dead Stock') return $price * 0.70; // 30% off
        if ($reason === 'Overstocked') return $price * 0.85; // 15% off
        return $price * 0.90; // 10% off
    }
}
