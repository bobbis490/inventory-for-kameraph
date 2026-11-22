<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'View Listing Details';
$activeMenu = 'product_listing';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    flash('danger', 'Invalid listing ID.');
    redirect('product_listing/index.php');
}

// Fetch listing with Model, Brand, Category, and Seller Store
$stmt = $conn->prepare("
    SELECT pl.*, pm.ModelName, pm.ReleaseYear, b.BrandName, c.CategoryName,
           sp.StoreName, sp.VerificationStatus AS SellerVerificationStatus
    FROM product_listing pl
    INNER JOIN product_model pm ON pl.ModelID = pm.ModelID
    INNER JOIN brand b ON pm.BrandID = b.BrandID
    INNER JOIN category c ON pm.CategoryID = c.CategoryID
    LEFT JOIN seller_profile sp ON pl.SellerID = sp.SellerID
    WHERE pl.ListingID = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$listing) {
    flash('danger', 'Product listing not found.');
    redirect('product_listing/index.php');
}

// Fetch Images
$images = $conn->query("SELECT * FROM listing_image WHERE ListingID = {$id} ORDER BY IsPrimary DESC, DisplayOrder ASC");

// Fetch Specifications
$specs = $conn->query("
    SELECT ls.*, sd.SpecName, sd.DataType, sd.Unit
    FROM listing_specification ls
    INNER JOIN spec_definition sd ON ls.SpecDefID = sd.SpecDefID
    WHERE ls.ListingID = {$id}
    ORDER BY sd.SpecName ASC
");

// Fetch Verification Record
$verifications = $conn->query("SELECT * FROM product_verification WHERE ListingID = {$id} ORDER BY VerificationID DESC");

// Fetch Reservations
$reservations = $conn->query("SELECT * FROM listing_reservation WHERE ListingID = {$id} ORDER BY ReservationID DESC");

// Fetch Model Reviews
$reviews = $conn->query("
    SELECT pr.* 
    FROM product_review pr 
    WHERE pr.ModelID = " . (int)$listing['ModelID'] . " 
    ORDER BY pr.ReviewID DESC
");

// Check if verified
$isVerifiedAuthentic = false;
if ($verifications && $verifications->num_rows > 0) {
    while ($vr = $verifications->fetch_assoc()) {
        if ($vr['VerificationStatus'] === 'Verified') {
            $isVerifiedAuthentic = true;
            break;
        }
    }
    $verifications->data_seek(0); // reset pointer
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>"><i class="bi bi-shop me-1"></i>Marketplace</a></li>
                <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Listings</a></li>
                <li class="breadcrumb-item active" aria-current="page">Listing #<?php echo $id; ?></li>
            </ol>
        </nav>
        <h2 class="h3 fw-bold text-dark mb-0">
            <?php echo sanitize($listing['Title']); ?>
        </h2>
        <div class="mt-1 d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-light text-dark border">
                <i class="bi bi-camera me-1"></i><?php echo sanitize($listing['BrandName'] . ' ' . $listing['ModelName']); ?>
            </span>
            <span class="badge bg-light text-secondary border">
                <i class="bi bi-folder me-1"></i><?php echo sanitize($listing['CategoryName']); ?>
            </span>
            <?php echo condition_badge($listing['Condition']); ?>
            <?php echo status_badge($listing['Status']); ?>
        </div>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <?php 
        $cu = current_user();
        $isOwnerOrAdmin = is_admin() || ($cu && !empty($cu['SellerID']) && (int)$cu['SellerID'] === (int)$listing['SellerID']);
        if ($isOwnerOrAdmin): 
        ?>
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-pencil me-1"></i> Edit Listing
            </a>
        <?php endif; ?>
        <a href="<?php echo base_url(); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Marketplace
        </a>
    </div>
</div>

<?php if ($listing['Status'] === 'Suspended' && !empty($listing['SuspensionReason'])): ?>
    <div class="alert alert-danger d-flex align-items-start mb-4 shadow-sm" role="alert">
        <i class="bi bi-exclamation-octagon-fill me-2 fs-5 mt-1"></i>
        <div>
            <strong>This listing is currently suspended:</strong>
            <p class="mb-0 mt-1"><?php echo nl2br(sanitize($listing['SuspensionReason'])); ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Left Column: Gallery & Details -->
    <div class="col-lg-7">
        <!-- Photo Gallery -->
        <div class="card card-kamera mb-4">
            <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="bi bi-images me-2 text-warning"></i>Product Gallery</span>
                <?php if ($isOwnerOrAdmin): ?>
                    <div class="d-flex gap-2">
                        <a href="<?php echo base_url('listing_image/index.php?listing_id=' . $id); ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-images me-1"></i> Manage Photos
                        </a>
                        <a href="<?php echo base_url('listing_image/add.php?listing_id=' . $id); ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Photo
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php
                $imgList = [];
                if ($images && $images->num_rows > 0) {
                    while ($img = $images->fetch_assoc()) {
                        $imgList[] = $img;
                    }
                }
                ?>
                <?php if (!empty($imgList)): ?>
                    <div class="text-center mb-3">
                        <img id="mainDisplayImage" src="<?php echo image_url($imgList[0]['ImageURL']); ?>" alt="Main Photo" class="img-fluid rounded border shadow-sm" style="max-height: 380px; width: 100%; object-fit: contain; background: #f8fafc;">
                    </div>
                    <?php if (count($imgList) > 1): ?>
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <?php foreach ($imgList as $idx => $img): ?>
                                <img src="<?php echo image_url($img['ImageURL']); ?>" alt="Thumb" class="rounded border p-1" style="width: 70px; height: 70px; object-fit: cover; cursor: pointer; <?php echo ($img['IsPrimary']) ? 'border: 2px solid #0284c7 !important;' : ''; ?>" onclick="document.getElementById('mainDisplayImage').src = this.src;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-camera empty-state-icon"></i>
                        <p class="mb-2">No photos uploaded for this listing.</p>
                        <a href="<?php echo base_url('listing_image/add.php?listing_id=' . $id); ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-upload me-1"></i> Upload First Photo
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Description -->
        <div class="card card-kamera mb-4">
            <div class="card-header card-header-kamera fw-bold text-dark">
                <i class="bi bi-card-text me-2 text-primary"></i>Seller Description
            </div>
            <div class="card-body">
                <?php if (!empty($listing['Description'])): ?>
                    <div class="text-secondary" style="white-space: pre-line; line-height: 1.7;">
                        <?php echo nl2br(sanitize($listing['Description'])); ?>
                    </div>
                <?php else: ?>
                    <span class="text-muted fst-italic">No seller description provided.</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Technical Specifications -->
        <div class="card card-kamera">
            <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="bi bi-cpu me-2 text-danger"></i>Technical Specifications</span>
                <?php if ($isOwnerOrAdmin): ?>
                    <a href="<?php echo base_url('listing_specification/add.php?listing_id=' . $id); ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add Spec
                    </a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-kamera mb-0">
                    <thead>
                        <tr>
                            <th>Specification</th>
                            <th>Value</th>
                            <?php if ($isOwnerOrAdmin): ?>
                                <th class="text-end" style="width: 100px;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($specs && $specs->num_rows > 0): ?>
                            <?php while ($s = $specs->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?php echo sanitize($s['SpecName']); ?></td>
                                    <td>
                                        <?php echo sanitize($s['SpecValue']); ?>
                                        <?php if (!empty($s['Unit'])): ?>
                                            <span class="text-muted small"><?php echo sanitize($s['Unit']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($isOwnerOrAdmin): ?>
                                        <td class="text-end">
                                            <a href="<?php echo base_url('listing_specification/edit.php?id=' . $s['ListingSpecID']); ?>" class="btn btn-sm btn-link p-0 text-primary">Edit</a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">
                                    <small>No technical specifications listed yet.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Model Community Reviews -->
        <div class="card card-kamera mt-4">
            <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="bi bi-star-fill me-2 text-warning"></i>Model Reviews & Feedback
                </span>
                <a href="<?php echo base_url('product_review/add.php?model_id=' . (int)$listing['ModelID']); ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Review
                </a>
            </div>
            <div class="card-body">
                <?php if ($reviews && $reviews->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($rev = $reviews->fetch_assoc()): ?>
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div>
                                        <?php echo render_stars($rev['RatingValue']); ?>
                                        <span class="small fw-semibold text-dark ms-2">Buyer #<?php echo $rev['ReviewerUserID']; ?></span>
                                    </div>
                                    <small class="text-muted"><?php echo !empty($rev['CreatedAt']) ? date('M d, Y', strtotime($rev['CreatedAt'])) : ''; ?></small>
                                </div>
                                <p class="small text-secondary mb-0"><?php echo nl2br(sanitize($rev['ReviewText'])); ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted small py-2">
                        <i class="bi bi-chat-left-dots me-1"></i> No reviews recorded yet for <?php echo sanitize($listing['BrandName'] . ' ' . $listing['ModelName']); ?>.
                        <a href="<?php echo base_url('product_review/add.php?model_id=' . (int)$listing['ModelID']); ?>" class="ms-1">Be the first to leave a review</a>.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Pricing, Inventory, Verification & Reservation -->
    <div class="col-lg-5">
        <!-- Price & Stock Card -->
        <div class="card card-kamera mb-4">
            <div class="card-header card-header-kamera fw-bold text-dark">
                <i class="bi bi-currency-dollar me-2 text-success"></i>Pricing & Inventory
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="text-muted d-block small text-uppercase">Selling Price</span>
                    <h2 class="fw-bold text-primary mb-0"><?php echo format_price($listing['Price']); ?></h2>
                    <?php if (!empty($listing['OriginalPrice']) && (float)$listing['OriginalPrice'] > (float)$listing['Price']): ?>
                        <div class="text-muted mt-1">
                            <span class="text-decoration-line-through"><?php echo format_price($listing['OriginalPrice']); ?></span>
                            <span class="badge bg-danger ms-1">Save <?php echo format_price((float)$listing['OriginalPrice'] - (float)$listing['Price']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row g-2 py-2 border-top border-bottom my-3">
                    <div class="col-6">
                        <span class="text-muted small d-block">Quantity Available</span>
                        <strong class="fs-5 text-dark"><?php echo $listing['Quantity']; ?> units</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Sold By</span>
                        <strong class="fs-6 text-dark d-flex align-items-center mt-1">
                            <i class="bi bi-shop text-primary me-1"></i>
                            <?php echo sanitize($listing['StoreName'] ?? ('Store #' . $listing['SellerID'])); ?>
                        </strong>
                    </div>
                </div>

                <!-- Purchase & Reservation Actions -->
                <div class="mt-3 pt-3 border-top">
                    <?php if ($isVerifiedAuthentic): ?>
                        <div class="alert alert-success d-flex align-items-center py-2 px-3 mb-3 small" role="alert">
                            <i class="bi bi-patch-check-fill text-success fs-5 me-2"></i>
                            <div><strong>Verified Authentic:</strong> Inspected by staff.</div>
                        </div>
                    <?php endif; ?>

                    <?php if ($listing['Status'] === 'Live' && (int)$listing['Quantity'] > 0): ?>
                        <a href="<?php echo base_url('listing_reservation/add.php?listing_id=' . $id); ?>" class="btn btn-warning w-100 fw-bold py-2 mb-2 shadow-sm d-flex align-items-center justify-content-center">
                            <i class="bi bi-clock-history me-2"></i> Place Reservation Hold
                        </a>
                    <?php elseif ($listing['Status'] === 'Reserved' || (int)$listing['Quantity'] <= 0): ?>
                        <button class="btn btn-secondary w-100 py-2 mb-2" disabled>
                            <i class="bi bi-lock me-1"></i> Currently Reserved / Out of Stock
                        </button>
                    <?php else: ?>
                        <div class="alert alert-warning py-2 px-3 small mb-2">
                            <i class="bi bi-info-circle me-1"></i> Listing status is <strong><?php echo sanitize($listing['Status']); ?></strong>. Not currently active for checkout.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Shipping dimensions -->
                <h6 class="fw-bold text-dark mt-3 mb-2 small text-uppercase">Shipping & Logistics</h6>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Weight:</span>
                        <strong><?php echo number_format($listing['WeightGrams']); ?> g</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Dimensions (L x W x H):</span>
                        <strong><?php echo $listing['LengthCm']; ?> &times; <?php echo $listing['WidthCm']; ?> &times; <?php echo $listing['HeightCm']; ?> cm</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Declared Insured Value:</span>
                        <strong><?php echo format_price($listing['InsuredValue']); ?></strong>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Verification Record Card -->
        <div class="card card-kamera mb-4">
            <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="bi bi-shield-check me-2 text-success"></i>Gear Verification</span>
                <a href="<?php echo base_url('product_verification/add.php?listing_id=' . $id); ?>" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-plus-circle me-1"></i> Add Record
                </a>
            </div>
            <div class="card-body">
                <?php if ($verifications && $verifications->num_rows > 0): ?>
                    <?php while ($v = $verifications->fetch_assoc()): ?>
                        <div class="border rounded p-3 mb-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong><?php echo sanitize($v['VerificationType']); ?></strong>
                                <?php echo status_badge($v['VerificationStatus']); ?>
                            </div>
                            <small class="text-muted d-block">
                                Admin #<?php echo $v['VerifiedByAdminID']; ?> &bull; <?php echo !empty($v['VerifiedAt']) ? date('M d, Y h:i A', strtotime($v['VerifiedAt'])) : 'Pending'; ?>
                            </small>
                            <?php if (!empty($v['Remarks'])): ?>
                                <p class="small mb-0 mt-2 text-dark bg-white p-2 rounded border"><?php echo sanitize($v['Remarks']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">No verification checks registered yet for this listing.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reservations Card -->
        <div class="card card-kamera">
            <div class="card-header card-header-kamera d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="bi bi-clock-history me-2 text-info"></i>Active Reservations</span>
                <a href="<?php echo base_url('listing_reservation/add.php?listing_id=' . $id); ?>" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-plus-circle me-1"></i> Reserve
                </a>
            </div>
            <div class="card-body">
                <?php if ($reservations && $reservations->num_rows > 0): ?>
                    <?php while ($r = $reservations->fetch_assoc()): ?>
                        <div class="border rounded p-3 mb-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>Order <strong>#<?php echo $r['OrderID']; ?></strong> (<?php echo $r['Quantity']; ?> qty)</span>
                                <?php echo status_badge($r['Status']); ?>
                            </div>
                            <small class="text-muted d-block">
                                Expires: <?php echo date('M d, Y h:i A', strtotime($r['ExpiresAt'])); ?>
                            </small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">No reservations currently held on this listing.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
