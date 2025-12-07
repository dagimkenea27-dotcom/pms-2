<?php
// brands/edit.php
require_once '../config/auth_check.php';
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../models/Brand.php';

$database = new Database();
$db = $database->getConnection();

$brand = new Brand($db);
$message = '';
$message_type = '';
$errors = [];

// Get ID from URL
$brand->id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// Read brand data to fill form
$brand->readOne();

// Handle form submission
if ($_POST) {
    // Validate input
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validation checks
    if (empty($name)) {
        $errors[] = "Brand name is required.";
    } else if ($name != $brand->name) {
        // Check if new brand name already exists
        $check_query = "SELECT id FROM brands WHERE name = :name AND id != :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":name", $name);
        $check_stmt->bindParam(":id", $brand->id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $errors[] = "Brand name already exists. Please use a unique name.";
        }
    }
    
    if (strlen($name) > 100) {
        $errors[] = "Brand name must be less than 100 characters.";
    }
    
    if (strlen($description) > 500) {
        $errors[] = "Description must be less than 500 characters.";
    }
    
    // If no errors, proceed with update
    if (empty($errors)) {
        $brand->name = $name;
        $brand->description = $description;
        
        if ($brand->update()) {
            $message = "Brand updated successfully.";
            $message_type = "success";
            // Refresh brand data
            $brand->readOne();
        } else {
            $message = "Unable to update brand.";
            $message_type = "danger";
        }
    } else {
        $message = "Please correct the following errors:";
        $message_type = "danger";
    }
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-edit"></i> Edit Brand</h1>
    <a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
    </a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <?php if (!empty($errors)): ?>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card dashboard-card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Brand Details</h6>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id={$brand->id}"); ?>" method="post" id="editBrandForm">
            <div class="mb-3">
                <label for="name" class="form-label">Brand Name *</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo htmlspecialchars($brand->name); ?>" 
                       required maxlength="100" placeholder="Enter brand name">
                <div class="form-text">Maximum 100 characters</div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" 
                          maxlength="500" placeholder="Enter brand description"><?php echo htmlspecialchars($brand->description); ?></textarea>
                <div class="form-text">Maximum 500 characters</div>
            </div>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> Update Brand
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </form>
    </div>
</div>

<script>
// Form validation
document.getElementById('editBrandForm').addEventListener('submit', function(e) {
    let isValid = true;
    const errors = [];
    
    // Get form values
    const name = document.getElementById('name').value.trim();
    
    // Validation checks
    if (!name) {
        isValid = false;
        errors.push('Brand name is required');
    }
    
    if (name.length > 100) {
        isValid = false;
        errors.push('Brand name must be less than 100 characters');
    }
    
    const description = document.getElementById('description').value.trim();
    if (description.length > 500) {
        isValid = false;
        errors.push('Description must be less than 500 characters');
    }
    
    if (!isValid) {
        e.preventDefault();
        alert('Please correct the following errors:\n' + errors.join('\n'));
        return false;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>