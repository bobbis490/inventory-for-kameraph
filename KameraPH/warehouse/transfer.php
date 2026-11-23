<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin access required

ensure_warehouse_tables_exist($conn);

$pageTitle = 'Inter-Warehouse Stock Transfer';
$activeMenu = 'warehouse';

$currUser = current_user();
$adminName = $currUser['FullName'] ?? ($currUser['FirstName'] ?? 'Admin');

$preListingId = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
$preFromWh = isset($_GET['from_wh']) ? (int)$_GET['from_wh'] : 0;

$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transfer'])) {
    try {
        $listingId = (int)($_POST['listing_id'] ?? 0);
        $fromWh = (int)($_POST['from_warehouse_id'] ?? 0);
        $toWh = (int)($_POST['to_warehouse_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        $ref = trim($_POST['reference_number'] ?? '');
        $courier = trim($_POST['courier_notes'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (!empty($courier)) {
            $notes = "Courier/Transport: " . $courier . ($notes ? " | " . $notes : "");
        }

        transfer_warehouse_stock($conn, $listingId, $fromWh, $toWh, $qty, $ref, $notes, $adminName);
        flash('success', "Stock transfer completed successfully! Transferred {$qty} units between warehouse hubs.");
        redirect('warehouse/movement.php?type=Transfer');
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

$warehouses = get_warehouses($conn, true);

// Get available products that have warehouse stock
$availableStockRes = $conn->query("
    SELECT wi.InventoryID, wi.WarehouseID, wi.ListingID, wi.QuantityAvailable, wi.Aisle, wi.Rack, wi.Shelf, wi.Bin,
           pl.Title AS ListingTitle, pl.Price, pm.ModelName, b.BrandName, w.WarehouseCode, w.WarehouseName
    FROM warehouse_inventory wi
    INNER JOIN product_listing pl ON wi.ListingID = pl.ListingID
    INNER JOIN product_model pm ON pl.ModelID = pm.ModelID
    INNER JOIN brand b ON pm.BrandID = b.BrandID
    INNER JOIN warehouse w ON wi.WarehouseID = w.WarehouseID
    WHERE wi.QuantityAvailable > 0
    ORDER BY w.WarehouseCode ASC, pl.Title ASC
");
$availableStock = $availableStockRes ? $availableStockRes->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="dashboard-title">
            <i class="bi bi-arrow-left-right text-primary me-2"></i>Inter-Warehouse Stock Transfer
        </h2>
        <p class="dashboard-subtitle">
            Relocate camera stock and inventory batches between regional fulfillment centers.
        </p>
    </div>
    <div>
        <a href="<?php echo base_url('warehouse/inventory.php'); ?>" class="btn btn-outline-light">
            <i class="bi bi-grid-3x3-gap me-1"></i>View Live Stock
        </a>
    </div>
</div>

<?php if ($errorMsg !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo sanitize($errorMsg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-kamera shadow">
            <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="bi bi-box-seam me-2 text-primary"></i>Transfer Dispatch Order
                </span>
                <span class="badge bg-primary">Logistics Routing</span>
            </div>
            <div class="card-body p-4 text-dark">
                <form method="POST" id="transferForm">
                    <input type="hidden" name="submit_transfer" value="1">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">1. Origin Facility (From Warehouse) <span class="text-danger">*</span></label>
                            <select name="from_warehouse_id" id="fromWhSelect" class="form-select" required onchange="filterAvailableProducts()">
                                <option value="">-- Select Source Warehouse --</option>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?php echo $wh['WarehouseID']; ?>" <?php echo ($preFromWh === (int)$wh['WarehouseID']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($wh['WarehouseCode'] . ' - ' . $wh['WarehouseName'] . ' (' . $wh['City'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">2. Destination Facility (To Warehouse) <span class="text-danger">*</span></label>
                            <select name="to_warehouse_id" id="toWhSelect" class="form-select" required>
                                <option value="">-- Select Destination Warehouse --</option>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?php echo $wh['WarehouseID']; ?>">
                                        <?php echo sanitize($wh['WarehouseCode'] . ' - ' . $wh['WarehouseName'] . ' (' . $wh['City'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">3. Select Product to Transfer <span class="text-danger">*</span></label>
                        <select name="listing_id" id="productSelect" class="form-select" required onchange="updateMaxQuantity()">
                            <option value="">-- Select Product From Available Stock --</option>
                            <?php foreach ($availableStock as $stk): ?>
                                <option value="<?php echo $stk['ListingID']; ?>"
                                        data-wh="<?php echo $stk['WarehouseID']; ?>"
                                        data-avail="<?php echo (int)$stk['QuantityAvailable']; ?>"
                                        data-bin="<?php echo sanitize($stk['Aisle'] . '-' . $stk['Rack'] . '-' . $stk['Shelf'] . '-' . $stk['Bin']); ?>"
                                        <?php echo ($preListingId === (int)$stk['ListingID'] && $preFromWh === (int)$stk['WarehouseID']) ? 'selected' : ''; ?>>
                                    [<?php echo sanitize($stk['WarehouseCode']); ?>] <?php echo sanitize($stk['BrandName'] . ' ' . $stk['ModelName'] . ' - ' . $stk['ListingTitle']); ?> (Available: <?php echo (int)$stk['QuantityAvailable']; ?> units | Bin: <?php echo sanitize($stk['Aisle'] . '-' . $stk['Rack'] . '-' . $stk['Shelf'] . '-' . $stk['Bin']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="stockNotice" class="form-text text-primary small mt-1"></div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">4. Transfer Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantityInput" class="form-control" min="1" value="1" required>
                            <small class="text-muted" id="availNotice">Max allowed: —</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">5. Transfer Slip / Waybill #</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="e.g. TR-202611-001" value="<?php echo 'TR-' . date('Ymd-His'); ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">6. Courier / Driver / Truck #</label>
                            <input type="text" name="courier_notes" class="form-control" placeholder="e.g. LBC Logistics / Van 04">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">7. Transfer Notes / Instructions</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Fragile glass camera lenses. Inspected before loading."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="<?php echo base_url('warehouse/index.php'); ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary fw-semibold px-4">
                            <i class="bi bi-send-check me-1"></i>Process & Execute Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Instructions & Summary Column -->
    <div class="col-lg-4">
        <div class="card card-kamera mb-4">
            <div class="card-header card-header-kamera">
                <span class="fw-bold text-dark">
                    <i class="bi bi-info-circle-fill text-info me-2"></i>Transfer Instructions
                </span>
            </div>
            <div class="card-body p-3 small text-muted">
                <ul class="ps-3 mb-3">
                    <li class="mb-2"><strong>Dual Inventory Update:</strong> Quantities are atomically deducted from the origin facility and added to the destination facility.</li>
                    <li class="mb-2"><strong>Marketplace Sync:</strong> KameraPH product listings automatically retain the accurate aggregate available stock.</li>
                    <li class="mb-2"><strong>Audit Trail:</strong> Every transfer is logged permanently in the Movement Ledger with reference number and authorized staff ID.</li>
                    <li><strong>Origin Check:</strong> You cannot transfer more units than currently available (unreserved) at the origin warehouse.</li>
                </ul>
                <div class="p-2 bg-light rounded text-center">
                    <i class="bi bi-shield-check text-success fs-4 d-block mb-1"></i>
                    <span class="fw-semibold text-dark">Automated Safe Reconciliation</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterAvailableProducts() {
    const fromWh = document.getElementById('fromWhSelect').value;
    const prodSelect = document.getElementById('productSelect');
    const options = prodSelect.querySelectorAll('option');

    options.forEach(opt => {
        if (!opt.value) return;
        const optWh = opt.getAttribute('data-wh');
        if (!fromWh || optWh === fromWh) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });

    // Reset selection if hidden
    const selectedOpt = prodSelect.options[prodSelect.selectedIndex];
    if (selectedOpt && selectedOpt.style.display === 'none') {
        prodSelect.value = '';
    }
    updateMaxQuantity();
}

function updateMaxQuantity() {
    const prodSelect = document.getElementById('productSelect');
    const qtyInput = document.getElementById('quantityInput');
    const notice = document.getElementById('availNotice');
    const stockNotice = document.getElementById('stockNotice');

    const selectedOpt = prodSelect.options[prodSelect.selectedIndex];
    if (selectedOpt && selectedOpt.value) {
        const avail = parseInt(selectedOpt.getAttribute('data-avail') || '0', 10);
        const bin = selectedOpt.getAttribute('data-bin') || '';
        qtyInput.max = avail;
        notice.innerHTML = 'Max allowed: <strong>' + avail + ' units</strong>';
        stockNotice.innerHTML = '<i class="bi bi-check-circle text-success me-1"></i>Current location bin: <strong>' + bin + '</strong> (' + avail + ' available for dispatch)';
    } else {
        qtyInput.removeAttribute('max');
        notice.innerText = 'Max allowed: —';
        stockNotice.innerText = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    filterAvailableProducts();
    updateMaxQuantity();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
