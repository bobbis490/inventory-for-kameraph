<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ModelID'])) {
    $id = (int)$_POST['ModelID'];

    $modelName = "Model #{$id}";
    $stmtName = $conn->prepare("SELECT ModelName FROM product_model WHERE ModelID = ?");
    $stmtName->bind_param("i", $id);
    $stmtName->execute();
    $res = $stmtName->get_result();
    if ($row = $res->fetch_assoc()) {
        $modelName = $row['ModelName'];
    }
    $stmtName->close();

    $sql = "DELETE FROM product_model WHERE ModelID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    try {
        if ($stmt->execute()) {
            flash('success', "Camera model '{$modelName}' was deleted successfully.");
        } else {
            flash('danger', "Error deleting camera model: " . $stmt->error);
        }
    } catch (mysqli_sql_exception $e) {
        if ($stmt->errno == 1451 || $e->getCode() == 1451) {
            flash('danger', "Cannot delete camera model '{$modelName}' because it is linked to product listings or reviews. Please remove those records first.");
        } else {
            flash('danger', "Database error: " . $e->getMessage());
        }
    }
    $stmt->close();
}

redirect('product_model/index.php');
