<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Admin or Seller

$pageTitle = 'Listing Images';
$activeMenu = 'listing_image';

$listingFilter = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;

$currUser = current_user();
$whereConditions = [];
if ($listingFilter > 0) {
    $whereConditions[] = "li.ListingID = {$listingFilter}";
}
if (!is_admin() && !empty($currUser['SellerID'])) {
    $whereConditions[] = "pl.SellerID = " . (int)$currUser['SellerID'];
}
$whereSql = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

$sql = "SELECT li.*, pl.Title, pl.Price
        FROM listing_image li
        INNER JOIN product_listing pl ON li.ListingID = pl.ListingID
        {$whereSql}
        ORDER BY li.ListingID DESC, li.IsPrimary DESC, li.DisplayOrder ASC";

$result = $conn->query($sql);

$listings = $conn->query("SELECT ListingID, Title FROM product_listing ORDER BY Title ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold mb-0">
            <i class="bi bi-images text-warning me-2"></i>Listing Image Management
        </h2>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="add.php<?php echo ($listingFilter > 0) ? '?listing_id=' . $listingFilter : ''; ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-upload me-1"></i> Add / Upload Image
        </a>
    </div>
</div>

<div class="card card-kamera">
    <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold text-dark">Photos & Attachments</span>
        <form method="GET" class="d-flex" style="max-width: 320px;">
            <select name="listing_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Filter by Product Listing (All)</option>
                <?php if ($listings): ?>
                    <?php while ($l = $listings->fetch_assoc()): ?>
                        <option value="<?php echo $l['ListingID']; ?>" <?php echo ($listingFilter == $l['ListingID']) ? 'selected' : ''; ?>>
                            #<?php echo $l['ListingID']; ?> - <?php echo sanitize(mb_substr($l['Title'], 0, 35)) . (mb_strlen($l['Title']) > 35 ? '...' : ''); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <?php if ($listingFilter > 0): ?>
                <a href="index.php" class="btn btn-sm btn-outline-secondary ms-1" title="Clear Filter"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-kamera table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 80px;">Preview</th>
                    <th>Product Listing</th>
                    <th>Path / URL</th>
                    <th style="width: 120px;" class="text-center">Display Order</th>
                    <th style="width: 120px;" class="text-center">Primary</th>
                    <th style="width: 150px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?php echo image_url($row['ImageURL']); ?>" alt="Photo" class="listing-thumb shadow-sm" style="cursor: pointer;" onclick="showImageModal(this.src, '<?php echo addslashes(sanitize($row['Title'])); ?>')">
                            </td>
                            <td>
                                <a href="<?php echo base_url('product_listing/view.php?id=' . $row['ListingID']); ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo sanitize($row['Title']); ?>
                                </a>
                                <small class="text-muted d-block">Listing ID: #<?php echo $row['ListingID']; ?></small>
                            </td>
                            <td>
                                <span class="text-muted small font-monospace d-inline-block text-truncate" style="max-width: 250px;">
                                    <?php echo sanitize($row['ImageURL']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-2 py-1"><?php echo $row['DisplayOrder']; ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($row['IsPrimary']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Primary</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Secondary</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $row['ImageID']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <form action="delete.php" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete this image (ID #<?php echo $row['ImageID']; ?>)?');">
                                        <input type="hidden" name="id" value="<?php echo $row['ImageID']; ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-images empty-state-icon"></i>
                                <h5>No Images Found</h5>
                                <p class="text-muted">No listing photos found matching your current filter.</p>
                                <a href="add.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-upload me-1"></i> Upload Photo
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Enlarged Preview -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalTitle">Image Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-2 bg-light">
                <img id="modalPreviewImg" src="" alt="Enlarged Preview" class="img-fluid rounded" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<script>
function showImageModal(src, title) {
    document.getElementById('modalPreviewImg').src = src;
    document.getElementById('previewModalTitle').innerText = title;
    var modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
