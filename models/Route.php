<?php
// models/Route.php
require_once __DIR__ . '/../config/database.php';

class Route {
    private $conn;
    private $table = 'routes';
    private $stops_table = 'route_stops';
    private $settings_table = 'route_optimization_settings';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Save a new route
    public function saveRoute($name, $warehouse_location, $warehouse_coords, $driver_count, $country_code, $created_by, $stops, $algorithm = 'nearest_neighbor') {
        try {
            $this->conn->beginTransaction();

            // Insert route
            $query = "INSERT INTO " . $this->table . " (name, warehouse_location, warehouse_coords, driver_count, country_code, created_by) 
                      VALUES (:name, :warehouse_location, :warehouse_coords, :driver_count, :country_code, :created_by)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':warehouse_location', $warehouse_location);
            $stmt->bindParam(':warehouse_coords', json_encode($warehouse_coords));
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
                    $stmt->bindParam(':coordinates', json_encode(['lat' => $stop['lat'], 'lon' => $stop['lon']]));
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
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    // Get all routes for a user
    public function getUserRoutes($user_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE created_by = :user_id 
                  ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a specific route with stops
    public function getRouteWithStops($route_id) {
        // Get route details
        $query = "SELECT * FROM " . $this->table . " WHERE id = :route_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':route_id', $route_id);
        $stmt->execute();
        $route = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$route) {
            return null;
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
    public function deleteRoute($route_id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :route_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':route_id', $route_id);
        return $stmt->execute();
    }
}
?>