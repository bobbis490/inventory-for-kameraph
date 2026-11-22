<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Requires login (admin or listing owner)

$currUser = current_user();

$pageTitle = 'Edit Product Listing';
$activeMenu = 'product_listing';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    flash('danger', 'Invalid listing ID.');
    redirect('product_listing/index.php');
}

$stmt = $conn->prepare("SELECT * FROM product_listing WHERE ListingID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$listing) {
    flash('danger', 'Product listing not found.');
    redirect('product_listing/index.php');
}

// Ownership check: if seller and not admin, can only edit their own listings
if (!is_admin() && (int)$listing['SellerID'] !== (int)($currUser['SellerID'] ?? 0)) {
    flash('danger', 'Access denied. You can only edit listings belonging to your store.');
    redirect('product_listing/index.php');
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sellerID = is_admin() ? (int)($_POST['SellerID'] ?? $listing['SellerID']) : (int)$listing['SellerID'];
    $modelID = (int)($_POST['ModelID'] ?? 0);
    $title = trim($_POST['Title'] ?? '');
    $description = trim($_POST['Description'] ?? '');
    $priceInput = trim($_POST['Price'] ?? '');
    $origPriceInput = trim($_POST['OriginalPrice'] ?? '');
    $condition = $_POST['Condition'] ?? 'Excellent';
    $quantity = (int)($_POST['Quantity'] ?? 1);
    $weightGrams = (int)($_POST['WeightGrams'] ?? 0);
    $lengthCm = (float)($_POST['LengthCm'] ?? 0);
    $widthCm = (float)($_POST['WidthCm'] ?? 0);
    $heightCm = (float)($_POST['HeightCm'] ?? 0);
    $insuredInput = trim($_POST['InsuredValue'] ?? '');
    $status = $_POST['Status'] ?? 'Draft';
    $suspensionReason = trim($_POST['SuspensionReason'] ?? '');

    $price = ($priceInput !== '') ? (float)$priceInput : 0.0;
    $origPrice = ($origPriceInput !== '') ? (float)$origPriceInput : null;
    $descVal = ($description !== '') ? $description : null;
    $insuredVal = ($insuredInput !== '') ? (float)$insuredInput : 0.0;
    $suspReasonVal = ($suspensionReason !== '') ? $suspensionReason : null;

    if ($sellerID <= 0) {
        $message = "Please provide a valid Seller ID.";
    } elseif ($modelID <= 0) {
        $message = "Please select a Camera Model.";
    } elseif ($title === "") {
        $message = "Listing title is required.";
    } elseif ($price <= 0) {
        $message = "Selling price must be greater than zero.";
    } elseif ($quantity < 0) {
        $message = "Quantity cannot be negative.";
    } else {
        $sql = "UPDATE product_listing SET
                SellerID = ?, ModelID = ?, Title = ?, Description = ?,
                Price = ?, OriginalPrice = ?, `Condition` = ?,
                Quantity = ?, WeightGrams = ?, LengthCm = ?,
                WidthCm = ?, HeightCm = ?, InsuredValue = ?,
                Status = ?, SuspensionReason = ?
                WHERE ListingID = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iissddsiiddddssi",
            $sellerID,
            $modelID,
            $title,
            $descVal,
            $price,
            $origPrice,
            $condition,
            $quantity,
            $weightGrams,
            $lengthCm,
            $widthCm,
            $heightCm,
            $insuredVal,
            $status,
            $suspReasonVal,
            $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            flash('success', "Listing #{$id} updated successfully!");
            redirect("product_listing/view.php?id={$id}");
        } else {
            $message = "Error updating listing: " . $stmt->error;
            $stmt->close();
        }
    }

    $listing['SellerID'] = $sellerID;
    $listing['ModelID'] = $modelID;
    $listing['Title'] = $title;
    $listing['Description'] = $description;
    $listing['Price'] = $price;
    $listing['OriginalPrice'] = $origPrice;
    $listing['Condition'] = $condition;
    $listing['Quantity'] = $quantity;
    $listing['WeightGrams'] = $weightGrams;
    $listing['LengthCm'] = $lengthCm;
    $listing['WidthCm'] = $widthCm;
    $listing['HeightCm'] = $heightCm;
    $listing['InsuredValue'] = $insuredVal;
    $listing['Status'] = $status;
    $listing['SuspensionReason'] = $suspensionReason;
}

$sellerProfiles = null;
if (is_admin()) {
    $sellerProfiles = $conn->query("SELECT SellerID, StoreName FROM seller_profile ORDER BY StoreName ASC");
}

// Fetch current store name
$storeRes = $conn->query("SELECT StoreName FROM seller_profile WHERE SellerID = " . (int)$listing['SellerID']);
$currentStoreName = ($storeRes && $sRow = $storeRes->fetch_assoc()) ? $sRow['StoreName'] : "Store #{$listing['SellerID']}";

$models = $conn->query("
    SELECT pm.ModelID, pm.ModelName, b.BrandName, c.CategoryName
    FROM product_model pm
    INNER JOIN brand b ON pm.BrandID = b.BrandID
    INNER JOIN category c ON pm.CategoryID = c.CategoryID
    ORDER BY b.BrandName ASC, pm.ModelName ASC
");

$conditions = ['Brand New', 'Mint', 'Excellent', 'Good', 'Fair', 'With defects'];
$statuses = ['Live', 'Draft', 'Pending Verification', 'Reserved', 'Sold', 'Suspended'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mb-5">
    <div class="col-lg-9">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Listings</a></li>
                <li class="breadcrumb-item"><a href="view.php?id=<?php echo $id; ?>">#<?php echo $id; ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-pencil-square text-primary me-2"></i>Edit Product Listing #<?php echo $id; ?>
                </h4>
            </div>
            <div class="card-body p-4">
                <?php if ($message !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?php echo sanitize($message); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Section 1: Item Basic Info -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-info-circle me-1 text-primary"></i> 1. Basic Listing Information
                    </h6>
                    <div class="row g-3 mb-4">
                        <?php if (is_admin()): ?>
                            <div class="col-md-4">
                                <label for="SellerID" class="form-label fw-semibold">Seller Store <span class="text-danger">*</span></label>
                                <select name="SellerID" id="SellerID" class="form-select" required>
                                    <?php if ($sellerProfiles): ?>
                                        <?php while ($sp = $sellerProfiles->fetch_assoc()): ?>
                                            <option value="<?php echo $sp['SellerID']; ?>" <?php echo ($listing['SellerID'] == $sp['SellerID']) ? 'selected' : ''; ?>>
                                                <?php echo sanitize($sp['StoreName']); ?> (#<?php echo $sp['SellerID']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Seller Store</label>
                                <div class="form-control bg-light d-flex align-items-center">
                                    <i class="bi bi-shop text-primary me-2"></i>
                                    <strong><?php echo sanitize($currentStoreName); ?></strong>
                                </div>
                                <input type="hidden" name="SellerID" value="<?php echo (int)$listing['SellerID']; ?>">
                            </div>
                        <?php endif; ?>
                        <div class="col-md-8">
                            <label for="ModelID" class="form-label">Camera Model <span class="text-danger">*</span></label>
                            <select name="ModelID" id="ModelID" class="form-select" required>
                                <?php if ($models): ?>
                                    <?php while ($m = $models->fetch_assoc()): ?>
                                        <option value="<?php echo $m['ModelID']; ?>" <?php echo ($listing['ModelID'] == $m['ModelID']) ? 'selected' : ''; ?>>
                                            <?php echo sanitize($m['BrandName'] . ' - ' . $m['ModelName'] . ' (' . $m['CategoryName'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="Title" class="form-label">Listing Title <span class="text-danger">*</span></label>
                            <input type="text" name="Title" id="Title" class="form-control" maxlength="255" value="<?php echo sanitize($listing['Title']); ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="Description" class="form-label">Description & Inclusions</label>
                            <textarea name="Description" id="Description" class="form-control" rows="4"><?php echo sanitize($listing['Description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Section 2: Pricing & Condition -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-tag me-1 text-success"></i> 2. Pricing & Condition
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="Price" class="form-label">Selling Price (PHP) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="Price" id="Price" class="form-control" min="0.01" step="0.01" value="<?php echo sanitize($listing['Price']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="OriginalPrice" class="form-label">Original / SRP (PHP)</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="OriginalPrice" id="OriginalPrice" class="form-control" min="0" step="0.01" value="<?php echo sanitize($listing['OriginalPrice'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="Condition" class="form-label">Condition <span class="text-danger">*</span></label>
                            <select name="Condition" id="Condition" class="form-select" required>
                                <?php foreach ($conditions as $c): ?>
                                    <option value="<?php echo $c; ?>" <?php echo ($listing['Condition'] === $c) ? 'selected' : ''; ?>>
                                        <?php echo $c; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="Quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="Quantity" id="Quantity" class="form-control" min="0" value="<?php echo sanitize($listing['Quantity']); ?>" required>
                        </div>
                    </div>

                    <!-- Section 3: Shipping & Logistics -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-truck me-1 text-info"></i> 3. Shipping Dimensions & Valuation
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-md-3">
                            <label for="WeightGrams" class="form-label">Weight (grams) <span class="text-danger">*</span></label>
                            <input type="number" name="WeightGrams" id="WeightGrams" class="form-control" min="0" value="<?php echo sanitize($listing['WeightGrams']); ?>" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="LengthCm" class="form-label">Length (cm) <span class="text-danger">*</span></label>
                            <input type="number" name="LengthCm" id="LengthCm" class="form-control" min="0" step="0.1" value="<?php echo sanitize($listing['LengthCm']); ?>" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="WidthCm" class="form-label">Width (cm) <span class="text-danger">*</span></label>
                            <input type="number" name="WidthCm" id="WidthCm" class="form-control" min="0" step="0.1" value="<?php echo sanitize($listing['WidthCm']); ?>" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="HeightCm" class="form-label">Height (cm) <span class="text-danger">*</span></label>
                            <input type="number" name="HeightCm" id="HeightCm" class="form-control" min="0" step="0.1" value="<?php echo sanitize($listing['HeightCm']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="InsuredValue" class="form-label">Insured Valuation (PHP) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="InsuredValue" id="InsuredValue" class="form-control" min="0" step="0.01" value="<?php echo sanitize($listing['InsuredValue']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Status -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-shield-check me-1 text-warning"></i> 4. Status & Publication
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="Status" class="form-label">Publication Status <span class="text-danger">*</span></label>
                            <select name="Status" id="Status" class="form-select" required>
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo ($listing['Status'] === $st) ? 'selected' : ''; ?>>
                                        <?php echo $st; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="SuspensionReason" class="form-label">Suspension Reason</label>
                            <input type="text" name="SuspensionReason" id="SuspensionReason" class="form-control" value="<?php echo sanitize($listing['SuspensionReason'] ?? ''); ?>" placeholder="Only applicable if status is Suspended">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
