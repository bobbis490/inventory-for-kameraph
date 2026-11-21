<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Edit Product Verification';
$activeMenu = 'product_verification';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    flash('danger', 'Invalid verification ID.');
    redirect('product_verification/index.php');
}

$stmt = $conn->prepare("SELECT * FROM product_verification WHERE VerificationID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$verification = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$verification) {
    flash('danger', 'Verification record not found.');
    redirect('product_verification/index.php');
}

$message = "";
$listingID = $verification['ListingID'];
$verificationType = $verification['VerificationType'];
$verificationStatus = $verification['VerificationStatus'];
$adminID = $verification['VerifiedByAdminID'];
$remarks = $verification['Remarks'] ?? '';
$verifiedAt = $verification['VerifiedAt'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $listingID = (int)($_POST['ListingID'] ?? 0);
    $verificationType = trim($_POST['VerificationType'] ?? '');
    $newStatus = $_POST['VerificationStatus'] ?? 'Pending';
    $adminID = (int)($_POST['VerifiedByAdminID'] ?? 0);
    $remarks = trim($_POST['Remarks'] ?? '');

    $remarksVal = ($remarks !== "") ? $remarks : null;

    // Handle VerifiedAt intelligently
    $newVerifiedAt = $verification['VerifiedAt'];
    if ($newStatus === 'Verified') {
        if ($verification['VerificationStatus'] !== 'Verified' || empty($newVerifiedAt)) {
            $newVerifiedAt = date('Y-m-d H:i:s');
        }
    } elseif ($newStatus === 'Pending') {
        $newVerifiedAt = null;
    }

    if ($listingID <= 0) {
        $message = "Please select a product listing.";
    } elseif ($verificationType === "") {
        $message = "Verification type / check name is required.";
    } elseif ($adminID <= 0) {
        $message = "Please specify a valid Admin ID.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE product_verification
             SET ListingID = ?, VerificationType = ?, VerificationStatus = ?, VerifiedByAdminID = ?, VerifiedAt = ?, Remarks = ?
             WHERE VerificationID = ?"
        );
        $stmt->bind_param("ississi", $listingID, $verificationType, $newStatus, $adminID, $newVerifiedAt, $remarksVal, $id);

        if ($stmt->execute()) {
            $stmt->close();
            flash('success', "Verification record #{$id} updated successfully!");
            redirect("product_verification/index.php?listing_id={$listingID}");
        } else {
            $message = "Error updating verification: " . $stmt->error;
            $stmt->close();
        }
    }

    $verification['ListingID'] = $listingID;
    $verification['VerificationType'] = $verificationType;
    $verification['VerificationStatus'] = $newStatus;
    $verification['VerifiedByAdminID'] = $adminID;
    $verification['VerifiedAt'] = $newVerifiedAt;
    $verification['Remarks'] = $remarks;
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
                <li class="breadcrumb-item active" aria-current="page">Edit #<?php echo $id; ?></li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-pencil-square text-success me-2"></i>Edit Product Verification #<?php echo $id; ?>
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
                                    <option value="<?php echo $l['ListingID']; ?>" <?php echo ($verification['ListingID'] == $l['ListingID']) ? 'selected' : ''; ?>>
                                        #<?php echo $l['ListingID']; ?> - <?php echo sanitize($l['Title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="VerificationType" class="form-label">Verification Type / Inspection <span class="text-danger">*</span></label>
                        <input type="text" name="VerificationType" id="VerificationType" class="form-control" value="<?php echo sanitize($verification['VerificationType']); ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="VerificationStatus" class="form-label">Verification Status <span class="text-danger">*</span></label>
                            <select name="VerificationStatus" id="VerificationStatus" class="form-select" required>
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo ($verification['VerificationStatus'] === $st) ? 'selected' : ''; ?>>
                                        <?php echo $st; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="VerifiedByAdminID" class="form-label">Admin Inspector ID <span class="text-danger">*</span></label>
                            <input type="number" name="VerifiedByAdminID" id="VerifiedByAdminID" class="form-control" min="1" value="<?php echo sanitize($verification['VerifiedByAdminID']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="Remarks" class="form-label">Inspection Notes & Remarks</label>
                        <textarea name="Remarks" id="Remarks" class="form-control" rows="3"><?php echo sanitize($verification['Remarks'] ?? ''); ?></textarea>
                    </div>

                    <?php if (!empty($verification['VerifiedAt'])): ?>
                        <div class="small text-muted mb-3">
                            <i class="bi bi-clock me-1"></i> Previously verified at: <strong><?php echo date('M d, Y h:i A', strtotime($verification['VerifiedAt'])); ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php?listing_id=<?php echo $verification['ListingID']; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Update Verification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
