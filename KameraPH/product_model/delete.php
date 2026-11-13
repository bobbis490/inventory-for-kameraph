
<?php
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ModelID'])) {

    $id = $_POST['ModelID'];

    $sql = "DELETE FROM product_model WHERE ModelID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        if ($stmt->errno == 1451) {
            die("Cannot delete this model because it is being used by another record.");
        } else {
            die("Error deleting model: " . htmlspecialchars($stmt->error));
        }
    }

    $stmt->close();

} else {
    header("Location: index.php");
    exit();
}
?>
