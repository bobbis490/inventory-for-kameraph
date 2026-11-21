<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Add Camera Model';
$activeMenu = 'product_model';

$message = "";
$brandID = "";
$categoryID = "";
$modelName = "";
$releaseYear = "";
$status = "Active";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $brandID = (int)($_POST['BrandID'] ?? 0);
    $categoryID = (int)($_POST['CategoryID'] ?? 0);
    $modelName = trim($_POST['ModelName'] ?? '');
    $releaseYearInput = trim($_POST['ReleaseYear'] ?? '');
    $status = in_array($_POST['Status'] ?? '', ['Active', 'Inactive']) ? $_POST['Status'] : 'Active';

    $releaseYearVal = ($releaseYearInput !== "") ? (int)$releaseYearInput : null;

    if ($brandID <= 0) {
        $message = "Please select a brand.";
    } elseif ($categoryID <= 0) {
        $message = "Please select a category.";
    } elseif ($modelName === "") {
        $message = "Model name is required.";
    } elseif ($releaseYearVal !== null && ($releaseYearVal < 1900 || $releaseYearVal > 2100)) {
        $message = "Please enter a valid release year (1900 - 2100).";
    } else {
        $sql = "INSERT INTO product_model (BrandID, CategoryID, ModelName, ReleaseYear, Status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisis", $brandID, $categoryID, $modelName, $releaseYearVal, $status);

        if ($stmt->execute()) {
            $stmt->close();
            flash('success', "Camera model '{$modelName}' added successfully!");
            redirect('product_model/index.php');
        } else {
            $message = "Error adding model: " . $stmt->error;
            $stmt->close();
        }
    }
}

$brands = $conn->query("SELECT BrandID, BrandName FROM brand WHERE Status = 'Active' ORDER BY BrandName ASC");
$categories = $conn->query("SELECT CategoryID, CategoryName FROM category WHERE Status = 'Active' ORDER BY CategoryName ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Camera Models</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Model</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Add New Camera Model
                </h4>
            </div>
            <div class="card-body p-4">
                <?php if ($message !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?php echo sanitize($message); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="BrandID" class="form-label">Brand <span class="text-danger">*</span></label>
                            <select name="BrandID" id="BrandID" class="form-select" required>
                                <option value="">Select Brand</option>
                                <?php if ($brands): ?>
                                    <?php while ($b = $brands->fetch_assoc()): ?>
                                        <option value="<?php echo $b['BrandID']; ?>" <?php echo ($brandID == $b['BrandID']) ? 'selected' : ''; ?>>
                                            <?php echo sanitize($b['BrandName']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="CategoryID" class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="CategoryID" id="CategoryID" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php if ($categories): ?>
                                    <?php while ($c = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $c['CategoryID']; ?>" <?php echo ($categoryID == $c['CategoryID']) ? 'selected' : ''; ?>>
                                            <?php echo sanitize($c['CategoryName']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="ModelName" class="form-label">Model Name <span class="text-danger">*</span></label>
                        <input type="text" name="ModelName" id="ModelName" class="form-control" placeholder="e.g. Alpha A7 IV, EOS R6, X-T5" value="<?php echo sanitize($modelName); ?>" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label for="ReleaseYear" class="form-label">Release Year</label>
                            <input type="number" name="ReleaseYear" id="ReleaseYear" class="form-control" placeholder="e.g. 2023" min="1900" max="2100" value="<?php echo sanitize($releaseYear); ?>">
                            <div class="form-text">Optional 4-digit year.</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="Status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="Status" id="Status" class="form-select" required>
                                <option value="Active" <?php echo ($status === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($status === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Camera Model
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
