<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Requires login (admin, seller, or customer)

$pageTitle = 'Add Listing Reservation';
$activeMenu = 'listing_reservation';

$message = "";
$listingID = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
$orderID = "";
$quantity = 1;
$reservedAt = date('Y-m-d\TH:i');
$expiresAt = date('Y-m-d\TH:i', strtotime('+24 hours'));
$status = "Reserved";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $listingID = (int)($_POST['ListingID'] ?? 0);
    $orderID = (int)($_POST['OrderID'] ?? 0);
    $quantity = (int)($_POST['Quantity'] ?? 1);
    $reservedInput = $_POST['ReservedAt'] ?? '';
    $expiresInput = $_POST['ExpiresAt'] ?? '';
    $status = $_POST['Status'] ?? 'Reserved';

    $reservedTime = strtotime($reservedInput);
    $expiresTime = strtotime($expiresInput);

    // Validate listing & inventory availability
    $chkListing = $conn->prepare("SELECT Quantity, Status FROM product_listing WHERE ListingID = ?");
    $chkListing->bind_param("i", $listingID);
    $chkListing->execute();
    $lRow = $chkListing->get_result()->fetch_assoc();
    $chkListing->close();

    if ($listingID <= 0 || !$lRow) {
        $message = "Please select a valid product listing.";
    } elseif ($orderID <= 0) {
        $message = "Please specify a valid Order ID.";
    } elseif ($quantity < 1) {
        $message = "Hold quantity must be at least 1.";
    } elseif ($status === 'Reserved' && $quantity > (int)$lRow['Quantity']) {
        $message = "Cannot hold {$quantity} units. Only {$lRow['Quantity']} units are currently in stock.";
    } elseif (!$reservedTime || !$expiresTime) {
        $message = "Please enter valid reservation and expiration dates.";
    } elseif ($expiresTime <= $reservedTime) {
        $message = "Expiration date must be later than the reservation start date.";
    } else {
        $reservedAtFormatted = date('Y-m-d H:i:s', $reservedTime);
        $expiresAtFormatted = date('Y-m-d H:i:s', $expiresTime);

        $stmt = $conn->prepare(
            "INSERT INTO listing_reservation (ListingID, OrderID, Quantity, ReservedAt, ExpiresAt, Status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iiisss", $listingID, $orderID, $quantity, $reservedAtFormatted, $expiresAtFormatted, $status);

        if ($stmt->execute()) {
            $stmt->close();

            // Deduct held quantity from listing stock
            if ($status === 'Reserved') {
                $newQty = max(0, (int)$lRow['Quantity'] - $quantity);
                $newStatus = ($newQty === 0) ? 'Reserved' : $lRow['Status'];
                $updListing = $conn->prepare("UPDATE product_listing SET Quantity = ?, Status = ? WHERE ListingID = ?");
                $updListing->bind_param("isi", $newQty, $newStatus, $listingID);
                $updListing->execute();
                $updListing->close();
            }

            flash('success', "Reservation created for Order #{$orderID} successfully!");
            redirect("product_listing/view.php?id={$listingID}");
        } else {
            $message = "Error creating reservation: " . $stmt->error;
            $stmt->close();
        }
    }
}

$listings = $conn->query("SELECT ListingID, Title, Quantity FROM product_listing ORDER BY Title ASC");
$statuses = ["Reserved", "Completed", "Cancelled", "Expired"];
if (empty($orderID)) {
    $orderID = rand(1001, 9999);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Reservations</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Hold</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-clock-history text-info me-2"></i>Create Listing Reservation Hold
                </h4>
            </div>
            <div class="card-body p-4">
                <?php if ($message !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?php echo sanitize($message); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="ListingID" class="form-label">Product Listing <span class="text-danger">*</span></label>
                        <select name="ListingID" id="ListingID" class="form-select" required>
                            <option value="">Select Product Listing</option>
                            <?php if ($listings): ?>
                                <?php while ($l = $listings->fetch_assoc()): ?>
                                    <option value="<?php echo $l['ListingID']; ?>" <?php echo ($listingID == $l['ListingID']) ? 'selected' : ''; ?>>
                                        #<?php echo $l['ListingID']; ?> - <?php echo sanitize($l['Title']); ?> (<?php echo (int)$l['Quantity']; ?> in stock)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="OrderID" class="form-label">Order ID <span class="text-danger">*</span></label>
                            <input type="number" name="OrderID" id="OrderID" class="form-control" min="1" placeholder="e.g. 5001" value="<?php echo sanitize($orderID); ?>" required>
                            <div class="form-text">Customer reservation or checkout reference number.</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="Quantity" class="form-label">Quantity Reserved <span class="text-danger">*</span></label>
                            <input type="number" name="Quantity" id="Quantity" class="form-control" min="1" value="<?php echo sanitize($quantity); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="ReservedAt" class="form-label">Reserved At <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="ReservedAt" id="ReservedAt" class="form-control" value="<?php echo sanitize($reservedAt); ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="ExpiresAt" class="form-label">Expires At <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="ExpiresAt" id="ExpiresAt" class="form-control" value="<?php echo sanitize($expiresAt); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="Status" class="form-label">Reservation Status <span class="text-danger">*</span></label>
                        <select name="Status" id="Status" class="form-select" required>
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?php echo $st; ?>" <?php echo ($status === $st) ? 'selected' : ''; ?>>
                                    <?php echo $st; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="<?php echo ($listingID > 0) ? base_url('product_listing/view.php?id=' . $listingID) : 'index.php'; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Reservation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
