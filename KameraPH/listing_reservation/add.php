<?php
include "../config/database.php";

$message = "";

$listings = $conn->query(
    "SELECT ListingID, Title FROM product_listing ORDER BY ListingID"
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $listingID = (int) $_POST['ListingID'];
    $orderID = (int) $_POST['OrderID'];
    $quantity = (int) $_POST['Quantity'];

    $reservedAt = str_replace("T", " ", $_POST['ReservedAt']) . ":00";
    $expiresAt = str_replace("T", " ", $_POST['ExpiresAt']) . ":00";

    $status = $_POST['Status'];

    if ($quantity < 1) {
        $message = "Quantity must be at least 1.";
    } elseif (strtotime($expiresAt) <= strtotime($reservedAt)) {
        $message = "Expiration must be later than the reservation date.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO listing_reservation
            (ListingID, OrderID, Quantity, ReservedAt, ExpiresAt, Status)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iiisss",
            $listingID,
            $orderID,
            $quantity,
            $reservedAt,
            $expiresAt,
            $status
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $message = "Error adding reservation: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Listing Reservation</title>
</head>
<body>

<h2>Add Listing Reservation</h2>

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

    <label>Order ID:</label><br>
    <input type="number" name="OrderID" min="1" required>

    <br><br>

    <label>Quantity:</label><br>
    <input type="number" name="Quantity" min="1" required>

    <br><br>

    <label>Reserved At:</label><br>
    <input type="datetime-local" name="ReservedAt"
           value="<?php echo date('Y-m-d\TH:i'); ?>" required>

    <br><br>

    <label>Expires At:</label><br>
    <input type="datetime-local" name="ExpiresAt" required>

    <br><br>

    <label>Status:</label><br>
    <select name="Status" required>
        <option value="Reserved">Reserved</option>
        <option value="Completed">Completed</option>
        <option value="Cancelled">Cancelled</option>
        <option value="Expired">Expired</option>
    </select>

    <br><br>

    <button type="submit">Save Reservation</button>

</form>

<br>
<a href="index.php">Back to Reservations</a>

</body>
</html>
