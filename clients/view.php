 <?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$client_id = (int) $_GET["id"];

/* Get client */
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$client = $result->fetch_assoc();

/* Get documents belonging to this client */
$doc_stmt = $conn->prepare("
    SELECT document_id, document_name, document_type, file_path, uploaded_at
    FROM documents
    WHERE client_id = ?
    ORDER BY uploaded_at DESC
");

$doc_stmt->bind_param("i", $client_id);
$doc_stmt->execute();

$documents_result = $doc_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Client Profile - Social Welfare System</title>

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

        /* =========================
           SIDEBAR
        ========================= */

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
            margin: 0 0 30px;
            font-size: 20px;
        }

        .sidebar h2 i {
            font-size: 22px;
            margin-right: 7px;
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

        .sidebar a i {
            width: 26px;
            min-width: 26px;
            text-align: center;
            font-size: 18px;
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

        /* =========================
           TOP BAR
        ========================= */

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

        .topbar h1 i {
            margin-right: 10px;
        }

        /* =========================
           MAIN
        ========================= */

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
            font-size: 24px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            background: #171717;
            color: white;
            padding: 11px 17px;
            border-radius: 6px;
        }

        .back-btn:hover {
            background: #333;
        }

        /* =========================
           SUCCESS MESSAGE
        ========================= */

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 5px solid #43a047;
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success-message i {
            margin-right: 8px;
        }

        /* =========================
           PROFILE HEADER
        ========================= */

        .profile-header {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }

        .profile-icon {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            background: #171717;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .profile-info h3 {
            margin: 0 0 7px;
            font-size: 24px;
        }

        .profile-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .client-number {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 10px;
            background: #f1f1f1;
            border-radius: 5px;
            font-weight: bold;
            color: #333;
        }

        /* =========================
           CARD
        ========================= */

        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            margin-bottom: 20px;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 1px solid #eee;
        }

        .card-title i {
            font-size: 20px;
            color: #e53935;
        }

        /* =========================
           PERSONAL INFORMATION
        ========================= */

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .info-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 16px;
            background: #fafafa;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .info-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 8px;
            background: #171717;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 13px;
            color: #777;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 15px;
            font-weight: bold;
            color: #222;
            word-break: break-word;
        }

        /* Different information icon colors */

        .blue {
            background: #1976d2;
        }

        .green {
            background: #43a047;
        }

        .orange {
            background: #fb8c00;
        }

        .purple {
            background: #8e24aa;
        }

        .cyan {
            background: #00acc1;
        }

        .pink {
            background: #d81b60;
        }

        .brown {
            background: #6d4c41;
        }

        .red {
            background: #e53935;
        }

        /* =========================
           DOCUMENTS
        ========================= */

        .documents-table {
            width: 100%;
            overflow-x: auto;
        }

        .documents-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        .documents-table th {
            background: #171717;
            color: white;
            padding: 14px;
            text-align: left;
            font-size: 14px;
        }

        .documents-table th i {
            margin-right: 6px;
        }

        .documents-table td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .documents-table tbody tr:hover {
            background: #fafafa;
        }

        .document-name {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .document-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 8px;
            background: #f3e5f5;
            color: #8e24aa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .document-type {
            display: inline-block;
            padding: 5px 9px;
            background: #f3e5f5;
            color: #7b1fa2;
            border-radius: 5px;
            font-size: 13px;
            font-weight: bold;
        }

        .document-actions {
            display: flex;
            gap: 8px;
        }

        .view-document,
        .download-document {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            color: white;
            text-decoration: none;
        }

        .view-document {
            background: #1976d2;
        }

        .view-document:hover {
            background: #125ca5;
        }

        .download-document {
            background: #43a047;
        }

        .download-document:hover {
            background: #2e7d32;
        }

        .no-documents {
            text-align: center;
            padding: 35px 20px;
            color: #777;
        }

        .no-documents > i {
            font-size: 50px;
            color: #bbb;
            margin-bottom: 15px;
        }

        .no-documents h3 {
            margin: 5px 0 8px;
            color: #444;
        }

        .no-documents p {
            margin-bottom: 20px;
        }

        /* =========================
           ACTION BUTTONS
        ========================= */

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            text-decoration: none;
            color: white;
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .edit-btn {
            background: #1976d2;
        }

        .edit-btn:hover {
            background: #125ca5;
        }

        .document-btn {
            background: #8e24aa;
        }

        .document-btn:hover {
            background: #6d1b85;
        }

        .delete-btn {
            background: #e53935;
        }

        .delete-btn:hover {
            background: #c62828;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .info-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 700px) {

            .sidebar {
                width: 210px;
            }

            .topbar,
            .main {
                margin-left: 210px;
            }

            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }

        }

    </style>

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

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


<!-- =========================
     TOP BAR
========================= -->

<div class="topbar">

    <h1>
        <i class="fas fa-user"></i>
        Client Profile
    </h1>

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">

    <div class="page-header">

        <h2>Client Profile</h2>

        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Clients
        </a>

    </div>


    <!-- SUCCESS MESSAGE -->

    <?php if (isset($_GET["document_added"]) && $_GET["document_added"] == "1"): ?>

        <div class="success-message">

            <i class="fas fa-check-circle"></i>

            Document uploaded successfully and added to this client's record.

        </div>

    <?php endif; ?>


    <!-- PROFILE HEADER -->

    <div class="profile-header">

        <div class="profile-icon">
            <i class="fas fa-user"></i>
        </div>

        <div class="profile-info">

            <h3>
                <?php echo htmlspecialchars($client["full_name"]); ?>
            </h3>

            <p>
                <i class="fas fa-id-card"></i>
                Registered Social Welfare Client
            </p>

            <span class="client-number">
                <?php echo htmlspecialchars($client["client_number"]); ?>
            </span>

        </div>

    </div>


    <!-- PERSONAL INFORMATION -->

    <div class="card">

        <div class="card-title">

            <i class="fas fa-user-circle"></i>

            Personal Information

        </div>


        <div class="info-grid">

            <!-- CLIENT NUMBER -->

            <div class="info-item">

                <div class="info-icon blue">
                    <i class="fas fa-id-card"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Client Number
                    </div>

                    <div class="info-value">
                        <?php echo htmlspecialchars($client["client_number"]); ?>
                    </div>

                </div>

            </div>


            <!-- FULL NAME -->

            <div class="info-item">

                <div class="info-icon green">
                    <i class="fas fa-user"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Full Name
                    </div>

                    <div class="info-value">
                        <?php echo htmlspecialchars($client["full_name"]); ?>
                    </div>

                </div>

            </div>


            <!-- GENDER -->

            <div class="info-item">

                <div class="info-icon purple">
                    <i class="fas fa-venus-mars"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Gender
                    </div>

                    <div class="info-value">
                        <?php echo htmlspecialchars($client["gender"]); ?>
                    </div>

                </div>

            </div>


            <!-- DATE OF BIRTH -->

            <div class="info-item">

                <div class="info-icon orange">
                    <i class="fas fa-birthday-cake"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Date of Birth
                    </div>

                    <div class="info-value">

                        <?php
                        echo !empty($client["date_of_birth"])
                            ? htmlspecialchars($client["date_of_birth"])
                            : "Not provided";
                        ?>

                    </div>

                </div>

            </div>


            <!-- PHONE -->

            <div class="info-item">

                <div class="info-icon cyan">
                    <i class="fas fa-phone"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Phone
                    </div>

                    <div class="info-value">

                        <?php
                        echo !empty($client["phone"])
                            ? htmlspecialchars($client["phone"])
                            : "Not provided";
                        ?>

                    </div>

                </div>

            </div>


            <!-- ADDRESS -->

            <div class="info-item">

                <div class="info-icon red">
                    <i class="fas fa-home"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Address
                    </div>

                    <div class="info-value">

                        <?php
                        echo !empty($client["address"])
                            ? htmlspecialchars($client["address"])
                            : "Not provided";
                        ?>

                    </div>

                </div>

            </div>


            <!-- CHIEFDOM -->

            <div class="info-item">

                <div class="info-icon green">
                    <i class="fas fa-map-marker-alt"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Chiefdom
                    </div>

                    <div class="info-value">

                        <?php
                        echo !empty($client["chiefdom"])
                            ? htmlspecialchars($client["chiefdom"])
                            : "Not provided";
                        ?>

                    </div>

                </div>

            </div>


            <!-- SECTION -->

            <div class="info-item">

                <div class="info-icon blue">
                    <i class="fas fa-map"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Section
                    </div>

                    <div class="info-value">

                        <?php
                        echo !empty($client["section"])
                            ? htmlspecialchars($client["section"])
                            : "Not provided";
                        ?>

                    </div>

                </div>

            </div>


            <!-- DISABILITY -->

            <div class="info-item">

                <div class="info-icon purple">
                    <i class="fas fa-wheelchair"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Disability Status
                    </div>

                    <div class="info-value">

                        <?php
                        echo !empty($client["disability_status"])
                            ? htmlspecialchars($client["disability_status"])
                            : "Not provided";
                        ?>

                    </div>

                </div>

            </div>


            <!-- MARITAL STATUS -->

            <div class="info-item">

                <div class="info-icon pink">
                    <i class="fas fa-ring"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Marital Status
                    </div>

                    <div class="info-value">

                        <?php
                        echo !empty($client["marital_status"])
                            ? htmlspecialchars($client["marital_status"])
                            : "Not provided";
                        ?>

                    </div>

                </div>

            </div>


            <!-- OCCUPATION -->

            <div class="info-item">

                <div class="info-icon brown">
                    <i class="fas fa-briefcase"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Occupation
                    </div>

                    <div class="info-value">

                        <?php
                        echo !empty($client["occupation"])
                            ? htmlspecialchars($client["occupation"])
                            : "Not provided";
                        ?>

                    </div>

                </div>

            </div>


            <!-- REGISTERED DATE -->

            <div class="info-item">

                <div class="info-icon red">
                    <i class="fas fa-calendar-plus"></i>
                </div>

                <div class="info-content">

                    <div class="info-label">
                        Registered On
                    </div>

                    <div class="info-value">

                        <?php
                        echo !empty($client["created_at"])
                            ? htmlspecialchars($client["created_at"])
                            : "Not provided";
                        ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================
         CLIENT DOCUMENTS
    ========================= -->

    <div class="card">

        <div class="card-title">

            <i class="fas fa-folder-open"></i>

            Client Documents

        </div>


        <?php if ($documents_result->num_rows > 0): ?>

            <div class="documents-table">

                <table>

                    <thead>

                        <tr>

                            <th>
                                <i class="fas fa-file"></i>
                                Document
                            </th>

                            <th>
                                <i class="fas fa-tag"></i>
                                Type
                            </th>

                            <th>
                                <i class="fas fa-calendar"></i>
                                Uploaded
                            </th>

                            <th>
                                <i class="fas fa-cogs"></i>
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php while ($document = $documents_result->fetch_assoc()): ?>

                            <?php

                            $extension = strtolower(
                                pathinfo(
                                    $document["file_path"],
                                    PATHINFO_EXTENSION
                                )
                            );

                            ?>

                            <tr>

                                <td>

                                    <div class="document-name">

                                        <div class="document-icon">

                                            <?php

                                            if ($extension === "pdf") {

                                                echo '<i class="fas fa-file-pdf"></i>';

                                            } elseif (
                                                $extension === "jpg" ||
                                                $extension === "jpeg" ||
                                                $extension === "png"
                                            ) {

                                                echo '<i class="fas fa-file-image"></i>';

                                            } elseif (
                                                $extension === "doc" ||
                                                $extension === "docx"
                                            ) {

                                                echo '<i class="fas fa-file-word"></i>';

                                            } else {

                                                echo '<i class="fas fa-file-alt"></i>';

                                            }

                                            ?>

                                        </div>

                                        <div>

                                            <strong>

                                                <?php
                                                echo htmlspecialchars(
                                                    $document["document_name"]
                                                );
                                                ?>

                                            </strong>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    <span class="document-type">

                                        <?php

                                        echo !empty($document["document_type"])
                                            ? htmlspecialchars($document["document_type"])
                                            : "Not specified";

                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $document["uploaded_at"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <div class="document-actions">

                                        <!-- VIEW -->

                                        <a
                                            href="../documents/<?php echo htmlspecialchars($document["file_path"]); ?>"
                                            target="_blank"
                                            class="view-document"
                                            title="View Document">

                                            <i class="fas fa-eye"></i>

                                        </a>


                                        <!-- DOWNLOAD -->

                                        <a
                                            href="../documents/<?php echo htmlspecialchars($document["file_path"]); ?>"
                                            download
                                            class="download-document"
                                            title="Download Document">

                                            <i class="fas fa-download"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="no-documents">

                <i class="fas fa-folder-open"></i>

                <h3>No Documents Found</h3>

                <p>
                    No documents have been uploaded for this client yet.
                </p>

                <a
                    href="../documents/add.php?client_id=<?php echo $client_id; ?>"
                    class="action-btn document-btn">

                    <i class="fas fa-plus"></i>

                    Add First Document

                </a>

            </div>

        <?php endif; ?>

    </div>


    <!-- =========================
         CLIENT ACTIONS
    ========================= -->

    <div class="card">

        <div class="card-title">

            <i class="fas fa-cogs"></i>

            Client Actions

        </div>


        <div class="actions">

            <!-- EDIT -->

            <a
                href="edit.php?id=<?php echo $client_id; ?>"
                class="action-btn edit-btn">

                <i class="fas fa-edit"></i>

                Edit Client

            </a>


            <!-- ADD DOCUMENT -->

            <a
                href="../documents/add.php?client_id=<?php echo $client_id; ?>"
                class="action-btn document-btn">

                <i class="fas fa-file-medical"></i>

                Add Document

            </a>


            <!-- DELETE -->

            <a
                href="delete.php?id=<?php echo $client_id; ?>"
                class="action-btn delete-btn"
                onclick="return confirm('Are you sure you want to delete this client? This action cannot be undone.');">

                <i class="fas fa-trash-alt"></i>

                Delete Client

            </a>

        </div>

    </div>

</div>

</body>

</html>