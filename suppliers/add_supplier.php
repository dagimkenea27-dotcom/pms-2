<?php
// suppliers/add_supplier.php
session_start();
require_once "../config/database.php";
require_once "../models/Supplier.php";

$database = new Database();
$db = $database->getConnection();
$supplier = new Supplier($db);

$message = '';
$message_type = '';

if ($_POST) {
    $supplier->name = $_POST['name'];
    $supplier->contact_person = $_POST['contact_person'];
    $supplier->email = $_POST['email'];
    $supplier->phone = $_POST['phone'];
    $supplier->address = $_POST['address'];
    $supplier->website = $_POST['website'];
    $supplier->payment_terms = $_POST['payment_terms'];
    $supplier->notes = $_POST['notes'];
    
    if ($supplier->create()) {
        $message = "Supplier added successfully!";
        $message_type = "success";
        // Clear form
        $_POST = array();
    } else {
        $message = "Error adding supplier.";
        $message_type = "danger";
    }
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-plus"></i> Add New Supplier</h1>
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
                                <label for="name" class="form-label">Supplier Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo $_POST['name'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person"
                                       value="<?php echo $_POST['contact_person'] ?? ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo $_POST['email'] ?? ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                       value="<?php echo $_POST['phone'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website"
                                       value="<?php echo $_POST['website'] ?? ''; ?>" 
                                       placeholder="https://">
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_terms" class="form-label">Payment Terms</label>
                                <input type="text" class="form-control" id="payment_terms" name="payment_terms"
                                       value="<?php echo $_POST['payment_terms'] ?? ''; ?>"
                                       placeholder="e.g., Net 30, Net 60">
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" 
                                          rows="3"><?php echo $_POST['address'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" 
                                          rows="2"><?php echo $_POST['notes'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Supplier
                    </button>
                    <a href="view_suppliers.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Suppliers
                    </a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="fas fa-info-circle"></i> Tips</h6>
            </div>
            <div class="card-body">
                <ul class="small text-muted">
                    <li>Use consistent naming for suppliers</li>
                    <li>Include complete contact information</li>
                    <li>Set payment terms to manage accounts payable</li>
                    <li>Add notes for special instructions or agreements</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>