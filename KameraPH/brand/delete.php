<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('admin'); // Admin login required

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['BrandID'])) {
    $id = (int)$_POST['BrandID'];

    // Get brand name first for friendly messaging
    $brandName = "Brand #{$id}";
    $stmtName = $conn->prepare("SELECT BrandName FROM brand WHERE BrandID = ?");
    $stmtName->bind_param("i", $id);
    $stmtName->execute();
    $res = $stmtName->get_result();
    if ($row = $res->fetch_assoc()) {
        $brandName = $row['BrandName'];
    }
    $stmtName->close();

    $sql = "DELETE FROM brand WHERE BrandID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    try {
        if ($stmt->execute()) {
            flash('success', "Brand '{$brandName}' was deleted successfully.");
        } else {
            flash('danger', "Error deleting brand: " . $stmt->error);
        }
    } catch (mysqli_sql_exception $e) {
        if ($stmt->errno == 1451 || $e->getCode() == 1451) {
            flash('danger', "Cannot delete brand '{$brandName}' because it is currently linked to camera models. Please reassign or delete those models first.");
        } else {
            flash('danger', "Database error: " . $e->getMessage());
        }
    }
    $stmt->close();
}

redirect('brand/index.php');
