<?php
/**
 * Capacity Planner
 * Handles vehicle loading optimization with weight and volume constraints
 */

class CapacityPlanner {
    private $vehicles;
    private $packages;
    
    public function __construct($vehicles, $packages) {
        $this->vehicles = $vehicles;
        $this->packages = $packages;
    }
    
    /**
     * Assign packages to vehicles considering capacity constraints
     * Uses First Fit Decreasing (FFD) bin packing algorithm
     */
    public function assignPackages() {
        // Sort packages by weight (descending) for better packing
        usort($this->packages, function($a, $b) {
            $weightA = $a['weight'] ?? 0;
            $weightB = $b['weight'] ?? 0;
            return $weightB <=> $weightA;
        });
        
        // Initialize vehicle loads
        $vehicleLoads = [];
        foreach ($this->vehicles as $vehicle) {
            $vehicleLoads[$vehicle['id']] = [
                'vehicle' => $vehicle,
                'packages' => [],
                'used_weight' => 0,
                'used_volume' => 0,
                'remaining_weight' => $vehicle['capacity_weight'] ?? PHP_FLOAT_MAX,
                'remaining_volume' => $vehicle['capacity_volume'] ?? PHP_FLOAT_MAX
            ];
        }
        
        $unassigned = [];
        
        // Assign each package to first vehicle that can fit it
        foreach ($this->packages as $package) {
            $assigned = false;
            $packageWeight = $package['weight'] ?? 0;
            $packageVolume = $package['volume'] ?? 0;
            
            // Check special handling requirements
            $specialHandling = $package['special_handling'] ?? [];
            
            foreach ($vehicleLoads as $vehicleId => &$load) {
                // Check capacity constraints
                if ($packageWeight <= $load['remaining_weight'] && 
                    $packageVolume <= $load['remaining_volume']) {
                    
                    // Check vehicle type compatibility
                    if ($this->isCompatible($load['vehicle'], $specialHandling)) {
                        $load['packages'][] = $package;
                        $load['used_weight'] += $packageWeight;
                        $load['used_volume'] += $packageVolume;
                        $load['remaining_weight'] -= $packageWeight;
                        $load['remaining_volume'] -= $packageVolume;
                        $assigned = true;
                        break;
                    }
                }
            }
            
            if (!$assigned) {
                $unassigned[] = $package;
            }
        }
        
        return [
            'assignments' => $vehicleLoads,
            'unassigned' => $unassigned,
            'utilization' => $this->calculateUtilization($vehicleLoads)
        ];
    }
    
    /**
     * Check if vehicle is compatible with package requirements
     */
    private function isCompatible($vehicle, $specialHandling) {
        if (empty($specialHandling)) {
            return true;
        }
        
        $vehicleType = $vehicle['vehicle_type'] ?? 'van';
        
        // Check refrigerated requirement
        if (in_array('refrigerated', $specialHandling)) {
            if ($vehicleType !== 'refrigerated') {
                return false;
            }
        }
        
        // Check hazmat requirement (only trucks)
        if (in_array('hazmat', $specialHandling)) {
            if ($vehicleType !== 'truck') {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Calculate vehicle utilization percentages
     */
    private function calculateUtilization($vehicleLoads) {
        $utilization = [];
        
        foreach ($vehicleLoads as $vehicleId => $load) {
            $maxWeight = $load['vehicle']['capacity_weight'] ?? 1;
            $maxVolume = $load['vehicle']['capacity_volume'] ?? 1;
            
            $weightUtil = ($load['used_weight'] / $maxWeight) * 100;
            $volumeUtil = ($load['used_volume'] / $maxVolume) * 100;
            
            $utilization[$vehicleId] = [
                'weight_percentage' => round($weightUtil, 2),
                'volume_percentage' => round($volumeUtil, 2),
                'overall_percentage' => round(max($weightUtil, $volumeUtil), 2)
            ];
        }
        
        return $utilization;
    }
    
    /**
     * Optimize package distribution across vehicles
     * Uses a greedy approach to balance loads
     */
    public function balanceLoads($assignments) {
        $improved = true;
        $iterations = 0;
        $maxIterations = 100;
        
        while ($improved && $iterations < $maxIterations) {
            $improved = false;
            $iterations++;
            
            // Try to move packages between vehicles to balance loads
            foreach ($assignments as $fromVehicleId => &$fromLoad) {
                foreach ($fromLoad['packages'] as $packageIdx => $package) {
                    $packageWeight = $package['weight'] ?? 0;
                    $packageVolume = $package['volume'] ?? 0;
                    
                    foreach ($assignments as $toVehicleId => &$toLoad) {
                        if ($fromVehicleId === $toVehicleId) continue;
                        
                        // Check if moving improves balance
                        $fromUtil = $fromLoad['used_weight'] / ($fromLoad['vehicle']['capacity_weight'] ?? 1);
                        $toUtil = $toLoad['used_weight'] / ($toLoad['vehicle']['capacity_weight'] ?? 1);
                        
                        // Only move if it improves balance and fits
                        if ($fromUtil > $toUtil + 0.1 && 
                            $packageWeight <= $toLoad['remaining_weight'] &&
                            $packageVolume <= $toLoad['remaining_volume']) {
                            
                            // Move package
                            $toLoad['packages'][] = $package;
                            $toLoad['used_weight'] += $packageWeight;
                            $toLoad['used_volume'] += $packageVolume;
                            $toLoad['remaining_weight'] -= $packageWeight;
                            $toLoad['remaining_volume'] -= $packageVolume;
                            
                            unset($fromLoad['packages'][$packageIdx]);
                            $fromLoad['packages'] = array_values($fromLoad['packages']);
                            $fromLoad['used_weight'] -= $packageWeight;
                            $fromLoad['used_volume'] -= $packageVolume;
                            $fromLoad['remaining_weight'] += $packageWeight;
                            $fromLoad['remaining_volume'] += $packageVolume;
                            
                            $improved = true;
                            break 2; // Start over
                        }
                    }
                }
            }
        }
        
        return $assignments;
    }
    
    /**
     * Get loading recommendations
     */
    public function getLoadingRecommendations($vehicleLoad) {
        $recommendations = [];
        
        // Sort packages by priority and fragility
        $packages = $vehicleLoad['packages'];
        
        usort($packages, function($a, $b) {
            // Fragile items on top
            $aFragile = in_array('fragile', $a['special_handling'] ?? []);
            $bFragile = in_array('fragile', $b['special_handling'] ?? []);
            
            if ($aFragile && !$bFragile) return -1;
            if (!$aFragile && $bFragile) return 1;
            
            // Then by priority
            $priorities = ['urgent' => 4, 'high' => 3, 'normal' => 2, 'low' => 1];
            $aPriority = $priorities[$a['priority'] ?? 'normal'];
            $bPriority = $priorities[$b['priority'] ?? 'normal'];
            
            return $bPriority <=> $aPriority;
        });
        
        $recommendations['loading_order'] = $packages;
        $recommendations['notes'] = [];
        
        // Add specific notes
        foreach ($packages as $package) {
            if (in_array('fragile', $package['special_handling'] ?? [])) {
                $recommendations['notes'][] = "Package {$package['tracking_number']}: Handle with care - Fragile";
            }
            if (in_array('refrigerated', $package['special_handling'] ?? [])) {
                $recommendations['notes'][] = "Package {$package['tracking_number']}: Keep refrigerated";
            }
        }
        
        return $recommendations;
    }
}
