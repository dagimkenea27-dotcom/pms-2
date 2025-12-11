<?php
/**
 * Routes API
 * RESTful API endpoints for route management system
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/auth.php';

// Check authentication for API
Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Extract endpoint from path
$endpoint = $path_parts[count($path_parts) - 1] ?? '';

// Database connection
$database = new Database();
$db = $database->getConnection();

// Response helper
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

// Error helper
function sendError($message, $status_code = 400) {
    sendResponse(['error' => $message], $status_code);
}

// ============================================
// DRIVER ENDPOINTS
// ============================================

if (strpos($endpoint, 'drivers') !== false) {
    require_once '../models/Driver.php';
    $driver = new Driver($db);
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $result = $driver->getById($_GET['id']);
                if ($result) {
                    sendResponse($result);
                } else {
                    sendError('Driver not found', 404);
                }
            } else {
                $status = $_GET['status'] ?? null;
                $result = $driver->getAll($status);
                sendResponse($result->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $driver->create($data);
            if ($id) {
                sendResponse(['id' => $id, 'message' => 'Driver created successfully'], 201);
            } else {
                sendError('Failed to create driver');
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                sendError('Driver ID required');
            }
            if ($driver->update($data['id'], $data)) {
                sendResponse(['message' => 'Driver updated successfully']);
            } else {
                sendError('Failed to update driver');
            }
            break;
            
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                sendError('Driver ID required');
            }
            if ($driver->delete($data['id'])) {
                sendResponse(['message' => 'Driver deleted successfully']);
            } else {
                sendError('Failed to delete driver');
            }
            break;
    }
}

// ============================================
// VEHICLE ENDPOINTS
// ============================================

if (strpos($endpoint, 'vehicles') !== false) {
    require_once '../models/Vehicle.php';
    $vehicle = new Vehicle($db);
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $result = $vehicle->getById($_GET['id']);
                if ($result) {
                    sendResponse($result);
                } else {
                    sendError('Vehicle not found', 404);
                }
            } else {
                $status = $_GET['status'] ?? null;
                $result = $vehicle->getAll($status);
                sendResponse($result->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $vehicle->create($data);
            if ($id) {
                sendResponse(['id' => $id, 'message' => 'Vehicle created successfully'], 201);
            } else {
                sendError('Failed to create vehicle');
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                sendError('Vehicle ID required');
            }
            if ($vehicle->update($data['id'], $data)) {
                sendResponse(['message' => 'Vehicle updated successfully']);
            } else {
                sendError('Failed to update vehicle');
            }
            break;
            
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                sendError('Vehicle ID required');
            }
            if ($vehicle->delete($data['id'])) {
                sendResponse(['message' => 'Vehicle deleted successfully']);
            } else {
                sendError('Failed to delete vehicle');
            }
            break;
    }
}

// ============================================
// CUSTOMER ENDPOINTS
// ============================================

if (strpos($endpoint, 'customers') !== false) {
    require_once '../models/Customer.php';
    $customer = new Customer($db);
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $result = $customer->getById($_GET['id']);
                if ($result) {
                    sendResponse($result);
                } else {
                    sendError('Customer not found', 404);
                }
            } elseif (isset($_GET['search'])) {
                $result = $customer->search($_GET['search']);
                sendResponse($result->fetchAll(PDO::FETCH_ASSOC));
            } else {
                $result = $customer->getAll();
                sendResponse($result->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $customer->create($data);
            if ($id) {
                sendResponse(['id' => $id, 'message' => 'Customer created successfully'], 201);
            } else {
                sendError('Failed to create customer');
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                sendError('Customer ID required');
            }
            if ($customer->update($data['id'], $data)) {
                sendResponse(['message' => 'Customer updated successfully']);
            } else {
                sendError('Failed to update customer');
            }
            break;
            
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                sendError('Customer ID required');
            }
            if ($customer->delete($data['id'])) {
                sendResponse(['message' => 'Customer deleted successfully']);
            } else {
                sendError('Failed to delete customer');
            }
            break;
    }
}

// ============================================
// PACKAGE ENDPOINTS
// ============================================

if (strpos($endpoint, 'packages') !== false) {
    require_once '../models/DeliveryPackage.php';
    $package = new DeliveryPackage($db);
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $result = $package->getById($_GET['id']);
                if ($result) {
                    sendResponse($result);
                } else {
                    sendError('Package not found', 404);
                }
            } elseif (isset($_GET['tracking'])) {
                $result = $package->getByTracking($_GET['tracking']);
                if ($result) {
                    sendResponse($result);
                } else {
                    sendError('Package not found', 404);
                }
            } else {
                $status = $_GET['status'] ?? null;
                $result = $package->getAll($status);
                sendResponse($result->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $package->create($data);
            if ($id) {
                sendResponse(['id' => $id, 'message' => 'Package created successfully'], 201);
            } else {
                sendError('Failed to create package');
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                sendError('Package ID required');
            }
            if ($package->update($data['id'], $data)) {
                sendResponse(['message' => 'Package updated successfully']);
            } else {
                sendError('Failed to update package');
            }
            break;
            
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                sendError('Package ID required');
            }
            if ($package->delete($data['id'])) {
                sendResponse(['message' => 'Package deleted successfully']);
            } else {
                sendError('Failed to delete package');
            }
            break;
    }
}

// If no endpoint matched
sendError('Invalid endpoint', 404);
