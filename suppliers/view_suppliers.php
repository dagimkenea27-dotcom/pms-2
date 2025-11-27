<?php
// suppliers/view_suppliers.php
session_start();
require_once "../config/database.php";
require_once "../models/Supplier.php";

$database = new Database();
$db = $database->getConnection();
$supplier = new Supplier($db);

$suppliers = $supplier->read()->fetchAll(PDO::FETCH_ASSOC);

// Handle deletion
if (isset($_GET['delete_id'])) {
    $supplier->id = $_GET['delete_id'];
    if ($supplier->delete()) {
        $_SESSION['message'] = "Supplier deleted successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error deleting supplier.";
        $_SESSION['message_type'] = 'danger';
    }
    header("Location: view_suppliers.php");
    exit();
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-truck"></i> Supplier Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_supplier.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Supplier
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
        <?php if ($suppliers): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="suppliersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): 
                            // Count products for this supplier
                            $product_count_query = "SELECT COUNT(*) as count FROM products WHERE supplier_id = ?";
                            $product_count_stmt = $db->prepare($product_count_query);
                            $product_count_stmt->execute([$supplier['id']]);
                            $product_count = $product_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                <?php if ($supplier['website']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($supplier['website']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                            <td>
                                <?php if ($supplier['email']): ?>
                                    <a href="mailto:<?php echo $supplier['email']; ?>">
                                        <?php echo htmlspecialchars($supplier['email']); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo $product_count; ?> products</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_supplier.php?id=<?php echo $supplier['id']; ?>" 
                                       class="btn btn-outline-primary" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_supplier.php?id=<?php echo $supplier['id']; ?>" 
                                       class="btn btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete_id=<?php echo $supplier['id']; ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('Delete <?php echo addslashes($supplier['name']); ?>?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                <h4>No suppliers found</h4>
                <p class="text-muted">Get started by adding your first supplier.</p>
                <a href="add_supplier.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Your First Supplier
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Simple search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Search suppliers...';
        searchInput.className = 'form-control mb-3';
        searchInput.style.maxWidth = '300px';
        
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#suppliersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
        
        document.querySelector('.card-body').insertBefore(searchInput, document.querySelector('.table-responsive'));
    });
</script>

<?php require_once "../includes/footer.php"; ?>