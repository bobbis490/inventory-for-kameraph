<?php
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$active = $activeMenu ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-kamera sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo base_url(); ?>">
            <i class="bi bi-camera me-2 fs-4 text-white"></i>
            <span class="text-white fw-bold">KameraPH</span>
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
