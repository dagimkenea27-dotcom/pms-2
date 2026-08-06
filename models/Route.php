<?php
// models/Route.php
require_once __DIR__ . '/../config/database.php';

class Route
{
    private $conn;
    private $table = 'routes';
    private $stops_table = 'route_stops';
    private $settings_table = 'route_optimization_settings';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Save a new route
    public function saveRoute($name, $warehouse_locations, $driver_count, $country_code, $created_by, $stops, $algorithm = 'nearest_neighbor')
    {
        try {
            $this->conn->beginTransaction();

            // Insert route
            $query = "INSERT INTO " . $this->table . " (name, warehouse_locations, driver_count, country_code, created_by) 
                      VALUES (:name, :warehouse_locations, :driver_count, :country_code, :created_by)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindValue(':warehouse_locations', json_encode($warehouse_locations));
            $stmt->bindParam(':driver_count', $driver_count);
            $stmt->bindParam(':country_code', $country_code);
            $stmt->bindParam(':created_by', $created_by);

            if (!$stmt->execute()) {
                throw new Exception("Failed to save route");
            }

            $route_id = $this->conn->lastInsertId();

            // Insert stops
            foreach ($stops as $driver_id => $driver_stops) {
                foreach ($driver_stops as $stop_number => $stop) {
                    $query = "INSERT INTO " . $this->stops_table . " 
                              (route_id, address, coordinates, stop_number, driver_id, distance_from_previous) 
                              VALUES (:route_id, :address, :coordinates, :stop_number, :driver_id, :distance)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':route_id', $route_id);
                    $stmt->bindParam(':address', $stop['address']);
                    $stmt->bindValue(':coordinates', json_encode(['lat' => $stop['lat'], 'lon' => $stop['lon']]));
                    $stmt->bindParam(':stop_number', $stop_number);
                    $stmt->bindParam(':driver_id', $driver_id);
                    $distance = isset($stop['distance']) ? $stop['distance'] : 0;
                    $stmt->bindParam(':distance', $distance);

                    if (!$stmt->execute()) {
                        throw new Exception("Failed to save route stops");
                    }
                }
            }

            // Insert optimization settings
            $query = "INSERT INTO " . $this->settings_table . " 
                      (route_id, algorithm) 
                      VALUES (:route_id, :algorithm)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':route_id', $route_id);
            $stmt->bindParam(':algorithm', $algorithm);

            if (!$stmt->execute()) {
                throw new Exception("Failed to save route settings");
            }

            $this->conn->commit();
            return $route_id;
        }
        catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    // Get all routes for a user
    public function getUserRoutes($user_id)
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE created_by = :user_id 
                  ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a specific route with stops
    public function getRouteWithStops($route_id)
    {
        // Get route details
        $query = "SELECT * FROM " . $this->table . " WHERE id = :route_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':route_id', $route_id);
        $stmt->execute();
        $route = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$route) {
            return null;
        }

        // Decode warehouse locations
        if (isset($route['warehouse_locations'])) {
            $route['warehouse_locations'] = json_decode($route['warehouse_locations'], true);
        }

        // Get stops
        $query = "SELECT * FROM " . $this->stops_table . " 
                  WHERE route_id = :route_id 
                  ORDER BY driver_id, stop_number";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':route_id', $route_id);
        $stmt->execute();
        $stops = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organize stops by driver
        $organized_stops = [];
        foreach ($stops as $stop) {
            $driver_id = $stop['driver_id'];
            if (!isset($organized_stops[$driver_id])) {
                $organized_stops[$driver_id] = [];
            }
            $organized_stops[$driver_id][] = $stop;
        }

        $route['stops'] = $organized_stops;
        return $route;
    }

    // Delete a route
    public function deleteRoute($route_id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :route_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':route_id', $route_id);
        return $stmt->execute();
    }

    /**
     * Optimize route with advanced algorithms
     */
    public function optimizeWithAlgorithm($warehouse, $points, $numVehicles, $algorithm = 'nearest_neighbor', $options = [])
    {
        require_once __DIR__ . '/../routes/classes/AdvancedOptimizer.php';
        require_once __DIR__ . '/../routes/classes/TimeWindowOptimizer.php';
        require_once __DIR__ . '/../routes/classes/CapacityPlanner.php';

        switch ($algorithm) {
            case 'savings':
                return AdvancedOptimizer::savingsAlgorithm($warehouse, $points, $numVehicles);

            case 'genetic':
                $generations = $options['generations'] ?? 100;
                return AdvancedOptimizer::geneticAlgorithm($warehouse, $points, $numVehicles, $generations);

            case 'multi_objective':
                $weights = $options['weights'] ?? null;
                return AdvancedOptimizer::multiObjective($warehouse, $points, $numVehicles, $weights);

            case 'time_window':
                $optimizer = new TimeWindowOptimizer($warehouse, $points, $numVehicles);
                return $optimizer->optimize();

            default: // nearest_neighbor
                return AdvancedOptimizer::nearestNeighbor($warehouse, $points, $numVehicles);
        }
    }

    /**
     * Validate capacity constraints
     */
    public function validateCapacity($vehicles, $packages)
    {
        require_once __DIR__ . '/../routes/classes/CapacityPlanner.php';

        $planner = new CapacityPlanner($vehicles, $packages);
        return $planner->assignPackages();
    }

    /**
     * Get route performance metrics
     */
    public function getPerformanceMetrics($route_id)
    {
        $query = "SELECT * FROM route_performance WHERE route_id = :route_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':route_id', $route_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Save route performance data
     */
    public function savePerformance($data)
    {
        $query = "INSERT INTO route_performance 
                  (route_id, driver_id, vehicle_id, planned_distance, actual_distance,
                   planned_duration, actual_duration, fuel_consumed, fuel_cost, labor_cost,
                   total_cost, deliveries_planned, deliveries_completed, deliveries_failed,
                   on_time_percentage, customer_rating, carbon_footprint, completed_at)
                  VALUES 
                  (:route_id, :driver_id, :vehicle_id, :planned_distance, :actual_distance,
                   :planned_duration, :actual_duration, :fuel_consumed, :fuel_cost, :labor_cost,
                   :total_cost, :deliveries_planned, :deliveries_completed, :deliveries_failed,
                   :on_time_percentage, :customer_rating, :carbon_footprint, :completed_at)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':route_id', $data['route_id']);
        $stmt->bindParam(':driver_id', $data['driver_id']);
        $stmt->bindParam(':vehicle_id', $data['vehicle_id']);
        $stmt->bindParam(':planned_distance', $data['planned_distance']);
        $stmt->bindParam(':actual_distance', $data['actual_distance']);
        $stmt->bindParam(':planned_duration', $data['planned_duration']);
        $stmt->bindParam(':actual_duration', $data['actual_duration']);
        $stmt->bindParam(':fuel_consumed', $data['fuel_consumed']);
        $stmt->bindParam(':fuel_cost', $data['fuel_cost']);
        $stmt->bindParam(':labor_cost', $data['labor_cost']);
        $stmt->bindParam(':total_cost', $data['total_cost']);
        $stmt->bindParam(':deliveries_planned', $data['deliveries_planned']);
        $stmt->bindParam(':deliveries_completed', $data['deliveries_completed']);
        $stmt->bindParam(':deliveries_failed', $data['deliveries_failed']);
        $stmt->bindParam(':on_time_percentage', $data['on_time_percentage']);
        $stmt->bindParam(':customer_rating', $data['customer_rating']);
        $stmt->bindParam(':carbon_footprint', $data['carbon_footprint']);
        $stmt->bindParam(':completed_at', $data['completed_at']);

        return $stmt->execute();
    }
}
?>