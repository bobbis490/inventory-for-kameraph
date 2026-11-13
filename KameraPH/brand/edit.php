
<?php
include '../config/database.php';

$message = "";

if (!isset($_GET['id'])) {
    die("Brand ID not found.");
}

$id = $_GET['id'];

// Get existing brand information
$sql = "SELECT * FROM brand WHERE BrandID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$brand = $result->fetch_assoc();

$stmt->close();

if (!$brand) {
    die("Brand not found.");
}

// Update brand information
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $brandName = trim($_POST['BrandName']);
    $status = $_POST['Status'];

    if ($brandName === "") {
        $message = "Brand name is required.";
    } else {

        $sql = "UPDATE brand
                SET BrandName = ?, Status = ?
                WHERE BrandID = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $brandName, $status, $id);

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            if ($stmt->errno == 1062) {
                $message = "This brand name already exists.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        }

        $stmt->close();
    }

    $brand['BrandName'] = $brandName;
    $brand['Status'] = $status;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Brand - KameraPH</title>
</head>
<body>

    <h1>Edit Brand</h1>

    <p style="color:red;">
        <?php echo htmlspecialchars($message); ?>
    </p>

    <form method="POST">

        <label>Brand Name:</label><br>
        <input type="text" name="BrandName"
        value="<?php echo htmlspecialchars($brand['BrandName']); ?>" required>
        <br><br>

        <label>Status:</label><br>
        <select name="Status" required>
            <option value="Active"
                <?php if ($brand['Status'] == 'Active') echo 'selected'; ?>>
                Active
            </option>

            <option value="Inactive"
                <?php if ($brand['Status'] == 'Inactive') echo 'selected'; ?>>
                Inactive
            </option>
        </select>
        <br><br>

        <button type="submit">Update Brand</button>
        <a href="index.php">Cancel</a>

    </form>

</body>
</html>
