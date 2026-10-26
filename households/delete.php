<?php
session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$household_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($household_id <= 0) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Check Household Exists
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT household_id, household_name, household_number
    FROM households
    WHERE household_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $household_id);
$stmt->execute();

$household = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$household) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Delete Household
|--------------------------------------------------------------------------
|
| Because household_members uses ON DELETE CASCADE,
| members linked to this household will also be removed.
|
*/

$stmt = $conn->prepare("
    DELETE FROM households
    WHERE household_id = ?
");

$stmt->bind_param("i", $household_id);

if ($stmt->execute()) {

    $stmt->close();

    header(
        "Location: index.php?success=" .
        urlencode("Household deleted successfully.")
    );

    exit;

} else {

    $stmt->close();

    header(
        "Location: view.php?id=" .
        $household_id .
        "&error=" .
        urlencode("Unable to delete household.")
    );

    exit;
}
?>