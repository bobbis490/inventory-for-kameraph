<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Add Specification Definition';
$activeMenu = 'spec_definition';

$message = "";
$categoryID = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$specName = "";
$dataType = "Text";
$unit = "";
$isRequired = 0;
$isFilterable = 1;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoryID = (int)($_POST['CategoryID'] ?? 0);
    $specName = trim($_POST['SpecName'] ?? '');
    $dataType = $_POST['DataType'] ?? 'Text';
    $unitInput = trim($_POST['Unit'] ?? '');
    $unit = ($unitInput !== "") ? $unitInput : null;

    $isRequired = isset($_POST['IsRequired']) ? 1 : 0;
    $isFilterable = isset($_POST['IsFilterable']) ? 1 : 0;

    if ($categoryID <= 0) {
        $message = "Please select an equipment category.";
    } elseif ($specName === "") {
        $message = "Specification attribute name is required.";
    } else {
        $sql = "INSERT INTO spec_definition (CategoryID, SpecName, DataType, Unit, IsRequired, IsFilterable)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssii", $categoryID, $specName, $dataType, $unit, $isRequired, $isFilterable);

        if ($stmt->execute()) {
            $stmt->close();
            flash('success', "Specification field '{$specName}' added successfully!");
            redirect("spec_definition/index.php?category_id={$categoryID}");
        } else {
            $message = "Error saving specification: " . $stmt->error;
            $stmt->close();
        }
    }
}

$categories = $conn->query("SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName ASC");
$dataTypes = ['Text', 'Number', 'Decimal', 'Boolean'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Spec Definitions</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Field</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-sliders text-secondary me-2"></i>Add Specification Definition
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

                    <div class="mb-3">
                        <label for="SpecName" class="form-label">Specification Name <span class="text-danger">*</span></label>
                        <input type="text" name="SpecName" id="SpecName" class="form-control" placeholder="e.g. Effective Megapixels, Lens Mount, Max ISO" value="<?php echo sanitize($specName); ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="DataType" class="form-label">Data Type <span class="text-danger">*</span></label>
                            <select name="DataType" id="DataType" class="form-select" required>
                                <?php foreach ($dataTypes as $dt): ?>
                                    <option value="<?php echo $dt; ?>" <?php echo ($dataType === $dt) ? 'selected' : ''; ?>>
                                        <?php echo $dt; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="Unit" class="form-label">Measurement Unit</label>
                            <input type="text" name="Unit" id="Unit" class="form-control" placeholder="e.g. MP, mm, fps, g, kg" value="<?php echo sanitize($unit ?? ''); ?>">
                            <div class="form-text">Optional unit symbol.</div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border mb-4">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="IsRequired" id="IsRequired" value="1" <?php echo ($isRequired) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="IsRequired">
                                Required Attribute (Must be filled by seller)
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="IsFilterable" id="IsFilterable" value="1" <?php echo ($isFilterable) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="IsFilterable">
                                Filterable (Available in marketplace sidebar filters)
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Definition
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
