<?php
include "../config/database.php";

$sql = "SELECT lr.*, pl.Title
        FROM listing_reservation lr
        INNER JOIN product_listing pl
        ON lr.ListingID = pl.ListingID
        ORDER BY lr.ReservationID DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Listing Reservation Management</title>
</head>
<body>

<h2>Listing Reservation Management</h2>

<a href="add.php">+ Add Reservation</a>

<br><br>

<table border="1" cellpadding="10">
    <tr>
        <th>Reservation ID</th>
        <th>Listing ID</th>
        <th>Product Title</th>
        <th>Order ID</th>
        <th>Quantity</th>
        <th>Reserved At</th>
        <th>Expires At</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?php echo $row['ReservationID']; ?></td>
        <td><?php echo $row['ListingID']; ?></td>
        <td><?php echo htmlspecialchars($row['Title']); ?></td>
        <td><?php echo $row['OrderID']; ?></td>
        <td><?php echo $row['Quantity']; ?></td>
        <td><?php echo htmlspecialchars($row['ReservedAt']); ?></td>
        <td><?php echo htmlspecialchars($row['ExpiresAt']); ?></td>
        <td><?php echo htmlspecialchars($row['Status']); ?></td>

        <td>
            <a href="edit.php?id=<?php echo $row['ReservationID']; ?>">
                Edit
            </a>

            <form action="delete.php" method="POST"
                  style="display:inline;"
                  onsubmit="return confirm('Delete this reservation?');">

                <input type="hidden" name="id"
                       value="<?php echo $row['ReservationID']; ?>">

                <button type="submit">Delete</button>
            </form>
        </td>
    </tr>
    <?php } ?>

</table>

</body>
</html>
