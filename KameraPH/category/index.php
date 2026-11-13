<?php
include '../config/database.php';

$sql = "SELECT * FROM category ORDER BY CategoryID DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>KameraPH - Category Management</title>
</head>
<body>

    <h1>Category Management</h1>

    <a href="add.php" class="btn btn-primary">+ Add Category</a>

    <table border="1" cellpadding="10">
        <tr>
            <th>Category ID</th>
            <th>Category Name</th>
            <th>Description</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        ?>

        <tr>
            <td><?php echo htmlspecialchars($row['CategoryID']); ?></td>
            <td><?php echo htmlspecialchars($row['CategoryName']); ?></td>
            <td><?php echo htmlspecialchars($row['Description'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['Status']); ?></td>

            <td>
                <a href="edit.php?id=<?php echo $row['CategoryID']; ?>">
                    Edit
                </a>

                <form action="delete.php" method="POST"
            onsubmit="return confirm('Are you sure you want to delete this category?');">

            <input type="hidden" name="CategoryID"
                value="<?php echo $row['CategoryID']; ?>">

            <button type="submit">Delete</button>

        </form>
            </td>
        </tr>

        <?php
            }
        } else {
            echo "<tr><td colspan='5'>No categories found.</td></tr>";
        }
        ?>

    </table>

</body>
</html>