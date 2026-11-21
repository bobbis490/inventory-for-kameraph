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
                    <a class="nav-link <?php echo ($active === 'dashboard') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>">
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
                                <i class="bi bi-camera2 me-2 text-info"></i> Camera Models
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

            <div class="d-flex align-items-center">
                <a href="<?php echo base_url('product_listing/add.php'); ?>" class="btn-nav-new-listing text-decoration-none">
                    <i class="bi bi-plus-circle me-2"></i> New Listing
                </a>
            </div>
        </div>
    </div>
</nav>
