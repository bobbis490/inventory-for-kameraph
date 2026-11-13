<?php
include "../config/database.php";

$message = "";

$listings = $conn->query(
    "SELECT ListingID, Title FROM product_listing ORDER BY ListingID"
);

$specifications = $conn->query(
    "SELECT SpecDefID, SpecName, DataType
     FROM spec_definition ORDER BY SpecName"
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $listingID = (int) $_POST['ListingID'];
    $specDefID = (int) $_POST['SpecDefID'];
    $specValue = trim($_POST['SpecValue']);

    $stmt = $conn->prepare(
        "INSERT INTO listing_specification
        (ListingID, SpecDefID, SpecValue)
        VALUES (?, ?, ?)"
    );

    $stmt->bind_param(
        "iis",
        $listingID,
        $specDefID,
        $specValue
    );

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        $message = "Error adding specification: " . $stmt->error;
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Add Listing Specification</title>
</head>
<body>

<h2>Add Listing Specification</h2>

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

    <label>Specification:</label><br>

    <select name="SpecDefID" required>
        <option value="">Select Specification</option>

        <?php while ($spec = $specifications->fetch_assoc()) { ?>
            <option value="<?php echo $spec['SpecDefID']; ?>">
                <?php echo htmlspecialchars($spec['SpecName']); ?>
                (<?php echo htmlspecialchars($spec['DataType']); ?>)
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Specification Value:</label><br>
    <input type="text" name="SpecValue" required>

    <br><br>

    <button type="submit">Save Specification</button>

</form>

<br>
<a href="index.php">Back to Listing Specifications</a>

</body>
</html>
