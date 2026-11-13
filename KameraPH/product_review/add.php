<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
include "../config/database.php";

$message = "";

$models = $conn->query(
    "SELECT ModelID, ModelName FROM product_model ORDER BY ModelName"
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $orderItemID = (int) $_POST['OrderItemID'];
    $modelID = (int) $_POST['ModelID'];
    $reviewerUserID = (int) $_POST['ReviewerUserID'];
    $ratingValue = (int) $_POST['RatingValue'];
    $reviewText = trim($_POST['ReviewText']);

    if ($ratingValue < 1 || $ratingValue > 5) {
        $message = "Rating must be between 1 and 5.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO product_review
            (OrderItemID, ModelID, ReviewerUserID,
             RatingValue, ReviewText, CreatedAt)
            VALUES (?, ?, ?, ?, ?, NOW())"
        );

        $stmt->bind_param(
            "iiiis",
            $orderItemID,
            $modelID,
            $reviewerUserID,
            $ratingValue,
            $reviewText
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $message = "Error adding review: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product Review</title>
</head>
<body>

<h2>Add Product Review</h2>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Order Item ID:</label><br>
    <input type="number" name="OrderItemID" min="1" required>

    <br><br>

    <label>Camera Model:</label><br>
    <select name="ModelID" required>
        <option value="">Select Camera Model</option>

        <?php while ($model = $models->fetch_assoc()) { ?>
            <option value="<?php echo $model['ModelID']; ?>">
                <?php echo htmlspecialchars($model['ModelName']); ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Reviewer User ID:</label><br>
    <input type="number" name="ReviewerUserID" min="1" required>

    <br><br>

    <label>Rating:</label><br>
    <select name="RatingValue" required>
        <option value="">Select Rating</option>
        <option value="1">1 - Poor</option>
        <option value="2">2 - Fair</option>
        <option value="3">3 - Good</option>
        <option value="4">4 - Very Good</option>
        <option value="5">5 - Excellent</option>
    </select>

    <br><br>

    <label>Review:</label><br>
    <textarea name="ReviewText" rows="4" cols="40"
              required></textarea>

    <br><br>

    <button type="submit">Save Review</button>

</form>

<br>
<a href="index.php">Back to Product Reviews</a>

</body>
</html>
