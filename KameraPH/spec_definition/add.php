<?php
include '../config/database.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $categoryID = $_POST['CategoryID'];
    $specName = trim($_POST['SpecName']);
    $dataType = $_POST['DataType'];
    $unit = trim($_POST['Unit']);
    $unit = ($unit === "") ? null : $unit;

    $isRequired = isset($_POST['IsRequired']) ? 1 : 0;
    $isFilterable = isset($_POST['IsFilterable']) ? 1 : 0;

    if ($specName === "") {
        $message = "Specification name is required.";
    } else {

        $sql = "INSERT INTO spec_definition
                (CategoryID, SpecName, DataType, Unit, IsRequired, IsFilterable)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssii",
            $categoryID,
            $specName,
            $dataType,
            $unit,
            $isRequired,
            $isFilterable
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

$categories = $conn->query("SELECT * FROM category ORDER BY CategoryName");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Specification</title>
</head>
<body>

<h1>Add New Specification</h1>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

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

    <label>Specification Name:</label><br>
    <input type="text" name="SpecName" required>
    <br><br>

    <label>Data Type:</label><br>
    <select name="DataType" required>
        <option value="Text">Text</option>
        <option value="Number">Number</option>
        <option value="Decimal">Decimal</option>
        <option value="Boolean">Boolean</option>
    </select>

    <br><br>

    <label>Unit:</label><br>
    <input type="text" name="Unit"
           placeholder="Example: MP, mm, kg">
    <br><br>

    <label>
        <input type="checkbox" name="IsRequired" value="1">
        Required
    </label>

    <br>

    <label>
        <input type="checkbox" name="IsFilterable" value="1">
        Filterable
    </label>

    <br><br>

    <button type="submit">Save Specification</button>
    <a href="index.php">Cancel</a>

</form>

</body>
</html>
