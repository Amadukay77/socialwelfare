
<?php
session_start();

require_once "../includes/db.php";
require_once "../includes/permissions.php";

require_login();
require_permission("reports_view");

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

$search = trim($_GET["search"] ?? "");

$sql = "
    SELECT
        worker_id,
        worker_number,
        full_name,
        gender,
        phone,
        email,
        address,
        position,
        status,
        created_at
    FROM social_workers
";

$params = [];

if ($search !== "") {
    $sql .= "
        WHERE worker_number LIKE ?
           OR full_name LIKE ?
           OR gender LIKE ?
           OR phone LIKE ?
           OR email LIKE ?
           OR address LIKE ?
           OR position LIKE ?
           OR status LIKE ?
    ";

    $term = "%" . $search . "%";
    $params = array_fill(0, 8, $term);
}

$sql .= " ORDER BY full_name ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to prepare the Social Workers Report.");
}

if ($params) {
    $stmt->bind_param("ssssssss", ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$workers = [];

while ($row = $result->fetch_assoc()) {
    $workers[] = $row;
}

$stmt->close();

// Export CSV
if (isset($_GET["export"]) && $_GET["export"] === "csv") {
    header("Content-Type: text/csv; charset=utf-8");
    header('Content-Disposition: attachment; filename="social_workers_report.csv"');

    $output = fopen("php://output", "w");
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        "Worker Number",
        "Full Name",
        "Gender",
        "Phone",
        "Email",
        "Address",
        "Position",
        "Status",
        "Date Registered"
    ]);

    foreach ($workers as $worker) {
        fputcsv($output, [
            $worker["worker_number"],
            $worker["full_name"],
            $worker["gender"],
            $worker["phone"],
            $worker["email"],
            $worker["address"],
            $worker["position"],
            $worker["status"],
            $worker["created_at"]
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Workers Report | Social Welfare System</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #222;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 230px;
            height: 100vh;
            overflow-y: auto;
            background: #171717;
            color: white;
            padding: 24px 16px;
        }

        .brand {
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .brand-subtitle {
            color: #aaa;
            font-size: 12px;
            margin-bottom: 28px;
        }

        .sidebar a {
            display: block;
            color: #ddd;
            text-decoration: none;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #303030;
            color: white;
        }

        .sidebar a i {
            width: 24px;
            margin-right: 7px;
        }

        .sidebar a:nth-of-type(1) i { color: #42a5f5; }
        .sidebar a:nth-of-type(2) i { color: #66bb6a; }
        .sidebar a:nth-of-type(3) i { color: #ffb74d; }
        .sidebar a:nth-of-type(4) i { color: #ba68c8; }
        .sidebar a:nth-of-type(5) i { color: #4dd0e1; }
        .sidebar a:nth-of-type(6) i { color: #f06292; }
        .sidebar a:nth-of-type(7) i { color: #aed581; }
        .sidebar a:nth-of-type(8) i { color: #ffd54f; }

        .main {
            margin-left: 230px;
            padding: 28px;
        }

        .topbar {
            background: white;
            padding: 18px 22px;
            border-radius: 10px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px #0000000a;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .muted {
            color: #777;
            font-size: 14px;
        }

        .panel {
            background: white;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 2px 8px #0000000a;
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 18px;
        }

        input[type="search"] {
            flex: 1;
            min-width: 190px;
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .btn {
            display: inline-block;
            padding: 11px 14px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
            color: white;
            background: #343a40;
        }

        .btn-blue { background: #1976d2; }
        .btn-green { background: #218838; }
        .btn-red { background: #c62828; }

        .count {
            margin-bottom: 16px;
            font-size: 14px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th, td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        th {
            background: #f7f8fa;
            color: #444;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #fafafa;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge.inactive {
            background: #ffebee;
            color: #c62828;
        }

        .empty {
            padding: 28px;
            text-align: center;
            color: #777;
        }

        @media print {
            .sidebar, .toolbar, .no-print {
                display: none !important;
            }

            .main {
                margin: 0;
                padding: 0;
            }

            body {
                background: white;
            }

            .panel, .topbar {
                box-shadow: none;
                padding: 8px 0;
            }

            table {
                min-width: 0;
            }

            th, td {
                padding: 6px 4px;
                font-size: 9px;
            }
        }

        @media (max-width: 760px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 14px;
            }
        }
    </style>
</head>
<body>

<aside class="sidebar no-print">
    <div class="brand"><i class="fas fa-hands-helping"></i> Social Welfare</div>
    <div class="brand-subtitle">Tonkolili District</div>

    <a href="../index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="../clients/index.php"><i class="fas fa-users"></i> Clients</a>
    <a href="../cases/index.php"><i class="fas fa-folder-open"></i> Cases</a>
    <a href="../documents/index.php"><i class="fas fa-file-alt"></i> Documents</a>
    <a href="../social_workers/index.php" class="active"><i class="fas fa-user-tie"></i> Social Workers</a>
    <a href="../households/index.php"><i class="fas fa-home"></i> Households</a>
    <a href="../activities/index.php"><i class="fas fa-tasks"></i> Activities</a>
    <a href="../appointments/index.php"><i class="fas fa-calendar-alt"></i> Appointments</a>
    <a href="index.php"><i class="fas fa-chart-bar"></i> Reports</a>
    <a href="../settings/index.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<main class="main">
    <div class="topbar">
        <h1><i class="fas fa-user-tie"></i> Social Workers Report</h1>
        <div class="muted">
            Review registered social workers and their contact information.
        </div>
    </div>

    <section class="panel">
        <form method="GET" class="toolbar">
            <input
                type="search"
                name="search"
                placeholder="Search name, worker number, phone or status..."
                value="<?= e($search) ?>"
            >

            <button class="btn btn-blue" type="submit">
                <i class="fas fa-search"></i> Search
            </button>

            <a class="btn" href="social_workers.php">
                <i class="fas fa-times"></i> Clear
            </a>

            <a class="btn btn-green"
               href="social_workers.php?<?= http_build_query(['search' => $search, 'export' => 'csv']) ?>">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>

            <button class="btn btn-red" type="button" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>

            <a class="btn" href="index.php">
                <i class="fas fa-arrow-left"></i> Reports
            </a>
        </form>

        <div class="count">
            Matching social workers:
            <strong><?= count($workers) ?></strong>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Worker Number</th>
                    <th>Full Name</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Date Registered</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($workers) > 0): ?>
                    <?php foreach ($workers as $worker): ?>
                        <tr>
                            <td><?= e($worker["worker_number"]) ?></td>
                            <td><?= e($worker["full_name"]) ?></td>
                            <td><?= e($worker["gender"] ?: "Not specified") ?></td>
                            <td><?= e($worker["phone"] ?: "Not provided") ?></td>
                            <td><?= e($worker["email"] ?: "Not provided") ?></td>
                            <td><?= e($worker["address"] ?: "Not provided") ?></td>
                            <td><?= e($worker["position"] ?: "Not specified") ?></td>
                            <td>
                                <span class="badge <?= $worker["status"] === "Inactive" ? "inactive" : "" ?>">
                                    <?= e($worker["status"]) ?>
                                </span>
                            </td>
                            <td><?= e($worker["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="empty">
                            No social workers found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

</body>
</html>
