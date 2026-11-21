<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Product Verifications';
$activeMenu = 'product_verification';

$statusFilter = trim($_GET['status'] ?? '');
$listingFilter = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;

$where = [];
if ($statusFilter !== '') {
    $where[] = "pv.VerificationStatus = '" . $conn->real_escape_string($statusFilter) . "'";
}
if ($listingFilter > 0) {
    $where[] = "pv.ListingID = {$listingFilter}";
}
$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT pv.*, pl.Title
        FROM product_verification pv
        INNER JOIN product_listing pl ON pv.ListingID = pl.ListingID
        {$whereSql}
        ORDER BY pv.VerificationID DESC";

$result = $conn->query($sql);

$statuses = ["Pending", "Verified", "Rejected"];
$listings = $conn->query("SELECT ListingID, Title FROM product_listing ORDER BY Title ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Verifications</li>
            </ol>
        </nav>
        <h2 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-shield-check text-success me-2"></i>Product Verification Management
        </h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="add.php<?php echo ($listingFilter > 0) ? '?listing_id=' . $listingFilter : ''; ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Verification Check
        </a>
    </div>
</div>

<div class="card card-kamera">
    <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold text-dark">Equipment Inspection Logs</span>
        <form method="GET" class="d-flex gap-2" style="max-width: 400px;">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Verification Statuses</option>
                <?php foreach ($statuses as $st): ?>
                    <option value="<?php echo $st; ?>" <?php echo ($statusFilter === $st) ? 'selected' : ''; ?>>
                        <?php echo $st; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($statusFilter !== '' || $listingFilter > 0): ?>
                <a href="index.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-kamera table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Product Listing</th>
                    <th>Inspection Type</th>
                    <th style="width: 130px;">Status</th>
                    <th>Admin ID</th>
                    <th>Verified At</th>
                    <th>Remarks</th>
                    <th style="width: 150px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-semibold">#<?php echo $row['VerificationID']; ?></span></td>
                            <td>
                                <a href="<?php echo base_url('product_listing/view.php?id=' . $row['ListingID']); ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo sanitize($row['Title']); ?>
                                </a>
                                <small class="text-muted d-block">Listing #<?php echo $row['ListingID']; ?></small>
                            </td>
                            <td><strong class="text-dark"><?php echo sanitize($row['VerificationType']); ?></strong></td>
                            <td><?php echo status_badge($row['VerificationStatus']); ?></td>
                            <td><span class="badge bg-light text-dark border">Admin #<?php echo $row['VerifiedByAdminID']; ?></span></td>
                            <td>
                                <small class="text-muted">
                                    <?php echo !empty($row['VerifiedAt']) ? date('M d, Y h:i A', strtotime($row['VerifiedAt'])) : '—'; ?>
                                </small>
                            </td>
                            <td>
                                <span class="text-muted small d-inline-block text-truncate" style="max-width: 200px;">
                                    <?php echo sanitize($row['Remarks'] ?? '—'); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $row['VerificationID']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <form action="delete.php" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete verification record #<?php echo $row['VerificationID']; ?>?');">
                                        <input type="hidden" name="id" value="<?php echo $row['VerificationID']; ?>">
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
                                <i class="bi bi-shield-check empty-state-icon"></i>
                                <h5>No Verification Records Found</h5>
                                <p class="text-muted">No gear verification checks match your current filter.</p>
                                <a href="add.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add Verification
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