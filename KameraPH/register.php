<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Create an Account';
$activeMenu = 'register';
$publicPage = true;

// If already logged in, redirect
if (is_logged_in()) {
    redirect(is_admin() ? 'dashboard.php' : 'index.php');
}

$errorMessage = "";
$successMessage = "";

// Form inputs
$firstName = "";
$lastName = "";
$email = "";
$phone = "";
$accountType = "customer"; // customer or seller
$storeName = "";
$sellerType = "Individual";
$storeDescription = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $accountType = $_POST['account_type'] ?? 'customer';
    $storeName = trim($_POST['store_name'] ?? '');
    $sellerType = $_POST['seller_type'] ?? 'Individual';
    $storeDescription = trim($_POST['store_description'] ?? '');

    // Validation
    if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $password === '') {
        $errorMessage = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please provide a valid email address.";
    } elseif (strlen($password) < 8) {
        $errorMessage = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirmPassword) {
        $errorMessage = "Passwords do not match. Please re-enter.";
    } elseif ($accountType === 'seller' && $storeName === '') {
        $errorMessage = "Store Name is required for Seller accounts.";
    } else {
        try {
            $db = Database::connect();

            // Check if email already exists
            $checkStmt = $db->prepare("SELECT UserID FROM user WHERE Email = :email LIMIT 1");
            $checkStmt->execute([':email' => $email]);
            if ($checkStmt->fetch()) {
                $errorMessage = "An account with the email '{$email}' already exists. Please log in instead.";
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                $db->beginTransaction();

                // Insert user
                $insUser = $db->prepare(
                    "INSERT INTO user (FirstName, LastName, Email, PhoneNumber, PasswordHash, DateJoined, Status)
                     VALUES (:fn, :ln, :email, :phone, :hash, NOW(), 'Active')"
                );
                $insUser->execute([
                    ':fn'    => $firstName,
                    ':ln'    => $lastName,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':hash'  => $passwordHash
                ]);

                $newUserId = (int)$db->lastInsertId();

                // If seller, insert seller_profile
                $newSellerId = null;
                if ($accountType === 'seller') {
                    $insSeller = $db->prepare(
                        "INSERT INTO seller_profile (UserID, SellerType, StoreName, StoreDescription, VerificationStatus)
                         VALUES (:uid, :stype, :sname, :sdesc, 'Pending')"
                    );
                    $insSeller->execute([
                        ':uid'   => $newUserId,
                        ':stype' => in_array($sellerType, ['Individual', 'Business']) ? $sellerType : 'Individual',
                        ':sname' => $storeName,
                        ':sdesc' => $storeDescription !== '' ? $storeDescription : null
                    ]);
                    $newSellerId = (int)$db->lastInsertId();
                }

                $db->commit();

                // Auto-login session
                $_SESSION['user'] = [
                    'UserID'          => $newUserId,
                    'Email'           => $email,
                    'FirstName'       => $firstName,
                    'LastName'        => $lastName,
                    'FullName'        => trim($firstName . ' ' . $lastName),
                    'PhoneNumber'     => $phone,
                    'Role'            => $accountType === 'seller' ? 'seller' : 'customer',
                    'is_admin'        => false,
                    'AdminID'         => null,
                    'AdminRole'       => null,
                    'PermissionLevel' => null,
                    'SellerID'        => $newSellerId,
                    'StoreName'       => $accountType === 'seller' ? $storeName : null,
                ];

                flash('success', "Registration successful! Welcome to KameraPH, <strong>" . sanitize($firstName) . "</strong>.");
                redirect('index.php');
            }
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $errorMessage = "Registration error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-9 col-lg-7">
        <div class="card card-kamera shadow-lg border-0">
            <div class="card-header card-header-kamera text-center py-4 bg-dark text-white border-bottom border-warning">
                <div class="d-inline-flex p-3 rounded-circle bg-warning text-dark mb-2 shadow-sm">
                    <i class="bi bi-person-plus-fill fs-2"></i>
                </div>
                <h3 class="h4 fw-bold mb-1 text-white">Create an Account</h3>
                <p class="text-white-50 small mb-0">Join KameraPH to reserve camera gear, buy equipment, or sell your inventory</p>
            </div>

            <div class="card-body p-4 p-md-5">
                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?php echo sanitize($errorMessage); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registerForm">
                    <!-- Personal Info -->
                    <h6 class="text-uppercase text-muted fw-bold mb-3 small">
                        <i class="bi bi-person-vcard text-primary me-1"></i> Personal Information
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="first_name" class="form-control" placeholder="e.g. Maria" value="<?php echo sanitize($firstName); ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="last_name" class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="last_name" class="form-control" placeholder="e.g. Santos" value="<?php echo sanitize($lastName); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="name@example.ph" value="<?php echo sanitize($email); ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="phone" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" id="phone" class="form-control" placeholder="09171234567" value="<?php echo sanitize($phone); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" minlength="8" placeholder="At least 8 characters" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="8" placeholder="Repeat password" required>
                        </div>
                    </div>

                    <!-- Account Type Selection -->
                    <h6 class="text-uppercase text-muted fw-bold mb-3 small pt-2 border-top">
                        <i class="bi bi-briefcase text-primary me-1"></i> Account Type
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="card h-100 border p-3 cursor-pointer account-card" onclick="selectType('customer')">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="account_type" id="type_customer" value="customer" <?php echo ($accountType === 'customer') ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="type_customer">
                                        <i class="bi bi-cart-check text-success me-1"></i> Buyer / Customer
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-2">Browse catalog, place reservation holds, and leave product reviews.</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card h-100 border p-3 cursor-pointer account-card" onclick="selectType('seller')">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="account_type" id="type_seller" value="seller" <?php echo ($accountType === 'seller') ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="type_seller">
                                        <i class="bi bi-shop text-primary me-1"></i> Camera Seller
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-2">List camera equipment, manage your store stock, and fulfill reservations.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Seller-specific fields -->
                    <div id="sellerFields" style="<?php echo ($accountType === 'seller') ? '' : 'display: none;'; ?>" class="mb-4 p-3 bg-light rounded border">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shop me-2 text-primary"></i>Store Information</h6>
                        <div class="mb-3">
                            <label for="store_name" class="form-label fw-semibold">Store / Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="store_name" id="store_name" class="form-control" placeholder="e.g. Manila Camera Vault" value="<?php echo sanitize($storeName); ?>">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="seller_type" class="form-label fw-semibold">Seller Entity</label>
                                <select name="seller_type" id="seller_type" class="form-select">
                                    <option value="Individual" <?php echo ($sellerType === 'Individual') ? 'selected' : ''; ?>>Individual Seller</option>
                                    <option value="Business" <?php echo ($sellerType === 'Business') ? 'selected' : ''; ?>>Registered Business / Shop</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="store_description" class="form-label fw-semibold">Short Bio / Tagline</label>
                                <input type="text" name="store_description" id="store_description" class="form-control" placeholder="e.g. Vintage lenses and mirrorless specialist" value="<?php echo sanitize($storeDescription); ?>">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold shadow-sm mb-3">
                        <i class="bi bi-person-check-fill me-2"></i> Complete Registration
                    </button>

                    <div class="text-center pt-2">
                        <span class="text-muted">Already have a system account?</span>
                        <a href="<?php echo base_url('login.php'); ?>" class="fw-bold text-decoration-none ms-1">
                            Sign In Here <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function selectType(type) {
    document.getElementById('type_' + type).checked = true;
    const sellerFields = document.getElementById('sellerFields');
    if (type === 'seller') {
        sellerFields.style.display = 'block';
    } else {
        sellerFields.style.display = 'none';
    }
}
document.getElementById('type_customer').addEventListener('change', () => selectType('customer'));
document.getElementById('type_seller').addEventListener('change', () => selectType('seller'));
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
