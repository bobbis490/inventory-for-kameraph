<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin access required

ensure_warehouse_tables_exist($conn);

$pageTitle = 'Physical Inventory Audit & Cycle Count';
$activeMenu = 'warehouse';

$currUser = current_user();
$adminName = $currUser['FullName'] ?? ($currUser['FirstName'] ?? 'Admin');

$selectedWhId = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 1;
$warehouses = get_warehouses($conn, true);

// Handle Audit Reconciliation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reconcile_audit'])) {
    $whId = (int)($_POST['warehouse_id'] ?? 0);
    $counts = $_POST['physical_count'] ?? [];
    $reasons = $_POST['reason'] ?? [];
    $auditNotes = trim($_POST['audit_notes'] ?? '');

    if ($whId > 0 && !empty($counts)) {
        $auditCode = 'AUDIT-' . date('Ymd-His');
        $totalItems = 0;
        $totalDiscrepancy = 0;

        // Create audit session header
        $insAudit = $conn->prepare("
            INSERT INTO inventory_audit
            (AuditCode, WarehouseID, AuditedBy, Status, TotalItemsCounted, TotalDiscrepancy, Notes, CompletedAt)
            VALUES (?, ?, ?, 'Completed', 0, 0, ?, NOW())
        ");
        $insAudit->bind_param("siss", $auditCode, $whId, $adminName, $auditNotes);
        $insAudit->execute();
        $auditId = $insAudit->insert_id;
        $insAudit->close();

        // Process each item
        foreach ($counts as $invId => $physicalQty) {
            $invId = (int)$invId;
            $physicalQty = max(0, (int)$physicalQty);

            // Fetch current inventory item
            $stmt = $conn->prepare("SELECT * FROM warehouse_inventory WHERE InventoryID = ? AND WarehouseID = ?");
            $stmt->bind_param("ii", $invId, $whId);
            $stmt->execute();
            $inv = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($inv) {
                $totalItems++;
                $sysQty = (int)$inv['QuantityOnHand'];
                $discrepancy = $physicalQty - $sysQty;
                $reason = trim($reasons[$invId] ?? '');

                if ($discrepancy !== 0) {
                    $totalDiscrepancy += abs($discrepancy);

                    // Insert audit item record
                    $insItem = $conn->prepare("
                        INSERT INTO inventory_audit_item
                        (AuditID, InventoryID, SystemQuantity, PhysicalQuantity, Discrepancy, DiscrepancyReason)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $insItem->bind_param("iiiiis", $auditId, $invId, $sysQty, $physicalQty, $discrepancy, $reason);
                    $insItem->execute();
                    $insItem->close();

                    // If user chose to apply adjustment
                    if (isset($_POST['apply_adjustments'])) {
                        $newOnHand = $physicalQty;
                        $reserved = (int)$inv['QuantityReserved'];
                        $newAvail = max(0, $newOnHand - $reserved);

                        $updInv = $conn->prepare("UPDATE warehouse_inventory SET QuantityOnHand = ?, QuantityAvailable = ?, LastAuditedAt = NOW() WHERE InventoryID = ?");
                        $updInv->bind_param("iii", $newOnHand, $newAvail, $invId);
                        $updInv->execute();
                        $updInv->close();

                        // Log movement
                        $movRef = 'AUDIT-' . $auditId . '-INV' . $invId;
                        $movNotes = "Audit reconciliation [{$auditCode}]. Discrepancy: " . ($discrepancy > 0 ? "+{$discrepancy}" : "{$discrepancy}");
                        if ($reason !== '') {
                            $movNotes .= " | Reason: {$reason}";
                        }
                        $movType = 'Adjustment';
                        $absDelta = abs($discrepancy);

                        $insMov = $conn->prepare("
                            INSERT INTO stock_movement
                            (MovementType, ReferenceNumber, ListingID, FromWarehouseID, ToWarehouseID, Quantity, Reason, Notes, PerformedBy)
                            VALUES (?, ?, ?, ?, ?, ?, 'Cycle Count Reconciliation', ?, ?)
                        ");
                        $insMov->bind_param("ssiiiiss", $movType, $movRef, $inv['ListingID'], $whId, $whId, $absDelta, $movNotes, $adminName);
                        $insMov->execute();
                        $insMov->close();

                        // Synchronize product listing
                        sync_listing_quantity($conn, $inv['ListingID']);
                    }
                } else {
                    // Mark as audited without discrepancy
                    $updAuditDate = $conn->prepare("UPDATE warehouse_inventory SET LastAuditedAt = NOW() WHERE InventoryID = ?");
                    $updAuditDate->bind_param("i", $invId);
                    $updAuditDate->execute();
                    $updAuditDate->close();
                }
            }
        }

        // Update audit summary
        $updAudit = $conn->prepare("UPDATE inventory_audit SET TotalItemsCounted = ?, TotalDiscrepancy = ? WHERE AuditID = ?");
        $updAudit->bind_param("iii", $totalItems, $totalDiscrepancy, $auditId);
        $updAudit->execute();
        $updAudit->close();

        flash('success', "Cycle count audit [{$auditCode}] completed! Counted {$totalItems} items with {$totalDiscrepancy} units discrepancy reconciled.");
        redirect('warehouse/inventory.php?warehouse_id=' . $whId);
    }
}

// Fetch items for selected warehouse
$items = get_warehouse_inventory($conn, ['warehouse_id' => $selectedWhId]);
$targetWh = get_warehouse_by_id($conn, $selectedWhId);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="dashboard-title">
            <i class="bi bi-clipboard-check text-primary me-2"></i>Physical Inventory Audit & Cycle Count
        </h2>
        <p class="dashboard-subtitle">
            Perform physical bin audits, detect discrepancies, and automatically synchronize ledger stock with on-shelf gear.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo base_url('warehouse/inventory.php?warehouse_id=' . $selectedWhId); ?>" class="btn btn-outline-light">
            <i class="bi bi-boxes me-1"></i> View Live Stock
        </a>
    </div>
</div>

<!-- Select Warehouse Hub -->
<div class="card card-kamera mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <label class="small text-muted fw-bold mb-1">Select Facility to Audit:</label>
                <select name="warehouse_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?php echo $w['WarehouseID']; ?>" <?php echo ($selectedWhId === (int)$w['WarehouseID']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($w['WarehouseCode'] . ' - ' . $w['WarehouseName'] . ' (' . $w['City'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-7 text-md-end pt-md-3">
                <span class="badge bg-primary fs-6">
                    <i class="bi bi-shield-check me-1"></i>Auditor: <?php echo htmlspecialchars($adminName); ?>
                </span>
                <span class="badge bg-secondary fs-6 ms-1">
                    <?php echo count($items); ?> SKUs at facility
                </span>
            </div>
        </form>
    </div>
</div>

<!-- Audit Form -->
<form method="POST">
    <input type="hidden" name="reconcile_audit" value="1">
    <input type="hidden" name="warehouse_id" value="<?php echo $selectedWhId; ?>">

    <div class="card card-kamera mb-4 shadow">
        <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold text-dark">
                <i class="bi bi-list-check me-2 text-primary"></i>Physical Count Worksheet — <?php echo sanitize($targetWh['WarehouseName'] ?? 'Warehouse'); ?>
            </span>
            <div class="d-flex align-items-center gap-2">
                <!-- Live search bar -->
                <div class="input-group input-group-sm" style="width: 280px;">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="auditSearchInput" class="form-control"
                           placeholder="Search product, bin, model…"
                           oninput="filterAuditRows(this.value)"
                           autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('auditSearchInput').value=''; filterAuditRows('');" title="Clear search">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <small class="text-muted text-nowrap" id="auditRowCount">
                    <?php echo count($items); ?> SKUs
                </small>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th>Storage Bin</th>
                        <th>Product & Model</th>
                        <th class="text-center" style="width: 130px;">System Expected</th>
                        <th class="text-center" style="width: 150px;">Physical Count</th>
                        <th class="text-center" style="width: 120px;">Discrepancy</th>
                        <th style="min-width: 200px;">Discrepancy Reason (if any)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No stock stored at this facility.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $it): 
                            $sysCount = (int)$it['QuantityOnHand'];
                            $searchAttr = strtolower(
                                ($it['ListingTitle'] ?? '') . ' ' .
                                ($it['BrandName'] ?? '') . ' ' .
                                ($it['ModelName'] ?? '') . ' ' .
                                $it['Aisle'] . '-' . $it['Rack'] . '-' . $it['Shelf'] . '-' . $it['Bin']
                            );
                        ?>
                        <tr class="audit-row" data-search="<?php echo htmlspecialchars($searchAttr); ?>">
                            <td>
                                <span class="font-monospace small bg-light p-1 px-2 rounded border fw-bold text-dark">
                                    <?php echo sanitize($it['Aisle'] . '-' . $it['Rack'] . '-' . $it['Shelf'] . '-' . $it['Bin']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo sanitize($it['ListingTitle']); ?></div>
                                <small class="text-muted"><?php echo sanitize($it['BrandName'] . ' ' . $it['ModelName']); ?></small>
                            </td>
                            <td class="text-center fw-bold fs-6 text-dark" id="sys_<?php echo $it['InventoryID']; ?>" data-val="<?php echo $sysCount; ?>">
                                <?php echo $sysCount; ?> units
                            </td>
                            <td>
                                <input type="number" name="physical_count[<?php echo $it['InventoryID']; ?>]"
                                       id="phys_<?php echo $it['InventoryID']; ?>"
                                       class="form-control form-control-sm text-center fw-bold physical-input"
                                       value="<?php echo $sysCount; ?>" min="0" required
                                       oninput="calcDelta(<?php echo $it['InventoryID']; ?>)">
                            </td>
                            <td class="text-center fw-bold" id="delta_<?php echo $it['InventoryID']; ?>">
                                <span class="text-success"><i class="bi bi-check-lg me-1"></i>0</span>
                            </td>
                            <td>
                                <input type="text" name="reason[<?php echo $it['InventoryID']; ?>]" class="form-control form-control-sm" placeholder="e.g. Scratched lens damaged, Found extra unit in Aisle B">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="auditNoResults" style="display:none;">
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-search me-2"></i>No items match your search.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white p-3 border-top">
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="apply_adjustments" id="applyAdjustments" value="1" checked>
                        <label class="form-check-label fw-bold text-dark" for="applyAdjustments">
                            Automatically apply adjustments to Inventory & Ledger
                        </label>
                        <small class="text-muted d-block" style="font-size:0.75rem;">If checked, physical counts become new on-hand totals, discrepancies generate audit transactions, and marketplace listings synchronize instantly.</small>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo base_url('warehouse/index.php'); ?>" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary fw-semibold px-4" <?php echo empty($items) ? 'disabled' : ''; ?>>
                        <i class="bi bi-check2-circle me-1"></i>Finalize & Submit Audit
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function calcDelta(invId) {
    const sysEl = document.getElementById('sys_' + invId);
    const physEl = document.getElementById('phys_' + invId);
    const deltaEl = document.getElementById('delta_' + invId);

    const sys = parseInt(sysEl.getAttribute('data-val') || '0', 10);
    const phys = parseInt(physEl.value || '0', 10);
    const diff = phys - sys;

    if (diff === 0) {
        deltaEl.innerHTML = '<span class="text-success"><i class="bi bi-check-lg me-1"></i>0</span>';
    } else if (diff > 0) {
        deltaEl.innerHTML = '<span class="text-primary fw-bold">+' + diff + '</span>';
    } else {
        deltaEl.innerHTML = '<span class="text-danger fw-bold">' + diff + '</span>';
    }
}

function filterAuditRows(query) {
    const q = query.trim().toLowerCase();
    const rows = document.querySelectorAll('tr.audit-row');
    const noResults = document.getElementById('auditNoResults');
    const countEl = document.getElementById('auditRowCount');
    let visible = 0;

    rows.forEach(function(row) {
        const haystack = row.getAttribute('data-search') || '';
        const match = q === '' || haystack.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    // Update count label
    if (countEl) {
        countEl.textContent = q === ''
            ? rows.length + ' SKUs'
            : visible + ' of ' + rows.length + ' SKUs';
    }

    // Show/hide empty state
    if (noResults) {
        noResults.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
    }

    // Highlight matching input
    const input = document.getElementById('auditSearchInput');
    if (input) {
        input.classList.toggle('border-warning', q !== '' && visible === 0);
        input.classList.toggle('border-success', q !== '' && visible > 0);
    }
}

// Auto-focus the search bar on page load
document.addEventListener('DOMContentLoaded', function() {
    const inp = document.getElementById('auditSearchInput');
    if (inp) inp.focus();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
