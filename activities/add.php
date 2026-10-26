 <?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$message_type = "";

/*
|--------------------------------------------------------------------------
| Generate Activity Number
|--------------------------------------------------------------------------
*/

function generateActivityNumber($conn)
{
    do {

        $number = "ACT-" . date("YmdHis") . "-" . rand(100, 999);

        $check = $conn->prepare("
            SELECT activity_id
            FROM activities
            WHERE activity_number = ?
            LIMIT 1
        ");

        $check->bind_param("s", $number);
        $check->execute();

        $result = $check->get_result();

        $exists = $result->num_rows > 0;

        $check->close();

    } while ($exists);

    return $number;
}

$activity_number = generateActivityNumber($conn);


/*
|--------------------------------------------------------------------------
| Load Clients
|--------------------------------------------------------------------------
*/

$clients = $conn->query("
    SELECT
        client_id,
        client_number,
        full_name
    FROM clients
    ORDER BY full_name ASC
");


/*
|--------------------------------------------------------------------------
| Load Cases
|--------------------------------------------------------------------------
*/

$cases = $conn->query("
    SELECT
        c.case_id,
        c.case_number,
        c.case_type,
        c.status,
        c.client_id,
        cl.full_name
    FROM cases c
    LEFT JOIN clients cl
        ON c.client_id = cl.client_id
    ORDER BY c.created_at DESC
");


/*
|--------------------------------------------------------------------------
| Load Active Social Workers
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Save Activity
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $activity_number = trim($_POST["activity_number"] ?? "");
    $activity_type   = trim($_POST["activity_type"] ?? "");
    $activity_date   = trim($_POST["activity_date"] ?? "");
    $location        = trim($_POST["location"] ?? "");

    $client_id = !empty($_POST["client_id"])
        ? (int) $_POST["client_id"]
        : null;

    $case_id = !empty($_POST["case_id"])
        ? (int) $_POST["case_id"]
        : null;

    $worker_id = !empty($_POST["worker_id"])
        ? (int) $_POST["worker_id"]
        : null;

    $participants = trim($_POST["participants"] ?? "");
    $description  = trim($_POST["description"] ?? "");
    $outcome      = trim($_POST["outcome"] ?? "");
    $notes        = trim($_POST["notes"] ?? "");


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($activity_number === "") {

        $message = "Please enter an activity number.";
        $message_type = "error";

    } elseif ($activity_type === "") {

        $message = "Please select an activity type.";
        $message_type = "error";

    } elseif ($activity_date === "") {

        $message = "Please select the activity date.";
        $message_type = "error";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check Duplicate Activity Number
        |--------------------------------------------------------------------------
        */

        $check_number = $conn->prepare("
            SELECT activity_id
            FROM activities
            WHERE activity_number = ?
            LIMIT 1
        ");

        $check_number->bind_param("s", $activity_number);
        $check_number->execute();

        $number_result = $check_number->get_result();

        if ($number_result->num_rows > 0) {

            $message = "This activity number already exists. Please enter another one.";
            $message_type = "error";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Verify Client
            |--------------------------------------------------------------------------
            */

            if ($client_id !== null) {

                $check_client = $conn->prepare("
                    SELECT client_id
                    FROM clients
                    WHERE client_id = ?
                    LIMIT 1
                ");

                $check_client->bind_param("i", $client_id);
                $check_client->execute();

                $client_result = $check_client->get_result();

                if ($client_result->num_rows === 0) {
                    $client_id = null;
                }

                $check_client->close();
            }


            /*
            |--------------------------------------------------------------------------
            | Verify Case
            |--------------------------------------------------------------------------
            */

            if ($case_id !== null) {

                $check_case = $conn->prepare("
                    SELECT case_id, client_id
                    FROM cases
                    WHERE case_id = ?
                    LIMIT 1
                ");

                $check_case->bind_param("i", $case_id);
                $check_case->execute();

                $case_result = $check_case->get_result();

                if ($case_result->num_rows === 0) {

                    $case_id = null;

                } else {

                    $case_data = $case_result->fetch_assoc();

                    if (
                        $client_id !== null &&
                        (int)$case_data["client_id"] !== $client_id
                    ) {
                        $case_id = null;
                    }
                }

                $check_case->close();
            }


            /*
            |--------------------------------------------------------------------------
            | Verify Social Worker
            |--------------------------------------------------------------------------
            */

            if ($worker_id !== null) {

                $check_worker = $conn->prepare("
                    SELECT worker_id
                    FROM social_workers
                    WHERE worker_id = ?
                    AND status = 'Active'
                    LIMIT 1
                ");

                $check_worker->bind_param("i", $worker_id);
                $check_worker->execute();

                $worker_result = $check_worker->get_result();

                if ($worker_result->num_rows === 0) {
                    $worker_id = null;
                }

                $check_worker->close();
            }


            /*
            |--------------------------------------------------------------------------
            | Convert Empty IDs To NULL
            |--------------------------------------------------------------------------
            */

            $client_value = $client_id ?? 0;
            $case_value   = $case_id ?? 0;
            $worker_value = $worker_id ?? 0;


            /*
            |--------------------------------------------------------------------------
            | Insert Activity
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                INSERT INTO activities
                (
                    activity_number,
                    activity_type,
                    activity_date,
                    location,
                    description,
                    client_id,
                    case_id,
                    worker_id,
                    participants,
                    outcome,
                    notes
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NULLIF(?, 0),
                    NULLIF(?, 0),
                    NULLIF(?, 0),
                    ?,
                    ?,
                    ?
                )
            ");


            if (!$stmt) {

                $message = "Database error: " . $conn->error;
                $message_type = "error";

            } else {

                $stmt->bind_param(
                    "sssssiiisss",
                    $activity_number,
                    $activity_type,
                    $activity_date,
                    $location,
                    $description,
                    $client_value,
                    $case_value,
                    $worker_value,
                    $participants,
                    $outcome,
                    $notes
                );


                if ($stmt->execute()) {

                    $stmt->close();
                    $check_number->close();

                    header(
                        "Location: index.php?success=" .
                        urlencode("Activity recorded successfully.")
                    );

                    exit;

                } else {

                    $message = "Unable to save activity: " . $stmt->error;
                    $message_type = "error";

                    $stmt->close();
                }
            }
        }

        $check_number->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Add Activity - Tonkolili District Social Welfare
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


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;

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
    font-size: 19px;
}

.sidebar-header p {
    margin: 6px 0 0;
    color: #aaa;
    font-size: 13px;
}

.menu-title {
    padding: 18px 20px 8px;
    color: #777;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.sidebar a {
    display: flex;
    align-items: center;

    padding: 12px 20px;

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
    width: 30px;
    font-size: 16px;
}

.icon-dashboard {
    color: #42a5f5;
}

.icon-clients {
    color: #66bb6a;
}

.icon-cases {
    color: #ff7043;
}

.icon-documents {
    color: #ab47bc;
}

.icon-workers {
    color: #29b6f6;
}

.icon-households {
    color: #ffa726;
}

.icon-activities {
    color: #ef5350;
}

.icon-appointments {
    color: #26a69a;
}

.icon-reports {
    color: #7e57c2;
}

.icon-settings {
    color: #bdbdbd;
}

.icon-logout {
    color: #ef5350;
}


/* =========================================================
   MAIN
========================================================= */

.main {
    margin-left: 250px;
    min-height: 100vh;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {
    height: 65px;

    background: white;

    border-bottom: 1px solid #ddd;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;
}

.topbar h1 {
    margin: 0;
    font-size: 20px;
}

.user-info {
    color: #555;

    font-size: 14px;

    display: flex;

    align-items: center;

    gap: 8px;
}


/* =========================================================
   CONTENT
========================================================= */

.content {
    padding: 30px;
    max-width: 1150px;
}

.page-title {
    margin-bottom: 25px;
}

.page-title h2 {
    margin: 0 0 7px;

    font-size: 25px;

    font-weight: 600;
}

.page-title h2 i {
    color: #e53935;
    margin-right: 7px;
}

.page-title p {
    margin: 0;
    color: #777;
    font-size: 14px;
}


/* =========================================================
   ALERT
========================================================= */

.alert {
    padding: 14px 16px;

    border-radius: 6px;

    margin-bottom: 20px;

    font-size: 14px;
}

.alert-error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}


/* =========================================================
   FORM CARD
========================================================= */

.form-card {
    background: white;

    border-radius: 8px;

    padding: 30px;

    box-shadow:
        0 2px 8px rgba(0,0,0,0.06);
}


/* =========================================================
   FORM GRID
========================================================= */

.form-grid {
    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 22px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}


/* =========================================================
   LABELS
========================================================= */

label {
    margin-bottom: 7px;

    font-size: 14px;

    font-weight: 600;

    color: #333;
}

.required {
    color: #e53935;
}


/* =========================================================
   INPUTS
========================================================= */

input,
select,
textarea {
    width: 100%;

    padding: 12px 13px;

    border: 1px solid #ccc;

    border-radius: 5px;

    background: white;

    color: #222;

    font-family: inherit;

    font-size: 14px;

    outline: none;

    transition: 0.2s;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #e53935;

    box-shadow:
        0 0 0 2px rgba(229,57,53,0.08);
}

textarea {
    min-height: 115px;
    resize: vertical;
}


/* =========================================================
   HELP
========================================================= */

.form-help {
    margin-top: 6px;

    color: #777;

    font-size: 12px;
}


/* =========================================================
   BUTTONS
========================================================= */

.form-actions {
    display: flex;

    gap: 10px;

    margin-top: 28px;

    padding-top: 22px;

    border-top: 1px solid #eee;
}

.btn {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 11px 18px;

    border: none;

    border-radius: 5px;

    font-size: 14px;

    font-weight: 600;

    text-decoration: none;

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


/* =========================================================
   RESPONSIVE
========================================================= */

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
        padding: 0 18px;
    }

    .content {
        padding: 20px 15px;
    }

    .form-card {
        padding: 20px;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }
}

</style>

</head>

<body>


<!-- =====================================================
     SIDEBAR
====================================================== -->

<div class="sidebar">

    <div class="sidebar-header">

        <h2>
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


    <!-- TOPBAR -->

    <div class="topbar">

        <h1>
            Activities
        </h1>


        <div class="user-info">

            <i class="fas fa-user"></i>

            <?php

            echo htmlspecialchars(
                $_SESSION["full_name"] ?? "User"
            );

            ?>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="content">


        <div class="page-title">

            <h2>

                <i class="fas fa-plus-circle"></i>

                Record New Activity

            </h2>


            <p>

                Record a social welfare visit,
                meeting, intervention, outreach
                or other activity.

            </p>

        </div>


        <?php if ($message !== ""): ?>

            <div class="alert alert-error">

                <i class="fas fa-exclamation-circle"></i>

                <?php

                echo htmlspecialchars($message);

                ?>

            </div>

        <?php endif; ?>


        <div class="form-card">


            <form method="POST"
                  autocomplete="off">


                <div class="form-grid">


                    <!-- ACTIVITY NUMBER -->

                    <div class="form-group">

                        <label>

                            Activity Number
                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            name="activity_number"
                            id="activity_number"
                            value="<?php

                                echo htmlspecialchars(
                                    $_POST["activity_number"]
                                    ?? $activity_number
                                );

                            ?>"
                            placeholder="Enter activity number"
                            required
                        >


                        <div class="form-help">

                            An activity number is generated
                            automatically, but you can edit it.

                        </div>

                    </div>


                    <!-- ACTIVITY TYPE -->

                    <div class="form-group">

                        <label>

                            Activity Type
                            <span class="required">*</span>

                        </label>


                        <select
                            name="activity_type"
                            required
                        >

                            <option value="">
                                -- Select Activity Type --
                            </option>


                            <?php

                            $activity_types = [

                                "Home Visit",
                                "Community Meeting",
                                "Client Interview",
                                "Case Conference",
                                "Counselling",
                                "Assessment",
                                "Follow-up Visit",
                                "Community Outreach",
                                "Training",
                                "Workshop",
                                "Referral",
                                "Awareness Campaign",
                                "Distribution",
                                "Monitoring Visit",
                                "Stakeholder Meeting",
                                "Other"

                            ];

                            foreach (
                                $activity_types
                                as $type
                            ):

                            ?>

                                <option
                                    value="<?php
                                        echo htmlspecialchars($type);
                                    ?>"
                                    <?php

                                    echo (
                                        ($_POST["activity_type"] ?? "")
                                        === $type
                                    )
                                    ? "selected"
                                    : "";

                                    ?>
                                >

                                    <?php

                                    echo htmlspecialchars($type);

                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- ACTIVITY DATE -->

                    <div class="form-group">

                        <label>

                            Activity Date
                            <span class="required">*</span>

                        </label>


                        <input
                            type="date"
                            name="activity_date"
                            value="<?php

                                echo htmlspecialchars(
                                    $_POST["activity_date"]
                                    ?? date("Y-m-d")
                                );

                            ?>"
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
                            placeholder="Enter activity location"
                            value="<?php

                                echo htmlspecialchars(
                                    $_POST["location"] ?? ""
                                );

                            ?>"
                        >

                    </div>


                    <!-- CLIENT -->

                    <div class="form-group">

                        <label>
                            Client
                        </label>


                        <select
                            name="client_id"
                            id="client_id"
                        >

                            <option value="">
                                -- No Specific Client --
                            </option>


                            <?php if ($clients): ?>

                                <?php while (
                                    $client =
                                    $clients->fetch_assoc()
                                ): ?>

                                    <option
                                        value="<?php

                                            echo (int)
                                            $client["client_id"];

                                        ?>"
                                        <?php

                                        echo (
                                            isset(
                                                $_POST["client_id"]
                                            ) &&
                                            $_POST["client_id"] ==
                                            $client["client_id"]
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

                            <option value="">
                                -- No Specific Case --
                            </option>


                            <?php if ($cases): ?>

                                <?php while (
                                    $case =
                                    $cases->fetch_assoc()
                                ): ?>

                                    <option
                                        value="<?php

                                            echo (int)
                                            $case["case_id"];

                                        ?>"

                                        data-client="<?php

                                            echo (int)
                                            $case["client_id"];

                                        ?>"

                                        <?php

                                        echo (
                                            isset(
                                                $_POST["case_id"]
                                            ) &&
                                            $_POST["case_id"] ==
                                            $case["case_id"]
                                        )
                                        ? "selected"
                                        : "";

                                        ?>
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $case["case_number"] .
                                            " - " .
                                            $case["full_name"] .
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


                        <div class="form-help">

                            Selecting a client filters
                            the related cases.

                        </div>

                    </div>


                    <!-- SOCIAL WORKER -->

                    <div class="form-group">

                        <label>
                            Social Worker
                        </label>


                        <select
                            name="worker_id"
                        >

                            <option value="">
                                -- Select Social Worker --
                            </option>


                            <?php if ($social_workers): ?>

                                <?php while (
                                    $worker =
                                    $social_workers->fetch_assoc()
                                ): ?>

                                    <option
                                        value="<?php

                                            echo (int)
                                            $worker["worker_id"];

                                        ?>"

                                        <?php

                                        echo (
                                            isset(
                                                $_POST["worker_id"]
                                            ) &&
                                            $_POST["worker_id"] ==
                                            $worker["worker_id"]
                                        )
                                        ? "selected"
                                        : "";

                                        ?>
                                    >

                                        <?php

                                        $worker_label =
                                            $worker["full_name"];

                                        if (
                                            !empty(
                                                $worker["position"]
                                            )
                                        ) {

                                            $worker_label .=
                                                " - " .
                                                $worker["position"];

                                        }

                                        echo htmlspecialchars(
                                            $worker_label
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
                            placeholder="e.g. 25 community members"
                            value="<?php

                                echo htmlspecialchars(
                                    $_POST["participants"] ?? ""
                                );

                            ?>"
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
                        ><?php

                        echo htmlspecialchars(
                            $_POST["description"] ?? ""
                        );

                        ?></textarea>

                    </div>


                    <!-- OUTCOME -->

                    <div class="form-group full">

                        <label>
                            Outcome
                        </label>


                        <textarea
                            name="outcome"
                            placeholder="Describe the result or outcome of the activity..."
                        ><?php

                        echo htmlspecialchars(
                            $_POST["outcome"] ?? ""
                        );

                        ?></textarea>

                    </div>


                    <!-- NOTES -->

                    <div class="form-group full">

                        <label>
                            Additional Notes
                        </label>


                        <textarea
                            name="notes"
                            placeholder="Enter any additional information..."
                        ><?php

                        echo htmlspecialchars(
                            $_POST["notes"] ?? ""
                        );

                        ?></textarea>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-save"></i>

                        Save Activity

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


<script>

/*
|--------------------------------------------------------------------------
| CLIENT → CASE FILTER
|--------------------------------------------------------------------------
*/

const clientSelect =
    document.getElementById("client_id");

const caseSelect =
    document.getElementById("case_id");


function filterCases() {

    const selectedClient =
        clientSelect.value;

    const options =
        caseSelect.querySelectorAll("option");


    options.forEach(function(option) {

        if (option.value === "") {

            option.style.display = "block";

            return;
        }


        const caseClient =
            option.getAttribute("data-client");


        if (
            selectedClient === "" ||
            caseClient === selectedClient
        ) {

            option.style.display = "block";

        } else {

            option.style.display = "none";

            if (option.selected) {
                caseSelect.value = "";
            }

        }

    });
}


clientSelect.addEventListener(
    "change",
    filterCases
);


filterCases();

</script>

</body>

</html>