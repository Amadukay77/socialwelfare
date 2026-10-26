 ```php
<?php

session_start();

require_once "../includes/db.php";
require_once "../includes/permissions.php";

// Require login and permission to add cases.
require_permission("cases_add");

// Escape output safely.
function h($value): string
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$error = "";

// Default form values.
$client_id = 0;
$case_type = "";
$description = "";
$priority = "Medium";
$status = "Open";
$date_opened = date("Y-m-d");
$assigned_to = null;

// Load clients safely.
$clients = $conn->query("
    SELECT client_id, client_number, full_name
    FROM clients
    ORDER BY full_name ASC
");

// Load active social workers.
$social_workers = $conn->query("
    SELECT worker_id, worker_number, full_name
    FROM social_workers
    WHERE status = 'Active'
    ORDER BY full_name ASC
");

// Process form submission.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $client_id = (int)($_POST["client_id"] ?? 0);
    $case_type = trim($_POST["case_type"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $priority = $_POST["priority"] ?? "Medium";
    $status = $_POST["status"] ?? "Open";
    $date_opened = $_POST["date_opened"] ?? "";

    $assignedInput = $_POST["assigned_to"] ?? "";
    $assigned_to = $assignedInput !== ""
        ? (int)$assignedInput
        : null;

    // Accept only supported values.
    $allowedTypes = [
        "Child Protection",
        "Family Welfare",
        "Domestic Violence",
        "Disability Support",
        "Elderly Support",
        "Poverty Assistance",
        "Emergency Assistance",
        "Other"
    ];

    $allowedPriorities = [
        "Low", "Medium", "High", "Urgent"
    ];

    $allowedStatuses = [
        "Open", "In Progress", "Pending", "Closed"
    ];

    // Basic validation.
    if (
        $client_id <= 0 ||
        $case_type === "" ||
        $date_opened === ""
    ) {
        $error = "Please fill in all required fields.";

    } elseif (!in_array($case_type, $allowedTypes, true)) {
        $error = "Please select a valid case type.";

    } elseif (!in_array($priority, $allowedPriorities, true)) {
        $error = "Please select a valid priority.";

    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = "Please select a valid case status.";

    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_opened) ||
        !checkdate(
            (int)substr($date_opened, 5, 2),
            (int)substr($date_opened, 8, 2),
            (int)substr($date_opened, 0, 4)
        )
    ) {
        $error = "Please enter a valid date.";

    } else {

        // Confirm that the selected client exists.
        $clientStmt = $conn->prepare(
            "SELECT client_id FROM clients WHERE client_id = ?"
        );

        if (!$clientStmt) {
            $error = "Unable to validate the selected client.";
        } else {
            $clientStmt->bind_param("i", $client_id);
            $clientStmt->execute();
            $clientResult = $clientStmt->get_result();
            $clientExists = $clientResult->num_rows > 0;
            $clientStmt->close();

            if (!$clientExists) {
                $error = "The selected client does not exist.";
            }
        }

        // Confirm that an assigned worker is active.
        if ($error === "" && $assigned_to !== null) {

            if ($assigned_to <= 0) {
                $error = "Please select a valid social worker.";
            } else {
                $workerStmt = $conn->prepare("
                    SELECT worker_id
                    FROM social_workers
                    WHERE worker_id = ?
                      AND status = 'Active'
                ");

                if (!$workerStmt) {
                    $error = "Unable to validate the social worker.";
                } else {
                    $workerStmt->bind_param("i", $assigned_to);
                    $workerStmt->execute();
                    $workerResult = $workerStmt->get_result();
                    $workerExists = $workerResult->num_rows > 0;
                    $workerStmt->close();

                    if (!$workerExists) {
                        $error = "The selected worker is not active or does not exist.";
                    }
                }
            }
        }

        // Save the case only after all checks pass.
        if ($error === "") {

            try {
                $case_number = "CASE-" . date("YmdHis")
                    . "-" . random_int(100, 999);

                $stmt = $conn->prepare("
                    INSERT INTO cases (
                        client_id,
                        case_number,
                        case_type,
                        description,
                        priority,
                        status,
                        assigned_to,
                        date_opened
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$stmt) {
                    $error = "Unable to prepare the case record.";
                } else {

                    // Eight parameters: i, s, s, s, s, s, i, s.
                    $stmt->bind_param(
                        "isssssis",
                        $client_id,
                        $case_number,
                        $case_type,
                        $description,
                        $priority,
                        $status,
                        $assigned_to,
                        $date_opened
                    );

                    if ($stmt->execute()) {
                        $stmt->close();

                        header(
                            "Location: index.php?success="
                            . urlencode("Case added successfully.")
                        );
                        exit;

                    } else {
                        $error = "Failed to save the case. Please try again.";
                        $stmt->close();
                    }
                }

            } catch (Throwable $e) {
                // Keep technical details out of the browser.
                error_log("Add case error: " . $e->getMessage());
                $error = "An unexpected error occurred while saving the case.";
            }
        }
    }
}

?>
```
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Add Case - Social Welfare System</title>


<!-- =========================
     FONT AWESOME
========================= -->

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


<style>

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

    box-shadow:
        3px 0 12px rgba(0,0,0,0.15);
}


/* SIDEBAR TITLE */

.sidebar h2 {

    text-align: center;

    margin: 0;

    padding:
        25px 15px;

    border-bottom:
        1px solid #333;

    font-size: 20px;
}

.sidebar h2 i {

    margin-right: 7px;

    color: #198754;
}


/* SIDEBAR LINKS */

.sidebar a {

    display: flex;

    align-items: center;

    gap: 14px;

    color: #ddd;

    text-decoration: none;

    padding:
        13px 20px;

    font-size: 14px;

    transition: 0.2s;
}

.sidebar a:hover {

    background: #292929;

    color: white;
}

.sidebar a.active {

    background: #292929;

    color: white;

    border-left:
        4px solid #e53935;

    padding-left: 16px;
}


/* SIDEBAR ICON */

.sidebar a i {

    width: 22px;

    text-align: center;

    font-size: 16px;
}


/* =========================
   SIDEBAR ICON COLORS
========================= */

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

.icon-workers {
    color: #26c6da;
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
    color: #8d6e63;
}

.icon-settings {
    color: #bdbdbd;
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

    padding: 30px;
}


/* =========================
   PAGE HEADER
========================= */

.page-header {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 25px;
}

.page-header h1 {

    margin: 0;

    font-size: 25px;

    color: #333;
}

.page-header h1 i {

    color: #e53935;

    margin-right: 8px;
}


/* =========================
   BACK BUTTON
========================= */

.back-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #171717;

    color: white;

    text-decoration: none;

    padding:
        11px 18px;

    border-radius: 6px;

    transition: 0.2s;
}

.back-btn:hover {

    background: #333;
}


/* =========================
   CARD
========================= */

.card {

    background: white;

    padding: 30px;

    border-radius: 10px;

    box-shadow:
        0 3px 10px rgba(0,0,0,0.07);

    max-width: 1000px;
}


/* =========================
   ERROR
========================= */

.error {

    background: #f8d7da;

    color: #842029;

    padding: 13px 16px;

    border-radius: 6px;

    margin-bottom: 20px;

    border:
        1px solid #f5c2c7;

    display: flex;

    align-items: center;

    gap: 9px;
}


/* =========================
   FORM GRID
========================= */

.form-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 20px;
}


/* =========================
   FORM GROUP
========================= */

.form-group {

    display: flex;

    flex-direction: column;
}

.form-group.full {

    grid-column:
        1 / -1;
}


/* =========================
   LABEL
========================= */

label {

    font-weight: bold;

    margin-bottom: 8px;

    color: #333;

    font-size: 14px;
}

label i {

    margin-right: 5px;

    color: #198754;
}

.required {

    color: #e53935;
}


/* =========================
   INPUTS
========================= */

input,
select,
textarea {

    width: 100%;

    padding: 13px;

    border:
        1px solid #ccc;

    border-radius: 6px;

    font-size: 14px;

    background: white;

    color: #333;
}

input:focus,
select:focus,
textarea:focus {

    outline: none;

    border-color: #198754;

    box-shadow:
        0 0 0 2px
        rgba(25,135,84,0.10);
}


textarea {

    min-height: 130px;

    resize: vertical;
}


/* =========================
   HELP TEXT
========================= */

.help-text {

    margin-top: 6px;

    font-size: 12px;

    color: #888;
}


/* =========================
   SAVE BUTTON
========================= */

.submit-btn {

    margin-top: 25px;

    background: #198754;

    color: white;

    border: none;

    padding:
        13px 25px;

    border-radius: 6px;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.2s;
}

.submit-btn:hover {

    background: #146c43;
}

.submit-btn i {

    margin-right: 7px;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 800px) {

    .sidebar {

        width: 210px;
    }

    .main {

        margin-left: 210px;

        padding: 20px;
    }

    .form-grid {

        grid-template-columns: 1fr;
    }

}


@media (max-width: 600px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
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

}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

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


    <a href="index.php"
       class="active">

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



<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <h1>

            <i class="fas fa-folder-plus"></i>

            Add New Case

        </h1>


        <a href="index.php"
           class="back-btn">

            <i class="fas fa-arrow-left"></i>

            Back to Cases

        </a>

    </div>



    <!-- CARD -->

    <div class="card">


        <?php if (!empty($error)): ?>

            <div class="error">

                <i class="fas fa-exclamation-circle"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>



        <form method="POST">


            <div class="form-grid">


                <!-- =========================
                     CLIENT
                ========================= -->

                <div class="form-group full">

                    <label>

                        <i class="fas fa-user"></i>

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

                            <?php while ($client = $clients->fetch_assoc()): ?>

                                <option
                                    value="<?php echo $client["client_id"]; ?>"
                                    <?php
                                    if (
                                        isset($_POST["client_id"]) &&
                                        $_POST["client_id"] == $client["client_id"]
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



                <!-- =========================
                     CASE TYPE
                ========================= -->

                <div class="form-group">

                    <label>

                        <i class="fas fa-folder-open"></i>

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

                        <option value="Child Protection">
                            Child Protection
                        </option>

                        <option value="Family Welfare">
                            Family Welfare
                        </option>

                        <option value="Domestic Violence">
                            Domestic Violence
                        </option>

                        <option value="Disability Support">
                            Disability Support
                        </option>

                        <option value="Elderly Support">
                            Elderly Support
                        </option>

                        <option value="Poverty Assistance">
                            Poverty Assistance
                        </option>

                        <option value="Emergency Assistance">
                            Emergency Assistance
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>



                <!-- =========================
                     ASSIGNED SOCIAL WORKER
                ========================= -->

                <div class="form-group">

                    <label>

                        <i class="fas fa-user-tie"></i>

                        Assigned Social Worker

                    </label>


                    <select name="assigned_to">

                        <option value="">

                            Not Assigned

                        </option>


                        <?php if ($social_workers && $social_workers->num_rows > 0): ?>

                            <?php while ($worker = $social_workers->fetch_assoc()): ?>

                                <option
                                    value="<?php echo $worker["worker_id"]; ?>"
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

                        <?php else: ?>

                            <option value=""
                                    disabled>

                                No active social workers available

                            </option>

                        <?php endif; ?>

                    </select>


                    <div class="help-text">

                        <i class="fas fa-info-circle"></i>

                        You can assign a social worker now or leave it unassigned.

                    </div>

                </div>



                <!-- =========================
                     PRIORITY
                ========================= -->

                <div class="form-group">

                    <label>

                        <i class="fas fa-exclamation-triangle"></i>

                        Priority

                    </label>


                    <select name="priority">

                        <option value="Low">
                            Low
                        </option>

                        <option
                            value="Medium"
                            selected
                        >
                            Medium
                        </option>

                        <option value="High">
                            High
                        </option>

                        <option value="Urgent">
                            Urgent
                        </option>

                    </select>

                </div>



                <!-- =========================
                     STATUS
                ========================= -->

                <div class="form-group">

                    <label>

                        <i class="fas fa-tasks"></i>

                        Status

                    </label>


                    <select name="status">

                        <option
                            value="Open"
                            selected
                        >
                            Open
                        </option>

                        <option value="In Progress">
                            In Progress
                        </option>

                        <option value="Pending">
                            Pending
                        </option>

                        <option value="Closed">
                            Closed
                        </option>

                    </select>

                </div>



                <!-- =========================
                     DATE OPENED
                ========================= -->

                <div class="form-group">

                    <label>

                        <i class="fas fa-calendar-alt"></i>

                        Date Opened

                        <span class="required">*</span>

                    </label>


                    <input
                        type="date"
                        name="date_opened"
                        value="<?php echo htmlspecialchars(
                            $_POST["date_opened"]
                            ?? date("Y-m-d")
                        ); ?>"
                        required
                    >

                </div>



                <!-- =========================
                     DESCRIPTION
                ========================= -->

                <div class="form-group full">

                    <label>

                        <i class="fas fa-align-left"></i>

                        Case Description

                    </label>


                    <textarea
                        name="description"
                        placeholder="Enter details about the case..."
                    ><?php echo htmlspecialchars(
                        $_POST["description"] ?? ""
                    ); ?></textarea>

                </div>


            </div>



            <!-- =========================
                 SAVE
            ========================= -->

            <button
                type="submit"
                class="submit-btn"
            >

                <i class="fas fa-save"></i>

                Save Case

            </button>


        </form>


    </div>


</div>


</body>

</html>