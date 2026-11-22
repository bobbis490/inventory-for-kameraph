<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin login required

$redirectListing = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    $stmtVer = $conn->prepare("SELECT ListingID FROM product_verification WHERE VerificationID = ?");
    $stmtVer->bind_param("i", $id);
    $stmtVer->execute();
    $res = $stmtVer->get_result();
    if ($row = $res->fetch_assoc()) {
        $redirectListing = $row['ListingID'];
    }
    $stmtVer->close();

    $stmt = $conn->prepare("DELETE FROM product_verification WHERE VerificationID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        flash('success', "Verification check #{$id} was deleted successfully.");
    } else {
        flash('danger', "Error deleting verification: " . $stmt->error);
    }
    $stmt->close();
}

$url = ($redirectListing > 0) ? "product_verification/index.php?listing_id={$redirectListing}" : "product_verification/index.php";
redirect($url);

