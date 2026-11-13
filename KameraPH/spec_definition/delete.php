<?php
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['SpecDefID'])) {

    $id = $_POST['SpecDefID'];

    $sql = "DELETE FROM spec_definition WHERE SpecDefID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        if ($stmt->errno == 1451) {
            die("Cannot delete this specification because it is being used by a product listing.");
        } else {
            die("Error deleting specification: " .
                htmlspecialchars($stmt->error));
        }
    }

    $stmt->close();

} else {
    header("Location: index.php");
    exit();
}
?>
