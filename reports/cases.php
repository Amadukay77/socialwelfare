```php
<?php
session_start();

require_once "../includes/db.php";
require_once "../includes/permissions.php";

require_login();
require_permission("reports_view");

$search = trim($_GET["search"] ?? "");

// Search cases and get related client/worker names.
// LEFT JOIN keeps cases visible even if a related record is missing.
$sql = "
    SELECT
        c.case_id,
        c.case_number,
        c.client_id,
        c.case_type,
        c.description,
        c.priority,
        c.status,
        c.assigned_to,
        c.date_opened,
        c.date_closed,
        cl.full_name AS client_name,
        sw.full_name AS worker_name
    FROM cases c
    LEFT JOIN clients cl ON c.client_id = cl.client_id
    LEFT JOIN social_workers sw ON c.assigned_to = sw.worker_id
    WHERE (
        c.case_number LIKE ?
        OR c.case_type LIKE ?
        OR c.status LIKE ?
        OR c.priority LIKE ?
        OR cl.full_name LIKE ?
        OR sw.full_name LIKE ?
    )
    ORDER BY c.date_opened DESC, c.case_id DESC
";

$stmt = $conn->prepare($sql);

$term = "%" . $search . "%";
$stmt->bind_param(
    "ssssss",
    $term,
    $term,
    $term,
    $term,
    $term,
    $term
);

$stmt->execute();
$result = $stmt->get_result();

$cases = [];
while ($row = $result->fetch_assoc()) {
    $cases[] = $row;
}
$stmt->close();

// Export matching records to CSV.
if (isset($_GET["export"]) && $_GET["export"] === "csv") {
    header("Content-Type: text/csv; charset=utf-8");
    header('Content-Disposition: attachment; filename="cases_report.csv"');

    $output = fopen("php://output", "w");

    // Help spreadsheet software recognize UTF-8.
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        "Case Number",
        "Client Name",
        "Case Type",
        "Description",
        "Priority",
        "Status",
        "Assigned Social Worker",
        "Date Opened",
        "Date Closed"
    ]);

    foreach ($cases as $case) {
        fputcsv($output, [
            $case["case_number"],
            $case["client_name"] ?? "Not available",
            $case["case_type"],
            $case["description"],
            $case["priority"],
            $case["status"],
            $case["worker_name"] ?? "Unassigned",
            $case["date_opened"],
            $case["date_closed"]
        ]);
    }

    fclose($output);
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cases Report - Social Welfare System</title>

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
    margin-bottom: 22px;
}

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    margin-bottom: 20px;
}

.search-form {
    display: flex;
    flex: 1;
    min-width: 230px;
    gap: 8px;
}

input[type="text"] {
    flex: 1;
    min-width: 100px;
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.btn {
    display: inline-block;
    padding: 11px 15px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    color: white;
    cursor: pointer;
    font-size: 14px;
}

.btn-search { background: #1976d2; }
.btn-clear { background: #6c757d; }
.btn-export { background: #198754; }
.btn-print { background: #333; }
.btn-back { background: #e53935; }

.btn:hover {
    opacity: 0.88;
}

.summary {
    background: white;
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.table-wrap {
    background: white;
    padding: 15px;
    border-radius: 10px;
    overflow-x: auto;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}

table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
    font-size: 13px;
    vertical-align: top;
}

th {
    background: #202020;
    color: white;
    white-space: nowrap;
}

tr:hover td {
    background: #fafafa;
}

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.priority-low { background: #e8f5e9; color: #2e7d32; }
.priority-medium { background: #fff3cd; color: #856404; }
.priority-high { background: #ffe0b2; color: #e65100; }
.priority-urgent { background: #ffebee; color: #c62828; }

.status-open { background: #e3f2fd; color: #1565c0; }
.status-in-progress { background: #fff3e0; color: #ef6c00; }
.status-closed { background: #e8f5e9; color: #2e7d32; }
.status-pending { background: #f3e5f5; color: #7b1fa2; }

.empty {
    text-align: center;
    padding: 25px;
    color: #777;
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

@media print {
    .sidebar,
    .toolbar,
    .no-print {
        display: none !important;
    }

    .main {
        margin: 0;
        padding: 0;
    }

    body {
        background: white;
    }

    .table-wrap,
    .summary {
        box-shadow: none;
        padding: 0;
    }

    table {
        min-width: 0;
    }

    th, td {
        font-size: 9px;
        padding: 5px;
    }

    th {
        background: #ddd !important;
        color: black !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
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

    <h1><i class="fas fa-folder-open"></i> Cases Report</h1>

    <p class="subtitle">
        Review and report registered social welfare cases.
    </p>

    <div class="toolbar no-print">

        <form class="search-form" method="GET" action="">
            <input
                type="text"
                name="search"
                placeholder="Search case, client, worker, status..."
                value="<?= e($search); ?>"
            >
            <button type="submit" class="btn btn-search">
                <i class="fas fa-search"></i> Search
            </button>
        </form>

        <a class="btn btn-clear" href="cases.php">
            <i class="fas fa-times"></i> Clear
        </a>

        <a class="btn btn-export"
           href="?search=<?= urlencode($search); ?>&export=csv">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>

        <button class="btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>

        <a class="btn btn-back" href="index.php">
            <i class="fas fa-arrow-left"></i> Reports
        </a>
    </div>

    <div class="summary">
        <i class="fas fa-info-circle"></i>
        Matching case records:
        <strong><?= count($cases); ?></strong>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Case Number</th>
                    <th>Client Name</th>
                    <th>Case Type</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned Worker</th>
                    <th>Date Opened</th>
                    <th>Date Closed</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($cases) > 0): ?>
                <?php foreach ($cases as $case): ?>
                    <?php
                    $priorityClass = "priority-" .
                        strtolower((string)($case["priority"] ?? "medium"));

                    $statusClass = "status-" .
                        str_replace(
                            " ",
                            "-",
                            strtolower((string)($case["status"] ?? "open"))
                        );
                    ?>
                    <tr>
                        <td><?= e($case["case_number"]); ?></td>
                        <td><?= e($case["client_name"] ?? "Not available"); ?></td>
                        <td><?= e($case["case_type"]); ?></td>
                        <td><?= e($case["description"]); ?></td>
                        <td>
                            <span class="badge <?= e($priorityClass); ?>">
                                <?= e($case["priority"] ?? "Medium"); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= e($statusClass); ?>">
                                <?= e($case["status"] ?? "Open"); ?>
                            </span>
                        </td>
                        <td><?= e($case["worker_name"] ?? "Unassigned"); ?></td>
                        <td><?= e($case["date_opened"]); ?></td>
                        <td><?= e($case["date_closed"] ?: "Not closed"); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="empty">
                        No matching case records were found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
```