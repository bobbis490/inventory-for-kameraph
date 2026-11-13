<?php
include '../config/database.php';

$message = "";

$models = $conn->query("
    SELECT pm.ModelID, pm.ModelName, b.BrandName
    FROM product_model pm
    INNER JOIN brand b ON pm.BrandID = b.BrandID
    ORDER BY b.BrandName, pm.ModelName
");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $sellerID = (int)$_POST['SellerID'];
    $modelID = (int)$_POST['ModelID'];
    $title = trim($_POST['Title']);
    $description = trim($_POST['Description']);
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

    $description = ($description === "") ? null : $description;

    if ($sellerID <= 0 || $modelID <= 0 || $title === "") {
        $message = "Please provide a valid seller, model, and title.";
    } else {

        $sql = "INSERT INTO product_listing
        (SellerID, ModelID, Title, Description, Price, OriginalPrice,
         `Condition`, Quantity, WeightGrams, LengthCm, WidthCm,
         HeightCm, InsuredValue, Status, SuspensionReason)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iissddsiiddddss",
            $sellerID, $modelID, $title, $description,
            $price, $originalPrice, $condition, $quantity,
            $weight, $length, $width, $height,
            $insuredValue, $status, $suspensionReason
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

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
    <title>Add Product Listing</title>
</head>
<body>

<h1>Add New Product Listing</h1>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Seller ID:</label><br>
    <input type="number" name="SellerID" min="1" required>
    <br><br>

    <label>Camera Model:</label><br>
    <select name="ModelID" required>
        <option value="">Select Model</option>
        <?php while ($model = $models->fetch_assoc()) { ?>
            <option value="<?php echo $model['ModelID']; ?>">
                <?php echo htmlspecialchars(
                    $model['BrandName'] . ' - ' . $model['ModelName']
                ); ?>
            </option>
        <?php } ?>
    </select>
    <br><br>

    <label>Listing Title:</label><br>
    <input type="text" name="Title" maxlength="255" required>
    <br><br>

    <label>Description:</label><br>
    <textarea name="Description"></textarea>
    <br><br>

    <label>Price:</label><br>
    <input type="number" name="Price" min="0.01"
           step="0.01" required>
    <br><br>

    <label>Original Price:</label><br>
    <input type="number" name="OriginalPrice"
           min="0" step="0.01">
    <br><br>

    <label>Condition:</label><br>
    <select name="Condition" required>
        <?php foreach ($conditions as $condition) { ?>
            <option value="<?php echo $condition; ?>">
                <?php echo $condition; ?>
            </option>
        <?php } ?>
    </select>
    <br><br>

    <label>Quantity:</label><br>
    <input type="number" name="Quantity" min="0" required>
    <br><br>

    <label>Weight (grams):</label><br>
    <input type="number" name="WeightGrams" min="0" required>
    <br><br>

    <label>Length (cm):</label><br>
    <input type="number" name="LengthCm" min="0"
           step="0.01" required>
    <br><br>

    <label>Width (cm):</label><br>
    <input type="number" name="WidthCm" min="0"
           step="0.01" required>
    <br><br>

    <label>Height (cm):</label><br>
    <input type="number" name="HeightCm" min="0"
           step="0.01" required>
    <br><br>

    <label>Insured Value:</label><br>
    <input type="number" name="InsuredValue" min="0"
           step="0.01" required>
    <br><br>

    <label>Status:</label><br>
    <select name="Status" required>
        <?php foreach ($statuses as $status) { ?>
            <option value="<?php echo $status; ?>"
                <?php if ($status == 'Draft') echo 'selected'; ?>>
                <?php echo $status; ?>
            </option>
        <?php } ?>
    </select>
    <br><br>

    <label>Suspension Reason:</label><br>
    <textarea name="SuspensionReason"></textarea>
    <br><br>

    <button type="submit">Save Listing</button>
    <a href="index.php">Cancel</a>

</form>

</body>
</html>
