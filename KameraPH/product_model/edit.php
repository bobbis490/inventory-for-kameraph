
<?php
include '../config/database.php';

if (!isset($_GET['id'])) {
    die("Model ID not found.");
}

$id = $_GET['id'];

$sql = "SELECT * FROM product_model WHERE ModelID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$model = $result->fetch_assoc();
$stmt->close();

if (!$model) {
    die("Camera model not found.");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $brandID = $_POST['BrandID'];
    $categoryID = $_POST['CategoryID'];
    $modelName = trim($_POST['ModelName']);
    $releaseYear = $_POST['ReleaseYear'] !== "" ? $_POST['ReleaseYear'] : null;
    $status = $_POST['Status'];

    if ($modelName === "") {
        $message = "Model name is required.";
    } else {

        $sql = "UPDATE product_model
                SET BrandID = ?, CategoryID = ?,
                    ModelName = ?, ReleaseYear = ?, Status = ?
                WHERE ModelID = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssi",
            $brandID, $categoryID, $modelName,
            $releaseYear, $status, $id
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $model['BrandID'] = $brandID;
    $model['CategoryID'] = $categoryID;
    $model['ModelName'] = $modelName;
    $model['ReleaseYear'] = $releaseYear;
    $model['Status'] = $status;
}

$brands = $conn->query("SELECT * FROM brand ORDER BY BrandName");
$categories = $conn->query("SELECT * FROM category ORDER BY CategoryName");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Camera Model</title>
</head>
<body>

<h1>Edit Camera Model</h1>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Brand:</label><br>
    <select name="BrandID" required>
        <?php while ($brand = $brands->fetch_assoc()) { ?>
            <option value="<?php echo $brand['BrandID']; ?>"
                <?php if ($model['BrandID'] == $brand['BrandID']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($brand['BrandName']); ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Category:</label><br>
    <select name="CategoryID" required>
        <?php while ($category = $categories->fetch_assoc()) { ?>
            <option value="<?php echo $category['CategoryID']; ?>"
                <?php if ($model['CategoryID'] == $category['CategoryID']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($category['CategoryName']); ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Model Name:</label><br>
    <input type="text" name="ModelName"
        value="<?php echo htmlspecialchars($model['ModelName']); ?>" required>

    <br><br>

    <label>Release Year:</label><br>
    <input type="number" name="ReleaseYear"
        value="<?php echo htmlspecialchars($model['ReleaseYear'] ?? ''); ?>"
        min="1900" max="2100">

    <br><br>

    <label>Status:</label><br>
    <select name="Status" required>
        <option value="Active"
            <?php if ($model['Status'] == 'Active') echo 'selected'; ?>>
            Active
        </option>

        <option value="Inactive"
            <?php if ($model['Status'] == 'Inactive') echo 'selected'; ?>>
            Inactive
        </option>
    </select>

    <br><br>

    <button type="submit">Update Model</button>
    <a href="index.php">Cancel</a>

</form>

</body>
</html>
