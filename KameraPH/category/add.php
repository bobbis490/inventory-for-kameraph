
<?php
include '../config/database.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $categoryName = trim($_POST['CategoryName']);
    $description = trim($_POST['Description']);
    $parentCategoryID = $_POST['ParentCategoryID'];
    $status = $_POST['Status'];

    if ($parentCategoryID === "") {
        $parentCategoryID = NULL;
    } else {
        $parentCategoryID = (int)$parentCategoryID;
    }

    $sql = "INSERT INTO category
            (ParentCategoryID, CategoryName, Description, Status)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isss",
        $parentCategoryID,
        $categoryName,
        $description,
        $status
    );

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Get existing categories for the parent category dropdown
$categories = $conn->query(
    "SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Category - KameraPH</title>
</head>
<body>

    <h1>Add New Category</h1>

    <p style="color:red;">
        <?php echo htmlspecialchars($message); ?>
    </p>

    <form method="POST">

        <label>Category Name:</label><br>
        <input type="text" name="CategoryName" required>
        <br><br>

        <label>Description:</label><br>
        <textarea name="Description"></textarea>
        <br><br>

        <label>Parent Category:</label><br>
        <select name="ParentCategoryID">
            <option value="">None (Main Category)</option>

            <?php while ($row = $categories->fetch_assoc()) { ?>
                <option value="<?php echo $row['CategoryID']; ?>">
                    <?php echo htmlspecialchars($row['CategoryName']); ?>
                </option>
            <?php } ?>

        </select>
        <br><br>

        <label>Status:</label><br>
        <select name="Status" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>
        <br><br>

        <button type="submit">Save Category</button>
        <a href="index.php">Cancel</a>

    </form>

</body>
</html>
