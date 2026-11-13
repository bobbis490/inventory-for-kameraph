<?php
include "../config/database.php";

$sql = "SELECT li.*, pl.Title
        FROM listing_image li
        INNER JOIN product_listing pl
        ON li.ListingID = pl.ListingID
        ORDER BY li.ListingID, li.DisplayOrder";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Listing Image Management</title>
</head>
<body>

<h2>Listing Image Management</h2>

<a href="add.php">+ Add Image</a>

<br><br>

<table border="1" cellpadding="10">
    <tr>
        <th>Image ID</th>
        <th>Listing ID</th>
        <th>Product Title</th>
        <th>Image</th>
        <th>Image URL</th>
        <th>Display Order</th>
        <th>Primary</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?php echo $row['ImageID']; ?></td>
        <td><?php echo $row['ListingID']; ?></td>
        <td><?php echo htmlspecialchars($row['Title']); ?></td>
        <td>
            <img src="<?php echo htmlspecialchars($row['ImageURL']); ?>"
                 width="100" alt="Product image">
        </td>
        <td><?php echo htmlspecialchars($row['ImageURL']); ?></td>
        <td><?php echo $row['DisplayOrder']; ?></td>
        <td><?php echo $row['IsPrimary'] ? "Yes" : "No"; ?></td>
        <td>
            <a href="edit.php?id=<?php echo $row['ImageID']; ?>">Edit</a>

            <form action="delete.php" method="POST"
                  style="display:inline;"
                  onsubmit="return confirm('Delete this image?');">
                <input type="hidden" name="id"
                       value="<?php echo $row['ImageID']; ?>">
                <button type="submit">Delete</button>
            </form>
        </td>
    </tr>
    <?php } ?>

</table>

</body>
</html>
