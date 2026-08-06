<?php
/**
 * Route Analytics Model
 * Provides comprehensive analytics and reporting for route optimization
 */

require_once __DIR__ . '/../config/database.php';

class RouteAnalytics {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get dashboard KPIs
     */
    public function getDashboardKPIs($userId = null, $startDate = null, $endDate = null) {
        try {
            $sql = "SELECT 
                    COUNT(DISTINCT r.id) as total_routes,
                    COUNT(DISTINCT r.assigned_driver_id) as active_drivers,
                    COUNT(DISTINCT r.assigned_vehicle_id) as active_vehicles,
                    SUM(rp.total_distance_km) as total_distance,
                    SUM(rp.total_duration_minutes) as total_duration,
                    SUM(rp.total_stops) as total_stops,
                    SUM(rp.completed_stops) as completed_stops,
                    SUM(rp.failed_stops) as failed_stops,
                    AVG(rp.efficiency_score) as avg_efficiency,
                    SUM(rp.total_cost) as total_cost,
                    SUM(rp.fuel_cost) as total_fuel_cost,
                    SUM(rp.driver_cost) as total_driver_cost,
                    AVG(rp.customer_satisfaction) as avg_satisfaction,
                    SUM(rp.carbon_emissions_kg) as total_emissions
                    FROM routes r
                    LEFT JOIN route_performance rp ON rp.route_id = r.id
                    WHERE 1=1";
            
            $params = [];
            
            if ($userId) {
                $sql .= " AND r.created_by = ?";
                $params[] = $userId;
            }
            
            if ($startDate && $endDate) {
                $sql .= " AND r.created_at BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $kpis = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate derived metrics
            if ($kpis['total_stops'] > 0) {
                $kpis['completion_rate'] = ($kpis['completed_stops'] / $kpis['total_stops']) * 100;
                $kpis['failure_rate'] = ($kpis['failed_stops'] / $kpis['total_stops']) * 100;
            } else {
                $kpis['completion_rate'] = 0;
                $kpis['failure_rate'] = 0;
            }
            
            if ($kpis['total_distance'] > 0) {
                $kpis['cost_per_km'] = $kpis['total_cost'] / $kpis['total_distance'];
            } else {
                $kpis['cost_per_km'] = 0;
            }
            
            return $kpis;
        } catch (PDOException $e) {
            error_log("Get dashboard KPIs error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get route trends over time
     */
    public function getRouteTrends($period = 'daily', $limit = 30) {
        try {
            $dateFormat = match($period) {
                'hourly' => '%Y-%m-%d %H:00:00',
                'daily' => '%Y-%m-%d',
                'weekly' => '%Y-%u',
                'monthly' => '%Y-%m',
                default => '%Y-%m-%d'
            };
            
            $sql = "SELECT 
                    DATE_FORMAT(r.created_at, ?) as period,
                    COUNT(r.id) as route_count,
                    SUM(rp.total_distance_km) as total_distance,
                    AVG(rp.efficiency_score) as avg_efficiency,
                    SUM(rp.total_cost) as total_cost,
                    SUM(rp.completed_stops) as completed_stops
                    FROM routes r
                    LEFT JOIN route_performance rp ON rp.route_id = r.id
                    WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY period
                    ORDER BY period DESC
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$dateFormat, $limit, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get route trends error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get driver performance leaderboard
     */
    public function getDriverLeaderboard($limit = 10, $metric = 'efficiency') {
        try {
            $orderBy = match($metric) {
                'efficiency' => 'avg_efficiency DESC',
                'distance' => 'total_distance DESC',
                'routes' => 'total_routes DESC',
                'rating' => 'd.rating DESC',
                'on_time' => 'd.on_time_percentage DESC',
                default => 'avg_efficiency DESC'
            };
            
            $sql = "SELECT 
                    d.id,
                    d.driver_code,
                    d.rating,
                    d.total_deliveries,
                    d.on_time_percentage,
                    u.username,
                    COUNT(DISTINCT r.id) as total_routes,
                    SUM(rp.total_distance_km) as total_distance,
                    AVG(rp.efficiency_score) as avg_efficiency,
                    SUM(rp.total_cost) as total_cost,
                    AVG(rd.customer_rating) as avg_customer_rating
                    FROM route_drivers d
                    LEFT JOIN users u ON d.user_id = u.id
                    LEFT JOIN routes r ON r.assigned_driver_id = d.id
                    LEFT JOIN route_performance rp ON rp.route_id = r.id
                    LEFT JOIN route_deliveries rd ON rd.route_id = r.id
                    WHERE d.status = 'active'
                    GROUP BY d.id
                    ORDER BY $orderBy
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get driver leaderboard error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cost breakdown analysis
     */
    public function getCostBreakdown($startDate = null, $endDate = null) {
        try {
            $sql = "SELECT 
                    cost_type,
                    SUM(amount) as total_amount,
                    COUNT(*) as count,
                    AVG(amount) as avg_amount
                    FROM route_costs
                    WHERE 1=1";
            
            $params = [];
            
            if ($startDate && $endDate) {
                $sql .= " AND recorded_at BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            
            $sql .= " GROUP BY cost_type ORDER BY total_amount DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get cost breakdown error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get delivery heat map data
     */
    public function getDeliveryHeatMap($startDate = null, $endDate = null) {
        try {
            $sql = "SELECT 
                    latitude,
                    longitude,
                    COUNT(*) as delivery_count,
                    AVG(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as success_rate
                    FROM route_deliveries
                    WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
            
            $params = [];
            
            if ($startDate && $endDate) {
                $sql .= " AND created_at BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            
            $sql .= " GROUP BY latitude, longitude";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get delivery heat map error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get route comparison data
     */
    public function compareRoutes($routeIds) {
        try {
            $placeholders = implode(',', array_fill(0, count($routeIds), '?'));
            
            $sql = "SELECT 
                    r.id,
                    r.name,
                    r.optimization_algorithm,
                    rp.total_distance_km,
                    rp.total_duration_minutes,
                    rp.total_stops,
                    rp.completed_stops,
                    rp.efficiency_score,
                    rp.total_cost,
                    rp.customer_satisfaction
                    FROM routes r
                    LEFT JOIN route_performance rp ON rp.route_id = r.id
                    WHERE r.id IN ($placeholders)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($routeIds);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Compare routes error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get algorithm performance comparison
     */
    public function getAlgorithmPerformance() {
        try {
            $sql = "SELECT 
                    r.optimization_algorithm,
                    COUNT(r.id) as usage_count,
                    AVG(rp.total_distance_km) as avg_distance,
                    AVG(rp.total_duration_minutes) as avg_duration,
                    AVG(rp.efficiency_score) as avg_efficiency,
                    AVG(rp.total_cost) as avg_cost
                    FROM routes r
                    LEFT JOIN route_performance rp ON rp.route_id = r.id
                    WHERE r.optimization_algorithm IS NOT NULL
                    GROUP BY r.optimization_algorithm
                    ORDER BY avg_efficiency DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get algorithm performance error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get time window compliance
     */
    public function getTimeWindowCompliance($startDate = null, $endDate = null) {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_deliveries,
                    SUM(CASE WHEN actual_arrival BETWEEN time_window_start AND time_window_end THEN 1 ELSE 0 END) as on_time,
                    SUM(CASE WHEN actual_arrival < time_window_start THEN 1 ELSE 0 END) as early,
                    SUM(CASE WHEN actual_arrival > time_window_end THEN 1 ELSE 0 END) as late
                    FROM route_deliveries
                    WHERE time_window_start IS NOT NULL 
                    AND time_window_end IS NOT NULL
                    AND actual_arrival IS NOT NULL";
            
            $params = [];
            
            if ($startDate && $endDate) {
                $sql .= " AND created_at BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data['total_deliveries'] > 0) {
                $data['on_time_percentage'] = ($data['on_time'] / $data['total_deliveries']) * 100;
                $data['early_percentage'] = ($data['early'] / $data['total_deliveries']) * 100;
                $data['late_percentage'] = ($data['late'] / $data['total_deliveries']) * 100;
            }
            
            return $data;
        } catch (PDOException $e) {
            error_log("Get time window compliance error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get carbon emissions report
     */
    public function getCarbonEmissionsReport($startDate = null, $endDate = null) {
        try {
            $sql = "SELECT 
                    SUM(rp.carbon_emissions_kg) as total_emissions,
                    AVG(rp.carbon_emissions_kg) as avg_per_route,
                    SUM(rp.total_distance_km) as total_distance,
                    (SUM(rp.carbon_emissions_kg) / SUM(rp.total_distance_km)) as emissions_per_km
                    FROM route_performance rp
                    INNER JOIN routes r ON r.id = rp.route_id
                    WHERE 1=1";
            
            $params = [];
            
            if ($startDate && $endDate) {
                $sql .= " AND r.created_at BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get carbon emissions report error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get predictive analytics
     */
    public function getPredictiveAnalytics($daysAhead = 7) {
        try {
            // Get historical average for same day of week
            $sql = "SELECT 
                    DAYOFWEEK(r.created_at) as day_of_week,
                    COUNT(r.id) as avg_routes,
                    AVG(rp.total_distance_km) as avg_distance,
                    AVG(rp.total_cost) as avg_cost
                    FROM routes r
                    LEFT JOIN route_performance rp ON rp.route_id = r.id
                    WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                    GROUP BY day_of_week";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $historical = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate predictions for next N days
            $predictions = [];
            for ($i = 1; $i <= $daysAhead; $i++) {
                $date = date('Y-m-d', strtotime("+$i days"));
                $dayOfWeek = date('N', strtotime($date));
                
                $historicalData = array_filter($historical, function($h) use ($dayOfWeek) {
                    return $h['day_of_week'] == $dayOfWeek;
                });
                
                if (!empty($historicalData)) {
                    $historicalData = reset($historicalData);
                    $predictions[] = [
                        'date' => $date,
                        'predicted_routes' => round($historicalData['avg_routes']),
                        'predicted_distance' => round($historicalData['avg_distance'], 2),
                        'predicted_cost' => round($historicalData['avg_cost'], 2)
                    ];
                }
            }
            
            return $predictions;
        } catch (PDOException $e) {
            error_log("Get predictive analytics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get route efficiency distribution
     */
    public function getEfficiencyDistribution() {
        try {
            $sql = "SELECT 
                    CASE 
                        WHEN efficiency_score >= 90 THEN 'Excellent (90-100)'
                        WHEN efficiency_score >= 75 THEN 'Good (75-89)'
                        WHEN efficiency_score >= 60 THEN 'Average (60-74)'
                        WHEN efficiency_score >= 40 THEN 'Below Average (40-59)'
                        ELSE 'Poor (0-39)'
                    END as efficiency_category,
                    COUNT(*) as route_count,
                    AVG(total_cost) as avg_cost,
                    AVG(total_distance_km) as avg_distance
                    FROM route_performance
                    WHERE efficiency_score IS NOT NULL
                    GROUP BY efficiency_category
                    ORDER BY MIN(efficiency_score) DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get efficiency distribution error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Export analytics data to CSV
     */
    public function exportToCSV($type, $startDate = null, $endDate = null) {
        $data = match($type) {
            'routes' => $this->getRouteExportData($startDate, $endDate),
            'drivers' => $this->getDriverLeaderboard(100),
            'costs' => $this->getCostBreakdown($startDate, $endDate),
            default => []
        };
        
        if (empty($data)) {
            return false;
        }
        
        $filename = "analytics_{$type}_" . date('Y-m-d_H-i') . ".csv";
        $filepath = __DIR__ . '/../uploads/exports/' . $filename;
        
        // Create directory if it doesn't exist
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $fp = fopen($filepath, 'w');
        fputcsv($fp, array_keys($data[0])); // Headers
        
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);
        return $filename;
    }
    
    /**
     * Get route export data
     */
    private function getRouteExportData($startDate, $endDate) {
        try {
            $sql = "SELECT 
                    r.id,
                    r.name,
                    r.created_at,
                    r.route_status,
                    d.driver_code,
                    v.license_plate,
                    rp.total_distance_km,
                    rp.total_duration_minutes,
                    rp.total_stops,
                    rp.completed_stops,
                    rp.efficiency_score,
                    rp.total_cost
                    FROM routes r
                    LEFT JOIN route_drivers d ON r.assigned_driver_id = d.id
                    LEFT JOIN route_vehicles v ON r.assigned_vehicle_id = v.id
                    LEFT JOIN route_performance rp ON rp.route_id = r.id
                    WHERE 1=1";
            
            $params = [];
            
            if ($startDate && $endDate) {
                $sql .= " AND r.created_at BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            
            $sql .= " ORDER BY r.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get route export data error: " . $e->getMessage());
            return [];
        }
    }
}
?>
