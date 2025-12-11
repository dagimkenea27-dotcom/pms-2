<?php
require_once '../config/database.php';
require_once '../config/auth.php';

Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Get current preferences
$query = "SELECT * FROM optimization_preferences WHERE user_id = :user_id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $currentUser['id']);
$stmt->execute();
$preferences = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cost settings
$query = "SELECT * FROM cost_settings ORDER BY created_at DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$cost_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_preferences'])) {
        $data = [
            'user_id' => $currentUser['id'],
            'default_algorithm' => $_POST['default_algorithm'] ?? 'nearest_neighbor',
            'default_vehicle_count' => intval($_POST['default_vehicle_count'] ?? 1),
            'default_country_code' => $_POST['default_country_code'] ?? 'et',
            'optimization_priority' => $_POST['optimization_priority'] ?? 'distance',
            'enable_time_windows' => isset($_POST['enable_time_windows']) ? 1 : 0,
            'enable_capacity_check' => isset($_POST['enable_capacity_check']) ? 1 : 0,
            'enable_traffic' => isset($_POST['enable_traffic']) ? 1 : 0,
            'max_route_duration' => floatval($_POST['max_route_duration'] ?? 8.0),
            'max_stops_per_route' => intval($_POST['max_stops_per_route'] ?? 50)
        ];
        
        if ($preferences) {
            // Update existing
            $query = "UPDATE optimization_preferences SET 
                      default_algorithm = :default_algorithm,
                      default_vehicle_count = :default_vehicle_count,
                      default_country_code = :default_country_code,
                      optimization_priority = :optimization_priority,
                      enable_time_windows = :enable_time_windows,
                      enable_capacity_check = :enable_capacity_check,
                      enable_traffic = :enable_traffic,
                      max_route_duration = :max_route_duration,
                      max_stops_per_route = :max_stops_per_route
                      WHERE user_id = :user_id";
        } else {
            // Insert new
            $query = "INSERT INTO optimization_preferences 
                      (user_id, default_algorithm, default_vehicle_count, default_country_code,
                       optimization_priority, enable_time_windows, enable_capacity_check,
                       enable_traffic, max_route_duration, max_stops_per_route)
                      VALUES 
                      (:user_id, :default_algorithm, :default_vehicle_count, :default_country_code,
                       :optimization_priority, :enable_time_windows, :enable_capacity_check,
                       :enable_traffic, :max_route_duration, :max_stops_per_route)";
        }
        
        $stmt = $db->prepare($query);
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        if ($stmt->execute()) {
            $message = "Preferences saved successfully!";
            // Refresh preferences
            $stmt = $db->prepare("SELECT * FROM optimization_preferences WHERE user_id = :user_id LIMIT 1");
            $stmt->bindParam(':user_id', $currentUser['id']);
            $stmt->execute();
            $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to save preferences.";
        }
    }
    
    if (isset($_POST['save_costs'])) {
        $cost_data = [
            'fuel_cost_per_liter' => floatval($_POST['fuel_cost_per_liter']),
            'labor_cost_per_hour' => floatval($_POST['labor_cost_per_hour']),
            'vehicle_maintenance_per_km' => floatval($_POST['vehicle_maintenance_per_km']),
            'carbon_emission_factor' => floatval($_POST['carbon_emission_factor'])
        ];
        
        $query = "INSERT INTO cost_settings 
                  (fuel_cost_per_liter, labor_cost_per_hour, vehicle_maintenance_per_km, carbon_emission_factor)
                  VALUES 
                  (:fuel_cost_per_liter, :labor_cost_per_hour, :vehicle_maintenance_per_km, :carbon_emission_factor)";
        
        $stmt = $db->prepare($query);
        foreach ($cost_data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        if ($stmt->execute()) {
            $message = "Cost settings saved successfully!";
            // Refresh cost settings
            $stmt = $db->prepare("SELECT * FROM cost_settings ORDER BY created_at DESC LIMIT 1");
            $stmt->execute();
            $cost_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to save cost settings.";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-cog"></i> Route Optimization Preferences</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Optimization Preferences -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Optimization Preferences</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Default Algorithm</label>
                        <select name="default_algorithm" class="form-control">
                            <option value="nearest_neighbor" <?= ($preferences['default_algorithm'] ?? '') === 'nearest_neighbor' ? 'selected' : '' ?>>
                                Nearest Neighbor (Fast)
                            </option>
                            <option value="savings" <?= ($preferences['default_algorithm'] ?? '') === 'savings' ? 'selected' : '' ?>>
                                Savings Algorithm (Balanced)
                            </option>
                            <option value="genetic" <?= ($preferences['default_algorithm'] ?? '') === 'genetic' ? 'selected' : '' ?>>
                                Genetic Algorithm (Best Quality)
                            </option>
                            <option value="multi_objective" <?= ($preferences['default_algorithm'] ?? '') === 'multi_objective' ? 'selected' : '' ?>>
                                Multi-Objective (Balanced)
                            </option>
                        </select>
                        <small class="text-muted">Algorithm used by default when optimizing routes</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Optimization Priority</label>
                        <select name="optimization_priority" class="form-control">
                            <option value="distance" <?= ($preferences['optimization_priority'] ?? '') === 'distance' ? 'selected' : '' ?>>
                                Minimize Distance
                            </option>
                            <option value="time" <?= ($preferences['optimization_priority'] ?? '') === 'time' ? 'selected' : '' ?>>
                                Minimize Time
                            </option>
                            <option value="cost" <?= ($preferences['optimization_priority'] ?? '') === 'cost' ? 'selected' : '' ?>>
                                Minimize Cost
                            </option>
                            <option value="balanced" <?= ($preferences['optimization_priority'] ?? '') === 'balanced' ? 'selected' : '' ?>>
                                Balanced
                            </option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Default Vehicles</label>
                            <input type="number" name="default_vehicle_count" class="form-control" 
                                   value="<?= $preferences['default_vehicle_count'] ?? 1 ?>" min="1" max="20">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Country Code</label>
                            <input type="text" name="default_country_code" class="form-control" 
                                   value="<?= $preferences['default_country_code'] ?? 'et' ?>" maxlength="2">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Max Route Duration (hours)</label>
                        <input type="number" name="max_route_duration" class="form-control" step="0.5"
                               value="<?= $preferences['max_route_duration'] ?? 8.0 ?>" min="1" max="24">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Max Stops per Route</label>
                        <input type="number" name="max_stops_per_route" class="form-control"
                               value="<?= $preferences['max_stops_per_route'] ?? 50 ?>" min="1" max="200">
                    </div>
                    
                    <div class="mb-3">
                        <h6>Advanced Features</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_time_windows" 
                                   id="timeWindows" <?= ($preferences['enable_time_windows'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="timeWindows">
                                Enable Time Windows
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_capacity_check" 
                                   id="capacityCheck" <?= ($preferences['enable_capacity_check'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="capacityCheck">
                                Enable Capacity Checking
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_traffic" 
                                   id="traffic" <?= ($preferences['enable_traffic'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="traffic">
                                Consider Real-Time Traffic
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" name="save_preferences" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Preferences
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Cost Settings -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Cost Parameters</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Fuel Cost per Liter (ETB)</label>
                        <input type="number" name="fuel_cost_per_liter" class="form-control" step="0.01"
                               value="<?= $cost_settings['fuel_cost_per_liter'] ?? 60.00 ?>">
                        <small class="text-muted">Current fuel price in Ethiopian Birr</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Labor Cost per Hour (ETB)</label>
                        <input type="number" name="labor_cost_per_hour" class="form-control" step="0.01"
                               value="<?= $cost_settings['labor_cost_per_hour'] ?? 50.00 ?>">
                        <small class="text-muted">Average driver hourly wage</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Vehicle Maintenance per KM (ETB)</label>
                        <input type="number" name="vehicle_maintenance_per_km" class="form-control" step="0.01"
                               value="<?= $cost_settings['vehicle_maintenance_per_km'] ?? 2.50 ?>">
                        <small class="text-muted">Maintenance and depreciation cost</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Carbon Emission Factor (kg CO₂/L)</label>
                        <input type="number" name="carbon_emission_factor" class="form-control" step="0.01"
                               value="<?= $cost_settings['carbon_emission_factor'] ?? 2.31 ?>">
                        <small class="text-muted">For environmental impact calculation</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> These values are used to calculate route costs and environmental impact.
                    </div>
                    
                    <button type="submit" name="save_costs" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Cost Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
