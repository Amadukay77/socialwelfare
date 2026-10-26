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

$search = trim($_GET["search"] ?? "");


/* =========================
   GET DOCUMENTS
========================= */

if ($search !== "") {

    $stmt = $conn->prepare("
        SELECT
            d.*,
            c.client_number,
            c.full_name AS client_name,
            ca.case_number,
            u.username
        FROM documents d

        INNER JOIN clients c
            ON d.client_id = c.client_id

        LEFT JOIN cases ca
            ON d.case_id = ca.case_id

        LEFT JOIN users u
            ON d.uploaded_by = u.user_id

        WHERE
            d.document_name LIKE ?
            OR d.document_type LIKE ?
            OR c.full_name LIKE ?
            OR c.client_number LIKE ?
            OR ca.case_number LIKE ?

        ORDER BY d.uploaded_at DESC
    ");

    $search_value = "%" . $search . "%";

    $stmt->bind_param(
        "sssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );

    $stmt->execute();

    $documents = $stmt->get_result();

} else {

    $documents = $conn->query("
        SELECT
            d.*,
            c.client_number,
            c.full_name AS client_name,
            ca.case_number,
            u.username

        FROM documents d

        INNER JOIN clients c
            ON d.client_id = c.client_id

        LEFT JOIN cases ca
            ON d.case_id = ca.case_id

        LEFT JOIN users u
            ON d.uploaded_by = u.user_id

        ORDER BY d.uploaded_at DESC
    ");
}


/* =========================
   TOTAL DOCUMENTS
========================= */

$total_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM documents
");

$total_documents = $total_result->fetch_assoc()["total"];

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Documents - Social Welfare System</title>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f5f7;
    color: #222;
}


/* SIDEBAR */

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

.sidebar a {
    display: flex;
    align-items: center;
    gap: 14px;
    color: #ddd;
    text-decoration: none;
    padding: 14px 20px;
    font-size: 14px;
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
    width: 22px;
    text-align: center;
}


/* ICON COLORS */

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
    color: #26a69a;
}

.icon-settings {
    color: #bdbdbd;
}

.icon-logout {
    color: #ef5350;
}


/* MAIN */

.main {
    margin-left: 250px;
    padding: 30px;
}


/* HEADER */

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.page-header h1 {
    margin: 0;
    font-size: 26px;
}

.page-header p {
    margin: 7px 0 0;
    color: #777;
}


/* ADD BUTTON */

.add-btn {
    background: #198754;
    color: white;
    text-decoration: none;
    padding: 12px 18px;
    border-radius: 6px;
    font-weight: bold;
}

.add-btn:hover {
    background: #146c43;
}


/* STAT CARD */

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    margin-bottom: 20px;
}

.stat-card i {
    font-size: 28px;
    color: #ab47bc;
    margin-right: 10px;
}

.stat-number {
    font-size: 25px;
    font-weight: bold;
}


/* SEARCH */

.search-box {
    background: white;
    padding: 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.search-form {
    display: flex;
    gap: 10px;
}

.search-form input {
    flex: 1;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}

.search-form button {
    background: #171717;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    cursor: pointer;
}

.clear-btn {
    display: flex;
    align-items: center;
    background: #777;
    color: white;
    text-decoration: none;
    padding: 12px 18px;
    border-radius: 6px;
}


/* TABLE */

.card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    overflow: hidden;
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #171717;
    color: white;
    padding: 14px;
    text-align: left;
    font-size: 13px;
}

td {
    padding: 14px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

tr:hover {
    background: #fafafa;
}


/* DOCUMENT ICON */

.document-icon {
    width: 38px;
    height: 38px;
    border-radius: 6px;
    background: #f3e5f5;
    color: #8e24aa;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
}

.document-name {
    font-weight: bold;
}


/* BADGE */

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    background: #e8def8;
    color: #6a1b9a;
    font-size: 12px;
    font-weight: bold;
}


/* ACTIONS */

.actions {
    display: flex;
    gap: 6px;
}

.action-btn {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    color: white;
    text-decoration: none;
}

.view-btn {
    background: #1976d2;
}

.edit-btn {
    background: #f9a825;
}

.delete-btn {
    background: #e53935;
}

.action-btn:hover {
    opacity: .85;
}


/* EMPTY */

.empty {
    text-align: center;
    padding: 50px;
    color: #777;
}

.empty i {
    font-size: 45px;
    color: #bbb;
    margin-bottom: 15px;
}


/* RESPONSIVE */

@media(max-width: 800px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
        padding: 20px;
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
        padding: 15px;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .search-form {
        flex-direction: column;
    }

}

</style>

</head>

<body>


<!-- SIDEBAR -->

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

<a href="../cases/index.php">
<i class="fas fa-folder-open icon-cases"></i>
Cases
</a>

<a href="index.php" class="active">
<i class="fas fa-file-alt icon-documents"></i>
Documents
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


<!-- MAIN -->

<div class="main">


<div class="page-header">

<div>

<h1>
<i class="fas fa-file-alt"></i>
Documents
</h1>

<p>
Manage and store social welfare documents
</p>

</div>

<a href="add.php" class="add-btn">
<i class="fas fa-upload"></i>
Upload Document
</a>

</div>


<!-- STAT -->

<div class="stat-card">

<i class="fas fa-folder"></i>

<span>Total Documents</span>

<div class="stat-number">
<?php echo $total_documents; ?>
</div>

</div>


<!-- SEARCH -->

<div class="search-box">

<form method="GET" class="search-form">

<input
type="text"
name="search"
placeholder="Search document, client or case..."
value="<?php echo htmlspecialchars($search); ?>"
>

<button type="submit">
<i class="fas fa-search"></i>
Search
</button>

<?php if ($search !== ""): ?>

<a href="index.php" class="clear-btn">
Clear
</a>

<?php endif; ?>

</form>

</div>


<!-- TABLE -->

<div class="card">

<div class="table-container">

<table>

<thead>

<tr>

<th>Document</th>

<th>Type</th>

<th>Client</th>

<th>Case</th>

<th>Uploaded By</th>

<th>Date</th>

<th>Actions</th>

</tr>

</thead>

<tbody>


<?php if ($documents && $documents->num_rows > 0): ?>


<?php while ($document = $documents->fetch_assoc()): ?>

<tr>


<td>

<div>

<span class="document-icon">

<i class="fas fa-file"></i>

</span>

<span class="document-name">

<?php
echo htmlspecialchars(
    $document["document_name"]
);
?>

</span>

</div>

</td>


<td>

<span class="badge">

<?php

echo htmlspecialchars(
    $document["document_type"]
    ?: "Other"
);

?>

</span>

</td>


<td>

<strong>
<?php
echo htmlspecialchars(
    $document["client_name"]
);
?>
</strong>

<br>

<small>
<?php
echo htmlspecialchars(
    $document["client_number"]
);
?>
</small>

</td>


<td>

<?php

echo !empty($document["case_number"])
    ? htmlspecialchars(
        $document["case_number"]
      )
    : "No Case";

?>

</td>


<td>

<?php

echo !empty($document["username"])
    ? htmlspecialchars(
        $document["username"]
      )
    : "Unknown";

?>

</td>


<td>

<?php

echo date(
    "d M Y",
    strtotime(
        $document["uploaded_at"]
    )
);

?>

</td>


<td>

<div class="actions">

<a
href="view.php?id=<?php echo $document["document_id"]; ?>"
class="action-btn view-btn"
title="View"
>

<i class="fas fa-eye"></i>

</a>


<a
href="edit.php?id=<?php echo $document["document_id"]; ?>"
class="action-btn edit-btn"
title="Edit"
>

<i class="fas fa-edit"></i>

</a>


<a
href="delete.php?id=<?php echo $document["document_id"]; ?>"
class="action-btn delete-btn"
title="Delete"
onclick="return confirm('Are you sure you want to delete this document?');"
>

<i class="fas fa-trash"></i>

</a>

</div>

</td>


</tr>

<?php endwhile; ?>


<?php else: ?>

<tr>

<td colspan="7">

<div class="empty">

<i class="fas fa-folder-open"></i>

<h3>No Documents Found</h3>

<p>
There are currently no documents in the system.
</p>

<a href="add.php" class="add-btn">
Upload First Document
</a>

</div>

</td>

</tr>

<?php endif; ?>


</tbody>

</table>

</div>

</div>


</div>

</body>

</html>