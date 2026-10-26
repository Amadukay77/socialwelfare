 ```php
<?php
session_start();

require_once "../includes/db.php";
require_once "../includes/permissions.php";

require_login();
require_permission("reports_view");

// Get report totals
$clientTotal = $conn->query(
    "SELECT COUNT(*) AS total FROM clients"
)->fetch_assoc()["total"];

$caseTotal = $conn->query(
    "SELECT COUNT(*) AS total FROM cases"
)->fetch_assoc()["total"];

$documentTotal = $conn->query(
    "SELECT COUNT(*) AS total FROM documents"
)->fetch_assoc()["total"];

$workerTotal = $conn->query(
    "SELECT COUNT(*) AS total FROM social_workers"
)->fetch_assoc()["total"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports Dashboard - Social Welfare System</title>

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

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    overflow-y: auto;
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
    gap: 14px;
    align-items: center;
    padding: 14px 20px;
    color: #ddd;
    text-decoration: none;
}

.sidebar a:hover,
.sidebar a.active {
    background: #292929;
    color: white;
}

.sidebar a.active {
    border-left: 4px solid #e53935;
}

.sidebar a i {
    width: 24px;
    text-align: center;
}

.sidebar a:nth-of-type(1) i { color: #42a5f5; }
.sidebar a:nth-of-type(2) i { color: #66bb6a; }
.sidebar a:nth-of-type(3) i { color: #ffa726; }
.sidebar a:nth-of-type(4) i { color: #ab47bc; }
.sidebar a:nth-of-type(5) i { color: #26c6da; }
.sidebar a:nth-of-type(6) i { color: #ec407a; }
.sidebar a:nth-of-type(7) i { color: #ffee58; }
.sidebar a:nth-of-type(8) i { color: #29b6f6; }
.sidebar a:nth-of-type(9) i { color: #8d6e63; }
.sidebar a:nth-of-type(10) i { color: #bdbdbd; }
.sidebar a:nth-of-type(11) i { color: #ef5350; }

.main {
    margin-left: 250px;
    padding: 30px;
}

h1 {
    margin-top: 0;
}

.subtitle {
    color: #777;
    margin-bottom: 25px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 20px;
}

.card {
    background: white;
    padding: 24px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    border-left: 5px solid #198754;
}

.card i {
    font-size: 27px;
    margin-bottom: 15px;
}

.card h3 {
    font-size: 15px;
    color: #666;
    margin: 0 0 12px;
}

.number {
    font-size: 30px;
    font-weight: bold;
}

.blue { border-color: #1976d2; }
.blue i { color: #1976d2; }

.green { border-color: #198754; }
.green i { color: #198754; }

.orange { border-color: #f39c12; }
.orange i { color: #f39c12; }

.purple { border-color: #8e44ad; }
.purple i { color: #8e44ad; }

.report-note,
.report-section {
    margin-top: 25px;
    background: white;
    border-radius: 10px;
    padding: 22px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.report-section h2 {
    margin-top: 0;
    font-size: 21px;
}

.report-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
}

.report-link {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    color: #222;
    background: #fff;
    transition: 0.2s;
}

.report-link:hover {
    border-color: #198754;
    background: #f7fff9;
    transform: translateY(-2px);
}

.report-link i {
    font-size: 25px;
    width: 32px;
    text-align: center;
}

.report-link strong {
    display: block;
    margin-bottom: 5px;
}

.report-link small {
    color: #777;
    line-height: 1.4;
}

.client-icon { color: #1976d2; }
.case-icon { color: #e67e22; }
.document-icon { color: #8e44ad; }
.worker-icon { color: #198754; }

@media (max-width: 800px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="sidebar">
    <h2><i class="fas fa-hands-helping"></i> Social Welfare</h2>

    <a href="../index.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>

    <a href="../clients/index.php">
        <i class="fas fa-users"></i> Clients
    </a>

    <a href="../cases/index.php">
        <i class="fas fa-folder-open"></i> Cases
    </a>

    <a href="../documents/index.php">
        <i class="fas fa-file-alt"></i> Documents
    </a>

    <a href="../social_workers/index.php">
        <i class="fas fa-user-tie"></i> Social Workers
    </a>

    <a href="../households/index.php">
        <i class="fas fa-home"></i> Households
    </a>

    <a href="../activities/index.php">
        <i class="fas fa-tasks"></i> Activities
    </a>

    <a href="../appointments/index.php">
        <i class="fas fa-calendar-alt"></i> Appointments
    </a>

    <a href="index.php" class="active">
        <i class="fas fa-chart-bar"></i> Reports
    </a>

    <a href="../settings/index.php">
        <i class="fas fa-cog"></i> Settings
    </a>

    <a href="../auth/logout.php">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="main">

    <h1><i class="fas fa-chart-bar"></i> Reports Dashboard</h1>

    <p class="subtitle">
        View, search, and print reports from the Tonkolili District Social Welfare System.
    </p>

    <!-- Summary cards -->
    <div class="cards">

        <div class="card blue">
            <i class="fas fa-users"></i>
            <h3>Total Clients</h3>
            <div class="number">
                <?= number_format((int)$clientTotal); ?>
            </div>
        </div>

        <div class="card green">
            <i class="fas fa-folder-open"></i>
            <h3>Total Cases</h3>
            <div class="number">
                <?= number_format((int)$caseTotal); ?>
            </div>
        </div>

        <div class="card orange">
            <i class="fas fa-file-alt"></i>
            <h3>Total Documents</h3>
            <div class="number">
                <?= number_format((int)$documentTotal); ?>
            </div>
        </div>

        <div class="card purple">
            <i class="fas fa-user-tie"></i>
            <h3>Total Social Workers</h3>
            <div class="number">
                <?= number_format((int)$workerTotal); ?>
            </div>
        </div>

    </div>

    <!-- Detailed report links -->
    <div class="report-section">
        <h2><i class="fas fa-file-invoice"></i> Detailed Reports</h2>

        <p class="subtitle">
            Select a report below to view its records.
        </p>

        <div class="report-links">

            <a class="report-link" href="clients.php">
                <i class="fas fa-users client-icon"></i>
                <span>
                    <strong>Client Records Report</strong>
                    <small>View, search, export, and print client records.</small>
                </span>
            </a>

            <a class="report-link" href="cases.php">
                <i class="fas fa-folder-open case-icon"></i>
                <span>
                    <strong>Cases Report</strong>
                    <small>Review registered social welfare cases.</small>
                </span>
            </a>

            <a class="report-link" href="documents.php">
                <i class="fas fa-file-alt document-icon"></i>
                <span>
                    <strong>Documents Report</strong>
                    <small>Review documents recorded in the system.</small>
                </span>
            </a>

            <a class="report-link" href="social_workers.php">
                <i class="fas fa-user-tie worker-icon"></i>
                <span>
                    <strong>Social Workers Report</strong>
                    <small>Review registered social workers.</small>
                </span>
            </a>

        </div>
    </div>

   
<div class="report-note">
    <h3><i class="fas fa-info-circle"></i> About Reports</h3>
    <p>
        Use the detailed report links above to review registered clients,
        social welfare cases, uploaded documents, and social workers.
        Each report supports searching, CSV export, and printing.
    </p>
</div>


</div>

</body>
</html>
```