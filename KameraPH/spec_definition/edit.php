<?php
include '../config/database.php';

if (!isset($_GET['id'])) {
    die("Specification ID not found.");
}

$id = $_GET['id'];

$sql = "SELECT * FROM spec_definition WHERE SpecDefID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$spec = $result->fetch_assoc();
$stmt->close();

if (!$spec) {
    die("Specification not found.");
}

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

        $sql = "UPDATE spec_definition
                SET CategoryID = ?, SpecName = ?, DataType = ?,
                    Unit = ?, IsRequired = ?, IsFilterable = ?
                WHERE SpecDefID = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssiii",
            $categoryID,
            $specName,
            $dataType,
            $unit,
            $isRequired,
            $isFilterable,
            $id
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $spec['CategoryID'] = $categoryID;
    $spec['SpecName'] = $specName;
    $spec['DataType'] = $dataType;
    $spec['Unit'] = $unit;
    $spec['IsRequired'] = $isRequired;
    $spec['IsFilterable'] = $isFilterable;
}

$categories = $conn->query("SELECT * FROM category ORDER BY CategoryName");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Specification</title>
</head>
<body>

<h1>Edit Specification</h1>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Category:</label><br>
    <select name="CategoryID" required>
        <?php while ($category = $categories->fetch_assoc()) { ?>
            <option value="<?php echo $category['CategoryID']; ?>"
                <?php if ($spec['CategoryID'] == $category['CategoryID']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($category['CategoryName']); ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Specification Name:</label><br>
    <input type="text" name="SpecName"
        value="<?php echo htmlspecialchars($spec['SpecName']); ?>" required>

    <br><br>

    <label>Data Type:</label><br>
    <select name="DataType" required>
        <?php
        $types = ['Text', 'Number', 'Decimal', 'Boolean'];
        foreach ($types as $type) {
        ?>
            <option value="<?php echo $type; ?>"
                <?php if ($spec['DataType'] == $type) echo 'selected'; ?>>
                <?php echo $type; ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Unit:</label><br>
    <input type="text" name="Unit"
        value="<?php echo htmlspecialchars($spec['Unit'] ?? ''); ?>">

    <br><br>

    <label>
        <input type="checkbox" name="IsRequired" value="1"
            <?php if ($spec['IsRequired']) echo 'checked'; ?>>
        Required
    </label>

    <br>

    <label>
        <input type="checkbox" name="IsFilterable" value="1"
            <?php if ($spec['IsFilterable']) echo 'checked'; ?>>
        Filterable
    </label>

    <br><br>

    <button type="submit">Update Specification</button>
    <a href="index.php">Cancel</a>

</form>

</body>
</html>
