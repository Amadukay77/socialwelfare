 <?php
session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}


/* =========================================================
   CHECK ACTIVITY ID
========================================================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php?error=Invalid activity ID.");
    exit;
}

$activity_id = (int) $_GET["id"];


/* =========================================================
   LOAD ACTIVITY
========================================================= */

$stmt = $conn->prepare("
    SELECT *
    FROM activities
    WHERE activity_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $activity_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();

    header("Location: index.php?error=Activity record not found.");
    exit;
}

$activity = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   VARIABLES
========================================================= */

$activity_number = $activity["activity_number"];
$activity_type   = $activity["activity_type"];
$activity_date   = $activity["activity_date"];
$location        = $activity["location"];
$description     = $activity["description"];
$client_id       = $activity["client_id"];
$case_id         = $activity["case_id"];
$worker_id       = $activity["worker_id"];
$participants    = $activity["participants"];
$outcome         = $activity["outcome"];
$notes           = $activity["notes"];

$errors = [];


/* =========================================================
   FORM SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $activity_number = trim($_POST["activity_number"] ?? "");
    $activity_type   = trim($_POST["activity_type"] ?? "");
    $activity_date   = trim($_POST["activity_date"] ?? "");
    $location        = trim($_POST["location"] ?? "");
    $description     = trim($_POST["description"] ?? "");
    $client_id       = (int) ($_POST["client_id"] ?? 0);
    $case_id         = (int) ($_POST["case_id"] ?? 0);
    $worker_id       = (int) ($_POST["worker_id"] ?? 0);
    $participants    = trim($_POST["participants"] ?? "");
    $outcome         = trim($_POST["outcome"] ?? "");
    $notes           = trim($_POST["notes"] ?? "");


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($activity_number === "") {
        $errors[] = "Activity number is required.";
    }

    if ($activity_type === "") {
        $errors[] = "Activity type is required.";
    }

    if ($activity_date === "") {
        $errors[] = "Activity date is required.";
    }


    /* =====================================================
       CHECK DUPLICATE ACTIVITY NUMBER
    ===================================================== */

    if ($activity_number !== "") {

        $check = $conn->prepare("
            SELECT activity_id
            FROM activities
            WHERE activity_number = ?
            AND activity_id != ?
            LIMIT 1
        ");

        if (!$check) {

            $errors[] = "Unable to check activity number.";

        } else {

            $check->bind_param(
                "si",
                $activity_number,
                $activity_id
            );

            $check->execute();

            $duplicate = $check->get_result();

            if ($duplicate->num_rows > 0) {
                $errors[] = "This activity number already exists.";
            }

            $check->close();
        }
    }


    /* =====================================================
       CHECK CLIENT
    ===================================================== */

    if ($client_id > 0) {

        $client_check = $conn->prepare("
            SELECT client_id
            FROM clients
            WHERE client_id = ?
            LIMIT 1
        ");

        if ($client_check) {

            $client_check->bind_param(
                "i",
                $client_id
            );

            $client_check->execute();

            $client_result = $client_check->get_result();

            if ($client_result->num_rows === 0) {
                $errors[] = "Selected client does not exist.";
                $client_id = 0;
            }

            $client_check->close();
        }
    }


    /* =====================================================
       CHECK CASE
    ===================================================== */

    if ($case_id > 0) {

        $case_check = $conn->prepare("
            SELECT case_id
            FROM cases
            WHERE case_id = ?
            LIMIT 1
        ");

        if ($case_check) {

            $case_check->bind_param(
                "i",
                $case_id
            );

            $case_check->execute();

            $case_result = $case_check->get_result();

            if ($case_result->num_rows === 0) {
                $errors[] = "Selected case does not exist.";
                $case_id = 0;
            }

            $case_check->close();
        }
    }


    /* =====================================================
       CHECK SOCIAL WORKER
    ===================================================== */

    if ($worker_id > 0) {

        $worker_check = $conn->prepare("
            SELECT worker_id
            FROM social_workers
            WHERE worker_id = ?
            LIMIT 1
        ");

        if ($worker_check) {

            $worker_check->bind_param(
                "i",
                $worker_id
            );

            $worker_check->execute();

            $worker_result = $worker_check->get_result();

            if ($worker_result->num_rows === 0) {
                $errors[] = "Selected social worker does not exist.";
                $worker_id = 0;
            }

            $worker_check->close();
        }
    }


    /* =====================================================
       UPDATE ACTIVITY
    ===================================================== */

    if (empty($errors)) {

        $sql = "
            UPDATE activities
            SET
                activity_number = ?,
                activity_type = ?,
                activity_date = ?,
                location = ?,
                description = ?,
                client_id = NULLIF(?, 0),
                case_id = NULLIF(?, 0),
                worker_id = NULLIF(?, 0),
                participants = ?,
                outcome = ?,
                notes = ?
            WHERE activity_id = ?
        ";

        $update = $conn->prepare($sql);

        if (!$update) {

            $errors[] = "Database error: " . $conn->error;

        } else {

            /*
             * There are exactly 12 variables:
             *
             * 1  activity_number  = string
             * 2  activity_type    = string
             * 3  activity_date    = string
             * 4  location         = string
             * 5  description      = string
             * 6  client_id        = integer
             * 7  case_id          = integer
             * 8  worker_id        = integer
             * 9  participants     = string
             * 10 outcome         = string
             * 11 notes           = string
             * 12 activity_id     = integer
             *
             * Correct type string:
             * sssssiiisssi
             */

            $update->bind_param(
                "sssssiiisssi",
                $activity_number,
                $activity_type,
                $activity_date,
                $location,
                $description,
                $client_id,
                $case_id,
                $worker_id,
                $participants,
                $outcome,
                $notes,
                $activity_id
            );

            if ($update->execute()) {

                $update->close();

                header(
                    "Location: view.php?id=" .
                    $activity_id .
                    "&success=" .
                    urlencode("Activity updated successfully.")
                );

                exit;

            } else {

                $errors[] =
                    "Unable to update activity: " .
                    $update->error;

                $update->close();
            }
        }
    }
}


/* =========================================================
   LOAD CLIENTS
========================================================= */

$clients = $conn->query("
    SELECT
        client_id,
        client_number,
        full_name
    FROM clients
    ORDER BY full_name ASC
");


/* =========================================================
   LOAD CASES
========================================================= */

$cases = $conn->query("
    SELECT
        case_id,
        client_id,
        case_number,
        case_type,
        status
    FROM cases
    ORDER BY case_number DESC
");


/* =========================================================
   LOAD SOCIAL WORKERS
========================================================= */

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


/* =========================================================
   USER INFORMATION
========================================================= */

$full_name = $_SESSION["full_name"] ?? "User";
$role      = $_SESSION["role"] ?? "User";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Edit Activity - Tonkolili District Social Welfare
</title>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f5f7;
    color: #222;
}

a {
    text-decoration: none;
}


/* SIDEBAR */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: #171717;
    color: #fff;
    overflow-y: auto;
    z-index: 1000;
}

.sidebar-header {
    padding: 22px 20px;
    border-bottom: 1px solid #2b2b2b;
}

.sidebar-header h2 {
    font-size: 20px;
    margin-bottom: 5px;
}

.sidebar-header p {
    color: #aaa;
    font-size: 12px;
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
    gap: 13px;
    padding: 12px 20px;
    color: #ccc;
    font-size: 14px;
    border-left: 3px solid transparent;
    transition: 0.2s;
}

.sidebar a:hover {
    background: #222;
    color: #fff;
}

.sidebar a.active {
    background: #222;
    color: #fff;
    border-left-color: #e53935;
}

.sidebar a i {
    width: 20px;
    text-align: center;
    font-size: 15px;
}

.icon-dashboard { color: #42a5f5; }
.icon-client { color: #66bb6a; }
.icon-case { color: #ffa726; }
.icon-document { color: #ab47bc; }
.icon-worker { color: #26c6da; }
.icon-household { color: #ec407a; }
.icon-activity { color: #ffca28; }
.icon-appointment { color: #29b6f6; }
.icon-report { color: #7e57c2; }
.icon-settings { color: #bdbdbd; }
.icon-logout { color: #ef5350; }


/* MAIN */

.main {
    margin-left: 250px;
    min-height: 100vh;
}

.topbar {
    min-height: 70px;
    background: #fff;
    border-bottom: 1px solid #ddd;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 15px 30px;
}

.topbar-title h1 {
    font-size: 22px;
}

.topbar-title p {
    margin-top: 3px;
    font-size: 12px;
    color: #777;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #171717;
    color: #fff;

    display: flex;
    align-items: center;
    justify-content: center;
}

.user-text strong {
    display: block;
    font-size: 14px;
}

.user-text span {
    color: #777;
    font-size: 12px;
}

.content {
    padding: 30px;
    max-width: 1200px;
}


/* BREADCRUMB */

.breadcrumb {
    margin-bottom: 20px;
    font-size: 13px;
    color: #777;
}

.breadcrumb a {
    color: #e53935;
}

.breadcrumb i {
    margin: 0 8px;
    font-size: 10px;
}


/* CARD */

.card {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 10px;
    overflow: hidden;
}

.card-header {
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    background: #fafafa;
}

.card-header h2 {
    font-size: 20px;
    margin-bottom: 5px;
}

.card-header p {
    color: #777;
    font-size: 13px;
}

.card-body {
    padding: 25px;
}


/* ALERT */

.alert {
    padding: 13px 15px;
    border-radius: 7px;
    margin-bottom: 20px;
    font-size: 13px;
}

.alert-error {
    background: #fdecea;
    color: #b71c1c;
    border: 1px solid #f5c6cb;
}

.alert-error ul {
    margin-left: 20px;
    margin-top: 5px;
}


/* CURRENT RECORD */

.current-record {
    margin-bottom: 20px;
    padding: 14px 16px;
    background: #f8f9fa;
    border: 1px solid #e5e5e5;
    border-radius: 7px;

    display: flex;
    align-items: center;
    gap: 10px;
}

.current-record i {
    color: #e53935;
}


/* FORM */

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
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
    color: #333;
}

label span {
    color: #e53935;
}

input,
select,
textarea {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    background: #fff;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #e53935;
    box-shadow: 0 0 0 2px rgba(229, 57, 53, 0.08);
}

textarea {
    min-height: 120px;
    resize: vertical;
}

.small-help {
    color: #888;
    font-size: 11px;
    margin-top: 5px;
}


/* BUTTONS */

.form-actions {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eee;

    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn {
    border: none;
    border-radius: 6px;
    padding: 11px 17px;
    font-size: 13px;
    cursor: pointer;

    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.btn-save {
    background: #e53935;
    color: #fff;
}

.btn-cancel {
    background: #e9ecef;
    color: #333;
}

.btn:hover {
    opacity: 0.9;
}


/* RESPONSIVE */

@media (max-width: 900px) {

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
        flex-direction: column;
        align-items: flex-start;
    }

    .content {
        padding: 15px;
    }

    .card-body {
        padding: 18px;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn {
        justify-content: center;
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


    <a href="index.php" class="active">
        <i class="fas fa-calendar-check icon-activity"></i>
        Activities
    </a>


    <a href="../appointments/index.php">
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


<!-- MAIN -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div class="topbar-title">

            <h1>
                Edit Activity
            </h1>

            <p>
                Tonkolili District Social Welfare Records Management System
            </p>

        </div>


        <div class="user-info">

            <div class="user-icon">
                <i class="fas fa-user"></i>
            </div>

            <div class="user-text">

                <strong>
                    <?php echo htmlspecialchars($full_name); ?>
                </strong>

                <span>
                    <?php echo htmlspecialchars($role); ?>
                </span>

            </div>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="content">


        <!-- BREADCRUMB -->

        <div class="breadcrumb">

            <a href="index.php">
                Activities
            </a>

            <i class="fas fa-chevron-right"></i>

            <a href="view.php?id=<?php echo $activity_id; ?>">
                View Activity
            </a>

            <i class="fas fa-chevron-right"></i>

            Edit

        </div>


        <!-- CARD -->

        <div class="card">


            <div class="card-header">

                <h2>
                    <i class="fas fa-edit"></i>
                    Edit Activity Record
                </h2>

                <p>
                    Update the information for this social welfare activity.
                </p>

            </div>


            <div class="card-body">


                <!-- ERRORS -->

                <?php if (!empty($errors)): ?>

                    <div class="alert alert-error">

                        <strong>
                            <i class="fas fa-exclamation-circle"></i>
                            Please correct the following:
                        </strong>

                        <ul>

                            <?php foreach ($errors as $error): ?>

                                <li>
                                    <?php echo htmlspecialchars($error); ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                <?php endif; ?>


                <!-- CURRENT RECORD -->

                <div class="current-record">

                    <i class="fas fa-info-circle"></i>

                    <span>
                        Editing activity:
                        <strong>
                            <?php echo htmlspecialchars($activity_number); ?>
                        </strong>
                    </span>

                </div>


                <!-- FORM -->

                <form method="POST">


                    <div class="form-grid">


                        <!-- ACTIVITY NUMBER -->

                        <div class="form-group">

                            <label>
                                Activity Number
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="activity_number"
                                value="<?php echo htmlspecialchars($activity_number); ?>"
                                required
                            >

                            <div class="small-help">
                                Activity number must be unique.
                            </div>

                        </div>


                        <!-- ACTIVITY TYPE -->

                        <div class="form-group">

                            <label>
                                Activity Type
                                <span>*</span>
                            </label>

                            <select
                                name="activity_type"
                                required
                            >

                                <option value="">
                                    Select activity type
                                </option>

                                <?php

                                $activity_types = [
                                    "Assessment",
                                    "Home Visit",
                                    "Office Visit",
                                    "Meeting",
                                    "Counselling",
                                    "Case Conference",
                                    "Training",
                                    "Workshop",
                                    "Community Awareness",
                                    "Referral",
                                    "Follow-up",
                                    "Other"
                                ];

                                foreach ($activity_types as $type):

                                ?>

                                    <option
                                        value="<?php echo htmlspecialchars($type); ?>"
                                        <?php
                                        echo ($activity_type === $type)
                                            ? "selected"
                                            : "";
                                        ?>
                                    >

                                        <?php echo htmlspecialchars($type); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- DATE -->

                        <div class="form-group">

                            <label>
                                Activity Date
                                <span>*</span>
                            </label>

                            <input
                                type="date"
                                name="activity_date"
                                value="<?php echo htmlspecialchars($activity_date); ?>"
                                required
                            >

                        </div>


                        <!-- LOCATION -->

                        <div class="form-group">

                            <label>
                                Location
                            </label>

                            <input
                                type="text"
                                name="location"
                                value="<?php echo htmlspecialchars($location ?? ""); ?>"
                                placeholder="Where did the activity take place?"
                            >

                        </div>


                        <!-- CLIENT -->

                        <div class="form-group">

                            <label>
                                Related Client
                            </label>

                            <select
                                name="client_id"
                                id="client_id"
                            >

                                <option value="0">
                                    -- No Client Linked --
                                </option>

                                <?php if ($clients): ?>

                                    <?php while ($client = $clients->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo (int)$client["client_id"]; ?>"
                                            <?php
                                            echo (
                                                (int)$client["client_id"] ===
                                                (int)$client_id
                                            )
                                                ? "selected"
                                                : "";
                                            ?>
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $client["client_number"] .
                                                " - " .
                                                $client["full_name"]
                                            );
                                            ?>

                                        </option>

                                    <?php endwhile; ?>

                                <?php endif; ?>

                            </select>

                        </div>


                        <!-- CASE -->

                        <div class="form-group">

                            <label>
                                Related Case
                            </label>

                            <select
                                name="case_id"
                                id="case_id"
                            >

                                <option value="0">
                                    -- No Case Linked --
                                </option>

                                <?php if ($cases): ?>

                                    <?php while ($case = $cases->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo (int)$case["case_id"]; ?>"
                                            data-client="<?php echo (int)$case["client_id"]; ?>"
                                            <?php
                                            echo (
                                                (int)$case["case_id"] ===
                                                (int)$case_id
                                            )
                                                ? "selected"
                                                : "";
                                            ?>
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $case["case_number"] .
                                                " - " .
                                                $case["case_type"] .
                                                " (" .
                                                $case["status"] .
                                                ")"
                                            );
                                            ?>

                                        </option>

                                    <?php endwhile; ?>

                                <?php endif; ?>

                            </select>

                            <div class="small-help">
                                Cases are filtered according to the selected client.
                            </div>

                        </div>


                        <!-- WORKER -->

                        <div class="form-group">

                            <label>
                                Social Worker
                            </label>

                            <select name="worker_id">

                                <option value="0">
                                    -- No Social Worker Assigned --
                                </option>

                                <?php if ($social_workers): ?>

                                    <?php while ($worker = $social_workers->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo (int)$worker["worker_id"]; ?>"
                                            <?php
                                            echo (
                                                (int)$worker["worker_id"] ===
                                                (int)$worker_id
                                            )
                                                ? "selected"
                                                : "";
                                            ?>
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $worker["worker_number"] .
                                                " - " .
                                                $worker["full_name"] .
                                                (
                                                    !empty($worker["position"])
                                                        ? " (" . $worker["position"] . ")"
                                                        : ""
                                                )
                                            );

                                            ?>

                                        </option>

                                    <?php endwhile; ?>

                                <?php endif; ?>

                            </select>

                        </div>


                        <!-- PARTICIPANTS -->

                        <div class="form-group">

                            <label>
                                Participants
                            </label>

                            <input
                                type="text"
                                name="participants"
                                value="<?php echo htmlspecialchars($participants ?? ""); ?>"
                                placeholder="Example: 50"
                            >

                        </div>


                        <!-- DESCRIPTION -->

                        <div class="form-group full">

                            <label>
                                Activity Description
                            </label>

                            <textarea
                                name="description"
                                placeholder="Describe what happened during the activity..."
                            ><?php echo htmlspecialchars($description ?? ""); ?></textarea>

                        </div>


                        <!-- OUTCOME -->

                        <div class="form-group full">

                            <label>
                                Outcome
                            </label>

                            <textarea
                                name="outcome"
                                placeholder="Describe the outcome or result..."
                            ><?php echo htmlspecialchars($outcome ?? ""); ?></textarea>

                        </div>


                        <!-- NOTES -->

                        <div class="form-group full">

                            <label>
                                Notes
                            </label>

                            <textarea
                                name="notes"
                                placeholder="Add any additional notes..."
                            ><?php echo htmlspecialchars($notes ?? ""); ?></textarea>

                        </div>


                    </div>


                    <!-- ACTIONS -->

                    <div class="form-actions">

                        <a
                            href="view.php?id=<?php echo $activity_id; ?>"
                            class="btn btn-cancel"
                        >

                            <i class="fas fa-times"></i>

                            Cancel

                        </a>


                        <button
                            type="submit"
                            class="btn btn-save"
                        >

                            <i class="fas fa-save"></i>

                            Save Changes

                        </button>

                    </div>


                </form>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const clientSelect =
        document.getElementById("client_id");

    const caseSelect =
        document.getElementById("case_id");

    if (!clientSelect || !caseSelect) {
        return;
    }


    const allCases =
        Array.from(caseSelect.options).map(function(option) {

            return {
                value: option.value,
                text: option.text,
                client: option.dataset.client || ""
            };

        });


    const currentCase =
        <?php echo (int)$case_id; ?>;


    function filterCases() {

        const selectedClient =
            clientSelect.value;

        caseSelect.innerHTML = "";


        const defaultOption =
            document.createElement("option");

        defaultOption.value = "0";

        defaultOption.textContent =
            "-- No Case Linked --";

        caseSelect.appendChild(defaultOption);


        allCases.forEach(function(item) {

            if (
                item.value === "0" ||
                selectedClient === "0" ||
                item.client === selectedClient
            ) {

                const option =
                    document.createElement("option");

                option.value = item.value;

                option.textContent = item.text;

                option.dataset.client =
                    item.client;


                if (
                    parseInt(item.value) ===
                    parseInt(currentCase)
                ) {
                    option.selected = true;
                }


                caseSelect.appendChild(option);

            }

        });

    }


    clientSelect.addEventListener(
        "change",
        filterCases
    );


    filterCases();

});

</script>


</body>
</html>