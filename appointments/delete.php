<?php
session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$appointment_id = (int) $_GET["id"];

$errors = [];


/* =========================
   LOAD APPOINTMENT
========================= */

$stmt = $conn->prepare("
    SELECT
        a.*,
        c.client_number,
        c.full_name AS client_name,
        sw.worker_number,
        sw.full_name AS worker_name
    FROM appointments a
    INNER JOIN clients c
        ON a.client_id = c.client_id
    LEFT JOIN social_workers sw
        ON a.worker_id = sw.worker_id
    WHERE a.appointment_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $appointment_id);
$stmt->execute();

$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

$stmt->close();


if (!$appointment) {
    header("Location: index.php");
    exit;
}


/* =========================
   DELETE APPOINTMENT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $confirm = $_POST["confirm"] ?? "";

    if ($confirm !== "yes") {
        header("Location: view.php?id=" . $appointment_id);
        exit;
    }


    $delete = $conn->prepare("
        DELETE FROM appointments
        WHERE appointment_id = ?
        LIMIT 1
    ");

    if (!$delete) {

        $errors[] =
            "Unable to prepare delete operation: " .
            $conn->error;

    } else {

        $delete->bind_param(
            "i",
            $appointment_id
        );


        if ($delete->execute()) {

            $delete->close();

            header(
                "Location: index.php?success=" .
                urlencode("Appointment deleted successfully.")
            );

            exit;

        } else {

            $errors[] =
                "Unable to delete appointment. Database error: " .
                $delete->error;

            $delete->close();
        }
    }
}


function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Delete Appointment</title>

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

        .sidebar-header {
            text-align: center;
            padding: 10px 20px 25px;
            border-bottom: 1px solid #333;
        }

        .sidebar-header i {
            font-size: 32px;
            color: #e53935;
            margin-bottom: 8px;
        }

        .sidebar-header h2 {
            margin: 5px 0;
            font-size: 20px;
        }

        .sidebar-header p {
            margin: 0;
            color: #aaa;
            font-size: 12px;
        }

        .menu-title {
            padding: 20px 20px 8px;
            color: #777;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 20px;
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
            border-left: 4px solid transparent;
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
            color: #ffca28;
        }

        .icon-document {
            color: #ab47bc;
        }

        .icon-worker {
            color: #26c6da;
        }

        .icon-house {
            color: #ffa726;
        }

        .icon-activity {
            color: #ec407a;
        }

        .icon-appointment {
            color: #ef5350;
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

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            height: 70px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            border-bottom: 1px solid #ddd;
        }

        .topbar h1 {
            margin: 0;
            font-size: 22px;
        }

        .user-info {
            color: #555;
            font-size: 14px;
        }

        .content {
            padding: 30px;
            max-width: 850px;
            margin: 0 auto;
        }

        .confirmation-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.10);
            overflow: hidden;
        }

        .warning-header {
            background: #b71c1c;
            color: white;
            text-align: center;
            padding: 30px 20px;
        }

        .warning-header i {
            font-size: 45px;
            margin-bottom: 12px;
        }

        .warning-header h2 {
            margin: 0;
            font-size: 24px;
        }

        .warning-header p {
            margin: 8px 0 0;
            color: #ffcdd2;
        }

        .confirmation-body {
            padding: 30px;
        }

        .warning-message {
            text-align: center;
            color: #555;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .appointment-info {
            background: #f8f9fa;
            border: 1px solid #e1e1e1;
            border-radius: 7px;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 190px 1fr;
            border-bottom: 1px solid #e5e5e5;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            background: #f1f2f3;
            padding: 13px 16px;
            font-weight: bold;
            color: #555;
        }

        .info-value {
            padding: 13px 16px;
        }

        .status {
            display: inline-block;
            padding: 5px 11px;
            border-radius: 20px;
            background: #eee;
            font-size: 12px;
            font-weight: bold;
        }

        .error-box {
            background: #ffebee;
            color: #b71c1c;
            border: 1px solid #ef9a9a;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 6px;
            border: none;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-delete {
            background: #b71c1c;
            color: white;
        }

        .btn-delete:hover {
            background: #8e0000;
        }

        .btn-cancel {
            background: #555;
            color: white;
        }

        .btn-cancel:hover {
            background: #333;
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
                padding: 0 15px;
            }

            .content {
                padding: 15px;
            }

            .info-row {
                grid-template-columns: 1fr;
            }

            .info-label {
                padding-bottom: 5px;
            }

            .info-value {
                padding-top: 5px;
            }
        }

    </style>

</head>

<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <div class="sidebar-header">

        <i class="fas fa-hands-helping"></i>

        <h2>Social Welfare</h2>

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

        <i class="fas fa-home icon-house"></i>

        Households

    </a>


    <a href="../activities/index.php">

        <i class="fas fa-tasks icon-activity"></i>

        Activities

    </a>


    <a href="index.php" class="active">

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



<!-- MAIN -->

<div class="main">


    <div class="topbar">

        <h1>
            Delete Appointment
        </h1>


        <div class="user-info">

            <i class="fas fa-user-circle"></i>

            <?php
            echo e(
                $_SESSION["full_name"]
                ?? "System Administrator"
            );
            ?>

        </div>

    </div>



    <div class="content">


        <?php if (!empty($errors)): ?>

            <div class="error-box">

                <?php foreach ($errors as $error): ?>

                    <div>

                        <i class="fas fa-exclamation-circle"></i>

                        <?php echo e($error); ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>



        <div class="confirmation-card">


            <div class="warning-header">

                <i class="fas fa-exclamation-triangle"></i>

                <h2>
                    Delete Appointment
                </h2>

                <p>
                    This action cannot be undone.
                </p>

            </div>



            <div class="confirmation-body">


                <div class="warning-message">

                    <strong>
                        Are you sure you want to delete this appointment?
                    </strong>

                    <br>

                    The appointment record will be permanently removed
                    from the system.

                </div>



                <div class="appointment-info">


                    <div class="info-row">

                        <div class="info-label">
                            Appointment Number
                        </div>

                        <div class="info-value">

                            <strong>
                                <?php
                                echo e(
                                    $appointment["appointment_number"]
                                );
                                ?>
                            </strong>

                        </div>

                    </div>



                    <div class="info-row">

                        <div class="info-label">
                            Client
                        </div>

                        <div class="info-value">

                            <?php
                            echo e(
                                $appointment["client_name"]
                            );
                            ?>

                            <span style="color:#888;">
                                -
                                <?php
                                echo e(
                                    $appointment["client_number"]
                                );
                                ?>
                            </span>

                        </div>

                    </div>



                    <div class="info-row">

                        <div class="info-label">
                            Date
                        </div>

                        <div class="info-value">

                            <?php
                            echo date(
                                "d F Y",
                                strtotime(
                                    $appointment["appointment_date"]
                                )
                            );
                            ?>

                        </div>

                    </div>



                    <div class="info-row">

                        <div class="info-label">
                            Time
                        </div>

                        <div class="info-value">

                            <?php

                            if (!empty($appointment["appointment_time"])) {

                                echo date(
                                    "h:i A",
                                    strtotime(
                                        $appointment["appointment_time"]
                                    )
                                );

                            } else {

                                echo "Not specified";

                            }

                            ?>

                        </div>

                    </div>



                    <div class="info-row">

                        <div class="info-label">
                            Appointment Type
                        </div>

                        <div class="info-value">

                            <?php
                            echo e(
                                $appointment["appointment_type"]
                            );
                            ?>

                        </div>

                    </div>



                    <div class="info-row">

                        <div class="info-label">
                            Social Worker
                        </div>

                        <div class="info-value">

                            <?php

                            if (!empty($appointment["worker_name"])) {

                                echo e(
                                    $appointment["worker_name"]
                                );

                            } else {

                                echo "Not assigned";

                            }

                            ?>

                        </div>

                    </div>



                    <div class="info-row">

                        <div class="info-label">
                            Status
                        </div>

                        <div class="info-value">

                            <span class="status">

                                <?php
                                echo e(
                                    $appointment["status"]
                                );
                                ?>

                            </span>

                        </div>

                    </div>


                </div>



                <form
                    method="POST"
                    action=""
                >

                    <input
                        type="hidden"
                        name="confirm"
                        value="yes"
                    >


                    <div class="form-actions">


                        <button
                            type="submit"
                            class="btn btn-delete"
                        >

                            <i class="fas fa-trash-alt"></i>

                            Yes, Delete Appointment

                        </button>


                        <a
                            href="view.php?id=<?php echo $appointment_id; ?>"
                            class="btn btn-cancel"
                        >

                            <i class="fas fa-times"></i>

                            Cancel

                        </a>


                    </div>

                </form>


            </div>

        </div>


    </div>


</div>


</body>

</html>