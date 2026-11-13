<?php
include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {

    $id = (int) $_POST['id'];

    $stmt = $conn->prepare(
        "DELETE FROM listing_reservation WHERE ReservationID = ?"
    );

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "Error deleting reservation: "
             . htmlspecialchars($stmt->error);
    }

    $stmt->close();

} else {
    echo "Invalid request.";
}
?>

<br>
<a href="index.php">Back to Reservations</a>
