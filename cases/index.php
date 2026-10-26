 <?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

/* =========================
   SEARCH
========================= */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}


/* =========================
   GET CASES
========================= */

$sql = "
    SELECT
        c.case_id,
        c.case_number,
        c.case_type,
        c.description,
        c.priority,
        c.status,
        c.date_opened,

        cl.client_number,
        cl.full_name AS client_name,

        sw.worker_id,
        sw.worker_number,
        sw.full_name AS worker_name,
        sw.position AS worker_position

    FROM cases c

    INNER JOIN clients cl
        ON c.client_id = cl.client_id

    LEFT JOIN social_workers sw
        ON c.assigned_to = sw.worker_id
";


if (!empty($search)) {

    $sql .= "
        WHERE
            c.case_number LIKE ?
            OR c.case_type LIKE ?
            OR c.status LIKE ?
            OR c.priority LIKE ?
            OR cl.full_name LIKE ?
            OR cl.client_number LIKE ?
            OR sw.full_name LIKE ?
            OR sw.worker_number LIKE ?
            OR sw.position LIKE ?
    ";

    $sql .= " ORDER BY c.case_id DESC";

    $stmt = $conn->prepare($sql);

    $searchTerm = "%" . $search . "%";

    $stmt->bind_param(
        "sssssssss",
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    );

    $stmt->execute();

    $cases = $stmt->get_result();

} else {

    $sql .= " ORDER BY c.case_id DESC";

    $cases = $conn->query($sql);
}


/* =========================
   SUCCESS / ERROR MESSAGE
========================= */

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Cases - Social Welfare System</title>

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


/* =========================
   SIDEBAR
========================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 250px;
    height: 100vh;

    background: #171717;

    color: white;

    padding-top: 20px;

    overflow-y: auto;
}

.sidebar h2 {

    text-align: center;

    margin: 0 0 30px;

    font-size: 20px;
}

.sidebar h2 i {

    margin-right: 7px;
}

.sidebar a {

    display: flex;

    align-items: center;

    gap: 14px;

    color: #ddd;

    text-decoration: none;

    padding: 14px 20px;

    font-size: 15px;

    transition: 0.2s;
}

.sidebar a:hover {

    background: #242424;

    color: white;
}

.sidebar a.active {

    background: #242424;

    border-left: 4px solid #e53935;

    color: white;
}

.sidebar a i {

    width: 25px;

    text-align: center;

    font-size: 17px;
}


/* =========================
   SIDEBAR ICON COLORS
========================= */

.icon-dashboard {
    color: #42a5f5;
}

.icon-clients {
    color: #66bb6a;
}

.icon-cases {
    color: #ffa726;
}

.icon-documents {
    color: #ab47bc;
}

.icon-social-workers {
    color: #26c6da;
}

.icon-households {
    color: #ec407a;
}

.icon-activities {
    color: #ffee58;
}

.icon-appointments {
    color: #29b6f6;
}

.icon-reports {
    color: #8d6e63;
}

.icon-settings {
    color: #bdbdbd;
}

.icon-logout {
    color: #ef5350;
}


/* =========================
   MAIN
========================= */

.main {

    margin-left: 250px;

    padding: 30px;
}


/* =========================
   PAGE HEADER
========================= */

.page-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}

.page-header h1 {

    margin: 0;

    font-size: 25px;
}

.page-header h1 i {

    color: #e53935;

    margin-right: 8px;
}


/* =========================
   ADD BUTTON
========================= */

.add-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #198754;

    color: white;

    text-decoration: none;

    padding: 12px 18px;

    border-radius: 6px;

    font-weight: bold;

    transition: 0.2s;
}

.add-btn:hover {

    background: #146c43;
}


/* =========================
   ALERTS
========================= */

.alert {

    padding: 13px 16px;

    border-radius: 6px;

    margin-bottom: 20px;

    display: flex;

    align-items: center;

    gap: 10px;

    font-size: 14px;
}

.alert-success {

    background: #d1e7dd;

    color: #0f5132;
}

.alert-error {

    background: #f8d7da;

    color: #842029;
}


/* =========================
   SEARCH
========================= */

.search-card {

    background: white;

    padding: 20px;

    border-radius: 10px;

    box-shadow: 0 2px 8px rgba(0,0,0,0.07);

    margin-bottom: 20px;
}

.search-form {

    display: flex;

    gap: 10px;
}

.search-input {

    flex: 1;

    padding: 12px 14px;

    border: 1px solid #ccc;

    border-radius: 6px;

    font-size: 15px;
}

.search-input:focus {

    outline: none;

    border-color: #198754;
}

.search-btn {

    border: none;

    background: #171717;

    color: white;

    padding: 12px 20px;

    border-radius: 6px;

    cursor: pointer;

    font-weight: bold;
}

.search-btn:hover {

    background: #333;
}

.clear-btn {

    display: inline-flex;

    align-items: center;

    padding: 12px 16px;

    background: #e9ecef;

    color: #333;

    text-decoration: none;

    border-radius: 6px;
}


/* =========================
   TABLE CARD
========================= */

.table-card {

    background: white;

    border-radius: 10px;

    box-shadow: 0 2px 8px rgba(0,0,0,0.07);

    overflow: hidden;
}

.table-header {

    padding: 20px 22px;

    border-bottom: 1px solid #eee;

    display: flex;

    justify-content: space-between;

    align-items: center;
}

.table-header h3 {

    margin: 0;

    font-size: 18px;
}

.record-count {

    color: #777;

    font-size: 14px;
}


/* =========================
   TABLE
========================= */

.table-wrapper {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1100px;
}

thead {

    background: #171717;

    color: white;
}

th {

    padding: 14px;

    text-align: left;

    font-size: 13px;

    white-space: nowrap;
}

td {

    padding: 15px 14px;

    border-bottom: 1px solid #eee;

    font-size: 14px;

    vertical-align: middle;
}

tbody tr:hover {

    background: #fafafa;
}


/* =========================
   CASE NUMBER
========================= */

.case-number {

    font-weight: bold;

    color: #198754;
}


/* =========================
   CLIENT
========================= */

.client-name {

    font-weight: bold;

    margin-bottom: 4px;
}

.client-number {

    color: #777;

    font-size: 12px;
}


/* =========================
   SOCIAL WORKER
========================= */

.worker-name {

    font-weight: bold;

    color: #333;

    margin-bottom: 4px;
}

.worker-number {

    color: #777;

    font-size: 12px;

    margin-bottom: 3px;
}

.worker-position {

    color: #198754;

    font-size: 11px;

    margin-top: 3px;
}

.not-assigned {

    color: #999;

    font-style: italic;

    font-size: 13px;
}


/* =========================
   BADGES
========================= */

.badge {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

    white-space: nowrap;
}


/* PRIORITY */

.priority-low {

    background: #d1e7dd;

    color: #0f5132;
}

.priority-medium {

    background: #fff3cd;

    color: #664d03;
}

.priority-high {

    background: #ffe5d0;

    color: #984c0c;
}

.priority-urgent {

    background: #f8d7da;

    color: #842029;
}


/* STATUS */

.status-open {

    background: #cfe2ff;

    color: #084298;
}

.status-progress {

    background: #fff3cd;

    color: #664d03;
}

.status-pending {

    background: #e2e3e5;

    color: #41464b;
}

.status-closed {

    background: #d1e7dd;

    color: #0f5132;
}


/* =========================
   ACTIONS
========================= */

.actions {

    display: flex;

    gap: 6px;

    flex-wrap: wrap;
}

.action-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 5px;

    text-decoration: none;

    padding: 7px 10px;

    border-radius: 5px;

    font-size: 12px;

    color: white;
}

.view-btn {

    background: #1976d2;
}

.view-btn:hover {

    background: #125ca5;
}

.edit-btn {

    background: #198754;
}

.edit-btn:hover {

    background: #146c43;
}

.delete-btn {

    background: #e53935;
}

.delete-btn:hover {

    background: #c62828;
}


/* =========================
   EMPTY
========================= */

.empty-state {

    text-align: center;

    padding: 60px 20px;

    color: #777;
}

.empty-state i {

    font-size: 45px;

    color: #ccc;

    margin-bottom: 15px;
}

.empty-state h3 {

    margin: 5px 0 8px;

    color: #555;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 800px) {

    .sidebar {

        width: 210px;
    }

    .main {

        margin-left: 210px;

        padding: 20px;
    }

    .page-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }
}

@media(max-width: 600px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .main {

        margin-left: 0;
    }

    .search-form {

        flex-direction: column;
    }

    .search-btn,
    .clear-btn {

        width: 100%;

        justify-content: center;
    }
}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

<h2>

    <i class="fas fa-hands-helping"></i>

    Social Welfare

</h2>


<a href="../index.php">

    <i class="fas fa-tachometer-alt icon-dashboard"></i>

    Dashboard

</a>


<a href="../clients/index.php">

    <i class="fas fa-users icon-clients"></i>

    Clients

</a>


<a href="index.php" class="active">

    <i class="fas fa-folder-open icon-cases"></i>

    Cases

</a>


<a href="../documents/index.php">

    <i class="fas fa-file-alt icon-documents"></i>

    Documents

</a>


<a href="../social_workers/index.php">

    <i class="fas fa-user-tie icon-social-workers"></i>

    Social Workers

</a>


<a href="../households/index.php">

    <i class="fas fa-home icon-households"></i>

    Households

</a>


<a href="../activities/index.php">

    <i class="fas fa-tasks icon-activities"></i>

    Activities

</a>


<a href="../appointments/index.php">

    <i class="fas fa-calendar-alt icon-appointments"></i>

    Appointments

</a>


<a href="../reports/index.php">

    <i class="fas fa-chart-bar icon-reports"></i>

    Reports

</a>


<a href="../settings/index.php">

    <i class="fas fa-cog icon-settings"></i>

    Settings

</a>


<a href="../auth/logout.php">

    <i class="fas fa-sign-out-alt icon-logout"></i>

    Logout

</a>

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">


<!-- PAGE HEADER -->

<div class="page-header">

<h1>

<i class="fas fa-folder-open"></i>

Cases

</h1>


<a href="add.php" class="add-btn">

<i class="fas fa-plus"></i>

Add New Case

</a>

</div>


<!-- SUCCESS -->

<?php if (!empty($success)): ?>

<div class="alert alert-success">

<i class="fas fa-check-circle"></i>

<?php echo htmlspecialchars($success); ?>

</div>

<?php endif; ?>


<!-- ERROR -->

<?php if (!empty($error)): ?>

<div class="alert alert-error">

<i class="fas fa-exclamation-circle"></i>

<?php echo htmlspecialchars($error); ?>

</div>

<?php endif; ?>


<!-- SEARCH -->

<div class="search-card">

<form method="GET" class="search-form">

<input
type="text"
name="search"
class="search-input"
placeholder="Search case number, client, social worker, case type, status..."
value="<?php echo htmlspecialchars($search); ?>"
>

<button type="submit" class="search-btn">

<i class="fas fa-search"></i>

Search

</button>


<?php if (!empty($search)): ?>

<a href="index.php" class="clear-btn">

<i class="fas fa-times"></i>

Clear

</a>

<?php endif; ?>

</form>

</div>


<!-- TABLE -->

<div class="table-card">


<div class="table-header">

<h3>

<i class="fas fa-list"></i>

Case Records

</h3>


<div class="record-count">

<?php echo $cases->num_rows; ?> record(s)

</div>

</div>


<div class="table-wrapper">


<?php if ($cases->num_rows > 0): ?>

<table>

<thead>

<tr>

<th>Case No.</th>

<th>Client</th>

<th>Case Type</th>

<th>Social Worker</th>

<th>Priority</th>

<th>Status</th>

<th>Date Opened</th>

<th>Actions</th>

</tr>

</thead>


<tbody>


<?php while ($case = $cases->fetch_assoc()): ?>


<?php

/* =========================
   PRIORITY CLASS
========================= */

$priorityClass = "priority-medium";

if ($case["priority"] === "Low") {
    $priorityClass = "priority-low";
}

if ($case["priority"] === "High") {
    $priorityClass = "priority-high";
}

if ($case["priority"] === "Urgent") {
    $priorityClass = "priority-urgent";
}


/* =========================
   STATUS CLASS
========================= */

$statusClass = "status-open";

if ($case["status"] === "In Progress") {
    $statusClass = "status-progress";
}

if ($case["status"] === "Pending") {
    $statusClass = "status-pending";
}

if ($case["status"] === "Closed") {
    $statusClass = "status-closed";
}

?>


<tr>


<!-- =========================
     CASE NUMBER
========================= -->

<td>

<span class="case-number">

<?php echo htmlspecialchars($case["case_number"]); ?>

</span>

</td>


<!-- =========================
     CLIENT
========================= -->

<td>

<div class="client-name">

<?php echo htmlspecialchars($case["client_name"]); ?>

</div>

<div class="client-number">

<?php echo htmlspecialchars($case["client_number"]); ?>

</div>

</td>


<!-- =========================
     CASE TYPE
========================= -->

<td>

<?php echo htmlspecialchars($case["case_type"]); ?>

</td>


<!-- =========================
     SOCIAL WORKER
========================= -->

<td>

<?php if (!empty($case["worker_name"])): ?>

<div class="worker-name">

<?php echo htmlspecialchars($case["worker_name"]); ?>

</div>

<div class="worker-number">

<?php echo htmlspecialchars($case["worker_number"]); ?>

</div>

<?php if (!empty($case["worker_position"])): ?>

<div class="worker-position">

<?php echo htmlspecialchars($case["worker_position"]); ?>

</div>

<?php endif; ?>

<?php else: ?>

<span class="not-assigned">

Not Assigned

</span>

<?php endif; ?>

</td>


<!-- =========================
     PRIORITY
========================= -->

<td>

<span class="badge <?php echo $priorityClass; ?>">

<?php echo htmlspecialchars($case["priority"]); ?>

</span>

</td>


<!-- =========================
     STATUS
========================= -->

<td>

<span class="badge <?php echo $statusClass; ?>">

<?php echo htmlspecialchars($case["status"]); ?>

</span>

</td>


<!-- =========================
     DATE
========================= -->

<td>

<?php echo htmlspecialchars($case["date_opened"]); ?>

</td>


<!-- =========================
     ACTIONS
========================= -->

<td>

<div class="actions">


<a
href="view.php?id=<?php echo $case["case_id"]; ?>"
class="action-btn view-btn"
title="View Case"
>

<i class="fas fa-eye"></i>

View

</a>


<a
href="edit.php?id=<?php echo $case["case_id"]; ?>"
class="action-btn edit-btn"
title="Edit Case"
>

<i class="fas fa-edit"></i>

Edit

</a>


<a
href="delete.php?id=<?php echo $case["case_id"]; ?>"
class="action-btn delete-btn"
title="Delete Case"
onclick="return confirm('Are you sure you want to delete this case? This action cannot be undone.');"
>

<i class="fas fa-trash-alt"></i>

Delete

</a>


</div>

</td>


</tr>


<?php endwhile; ?>


</tbody>

</table>


<?php else: ?>


<div class="empty-state">

<i class="fas fa-folder-open"></i>

<h3>No Case Records Found</h3>

<p>

<?php if (!empty($search)): ?>

No cases matched your search.

<?php else: ?>

There are currently no case records.

<?php endif; ?>

</p>


<a href="add.php" class="add-btn">

<i class="fas fa-plus"></i>

Add New Case

</a>

</div>


<?php endif; ?>


</div>

</div>


</div>

</body>

</html>