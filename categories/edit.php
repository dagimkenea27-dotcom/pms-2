<?php
// categories/edit.php
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../models/Category.php';

$database = new Database();
$db = $database->getConnection();

$category = new Category($db);
$message = '';
$message_type = '';

// Get ID from URL
$category->id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// Handle form submission
if ($_POST) {
    $category->name = $_POST['name'];
    $category->description = $_POST['description'];

    if ($category->update()) {
        $message = "Category updated successfully.";
        $message_type = "success";
    } else {
        $message = "Unable to update category.";
        $message_type = "danger";
    }
}

// Read category data to fill form
$category->readOne();
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Category</h1>
    <a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
    </a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card dashboard-card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Category Details</h6>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id={$category->id}"); ?>" method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Category Name *</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($category->name); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category->description); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Category
            </button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
