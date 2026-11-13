<?php
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = (int) $_POST['CategoryID'];

    try {
        $stmt = $conn->prepare(
            "DELETE FROM category WHERE CategoryID = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt->close();

        header("Location: index.php");
        exit();

    } catch (mysqli_sql_exception $e) {
        die("Cannot delete this category because it is being used by another record.");
    }

} else {
    header("Location: index.php");
    exit();
}
?>