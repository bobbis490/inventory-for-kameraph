<?php
include '../config/database.php';

$sql = "SELECT sd.*, c.CategoryName
        FROM spec_definition sd
        INNER JOIN category c ON sd.CategoryID = c.CategoryID
        ORDER BY sd.SpecDefID DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Specifications</title>
</head>
<body>

<h1>Product Specifications</h1>

<a href="add.php">Add New Specification</a>

<br><br>

<table border="1" cellpadding="10">
<tr>
    <th>Specification ID</th>
    <th>Category</th>
    <th>Specification Name</th>
    <th>Data Type</th>
    <th>Unit</th>
    <th>Required</th>
    <th>Filterable</th>
    <th>Actions</th>
</tr>

<?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
?>

<tr>
    <td><?php echo htmlspecialchars($row['SpecDefID']); ?></td>
    <td><?php echo htmlspecialchars($row['CategoryName']); ?></td>
    <td><?php echo htmlspecialchars($row['SpecName']); ?></td>
    <td><?php echo htmlspecialchars($row['DataType']); ?></td>
    <td><?php echo htmlspecialchars($row['Unit'] ?? ''); ?></td>
    <td><?php echo $row['IsRequired'] ? 'Yes' : 'No'; ?></td>
    <td><?php echo $row['IsFilterable'] ? 'Yes' : 'No'; ?></td>
    <td>
        <a href="edit.php?id=<?php echo $row['SpecDefID']; ?>">Edit</a>

        <form method="POST" action="delete.php"
              style="display:inline;"
              onsubmit="return confirm('Delete this specification?');">
            <input type="hidden" name="SpecDefID"
                   value="<?php echo $row['SpecDefID']; ?>">
            <button type="submit">Delete</button>
        </form>
    </td>
</tr>

<?php
    }
} else {
    echo "<tr><td colspan='8'>No specifications found.</td></tr>";
}
?>

</table>

</body>
</html>
