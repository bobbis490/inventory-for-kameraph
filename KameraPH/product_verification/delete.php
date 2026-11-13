<?php
include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {

    $id = (int) $_POST['id'];

    $stmt = $conn->prepare(
        "DELETE FROM product_verification
         WHERE VerificationID = ?"
    );

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "Error deleting verification: "
             . htmlspecialchars($stmt->error);
    }

    $stmt->close();

} else {
    echo "Invalid request.";
}
?>

<br>
<a href="index.php">Back to Verification Records</a>
