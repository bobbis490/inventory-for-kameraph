<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin access required

ensure_warehouse_tables_exist($conn);

$pageTitle = 'Warehouse Product Inventory';
$activeMenu = 'warehouse';

// Handle quick inline bin update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_bin') {
    $invId = (int)($_POST['inventory_id'] ?? 0);
    $aisle = trim($_POST['aisle'] ?? 'A1');
    $rack = trim($_POST['rack'] ?? 'R1');
    $shelf = trim($_POST['shelf'] ?? 'S1');
    $bin = trim($_POST['bin'] ?? 'B1');
    $reorder = max(0, (int)($_POST['reorder_level'] ?? 5));
    $reorderQty = max(1, (int)($_POST['reorder_qty'] ?? 15));

    if ($invId > 0) {
        $upd = $conn->prepare("UPDATE warehouse_inventory SET Aisle = ?, Rack = ?, Shelf = ?, Bin = ?, ReorderLevel = ?, ReorderQuantity = ? WHERE InventoryID = ?");
        $upd->bind_param("ssssiii", $aisle, $rack, $shelf, $bin, $reorder, $reorderQty, $invId);
        if ($upd->execute()) {
            flash('success', 'Storage location and reorder thresholds updated successfully.');
        } else {
            flash('danger', 'Failed to update storage location.');
        }
        $upd->close();
    }
    redirect('warehouse/inventory.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
}

// Filters
$whFilter = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
$statusFilter = trim($_GET['status'] ?? '');
$searchFilter = trim($_GET['search'] ?? '');

$filters = [
    'warehouse_id' => $whFilter,
    'status' => $statusFilter,
    'search' => $searchFilter
];

$items = get_warehouse_inventory($conn, $filters);
$warehouses = get_warehouses($conn);

// Calculate totals for currently filtered list
$totalFilteredUnits = 0;
$totalFilteredVal = 0.0;
foreach ($items as $it) {
    $totalFilteredUnits += (int)$it['QuantityOnHand'];
    $totalFilteredVal += (float)$it['Price'] * (int)$it['QuantityOnHand'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="dashboard-title">
            <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Product Inventory & Bin Locations
        </h2>
        <p class="dashboard-subtitle">
            Manage multi-location camera gear storage, physical bins, on-hand counts, and reorder levels.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
        <a href="<?php echo base_url('warehouse/movement.php?action=in'); ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-box-arrow-in-down me-1"></i> Stock In
        </a>
        <a href="<?php echo base_url('warehouse/transfer.php'); ?>" class="btn btn-info text-white shadow-sm">
            <i class="bi bi-arrow-left-right me-1"></i> Transfer Stock
        </a>
        <a href="<?php echo base_url('warehouse/movement.php?action=adjust'); ?>" class="btn btn-warning text-dark shadow-sm">
            <i class="bi bi-sliders me-1"></i> Adjust Stock
        </a>
    </div>
</div>

<!-- Filter Bar Card -->
<div class="card card-kamera mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="small text-muted fw-bold mb-1">Warehouse Facility</label>
                <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- All Warehouses --</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo $wh['WarehouseID']; ?>" <?php echo ($whFilter === (int)$wh['WarehouseID']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($wh['WarehouseCode'] . ' - ' . $wh['WarehouseName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small text-muted fw-bold mb-1">Stock Level Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="" <?php echo ($statusFilter === '') ? 'selected' : ''; ?>>All Stock Levels</option>
                    <option value="in_stock" <?php echo ($statusFilter === 'in_stock') ? 'selected' : ''; ?>>In Stock (Healthy)</option>
                    <option value="low_stock" <?php echo ($statusFilter === 'low_stock') ? 'selected' : ''; ?>>Low Stock (<= Reorder Level)</option>
                    <option value="out_of_stock" <?php echo ($statusFilter === 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock (0 units)</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="small text-muted fw-bold mb-1">Search Product / SKU / Bin</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search title, model, brand, bin..." value="<?php echo sanitize($searchFilter); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    <?php if ($searchFilter !== '' || $whFilter > 0 || $statusFilter !== ''): ?>
                        <a href="inventory.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-2 text-md-end pt-md-3">
                <div class="small text-muted">Filtered Valuation:</div>
                <div class="fw-bold text-dark"><?php echo format_price($totalFilteredVal); ?></div>
            </div>
        </form>
    </div>
</div>

<!-- Main Inventory Table Card -->
<div class="card card-kamera">
    <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold text-dark">
            <i class="bi bi-boxes me-2 text-primary"></i>Warehouse Stock Records (<?php echo count($items); ?> items found)
        </span>
        <div class="small text-muted">
            Total Units in View: <strong><?php echo number_format($totalFilteredUnits); ?> units</strong>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-muted">
                <tr>
                    <th style="width: 50px;">Photo</th>
                    <th>Product & Model</th>
                    <th>Warehouse Hub</th>
                    <th>Storage Location (Bin)</th>
                    <th class="text-center">On Hand</th>
                    <th class="text-center">Reserved</th>
                    <th class="text-center">Available</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            No warehouse inventory records found matching your filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): 
                        $avail = (int)$item['QuantityAvailable'];
                        $onHand = (int)$item['QuantityOnHand'];
                        $res = (int)$item['QuantityReserved'];
                        $reorder = (int)$item['ReorderLevel'];

                        if ($avail <= 0) {
                            $stockBadge = '<span class="badge bg-danger">Out of Stock</span>';
                        } elseif ($avail <= $reorder) {
                            $stockBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i>Low Stock</span>';
                        } else {
                            $stockBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle">In Stock</span>';
                        }
                    ?>
                    <tr>
                        <td>
                            <img src="<?php echo image_url($item['PrimaryImage']); ?>" alt="Photo" class="rounded object-fit-cover" style="width: 44px; height: 44px; border: 1px solid #e2e8f0;">
                        </td>
                        <td>
                            <div class="fw-bold text-dark text-truncate" style="max-width: 240px;" title="<?php echo sanitize($item['ListingTitle']); ?>">
                                <a href="<?php echo base_url('product_listing/view.php?id=' . $item['ListingID']); ?>" class="text-dark text-decoration-none hover-primary">
                                    <?php echo sanitize($item['ListingTitle']); ?>
                                </a>
                            </div>
                            <div class="small text-muted">
                                <?php echo sanitize($item['BrandName'] . ' ' . $item['ModelName']); ?>
                                &bull; <span class="badge bg-light text-dark border"><?php echo sanitize($item['Condition']); ?></span>
                            </div>
                            <small class="text-primary fw-semibold"><?php echo format_price($item['Price']); ?></small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><?php echo sanitize($item['WarehouseCode']); ?></div>
                            <small class="text-muted"><?php echo sanitize($item['WarehouseCity']); ?></small>
                        </td>
                        <td>
                            <div class="d-inline-flex align-items-center gap-1 font-monospace small bg-light p-1 px-2 rounded border">
                                <i class="bi bi-geo-alt text-primary"></i>
                                <span><?php echo sanitize($item['Aisle'] . '-' . $item['Rack'] . '-' . $item['Shelf'] . '-' . $item['Bin']); ?></span>
                            </div>
                            <div class="small text-muted mt-1">Reorder: <?php echo $reorder; ?> units</div>
                        </td>
                        <td class="text-center fw-bold text-dark">
                            <?php echo $onHand; ?>
                        </td>
                        <td class="text-center text-muted">
                            <?php echo $res; ?>
                        </td>
                        <td class="text-center">
                            <span class="fs-6 fw-bold <?php echo ($avail <= $reorder) ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $avail; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $stockBadge; ?>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Manage
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo base_url('warehouse/movement.php?action=in&listing_id=' . $item['ListingID'] . '&warehouse_id=' . $item['WarehouseID']); ?>">
                                            <i class="bi bi-box-arrow-in-down text-success me-2"></i>Stock In (Restock)
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo base_url('warehouse/transfer.php?listing_id=' . $item['ListingID'] . '&from_wh=' . $item['WarehouseID']); ?>">
                                            <i class="bi bi-arrow-left-right text-info me-2"></i>Transfer to Hub
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo base_url('warehouse/movement.php?action=adjust&listing_id=' . $item['ListingID'] . '&warehouse_id=' . $item['WarehouseID']); ?>">
                                            <i class="bi bi-sliders text-warning me-2"></i>Count Adjustment
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editBinModal<?php echo $item['InventoryID']; ?>">
                                            <i class="bi bi-geo text-primary me-2"></i>Edit Bin Location
                                        </button>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo base_url('product_listing/view.php?id=' . $item['ListingID']); ?>">
                                            <i class="bi bi-eye me-2"></i>View Product Details
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Edit Bin Modal -->
                            <div class="modal fade" id="editBinModal<?php echo $item['InventoryID']; ?>" tabindex="-1" aria-hidden="true" style="color: #334155; text-align: left;">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_bin">
                                            <input type="hidden" name="inventory_id" value="<?php echo $item['InventoryID']; ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold text-dark">
                                                    <i class="bi bi-geo-alt-fill text-primary me-2"></i>Edit Storage Bin & Thresholds
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="p-2 mb-3 bg-light rounded small">
                                                    <strong><?php echo sanitize($item['ListingTitle']); ?></strong><br>
                                                    <span class="text-muted">Facility: <?php echo sanitize($item['WarehouseName'] . ' (' . $item['WarehouseCode'] . ')'); ?></span>
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-3">
                                                        <label class="form-label small fw-bold">Aisle</label>
                                                        <input type="text" name="aisle" class="form-control form-control-sm" value="<?php echo sanitize($item['Aisle']); ?>" required>
                                                    </div>
                                                    <div class="col-3">
                                                        <label class="form-label small fw-bold">Rack</label>
                                                        <input type="text" name="rack" class="form-control form-control-sm" value="<?php echo sanitize($item['Rack']); ?>" required>
                                                    </div>
                                                    <div class="col-3">
                                                        <label class="form-label small fw-bold">Shelf</label>
                                                        <input type="text" name="shelf" class="form-control form-control-sm" value="<?php echo sanitize($item['Shelf']); ?>" required>
                                                    </div>
                                                    <div class="col-3">
                                                        <label class="form-label small fw-bold">Bin</label>
                                                        <input type="text" name="bin" class="form-control form-control-sm" value="<?php echo sanitize($item['Bin']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <label class="form-label small fw-bold">Reorder Alert Level</label>
                                                        <input type="number" name="reorder_level" class="form-control form-control-sm" value="<?php echo (int)$item['ReorderLevel']; ?>" min="0" required>
                                                        <small class="text-muted" style="font-size:0.75rem;">Alert when stock drops to this.</small>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label small fw-bold">Reorder Suggested Qty</label>
                                                        <input type="number" name="reorder_qty" class="form-control form-control-sm" value="<?php echo (int)$item['ReorderQuantity']; ?>" min="1" required>
                                                        <small class="text-muted" style="font-size:0.75rem;">Standard replenishment batch.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
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
