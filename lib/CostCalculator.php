<?php
/**
 * Cost Calculator
 * Calculates comprehensive costs for routes including fuel, driver, vehicle, and other expenses
 */

require_once __DIR__ . '/../config/database.php';

class CostCalculator {
    private $conn;
    private $fuelPricePerLiter;
    private $electricityPricePerKWh;
    private $defaultDriverHourlyRate;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Load default prices from settings
        $this->loadDefaultPrices();
    }
    
    /**
     * Load default prices from database settings
     */
    private function loadDefaultPrices() {
        try {
            $sql = "SELECT setting_key, setting_value FROM route_settings 
                    WHERE setting_key IN ('default_fuel_price_per_liter', 'default_driver_hourly_rate', 'electricity_price_per_kwh')
                    LIMIT 3";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $this->fuelPricePerLiter = $settings['default_fuel_price_per_liter'] ?? 1.50;
            $this->electricityPricePerKWh = $settings['electricity_price_per_kwh'] ?? 0.15;
            $this->defaultDriverHourlyRate = $settings['default_driver_hourly_rate'] ?? 15.00;
        } catch (PDOException $e) {
            // Use fallback defaults
            $this->fuelPricePerLiter = 1.50;
            $this->electricityPricePerKWh = 0.15;
            $this->defaultDriverHourlyRate = 15.00;
        }
    }
    
    /**
     * Calculate fuel cost for a route
     */
    public function calculateFuelCost($vehicleId, $distanceKm) {
        try {
            require_once __DIR__ . '/../models/Vehicle.php';
            $vehicleModel = new Vehicle();
            $vehicle = $vehicleModel->getVehicle($vehicleId);
            
            if (!$vehicle || $vehicle['fuel_efficiency'] <= 0) {
                return 0;
            }
            
            if ($vehicle['fuel_type'] === 'electric') {
                // For electric: fuel_efficiency is kWh per 100km
                $kWhNeeded = ($distanceKm / 100) * $vehicle['fuel_efficiency'];
                return $kWhNeeded * $this->electricityPricePerKWh;
            } else {
                // For gas/diesel: fuel_efficiency is km per liter
                $litersNeeded = $distanceKm / $vehicle['fuel_efficiency'];
                return $litersNeeded * $this->fuelPricePerLiter;
            }
        } catch (Exception $e) {
            error_log("Calculate fuel cost error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calculate driver cost for a route
     */
    public function calculateDriverCost($driverId, $durationMinutes) {
        try {
            require_once __DIR__ . '/../models/Driver.php';
            $driverModel = new Driver();
            $driver = $driverModel->getDriver($driverId);
            
            $hourlyRate = $driver['hourly_rate'] ?? $this->defaultDriverHourlyRate;
            $hours = $durationMinutes / 60;
            
            return $hours * $hourlyRate;
        } catch (Exception $e) {
            error_log("Calculate driver cost error: " . $e->getMessage());
            return ($durationMinutes / 60) * $this->defaultDriverHourlyRate;
        }
    }
    
    /**
     * Calculate vehicle maintenance cost
     */
    public function calculateMaintenanceCost($vehicleId, $distanceKm) {
        try {
            require_once __DIR__ . '/../models/Vehicle.php';
            $vehicleModel = new Vehicle();
            $vehicle = $vehicleModel->getVehicle($vehicleId);
            
            $costPerKm = $vehicle['cost_per_km'] ?? 0.10; // Default $0.10 per km
            return $distanceKm * $costPerKm;
        } catch (Exception $e) {
            error_log("Calculate maintenance cost error: " . $e->getMessage());
            return $distanceKm * 0.10;
        }
    }
    
    /**
     * Calculate carbon emissions
     */
    public function calculateCarbonEmissions($vehicleId, $distanceKm) {
        try {
            require_once __DIR__ . '/../models/Vehicle.php';
            $vehicleModel = new Vehicle();
            $vehicle = $vehicleModel->getVehicle($vehicleId);
            
            // Carbon emissions in kg CO2 per km
            $emissionsPerKm = match($vehicle['fuel_type']) {
                'gasoline' => 0.192, // kg CO2 per km
                'diesel' => 0.171,
                'hybrid' => 0.120,
                'electric' => 0.053, // Depends on electricity source
                default => 0.192
            };
            
            return $distanceKm * $emissionsPerKm;
        } catch (Exception $e) {
            error_log("Calculate carbon emissions error: " . $e->getMessage());
            return $distanceKm * 0.192; // Default to gasoline
        }
    }
    
    /**
     * Calculate total route cost
     */
    public function calculateRouteCost($routeId) {
        try {
            require_once __DIR__ . '/../models/Route.php';
            $routeModel = new Route();
            $route = $routeModel->getRouteWithStops($routeId);
            
            if (!$route) {
                return null;
            }
            
            // Get route performance data
            $sql = "SELECT total_distance_km, total_duration_minutes 
                    FROM route_performance WHERE route_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$routeId]);
            $performance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$performance) {
                return null;
            }
            
            $distanceKm = $performance['total_distance_km'];
            $durationMinutes = $performance['total_duration_minutes'];
            
            // Calculate individual costs
            $fuelCost = $route['assigned_vehicle_id'] 
                ? $this->calculateFuelCost($route['assigned_vehicle_id'], $distanceKm)
                : 0;
                
            $driverCost = $route['assigned_driver_id']
                ? $this->calculateDriverCost($route['assigned_driver_id'], $durationMinutes)
                : 0;
                
            $maintenanceCost = $route['assigned_vehicle_id']
                ? $this->calculateMaintenanceCost($route['assigned_vehicle_id'], $distanceKm)
                : 0;
                
            $carbonEmissions = $route['assigned_vehicle_id']
                ? $this->calculateCarbonEmissions($route['assigned_vehicle_id'], $distanceKm)
                : 0;
            
            // Get additional costs from route_costs table
            $sql = "SELECT SUM(amount) as additional_costs FROM route_costs WHERE route_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$routeId]);
            $additionalCosts = $stmt->fetchColumn() ?? 0;
            
            $totalCost = $fuelCost + $driverCost + $maintenanceCost + $additionalCosts;
            
            return [
                'fuel_cost' => round($fuelCost, 2),
                'driver_cost' => round($driverCost, 2),
                'maintenance_cost' => round($maintenanceCost, 2),
                'additional_costs' => round($additionalCosts, 2),
                'total_cost' => round($totalCost, 2),
                'carbon_emissions_kg' => round($carbonEmissions, 2),
                'cost_per_km' => $distanceKm > 0 ? round($totalCost / $distanceKm, 2) : 0,
                'cost_per_stop' => count($route['stops']) > 0 ? round($totalCost / count($route['stops']), 2) : 0
            ];
        } catch (Exception $e) {
            error_log("Calculate route cost error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save route cost breakdown
     */
    public function saveRouteCost($routeId, $costs) {
        try {
            // Update route_performance table
            $sql = "UPDATE route_performance SET 
                    fuel_cost = ?,
                    driver_cost = ?,
                    total_cost = ?,
                    carbon_emissions_kg = ?
                    WHERE route_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                $costs['fuel_cost'],
                $costs['driver_cost'],
                $costs['total_cost'],
                $costs['carbon_emissions_kg'],
                $routeId
            ]);
        } catch (PDOException $e) {
            error_log("Save route cost error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add additional cost to route
     */
    public function addRouteCost($routeId, $costType, $amount, $description = null, $userId = null) {
        try {
            $sql = "INSERT INTO route_costs (route_id, cost_type, amount, description, recorded_by)
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$routeId, $costType, $amount, $description, $userId]);
        } catch (PDOException $e) {
            error_log("Add route cost error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate estimated cost for a route before execution
     */
    public function estimateRouteCost($distanceKm, $durationMinutes, $vehicleId = null, $driverId = null) {
        $fuelCost = $vehicleId 
            ? $this->calculateFuelCost($vehicleId, $distanceKm)
            : ($distanceKm / 10) * $this->fuelPricePerLiter; // Assume 10 km/L
            
        $driverCost = $driverId
            ? $this->calculateDriverCost($driverId, $durationMinutes)
            : ($durationMinutes / 60) * $this->defaultDriverHourlyRate;
            
        $maintenanceCost = $vehicleId
            ? $this->calculateMaintenanceCost($vehicleId, $distanceKm)
            : $distanceKm * 0.10;
            
        $carbonEmissions = $vehicleId
            ? $this->calculateCarbonEmissions($vehicleId, $distanceKm)
            : $distanceKm * 0.192;
        
        $totalCost = $fuelCost + $driverCost + $maintenanceCost;
        
        return [
            'fuel_cost' => round($fuelCost, 2),
            'driver_cost' => round($driverCost, 2),
            'maintenance_cost' => round($maintenanceCost, 2),
            'total_cost' => round($totalCost, 2),
            'carbon_emissions_kg' => round($carbonEmissions, 2),
            'cost_per_km' => round($totalCost / $distanceKm, 2)
        ];
    }
    
    /**
     * Compare costs between different routes or algorithms
     */
    public function compareCosts($routeIds) {
        $comparison = [];
        
        foreach ($routeIds as $routeId) {
            $costs = $this->calculateRouteCost($routeId);
            if ($costs) {
                $comparison[$routeId] = $costs;
            }
        }
        
        return $comparison;
    }
    
    /**
     * Get cost savings by comparing optimized vs unoptimized route
     */
    public function calculateSavings($optimizedRouteId, $unoptimizedRouteId) {
        $optimizedCosts = $this->calculateRouteCost($optimizedRouteId);
        $unoptimizedCosts = $this->calculateRouteCost($unoptimizedRouteId);
        
        if (!$optimizedCosts || !$unoptimizedCosts) {
            return null;
        }
        
        $savings = $unoptimizedCosts['total_cost'] - $optimizedCosts['total_cost'];
        $savingsPercentage = ($savings / $unoptimizedCosts['total_cost']) * 100;
        
        return [
            'cost_savings' => round($savings, 2),
            'savings_percentage' => round($savingsPercentage, 2),
            'fuel_savings' => round($unoptimizedCosts['fuel_cost'] - $optimizedCosts['fuel_cost'], 2),
            'time_savings_minutes' => 0, // Would need duration data
            'emissions_reduction_kg' => round($unoptimizedCosts['carbon_emissions_kg'] - $optimizedCosts['carbon_emissions_kg'], 2)
        ];
    }
    
    /**
     * Set custom fuel price
     */
    public function setFuelPrice($price) {
        $this->fuelPricePerLiter = $price;
    }
    
    /**
     * Set custom driver hourly rate
     */
    public function setDriverHourlyRate($rate) {
        $this->defaultDriverHourlyRate = $rate;
    }
}
?>
