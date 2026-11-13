<?php
include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {

    $id = (int) $_POST['id'];

    $stmt = $conn->prepare(
        "DELETE FROM listing_specification
         WHERE ListingSpecID = ?"
    );

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "Error deleting specification: "
             . htmlspecialchars($stmt->error);
    }

    $stmt->close();

} else {
    echo "Invalid request.";
}
?>

<br>
<a href="index.php">Back to Listing Specifications</a>
