<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Requires login (admin or seller)

$pageTitle = 'Add New Brand';
$activeMenu = 'brand';
$message = '';
$brandName = '';
$status = 'Active';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $brandName = trim($_POST['BrandName'] ?? '');
    $status = in_array($_POST['Status'] ?? '', ['Active', 'Inactive']) ? $_POST['Status'] : 'Active';

    if ($brandName === "") {
        $message = "Brand name is required.";
    } else {
        $sql = "INSERT INTO brand (BrandName, Status) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $brandName, $status);

        if ($stmt->execute()) {
            $stmt->close();
            flash('success', "Brand '{$brandName}' was added successfully!");
            redirect('brand/index.php');
        } else {
            if ($stmt->errno == 1062) {
                $message = "The brand '{$brandName}' already exists.";
            } else {
                $message = "Error adding brand: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Brands</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Brand</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Add New Camera Brand
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
                    <div class="mb-3">
                        <label for="BrandName" class="form-label">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="BrandName" id="BrandName" class="form-control" placeholder="e.g. Sony, Canon, Fujifilm, Nikon" value="<?php echo sanitize($brandName); ?>" required autofocus>
                        <div class="form-text">Enter the official camera or optics brand name.</div>
                    </div>

                    <div class="mb-4">
                        <label for="Status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="Status" id="Status" class="form-select" required>
                            <option value="Active" <?php echo ($status === 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($status === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <div class="form-text">Inactive brands cannot be selected for newly listed items.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Brand
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
