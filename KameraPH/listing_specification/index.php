<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Admin or Seller

$pageTitle = 'Listing Specifications';
$activeMenu = 'listing_specification';

$listingFilter = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
$whereConditions = [];
if ($listingFilter > 0) {
    $whereConditions[] = "ls.ListingID = {$listingFilter}";
}
if (!is_admin()) {
    $sellerId = get_current_seller_id();
    $whereConditions[] = "pl.SellerID = " . (int)$sellerId;
}
$whereSql = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

$sql = "SELECT ls.*, pl.Title, sd.SpecName, sd.DataType, sd.Unit, c.CategoryName
        FROM listing_specification ls
        INNER JOIN product_listing pl ON ls.ListingID = pl.ListingID
        INNER JOIN spec_definition sd ON ls.SpecDefID = sd.SpecDefID
        LEFT JOIN category c ON sd.CategoryID = c.CategoryID
        {$whereSql}
        ORDER BY ls.ListingID DESC, sd.SpecName ASC";

$result = $conn->query($sql);

if (!is_admin()) {
    $sellerId = get_current_seller_id();
    $listings = $conn->query("SELECT ListingID, Title FROM product_listing WHERE SellerID = " . (int)$sellerId . " ORDER BY Title ASC");
} else {
    $listings = $conn->query("SELECT ListingID, Title FROM product_listing ORDER BY Title ASC");
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold mb-0">
            <i class="bi bi-cpu text-danger me-2"></i>Listing Specifications
        </h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="add.php<?php echo ($listingFilter > 0) ? '?listing_id=' . $listingFilter : ''; ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Specification
        </a>
    </div>
</div>

<div class="card card-kamera">
    <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold text-dark">Technical Attributes</span>
        <form method="GET" class="d-flex" style="max-width: 320px;">
            <select name="listing_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Filter by Product Listing (All)</option>
                <?php if ($listings): ?>
                    <?php while ($l = $listings->fetch_assoc()): ?>
                        <option value="<?php echo $l['ListingID']; ?>" <?php echo ($listingFilter == $l['ListingID']) ? 'selected' : ''; ?>>
                            #<?php echo $l['ListingID']; ?> - <?php echo sanitize(mb_substr($l['Title'], 0, 35)); ?>
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
                    <th style="width: 80px;">ID</th>
                    <th>Product Listing</th>
                    <th>Specification Name</th>
                    <th>Data Type</th>
                    <th>Specification Value</th>
                    <th style="width: 150px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-semibold">#<?php echo $row['ListingSpecID']; ?></span></td>
                            <td>
                                <a href="<?php echo base_url('product_listing/view.php?id=' . $row['ListingID']); ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo sanitize($row['Title']); ?>
                                </a>
                                <small class="text-muted d-block">Listing #<?php echo $row['ListingID']; ?></small>
                            </td>
                            <td>
                                <strong class="text-dark"><?php echo sanitize($row['SpecName']); ?></strong>
                                <?php if (!empty($row['CategoryName'])): ?>
                                    <small class="text-muted d-block">(<?php echo sanitize($row['CategoryName']); ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border"><?php echo sanitize($row['DataType']); ?></span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?php echo sanitize($row['SpecValue']); ?></span>
                                <?php if (!empty($row['Unit'])): ?>
                                    <span class="text-muted small"><?php echo sanitize($row['Unit']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $row['ListingSpecID']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <form action="delete.php" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete this specification?');">
                                        <input type="hidden" name="id" value="<?php echo $row['ListingSpecID']; ?>">
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
                                <i class="bi bi-cpu empty-state-icon"></i>
                                <h5>No Specifications Found</h5>
                                <p class="text-muted">No specifications found matching the current listing filter.</p>
                                <a href="add.php<?php echo ($listingFilter > 0) ? '?listing_id=' . $listingFilter : ''; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add Specification
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
