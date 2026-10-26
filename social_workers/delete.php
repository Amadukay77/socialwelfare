<?php

session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

/* Check worker ID */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$worker_id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Check whether this worker is assigned to any cases
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("
    SELECT COUNT(*) AS total_cases
    FROM cases
    WHERE assigned_to = ?
");

$check->bind_param("i", $worker_id);
$check->execute();

$result = $check->get_result();
$data = $result->fetch_assoc();

$check->close();

$total_cases = (int) $data["total_cases"];


/*
|--------------------------------------------------------------------------
| If worker is assigned to cases, do not delete
|--------------------------------------------------------------------------
*/

if ($total_cases > 0) {

    header(
        "Location: index.php?error=assigned"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Delete worker
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    DELETE FROM social_workers
    WHERE worker_id = ?
");

$stmt->bind_param("i", $worker_id);

if ($stmt->execute()) {

    header("Location: index.php?success=deleted");
    exit;

} else {

    header("Location: index.php?error=delete");
    exit;
}

$stmt->close();

?>