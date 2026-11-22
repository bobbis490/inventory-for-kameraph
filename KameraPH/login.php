<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'System Login';
$activeMenu = 'login';
$publicPage = true;

// If already logged in, redirect
if (is_logged_in()) {
    $redirect = $_GET['redirect'] ?? (is_admin() ? 'dashboard.php' : 'index.php');
    redirect($redirect);
}

$errorMessage = "";
$emailInput = "";
$authToken = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $emailInput = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';
    $redirectTarget = trim($_POST['redirect'] ?? '');

    if ($emailInput === '' || $passwordInput === '') {
        $errorMessage = "Please enter both your email address and password.";
    } else {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM user WHERE Email = :email OR PhoneNumber = :phone LIMIT 1");
            $stmt->execute([':email' => $emailInput, ':phone' => $emailInput]);
            $user = $stmt->fetch();

            if ($user && password_verify($passwordInput, $user['PasswordHash'])) {
                if ($user['Status'] === 'Suspended') {
                    $reason = !empty($user['SuspensionReason']) ? ": " . $user['SuspensionReason'] : ".";
                    $errorMessage = "Your account has been suspended" . $reason;
                } else {
                    // Check if Admin
                    $adminStmt = $db->prepare("SELECT * FROM admin_user WHERE UserID = :uid AND Status = 'Active' LIMIT 1");
                    $adminStmt->execute([':uid' => $user['UserID']]);
                    $admin = $adminStmt->fetch();

                    // Check if Seller
                    $sellerStmt = $db->prepare("SELECT * FROM seller_profile WHERE UserID = :uid LIMIT 1");
                    $sellerStmt->execute([':uid' => $user['UserID']]);
                    $seller = $sellerStmt->fetch();

                    // Determine Role
                    $role = 'customer';
                    if ($admin) {
                        $role = 'admin';
                    } elseif ($seller) {
                        $role = 'seller';
                    }

                    // Save session
                    $_SESSION['user'] = [
                        'UserID'          => (int)$user['UserID'],
                        'Email'           => $user['Email'],
                        'FirstName'       => $user['FirstName'],
                        'LastName'        => $user['LastName'],
                        'FullName'        => trim($user['FirstName'] . ' ' . $user['LastName']),
                        'PhoneNumber'     => $user['PhoneNumber'] ?? '',
                        'Role'            => $role,
                        'is_admin'        => ($admin !== false),
                        'AdminID'         => $admin ? (int)$admin['AdminID'] : null,
                        'AdminRole'       => $admin ? $admin['AdminRole'] : null,
                        'PermissionLevel' => $admin ? $admin['PermissionLevel'] : null,
                        'SellerID'        => $seller ? (int)$seller['SellerID'] : null,
                        'StoreName'       => $seller ? $seller['StoreName'] : null,
                    ];

                    // Generate cross-module verification session token
                    $rawToken = bin2hex(random_bytes(32));
                    try {
                        $tokStmt = $db->prepare(
                            "INSERT INTO verification_token (UserID, Purpose, TokenHash, ExpiresAt)
                             VALUES (:uid, 'session', SHA2(:token, 256), DATE_ADD(NOW(), INTERVAL 7 DAY))"
                        );
                        $tokStmt->execute([':uid' => $user['UserID'], ':token' => $rawToken]);
                        $_SESSION['auth_token'] = $rawToken;
                        $authToken = $rawToken;
                    } catch (Exception $e) {
                        // Non-critical token failure
                    }

                    // Update last login timestamp
                    try {
                        $db->prepare("UPDATE user SET LastLoginAt = NOW() WHERE UserID = :uid")
                           ->execute([':uid' => $user['UserID']]);
                    } catch (Exception $e) {
                        // ignore
                    }

                    flash('success', "Welcome back, <strong>" . sanitize($user['FirstName']) . "</strong>! Logged in as " . ucfirst($role) . ".");

                    $dest = $redirectTarget !== '' ? $redirectTarget : ($admin ? 'dashboard.php' : 'index.php');
                    // Store token in client localStorage before redirecting
                    echo "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body>
                    <script>
                        try {
                            localStorage.setItem('kph-auth-token', " . json_encode($rawToken) . ");
                        } catch(e) {}
                        window.location.href = " . json_encode(base_url($dest)) . ";
                    </script>
                    <p>Redirecting to <a href='" . sanitize(base_url($dest)) . "'>KameraPH</a>...</p>
                    </body></html>";
                    exit();
                }
            } else {
                $errorMessage = "Invalid email address or password. Please try again.";
            }
        } catch (Exception $e) {
            $errorMessage = "Authentication error: " . $e->getMessage();
        }
    }
}

$redirectParam = $_GET['redirect'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-7 col-lg-5">
        <div class="card card-kamera shadow-lg border-0">
            <div class="card-header card-header-kamera text-center py-4 bg-dark text-white border-bottom border-warning">
                <div class="d-inline-flex p-3 rounded-circle bg-warning text-dark mb-2 shadow-sm">
                    <i class="bi bi-shield-lock-fill fs-2"></i>
                </div>
                <h3 class="h4 fw-bold mb-1 text-white">System Account Login</h3>
                <p class="text-white-50 small mb-0">Sign in to manage listings, verifications &amp; marketplace operations</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?php echo sanitize($errorMessage); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <input type="hidden" name="redirect" value="<?php echo sanitize($redirectParam); ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold text-dark">
                            <i class="bi bi-envelope me-1 text-primary"></i> Email Address
                        </label>
                        <input type="email" name="email" id="email" class="form-control form-control-lg" placeholder="name@kameraph.ph" value="<?php echo sanitize($emailInput); ?>" required autofocus>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label fw-semibold text-dark mb-0">
                                <i class="bi bi-key me-1 text-primary"></i> Password
                            </label>
                        </div>
                        <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold shadow-sm mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to Account
                    </button>
                </form>

                <!-- 1-Click Demo Accounts for Testing & Defense -->
                <div class="mt-4 pt-3 border-top">
                    <span class="d-block small text-muted text-uppercase fw-bold mb-2 text-center">
                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Demo Logins (Click to autofill)
                    </span>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary text-start d-flex justify-content-between align-items-center" onclick="fillLogin('admin@kameraph.ph', 'Admin123!')">
                            <div>
                                <i class="bi bi-shield-shaded me-1"></i> <strong>Logistics Admin</strong>
                                <span class="d-block small text-muted">admin@kameraph.ph</span>
                            </div>
                            <span class="badge bg-primary text-white">Full Admin</span>
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-info text-start d-flex justify-content-between align-items-center" onclick="fillLogin('patricia.ops@kameraph.ph', 'Admin123!')">
                            <div>
                                <i class="bi bi-gear-wide-connected me-1"></i> <strong>Operations Supervisor</strong>
                                <span class="d-block small text-muted">patricia.ops@kameraph.ph</span>
                            </div>
                            <span class="badge bg-info text-dark">Staff Ops</span>
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-success text-start d-flex justify-content-between align-items-center" onclick="fillLogin('customer@kameraph.ph', 'Customer123!')">
                            <div>
                                <i class="bi bi-person-check me-1"></i> <strong>Juan Dela Cruz</strong>
                                <span class="d-block small text-muted">customer@kameraph.ph</span>
                            </div>
                            <span class="badge bg-success text-white">Buyer / Customer</span>
                        </button>
                    </div>
                </div>

                <div class="text-center mt-4 pt-3 border-top">
                    <span class="text-muted small">Don't have an account yet?</span>
                    <a href="<?php echo base_url('register.php'); ?>" class="fw-bold small text-decoration-none ms-1">
                        Create an Account <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="text-center mt-3">
                    <a href="<?php echo base_url(); ?>" class="text-muted small text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Return to Marketplace
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillLogin(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
