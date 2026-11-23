<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin access required

ensure_warehouse_tables_exist($conn);

$pageTitle = 'Stock Movements & Inventory Log';
$activeMenu = 'warehouse';

$currUser = current_user();
$adminName = $currUser['FullName'] ?? ($currUser['FirstName'] ?? 'Admin');

$actionParam = trim($_GET['action'] ?? 'all');
$preListingId = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
$preWarehouseId = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;

$errorMsg = "";
$successMsg = "";

// Handle Form Submission for Stock In / Out / Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_movement'])) {
    try {
        $movType = trim($_POST['movement_type'] ?? 'Stock In');
        $listingId = (int)($_POST['listing_id'] ?? 0);
        $whId = (int)($_POST['warehouse_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        $unitCost = !empty($_POST['unit_cost']) ? (float)$_POST['unit_cost'] : null;
        $ref = trim($_POST['reference_number'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($listingId <= 0) {
            throw new InvalidArgumentException("Please select a valid product listing.");
        }
        if ($whId <= 0) {
            throw new InvalidArgumentException("Please select a target warehouse.");
        }
        if ($qty <= 0 && $movType !== 'Adjustment') {
            throw new InvalidArgumentException("Quantity must be greater than zero.");
        }

        if (empty($ref)) {
            $prefix = ($movType === 'Stock In') ? 'IN' : (($movType === 'Stock Out') ? 'OUT' : 'ADJ');
            $ref = $prefix . '-' . date('Ymd-His');
        }

        $data = [
            'MovementType' => $movType,
            'ReferenceNumber' => $ref,
            'ListingID' => $listingId,
            'Quantity' => $qty,
            'UnitCost' => $unitCost,
            'Reason' => $reason,
            'Notes' => $notes,
            'PerformedBy' => $adminName
        ];

        if ($movType === 'Stock In' || $movType === 'Return') {
            $data['ToWarehouseID'] = $whId;
        } else {
            $data['FromWarehouseID'] = $whId;
        }

        if ($movType === 'Adjustment' && isset($_POST['new_on_hand'])) {
            $data['NewOnHand'] = max(0, (int)$_POST['new_on_hand']);
        }

        record_stock_movement($conn, $data);
        flash('success', "Stock transaction [{$ref}] processed successfully! Warehouse inventory and product catalog synchronized.");
        redirect('warehouse/movement.php');
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

// Filters for movement table
$typeFilter = trim($_GET['type'] ?? '');
$whFilter = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
$searchFilter = trim($_GET['search'] ?? '');

$filterParams = [
    'movement_type' => $typeFilter,
    'warehouse_id' => $whFilter,
    'search' => $searchFilter
];

$movements = get_stock_movements($conn, 100, $filterParams);
$warehouses = get_warehouses($conn, true);

// Fetch all active listings for the dropdown
$listingsRes = $conn->query("
    SELECT pl.ListingID, pl.Title, pl.Price, pm.ModelName, b.BrandName
    FROM product_listing pl
    INNER JOIN product_model pm ON pl.ModelID = pm.ModelID
    INNER JOIN brand b ON pm.BrandID = b.BrandID
    ORDER BY b.BrandName ASC, pm.ModelName ASC, pl.Title ASC
");
$allListings = $listingsRes ? $listingsRes->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="dashboard-title">
            <i class="bi bi-arrow-down-up text-primary me-2"></i>Stock Movements & Audit Logs
        </h2>
        <p class="dashboard-subtitle">
            Record goods receipt (Stock In), dispatches, returns, and inventory adjustments with complete audit trails.
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <button class="btn btn-primary shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#newMovementCollapse" aria-expanded="<?php echo ($actionParam === 'in' || $actionParam === 'out' || $actionParam === 'adjust') ? 'true' : 'false'; ?>">
            <i class="bi bi-plus-circle me-1"></i> New Stock Transaction
        </button>
        <a href="<?php echo base_url('warehouse/transfer.php'); ?>" class="btn btn-info text-white shadow-sm">
            <i class="bi bi-arrow-left-right me-1"></i> Inter-Hub Transfer
        </a>
    </div>
</div>

<?php if ($errorMsg !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo sanitize($errorMsg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Collapsible Form for New Transaction -->
<div class="collapse mb-4 <?php echo ($actionParam === 'in' || $actionParam === 'out' || $actionParam === 'adjust' || $errorMsg !== '') ? 'show' : ''; ?>" id="newMovementCollapse">
    <div class="card card-kamera border-primary shadow">
        <div class="card-header card-header-kamera bg-primary text-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">
                <i class="bi bi-box-arrow-in-down-fill me-2"></i>Record New Stock Movement
            </span>
            <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse" data-bs-target="#newMovementCollapse"></button>
        </div>
        <div class="card-body p-4 text-dark">
            <form method="POST">
                <input type="hidden" name="submit_movement" value="1">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Movement Type <span class="text-danger">*</span></label>
                        <select name="movement_type" id="movementTypeSelect" class="form-select" required onchange="toggleMovementFields()">
                            <option value="Stock In" <?php echo ($actionParam === 'in') ? 'selected' : ''; ?>>Stock In (Supplier Receipt / Ingestion)</option>
                            <option value="Stock Out" <?php echo ($actionParam === 'out') ? 'selected' : ''; ?>>Stock Out (Dispatch / Customer Order)</option>
                            <option value="Adjustment" <?php echo ($actionParam === 'adjust') ? 'selected' : ''; ?>>Adjustment (Cycle Count Correction)</option>
                            <option value="Return">Return (Customer / RMA Return)</option>
                            <option value="Damage/Loss">Damage / Scrapped / Loss</option>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold small">Product Camera / Equipment <span class="text-danger">*</span></label>
                        <select name="listing_id" class="form-select" required>
                            <option value="">-- Select Camera Gear Listing --</option>
                            <?php foreach ($allListings as $l): ?>
                                <option value="<?php echo $l['ListingID']; ?>" <?php echo ($preListingId === (int)$l['ListingID']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($l['BrandName'] . ' ' . $l['ModelName'] . ' - ' . $l['Title'] . ' (' . format_price($l['Price']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Warehouse Facility <span class="text-danger">*</span></label>
                        <select name="warehouse_id" class="form-select" required>
                            <option value="">-- Select Warehouse Hub --</option>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?php echo $wh['WarehouseID']; ?>" <?php echo ($preWarehouseId === (int)$wh['WarehouseID']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($wh['WarehouseCode'] . ' - ' . $wh['WarehouseName'] . ' (' . $wh['City'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3" id="quantityField">
                        <label class="form-label fw-bold small">Quantity (Units) <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>

                    <div class="col-md-3 d-none" id="newOnHandField">
                        <label class="form-label fw-bold small">New Actual Count (On Hand) <span class="text-danger">*</span></label>
                        <input type="number" name="new_on_hand" class="form-control" min="0" value="0">
                        <small class="text-muted" style="font-size:0.75rem;">System will adjust stock to match this physical count.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Unit Cost (₱)</label>
                        <input type="number" step="0.01" name="unit_cost" class="form-control" placeholder="Optional purchasing cost">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Reference / PO / Slip #</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="Auto-generated if empty">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Reason / Event</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Regular Restock, Vendor PO #450">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small">Operational Notes / Comments</label>
                        <input type="text" name="notes" class="form-control" placeholder="Add serial batch, inspector notes, or condition details...">
                    </div>

                    <div class="col-12 text-end pt-2">
                        <button type="button" class="btn btn-secondary me-2" data-bs-toggle="collapse" data-bs-target="#newMovementCollapse">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-semibold px-4">
                            <i class="bi bi-check-circle me-1"></i>Confirm & Update Inventory
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleMovementFields() {
    const sel = document.getElementById('movementTypeSelect');
    const qtyCol = document.getElementById('quantityField');
    const newOnHandCol = document.getElementById('newOnHandField');
    if (sel && sel.value === 'Adjustment') {
        newOnHandCol.classList.remove('d-none');
    } else {
        newOnHandCol.classList.add('d-none');
    }
}
document.addEventListener('DOMContentLoaded', toggleMovementFields);
</script>

<!-- Filter Card -->
<div class="card card-kamera mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="small text-muted fw-bold mb-1">Movement Type</label>
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Movement Types</option>
                    <option value="Stock In" <?php echo ($typeFilter === 'Stock In') ? 'selected' : ''; ?>>Stock In</option>
                    <option value="Stock Out" <?php echo ($typeFilter === 'Stock Out') ? 'selected' : ''; ?>>Stock Out</option>
                    <option value="Transfer" <?php echo ($typeFilter === 'Transfer') ? 'selected' : ''; ?>>Transfer</option>
                    <option value="Adjustment" <?php echo ($typeFilter === 'Adjustment') ? 'selected' : ''; ?>>Adjustment</option>
                    <option value="Return" <?php echo ($typeFilter === 'Return') ? 'selected' : ''; ?>>Return</option>
                    <option value="Damage/Loss" <?php echo ($typeFilter === 'Damage/Loss') ? 'selected' : ''; ?>>Damage/Loss</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small text-muted fw-bold mb-1">Warehouse Facility</label>
                <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Warehouses</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo $wh['WarehouseID']; ?>" <?php echo ($whFilter === (int)$wh['WarehouseID']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($wh['WarehouseCode'] . ' - ' . $wh['WarehouseName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="small text-muted fw-bold mb-1">Search Movement</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search ref, product, user, reason..." value="<?php echo sanitize($searchFilter); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    <?php if ($searchFilter !== '' || $whFilter > 0 || $typeFilter !== ''): ?>
                        <a href="movement.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-2 text-md-end pt-md-3">
                <span class="badge bg-secondary"><?php echo count($movements); ?> Transactions</span>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Audit Table Card -->
<div class="card card-kamera">
    <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
        <span class="fw-bold text-dark">
            <i class="bi bi-clock-history me-2 text-primary"></i>Movement Transaction Ledger
        </span>
        <small class="text-muted">Showing latest 100 transactions</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-muted">
                <tr>
                    <th style="width: 100px;">ID / Ref</th>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Product & Model</th>
                    <th>Warehouse Source / Dest</th>
                    <th class="text-center">Quantity</th>
                    <th>Reason / Notes</th>
                    <th>Staff</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                            No stock movement records found matching your filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): 
                        $badgeCls = 'bg-secondary';
                        $sign = '+';
                        $qtyCls = 'text-success';

                        switch ($m['MovementType']) {
                            case 'Stock In':
                            case 'Return':
                                $badgeCls = 'bg-success';
                                $sign = '+';
                                $qtyCls = 'text-success';
                                break;
                            case 'Stock Out':
                            case 'Damage/Loss':
                                $badgeCls = 'bg-danger';
                                $sign = '-';
                                $qtyCls = 'text-danger';
                                break;
                            case 'Transfer':
                                $badgeCls = 'bg-info text-white';
                                $sign = '⇄ ';
                                $qtyCls = 'text-info';
                                break;
                            case 'Adjustment':
                                $badgeCls = 'bg-warning text-dark';
                                $sign = 'Δ ';
                                $qtyCls = 'text-dark';
                                break;
                        }
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">#<?php echo $m['MovementID']; ?></div>
                            <span class="font-monospace small text-muted"><?php echo sanitize($m['ReferenceNumber']); ?></span>
                        </td>
                        <td class="small">
                            <div><?php echo date('M d, Y', strtotime($m['CreatedAt'])); ?></div>
                            <div class="text-muted"><?php echo date('H:i:s', strtotime($m['CreatedAt'])); ?></div>
                        </td>
                        <td>
                            <span class="badge <?php echo $badgeCls; ?>"><?php echo sanitize($m['MovementType']); ?></span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="<?php echo sanitize($m['ListingTitle']); ?>">
                                <a href="<?php echo base_url('product_listing/view.php?id=' . $m['ListingID']); ?>" class="text-dark text-decoration-none">
                                    <?php echo sanitize($m['ListingTitle']); ?>
                                </a>
                            </div>
                            <small class="text-muted"><?php echo sanitize($m['BrandName'] . ' ' . $m['ModelName']); ?></small>
                        </td>
                        <td class="small">
                            <?php if ($m['MovementType'] === 'Transfer'): ?>
                                <span class="badge bg-light text-danger border"><?php echo sanitize($m['FromWarehouseCode'] ?? 'WH'); ?></span>
                                <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                <span class="badge bg-light text-success border"><?php echo sanitize($m['ToWarehouseCode'] ?? 'WH'); ?></span>
                            <?php elseif ($m['MovementType'] === 'Stock In' || $m['MovementType'] === 'Return'): ?>
                                <span class="text-success"><i class="bi bi-box-arrow-in-down me-1"></i><?php echo sanitize($m['ToWarehouseName'] ?? 'Warehouse'); ?></span>
                            <?php else: ?>
                                <span class="text-danger"><i class="bi bi-box-arrow-up-right me-1"></i><?php echo sanitize($m['FromWarehouseName'] ?? 'Warehouse'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-bold fs-6 <?php echo $qtyCls; ?>">
                            <?php echo $sign . (int)$m['Quantity']; ?>
                        </td>
                        <td class="small">
                            <div class="fw-semibold text-dark"><?php echo sanitize($m['Reason'] ?: '—'); ?></div>
                            <?php if (!empty($m['Notes'])): ?>
                                <small class="text-muted"><?php echo sanitize($m['Notes']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <i class="bi bi-person me-1"></i><?php echo sanitize($m['PerformedBy']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
