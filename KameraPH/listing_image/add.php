<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../config/database.php";

$message = "";

$listings = $conn->query(
    "SELECT ListingID, Title FROM product_listing ORDER BY ListingID"
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $listingID = (int) $_POST['ListingID'];
    $imageURL = trim($_POST['ImageURL']);
    $displayOrder = (int) $_POST['DisplayOrder'];
    $isPrimary = isset($_POST['IsPrimary']) ? 1 : 0;

    if ($isPrimary) {
        $conn->begin_transaction();

        $stmt = $conn->prepare(
            "UPDATE listing_image SET IsPrimary = 0 WHERE ListingID = ?"
        );
        $stmt->bind_param("i", $listingID);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare(
        "INSERT INTO listing_image
        (ListingID, ImageURL, DisplayOrder, IsPrimary)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "isii",
        $listingID,
        $imageURL,
        $displayOrder,
        $isPrimary
    );

    if ($stmt->execute()) {
        if ($isPrimary) {
            $conn->commit();
        }

        header("Location: index.php");
        exit;
    } else {
        if ($isPrimary) {
            $conn->rollback();
        }

        $message = "Error adding image: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Listing Image</title>
</head>
<body>

<h2>Add Listing Image</h2>

<p style="color:red;"><?php echo htmlspecialchars($message); ?></p>

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

    <label>Image URL:</label><br>
    <input type="text" name="ImageURL" required>

    <br><br>

    <label>Display Order:</label><br>
    <input type="number" name="DisplayOrder" value="1" min="1" required>

    <br><br>

    <label>
        <input type="checkbox" name="IsPrimary" value="1">
        Set as Primary Image
    </label>

    <br><br>

    <button type="submit">Save Image</button>

</form>

<br>
<a href="index.php">Back to Listing Images</a>

</body>
</html>
