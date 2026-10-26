 <?php

session_start();

require_once "../includes/db.php";

/* =====================================================
   LOGIN CHECK
===================================================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}


/* =====================================================
   CHECK CASE ID
===================================================== */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$case_id = (int) $_GET["id"];

$error = "";


/* =====================================================
   GET CASE
===================================================== */

$stmt = $conn->prepare("
    SELECT *
    FROM cases
    WHERE case_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $case_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit;
}

$case = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   GET CLIENTS
===================================================== */

$clients = $conn->query("
    SELECT
        client_id,
        client_number,
        full_name
    FROM clients
    ORDER BY full_name ASC
");


/* =====================================================
   GET SOCIAL WORKERS

   IMPORTANT:
   Your social_workers table uses worker_id,
   NOT user_id.
===================================================== */

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


/* =====================================================
   UPDATE CASE
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CLIENT */

    $client_id = (int) ($_POST["client_id"] ?? 0);


    /* CASE NUMBER */

    $case_number = trim(
        $_POST["case_number"] ?? ""
    );


    /* CASE TYPE */

    $case_type = trim(
        $_POST["case_type"] ?? ""
    );


    /* DESCRIPTION */

    $description = trim(
        $_POST["description"] ?? ""
    );


    /* PRIORITY */

    $priority = $_POST["priority"] ?? "Medium";


    /* STATUS */

    $status = $_POST["status"] ?? "Open";


    /* SOCIAL WORKER */

    if (
        isset($_POST["assigned_to"]) &&
        $_POST["assigned_to"] !== ""
    ) {

        $assigned_to = (int) $_POST["assigned_to"];

    } else {

        $assigned_to = null;
    }


    /* DATE OPENED */

    $date_opened = $_POST["date_opened"] ?? "";


    /* DATE CLOSED */

    $date_closed = !empty($_POST["date_closed"])
        ? $_POST["date_closed"]
        : null;


    /* =================================================
       VALIDATION
    ================================================= */

    if ($client_id <= 0) {

        $error = "Please select a client.";

    } elseif (empty($case_number)) {

        $error = "Please enter the case number.";

    } elseif (empty($case_type)) {

        $error = "Please select the case type.";

    } elseif (empty($date_opened)) {

        $error = "Please select the date the case was opened.";

    } else {

        /* =================================================
           CHECK CASE NUMBER DUPLICATE
        ================================================= */

        $check = $conn->prepare("
            SELECT case_id
            FROM cases
            WHERE case_number = ?
            AND case_id != ?
            LIMIT 1
        ");

        $check->bind_param(
            "si",
            $case_number,
            $case_id
        );

        $check->execute();

        $duplicate = $check->get_result();

        if ($duplicate->num_rows > 0) {

            $error = "This case number is already being used by another case.";

            $check->close();

        } else {

            $check->close();


            /* =================================================
               VERIFY SOCIAL WORKER

               This prevents the foreign-key error.
            ================================================= */

            if ($assigned_to !== null) {

                $worker_check = $conn->prepare("
                    SELECT worker_id
                    FROM social_workers
                    WHERE worker_id = ?
                    AND status = 'Active'
                    LIMIT 1
                ");

                $worker_check->bind_param(
                    "i",
                    $assigned_to
                );

                $worker_check->execute();

                $worker_result =
                    $worker_check->get_result();

                if ($worker_result->num_rows === 0) {

                    $error =
                        "The selected social worker is not valid.";

                    $assigned_to = null;
                }

                $worker_check->close();
            }


            /* =================================================
               UPDATE DATABASE
            ================================================= */

            if (empty($error)) {

                $stmt = $conn->prepare("
                    UPDATE cases
                    SET
                        client_id = ?,
                        case_number = ?,
                        case_type = ?,
                        description = ?,
                        priority = ?,
                        status = ?,
                        assigned_to = ?,
                        date_opened = ?,
                        date_closed = ?
                    WHERE case_id = ?
                ");


                /*
                   10 VALUES

                   1  client_id
                   2  case_number
                   3  case_type
                   4  description
                   5  priority
                   6  status
                   7  assigned_to
                   8  date_opened
                   9  date_closed
                   10 case_id
                */

                $stmt->bind_param(
                    "isssssissi",
                    $client_id,
                    $case_number,
                    $case_type,
                    $description,
                    $priority,
                    $status,
                    $assigned_to,
                    $date_opened,
                    $date_closed,
                    $case_id
                );


                try {

                    if ($stmt->execute()) {

                        $stmt->close();

                        header(
                            "Location: view.php?id=" .
                            $case_id .
                            "&success=Case updated successfully"
                        );

                        exit;

                    } else {

                        $error =
                            "Unable to update the case.";

                        $stmt->close();
                    }

                } catch (mysqli_sql_exception $e) {

                    $error =
                        "Database error: " .
                        $e->getMessage();

                    $stmt->close();
                }
            }
        }
    }


    /* =================================================
       KEEP FORM VALUES AFTER ERROR
    ================================================= */

    $case["client_id"] =
        $client_id;

    $case["case_number"] =
        $case_number;

    $case["case_type"] =
        $case_type;

    $case["description"] =
        $description;

    $case["priority"] =
        $priority;

    $case["status"] =
        $status;

    $case["assigned_to"] =
        $assigned_to;

    $case["date_opened"] =
        $date_opened;

    $case["date_closed"] =
        $date_closed;
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Case - Social Welfare System</title>


<!-- FONT AWESOME -->

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
>


<style>

/* =====================================================
   GENERAL
===================================================== */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f5f7;

    color: #222;
}


/* =====================================================
   SIDEBAR
===================================================== */

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


.sidebar h2 {

    text-align: center;

    margin: 0 0 30px;

    font-size: 20px;
}


.sidebar h2 i {

    margin-right: 7px;

    color: #e53935;
}


.sidebar a {

    display: flex;

    align-items: center;

    gap: 14px;

    color: #ddd;

    text-decoration: none;

    padding: 14px 20px;

    font-size: 14px;

    transition: 0.2s;
}


.sidebar a:hover {

    background: #242424;

    color: white;
}


.sidebar a.active {

    background: #242424;

    border-left: 4px solid #e53935;

    color: white;
}


.sidebar a i {

    width: 22px;

    text-align: center;

    font-size: 16px;
}


/* =====================================================
   SIDEBAR ICON COLORS
===================================================== */

.icon-dashboard {
    color: #42a5f5;
}

.icon-clients {
    color: #66bb6a;
}

.icon-cases {
    color: #ffa726;
}

.icon-documents {
    color: #ab47bc;
}

.icon-households {
    color: #ec407a;
}

.icon-activities {
    color: #ffee58;
}

.icon-appointments {
    color: #29b6f6;
}

.icon-reports {
    color: #26a69a;
}

.icon-settings {
    color: #bdbdbd;
}

.icon-logout {
    color: #ef5350;
}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 250px;

    padding: 30px;

    min-height: 100vh;
}


/* =====================================================
   HEADER
===================================================== */

.page-header {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 25px;
}


.page-header h1 {

    margin: 0;

    font-size: 26px;
}


.page-header h1 i {

    color: #e53935;

    margin-right: 8px;
}


/* =====================================================
   BACK BUTTON
===================================================== */

.back-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #171717;

    color: white;

    text-decoration: none;

    padding: 11px 18px;

    border-radius: 6px;

    transition: 0.2s;
}


.back-btn:hover {

    background: #333;
}


/* =====================================================
   CARD
===================================================== */

.card {

    background: white;

    padding: 30px;

    border-radius: 10px;

    box-shadow:
        0 2px 8px rgba(0,0,0,0.08);

    max-width: 1000px;
}


/* =====================================================
   ERROR
===================================================== */

.error {

    background: #f8d7da;

    color: #842029;

    padding: 14px;

    border-radius: 7px;

    margin-bottom: 22px;

    border: 1px solid #f5c2c7;

    display: flex;

    align-items: flex-start;

    gap: 10px;

    line-height: 1.5;
}


/* =====================================================
   FORM GRID
===================================================== */

.form-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 20px;
}


.form-group {

    display: flex;

    flex-direction: column;
}


.form-group.full {

    grid-column: 1 / -1;
}


/* =====================================================
   LABELS
===================================================== */

label {

    font-weight: bold;

    margin-bottom: 8px;

    color: #333;
}


.required {

    color: #e53935;
}


/* =====================================================
   INPUTS
===================================================== */

input,
select,
textarea {

    width: 100%;

    padding: 13px;

    border: 1px solid #ccc;

    border-radius: 6px;

    font-size: 15px;

    background: white;

    color: #222;
}


input:focus,
select:focus,
textarea:focus {

    outline: none;

    border-color: #198754;

    box-shadow:
        0 0 0 3px
        rgba(25,135,84,0.10);
}


textarea {

    min-height: 150px;

    resize: vertical;
}


/* =====================================================
   CASE NUMBER
===================================================== */

.case-number-input {

    font-weight: bold;

    letter-spacing: 0.3px;

    background: #fff;
}


/* =====================================================
   HELP TEXT
===================================================== */

.help-text {

    margin-top: 6px;

    color: #777;

    font-size: 12px;

    line-height: 1.5;
}


/* =====================================================
   WORKER HELP
===================================================== */

.worker-help {

    margin-top: 6px;

    color: #777;

    font-size: 12px;

    line-height: 1.5;
}


/* =====================================================
   BUTTONS
===================================================== */

.buttons {

    margin-top: 28px;

    display: flex;

    gap: 12px;

    flex-wrap: wrap;
}


.update-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #198754;

    color: white;

    border: none;

    padding: 13px 24px;

    border-radius: 6px;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.2s;
}


.update-btn:hover {

    background: #146c43;
}


.cancel-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #555;

    color: white;

    text-decoration: none;

    padding: 13px 24px;

    border-radius: 6px;

    font-weight: bold;

    transition: 0.2s;
}


.cancel-btn:hover {

    background: #333;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 900px) {

    .sidebar {

        width: 220px;
    }

    .main {

        margin-left: 220px;

        padding: 20px;
    }

    .form-grid {

        grid-template-columns: 1fr;
    }

    .form-group.full {

        grid-column: auto;
    }
}


@media(max-width: 650px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

        padding-bottom: 10px;
    }

    .main {

        margin-left: 0;

        padding: 15px;
    }

    .page-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .card {

        padding: 20px;
    }

    .buttons {

        flex-direction: column;
    }

    .update-btn,
    .cancel-btn {

        justify-content: center;

        width: 100%;
    }
}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">


<h2>

    <i class="fas fa-hands-helping"></i>

    Social Welfare

</h2>


<a href="../index.php">

    <i class="fas fa-tachometer-alt icon-dashboard"></i>

    Dashboard

</a>


<a href="../clients/index.php">

    <i class="fas fa-users icon-clients"></i>

    Clients

</a>


<a href="index.php" class="active">

    <i class="fas fa-folder-open icon-cases"></i>

    Cases

</a>


<a href="../documents/index.php">

    <i class="fas fa-file-alt icon-documents"></i>

    Documents

</a>


<a href="../households/index.php">

    <i class="fas fa-home icon-households"></i>

    Households

</a>


<a href="../activities/index.php">

    <i class="fas fa-tasks icon-activities"></i>

    Activities

</a>


<a href="../appointments/index.php">

    <i class="fas fa-calendar-alt icon-appointments"></i>

    Appointments

</a>


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
     MAIN CONTENT
===================================================== -->

<div class="main">


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="page-header">


<h1>

    <i class="fas fa-edit"></i>

    Edit Case

</h1>


<a
href="view.php?id=<?php echo $case_id; ?>"
class="back-btn"
>

    <i class="fas fa-arrow-left"></i>

    Back to Case

</a>


</div>



<!-- =====================================================
     FORM CARD
===================================================== -->

<div class="card">


<?php if (!empty($error)): ?>

<div class="error">

    <i class="fas fa-exclamation-circle"></i>

    <div>

        <?php echo htmlspecialchars($error); ?>

    </div>

</div>

<?php endif; ?>



<form method="POST">


<div class="form-grid">


<!-- =================================================
     CLIENT
================================================= -->

<div class="form-group full">

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


<?php if ($clients && $clients->num_rows > 0): ?>


<?php while (
    $client = $clients->fetch_assoc()
): ?>


<option
value="<?php echo (int) $client["client_id"]; ?>"

<?php

if (
    (int) $client["client_id"] ===
    (int) $case["client_id"]
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



<!-- =================================================
     CASE NUMBER
================================================= -->

<div class="form-group">

<label>

    Case Number

    <span class="required">*</span>

</label>


<input
type="text"
name="case_number"
class="case-number-input"
value="<?php
echo htmlspecialchars(
    $case["case_number"]
);
?>"
maxlength="50"
required
>


<div class="help-text">

    You can edit the case number.
    It must be unique.

</div>

</div>



<!-- =================================================
     CASE TYPE
================================================= -->

<div class="form-group">

<label>

    Case Type

    <span class="required">*</span>

</label>


<select
name="case_type"
required
>

<option value="">

    Select Case Type

</option>


<?php

$case_types = [

    "Child Protection",

    "Family Welfare",

    "Domestic Violence",

    "Disability Support",

    "Elderly Support",

    "Poverty Assistance",

    "Emergency Assistance",

    "Other"

];

foreach (
    $case_types as $type
):

?>


<option
value="<?php
echo htmlspecialchars($type);
?>"

<?php

if (
    $case["case_type"] ===
    $type
) {

    echo "selected";
}

?>
>

<?php

echo htmlspecialchars($type);

?>

</option>


<?php endforeach; ?>


</select>

</div>



<!-- =================================================
     PRIORITY
================================================= -->

<div class="form-group">

<label>

    Priority

</label>


<select name="priority">


<?php

$priorities = [

    "Low",

    "Medium",

    "High",

    "Urgent"

];

foreach (
    $priorities as $item
):

?>


<option
value="<?php
echo htmlspecialchars($item);
?>"

<?php

if (
    $case["priority"] ===
    $item
) {

    echo "selected";
}

?>
>

<?php

echo htmlspecialchars($item);

?>

</option>


<?php endforeach; ?>


</select>

</div>



<!-- =================================================
     STATUS
================================================= -->

<div class="form-group">

<label>

    Status

</label>


<select name="status">


<?php

$statuses = [

    "Open",

    "In Progress",

    "Pending",

    "Closed"

];

foreach (
    $statuses as $item
):

?>


<option
value="<?php
echo htmlspecialchars($item);
?>"

<?php

if (
    $case["status"] ===
    $item
) {

    echo "selected";
}

?>
>

<?php

echo htmlspecialchars($item);

?>

</option>


<?php endforeach; ?>


</select>

</div>



<!-- =================================================
     SOCIAL WORKER
================================================= -->

<div class="form-group">

<label>

    <i class="fas fa-user-tie"></i>

    Assigned Social Worker

</label>


<select
name="assigned_to"
>


<option value="">

    Not Assigned

</option>


<?php

if (
    $social_workers &&
    $social_workers->num_rows > 0
):

?>


<?php

while (
    $worker =
    $social_workers->fetch_assoc()
):

?>


<option
value="<?php
echo (int)
$worker["worker_id"];
?>"

<?php

if (
    $case["assigned_to"] !== null &&
    $case["assigned_to"] !== "" &&
    (int)
    $case["assigned_to"] ===
    (int)
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

if (
    !empty(
        $worker["position"]
    )
) {

    echo " (" .
        htmlspecialchars(
            $worker["position"]
        )
        . ")";
}

?>

</option>


<?php endwhile; ?>


<?php else: ?>


<option value="" disabled>

    No active social workers available

</option>


<?php endif; ?>


</select>


<div class="worker-help">

    Select the social welfare officer
    responsible for this case.

</div>

</div>



<!-- =================================================
     DATE OPENED
================================================= -->

<div class="form-group">

<label>

    Date Opened

    <span class="required">*</span>

</label>


<input
type="date"
name="date_opened"
value="<?php
echo htmlspecialchars(
    $case["date_opened"]
);
?>"
required
>

</div>



<!-- =================================================
     DATE CLOSED
================================================= -->

<div class="form-group">

<label>

    Date Closed

</label>


<input
type="date"
name="date_closed"
value="<?php

echo !empty(
    $case["date_closed"]
)

    ? htmlspecialchars(
        $case["date_closed"]
      )

    : "";

?>
>

</div>



<!-- =================================================
     DESCRIPTION
================================================= -->

<div class="form-group full">

<label>

    Case Description

</label>


<textarea
name="description"
placeholder="Enter details about the case..."
><?php

echo htmlspecialchars(
    $case["description"] ?? ""
);

?></textarea>

</div>


</div>



<!-- =================================================
     BUTTONS
================================================= -->

<div class="buttons">


<button
type="submit"
class="update-btn"
>

    <i class="fas fa-save"></i>

    Update Case

</button>


<a
href="view.php?id=<?php echo $case_id; ?>"
class="cancel-btn"
>

    <i class="fas fa-times"></i>

    Cancel

</a>


</div>


</form>


</div>


</div>


</body>

</html>