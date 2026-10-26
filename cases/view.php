 <?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

/* Check ID */
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$case_id = (int) $_GET["id"];

/*
|--------------------------------------------------------------------------
| Get Case Information
|--------------------------------------------------------------------------
| Includes:
| - Client information
| - Assigned social worker information
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        c.*,

        cl.client_number,
        cl.full_name AS client_name,
        cl.gender AS client_gender,
        cl.phone AS client_phone,

        sw.worker_id,
        sw.worker_number,
        sw.full_name AS worker_name,
        sw.gender AS worker_gender,
        sw.phone AS worker_phone,
        sw.email AS worker_email,
        sw.position AS worker_position,
        sw.status AS worker_status

    FROM cases c

    INNER JOIN clients cl
        ON c.client_id = cl.client_id

    LEFT JOIN social_workers sw
        ON c.assigned_to = sw.worker_id

    WHERE c.case_id = ?

    LIMIT 1
");

$stmt->bind_param("i", $case_id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$case = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
View Case - Tonkolili District Social Welfare
</title>

<!-- Font Awesome -->

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
>

<style>

/* =========================================================
   GENERAL
========================================================= */

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

    width: 250px;
    height: 100vh;

    background: #171717;
    color: white;

    padding-top: 20px;

    overflow-y: auto;
}

.sidebar-header {
    text-align: center;
    padding: 5px 15px 25px;
}

.sidebar-header .sidebar-logo {
    width: 48px;
    height: 48px;

    background: #e53935;
    color: white;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    margin: 0 auto 10px;

    font-size: 21px;
}

.sidebar-header h2 {
    margin: 0;
    font-size: 19px;
}

.sidebar-header p {
    margin: 5px 0 0;
    color: #999;
    font-size: 12px;
}

.sidebar-section {
    padding: 15px 20px 7px;

    font-size: 11px;
    text-transform: uppercase;

    color: #777;

    letter-spacing: 1px;
}

.sidebar a {
    display: flex;
    align-items: center;

    gap: 14px;

    color: #ddd;
    text-decoration: none;

    padding: 13px 20px;

    font-size: 14px;

    border-left: 4px solid transparent;

    transition: 0.2s;
}

.sidebar a i {
    width: 20px;
    text-align: center;
    font-size: 16px;
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


/* Different sidebar icon colors */

.sidebar a:nth-of-type(1) i {
    color: #4dabf7;
}

.sidebar a:nth-of-type(2) i {
    color: #51cf66;
}

.sidebar a:nth-of-type(3) i {
    color: #ff922b;
}

.sidebar a:nth-of-type(4) i {
    color: #845ef7;
}

.sidebar a:nth-of-type(5) i {
    color: #20c997;
}

.sidebar a:nth-of-type(6) i {
    color: #fcc419;
}

.sidebar a:nth-of-type(7) i {
    color: #f06595;
}

.sidebar a:nth-of-type(8) i {
    color: #74c0fc;
}

.sidebar a:nth-of-type(9) i {
    color: #a9e34b;
}

.sidebar a:nth-of-type(10) i {
    color: #ff6b6b;
}


/* =========================================================
   MAIN
========================================================= */

.main {
    margin-left: 250px;
    padding: 30px;
    min-height: 100vh;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {
    display: flex;

    justify-content: space-between;
    align-items: center;

    margin-bottom: 25px;

    gap: 15px;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-title .title-icon {
    width: 45px;
    height: 45px;

    background: #171717;
    color: white;

    border-radius: 8px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 19px;
}

.page-header h1 {
    margin: 0;

    font-size: 25px;
}

.page-header p {
    margin: 4px 0 0;

    color: #777;
    font-size: 13px;
}


/* Back button */

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    background: #171717;
    color: white;

    text-decoration: none;

    padding: 11px 18px;

    border-radius: 6px;

    font-size: 14px;
}

.back-btn:hover {
    background: #333;
}


/* =========================================================
   CARDS
========================================================= */

.card {
    background: white;

    padding: 28px;

    border-radius: 10px;

    box-shadow:
        0 2px 8px rgba(0,0,0,0.07);

    margin-bottom: 20px;

    border: 1px solid #eee;
}


/* =========================================================
   CASE HEADER
========================================================= */

.case-header {
    display: flex;

    align-items: center;

    gap: 18px;

    padding-bottom: 24px;

    margin-bottom: 24px;

    border-bottom: 1px solid #eee;
}

.case-icon {
    width: 65px;
    height: 65px;

    min-width: 65px;

    border-radius: 50%;

    background: #171717;

    color: white;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 25px;
}

.case-header h2 {
    margin: 0 0 7px;

    font-size: 22px;
}

.case-number {
    color: #777;

    font-size: 13px;
}

.case-number strong {
    color: #333;
}


/* =========================================================
   SECTION TITLE
========================================================= */

.section-title {
    display: flex;
    align-items: center;

    gap: 10px;

    margin: 0 0 20px;

    font-size: 19px;

    color: #222;
}

.section-title i {
    color: #e53935;
}


/* =========================================================
   INFORMATION GRID
========================================================= */

.info-grid {
    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 15px;
}

.info-box {
    border: 1px solid #e8e8e8;

    background: #fafafa;

    border-radius: 8px;

    padding: 16px;
}

.info-label {
    display: flex;
    align-items: center;

    gap: 7px;

    font-size: 12px;

    color: #777;

    margin-bottom: 8px;
}

.info-label i {
    color: #888;
}

.info-value {
    font-size: 15px;

    font-weight: bold;

    color: #222;

    word-break: break-word;
}


/* =========================================================
   CLIENT CARD
========================================================= */

.client-box {
    display: flex;

    align-items: center;

    gap: 15px;

    padding: 18px;

    background: #f8f9fa;

    border: 1px solid #eee;

    border-radius: 8px;
}

.client-icon {
    width: 48px;
    height: 48px;

    min-width: 48px;

    border-radius: 50%;

    background: #e8f5e9;

    color: #2e7d32;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 19px;
}

.client-details strong {
    display: block;

    font-size: 16px;

    margin-bottom: 4px;
}

.client-details span {
    display: block;

    color: #777;

    font-size: 13px;

    margin-top: 2px;
}


/* =========================================================
   SOCIAL WORKER
========================================================= */

.worker-card {
    border: 1px solid #e5e5e5;

    border-radius: 10px;

    overflow: hidden;

    background: #fff;
}

.worker-header {
    display: flex;

    align-items: center;

    gap: 15px;

    padding: 18px;

    background: #f8f9fa;

    border-bottom: 1px solid #eee;
}

.worker-avatar {
    width: 52px;
    height: 52px;

    min-width: 52px;

    border-radius: 50%;

    background: #171717;

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 19px;
}

.worker-name h3 {
    margin: 0 0 4px;

    font-size: 17px;
}

.worker-name span {
    color: #777;

    font-size: 13px;
}

.worker-details {
    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 0;
}

.worker-detail {
    padding: 15px 18px;

    border-bottom: 1px solid #eee;
}

.worker-detail:nth-child(odd) {
    border-right: 1px solid #eee;
}

.worker-detail-label {
    color: #888;

    font-size: 12px;

    margin-bottom: 5px;
}

.worker-detail-value {
    font-size: 14px;

    font-weight: bold;

    word-break: break-word;
}

.worker-status {
    display: inline-block;

    padding: 5px 10px;

    border-radius: 15px;

    font-size: 12px;

    font-weight: bold;

    background: #d1e7dd;

    color: #0f5132;
}

.no-worker {
    padding: 25px;

    text-align: center;

    border: 1px dashed #ccc;

    border-radius: 8px;

    background: #fafafa;

    color: #777;
}

.no-worker i {
    display: block;

    font-size: 30px;

    margin-bottom: 10px;

    color: #aaa;
}

.no-worker strong {
    display: block;

    color: #555;

    margin-bottom: 5px;
}


/* =========================================================
   BADGES
========================================================= */

.badge {
    display: inline-block;

    padding: 6px 12px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}

.priority-low {
    background: #d1e7dd;
    color: #0f5132;
}

.priority-medium {
    background: #fff3cd;
    color: #664d03;
}

.priority-high {
    background: #ffe5d0;
    color: #984c0c;
}

.priority-urgent {
    background: #f8d7da;
    color: #842029;
}

.status-open {
    background: #d1e7dd;
    color: #0f5132;
}

.status-progress {
    background: #cff4fc;
    color: #055160;
}

.status-pending {
    background: #fff3cd;
    color: #664d03;
}

.status-closed {
    background: #e2e3e5;
    color: #41464b;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.description-box {
    background: #fafafa;

    border: 1px solid #eee;

    border-left: 4px solid #e53935;

    border-radius: 7px;

    padding: 18px;
}

.description {
    margin: 0;

    line-height: 1.7;

    color: #444;

    white-space: pre-wrap;

    font-size: 14px;
}


/* =========================================================
   ACTIONS
========================================================= */

.actions {
    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}

.action-btn {
    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 11px 17px;

    border-radius: 6px;

    text-decoration: none;

    color: white;

    font-weight: bold;

    font-size: 13px;

    border: none;

    cursor: pointer;
}

.edit-btn {
    background: #1976d2;
}

.edit-btn:hover {
    background: #125ca5;
}

.delete-btn {
    background: #e53935;
}

.delete-btn:hover {
    background: #c62828;
}

.back-action {
    background: #555;
}

.back-action:hover {
    background: #333;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 1000px) {

    .info-grid {
        grid-template-columns: 1fr;
    }

    .worker-details {
        grid-template-columns: 1fr;
    }

    .worker-detail:nth-child(odd) {
        border-right: none;
    }

}


@media(max-width: 800px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;

        padding: 20px;
    }

    .page-header {
        align-items: flex-start;

        flex-direction: column;
    }

}


@media(max-width: 600px) {

    .sidebar {
        position: relative;

        width: 100%;

        height: auto;
    }

    .main {
        margin-left: 0;

        padding: 15px;
    }

    .case-header {
        align-items: flex-start;

        flex-direction: column;
    }

    .card {
        padding: 20px;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">

    <div class="sidebar-header">

        <div class="sidebar-logo">
            <i class="fas fa-hands-helping"></i>
        </div>

        <h2>Social Welfare</h2>

        <p>Tonkolili District</p>

    </div>


    <div class="sidebar-section">
        Main Menu
    </div>


    <a href="../index.php">
        <i class="fas fa-tachometer-alt"></i>
        <span>Dashboard</span>
    </a>


    <a href="../clients/index.php">
        <i class="fas fa-users"></i>
        <span>Clients</span>
    </a>


    <a href="index.php" class="active">
        <i class="fas fa-folder-open"></i>
        <span>Cases</span>
    </a>


    <a href="../documents/index.php">
        <i class="fas fa-file-alt"></i>
        <span>Documents</span>
    </a>


    <a href="../social_workers/index.php">
        <i class="fas fa-user-tie"></i>
        <span>Social Workers</span>
    </a>


    <a href="../households/index.php">
        <i class="fas fa-home"></i>
        <span>Households</span>
    </a>


    <a href="../activities/index.php">
        <i class="fas fa-tasks"></i>
        <span>Activities</span>
    </a>


    <a href="../appointments/index.php">
        <i class="fas fa-calendar-alt"></i>
        <span>Appointments</span>
    </a>


    <div class="sidebar-section">
        Management
    </div>


    <a href="../reports/index.php">
        <i class="fas fa-chart-bar"></i>
        <span>Reports</span>
    </a>


    <a href="../settings/index.php">
        <i class="fas fa-cog"></i>
        <span>Settings</span>
    </a>


    <a href="../auth/logout.php">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>

</div>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="main">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div class="page-title">

            <div class="title-icon">
                <i class="fas fa-folder-open"></i>
            </div>

            <div>

                <h1>Case Details</h1>

                <p>
                    View complete information about this social welfare case
                </p>

            </div>

        </div>


        <a href="index.php" class="back-btn">

            <i class="fas fa-arrow-left"></i>

            Back to Cases

        </a>

    </div>



    <!-- =================================================
         CASE OVERVIEW
    ================================================== -->

    <div class="card">

        <div class="case-header">

            <div class="case-icon">
                <i class="fas fa-folder-open"></i>
            </div>


            <div>

                <h2>
                    <?php echo htmlspecialchars($case["case_type"]); ?>
                </h2>


                <div class="case-number">

                    Case Number:

                    <strong>
                        <?php echo htmlspecialchars($case["case_number"]); ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- CASE INFORMATION -->

        <h3 class="section-title">

            <i class="fas fa-info-circle"></i>

            Case Information

        </h3>


        <div class="info-grid">


            <!-- Client -->

            <div class="info-box">

                <div class="info-label">

                    <i class="fas fa-user"></i>

                    Client

                </div>

                <div class="info-value">

                    <?php echo htmlspecialchars($case["client_name"]); ?>

                </div>

            </div>


            <!-- Client Number -->

            <div class="info-box">

                <div class="info-label">

                    <i class="fas fa-id-card"></i>

                    Client Number

                </div>

                <div class="info-value">

                    <?php echo htmlspecialchars($case["client_number"]); ?>

                </div>

            </div>


            <!-- Case Type -->

            <div class="info-box">

                <div class="info-label">

                    <i class="fas fa-folder"></i>

                    Case Type

                </div>

                <div class="info-value">

                    <?php echo htmlspecialchars($case["case_type"]); ?>

                </div>

            </div>


            <!-- Priority -->

            <div class="info-box">

                <div class="info-label">

                    <i class="fas fa-exclamation-circle"></i>

                    Priority

                </div>

                <div class="info-value">

                    <?php

                    $priority_class = strtolower(
                        $case["priority"] ?? "medium"
                    );

                    ?>

                    <span class="badge priority-<?php echo $priority_class; ?>">

                        <?php
                        echo htmlspecialchars(
                            $case["priority"] ?? "Medium"
                        );
                        ?>

                    </span>

                </div>

            </div>


            <!-- Status -->

            <div class="info-box">

                <div class="info-label">

                    <i class="fas fa-tasks"></i>

                    Status

                </div>

                <div class="info-value">

                    <?php

                    $status_class = "open";

                    if ($case["status"] === "In Progress") {

                        $status_class = "progress";

                    } elseif ($case["status"] === "Pending") {

                        $status_class = "pending";

                    } elseif ($case["status"] === "Closed") {

                        $status_class = "closed";

                    }

                    ?>

                    <span class="badge status-<?php echo $status_class; ?>">

                        <?php
                        echo htmlspecialchars($case["status"]);
                        ?>

                    </span>

                </div>

            </div>


            <!-- Date Opened -->

            <div class="info-box">

                <div class="info-label">

                    <i class="fas fa-calendar-plus"></i>

                    Date Opened

                </div>

                <div class="info-value">

                    <?php
                    echo htmlspecialchars($case["date_opened"]);
                    ?>

                </div>

            </div>


            <!-- Date Closed -->

            <div class="info-box">

                <div class="info-label">

                    <i class="fas fa-calendar-check"></i>

                    Date Closed

                </div>

                <div class="info-value">

                    <?php

                    echo !empty($case["date_closed"])

                        ? htmlspecialchars($case["date_closed"])

                        : "Not closed";

                    ?>

                </div>

            </div>


            <!-- Created -->

            <div class="info-box">

                <div class="info-label">

                    <i class="fas fa-clock"></i>

                    Created At

                </div>

                <div class="info-value">

                    <?php
                    echo htmlspecialchars($case["created_at"]);
                    ?>

                </div>

            </div>


        </div>

    </div>



    <!-- =================================================
         CLIENT INFORMATION
    ================================================== -->

    <div class="card">

        <h3 class="section-title">

            <i class="fas fa-user"></i>

            Client Information

        </h3>


        <div class="client-box">

            <div class="client-icon">

                <i class="fas fa-user"></i>

            </div>


            <div class="client-details">

                <strong>

                    <?php
                    echo htmlspecialchars($case["client_name"]);
                    ?>

                </strong>


                <span>

                    <i class="fas fa-id-card"></i>

                    Client No:
                    <?php
                    echo htmlspecialchars($case["client_number"]);
                    ?>

                </span>


                <?php if (!empty($case["client_gender"])): ?>

                    <span>

                        <i class="fas fa-venus-mars"></i>

                        Gender:
                        <?php
                        echo htmlspecialchars($case["client_gender"]);
                        ?>

                    </span>

                <?php endif; ?>


                <?php if (!empty($case["client_phone"])): ?>

                    <span>

                        <i class="fas fa-phone"></i>

                        Phone:
                        <?php
                        echo htmlspecialchars($case["client_phone"]);
                        ?>

                    </span>

                <?php endif; ?>

            </div>

        </div>

    </div>



    <!-- =================================================
         ASSIGNED SOCIAL WORKER
    ================================================== -->

    <div class="card">

        <h3 class="section-title">

            <i class="fas fa-user-tie"></i>

            Assigned Social Worker

        </h3>


        <?php if (!empty($case["worker_id"])): ?>

            <div class="worker-card">


                <div class="worker-header">

                    <div class="worker-avatar">

                        <i class="fas fa-user-tie"></i>

                    </div>


                    <div class="worker-name">

                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $case["worker_name"]
                            );
                            ?>

                        </h3>


                        <span>

                            <?php

                            if (!empty($case["worker_position"])) {

                                echo htmlspecialchars(
                                    $case["worker_position"]
                                );

                            } else {

                                echo "Social Welfare Worker";

                            }

                            ?>

                        </span>

                    </div>

                </div>


                <div class="worker-details">


                    <!-- Worker Number -->

                    <div class="worker-detail">

                        <div class="worker-detail-label">

                            Worker Number

                        </div>

                        <div class="worker-detail-value">

                            <?php

                            echo htmlspecialchars(
                                $case["worker_number"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- Position -->

                    <div class="worker-detail">

                        <div class="worker-detail-label">

                            Position

                        </div>

                        <div class="worker-detail-value">

                            <?php

                            echo !empty($case["worker_position"])

                                ? htmlspecialchars(
                                    $case["worker_position"]
                                )

                                : "Not specified";

                            ?>

                        </div>

                    </div>


                    <!-- Phone -->

                    <div class="worker-detail">

                        <div class="worker-detail-label">

                            Phone

                        </div>

                        <div class="worker-detail-value">

                            <?php

                            echo !empty($case["worker_phone"])

                                ? htmlspecialchars(
                                    $case["worker_phone"]
                                )

                                : "Not provided";

                            ?>

                        </div>

                    </div>


                    <!-- Email -->

                    <div class="worker-detail">

                        <div class="worker-detail-label">

                            Email

                        </div>

                        <div class="worker-detail-value">

                            <?php

                            echo !empty($case["worker_email"])

                                ? htmlspecialchars(
                                    $case["worker_email"]
                                )

                                : "Not provided";

                            ?>

                        </div>

                    </div>


                    <!-- Gender -->

                    <div class="worker-detail">

                        <div class="worker-detail-label">

                            Gender

                        </div>

                        <div class="worker-detail-value">

                            <?php

                            echo !empty($case["worker_gender"])

                                ? htmlspecialchars(
                                    $case["worker_gender"]
                                )

                                : "Not specified";

                            ?>

                        </div>

                    </div>


                    <!-- Status -->

                    <div class="worker-detail">

                        <div class="worker-detail-label">

                            Worker Status

                        </div>

                        <div class="worker-detail-value">

                            <?php

                            if (
                                isset($case["worker_status"]) &&
                                $case["worker_status"] === "Active"
                            ) {

                                echo '<span class="worker-status">
                                        <i class="fas fa-check-circle"></i>
                                        Active
                                      </span>';

                            } else {

                                echo '<span class="worker-status">
                                        <i class="fas fa-info-circle"></i>
                                        ' .
                                        htmlspecialchars(
                                            $case["worker_status"] ?? "Unknown"
                                        )
                                        .
                                      '</span>';

                            }

                            ?>

                        </div>

                    </div>


                </div>

            </div>


        <?php else: ?>


            <div class="no-worker">

                <i class="fas fa-user-slash"></i>

                <strong>
                    No Social Worker Assigned
                </strong>

                <span>
                    This case has not yet been assigned to a social worker.
                </span>

            </div>


        <?php endif; ?>

    </div>



    <!-- =================================================
         CASE DESCRIPTION
    ================================================== -->

    <div class="card">

        <h3 class="section-title">

            <i class="fas fa-file-alt"></i>

            Case Description

        </h3>


        <div class="description-box">

            <p class="description">

                <?php

                echo !empty($case["description"])

                    ? htmlspecialchars($case["description"])

                    : "No description was provided.";

                ?>

            </p>

        </div>

    </div>



    <!-- =================================================
         CASE ACTIONS
    ================================================== -->

    <div class="card">

        <h3 class="section-title">

            <i class="fas fa-cogs"></i>

            Case Actions

        </h3>


        <div class="actions">


            <a
                href="edit.php?id=<?php echo $case_id; ?>"
                class="action-btn edit-btn"
            >

                <i class="fas fa-edit"></i>

                Edit Case

            </a>


            <a
                href="delete.php?id=<?php echo $case_id; ?>"
                class="action-btn delete-btn"
                onclick="return confirm('Are you sure you want to delete this case? This action cannot be undone.');"
            >

                <i class="fas fa-trash-alt"></i>

                Delete Case

            </a>


            <a
                href="index.php"
                class="action-btn back-action"
            >

                <i class="fas fa-arrow-left"></i>

                Back to Cases

            </a>


        </div>

    </div>


</div>


</body>

</html>