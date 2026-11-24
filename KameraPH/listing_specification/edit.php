<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Admin or Seller

$pageTitle = 'Edit Listing Specification';
$activeMenu = 'listing_specification';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    flash('danger', 'Invalid specification ID.');
    redirect('listing_specification/index.php');
}

$stmt = $conn->prepare("SELECT * FROM listing_specification WHERE ListingSpecID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$specification = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$specification) {
    flash('danger', 'Listing specification not found.');
    redirect('listing_specification/index.php');
}

if (!is_admin()) {
    $chkOwner = $conn->prepare("SELECT SellerID FROM product_listing WHERE ListingID = ?");
    $chkOwner->bind_param("i", $specification['ListingID']);
    $chkOwner->execute();
    $lRow = $chkOwner->get_result()->fetch_assoc();
    $chkOwner->close();
    if (!$lRow || !can_edit_listing(['SellerID' => $lRow['SellerID']])) {
        flash('danger', 'Access denied: You do not own this listing specification.');
        redirect('product_listing/index.php');
    }
}

$message = "";
$listingID = $specification['ListingID'];
$specDefID = $specification['SpecDefID'];
$specValue = $specification['SpecValue'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $listingID = (int)($_POST['ListingID'] ?? 0);
    $specDefID = (int)($_POST['SpecDefID'] ?? 0);
    $specValue = trim($_POST['SpecValue'] ?? '');

    if (!is_admin()) {
        $chkTarget = $conn->prepare("SELECT SellerID FROM product_listing WHERE ListingID = ?");
        $chkTarget->bind_param("i", $listingID);
        $chkTarget->execute();
        $tRow = $chkTarget->get_result()->fetch_assoc();
        $chkTarget->close();
        if (!$tRow || !can_edit_listing(['SellerID' => $tRow['SellerID']])) {
            $message = "Access denied: you can only assign specifications to listings belonging to your store.";
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
            $stmt = $conn->prepare("UPDATE listing_specification SET ListingID = ?, SpecDefID = ?, SpecValue = ? WHERE ListingSpecID = ?");
            $stmt->bind_param("iisi", $listingID, $specDefID, $specValue, $id);

            if ($stmt->execute()) {
                $stmt->close();
                flash('success', "Specification updated successfully!");
                redirect("product_listing/view.php?id={$listingID}");
            } else {
                $message = "Error updating specification: " . $stmt->error;
                $stmt->close();
            }
        }
    }

    $specification['ListingID'] = $listingID;
    $specification['SpecDefID'] = $specDefID;
    $specification['SpecValue'] = $specValue;
}

if (!is_admin()) {
    $sellerId = get_current_seller_id();
    $listings = $conn->query("SELECT ListingID, Title FROM product_listing WHERE SellerID = " . (int)$sellerId . " ORDER BY Title ASC");
} else {
    $listings = $conn->query("SELECT ListingID, Title FROM product_listing ORDER BY Title ASC");
}
$specifications = $conn->query("
    SELECT sd.SpecDefID, sd.SpecName, sd.DataType, sd.Unit, c.CategoryName
    FROM spec_definition sd
    LEFT JOIN category c ON sd.CategoryID = c.CategoryID
    ORDER BY c.CategoryName ASC, sd.SpecName ASC
");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Specifications</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Spec #<?php echo $id; ?></li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-pencil-square text-danger me-2"></i>Edit Listing Specification #<?php echo $id; ?>
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
                                    <option value="<?php echo $l['ListingID']; ?>" <?php echo ($specification['ListingID'] == $l['ListingID']) ? 'selected' : ''; ?>>
                                        #<?php echo $l['ListingID']; ?> - <?php echo sanitize($l['Title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="SpecDefID" class="form-label">Specification Attribute <span class="text-danger">*</span></label>
                        <select name="SpecDefID" id="SpecDefID" class="form-select" required>
                            <?php if ($specifications): ?>
                                <?php while ($s = $specifications->fetch_assoc()): ?>
                                    <option value="<?php echo $s['SpecDefID']; ?>" <?php echo ($specification['SpecDefID'] == $s['SpecDefID']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($s['SpecName']); ?> (<?php echo sanitize($s['DataType']); ?><?php echo !empty($s['Unit']) ? ', ' . sanitize($s['Unit']) : ''; ?>) - <?php echo sanitize($s['CategoryName'] ?? 'General'); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="SpecValue" class="form-label">Specification Value <span class="text-danger">*</span></label>
                        <input type="text" name="SpecValue" id="SpecValue" class="form-control" value="<?php echo sanitize($specification['SpecValue']); ?>" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php?listing_id=<?php echo $specification['ListingID']; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Update Specification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
