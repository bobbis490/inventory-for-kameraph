<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ListingID'])) {
    $id = (int)$_POST['ListingID'];

    $title = "Listing #{$id}";
    $stmtName = $conn->prepare("SELECT Title FROM product_listing WHERE ListingID = ?");
    $stmtName->bind_param("i", $id);
    $stmtName->execute();
    $res = $stmtName->get_result();
    if ($row = $res->fetch_assoc()) {
        $title = $row['Title'];
    }
    $stmtName->close();

    $sql = "DELETE FROM product_listing WHERE ListingID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    try {
        if ($stmt->execute()) {
            flash('success', "Listing '{$title}' (#{$id}) was deleted successfully.");
        } else {
            flash('danger', "Error deleting listing: " . $stmt->error);
        }
    } catch (mysqli_sql_exception $e) {
        if ($stmt->errno == 1451 || $e->getCode() == 1451) {
            flash('danger', "Cannot delete listing '{$title}' because it has related records (images, specifications, verifications, or reservations). Please remove those child records first.");
        } else {
            flash('danger', "Database error: " . $e->getMessage());
        }
    }
    $stmt->close();
}

redirect('product_listing/index.php');
