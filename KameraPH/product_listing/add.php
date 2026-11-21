<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Add Product Listing';
$activeMenu = 'product_listing';

$message = "";

// Form state defaults
$sellerID = "";
$modelID = isset($_GET['model_id']) ? (int)$_GET['model_id'] : "";
$title = "";
$description = "";
$price = "";
$originalPrice = "";
$condition = "Excellent";
$quantity = "1";
$weightGrams = "650";
$lengthCm = "14.5";
$widthCm = "12.0";
$heightCm = "9.5";
$insuredValue = "";
$status = "Draft";
$suspensionReason = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sellerID = (int)($_POST['SellerID'] ?? 0);
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
        $sql = "INSERT INTO product_listing
                (SellerID, ModelID, Title, Description, Price, OriginalPrice,
                 `Condition`, Quantity, WeightGrams, LengthCm, WidthCm,
                 HeightCm, InsuredValue, Status, SuspensionReason)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iissddsiiddddss",
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
            $suspReasonVal
        );

        if ($stmt->execute()) {
            $newID = $stmt->insert_id;
            $stmt->close();
            flash('success', "Listing '{$title}' (#{$newID}) was created successfully!");
            redirect("product_listing/view.php?id={$newID}");
        } else {
            $message = "Error creating listing: " . $stmt->error;
            $stmt->close();
        }
    }
}

// Fetch active camera models
$models = $conn->query("
    SELECT pm.ModelID, pm.ModelName, b.BrandName, c.CategoryName
    FROM product_model pm
    INNER JOIN brand b ON pm.BrandID = b.BrandID
    INNER JOIN category c ON pm.CategoryID = c.CategoryID
    ORDER BY b.BrandName ASC, pm.ModelName ASC
");

$conditions = ['Brand New', 'Mint', 'Excellent', 'Good', 'Fair', 'With defects'];
$statuses = ['Draft', 'Pending Verification', 'Rejected', 'Live', 'Reserved', 'Sold', 'Suspended'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mb-5">
    <div class="col-lg-9">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Listings</a></li>
                <li class="breadcrumb-item active" aria-current="page">New Listing</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Create New Product Listing
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
                        <div class="col-md-4">
                            <label for="SellerID" class="form-label">Seller User ID <span class="text-danger">*</span></label>
                            <input type="number" name="SellerID" id="SellerID" class="form-control" min="1" placeholder="e.g. 101" value="<?php echo sanitize($sellerID); ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label for="ModelID" class="form-label">Camera Model <span class="text-danger">*</span></label>
                            <select name="ModelID" id="ModelID" class="form-select" required>
                                <option value="">Select Camera Model</option>
                                <?php if ($models): ?>
                                    <?php while ($m = $models->fetch_assoc()): ?>
                                        <option value="<?php echo $m['ModelID']; ?>" <?php echo ($modelID == $m['ModelID']) ? 'selected' : ''; ?>>
                                            <?php echo sanitize($m['BrandName'] . ' - ' . $m['ModelName'] . ' (' . $m['CategoryName'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="Title" class="form-label">Listing Title <span class="text-danger">*</span></label>
                            <input type="text" name="Title" id="Title" class="form-control" maxlength="255" placeholder="e.g. Sony Alpha A6400 with 16-50mm Kit Lens - Low Shutter Count" value="<?php echo sanitize($title); ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="Description" class="form-label">Description & Inclusions</label>
                            <textarea name="Description" id="Description" class="form-control" rows="4" placeholder="Detail condition, included accessories (batteries, charger, box, strap), shutter count, cosmetic notes..."><?php echo sanitize($description); ?></textarea>
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
                                <input type="number" name="Price" id="Price" class="form-control" min="0.01" step="0.01" placeholder="0.00" value="<?php echo sanitize($price); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="OriginalPrice" class="form-label">Original / SRP (PHP)</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="OriginalPrice" id="OriginalPrice" class="form-control" min="0" step="0.01" placeholder="0.00" value="<?php echo sanitize($originalPrice); ?>">
                            </div>
                            <div class="form-text">Optional comparison price.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="Condition" class="form-label">Condition <span class="text-danger">*</span></label>
                            <select name="Condition" id="Condition" class="form-select" required>
                                <?php foreach ($conditions as $c): ?>
                                    <option value="<?php echo $c; ?>" <?php echo ($condition === $c) ? 'selected' : ''; ?>>
                                        <?php echo $c; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="Quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="Quantity" id="Quantity" class="form-control" min="0" value="<?php echo sanitize($quantity); ?>" required>
                        </div>
                    </div>

                    <!-- Section 3: Shipping & Logistics -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-truck me-1 text-info"></i> 3. Shipping Dimensions & Valuation
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-md-3">
                            <label for="WeightGrams" class="form-label">Weight (grams) <span class="text-danger">*</span></label>
                            <input type="number" name="WeightGrams" id="WeightGrams" class="form-control" min="0" value="<?php echo sanitize($weightGrams); ?>" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="LengthCm" class="form-label">Length (cm) <span class="text-danger">*</span></label>
                            <input type="number" name="LengthCm" id="LengthCm" class="form-control" min="0" step="0.1" value="<?php echo sanitize($lengthCm); ?>" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="WidthCm" class="form-label">Width (cm) <span class="text-danger">*</span></label>
                            <input type="number" name="WidthCm" id="WidthCm" class="form-control" min="0" step="0.1" value="<?php echo sanitize($widthCm); ?>" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="HeightCm" class="form-label">Height (cm) <span class="text-danger">*</span></label>
                            <input type="number" name="HeightCm" id="HeightCm" class="form-control" min="0" step="0.1" value="<?php echo sanitize($heightCm); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="InsuredValue" class="form-label">Insured Valuation (PHP) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="InsuredValue" id="InsuredValue" class="form-control" min="0" step="0.01" placeholder="Insurance value" value="<?php echo sanitize($insuredValue !== '' ? $insuredValue : $price); ?>" required>
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
                                    <option value="<?php echo $st; ?>" <?php echo ($status === $st) ? 'selected' : ''; ?>>
                                        <?php echo $st; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="SuspensionReason" class="form-label">Suspension Reason</label>
                            <input type="text" name="SuspensionReason" id="SuspensionReason" class="form-control" placeholder="Only applicable if status is Suspended" value="<?php echo sanitize($suspensionReason); ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Create Listing
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
