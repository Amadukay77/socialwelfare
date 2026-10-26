 <?php
session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

/* =========================================================
   STATISTICS
========================================================= */

$total_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM activities
");

$total_activities = $total_result->fetch_assoc()["total"] ?? 0;


$today_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM activities
    WHERE activity_date = CURDATE()
");

$today_activities = $today_result->fetch_assoc()["total"] ?? 0;


$month_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM activities
    WHERE YEAR(activity_date) = YEAR(CURDATE())
    AND MONTH(activity_date) = MONTH(CURDATE())
");

$month_activities = $month_result->fetch_assoc()["total"] ?? 0;


/* Current Year */

$year_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM activities
    WHERE YEAR(activity_date) = YEAR(CURDATE())
");

$year_activities = $year_result->fetch_assoc()["total"] ?? 0;


/* =========================================================
   ACTIVITIES
========================================================= */

if ($search !== "") {

    $stmt = $conn->prepare("
        SELECT
            a.activity_id,
            a.activity_number,
            a.activity_type,
            a.activity_date,
            a.location,
            a.description,

            c.full_name AS client_name,
            c.client_number,

            cs.case_number,

            sw.full_name AS worker_name

        FROM activities a

        LEFT JOIN clients c
            ON a.client_id = c.client_id

        LEFT JOIN cases cs
            ON a.case_id = cs.case_id

        LEFT JOIN social_workers sw
            ON a.worker_id = sw.worker_id

        WHERE
            a.activity_number LIKE ?
            OR a.activity_type LIKE ?
            OR a.location LIKE ?
            OR a.description LIKE ?
            OR c.full_name LIKE ?
            OR c.client_number LIKE ?
            OR cs.case_number LIKE ?
            OR sw.full_name LIKE ?

        ORDER BY
            a.activity_date DESC,
            a.activity_id DESC
    ");

    $like = "%" . $search . "%";

    $stmt->bind_param(
        "ssssssss",
        $like,
        $like,
        $like,
        $like,
        $like,
        $like,
        $like,
        $like
    );

    $stmt->execute();

    $activities = $stmt->get_result();

} else {

    $activities = $conn->query("
        SELECT
            a.activity_id,
            a.activity_number,
            a.activity_type,
            a.activity_date,
            a.location,
            a.description,

            c.full_name AS client_name,
            c.client_number,

            cs.case_number,

            sw.full_name AS worker_name

        FROM activities a

        LEFT JOIN clients c
            ON a.client_id = c.client_id

        LEFT JOIN cases cs
            ON a.case_id = cs.case_id

        LEFT JOIN social_workers sw
            ON a.worker_id = sw.worker_id

        ORDER BY
            a.activity_date DESC,
            a.activity_id DESC
    ");
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Activities - Tonkolili District Social Welfare</title>

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


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: #171717;
    color: white;
    overflow-y: auto;
}

.brand {
    padding: 22px 20px;
    border-bottom: 1px solid #333;
}

.brand h2 {
    margin: 0;
    font-size: 20px;
}

.brand p {
    margin: 6px 0 0;
    color: #aaa;
    font-size: 13px;
}

.menu-title {
    padding: 18px 20px 8px;
    color: #777;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 20px;
    color: #ccc;
    text-decoration: none;
    border-left: 3px solid transparent;
}

.sidebar a:hover {
    background: #222;
    color: white;
}

.sidebar a.active {
    background: #222;
    color: white;
    border-left-color: #e53935;
}

.sidebar a i {
    width: 20px;
    text-align: center;
}


/* Different sidebar icon colors */

.icon-dashboard {
    color: #4caf50;
}

.icon-clients {
    color: #2196f3;
}

.icon-cases {
    color: #ff9800;
}

.icon-documents {
    color: #9c27b0;
}

.icon-workers {
    color: #00bcd4;
}

.icon-households {
    color: #f44336;
}

.icon-activities {
    color: #8bc34a;
}

.icon-appointments {
    color: #ffc107;
}

.icon-reports {
    color: #03a9f4;
}

.icon-settings {
    color: #e91e63;
}

.icon-logout {
    color: #607d8b;
}


/* =========================================================
   MAIN
========================================================= */

.main {
    margin-left: 250px;
    min-height: 100vh;
}

.topbar {
    background: white;
    padding: 18px 30px;
    border-bottom: 1px solid #ddd;

    display: flex;
    justify-content: space-between;
    align-items: center;
}

.topbar h1 {
    margin: 0;
    font-size: 22px;
}

.admin {
    color: #555;
    font-size: 14px;
}

.content {
    padding: 30px;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-bottom: 25px;
    gap: 15px;
}

.page-header h2 {
    margin: 0 0 6px;
    font-size: 26px;
}

.page-header p {
    margin: 0;
    color: #777;
}


/* =========================================================
   BUTTONS
========================================================= */

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 8px;

    padding: 11px 16px;

    border-radius: 6px;

    text-decoration: none;

    border: none;

    cursor: pointer;

    font-size: 14px;
}

.btn-primary {
    background: #e53935;
    color: white;
}

.btn-primary:hover {
    background: #c62828;
}


/* =========================================================
   STATISTICS
========================================================= */

.stats {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 20px;

    margin-bottom: 25px;
}

.stat-card {
    background: white;

    border-radius: 8px;

    padding: 20px;

    box-shadow:
        0 2px 8px rgba(0,0,0,0.06);

    display: flex;

    align-items: center;

    gap: 16px;
}

.stat-icon {
    width: 50px;
    height: 50px;

    border-radius: 8px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    flex-shrink: 0;
}

.stat-icon.total {
    background: #e3f2fd;
    color: #1976d2;
}

.stat-icon.today {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-icon.month {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.year {
    background: #f3e5f5;
    color: #8e24aa;
}

.stat-card h3 {
    margin: 0 0 5px;

    font-size: 25px;
}

.stat-card p {
    margin: 0;

    color: #777;

    font-size: 13px;
}


/* =========================================================
   SEARCH
========================================================= */

.search-box {
    background: white;

    padding: 18px;

    border-radius: 8px;

    margin-bottom: 20px;

    box-shadow:
        0 2px 8px rgba(0,0,0,0.06);
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

    outline: none;
}

.search-form input:focus {
    border-color: #e53935;
}

.search-btn {
    background: #171717;
    color: white;
}

.search-btn:hover {
    background: #333;
}

.clear-btn {
    background: #eee;
    color: #333;
}


/* =========================================================
   TABLE
========================================================= */

.table-card {
    background: white;

    border-radius: 8px;

    box-shadow:
        0 2px 8px rgba(0,0,0,0.06);

    overflow: hidden;
}

.table-header {
    padding: 18px 20px;

    border-bottom: 1px solid #eee;
}

.table-header h3 {
    margin: 0;

    font-size: 17px;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;
}

th,
td {
    padding: 14px 16px;

    border-bottom: 1px solid #eee;

    text-align: left;

    white-space: nowrap;

    font-size: 13px;
}

th {
    background: #fafafa;

    color: #555;

    font-size: 12px;

    text-transform: uppercase;
}

tr:hover td {
    background: #fafafa;
}


/* =========================================================
   ACTIVITY DATA
========================================================= */

.activity-number {
    font-weight: bold;

    color: #333;
}

.type-badge {
    display: inline-block;

    padding: 5px 9px;

    border-radius: 15px;

    background: #e3f2fd;

    color: #1976d2;

    font-size: 11px;
}

.description {
    max-width: 250px;

    overflow: hidden;

    text-overflow: ellipsis;
}

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

    text-decoration: none;
}

.view-btn {
    background: #e3f2fd;
    color: #1976d2;
}

.edit-btn {
    background: #fff3e0;
    color: #ef6c00;
}

.delete-btn {
    background: #ffebee;
    color: #d32f2f;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty {
    padding: 50px 20px;

    text-align: center;

    color: #777;
}

.empty i {
    font-size: 40px;

    margin-bottom: 15px;

    color: #aaa;
}

.empty h3 {
    margin: 10px 0;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 900px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
    }

}

@media (max-width: 700px) {

    .sidebar {
        position: relative;

        width: 100%;

        height: auto;
    }

    .main {
        margin-left: 0;
    }

    .topbar {
        padding: 15px 20px;
    }

    .content {
        padding: 20px;
    }

    .page-header {
        flex-direction: column;

        align-items: flex-start;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .search-form {
        flex-direction: column;
    }

}

</style>

</head>

<body>


<!-- =====================================================
     SIDEBAR
====================================================== -->

<div class="sidebar">

    <div class="brand">

        <h2>
            <i class="fas fa-hands-helping"></i>
            Social Welfare
        </h2>

        <p>
            Tonkolili District
        </p>

    </div>


    <div class="menu-title">
        Main Menu
    </div>


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


    <a href="../documents/index.php">

        <i class="fas fa-file-alt icon-documents"></i>

        Documents

    </a>


    <a href="../social_workers/index.php">

        <i class="fas fa-user-tie icon-workers"></i>

        Social Workers

    </a>


    <a href="../households/index.php">

        <i class="fas fa-home icon-households"></i>

        Households

    </a>


    <a href="index.php" class="active">

        <i class="fas fa-calendar-check icon-activities"></i>

        Activities

    </a>


    <a href="../appointments/index.php">

        <i class="fas fa-calendar-alt icon-appointments"></i>

        Appointments

    </a>


    <div class="menu-title">
        Management
    </div>


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


<!-- =====================================================
     MAIN
====================================================== -->

<div class="main">


    <div class="topbar">

        <h1>
            Activities
        </h1>


        <div class="admin">

            <i class="fas fa-user-circle"></i>

            <?php

            echo htmlspecialchars(
                $_SESSION["full_name"]
                ?? "System Administrator"
            );

            ?>

        </div>

    </div>


    <div class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <h2>
                    Activities Management
                </h2>

                <p>
                    Record and manage social welfare activities.
                </p>

            </div>


            <a
                href="add.php"
                class="btn btn-primary"
            >

                <i class="fas fa-plus"></i>

                Add Activity

            </a>

        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="stats">


            <!-- TOTAL -->

            <div class="stat-card">

                <div class="stat-icon total">

                    <i class="fas fa-tasks"></i>

                </div>

                <div>

                    <h3>
                        <?php echo $total_activities; ?>
                    </h3>

                    <p>
                        Total Activities
                    </p>

                </div>

            </div>


            <!-- TODAY -->

            <div class="stat-card">

                <div class="stat-icon today">

                    <i class="fas fa-calendar-day"></i>

                </div>

                <div>

                    <h3>
                        <?php echo $today_activities; ?>
                    </h3>

                    <p>
                        Today's Activities
                    </p>

                </div>

            </div>


            <!-- MONTH -->

            <div class="stat-card">

                <div class="stat-icon month">

                    <i class="fas fa-calendar-alt"></i>

                </div>

                <div>

                    <h3>
                        <?php echo $month_activities; ?>
                    </h3>

                    <p>
                        This Month
                    </p>

                </div>

            </div>


            <!-- YEAR -->

            <div class="stat-card">

                <div class="stat-icon year">

                    <i class="fas fa-chart-line"></i>

                </div>

                <div>

                    <h3>
                        <?php echo $year_activities; ?>
                    </h3>

                    <p>
                        This Year
                    </p>

                </div>

            </div>


        </div>


        <!-- =================================================
             SEARCH
        ================================================== -->

        <div class="search-box">

            <form
                method="GET"
                class="search-form"
            >

                <input
                    type="text"
                    name="search"
                    placeholder="Search activity, client, case, worker or location..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >


                <button
                    type="submit"
                    class="btn search-btn"
                >

                    <i class="fas fa-search"></i>

                    Search

                </button>


                <?php if ($search !== ""): ?>

                    <a
                        href="index.php"
                        class="btn clear-btn"
                    >

                        <i class="fas fa-times"></i>

                        Clear

                    </a>

                <?php endif; ?>


            </form>

        </div>


        <!-- =================================================
             ACTIVITY TABLE
        ================================================== -->

        <div class="table-card">


            <div class="table-header">

                <h3>

                    Activity Records

                    (<?php echo $activities->num_rows; ?> record(s))

                </h3>

            </div>


            <?php if ($activities->num_rows > 0): ?>


                <div class="table-wrapper">

                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Activity No.
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Location
                                </th>

                                <th>
                                    Client
                                </th>

                                <th>
                                    Case
                                </th>

                                <th>
                                    Social Worker
                                </th>

                                <th>
                                    Description
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php while ($activity = $activities->fetch_assoc()): ?>


                            <tr>


                                <td>

                                    <span class="activity-number">

                                        <?php

                                        echo htmlspecialchars(
                                            $activity["activity_number"]
                                        );

                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="type-badge">

                                        <?php

                                        echo htmlspecialchars(
                                            $activity["activity_type"]
                                        );

                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $activity["activity_date"]
                                        )
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $activity["location"]
                                        ?: "-"
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php if (!empty($activity["client_name"])): ?>

                                        <strong>

                                            <?php

                                            echo htmlspecialchars(
                                                $activity["client_name"]
                                            );

                                            ?>

                                        </strong>

                                        <br>

                                        <small>

                                            <?php

                                            echo htmlspecialchars(
                                                $activity["client_number"]
                                            );

                                            ?>

                                        </small>

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $activity["case_number"]
                                        ?: "-"
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $activity["worker_name"]
                                        ?: "-"
                                    );

                                    ?>

                                </td>


                                <td>

                                    <div class="description">

                                        <?php

                                        echo htmlspecialchars(
                                            $activity["description"]
                                            ?: "-"
                                        );

                                        ?>

                                    </div>

                                </td>


                                <td>

                                    <div class="actions">


                                        <a
                                            href="view.php?id=<?php echo $activity["activity_id"]; ?>"
                                            class="action-btn view-btn"
                                            title="View Activity"
                                        >

                                            <i class="fas fa-eye"></i>

                                        </a>


                                        <a
                                            href="edit.php?id=<?php echo $activity["activity_id"]; ?>"
                                            class="action-btn edit-btn"
                                            title="Edit Activity"
                                        >

                                            <i class="fas fa-edit"></i>

                                        </a>


                                        <a
                                            href="delete.php?id=<?php echo $activity["activity_id"]; ?>"
                                            class="action-btn delete-btn"
                                            title="Delete Activity"
                                            onclick="return confirm('Are you sure you want to delete this activity?');"
                                        >

                                            <i class="fas fa-trash"></i>

                                        </a>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty">

                    <i class="fas fa-calendar-check"></i>

                    <h3>
                        No Activities Found
                    </h3>

                    <p>
                        No social welfare activity has been recorded yet.
                    </p>


                    <a
                        href="add.php"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-plus"></i>

                        Add First Activity

                    </a>

                </div>


            <?php endif; ?>


        </div>


    </div>

</div>


</body>

</html>