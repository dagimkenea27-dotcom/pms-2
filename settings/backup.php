<?php
require_once dirname(__DIR__) . '/includes/header.php';

// Check if logged in and is admin
if (!Auth::isLoggedIn() || Auth::getCurrentUser()['role'] !== 'admin') {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '') . "index.php");
    exit();
}
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo __('database_backup'); ?></h1>
    </div>

    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo __('backup'); ?></h6>
                    <i class="fas fa-database text-gray-300"></i>
                </div>
                <div class="card-body">
                    <p class="mb-4">
                        Download a full SQL export of your database. This backup includes all tables, products, movements, and user records.
                    </p>
                    
                    <div class="text-center py-4">
                        <img src="<?php echo BASE_URL; ?>assets/img/undraw_data_backup.svg" alt="Backup" style="width: 200px; margin-bottom: 20px; opacity: 0.8;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2873/2873173.png'">
                        <br>
                        <a href="<?php echo BASE_URL; ?>api/backup_db.php" class="btn btn-primary btn-lg shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Generate and Download Backup
                        </a>
                    </div>
                    
                    <hr>
                    
                    <div class="alert alert-info border-left-info shadow-sm">
                        <div class="d-flex">
                            <i class="fas fa-info-circle fa-2x mr-3 text-info"></i>
                            <div>
                                <h4 class="alert-heading h5">Good Practice</h4>
                                <p class="mb-0 small">It's recommended to perform regular backups, especially before making major changes to the system or importing large amounts of data.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
