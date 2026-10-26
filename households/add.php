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

$error = "";

$household_number = "";
$household_name = "";
$address = "";
$chiefdom = "";
$section = "";
$phone = "";
$household_size = 0;
$head_client_id = "";
$status = "Active";
$notes = "";

/*
|--------------------------------------------------------------------------
| Generate household number
|--------------------------------------------------------------------------
*/
$household_number = "HH-" . date("YmdHis");

/*
|--------------------------------------------------------------------------
| Get clients for Head of Household dropdown
|--------------------------------------------------------------------------
*/
$clients = $conn->query("
    SELECT client_id, client_number, full_name, gender, phone
    FROM clients
    ORDER BY full_name ASC
");

/*
|--------------------------------------------------------------------------
| Save household
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $household_number = trim($_POST["household_number"] ?? "");
    $household_name = trim($_POST["household_name"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $chiefdom = trim($_POST["chiefdom"] ?? "");
    $section = trim($_POST["section"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $household_size = (int)($_POST["household_size"] ?? 0);
    $head_client_id = !empty($_POST["head_client_id"])
        ? (int)$_POST["head_client_id"]
        : null;
    $status = $_POST["status"] ?? "Active";
    $notes = trim($_POST["notes"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($household_number === "") {
        $error = "Please enter a household number.";
    } elseif ($household_name === "") {
        $error = "Please enter the household name.";
    } elseif ($household_size < 1) {
        $error = "Household size must be at least 1.";
    } elseif (!in_array($status, ["Active", "Inactive"], true)) {
        $error = "Invalid household status.";
    } else {

        /*
        |--------------------------------------------------------------------------
        | Check duplicate household number
        |--------------------------------------------------------------------------
        */

        $check = $conn->prepare("
            SELECT household_id
            FROM households
            WHERE household_number = ?
            LIMIT 1
        ");

        $check->bind_param("s", $household_number);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {

            $error = "This household number already exists.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Insert household
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                INSERT INTO households
                (
                    household_number,
                    household_name,
                    address,
                    chiefdom,
                    section,
                    phone,
                    household_size,
                    head_client_id,
                    status,
                    notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

           $stmt->bind_param(
    "ssssssiiss",
    $household_number,
    $household_name,
    $address,
    $chiefdom,
    $section,
    $phone,
    $household_size,
    $head_client_id,
    $status,
    $notes
);

            /*
            |--------------------------------------------------------------------------
            | Correct bind types
            |--------------------------------------------------------------------------
            */

            if ($stmt->execute()) {

                header("Location: index.php?success=Household registered successfully");
                exit;

            } else {

                $error = "Unable to save household. Please try again.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Reload clients if form submission caused an error
|--------------------------------------------------------------------------
*/
$clients = $conn->query("
    SELECT client_id, client_number, full_name, gender, phone
    FROM clients
    ORDER BY full_name ASC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register Household - Social Welfare</title>

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
            max-width: 1200px;
        }

        /* PAGE HEADER */

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h2 {
            font-size: 26px;
            color: #222;
            margin-bottom: 6px;
        }

        .page-header p {
            color: #777;
            font-size: 14px;
        }

        /* ALERT */

        .alert {
            padding: 14px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        /* FORM CARD */

        .form-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .form-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            background: #fafafa;
        }

        .form-header h3 {
            font-size: 17px;
            color: #222;
        }

        .form-header p {
            margin-top: 5px;
            color: #777;
            font-size: 13px;
        }

        .form-body {
            padding: 25px;
        }

        .section-title {
            font-size: 15px;
            color: #222;
            font-weight: bold;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .section-title i {
            color: #e53935;
            margin-right: 7px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            font-weight: bold;
            color: #444;
            margin-bottom: 7px;
        }

        label span {
            color: #e53935;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 11px 12px;
            font-size: 14px;
            font-family: Arial, Helvetica, sans-serif;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #e53935;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .help-text {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }

        /* BUTTONS */

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            border: none;
            border-radius: 5px;
            padding: 12px 20px;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .save-btn {
            background: #e53935;
            color: white;
        }

        .save-btn:hover {
            background: #c62828;
        }

        .cancel-btn {
            background: #eee;
            color: #333;
        }

        .cancel-btn:hover {
            background: #ddd;
        }

        /* RESPONSIVE */

        @media (max-width: 800px) {

            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 650px) {

            .layout {
                display: block;
            }

            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .content {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
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

            <h1>Register Household</h1>

            <div class="user-info">

                <div class="user-icon">
                    <i class="fas fa-user"></i>
                </div>

                <span>
                    <?php
                    echo e(
                        $_SESSION["full_name"]
                        ?? $_SESSION["username"]
                        ?? "User"
                    );
                    ?>
                </span>

            </div>

        </div>


        <div class="content">

            <div class="page-header">

                <h2>Register New Household</h2>

                <p>
                    Enter the household information below.
                </p>

            </div>


            <?php if ($error !== ""): ?>

                <div class="alert alert-error">

                    <i class="fas fa-exclamation-circle"></i>

                    <?php echo e($error); ?>

                </div>

            <?php endif; ?>


            <div class="form-card">

                <div class="form-header">

                    <h3>
                        <i class="fas fa-home"></i>
                        Household Information
                    </h3>

                    <p>
                        Fields marked with
                        <strong>*</strong>
                        are required.
                    </p>

                </div>


                <div class="form-body">

                    <form method="POST">


                        <!-- BASIC INFORMATION -->

                        <div class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </div>


                        <div class="form-grid">

                            <div class="form-group">

                                <label>
                                    Household Number
                                    <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    name="household_number"
                                    value="<?php echo e($household_number); ?>"
                                    required
                                >

                                <div class="help-text">
                                    Example: HH-20261004045514
                                </div>

                            </div>


                            <div class="form-group">

                                <label>
                                    Household Name
                                    <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    name="household_name"
                                    value="<?php echo e($household_name); ?>"
                                    placeholder="Enter household name"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Household Size
                                    <span>*</span>
                                </label>

                                <input
                                    type="number"
                                    name="household_size"
                                    value="<?php echo e($household_size); ?>"
                                    min="1"
                                    required
                                >

                                <div class="help-text">
                                    Total number of people in the household.
                                </div>

                            </div>


                            <div class="form-group">

                                <label>
                                    Status
                                    <span>*</span>
                                </label>

                                <select name="status" required>

                                    <option
                                        value="Active"
                                        <?php echo $status === "Active" ? "selected" : ""; ?>
                                    >
                                        Active
                                    </option>

                                    <option
                                        value="Inactive"
                                        <?php echo $status === "Inactive" ? "selected" : ""; ?>
                                    >
                                        Inactive
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- LOCATION -->

                        <div class="section-title">

                            <i class="fas fa-map-marker-alt"></i>

                            Location and Contact

                        </div>


                        <div class="form-grid">

                            <div class="form-group full">

                                <label>
                                    Address
                                </label>

                                <input
                                    type="text"
                                    name="address"
                                    value="<?php echo e($address); ?>"
                                    placeholder="Enter household address"
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Chiefdom
                                </label>

                                <input
                                    type="text"
                                    name="chiefdom"
                                    value="<?php echo e($chiefdom); ?>"
                                    placeholder="Enter chiefdom"
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Section
                                </label>

                                <input
                                    type="text"
                                    name="section"
                                    value="<?php echo e($section); ?>"
                                    placeholder="Enter section"
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Phone
                                </label>

                                <input
                                    type="text"
                                    name="phone"
                                    value="<?php echo e($phone); ?>"
                                    placeholder="Enter household phone number"
                                >

                            </div>

                        </div>


                        <!-- HEAD OF HOUSEHOLD -->

                        <div class="section-title">

                            <i class="fas fa-user"></i>

                            Head of Household

                        </div>


                        <div class="form-grid">

                            <div class="form-group full">

                                <label>
                                    Select Head of Household
                                </label>

                                <select name="head_client_id">

                                    <option value="">
                                        -- Select Client --
                                    </option>

                                    <?php if ($clients && $clients->num_rows > 0): ?>

                                        <?php while ($client = $clients->fetch_assoc()): ?>

                                            <option
                                                value="<?php echo $client["client_id"]; ?>"
                                                <?php
                                                echo (
                                                    (string)$head_client_id ===
                                                    (string)$client["client_id"]
                                                )
                                                ? "selected"
                                                : "";
                                                ?>
                                            >

                                                <?php
                                                echo e(
                                                    $client["full_name"]
                                                    . " — "
                                                    . $client["client_number"]
                                                );
                                                ?>

                                                <?php if (!empty($client["gender"])): ?>

                                                    <?php
                                                    echo " (" . e($client["gender"]) . ")";
                                                    ?>

                                                <?php endif; ?>

                                            </option>

                                        <?php endwhile; ?>

                                    <?php else: ?>

                                        <option value="" disabled>
                                            No clients registered yet
                                        </option>

                                    <?php endif; ?>

                                </select>

                                <div class="help-text">
                                    The head must already be registered as a client.
                                </div>

                            </div>

                        </div>


                        <!-- NOTES -->

                        <div class="section-title">

                            <i class="fas fa-sticky-note"></i>

                            Additional Information

                        </div>


                        <div class="form-grid">

                            <div class="form-group full">

                                <label>
                                    Notes
                                </label>

                                <textarea
                                    name="notes"
                                    placeholder="Enter any additional household information..."
                                ><?php echo e($notes); ?></textarea>

                            </div>

                        </div>


                        <!-- ACTIONS -->

                        <div class="form-actions">

                            <a
                                href="index.php"
                                class="btn cancel-btn"
                            >
                                <i class="fas fa-arrow-left"></i>
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="btn save-btn"
                            >
                                <i class="fas fa-save"></i>
                                Save Household
                            </button>

                        </div>


                    </form>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>