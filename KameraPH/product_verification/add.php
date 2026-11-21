<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Add Product Verification';
$activeMenu = 'product_verification';

$message = "";
$listingID = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
$verificationType = "Physical Authenticity & Serial Check";
$verificationStatus = "Pending";
$adminID = "1";
$remarks = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $listingID = (int)($_POST['ListingID'] ?? 0);
    $verificationType = trim($_POST['VerificationType'] ?? '');
    $verificationStatus = $_POST['VerificationStatus'] ?? 'Pending';
    $adminID = (int)($_POST['VerifiedByAdminID'] ?? 0);
    $remarks = trim($_POST['Remarks'] ?? '');

    $remarksVal = ($remarks !== "") ? $remarks : null;
    $verifiedAt = ($verificationStatus === 'Verified') ? date('Y-m-d H:i:s') : null;

    if ($listingID <= 0) {
        $message = "Please select a product listing.";
    } elseif ($verificationType === "") {
        $message = "Verification type / check name is required.";
    } elseif ($adminID <= 0) {
        $message = "Please specify a valid Admin ID.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO product_verification
             (ListingID, VerificationType, VerificationStatus, VerifiedByAdminID, VerifiedAt, Remarks)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ississ", $listingID, $verificationType, $verificationStatus, $adminID, $verifiedAt, $remarksVal);

        if ($stmt->execute()) {
            $stmt->close();
            flash('success', "Verification record saved successfully!");
            redirect("product_verification/index.php?listing_id={$listingID}");
        } else {
            $message = "Error adding verification: " . $stmt->error;
            $stmt->close();
        }
    }
}

$listings = $conn->query("SELECT ListingID, Title FROM product_listing ORDER BY Title ASC");
$statuses = ["Pending", "Verified", "Rejected"];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Verifications</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Check</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-shield-check text-success me-2"></i>Add Verification Inspection
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
                                        #<?php echo $l['ListingID']; ?> - <?php echo sanitize($l['Title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="VerificationType" class="form-label">Verification Type / Inspection <span class="text-danger">*</span></label>
                        <input type="text" name="VerificationType" id="VerificationType" class="form-control" placeholder="e.g. Serial Number Verification, Sensor Cleanliness, Shutter Actuation Test" value="<?php echo sanitize($verificationType); ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="VerificationStatus" class="form-label">Verification Status <span class="text-danger">*</span></label>
                            <select name="VerificationStatus" id="VerificationStatus" class="form-select" required>
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo ($verificationStatus === $st) ? 'selected' : ''; ?>>
                                        <?php echo $st; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="VerifiedByAdminID" class="form-label">Admin Inspector ID <span class="text-danger">*</span></label>
                            <input type="number" name="VerifiedByAdminID" id="VerifiedByAdminID" class="form-control" min="1" value="<?php echo sanitize($adminID); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="Remarks" class="form-label">Inspection Notes & Remarks</label>
                        <textarea name="Remarks" id="Remarks" class="form-control" rows="3" placeholder="Condition details, test outcomes, optical check notes..."><?php echo sanitize($remarks); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php<?php echo ($listingID > 0) ? '?listing_id=' . $listingID : ''; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Verification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
