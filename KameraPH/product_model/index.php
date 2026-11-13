
<?php
include '../config/database.php';

$sql = "SELECT pm.*, b.BrandName, c.CategoryName
        FROM product_model pm
        INNER JOIN brand b ON pm.BrandID = b.BrandID
        INNER JOIN category c ON pm.CategoryID = c.CategoryID
        ORDER BY pm.ModelID DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Camera Model Catalog</title>
</head>
<body>

<h1>Camera Model Catalog</h1>

<a href="add.php">Add New Model</a>

<br><br>

<table border="1" cellpadding="10">
<tr>
    <th>Model ID</th>
    <th>Brand</th>
    <th>Category</th>
    <th>Model Name</th>
    <th>Release Year</th>
    <th>Status</th>
    <th>Actions</th>
</tr>

<?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
?>

<tr>
    <td><?php echo htmlspecialchars($row['ModelID']); ?></td>
    <td><?php echo htmlspecialchars($row['BrandName']); ?></td>
    <td><?php echo htmlspecialchars($row['CategoryName']); ?></td>
    <td><?php echo htmlspecialchars($row['ModelName']); ?></td>
    <td><?php echo htmlspecialchars($row['ReleaseYear'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($row['Status']); ?></td>
    <td>
        <a href="edit.php?id=<?php echo $row['ModelID']; ?>">Edit</a>

        <form method="POST" action="delete.php"
              style="display:inline;"
              onsubmit="return confirm('Delete this camera model?');">
            <input type="hidden" name="ModelID"
                   value="<?php echo $row['ModelID']; ?>">
            <button type="submit">Delete</button>
        </form>
    </td>
</tr>

<?php
    }
} else {
    echo "<tr><td colspan='7'>No camera models found.</td></tr>";
}
?>

</table>

</body>
</html>
