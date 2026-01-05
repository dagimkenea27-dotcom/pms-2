<?php
/**
 * Route Optimizer REST API
 * Provides programmatic access to route optimization features
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Route.php';
require_once __DIR__ . '/../models/Driver.php';
require_once __DIR__ . '/../models/Vehicle.php';
require_once __DIR__ . '/../models/RouteAnalytics.php';
require_once __DIR__ . '/../models/RouteTemplate.php';
require_once __DIR__ . '/../services/TrafficService.php';

class RouteAPI {
    private $method;
    private $endpoint;
    private $params;
    private $user;
    
    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->parseEndpoint();
        $this->authenticate();
    }
    
    /**
     * Parse the endpoint from the request URI
     */
    private function parseEndpoint() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/api/routes_api.php/', '', $path);
        $parts = explode('/', trim($path, '/'));
        
        $this->endpoint = $parts[0] ?? '';
        $this->params = array_slice($parts, 1);
    }
    
    /**
     * Authenticate the request
     */
    private function authenticate() {
        // Check for API key in header or query parameter
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
        
        if (!$apiKey) {
            // Fall back to session authentication
            session_start();
            if (!Auth::isLoggedIn()) {
                $this->sendError('Unauthorized', 401);
            }
            $this->user = Auth::getCurrentUser();
        } else {
            // Validate API key (implement your own validation)
            $this->user = $this->validateApiKey($apiKey);
            if (!$this->user) {
                $this->sendError('Invalid API key', 401);
            }
        }
    }
    
    /**
     * Validate API key
     */
    private function validateApiKey($apiKey) {
        // Implement API key validation
        // This is a placeholder - implement proper API key management
        $database = new Database();
        $conn = $database->getConnection();
        
        $sql = "SELECT u.* FROM users u 
                INNER JOIN user_api_keys k ON u.id = k.user_id 
                WHERE k.api_key = ? AND k.is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$apiKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Process the API request
     */
    public function processRequest() {
        try {
            $response = match($this->endpoint) {
                'routes' => $this->handleRoutes(),
                'optimize' => $this->handleOptimize(),
                'drivers' => $this->handleDrivers(),
                'vehicles' => $this->handleVehicles(),
                'analytics' => $this->handleAnalytics(),
                'templates' => $this->handleTemplates(),
                'traffic' => $this->handleTraffic(),
                default => $this->sendError('Endpoint not found', 404)
            };
            
            $this->sendResponse($response);
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Handle routes endpoint
     */
    private function handleRoutes() {
        $routeModel = new Route();
        
        switch ($this->method) {
            case 'GET':
                if (!empty($this->params[0])) {
                    // Get specific route
                    $route = $routeModel->getRouteWithStops($this->params[0]);
                    return $route ?: $this->sendError('Route not found', 404);
                } else {
                    // Get all routes for user
                    return $routeModel->getUserRoutes($this->user['id']);
                }
                
            case 'POST':
                // Create new route
                $data = json_decode(file_get_contents('php://input'), true);
                $routeId = $routeModel->saveRoute(
                    $data['name'],
                    $data['warehouse_locations'],
                    $data['driver_count'] ?? 1,
                    $data['country_code'] ?? 'et',
                    $this->user['id'],
                    $data['stops'] ?? []
                );
                
                return ['id' => $routeId, 'message' => 'Route created successfully'];
                
            case 'DELETE':
                if (empty($this->params[0])) {
                    return $this->sendError('Route ID required', 400);
                }
                
                $success = $routeModel->deleteRoute($this->params[0]);
                return ['success' => $success, 'message' => $success ? 'Route deleted' : 'Failed to delete route'];
                
            default:
                return $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Handle optimize endpoint
     */
    private function handleOptimize() {
        if ($this->method !== 'POST') {
            return $this->sendError('Method not allowed', 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['warehouse']) || empty($data['stops'])) {
            return $this->sendError('Warehouse and stops are required', 400);
        }
        
        $algorithm = $data['algorithm'] ?? 'nearest_neighbor';
        $numVehicles = $data['num_vehicles'] ?? 1;
        $useTraffic = $data['use_traffic'] ?? false;
        
        if ($useTraffic) {
            $trafficService = new TrafficService();
            $routes = $trafficService->getOptimizedRoute(
                $data['warehouse'],
                $data['stops'],
                $numVehicles
            );
        } else {
            // Use existing optimization
            require_once __DIR__ . '/../routes/classes/AdvancedOptimizer.php';
            $optimizer = new AdvancedOptimizer();
            
            $routes = match($algorithm) {
                'savings' => $optimizer->savingsAlgorithm($data['warehouse'], $data['stops'], $numVehicles),
                'genetic' => $optimizer->geneticAlgorithm($data['warehouse'], $data['stops'], $numVehicles),
                default => $optimizer->nearestNeighbor($data['warehouse'], $data['stops'], $numVehicles)
            };
        }
        
        return [
            'routes' => $routes,
            'algorithm' => $algorithm,
            'traffic_considered' => $useTraffic
        ];
    }
    
    /**
     * Handle drivers endpoint
     */
    private function handleDrivers() {
        $driverModel = new Driver();
        
        switch ($this->method) {
            case 'GET':
                if (!empty($this->params[0])) {
                    return $driverModel->getDriver($this->params[0]);
                } else {
                    return $driverModel->getActiveDrivers();
                }
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $driverId = $driverModel->createDriver($data);
                return ['id' => $driverId, 'message' => 'Driver created successfully'];
                
            case 'PUT':
                if (empty($this->params[0])) {
                    return $this->sendError('Driver ID required', 400);
                }
                
                $data = json_decode(file_get_contents('php://input'), true);
                $success = $driverModel->updateDriver($this->params[0], $data);
                return ['success' => $success, 'message' => $success ? 'Driver updated' : 'Failed to update driver'];
                
            default:
                return $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Handle vehicles endpoint
     */
    private function handleVehicles() {
        $vehicleModel = new Vehicle();
        
        switch ($this->method) {
            case 'GET':
                if (!empty($this->params[0])) {
                    return $vehicleModel->getVehicle($this->params[0]);
                } else {
                    return $vehicleModel->getActiveVehicles();
                }
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $vehicleId = $vehicleModel->createVehicle($data);
                return ['id' => $vehicleId, 'message' => 'Vehicle created successfully'];
                
            case 'PUT':
                if (empty($this->params[0])) {
                    return $this->sendError('Vehicle ID required', 400);
                }
                
                $data = json_decode(file_get_contents('php://input'), true);
                $success = $vehicleModel->updateVehicle($this->params[0], $data);
                return ['success' => $success, 'message' => $success ? 'Vehicle updated' : 'Failed to update vehicle'];
                
            default:
                return $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Handle analytics endpoint
     */
    private function handleAnalytics() {
        if ($this->method !== 'GET') {
            return $this->sendError('Method not allowed', 405);
        }
        
        $analytics = new RouteAnalytics();
        $type = $this->params[0] ?? 'dashboard';
        
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        return match($type) {
            'dashboard' => $analytics->getDashboardKPIs($this->user['id'], $startDate, $endDate),
            'trends' => $analytics->getRouteTrends($_GET['period'] ?? 'daily', $_GET['limit'] ?? 30),
            'leaderboard' => $analytics->getDriverLeaderboard($_GET['limit'] ?? 10, $_GET['metric'] ?? 'efficiency'),
            'costs' => $analytics->getCostBreakdown($startDate, $endDate),
            'algorithms' => $analytics->getAlgorithmPerformance(),
            'predictions' => $analytics->getPredictiveAnalytics($_GET['days'] ?? 7),
            default => $this->sendError('Invalid analytics type', 400)
        };
    }
    
    /**
     * Handle templates endpoint
     */
    private function handleTemplates() {
        $templateModel = new RouteTemplate();
        
        switch ($this->method) {
            case 'GET':
                if (!empty($this->params[0])) {
                    if ($this->params[0] === 'popular') {
                        return $templateModel->getPopularTemplates($_GET['limit'] ?? 10);
                    } else {
                        return $templateModel->getTemplate($this->params[0]);
                    }
                } else {
                    return $templateModel->getUserTemplates($this->user['id']);
                }
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (isset($data['apply_template'])) {
                    // Apply template to create route
                    $routeId = $templateModel->applyTemplate(
                        $data['template_id'],
                        $data['route_name'],
                        $this->user['id']
                    );
                    return ['route_id' => $routeId, 'message' => 'Template applied successfully'];
                } else {
                    // Create new template
                    $data['created_by'] = $this->user['id'];
                    $templateId = $templateModel->createTemplate($data);
                    
                    if ($templateId && !empty($data['stops'])) {
                        $templateModel->addTemplateStops($templateId, $data['stops']);
                    }
                    
                    return ['id' => $templateId, 'message' => 'Template created successfully'];
                }
                
            case 'DELETE':
                if (empty($this->params[0])) {
                    return $this->sendError('Template ID required', 400);
                }
                
                $success = $templateModel->deleteTemplate($this->params[0], $this->user['id']);
                return ['success' => $success, 'message' => $success ? 'Template deleted' : 'Failed to delete template'];
                
            default:
                return $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Handle traffic endpoint
     */
    private function handleTraffic() {
        if ($this->method !== 'POST') {
            return $this->sendError('Method not allowed', 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $trafficService = new TrafficService();
        
        if (isset($data['route_id'])) {
            // Get traffic conditions for existing route
            return $trafficService->getRouteTrafficConditions($data['route_id']);
        } elseif (isset($data['origin']) && isset($data['destination'])) {
            // Get traffic between two points
            return $trafficService->getDistanceWithTraffic(
                $data['origin']['lat'],
                $data['origin']['lon'],
                $data['destination']['lat'],
                $data['destination']['lon']
            );
        } else {
            return $this->sendError('Invalid traffic request', 400);
        }
    }
    
    /**
     * Send JSON response
     */
    private function sendResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('c')
        ]);
        exit;
    }
}

// Process the API request
$api = new RouteAPI();
$api->processRequest();
?>
