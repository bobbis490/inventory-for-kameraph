<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Category Management';
$activeMenu = 'category';

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $conn->prepare("
        SELECT c.*, p.CategoryName AS ParentCategoryName
        FROM category c
        LEFT JOIN category p ON c.ParentCategoryID = p.CategoryID
        WHERE c.CategoryName LIKE ? OR c.Description LIKE ?
        ORDER BY c.CategoryID DESC
    ");
    $searchTerm = "%{$search}%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("
        SELECT c.*, p.CategoryName AS ParentCategoryName
        FROM category c
        LEFT JOIN category p ON c.ParentCategoryID = p.CategoryID
        ORDER BY c.CategoryID DESC
    ");
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold  mb-0">
            <i class="bi bi-folder text-success me-2"></i>Category Management
        </h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="add.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Category
        </a>
    </div>
</div>

<div class="card card-kamera">
    <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold text-dark">Gear Categories</span>
        <form method="GET" class="d-flex" style="max-width: 300px;">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Search categories..." value="<?php echo sanitize($search); ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                <?php if ($search !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary" title="Clear"><i class="bi bi-x"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-kamera table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 90px;">ID</th>
                    <th>Category Name</th>
                    <th>Parent Category</th>
                    <th>Description</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 160px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-semibold">#<?php echo $row['CategoryID']; ?></span></td>
                            <td><strong class="text-dark"><?php echo sanitize($row['CategoryName']); ?></strong></td>
                            <td>
                                <?php if (!empty($row['ParentCategoryName'])): ?>
                                    <span class="badge bg-light text-secondary border">
                                        <i class="bi bi-arrow-return-right me-1"></i><?php echo sanitize($row['ParentCategoryName']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Main Category</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                    <?php echo sanitize($row['Description'] ?? '—'); ?>
                                </span>
                            </td>
                            <td><?php echo status_badge($row['Status']); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $row['CategoryID']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <form action="delete.php" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Are you sure you want to delete category \'<?php echo addslashes(sanitize($row['CategoryName'])); ?>\'?');">
                                        <input type="hidden" name="CategoryID" value="<?php echo $row['CategoryID']; ?>">
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
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-folder empty-state-icon"></i>
                                <h5>No Categories Found</h5>
                                <p class="text-muted"><?php echo ($search !== '') ? 'No categories match your search.' : 'Organize your equipment catalog by adding categories.'; ?></p>
                                <a href="add.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add Category
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
