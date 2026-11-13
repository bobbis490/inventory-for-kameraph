<?php
include "../config/database.php";

$sql = "SELECT pv.*, pl.Title
        FROM product_verification pv
        INNER JOIN product_listing pl
        ON pv.ListingID = pl.ListingID
        ORDER BY pv.VerificationID DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Verification Management</title>
</head>
<body>

<h2>Product Verification Management</h2>

<a href="add.php">+ Add Verification</a>

<br><br>

<table border="1" cellpadding="10">
    <tr>
        <th>Verification ID</th>
        <th>Listing ID</th>
        <th>Product Title</th>
        <th>Verification Type</th>
        <th>Status</th>
        <th>Admin ID</th>
        <th>Verified At</th>
        <th>Remarks</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?php echo $row['VerificationID']; ?></td>
        <td><?php echo $row['ListingID']; ?></td>
        <td><?php echo htmlspecialchars($row['Title']); ?></td>
        <td><?php echo htmlspecialchars($row['VerificationType']); ?></td>
        <td><?php echo htmlspecialchars($row['VerificationStatus']); ?></td>
        <td><?php echo $row['VerifiedByAdminID']; ?></td>
        <td><?php echo htmlspecialchars($row['VerifiedAt'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($row['Remarks'] ?? ''); ?></td>
        <td>
            <a href="edit.php?id=<?php echo $row['VerificationID']; ?>">Edit</a>

            <form action="delete.php" method="POST"
                  style="display:inline;"
                  onsubmit="return confirm('Delete this verification record?');">

                <input type="hidden" name="id"
                       value="<?php echo $row['VerificationID']; ?>">

                <button type="submit">Delete</button>
            </form>
        </td>
    </tr>
    <?php } ?>