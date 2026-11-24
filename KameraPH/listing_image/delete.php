<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Admin or Seller

$redirectListing = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    // Get listing ID & image URL to clean up physical file
    $stmtImg = $conn->prepare("SELECT ListingID, ImageURL FROM listing_image WHERE ImageID = ?");
    $stmtImg->bind_param("i", $id);
    $stmtImg->execute();
    $res = $stmtImg->get_result();
    $imagePath = "";
    if ($row = $res->fetch_assoc()) {
        $redirectListing = (int)$row['ListingID'];
        $imagePath = $row['ImageURL'];
    }
    $stmtImg->close();

    $currUser = current_user();
    if (!is_admin()) {
        $chkOwner = $conn->prepare("SELECT SellerID FROM product_listing WHERE ListingID = ?");
        $chkOwner->bind_param("i", $redirectListing);
        $chkOwner->execute();
        $lRow = $chkOwner->get_result()->fetch_assoc();
        $chkOwner->close();
        if (!$lRow || !can_edit_listing(['SellerID' => $lRow['SellerID']])) {
            flash('danger', 'Access denied.');
            redirect('product_listing/index.php');
        }
    }

    $stmt = $conn->prepare("DELETE FROM listing_image WHERE ImageID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Clean up physical file if stored in uploads/ and not shared by another listing
        if (!empty($imagePath) && strpos($imagePath, 'uploads/') === 0) {
            $fullPath = __DIR__ . '/../' . $imagePath;
            $chkOther = $conn->prepare("SELECT COUNT(*) FROM listing_image WHERE ImageURL = ? AND ImageID != ?");
            $chkOther->bind_param("si", $imagePath, $id);
            $chkOther->execute();
            if ($chkOther->get_result()->fetch_row()[0] == 0 && file_exists($fullPath)) {
                @unlink($fullPath);
            }
            $chkOther->close();
        }

        flash('success', "Image #{$id} was deleted successfully.");
    } else {
        flash('danger', "Error deleting image: " . $stmt->error);
    }
    $stmt->close();
}

$url = ($redirectListing > 0) ? "product_listing/view.php?id={$redirectListing}" : "listing_image/index.php";
redirect($url);
