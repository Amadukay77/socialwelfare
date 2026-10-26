 <?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$error = "";

/* =========================
   DEFAULT VALUES
========================= */

$appointment_number = "APT-" . date("YmdHis") . "-" . rand(100, 999);
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
   SAVE APPOINTMENT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $appointment_number = trim($_POST["appointment_number"] ?? "");
    $client_id = (int)($_POST["client_id"] ?? 0);
    $worker_id = (int)($_POST["worker_id"] ?? 0);

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

        $error = "Appointment number is required.";

    } elseif ($client_id <= 0) {

        $error = "Please select a client.";

    } elseif ($appointment_date === "") {

        $error = "Appointment date is required.";

    } elseif ($appointment_type === "") {

        $error = "Appointment type is required.";

    } elseif (
        !in_array(
            $status,
            [
                "Scheduled",
                "Completed",
                "Cancelled",
                "Missed",
                "Rescheduled"
            ]
        )
    ) {

        $error = "Invalid appointment status.";

    } else {


        /* =========================
           CHECK DUPLICATE NUMBER
        ========================= */

        $check = $conn->prepare("
            SELECT appointment_id
            FROM appointments
            WHERE appointment_number = ?
            LIMIT 1
        ");

        if (!$check) {

            $error = "Database error: " . $conn->error;

        } else {

            $check->bind_param(
                "s",
                $appointment_number
            );

            $check->execute();

            $check_result = $check->get_result();


            if ($check_result->num_rows > 0) {

                $error = "This appointment number already exists.";

            } else {


                /* =========================
                   CHECK CLIENT
                ========================= */

                $client_check = $conn->prepare("
                    SELECT client_id
                    FROM clients
                    WHERE client_id = ?
                    LIMIT 1
                ");

                if (!$client_check) {

                    $error = "Database error checking client: " . $conn->error;

                } else {

                    $client_check->bind_param(
                        "i",
                        $client_id
                    );

                    $client_check->execute();

                    $client_result = $client_check->get_result();


                    if ($client_result->num_rows === 0) {

                        $error = "The selected client does not exist.";

                    } else {


                        /* =========================
                           INSERT
                        ========================= */

                        $insert = $conn->prepare("
                            INSERT INTO appointments (
                                appointment_number,
                                client_id,
                                worker_id,
                                appointment_date,
                                appointment_time,
                                appointment_type,
                                location,
                                purpose,
                                status,
                                notes
                            )
                            VALUES (
                                ?,
                                ?,
                                NULLIF(?, 0),
                                ?,
                                NULLIF(?, ''),
                                ?,
                                ?,
                                ?,
                                ?,
                                ?
                            )
                        ");


                        if (!$insert) {

                            $error = "Database prepare error: " . $conn->error;

                        } else {


                            $insert->bind_param(
                                "siisssssss",
                                $appointment_number,
                                $client_id,
                                $worker_id,
                                $appointment_date,
                                $appointment_time,
                                $appointment_type,
                                $location,
                                $purpose,
                                $status,
                                $notes
                            );


                            if ($insert->execute()) {

                                $new_id = $conn->insert_id;

                                $insert->close();
                                $client_check->close();
                                $check->close();

                                header(
                                    "Location: view.php?id="
                                    . $new_id
                                    . "&success="
                                    . urlencode(
                                        "Appointment recorded successfully."
                                    )
                                );

                                exit;

                            } else {

                                /*
                                 * SHOW THE REAL MYSQL ERROR
                                 * IF SOMETHING GOES WRONG.
                                 */

                                $error =
                                    "Unable to save appointment. "
                                    . "Database error: "
                                    . $insert->error;

                                $insert->close();
                            }
                        }
                    }

                    $client_check->close();
                }
            }

            $check->close();
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
   LOAD SOCIAL WORKERS
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Add Appointment | Tonkolili District Social Welfare
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

.icon-dashboard { color: #42a5f5; }
.icon-client { color: #66bb6a; }
.icon-case { color: #ef5350; }
.icon-document { color: #ab47bc; }
.icon-worker { color: #ffa726; }
.icon-household { color: #26c6da; }
.icon-activity { color: #ffca28; }
.icon-appointment { color: #ec407a; }
.icon-report { color: #7e57c2; }
.icon-settings { color: #78909c; }
.icon-logout { color: #ef5350; }


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
    max-width: 1100px;
}

.page-title {
    margin-bottom: 25px;
}

.page-title h2 {
    margin: 0 0 6px;
    font-size: 26px;
}

.page-title p {
    margin: 0;
    color: #777;
}


/* =========================
   CARD
========================= */

.card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 28px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}


/* =========================
   ERROR
========================= */

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-error {
    background: #ffebee;
    color: #b71c1c;
    border: 1px solid #ef9a9a;
}


/* =========================
   FORM
========================= */

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}

.section-title {
    grid-column: 1 / -1;

    font-size: 16px;
    font-weight: bold;

    padding-bottom: 10px;

    margin-top: 5px;

    border-bottom: 1px solid #eee;
}

.section-title i {
    color: #e53935;
    margin-right: 7px;
}

label {
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 7px;
    color: #444;
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
    border-radius: 5px;

    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;

    background: white;
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: #e53935;
}

textarea {
    min-height: 110px;
    resize: vertical;
}

.help-text {
    margin-top: 5px;
    font-size: 11px;
    color: #888;
}


/* =========================
   BUTTONS
========================= */

.form-actions {
    display: flex;
    gap: 10px;

    margin-top: 28px;
    padding-top: 20px;

    border-top: 1px solid #eee;
}

.btn {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 8px;

    padding: 12px 20px;

    border-radius: 5px;

    border: none;

    text-decoration: none;

    font-size: 14px;

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

.btn-secondary:hover {
    background: #333;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 850px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
    }

    .content {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full,
    .section-title {
        grid-column: auto;
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
            Add Appointment
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


        <div class="page-title">

            <h2>
                Schedule New Appointment
            </h2>

            <p>
                Enter the appointment details below.
            </p>

        </div>


        <?php if ($error !== ""): ?>

            <div class="alert alert-error">

                <i class="fas fa-exclamation-circle"></i>

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <div class="card">

            <form method="POST">


                <div class="form-grid">


                    <div class="section-title">

                        <i class="fas fa-calendar-alt"></i>

                        Appointment Information

                    </div>


                    <div class="form-group">

                        <label>
                            Appointment Number
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="appointment_number"
                            value="<?php echo htmlspecialchars($appointment_number); ?>"
                            required
                        >

                        <div class="help-text">
                            You can edit the generated appointment number.
                        </div>

                    </div>


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
                                -- Select Appointment Type --
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
                                    value="<?php echo htmlspecialchars($type); ?>"
                                    <?php
                                    if ($appointment_type === $type) {
                                        echo "selected";
                                    }
                                    ?>
                                >
                                    <?php echo htmlspecialchars($type); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


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
                                -- Select Client --
                            </option>

                            <?php if ($clients): ?>

                                <?php while ($client = $clients->fetch_assoc()): ?>

                                    <option
                                        value="<?php echo $client["client_id"]; ?>"
                                        <?php
                                        if (
                                            $client_id ==
                                            $client["client_id"]
                                        ) {
                                            echo "selected";
                                        }
                                        ?>
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $client["client_number"]
                                            . " - "
                                            . $client["full_name"]
                                        );
                                        ?>

                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Social Worker
                        </label>

                        <select name="worker_id">

                            <option value="0">
                                -- Not Assigned --
                            </option>

                            <?php if ($social_workers): ?>

                                <?php while ($worker = $social_workers->fetch_assoc()): ?>

                                    <option
                                        value="<?php echo $worker["worker_id"]; ?>"
                                        <?php
                                        if (
                                            $worker_id ==
                                            $worker["worker_id"]
                                        ) {
                                            echo "selected";
                                        }
                                        ?>
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $worker["worker_number"]
                                            . " - "
                                            . $worker["full_name"]
                                        );
                                        ?>

                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                    </div>


                    <div class="section-title">

                        <i class="fas fa-clock"></i>

                        Date and Time

                    </div>


                    <div class="form-group">

                        <label>
                            Appointment Date
                            <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            name="appointment_date"
                            value="<?php echo htmlspecialchars($appointment_date); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Appointment Time
                        </label>

                        <input
                            type="time"
                            name="appointment_time"
                            value="<?php echo htmlspecialchars($appointment_time); ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            value="<?php echo htmlspecialchars($location); ?>"
                            placeholder="e.g. Social Welfare Office"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Status
                        </label>

                        <select name="status">

                            <option value="Scheduled">
                                Scheduled
                            </option>

                            <option value="Completed">
                                Completed
                            </option>

                            <option value="Cancelled">
                                Cancelled
                            </option>

                            <option value="Missed">
                                Missed
                            </option>

                            <option value="Rescheduled">
                                Rescheduled
                            </option>

                        </select>

                    </div>


                    <div class="section-title">

                        <i class="fas fa-align-left"></i>

                        Appointment Details

                    </div>


                    <div class="form-group full">

                        <label>
                            Purpose
                        </label>

                        <textarea
                            name="purpose"
                            placeholder="Enter the reason for the appointment..."
                        ><?php echo htmlspecialchars($purpose); ?></textarea>

                    </div>


                    <div class="form-group full">

                        <label>
                            Notes
                        </label>

                        <textarea
                            name="notes"
                            placeholder="Enter additional notes..."
                        ><?php echo htmlspecialchars($notes); ?></textarea>

                    </div>


                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-save"></i>

                        Save Appointment

                    </button>


                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >

                        <i class="fas fa-arrow-left"></i>

                        Cancel

                    </a>

                </div>


            </form>

        </div>

    </div>

</div>

</body>

</html>