<?php
// categories/delete.php
require_once '../config/auth_check.php';
require_once '../config/database.php';
require_once '../models/Category.php';

$database = new Database();
$db = $database->getConnection();

$category = new Category($db);

// Get ID
$category->id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');
if($category->delete()) {
    header("Location: index.php?msg=deleted");
} else {
    // Redirect with error (likely because products are using it)
    echo "<script>
            alert('Unable to delete category. It may be in use by products.');
            window.location.href='index.php';
          </script>";
}
?>
