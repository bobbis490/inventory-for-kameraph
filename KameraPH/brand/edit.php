<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin login required

$pageTitle = 'Edit Brand';
$activeMenu = 'brand';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    flash('danger', 'Invalid brand ID specified.');
    redirect('brand/index.php');
}

// Fetch existing brand
$stmt = $conn->prepare("SELECT * FROM brand WHERE BrandID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$brand = $result->fetch_assoc();
$stmt->close();

if (!$brand) {
    flash('danger', 'Brand not found.');
    redirect('brand/index.php');
}

$message = "";
$brandName = $brand['BrandName'];
$status = $brand['Status'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $brandName = trim($_POST['BrandName'] ?? '');
    $status = in_array($_POST['Status'] ?? '', ['Active', 'Inactive']) ? $_POST['Status'] : 'Active';

    if ($brandName === "") {
        $message = "Brand name is required.";
    } else {
        $sql = "UPDATE brand SET BrandName = ?, Status = ? WHERE BrandID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $brandName, $status, $id);

        if ($stmt->execute()) {
            $stmt->close();
            flash('success', "Brand '{$brandName}' was updated successfully!");
            redirect('brand/index.php');
        } else {
            if ($stmt->errno == 1062) {
                $message = "The brand name '{$brandName}' already exists.";
            } else {
                $message = "Error updating brand: " . $stmt->error;
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
                <li class="breadcrumb-item active" aria-current="page">Edit Brand</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-pencil-square text-primary me-2"></i>Edit Camera Brand #<?php echo $id; ?>
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
                        <input type="text" name="BrandName" id="BrandName" class="form-control" value="<?php echo sanitize($brandName); ?>" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="Status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="Status" id="Status" class="form-select" required>
                            <option value="Active" <?php echo ($status === 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($status === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Update Brand
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
