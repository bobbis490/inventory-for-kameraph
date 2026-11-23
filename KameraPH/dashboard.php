<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_auth('admin'); // Admin login required

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';

// Fetch Statistics safely
$stats = [
    'listings' => 0,
    'live_listings' => 0,
    'brands' => 0,
    'models' => 0,
    'categories' => 0,
    'pending_verifications' => 0,
    'active_reservations' => 0,
    'reviews' => 0,
    'avg_rating' => 0.0
];

if ($res = $conn->query("SELECT COUNT(*) AS total, SUM(CASE WHEN Status = 'Live' THEN 1 ELSE 0 END) AS live_total FROM product_listing")) {
    $row = $res->fetch_assoc();
    $stats['listings'] = (int)($row['total'] ?? 0);
    $stats['live_listings'] = (int)($row['live_total'] ?? 0);
}

if ($res = $conn->query("SELECT COUNT(*) AS total FROM brand")) {
    $stats['brands'] = (int)($res->fetch_assoc()['total'] ?? 0);
}

if ($res = $conn->query("SELECT COUNT(*) AS total FROM product_model")) {
    $stats['models'] = (int)($res->fetch_assoc()['total'] ?? 0);
}

if ($res = $conn->query("SELECT COUNT(*) AS total FROM category")) {
    $stats['categories'] = (int)($res->fetch_assoc()['total'] ?? 0);
}

if ($res = $conn->query("SELECT COUNT(*) AS total FROM product_verification WHERE VerificationStatus = 'Pending'")) {
    $stats['pending_verifications'] = (int)($res->fetch_assoc()['total'] ?? 0);
}

if ($res = $conn->query("SELECT COUNT(*) AS total FROM listing_reservation WHERE Status = 'Reserved'")) {
    $stats['active_reservations'] = (int)($res->fetch_assoc()['total'] ?? 0);
}

if ($res = $conn->query("SELECT COUNT(*) AS total, AVG(RatingValue) AS avg_rate FROM product_review")) {
    $row = $res->fetch_assoc();
    $stats['reviews'] = (int)($row['total'] ?? 0);
    $stats['avg_rating'] = round((float)($row['avg_rate'] ?? 0), 1);
}

// Fetch Recent Listings
$recentListings = $conn->query("
    SELECT pl.*, pm.ModelName, b.BrandName,
           (SELECT ImageURL FROM listing_image WHERE ListingID = pl.ListingID ORDER BY IsPrimary DESC, DisplayOrder ASC LIMIT 1) AS PrimaryImage
    FROM product_listing pl
    INNER JOIN product_model pm ON pl.ModelID = pm.ModelID
    INNER JOIN brand b ON pm.BrandID = b.BrandID
    ORDER BY pl.ListingID DESC
    LIMIT 6
");

// Fetch Recent Verifications
$recentVerifications = $conn->query("
    SELECT pv.*, pl.Title
    FROM product_verification pv
    INNER JOIN product_listing pl ON pv.ListingID = pl.ListingID
    ORDER BY pv.VerificationID DESC
    LIMIT 5
");

$curr = current_user();
$adminName = $curr['FullName'] ?? ($curr['FirstName'] ?? 'Admin');
$adminRole = $curr['AdminRole'] ?? 'Administrator';

// Fetch Pending Review Queue count
$stats['pending_review'] = 0;
if ($res = $conn->query("SELECT COUNT(*) AS total FROM product_listing WHERE Status = 'Pending Verification'")) {
    $stats['pending_review'] = (int)($res->fetch_assoc()['total'] ?? 0);
}

// Fetch Warehouse Inventory Stats
$whStats = get_warehouse_stats($conn);

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="dashboard-title">
            <i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard Overview
        </h2>
        <p class="dashboard-subtitle">
            Welcome back, <strong><?php echo htmlspecialchars($adminName); ?></strong>
            <span class="badge bg-primary ms-1"><?php echo htmlspecialchars($adminRole); ?></span>
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="<?php echo base_url('product_listing/review_queue.php'); ?>" class="btn btn-outline-warning text-decoration-none position-relative">
            <i class="bi bi-clipboard2-check me-1"></i> Review Queue
            <?php if ($stats['pending_review'] > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;">
                    <?php echo $stats['pending_review']; ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="<?php echo base_url('warehouse/index.php'); ?>" class="btn btn-outline-light text-decoration-none">
            <i class="bi bi-buildings me-1"></i> Warehouse Hub
        </a>
        <a href="<?php echo base_url('product_listing/add.php'); ?>" class="btn-add-listing-blue text-decoration-none">
            <i class="bi bi-plus-circle me-1"></i> Add Listing
        </a>
        <a href="<?php echo base_url('product_model/add.php'); ?>" class="btn-add-model-dark text-decoration-none">
            <i class="bi bi-camera me-1"></i> Add Model
        </a>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card-white stat-card-cyan">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-card-label">TOTAL LISTINGS</div>
                    <div class="stat-card-number"><?php echo number_format($stats['listings']); ?></div>
                    <div class="stat-card-sub text-success"><i class="bi bi-check-circle me-1"></i><?php echo $stats['live_listings']; ?> Live</div>
                </div>
                <div class="stat-card-icon-box stat-icon-cyan">
                    <i class="bi bi-shop"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="stat-card-white stat-card-green">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-card-label">MODELS &amp; BRANDS</div>
                    <div class="stat-card-number"><?php echo number_format($stats['models']); ?></div>
                    <div class="stat-card-sub"><?php echo $stats['brands']; ?> Brands &bull; <?php echo $stats['categories']; ?> Categories</div>
                </div>
                <div class="stat-card-icon-box stat-icon-green">
                    <i class="bi bi-camera"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="stat-card-white stat-card-amber">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-card-label">VERIFICATIONS</div>
                    <div class="stat-card-number"><?php echo number_format($stats['pending_verifications']); ?></div>
                    <div class="stat-card-sub text-warning fw-semibold">
                        <?php echo ($stats['pending_verifications'] > 0) ? 'Pending Admin Action' : 'All up to date'; ?>
                    </div>
                </div>
                <div class="stat-card-icon-box stat-icon-amber">
                    <i class="bi bi-shield-check"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="stat-card-white stat-card-purple">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-card-label">REVIEWS &amp; RATING</div>
                    <div class="stat-card-number"><?php echo $stats['reviews'] > 0 ? $stats['avg_rating'] : '0'; ?><span style="font-size: 1.1rem; color: #64748b; font-weight: 600;">/5</span></div>
                    <div class="stat-card-sub"><?php echo number_format($stats['reviews']); ?> Verified Reviews</div>
                </div>
                <div>
                    <i class="bi bi-star-fill stat-icon-star"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Review Queue stat card -->
    <div class="col-sm-6 col-lg-3">
        <a href="<?php echo base_url('product_listing/review_queue.php'); ?>" class="text-decoration-none">
            <div class="stat-card-white <?php echo $stats['pending_review'] > 0 ? 'stat-card-amber border border-warning' : 'stat-card-cyan'; ?> position-relative">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-card-label">REVIEW QUEUE</div>
                        <div class="stat-card-number"><?php echo number_format($stats['pending_review']); ?></div>
                        <div class="stat-card-sub <?php echo $stats['pending_review'] > 0 ? 'text-danger fw-semibold' : 'text-success'; ?>">
                            <?php echo $stats['pending_review'] > 0 ? 'Needs Approval' : 'All reviewed'; ?>
                        </div>
                    </div>
                    <div class="stat-card-icon-box <?php echo $stats['pending_review'] > 0 ? 'stat-icon-amber' : 'stat-icon-cyan'; ?>">
                        <i class="bi bi-clipboard2-check"></i>
                    </div>
                </div>
                <?php if ($stats['pending_review'] > 0): ?>
                    <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-danger" style="font-size:0.75rem;">
                        <?php echo $stats['pending_review']; ?>
                    </span>
                <?php endif; ?>
            </div>
        </a>
    </div>
</div>

<!-- Review Queue Alert Banner (shown when listings are pending) -->
<?php if ($stats['pending_review'] > 0): ?>
<div class="card mb-4 shadow-sm border-start border-danger border-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 bg-danger-subtle text-danger rounded-3">
                <i class="bi bi-clipboard2-check fs-3"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    <?php echo $stats['pending_review']; ?> Listing<?php echo $stats['pending_review'] !== 1 ? 's' : ''; ?> Awaiting Your Review
                </h5>
                <small class="text-muted">Sellers are waiting for approval before their items go live on the marketplace.</small>
            </div>
        </div>
        <a href="<?php echo base_url('product_listing/review_queue.php'); ?>" class="btn btn-danger fw-semibold px-4">
            <i class="bi bi-clipboard2-check me-1"></i> Open Review Queue
            <span class="badge bg-white text-danger ms-2"><?php echo $stats['pending_review']; ?></span>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Warehouse Inventory Overview Banner -->
<div class="card card-kamera mb-4 p-3 shadow-sm border-start border-primary border-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center">
            <div class="p-3 bg-primary-subtle text-primary rounded-3 me-3">
                <i class="bi bi-buildings-fill fs-3"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">Warehouse Physical Inventory Network</h5>
                <small class="text-muted">
                    <strong><?php echo $whStats['total_warehouses']; ?></strong> Facilities &bull;
                    <strong><?php echo number_format($whStats['total_items_in_stock']); ?></strong> Units Stored &bull;
                    <strong><?php echo format_price($whStats['stock_valuation']); ?></strong> Stock Valuation
                </small>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?php if ($whStats['low_stock_count'] > 0): ?>
                <a href="<?php echo base_url('warehouse/inventory.php?status=low_stock'); ?>" class="badge bg-warning text-dark text-decoration-none p-2">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo $whStats['low_stock_count']; ?> Low Stock Alert(s)
                </a>
            <?php endif; ?>
            <a href="<?php echo base_url('warehouse/movement.php?action=in'); ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-box-arrow-in-down me-1"></i>Stock In
            </a>
            <a href="<?php echo base_url('warehouse/transfer.php'); ?>" class="btn btn-sm btn-info text-white">
                <i class="bi bi-arrow-left-right me-1"></i>Transfer
            </a>
            <a href="<?php echo base_url('warehouse/index.php'); ?>" class="btn btn-sm btn-outline-dark">
                Warehouse Hub <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Listings -->
    <div class="col-lg-8">
        <div class="card card-kamera h-100">
            <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="bi bi-box-seam me-2 text-primary"></i>Recent Product Listings</span>
                <a href="<?php echo base_url('product_listing/index.php'); ?>" class="btn btn-sm btn-link text-decoration-none fw-semibold">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-kamera table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">IMAGE</th>
                            <th>LISTING</th>
                            <th>MODEL</th>
                            <th>PRICE</th>
                            <th>CONDITION</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentListings && $recentListings->num_rows > 0): ?>
                            <?php while ($row = $recentListings->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo image_url($row['PrimaryImage'] ?? ''); ?>" 
                                             alt="Thumb" 
                                             class="listing-thumb"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2248%22%20height%3D%2248%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%2394a3b8%22%20stroke-width%3D%221.5%22%3E%3Cpath%20d%3D%22M23%2019a2%202%200%200%201-2%202H3a2%202%200%200%201-2-2V8a2%202%200%200%201%202-2h4l2-3h6l2%203h4a2%202%200%200%201%202%202z%22%2F%3E%3Ccircle%20cx%3D%2212%22%20cy%3D%2213%22%20r%3D%224%22%2F%3E%3C%2Fsvg%3E';">
                                    </td>
                                    <td>
                                        <a href="<?php echo base_url('product_listing/view.php?id=' . $row['ListingID']); ?>" class="fw-semibold text-dark text-decoration-none d-block">
                                            <?php echo sanitize($row['Title']); ?>
                                        </a>
                                        <small class="text-muted">ID: #<?php echo $row['ListingID']; ?> &bull; Qty: <?php echo $row['Quantity']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-model-pill">
                                            <?php echo sanitize($row['BrandName'] . ' ' . $row['ModelName']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="price-bold"><?php echo format_price($row['Price']); ?></span>
                                    </td>
                                    <td><?php echo condition_badge($row['Condition']); ?></td>
                                    <td><?php echo status_badge($row['Status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state py-4 text-center">
                                        <i class="bi bi-camera fs-1 text-muted"></i>
                                        <p class="mb-2 text-muted">No product listings found.</p>
                                        <a href="<?php echo base_url('product_listing/add.php'); ?>" class="btn-add-listing-blue text-decoration-none">
                                            <i class="bi bi-plus-circle me-1"></i> Create First Listing
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Navigation & Recent Verifications -->
    <div class="col-lg-4">
        <!-- Quick Nav Card -->
        <div class="card card-kamera mb-4">
            <div class="card-header card-header-kamera fw-bold text-dark d-flex align-items-center">
                <i class="bi bi-lightning-charge text-warning me-2 fs-5"></i>Quick Management
            </div>
            <div>
                <a href="<?php echo base_url('warehouse/index.php'); ?>" class="quick-manage-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-buildings text-primary me-2"></i>
                        <span>Warehouse Inventory</span>
                    </div>
                    <span class="quick-manage-badge"><?php echo number_format($whStats['total_items_in_stock']); ?> units</span>
                </a>
                <a href="<?php echo base_url('brand/index.php'); ?>" class="quick-manage-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-tag text-primary me-2"></i>
                        <span>Brand Management</span>
                    </div>
                    <span class="quick-manage-badge"><?php echo $stats['brands']; ?></span>
                </a>
                <a href="<?php echo base_url('category/index.php'); ?>" class="quick-manage-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-folder text-success me-2"></i>
                        <span>Category Management</span>
                    </div>
                    <span class="quick-manage-badge"><?php echo $stats['categories']; ?></span>
                </a>
                <a href="<?php echo base_url('product_model/index.php'); ?>" class="quick-manage-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-camera2 text-info me-2"></i>
                        <span>Camera Models</span>
                    </div>
                    <span class="quick-manage-badge"><?php echo $stats['models']; ?></span>
                </a>
                <a href="<?php echo base_url('listing_reservation/index.php'); ?>" class="quick-manage-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-clock-history text-secondary me-2"></i>
                        <span>Reservations</span>
                    </div>
                    <span class="quick-manage-badge"><?php echo $stats['active_reservations']; ?></span>
                </a>
                <a href="<?php echo base_url('product_review/index.php'); ?>" class="quick-manage-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-star text-warning me-2"></i>
                        <span>Product Reviews</span>
                    </div>
                    <span class="quick-manage-badge"><?php echo $stats['reviews']; ?></span>
                </a>
            </div>
        </div>

        <!-- Recent Verifications Card -->
        <div class="card card-kamera">
            <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="bi bi-shield-check me-2 text-success"></i>Verifications</span>
                <a href="<?php echo base_url('product_verification/index.php'); ?>" class="btn btn-sm btn-link text-decoration-none fw-semibold">All</a>
            </div>
            <div>
                <?php if ($recentVerifications && $recentVerifications->num_rows > 0): ?>
                    <?php while ($v = $recentVerifications->fetch_assoc()): ?>
                        <div class="verification-item">
                            <div class="pe-2 overflow-hidden">
                                <div class="fw-semibold text-truncate text-dark" style="max-width: 170px;">
                                    <?php echo sanitize($v['Title']); ?>
                                </div>
                                <small class="text-muted d-block"><?php echo sanitize($v['VerificationType']); ?></small>
                            </div>
                            <div class="text-end text-nowrap">
                                <?php echo status_badge($v['VerificationStatus']); ?>
                                <a href="<?php echo base_url('product_verification/edit.php?id=' . $v['VerificationID']); ?>" class="d-block mt-1 text-primary text-decoration-none fw-semibold" style="font-size:0.75rem;">Review</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-3 text-center text-muted">
                        <small>No verification requests recorded yet.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
