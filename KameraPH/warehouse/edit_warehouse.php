<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin access required

ensure_warehouse_tables_exist($conn);

$pageTitle = 'Edit Warehouse Facility';
$activeMenu = 'warehouse';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    flash('danger', 'Invalid warehouse ID.');
    redirect('warehouse/warehouses.php');
}

$wh = get_warehouse_by_id($conn, $id);
if (!$wh) {
    flash('danger', 'Warehouse not found.');
    redirect('warehouse/warehouses.php');
}

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
        // Check duplicate code on other warehouses
        $dupCheck = $conn->prepare("SELECT WarehouseID FROM warehouse WHERE WarehouseCode = ? AND WarehouseID != ?");
        $dupCheck->bind_param("si", $code, $id);
        $dupCheck->execute();
        if ($dupCheck->get_result()->fetch_assoc()) {
            $errorMsg = "A warehouse with code '{$code}' already exists.";
        } else {
            $upd = $conn->prepare("
                UPDATE warehouse SET
                WarehouseCode = ?, WarehouseName = ?, Address = ?, City = ?, Province = ?,
                PostalCode = ?, ContactPerson = ?, ContactPhone = ?, ContactEmail = ?,
                StorageCapacity = ?, Status = ?, Notes = ?
                WHERE WarehouseID = ?
            ");
            $upd->bind_param("sssssssssissi", $code, $name, $address, $city, $province, $postal, $person, $phone, $email, $capacity, $status, $notes, $id);
            if ($upd->execute()) {
                flash('success', "Warehouse facility '{$name}' [{$code}] updated successfully!");
                redirect('warehouse/warehouses.php');
            } else {
                $errorMsg = "Failed to update warehouse record: " . $conn->error;
            }
            $upd->close();
        }
        $dupCheck->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="dashboard-title">
            <i class="bi bi-pencil-square text-primary me-2"></i>Edit Warehouse: <?php echo sanitize($wh['WarehouseName']); ?>
        </h2>
        <p class="dashboard-subtitle">
            Update facility details, capacities, management contacts, and operational status.
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
    <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
        <span class="fw-bold">
            <i class="bi bi-building me-2 text-primary"></i>Warehouse Specifications [ID #<?php echo $id; ?>]
        </span>
        <span class="badge bg-secondary"><?php echo sanitize($wh['WarehouseCode']); ?></span>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Warehouse Code <span class="text-danger">*</span></label>
                    <input type="text" name="WarehouseCode" class="form-control" value="<?php echo sanitize($_POST['WarehouseCode'] ?? $wh['WarehouseCode']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small">Facility Name <span class="text-danger">*</span></label>
                    <input type="text" name="WarehouseName" class="form-control" value="<?php echo sanitize($_POST['WarehouseName'] ?? $wh['WarehouseName']); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small">Operational Status</label>
                    <select name="Status" class="form-select">
                        <option value="Active" <?php echo ($wh['Status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Under Maintenance" <?php echo ($wh['Status'] === 'Under Maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                        <option value="Inactive" <?php echo ($wh['Status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small">Street Address <span class="text-danger">*</span></label>
                    <input type="text" name="Address" class="form-control" value="<?php echo sanitize($_POST['Address'] ?? $wh['Address']); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small">City / Municipality <span class="text-danger">*</span></label>
                    <input type="text" name="City" class="form-control" value="<?php echo sanitize($_POST['City'] ?? $wh['City']); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small">Province / Region</label>
                    <input type="text" name="Province" class="form-control" value="<?php echo sanitize($_POST['Province'] ?? $wh['Province']); ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold small">Postal Code</label>
                    <input type="text" name="PostalCode" class="form-control" value="<?php echo sanitize($_POST['PostalCode'] ?? $wh['PostalCode']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small">Contact Person / Manager</label>
                    <input type="text" name="ContactPerson" class="form-control" value="<?php echo sanitize($_POST['ContactPerson'] ?? $wh['ContactPerson']); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small">Contact Phone</label>
                    <input type="text" name="ContactPhone" class="form-control" value="<?php echo sanitize($_POST['ContactPhone'] ?? $wh['ContactPhone']); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small">Contact Email</label>
                    <input type="email" name="ContactEmail" class="form-control" value="<?php echo sanitize($_POST['ContactEmail'] ?? $wh['ContactEmail']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small">Maximum Unit Capacity</label>
                    <input type="number" name="StorageCapacity" class="form-control" min="100" value="<?php echo (int)($_POST['StorageCapacity'] ?? $wh['StorageCapacity']); ?>" required>
                    <small class="text-muted" style="font-size:0.75rem;">Current stored: <?php echo number_format($wh['CurrentUnits']); ?> units</small>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-bold small">Facility Notes & Handling Features</label>
                    <input type="text" name="Notes" class="form-control" value="<?php echo sanitize($_POST['Notes'] ?? $wh['Notes']); ?>">
                </div>

                <div class="col-12 text-end pt-3 border-top">
                    <a href="<?php echo base_url('warehouse/warehouses.php'); ?>" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
