
<?php
include '../config/database.php';

$sql = "SELECT * FROM brand ORDER BY BrandID DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>KameraPH - Brand Management</title>
</head>
<body>

    <h1>Brand Management</h1>

    <a href="add.php">Add New Brand</a>

    <br><br>

    <table border="1" cellpadding="10">
        <tr>
            <th>Brand ID</th>
            <th>Brand Name</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        ?>

        <tr>
            <td><?php echo htmlspecialchars($row['BrandID']); ?></td>
            <td><?php echo htmlspecialchars($row['BrandName']); ?></td>
            <td><?php echo htmlspecialchars($row['Status']); ?></td>
            <td><a href="edit.php?id=<?php echo $row['BrandID']; ?>">Edit</a>
               
                <form method="POST" action="delete.php" style="display:inline;"
                onsubmit="return confirm('Are you sure you want to delete this brand?');">
                <input type="hidden" name="BrandID"
                    value="<?php echo $row['BrandID']; ?>">
                <button type="submit">Delete</button>
                </form>
        
            </td>
        </tr>

        <?php
            }
        } else {
            echo "<tr><td colspan='3'>No brands found.</td></tr>";
        }
        ?>

    </table>

</body>
</html>
