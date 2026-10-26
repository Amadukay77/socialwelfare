<?php
session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php?error=Invalid activity ID.");
    exit;
}

$activity_id = (int) $_GET["id"];

/* =========================================================
   GET ACTIVITY DETAILS
========================================================= */

$sql = "
    SELECT
        a.*,

        c.client_number,
        c.full_name AS client_name,
        c.gender AS client_gender,
        c.phone AS client_phone,

        cs.case_number,
        cs.case_type,
        cs.status AS case_status,

        sw.worker_number,
        sw.full_name AS worker_name,
        sw.position AS worker_position

    FROM activities a

    LEFT JOIN clients c
        ON a.client_id = c.client_id

    LEFT JOIN cases cs
        ON a.case_id = cs.case_id

    LEFT JOIN social_workers sw
        ON a.worker_id = sw.worker_id

    WHERE a.activity_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

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
   HELPER FUNCTION
========================================================= */

function display_value($value)
{
    if ($value === null || trim((string)$value) === "") {
        return "Not provided";
    }

    return nl2br(htmlspecialchars($value));
}


/* =========================================================
   FORMAT DATE
========================================================= */

$activity_date = "Not provided";

if (!empty($activity["activity_date"])) {
    $activity_date = date(
        "d F Y",
        strtotime($activity["activity_date"])
    );
}

$created_date = "Not provided";

if (!empty($activity["created_at"])) {
    $created_date = date(
        "d F Y, h:i A",
        strtotime($activity["created_at"])
    );
}


/* =========================================================
   ACTIVITY STATUS / TYPE ICON
========================================================= */

$activity_type = strtolower($activity["activity_type"] ?? "");

$type_icon = "fas fa-calendar-check";

if (strpos($activity_type, "meeting") !== false) {
    $type_icon = "fas fa-users";
} elseif (strpos($activity_type, "visit") !== false) {
    $type_icon = "fas fa-home";
} elseif (strpos($activity_type, "training") !== false) {
    $type_icon = "fas fa-chalkboard-teacher";
} elseif (strpos($activity_type, "assessment") !== false) {
    $type_icon = "fas fa-clipboard-check";
} elseif (strpos($activity_type, "counselling") !== false || strpos($activity_type, "counseling") !== false) {
    $type_icon = "fas fa-comments";
} elseif (strpos($activity_type, "referral") !== false) {
    $type_icon = "fas fa-share-square";
} elseif (strpos($activity_type, "awareness") !== false) {
    $type_icon = "fas fa-bullhorn";
} elseif (strpos($activity_type, "workshop") !== false) {
    $type_icon = "fas fa-tools";
}


/* =========================================================
   SIDEBAR USER INFORMATION
========================================================= */

$full_name = $_SESSION["full_name"] ?? "User";
$username = $_SESSION["username"] ?? "";
$role = $_SESSION["role"] ?? "User";

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
        View Activity - Tonkolili District Social Welfare
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

        /* =================================================
           SIDEBAR
        ================================================= */

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

        /* Different icon colors */

        .icon-dashboard {
            color: #42a5f5;
        }

        .icon-client {
            color: #66bb6a;
        }

        .icon-case {
            color: #ffa726;
        }

        .icon-document {
            color: #ab47bc;
        }

        .icon-worker {
            color: #26c6da;
        }

        .icon-household {
            color: #ec407a;
        }

        .icon-activity {
            color: #ffca28;
        }

        .icon-appointment {
            color: #29b6f6;
        }

        .icon-report {
            color: #7e57c2;
        }

        .icon-settings {
            color: #bdbdbd;
        }

        .icon-logout {
            color: #ef5350;
        }


        /* =================================================
           MAIN
        ================================================= */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            height: 70px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .topbar-title h1 {
            font-size: 22px;
            color: #222;
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
        }


        /* =================================================
           BREADCRUMB
        ================================================= */

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


        /* =================================================
           PAGE HEADER
        ================================================= */

        .page-header {
            background: #fff;
            border-radius: 10px;
            padding: 22px 25px;
            margin-bottom: 20px;
            border: 1px solid #e1e1e1;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .activity-icon {
            width: 55px;
            height: 55px;
            border-radius: 10px;
            background: #fff3cd;
            color: #f39c12;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 23px;
        }

        .page-title h2 {
            font-size: 22px;
            margin-bottom: 5px;
        }

        .page-title p {
            color: #777;
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-back {
            background: #e9ecef;
            color: #333;
        }

        .btn-edit {
            background: #1976d2;
            color: #fff;
        }

        .btn-delete {
            background: #e53935;
            color: #fff;
        }

        .btn:hover {
            opacity: 0.9;
        }


        /* =================================================
           CARDS
        ================================================= */

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card {
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 10px;
            overflow: hidden;
        }

        .card.full {
            grid-column: 1 / -1;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            background: #fafafa;

            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header i {
            color: #e53935;
        }

        .card-header h3 {
            font-size: 15px;
        }

        .card-body {
            padding: 20px;
        }


        /* =================================================
           DETAILS
        ================================================= */

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 25px;
        }

        .detail-item {
            border-bottom: 1px solid #eee;
            padding-bottom: 12px;
        }

        .detail-item.full {
            grid-column: 1 / -1;
        }

        .detail-label {
            color: #777;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #222;
            font-size: 14px;
            line-height: 1.6;
            font-weight: 500;
        }


        /* =================================================
           BADGES
        ================================================= */

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-type {
            background: #fff3cd;
            color: #856404;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-muted {
            background: #e9ecef;
            color: #555;
        }


        /* =================================================
           EMPTY
        ================================================= */

        .not-provided {
            color: #999;
            font-style: italic;
            font-weight: normal;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 900px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .card.full {
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
                height: auto;
                padding: 15px;
                gap: 15px;
                flex-direction: column;
                align-items: flex-start;
            }

            .content {
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .detail-item.full {
                grid-column: auto;
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


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div class="topbar-title">

            <h1>
                Activity Details
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

            View Activity

        </div>


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div class="page-title">

                <div class="activity-icon">

                    <i class="<?php echo htmlspecialchars($type_icon); ?>"></i>

                </div>


                <div>

                    <h2>
                        <?php echo htmlspecialchars($activity["activity_number"]); ?>
                    </h2>

                    <p>
                        Complete activity record
                    </p>

                </div>

            </div>


            <div class="actions">

                <a
                    href="index.php"
                    class="btn btn-back"
                >

                    <i class="fas fa-arrow-left"></i>

                    Back

                </a>


                <a
                    href="edit.php?id=<?php echo $activity_id; ?>"
                    class="btn btn-edit"
                >

                    <i class="fas fa-edit"></i>

                    Edit

                </a>


                <a
                    href="delete.php?id=<?php echo $activity_id; ?>"
                    class="btn btn-delete"
                    onclick="return confirm('Are you sure you want to delete this activity?');"
                >

                    <i class="fas fa-trash"></i>

                    Delete

                </a>

            </div>

        </div>


        <!-- =================================================
             INFORMATION GRID
        ================================================= -->

        <div class="grid">


            <!-- ACTIVITY INFORMATION -->

            <div class="card">

                <div class="card-header">

                    <i class="fas fa-calendar-check"></i>

                    <h3>
                        Activity Information
                    </h3>

                </div>


                <div class="card-body">

                    <div class="detail-grid">


                        <div class="detail-item">

                            <div class="detail-label">
                                Activity Number
                            </div>

                            <div class="detail-value">

                                <?php
                                echo htmlspecialchars(
                                    $activity["activity_number"]
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">
                                Activity Type
                            </div>

                            <div class="detail-value">

                                <span class="badge badge-type">

                                    <i class="<?php echo htmlspecialchars($type_icon); ?>"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $activity["activity_type"]
                                    );
                                    ?>

                                </span>

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">
                                Activity Date
                            </div>

                            <div class="detail-value">

                                <i class="far fa-calendar"></i>

                                <?php
                                echo htmlspecialchars($activity_date);
                                ?>

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">
                                Location
                            </div>

                            <div class="detail-value">

                                <?php
                                echo display_value(
                                    $activity["location"]
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-item full">

                            <div class="detail-label">
                                Participants
                            </div>

                            <div class="detail-value">

                                <?php
                                echo display_value(
                                    $activity["participants"]
                                );
                                ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- CLIENT INFORMATION -->

            <div class="card">

                <div class="card-header">

                    <i class="fas fa-user"></i>

                    <h3>
                        Related Client
                    </h3>

                </div>


                <div class="card-body">

                    <div class="detail-grid">


                        <div class="detail-item">

                            <div class="detail-label">
                                Client Number
                            </div>

                            <div class="detail-value">

                                <?php if (!empty($activity["client_number"])): ?>

                                    <a
                                        href="../clients/view.php?id=<?php echo (int)$activity["client_id"]; ?>"
                                        style="color:#e53935;"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $activity["client_number"]
                                        );
                                        ?>

                                    </a>

                                <?php else: ?>

                                    <span class="not-provided">
                                        Not linked
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">
                                Full Name
                            </div>

                            <div class="detail-value">

                                <?php
                                echo display_value(
                                    $activity["client_name"]
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">
                                Gender
                            </div>

                            <div class="detail-value">

                                <?php
                                echo display_value(
                                    $activity["client_gender"]
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">
                                Phone
                            </div>

                            <div class="detail-value">

                                <?php
                                echo display_value(
                                    $activity["client_phone"]
                                );
                                ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- CASE INFORMATION -->

            <div class="card">

                <div class="card-header">

                    <i class="fas fa-folder-open"></i>

                    <h3>
                        Related Case
                    </h3>

                </div>


                <div class="card-body">

                    <div class="detail-grid">


                        <div class="detail-item">

                            <div class="detail-label">
                                Case Number
                            </div>

                            <div class="detail-value">

                                <?php if (!empty($activity["case_number"])): ?>

                                    <a
                                        href="../cases/view.php?id=<?php echo (int)$activity["case_id"]; ?>"
                                        style="color:#e53935;"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $activity["case_number"]
                                        );
                                        ?>

                                    </a>

                                <?php else: ?>

                                    <span class="not-provided">
                                        Not linked
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">
                                Case Type
                            </div>

                            <div class="detail-value">

                                <?php
                                echo display_value(
                                    $activity["case_type"]
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">
                                Case Status
                            </div>

                            <div class="detail-value">

                                <?php if (!empty($activity["case_status"])): ?>

                                    <span class="badge badge-active">

                                        <?php
                                        echo htmlspecialchars(
                                            $activity["case_status"]
                                        );
                                        ?>

                                    </span>

                                <?php else: ?>

                                    <span class="not-provided">
                                        Not linked
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- SOCIAL WORKER -->

            <div class="card">

                <div class="card-header">

                    <i class="fas fa-user-tie"></i>

                    <h3>
                        Social Worker
                    </h3>

                </div>


                <div class="card-body">

                    <div class="detail-grid">


                        <div class="detail-item">

                            <div class="detail-label">
                                Worker Number
                            </div>

                            <div class="detail-value">

                                <?php
                                echo display_value(
                                    $activity["worker_number"]
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">
                                Full Name
                            </div>

                            <div class="detail-value">

                                <?php
                                echo display_value(
                                    $activity["worker_name"]
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-item full">

                            <div class="detail-label">
                                Position
                            </div>

                            <div class="detail-value">

                                <?php
                                echo display_value(
                                    $activity["worker_position"]
                                );
                                ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- DESCRIPTION -->

            <div class="card full">

                <div class="card-header">

                    <i class="fas fa-align-left"></i>

                    <h3>
                        Activity Description
                    </h3>

                </div>


                <div class="card-body">

                    <div class="detail-value">

                        <?php
                        echo display_value(
                            $activity["description"]
                        );
                        ?>

                    </div>

                </div>

            </div>


            <!-- OUTCOME -->

            <div class="card">

                <div class="card-header">

                    <i class="fas fa-check-circle"></i>

                    <h3>
                        Outcome
                    </h3>

                </div>


                <div class="card-body">

                    <div class="detail-value">

                        <?php
                        echo display_value(
                            $activity["outcome"]
                        );
                        ?>

                    </div>

                </div>

            </div>


            <!-- NOTES -->

            <div class="card">

                <div class="card-header">

                    <i class="fas fa-sticky-note"></i>

                    <h3>
                        Notes
                    </h3>

                </div>


                <div class="card-body">

                    <div class="detail-value">

                        <?php
                        echo display_value(
                            $activity["notes"]
                        );
                        ?>

                    </div>

                </div>

            </div>


            <!-- RECORD INFORMATION -->

            <div class="card full">

                <div class="card-header">

                    <i class="fas fa-info-circle"></i>

                    <h3>
                        Record Information
                    </h3>

                </div>


                <div class="card-body">

                    <div class="detail-grid">


                        <div class="detail-item">

                            <div class="detail-label">
                                Activity ID
                            </div>

                            <div class="detail-value">

                                #<?php
                                echo (int)$activity["activity_id"];
                                ?>

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">
                                Record Created
                            </div>

                            <div class="detail-value">

                                <?php
                                echo htmlspecialchars($created_date);
                                ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


        </div>

    </div>

</div>


</body>
</html>