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

$appointment_number = "";
$client_id = 0;
$worker_id = 0;
$appointment_date = "";
$appointment_time = "";
$appointment_type = "";
$location = "";
$purpose = "";
$status = "Scheduled";
$notes = "";


/* =========================
   LOAD APPOINTMENT
========================= */

$stmt = $conn->prepare("
    SELECT *
    FROM appointments
    WHERE appointment_id = ?
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
   LOAD EXISTING VALUES
========================= */

$appointment_number = $appointment["appointment_number"];
$client_id = (int) $appointment["client_id"];
$worker_id = !empty($appointment["worker_id"])
    ? (int) $appointment["worker_id"]
    : 0;

$appointment_date = $appointment["appointment_date"];
$appointment_time = $appointment["appointment_time"] ?? "";
$appointment_type = $appointment["appointment_type"];
$location = $appointment["location"] ?? "";
$purpose = $appointment["purpose"] ?? "";
$status = $appointment["status"];
$notes = $appointment["notes"] ?? "";


/* =========================
   UPDATE APPOINTMENT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $appointment_number = trim($_POST["appointment_number"] ?? "");
    $client_id = (int) ($_POST["client_id"] ?? 0);
    $worker_id = (int) ($_POST["worker_id"] ?? 0);

    $appointment_date = trim($_POST["appointment_date"] ?? "");
    $appointment_time = trim($_POST["appointment_time"] ?? "");

    $appointment_type = trim($_POST["appointment_type"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $purpose = trim($_POST["purpose"] ?? "");

    $status = trim($_POST["status"] ?? "Scheduled");
    $notes = trim($_POST["notes"] ?? "");


    /* =========================
       VALIDATION
    ========================= */

    if ($appointment_number === "") {
        $errors[] = "Appointment number is required.";
    }

    if ($client_id <= 0) {
        $errors[] = "Please select a client.";
    }

    if ($appointment_date === "") {
        $errors[] = "Appointment date is required.";
    }

    if ($appointment_type === "") {
        $errors[] = "Appointment type is required.";
    }

    $allowed_statuses = [
        "Scheduled",
        "Completed",
        "Cancelled",
        "Missed",
        "Rescheduled"
    ];

    if (!in_array($status, $allowed_statuses, true)) {
        $errors[] = "Invalid appointment status.";
    }


    /* =========================
       CHECK DUPLICATE NUMBER
    ========================= */

    if (empty($errors)) {

        $check = $conn->prepare("
            SELECT appointment_id
            FROM appointments
            WHERE appointment_number = ?
            AND appointment_id != ?
            LIMIT 1
        ");

        $check->bind_param(
            "si",
            $appointment_number,
            $appointment_id
        );

        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $errors[] = "This appointment number already exists.";
        }

        $check->close();
    }


    /* =========================
       CHECK CLIENT
    ========================= */

    if (empty($errors)) {

        $check_client = $conn->prepare("
            SELECT client_id
            FROM clients
            WHERE client_id = ?
            LIMIT 1
        ");

        $check_client->bind_param(
            "i",
            $client_id
        );

        $check_client->execute();

        $client_result = $check_client->get_result();

        if ($client_result->num_rows === 0) {
            $errors[] = "Selected client does not exist.";
        }

        $check_client->close();
    }


    /* =========================
       UPDATE DATABASE
    ========================= */

    if (empty($errors)) {

        $update = $conn->prepare("
            UPDATE appointments
            SET
                appointment_number = ?,
                client_id = ?,
                worker_id = NULLIF(?, 0),
                appointment_date = ?,
                appointment_time = NULLIF(?, ''),
                appointment_type = ?,
                location = ?,
                purpose = ?,
                status = ?,
                notes = ?
            WHERE appointment_id = ?
        ");

        if (!$update) {

            $errors[] =
                "Unable to prepare update: " . $conn->error;

        } else {

            $update->bind_param(
                "siisssssssi",
                $appointment_number,
                $client_id,
                $worker_id,
                $appointment_date,
                $appointment_time,
                $appointment_type,
                $location,
                $purpose,
                $status,
                $notes,
                $appointment_id
            );


            if ($update->execute()) {

                $update->close();

                header(
                    "Location: view.php?id=" .
                    $appointment_id .
                    "&success=" .
                    urlencode("Appointment updated successfully.")
                );

                exit;

            } else {

                $errors[] =
                    "Unable to update appointment. Database error: " .
                    $update->error;

                $update->close();
            }
        }
    }
}


/* =========================
   LOAD CLIENTS
========================= */

$clients = $conn->query("
    SELECT
        client_id,
        client_number,
        full_name,
        gender
    FROM clients
    ORDER BY full_name ASC
");


/* =========================
   LOAD ACTIVE SOCIAL WORKERS
========================= */

$social_workers = $conn->query("
    SELECT
        worker_id,
        worker_number,
        full_name,
        position
    FROM social_workers
    WHERE status = 'Active'
    ORDER BY full_name ASC
");


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

    <title>Edit Appointment</title>

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
            max-width: 1100px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 25px;
        }

        .page-header p {
            margin: 7px 0 0;
            color: #777;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 25px;
        }

        .error-box {
            background: #ffebee;
            color: #b71c1c;
            border: 1px solid #ef9a9a;
            padding: 15px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .error-box div {
            margin-bottom: 5px;
        }

        .error-box div:last-child {
            margin-bottom: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
            font-size: 14px;
        }

        .required {
            color: #e53935;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #e53935;
            box-shadow: 0 0 0 2px rgba(229,57,53,0.10);
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .help-text {
            margin-top: 5px;
            font-size: 12px;
            color: #888;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 18px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
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

        .btn-secondary {
            background: #555;
            color: white;
        }

        .btn-secondary:hover {
            background: #333;
        }

        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
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

            .topbar {
                padding: 0 15px;
            }

            .content {
                padding: 15px;
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
            Edit Appointment
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


        <div class="page-header">

            <h2>
                Edit Appointment
            </h2>

            <p>
                Update the appointment information below.
            </p>

        </div>



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



        <div class="card">


            <form
                method="POST"
                action=""
            >


                <div class="form-grid">


                    <!-- APPOINTMENT NUMBER -->

                    <div class="form-group">

                        <label>
                            Appointment Number
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="appointment_number"
                            value="<?php echo e($appointment_number); ?>"
                            required
                        >

                        <div class="help-text">
                            You can change the appointment number.
                        </div>

                    </div>



                    <!-- CLIENT -->

                    <div class="form-group">

                        <label>
                            Client
                            <span class="required">*</span>
                        </label>

                        <select
                            name="client_id"
                            required
                        >

                            <option value="">
                                Select Client
                            </option>


                            <?php if ($clients): ?>

                                <?php while ($client = $clients->fetch_assoc()): ?>

                                    <option
                                        value="<?php echo (int)$client["client_id"]; ?>"
                                        <?php
                                        echo (
                                            $client_id ==
                                            $client["client_id"]
                                        )
                                        ? "selected"
                                        : "";
                                        ?>
                                    >

                                        <?php
                                        echo e(
                                            $client["client_number"]
                                        );
                                        ?>

                                        -
                                        <?php
                                        echo e(
                                            $client["full_name"]
                                        );
                                        ?>

                                        <?php
                                        if (!empty($client["gender"])) {
                                            echo " (" .
                                                e($client["gender"]) .
                                                ")";
                                        }
                                        ?>

                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                    </div>



                    <!-- SOCIAL WORKER -->

                    <div class="form-group">

                        <label>
                            Social Worker
                        </label>

                        <select
                            name="worker_id"
                        >

                            <option value="0">
                                Not Assigned
                            </option>


                            <?php if ($social_workers): ?>

                                <?php while ($worker = $social_workers->fetch_assoc()): ?>

                                    <option
                                        value="<?php echo (int)$worker["worker_id"]; ?>"
                                        <?php
                                        echo (
                                            $worker_id ==
                                            $worker["worker_id"]
                                        )
                                        ? "selected"
                                        : "";
                                        ?>
                                    >

                                        <?php
                                        echo e(
                                            $worker["worker_number"]
                                        );
                                        ?>

                                        -
                                        <?php
                                        echo e(
                                            $worker["full_name"]
                                        );
                                        ?>

                                        <?php
                                        if (!empty($worker["position"])) {
                                            echo " (" .
                                                e($worker["position"]) .
                                                ")";
                                        }
                                        ?>

                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                    </div>



                    <!-- DATE -->

                    <div class="form-group">

                        <label>
                            Appointment Date
                            <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            name="appointment_date"
                            value="<?php echo e($appointment_date); ?>"
                            required
                        >

                    </div>



                    <!-- TIME -->

                    <div class="form-group">

                        <label>
                            Appointment Time
                        </label>

                        <input
                            type="time"
                            name="appointment_time"
                            value="<?php echo e($appointment_time); ?>"
                        >

                    </div>



                    <!-- TYPE -->

                    <div class="form-group">

                        <label>
                            Appointment Type
                            <span class="required">*</span>
                        </label>

                        <select
                            name="appointment_type"
                            required
                        >

                            <option value="">
                                Select Appointment Type
                            </option>

                            <?php

                            $types = [
                                "Counseling",
                                "Assessment",
                                "Follow-up",
                                "Home Visit",
                                "Case Review",
                                "Family Meeting",
                                "Court/Legal",
                                "Referral",
                                "Other"
                            ];

                            foreach ($types as $type):

                            ?>

                                <option
                                    value="<?php echo e($type); ?>"
                                    <?php
                                    echo (
                                        $appointment_type === $type
                                    )
                                    ? "selected"
                                    : "";
                                    ?>
                                >

                                    <?php echo e($type); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- LOCATION -->

                    <div class="form-group">

                        <label>
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            value="<?php echo e($location); ?>"
                            placeholder="e.g. Magburaka"
                        >

                    </div>



                    <!-- STATUS -->

                    <div class="form-group">

                        <label>
                            Status
                            <span class="required">*</span>
                        </label>

                        <select
                            name="status"
                            required
                        >

                            <?php

                            $statuses = [
                                "Scheduled",
                                "Completed",
                                "Cancelled",
                                "Missed",
                                "Rescheduled"
                            ];

                            foreach ($statuses as $status_option):

                            ?>

                                <option
                                    value="<?php echo e($status_option); ?>"
                                    <?php
                                    echo (
                                        $status === $status_option
                                    )
                                    ? "selected"
                                    : "";
                                    ?>
                                >

                                    <?php echo e($status_option); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- PURPOSE -->

                    <div class="form-group full">

                        <label>
                            Purpose
                        </label>

                        <textarea
                            name="purpose"
                            placeholder="Enter the purpose of the appointment..."
                        ><?php echo e($purpose); ?></textarea>

                    </div>



                    <!-- NOTES -->

                    <div class="form-group full">

                        <label>
                            Notes
                        </label>

                        <textarea
                            name="notes"
                            placeholder="Enter additional notes..."
                        ><?php echo e($notes); ?></textarea>

                    </div>


                </div>



                <!-- BUTTONS -->

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-save"></i>

                        Update Appointment

                    </button>


                    <a
                        href="view.php?id=<?php echo $appointment_id; ?>"
                        class="btn btn-secondary"
                    >

                        <i class="fas fa-times"></i>

                        Cancel

                    </a>

                </div>


            </form>


        </div>


    </div>


</div>


</body>

</html>