<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Camera Model Catalog';
$activeMenu = 'product_model';

$search = trim($_GET['search'] ?? '');
$filterBrand = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$filterCategory = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$where = [];
$params = [];
$types = "";

if ($search !== '') {
    $where[] = "pm.ModelName LIKE ?";
    $params[] = "%{$search}%";
    $types .= "s";
}
if ($filterBrand > 0) {
    $where[] = "pm.BrandID = ?";
    $params[] = $filterBrand;
    $types .= "i";
}
if ($filterCategory > 0) {
    $where[] = "pm.CategoryID = ?";
    $params[] = $filterCategory;
    $types .= "i";
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT pm.*, b.BrandName, c.CategoryName
        FROM product_model pm
        INNER JOIN brand b ON pm.BrandID = b.BrandID
        INNER JOIN category c ON pm.CategoryID = c.CategoryID
        {$whereSql}
        ORDER BY pm.ModelID DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Fetch brands & categories for filter dropdowns
$brandsList = $conn->query("SELECT BrandID, BrandName FROM brand ORDER BY BrandName ASC");
$categoriesList = $conn->query("SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Camera Models</li>
            </ol>
        </nav>
        <h2 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-camera2 text-info me-2"></i>Camera Model Catalog
        </h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="add.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Camera Model
        </a>
    </div>
</div>

<div class="card card-kamera">
    <div class="card-header card-header-kamera">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search model name..." value="<?php echo sanitize($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="brand_id" class="form-select form-select-sm">
                    <option value="">All Brands</option>
                    <?php if ($brandsList): ?>
                        <?php while ($b = $brandsList->fetch_assoc()): ?>
                            <option value="<?php echo $b['BrandID']; ?>" <?php echo ($filterBrand == $b['BrandID']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($b['BrandName']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php if ($categoriesList): ?>
                        <?php while ($c = $categoriesList->fetch_assoc()): ?>
                            <option value="<?php echo $c['CategoryID']; ?>" <?php echo ($filterCategory == $c['CategoryID']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($c['CategoryName']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-filter me-1"></i>Filter
                </button>
                <?php if ($search !== '' || $filterBrand > 0 || $filterCategory > 0): ?>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-kamera table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 90px;">Model ID</th>
                    <th>Model Name</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th style="width: 130px;">Release Year</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 160px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-semibold">#<?php echo $row['ModelID']; ?></span></td>
                            <td><strong class="text-dark"><?php echo sanitize($row['ModelName']); ?></strong></td>
                            <td><span class="badge bg-light text-primary border"><?php echo sanitize($row['BrandName']); ?></span></td>
                            <td><span class="badge bg-light text-secondary border"><?php echo sanitize($row['CategoryName']); ?></span></td>
                            <td><?php echo sanitize($row['ReleaseYear'] ?? '—'); ?></td>
                            <td><?php echo status_badge($row['Status']); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $row['ModelID']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <form action="delete.php" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete camera model \'<?php echo addslashes(sanitize($row['ModelName'])); ?>\'?');">
                                        <input type="hidden" name="ModelID" value="<?php echo $row['ModelID']; ?>">
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
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-camera2 empty-state-icon"></i>
                                <h5>No Camera Models Found</h5>
                                <p class="text-muted">No models match your current filters. Add new models to catalog.</p>
                                <a href="add.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add Camera Model
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
