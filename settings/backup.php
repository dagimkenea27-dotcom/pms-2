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
        <!-- Download Backup -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo __('backup'); ?></h6>
                    <i class="fas fa-download text-gray-300"></i>
                </div>
                <div class="card-body">
                    <p class="mb-4">
                        Download a full backup of your system. This includes a <strong>full SQL export</strong> of your database and <strong>all product images</strong> in a single ZIP file.
                    </p>
                    
                    <div class="text-center py-4">
                        <img src="https://cdn-icons-png.flaticon.com/512/2873/2873173.png" alt="Backup" style="width: 120px; margin-bottom: 20px; opacity: 0.8;">
                        <br>
                        <a href="<?php echo BASE_URL; ?>api/backup_db.php" class="btn btn-primary btn-lg shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Download Backup
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Backup -->
        <div class="col-lg-6">
            <div class="card shadow mb-4 border-left-warning">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-warning">Restore System</h6>
                    <i class="fas fa-upload text-gray-300"></i>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['restore'])): ?>
                        <?php if ($_GET['restore'] == 'success'): ?>
                            <div class="alert alert-success">System restored successfully!</div>
                        <?php elseif ($_GET['restore'] == 'error'): ?>
                            <div class="alert alert-danger">Restore failed: <?php echo htmlspecialchars($_GET['message'] ?? 'Unknown error'); ?></div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <p class="mb-3">
                        Upload a previously downloaded <strong>.zip</strong> backup file to restore the database and images.
                    </p>
                    
                    <div class="card bg-light mb-3">
                        <div class="card-body py-2 small">
                            <strong>System Limits:</strong><br>
                            Max Upload: <span class="text-primary"><?php echo ini_get('upload_max_filesize'); ?></span><br>
                            Max POST: <span class="text-primary"><?php echo ini_get('post_max_size'); ?></span><br>
                            <span class="text-muted">If your backup is larger than these, ask your server admin to increase them in php.ini.</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning small py-2">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> This will <strong>overwrite</strong> your current database and images!
                    </div>

                    <form action="<?php echo BASE_URL; ?>api/restore_db.php" method="POST" enctype="multipart/form-data" id="restoreForm">
                        <div class="mb-3">
                            <label class="form-label">Select Backup File (.zip)</label>
                            <input type="file" name="backup_file" class="form-control" accept=".zip" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-warning w-100" onclick="return confirm('ARE YOU SURE? This will overwrite the entire system data!')">
                                <i class="fas fa-upload fa-sm"></i> Start Restoration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
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

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
