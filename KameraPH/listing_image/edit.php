<?php
include "../config/database.php";

$message = "";

if (!isset($_GET['id'])) {
    die("Image ID is missing.");
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare(
    "SELECT * FROM listing_image WHERE ImageID = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$image = $result->fetch_assoc();
$stmt->close();

if (!$image) {
    die("Listing image not found.");
}

$listings = $conn->query(
    "SELECT ListingID, Title FROM product_listing ORDER BY ListingID"
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $listingID = (int) $_POST['ListingID'];
    $imageURL = trim($_POST['ImageURL']);
    $displayOrder = (int) $_POST['DisplayOrder'];
    $isPrimary = isset($_POST['IsPrimary']) ? 1 : 0;

    $conn->begin_transaction();

    if ($isPrimary) {
        $clear = $conn->prepare(
            "UPDATE listing_image
             SET IsPrimary = 0
             WHERE ListingID = ? AND ImageID != ?"
        );
        $clear->bind_param("ii", $listingID, $id);
        $clear->execute();
        $clear->close();
    }

    $stmt = $conn->prepare(
        "UPDATE listing_image
         SET ListingID = ?, ImageURL = ?, DisplayOrder = ?, IsPrimary = ?
         WHERE ImageID = ?"
    );

    $stmt->bind_param(
        "isiii",
        $listingID,
        $imageURL,
        $displayOrder,
        $isPrimary,
        $id
    );

    if ($stmt->execute()) {
        $conn->commit();
        header("Location: index.php");
        exit;
    } else {
        $conn->rollback();
        $message = "Error updating image: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Listing Image</title>
</head>
<body>

<h2>Edit Listing Image</h2>

<p style="color:red;"><?php echo htmlspecialchars($message); ?></p>

<form method="POST">

    <label>Product Listing:</label><br>
    <select name="ListingID" required>

        <?php while ($listing = $listings->fetch_assoc()) { ?>
            <option value="<?php echo $listing['ListingID']; ?>"
                <?php echo ($listing['ListingID'] == $image['ListingID']) ? "selected" : ""; ?>>
                <?php echo $listing['ListingID']; ?> -
                <?php echo htmlspecialchars($listing['Title']); ?>
            </option>
        <?php } ?>

    </select>

    <br><br>

    <label>Image URL:</label><br>
    <input type="text" name="ImageURL"
           value="<?php echo htmlspecialchars($image['ImageURL']); ?>"
           required>

    <br><br>

    <label>Display Order:</label><br>
    <input type="number" name="DisplayOrder"
           value="<?php echo $image['DisplayOrder']; ?>"
           min="1" required>

    <br><br>

    <label>
        <input type="checkbox" name="IsPrimary" value="1"
            <?php echo $image['IsPrimary'] ? "checked" : ""; ?>>
        Set as Primary Image
    </label>

    <br><br>

    <button type="submit">Update Image</button>

</form>

<br>
<a href="index.php">Back to Listing Images</a>

</body>
</html>
