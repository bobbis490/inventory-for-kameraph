<?php
include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {

    $id = (int) $_POST['id'];

    $stmt = $conn->prepare(
        "DELETE FROM listing_image WHERE ImageID = ?"
    );

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "Error deleting image: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();

} else {
    echo "Invalid request.";
}
?>
<br>
<a href="index.php">Back to Listing Images</a>
