<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin access required

ensure_warehouse_tables_exist($conn);

$pageTitle = 'Add Warehouse Facility';
$activeMenu = 'warehouse';

$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['WarehouseCode'] ?? ''));
    $name = trim($_POST['WarehouseName'] ?? '');
    $address = trim($_POST['Address'] ?? '');
    $city = trim($_POST['City'] ?? '');
    $province = trim($_POST['Province'] ?? '');
    $postal = trim($_POST['PostalCode'] ?? '');
    $person = trim($_POST['ContactPerson'] ?? '');
    $phone = trim($_POST['ContactPhone'] ?? '');
    $email = trim($_POST['ContactEmail'] ?? '');
    $capacity = max(100, (int)($_POST['StorageCapacity'] ?? 5000));
    $status = $_POST['Status'] ?? 'Active';
    $notes = trim($_POST['Notes'] ?? '');

    if ($code === '' || $name === '' || $address === '' || $city === '') {
        $errorMsg = "Warehouse Code, Facility Name, Address, and City are required fields.";
    } else {
        // Check duplicate code
        $dupCheck = $conn->prepare("SELECT WarehouseID FROM warehouse WHERE WarehouseCode = ?");
        $dupCheck->bind_param("s", $code);
        $dupCheck->execute();
        if ($dupCheck->get_result()->fetch_assoc()) {
            $errorMsg = "A warehouse with code '{$code}' already exists. Please choose a unique code.";
        } else {
            $ins = $conn->prepare("
                INSERT INTO warehouse
                (WarehouseCode, WarehouseName, Address, City, Province, PostalCode, ContactPerson, ContactPhone, ContactEmail, StorageCapacity, Status, Notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->bind_param("sssssssssiss", $code, $name, $address, $city, $province, $postal, $person, $phone, $email, $capacity, $status, $notes);
            if ($ins->execute()) {
                flash('success', "Warehouse facility '{$name}' [{$code}] created successfully!");
                redirect('warehouse/warehouses.php');
            } else {
                $errorMsg = "Failed to create warehouse record: " . $conn->error;
            }
            $ins->close();
        }
        $dupCheck->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="dashboard-title">
            <i class="bi bi-plus-circle text-primary me-2"></i>Add New Warehouse Facility
        </h2>
        <p class="dashboard-subtitle">
            Register a new storage depot or regional fulfillment facility in the KameraPH logistics network.
        </p>
    </div>
    <div>
        <a href="<?php echo base_url('warehouse/warehouses.php'); ?>" class="btn btn-outline-light">
            <i class="bi bi-arrow-left me-1"></i> Back to Facilities
        </a>
    </div>
</div>

<?php if ($errorMsg !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo sanitize($errorMsg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card card-kamera shadow text-dark">
    <div class="card-header card-header-kamera">
        <span class="fw-bold">
            <i class="bi bi-building me-2 text-primary"></i>Warehouse Specifications
        </span>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Warehouse Code <span class="text-danger">*</span></label>
                    <input type="text" name="WarehouseCode" class="form-control" placeholder="e.g. WH-MNL-02" value="<?php echo sanitize($_POST['WarehouseCode'] ?? ''); ?>" required>
                    <small class="text-muted" style="font-size:0.75rem;">Unique identifier code.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small">Facility Name <span class="text-danger">*</span></label>
                    <input type="text" name="WarehouseName" class="form-control" placeholder="e.g. Pasig Optical & Lens Hub" value="<?php echo sanitize($_POST['WarehouseName'] ?? ''); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small">Operational Status</label>
                    <select name="Status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small">Street Address <span class="text-danger">*</span></label>
                    <input type="text" name="Address" class="form-control" placeholder="Building #, Street, Logistics Park" value="<?php echo sanitize($_POST['Address'] ?? ''); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small">City / Municipality <span class="text-danger">*</span></label>
                    <input type="text" name="City" class="form-control" placeholder="e.g. Pasig City" value="<?php echo sanitize($_POST['City'] ?? ''); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small">Province / Region</label>
                    <input type="text" name="Province" class="form-control" placeholder="e.g. Metro Manila" value="<?php echo sanitize($_POST['Province'] ?? ''); ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold small">Postal Code</label>
                    <input type="text" name="PostalCode" class="form-control" placeholder="e.g. 1600" value="<?php echo sanitize($_POST['PostalCode'] ?? ''); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small">Contact Person / Manager</label>
                    <input type="text" name="ContactPerson" class="form-control" placeholder="e.g. Jose Ramirez" value="<?php echo sanitize($_POST['ContactPerson'] ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small">Contact Phone</label>
                    <input type="text" name="ContactPhone" class="form-control" placeholder="e.g. +63 917 555 1234" value="<?php echo sanitize($_POST['ContactPhone'] ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small">Contact Email</label>
                    <input type="email" name="ContactEmail" class="form-control" placeholder="e.g. pasig.wh@kameraph.com" value="<?php echo sanitize($_POST['ContactEmail'] ?? ''); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small">Maximum Unit Capacity</label>
                    <input type="number" name="StorageCapacity" class="form-control" min="100" value="<?php echo (int)($_POST['StorageCapacity'] ?? 5000); ?>" required>
                    <small class="text-muted" style="font-size:0.75rem;">Total physical units the facility can store.</small>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-bold small">Facility Notes & Handling Features</label>
                    <input type="text" name="Notes" class="form-control" placeholder="e.g. Climate-controlled room for vintage Leica and Hasselblad bodies..." value="<?php echo sanitize($_POST['Notes'] ?? ''); ?>">
                </div>

                <div class="col-12 text-end pt-3 border-top">
                    <a href="<?php echo base_url('warehouse/warehouses.php'); ?>" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">
                        <i class="bi bi-check-circle me-1"></i>Save Warehouse Facility
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
