<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../models/Driver.php';

Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$driver = new Driver($db);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'user_id' => $_POST['user_id'] ?? null,
            'full_name' => $_POST['full_name'],
            'email' => $_POST['email'] ?? null,
            'phone' => $_POST['phone'],
            'license_number' => $_POST['license_number'] ?? null,
            'license_expiry' => $_POST['license_expiry'] ?? null,
            'status' => $_POST['status'] ?? 'active',
            'skills' => isset($_POST['skills']) ? $_POST['skills'] : [],
            'max_working_hours' => $_POST['max_working_hours'] ?? 8.00,
            'hourly_rate' => $_POST['hourly_rate'] ?? null
        ];
        
        $driver_id = $driver->create($data);
        
        if ($driver_id) {
            header('Location: index.php?message=Driver added successfully');
            exit;
        } else {
            $error = "Failed to add driver. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-user-plus"></i> Add New Driver</h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Drivers
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Driver Information</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row g-3">
                <!-- Basic Information -->
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="form-control" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="on_leave">On Leave</option>
                    </select>
                </div>
                
                <!-- License Information -->
                <div class="col-12"><hr></div>
                <div class="col-12"><h6>License Information</h6></div>
                
                <div class="col-md-6">
                    <label class="form-label">License Number</label>
                    <input type="text" name="license_number" class="form-control">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">License Expiry Date</label>
                    <input type="date" name="license_expiry" class="form-control">
                </div>
                
                <!-- Skills & Capabilities -->
                <div class="col-12"><hr></div>
                <div class="col-12"><h6>Skills & Capabilities</h6></div>
                
                <div class="col-md-12">
                    <label class="form-label">Skills</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="skills[]" value="refrigerated" id="skill1">
                        <label class="form-check-label" for="skill1">Refrigerated Transport</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="skills[]" value="hazmat" id="skill2">
                        <label class="form-check-label" for="skill2">Hazardous Materials</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="skills[]" value="heavy_load" id="skill3">
                        <label class="form-check-label" for="skill3">Heavy Load</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="skills[]" value="long_distance" id="skill4">
                        <label class="form-check-label" for="skill4">Long Distance</label>
                    </div>
                </div>
                
                <!-- Work Schedule -->
                <div class="col-12"><hr></div>
                <div class="col-12"><h6>Work Schedule & Compensation</h6></div>
                
                <div class="col-md-6">
                    <label class="form-label">Max Working Hours per Day</label>
                    <input type="number" name="max_working_hours" class="form-control" step="0.5" value="8.00" min="1" max="12">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Hourly Rate (ETB)</label>
                    <input type="number" name="hourly_rate" class="form-control" step="0.01" min="0">
                </div>
                
                <!-- Submit -->
                <div class="col-12">
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Driver
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
