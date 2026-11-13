<?php
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['BrandID'])) {

    $id = $_POST['BrandID'];

    $sql = "DELETE FROM brand WHERE BrandID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        if ($stmt->errno == 1451) {
            die("Cannot delete this brand because it is being used by a camera model.");
        } else {
            die("Error deleting brand: " . htmlspecialchars($stmt->error));
        }
    }

    $stmt->close();

} else {
    header("Location: index.php");
    exit();
}
?>
