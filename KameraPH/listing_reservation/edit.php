<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Edit Listing Reservation';
$activeMenu = 'listing_reservation';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    flash('danger', 'Invalid reservation ID.');
    redirect('listing_reservation/index.php');
}

$stmt = $conn->prepare("SELECT * FROM listing_reservation WHERE ReservationID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    flash('danger', 'Reservation record not found.');
    redirect('listing_reservation/index.php');
}

$message = "";
$listingID = $reservation['ListingID'];
$orderID = $reservation['OrderID'];
$quantity = $reservation['Quantity'];
$reservedAt = date('Y-m-d\TH:i', strtotime($reservation['ReservedAt']));
$expiresAt = date('Y-m-d\TH:i', strtotime($reservation['ExpiresAt']));
$status = $reservation['Status'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $listingID = (int)($_POST['ListingID'] ?? 0);
    $orderID = (int)($_POST['OrderID'] ?? 0);
    $quantity = (int)($_POST['Quantity'] ?? 1);
    $reservedInput = $_POST['ReservedAt'] ?? '';
    $expiresInput = $_POST['ExpiresAt'] ?? '';
    $status = $_POST['Status'] ?? 'Reserved';

    $reservedTime = strtotime($reservedInput);
    $expiresTime = strtotime($expiresInput);

    if ($listingID <= 0) {
        $message = "Please select a product listing.";
    } elseif ($orderID <= 0) {
        $message = "Please specify a valid Order ID.";
    } elseif ($quantity < 1) {
        $message = "Quantity must be at least 1.";
    } elseif (!$reservedTime || !$expiresTime) {
        $message = "Please enter valid reservation and expiration dates.";
    } elseif ($expiresTime <= $reservedTime) {
        $message = "Expiration must be later than the reservation date.";
    } else {
        $reservedAtFormatted = date('Y-m-d H:i:s', $reservedTime);
        $expiresAtFormatted = date('Y-m-d H:i:s', $expiresTime);

        $stmt = $conn->prepare(
            "UPDATE listing_reservation SET ListingID = ?, OrderID = ?, Quantity = ?, ReservedAt = ?, ExpiresAt = ?, Status = ?
             WHERE ReservationID = ?"
        );
        $stmt->bind_param("iiisssi", $listingID, $orderID, $quantity, $reservedAtFormatted, $expiresAtFormatted, $status, $id);

        if ($stmt->execute()) {
            $stmt->close();
            flash('success', "Reservation #{$id} updated successfully!");
            redirect("listing_reservation/index.php?listing_id={$listingID}");
        } else {
            $message = "Error updating reservation: " . $stmt->error;
            $stmt->close();
        }
    }

    $reservation['ListingID'] = $listingID;
    $reservation['OrderID'] = $orderID;
    $reservation['Quantity'] = $quantity;
    $reservation['ReservedAt'] = $reservedInput;
    $reservation['ExpiresAt'] = $expiresInput;
    $reservation['Status'] = $status;
}

$listings = $conn->query("SELECT ListingID, Title FROM product_listing ORDER BY Title ASC");
$statuses = ["Reserved", "Completed", "Cancelled", "Expired"];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Reservations</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit #<?php echo $id; ?></li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-pencil-square text-info me-2"></i>Edit Listing Reservation #<?php echo $id; ?>
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
                            <?php if ($listings): ?>
                                <?php while ($l = $listings->fetch_assoc()): ?>
                                    <option value="<?php echo $l['ListingID']; ?>" <?php echo ($reservation['ListingID'] == $l['ListingID']) ? 'selected' : ''; ?>>
                                        #<?php echo $l['ListingID']; ?> - <?php echo sanitize($l['Title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="OrderID" class="form-label">Order ID <span class="text-danger">*</span></label>
                            <input type="number" name="OrderID" id="OrderID" class="form-control" min="1" value="<?php echo sanitize($reservation['OrderID']); ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="Quantity" class="form-label">Quantity Reserved <span class="text-danger">*</span></label>
                            <input type="number" name="Quantity" id="Quantity" class="form-control" min="1" value="<?php echo sanitize($reservation['Quantity']); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="ReservedAt" class="form-label">Reserved At <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="ReservedAt" id="ReservedAt" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($reservation['ReservedAt'])); ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="ExpiresAt" class="form-label">Expires At <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="ExpiresAt" id="ExpiresAt" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($reservation['ExpiresAt'])); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="Status" class="form-label">Reservation Status <span class="text-danger">*</span></label>
                        <select name="Status" id="Status" class="form-select" required>
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?php echo $st; ?>" <?php echo ($reservation['Status'] === $st) ? 'selected' : ''; ?>>
                                    <?php echo $st; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php?listing_id=<?php echo $reservation['ListingID']; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Update Reservation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
