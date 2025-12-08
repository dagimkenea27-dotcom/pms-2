<?php
// reports/stock_valuation.php
require_once "../config/auth_check.php";
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Count total products for pagination
$count_query = "SELECT COUNT(*) as total FROM products WHERE quantity > 0";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_products = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_products / $records_per_page);

// Get stock valuation data with pagination
$query = "
    SELECT 
        p.id,
        p.sku,
        p.name,
        p.category,
        p.quantity,
        p.cost_price,
        p.price,
        (p.quantity * p.cost_price) as total_cost_value,
        (p.quantity * p.price) as total_retail_value,
        s.name as supplier_name
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.quantity > 0
    ORDER BY total_cost_value DESC
    LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals for all products (not just the paginated ones)
$totals_query = "
    SELECT 
        SUM(p.quantity * p.cost_price) as total_cost_value,
        SUM(p.quantity * p.price) as total_retail_value,
        COUNT(*) as total_products,
        SUM(p.quantity) as total_quantity
    FROM products p
    WHERE p.quantity > 0";

$totals_stmt = $db->prepare($totals_query);
$totals_stmt->execute();
$totals = $totals_stmt->fetch(PDO::FETCH_ASSOC);

$total_cost_value = $totals['total_cost_value'] ?? 0;
$total_retail_value = $totals['total_retail_value'] ?? 0;
$total_products_count = $totals['total_products'] ?? 0;
$total_quantity = $totals['total_quantity'] ?? 0;

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-chart-bar"></i> Stock Valuation Report</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>$<?php echo number_format($total_cost_value, 2); ?></h4>
                        <p>Total Cost Value</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>$<?php echo number_format($total_retail_value, 2); ?></h4>
                        <p>Total Retail Value</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-tags fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $total_products_count; ?></h4>
                        <p>Products in Stock</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-cubes fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $total_quantity; ?></h4>
                        <p>Total Quantity</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-boxes fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Valuation Report -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-table"></i> Detailed Stock Valuation
        </h5>
        <div class="small text-muted">
            Showing <?php echo min($offset + 1, $total_products); ?> 
            to <?php echo min($offset + $records_per_page, $total_products); ?> 
            of <?php echo $total_products; ?> products
        </div>
    </div>
    <div class="card-body">
        <?php if ($products): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="valuationTable">
                    <thead class="table-dark">
                        <tr>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Cost Price</th>
                            <th>Retail Price</th>
                            <th>Cost Value</th>
                            <th>Retail Value</th>
                            <th>Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['sku']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td>$<?php echo number_format($product['cost_price'], 2); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><strong>$<?php echo number_format($product['total_cost_value'], 2); ?></strong></td>
                            <td><strong>$<?php echo number_format($product['total_retail_value'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-active">
                        <tr>
                            <td colspan="6"><strong>Totals:</strong></td>
                            <td><strong>$<?php echo number_format($total_cost_value, 2); ?></strong></td>
                            <td><strong>$<?php echo number_format($total_retail_value, 2); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Products pagination">
                <ul class="pagination justify-content-center">
                    <!-- Previous Button -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    // Show first page and ellipsis if needed
                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Page numbers
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                    }
                    
                    // Show last page and ellipsis if needed
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Next Button -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <!-- Export Options -->
            <div class="mt-3">
                <button class="btn btn-success" onclick="exportToCSV()">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </button>
                <button class="btn btn-danger" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i> Export to PDF
                </button>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                <h4>No stock data available</h4>
                <p class="text-muted">Add products with cost prices to generate valuation reports.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportToCSV() {
    let csv = [];
    let rows = document.querySelectorAll("#valuationTable tr");
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (let j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
        }
        
        csv.push(row.join(","));        
    }

    // Download CSV file
    downloadCSV(csv.join("\n"), 'stock_valuation.csv');
}

function downloadCSV(csv, filename) {
    let csvFile = new Blob([csv], {type: "text/csv"});
    let downloadLink = document.createElement("a");
    
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function exportToPDF() {
    alert('PDF export would be implemented with a library like jsPDF or server-side generation.');
    // In a real implementation, you would use jsPDF or make an AJAX call to a PDF generation script
}
</script>

<?php require_once "../includes/footer.php"; ?>