<?php
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ListingID'])) {

    $id = (int)$_POST['ListingID'];

    $sql = "DELETE FROM product_listing WHERE ListingID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        if ($stmt->errno == 1451) {
            die("Cannot delete this listing because it has related records.");
        } else {
            die("Error deleting listing: " .
                htmlspecialchars($stmt->error));
        }
    }

    $stmt->close();

} else {
    header("Location: index.php");
    exit();
}
?>
