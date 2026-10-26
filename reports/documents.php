
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
        d.document_id,
        d.document_name,
        d.document_type,
        d.file_path,
        d.uploaded_at,
        d.case_id,
        cl.full_name AS client_name,
        c.case_number,
        u.username AS uploader_name
    FROM documents d
    LEFT JOIN clients cl ON d.client_id = cl.client_id
    LEFT JOIN cases c ON d.case_id = c.case_id
    LEFT JOIN users u ON d.uploaded_by = u.user_id
";

$params = [];

if ($search !== "") {
    $sql .= "
        WHERE d.document_name LIKE ?
           OR d.document_type LIKE ?
           OR cl.full_name LIKE ?
           OR c.case_number LIKE ?
           OR u.username LIKE ?
    ";

    $term = "%" . $search . "%";
    $params = [$term, $term, $term, $term, $term];
}

$sql .= " ORDER BY d.uploaded_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to prepare report query. Please check your database column names.");
}

if ($params) {
    $stmt->bind_param("sssss", ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$documents = [];

while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}

$stmt->close();

// Export report to CSV
if (isset($_GET["export"]) && $_GET["export"] === "csv") {
    header("Content-Type: text/csv; charset=utf-8");
    header('Content-Disposition: attachment; filename="documents_report.csv"');

    $output = fopen("php://output", "w");
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        "Document Name",
        "Document Type",
        "Client Name",
        "Case Number",
        "Uploaded By",
        "Date Uploaded"
    ]);

    foreach ($documents as $doc) {
        fputcsv($output, [
            $doc["document_name"],
            $doc["document_type"],
            $doc["client_name"],
            $doc["case_number"],
            $doc["uploader_name"],
            $doc["uploaded_at"]
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
    <title>Documents Report | Social Welfare System</title>

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

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
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
            background: #e8eaf6;
            color: #3949ab;
            font-size: 12px;
        }

        .count {
            margin-bottom: 16px;
            font-size: 14px;
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
                padding: 7px 5px;
                font-size: 10px;
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
    <a href="../documents/index.php" class="active"><i class="fas fa-file-alt"></i> Documents</a>
    <a href="../social_workers/index.php"><i class="fas fa-user-tie"></i> Social Workers</a>
    <a href="../households/index.php"><i class="fas fa-home"></i> Households</a>
    <a href="../activities/index.php"><i class="fas fa-tasks"></i> Activities</a>
    <a href="../appointments/index.php"><i class="fas fa-calendar-alt"></i> Appointments</a>
    <a href="index.php"><i class="fas fa-chart-bar"></i> Reports</a>
    <a href="../settings/index.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<main class="main">
    <div class="topbar">
        <h1><i class="fas fa-file-alt"></i> Documents Report</h1>
        <div class="muted">Review and report uploaded social welfare documents.</div>
    </div>

    <section class="panel">
        <form method="GET" class="toolbar">
            <input
                type="search"
                name="search"
                placeholder="Search document, client, case or uploader..."
                value="<?= e($search) ?>"
            >

            <button class="btn btn-blue" type="submit">
                <i class="fas fa-search"></i> Search
            </button>

            <a class="btn" href="documents.php">
                <i class="fas fa-times"></i> Clear
            </a>

            <a class="btn btn-green"
               href="documents.php?<?= http_build_query(['search' => $search, 'export' => 'csv']) ?>">
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
            Matching document records:
            <strong><?= count($documents) ?></strong>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Document Name</th>
                    <th>Document Type</th>
                    <th>Client Name</th>
                    <th>Case Number</th>
                    <th>Uploaded By</th>
                    <th>Date Uploaded</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($documents) > 0): ?>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= e($doc["document_name"]) ?></td>
                            <td>
                                <span class="badge">
                                    <?= e($doc["document_type"] ?: "Unspecified") ?>
                                </span>
                            </td>
                            <td><?= e($doc["client_name"] ?: "Unknown client") ?></td>
                            <td><?= e($doc["case_number"] ?: "Not linked") ?></td>
                            <td><?= e($doc["uploader_name"] ?: "Unknown") ?></td>
                            <td><?= e($doc["uploaded_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty">
                            No documents found.
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
