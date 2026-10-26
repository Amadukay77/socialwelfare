 <?php

session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

/* =========================================================
   NOTIFICATIONS
========================================================= */

$success_message = "";
$error_message = "";

if (isset($_GET["success"])) {

    switch ($_GET["success"]) {

        case "added":
            $success_message = "Social worker added successfully.";
            break;

        case "updated":
            $success_message = "Social worker updated successfully.";
            break;

        case "deleted":
            $success_message = "Social worker deleted successfully.";
            break;
    }
}

if (isset($_GET["error"])) {

    switch ($_GET["error"]) {

        case "assigned":
            $error_message = "This social worker cannot be deleted because they are assigned to one or more cases.";
            break;

        case "delete":
            $error_message = "Unable to delete the social worker. Please try again.";
            break;
    }
}


/* =========================================================
   SEARCH
========================================================= */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if ($search !== "") {

    $stmt = $conn->prepare("
        SELECT *
        FROM social_workers
        WHERE worker_number LIKE ?
           OR full_name LIKE ?
           OR phone LIKE ?
           OR email LIKE ?
           OR position LIKE ?
        ORDER BY worker_id DESC
    ");

    $searchTerm = "%" . $search . "%";

    $stmt->bind_param(
        "sssss",
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    );

    $stmt->execute();

    $workers = $stmt->get_result();

} else {

    $workers = $conn->query("
        SELECT *
        FROM social_workers
        ORDER BY worker_id DESC
    ");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Social Workers | Social Welfare</title>

    <!-- Font Awesome -->
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


        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 245px;
            height: 100vh;
            background: #171717;
            color: white;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 22px 18px;
            border-bottom: 1px solid #333;
            text-align: center;
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 19px;
        }

        .sidebar-header h2 i {
            color: #e53935;
            margin-right: 6px;
        }

        .sidebar-header p {
            margin: 6px 0 0;
            font-size: 12px;
            color: #aaa;
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
            padding: 13px 20px;
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
            border-left: 4px solid transparent;
            transition: 0.2s;
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
            width: 25px;
            margin-right: 8px;
            font-size: 15px;
        }


        /* Different icon colors */

        .icon-dashboard {
            color: #42a5f5;
        }

        .icon-client {
            color: #66bb6a;
        }

        .icon-case {
            color: #ffca28;
        }

        .icon-document {
            color: #ab47bc;
        }

        .icon-worker {
            color: #ef5350;
        }

        .icon-house {
            color: #26c6da;
        }

        .icon-activity {
            color: #ffa726;
        }

        .icon-appointment {
            color: #ec407a;
        }

        .icon-report {
            color: #8d6e63;
        }

        .icon-settings {
            color: #78909c;
        }

        .icon-logout {
            color: #ef5350;
        }


        /* =========================================================
           MAIN
        ========================================================= */

        .main {
            margin-left: 245px;
            min-height: 100vh;
        }

        .topbar {
            background: white;
            padding: 18px 25px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            margin: 0;
            font-size: 21px;
        }

        .topbar-right {
            color: #666;
            font-size: 14px;
        }

        .content {
            padding: 25px;
        }


        /* =========================================================
           NOTIFICATIONS
        ========================================================= */

        .notification {
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            border: 1px solid transparent;
        }

        .notification-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-color: #c8e6c9;
        }

        .notification-error {
            background: #ffebee;
            color: #c62828;
            border-color: #ffcdd2;
        }

        .notification i {
            font-size: 17px;
        }


        /* =========================================================
           PAGE HEADER
        ========================================================= */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .page-title h2 {
            margin: 0 0 5px;
            font-size: 23px;
        }

        .page-title p {
            margin: 0;
            color: #777;
            font-size: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 16px;
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


        /* =========================================================
           SEARCH
        ========================================================= */

        .search-box {
            background: white;
            padding: 18px;
            border-radius: 7px;
            border: 1px solid #e3e3e3;
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-form input {
            flex: 1;
            padding: 11px 13px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            outline: none;
        }

        .search-form input:focus {
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
            background: #eee;
            color: #333;
        }


        /* =========================================================
           TABLE
        ========================================================= */

        .table-card {
            background: white;
            border-radius: 7px;
            border: 1px solid #e3e3e3;
            overflow: hidden;
        }

        .table-header {
            padding: 17px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .record-count {
            color: #777;
            font-size: 13px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            background: #f7f7f7;
            padding: 13px 15px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 1px solid #ddd;
        }

        td {
            padding: 14px 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        tr:hover td {
            background: #fafafa;
        }

        .worker-number {
            font-weight: bold;
            color: #333;
        }

        .worker-name {
            font-weight: 600;
        }

        .position {
            color: #666;
        }


        /* =========================================================
           STATUS
        ========================================================= */

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }


        /* =========================================================
           ACTION BUTTONS
        ========================================================= */

        .actions {
            display: flex;
            gap: 6px;
        }

        .action-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
        }

        .edit-btn {
            background: #e3f2fd;
            color: #1976d2;
        }

        .delete-btn {
            background: #ffebee;
            color: #d32f2f;
        }

        .edit-btn:hover {
            background: #bbdefb;
        }

        .delete-btn:hover {
            background: #ffcdd2;
        }


        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .empty {
            padding: 45px 20px;
            text-align: center;
            color: #888;
        }

        .empty i {
            font-size: 40px;
            margin-bottom: 12px;
            color: #bbb;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
            }

            .content {
                padding: 18px;
            }

        }

        @media (max-width: 650px) {

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

        }

    </style>

</head>

<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

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


    <a href="index.php" class="active">
        <i class="fas fa-user-tie icon-worker"></i>
        Social Workers
    </a>


    <a href="../households/index.php">
        <i class="fas fa-home icon-house"></i>
        Households
    </a>


    <a href="../activities/index.php">
        <i class="fas fa-tasks icon-activity"></i>
        Activities
    </a>


    <a href="../appointments/index.php">
        <i class="fas fa-calendar-check icon-appointment"></i>
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



<!-- =========================================================
     MAIN
========================================================= -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <h1>
            Social Workers
        </h1>


        <div class="topbar-right">

            <i class="fas fa-user-circle"></i>

            <?php
            echo htmlspecialchars(
                $_SESSION["full_name"] ?? "User"
            );
            ?>

        </div>

    </div>



    <!-- CONTENT -->

    <div class="content">


        <!-- =====================================================
             NOTIFICATIONS
        ====================================================== -->

        <?php if ($success_message !== ""): ?>

            <div class="notification notification-success">

                <i class="fas fa-check-circle"></i>

                <span>
                    <?php
                    echo htmlspecialchars($success_message);
                    ?>
                </span>

            </div>

        <?php endif; ?>


        <?php if ($error_message !== ""): ?>

            <div class="notification notification-error">

                <i class="fas fa-exclamation-circle"></i>

                <span>
                    <?php
                    echo htmlspecialchars($error_message);
                    ?>
                </span>

            </div>

        <?php endif; ?>



        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="page-header">


            <div class="page-title">

                <h2>

                    <i class="fas fa-user-tie"></i>

                    Social Workers

                </h2>


                <p>
                    Manage social welfare officers and case workers.
                </p>

            </div>


            <a href="add.php" class="btn btn-primary">

                <i class="fas fa-plus"></i>

                Add Social Worker

            </a>

        </div>



        <!-- =====================================================
             SEARCH
        ====================================================== -->

        <div class="search-box">

            <form method="GET" class="search-form">


                <input
                    type="text"
                    name="search"
                    placeholder="Search by worker number, name, phone, email or position..."
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



        <!-- =====================================================
             TABLE
        ====================================================== -->

        <div class="table-card">


            <div class="table-header">


                <h3>

                    <i class="fas fa-list"></i>

                    Registered Social Workers

                </h3>


                <span class="record-count">

                    <?php
                    echo $workers
                        ? $workers->num_rows
                        : 0;
                    ?>

                    record(s)

                </span>

            </div>



            <div class="table-wrapper">


                <?php if ($workers && $workers->num_rows > 0): ?>


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Worker No.
                                </th>

                                <th>
                                    Full Name
                                </th>

                                <th>
                                    Gender
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Position
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


                        <?php while ($worker = $workers->fetch_assoc()): ?>


                            <tr>


                                <td>

                                    <span class="worker-number">

                                        <?php
                                        echo htmlspecialchars(
                                            $worker["worker_number"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="worker-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $worker["full_name"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $worker["gender"] ?? ""
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $worker["phone"] ?? ""
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $worker["email"] ?? ""
                                    );
                                    ?>

                                </td>


                                <td>

                                    <span class="position">

                                        <?php
                                        echo htmlspecialchars(
                                            $worker["position"] ?? ""
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>


                                    <?php if ($worker["status"] === "Active"): ?>


                                        <span class="status status-active">

                                            <i class="fas fa-check-circle"></i>

                                            Active

                                        </span>


                                    <?php else: ?>


                                        <span class="status status-inactive">

                                            <i class="fas fa-times-circle"></i>

                                            Inactive

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <td>


                                    <div class="actions">


                                        <!-- EDIT -->

                                        <a
                                            href="edit.php?id=<?php echo $worker["worker_id"]; ?>"
                                            class="action-btn edit-btn"
                                            title="Edit"
                                        >

                                            <i class="fas fa-edit"></i>

                                        </a>



                                        <!-- DELETE -->

                                        <a
                                            href="delete.php?id=<?php echo $worker["worker_id"]; ?>"
                                            class="action-btn delete-btn"
                                            title="Delete"
                                            onclick="return confirm('Are you sure you want to delete this social worker?');"
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


                        <i class="fas fa-user-tie"></i>


                        <p>
                            No social workers found.
                        </p>


                        <a
                            href="add.php"
                            class="btn btn-primary"
                        >

                            <i class="fas fa-plus"></i>

                            Add First Social Worker

                        </a>


                    </div>


                <?php endif; ?>


            </div>

        </div>

    </div>

</div>


</body>

</html>