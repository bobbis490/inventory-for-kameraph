<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin access required

ensure_warehouse_tables_exist($conn);

$pageTitle = 'Warehouse & Inventory Hub';
$activeMenu = 'warehouse';

$stats = get_warehouse_stats($conn);
$warehouses = get_warehouses($conn);
$lowStockItems = get_low_stock_items($conn);
$recentMovements = get_stock_movements($conn, 8);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="dashboard-title">
            <i class="bi bi-buildings-fill text-primary me-2"></i>Warehouse & Inventory Management
        </h2>
        <p class="dashboard-subtitle">
            Monitor multi-warehouse stock levels, bin allocations, inter-facility transfers, and inventory transactions.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
        <a href="<?php echo base_url('warehouse/movement.php?action=in'); ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-box-arrow-in-down me-1"></i> Stock In / Restock
        </a>
        <a href="<?php echo base_url('warehouse/transfer.php'); ?>" class="btn btn-info text-white shadow-sm">
            <i class="bi bi-arrow-left-right me-1"></i> Transfer Stock
        </a>
        <a href="<?php echo base_url('warehouse/add_warehouse.php'); ?>" class="btn btn-outline-light shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Warehouse
        </a>
    </div>
</div>

<!-- Key Performance Indicators (KPI Cards) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card-white stat-card-cyan">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-uppercase fw-bold text-muted small" style="font-size: 0.75rem; letter-spacing: 0.5px;">Warehouses</span>
                    <h3 class="fw-bold mt-1 mb-0 text-dark"><?php echo number_format($stats['total_warehouses']); ?></h3>
                    <small class="text-success"><i class="bi bi-check-circle-fill me-1"></i><?php echo $stats['active_warehouses']; ?> Active Hubs</small>
                </div>
                <div class="p-2 bg-light rounded-3 text-info">
                    <i class="bi bi-buildings fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card-white stat-card-green">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-uppercase fw-bold text-muted small" style="font-size: 0.75rem; letter-spacing: 0.5px;">Units in Stock</span>
                    <h3 class="fw-bold mt-1 mb-0 text-dark"><?php echo number_format($stats['total_items_in_stock']); ?></h3>
                    <small class="text-primary"><i class="bi bi-box-seam me-1"></i><?php echo number_format($stats['total_stock_available']); ?> Available</small>
                </div>
                <div class="p-2 bg-light rounded-3 text-success">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card-white stat-card-amber">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-uppercase fw-bold text-muted small" style="font-size: 0.75rem; letter-spacing: 0.5px;">Low Stock Alerts</span>
                    <h3 class="fw-bold mt-1 mb-0 <?php echo $stats['low_stock_count'] > 0 ? 'text-danger' : 'text-dark'; ?>">
                        <?php echo number_format($stats['low_stock_count']); ?>
                    </h3>
                    <small class="text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo $stats['out_of_stock_count']; ?> Out of Stock
                    </small>
                </div>
                <div class="p-2 bg-light rounded-3 text-warning">
                    <i class="bi bi-bell-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-uppercase fw-bold text-muted small" style="font-size: 0.75rem; letter-spacing: 0.5px;">Inventory Valuation</span>
                    <h3 class="fw-bold mt-1 mb-0 text-dark"><?php echo format_price($stats['stock_valuation']); ?></h3>
                    <small class="text-secondary"><i class="bi bi-arrow-repeat me-1"></i><?php echo number_format($stats['total_movements']); ?> Movements Logged</small>
                </div>
                <div class="p-2 bg-light rounded-3 text-primary">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Warning Banner if any -->
<?php if (!empty($lowStockItems)): ?>
<div class="card card-kamera border-start border-warning border-4 mb-4">
    <div class="card-header card-header-kamera bg-warning-subtle d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-5 me-2"></i>
            <span class="fw-bold text-dark">Attention Required: Products Below Reorder Level (<?php echo count($lowStockItems); ?>)</span>
        </div>
        <a href="<?php echo base_url('warehouse/inventory.php?status=low_stock'); ?>" class="btn btn-sm btn-outline-dark">
            View All Low Stock
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-muted">
                <tr>
                    <th>Product</th>
                    <th>Warehouse & Location</th>
                    <th class="text-center">Available Stock</th>
                    <th class="text-center">Reorder Threshold</th>
                    <th class="text-center">Suggested Restock</th>
                    <th class="text-end">Quick Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($lowStockItems, 0, 5) as $low): ?>
                <tr>
                    <td>
                        <div class="fw-bold text-dark"><?php echo sanitize($low['ListingTitle']); ?></div>
                        <small class="text-muted"><?php echo sanitize($low['BrandName'] . ' ' . $low['ModelName']); ?></small>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border"><?php echo sanitize($low['WarehouseCode']); ?></span>
                        <small class="text-secondary ms-1">Bin: <?php echo sanitize($low['Aisle'] . '-' . $low['Rack'] . '-' . $low['Shelf'] . '-' . $low['Bin']); ?></small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-danger-subtle text-danger fw-bold fs-6">
                            <?php echo (int)$low['QuantityAvailable']; ?> units
                        </span>
                    </td>
                    <td class="text-center text-muted"><?php echo (int)$low['ReorderLevel']; ?> units</td>
                    <td class="text-center fw-semibold text-primary">+<?php echo (int)$low['ReorderQuantity']; ?> units</td>
                    <td class="text-end">
                        <a href="<?php echo base_url('warehouse/movement.php?action=in&listing_id=' . $low['ListingID'] . '&warehouse_id=' . $low['WarehouseID']); ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Restock Now
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Warehouses Grid Overview -->
<div class="card card-kamera mb-4">
    <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-building me-2 text-primary"></i>Fulfillment Centers & Warehouses
            </h5>
            <small class="text-muted">Real-time capacity and physical storage utilization</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo base_url('warehouse/inventory.php'); ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-grid-3x3-gap me-1"></i>View All Inventory
            </a>
            <a href="<?php echo base_url('warehouse/warehouses.php'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-gear me-1"></i>Manage Hubs
            </a>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <?php foreach ($warehouses as $wh): 
                $rate = $wh['UtilizationRate'];
                $progressColor = ($rate > 85) ? 'bg-danger' : (($rate > 60) ? 'bg-warning' : 'bg-success');
            ?>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-white h-100 shadow-sm position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-secondary mb-1"><?php echo sanitize($wh['WarehouseCode']); ?></span>
                            <h6 class="fw-bold text-dark mb-0"><?php echo sanitize($wh['WarehouseName']); ?></h6>
                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo sanitize($wh['City'] . ', ' . $wh['Province']); ?></small>
                        </div>
                        <?php if ($wh['Status'] === 'Active'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?php echo sanitize($wh['Status']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="my-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Capacity Used: <strong><?php echo number_format($wh['CurrentUnits']); ?> / <?php echo number_format($wh['StorageCapacity']); ?></strong></span>
                            <span class="fw-bold"><?php echo $rate; ?>%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar <?php echo $progressColor; ?>" role="progressbar" style="width: <?php echo $rate; ?>%;" aria-valuenow="<?php echo $rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top small">
                        <span class="text-muted"><i class="bi bi-box me-1"></i><?php echo number_format($wh['DistinctProductsCount']); ?> Product SKUs</span>
                        <div class="btn-group btn-group-sm">
                            <a href="<?php echo base_url('warehouse/inventory.php?warehouse_id=' . $wh['WarehouseID']); ?>" class="btn btn-outline-primary" title="View Stock">
                                View Stock
                            </a>
                            <a href="<?php echo base_url('warehouse/edit_warehouse.php?id=' . $wh['WarehouseID']); ?>" class="btn btn-outline-secondary" title="Edit Hub">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Recent Stock Movements & Audits Section -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-kamera h-100">
            <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="bi bi-clock-history me-2 text-info"></i>Recent Stock Movements & Transactions
                </span>
                <a href="<?php echo base_url('warehouse/movement.php'); ?>" class="btn btn-sm btn-outline-primary">
                    View Full Audit Log
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Product / Gear</th>
                            <th>Location / Route</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentMovements)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No stock movements recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentMovements as $mov): 
                                $typeClass = 'bg-secondary';
                                $qtySign = '+';
                                $qtyColor = 'text-success';
                                switch ($mov['MovementType']) {
                                    case 'Stock In':
                                    case 'Return':
                                        $typeClass = 'bg-success';
                                        $qtySign = '+';
                                        $qtyColor = 'text-success';
                                        break;
                                    case 'Stock Out':
                                    case 'Damage/Loss':
                                        $typeClass = 'bg-danger';
                                        $qtySign = '-';
                                        $qtyColor = 'text-danger';
                                        break;
                                    case 'Transfer':
                                        $typeClass = 'bg-info text-white';
                                        $qtySign = '⇄ ';
                                        $qtyColor = 'text-info';
                                        break;
                                    case 'Adjustment':
                                        $typeClass = 'bg-warning text-dark';
                                        $qtySign = 'Δ ';
                                        $qtyColor = 'text-dark';
                                        break;
                                }
                            ?>
                            <tr>
                                <td>
                                    <span class="badge <?php echo $typeClass; ?>"><?php echo sanitize($mov['MovementType']); ?></span>
                                </td>
                                <td>
                                    <span class="small font-monospace text-muted"><?php echo sanitize($mov['ReferenceNumber']); ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="<?php echo sanitize($mov['ListingTitle']); ?>">
                                        <?php echo sanitize($mov['ListingTitle']); ?>
                                    </div>
                                    <small class="text-muted"><?php echo sanitize($mov['BrandName'] . ' ' . $mov['ModelName']); ?></small>
                                </td>
                                <td class="small">
                                    <?php if ($mov['MovementType'] === 'Transfer'): ?>
                                        <span class="text-danger"><?php echo sanitize($mov['FromWarehouseCode'] ?? 'WH'); ?></span>
                                        <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                        <span class="text-success"><?php echo sanitize($mov['ToWarehouseCode'] ?? 'WH'); ?></span>
                                    <?php elseif ($mov['MovementType'] === 'Stock In'): ?>
                                        <span class="text-success"><i class="bi bi-box-arrow-in-down me-1"></i><?php echo sanitize($mov['ToWarehouseCode'] ?? 'WH'); ?></span>
                                    <?php else: ?>
                                        <span class="text-dark"><?php echo sanitize($mov['FromWarehouseCode'] ?? $mov['ToWarehouseCode'] ?? 'WH'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold <?php echo $qtyColor; ?>">
                                    <?php echo $qtySign . (int)$mov['Quantity']; ?>
                                </td>
                                <td class="text-end small text-muted">
                                    <?php echo date('M d, H:i', strtotime($mov['CreatedAt'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Operations & Actions Column -->
    <div class="col-lg-4">
        <div class="card card-kamera h-100">
            <div class="card-header card-header-kamera">
                <span class="fw-bold text-dark">
                    <i class="bi bi-lightning-charge-fill me-2 text-warning"></i>Warehouse Operations Menu
                </span>
            </div>
            <div class="card-body p-3">
                <div class="list-group list-group-flush">
                    <a href="<?php echo base_url('warehouse/inventory.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0 rounded-2 mb-2 bg-light">
                        <div class="p-2 bg-primary-subtle text-primary rounded-3 me-3">
                            <i class="bi bi-grid-3x3-gap-fill fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Live Inventory & Bins</div>
                            <small class="text-muted">Track product aisle, rack, shelf, bin locations</small>
                        </div>
                    </a>

                    <a href="<?php echo base_url('warehouse/movement.php?action=in'); ?>" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0 rounded-2 mb-2 bg-light">
                        <div class="p-2 bg-success-subtle text-success rounded-3 me-3">
                            <i class="bi bi-box-arrow-in-down-fill fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Receive Stock In</div>
                            <small class="text-muted">Ingest new shipments, returns, and inventory</small>
                        </div>
                    </a>

                    <a href="<?php echo base_url('warehouse/transfer.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0 rounded-2 mb-2 bg-light">
                        <div class="p-2 bg-info-subtle text-info rounded-3 me-3">
                            <i class="bi bi-arrow-left-right fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Inter-Hub Transfer</div>
                            <small class="text-muted">Relocate units between Manila, Cebu, and Davao</small>
                        </div>
                    </a>

                    <a href="<?php echo base_url('warehouse/movement.php?action=adjust'); ?>" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0 rounded-2 mb-2 bg-light">
                        <div class="p-2 bg-warning-subtle text-warning rounded-3 me-3">
                            <i class="bi bi-sliders fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Stock Count Adjustment</div>
                            <small class="text-muted">Correct discrepancies from cycle counts</small>
                        </div>
                    </a>

                    <a href="<?php echo base_url('warehouse/audit.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0 rounded-2 mb-2 bg-light">
                        <div class="p-2 bg-secondary-subtle text-secondary rounded-3 me-3">
                            <i class="bi bi-clipboard-check-fill fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Physical Inventory Audit</div>
                            <small class="text-muted">Perform full cycle count and auto-reconciliation</small>
                        </div>
                    </a>

                    <a href="<?php echo base_url('warehouse/warehouses.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0 rounded-2 bg-light">
                        <div class="p-2 bg-dark-subtle text-dark rounded-3 me-3">
                            <i class="bi bi-buildings-fill fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Facility Configurations</div>
                            <small class="text-muted">Manage warehouse locations and capacity settings</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
