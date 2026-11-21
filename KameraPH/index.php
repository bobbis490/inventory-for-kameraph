<?php
require_once __DIR__ . '/config/database.php';

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
           (SELECT ImageURL FROM listing_image WHERE ListingID = pl.ListingID AND IsPrimary = 1 LIMIT 1) AS PrimaryImage
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

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="dashboard-title">
            <i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard Overview
        </h2>
        <p class="dashboard-subtitle">Welcome to KameraPH camera equipment inventory and catalog system</p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
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
