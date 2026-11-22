<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Requires login (admin or listing owner)

$currUser = current_user();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ListingID'])) {
    $id = (int)$_POST['ListingID'];

    $title = "Listing #{$id}";
    $stmtName = $conn->prepare("SELECT Title, SellerID FROM product_listing WHERE ListingID = ?");
    $stmtName->bind_param("i", $id);
    $stmtName->execute();
    $res = $stmtName->get_result();
    $row = $res->fetch_assoc();
    $stmtName->close();

    if ($row) {
        $title = $row['Title'];
        // Ownership check
        if (!is_admin() && (int)$row['SellerID'] !== (int)($currUser['SellerID'] ?? 0)) {
            flash('danger', 'Access denied. You can only delete listings belonging to your store.');
            redirect('product_listing/index.php');
        }
    }

    $conn->begin_transaction();
    try {
        // 1. Clean up images & physical files
        $imgStmt = $conn->prepare("SELECT ImageURL FROM listing_image WHERE ListingID = ?");
        $imgStmt->bind_param("i", $id);
        $imgStmt->execute();
        $imgRes = $imgStmt->get_result();
        $uploadsDir = __DIR__ . '/../';
        while ($imgRow = $imgRes->fetch_assoc()) {
            $path = $imgRow['ImageURL'];
            if (strpos($path, 'uploads/') === 0 && file_exists($uploadsDir . $path)) {
                // Check if another listing is using the same image file
                $chkOther = $conn->prepare("SELECT COUNT(*) FROM listing_image WHERE ImageURL = ? AND ListingID != ?");
                $chkOther->bind_param("si", $path, $id);
                $chkOther->execute();
                if ($chkOther->get_result()->fetch_row()[0] == 0) {
                    @unlink($uploadsDir . $path);
                }
                $chkOther->close();
            }
        }
        $imgStmt->close();

        // 2. Delete child records across all dependent tables
        $delImgs = $conn->prepare("DELETE FROM listing_image WHERE ListingID = ?");
        $delImgs->bind_param("i", $id);
        $delImgs->execute();
        $delImgs->close();

        $delSpecs = $conn->prepare("DELETE FROM listing_specification WHERE ListingID = ?");
        $delSpecs->bind_param("i", $id);
        $delSpecs->execute();
        $delSpecs->close();

        $delVer = $conn->prepare("DELETE FROM product_verification WHERE ListingID = ?");
        $delVer->bind_param("i", $id);
        $delVer->execute();
        $delVer->close();

        $delRes = $conn->prepare("DELETE FROM listing_reservation WHERE ListingID = ?");
        $delRes->bind_param("i", $id);
        $delRes->execute();
        $delRes->close();

        // 3. Delete the listing itself
        $stmt = $conn->prepare("DELETE FROM product_listing WHERE ListingID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        flash('success', "Listing '{$title}' (#{$id}) and all associated records were deleted successfully.");
    } catch (Exception $e) {
        $conn->rollback();
        flash('danger', "Error deleting listing: " . $e->getMessage());
    }
}

redirect('product_listing/index.php');
