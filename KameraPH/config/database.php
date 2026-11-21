<?php
// Start session and output buffering to prevent headers-already-sent errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!ob_get_level()) {
    ob_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$username = "root";
$password = "";
$database = "inventory";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:20px;margin:20px;border:1px solid #ef4444;background:#fee2e2;color:#991b1b;border-radius:8px;'>
        <h3>Database Connection Error</h3>
        <p>Could not connect to database '<strong>" . htmlspecialchars($database) . "</strong>' on <strong>" . htmlspecialchars($host) . "</strong>.</p>
        <p><small>MySQL error: " . htmlspecialchars($conn->connect_error) . "</small></p>
        <p>Please make sure MySQL is running in your XAMPP Control Panel.</p>
    </div>");
}

$conn->set_charset("utf8mb4");

// Load core helper functions
require_once __DIR__ . '/../includes/functions.php';
