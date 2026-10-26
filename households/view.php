<?php
session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$household_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($household_id <= 0) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Load Household
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        h.*,
        c.full_name AS head_name,
        c.client_number AS head_client_number,
        c.gender AS head_gender,
        c.phone AS head_phone,
        c.date_of_birth AS head_date_of_birth
    FROM households h
    LEFT JOIN clients c
        ON h.head_client_id = c.client_id
    WHERE h.household_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $household_id);
$stmt->execute();

$result = $stmt->get_result();
$household = $result->fetch_assoc();

$stmt->close();

if (!$household) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Load Household Members
|--------------------------------------------------------------------------
*/

$members_stmt = $conn->prepare("
    SELECT
        hm.member_id,
        hm.relationship_to_head,
        hm.joined_date,
        hm.notes,

        c.client_id,
        c.client_number,
        c.full_name,
        c.gender,
        c.date_of_birth,
        c.phone

    FROM household_members hm

    INNER JOIN clients c
        ON hm.client_id = c.client_id

    WHERE hm.household_id = ?

    ORDER BY c.full_name ASC
");

$members_stmt->bind_param(
    "i",
    $household_id
);

$members_stmt->execute();

$members = $members_stmt->get_result();

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
        Household Profile - Social Welfare
    </title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    >

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
            width: 250px;
            height: 100vh;
            background: #171717;
            color: #fff;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 22px 20px;
            border-bottom: 1px solid #333;
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 22px;
        }

        .sidebar-header p {
            margin: 5px 0 0;
            color: #aaa;
            font-size: 13px;
        }

        .menu-title {
            padding: 20px 20px 8px;
            color: #777;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 20px;
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
            border-left: 4px solid transparent;
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
        }

        .icon-dashboard {
            color: #42a5f5;
        }

        .icon-clients {
            color: #66bb6a;
        }

        .icon-cases {
            color: #ffca28;
        }

        .icon-documents {
            color: #ab47bc;
        }

        .icon-workers {
            color: #29b6f6;
        }

        .icon-households {
            color: #ff7043;
        }

        .icon-activities {
            color: #26a69a;
        }

        .icon-appointments {
            color: #ec407a;
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
            min-height: 100vh;
        }

        .topbar {
            height: 70px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 30px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .user-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #171717;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content {
            padding: 30px;
        }

        /* PAGE HEADER */

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 27px;
        }

        .page-header p {
            margin: 0;
            color: #777;
        }

        /* BUTTONS */

        .buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
        }

        .btn-back {
            background: #eee;
            color: #333;
        }

        .btn-edit {
            background: #171717;
            color: #fff;
        }

        .btn-delete {
            background: #e53935;
            color: #fff;
        }

        .btn-members {
            background: #1976d2;
            color: #fff;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* PROFILE HEADER */

        .profile-header {
            background: #171717;
            color: #fff;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .profile-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .profile-header h2 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .profile-number {
            color: #bbb;
            font-size: 14px;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background: #2e7d32;
            color: #fff;
        }

        .status-inactive {
            background: #616161;
            color: #fff;
        }

        .registered {
            margin-top: 18px;
            color: #bbb;
            font-size: 13px;
        }

        /* CARDS */

        .card {
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .card-title {
            font-size: 19px;
            font-weight: bold;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .card-title i {
            margin-right: 7px;
        }

        /* INFORMATION GRID */

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f8f8;
            border-radius: 6px;
            border-left: 3px solid #ddd;
        }

        .info-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 15px;
            font-weight: bold;
        }

        /* HEAD */

        .head-box {
            display: flex;
            align-items: center;
            gap: 18px;
            background: #f8f8f8;
            padding: 20px;
            border-radius: 7px;
        }

        .head-icon {
            width: 55px;
            height: 55px;
            background: #171717;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .head-details h3 {
            margin: 0 0 6px;
        }

        .head-details p {
            margin: 4px 0;
            color: #666;
            font-size: 13px;
        }

        .profile-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            color: #1976d2;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
        }

        /* MEMBERS */

        .member-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .member-count {
            background: #171717;
            color: #fff;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th,
        td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        th {
            background: #f7f7f7;
            color: #555;
            font-size: 11px;
            text-transform: uppercase;
        }

        tr:hover td {
            background: #fafafa;
        }

        .member-name {
            font-weight: bold;
        }

        .member-number {
            color: #777;
            font-size: 11px;
            margin-top: 3px;
        }

        .relationship {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 15px;
            background: #eee;
            color: #555;
            font-size: 11px;
            font-weight: bold;
        }

        .view-client {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #1976d2;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
        }

        .empty-members {
            text-align: center;
            padding: 35px 20px;
            color: #777;
        }

        .empty-members i {
            font-size: 42px;
            color: #bbb;
            margin-bottom: 12px;
        }

        /* NOTES */

        .notes {
            background: #f8f8f8;
            padding: 18px;
            border-radius: 6px;
            line-height: 1.6;
            color: #555;
        }

        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .profile-header-top {
                flex-direction: column;
            }

            .content {
                padding: 20px;
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
            }

            .content {
                padding: 15px;
            }

        }

    </style>

</head>

<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <div class="sidebar-header">
        <h2>Social Welfare</h2>
        <p>Tonkolili District</p>
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

    <a href="index.php" class="active">
        <i class="fas fa-home icon-households"></i>
        Households
    </a>

    <a href="../activities/index.php">
        <i class="fas fa-tasks icon-activities"></i>
        Activities
    </a>

    <a href="../appointments/index.php">
        <i class="fas fa-calendar-check icon-appointments"></i>
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


<!-- MAIN -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div class="user-info">

            <div class="user-icon">
                <i class="fas fa-user"></i>
            </div>

            <span>
                <?php
                echo htmlspecialchars(
                    $_SESSION["full_name"] ??
                    "System Administrator"
                );
                ?>
            </span>

        </div>

    </div>


    <div class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <h1>
                <i class="fas fa-home"></i>
                Household Profile
            </h1>

            <p>
                View complete household information.
            </p>

        </div>


        <!-- BUTTONS -->

        <div class="buttons">

            <a
                href="index.php"
                class="btn btn-back"
            >
                <i class="fas fa-arrow-left"></i>
                Back
            </a>


            <a
                href="edit.php?id=<?php echo $household_id; ?>"
                class="btn btn-edit"
            >
                <i class="fas fa-edit"></i>
                Edit
            </a>


            <a
                href="members.php?id=<?php echo $household_id; ?>"
                class="btn btn-members"
            >
                <i class="fas fa-users"></i>
                Manage Members
            </a>


            <a
                href="delete.php?id=<?php echo $household_id; ?>"
                class="btn btn-delete"
                onclick="return confirm('Are you sure you want to delete this household?');"
            >
                <i class="fas fa-trash"></i>
                Delete
            </a>

        </div>


        <!-- PROFILE HEADER -->

        <div class="profile-header">

            <div class="profile-header-top">

                <div>

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $household["household_name"]
                        );
                        ?>
                    </h2>

                    <div class="profile-number">

                        <i class="fas fa-hashtag"></i>

                        <?php
                        echo htmlspecialchars(
                            $household["household_number"]
                        );
                        ?>

                    </div>

                </div>


                <div>

                    <?php if ($household["status"] === "Active"): ?>

                        <span class="status status-active">
                            Active
                        </span>

                    <?php else: ?>

                        <span class="status status-inactive">
                            Inactive
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <div class="registered">

                <i class="far fa-calendar-alt"></i>

                Registered on

                <?php
                echo date(
                    "d M Y, h:i A",
                    strtotime(
                        $household["created_at"]
                    )
                );
                ?>

            </div>

        </div>


        <!-- HOUSEHOLD INFORMATION -->

        <div class="card">

            <div class="card-title">

                <i class="fas fa-info-circle"></i>

                Household Information

            </div>


            <div class="info-grid">


                <div class="info-item">

                    <div class="info-label">
                        Household Number
                    </div>

                    <div class="info-value">
                        <?php
                        echo htmlspecialchars(
                            $household["household_number"]
                        );
                        ?>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Household Name
                    </div>

                    <div class="info-value">
                        <?php
                        echo htmlspecialchars(
                            $household["household_name"]
                        );
                        ?>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Household Size
                    </div>

                    <div class="info-value">

                        <?php
                        echo (int)$household["household_size"];
                        ?>

                        member(s)

                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Address
                    </div>

                    <div class="info-value">
                        <?php
                        echo htmlspecialchars(
                            $household["address"] ?: "-"
                        );
                        ?>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Chiefdom
                    </div>

                    <div class="info-value">
                        <?php
                        echo htmlspecialchars(
                            $household["chiefdom"] ?: "-"
                        );
                        ?>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Section
                    </div>

                    <div class="info-value">
                        <?php
                        echo htmlspecialchars(
                            $household["section"] ?: "-"
                        );
                        ?>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Phone
                    </div>

                    <div class="info-value">
                        <?php
                        echo htmlspecialchars(
                            $household["phone"] ?: "-"
                        );
                        ?>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Status
                    </div>

                    <div class="info-value">
                        <?php
                        echo htmlspecialchars(
                            $household["status"]
                        );
                        ?>
                    </div>

                </div>


            </div>

        </div>


        <!-- HEAD OF HOUSEHOLD -->

        <div class="card">

            <div class="card-title">

                <i class="fas fa-user"></i>

                Head of Household

            </div>


            <?php if (!empty($household["head_name"])): ?>

                <div class="head-box">

                    <div class="head-icon">

                        <i class="fas fa-user"></i>

                    </div>


                    <div class="head-details">

                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $household["head_name"]
                            );
                            ?>

                        </h3>


                        <p>

                            <strong>
                                Client Number:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $household[
                                    "head_client_number"
                                ]
                            );
                            ?>

                        </p>


                        <p>

                            <strong>
                                Gender:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $household[
                                    "head_gender"
                                ] ?? "-"
                            );
                            ?>

                        </p>


                        <p>

                            <strong>
                                Phone:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $household[
                                    "head_phone"
                                ] ?? "-"
                            );
                            ?>

                        </p>


                        <a
                            href="../clients/view.php?id=<?php echo (int)$household["head_client_id"]; ?>"
                            class="profile-link"
                        >

                            <i class="fas fa-external-link-alt"></i>

                            View Client Profile

                        </a>

                    </div>

                </div>

            <?php else: ?>

                <div class="empty-members">

                    <i class="fas fa-user-slash"></i>

                    <p>
                        No head of household has been assigned.
                    </p>

                </div>

            <?php endif; ?>

        </div>


        <!-- HOUSEHOLD MEMBERS -->

        <div class="card">


            <div class="member-header">

                <div class="card-title" style="margin:0;padding:0;border:0;">

                    <i class="fas fa-users"></i>

                    Household Members

                </div>


                <div class="member-count">

                    <?php
                    echo $members->num_rows;
                    ?>

                    member(s)

                </div>

            </div>


            <?php if ($members->num_rows > 0): ?>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Member
                                </th>

                                <th>
                                    Gender
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Relationship
                                </th>

                                <th>
                                    Joined Date
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php
                            while (
                                $member =
                                $members->fetch_assoc()
                            ):
                            ?>

                                <tr>


                                    <td>

                                        <div class="member-name">

                                            <?php
                                            echo htmlspecialchars(
                                                $member[
                                                    "full_name"
                                                ]
                                            );
                                            ?>

                                        </div>

                                        <div class="member-number">

                                            <?php
                                            echo htmlspecialchars(
                                                $member[
                                                    "client_number"
                                                ]
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $member[
                                                "gender"
                                            ] ?? "-"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $member[
                                                "phone"
                                            ] ?? "-"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span class="relationship">

                                            <?php
                                            echo htmlspecialchars(
                                                $member[
                                                    "relationship_to_head"
                                                ] ??
                                                "Not specified"
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php

                                        if (
                                            !empty(
                                                $member[
                                                    "joined_date"
                                                ]
                                            )
                                        ) {

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $member[
                                                        "joined_date"
                                                    ]
                                                )
                                            );

                                        } else {

                                            echo "-";

                                        }

                                        ?>

                                    </td>


                                    <td>

                                        <a
                                            href="../clients/view.php?id=<?php echo (int)$member["client_id"]; ?>"
                                            class="view-client"
                                        >

                                            <i class="fas fa-eye"></i>

                                            View Client

                                        </a>

                                    </td>


                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty-members">

                    <i class="fas fa-user-friends"></i>

                    <h3>
                        No Household Members
                    </h3>

                    <p>
                        No clients have been linked to this household yet.
                    </p>


                    <a
                        href="members.php?id=<?php echo $household_id; ?>"
                        class="btn btn-members"
                    >

                        <i class="fas fa-user-plus"></i>

                        Add Household Member

                    </a>

                </div>


            <?php endif; ?>


        </div>


        <!-- NOTES -->

        <?php if (!empty($household["notes"])): ?>

            <div class="card">

                <div class="card-title">

                    <i class="fas fa-sticky-note"></i>

                    Notes

                </div>


                <div class="notes">

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $household["notes"]
                        )
                    );
                    ?>

                </div>

            </div>

        <?php endif; ?>


    </div>

</div>

</body>

</html>