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

$where = "";
$params = [];
$types = "";

if ($search !== "") {
    $where = "
        WHERE
            a.appointment_number LIKE ?
            OR a.appointment_type LIKE ?
            OR a.location LIKE ?
            OR a.status LIKE ?
            OR c.full_name LIKE ?
            OR c.client_number LIKE ?
            OR sw.full_name LIKE ?
    ";

    $search_param = "%" . $search . "%";

    $params = [
        $search_param,
        $search_param,
        $search_param,
        $search_param,
        $search_param,
        $search_param,
        $search_param
    ];

    $types = "sssssss";
}

/* =========================
   STATISTICS
========================= */
$total_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM appointments
");
$total_appointments = $total_result->fetch_assoc()["total"] ?? 0;

$today_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE appointment_date = CURDATE()
");
$today_appointments = $today_result->fetch_assoc()["total"] ?? 0;

$scheduled_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE status = 'Scheduled'
");
$scheduled_appointments = $scheduled_result->fetch_assoc()["total"] ?? 0;

$completed_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE status = 'Completed'
");
$completed_appointments = $completed_result->fetch_assoc()["total"] ?? 0;

/* =========================
   LOAD APPOINTMENTS
========================= */
$sql = "
    SELECT
        a.appointment_id,
        a.appointment_number,
        a.appointment_date,
        a.appointment_time,
        a.appointment_type,
        a.location,
        a.status,
        c.client_id,
        c.client_number,
        c.full_name AS client_name,
        sw.worker_id,
        sw.full_name AS worker_name
    FROM appointments a
    INNER JOIN clients c
        ON a.client_id = c.client_id
    LEFT JOIN social_workers sw
        ON a.worker_id = sw.worker_id
    $where
    ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.appointment_id DESC
";

if ($search !== "") {

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $appointments = $stmt->get_result();

} else {

    $appointments = $conn->query($sql);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Appointments | Tonkolili District Social Welfare
    </title>

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
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 22px 20px;
            border-bottom: 1px solid #333;
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .sidebar-header p {
            margin: 5px 0 0;
            color: #aaa;
            font-size: 13px;
        }

        .menu-title {
            padding: 18px 20px 8px;
            font-size: 11px;
            text-transform: uppercase;
            color: #777;
            letter-spacing: 1px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 20px;
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
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

        .icon-dashboard {
            color: #42a5f5;
        }

        .icon-client {
            color: #66bb6a;
        }

        .icon-case {
            color: #ef5350;
        }

        .icon-document {
            color: #ab47bc;
        }

        .icon-worker {
            color: #ffa726;
        }

        .icon-household {
            color: #26c6da;
        }

        .icon-activity {
            color: #ffca28;
        }

        .icon-appointment {
            color: #ec407a;
        }

        .icon-report {
            color: #7e57c2;
        }

        .icon-settings {
            color: #78909c;
        }

        .icon-logout {
            color: #ef5350;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            background: white;
            padding: 18px 28px;
            border-bottom: 1px solid #ddd;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            margin: 0;
            font-size: 21px;
        }

        .user {
            color: #555;
            font-size: 14px;
        }

        .content {
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0 0 6px;
            font-size: 26px;
        }

        .page-header p {
            margin: 0;
            color: #777;
        }

        /* =========================
           BUTTON
        ========================= */

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 11px 17px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 14px;

            border: none;

            cursor: pointer;
        }

        .btn-primary {
            background: #e53935;
            color: white;
        }

        .btn-primary:hover {
            background: #c62828;
        }

        /* =========================
           STATISTICS
        ========================= */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }

        .stat-card {
            background: white;

            border-radius: 8px;

            padding: 20px;

            border: 1px solid #ddd;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }

        .stat-info span {
            display: block;

            color: #777;

            font-size: 13px;

            margin-bottom: 7px;
        }

        .stat-info strong {
            font-size: 27px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;

            border-radius: 50%;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 20px;
        }

        .stat-total .stat-icon {
            background: #e3f2fd;
            color: #1976d2;
        }

        .stat-today .stat-icon {
            background: #fce4ec;
            color: #c2185b;
        }

        .stat-scheduled .stat-icon {
            background: #fff3e0;
            color: #ef6c00;
        }

        .stat-completed .stat-icon {
            background: #e8f5e9;
            color: #2e7d32;
        }

        /* =========================
           SEARCH
        ========================= */

        .search-card {
            background: white;

            border: 1px solid #ddd;

            border-radius: 8px;

            padding: 18px;

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

            border-radius: 5px;

            font-size: 14px;
        }

        .search-input:focus {
            outline: none;

            border-color: #e53935;
        }

        .btn-search {
            background: #333;

            color: white;
        }

        .btn-search:hover {
            background: #111;
        }

        .btn-clear {
            background: #777;

            color: white;
        }

        .btn-clear:hover {
            background: #555;
        }

        /* =========================
           TABLE
        ========================= */

        .table-card {
            background: white;

            border: 1px solid #ddd;

            border-radius: 8px;

            overflow-x: auto;
        }

        table {
            width: 100%;

            border-collapse: collapse;

            min-width: 1100px;
        }

        th {
            background: #1d1d1d;

            color: white;

            padding: 14px 12px;

            text-align: left;

            font-size: 13px;

            white-space: nowrap;
        }

        td {
            padding: 13px 12px;

            border-bottom: 1px solid #eee;

            font-size: 13px;

            vertical-align: middle;
        }

        tr:hover td {
            background: #fafafa;
        }

        .appointment-number {
            font-weight: bold;

            color: #333;
        }

        .client-name {
            font-weight: bold;

            color: #333;
        }

        .client-number {
            display: block;

            color: #888;

            font-size: 11px;

            margin-top: 3px;
        }

        .date {
            font-weight: bold;
        }

        .time {
            color: #777;

            margin-top: 3px;

            display: block;
        }

        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;
        }

        .status-scheduled {
            background: #fff3e0;

            color: #e65100;
        }

        .status-completed {
            background: #e8f5e9;

            color: #2e7d32;
        }

        .status-cancelled {
            background: #ffebee;

            color: #c62828;
        }

        .status-missed {
            background: #f3e5f5;

            color: #7b1fa2;
        }

        .status-rescheduled {
            background: #e3f2fd;

            color: #1565c0;
        }

        /* =========================
           ACTIONS
        ========================= */

        .actions {
            display: flex;

            gap: 6px;
        }

        .action-btn {
            width: 32px;

            height: 32px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 4px;

            text-decoration: none;

            color: white;

            font-size: 12px;
        }

        .view-btn {
            background: #1976d2;
        }

        .edit-btn {
            background: #ef6c00;
        }

        .delete-btn {
            background: #d32f2f;
        }

        .empty {
            padding: 50px;

            text-align: center;

            color: #777;
        }

        .empty i {
            font-size: 45px;

            color: #bbb;

            margin-bottom: 15px;
        }

        .empty h3 {
            margin: 0 0 7px;

            color: #555;
        }

        /* =========================
           ALERTS
        ========================= */

        .alert {
            padding: 13px 16px;

            border-radius: 5px;

            margin-bottom: 20px;

            font-size: 14px;
        }

        .alert-success {
            background: #e8f5e9;

            color: #2e7d32;

            border: 1px solid #a5d6a7;
        }

        .alert-error {
            background: #ffebee;

            color: #c62828;

            border: 1px solid #ef9a9a;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 700px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
            }

            .content {
                padding: 20px;
            }

            .page-header {
                align-items: flex-start;

                flex-direction: column;
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

<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="sidebar-header">

        <h2>
            <i class="fas fa-hands-helping"></i>
            Social Welfare
        </h2>

        <p>Tonkolili District</p>

    </div>


    <div class="menu-title">
        Main Menu
    </div>


    <a href="../index.php">

        <i class="fas fa-tachometer-alt icon-dashboard"></i>

        Dashboard

    </a>


    <a href="../clients/index.php">

        <i class="fas fa-users icon-client"></i>

        Clients

    </a>


    <a href="../cases/index.php">

        <i class="fas fa-folder-open icon-case"></i>

        Cases

    </a>


    <a href="../documents/index.php">

        <i class="fas fa-file-alt icon-document"></i>

        Documents

    </a>


    <a href="../social_workers/index.php">

        <i class="fas fa-user-tie icon-worker"></i>

        Social Workers

    </a>


    <a href="../households/index.php">

        <i class="fas fa-home icon-household"></i>

        Households

    </a>


    <a href="../activities/index.php">

        <i class="fas fa-calendar-check icon-activity"></i>

        Activities

    </a>


    <a href="index.php" class="active">

        <i class="fas fa-calendar-alt icon-appointment"></i>

        Appointments

    </a>


    <div class="menu-title">
        Management
    </div>


    <a href="../reports/index.php">

        <i class="fas fa-chart-bar icon-report"></i>

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


    <div class="topbar">

        <h1>
            Appointments
        </h1>


        <div class="user">

            <i class="fas fa-user-circle"></i>

            <?php
            echo htmlspecialchars(
                $_SESSION["full_name"]
                ?? $_SESSION["username"]
                ?? "User"
            );
            ?>

        </div>

    </div>


    <div class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <h2>
                    Appointment Management
                </h2>

                <p>
                    Schedule and manage client appointments.
                </p>

            </div>


            <a
                href="add.php"
                class="btn btn-primary"
            >

                <i class="fas fa-plus"></i>

                Add Appointment

            </a>

        </div>


        <!-- ALERTS -->

        <?php if (isset($_GET["success"])): ?>

            <div class="alert alert-success">

                <i class="fas fa-check-circle"></i>

                <?php
                echo htmlspecialchars($_GET["success"]);
                ?>

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["error"])): ?>

            <div class="alert alert-error">

                <i class="fas fa-exclamation-circle"></i>

                <?php
                echo htmlspecialchars($_GET["error"]);
                ?>

            </div>

        <?php endif; ?>


        <!-- STATISTICS -->

        <div class="stats">


            <div class="stat-card stat-total">

                <div class="stat-info">

                    <span>
                        Total Appointments
                    </span>

                    <strong>
                        <?php echo $total_appointments; ?>
                    </strong>

                </div>

                <div class="stat-icon">

                    <i class="fas fa-calendar-alt"></i>

                </div>

            </div>


            <div class="stat-card stat-today">

                <div class="stat-info">

                    <span>
                        Today's Appointments
                    </span>

                    <strong>
                        <?php echo $today_appointments; ?>
                    </strong>

                </div>

                <div class="stat-icon">

                    <i class="fas fa-calendar-day"></i>

                </div>

            </div>


            <div class="stat-card stat-scheduled">

                <div class="stat-info">

                    <span>
                        Scheduled
                    </span>

                    <strong>
                        <?php echo $scheduled_appointments; ?>
                    </strong>

                </div>

                <div class="stat-icon">

                    <i class="fas fa-clock"></i>

                </div>

            </div>


            <div class="stat-card stat-completed">

                <div class="stat-info">

                    <span>
                        Completed
                    </span>

                    <strong>
                        <?php echo $completed_appointments; ?>
                    </strong>

                </div>

                <div class="stat-icon">

                    <i class="fas fa-check"></i>

                </div>

            </div>


        </div>


        <!-- SEARCH -->

        <div class="search-card">

            <form
                method="GET"
                class="search-form"
            >

                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Search appointment number, client, worker, type, location or status..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >


                <button
                    type="submit"
                    class="btn btn-search"
                >

                    <i class="fas fa-search"></i>

                    Search

                </button>


                <?php if ($search !== ""): ?>

                    <a
                        href="index.php"
                        class="btn btn-clear"
                    >

                        <i class="fas fa-times"></i>

                        Clear

                    </a>

                <?php endif; ?>

            </form>

        </div>


        <!-- TABLE -->

        <div class="table-card">

            <?php if ($appointments && $appointments->num_rows > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>
                                Appointment No.
                            </th>

                            <th>
                                Client
                            </th>

                            <th>
                                Date & Time
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Social Worker
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php while ($row = $appointments->fetch_assoc()): ?>

                        <tr>


                            <td>

                                <span class="appointment-number">

                                    <?php
                                    echo htmlspecialchars(
                                        $row["appointment_number"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <span class="client-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $row["client_name"]
                                    );
                                    ?>

                                </span>

                                <span class="client-number">

                                    <?php
                                    echo htmlspecialchars(
                                        $row["client_number"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <span class="date">

                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $row["appointment_date"]
                                        )
                                    );
                                    ?>

                                </span>


                                <?php if (!empty($row["appointment_time"])): ?>

                                    <span class="time">

                                        <i class="far fa-clock"></i>

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime(
                                                $row["appointment_time"]
                                            )
                                        );
                                        ?>

                                    </span>

                                <?php else: ?>

                                    <span class="time">
                                        Time not set
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row["appointment_type"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row["location"]
                                    ?: "Not provided"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row["worker_name"]
                                    ?: "Not assigned"
                                );
                                ?>

                            </td>


                            <td>

                                <?php

                                $status_class = "status-scheduled";

                                if ($row["status"] === "Completed") {
                                    $status_class = "status-completed";
                                } elseif ($row["status"] === "Cancelled") {
                                    $status_class = "status-cancelled";
                                } elseif ($row["status"] === "Missed") {
                                    $status_class = "status-missed";
                                } elseif ($row["status"] === "Rescheduled") {
                                    $status_class = "status-rescheduled";
                                }

                                ?>

                                <span
                                    class="status <?php echo $status_class; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $row["status"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <div class="actions">


                                    <a
                                        href="view.php?id=<?php echo $row["appointment_id"]; ?>"
                                        class="action-btn view-btn"
                                        title="View"
                                    >

                                        <i class="fas fa-eye"></i>

                                    </a>


                                    <a
                                        href="edit.php?id=<?php echo $row["appointment_id"]; ?>"
                                        class="action-btn edit-btn"
                                        title="Edit"
                                    >

                                        <i class="fas fa-edit"></i>

                                    </a>


                                    <a
                                        href="delete.php?id=<?php echo $row["appointment_id"]; ?>"
                                        class="action-btn delete-btn"
                                        title="Delete"
                                    >

                                        <i class="fas fa-trash"></i>

                                    </a>


                                </div>

                            </td>


                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>


            <?php else: ?>


                <div class="empty">

                    <i class="fas fa-calendar-times"></i>

                    <h3>
                        No Appointments Found
                    </h3>

                    <p>
                        There are currently no appointments recorded.
                    </p>


                    <a
                        href="add.php"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-plus"></i>

                        Add First Appointment

                    </a>

                </div>


            <?php endif; ?>

        </div>


    </div>

</div>

</body>

</html>