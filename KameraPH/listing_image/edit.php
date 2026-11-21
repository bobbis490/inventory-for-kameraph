<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Edit Listing Image';
$activeMenu = 'listing_image';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    flash('danger', 'Invalid image ID.');
    redirect('listing_image/index.php');
}

$stmt = $conn->prepare("SELECT * FROM listing_image WHERE ImageID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$image = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$image) {
    flash('danger', 'Listing image not found.');
    redirect('listing_image/index.php');
}

$message = "";
$listingID = $image['ListingID'];
$imageURL = $image['ImageURL'];
$displayOrder = $image['DisplayOrder'];
$isPrimary = $image['IsPrimary'];

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
    $urlInput = trim($_POST['ImageURL'] ?? '');
    $existingSelect = trim($_POST['ExistingImage'] ?? '');
    $displayOrder = (int)($_POST['DisplayOrder'] ?? 1);
    $isPrimary = isset($_POST['IsPrimary']) ? 1 : 0;

    $finalImageURL = $image['ImageURL'];

    // 1. Direct file upload
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
    // 2. Existing uploads selection
    elseif ($existingSelect !== "") {
        $finalImageURL = "uploads/" . ltrim($existingSelect, '/');
    }
    // 3. User entered URL / filename
    elseif ($urlInput !== "") {
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
            $message = "Image URL or path cannot be empty.";
        } else {
            $conn->begin_transaction();

            try {
                if ($isPrimary) {
                    $clear = $conn->prepare("UPDATE listing_image SET IsPrimary = 0 WHERE ListingID = ? AND ImageID != ?");
                    $clear->bind_param("ii", $listingID, $id);
                    $clear->execute();
                    $clear->close();
                }

                $stmt = $conn->prepare("UPDATE listing_image SET ListingID = ?, ImageURL = ?, DisplayOrder = ?, IsPrimary = ? WHERE ImageID = ?");
                $stmt->bind_param("isiii", $listingID, $finalImageURL, $displayOrder, $isPrimary, $id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                flash('success', "Image updated successfully!");
                redirect("listing_image/index.php?listing_id={$listingID}");
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error updating image: " . $e->getMessage();
            }
        }
    }

    $image['ListingID'] = $listingID;
    $image['ImageURL'] = $finalImageURL;
    $image['DisplayOrder'] = $displayOrder;
    $image['IsPrimary'] = $isPrimary;
}

$listings = $conn->query("SELECT ListingID, Title FROM product_listing ORDER BY Title ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-7">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Listing Images</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Photo #<?php echo $id; ?></li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-pencil-square text-warning me-2"></i>Edit Listing Photo #<?php echo $id; ?>
                </h4>
            </div>
            <div class="card-body p-4">
                <?php if ($message !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?php echo sanitize($message); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Current & Live Preview -->
                <div class="text-center p-3 mb-4 bg-light rounded border">
                    <span class="text-muted small text-uppercase fw-semibold d-block mb-2">Photo Preview</span>
                    <img id="imageLivePreview" src="<?php echo image_url($image['ImageURL']); ?>" alt="Current Photo" class="img-fluid rounded border shadow-sm" style="max-height: 220px; min-height: 120px; object-fit: contain; background: #ffffff;">
                    <div id="previewStatus" class="small text-muted font-monospace mt-2 text-truncate"><?php echo sanitize($image['ImageURL']); ?></div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="ListingID" class="form-label">Product Listing <span class="text-danger">*</span></label>
                        <select name="ListingID" id="ListingID" class="form-select" required>
                            <?php if ($listings): ?>
                                <?php while ($l = $listings->fetch_assoc()): ?>
                                    <option value="<?php echo $l['ListingID']; ?>" <?php echo ($image['ListingID'] == $l['ListingID']) ? 'selected' : ''; ?>>
                                        #<?php echo $l['ListingID']; ?> - <?php echo sanitize($l['Title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Option 1: File Upload -->
                    <div class="card bg-light border p-3 mb-3">
                        <h6 class="fw-bold text-dark mb-2">
                            <i class="bi bi-folder-plus text-primary me-1"></i> Upload Replacement from Computer
                        </h6>
                        <input type="file" name="image_file" id="image_file" class="form-control" accept="image/*" onchange="handleFileSelect(event)">
                        <div class="form-text">Leave empty if you don't wish to upload a new file.</div>
                    </div>

                    <!-- Option 2: Select from uploads/ folder -->
                    <?php if (!empty($existingUploads)): ?>
                        <div class="card bg-light border p-3 mb-3">
                            <h6 class="fw-bold text-dark mb-2">
                                <i class="bi bi-images text-success me-1"></i> Pick File in <code>uploads/</code> Folder
                            </h6>
                            <select name="ExistingImage" id="ExistingImage" class="form-select" onchange="handleExistingSelect(this.value)">
                                <option value="">-- Choose from existing files in uploads/ --</option>
                                <?php foreach ($existingUploads as $file): ?>
                                    <option value="<?php echo sanitize($file); ?>" <?php echo (basename($image['ImageURL']) === $file) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($file); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Option 3: Image URL or Path -->
                    <div class="card bg-light border p-3 mb-4">
                        <h6 class="fw-bold text-dark mb-2">
                            <i class="bi bi-link-45deg text-info me-1"></i> Image URL or Relative Path
                        </h6>
                        <input type="text" name="ImageURL" id="ImageURL" class="form-control" value="<?php echo sanitize($image['ImageURL']); ?>" oninput="handleUrlInput(this.value)" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label for="DisplayOrder" class="form-label">Display Order</label>
                            <input type="number" name="DisplayOrder" id="DisplayOrder" class="form-control" min="1" value="<?php echo sanitize($image['DisplayOrder']); ?>" required>
                        </div>
                        <div class="col-sm-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="IsPrimary" id="IsPrimary" value="1" <?php echo ($image['IsPrimary']) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="IsPrimary">
                                    Set as Primary / Cover Photo
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php?listing_id=<?php echo $image['ListingID']; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Update Image
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
        if (clean.match(/^https?:\/\//i)) {
            previewImg.src = clean;
            statusText.innerText = "External URL preview";
            return;
        }
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
