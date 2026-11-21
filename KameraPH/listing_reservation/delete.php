<?php
require_once __DIR__ . '/../config/database.php';

$redirectListing = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    $stmtRes = $conn->prepare("SELECT ListingID FROM listing_reservation WHERE ReservationID = ?");
    $stmtRes->bind_param("i", $id);
    $stmtRes->execute();
    $res = $stmtRes->get_result();
    if ($row = $res->fetch_assoc()) {
        $redirectListing = $row['ListingID'];
    }
    $stmtRes->close();

    $stmt = $conn->prepare("DELETE FROM listing_reservation WHERE ReservationID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        flash('success', "Reservation #{$id} was deleted successfully.");
    } else {
        flash('danger', "Error deleting reservation: " . $stmt->error);
    }
    $stmt->close();
}

$url = ($redirectListing > 0) ? "listing_reservation/index.php?listing_id={$redirectListing}" : "listing_reservation/index.php";
redirect($url);

