<?php
/**
 * Finance Model
 * Handles financial calculations, profit/loss, and expense tracking
 */
class Finance
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get Gross Profit for a period
     * Gross Profit = Sales Revenue - Cost of Goods Sold (COGS)
     */
    public function getGrossProfit($startDate, $endDate)
    {
        $query = "
            SELECT 
                SUM(CASE WHEN sm.movement_type = 'OUT' AND (sm.reason = 'Sale' OR sm.reason IS NULL OR sm.reason = '') 
                    THEN sm.quantity * COALESCE(pv.price, p.price) ELSE 0 END) as revenue,
                SUM(CASE WHEN sm.movement_type = 'OUT' AND (sm.reason = 'Sale' OR sm.reason IS NULL OR sm.reason = '') 
                    THEN sm.quantity * p.cost_price ELSE 0 END) as cogs,
                SUM(CASE WHEN sm.movement_type = 'IN' AND sm.reason = 'Return' 
                    THEN sm.quantity * COALESCE(pv.price, p.price) ELSE 0 END) as returns_revenue,
                SUM(CASE WHEN sm.movement_type = 'IN' AND sm.reason = 'Return' 
                    THEN sm.quantity * p.cost_price ELSE 0 END) as returns_cogs
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            LEFT JOIN product_variants pv ON sm.variant_id = pv.id
            WHERE DATE(sm.created_at) BETWEEN ? AND ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $netRevenue = ($row['revenue'] ?? 0) - ($row['returns_revenue'] ?? 0);
        $netCogs = ($row['cogs'] ?? 0) - ($row['returns_cogs'] ?? 0);

        return [
            'revenue' => $netRevenue,
            'cogs' => $netCogs,
            'gross_profit' => $netRevenue - $netCogs
        ];
    }

    /**
     * Get Total Expenses for a period
     */
    public function getTotalExpenses($startDate, $endDate)
    {
        // 1. General Business Expenses
        $query = "SELECT SUM(amount) as total FROM business_expenses WHERE DATE(expense_date) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        $generalExpenses = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // 2. Route/Logistics Costs
        $query = "SELECT SUM(amount) as total FROM route_costs WHERE DATE(recorded_at) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        $logisticsCosts = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        return [
            'general' => $generalExpenses,
            'logistics' => $logisticsCosts,
            'total' => $generalExpenses + $logisticsCosts
        ];
    }

    /**
     * Get Net Profit
     */
    public function getNetProfit($startDate, $endDate)
    {
        $gross = $this->getGrossProfit($startDate, $endDate);
        $expenses = $this->getTotalExpenses($startDate, $endDate);

        return [
            'gross_profit' => $gross['gross_profit'],
            'total_expenses' => $expenses['total'],
            'net_profit' => $gross['gross_profit'] - $expenses['total'],
            'revenue' => $gross['revenue'],
            'cogs' => $gross['cogs']
        ];
    }

    /**
     * Get expense breakdown by category
     */
    public function getExpenseBreakdown($startDate, $endDate)
    {
        $query = "SELECT c.name, SUM(e.amount) as total, c.icon
                  FROM business_expenses e
                  JOIN expense_categories c ON e.category_id = c.id
                  WHERE e.expense_date BETWEEN ? AND ?
                  GROUP BY c.id
                  ORDER BY total DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
