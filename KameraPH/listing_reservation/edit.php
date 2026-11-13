<?php
include "../config/database.php";

$message = "";

if (!isset($_GET['id'])) {
    die("Reservation ID is missing.");
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare(
    "SELECT * FROM listing_reservation WHERE ReservationID = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$reservation = $result->fetch_assoc();
$stmt->close();

if (!$reservation) {
    die("Reservation not found.");
}

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
            "UPDATE listing_reservation
             SET ListingID = ?,
                 OrderID = ?,
                 Quantity = ?,
                 ReservedAt = ?,
                 ExpiresAt = ?,
                 Status = ?
             WHERE ReservationID = ?"
        );

        $stmt->bind_param(
            "iiisssi",
            $listingID,
            $orderID,
            $quantity,
            $reservedAt,
            $expiresAt,
            $status,
            $id
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $message = "Error updating reservation: " . $stmt->error;
        }

        $stmt->close();
    }

    $reservation['ListingID'] = $listingID;
    $reservation['OrderID'] = $orderID;
    $reservation['Quantity'] = $quantity;
    $reservation['ReservedAt'] = $reservedAt;
    $reservation['ExpiresAt'] = $expiresAt;
    $reservation['Status'] = $status;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Listing Reservation</title>
</head>
<body>

<h2>Edit Listing Reservation</h2>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Product Listing:</label><br>

    <select name="ListingID" required>
        <?php while ($listing = $listings->fetch_assoc()) { ?>
            <option value="<?php echo $listing['ListingID']; ?>"
                <?php echo ($listing['ListingID'] == $reservation['ListingID']) ? "selected" : ""; ?>>

                <?php echo $listing['ListingID']; ?> -
                <?php echo htmlspecialchars($listing['Title']); ?>

            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Order ID:</label><br>
    <input type="number" name="OrderID"
           value="<?php echo $reservation['OrderID']; ?>"
           min="1" required>

    <br><br>

    <label>Quantity:</label><br>
    <input type="number" name="Quantity"
           value="<?php echo $reservation['Quantity']; ?>"
           min="1" required>

    <br><br>

    <label>Reserved At:</label><br>
    <input type="datetime-local" name="ReservedAt"
           value="<?php echo date('Y-m-d\TH:i', strtotime($reservation['ReservedAt'])); ?>"
           required>

    <br><br>

    <label>Expires At:</label><br>
    <input type="datetime-local" name="ExpiresAt"
           value="<?php echo date('Y-m-d\TH:i', strtotime($reservation['ExpiresAt'])); ?>"
           required>

    <br><br>

    <label>Status:</label><br>

    <select name="Status" required>
        <?php
        $statuses = ["Reserved", "Completed", "Cancelled", "Expired"];

        foreach ($statuses as $status) {
        ?>
            <option value="<?php echo $status; ?>"
                <?php echo ($reservation['Status'] == $status) ? "selected" : ""; ?>>
                <?php echo $status; ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <button type="submit">Update Reservation</button>

</form>

<br>
<a href="index.php">Back to Reservations</a>

</body>
</html>
