<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin access required

ensure_warehouse_tables_exist($conn);

$pageTitle = 'Warehouse Facilities';
$activeMenu = 'warehouse';

// Handle Toggle Status / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $whId = (int)($_POST['warehouse_id'] ?? 0);
    if ($whId > 0) {
        if ($_POST['action'] === 'toggle_status') {
            $newStatus = ($_POST['current_status'] === 'Active') ? 'Inactive' : 'Active';
            $stmt = $conn->prepare("UPDATE warehouse SET Status = ? WHERE WarehouseID = ?");
            $stmt->bind_param("si", $newStatus, $whId);
            $stmt->execute();
            $stmt->close();
            flash('success', "Warehouse status updated to {$newStatus}.");
        } elseif ($_POST['action'] === 'delete') {
            // Check if stock exists
            $check = $conn->query("SELECT SUM(QuantityOnHand) AS total FROM warehouse_inventory WHERE WarehouseID = {$whId}");
            $totalUnits = ($check && $row = $check->fetch_assoc()) ? (int)$row['total'] : 0;
            if ($totalUnits > 0) {
                flash('danger', "Cannot remove warehouse while it holds {$totalUnits} physical units of inventory. Transfer or stock out the items first.");
            } else {
                $del = $conn->prepare("DELETE FROM warehouse WHERE WarehouseID = ?");
                $del->bind_param("i", $whId);
                $del->execute();
                $del->close();
                flash('success', "Warehouse removed successfully.");
            }
        }
    }
    redirect('warehouse/warehouses.php');
}

$warehouses = get_warehouses($conn);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="dashboard-title">
            <i class="bi bi-buildings text-primary me-2"></i>Warehouse Facilities Directory
        </h2>
        <p class="dashboard-subtitle">
            Configure regional storage facilities, physical storage capacities, contact coordinators, and operational statuses.
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="<?php echo base_url('warehouse/add_warehouse.php'); ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Warehouse
        </a>
        <a href="<?php echo base_url('warehouse/inventory.php'); ?>" class="btn btn-outline-light">
            <i class="bi bi-boxes me-1"></i> View All Stock
        </a>
    </div>
</div>

<div class="card card-kamera shadow">
    <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
        <span class="fw-bold text-dark">
            <i class="bi bi-building me-2 text-primary"></i>All Configured Warehouses (<?php echo count($warehouses); ?>)
        </span>
        <small class="text-muted">Total Storage Network</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-muted">
                <tr>
                    <th>Facility Code & Name</th>
                    <th>Address & Location</th>
                    <th>Contact & Manager</th>
                    <th>Capacity & Utilization</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($warehouses)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            No warehouses configured yet. Click "Add New Warehouse" to create one.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($warehouses as $wh): 
                        $rate = $wh['UtilizationRate'];
                        $progColor = ($rate > 85) ? 'bg-danger' : (($rate > 60) ? 'bg-warning' : 'bg-success');
                    ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary mb-1"><?php echo sanitize($wh['WarehouseCode']); ?></span>
                            <div class="fw-bold text-dark fs-6"><?php echo sanitize($wh['WarehouseName']); ?></div>
                            <small class="text-muted"><i class="bi bi-box me-1"></i><?php echo number_format($wh['DistinctProductsCount']); ?> product listings stored</small>
                        </td>
                        <td class="small">
                            <div><?php echo sanitize($wh['Address']); ?></div>
                            <div class="text-muted"><?php echo sanitize($wh['City'] . ', ' . ($wh['Province'] ?: '')); ?> <?php echo sanitize($wh['PostalCode']); ?></div>
                        </td>
                        <td class="small">
                            <div class="fw-semibold text-dark"><i class="bi bi-person me-1"></i><?php echo sanitize($wh['ContactPerson'] ?: 'Not Assigned'); ?></div>
                            <div class="text-muted"><i class="bi bi-telephone me-1"></i><?php echo sanitize($wh['ContactPhone'] ?: '—'); ?></div>
                            <div class="text-muted"><i class="bi bi-envelope me-1"></i><?php echo sanitize($wh['ContactEmail'] ?: '—'); ?></div>
                        </td>
                        <td style="min-width: 180px;">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span><?php echo number_format($wh['CurrentUnits']); ?> / <?php echo number_format($wh['StorageCapacity']); ?> units</span>
                                <span class="fw-bold"><?php echo $rate; ?>%</span>
                            </div>
                            <div class="progress" style="height: 7px;">
                                <div class="progress-bar <?php echo $progColor; ?>" role="progressbar" style="width: <?php echo $rate; ?>%;"></div>
                            </div>
                        </td>
                        <td>
                            <?php if ($wh['Status'] === 'Active'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            <?php elseif ($wh['Status'] === 'Under Maintenance'): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Maintenance</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo base_url('warehouse/inventory.php?warehouse_id=' . $wh['WarehouseID']); ?>" class="btn btn-outline-primary" title="View Stock">
                                    <i class="bi bi-eye"></i> Stock
                                </a>
                                <a href="<?php echo base_url('warehouse/edit_warehouse.php?id=' . $wh['WarehouseID']); ?>" class="btn btn-outline-secondary" title="Edit Hub">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Change status for this warehouse?');">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="warehouse_id" value="<?php echo $wh['WarehouseID']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $wh['Status']; ?>">
                                    <button type="submit" class="btn btn-outline-warning" title="Toggle Status">
                                        <i class="bi bi-power"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
