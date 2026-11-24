<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Requires login (admin or seller)

$currUser = current_user();

$pageTitle = 'Product Listings';
$activeMenu = 'product_listing';

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$conditionFilter = trim($_GET['condition'] ?? '');

$where = [];
$params = [];
$types = "";

// If seller, scope to their own listings
if (!is_admin()) {
    $sellerId = get_current_seller_id();
    $where[] = "pl.SellerID = ?";
    $params[] = $sellerId;
    $types .= "i";
}

if ($search !== '') {
    $where[] = "(pl.Title LIKE ? OR b.BrandName LIKE ? OR pm.ModelName LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= "sss";
}

if ($statusFilter !== '') {
    $where[] = "pl.Status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($conditionFilter !== '') {
    $where[] = "pl.Condition = ?";
    $params[] = $conditionFilter;
    $types .= "s";
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT pl.*, pm.ModelName, b.BrandName,
               (SELECT ImageURL FROM listing_image WHERE ListingID = pl.ListingID ORDER BY IsPrimary DESC, DisplayOrder ASC LIMIT 1) AS PrimaryImage,
               (SELECT COUNT(*) FROM listing_image WHERE ListingID = pl.ListingID) AS ImageCount
        FROM product_listing pl
        INNER JOIN product_model pm ON pl.ModelID = pm.ModelID
        INNER JOIN brand b ON pm.BrandID = b.BrandID
        {$whereSql}
        ORDER BY pl.ListingID DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$statuses = ['Draft', 'Pending Verification', 'Rejected', 'Live', 'Reserved', 'Sold', 'Suspended'];
$conditions = ['Brand New', 'Mint', 'Excellent', 'Good', 'Fair', 'With defects'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold mb-0">
            <i class="bi bi-box-seam text-primary me-2"></i>Product Listing Management
        </h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="add.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Listing
        </a>
    </div>
</div>

<div class="card card-kamera">
    <div class="card-header card-header-kamera">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by title, brand, or model..." value="<?php echo sanitize($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?php echo $st; ?>" <?php echo ($statusFilter === $st) ? 'selected' : ''; ?>>
                            <?php echo $st; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="condition" class="form-select form-select-sm">
                    <option value="">All Conditions</option>
                    <?php foreach ($conditions as $c): ?>
                        <option value="<?php echo $c; ?>" <?php echo ($conditionFilter === $c) ? 'selected' : ''; ?>>
                            <?php echo $c; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-filter me-1"></i>Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== '' || $conditionFilter !== ''): ?>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-kamera table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 70px;">Item</th>
                    <th>Listing Details</th>
                    <th>Camera Model</th>
                    <th>Price</th>
                    <th>Condition</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th style="width: 170px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="view.php?id=<?php echo $row['ListingID']; ?>" class="position-relative d-inline-block" onclick="sendToForYou('CLICK', <?php echo (int)$row['ListingID']; ?>);">
                                    <img src="<?php echo image_url($row['PrimaryImage'] ?? ''); ?>" alt="Photo" class="listing-thumb">
                                    <?php if ($row['ImageCount'] > 1): ?>
                                        <span class="position-absolute bottom-0 end-0 badge bg-dark opacity-75" style="font-size:0.65rem;">
                                            <i class="bi bi-images"></i> <?php echo $row['ImageCount']; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $row['ListingID']; ?>" class="fw-bold text-dark text-decoration-none d-block" onclick="sendToForYou('CLICK', <?php echo (int)$row['ListingID']; ?>);">
                                    <?php echo sanitize($row['Title']); ?>
                                </a>
                                <small class="text-muted">Listing #<?php echo $row['ListingID']; ?> &bull; Seller ID: #<?php echo $row['SellerID']; ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?php echo sanitize($row['BrandName'] . ' ' . $row['ModelName']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo format_price($row['Price']); ?></div>
                                <?php if (!empty($row['OriginalPrice']) && (float)$row['OriginalPrice'] > (float)$row['Price']): ?>
                                    <small class="text-muted text-decoration-line-through"><?php echo format_price($row['OriginalPrice']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo condition_badge($row['Condition']); ?></td>
                            <td>
                                <span class="<?php echo ($row['Quantity'] > 0) ? 'text-dark' : 'text-danger fw-bold'; ?>">
                                    <?php echo $row['Quantity']; ?>
                                </span>
                            </td>
                            <td><?php echo status_badge($row['Status']); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="view.php?id=<?php echo $row['ListingID']; ?>" class="btn btn-outline-secondary" title="View Full Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $row['ListingID']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="delete.php" style="display:inline;"
                                          onsubmit="return confirm('Delete listing #<?php echo $row['ListingID']; ?> (<?php echo addslashes(sanitize($row['Title'])); ?>)?');">
                                        <input type="hidden" name="ListingID" value="<?php echo $row['ListingID']; ?>">
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
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="bi bi-shop empty-state-icon"></i>
                                <h5>No Product Listings Found</h5>
                                <p class="text-muted">No gear listings matched your filters.</p>
                                <a href="add.php" class="btn btn-sm btn-primary">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
