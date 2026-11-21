<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['CategoryID'])) {
    $id = (int)$_POST['CategoryID'];

    // Get name for friendly message
    $catName = "Category #{$id}";
    $stmtName = $conn->prepare("SELECT CategoryName FROM category WHERE CategoryID = ?");
    $stmtName->bind_param("i", $id);
    $stmtName->execute();
    $res = $stmtName->get_result();
    if ($row = $res->fetch_assoc()) {
        $catName = $row['CategoryName'];
    }
    $stmtName->close();

    $sql = "DELETE FROM category WHERE CategoryID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    try {
        if ($stmt->execute()) {
            flash('success', "Category '{$catName}' was deleted successfully.");
        } else {
            flash('danger', "Error deleting category: " . $stmt->error);
        }
    } catch (mysqli_sql_exception $e) {
        if ($stmt->errno == 1451 || $e->getCode() == 1451) {
            flash('danger', "Cannot delete category '{$catName}' because it is linked to subcategories or camera models. Please reassign or remove them first.");
        } else {
            flash('danger', "Database error: " . $e->getMessage());
        }
    }
    $stmt->close();
}

redirect('category/index.php');
