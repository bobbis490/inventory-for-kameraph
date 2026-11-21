<?php
require_once __DIR__ . '/../config/database.php';

$redirectCategory = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['SpecDefID'])) {
    $id = (int)$_POST['SpecDefID'];

    $specName = "Specification #{$id}";
    $stmtName = $conn->prepare("SELECT SpecName, CategoryID FROM spec_definition WHERE SpecDefID = ?");
    $stmtName->bind_param("i", $id);
    $stmtName->execute();
    $res = $stmtName->get_result();
    if ($row = $res->fetch_assoc()) {
        $specName = $row['SpecName'];
        $redirectCategory = $row['CategoryID'];
    }
    $stmtName->close();

    $sql = "DELETE FROM spec_definition WHERE SpecDefID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    try {
        if ($stmt->execute()) {
            flash('success', "Specification field '{$specName}' was deleted successfully.");
        } else {
            flash('danger', "Error deleting specification: " . $stmt->error);
        }
    } catch (mysqli_sql_exception $e) {
        if ($stmt->errno == 1451 || $e->getCode() == 1451) {
            flash('danger', "Cannot delete specification '{$specName}' because it is actively used in product listing specifications. Remove those specifications first.");
        } else {
            flash('danger', "Database error: " . $e->getMessage());
        }
    }
    $stmt->close();
}

$url = ($redirectCategory > 0) ? "spec_definition/index.php?category_id={$redirectCategory}" : "spec_definition/index.php";
redirect($url);

