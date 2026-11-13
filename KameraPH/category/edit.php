
<?php
include '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid category ID.");
}

$id = (int) $_GET['id'];
$message = "";

// Get the selected category
$stmt = $conn->prepare(
    "SELECT * FROM category WHERE CategoryID = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$category = $result->fetch_assoc();
$stmt->close();

if (!$category) {
    die("Category not found.");
}

// Update category
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $categoryName = trim($_POST['CategoryName']);
    $description = trim($_POST['Description']);
    $parentCategoryID = $_POST['ParentCategoryID'];
    $status = $_POST['Status'];

    if ($categoryName === "") {
        $message = "Category name is required.";
    } elseif ($parentCategoryID !== "" && (int)$parentCategoryID === $id) {
        $message = "A category cannot be its own parent.";
    } else {

        $parentCategoryID = ($parentCategoryID === "")
            ? NULL
            : (int)$parentCategoryID;

        $sql = "UPDATE category
                SET ParentCategoryID = ?,
                    CategoryName = ?,
                    Description = ?,
                    Status = ?
                WHERE CategoryID = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssi",
            $parentCategoryID,
            $categoryName,
            $description,
            $status,
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
}

// Get available parent categories
$stmt = $conn->prepare(
    "SELECT CategoryID, CategoryName
     FROM category
     WHERE CategoryID != ?
     ORDER BY CategoryName"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$categories = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Category - KameraPH</title>
</head>
<body>

<h1>Edit Category</h1>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Category Name:</label><br>
    <input type="text" name="CategoryName"
        value="<?php echo htmlspecialchars($category['CategoryName']); ?>"
        required>
    <br><br>

    <label>Description:</label><br>
    <textarea name="Description"><?php
        echo htmlspecialchars($category['Description'] ?? '');
    ?></textarea>
    <br><br>

    <label>Parent Category:</label><br>

    <select name="ParentCategoryID">
        <option value="">None (Main Category)</option>

        <?php while ($row = $categories->fetch_assoc()) { ?>
            <option value="<?php echo $row['CategoryID']; ?>"
                <?php
                echo ($category['ParentCategoryID'] == $row['CategoryID'])
                    ? 'selected'
                    : '';
                ?>>
                <?php echo htmlspecialchars($row['CategoryName']); ?>
            </option>
        <?php } ?>

    </select>
    <br><br>

    <label>Status:</label><br>

    <select name="Status">
        <option value="Active"
            <?php echo ($category['Status'] == 'Active') ? 'selected' : ''; ?>>
            Active
        </option>

        <option value="Inactive"
            <?php echo ($category['Status'] == 'Inactive') ? 'selected' : ''; ?>>
            Inactive
        </option>
    </select>

    <br><br>

    <button type="submit">Update Category</button>
    <a href="index.php">Cancel</a>

</form>

</body>
</html>
