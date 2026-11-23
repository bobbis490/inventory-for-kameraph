<?php
// Ensure database and helpers are loaded
require_once __DIR__ . '/../config/database.php';

$pageTitle = isset($pageTitle) ? $pageTitle . ' - KameraPH' : 'KameraPH - Camera Gear Marketplace';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($pageTitle); ?></title>
    <!-- Anti-FOUC: apply saved theme before CSS renders -->
    <script>(function(){var t=localStorage.getItem('kameraph-theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();</script>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- KameraPH Custom CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css') . '?v=' . @filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
    <!-- KameraPH Theme (Night/Light Mode) -->
    <link rel="stylesheet" href="<?php echo asset_url('css/theme.css') . '?v=' . @filemtime(__DIR__ . '/../assets/css/theme.css'); ?>">
</head>
<body>

<?php 
if (!empty($publicPage)) {
    require_once __DIR__ . '/home_nav.php';
} else {
    require_once __DIR__ . '/navbar.php';
}
?>

<main class="container py-4">
    <?php echo render_flash(); ?>
