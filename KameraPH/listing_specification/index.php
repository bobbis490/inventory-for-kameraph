<?php
include "../config/database.php";

$sql = "SELECT ls.*, pl.Title, sd.SpecName, sd.DataType, sd.Unit
        FROM listing_specification ls
        INNER JOIN product_listing pl
        ON ls.ListingID = pl.ListingID
        INNER JOIN spec_definition sd
        ON ls.SpecDefID = sd.SpecDefID
        ORDER BY ls.ListingSpecID DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Listing Specification Management</title>
</head>
<body>

<h2>Listing Specification Management</h2>

<a href="add.php">+ Add Listing Specification</a>

<br><br>

<table border="1" cellpadding="10">
    <tr>
        <th>Specification ID</th>
        <th>Listing ID</th>
        <th>Product Title</th>
        <th>Specification Name</th>
        <th>Data Type</th>
        <th>Specification Value</th>
        <th>Unit</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?php echo $row['ListingSpecID']; ?></td>
        <td><?php echo $row['ListingID']; ?></td>
        <td><?php echo htmlspecialchars($row['Title']); ?></td>
        <td><?php echo htmlspecialchars($row['SpecName']); ?></td>
        <td><?php echo htmlspecialchars($row['DataType']); ?></td>
        <td><?php echo htmlspecialchars($row['SpecValue']); ?></td>
        <td><?php echo htmlspecialchars($row['Unit'] ?? ''); ?></td>

        <td>
            <a href="edit.php?id=<?php echo $row['ListingSpecID']; ?>">
                Edit
            </a>

            <form action="delete.php" method="POST"
                  style="display:inline;"
                  onsubmit="return confirm('Delete this specification?');">

                <input type="hidden" name="id"
                       value="<?php echo $row['ListingSpecID']; ?>">

                <button type="submit">Delete</button>
            </form>
        </td>
    </tr>
    <?php } ?>

</table>

</body>
</html>
