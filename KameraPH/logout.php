<?php
require_once __DIR__ . '/config/database.php';

// Unset user session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['user']);
unset($_SESSION['auth_token']);
session_destroy();

// Start new session just to flash logout message
session_start();
flash('info', 'You have been logged out successfully.');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body>
<script>
    try {
        localStorage.removeItem('kph-auth-token');
    } catch(e) {}
    window.location.href = " . json_encode(base_url('login.php')) . ";
</script>
<p>Logging out... <a href='" . sanitize(base_url('login.php')) . "'>Click here if not redirected</a>.</p>
</body></html>";
exit();
