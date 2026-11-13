<?php
include "../config/database.php";

$sql = "SELECT pr.*, pm.ModelName, b.BrandName
        FROM product_review pr
        INNER JOIN product_model pm
        ON pr.ModelID = pm.ModelID
        INNER JOIN brand b
        ON pm.BrandID = b.BrandID
        ORDER BY pr.ReviewID DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Review Management</title>
</head>
<body>

<h2>Product Review Management</h2>

<a href="add.php">+ Add Product Review</a>

<br><br>

<table border="1" cellpadding="10">
    <tr>
        <th>Review ID</th>
        <th>Order Item ID</th>
        <th>Model ID</th>
        <th>Brand</th>
        <th>Camera Model</th>
        <th>Reviewer User ID</th>
        <th>Rating</th>
        <th>Review</th>
        <th>Created At</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?php echo $row['ReviewID']; ?></td>
        <td><?php echo $row['OrderItemID']; ?></td>
        <td><?php echo $row['ModelID']; ?></td>
        <td><?php echo htmlspecialchars($row['BrandName']); ?></td>
        <td><?php echo htmlspecialchars($row['ModelName']); ?></td>
        <td><?php echo $row['ReviewerUserID']; ?></td>
        <td><?php echo $row['RatingValue']; ?>/5</td>
        <td><?php echo htmlspecialchars($row['ReviewText']); ?></td>
        <td><?php echo htmlspecialchars($row['CreatedAt']); ?></td>

        <td>
            <a href="edit.php?id=<?php echo $row['ReviewID']; ?>">Edit</a>

            <form action="delete.php" method="POST"
                  style="display:inline;"
                  onsubmit="return confirm('Delete this review?');">

                <input type="hidden" name="id"
                       value="<?php echo $row['ReviewID']; ?>">

                <button type="submit">Delete</button>
            </form>
        </td>
    </tr>
    <?php } ?>

</table>

</body>
</html>
