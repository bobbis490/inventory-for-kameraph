<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin login required

$pageTitle = 'Listing Reservations';
$activeMenu = 'listing_reservation';

$statusFilter = trim($_GET['status'] ?? '');
$listingFilter = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;

$where = [];
if ($statusFilter !== '') {
    $where[] = "lr.Status = '" . $conn->real_escape_string($statusFilter) . "'";
}
if ($listingFilter > 0) {
    $where[] = "lr.ListingID = {$listingFilter}";
}
$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT lr.*, pl.Title
        FROM listing_reservation lr
        INNER JOIN product_listing pl ON lr.ListingID = pl.ListingID
        {$whereSql}
        ORDER BY lr.ReservationID DESC";

$result = $conn->query($sql);

$statuses = ["Reserved", "Completed", "Cancelled", "Expired"];
$listings = $conn->query("SELECT ListingID, Title FROM product_listing ORDER BY Title ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold mb-0">
            <i class="bi bi-clock-history text-info me-2"></i>Gear Reservation Management
        </h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="add.php<?php echo ($listingFilter > 0) ? '?listing_id=' . $listingFilter : ''; ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Reservation
        </a>
    </div>
</div>

<div class="card card-kamera">
    <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold text-dark">Order Item Holds</span>
        <form method="GET" class="d-flex gap-2" style="max-width: 400px;">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
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
                    <th>Order ID</th>
                    <th style="width: 90px;" class="text-center">Qty</th>
                    <th>Reserved At</th>
                    <th>Expires At</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 150px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-semibold">#<?php echo $row['ReservationID']; ?></span></td>
                            <td>
                                <a href="<?php echo base_url('product_listing/view.php?id=' . $row['ListingID']); ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo sanitize($row['Title']); ?>
                                </a>
                                <small class="text-muted d-block">Listing #<?php echo $row['ListingID']; ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border">Order #<?php echo $row['OrderID']; ?></span></td>
                            <td class="text-center fw-bold"><?php echo $row['Quantity']; ?></td>
                            <td><small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($row['ReservedAt'])); ?></small></td>
                            <td>
                                <small class="<?php echo (strtotime($row['ExpiresAt']) < time() && $row['Status'] === 'Reserved') ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                    <?php echo date('M d, Y h:i A', strtotime($row['ExpiresAt'])); ?>
                                </small>
                            </td>
                            <td><?php echo status_badge($row['Status']); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $row['ReservationID']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <form action="delete.php" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete reservation #<?php echo $row['ReservationID']; ?>?');">
                                        <input type="hidden" name="id" value="<?php echo $row['ReservationID']; ?>">
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
                                <i class="bi bi-clock-history empty-state-icon"></i>
                                <h5>No Reservations Found</h5>
                                <p class="text-muted">No reservations match your current filter.</p>
                                <a href="add.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Create Reservation
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
