<?php
require_once __DIR__ . '/../config/database.php';

$redirectModel = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    $stmtRev = $conn->prepare("SELECT ModelID FROM product_review WHERE ReviewID = ?");
    $stmtRev->bind_param("i", $id);
    $stmtRev->execute();
    $res = $stmtRev->get_result();
    if ($row = $res->fetch_assoc()) {
        $redirectModel = $row['ModelID'];
    }
    $stmtRev->close();

    $stmt = $conn->prepare("DELETE FROM product_review WHERE ReviewID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        flash('success', "Review #{$id} was deleted successfully.");
    } else {
        flash('danger', "Error deleting review: " . $stmt->error);
    }
    $stmt->close();
}

$url = ($redirectModel > 0) ? "product_review/index.php?model_id={$redirectModel}" : "product_review/index.php";
redirect($url);

