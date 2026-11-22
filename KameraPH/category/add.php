<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin login required

$pageTitle = 'Add Category';
$activeMenu = 'category';
$message = "";
$categoryName = "";
$description = "";
$parentCategoryID = "";
$status = "Active";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoryName = trim($_POST['CategoryName'] ?? '');
    $description = trim($_POST['Description'] ?? '');
    $parentCategoryID = trim($_POST['ParentCategoryID'] ?? '');
    $status = in_array($_POST['Status'] ?? '', ['Active', 'Inactive']) ? $_POST['Status'] : 'Active';

    if ($categoryName === "") {
        $message = "Category name is required.";
    } else {
        $parentVal = ($parentCategoryID !== "") ? (int)$parentCategoryID : null;
        $descVal = ($description !== "") ? $description : null;

        $sql = "INSERT INTO category (ParentCategoryID, CategoryName, Description, Status) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $parentVal, $categoryName, $descVal, $status);

        if ($stmt->execute()) {
            $stmt->close();
            flash('success', "Category '{$categoryName}' added successfully!");
            redirect('category/index.php');
        } else {
            $message = "Error adding category: " . $stmt->error;
            $stmt->close();
        }
    }
}

// Fetch categories for parent dropdown
$parentCategories = $conn->query("SELECT CategoryID, CategoryName FROM category WHERE ParentCategoryID IS NULL ORDER BY CategoryName ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Categories</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Category</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Add Equipment Category
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
                        <label for="CategoryName" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="CategoryName" id="CategoryName" class="form-control" placeholder="e.g. Mirrorless Cameras, Prime Lenses, Drones" value="<?php echo sanitize($categoryName); ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="ParentCategoryID" class="form-label">Parent Category</label>
                        <select name="ParentCategoryID" id="ParentCategoryID" class="form-select">
                            <option value="">None (Top-Level Category)</option>
                            <?php if ($parentCategories): ?>
                                <?php while ($cat = $parentCategories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['CategoryID']; ?>" <?php echo ($parentCategoryID == $cat['CategoryID']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($cat['CategoryName']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Leave blank if this is a top-level category.</div>
                    </div>

                    <div class="mb-3">
                        <label for="Description" class="form-label">Description</label>
                        <textarea name="Description" id="Description" class="form-control" rows="3" placeholder="Brief description of this equipment category..."><?php echo sanitize($description); ?></textarea>
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
                            <i class="bi bi-check-lg me-1"></i> Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
