<?php
// users/view_users.php
require_once "../config/auth_check.php";
require_once "../config/database.php";
require_once "../models/User.php";
require_once "../config/auth.php";

// Auth check handled by auth_check.php
Auth::requireRole('admin'); // Only admin can list users

// Initialize DB and User
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Pagination and search variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build query based on search
$where_clause = "";
$params = [];

if (!empty($search)) {
    $where_clause = "(username LIKE :search OR first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = !empty($where_clause) ? "WHERE $where_clause" : "";

// Get total users count for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_sql";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $records_per_page);

// Get users with pagination and search
$query = "SELECT * FROM users $where_sql ORDER BY first_name ASC, last_name ASC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);

// Bind search parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(":limit", $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Delete
if (isset($_GET['delete_id'])) {
    $user->id = $_GET['delete_id'];
    // Prevent self-deletion
    if ($user->id == $_SESSION['user_id']) {
        $_SESSION['message'] = "Error: You cannot delete your own account.";
        $_SESSION['message_type'] = 'danger';
    } else {
        if ($user->delete()) {
            $_SESSION['message'] = "User deleted successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error: Cannot delete the last admin user.";
            $_SESSION['message_type'] = 'danger';
        }
    }
    // Redirect with search parameters
    $redirect_url = "view_users.php?page=$page";
    if (!empty($search)) {
        $redirect_url .= "&search=" . urlencode($search);
    }
    header("Location: $redirect_url");
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

<!-- Search Form -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <label for="search" class="form-label">Search Users</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by name, username, or email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="btn-group" role="group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="view_users.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
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
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Users List</h6>
        <div class="small text-muted">
            Showing <?php echo min($offset + 1, $total_users); ?> 
            to <?php echo min($offset + $records_per_page, $total_users); ?> 
            of <?php echo $total_users; ?> users
        </div>
    </div>
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
                                        <?php if ($user_data['is_active']): ?>
                                        <a href="toggle_status.php?id=<?php echo $user_data['id']; ?>&status=0&page=<?php echo $page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                           class="btn btn-outline-secondary" title="Deactivate">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                        <?php else: ?>
                                        <a href="toggle_status.php?id=<?php echo $user_data['id']; ?>&status=1&page=<?php echo $page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                           class="btn btn-outline-success" title="Activate">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <?php endif; ?>
                                    
                                    <a href="?delete_id=<?php echo $user_data['id']; ?>&page=<?php echo $page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Users pagination">
                <ul class="pagination justify-content-center">
                    <!-- Previous Button -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" tabindex="-1">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    // Show first page and ellipsis if needed
                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . '">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Page numbers
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $i . '</a></li>';
                    }
                    
                    // Show last page and ellipsis if needed
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $total_pages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Next Button -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4>No users found</h4>
                <?php if (!empty($search)): ?>
                    <p class="text-muted">No users match your search criteria.</p>
                    <a href="view_users.php" class="btn btn-primary">
                        <i class="fas fa-times"></i> Clear Search
                    </a>
                <?php else: ?>
                    <p class="text-muted">Get started by adding your first user.</p>
                    <a href="add_user.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add User
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>