<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Brand Management';
$activeMenu = 'brand';

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM brand WHERE BrandName LIKE ? ORDER BY BrandName ASC");
    $searchTerm = "%{$search}%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM brand ORDER BY BrandID DESC");
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold mb-0">
            <i class="bi bi-tag text-primary me-2"></i>Brand Management
        </h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="add.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Brand
        </a>
    </div>
</div>

<div class="card card-kamera">
    <div class="card-header card-header-kamera d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold text-dark">All Camera Brands</span>
        <form method="GET" class="d-flex" style="max-width: 300px;">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Search brands..." value="<?php echo sanitize($search); ?>">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
                <?php if ($search !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary" title="Clear">
                        <i class="bi bi-x"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-kamera table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 100px;">Brand ID</th>
                    <th>Brand Name</th>
                    <th style="width: 140px;">Status</th>
                    <th style="width: 160px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-semibold">#<?php echo $row['BrandID']; ?></span></td>
                            <td><strong class="text-dark"><?php echo sanitize($row['BrandName']); ?></strong></td>
                            <td><?php echo status_badge($row['Status']); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $row['BrandID']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <form method="POST" action="delete.php" style="display:inline;"
                                          onsubmit="return confirm('Are you sure you want to delete brand \'<?php echo addslashes(sanitize($row['BrandName'])); ?>\'?');">
                                        <input type="hidden" name="BrandID" value="<?php echo $row['BrandID']; ?>">
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
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="bi bi-tag empty-state-icon"></i>
                                <h5>No Brands Found</h5>
                                <p class="text-muted"><?php echo ($search !== '') ? 'No brands matched your search criteria.' : 'Get started by creating your first camera brand.'; ?></p>
                                <a href="add.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add Brand
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
