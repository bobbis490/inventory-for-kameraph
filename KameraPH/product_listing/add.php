<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Requires login (admin, seller, or customer)

$pageTitle = 'Add Product Listing';
$activeMenu = 'product_listing';

$message = "";

// Form state defaults
$curr = current_user();
$userId = (int)($curr['UserID'] ?? 0);

// Check if user has a seller profile
$userSellerProfile = null;
if (!empty($curr['SellerID'])) {
    $selQuery = $conn->query("SELECT * FROM seller_profile WHERE SellerID = " . (int)$curr['SellerID']);
    $userSellerProfile = $selQuery ? $selQuery->fetch_assoc() : null;
} else {
    // Check if seller profile exists in DB by UserID
    $selQuery = $conn->query("SELECT * FROM seller_profile WHERE UserID = {$userId} LIMIT 1");
    if ($selQuery && $selRow = $selQuery->fetch_assoc()) {
        $userSellerProfile = $selRow;
        $_SESSION['user']['SellerID'] = (int)$selRow['SellerID'];
        $_SESSION['user']['StoreName'] = $selRow['StoreName'];
        $_SESSION['user']['Role'] = 'seller';
    }
}

$sellerID = $userSellerProfile ? (int)$userSellerProfile['SellerID'] : 0;
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
// Non-admins always start at Pending Verification; admins can choose freely
$status = is_admin() ? "Live" : "Pending Verification";
$suspensionReason = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Resolve SellerID safely
    if (is_admin()) {
        $sellerID = (int)($_POST['SellerID'] ?? 0);
    } else {
        // If regular user has no seller profile yet, create one on the fly
        if (!$userSellerProfile) {
            $storeNameInput = trim($_POST['StoreName'] ?? ($curr['FirstName'] . "'s Camera Shop"));
            $storeName = ($storeNameInput !== '') ? $storeNameInput : ($curr['FirstName'] . "'s Camera Shop");
            $autoSel = $conn->prepare("INSERT INTO seller_profile (UserID, SellerType, StoreName, VerificationStatus) VALUES (?, 'Individual', ?, 'Pending')");
            $autoSel->bind_param("is", $userId, $storeName);
            if ($autoSel->execute()) {
                $sellerID = (int)$autoSel->insert_id;
                $_SESSION['user']['SellerID'] = $sellerID;
                $_SESSION['user']['StoreName'] = $storeName;
                $_SESSION['user']['Role'] = 'seller';
                $userSellerProfile = ['SellerID' => $sellerID, 'StoreName' => $storeName];
            }
            $autoSel->close();
        } else {
            $sellerID = (int)$userSellerProfile['SellerID'];
        }
    }

    // 2. Resolve ModelID: Check if user entered a custom / new camera model
    $rawModelInput = trim($_POST['ModelID'] ?? '');
    $isNewModel = ($rawModelInput === 'new') || (isset($_POST['is_new_model']) && $_POST['is_new_model'] === '1');

    if ($isNewModel) {
        $customBrand = trim($_POST['CustomBrandName'] ?? '');
        $selectedBrandId = (int)($_POST['NewBrandID'] ?? 0);
        $newModelName = trim($_POST['NewModelName'] ?? '');
        $newCatId = (int)($_POST['NewCategoryID'] ?? 0);
        $newReleaseYear = !empty($_POST['NewReleaseYear']) ? (int)$_POST['NewReleaseYear'] : null;

        // Auto-resolve or create Brand if typed custom brand
        if ($customBrand !== '') {
            $bCheck = $conn->prepare("SELECT BrandID FROM brand WHERE LOWER(BrandName) = LOWER(?) LIMIT 1");
            $bCheck->bind_param("s", $customBrand);
            $bCheck->execute();
            $bRow = $bCheck->get_result()->fetch_assoc();
            $bCheck->close();
            if ($bRow) {
                $selectedBrandId = (int)$bRow['BrandID'];
            } else {
                $bIns = $conn->prepare("INSERT INTO brand (BrandName, Status) VALUES (?, 'Active')");
                $bIns->bind_param("s", $customBrand);
                if ($bIns->execute()) {
                    $selectedBrandId = (int)$bIns->insert_id;
                }
                $bIns->close();
            }
        }

        if ($selectedBrandId <= 0) {
            $message = "Please select or enter a Brand for your camera model.";
        } elseif ($newCatId <= 0) {
            $message = "Please select a Category for your camera model.";
        } elseif ($newModelName === '') {
            $message = "Please enter the Camera Model Name.";
        } else {
            // Check if model already exists under this brand
            $mCheck = $conn->prepare("SELECT ModelID FROM product_model WHERE BrandID = ? AND LOWER(ModelName) = LOWER(?) LIMIT 1");
            $mCheck->bind_param("is", $selectedBrandId, $newModelName);
            $mCheck->execute();
            $mRow = $mCheck->get_result()->fetch_assoc();
            $mCheck->close();
            if ($mRow) {
                $modelID = (int)$mRow['ModelID'];
            } else {
                $mIns = $conn->prepare("INSERT INTO product_model (BrandID, CategoryID, ModelName, ReleaseYear, Status) VALUES (?, ?, ?, ?, 'Active')");
                $mIns->bind_param("iisi", $selectedBrandId, $newCatId, $newModelName, $newReleaseYear);
                if ($mIns->execute()) {
                    $modelID = (int)$mIns->insert_id;
                }
                $mIns->close();
            }
        }
    } else {
        $modelID = (int)$rawModelInput;
    }

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

    // Admin can set any status; regular users are always locked to Pending Verification
    if (is_admin()) {
        $status = $_POST['Status'] ?? 'Pending Verification';
        $suspensionReason = trim($_POST['SuspensionReason'] ?? '');
    } else {
        $status = 'Pending Verification';
        $suspensionReason = '';
    }

    $price = ($priceInput !== '') ? (float)$priceInput : 0.0;
    $origPrice = ($origPriceInput !== '') ? (float)$origPriceInput : null;
    $descVal = ($description !== '') ? $description : null;
    $insuredVal = ($insuredInput !== '') ? (float)$insuredInput : 0.0;
    $suspReasonVal = ($suspensionReason !== '') ? $suspensionReason : null;

    if ($message === "") {
        if ($sellerID <= 0) {
            $message = "Please select or set up a valid Seller Store.";
        } elseif ($modelID <= 0) {
            $message = "Please select or add a Camera Model.";
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

                // Direct Primary Photo Upload
                if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
                    $fileTmp = $_FILES['primary_image']['tmp_name'];
                    $fileName = $_FILES['primary_image']['name'];
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'jfif', 'gif'];

                    if (in_array($ext, $allowedExts) && $_FILES['primary_image']['size'] <= 8 * 1024 * 1024) {
                        $uniqueName = 'img_' . $newID . '_' . time() . '.' . $ext;
                        $targetDir = __DIR__ . '/../uploads/';
                        if (!is_dir($targetDir)) {
                            @mkdir($targetDir, 0777, true);
                        }
                        if (move_uploaded_file($fileTmp, $targetDir . $uniqueName)) {
                            $imagePath = 'uploads/' . $uniqueName;
                            $imgStmt = $conn->prepare("INSERT INTO listing_image (ListingID, ImageURL, DisplayOrder, IsPrimary) VALUES (?, ?, 1, 1)");
                            $imgStmt->bind_param("is", $newID, $imagePath);
                            $imgStmt->execute();
                            $imgStmt->close();
                        }
                    }
                }

                flash('success', is_admin()
                    ? "Listing '{$title}' (#{$newID}) was created successfully!"
                    : "Your listing '{$title}' (#{$newID}) has been submitted for admin review. It will appear on the marketplace once approved."
                );
                redirect("product_listing/view.php?id={$newID}");
            } else {
                $message = "Error creating listing: " . $stmt->error;
                $stmt->close();
            }
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

// Fetch active brands for new model dropdown
$allBrands = $conn->query("SELECT BrandID, BrandName FROM brand WHERE Status = 'Active' ORDER BY BrandName ASC");

// Fetch active categories for new model dropdown
$allCategories = $conn->query("SELECT CategoryID, CategoryName FROM category WHERE Status = 'Active' ORDER BY CategoryName ASC");

// Fetch active seller profiles for admins
$sellerProfiles = null;
if (is_admin()) {
    $sellerProfiles = $conn->query("SELECT SellerID, StoreName FROM seller_profile ORDER BY StoreName ASC");
}

$conditions = ['Brand New', 'Mint', 'Excellent', 'Good', 'Fair', 'With defects'];
$statuses = ['Live', 'Draft', 'Pending Verification', 'Reserved', 'Sold', 'Suspended'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <?php if (is_admin()): ?>
                    <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                <?php endif; ?>
                <li class="breadcrumb-item"><a href="index.php">Listings</a></li>
                <li class="breadcrumb-item active" aria-current="page">New Listing</li>
            </ol>
        </nav>

        <div class="card card-kamera shadow-sm">
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

                <form method="POST" enctype="multipart/form-data">
                    <!-- Section 1: Item Basic Info -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-info-circle me-1 text-primary"></i> 1. Basic Listing Information
                    </h6>
                    <div class="row g-3 mb-4">
                        <?php if (is_admin()): ?>
                            <div class="col-md-4">
                                <label for="SellerID" class="form-label fw-semibold">Seller Store <span class="text-danger">*</span></label>
                                <select name="SellerID" id="SellerID" class="form-select" required>
                                    <option value="">Select Seller Store</option>
                                    <?php if ($sellerProfiles): ?>
                                        <?php while ($sp = $sellerProfiles->fetch_assoc()): ?>
                                            <option value="<?php echo $sp['SellerID']; ?>" <?php echo ($sellerID == $sp['SellerID']) ? 'selected' : ''; ?>>
                                                <?php echo sanitize($sp['StoreName']); ?> (#<?php echo $sp['SellerID']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        <?php elseif ($userSellerProfile): ?>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Selling As Store</label>
                                <div class="form-control bg-light d-flex align-items-center">
                                    <i class="bi bi-shop text-primary me-2"></i>
                                    <strong><?php echo sanitize($userSellerProfile['StoreName']); ?></strong>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4">
                                <label for="StoreName" class="form-label fw-semibold">Your Store Name <span class="text-danger">*</span></label>
                                <input type="text" name="StoreName" id="StoreName" class="form-control" placeholder="e.g. My Camera Shop" value="<?php echo sanitize($curr['FirstName'] . "'s Camera Shop"); ?>" required>
                                <div class="form-text text-success"><i class="bi bi-info-circle me-1"></i>A seller profile will be created automatically.</div>
                            </div>
                        <?php endif; ?>

                        <!-- Camera Model Selection or Custom Input -->
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="ModelID" class="form-label fw-semibold mb-0">Camera Model <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold" id="toggleNewModelBtn" onclick="toggleNewModel()">
                                    <i class="bi bi-plus-circle me-1"></i> Can't find model? Add New
                                </button>
                            </div>
                            <select name="ModelID" id="ModelID" class="form-select" onchange="checkModelSelect(this)">
                                <option value="">Select Camera Model</option>
                                <?php if ($models): ?>
                                    <?php while ($m = $models->fetch_assoc()): ?>
                                        <option value="<?php echo $m['ModelID']; ?>" <?php echo ($modelID == $m['ModelID']) ? 'selected' : ''; ?>>
                                            <?php echo sanitize($m['BrandName'] . ' - ' . $m['ModelName'] . ' (' . $m['CategoryName'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                                <option value="new" class="text-primary fw-bold">+ Add New Camera Model / Brand...</option>
                            </select>
                        </div>

                        <!-- Expandable Custom Model / Brand Panel -->
                        <div class="col-12" id="newModelBox" style="display: none;">
                            <input type="hidden" name="is_new_model" id="is_new_model" value="0">
                            <div class="p-3 bg-light rounded border border-primary">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold text-primary mb-0">
                                        <i class="bi bi-camera me-1"></i> Register Your Camera Model
                                    </h6>
                                    <button type="button" class="btn-close btn-sm" onclick="closeNewModel()" aria-label="Close"></button>
                                </div>
                                <p class="text-muted small mb-3">Sell any camera, lens, drone, or accessory—even if not in the catalog yet. It will be added to the system automatically!</p>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Brand <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select name="NewBrandID" id="NewBrandID" class="form-select form-select-sm">
                                                <option value="">Select Existing Brand</option>
                                                <?php if ($allBrands): ?>
                                                    <?php while ($b = $allBrands->fetch_assoc()): ?>
                                                        <option value="<?php echo $b['BrandID']; ?>"><?php echo sanitize($b['BrandName']); ?></option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <input type="text" name="CustomBrandName" id="CustomBrandName" class="form-control form-control-sm mt-1" placeholder="Or type new brand (e.g. Nikon, Leica, Hasselblad, GoPro)">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="NewModelName" class="form-label fw-semibold small">Model Name <span class="text-danger">*</span></label>
                                        <input type="text" name="NewModelName" id="NewModelName" class="form-control form-control-sm" placeholder="e.g. Z6 III, FX30, HERO 12, Pocket 6K">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="NewCategoryID" class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
                                        <select name="NewCategoryID" id="NewCategoryID" class="form-select form-select-sm">
                                            <option value="">Select Category</option>
                                            <?php if ($allCategories): ?>
                                                <?php while ($cat = $allCategories->fetch_assoc()): ?>
                                                    <option value="<?php echo $cat['CategoryID']; ?>"><?php echo sanitize($cat['CategoryName']); ?></option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="NewReleaseYear" class="form-label fw-semibold small">Release Year (Optional)</label>
                                        <input type="number" name="NewReleaseYear" id="NewReleaseYear" class="form-control form-control-sm" min="1900" max="2100" placeholder="e.g. 2024">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="Title" class="form-label fw-semibold">Listing Title <span class="text-danger">*</span></label>
                            <input type="text" name="Title" id="Title" class="form-control" maxlength="255" placeholder="e.g. Sony Alpha A6400 with 16-50mm Kit Lens - Low Shutter Count" value="<?php echo sanitize($title); ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="Description" class="form-label fw-semibold">Description & Inclusions</label>
                            <textarea name="Description" id="Description" class="form-control" rows="4" placeholder="Detail condition, included accessories (batteries, charger, box, strap), shutter count, cosmetic notes..."><?php echo sanitize($description); ?></textarea>
                        </div>
                    </div>

                    <!-- Direct Photo Upload -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-image me-1 text-primary"></i> 2. Primary Photo
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="primary_image" class="form-label fw-semibold">Upload Equipment Photo</label>
                            <input type="file" name="primary_image" id="primary_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                            <div class="form-text">Choose a clear photo of your item (JPG, PNG, WEBP, up to 8MB). You can add more photos after creating the listing.</div>
                        </div>
                    </div>

                    <!-- Section 3: Pricing & Condition -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-tag me-1 text-success"></i> 3. Pricing & Condition
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="Price" class="form-label fw-semibold">Selling Price (PHP) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="Price" id="Price" class="form-control" min="0" step="0.01" placeholder="0.00" value="<?php echo sanitize($price); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="OriginalPrice" class="form-label fw-semibold">Original Price (PHP)</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="OriginalPrice" id="OriginalPrice" class="form-control" min="0" step="0.01" placeholder="Optional" value="<?php echo sanitize($originalPrice); ?>">
                            </div>
                            <div class="form-text">Used to show discount badge.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="Condition" class="form-label fw-semibold">Item Condition <span class="text-danger">*</span></label>
                            <select name="Condition" id="Condition" class="form-select" required>
                                <?php foreach ($conditions as $c): ?>
                                    <option value="<?php echo $c; ?>" <?php echo ($condition === $c) ? 'selected' : ''; ?>>
                                        <?php echo $c; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="Quantity" class="form-label fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="Quantity" id="Quantity" class="form-control" min="0" value="<?php echo sanitize($quantity); ?>" required>
                        </div>
                    </div>

                    <!-- Section 4: Shipping & Logistics -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-truck me-1 text-info"></i> 4. Shipping Dimensions & Valuation
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-md-3">
                            <label for="WeightGrams" class="form-label fw-semibold">Weight (grams) <span class="text-danger">*</span></label>
                            <input type="number" name="WeightGrams" id="WeightGrams" class="form-control" min="0" value="<?php echo sanitize($weightGrams); ?>" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="LengthCm" class="form-label fw-semibold">Length (cm) <span class="text-danger">*</span></label>
                            <input type="number" name="LengthCm" id="LengthCm" class="form-control" min="0" step="0.1" value="<?php echo sanitize($lengthCm); ?>" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="WidthCm" class="form-label fw-semibold">Width (cm) <span class="text-danger">*</span></label>
                            <input type="number" name="WidthCm" id="WidthCm" class="form-control" min="0" step="0.1" value="<?php echo sanitize($widthCm); ?>" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="HeightCm" class="form-label fw-semibold">Height (cm) <span class="text-danger">*</span></label>
                            <input type="number" name="HeightCm" id="HeightCm" class="form-control" min="0" step="0.1" value="<?php echo sanitize($heightCm); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="InsuredValue" class="form-label fw-semibold">Insured Valuation (PHP) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="InsuredValue" id="InsuredValue" class="form-control" min="0" step="0.01" placeholder="Insurance value" value="<?php echo sanitize($insuredValue !== '' ? $insuredValue : $price); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Section 5: Status -->
                    <?php if (is_admin()): ?>
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-shield-check me-1 text-warning"></i> 5. Publication Status
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="Status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="Status" id="Status" class="form-select" required>
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo ($status === $st) ? 'selected' : ''; ?>>
                                        <?php echo $st; ?> <?php echo ($st === 'Live') ? '(Published immediately to marketplace)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="SuspensionReason" class="form-label fw-semibold">Suspension Reason</label>
                            <input type="text" name="SuspensionReason" id="SuspensionReason" class="form-control" placeholder="Only applicable if status is Suspended" value="<?php echo sanitize($suspensionReason); ?>">
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Non-admin: locked to Pending Verification -->
                    <div class="alert alert-info d-flex align-items-start mb-4" role="alert">
                        <i class="bi bi-info-circle-fill fs-5 me-3 mt-1 text-info flex-shrink-0"></i>
                        <div>
                            <strong>How it works:</strong> After you submit, your listing goes into
                            <strong>Pending Verification</strong> status. An admin will review your item and
                            approve it to go <strong>Live</strong> on the marketplace — usually within 24 hours.
                            You'll be able to track the status from your listings page.
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning fw-bold px-4">
                            <?php if (is_admin()): ?>
                                <i class="bi bi-check-lg me-1"></i> Save &amp; Publish Listing
                            <?php else: ?>
                                <i class="bi bi-send me-1"></i> Submit for Review
                            <?php endif; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleNewModel() {
    const box = document.getElementById('newModelBox');
    const isNew = document.getElementById('is_new_model');
    const modelSelect = document.getElementById('ModelID');
    if (box.style.display === 'none') {
        box.style.display = 'block';
        isNew.value = '1';
        modelSelect.value = 'new';
        document.getElementById('toggleNewModelBtn').innerHTML = '<i class="bi bi-list-ul me-1"></i> Pick from catalog list';
    } else {
        closeNewModel();
    }
}

function checkModelSelect(sel) {
    if (sel.value === 'new') {
        document.getElementById('newModelBox').style.display = 'block';
        document.getElementById('is_new_model').value = '1';
        document.getElementById('toggleNewModelBtn').innerHTML = '<i class="bi bi-list-ul me-1"></i> Pick from catalog list';
    } else {
        closeNewModel();
    }
}

function closeNewModel() {
    document.getElementById('newModelBox').style.display = 'none';
    document.getElementById('is_new_model').value = '0';
    const modelSelect = document.getElementById('ModelID');
    if (modelSelect.value === 'new') {
        modelSelect.value = '';
    }
    document.getElementById('toggleNewModelBtn').innerHTML = '<i class="bi bi-plus-circle me-1"></i> Can\'t find model? Add New';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
