 
<?php
session_start();

require_once "../includes/db.php";
require_once "../includes/permissions.php";

// Only users with permission can edit client records
require_permission("clients_edit");

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$client_id = (int) $_GET["id"];

/* Get client information */
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$client = $result->fetch_assoc();

/* Update client */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $client_number = trim($_POST["client_number"]);
    $full_name = trim($_POST["full_name"]);
    $gender = $_POST["gender"];

    $date_of_birth = !empty($_POST["date_of_birth"])
        ? $_POST["date_of_birth"]
        : null;

    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);
    $chiefdom = trim($_POST["chiefdom"]);
    $section = trim($_POST["section"]);
    $disability_status = trim($_POST["disability_status"]);
    $marital_status = trim($_POST["marital_status"]);
    $occupation = trim($_POST["occupation"]);

    if ($client_number === "" || $full_name === "" || $gender === "") {

        $error = "Client number, full name and gender are required.";

    } else {

        /* Check if another client already uses this client number */
        $check = $conn->prepare("
            SELECT client_id 
            FROM clients 
            WHERE client_number = ? 
            AND client_id != ?
        ");

        $check->bind_param("si", $client_number, $client_id);
        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {

            $error = "This client number is already being used by another client.";

        } else {

            $stmt = $conn->prepare("
                UPDATE clients SET
                    client_number = ?,
                    full_name = ?,
                    gender = ?,
                    date_of_birth = ?,
                    phone = ?,
                    address = ?,
                    chiefdom = ?,
                    section = ?,
                    disability_status = ?,
                    marital_status = ?,
                    occupation = ?
                WHERE client_id = ?
            ");

            $stmt->bind_param(
                "sssssssssssi",
                $client_number,
                $full_name,
                $gender,
                $date_of_birth,
                $phone,
                $address,
                $chiefdom,
                $section,
                $disability_status,
                $marital_status,
                $occupation,
                $client_id
            );

            if ($stmt->execute()) {

                header("Location: view.php?id=" . $client_id . "&updated=1");
                exit;

            } else {

                $error = "Failed to update client.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Client - Social Welfare System</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
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
            color: white;
            padding-top: 20px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 20px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #ddd;
            text-decoration: none;
            padding: 14px 20px;
            font-size: 15px;
            transition: 0.3s;
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

        .sidebar i {
            width: 22px;
            text-align: center;
        }

        /* Different icon colors */

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

        /* TOP BAR */

        .topbar {
            margin-left: 250px;
            height: 70px;
            background: white;
            display: flex;
            align-items: center;
            padding: 0 30px;
            border-bottom: 1px solid #ddd;
        }

        .topbar h1 {
            margin: 0;
            font-size: 22px;
        }

        /* MAIN */

        .main {
            margin-left: 250px;
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0;
        }

        .back-btn {
            text-decoration: none;
            background: #171717;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
        }

        /* FORM */

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            max-width: 1000px;
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
            grid-column: span 2;
        }

        label {
            font-weight: bold;
            margin-bottom: 8px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #171717;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .buttons {
            margin-top: 25px;
            display: flex;
            gap: 12px;
        }

        .save-btn {
            background: #171717;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .save-btn:hover {
            background: #333;
        }

        .cancel-btn {
            background: #e0e0e0;
            color: #222;
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 6px;
        }

        @media (max-width: 800px) {

            .sidebar {
                width: 200px;
            }

            .topbar,
            .main {
                margin-left: 200px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: span 1;
            }
        }

    </style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <h2>
        <i class="fas fa-hands-helping"></i>
        Social Welfare
    </h2>

    <a href="../index.php">
        <i class="fas fa-tachometer-alt icon-dashboard"></i>
        Dashboard
    </a>

    <a href="index.php" class="active">
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


<!-- TOP BAR -->

<div class="topbar">

    <h1>
        <i class="fas fa-user-edit"></i>
        Edit Client
    </h1>

</div>


<!-- MAIN -->

<div class="main">

    <div class="page-header">

        <h2>Edit Client Information</h2>

        <a href="view.php?id=<?php echo $client_id; ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Profile
        </a>

    </div>


    <div class="form-container">

        <?php if (isset($error)): ?>

            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-grid">

                <!-- CLIENT NUMBER -->

                <div class="form-group">

                    <label>
                        <i class="fas fa-id-card"></i>
                        Client Number
                    </label>

                    <input
                        type="text"
                        name="client_number"
                        value="<?php echo htmlspecialchars($client["client_number"]); ?>"
                        required>

                </div>


                <!-- FULL NAME -->

                <div class="form-group">

                    <label>
                        <i class="fas fa-user"></i>
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        value="<?php echo htmlspecialchars($client["full_name"]); ?>"
                        required>

                </div>


                <!-- GENDER -->

                <div class="form-group">

                    <label>
                        <i class="fas fa-venus-mars"></i>
                        Gender
                    </label>

                    <select name="gender" required>

                        <option value="Male"
                            <?php echo ($client["gender"] == "Male") ? "selected" : ""; ?>>
                            Male
                        </option>

                        <option value="Female"
                            <?php echo ($client["gender"] == "Female") ? "selected" : ""; ?>>
                            Female
                        </option>

                        <option value="Other"
                            <?php echo ($client["gender"] == "Other") ? "selected" : ""; ?>>
                            Other
                        </option>

                    </select>

                </div>


                <!-- DATE OF BIRTH -->

                <div class="form-group">

                    <label>
                        <i class="fas fa-birthday-cake"></i>
                        Date of Birth
                    </label>

                    <input
                        type="date"
                        name="date_of_birth"
                        value="<?php echo htmlspecialchars($client["date_of_birth"] ?? ""); ?>">

                </div>


                <!-- PHONE -->

                <div class="form-group">

                    <label>
                        <i class="fas fa-phone"></i>
                        Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="<?php echo htmlspecialchars($client["phone"] ?? ""); ?>">

                </div>


                <!-- CHIEFDOM -->

                <div class="form-group">

                    <label>
                        <i class="fas fa-map-marker-alt"></i>
                        Chiefdom
                    </label>

                    <input
                        type="text"
                        name="chiefdom"
                        value="<?php echo htmlspecialchars($client["chiefdom"] ?? ""); ?>">

                </div>


                <!-- SECTION -->

                <div class="form-group">

                    <label>
                        <i class="fas fa-map"></i>
                        Section
                    </label>

                    <input
                        type="text"
                        name="section"
                        value="<?php echo htmlspecialchars($client["section"] ?? ""); ?>">

                </div>


                <!-- DISABILITY -->

                <div class="form-group">

                    <label>
                        <i class="fas fa-wheelchair"></i>
                        Disability Status
                    </label>

                    <input
                        type="text"
                        name="disability_status"
                        value="<?php echo htmlspecialchars($client["disability_status"] ?? ""); ?>">

                </div>


                <!-- MARITAL STATUS -->

                <div class="form-group">

                    <label>
                        <i class="fas fa-ring"></i>
                        Marital Status
                    </label>

                    <input
                        type="text"
                        name="marital_status"
                        value="<?php echo htmlspecialchars($client["marital_status"] ?? ""); ?>">

                </div>


                <!-- OCCUPATION -->

                <div class="form-group">

                    <label>
                        <i class="fas fa-briefcase"></i>
                        Occupation
                    </label>

                    <input
                        type="text"
                        name="occupation"
                        value="<?php echo htmlspecialchars($client["occupation"] ?? ""); ?>">

                </div>


                <!-- ADDRESS -->

                <div class="form-group full">

                    <label>
                        <i class="fas fa-home"></i>
                        Address
                    </label>

                    <textarea name="address"><?php echo htmlspecialchars($client["address"] ?? ""); ?></textarea>

                </div>

            </div>


            <!-- BUTTONS -->

            <div class="buttons">

                <button type="submit" class="save-btn">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>

                <a
                    href="view.php?id=<?php echo $client_id; ?>"
                    class="cancel-btn">

                    <i class="fas fa-times"></i>
                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>