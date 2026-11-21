<?php
require_once __DIR__ . '/../config/database.php';

$redirectListing = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    // Get listing ID to redirect back nicely
    $stmtImg = $conn->prepare("SELECT ListingID, ImageURL FROM listing_image WHERE ImageID = ?");
    $stmtImg->bind_param("i", $id);
    $stmtImg->execute();
    $res = $stmtImg->get_result();
    if ($row = $res->fetch_assoc()) {
        $redirectListing = $row['ListingID'];
    }
    $stmtImg->close();

    $stmt = $conn->prepare("DELETE FROM listing_image WHERE ImageID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        flash('success', "Image #{$id} was deleted successfully.");
    } else {
        flash('danger', "Error deleting image: " . $stmt->error);
    }
    $stmt->close();
}

$url = ($redirectListing > 0) ? "listing_image/index.php?listing_id={$redirectListing}" : "listing_image/index.php";
redirect($url);
