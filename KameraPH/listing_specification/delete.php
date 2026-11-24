<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth(); // Admin or Seller

$redirectListing = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    $stmtSpec = $conn->prepare("SELECT ListingID FROM listing_specification WHERE ListingSpecID = ?");
    $stmtSpec->bind_param("i", $id);
    $stmtSpec->execute();
    $res = $stmtSpec->get_result();
    if ($row = $res->fetch_assoc()) {
        $redirectListing = $row['ListingID'];
    }
    $stmtSpec->close();

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

    $stmt = $conn->prepare("DELETE FROM listing_specification WHERE ListingSpecID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        flash('success', "Specification #{$id} was deleted successfully.");
    } else {
        flash('danger', "Error deleting specification: " . $stmt->error);
    }
    $stmt->close();
}

$url = ($redirectListing > 0) ? "listing_specification/index.php?listing_id={$redirectListing}" : "listing_specification/index.php";
redirect($url);

