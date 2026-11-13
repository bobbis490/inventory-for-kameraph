<?php
include "../config/database.php";

$message = "";

$listings = $conn->query(
    "SELECT ListingID, Title FROM product_listing ORDER BY ListingID"
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $listingID = (int) $_POST['ListingID'];
    $verificationType = trim($_POST['VerificationType']);
    $verificationStatus = $_POST['VerificationStatus'];
    $adminID = (int) $_POST['VerifiedByAdminID'];
    $remarks = trim($_POST['Remarks']);

    $stmt = $conn->prepare(
        "INSERT INTO product_verification
        (ListingID, VerificationType, VerificationStatus,
         VerifiedByAdminID, VerifiedAt, Remarks)
        VALUES (?, ?, ?, ?, NOW(), ?)"
    );

    $stmt->bind_param(
        "issis",
        $listingID,
        $verificationType,
        $verificationStatus,
        $adminID,
        $remarks
    );

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        $message = "Error adding verification: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product Verification</title>
</head>
<body>

<h2>Add Product Verification</h2>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Product Listing:</label><br>

    <select name="ListingID" required>
        <option value="">Select Product Listing</option>

        <?php while ($listing = $listings->fetch_assoc()) { ?>
            <option value="<?php echo $listing['ListingID']; ?>">
                <?php echo $listing['ListingID']; ?> -
                <?php echo htmlspecialchars($listing['Title']); ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Verification Type:</label><br>
    <input type="text" name="VerificationType" required>

    <br><br>

    <label>Verification Status:</label><br>
    <select name="VerificationStatus" required>
        <option value="Pending">Pending</option>
        <option value="Verified">Verified</option>
        <option value="Rejected">Rejected</option>
    </select>

    <br><br>

    <label>Verified By Admin ID:</label><br>
    <input type="number" name="VerifiedByAdminID" min="1" required>

    <br><br>

    <label>Remarks:</label><br>
    <textarea name="Remarks" rows="4" cols="40"></textarea>

    <br><br>

    <button type="submit">Save Verification</button>

</form>

<br>
<a href="index.php">Back to Verification Records</a>

</body>
</html>
