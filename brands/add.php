<?php
// brands/add.php
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../models/Brand.php';

$database = new Database();
$db = $database->getConnection();

$brand = new Brand($db);
$message = '';
$message_type = '';

if ($_POST) {
    $brand->name = $_POST['name'];
    $brand->description = $_POST['description'];

    if ($brand->create()) {
        $message = "Brand created successfully.";
        $message_type = "success";
    } else {
        $message = "Unable to create brand. Name might already exist.";
        $message_type = "danger";
    }
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Add Brand</h1>
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
        <h6 class="m-0 font-weight-bold text-primary">Brand Details</h6>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Brand Name *</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Brand
            </button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
