<?php
require_once 'includes/header.php';

// Check if logged in
if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit();
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">System Settings</h6>
            </div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label for="site_title" class="form-label">Application Name</label>
                        <input type="text" class="form-control" id="site_title" value="Inventory Management System" disabled>
                        <div class="form-text">System-wide settings are currently managed via configuration files.</div>
                    </div>
                    
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="darkModeSwitch" onclick="toggleTheme()">
                        <label class="form-check-label" for="darkModeSwitch">Enable Dark Mode</label>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notificationsSwitch" checked disabled>
                        <label class="form-check-label" for="notificationsSwitch">Enable Email Notifications</label>
                    </div>

                    <button type="button" class="btn btn-secondary" disabled>Save Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
