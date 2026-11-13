<?php
include '../config/database.php';

$sql = "SELECT pl.*, pm.ModelName, b.BrandName
        FROM product_listing pl
        INNER JOIN product_model pm ON pl.ModelID = pm.ModelID
        INNER JOIN brand b ON pm.BrandID = b.BrandID
        ORDER BY pl.ListingID DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>KameraPH - Product Listings</title>
</head>
<body>

<h1>Product Listing Management</h1>

<a href="add.php">Add New Listing</a>

<br><br>

<table border="1" cellpadding="10">
<tr>
    <th>Listing ID</th>
    <th>Seller ID</th>
    <th>Brand</th>
    <th>Model</th>
    <th>Title</th>
    <th>Price</th>
    <th>Quantity</th>
    <th>Condition</th>
    <th>Status</th>
    <th>Actions</th>
</tr>

<?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
?>

<tr>
    <td><?php echo htmlspecialchars($row['ListingID']); ?></td>
    <td><?php echo htmlspecialchars($row['SellerID']); ?></td>
    <td><?php echo htmlspecialchars($row['BrandName']); ?></td>
    <td><?php echo htmlspecialchars($row['ModelName']); ?></td>
    <td><?php echo htmlspecialchars($row['Title']); ?></td>
    <td><?php echo number_format((float)$row['Price'], 2); ?></td>
    <td><?php echo htmlspecialchars($row['Quantity']); ?></td>
    <td><?php echo htmlspecialchars($row['Condition']); ?></td>
    <td><?php echo htmlspecialchars($row['Status']); ?></td>
    <td>
        <a href="edit.php?id=<?php echo $row['ListingID']; ?>">Edit</a>

        <form method="POST" action="delete.php"
              style="display:inline;"
              onsubmit="return confirm('Delete this listing?');">
            <input type="hidden" name="ListingID"
                   value="<?php echo $row['ListingID']; ?>">
            <button type="submit">Delete</button>
        </form>
    </td>
</tr>

<?php
    }
} else {
    echo "<tr><td colspan='10'>No listings found.</td></tr>";
}
?>

</table>

</body>
</html>
