<?php
include '../config/database.php';

if (!isset($_GET['id'])) {
    die("Listing ID not found.");
}

$id = (int)$_GET['id'];

$sql = "SELECT * FROM product_listing WHERE ListingID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$listing = $result->fetch_assoc();
$stmt->close();

if (!$listing) {
    die("Listing not found.");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $sellerID = (int)$_POST['SellerID'];
    $modelID = (int)$_POST['ModelID'];
    $title = trim($_POST['Title']);
    $description = trim($_POST['Description']);
    $description = ($description === "") ? null : $description;
    $price = (float)$_POST['Price'];
    $originalPrice = $_POST['OriginalPrice'] !== ""
        ? (float)$_POST['OriginalPrice'] : null;
    $condition = $_POST['Condition'];
    $quantity = (int)$_POST['Quantity'];
    $weight = (int)$_POST['WeightGrams'];
    $length = (float)$_POST['LengthCm'];
    $width = (float)$_POST['WidthCm'];
    $height = (float)$_POST['HeightCm'];
    $insuredValue = (float)$_POST['InsuredValue'];
    $status = $_POST['Status'];
    $suspensionReason = trim($_POST['SuspensionReason']);
    $suspensionReason = ($suspensionReason === "")
        ? null : $suspensionReason;

    if ($sellerID <= 0 || $modelID <= 0 || $title === "") {
        $message = "Please provide a valid seller, model, and title.";
    } else {

        $sql = "UPDATE product_listing SET
                SellerID = ?, ModelID = ?, Title = ?, Description = ?,
                Price = ?, OriginalPrice = ?, `Condition` = ?,
                Quantity = ?, WeightGrams = ?, LengthCm = ?,
                WidthCm = ?, HeightCm = ?, InsuredValue = ?,
                Status = ?, SuspensionReason = ?
                WHERE ListingID = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iissddsiiddddssi",
            $sellerID, $modelID, $title, $description,
            $price, $originalPrice, $condition, $quantity,
            $weight, $length, $width, $height,
            $insuredValue, $status, $suspensionReason, $id
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $listing['SellerID'] = $sellerID;
    $listing['ModelID'] = $modelID;
    $listing['Title'] = $title;
    $listing['Description'] = $description;
    $listing['Price'] = $price;
    $listing['OriginalPrice'] = $originalPrice;
    $listing['Condition'] = $condition;
    $listing['Quantity'] = $quantity;
    $listing['WeightGrams'] = $weight;
    $listing['LengthCm'] = $length;
    $listing['WidthCm'] = $width;
    $listing['HeightCm'] = $height;
    $listing['InsuredValue'] = $insuredValue;
    $listing['Status'] = $status;
    $listing['SuspensionReason'] = $suspensionReason;
}

$models = $conn->query("
    SELECT pm.ModelID, pm.ModelName, b.BrandName
    FROM product_model pm
    INNER JOIN brand b ON pm.BrandID = b.BrandID
    ORDER BY b.BrandName, pm.ModelName
");

$conditions = [
    'Brand New', 'Mint', 'Excellent',
    'Good', 'Fair', 'With defects'
];

$statuses = [
    'Draft', 'Pending Verification', 'Rejected',
    'Live', 'Reserved', 'Sold', 'Suspended'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product Listing</title>
</head>
<body>

<h1>Edit Product Listing</h1>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Seller ID:</label><br>
    <input type="number" name="SellerID" min="1"
        value="<?php echo htmlspecialchars($listing['SellerID']); ?>"
        required>
    <br><br>

    <label>Camera Model:</label><br>
    <select name="ModelID" required>
        <?php while ($model = $models->fetch_assoc()) { ?>
            <option value="<?php echo $model['ModelID']; ?>"
                <?php if ($listing['ModelID'] == $model['ModelID']) echo 'selected'; ?>>
                <?php echo htmlspecialchars(
                    $model['BrandName'] . ' - ' . $model['ModelName']
                ); ?>
            </option>
        <?php } ?>
    </select>
    <br><br>

    <label>Listing Title:</label><br>
    <input type="text" name="Title" maxlength="255"
        value="<?php echo htmlspecialchars($listing['Title']); ?>"
        required>
    <br><br>

    <label>Description:</label><br>
    <textarea name="Description"><?php
        echo htmlspecialchars($listing['Description'] ?? '');
    ?></textarea>
    <br><br>

    <label>Price:</label><br>
    <input type="number" name="Price" min="0.01" step="0.01"
        value="<?php echo htmlspecialchars($listing['Price']); ?>"
        required>
    <br><br>

    <label>Original Price:</label><br>
    <input type="number" name="OriginalPrice" min="0" step="0.01"
        value="<?php echo htmlspecialchars($listing['OriginalPrice'] ?? ''); ?>">
    <br><br>

    <label>Condition:</label><br>
    <select name="Condition" required>
        <?php foreach ($conditions as $condition) { ?>
            <option value="<?php echo $condition; ?>"
                <?php if ($listing['Condition'] == $condition) echo 'selected'; ?>>
                <?php echo $condition; ?>
            </option>
        <?php } ?>
    </select>
    <br><br>

    <label>Quantity:</label><br>
    <input type="number" name="Quantity" min="0"
        value="<?php echo htmlspecialchars($listing['Quantity']); ?>"
        required>
    <br><br>

    <label>Weight (grams):</label><br>
    <input type="number" name="WeightGrams" min="0"
        value="<?php echo htmlspecialchars($listing['WeightGrams']); ?>"
        required>
    <br><br>

    <label>Length (cm):</label><br>
    <input type="number" name="LengthCm" min="0" step="0.01"
        value="<?php echo htmlspecialchars($listing['LengthCm']); ?>"
        required>
    <br><br>

    <label>Width (cm):</label><br>
    <input type="number" name="WidthCm" min="0" step="0.01"
        value="<?php echo htmlspecialchars($listing['WidthCm']); ?>"
        required>
    <br><br>

    <label>Height (cm):</label><br>
    <input type="number" name="HeightCm" min="0" step="0.01"
        value="<?php echo htmlspecialchars($listing['HeightCm']); ?>"
        required>
    <br><br>

    <label>Insured Value:</label><br>
    <input type="number" name="InsuredValue" min="0" step="0.01"
        value="<?php echo htmlspecialchars($listing['InsuredValue']); ?>"
        required>
    <br><br>

    <label>Status:</label><br>
    <select name="Status" required>
        <?php foreach ($statuses as $status) { ?>
            <option value="<?php echo $status; ?>"
                <?php if ($listing['Status'] == $status) echo 'selected'; ?>>
                <?php echo $status; ?>
            </option>
        <?php } ?>
    </select>
    <br><br>

    <label>Suspension Reason:</label><br>
    <textarea name="SuspensionReason"><?php
        echo htmlspecialchars($listing['SuspensionReason'] ?? '');
    ?></textarea>
    <br><br>

    <button type="submit">Update Listing</button>
    <a href="index.php">Cancel</a>

</form>

</body>
</html>
