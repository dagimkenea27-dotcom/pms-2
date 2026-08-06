<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../models/Route.php';

Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_template'])) {
        $template_id = intval($_POST['template_id']);
        
        $query = "DELETE FROM route_templates WHERE id = :id AND created_by = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $template_id);
        $stmt->bindParam(':user_id', $currentUser['id']);
        
        if ($stmt->execute()) {
            $message = "Template deleted successfully!";
        } else {
            $error = "Failed to delete template.";
        }
    } elseif (isset($_POST['toggle_active'])) {
        $template_id = intval($_POST['template_id']);
        $is_active = intval($_POST['is_active']);
        
        $query = "UPDATE route_templates SET is_active = :is_active WHERE id = :id AND created_by = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':is_active', $is_active);
        $stmt->bindParam(':id', $template_id);
        $stmt->bindParam(':user_id', $currentUser['id']);
        
        if ($stmt->execute()) {
            $message = $is_active ? "Template activated!" : "Template deactivated!";
        }
    } elseif (isset($_POST['create_template'])) {
        // Create new template
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? null,
            'recurrence' => $_POST['recurrence'] ?? null,
            'warehouse_location' => $_POST['warehouse_location'],
            'warehouse_lat' => $_POST['warehouse_lat'] ?? null,
            'warehouse_lon' => $_POST['warehouse_lon'] ?? null,
            'addresses' => $_POST['addresses'],
            'driver_count' => intval($_POST['driver_count']),
            'country_code' => $_POST['country_code'] ?? 'et',
            'created_by' => $currentUser['id']
        ];
        
        // Handle recurrence pattern
        if ($data['recurrence'] === 'custom' && isset($_POST['recurrence_days'])) {
            $pattern = ['days' => $_POST['recurrence_days']];
            $data['recurrence_pattern'] = json_encode($pattern);
        } else {
            $data['recurrence_pattern'] = null;
        }
        
        $query = "INSERT INTO route_templates 
                  (name, description, recurrence, recurrence_pattern, warehouse_location, 
                   warehouse_lat, warehouse_lon, addresses, driver_count, country_code, created_by)
                  VALUES 
                  (:name, :description, :recurrence, :recurrence_pattern, :warehouse_location,
                   :warehouse_lat, :warehouse_lon, :addresses, :driver_count, :country_code, :created_by)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':recurrence', $data['recurrence']);
        $stmt->bindParam(':recurrence_pattern', $data['recurrence_pattern']);
        $stmt->bindParam(':warehouse_location', $data['warehouse_location']);
        $stmt->bindParam(':warehouse_lat', $data['warehouse_lat']);
        $stmt->bindParam(':warehouse_lon', $data['warehouse_lon']);
        $stmt->bindParam(':addresses', $data['addresses']);
        $stmt->bindParam(':driver_count', $data['driver_count']);
        $stmt->bindParam(':country_code', $data['country_code']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        if ($stmt->execute()) {
            $message = "Template created successfully!";
        } else {
            $error = "Failed to create template.";
        }
    }
}

// Get all templates
$query = "SELECT * FROM route_templates WHERE created_by = :user_id ORDER BY is_active DESC, created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $currentUser['id']);
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-bookmark"></i> Route Templates</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
        <i class="fas fa-plus"></i> Create New Template
    </button>
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

<!-- Templates Grid -->
<div class="row">
    <?php if (empty($templates)): ?>
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bookmark fa-3x text-gray-300 mb-3"></i>
                    <h5>No Templates Yet</h5>
                    <p class="text-muted">Create your first route template to save time on recurring deliveries.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                        <i class="fas fa-plus"></i> Create Template
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($templates as $template): ?>
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100 <?= $template['is_active'] ? '' : 'border-secondary' ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?= htmlspecialchars($template['name']) ?>
                            <?php if (!$template['is_active']): ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="../routes/index.php?template_id=<?= $template['id'] ?>">
                                        <i class="fas fa-play"></i> Use Template
                                    </a>
                                </li>
                                <li>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                                        <input type="hidden" name="is_active" value="<?= $template['is_active'] ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_active" class="dropdown-item">
                                            <i class="fas fa-<?= $template['is_active'] ? 'pause' : 'play' ?>"></i> 
                                            <?= $template['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this template?');">
                                        <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                                        <button type="submit" name="delete_template" class="dropdown-item text-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($template['description']): ?>
                            <p class="text-muted small"><?= htmlspecialchars($template['description']) ?></p>
                        <?php endif; ?>
                        
                        <div class="mb-2">
                            <strong>Warehouse:</strong> <?= htmlspecialchars($template['warehouse_location']) ?>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Drivers:</strong> <?= $template['driver_count'] ?>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Addresses:</strong> <?= substr_count($template['addresses'], "\n") + 1 ?> stops
                        </div>
                        
                        <?php if ($template['recurrence']): ?>
                            <div class="mb-2">
                                <strong>Recurrence:</strong> 
                                <span class="badge bg-info"><?= ucfirst($template['recurrence']) ?></span>
                                <?php if ($template['recurrence_pattern']): ?>
                                    <?php
                                    $pattern = json_decode($template['recurrence_pattern'], true);
                                    if (isset($pattern['days'])):
                                    ?>
                                        <br><small class="text-muted">Days: <?= implode(', ', array_map('ucfirst', $pattern['days'])) ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-muted small">
                            <i class="fas fa-clock"></i> Created <?= date('M d, Y', strtotime($template['created_at'])) ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="../routes/index.php?template_id=<?= $template['id'] ?>" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-play"></i> Use This Template
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Create Template Modal -->
<div class="modal fade" id="createTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Create Route Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Template Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g., Monday Morning Deliveries">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Drivers</label>
                            <input type="number" name="driver_count" class="form-control" value="1" min="1" max="20">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                        </div>
                        
                        <div class="col-md-8">
                            <label class="form-label">Warehouse Location <span class="text-danger">*</span></label>
                            <input type="text" name="warehouse_location" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Country Code</label>
                            <input type="text" name="country_code" class="form-control" value="et" maxlength="2">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Delivery Addresses <span class="text-danger">*</span></label>
                            <textarea name="addresses" class="form-control" rows="6" required placeholder="One address per line"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Recurrence</label>
                            <select name="recurrence" class="form-control" id="recurrenceSelect">
                                <option value="">One-time</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        
                        <div class="col-12" id="customRecurrence" style="display:none;">
                            <label class="form-label">Select Days</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="recurrence_days[]" value="monday" id="mon">
                                    <label class="form-check-label" for="mon">Mon</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="recurrence_days[]" value="tuesday" id="tue">
                                    <label class="form-check-label" for="tue">Tue</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="recurrence_days[]" value="wednesday" id="wed">
                                    <label class="form-check-label" for="wed">Wed</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="recurrence_days[]" value="thursday" id="thu">
                                    <label class="form-check-label" for="thu">Thu</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="recurrence_days[]" value="friday" id="fri">
                                    <label class="form-check-label" for="fri">Fri</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="recurrence_days[]" value="saturday" id="sat">
                                    <label class="form-check-label" for="sat">Sat</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="recurrence_days[]" value="sunday" id="sun">
                                    <label class="form-check-label" for="sun">Sun</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_template" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('recurrenceSelect').addEventListener('change', function() {
    document.getElementById('customRecurrence').style.display = 
        this.value === 'custom' ? 'block' : 'none';
});
</script>

<?php require_once '../includes/footer.php'; ?>
