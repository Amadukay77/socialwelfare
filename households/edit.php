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
    SELECT *
    FROM households
    WHERE household_id = ?
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
| Default Form Values
|--------------------------------------------------------------------------
*/
$household_number = $household["household_number"];
$household_name   = $household["household_name"];
$address          = $household["address"];
$chiefdom         = $household["chiefdom"];
$section          = $household["section"];
$phone            = $household["phone"];
$household_size   = $household["household_size"];
$head_client_id   = $household["head_client_id"];
$status           = $household["status"];
$notes            = $household["notes"];

$errors = [];

/*
|--------------------------------------------------------------------------
| Update Household
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $household_number = trim($_POST["household_number"] ?? "");
    $household_name   = trim($_POST["household_name"] ?? "");
    $address          = trim($_POST["address"] ?? "");
    $chiefdom         = trim($_POST["chiefdom"] ?? "");
    $section          = trim($_POST["section"] ?? "");
    $phone            = trim($_POST["phone"] ?? "");
    $household_size   = (int)($_POST["household_size"] ?? 0);

    $head_client_raw = $_POST["head_client_id"] ?? "";
    $head_client_id  = ($head_client_raw !== "") ? (int)$head_client_raw : null;

    $status = $_POST["status"] ?? "Active";
    $notes  = trim($_POST["notes"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($household_number === "") {
        $errors[] = "Household number is required.";
    }

    if ($household_name === "") {
        $errors[] = "Household name is required.";
    }

    if ($household_size < 0) {
        $errors[] = "Household size cannot be negative.";
    }

    if (!in_array($status, ["Active", "Inactive"], true)) {
        $errors[] = "Invalid household status.";
    }

    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Household Number
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $check = $conn->prepare("
            SELECT household_id
            FROM households
            WHERE household_number = ?
            AND household_id <> ?
            LIMIT 1
        ");

        $check->bind_param(
            "si",
            $household_number,
            $household_id
        );

        $check->execute();
        $duplicate = $check->get_result()->fetch_assoc();
        $check->close();

        if ($duplicate) {
            $errors[] = "This household number already exists.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Database
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        if ($head_client_id === null) {

            $stmt = $conn->prepare("
                UPDATE households
                SET
                    household_number = ?,
                    household_name = ?,
                    address = ?,
                    chiefdom = ?,
                    section = ?,
                    phone = ?,
                    household_size = ?,
                    head_client_id = NULL,
                    status = ?,
                    notes = ?
                WHERE household_id = ?
            ");

            $stmt->bind_param(
                "ssssssissi",
                $household_number,
                $household_name,
                $address,
                $chiefdom,
                $section,
                $phone,
                $household_size,
                $status,
                $notes,
                $household_id
            );

        } else {

            $stmt = $conn->prepare("
                UPDATE households
                SET
                    household_number = ?,
                    household_name = ?,
                    address = ?,
                    chiefdom = ?,
                    section = ?,
                    phone = ?,
                    household_size = ?,
                    head_client_id = ?,
                    status = ?,
                    notes = ?
                WHERE household_id = ?
            ");

            $stmt->bind_param(
                "ssssssiissi",
                $household_number,
                $household_name,
                $address,
                $chiefdom,
                $section,
                $phone,
                $household_size,
                $head_client_id,
                $status,
                $notes,
                $household_id
            );
        }

        if ($stmt->execute()) {

            $stmt->close();

            header("Location: view.php?id=" . $household_id);
            exit;

        } else {

            $errors[] = "Failed to update household. Please try again.";
            $stmt->close();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Load Clients for Head of Household Dropdown
|--------------------------------------------------------------------------
*/

$clients = $conn->query("
    SELECT
        client_id,
        client_number,
        full_name,
        gender
    FROM clients
    ORDER BY full_name ASC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Household - Social Welfare</title>

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
            max-width: 1100px;
            margin: auto;
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

        .card {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e1e1e1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 25px;
        }

        .card-title {
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 22px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

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

        label {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 7px;
            color: #333;
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
            font-size: 14px;
            background: #fff;
            color: #222;
            outline: none;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #555;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .help-text {
            margin-top: 6px;
            color: #777;
            font-size: 12px;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
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

        .buttons {
            margin-top: 25px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
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

        .current-number {
            background: #f7f7f7;
            border: 1px solid #ddd;
            padding: 10px 12px;
            border-radius: 5px;
            margin-top: 7px;
            color: #555;
            font-size: 13px;
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

            .topbar {
                padding: 0 15px;
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
        <i class="fas fa-calendar-check icon-appointments"></i>
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


<!-- MAIN -->

<div class="main">

    <div class="topbar">

        <div class="user-info">

            <div class="user-icon">
                <i class="fas fa-user"></i>
            </div>

            <span>
                <?php echo htmlspecialchars($_SESSION["full_name"] ?? "System Administrator"); ?>
            </span>

        </div>

    </div>


    <div class="content">

        <div class="page-header">

            <h1>
                <i class="fas fa-edit"></i>
                Edit Household
            </h1>

            <p>
                Update household information and head of household details.
            </p>

        </div>


        <?php if (!empty($errors)): ?>

            <div class="alert alert-error">

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?php echo htmlspecialchars($error); ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <div class="card">

            <div class="card-title">
                <i class="fas fa-home"></i>
                Household Information
            </div>


            <form method="POST">


                <div class="form-grid">


                    <!-- HOUSEHOLD NUMBER -->

                    <div class="form-group">

                        <label>
                            Household Number
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="household_number"
                            value="<?php echo htmlspecialchars($household_number); ?>"
                            required
                        >

                        <div class="help-text">
                            Unique identification number for this household.
                        </div>

                    </div>


                    <!-- HOUSEHOLD NAME -->

                    <div class="form-group">

                        <label>
                            Household Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="household_name"
                            value="<?php echo htmlspecialchars($household_name); ?>"
                            required
                        >

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group full">

                        <label>
                            Address
                        </label>

                        <input
                            type="text"
                            name="address"
                            value="<?php echo htmlspecialchars($address); ?>"
                            placeholder="Enter household address"
                        >

                    </div>


                    <!-- CHIEFDOM -->

                    <div class="form-group">

                        <label>
                            Chiefdom
                        </label>

                        <input
                            type="text"
                            name="chiefdom"
                            value="<?php echo htmlspecialchars($chiefdom); ?>"
                            placeholder="Enter chiefdom"
                        >

                    </div>


                    <!-- SECTION -->

                    <div class="form-group">

                        <label>
                            Section
                        </label>

                        <input
                            type="text"
                            name="section"
                            value="<?php echo htmlspecialchars($section); ?>"
                            placeholder="Enter section"
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label>
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            value="<?php echo htmlspecialchars($phone); ?>"
                            placeholder="Enter phone number"
                        >

                    </div>


                    <!-- HOUSEHOLD SIZE -->

                    <div class="form-group">

                        <label>
                            Household Size
                        </label>

                        <input
                            type="number"
                            name="household_size"
                            min="0"
                            value="<?php echo (int)$household_size; ?>"
                        >

                        <div class="help-text">
                            Number of people currently in the household.
                        </div>

                    </div>


                    <!-- HEAD OF HOUSEHOLD -->

                    <div class="form-group full">

                        <label>
                            Head of Household
                        </label>

                        <select name="head_client_id">

                            <option value="">
                                -- Select Head of Household --
                            </option>

                            <?php if ($clients && $clients->num_rows > 0): ?>

                                <?php while ($client = $clients->fetch_assoc()): ?>

                                    <option
                                        value="<?php echo (int)$client["client_id"]; ?>"
                                        <?php
                                        if (
                                            $head_client_id !== null &&
                                            (int)$head_client_id === (int)$client["client_id"]
                                        ) {
                                            echo "selected";
                                        }
                                        ?>
                                    >

                                        <?php echo htmlspecialchars($client["full_name"]); ?>

                                        -
                                        <?php echo htmlspecialchars($client["client_number"]); ?>

                                        <?php if (!empty($client["gender"])): ?>

                                            (<?php echo htmlspecialchars($client["gender"]); ?>)

                                        <?php endif; ?>

                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                        <div class="help-text">
                            Select an existing client as the head of this household.
                        </div>

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label>
                            Status
                        </label>

                        <select name="status">

                            <option
                                value="Active"
                                <?php echo ($status === "Active") ? "selected" : ""; ?>
                            >
                                Active
                            </option>

                            <option
                                value="Inactive"
                                <?php echo ($status === "Inactive") ? "selected" : ""; ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>


                    <!-- NOTES -->

                    <div class="form-group full">

                        <label>
                            Notes
                        </label>

                        <textarea
                            name="notes"
                            placeholder="Enter any additional household information..."
                        ><?php echo htmlspecialchars($notes); ?></textarea>

                    </div>


                </div>


                <!-- BUTTONS -->

                <div class="buttons">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>


                    <a
                        href="view.php?id=<?php echo $household_id; ?>"
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