<?php
require_once 'includes/header.php';
require_once 'models/User.php';

// Check if logged in
if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Initialize User model
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$current_user_data = Auth::getCurrentUser();
$user->id = $current_user_data['id'];

// Fetch latest user data from DB to ensure we have current state
if ($user->readOne()) {
    $userData = [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'username' => $user->username,
        'role' => $user->role,
        'is_active' => $user->is_active 
    ];
} else {
    // Fallback or error
    $userData = $current_user_data;
}

$success_msg = '';
$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $user->first_name = trim($_POST['first_name']);
    $user->last_name = trim($_POST['last_name']);
    $user->email = trim($_POST['email']);
    // Username is typically read-only or requires uniqueness check. Let's keep it read-only for now or allow update if logic permits.
    // The User model update() expects username, so we pass the existing one or submitted one.
    // Let's assume we allow updating email/names. Username might be sensitive.
    $user->username = $userData['username']; 
    
    // Check for password update
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $user->password = $_POST['new_password'];
        } else {
            $error_msg = "New passwords do not match.";
        }
    }

    if (empty($error_msg)) {
        if ($user->update()) {
             // Update session data
             $_SESSION['user_id'] = $user->id;
             $_SESSION['role'] = $user->role;
             $_SESSION['full_name'] = $user->getFullName();
             
             $success_msg = "Profile updated successfully.";
             
             // Refresh data for form
             $userData['first_name'] = $user->first_name;
             $userData['last_name'] = $user->last_name;
             $userData['email'] = $user->email;
        } else {
            $error_msg = "Unable to update profile. Please try again.";
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit Profile</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($userData['username']); ?>" disabled>
                            <div class="form-text">Username cannot be changed.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="heading-small text-muted mb-4">Change Password (Optional)</h6>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
