 
<?php
session_start();

require_once "../includes/db.php";
require_once "../includes/permissions.php";

// Only Administrators can delete client records
require_admin();

/* Check client ID */
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$client_id = (int) $_GET["id"];

/* Check that client exists */
$stmt = $conn->prepare(
    "SELECT client_id, client_number, full_name
     FROM clients WHERE client_id = ?"
);
$stmt->bind_param("i", $client_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$client = $result->fetch_assoc();

/* Delete client */
$delete = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
$delete->bind_param("i", $client_id);

if ($delete->execute()) {

    header("Location: index.php?deleted=1");
    exit;

} else {

    echo "Error deleting client: " . htmlspecialchars($conn->error);
}
?>