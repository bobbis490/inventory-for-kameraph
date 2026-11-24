<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Admin or Seller

$pageTitle = 'Add Listing Specification';
$activeMenu = 'listing_specification';

$message = "";
$listingID = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
$specDefID = 0;
$specValue = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $listingID = (int)($_POST['ListingID'] ?? 0);
    $specDefID = (int)($_POST['SpecDefID'] ?? 0);
    $specValue = trim($_POST['SpecValue'] ?? '');

    if (!is_admin()) {
        $chkOwner = $conn->prepare("SELECT SellerID FROM product_listing WHERE ListingID = ?");
        $chkOwner->bind_param("i", $listingID);
        $chkOwner->execute();
        $lRow = $chkOwner->get_result()->fetch_assoc();
        $chkOwner->close();
        if (!$lRow || !can_edit_listing(['SellerID' => $lRow['SellerID']])) {
            $message = "Access denied: you can only add specifications to listings belonging to your store.";
        }
    }

    if ($message === "") {
        if ($listingID <= 0) {
            $message = "Please select a product listing.";
        } elseif ($specDefID <= 0) {
            $message = "Please select a specification.";
        } elseif ($specValue === "") {
            $message = "Specification value is required.";
        } else {
            // Check for existing duplicate specification for this listing
            $check = $conn->prepare("SELECT ListingSpecID FROM listing_specification WHERE ListingID = ? AND SpecDefID = ?");
            $check->bind_param("ii", $listingID, $specDefID);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = "This specification is already defined for this listing. Please edit the existing value instead.";
                $check->close();
            } else {
                $check->close();

                $stmt = $conn->prepare("INSERT INTO listing_specification (ListingID, SpecDefID, SpecValue) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $listingID, $specDefID, $specValue);

                if ($stmt->execute()) {
                    $stmt->close();
                    flash('success', "Specification added successfully!");
                    redirect("product_listing/view.php?id={$listingID}");
                } else {
                    $message = "Error adding specification: " . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

if (!is_admin()) {
    $sellerId = get_current_seller_id();
    $listings = $conn->query("SELECT ListingID, Title FROM product_listing WHERE SellerID = " . (int)$sellerId . " ORDER BY Title ASC");
} else {
    $listings = $conn->query("SELECT ListingID, Title FROM product_listing ORDER BY Title ASC");
}

$listingCatID = 0;
if ($listingID > 0) {
    $catQ = $conn->query("SELECT pm.CategoryID FROM product_listing pl INNER JOIN product_model pm ON pl.ModelID = pm.ModelID WHERE pl.ListingID = {$listingID}");
    if ($catQ && $crow = $catQ->fetch_assoc()) {
        $listingCatID = (int)$crow['CategoryID'];
    }
}

$specifications = $conn->query("
    SELECT sd.SpecDefID, sd.SpecName, sd.DataType, sd.Unit, sd.CategoryID, c.CategoryName
    FROM spec_definition sd
    LEFT JOIN category c ON sd.CategoryID = c.CategoryID
    ORDER BY (sd.CategoryID = {$listingCatID}) DESC, c.CategoryName ASC, sd.SpecName ASC
");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Specifications</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Spec</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-plus-circle text-danger me-2"></i>Add Listing Specification
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
                        <label for="SpecDefID" class="form-label">Specification Attribute <span class="text-danger">*</span></label>
                        <select name="SpecDefID" id="SpecDefID" class="form-select" required>
                            <option value="">Select Specification</option>
                            <?php if ($specifications): ?>
                                <?php while ($s = $specifications->fetch_assoc()): ?>
                                    <option value="<?php echo $s['SpecDefID']; ?>" <?php echo ($specDefID == $s['SpecDefID']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($s['SpecName']); ?> (<?php echo sanitize($s['DataType']); ?><?php echo !empty($s['Unit']) ? ', ' . sanitize($s['Unit']) : ''; ?>) - <?php echo sanitize($s['CategoryName'] ?? 'General'); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="SpecValue" class="form-label">Specification Value <span class="text-danger">*</span></label>
                        <input type="text" name="SpecValue" id="SpecValue" class="form-control" placeholder="e.g. 24.2 MP, 4K 60fps, 1/8000s, Yes" value="<?php echo sanitize($specValue); ?>" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="<?php echo ($listingID > 0) ? base_url('product_listing/view.php?id=' . $listingID) : 'index.php'; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Specification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
