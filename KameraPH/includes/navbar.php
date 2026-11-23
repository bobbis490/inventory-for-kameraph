<?php
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$active = $activeMenu ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-kamera sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo base_url(); ?>">
            <span class="logo-badge d-inline-flex align-items-center justify-content-center bg-white rounded-2 shadow-sm flex-shrink-0" style="width:38px;height:38px;padding:3px;">
                <img src="<?php echo base_url('assets/logo.php'); ?>" alt="KameraPH Logo" style="height:30px;width:auto;object-fit:contain;">
            </span>
            <span class="text-white fw-bold fs-5">KameraPH</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#kameraNav" aria-controls="kameraNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="kameraNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active === 'home') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active === 'dashboard') ? 'active' : ''; ?>" href="<?php echo base_url('dashboard.php'); ?>">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>

                <!-- Catalog Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($active, ['brand', 'category', 'product_model', 'spec_definition']) ? 'active' : ''; ?>" href="#" id="catalogDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-grid me-1"></i> Catalog
                    </a>
                    <ul class="dropdown-menu shadow" aria-labelledby="catalogDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center <?php echo ($active === 'brand') ? 'active' : ''; ?>" href="<?php echo base_url('brand/index.php'); ?>">
                                <i class="bi bi-tag me-2 text-primary"></i> Brands
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center <?php echo ($active === 'category') ? 'active' : ''; ?>" href="<?php echo base_url('category/index.php'); ?>">
                                <i class="bi bi-folder me-2 text-success"></i> Categories
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center <?php echo ($active === 'product_model') ? 'active' : ''; ?>" href="<?php echo base_url('product_model/index.php'); ?>">
                                <i class="bi bi-camera2 me-2 text-info"></i> Equipment Models
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center <?php echo ($active === 'spec_definition') ? 'active' : ''; ?>" href="<?php echo base_url('spec_definition/index.php'); ?>">
                                <i class="bi bi-sliders me-2 text-secondary"></i> Spec Definitions
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Inventory & Listings Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($active, ['product_listing', 'listing_image', 'listing_specification']) ? 'active' : ''; ?>" href="#" id="inventoryDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-box-seam me-1"></i> Listings
                    </a>
                    <ul class="dropdown-menu shadow" aria-labelledby="inventoryDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center <?php echo ($active === 'product_listing') ? 'active' : ''; ?>" href="<?php echo base_url('product_listing/index.php'); ?>">
                                <i class="bi bi-shop me-2 text-primary"></i> Product Listings
                            </a>
                        </li>
                        <?php if (is_admin()):
                            // Live pending count badge
                            $pendingCount = 0;
                            $pq = $conn->query("SELECT COUNT(*) FROM product_listing WHERE Status = 'Pending Verification'");
                            if ($pq) $pendingCount = (int)$pq->fetch_row()[0];
                        ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between" href="<?php echo base_url('product_listing/review_queue.php'); ?>">
                                <span><i class="bi bi-clipboard2-check me-2 text-warning"></i> Review Queue</span>
                                <?php if ($pendingCount > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo $pendingCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center <?php echo ($active === 'listing_image') ? 'active' : ''; ?>" href="<?php echo base_url('listing_image/index.php'); ?>">
                                <i class="bi bi-images me-2 text-warning"></i> Listing Images
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center <?php echo ($active === 'listing_specification') ? 'active' : ''; ?>" href="<?php echo base_url('listing_specification/index.php'); ?>">
                                <i class="bi bi-cpu me-2 text-danger"></i> Listing Specs
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Warehouse Inventory Dropdown (Admin Only) -->
                <?php if (is_admin()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo ($active === 'warehouse') ? 'active' : ''; ?>" href="#" id="warehouseDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-buildings me-1"></i> Warehouse
                    </a>
                    <ul class="dropdown-menu shadow" aria-labelledby="warehouseDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="<?php echo base_url('warehouse/index.php'); ?>">
                                <i class="bi bi-speedometer2 me-2 text-primary"></i> Warehouse Hub
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="<?php echo base_url('warehouse/inventory.php'); ?>">
                                <i class="bi bi-grid-3x3-gap me-2 text-success"></i> Stock & Bins
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="<?php echo base_url('warehouse/movement.php'); ?>">
                                <i class="bi bi-arrow-down-up me-2 text-warning"></i> Movements & Logs
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="<?php echo base_url('warehouse/transfer.php'); ?>">
                                <i class="bi bi-arrow-left-right me-2 text-info"></i> Inter-Hub Transfer
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="<?php echo base_url('warehouse/audit.php'); ?>">
                                <i class="bi bi-clipboard-check me-2 text-secondary"></i> Cycle Audit
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="<?php echo base_url('warehouse/warehouses.php'); ?>">
                                <i class="bi bi-building me-2 text-dark"></i> Facilities Directory
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- Operations Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($active, ['product_verification', 'listing_reservation', 'product_review']) ? 'active' : ''; ?>" href="#" id="operationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear me-1"></i> Operations
                    </a>
                    <ul class="dropdown-menu shadow" aria-labelledby="operationsDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center <?php echo ($active === 'product_verification') ? 'active' : ''; ?>" href="<?php echo base_url('product_verification/index.php'); ?>">
                                <i class="bi bi-shield-check me-2 text-success"></i> Verifications
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center <?php echo ($active === 'listing_reservation') ? 'active' : ''; ?>" href="<?php echo base_url('listing_reservation/index.php'); ?>">
                                <i class="bi bi-clock-history me-2 text-info"></i> Reservations
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center <?php echo ($active === 'product_review') ? 'active' : ''; ?>" href="<?php echo base_url('product_review/index.php'); ?>">
                                <i class="bi bi-star me-2 text-warning"></i> Reviews
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <!-- Theme Toggle -->
                <button id="themeToggleBtn" class="btn btn-sm btn-outline-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" type="button" title="Toggle Light/Night Mode" style="width:36px;height:36px;">
                    <i class="bi bi-sun-fill" id="themeIcon"></i>
                </button>

                <a href="<?php echo base_url('product_listing/add.php'); ?>" class="btn-nav-new-listing text-decoration-none">
                    <i class="bi bi-plus-circle me-1"></i> New Listing
                </a>

                <?php 
                $currentUser = current_user();
                if ($currentUser): 
                ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <span><?php echo sanitize($currentUser['FirstName']); ?></span>
                            <?php if (!empty($currentUser['AdminRole'])): ?>
                                <span class="badge bg-warning text-dark ms-1 small" style="font-size:0.7rem;"><?php echo sanitize($currentUser['AdminRole']); ?></span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><h6 class="dropdown-header"><?php echo sanitize($currentUser['Email']); ?></h6></li>
                            <li><span class="dropdown-item-text small text-muted">Role: <strong><?php echo ucfirst(sanitize($currentUser['Role'])); ?></strong></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo base_url('profile.php'); ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo base_url(); ?>"><i class="bi bi-shop me-2"></i>Marketplace Home</a></li>
                            <li><a class="dropdown-item" href="<?php echo base_url('dashboard.php'); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo base_url('logout.php'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo base_url('login.php'); ?>" class="btn btn-sm btn-outline-warning fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
