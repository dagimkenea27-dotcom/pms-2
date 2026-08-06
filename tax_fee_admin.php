<?php
// tax_fee_admin.php
require_once "config/auth.php";
require_once "config/database.php";
require_once "models/TaxFeeConfig.php";

Auth::checkAuthAndPreventCache();

// Check if user is admin
$current_user = Auth::getCurrentUser();
if ($current_user['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$taxFeeConfig = new TaxFeeConfig($db);

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $taxFeeConfig->name = $_POST['name'];
                $taxFeeConfig->description = $_POST['description'];
                $taxFeeConfig->rate_type = $_POST['rate_type'];
                $taxFeeConfig->rate_value = $_POST['rate_value'];
                $taxFeeConfig->is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if ($taxFeeConfig->create()) {
                    $message = "Tax/Fee configuration created successfully.";
                    $message_type = "success";
                } else {
                    $message = "Failed to create tax/fee configuration.";
                    $message_type = "danger";
                }
                break;
                
            case 'update':
                $taxFeeConfig->id = $_POST['id'];
                if ($taxFeeConfig->getById($_POST['id'])) {
                    $taxFeeConfig->name = $_POST['name'];
                    $taxFeeConfig->description = $_POST['description'];
                    $taxFeeConfig->rate_type = $_POST['rate_type'];
                    $taxFeeConfig->rate_value = $_POST['rate_value'];
                    $taxFeeConfig->is_active = isset($_POST['is_active']) ? 1 : 0;
                    
                    if ($taxFeeConfig->update()) {
                        $message = "Tax/Fee configuration updated successfully.";
                        $message_type = "success";
                    } else {
                        $message = "Failed to update tax/fee configuration.";
                        $message_type = "danger";
                    }
                }
                break;
                
            case 'delete':
                $taxFeeConfig->id = $_POST['id'];
                if ($taxFeeConfig->delete()) {
                    $message = "Tax/Fee configuration deleted successfully.";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete tax/fee configuration.";
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get all tax/fee configurations
$stmt = $taxFeeConfig->getAllActive();
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-cogs"></i> Tax & Fee Configuration</h1>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Current Configurations</h5>
            </div>
            <div class="card-body">
                <?php if (count($configs) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($configs as $config): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($config['name']); ?></td>
                                <td><?php echo htmlspecialchars($config['description']); ?></td>
                                <td><?php echo ucfirst($config['rate_type']); ?></td>
                                <td>
                                    <?php if ($config['rate_type'] === 'percentage'): ?>
                                        <?php echo ($config['rate_value'] * 100); ?>%
                                    <?php else: ?>
                                        ETB <?php echo number_format($config['rate_value'], 2); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($config['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editConfig(<?php echo $config['id']; ?>, '<?php echo addslashes($config['name']); ?>', '<?php echo addslashes($config['description']); ?>', '<?php echo $config['rate_type']; ?>', <?php echo $config['rate_value']; ?>, <?php echo $config['is_active']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No tax/fee configurations found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0" id="formTitle">Add New Configuration</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="configForm">
                    <input type="hidden" name="action" value="create" id="formAction">
                    <input type="hidden" name="id" id="configId">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rate_type" class="form-label">Rate Type *</label>
                        <select class="form-select" id="rate_type" name="rate_type" required>
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rate_value" class="form-label">Rate Value *</label>
                        <input type="number" class="form-control" id="rate_value" name="rate_value" step="0.0001" min="0" required>
                        <div class="form-text" id="rate_help">Enter percentage as decimal (e.g., 0.15 for 15%) or fixed amount in ETB</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editConfig(id, name, description, rateType, rateValue, isActive) {
    document.getElementById('formTitle').textContent = 'Edit Configuration';
    document.getElementById('formAction').value = 'update';
    document.getElementById('configId').value = id;
    document.getElementById('name').value = name;
    document.getElementById('description').value = description;
    document.getElementById('rate_type').value = rateType;
    document.getElementById('rate_value').value = rateValue;
    document.getElementById('is_active').checked = isActive === 1;
    
    // Scroll to form
    document.querySelector('.col-md-4').scrollIntoView({behavior: 'smooth'});
}

function resetForm() {
    document.getElementById('formTitle').textContent = 'Add New Configuration';
    document.getElementById('formAction').value = 'create';
    document.getElementById('configForm').reset();
    document.getElementById('configId').value = '';
}
</script>

<?php require_once "includes/footer.php"; ?>