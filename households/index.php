<?php
session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

function e($value)
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/
$search = trim($_GET["search"] ?? "");

if ($search !== "") {

    $sql = "
        SELECT
            h.household_id,
            h.household_number,
            h.household_name,
            h.address,
            h.chiefdom,
            h.section,
            h.phone,
            h.household_size,
            h.status,
            h.created_at,
            c.full_name AS head_name,
            c.client_number AS head_client_number
        FROM households h
        LEFT JOIN clients c
            ON h.head_client_id = c.client_id
        WHERE
            h.household_number LIKE ?
            OR h.household_name LIKE ?
            OR h.address LIKE ?
            OR h.chiefdom LIKE ?
            OR h.section LIKE ?
            OR h.phone LIKE ?
            OR c.full_name LIKE ?
            OR c.client_number LIKE ?
        ORDER BY h.household_id DESC
    ";

    $stmt = $conn->prepare($sql);

    $term = "%" . $search . "%";

    $stmt->bind_param(
        "ssssssss",
        $term,
        $term,
        $term,
        $term,
        $term,
        $term,
        $term,
        $term
    );

    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $sql = "
        SELECT
            h.household_id,
            h.household_number,
            h.household_name,
            h.address,
            h.chiefdom,
            h.section,
            h.phone,
            h.household_size,
            h.status,
            h.created_at,
            c.full_name AS head_name,
            c.client_number AS head_client_number
        FROM households h
        LEFT JOIN clients c
            ON h.head_client_id = c.client_id
        ORDER BY h.household_id DESC
    ";

    $result = $conn->query($sql);
}

/*
|--------------------------------------------------------------------------
| Total households
|--------------------------------------------------------------------------
*/
$total_result = $conn->query("SELECT COUNT(*) AS total FROM households");
$total_row = $total_result->fetch_assoc();
$total_households = $total_row["total"] ?? 0;

/*
|--------------------------------------------------------------------------
| Active households
|--------------------------------------------------------------------------
*/
$active_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM households
    WHERE status = 'Active'
");

$active_row = $active_result->fetch_assoc();
$active_households = $active_row["total"] ?? 0;

/*
|--------------------------------------------------------------------------
| Inactive households
|--------------------------------------------------------------------------
*/
$inactive_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM households
    WHERE status = 'Inactive'
");

$inactive_row = $inactive_result->fetch_assoc();
$inactive_households = $inactive_row["total"] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Households - Social Welfare</title>

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
            color: #333;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            width: 250px;
            background: #171717;
            color: white;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 22px 20px;
            border-bottom: 1px solid #303030;
        }

        .sidebar-logo {
            width: 45px;
            height: 45px;
            background: #e53935;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .sidebar-header p {
            color: #aaa;
            font-size: 13px;
        }

        .menu-title {
            color: #777;
            font-size: 11px;
            text-transform: uppercase;
            padding: 20px 20px 8px;
            letter-spacing: 1px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 20px;
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
            border-left: 3px solid transparent;
            transition: 0.2s;
        }

        .menu a:hover {
            background: #222;
            color: white;
        }

        .menu a.active {
            background: #222;
            color: white;
            border-left-color: #e53935;
        }

        .menu a i {
            width: 20px;
            text-align: center;
        }

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
            color: #ef5350;
        }

        .icon-activities {
            color: #ffee58;
        }

        .icon-appointments {
            color: #29b6f6;
        }

        .icon-reports {
            color: #7e57c2;
        }

        .icon-settings {
            color: #90a4ae;
        }

        .icon-logout {
            color: #ef5350;
        }

        /* MAIN */

        .main {
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        .topbar {
            height: 70px;
            background: white;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .topbar h1 {
            font-size: 22px;
            color: #222;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
            font-size: 14px;
        }

        .user-icon {
            width: 38px;
            height: 38px;
            background: #171717;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content {
            padding: 30px;
        }

        /* PAGE HEADER */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
        }

        .page-title h2 {
            font-size: 26px;
            margin-bottom: 6px;
            color: #222;
        }

        .page-title p {
            color: #777;
            font-size: 14px;
        }

        .add-btn {
            background: #e53935;
            color: white;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .add-btn:hover {
            background: #c62828;
        }

        /* STAT CARDS */

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e1e1e1;
        }

        .stat-info p {
            color: #777;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .stat-info h3 {
            font-size: 27px;
            color: #222;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .total-icon {
            background: #e3f2fd;
            color: #1e88e5;
        }

        .active-icon {
            background: #e8f5e9;
            color: #43a047;
        }

        .inactive-icon {
            background: #ffebee;
            color: #e53935;
        }

        /* SEARCH */

        .search-box {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            height: 43px;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 0 14px;
            font-size: 14px;
            outline: none;
        }

        .search-input:focus {
            border-color: #e53935;
        }

        .search-btn {
            height: 43px;
            background: #171717;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0 20px;
            cursor: pointer;
            font-size: 14px;
        }

        .search-btn:hover {
            background: #333;
        }

        .clear-btn {
            height: 43px;
            background: #eee;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            padding: 0 18px;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .clear-btn:hover {
            background: #ddd;
        }

        /* TABLE */

        .table-card {
            background: white;
            border-radius: 8px;
            border: 1px solid #ddd;
            overflow: hidden;
        }

        .table-header {
            padding: 18px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 17px;
            color: #222;
        }

        .record-count {
            color: #777;
            font-size: 13px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th {
            background: #f8f8f8;
            color: #555;
            font-size: 12px;
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #ddd;
            text-transform: uppercase;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
            color: #444;
        }

        tr:hover td {
            background: #fafafa;
        }

        .household-number {
            font-weight: bold;
            color: #e53935;
        }

        .household-name {
            font-weight: 600;
            color: #222;
        }

        .head-name {
            color: #333;
        }

        .head-number {
            color: #888;
            font-size: 11px;
            margin-top: 3px;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }

        .action-buttons {
            display: flex;
            gap: 6px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12px;
        }

        .view-btn {
            background: #e3f2fd;
            color: #1976d2;
        }

        .edit-btn {
            background: #fff3e0;
            color: #ef6c00;
        }

        .delete-btn {
            background: #ffebee;
            color: #d32f2f;
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        .empty {
            text-align: center;
            padding: 50px 20px;
            color: #888;
        }

        .empty i {
            font-size: 40px;
            margin-bottom: 15px;
            color: #bbb;
        }

        .empty h3 {
            margin-bottom: 7px;
            color: #555;
        }

        /* RESPONSIVE */

        @media (max-width: 900px) {

            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .stats {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 700px) {

            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .layout {
                display: block;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-form {
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-header">

            <div class="sidebar-logo">
                <i class="fas fa-hands-helping"></i>
            </div>

            <h2>Social Welfare</h2>

            <p>Tonkolili District</p>

        </div>


        <div class="menu">

            <div class="menu-title">Main Menu</div>

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

            <a href="index.php" class="active">
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


            <div class="menu-title">Management</div>

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

    </aside>


    <!-- MAIN -->

    <main class="main">

        <div class="topbar">

            <h1>Households</h1>

            <div class="user-info">

                <div class="user-icon">
                    <i class="fas fa-user"></i>
                </div>

                <span>
                    <?php echo e($_SESSION["full_name"] ?? $_SESSION["username"] ?? "User"); ?>
                </span>

            </div>

        </div>


        <div class="content">

            <!-- PAGE HEADER -->

            <div class="page-header">

                <div class="page-title">

                    <h2>Household Management</h2>

                    <p>
                        Register and manage social welfare household records.
                    </p>

                </div>

                <a href="add.php" class="add-btn">
                    <i class="fas fa-plus"></i>
                    Register Household
                </a>

            </div>


            <!-- STATISTICS -->

            <div class="stats">

                <div class="stat-card">

                    <div class="stat-info">
                        <p>Total Households</p>
                        <h3><?php echo $total_households; ?></h3>
                    </div>

                    <div class="stat-icon total-icon">
                        <i class="fas fa-home"></i>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-info">
                        <p>Active Households</p>
                        <h3><?php echo $active_households; ?></h3>
                    </div>

                    <div class="stat-icon active-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-info">
                        <p>Inactive Households</p>
                        <h3><?php echo $inactive_households; ?></h3>
                    </div>

                    <div class="stat-icon inactive-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>

                </div>

            </div>


            <!-- SEARCH -->

            <div class="search-box">

                <form method="GET" class="search-form">

                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="Search household number, name, head, chiefdom, section or phone..."
                        value="<?php echo e($search); ?>"
                    >

                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        Search
                    </button>

                    <?php if ($search !== ""): ?>

                        <a href="index.php" class="clear-btn">
                            <i class="fas fa-times"></i>&nbsp;
                            Clear
                        </a>

                    <?php endif; ?>

                </form>

            </div>


            <!-- TABLE -->

            <div class="table-card">

                <div class="table-header">

                    <h3>
                        Household Records
                    </h3>

                    <span class="record-count">
                        <?php echo $result->num_rows; ?> record(s)
                    </span>

                </div>


                <?php if ($result->num_rows > 0): ?>

                    <div class="table-wrapper">

                        <table>

                            <thead>

                                <tr>

                                    <th>Household No.</th>

                                    <th>Household Name</th>

                                    <th>Head of Household</th>

                                    <th>Location</th>

                                    <th>Phone</th>

                                    <th>Members</th>

                                    <th>Status</th>

                                    <th>Created</th>

                                    <th>Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <span class="household-number">
                                            <?php echo e($row["household_number"]); ?>
                                        </span>
                                    </td>


                                    <td>
                                        <span class="household-name">
                                            <?php echo e($row["household_name"]); ?>
                                        </span>
                                    </td>


                                    <td>

                                        <?php if (!empty($row["head_name"])): ?>

                                            <div class="head-name">
                                                <?php echo e($row["head_name"]); ?>
                                            </div>

                                            <?php if (!empty($row["head_client_number"])): ?>

                                                <div class="head-number">
                                                    <?php echo e($row["head_client_number"]); ?>
                                                </div>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span style="color:#999;">
                                                Not assigned
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (!empty($row["chiefdom"])): ?>

                                            <div>
                                                <?php echo e($row["chiefdom"]); ?>
                                            </div>

                                        <?php endif; ?>

                                        <?php if (!empty($row["section"])): ?>

                                            <div style="font-size:11px;color:#888;">
                                                <?php echo e($row["section"]); ?>
                                            </div>

                                        <?php endif; ?>

                                    </td>


                                    <td>
                                        <?php echo e($row["phone"] ?: "—"); ?>
                                    </td>


                                    <td>
                                        <strong>
                                            <?php echo (int)$row["household_size"]; ?>
                                        </strong>
                                    </td>


                                    <td>

                                        <?php if ($row["status"] === "Active"): ?>

                                            <span class="status status-active">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="status status-inactive">
                                                Inactive
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>
                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime($row["created_at"])
                                        );
                                        ?>
                                    </td>


                                    <td>

                                        <div class="action-buttons">

                                            <a
                                                href="view.php?id=<?php echo $row["household_id"]; ?>"
                                                class="action-btn view-btn"
                                                title="View Household"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </a>


                                            <a
                                                href="edit.php?id=<?php echo $row["household_id"]; ?>"
                                                class="action-btn edit-btn"
                                                title="Edit Household"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </a>


                                            <a
                                                href="delete.php?id=<?php echo $row["household_id"]; ?>"
                                                class="action-btn delete-btn"
                                                title="Delete Household"
                                                onclick="return confirm('Are you sure you want to delete this household?');"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty">

                        <i class="fas fa-home"></i>

                        <h3>No Households Found</h3>

                        <?php if ($search !== ""): ?>

                            <p>
                                No household records match your search.
                            </p>

                        <?php else: ?>

                            <p>
                                No household has been registered yet.
                            </p>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

</body>

</html>