<?php
// users/edit_user.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/User.php";
require_once "../config/auth.php";

// Only admin can access this page
Auth::requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';
$message_type = '';

// Get user ID
if (isset($_GET['id'])) {
    $user->id = $_GET['id'];
    if (!$user->readOne()) {
        $_SESSION['message'] = "User not found!";
        $_SESSION['message_type'] = 'danger';
        header("Location: view_users.php");
        exit();
    }
} else {
    header("Location: view_users.php");
    exit();
}

// Handle form submission
if ($_POST) {
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];
    $user->first_name = $_POST['first_name'];
    $user->last_name = $_POST['last_name'];
    $user->role = $_POST['role'];
    $user->is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Only update password if provided
    if (!empty($_POST['password'])) {
        $user->password = $_POST['password'];
    }
    
    if ($user->update()) {
        $message = "User updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating user.";
        $message_type = "danger";
    }
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-edit"></i> Edit User</h1>
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
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user->username); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user->email); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Leave blank to keep current password">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user->first_name); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user->last_name); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="staff" <?php echo $user->role == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    <option value="manager" <?php echo $user->role == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="admin" <?php echo $user->role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo $user->is_active ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Active Account</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                    <a href="view_users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
