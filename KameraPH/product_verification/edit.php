<?php
include "../config/database.php";

$message = "";

if (!isset($_GET['id'])) {
    die("Verification ID is missing.");
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare(
    "SELECT * FROM product_verification WHERE VerificationID = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$verification = $result->fetch_assoc();
$stmt->close();

if (!$verification) {
    die("Verification record not found.");
}

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
        "UPDATE product_verification
         SET ListingID = ?,
             VerificationType = ?,
             VerificationStatus = ?,
             VerifiedByAdminID = ?,
             VerifiedAt = NOW(),
             Remarks = ?
         WHERE VerificationID = ?"
    );

    $stmt->bind_param(
        "issisi",
        $listingID,
        $verificationType,
        $verificationStatus,
        $adminID,
        $remarks,
        $id
    );

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        $message = "Error updating verification: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product Verification</title>
</head>
<body>

<h2>Edit Product Verification</h2>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Product Listing:</label><br>

    <select name="ListingID" required>
        <?php while ($listing = $listings->fetch_assoc()) { ?>
            <option value="<?php echo $listing['ListingID']; ?>"
                <?php echo ($listing['ListingID'] == $verification['ListingID']) ? "selected" : ""; ?>>

                <?php echo $listing['ListingID']; ?> -
                <?php echo htmlspecialchars($listing['Title']); ?>

            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Verification Type:</label><br>
    <input type="text" name="VerificationType"
           value="<?php echo htmlspecialchars($verification['VerificationType']); ?>"
           required>

    <br><br>

    <label>Verification Status:</label><br>
    <select name="VerificationStatus" required>
        <?php
        $statuses = ["Pending", "Verified", "Rejected"];

        foreach ($statuses as $status) {
        ?>
            <option value="<?php echo $status; ?>"
                <?php echo ($verification['VerificationStatus'] == $status) ? "selected" : ""; ?>>
                <?php echo $status; ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Verified By Admin ID:</label><br>
    <input type="number" name="VerifiedByAdminID"
           value="<?php echo $verification['VerifiedByAdminID']; ?>"
           min="1" required>

    <br><br>

    <label>Remarks:</label><br>
    <textarea name="Remarks" rows="4" cols="40"><?php
        echo htmlspecialchars($verification['Remarks'] ?? '');
    ?></textarea>

    <br><br>

    <button type="submit">Update Verification</button>

</form>

<br>
<a href="index.php">Back to Verification Records</a>

</body>
</html>
