<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin login required

$pageTitle = 'Specification Definitions';
$activeMenu = 'spec_definition';

$search = trim($_GET['search'] ?? '');
$categoryFilter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$where = [];
$params = [];
$types = "";

if ($search !== '') {
    $where[] = "sd.SpecName LIKE ?";
    $params[] = "%{$search}%";
    $types .= "s";
}
if ($categoryFilter > 0) {
    $where[] = "sd.CategoryID = ?";
    $params[] = $categoryFilter;
    $types .= "i";
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT sd.*, c.CategoryName
        FROM spec_definition sd
        INNER JOIN category c ON sd.CategoryID = c.CategoryID
        {$whereSql}
        ORDER BY c.CategoryName ASC, sd.SpecName ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$categories = $conn->query("SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold mb-0">
            <i class="bi bi-sliders text-secondary me-2"></i>Specification Schema Definitions
        </h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="add.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Specification Field
        </a>
    </div>
</div>

<div class="card card-kamera">
    <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold text-dark">Technical Attributes Catalog</span>
        <form method="GET" class="d-flex gap-2" style="max-width: 420px;">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search spec name..." value="<?php echo sanitize($search); ?>">
            <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php if ($categories): ?>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $c['CategoryID']; ?>" <?php echo ($categoryFilter == $c['CategoryID']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($c['CategoryName']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <?php if ($search !== '' || $categoryFilter > 0): ?>
                <a href="index.php" class="btn btn-sm btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-kamera table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Category</th>
                    <th>Specification Attribute</th>
                    <th>Data Type</th>
                    <th>Default Unit</th>
                    <th style="width: 110px;" class="text-center">Required</th>
                    <th style="width: 110px;" class="text-center">Filterable</th>
                    <th style="width: 150px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-semibold">#<?php echo $row['SpecDefID']; ?></span></td>
                            <td><span class="badge bg-light text-dark border"><?php echo sanitize($row['CategoryName']); ?></span></td>
                            <td><strong class="text-dark"><?php echo sanitize($row['SpecName']); ?></strong></td>
                            <td><span class="badge bg-light text-secondary border"><?php echo sanitize($row['DataType']); ?></span></td>
                            <td><?php echo sanitize($row['Unit'] ?? '—'); ?></td>
                            <td class="text-center">
                                <?php if ($row['IsRequired']): ?>
                                    <span class="badge bg-danger">Required</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Optional</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['IsFilterable']): ?>
                                    <span class="badge bg-info text-dark">Filterable</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">No</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $row['SpecDefID']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <form method="POST" action="delete.php" style="display:inline;"
                                          onsubmit="return confirm('Delete specification definition \'<?php echo addslashes(sanitize($row['SpecName'])); ?>\'?');">
                                        <input type="hidden" name="SpecDefID" value="<?php echo $row['SpecDefID']; ?>">
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
                                <i class="bi bi-sliders empty-state-icon"></i>
                                <h5>No Specifications Defined</h5>
                                <p class="text-muted">Define specifications like Resolution, Sensor Size, Mount, etc.</p>
                                <a href="add.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add Specification Field
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
