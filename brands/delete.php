<?php
// brands/delete.php
require_once '../config/auth_check.php';
require_once '../config/database.php';
require_once '../models/Brand.php';

$database = new Database();
$db = $database->getConnection();

$brand = new Brand($db);

// Get ID
$brand->id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');
if($brand->delete()) {
    header("Location: index.php?msg=deleted");
} else {
    // Redirect with error (likely because products are using it)
    echo "<script>
            alert('Unable to delete brand. It may be in use by products.');
            window.location.href='index.php';
          </script>";
}
?>
