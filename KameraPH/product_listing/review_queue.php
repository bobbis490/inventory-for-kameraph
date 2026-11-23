<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin');

$pageTitle   = 'Listing Review Queue';
$activeMenu  = 'product_listing';

// --- Handle approve / reject POST actions -----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && !empty($_POST['listing_id'])) {
    $listingId = (int)$_POST['listing_id'];
    $action    = $_POST['action'];
    $note      = trim($_POST['admin_note'] ?? '');
    $adminId   = (int)(current_user()['UserID'] ?? 0);

    if ($action === 'approve') {
        $upd = $conn->prepare("UPDATE product_listing SET Status = 'Live' WHERE ListingID = ?");
        $upd->bind_param("i", $listingId);
        $upd->execute();
        $upd->close();

        // Auto-create a verification record for the approval
        $ins = $conn->prepare(
            "INSERT INTO product_verification (ListingID, VerificationType, VerificationStatus, VerifiedByAdminID, Remarks, VerifiedAt)
             VALUES (?, 'Admin Review', 'Verified', ?, ?, NOW())"
        );
        $remarks = ($note !== '') ? $note : 'Approved via review queue.';
        $ins->bind_param("iis", $listingId, $adminId, $remarks);
        $ins->execute();
        $ins->close();

        flash('success', "Listing #{$listingId} approved and is now Live.");

    } elseif ($action === 'reject') {
        $reason = ($note !== '') ? $note : 'Rejected by admin — does not meet listing standards.';
        $upd = $conn->prepare("UPDATE product_listing SET Status = 'Rejected', SuspensionReason = ? WHERE ListingID = ?");
        $upd->bind_param("si", $reason, $listingId);
        $upd->execute();
        $upd->close();

        // Auto-create a verification record for the rejection
        $ins = $conn->prepare(
            "INSERT INTO product_verification (ListingID, VerificationType, VerificationStatus, VerifiedByAdminID, Remarks, VerifiedAt)
             VALUES (?, 'Admin Review', 'Rejected', ?, ?, NOW())"
        );
        $ins->bind_param("iis", $listingId, $adminId, $reason);
        $ins->execute();
        $ins->close();

        flash('warning', "Listing #{$listingId} rejected.");

    } elseif ($action === 'draft') {
        // Send back to draft (e.g. seller needs to fix something)
        $upd = $conn->prepare("UPDATE product_listing SET Status = 'Draft', SuspensionReason = ? WHERE ListingID = ?");
        $reason = ($note !== '') ? $note : 'Returned to draft by admin for revision.';
        $upd->bind_param("si", $reason, $listingId);
        $upd->execute();
        $upd->close();
        flash('info', "Listing #{$listingId} returned to Draft for revision.");
    }

    redirect('product_listing/review_queue.php');
}

// --- Fetch pending listings --------------------------------------------------
$pendingListings = $conn->query("
    SELECT pl.*, pm.ModelName, b.BrandName, c.CategoryName,
           sp.StoreName,
           (SELECT ImageURL FROM listing_image
            WHERE ListingID = pl.ListingID
            ORDER BY IsPrimary DESC, DisplayOrder ASC LIMIT 1) AS PrimaryImage,
           (SELECT COUNT(*) FROM listing_image WHERE ListingID = pl.ListingID) AS ImageCount
    FROM product_listing pl
    INNER JOIN product_model pm ON pl.ModelID = pm.ModelID
    INNER JOIN brand b ON pm.BrandID = b.BrandID
    LEFT  JOIN category c ON pm.CategoryID = c.CategoryID
    LEFT  JOIN seller_profile sp ON pl.SellerID = sp.SellerID
    WHERE pl.Status = 'Pending Verification'
    ORDER BY pl.ListingID ASC
");

$pendingCount = $pendingListings ? $pendingListings->num_rows : 0;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Listings</a></li>
                <li class="breadcrumb-item active">Review Queue</li>
            </ol>
        </nav>
        <h2 class="h3 fw-bold mb-0 mt-1">
            <i class="bi bi-clipboard2-check text-warning me-2"></i>Listing Review Queue
            <?php if ($pendingCount > 0): ?>
                <span class="badge bg-danger ms-2"><?php echo $pendingCount; ?> pending</span>
            <?php endif; ?>
        </h2>
        <p class="text-muted small mt-1 mb-0">
            Review and approve seller-submitted listings before they appear on the marketplace.
        </p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-list-ul me-1"></i> All Listings
        </a>
    </div>
</div>

<?php if ($pendingCount === 0): ?>
    <div class="card card-kamera">
        <div class="card-body text-center py-5">
            <i class="bi bi-check2-circle display-3 text-success mb-3 d-block"></i>
            <h5 class="fw-bold">All caught up!</h5>
            <p class="text-muted mb-0">No listings are currently waiting for review.</p>
        </div>
    </div>
<?php else: ?>

<div class="row g-4">
<?php while ($row = $pendingListings->fetch_assoc()): ?>
    <div class="col-12">
        <div class="card card-kamera shadow-sm border-start border-warning border-3">
            <div class="card-body">
                <div class="row g-3 align-items-start">

                    <!-- Thumbnail -->
                    <div class="col-auto">
                        <a href="view.php?id=<?php echo $row['ListingID']; ?>" target="_blank">
                            <img src="<?php echo sanitize(image_url($row['PrimaryImage'] ?? '')); ?>"
                                 alt="Photo" class="rounded border"
                                 style="width:90px;height:90px;object-fit:cover;">
                        </a>
                        <?php if ($row['ImageCount'] > 1): ?>
                            <div class="text-center mt-1">
                                <small class="text-muted"><i class="bi bi-images me-1"></i><?php echo $row['ImageCount']; ?> photos</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Listing info -->
                    <div class="col">
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                            <span class="text-muted small">#<?php echo $row['ListingID']; ?></span>
                            <?php echo condition_badge($row['Condition']); ?>
                            <span class="badge bg-secondary"><?php echo sanitize($row['CategoryName'] ?? '—'); ?></span>
                        </div>
                        <h5 class="fw-bold mb-1">
                            <a href="view.php?id=<?php echo $row['ListingID']; ?>" target="_blank" class="text-dark text-decoration-none">
                                <?php echo sanitize($row['Title']); ?>
                                <i class="bi bi-box-arrow-up-right small ms-1 text-muted"></i>
                            </a>
                        </h5>
                        <div class="small text-muted mb-2">
                            <i class="bi bi-camera me-1"></i><?php echo sanitize($row['BrandName'] . ' ' . $row['ModelName']); ?>
                            &nbsp;&bull;&nbsp;
                            <i class="bi bi-shop me-1"></i><?php echo sanitize($row['StoreName'] ?? 'Store #' . $row['SellerID']); ?>
                            &nbsp;&bull;&nbsp;
                            <i class="bi bi-clock me-1"></i>Submitted <?php echo !empty($row['CreatedAt']) ? date('M d, Y g:i A', strtotime($row['CreatedAt'])) : 'recently'; ?>
                        </div>
                        <div class="d-flex flex-wrap gap-3 small">
                            <span><strong class="text-success"><?php echo format_price($row['Price']); ?></strong>
                                <?php if (!empty($row['OriginalPrice']) && (float)$row['OriginalPrice'] > (float)$row['Price']): ?>
                                    <span class="text-muted text-decoration-line-through ms-1"><?php echo format_price($row['OriginalPrice']); ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="text-muted"><i class="bi bi-layers me-1"></i><?php echo (int)$row['Quantity']; ?> in stock</span>
                            <span class="text-muted"><i class="bi bi-weight me-1"></i><?php echo number_format($row['WeightGrams']); ?>g</span>
                        </div>
                        <?php if (!empty($row['Description'])): ?>
                            <p class="text-muted small mt-2 mb-0" style="max-height:3.5em;overflow:hidden;line-clamp:2;">
                                <?php echo sanitize(mb_substr($row['Description'], 0, 200)); ?>…
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Action Panel -->
                    <div class="col-lg-auto col-12">
                        <div class="card bg-light border-0 p-3" style="min-width:260px;">
                            <form method="POST" class="mb-0">
                                <input type="hidden" name="listing_id" value="<?php echo $row['ListingID']; ?>">

                                <label class="form-label small fw-semibold text-muted mb-1">Admin Note (optional)</label>
                                <textarea name="admin_note" class="form-control form-control-sm mb-3"
                                          rows="2" placeholder="e.g. Missing serial number, duplicate listing…"></textarea>

                                <div class="d-grid gap-2">
                                    <button type="submit" name="action" value="approve"
                                            class="btn btn-success btn-sm fw-semibold"
                                            onclick="return confirm('Approve this listing and make it Live?');">
                                        <i class="bi bi-check-circle me-1"></i> Approve &amp; Go Live
                                    </button>
                                    <button type="submit" name="action" value="draft"
                                            class="btn btn-outline-secondary btn-sm"
                                            onclick="return confirm('Return this listing to Draft for revision?');">
                                        <i class="bi bi-pencil me-1"></i> Return for Revision
                                    </button>
                                    <button type="submit" name="action" value="reject"
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Reject this listing? The seller will see it as Rejected.');">
                                        <i class="bi bi-x-circle me-1"></i> Reject Listing
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
