<?php
session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Household ID
|--------------------------------------------------------------------------
*/
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
    SELECT household_id, household_number, household_name, household_size
    FROM households
    WHERE household_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $household_id);
$stmt->execute();

$household = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$household) {
    header("Location: index.php");
    exit;
}

$errors = [];
$success = "";

/*
|--------------------------------------------------------------------------
| Add Household Member
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $client_id = isset($_POST["client_id"])
        ? (int)$_POST["client_id"]
        : 0;

    $relationship_to_head = trim(
        $_POST["relationship_to_head"] ?? ""
    );

    $joined_date = trim(
        $_POST["joined_date"] ?? ""
    );

    $notes = trim(
        $_POST["notes"] ?? ""
    );

    if ($client_id <= 0) {
        $errors[] = "Please select a client.";
    }

    /*
    |--------------------------------------------------------------------------
    | Check Client Exists
    |--------------------------------------------------------------------------
    */
    if (empty($errors)) {

        $check_client = $conn->prepare("
            SELECT client_id
            FROM clients
            WHERE client_id = ?
            LIMIT 1
        ");

        $check_client->bind_param(
            "i",
            $client_id
        );

        $check_client->execute();

        $client_exists = $check_client
            ->get_result()
            ->fetch_assoc();

        $check_client->close();

        if (!$client_exists) {
            $errors[] = "The selected client does not exist.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Member
    |--------------------------------------------------------------------------
    */
    if (empty($errors)) {

        $check_member = $conn->prepare("
            SELECT member_id
            FROM household_members
            WHERE household_id = ?
            AND client_id = ?
            LIMIT 1
        ");

        $check_member->bind_param(
            "ii",
            $household_id,
            $client_id
        );

        $check_member->execute();

        $existing_member = $check_member
            ->get_result()
            ->fetch_assoc();

        $check_member->close();

        if ($existing_member) {
            $errors[] = "This client is already a member of this household.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Insert Member
    |--------------------------------------------------------------------------
    */
    if (empty($errors)) {

        if ($joined_date === "") {

            $stmt = $conn->prepare("
                INSERT INTO household_members
                (
                    household_id,
                    client_id,
                    relationship_to_head,
                    joined_date,
                    notes
                )
                VALUES (?, ?, ?, NULL, ?)
            ");

            $stmt->bind_param(
                "iiss",
                $household_id,
                $client_id,
                $relationship_to_head,
                $notes
            );

        } else {

            $stmt = $conn->prepare("
                INSERT INTO household_members
                (
                    household_id,
                    client_id,
                    relationship_to_head,
                    joined_date,
                    notes
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "iisss",
                $household_id,
                $client_id,
                $relationship_to_head,
                $joined_date,
                $notes
            );
        }

        if ($stmt->execute()) {

            $stmt->close();

            header(
                "Location: members.php?id=" .
                $household_id .
                "&success=Member added successfully"
            );

            exit;

        } else {

            $errors[] = "Failed to add household member.";
            $stmt->close();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Delete Member
|--------------------------------------------------------------------------
*/
if (isset($_GET["delete"])) {

    $member_id = (int)$_GET["delete"];

    if ($member_id > 0) {

        $stmt = $conn->prepare("
            DELETE FROM household_members
            WHERE member_id = ?
            AND household_id = ?
        ");

        $stmt->bind_param(
            "ii",
            $member_id,
            $household_id
        );

        $stmt->execute();
        $stmt->close();

        header(
            "Location: members.php?id=" .
            $household_id .
            "&success=Member removed successfully"
        );

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/
if (isset($_GET["success"])) {
    $success = $_GET["success"];
}

/*
|--------------------------------------------------------------------------
| Load Existing Household Members
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

$members = $members_stmt
    ->get_result();

/*
|--------------------------------------------------------------------------
| Load Available Clients
|--------------------------------------------------------------------------
|
| Clients already belonging to this household are excluded.
|--------------------------------------------------------------------------
*/
$clients_stmt = $conn->prepare("
    SELECT
        c.client_id,
        c.client_number,
        c.full_name,
        c.gender,
        c.phone
    FROM clients c

    WHERE NOT EXISTS (
        SELECT 1
        FROM household_members hm
        WHERE hm.household_id = ?
        AND hm.client_id = c.client_id
    )

    ORDER BY c.full_name ASC
");

$clients_stmt->bind_param(
    "i",
    $household_id
);

$clients_stmt->execute();

$available_clients = $clients_stmt
    ->get_result();

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
        Household Members - Social Welfare
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

        /* HOUSEHOLD HEADER */

        .household-header {
            background: #171717;
            color: #fff;
            border-radius: 8px;
            padding: 22px 25px;
            margin-bottom: 25px;
        }

        .household-header h2 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        .household-meta {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            color: #ccc;
            font-size: 13px;
        }

        /* CARDS */

        .card {
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 25px;
            margin-bottom: 25px;
        }

        .card-title {
            font-size: 19px;
            font-weight: bold;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        /* FORM */

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            outline: none;
            background: #fff;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #555;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .help-text {
            color: #777;
            font-size: 12px;
            margin-top: 6px;
        }

        /* BUTTON */

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 17px;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-primary {
            background: #171717;
            color: #fff;
        }

        .btn-primary:hover {
            background: #000;
        }

        .btn-secondary {
            background: #eee;
            color: #333;
        }

        .btn-secondary:hover {
            background: #ddd;
        }

        .btn-danger {
            background: #e53935;
            color: #fff;
        }

        .btn-danger:hover {
            background: #c62828;
        }

        /* ALERTS */

        .alert {
            padding: 14px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-error {
            background: #ffebee;
            color: #b71c1c;
            border: 1px solid #ffcdd2;
        }

        .alert-error ul {
            margin: 0;
            padding-left: 20px;
        }

        /* TABLE */

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
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
            font-size: 12px;
            text-transform: uppercase;
            color: #555;
        }

        tr:hover td {
            background: #fafafa;
        }

        .client-name {
            font-weight: bold;
            color: #222;
        }

        .client-number {
            color: #777;
            font-size: 12px;
            margin-top: 3px;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            background: #eee;
            color: #555;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            padding: 7px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .view-link {
            background: #e3f2fd;
            color: #1565c0;
        }

        .delete-link {
            background: #ffebee;
            color: #c62828;
        }

        .empty {
            text-align: center;
            padding: 35px 20px;
            color: #777;
        }

        .empty i {
            font-size: 40px;
            margin-bottom: 12px;
            color: #bbb;
        }

        .back-link {
            margin-bottom: 20px;
            display: inline-flex;
        }

        @media (max-width: 800px) {

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


        <a
            href="view.php?id=<?php echo $household_id; ?>"
            class="btn btn-secondary back-link"
        >
            <i class="fas fa-arrow-left"></i>
            Back to Household
        </a>


        <div class="page-header">

            <h1>
                <i class="fas fa-users"></i>
                Household Members
            </h1>

            <p>
                Manage people belonging to this household.
            </p>

        </div>


        <!-- HOUSEHOLD HEADER -->

        <div class="household-header">

            <h2>
                <?php
                echo htmlspecialchars(
                    $household["household_name"]
                );
                ?>
            </h2>

            <div class="household-meta">

                <span>
                    <i class="fas fa-hashtag"></i>
                    <?php
                    echo htmlspecialchars(
                        $household["household_number"]
                    );
                    ?>
                </span>

                <span>
                    <i class="fas fa-users"></i>
                    <?php
                    echo (int)$household["household_size"];
                    ?>
                    registered household size
                </span>

            </div>

        </div>


        <!-- SUCCESS -->

        <?php if ($success !== ""): ?>

            <div class="alert alert-success">

                <i class="fas fa-check-circle"></i>

                <?php
                echo htmlspecialchars($success);
                ?>

            </div>

        <?php endif; ?>


        <!-- ERRORS -->

        <?php if (!empty($errors)): ?>

            <div class="alert alert-error">

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?php
                            echo htmlspecialchars($error);
                            ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <!-- ADD MEMBER -->

        <div class="card">

            <div class="card-title">

                <i class="fas fa-user-plus"></i>

                Add Household Member

            </div>


            <?php if ($available_clients->num_rows > 0): ?>

                <form method="POST">

                    <div class="form-grid">


                        <div class="form-group full">

                            <label>
                                Select Client
                            </label>

                            <select
                                name="client_id"
                                required
                            >

                                <option value="">
                                    -- Select Client --
                                </option>

                                <?php
                                while (
                                    $client =
                                    $available_clients->fetch_assoc()
                                ):
                                ?>

                                    <option
                                        value="<?php echo (int)$client["client_id"]; ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $client["full_name"]
                                        );
                                        ?>

                                        -
                                        <?php
                                        echo htmlspecialchars(
                                            $client["client_number"]
                                        );
                                        ?>

                                        <?php if (!empty($client["gender"])): ?>

                                            (
                                            <?php
                                            echo htmlspecialchars(
                                                $client["gender"]
                                            );
                                            ?>
                                            )

                                        <?php endif; ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>

                            <div class="help-text">
                                Only clients not already linked to this household are shown.
                            </div>

                        </div>


                        <div class="form-group">

                            <label>
                                Relationship to Head
                            </label>

                            <select name="relationship_to_head">

                                <option value="">
                                    -- Select Relationship --
                                </option>

                                <option value="Spouse">
                                    Spouse
                                </option>

                                <option value="Son">
                                    Son
                                </option>

                                <option value="Daughter">
                                    Daughter
                                </option>

                                <option value="Father">
                                    Father
                                </option>

                                <option value="Mother">
                                    Mother
                                </option>

                                <option value="Brother">
                                    Brother
                                </option>

                                <option value="Sister">
                                    Sister
                                </option>

                                <option value="Grandparent">
                                    Grandparent
                                </option>

                                <option value="Grandchild">
                                    Grandchild
                                </option>

                                <option value="Other Relative">
                                    Other Relative
                                </option>

                                <option value="Other">
                                    Other
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Joined Date
                            </label>

                            <input
                                type="date"
                                name="joined_date"
                            >

                        </div>


                        <div class="form-group full">

                            <label>
                                Notes
                            </label>

                            <textarea
                                name="notes"
                                placeholder="Additional information about this household member..."
                            ></textarea>

                        </div>


                    </div>


                    <div style="margin-top:20px;">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i class="fas fa-user-plus"></i>

                            Add Member

                        </button>

                    </div>

                </form>

            <?php else: ?>

                <div class="empty">

                    <i class="fas fa-users"></i>

                    <h3>
                        No Available Clients
                    </h3>

                    <p>
                        All registered clients are already members
                        of this household, or no clients have been registered yet.
                    </p>

                    <a
                        href="../clients/add.php"
                        class="btn btn-primary"
                    >
                        <i class="fas fa-user-plus"></i>
                        Register New Client
                    </a>

                </div>

            <?php endif; ?>

        </div>


        <!-- MEMBERS LIST -->

        <div class="card">

            <div class="card-title">

                <i class="fas fa-users"></i>

                Household Members

                <span style="color:#777;font-size:13px;font-weight:normal;">

                    (
                    <?php echo $members->num_rows; ?>
                    member(s)
                    )

                </span>

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
                                    Actions
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

                                        <div class="client-name">

                                            <?php
                                            echo htmlspecialchars(
                                                $member["full_name"]
                                            );
                                            ?>

                                        </div>

                                        <div class="client-number">

                                            <?php
                                            echo htmlspecialchars(
                                                $member["client_number"]
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $member["gender"] ??
                                            "-"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $member["phone"] ??
                                            "-"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span class="badge">

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
                                                $member["joined_date"]
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
                                            class="action-link view-link"
                                            title="View Client"
                                        >

                                            <i class="fas fa-eye"></i>

                                            View

                                        </a>


                                        <a
                                            href="members.php?id=<?php echo $household_id; ?>&delete=<?php echo (int)$member["member_id"]; ?>"
                                            class="action-link delete-link"
                                            title="Remove Member"
                                            onclick="return confirm('Are you sure you want to remove this client from the household?');"
                                        >

                                            <i class="fas fa-user-minus"></i>

                                            Remove

                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty">

                    <i class="fas fa-user-friends"></i>

                    <h3>
                        No Household Members
                    </h3>

                    <p>
                        No clients have been linked to this household yet.
                    </p>

                </div>

            <?php endif; ?>

        </div>


    </div>

</div>

</body>

</html>