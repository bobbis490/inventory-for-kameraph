<?php
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$customName = trim($_GET['name'] ?? '');

$switched = switch_user_account($id, $customName);

flash('info', "Switched active account to: <strong>{$switched['name']}</strong> (User #{$switched['id']}) &bull; {$switched['role']}");

// Redirect back to referring page or homepage
$redirect = $_SERVER['HTTP_REFERER'] ?? base_url();

// Avoid loop if referer was switch_account itself
if (strpos($redirect, 'switch_account.php') !== false) {
    $redirect = base_url();
}

header("Location: " . $redirect);
exit();
