<?php
/**
 * Time Window Optimizer
 * Handles route optimization with delivery time window constraints
 */

class TimeWindowOptimizer {
    private $warehouse;
    private $deliveries;
    private $vehicleCount;
    
    public function __construct($warehouse, $deliveries, $vehicleCount = 1) {
        $this->warehouse = $warehouse;
        $this->deliveries = $deliveries;
        $this->vehicleCount = $vehicleCount;
    }
    
    /**
     * Optimize routes considering time windows
     * Uses a modified nearest neighbor with time feasibility checks
     */
    public function optimize() {
        // Group deliveries by time windows
        $timeGroups = $this->groupByTimeWindow();
        
        // Assign deliveries to vehicles
        $vehicleRoutes = $this->assignToVehicles($timeGroups);
        
        // Optimize each vehicle's route
        $optimizedRoutes = [];
        foreach ($vehicleRoutes as $vehicleId => $deliveries) {
            $optimizedRoutes[$vehicleId] = $this->optimizeSingleRoute($deliveries);
        }
        
        return $optimizedRoutes;
    }
    
    /**
     * Group deliveries by time windows
     */
    private function groupByTimeWindow() {
        $groups = [
            'morning' => [],    // Before 12:00
            'afternoon' => [],  // 12:00 - 17:00
            'evening' => [],    // After 17:00
            'flexible' => []    // No time constraint
        ];
        
        foreach ($this->deliveries as $delivery) {
            if (!isset($delivery['time_window'])) {
                $groups['flexible'][] = $delivery;
                continue;
            }
            
            $earliest = $delivery['time_window']['earliest'] ?? '00:00';
            $latest = $delivery['time_window']['latest'] ?? '23:59';
            
            $earliestHour = (int)substr($earliest, 0, 2);
            $latestHour = (int)substr($latest, 0, 2);
            
            if ($latestHour <= 12) {
                $groups['morning'][] = $delivery;
            } elseif ($earliestHour >= 17) {
                $groups['evening'][] = $delivery;
            } elseif ($earliestHour >= 12 && $latestHour <= 17) {
                $groups['afternoon'][] = $delivery;
            } else {
                $groups['flexible'][] = $delivery;
            }
        }
        
        return $groups;
    }
    
    /**
     * Assign deliveries to vehicles
     */
    private function assignToVehicles($timeGroups) {
        $vehicleRoutes = array_fill(0, $this->vehicleCount, []);
        $vehicleIndex = 0;
        
        // Distribute time-constrained deliveries first
        foreach (['morning', 'afternoon', 'evening'] as $period) {
            foreach ($timeGroups[$period] as $delivery) {
                $vehicleRoutes[$vehicleIndex][] = $delivery;
                $vehicleIndex = ($vehicleIndex + 1) % $this->vehicleCount;
            }
        }
        
        // Distribute flexible deliveries
        foreach ($timeGroups['flexible'] as $delivery) {
            // Assign to vehicle with fewest deliveries
            $minCount = PHP_INT_MAX;
            $minIndex = 0;
            
            foreach ($vehicleRoutes as $idx => $route) {
                if (count($route) < $minCount) {
                    $minCount = count($route);
                    $minIndex = $idx;
                }
            }
            
            $vehicleRoutes[$minIndex][] = $delivery;
        }
        
        return $vehicleRoutes;
    }
    
    /**
     * Optimize a single vehicle's route
     */
    private function optimizeSingleRoute($deliveries) {
        if (empty($deliveries)) {
            return [
                'route' => [$this->warehouse, $this->warehouse],
                'total_km' => 0,
                'estimated_duration' => 0
            ];
        }
        
        $route = [$this->warehouse];
        $current = $this->warehouse;
        $remaining = $deliveries;
        $totalDistance = 0;
        $currentTime = strtotime('08:00'); // Start at 8 AM
        $avgSpeed = 30; // km/h average speed in city
        
        while (!empty($remaining)) {
            $best = null;
            $bestDistance = PHP_INT_MAX;
            $bestIndex = null;
            
            foreach ($remaining as $index => $delivery) {
                $distance = $this->haversineDistance(
                    $current['lat'], $current['lon'],
                    $delivery['lat'], $delivery['lon']
                );
                
                // Calculate arrival time
                $travelTime = ($distance / $avgSpeed) * 3600; // seconds
                $arrivalTime = $currentTime + $travelTime;
                
                // Check time window feasibility
                if ($this->isTimeFeasible($delivery, $arrivalTime)) {
                    if ($distance < $bestDistance) {
                        $bestDistance = $distance;
                        $best = $delivery;
                        $bestIndex = $index;
                    }
                }
            }
            
            // If no feasible delivery found, take the nearest one anyway
            if ($best === null) {
                foreach ($remaining as $index => $delivery) {
                    $distance = $this->haversineDistance(
                        $current['lat'], $current['lon'],
                        $delivery['lat'], $delivery['lon']
                    );
                    
                    if ($distance < $bestDistance) {
                        $bestDistance = $distance;
                        $best = $delivery;
                        $bestIndex = $index;
                    }
                }
            }
            
            if ($best) {
                $best['distance'] = $bestDistance;
                $route[] = $best;
                $totalDistance += $bestDistance;
                
                // Update current time (travel + 10 min service time)
                $travelTime = ($bestDistance / $avgSpeed) * 3600;
                $currentTime += $travelTime + 600; // 10 minutes service
                
                $current = $best;
                unset($remaining[$bestIndex]);
                $remaining = array_values($remaining); // Re-index
            }
        }
        
        // Return to warehouse
        $returnDistance = $this->haversineDistance(
            $current['lat'], $current['lon'],
            $this->warehouse['lat'], $this->warehouse['lon']
        );
        
        $warehouse = $this->warehouse;
        $warehouse['distance'] = $returnDistance;
        $route[] = $warehouse;
        $totalDistance += $returnDistance;
        
        $totalDuration = ($currentTime - strtotime('08:00')) / 60; // minutes
        
        return [
            'route' => $route,
            'total_km' => $totalDistance,
            'estimated_duration' => $totalDuration
        ];
    }
    
    /**
     * Check if delivery is feasible within time window
     */
    private function isTimeFeasible($delivery, $arrivalTime) {
        if (!isset($delivery['time_window'])) {
            return true; // No constraint
        }
        
        $earliest = $delivery['time_window']['earliest'] ?? '00:00';
        $latest = $delivery['time_window']['latest'] ?? '23:59';
        
        $earliestTime = strtotime(date('Y-m-d') . ' ' . $earliest);
        $latestTime = strtotime(date('Y-m-d') . ' ' . $latest);
        
        // Allow arrival up to 30 minutes before earliest (can wait)
        return $arrivalTime >= ($earliestTime - 1800) && $arrivalTime <= $latestTime;
    }
    
    /**
     * Calculate Haversine distance
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $R = 6371; // Earth's radius in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }
}
