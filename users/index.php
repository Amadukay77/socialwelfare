 ```php
<?php
session_start();
require_once "../includes/db.php";

// Require login.
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Only Administrators can manage users.
if (($_SESSION["role"] ?? "") !== "Administrator") {
    http_response_code(403);
    exit("Access denied. Only Administrators can manage system users.");
}

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Retrieve user accounts without password hashes.
$stmt = $conn->prepare(
    "SELECT user_id, full_name, username, role, status, created_at
     FROM users
     ORDER BY user_id DESC"
);

if (!$stmt || !$stmt->execute()) {
    http_response_code(500);
    exit("Unable to load users. Please check your database connection.");
}

$result = $stmt->get_result();
$users = [];
$totalUsers = 0;
$activeUsers = 0;
$inactiveUsers = 0;

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
    $totalUsers++;

    if ($row["status"] === "Active") {
        $activeUsers++;
    } else {
        $inactiveUsers++;
    }
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>User Management | Tonkolili District Social Welfare</title>

<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f5f7;
    color: #252525;
}

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 245px;
    height: 100vh;
    background: #171717;
    color: #fff;
    overflow-y: auto;
}

.brand {
    padding: 25px 18px;
    text-align: center;
    border-bottom: 1px solid #333;
}

.brand img {
    width: 62px;
    height: 62px;
    object-fit: contain;
    margin-bottom: 10px;
}

.brand h3 {
    margin: 0 0 5px;
    font-size: 17px;
}

.brand p {
    margin: 0;
    color: #aaa;
    font-size: 12px;
}

.menu-title {
    padding: 20px 20px 8px;
    color: #888;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 20px;
    color: #ccc;
    font-size: 14px;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: .2s;
}

.menu a i {
    width: 20px;
    text-align: center;
}

.menu a:hover {
    background: #252525;
    color: #fff;
}

.menu a.active {
    background: #252525;
    border-left-color: #e53935;
    color: #fff;
}

.dashboard-icon { color: #42a5f5; }
.clients-icon { color: #66bb6a; }
.cases-icon { color: #ffca28; }
.documents-icon { color: #ab47bc; }
.workers-icon { color: #29b6f6; }
.households-icon { color: #ffa726; }
.activities-icon { color: #26a69a; }
.appointments-icon { color: #ef5350; }
.reports-icon { color: #ec407a; }
.users-icon { color: #90a4ae; }
.settings-icon { color: #b0bec5; }
.logout-icon { color: #ef5350; }

.main {
    margin-left: 245px;
    min-height: 100vh;
}

.topbar {
    min-height: 70px;
    background: #fff;
    border-bottom: 1px solid #e5e5e5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 28px;
}

.topbar h2 {
    font-size: 20px;
    margin: 0;
}

.admin {
    font-size: 13px;
    color: #555;
}

.content {
    padding: 28px;
}

.heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

.heading h1 {
    margin: 0 0 7px;
    font-size: 25px;
}

.heading p {
    margin: 0;
    color: #777;
    font-size: 14px;
    line-height: 1.5;
}

.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 16px;
    border: 0;
    border-radius: 6px;
    background: #e53935;
    color: #fff;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
}

.button:hover {
    background: #c62828;
}

.button.secondary {
    background: #333;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.stat {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 9px;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.stat p {
    margin: 0 0 8px;
    color: #777;
    font-size: 12px;
}

.stat strong {
    font-size: 26px;
}

.stat i {
    width: 45px;
    height: 45px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    font-size: 19px;
    flex-shrink: 0;
}

.blue { color: #1976d2; background: #e3f2fd; }
.green { color: #388e3c; background: #e8f5e9; }
.orange { color: #ef6c00; background: #fff3e0; }

.notice {
    padding: 13px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 14px;
    line-height: 1.5;
}

.notice.success {
    background: #e8f5e9;
    color: #256c2b;
}

.notice.error {
    background: #ffebee;
    color: #b71c1c;
}

.panel {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 9px;
    overflow: hidden;
}

.panel-header {
    padding: 19px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.panel-header h3 {
    margin: 0;
    font-size: 16px;
}

.search {
    max-width: 320px;
    width: 100%;
    position: relative;
}

.search i {
    position: absolute;
    left: 12px;
    top: 12px;
    color: #888;
}

.search input {
    width: 100%;
    padding: 10px 12px 10px 35px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 13px;
}

.search input:focus {
    outline: 2px solid #e5393530;
    border-color: #e53935;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}

th, td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid #eee;
    font-size: 13px;
    vertical-align: middle;
}

th {
    background: #fafafa;
    color: #666;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: .4px;
    white-space: nowrap;
}

tr:hover td {
    background: #fcfcfc;
}

tr:last-child td {
    border-bottom: 0;
}

.person {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 175px;
}

.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: #eee;
    color: #444;
    flex-shrink: 0;
}

.person strong {
    display: block;
    margin-bottom: 4px;
}

.muted {
    color: #888;
    font-size: 11px;
}

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    white-space: nowrap;
}

.badge.active {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge.inactive {
    background: #ffebee;
    color: #c62828;
}

.badge.role {
    background: #e3f2fd;
    color: #1565c0;
}

.actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 10px;
    border-radius: 5px;
    background: #edf3ff;
    color: #174ea6;
    text-decoration: none;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
}

.action:hover {
    background: #dbe7ff;
}

.empty {
    padding: 35px;
    text-align: center;
    color: #888;
}

.empty i {
    display: block;
    font-size: 30px;
    margin-bottom: 10px;
}

.footer {
    text-align: center;
    padding: 24px;
    color: #888;
    font-size: 12px;
    line-height: 1.8;
}

@media (max-width: 1050px) {
    .sidebar { width: 210px; }
    .main { margin-left: 210px; }
    .content { padding: 20px; }
    .stats { grid-template-columns: 1fr; }
}

@media (max-width: 650px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main { margin-left: 0; }
    .topbar { padding: 16px; align-items: flex-start; flex-direction: column; }
    .content { padding: 14px; }
    .heading h1 { font-size: 22px; }
    .panel-header { align-items: stretch; }
    .search { max-width: none; }
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">
        <img src="../assets/images/sierra_leone_logo.png"
             alt="Sierra Leone emblem"
             onerror="this.style.display='none'">
        <h3>Social Welfare</h3>
        <p>Tonkolili District</p>
    </div>

    <div class="menu-title">Main Menu</div>
    <nav class="menu">
        <a href="../index.php">
            <i class="fas fa-tachometer-alt dashboard-icon"></i>Dashboard
        </a>
        <a href="../clients/index.php">
            <i class="fas fa-users clients-icon"></i>Clients
        </a>
        <a href="../cases/index.php">
            <i class="fas fa-folder-open cases-icon"></i>Cases
        </a>
        <a href="../documents/index.php">
            <i class="fas fa-file-alt documents-icon"></i>Documents
        </a>
        <a href="../social_workers/index.php">
            <i class="fas fa-user-tie workers-icon"></i>Social Workers
        </a>
        <a href="../households/index.php">
            <i class="fas fa-home households-icon"></i>Households
        </a>
        <a href="../activities/index.php">
            <i class="fas fa-calendar-check activities-icon"></i>Activities
        </a>
        <a href="../appointments/index.php">
            <i class="fas fa-calendar-alt appointments-icon"></i>Appointments
        </a>
    </nav>

    <div class="menu-title">Management</div>
    <nav class="menu">
        <a href="../reports/">
            <i class="fas fa-chart-bar reports-icon"></i>Reports
        </a>
        <a href="index.php" class="active">
            <i class="fas fa-user-cog users-icon"></i>Users
        </a>
        <a href="../settings/">
            <i class="fas fa-cog settings-icon"></i>Settings
        </a>
        <a href="../auth/logout.php">
            <i class="fas fa-sign-out-alt logout-icon"></i>Logout
        </a>
    </nav>
</aside>

<main class="main">
    <header class="topbar">
        <h2><i class="fas fa-user-shield"></i> User Management</h2>
        <div class="admin">
            <i class="fas fa-user-circle"></i>
            <?= e($_SESSION["full_name"] ?? "Administrator") ?>
        </div>
    </header>

    <section class="content">
        <div class="heading">
            <div>
                <h1>Users &amp; Staff</h1>
                <p>Create accounts and manage access to the records system.</p>
            </div>

            <a class="button" href="add.php">
                <i class="fas fa-user-plus"></i> Add User
            </a>
        </div>

        <?php if ($success !== ""): ?>
            <div class="notice success">
                <i class="fas fa-check-circle"></i>
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="notice error">
                <i class="fas fa-exclamation-circle"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat">
                <div>
                    <p>TOTAL USERS</p>
                    <strong><?= $totalUsers ?></strong>
                </div>
                <i class="fas fa-users blue"></i>
            </div>

            <div class="stat">
                <div>
                    <p>ACTIVE USERS</p>
                    <strong><?= $activeUsers ?></strong>
                </div>
                <i class="fas fa-user-check green"></i>
            </div>

            <div class="stat">
                <div>
                    <p>INACTIVE USERS</p>
                    <strong><?= $inactiveUsers ?></strong>
                </div>
                <i class="fas fa-user-slash orange"></i>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3><i class="fas fa-list"></i> Registered Users</h3>

                <div class="search">
                    <i class="fas fa-search"></i>
                    <input type="search"
                           id="userSearch"
                           placeholder="Search name, username or role..."
                           aria-label="Search users">
                </div>
            </div>

            <div class="table-wrap">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="person">
                                        <div class="avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <strong><?= e($user["full_name"]) ?></strong>
                                            <span class="muted">
                                                ID #<?= (int)$user["user_id"] ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td><?= e($user["username"]) ?></td>

                                <td>
                                    <span class="badge role">
                                        <?= e($user["role"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge <?= $user["status"] === "Active" ? "active" : "inactive" ?>">
                                        <?= e($user["status"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e(date("d M Y", strtotime($user["created_at"]))) ?>
                                </td>

                                <td>
                                    <div class="actions">
                                        <a class="action"
                                           href="edit.php?id=<?= (int)$user["user_id"] ?>"
                                           title="Edit this user">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty">
                                <i class="fas fa-users"></i>
                                No users found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer">
            Tonkolili District Social Welfare Records Management System
            <br>
            &copy; <?= date("Y") ?> All Rights Reserved
        </div>
    </section>
</main>

<script>
document.getElementById("userSearch").addEventListener("input", function () {
    const query = this.value.toLowerCase().trim();

    document.querySelectorAll("#usersTable tbody tr").forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(query)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
```