<?php
include "../config/database.php";

$message = "";

if (!isset($_GET['id'])) {
    die("Specification ID is missing.");
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare(
    "SELECT * FROM listing_specification
     WHERE ListingSpecID = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$specification = $result->fetch_assoc();
$stmt->close();

if (!$specification) {
    die("Listing specification not found.");
}

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
        "UPDATE listing_specification
         SET ListingID = ?,
             SpecDefID = ?,
             SpecValue = ?
         WHERE ListingSpecID = ?"
    );

    $stmt->bind_param(
        "iisi",
        $listingID,
        $specDefID,
        $specValue,
        $id
    );

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        $message = "Error updating specification: " . $stmt->error;
    }

    $stmt->close();

    $specification['ListingID'] = $listingID;
    $specification['SpecDefID'] = $specDefID;
    $specification['SpecValue'] = $specValue;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Listing Specification</title>
</head>
<body>

<h2>Edit Listing Specification</h2>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Product Listing:</label><br>

    <select name="ListingID" required>
        <?php while ($listing = $listings->fetch_assoc()) { ?>
            <option value="<?php echo $listing['ListingID']; ?>"
                <?php echo ($listing['ListingID'] == $specification['ListingID']) ? "selected" : ""; ?>>

                <?php echo $listing['ListingID']; ?> -
                <?php echo htmlspecialchars($listing['Title']); ?>

            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Specification:</label><br>

    <select name="SpecDefID" required>
        <?php while ($spec = $specifications->fetch_assoc()) { ?>
            <option value="<?php echo $spec['SpecDefID']; ?>"
                <?php echo ($spec['SpecDefID'] == $specification['SpecDefID']) ? "selected" : ""; ?>>

                <?php echo htmlspecialchars($spec['SpecName']); ?>
                (<?php echo htmlspecialchars($spec['DataType']); ?>)

            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Specification Value:</label><br>

    <input type="text" name="SpecValue"
           value="<?php echo htmlspecialchars($specification['SpecValue']); ?>"
           required>

    <br><br>

    <button type="submit">Update Specification</button>

</form>

<br>
<a href="index.php">Back to Listing Specifications</a>

</body>
</html>
