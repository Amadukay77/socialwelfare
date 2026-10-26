<?php

session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

/* Get worker ID */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$worker_id = (int) $_GET["id"];

$error = "";
$success = "";


/* Fetch worker */

$stmt = $conn->prepare("
    SELECT *
    FROM social_workers
    WHERE worker_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $worker_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$worker = $result->fetch_assoc();

$stmt->close();


/* Update worker */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $worker_number = trim($_POST["worker_number"] ?? "");
    $full_name     = trim($_POST["full_name"] ?? "");
    $gender        = $_POST["gender"] ?? "";
    $phone         = trim($_POST["phone"] ?? "");
    $email         = trim($_POST["email"] ?? "");
    $address       = trim($_POST["address"] ?? "");
    $position      = trim($_POST["position"] ?? "");
    $status        = $_POST["status"] ?? "Active";


    /* Validation */

    if ($worker_number === "" || $full_name === "") {

        $error = "Worker number and full name are required.";

    } elseif (!in_array($gender, ["Male", "Female", "Other", ""])) {

        $error = "Invalid gender selected.";

    } elseif (!in_array($status, ["Active", "Inactive"])) {

        $error = "Invalid status selected.";

    } else {

        /* Check if another worker already uses this number */

        $check = $conn->prepare("
            SELECT worker_id
            FROM social_workers
            WHERE worker_number = ?
            AND worker_id != ?
            LIMIT 1
        ");

        $check->bind_param(
            "si",
            $worker_number,
            $worker_id
        );

        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $error = "Another social worker already uses this worker number.";

        } else {

            /* Update */

            $update = $conn->prepare("
                UPDATE social_workers
                SET
                    worker_number = ?,
                    full_name = ?,
                    gender = ?,
                    phone = ?,
                    email = ?,
                    address = ?,
                    position = ?,
                    status = ?
                WHERE worker_id = ?
            ");

            $update->bind_param(
                "ssssssssi",
                $worker_number,
                $full_name,
                $gender,
                $phone,
                $email,
                $address,
                $position,
                $status,
                $worker_id
            );

            if ($update->execute()) {

                header("Location: index.php?success=updated");
                exit;

            } else {

                $error = "Unable to update social worker. Please try again.";
            }

            $update->close();
        }

        $check->close();
    }


    /* Keep entered values on error */

    $worker["worker_number"] = $worker_number;
    $worker["full_name"] = $full_name;
    $worker["gender"] = $gender;
    $worker["phone"] = $phone;
    $worker["email"] = $email;
    $worker["address"] = $address;
    $worker["position"] = $position;
    $worker["status"] = $status;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Social Worker | Social Welfare</title>

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

        /* SIDEBAR */

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

        /* ICON COLORS */

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

        /* MAIN */

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
            max-width: 1000px;
        }

        /* PAGE HEADER */

        .page-header {
            margin-bottom: 20px;
        }

        .page-header h2 {
            margin: 0 0 6px;
            font-size: 23px;
        }

        .page-header p {
            margin: 0;
            color: #777;
            font-size: 14px;
        }

        /* FORM CARD */

        .form-card {
            background: white;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .form-section-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
            font-size: 16px;
            font-weight: bold;
        }

        .form-section-title i {
            color: #e53935;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 7px;
            color: #444;
        }

        input,
        select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus {
            border-color: #e53935;
            box-shadow: 0 0 0 2px rgba(229,57,53,0.08);
        }

        .help-text {
            margin-top: 5px;
            color: #888;
            font-size: 11px;
        }

        /* ERROR */

        .error-message {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
            border-radius: 5px;
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .error-message i {
            margin-right: 7px;
        }

        /* BUTTONS */

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 11px 17px;
            border-radius: 5px;
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

        .btn-secondary {
            background: #eee;
            color: #333;
        }

        .btn-secondary:hover {
            background: #ddd;
        }

        /* RESPONSIVE */

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

            .content {
                padding: 15px;
            }

            .form-card {
                padding: 18px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <div class="sidebar-header">

        <h2>
            <i class="fas fa-hands-helping"></i>
            Social Welfare
        </h2>

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


<!-- MAIN -->

<div class="main">

    <div class="topbar">

        <h1>Edit Social Worker</h1>

        <div class="topbar-right">

            <i class="fas fa-user-circle"></i>

            <?php echo htmlspecialchars($_SESSION["full_name"] ?? "User"); ?>

        </div>

    </div>


    <div class="content">

        <div class="page-header">

            <h2>
                <i class="fas fa-user-edit"></i>
                Edit Social Worker
            </h2>

            <p>
                Update the information of this social welfare worker.
            </p>

        </div>


        <div class="form-card">

            <?php if ($error !== ""): ?>

                <div class="error-message">

                    <i class="fas fa-exclamation-circle"></i>

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <div class="form-section-title">

                <i class="fas fa-id-card"></i>

                Worker Information

            </div>


            <form method="POST">

                <div class="form-grid">


                    <!-- WORKER NUMBER -->

                    <div class="form-group">

                        <label>
                            Worker Number
                        </label>

                        <input
                            type="text"
                            name="worker_number"
                            value="<?php echo htmlspecialchars($worker["worker_number"]); ?>"
                            required
                        >

                    </div>


                    <!-- FULL NAME -->

                    <div class="form-group">

                        <label>
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            value="<?php echo htmlspecialchars($worker["full_name"]); ?>"
                            required
                        >

                    </div>


                    <!-- GENDER -->

                    <div class="form-group">

                        <label>Gender</label>

                        <select name="gender">

                            <option value="">Select Gender</option>

                            <option value="Male"
                                <?php echo ($worker["gender"] === "Male") ? "selected" : ""; ?>>
                                Male
                            </option>

                            <option value="Female"
                                <?php echo ($worker["gender"] === "Female") ? "selected" : ""; ?>>
                                Female
                            </option>

                            <option value="Other"
                                <?php echo ($worker["gender"] === "Other") ? "selected" : ""; ?>>
                                Other
                            </option>

                        </select>

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label>Phone Number</label>

                        <input
                            type="text"
                            name="phone"
                            value="<?php echo htmlspecialchars($worker["phone"] ?? ""); ?>"
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label>Email Address</label>

                        <input
                            type="email"
                            name="email"
                            value="<?php echo htmlspecialchars($worker["email"] ?? ""); ?>"
                        >

                    </div>


                    <!-- POSITION -->

                    <div class="form-group">

                        <label>Position</label>

                        <input
                            type="text"
                            name="position"
                            value="<?php echo htmlspecialchars($worker["position"] ?? ""); ?>"
                        >

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group full">

                        <label>Address</label>

                        <input
                            type="text"
                            name="address"
                            value="<?php echo htmlspecialchars($worker["address"] ?? ""); ?>"
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label>Status</label>

                        <select name="status">

                            <option value="Active"
                                <?php echo ($worker["status"] === "Active") ? "selected" : ""; ?>>
                                Active
                            </option>

                            <option value="Inactive"
                                <?php echo ($worker["status"] === "Inactive") ? "selected" : ""; ?>>
                                Inactive
                            </option>

                        </select>

                        <span class="help-text">
                            Inactive workers will not appear in the case assignment list.
                        </span>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="form-actions">

                    <a href="index.php" class="btn btn-secondary">

                        <i class="fas fa-arrow-left"></i>

                        Cancel

                    </a>

                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save"></i>

                        Update Social Worker

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

</body>

</html>