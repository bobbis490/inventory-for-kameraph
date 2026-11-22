<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Admin or Seller

$pageTitle = 'Add Listing Image';
$activeMenu = 'listing_image';

$message = "";
$listingID = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
$imageURL = "";
$displayOrder = 1;
$isPrimary = 0;

// Scan existing files in uploads/ folder
$existingUploads = [];
$uploadsDir = __DIR__ . '/../uploads/';
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..' && is_file($uploadsDir . $f)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'jfif', 'gif'])) {
                $existingUploads[] = $f;
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $listingID = (int)($_POST['ListingID'] ?? 0);
    $currUser = current_user();
    if (!is_admin() && !empty($currUser['SellerID'])) {
        $chkOwner = $conn->prepare("SELECT SellerID FROM product_listing WHERE ListingID = ?");
        $chkOwner->bind_param("i", $listingID);
        $chkOwner->execute();
        $lRow = $chkOwner->get_result()->fetch_assoc();
        $chkOwner->close();
        if (!$lRow || (int)$lRow['SellerID'] !== (int)$currUser['SellerID']) {
            $message = "Access denied: you can only upload photos to listings belonging to your store.";
        }
    }
    $urlInput = trim($_POST['ImageURL'] ?? '');
    $existingSelect = trim($_POST['ExistingImage'] ?? '');
    $displayOrder = (int)($_POST['DisplayOrder'] ?? 1);
    $isPrimary = isset($_POST['IsPrimary']) ? 1 : 0;

    $finalImageURL = "";

    // 1. Priority: Direct File Upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['image_file']['tmp_name'];
        $fileName = $_FILES['image_file']['name'];
        $fileSize = $_FILES['image_file']['size'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'jfif', 'gif'];
        if (!in_array($ext, $allowedExts)) {
            $message = "Invalid image type. Allowed: JPG, JPEG, PNG, WEBP, JFIF, GIF.";
        } elseif ($fileSize > 8 * 1024 * 1024) {
            $message = "Uploaded file size exceeds 8MB limit.";
        } else {
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            $safeName = "listing_" . $listingID . "_" . bin2hex(random_bytes(6)) . "." . $ext;
            $destination = $uploadsDir . $safeName;

            if (move_uploaded_file($fileTmp, $destination)) {
                $finalImageURL = "uploads/" . $safeName;
            } else {
                $message = "Failed to save uploaded image to server.";
            }
        }
    }
    // 2. Selected from existing uploads/ folder
    elseif ($existingSelect !== "") {
        $finalImageURL = "uploads/" . ltrim($existingSelect, '/');
    }
    // 3. User typed an Image URL or path
    elseif ($urlInput !== "") {
        // Normalize any Windows paths (e.g. C:\xampp\htdocs\KameraPH\uploads\...)
        $cleanPath = str_replace('\\', '/', $urlInput);
        if (preg_match('#(?:htdocs/)?KameraPH/(.+)$#i', $cleanPath, $matches)) {
            $cleanPath = $matches[1];
        } elseif (preg_match('#[a-zA-Z]:/.+/(uploads/.+)$#i', $cleanPath, $matches)) {
            $cleanPath = $matches[1];
        }
        $cleanPath = preg_replace('#^(\.\./|\./)+#', '', $cleanPath);

        if (!preg_match('#^https?://#i', $cleanPath) && strpos($cleanPath, 'uploads/') !== 0) {
            $cleanPath = "uploads/" . ltrim($cleanPath, '/');
        }
        $finalImageURL = $cleanPath;
    }

    if ($message === "") {
        if ($listingID <= 0) {
            $message = "Please select a product listing.";
        } elseif ($finalImageURL === "") {
            $message = "Please upload a photo, choose an existing image from uploads/, or enter an image URL.";
        } else {
            $conn->begin_transaction();

            try {
                if ($isPrimary) {
                    $clear = $conn->prepare("UPDATE listing_image SET IsPrimary = 0 WHERE ListingID = ?");
                    $clear->bind_param("i", $listingID);
                    $clear->execute();
                    $clear->close();
                }

                $stmt = $conn->prepare("INSERT INTO listing_image (ListingID, ImageURL, DisplayOrder, IsPrimary) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isii", $listingID, $finalImageURL, $displayOrder, $isPrimary);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                flash('success', "Image saved and linked to Listing #{$listingID} successfully!");
                redirect("product_listing/view.php?id={$listingID}");
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error saving image: " . $e->getMessage();
            }
        }
    }
}

$currUser = current_user();
if (!is_admin() && !empty($currUser['SellerID'])) {
    $listings = $conn->query("SELECT ListingID, Title FROM product_listing WHERE SellerID = " . (int)$currUser['SellerID'] . " ORDER BY Title ASC");
} else {
    $listings = $conn->query("SELECT ListingID, Title FROM product_listing ORDER BY Title ASC");
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-7">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Listing Images</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Photo</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-upload text-warning me-2"></i>Add Listing Photo
                </h4>
            </div>
            <div class="card-body p-4">
                <?php if ($message !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?php echo sanitize($message); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Live Preview Box -->
                <div class="text-center p-3 mb-4 bg-light rounded border">
                    <span class="text-muted small text-uppercase fw-semibold d-block mb-2">Live Image Preview</span>
                    <img id="imageLivePreview" src="<?php echo image_url(''); ?>" alt="Preview" class="img-fluid rounded border shadow-sm" style="max-height: 220px; min-height: 120px; object-fit: contain; background: #ffffff;">
                    <div id="previewStatus" class="small text-muted mt-2">Select an image below to preview</div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
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

                    <!-- Option 1: File Upload -->
                    <div class="card bg-light border p-3 mb-3">
                        <h6 class="fw-bold text-dark mb-2">
                            <i class="bi bi-folder-plus text-primary me-1"></i> Option A: Upload from your Computer
                        </h6>
                        <input type="file" name="image_file" id="image_file" class="form-control" accept="image/*" onchange="handleFileSelect(event)">
                        <div class="form-text">Choose any photo from your computer (JPG, PNG, WEBP, JFIF). It will be saved into <code>c:\xampp\htdocs\KameraPH\uploads\</code> automatically.</div>
                    </div>

                    <!-- Option 2: Select from uploads/ folder -->
                    <?php if (!empty($existingUploads)): ?>
                        <div class="card bg-light border p-3 mb-3">
                            <h6 class="fw-bold text-dark mb-2">
                                <i class="bi bi-images text-success me-1"></i> Option B: Pick Existing File in <code>uploads/</code> Folder
                            </h6>
                            <select name="ExistingImage" id="ExistingImage" class="form-select" onchange="handleExistingSelect(this.value)">
                                <option value="">-- Choose from existing files in uploads/ --</option>
                                <?php foreach ($existingUploads as $file): ?>
                                    <option value="<?php echo sanitize($file); ?>">
                                        <?php echo sanitize($file); ?> (in uploads/)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Option 3: Manual URL or Filename -->
                    <div class="card bg-light border p-3 mb-4">
                        <h6 class="fw-bold text-dark mb-2">
                            <i class="bi bi-link-45deg text-info me-1"></i> Option C: Direct Image URL or Filename
                        </h6>
                        <input type="text" name="ImageURL" id="ImageURL" class="form-control" placeholder="e.g. sony-a6400.jfif or uploads/sony-a6400.jfif or https://..." value="<?php echo sanitize($imageURL); ?>" oninput="handleUrlInput(this.value)">
                        <div class="form-text">If you manually placed a photo into <code>c:\xampp\htdocs\KameraPH\uploads\</code>, just type its filename here (e.g. <code>sony-a6400.jfif</code>).</div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label for="DisplayOrder" class="form-label">Display Order</label>
                            <input type="number" name="DisplayOrder" id="DisplayOrder" class="form-control" min="1" value="<?php echo sanitize($displayOrder); ?>" required>
                        </div>
                        <div class="col-sm-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="IsPrimary" id="IsPrimary" value="1" <?php echo ($isPrimary) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="IsPrimary">
                                    Set as Primary / Cover Photo
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php<?php echo ($listingID > 0) ? '?listing_id=' . $listingID : ''; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Image
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const baseUrl = "<?php echo base_url(); ?>";
const previewImg = document.getElementById('imageLivePreview');
const statusText = document.getElementById('previewStatus');

function handleFileSelect(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            previewImg.src = evt.target.result;
            statusText.innerText = "Loaded local file: " + file.name;
        };
        reader.readAsDataURL(file);
        if (document.getElementById('ExistingImage')) document.getElementById('ExistingImage').value = "";
        document.getElementById('ImageURL').value = "";
    }
}

function handleExistingSelect(filename) {
    if (filename) {
        const fullUrl = baseUrl + "/uploads/" + filename;
        previewImg.src = fullUrl;
        statusText.innerText = "Using file from uploads/: " + filename;
        document.getElementById('image_file').value = "";
        document.getElementById('ImageURL').value = "uploads/" + filename;
    }
}

function handleUrlInput(val) {
    val = val.trim();
    if (val) {
        let clean = val.replace(/\\/g, '/');
        // If full web URL
        if (clean.match(/^https?:\/\//i)) {
            previewImg.src = clean;
            statusText.innerText = "External URL preview";
            return;
        }
        // Normalize
        clean = clean.replace(/^(.*htdocs\/KameraPH\/)/i, '');
        clean = clean.replace(/^(\.\.\/)+/i, '');
        if (!clean.startsWith('uploads/')) {
            clean = 'uploads/' + clean;
        }
        previewImg.src = baseUrl + "/" + clean;
        statusText.innerText = "Resolved to: " + baseUrl + "/" + clean;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
