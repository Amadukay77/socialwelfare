
<?php
session_start();

require_once "../includes/db.php";
require_once "../includes/permissions.php";

// Only users with permission can add clients
require_permission("clients_add");

$success = "";
$error = "";

/* =========================
   SAVE CLIENT
========================= */

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


    // Basic validation

    if (
        $client_number === "" ||
        $full_name === "" ||
        $gender === ""
    ) {

        $error = "Please fill in all required fields.";

    } else {

        // Check duplicate client number

        $check = $conn->prepare("
            SELECT client_id
            FROM clients
            WHERE client_number = ?
        ");

        $check->bind_param(
            "s",
            $client_number
        );

        $check->execute();

        $check_result = $check->get_result();


        if ($check_result->num_rows > 0) {

            $error = "This client number already exists.";

        } else {

            // Insert client

            $stmt = $conn->prepare("
                INSERT INTO clients
                (
                    client_number,
                    full_name,
                    gender,
                    date_of_birth,
                    phone,
                    address,
                    chiefdom,
                    section,
                    disability_status,
                    marital_status,
                    occupation
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssssssssss",
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
                $occupation
            );


            if ($stmt->execute()) {

                header(
                    "Location: index.php?success=1"
                );

                exit;

            } else {

                $error =
                    "Unable to register client. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Register Client - Social Welfare System</title>

<!-- FONT AWESOME -->

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


<style>

/* =========================
   GENERAL
========================= */

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

    width: 235px;

    height: 100vh;

    background: #171717;

    color: white;

    overflow-y: auto;

    box-shadow:
        3px 0 12px rgba(0,0,0,0.15);
}


.logo {

    text-align: center;

    padding:
        25px 15px 22px;

    border-bottom:
        1px solid #333;
}

.logo-circle {

    width: 58px;
    height: 58px;

    margin: auto;

    border-radius: 50%;

    background: white;

    color: #222;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

    font-weight: bold;
}

.logo h2 {

    margin:
        12px 0 3px;

    font-size: 18px;
}

.logo p {

    margin: 0;

    font-size: 11px;

    color: #aaa;
}


.menu-title {

    padding:
        20px 18px 8px;

    font-size: 10px;

    color: #888;

    text-transform: uppercase;

    letter-spacing: 1px;
}


.sidebar a {

    display: flex;

    align-items: center;

    gap: 13px;

    padding:
        12px 18px;

    color: #ddd;

    text-decoration: none;

    font-size: 13px;

    transition: 0.2s;
}

.sidebar a i {

    width: 20px;

    text-align: center;

    font-size: 15px;
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

    padding-left: 14px;
}


.logout-link {

    margin-top: 15px;

    border-top:
        1px solid #333;
}


/* =========================
   MAIN
========================= */

.main {

    margin-left: 235px;

    min-height: 100vh;
}


/* =========================
   TOP BAR
========================= */

.topbar {

    height: 65px;

    background: white;

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    padding:
        0 25px;

    border-bottom:
        1px solid #e5e5e5;
}

.topbar h1 {

    margin: 0;

    font-size: 21px;

    color: #333;
}

.topbar h1 i {

    color: #e53935;

    margin-right: 8px;
}


.user-info {

    display: flex;

    align-items: center;

    gap: 9px;
}

.user-circle {

    width: 38px;
    height: 38px;

    border-radius: 50%;

    background: #333;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;
}

.user-text {

    font-size: 12px;
}

.user-text strong {

    display: block;

    color: #333;
}

.user-text span {

    color: #888;
}


/* =========================
   CONTENT
========================= */

.content {

    padding: 25px;

    max-width: 1100px;
}


/* =========================
   PAGE HEADER
========================= */

.page-header {

    margin-bottom: 20px;
}

.page-header h2 {

    margin: 0;

    font-size: 22px;

    color: #333;
}

.page-header h2 i {

    color: #e53935;

    margin-right: 8px;
}

.page-header p {

    margin:
        6px 0 0;

    color: #888;

    font-size: 13px;
}


/* BACK BUTTON */

.back-btn {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    margin-top: 12px;

    color: #555;

    text-decoration: none;

    font-size: 13px;
}

.back-btn:hover {

    color: #e53935;
}


/* =========================
   FORM PANEL
========================= */

.form-panel {

    background: white;

    border-radius: 8px;

    border:
        1px solid #e5e5e5;

    box-shadow:
        0 3px 10px rgba(0,0,0,0.06);

    overflow: hidden;
}


.form-title {

    padding:
        17px 20px;

    border-bottom:
        1px solid #eee;

    display: flex;

    align-items: center;

    gap: 10px;

    font-size: 16px;

    font-weight: bold;

    color: #333;
}

.form-title i {

    color: #1e88e5;
}


/* =========================
   ALERTS
========================= */

.alert {

    margin:
        20px;

    padding:
        12px 15px;

    border-radius: 6px;

    font-size: 13px;
}

.alert-error {

    background: #fdecec;

    border:
        1px solid #f5c2c2;

    color: #b71c1c;
}


/* =========================
   FORM
========================= */

.form-body {

    padding: 25px;
}


.section-title {

    font-size: 14px;

    font-weight: bold;

    color: #333;

    padding-bottom: 9px;

    margin-bottom: 18px;

    border-bottom:
        1px solid #eee;
}

.section-title i {

    color: #e53935;

    margin-right: 7px;
}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap:
        18px 20px;

    margin-bottom: 25px;
}


.form-group {

    display: flex;

    flex-direction: column;
}

.form-group.full {

    grid-column:
        1 / -1;
}


.form-group label {

    margin-bottom: 7px;

    font-size: 12px;

    font-weight: bold;

    color: #555;
}

.required {

    color: #e53935;
}


.form-control {

    width: 100%;

    padding:
        11px 12px;

    border:
        1px solid #dcdcdc;

    border-radius: 6px;

    font-size: 13px;

    font-family: inherit;

    outline: none;

    background: white;
}

.form-control:focus {

    border-color: #1e88e5;

    box-shadow:
        0 0 0 2px rgba(30,136,229,0.08);
}


textarea.form-control {

    min-height: 85px;

    resize: vertical;
}


/* =========================
   FORM FOOTER
========================= */

.form-footer {

    padding:
        18px 25px;

    background: #fafafa;

    border-top:
        1px solid #eee;

    display: flex;

    justify-content:
        flex-end;

    gap: 10px;
}


.cancel-btn {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding:
        11px 17px;

    border-radius: 6px;

    background: #eee;

    color: #555;

    text-decoration: none;

    font-size: 13px;
}

.cancel-btn:hover {

    background: #ddd;
}


.save-btn {

    border: none;

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding:
        11px 18px;

    border-radius: 6px;

    background: #e53935;

    color: white;

    font-size: 13px;

    font-weight: bold;

    cursor: pointer;
}

.save-btn:hover {

    background: #c62828;
}


/* =========================
   MOBILE
========================= */

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
}


@media (max-width: 650px) {

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

    .topbar {

        padding:
            0 15px;
    }

    .user-text {

        display: none;
    }

    .form-body {

        padding: 18px;
    }

    .form-footer {

        padding:
            15px 18px;

        flex-direction: column;
    }

    .cancel-btn,
    .save-btn {

        justify-content: center;
    }
}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">


    <div class="logo">

        <div class="logo-circle">
            SW
        </div>

        <h2>
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

        <i class="fas fa-tachometer-alt"></i>

        Dashboard

    </a>


    <a href="index.php"
       class="active">

        <i class="fas fa-users"></i>

        Clients

    </a>


    <a href="../cases/">

        <i class="fas fa-folder-open"></i>

        Cases

    </a>


    <a href="../documents/">

        <i class="fas fa-file-alt"></i>

        Documents

    </a>


    <a href="../social_workers/">

        <i class="fas fa-user-tie"></i>

        Social Workers

    </a>


    <a href="../households/">

        <i class="fas fa-home"></i>

        Households

    </a>


    <a href="../activities/">

        <i class="fas fa-tasks"></i>

        Activities

    </a>


    <a href="../appointments/">

        <i class="fas fa-calendar-alt"></i>

        Appointments

    </a>


    <div class="menu-title">
        Management
    </div>


    <a href="../reports/">

        <i class="fas fa-chart-bar"></i>

        Reports

    </a>


    <a href="../settings/">

        <i class="fas fa-cog"></i>

        Settings

    </a>


    <a href="../auth/logout.php"
       class="logout-link">

        <i class="fas fa-sign-out-alt"></i>

        Logout

    </a>

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <h1>

            <i class="fas fa-user-plus"></i>

            Register Client

        </h1>


        <div class="user-info">

            <div class="user-circle">

                <?php

                echo strtoupper(
                    substr(
                        $_SESSION["full_name"],
                        0,
                        1
                    )
                );

                ?>

            </div>


            <div class="user-text">

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $_SESSION["full_name"]
                    );

                    ?>

                </strong>


                <span>

                    <?php

                    echo htmlspecialchars(
                        $_SESSION["role"]
                    );

                    ?>

                </span>

            </div>

        </div>

    </div>


    <!-- =========================
         CONTENT
    ========================= -->

    <div class="content">


        <div class="page-header">

            <h2>

                <i class="fas fa-user-plus"></i>

                Register New Client

            </h2>

            <p>
                Enter the beneficiary's information
                below to create a new client record.
            </p>


            <a href="index.php"
               class="back-btn">

                <i class="fas fa-arrow-left"></i>

                Back to Clients

            </a>

        </div>


        <!-- FORM -->

        <div class="form-panel">


            <div class="form-title">

                <i class="fas fa-id-card"></i>

                Client Information

            </div>


            <?php if ($error !== ""): ?>

                <div class="alert alert-error">

                    <i class="fas fa-exclamation-circle"></i>

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <form method="POST">


                <div class="form-body">


                    <!-- BASIC INFORMATION -->

                    <div class="section-title">

                        <i class="fas fa-user"></i>

                        Basic Information

                    </div>


                    <div class="form-grid">


                        <!-- CLIENT NUMBER -->

                        <div class="form-group">

                            <label>

                                Client Number
                                <span class="required">*</span>

                            </label>

                            <input
                                type="text"
                                name="client_number"
                                class="form-control"
                                placeholder="Example: SW-0001"
                                value="<?php echo isset($_POST["client_number"]) ? htmlspecialchars($_POST["client_number"]) : ""; ?>"
                                required
                            >

                        </div>


                        <!-- FULL NAME -->

                        <div class="form-group">

                            <label>

                                Full Name
                                <span class="required">*</span>

                            </label>

                            <input
                                type="text"
                                name="full_name"
                                class="form-control"
                                placeholder="Enter full name"
                                value="<?php echo isset($_POST["full_name"]) ? htmlspecialchars($_POST["full_name"]) : ""; ?>"
                                required
                            >

                        </div>


                        <!-- GENDER -->

                        <div class="form-group">

                            <label>

                                Gender
                                <span class="required">*</span>

                            </label>

                            <select
                                name="gender"
                                class="form-control"
                                required
                            >

                                <option value="">
                                    Select Gender
                                </option>

                                <option value="Male"
                                    <?php
                                    if (
                                        isset($_POST["gender"]) &&
                                        $_POST["gender"] === "Male"
                                    ) echo "selected";
                                    ?>
                                >
                                    Male
                                </option>

                                <option value="Female"
                                    <?php
                                    if (
                                        isset($_POST["gender"]) &&
                                        $_POST["gender"] === "Female"
                                    ) echo "selected";
                                    ?>
                                >
                                    Female
                                </option>

                                <option value="Other"
                                    <?php
                                    if (
                                        isset($_POST["gender"]) &&
                                        $_POST["gender"] === "Other"
                                    ) echo "selected";
                                    ?>
                                >
                                    Other
                                </option>

                            </select>

                        </div>


                        <!-- DATE OF BIRTH -->

                        <div class="form-group">

                            <label>
                                Date of Birth
                            </label>

                            <input
                                type="date"
                                name="date_of_birth"
                                class="form-control"
                                value="<?php echo isset($_POST["date_of_birth"]) ? htmlspecialchars($_POST["date_of_birth"]) : ""; ?>"
                            >

                        </div>


                        <!-- PHONE -->

                        <div class="form-group">

                            <label>
                                Phone Number
                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                                placeholder="Enter phone number"
                                value="<?php echo isset($_POST["phone"]) ? htmlspecialchars($_POST["phone"]) : ""; ?>"
                            >

                        </div>


                        <!-- MARITAL STATUS -->

                        <div class="form-group">

                            <label>
                                Marital Status
                            </label>

                            <select
                                name="marital_status"
                                class="form-control"
                            >

                                <option value="">
                                    Select Marital Status
                                </option>

                                <option value="Single">
                                    Single
                                </option>

                                <option value="Married">
                                    Married
                                </option>

                                <option value="Divorced">
                                    Divorced
                                </option>

                                <option value="Widowed">
                                    Widowed
                                </option>

                                <option value="Separated">
                                    Separated
                                </option>

                            </select>

                        </div>


                        <!-- OCCUPATION -->

                        <div class="form-group">

                            <label>
                                Occupation
                            </label>

                            <input
                                type="text"
                                name="occupation"
                                class="form-control"
                                placeholder="Enter occupation"
                                value="<?php echo isset($_POST["occupation"]) ? htmlspecialchars($_POST["occupation"]) : ""; ?>"
                            >

                        </div>


                        <!-- DISABILITY -->

                        <div class="form-group">

                            <label>
                                Disability Status
                            </label>

                            <select
                                name="disability_status"
                                class="form-control"
                            >

                                <option value="">
                                    Select Status
                                </option>

                                <option value="None">
                                    None
                                </option>

                                <option value="Physical">
                                    Physical
                                </option>

                                <option value="Visual">
                                    Visual
                                </option>

                                <option value="Hearing">
                                    Hearing
                                </option>

                                <option value="Intellectual">
                                    Intellectual
                                </option>

                                <option value="Multiple">
                                    Multiple
                                </option>

                                <option value="Other">
                                    Other
                                </option>

                            </select>

                        </div>


                    </div>


                    <!-- LOCATION -->

                    <div class="section-title">

                        <i class="fas fa-map-marker-alt"></i>

                        Location Information

                    </div>


                    <div class="form-grid">


                        <!-- CHIEFDOM -->

                        <div class="form-group">

                            <label>
                                Chiefdom
                            </label>

                            <input
                                type="text"
                                name="chiefdom"
                                class="form-control"
                                placeholder="Enter chiefdom"
                                value="<?php echo isset($_POST["chiefdom"]) ? htmlspecialchars($_POST["chiefdom"]) : ""; ?>"
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
                                class="form-control"
                                placeholder="Enter section"
                                value="<?php echo isset($_POST["section"]) ? htmlspecialchars($_POST["section"]) : ""; ?>"
                            >

                        </div>


                        <!-- ADDRESS -->

                        <div class="form-group full">

                            <label>
                                Address
                            </label>

                            <textarea
                                name="address"
                                class="form-control"
                                placeholder="Enter full residential address"
                            ><?php echo isset($_POST["address"]) ? htmlspecialchars($_POST["address"]) : ""; ?></textarea>

                        </div>


                    </div>


                </div>


                <!-- FORM FOOTER -->

                <div class="form-footer">


                    <a href="index.php"
                       class="cancel-btn">

                        <i class="fas fa-times"></i>

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="save-btn">

                        <i class="fas fa-save"></i>

                        Register Client

                    </button>


                </div>


            </form>


        </div>


    </div>

</div>


</body>

</html>