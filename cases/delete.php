<?php

session_start();

require_once "../includes/permissions.php";
require_admin();

/* Check login */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

/* Check case ID */
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$case_id = (int) $_GET["id"];

/* Check that the case exists */
$stmt = $conn->prepare("
    SELECT case_id
    FROM cases
    WHERE case_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $case_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php?error=Case not found");
    exit;
}

$stmt->close();


/* Delete the case */
$stmt = $conn->prepare("
    DELETE FROM cases
    WHERE case_id = ?
");

$stmt->bind_param("i", $case_id);

if ($stmt->execute()) {

    $stmt->close();

    header("Location: index.php?success=Case deleted successfully");
    exit;

} else {

    $error = "Unable to delete case: " . $stmt->error;

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Delete Case - Social Welfare System</title>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f5f7;
    color: #222;
}

.container {
    max-width: 600px;
    margin: 100px auto;
    padding: 20px;
}

.card {
    background: white;
    padding: 35px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 3px 12px rgba(0,0,0,0.12);
}

.icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 20px;

    background: #fdecea;
    color: #e53935;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 30px;
}

h2 {
    margin-bottom: 10px;
}

p {
    color: #666;
    line-height: 1.6;
}

.error {
    background: #f8d7da;
    color: #842029;
    padding: 12px;
    border-radius: 6px;
    margin-top: 20px;
}

.back-btn {
    display: inline-block;
    margin-top: 20px;
    background: #171717;
    color: white;
    text-decoration: none;
    padding: 12px 20px;
    border-radius: 6px;
}

.back-btn:hover {
    background: #333;
}

</style>

</head>

<body>

<div class="container">

<div class="card">

<div class="icon">
    <i class="fas fa-exclamation-triangle"></i>
</div>

<h2>Unable to Delete Case</h2>

<p>
    <?php echo htmlspecialchars($error); ?>
</p>

<a href="index.php" class="back-btn">
    <i class="fas fa-arrow-left"></i>
    Back to Cases
</a>

</div>

</div>

</body>

</html>