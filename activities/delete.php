<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

/* =========================
   GET ACTIVITY ID
========================= */
$activity_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($activity_id <= 0) {
    header("Location: index.php?error=Invalid activity ID.");
    exit;
}

/* =========================
   LOAD ACTIVITY
========================= */
$stmt = $conn->prepare("
    SELECT
        a.activity_id,
        a.activity_number,
        a.activity_type,
        a.activity_date,
        a.location,
        c.full_name AS client_name,
        sw.full_name AS worker_name
    FROM activities a
    LEFT JOIN clients c
        ON a.client_id = c.client_id
    LEFT JOIN social_workers sw
        ON a.worker_id = sw.worker_id
    WHERE a.activity_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $activity_id);
$stmt->execute();

$result = $stmt->get_result();
$activity = $result->fetch_assoc();

$stmt->close();

if (!$activity) {
    header("Location: index.php?error=Activity not found.");
    exit;
}

/* =========================
   DELETE ACTIVITY
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $delete = $conn->prepare("
        DELETE FROM activities
        WHERE activity_id = ?
    ");

    $delete->bind_param("i", $activity_id);

    if ($delete->execute()) {
        $delete->close();

        header("Location: index.php?success=Activity deleted successfully.");
        exit;
    }

    $delete_error = "Unable to delete the activity. Please try again.";

    $delete->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Delete Activity | Tonkolili District Social Welfare</title>

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

        .topbar .user {
            font-size: 14px;
            color: #555;
        }

        .content {
            padding: 30px;
            max-width: 850px;
        }

        .page-title {
            margin-bottom: 20px;
        }

        .page-title h2 {
            margin: 0 0 6px;
            font-size: 25px;
        }

        .page-title p {
            margin: 0;
            color: #777;
        }

        .card {
            background: white;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .warning {
            background: #fff4f4;
            border-left: 5px solid #e53935;
            padding: 18px;
            margin-bottom: 25px;
            color: #8b1e1e;
        }

        .warning i {
            margin-right: 8px;
        }

        .details {
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        .detail-row {
            display: flex;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            width: 200px;
            background: #f7f7f7;
            padding: 13px 15px;
            font-weight: bold;
            color: #555;
        }

        .detail-value {
            flex: 1;
            padding: 13px 15px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            border: none;
            padding: 11px 18px;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-danger {
            background: #e53935;
            color: white;
        }

        .btn-danger:hover {
            background: #c62828;
        }

        .btn-secondary {
            background: #555;
            color: white;
        }

        .btn-secondary:hover {
            background: #333;
        }

        .error {
            background: #ffe5e5;
            color: #a00000;
            border: 1px solid #ffb3b3;
            padding: 13px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
            }

            .content {
                padding: 20px;
            }

            .detail-row {
                display: block;
            }

            .detail-label {
                width: 100%;
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
        <h2><i class="fas fa-hands-helping"></i> Social Welfare</h2>
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

<!-- =========================
     MAIN CONTENT
========================= -->
<div class="main">

    <div class="topbar">
        <h1>Delete Activity</h1>

        <div class="user">
            <i class="fas fa-user-circle"></i>
            <?php echo htmlspecialchars($_SESSION["full_name"] ?? $_SESSION["username"] ?? "User"); ?>
        </div>
    </div>

    <div class="content">

        <div class="page-title">
            <h2>Delete Activity</h2>
            <p>Review the activity before permanently deleting it.</p>
        </div>

        <?php if (isset($delete_error)): ?>

            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($delete_error); ?>
            </div>

        <?php endif; ?>

        <div class="card">

            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong>
                This action cannot be undone. Are you sure you want to delete this activity?
            </div>

            <div class="details">

                <div class="detail-row">
                    <div class="detail-label">
                        Activity Number
                    </div>

                    <div class="detail-value">
                        <?php echo htmlspecialchars($activity["activity_number"]); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        Activity Type
                    </div>

                    <div class="detail-value">
                        <?php echo htmlspecialchars($activity["activity_type"]); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        Activity Date
                    </div>

                    <div class="detail-value">
                        <?php
                        echo date(
                            "d F Y",
                            strtotime($activity["activity_date"])
                        );
                        ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        Location
                    </div>

                    <div class="detail-value">
                        <?php echo htmlspecialchars($activity["location"] ?: "Not provided"); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        Related Client
                    </div>

                    <div class="detail-value">
                        <?php echo htmlspecialchars($activity["client_name"] ?: "Not linked"); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        Social Worker
                    </div>

                    <div class="detail-value">
                        <?php echo htmlspecialchars($activity["worker_name"] ?: "Not assigned"); ?>
                    </div>
                </div>

            </div>

            <form method="POST">

                <div class="actions">

                    <button
                        type="submit"
                        class="btn btn-danger"
                        onclick="return confirm('Are you sure you want to permanently delete this activity?');"
                    >
                        <i class="fas fa-trash"></i>
                        Yes, Delete Activity
                    </button>

                    <a
                        href="view.php?id=<?php echo $activity_id; ?>"
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