 
<?php
session_start();

require_once "../includes/db.php";
require_once "../includes/permissions.php";

require_login();
require_permission("reports_view");

$search = trim($_GET["search"] ?? "");

$sql = "SELECT client_number, full_name, gender, date_of_birth,
               phone, chiefdom, section
        FROM clients";

if ($search !== "") {
    $sql .= " WHERE full_name LIKE ?
              OR client_number LIKE ?
              OR phone LIKE ?
              OR chiefdom LIKE ?
              OR section LIKE ?";
}

$sql .= " ORDER BY full_name ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to prepare client report.");
}

if ($search !== "") {
    $term = "%" . $search . "%";
    $stmt->bind_param(
        "sssss",
        $term,
        $term,
        $term,
        $term,
        $term
    );
}

$stmt->execute();
$clients = $stmt->get_result();

function reportValue($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Export matching records to CSV
if (isset($_GET["export"]) && $_GET["export"] === "csv") {
    header("Content-Type: text/csv; charset=utf-8");
    header('Content-Disposition: attachment; filename="client_records.csv"');

    $output = fopen("php://output", "w");
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        "Client Number",
        "Full Name",
        "Gender",
        "Date of Birth",
        "Phone",
        "Chiefdom",
        "Section"
    ]);

    while ($client = $clients->fetch_assoc()) {
        fputcsv($output, [
            $client["client_number"],
            $client["full_name"],
            $client["gender"],
            $client["date_of_birth"],
            $client["phone"],
            $client["chiefdom"],
            $client["section"]
        ]);
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Client Records Report</title>

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
    font-size: 20px;
    margin: 0 0 30px;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 14px;
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

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

h1 {
    margin: 0;
    font-size: 25px;
}

h1 i {
    color: #198754;
}

.tools {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 15px;
    border: 0;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}

.green { background: #198754; }
.dark { background: #171717; }
.blue { background: #1976d2; }

.search-card,
.table-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
}

.search-form {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.search-form input {
    flex: 1;
    min-width: 200px;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
}

.report-info {
    color: #666;
    margin-bottom: 15px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

thead {
    background: #171717;
    color: white;
}

th,
td {
    text-align: left;
    padding: 13px;
    border-bottom: 1px solid #eee;
    font-size: 13px;
}

th {
    white-space: nowrap;
}

tbody tr:hover {
    background: #f8f9fa;
}

@media print {
    .sidebar,
    .no-print,
    .search-card {
        display: none !important;
    }

    .main {
        margin: 0;
        padding: 0;
    }

    body {
        background: white;
    }

    .table-card {
        box-shadow: none;
        padding: 0;
    }
}

@media (max-width: 800px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
        padding: 18px;
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

    <div class="header">
        <h1><i class="fas fa-users"></i> Client Records Report</h1>

        <div class="tools no-print">
            <a class="btn dark" href="index.php">
                <i class="fas fa-arrow-left"></i> Reports
            </a>

            <a class="btn green"
               href="?<?php echo http_build_query([
                   "search" => $search,
                   "export" => "csv"
               ]); ?>">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>

            <button class="btn blue" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="search-card no-print">
        <form method="GET" class="search-form">
            <input
                type="text"
                name="search"
                placeholder="Search name, client number, phone, chiefdom..."
                value="<?php echo reportValue($search); ?>"
            >

            <button type="submit" class="btn dark">
                <i class="fas fa-search"></i> Search
            </button>

            <a href="clients.php" class="btn green">
                <i class="fas fa-times"></i> Clear
            </a>
        </form>
    </div>

    <div class="table-card">

        <div class="report-info">
            Matching client records:
            <strong><?php echo $clients->num_rows; ?></strong>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Client Number</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                        <th>Phone</th>
                        <th>Chiefdom</th>
                        <th>Section</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($clients->num_rows > 0): ?>

                    <?php while ($client = $clients->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo reportValue($client["client_number"]); ?></td>
                        <td><?php echo reportValue($client["full_name"]); ?></td>
                        <td><?php echo reportValue($client["gender"]); ?></td>
                        <td><?php echo reportValue($client["date_of_birth"]); ?></td>
                        <td><?php echo reportValue($client["phone"]); ?></td>
                        <td><?php echo reportValue($client["chiefdom"]); ?></td>
                        <td><?php echo reportValue($client["section"]); ?></td>
                    </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7">No matching client records found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
