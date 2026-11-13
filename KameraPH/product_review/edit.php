<?php
include "../config/database.php";

$message = "";

if (!isset($_GET['id'])) {
    die("Review ID is missing.");
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare(
    "SELECT * FROM product_review WHERE ReviewID = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$review = $result->fetch_assoc();
$stmt->close();

if (!$review) {
    die("Product review not found.");
}

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
            "UPDATE product_review
             SET OrderItemID = ?,
                 ModelID = ?,
                 ReviewerUserID = ?,
                 RatingValue = ?,
                 ReviewText = ?
             WHERE ReviewID = ?"
        );

        $stmt->bind_param(
            "iiiisi",
            $orderItemID,
            $modelID,
            $reviewerUserID,
            $ratingValue,
            $reviewText,
            $id
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $message = "Error updating review: " . $stmt->error;
        }

        $stmt->close();
    }

    // Keep the submitted values visible if validation fails.
    $review['OrderItemID'] = $orderItemID;
    $review['ModelID'] = $modelID;
    $review['ReviewerUserID'] = $reviewerUserID;
    $review['RatingValue'] = $ratingValue;
    $review['ReviewText'] = $reviewText;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product Review</title>
</head>
<body>

<h2>Edit Product Review</h2>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST">

    <label>Order Item ID:</label><br>
    <input type="number" name="OrderItemID"
           value="<?php echo $review['OrderItemID']; ?>"
           min="1" required>

    <br><br>

    <label>Camera Model:</label><br>
    <select name="ModelID" required>

        <?php while ($model = $models->fetch_assoc()) { ?>
            <option value="<?php echo $model['ModelID']; ?>"
                <?php echo ($model['ModelID'] == $review['ModelID']) ? "selected" : ""; ?>>
                <?php echo htmlspecialchars($model['ModelName']); ?>
            </option>
        <?php } ?>

    </select>

    <br><br>

    <label>Reviewer User ID:</label><br>
    <input type="number" name="ReviewerUserID"
           value="<?php echo $review['ReviewerUserID']; ?>"
           min="1" required>

    <br><br>

    <label>Rating:</label><br>
    <select name="RatingValue" required>
        <?php for ($i = 1; $i <= 5; $i++) { ?>
            <option value="<?php echo $i; ?>"
                <?php echo ($review['RatingValue'] == $i) ? "selected" : ""; ?>>
                <?php echo $i; ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <label>Review:</label><br>
    <textarea name="ReviewText" rows="4" cols="40"
              required><?php echo htmlspecialchars($review['ReviewText']); ?></textarea>

    <br><br>

    <button type="submit">Update Review</button>

</form>

<br>
<a href="index.php">Back to Product Reviews</a>

</body>
</html>
