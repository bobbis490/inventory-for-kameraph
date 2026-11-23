<?php
$currentUser = current_user();
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-kamera sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo base_url(); ?>">
            <span class="logo-badge d-inline-flex align-items-center justify-content-center bg-white rounded-2 shadow-sm flex-shrink-0" style="width:38px;height:38px;padding:3px;">
                <img src="<?php echo base_url('assets/logo.php'); ?>" alt="KameraPH Logo" style="height:30px;width:auto;object-fit:contain;">
            </span>
            <span class="text-white fw-bold fs-5">KameraPH</span>
            <span class="badge bg-warning text-dark ms-1 small d-none d-sm-inline-block" style="font-size:0.72rem;letter-spacing:0.5px;">MARKETPLACE</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <!-- Theme Toggle -->
            <button id="themeToggleBtn" class="btn btn-sm btn-outline-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" type="button" title="Toggle Light/Night Mode" style="width:36px;height:36px;">
                <i class="bi bi-sun-fill" id="themeIcon"></i>
            </button>

            <a href="<?php echo base_url(); ?>" class="btn btn-sm btn-outline-light d-none d-md-inline-flex align-items-center">
                <i class="bi bi-shop me-1"></i> Browse Gear
            </a>

            <?php if (!empty($currentUser['is_admin'])): ?>
                <a href="<?php echo base_url('dashboard.php'); ?>" class="btn btn-sm btn-outline-light d-inline-flex align-items-center">
                    <i class="bi bi-speedometer2 me-1"></i> Admin Dashboard
                </a>
            <?php endif; ?>

            <a href="<?php echo base_url('product_listing/add.php'); ?>" class="btn btn-sm btn-warning fw-semibold d-inline-flex align-items-center">
                <i class="bi bi-plus-circle me-1"></i> Sell Gear
            </a>

            <?php if ($currentUser): ?>
                <div class="dropdown ms-1">
                    <button class="btn btn-sm btn-outline-light dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>
                        <span><?php echo sanitize($currentUser['FirstName']); ?></span>
                        <span class="badge bg-warning text-dark ms-1 small" style="font-size:0.7rem;"><?php echo ucfirst(sanitize($currentUser['Role'])); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><h6 class="dropdown-header"><?php echo sanitize($currentUser['Email']); ?></h6></li>
                        <li><a class="dropdown-item" href="<?php echo base_url('profile.php'); ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <?php if (!empty($currentUser['is_admin'])): ?>
                            <li><a class="dropdown-item" href="<?php echo base_url('dashboard.php'); ?>"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="<?php echo base_url('product_listing/index.php'); ?>"><i class="bi bi-box-seam me-2"></i>My Listings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo base_url('logout.php'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?php echo base_url('login.php'); ?>" class="btn btn-sm btn-outline-warning fw-semibold ms-1">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </a>
                <a href="<?php echo base_url('register.php'); ?>" class="btn btn-sm btn-outline-light ms-1 d-none d-sm-inline-flex">
                    <i class="bi bi-person-plus me-1"></i> Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
