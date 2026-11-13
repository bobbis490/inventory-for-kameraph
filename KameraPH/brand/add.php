<?php
include '../config/database.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $brandName = trim($_POST['BrandName']);
    $status = $_POST['Status'];

    if ($brandName === "") {
        $message = "Brand name is required.";
    } else {

        $sql = "INSERT INTO brand (BrandName, Status)
                VALUES (?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $brandName, $status);

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            if ($stmt->errno == 1062) {
                $message = "This brand already exists.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Brand - KameraPH</title>
</head>
<body>

    <h1>Add New Brand</h1>

    <p style="color:red;">
        <?php echo htmlspecialchars($message); ?>
    </p>

    <form method="POST">

        <label>Brand Name:</label><br>
        <input type="text" name="BrandName" required>
        <br><br>

        <label>Status:</label><br>
        <select name="Status" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>
        <br><br>

        <button type="submit">Save Brand</button>
        <a href="index.php">Cancel</a>

    </form>

</body>
</html>