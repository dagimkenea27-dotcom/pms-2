<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../models/Customer.php';

Auth::requireLogin();
$currentUser = Auth::getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$customer = new Customer($db);

$message = '';
$error = '';
$search_query = $_GET['search'] ?? '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_customer'])) {
        $data = [
            'customer_code' => $_POST['customer_code'] ?? null,
            'name' => $_POST['name'],
            'email' => $_POST['email'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'company_name' => $_POST['company_name'] ?? null,
            'notification_preference' => $_POST['notification_preference'] ?? 'email',
            'notes' => $_POST['notes'] ?? null
        ];
        
        $customer_id = $customer->create($data);
        
        if ($customer_id && isset($_POST['address_line1'])) {
            // Add address
            $address_data = [
                'customer_id' => $customer_id,
                'address_label' => $_POST['address_label'] ?? 'Primary',
                'address_line1' => $_POST['address_line1'],
                'address_line2' => $_POST['address_line2'] ?? null,
                'city' => $_POST['city'] ?? null,
                'state' => $_POST['state'] ?? null,
                'postal_code' => $_POST['postal_code'] ?? null,
                'country' => $_POST['country'] ?? 'Ethiopia',
                'latitude' => $_POST['latitude'] ?? null,
                'longitude' => $_POST['longitude'] ?? null,
                'special_instructions' => $_POST['special_instructions'] ?? null,
                'is_default' => 1
            ];
            
            $customer->addAddress($address_data);
        }
        
        if ($customer_id) {
            $message = "Customer added to address book!";
        } else {
            $error = "Failed to add customer.";
        }
    }
}

// Get customers
if ($search_query) {
    $customers_result = $customer->search($search_query);
} else {
    $customers_result = $customer->getAll();
}

require_once '../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-address-book"></i> Address Book</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
        <i class="fas fa-plus"></i> Add New Contact
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Search -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, or customer code..." value="<?= htmlspecialchars($search_query) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Customers List -->
<div class="row">
    <?php while ($c = $customers_result->fetch(PDO::FETCH_ASSOC)): ?>
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($c['name']) ?></h5>
                            <?php if ($c['company_name']): ?>
                                <p class="text-muted small mb-0"><?= htmlspecialchars($c['company_name']) ?></p>
                            <?php endif; ?>
                            <?php if ($c['customer_code']): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($c['customer_code']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="useInRoute(<?= $c['id'] ?>)">
                                        <i class="fas fa-route"></i> Use in Route
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="viewAddresses(<?= $c['id'] ?>)">
                                        <i class="fas fa-map-marker-alt"></i> View Addresses
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="contact-info">
                        <?php if ($c['phone']): ?>
                            <p class="mb-1">
                                <i class="fas fa-phone text-primary"></i> <?= htmlspecialchars($c['phone']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($c['email']): ?>
                            <p class="mb-1">
                                <i class="fas fa-envelope text-primary"></i> <?= htmlspecialchars($c['email']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($c['address_line1']): ?>
                            <p class="mb-1">
                                <i class="fas fa-map-marker-alt text-primary"></i> 
                                <?= htmlspecialchars($c['address_line1']) ?>
                                <?php if ($c['city']): ?>
                                    , <?= htmlspecialchars($c['city']) ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Customer Code</label>
                            <input type="text" name="customer_code" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control">
                        </div>
                        
                        <div class="col-12"><hr></div>
                        <div class="col-12"><h6>Primary Address</h6></div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Address Label</label>
                            <input type="text" name="address_label" class="form-control" value="Primary">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Address Line 1</label>
                            <input type="text" name="address_line1" class="form-control">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="address_line2" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="Ethiopia">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Special Instructions</label>
                            <textarea name="special_instructions" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_customer" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Contact
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function useInRoute(customerId) {
    // Redirect to route optimizer with customer pre-selected
    window.location.href = '../routes/index.php?customer_id=' + customerId;
}

function viewAddresses(customerId) {
    // Could open a modal or redirect to customer detail page
    alert('View addresses for customer ' + customerId);
}
</script>

<?php require_once '../includes/footer.php'; ?>
