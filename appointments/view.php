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

$stmt = $conn->prepare("
    SELECT
        a.*,

        c.client_number,
        c.full_name AS client_name,
        c.gender AS client_gender,
        c.phone AS client_phone,
        c.chiefdom,
        c.section,

        sw.worker_number,
        sw.full_name AS worker_name,
        sw.position AS worker_position

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

if (!$appointment) {
    header("Location: index.php");
    exit;
}

$stmt->close();

$success = $_GET["success"] ?? "";

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function statusClass($status)
{
    switch ($status) {
        case "Scheduled":
            return "status-scheduled";

        case "Completed":
            return "status-completed";

        case "Cancelled":
            return "status-cancelled";

        case "Missed":
            return "status-missed";

        case "Rescheduled":
            return "status-rescheduled";

        default:
            return "status-default";
    }
}

$formatted_date = date(
    "d F Y",
    strtotime($appointment["appointment_date"])
);

$formatted_time = "";

if (!empty($appointment["appointment_time"])) {
    $formatted_time = date(
        "h:i A",
        strtotime($appointment["appointment_time"])
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Appointment <?php echo e($appointment["appointment_number"]); ?>
    </title>

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
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 25px;
        }

        .page-header p {
            margin: 6px 0 0;
            color: #777;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 16px;
            border-radius: 6px;
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

        .btn-secondary {
            background: #555;
            color: white;
        }

        .btn-edit {
            background: #1976d2;
            color: white;
        }

        .btn-delete {
            background: #b71c1c;
            color: white;
        }

        .success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
            padding: 13px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 18px 22px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .card-header i {
            color: #e53935;
            margin-right: 8px;
        }

        .details {
            padding: 0;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 220px 1fr;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            background: #f8f9fa;
            padding: 15px 20px;
            font-weight: bold;
            color: #555;
        }

        .detail-value {
            padding: 15px 20px;
            color: #222;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-scheduled {
            background: #e3f2fd;
            color: #1565c0;
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
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-rescheduled {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .status-default {
            background: #eee;
            color: #555;
        }

        .profile-box {
            padding: 20px;
            display: flex;
            gap: 18px;
            align-items: center;
        }

        .profile-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e53935;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .profile-info h3 {
            margin: 0 0 6px;
            font-size: 19px;
        }

        .profile-info p {
            margin: 3px 0;
            color: #777;
            font-size: 14px;
        }

        .profile-info a {
            color: #1976d2;
            text-decoration: none;
            font-weight: bold;
        }

        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
            }

            .two-columns {
                grid-template-columns: 1fr;
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
                padding: 0 15px;
            }

            .content {
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .detail-row {
                grid-template-columns: 1fr;
            }

            .detail-label {
                padding-bottom: 5px;
            }

            .detail-value {
                padding-top: 5px;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">

    <div class="sidebar-header">
        <i class="fas fa-hands-helping"></i>
        <h2>Social Welfare</h2>
        <p>Tonkolili District</p>
    </div>

    <div class="menu-title">Main Menu</div>

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

    <div class="menu-title">Management</div>

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


<div class="main">

    <div class="topbar">

        <h1>Appointments</h1>

        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <?php echo e($_SESSION["full_name"] ?? "System Administrator"); ?>
        </div>

    </div>


    <div class="content">

        <?php if ($success !== ""): ?>

            <div class="success">
                <i class="fas fa-check-circle"></i>
                <?php echo e($success); ?>
            </div>

        <?php endif; ?>


        <div class="page-header">

            <div>
                <h2>
                    Appointment Details
                </h2>

                <p>
                    View complete information about this appointment.
                </p>
            </div>

            <div class="actions">

                <a
                    href="edit.php?id=<?php echo $appointment_id; ?>"
                    class="btn btn-edit"
                >
                    <i class="fas fa-edit"></i>
                    Edit
                </a>

                <a
                    href="delete.php?id=<?php echo $appointment_id; ?>"
                    class="btn btn-delete"
                >
                    <i class="fas fa-trash"></i>
                    Delete
                </a>

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>

            </div>

        </div>


        <div class="card">

            <div class="card-header">

                <h3>
                    <i class="fas fa-calendar-check"></i>
                    Appointment Information
                </h3>

                <span class="status <?php echo statusClass($appointment["status"]); ?>">
                    <?php echo e($appointment["status"]); ?>
                </span>

            </div>


            <div class="details">

                <div class="detail-row">

                    <div class="detail-label">
                        Appointment Number
                    </div>

                    <div class="detail-value">
                        <strong>
                            <?php echo e($appointment["appointment_number"]); ?>
                        </strong>
                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Appointment Type
                    </div>

                    <div class="detail-value">
                        <?php echo e($appointment["appointment_type"]); ?>
                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Date
                    </div>

                    <div class="detail-value">
                        <?php echo e($formatted_date); ?>
                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Time
                    </div>

                    <div class="detail-value">

                        <?php if ($formatted_time !== ""): ?>

                            <?php echo e($formatted_time); ?>

                        <?php else: ?>

                            <span style="color:#888;">
                                Not specified
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Location
                    </div>

                    <div class="detail-value">
                        <?php echo e($appointment["location"] ?: "Not specified"); ?>
                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Purpose
                    </div>

                    <div class="detail-value">
                        <?php echo nl2br(e($appointment["purpose"] ?: "Not specified")); ?>
                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Notes
                    </div>

                    <div class="detail-value">
                        <?php echo nl2br(e($appointment["notes"] ?: "No notes")); ?>
                    </div>

                </div>

            </div>

        </div>


        <div class="two-columns">


            <div class="card">

                <div class="card-header">

                    <h3>
                        <i class="fas fa-user"></i>
                        Client Information
                    </h3>

                </div>


                <div class="profile-box">

                    <div class="profile-icon">
                        <i class="fas fa-user"></i>
                    </div>

                    <div class="profile-info">

                        <h3>
                            <?php echo e($appointment["client_name"]); ?>
                        </h3>

                        <p>
                            Client No:
                            <strong>
                                <?php echo e($appointment["client_number"]); ?>
                            </strong>
                        </p>

                        <p>
                            Gender:
                            <?php echo e($appointment["client_gender"] ?: "Not specified"); ?>
                        </p>

                        <p>
                            Phone:
                            <?php echo e($appointment["client_phone"] ?: "Not specified"); ?>
                        </p>

                        <p>
                            <?php echo e($appointment["chiefdom"] ?: ""); ?>

                            <?php if (!empty($appointment["section"])): ?>
                                -
                                <?php echo e($appointment["section"]); ?>
                            <?php endif; ?>
                        </p>

                        <p>
                            <a href="../clients/view.php?id=<?php echo (int)$appointment["client_id"]; ?>">
                                View Client Profile
                            </a>
                        </p>

                    </div>

                </div>

            </div>


            <div class="card">

                <div class="card-header">

                    <h3>
                        <i class="fas fa-user-tie"></i>
                        Social Worker
                    </h3>

                </div>


                <div class="profile-box">

                    <div class="profile-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>

                    <div class="profile-info">

                        <?php if (!empty($appointment["worker_name"])): ?>

                            <h3>
                                <?php echo e($appointment["worker_name"]); ?>
                            </h3>

                            <p>
                                Worker No:
                                <strong>
                                    <?php echo e($appointment["worker_number"]); ?>
                                </strong>
                            </p>

                            <p>
                                Position:
                                <?php echo e($appointment["worker_position"] ?: "Not specified"); ?>
                            </p>

                        <?php else: ?>

                            <h3>
                                Not Assigned
                            </h3>

                            <p>
                                No social worker has been assigned to this appointment.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-header">

                <h3>
                    <i class="fas fa-info-circle"></i>
                    Record Information
                </h3>

            </div>


            <div class="details">

                <div class="detail-row">

                    <div class="detail-label">
                        Appointment ID
                    </div>

                    <div class="detail-value">
                        #<?php echo (int) $appointment["appointment_id"]; ?>
                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Record Created
                    </div>

                    <div class="detail-value">
                        <?php
                        echo date(
                            "d F Y, h:i A",
                            strtotime($appointment["created_at"])
                        );
                        ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>