
<?php
include '../config/database.php';

$message = "";

$brands = $conn->query("SELECT * FROM brand ORDER BY BrandName");
$categories = $conn->query("SELECT * FROM category ORDER BY CategoryName");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $brandID = $_POST['BrandID'];
    $categoryID = $_POST['CategoryID'];
    $modelName = trim($_POST['ModelName']);
    $releaseYear = $_POST['ReleaseYear'] !== "" ? $_POST['ReleaseYear'] : null;
    $status = $_POST['Status'];

    if ($modelName === "") {
        $message = "Model name is required.";
    } else {

        $sql = "INSERT INTO product_model
                (BrandID, CategoryID, ModelName, ReleaseYear, Status)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisis",
            $brandID, $categoryID, $modelName, $releaseYear, $status
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Camera Model</title>
</head>
<body>

<h1>Add New Camera Model</h1>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Brand:</label><br>
    <select name="BrandID" required>
        <option value="">Select Brand</option>
        <?php while ($brand = $brands->fetch_assoc()) { ?>
            <option value="<?php echo $brand['BrandID']; ?>">
                <?php echo htmlspecialchars($brand['BrandName']); ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Category:</label><br>
    <select name="CategoryID" required>
        <option value="">Select Category</option>
        <?php while ($category = $categories->fetch_assoc()) { ?>
            <option value="<?php echo $category['CategoryID']; ?>">
                <?php echo htmlspecialchars($category['CategoryName']); ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Model Name:</label><br>
    <input type="text" name="ModelName" required>

    <br><br>

    <label>Release Year:</label><br>
    <input type="number" name="ReleaseYear"
           min="1900" max="2100">

    <br><br>

    <label>Status:</label><br>
    <select name="Status" required>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
    </select>

    <br><br>

    <button type="submit">Save Model</button>
    <a href="index.php">Cancel</a>

</form>

</body>
</html>
