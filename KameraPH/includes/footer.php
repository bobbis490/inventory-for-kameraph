</main>

<footer class="footer-orange mt-auto">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="d-flex align-items-center mb-2 mb-md-0">
                <i class="bi bi-camera me-2 fs-5"></i>
                <span class="fw-bold me-1">KameraPH</span>
                <span>&copy; <?php echo date('Y'); ?> Camera Equipment Platform</span>
            </div>
            <div class="d-flex flex-wrap align-items-center">
                <a href="<?php echo base_url('brand/index.php'); ?>">Brands</a>
                <a href="<?php echo base_url('product_model/index.php'); ?>">Models</a>
                <a href="<?php echo base_url('product_listing/index.php'); ?>">Listings</a>
                <a href="<?php echo base_url('warehouse/index.php'); ?>">Warehouse</a>
                <a href="<?php echo base_url('product_verification/index.php'); ?>">Verifications</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<!-- KameraPH For-You Engagement Bridge (Module 4) -->
<script src="<?php echo asset_url('js/engagement.js') . '?v=' . @filemtime(__DIR__ . '/../assets/js/engagement.js'); ?>"></script>
<!-- KameraPH Theme Switcher -->
<script src="<?php echo asset_url('js/theme.js') . '?v=' . @filemtime(__DIR__ . '/../assets/js/theme.js'); ?>"></script>
<!-- KameraPH Animation Interactions -->
<script src="<?php echo asset_url('js/animations.js') . '?v=' . @filemtime(__DIR__ . '/../assets/js/animations.js'); ?>"></script>

<!-- Global Form Confirmation & Image Preview Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus first input on modal/form if present
    const firstInput = document.querySelector('form input:not([type=hidden]):not([readonly]):not([disabled])');
    if (firstInput && !window.location.hash) {
        firstInput.focus();
    }
});
</script>

</body>
</html>
<?php
// Flush output buffer cleanly
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>
