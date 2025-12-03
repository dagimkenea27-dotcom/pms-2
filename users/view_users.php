<?php
// users/view_users.php
require_once "../config/auth.php";
    $user->id = $_GET['delete_id'];
    if ($user->delete()) {
        $_SESSION['message'] = "User deleted successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error: Cannot delete the last admin user.";
        $_SESSION['message_type'] = 'danger';
    }
    header("Location: view_users.php");
    exit();
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users"></i> User Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_user.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add New User
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php 
unset($_SESSION['message']);
unset($_SESSION['message_type']);
endif; ?>

<div class="card">
    <div class="card-body">
        <?php if ($users): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user_data): 
                            $status_class = $user_data['is_active'] ? 'success' : 'secondary';
                            $status_text = $user_data['is_active'] ? 'Active' : 'Inactive';
                            $role_class = [
                                'admin' => 'danger',
                                'manager' => 'warning', 
                                'staff' => 'info'
                            ][$user_data['role']] ?? 'secondary';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($user_data['username']); ?></td>
                            <td><?php echo htmlspecialchars($user_data['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $role_class; ?>">
                                    <?php echo ucfirst($user_data['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user_data['last_login']): ?>
                                    <?php echo date('M j, Y g:i A', strtotime($user_data['last_login'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit_user.php?id=<?php echo $user_data['id']; ?>" 
                                       class="btn btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user_data['id'] != $_SESSION['user_id']): ?>
                                    <a href="?delete_id=<?php echo $user_data['id']; ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('Delete user <?php echo addslashes($user_data['username']); ?>?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4>No users found</h4>
                <p class="text-muted">Get started by adding your first user.</p>
                <a href="add_user.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add User
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add search functionality
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Search users...';
        searchInput.className = 'form-control mb-3';
        searchInput.style.maxWidth = '300px';
        
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
        
        document.querySelector('.card-body').insertBefore(searchInput, document.querySelector('.table-responsive'));
    });
</script>

<?php require_once "../includes/footer.php"; ?>