<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Add Product Review';
$activeMenu = 'product_review';

$message = "";
$orderItemID = "";
$modelID = isset($_GET['model_id']) ? (int)$_GET['model_id'] : 0;
$reviewerUserID = "1";
$ratingValue = 5;
$reviewText = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $orderItemID = (int)($_POST['OrderItemID'] ?? 0);
    $modelID = (int)($_POST['ModelID'] ?? 0);
    $reviewerUserID = (int)($_POST['ReviewerUserID'] ?? 0);
    $ratingValue = (int)($_POST['RatingValue'] ?? 5);
    $reviewText = trim($_POST['ReviewText'] ?? '');

    if ($orderItemID <= 0) {
        $message = "Please enter a valid Order Item ID.";
    } elseif ($modelID <= 0) {
        $message = "Please select a Camera Model.";
    } elseif ($reviewerUserID <= 0) {
        $message = "Please specify a valid Reviewer User ID.";
    } elseif ($ratingValue < 1 || $ratingValue > 5) {
        $message = "Rating must be between 1 and 5 stars.";
    } elseif ($reviewText === "") {
        $message = "Review feedback cannot be empty.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO product_review (OrderItemID, ModelID, ReviewerUserID, RatingValue, ReviewText, CreatedAt)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("iiiis", $orderItemID, $modelID, $reviewerUserID, $ratingValue, $reviewText);

        if ($stmt->execute()) {
            $stmt->close();
            flash('success', "Product review submitted successfully!");
            redirect("product_review/index.php?model_id={$modelID}");
        } else {
            $message = "Error submitting review: " . $stmt->error;
            $stmt->close();
        }
    }
}

$models = $conn->query("
    SELECT pm.ModelID, pm.ModelName, b.BrandName
    FROM product_model pm
    INNER JOIN brand b ON pm.BrandID = b.BrandID
    ORDER BY b.BrandName ASC, pm.ModelName ASC
");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Reviews</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Review</li>
            </ol>
        </nav>

        <div class="card card-kamera">
            <div class="card-header card-header-kamera">
                <h4 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-star text-warning me-2"></i>Submit Product Review
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
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="OrderItemID" class="form-label">Order Item ID <span class="text-danger">*</span></label>
                            <input type="number" name="OrderItemID" id="OrderItemID" class="form-control" min="1" placeholder="e.g. 1001" value="<?php echo sanitize($orderItemID); ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="ReviewerUserID" class="form-label">Reviewer User ID <span class="text-danger">*</span></label>
                            <input type="number" name="ReviewerUserID" id="ReviewerUserID" class="form-control" min="1" value="<?php echo sanitize($reviewerUserID); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="ModelID" class="form-label">Camera Model <span class="text-danger">*</span></label>
                        <select name="ModelID" id="ModelID" class="form-select" required>
                            <option value="">Select Camera Model</option>
                            <?php if ($models): ?>
                                <?php while ($m = $models->fetch_assoc()): ?>
                                    <option value="<?php echo $m['ModelID']; ?>" <?php echo ($modelID == $m['ModelID']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($m['BrandName'] . ' - ' . $m['ModelName']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="RatingValue" class="form-label">Rating <span class="text-danger">*</span></label>
                        <select name="RatingValue" id="RatingValue" class="form-select" required>
                            <option value="5" <?php echo ($ratingValue == 5) ? 'selected' : ''; ?>>★★★★★ (5 Stars - Excellent)</option>
                            <option value="4" <?php echo ($ratingValue == 4) ? 'selected' : ''; ?>>★★★★☆ (4 Stars - Very Good)</option>
                            <option value="3" <?php echo ($ratingValue == 3) ? 'selected' : ''; ?>>★★★☆☆ (3 Stars - Good / Average)</option>
                            <option value="2" <?php echo ($ratingValue == 2) ? 'selected' : ''; ?>>★★☆☆☆ (2 Stars - Fair)</option>
                            <option value="1" <?php echo ($ratingValue == 1) ? 'selected' : ''; ?>>★☆☆☆☆ (1 Star - Poor)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="ReviewText" class="form-label">Review Comments <span class="text-danger">*</span></label>
                        <textarea name="ReviewText" id="ReviewText" class="form-control" rows="4" placeholder="Share your experience with autofocus speed, low-light ISO, build quality, ergonomics..." required><?php echo sanitize($reviewText); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
