<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_auth(); // Must be logged in

$curr = current_user();
$userId = (int)$curr['UserID'];

$pageTitle = 'My Profile';
$activeMenu = 'profile';

$successMsg = "";
$errorMsg = "";

$db = Database::connect();

// Fetch fresh user details
$stmt = $db->prepare("SELECT * FROM user WHERE UserID = :uid LIMIT 1");
$stmt->execute([':uid' => $userId]);
$userData = $stmt->fetch();

// Fetch seller profile if applicable
$sellerData = null;
if (!empty($curr['SellerID']) || $curr['Role'] === 'seller') {
    $selStmt = $db->prepare("SELECT * FROM seller_profile WHERE UserID = :uid LIMIT 1");
    $selStmt->execute([':uid' => $userId]);
    $sellerData = $selStmt->fetch();
}

// Fetch admin profile if applicable
$adminData = null;
if (!empty($curr['is_admin'])) {
    $admStmt = $db->prepare("SELECT * FROM admin_user WHERE UserID = :uid LIMIT 1");
    $admStmt->execute([':uid' => $userId]);
    $adminData = $admStmt->fetch();
}

// Handle Update Profile Form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_info') {
        $phone = trim($_POST['phone'] ?? '');
        $storeName = trim($_POST['store_name'] ?? '');
        $storeDesc = trim($_POST['store_description'] ?? '');

        if ($phone === '') {
            $errorMsg = "Phone number cannot be empty.";
        } else {
            try {
                $upd = $db->prepare("UPDATE user SET PhoneNumber = :phone WHERE UserID = :uid");
                $upd->execute([':phone' => $phone, ':uid' => $userId]);

                // Update seller profile if seller
                if ($sellerData) {
                    $updSeller = $db->prepare("UPDATE seller_profile SET StoreName = :sname, StoreDescription = :sdesc WHERE UserID = :uid");
                    $updSeller->execute([':sname' => $storeName, ':sdesc' => $storeDesc, ':uid' => $userId]);
                    $_SESSION['user']['StoreName'] = $storeName;
                }

                $_SESSION['user']['PhoneNumber'] = $phone;
                $successMsg = "Profile information updated successfully!";

                // Refresh userData
                $stmt->execute([':uid' => $userId]);
                $userData = $stmt->fetch();
                if ($sellerData) {
                    $selStmt->execute([':uid' => $userId]);
                    $sellerData = $selStmt->fetch();
                }
            } catch (Exception $e) {
                $errorMsg = "Update failed: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'change_password') {
        $currentPw = $_POST['current_password'] ?? '';
        $newPw = $_POST['new_password'] ?? '';
        $confirmPw = $_POST['confirm_password'] ?? '';

        if ($currentPw === '' || $newPw === '' || $confirmPw === '') {
            $errorMsg = "Please fill in all password fields.";
        } elseif (!password_verify($currentPw, $userData['PasswordHash'])) {
            $errorMsg = "Current password is incorrect.";
        } elseif (strlen($newPw) < 8) {
            $errorMsg = "New password must be at least 8 characters long.";
        } elseif ($newPw !== $confirmPw) {
            $errorMsg = "New passwords do not match.";
        } else {
            try {
                $newHash = password_hash($newPw, PASSWORD_BCRYPT);
                $updPw = $db->prepare("UPDATE user SET PasswordHash = :hash WHERE UserID = :uid");
                $updPw->execute([':hash' => $newHash, ':uid' => $userId]);
                $successMsg = "Password changed successfully!";
            } catch (Exception $e) {
                $errorMsg = "Password update failed: " . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
                    <?php if (!empty($curr['is_admin'])): ?>
                        <li class="breadcrumb-item"><a href="<?php echo base_url('dashboard.php'); ?>">Dashboard</a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page">My Profile</li>
                </ol>
            </nav>

            <?php if ($successMsg !== ''): ?>
                <div class="alert alert-success d-flex align-items-center mb-3 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <div><?php echo sanitize($successMsg); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg !== ''): ?>
                <div class="alert alert-danger d-flex align-items-center mb-3 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div><?php echo sanitize($errorMsg); ?></div>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- User Overview Sidebar Card -->
                <div class="col-md-4">
                    <div class="card card-kamera shadow-sm border-0 text-center p-4">
                        <div class="mb-3">
                            <div class="d-inline-flex p-3 rounded-circle bg-warning text-dark shadow-sm">
                                <i class="bi bi-person-fill fs-1"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1"><?php echo sanitize($userData['FirstName'] . ' ' . $userData['LastName']); ?></h4>
                        <p class="text-muted small mb-2"><?php echo sanitize($userData['Email']); ?></p>

                        <div class="mb-3">
                            <span class="badge bg-warning text-dark px-3 py-2 fw-semibold">
                                <i class="bi bi-shield-check me-1"></i> <?php echo ucfirst(sanitize($curr['Role'])); ?>
                            </span>
                            <?php if ($adminData): ?>
                                <span class="badge bg-primary px-3 py-2 fw-semibold ms-1">
                                    <?php echo sanitize($adminData['AdminRole']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <ul class="list-group list-group-flush text-start small border-top pt-2">
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Account Status:</span>
                                <span class="badge bg-success"><?php echo sanitize($userData['Status']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Member Since:</span>
                                <span><?php echo !empty($userData['DateJoined']) ? date('M d, Y', strtotime($userData['DateJoined'])) : '—'; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Last Login:</span>
                                <span><?php echo !empty($userData['LastLoginAt']) ? date('M d, H:i', strtotime($userData['LastLoginAt'])) : 'Now'; ?></span>
                            </li>
                            <?php if ($sellerData): ?>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Store Name:</span>
                                    <span class="fw-bold"><?php echo sanitize($sellerData['StoreName']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Store Status:</span>
                                    <span class="badge bg-info text-dark"><?php echo sanitize($sellerData['VerificationStatus']); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="mt-3">
                            <a href="<?php echo base_url('logout.php'); ?>" class="btn btn-sm btn-outline-danger w-100">
                                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Profile Edit & Password Tabs -->
                <div class="col-md-8">
                    <div class="card card-kamera shadow-sm border-0">
                        <div class="card-header bg-white border-bottom">
                            <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active fw-semibold" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab">
                                        <i class="bi bi-person me-1"></i> Account Details
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-pane" type="button" role="tab">
                                        <i class="bi bi-key me-1"></i> Change Password
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body p-4">
                            <div class="tab-content" id="profileTabContent">
                                <!-- Tab 1: Account Info -->
                                <div class="tab-pane fade show active" id="info-pane" role="tabpanel">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_info">

                                        <div class="row g-3 mb-3">
                                            <div class="col-sm-6">
                                                <label class="form-label fw-semibold">First Name</label>
                                                <input type="text" class="form-control bg-light" value="<?php echo sanitize($userData['FirstName']); ?>" readonly disabled>
                                                <small class="text-muted">Name is linked to verified ID.</small>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label fw-semibold">Last Name</label>
                                                <input type="text" class="form-control bg-light" value="<?php echo sanitize($userData['LastName']); ?>" readonly disabled>
                                            </div>
                                        </div>

                                        <div class="row g-3 mb-3">
                                            <div class="col-sm-6">
                                                <label class="form-label fw-semibold">Email Address</label>
                                                <input type="email" class="form-control bg-light" value="<?php echo sanitize($userData['Email']); ?>" readonly disabled>
                                                <small class="text-muted">Shared Azure user identifier.</small>
                                            </div>
                                            <div class="col-sm-6">
                                                <label for="phone" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                                <input type="tel" name="phone" id="phone" class="form-control" value="<?php echo sanitize($userData['PhoneNumber']); ?>" required>
                                            </div>
                                        </div>

                                        <?php if ($sellerData): ?>
                                            <div class="p-3 bg-light rounded border mb-3">
                                                <h6 class="fw-bold mb-3"><i class="bi bi-shop me-1 text-primary"></i>Seller Store Settings</h6>
                                                <div class="mb-3">
                                                    <label for="store_name" class="form-label fw-semibold">Store Name</label>
                                                    <input type="text" name="store_name" id="store_name" class="form-control" value="<?php echo sanitize($sellerData['StoreName']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="store_description" class="form-label fw-semibold">Store Description</label>
                                                    <textarea name="store_description" id="store_description" class="form-control" rows="2"><?php echo sanitize($sellerData['StoreDescription'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <button type="submit" class="btn btn-warning fw-bold px-4">
                                            <i class="bi bi-check-lg me-1"></i> Save Changes
                                        </button>
                                    </form>
                                </div>

                                <!-- Tab 2: Change Password -->
                                <div class="tab-pane fade" id="security-pane" role="tabpanel">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="change_password">

                                        <div class="mb-3">
                                            <label for="current_password" class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                                            <input type="password" name="current_password" id="current_password" class="form-control" placeholder="••••••••" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="new_password" class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                                            <input type="password" name="new_password" id="new_password" class="form-control" minlength="8" placeholder="At least 8 characters" required>
                                        </div>

                                        <div class="mb-4">
                                            <label for="confirm_password" class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="8" placeholder="Repeat new password" required>
                                        </div>

                                        <button type="submit" class="btn btn-primary fw-bold px-4">
                                            <i class="bi bi-shield-lock me-1"></i> Update Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
