<?php
// categories/index.php
require_once '../config/auth_check.php';
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../models/Category.php';

$database = new Database();
$db = $database->getConnection();

$category = new Category($db);

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query based on search
if (!empty($search)) {
    $query = "SELECT * FROM categories WHERE name LIKE :search OR description LIKE :search ORDER BY name ASC LIMIT :limit OFFSET :offset";
    $count_query = "SELECT COUNT(*) as count FROM categories WHERE name LIKE :search OR description LIKE :search";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':search', '%' . $search . '%');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindValue(':search', '%' . $search . '%');
    $count_stmt->execute();
    $total_rows = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} else {
    $query = "SELECT * FROM categories ORDER BY name ASC LIMIT :limit OFFSET :offset";
    $count_query = "SELECT COUNT(*) as count FROM categories";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $total_rows = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

$total_pages = ceil($total_rows / $limit);
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-tags"></i> Categories</h1>
    <a href="add.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Add Category
    </a>
</div>

<div class="card dashboard-card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Categories</h6>
    </div>
    <div class="card-body">
        <!-- Search Form -->
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search categories..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
        
        <?php if ($total_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="text-center text-muted">
                Showing <?php echo min($limit, $total_rows - $offset); ?> of <?php echo $total_rows; ?> categories
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                <h4>No categories found</h4>
                <?php if (!empty($search)): ?>
                    <p class="text-muted">No categories match your search criteria.</p>
                    <a href="index.php" class="btn btn-secondary">Clear Search</a>
                <?php else: ?>
                    <p class="text-muted">Get started by adding your first category.</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Your First Category
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>