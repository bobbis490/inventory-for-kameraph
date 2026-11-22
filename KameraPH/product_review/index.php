<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin login required

$pageTitle = 'Product Reviews';
$activeMenu = 'product_review';

$ratingFilter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$modelFilter = isset($_GET['model_id']) ? (int)$_GET['model_id'] : 0;

$where = [];
if ($ratingFilter > 0) {
    $where[] = "pr.RatingValue = {$ratingFilter}";
}
if ($modelFilter > 0) {
    $where[] = "pr.ModelID = {$modelFilter}";
}
$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT pr.*, pm.ModelName, b.BrandName
        FROM product_review pr
        INNER JOIN product_model pm ON pr.ModelID = pm.ModelID
        INNER JOIN brand b ON pm.BrandID = b.BrandID
        {$whereSql}
        ORDER BY pr.ReviewID DESC";

$result = $conn->query($sql);
$models = $conn->query("SELECT ModelID, ModelName FROM product_model ORDER BY ModelName ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold mb-0">
            <i class="bi bi-star text-warning me-2"></i>Product Reviews & Ratings
        </h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="add.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Review
        </a>
    </div>
</div>

<div class="card card-kamera">
    <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold text-dark">Customer Feedback</span>
        <form method="GET" class="d-flex gap-2" style="max-width: 450px;">
            <select name="rating" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Star Ratings</option>
                <?php for ($r = 5; $r >= 1; $r--): ?>
                    <option value="<?php echo $r; ?>" <?php echo ($ratingFilter == $r) ? 'selected' : ''; ?>>
                        <?php echo $r; ?> Stars (<?php echo str_repeat('★', $r); ?>)
                    </option>
                <?php endfor; ?>
            </select>
            <select name="model_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Camera Models</option>
                <?php if ($models): ?>
                    <?php while ($m = $models->fetch_assoc()): ?>
                        <option value="<?php echo $m['ModelID']; ?>" <?php echo ($modelFilter == $m['ModelID']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($m['ModelName']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <?php if ($ratingFilter > 0 || $modelFilter > 0): ?>
                <a href="index.php" class="btn btn-sm btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-kamera table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Camera Model</th>
                    <th style="width: 140px;">Rating</th>
                    <th>Review Feedback</th>
                    <th>Reviewer</th>
                    <th>Order Item</th>
                    <th>Date</th>
                    <th style="width: 150px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-semibold">#<?php echo $row['ReviewID']; ?></span></td>
                            <td>
                                <strong class="text-dark"><?php echo sanitize($row['BrandName'] . ' ' . $row['ModelName']); ?></strong>
                            </td>
                            <td>
                                <?php echo render_stars($row['RatingValue']); ?>
                                <small class="text-muted ms-1">(<?php echo $row['RatingValue']; ?>/5)</small>
                            </td>
                            <td>
                                <p class="mb-0 text-secondary text-truncate" style="max-width: 320px;" title="<?php echo sanitize($row['ReviewText']); ?>">
                                    <?php echo sanitize($row['ReviewText']); ?>
                                </p>
                            </td>
                            <td><span class="badge bg-light text-dark border">User #<?php echo $row['ReviewerUserID']; ?></span></td>
                            <td><span class="text-muted small">Item #<?php echo $row['OrderItemID']; ?></span></td>
                            <td><small class="text-muted"><?php echo date('M d, Y', strtotime($row['CreatedAt'])); ?></small></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $row['ReviewID']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <form action="delete.php" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete review #<?php echo $row['ReviewID']; ?>?');">
                                        <input type="hidden" name="id" value="<?php echo $row['ReviewID']; ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="bi bi-star empty-state-icon"></i>
                                <h5>No Reviews Found</h5>
                                <p class="text-muted">No reviews recorded yet for this selection.</p>
                                <a href="add.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add Review
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
