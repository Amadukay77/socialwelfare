 
<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$role = trim($_SESSION["role"] ?? "");
$full_name = $_SESSION["full_name"] ?? "User";

$is_admin = ($role === "Administrator");
$is_social_worker = ($role === "Social Worker");
$is_records_officer = ($role === "Records Officer");

if (!$is_admin && !$is_social_worker && !$is_records_officer) {
    http_response_code(403);
    exit("Access denied. Please contact your system administrator.");
}

function get_count($conn, $table)
{
    $allowed_tables = [
        "clients", "cases", "documents", "users",
        "households", "activities", "appointments"
    ];

    if (!in_array($table, $allowed_tables, true)) {
        return 0;
    }

    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM `$table`"
    );

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)($row["total"] ?? 0);
}

$client_count = get_count($conn, "clients");
$case_count = get_count($conn, "cases");
$document_count = get_count($conn, "documents");
$household_count = get_count($conn, "households");
$activity_count = get_count($conn, "activities");
$appointment_count = get_count($conn, "appointments");
$user_count = $is_admin ? get_count($conn, "users") : null;

$menu = [
    ["Dashboard", "index.php", "fa-chart-pie", "all"],
    ["Clients", "clients/index.php", "fa-users", "all"],
    ["Cases", "cases/index.php", "fa-folder-open", "all"],
    ["Documents", "documents/index.php", "fa-file-alt", "all"],
    ["Households", "households/index.php", "fa-house-user", "all"],
    ["Activities", "activities/index.php", "fa-tasks", "all"],
    ["Appointments", "appointments/index.php", "fa-calendar-check", "all"],
    ["Social Workers", "social_workers/index.php", "fa-user-tie", "admin"],
    ["Reports", "reports/index.php", "fa-chart-bar", "all"],
    ["Users", "users/index.php", "fa-user-cog", "admin"],
    ["Settings", "settings/index.php", "fa-cog", "admin"]
];

$quick_actions = [
    ["Manage Clients", "clients/index.php", "fa-user-plus", "blue"],
    ["Manage Cases", "cases/index.php", "fa-folder-open", "purple"],
    ["Manage Documents", "documents/index.php", "fa-file-upload", "green"],
    ["Manage Households", "households/index.php", "fa-house-user", "orange"],
    ["View Activities", "activities/index.php", "fa-tasks", "teal"],
    ["Appointments", "appointments/index.php", "fa-calendar-check", "pink"]
];

if ($is_admin) {
    $quick_actions[] = [
        "Manage Users", "users/index.php", "fa-user-cog", "orange"
    ];
    $quick_actions[] = [
        "Manage Social Workers", "social_workers/index.php",
        "fa-user-tie", "teal"
    ];
    $quick_actions[] = [
        "View Reports", "reports/index.php", "fa-chart-bar", "purple"
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard | Tonkolili District Social Welfare</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
:root {
    --green: #097377;
    --green-dark: #105652;
    --sidebar: #00040a;
    --sidebar-hover: #24344d;
    --sidebar-active: #1d2b40;
    --background: #f3f6f9;
    --text: #263342;
    --muted: #7a8795;
    --border: #e5eaf0;
    --white: #ffffff;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: var(--background);
    color: var(--text);
}

a {
    text-decoration: none;
}

/* =========================
   COMPACT BLUE-BLACK SIDEBAR
========================= */
.sidebar {
    width: 210px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    overflow-y: auto;
    background: var(--sidebar);
    color: white;
    padding-bottom: 24px;
    z-index: 100;
    box-shadow: 3px 0 15px rgba(0, 0, 0, .10);
}

.brand {
    padding: 22px 12px 19px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,.10);
}

.brand-logo {
    width: 58px;
    height: 58px;
    margin: 0 auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    color: white;
    background: linear-gradient(145deg, #0ef706, #105638);
    box-shadow: 0 6px 16px rgba(0,0,0,.20);
}

.brand-logo i {
    font-size: 27px;
}

.brand h3 {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
}

.brand p {
    margin: 4px 0 0;
    color: #aebdce;
    font-size: 11px;
}

.menu-heading {
    padding: 20px 18px 8px;
    color: #8e9db2;
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1.3px;
}

/* Sidebar navigation links */
.sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 3px 9px;
    padding: 11px 10px;
    border-radius: 6px;
    border-left: 3px solid transparent;
    color: #d4deeb;
    font-size: 12px;
    transition: background .2s ease, color .2s ease;
}

.sidebar a i {
    width: 19px;
    flex-shrink: 0;
    text-align: center;
    font-size: 15px;
}

/* Different colours for each icon */
.sidebar a[href="index.php"] i {
    color: #42a5f5;
}

.sidebar a[href="clients/index.php"] i {
    color: #66bb6a;
}

.sidebar a[href="cases/index.php"] i {
    color: #ffb74d;
}

.sidebar a[href="documents/index.php"] i {
    color: #ba68c8;
}

.sidebar a[href="households/index.php"] i {
    color: #4dd0e1;
}

.sidebar a[href="activities/index.php"] i {
    color: #f06292;
}

.sidebar a[href="appointments/index.php"] i {
    color: #aed581;
}

.sidebar a[href="social_workers/index.php"] i {
    color: #90caf9;
}

.sidebar a[href="reports/index.php"] i {
    color: #ffd54f;
}

.sidebar a[href="users/index.php"] i {
    color: #ce93d8;
}

.sidebar a[href="settings/index.php"] i {
    color: #80cbc4;
}

.sidebar a[href="auth/logout.php"] i {
    color: #ff7777;
}

/* Hover and active states */
.sidebar a:hover {
    background: var(--sidebar-hover);
    color: #ffffff;
}

.sidebar a.active {
    background: var(--sidebar-active);
    color: #ffffff;
    border-left-color: #45a3ff;
}

.sidebar a.active i {
    filter: brightness(1.2);
}

.sidebar .logout-link {
    color: #ffc2c2;
}

/* =========================
   MAIN CONTENT AREA
========================= */
.main {
    margin-left: 210px;
    min-height: 100vh;
}

/* Top bar */
.topbar {
    min-height: 78px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    gap: 15px;
    padding: 12px 25px;
    background: white;
    border-bottom: 1px solid var(--border);
}

.topbar-title {
    min-width: 0;
}

.topbar-title h2 {
    margin: 0;
    font-size: 19px;
    color: #25372d;
}

.topbar-title h2 i {
    color: var(--green);
    margin-right: 6px;
}

.topbar-title p {
    margin: 5px 0 0;
    font-size: 11px;
    color: var(--muted);
}

/* Centered Sierra Leone emblem */
.topbar-emblem {
    display: flex;
    align-items: center;
    justify-content: center;
}

.topbar-emblem img {
    display: block;
    width: 85px;
    height: 85px;
    object-fit: contain;
}

/* User information */
.user-info {
    justify-self: end;
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
}

.user-avatar {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #e2f3e9;
    color: var(--green);
}

.user-details {
    min-width: 0;
    text-align: right;
}

.user-details strong {
    display: block;
    overflow-wrap: anywhere;
    color: #27352c;
    font-size: 12px;
}

.user-details span {
    display: block;
    margin-top: 4px;
    color: var(--muted);
    font-size: 10px;
}

/* Page content */
.content {
    max-width: 1600px;
    margin: 0 auto;
    padding: 25px;
}

/* Welcome banner */
.welcome {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 25px;
    padding: 25px;
    border-radius: 10px;
    background: linear-gradient(115deg, #10243a, #172f4b);
    color: white;
    box-shadow: 0 6px 18px rgba(16, 36, 58, .12);
}

.welcome-text {
    min-width: 0;
}

.welcome .eyebrow {
    margin: 0 0 8px;
    color: #f7fafc;
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1.2px;
}

.welcome h1 {
    margin: 0 0 10px;
    font-size: 24px;
    line-height: 1.4;
}

.welcome p {
    max-width: 680px;
    margin: 0;
    color: #d8e4ef;
    font-size: 12px;
    line-height: 1.7;
}

.welcome-icon {
    width: 86px;
    height: 86px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255,255,255,.20);
    border-radius: 50%;
    background: rgba(255,255,255,.08);
}

.welcome-icon i {
    font-size: 37px;
    color: #0fecfc;
}

/* Section headings */
.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 25px 0 14px;
}

.section-heading h2 {
    margin: 0;
    font-size: 16px;
    color: #293a30;
}

.section-heading h2 i {
    margin-right: 6px;
    color: #2779bc;
}

.section-heading span {
    color: var(--muted);
    font-size: 10px;
}

/* Statistics cards */
.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
    padding: 18px;
    border: 1px solid #e9eef3;
    border-radius: 9px;
    background: white;
    box-shadow: 0 3px 10px rgba(25,50,75,.035);
    transition: transform .2s, box-shadow .2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 16px rgba(25,50,75,.07);
}

.stat-icon {
    width: 45px;
    height: 45px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 19px;
}

.stat-card h3 {
    margin: 0 0 5px;
    font-size: 24px;
    color: #27372d;
}

.stat-card p {
    margin: 0;
    color: var(--muted);
    font-size: 11px;
    line-height: 1.5;
}

/* Colour palettes */
.green  { background: #e5f5eb; color: #19804c; }
.blue   { background: #e6f1ff; color: #276dc0; }
.purple { background: #f0eaff; color: #7651bc; }
.orange { background: #fff0df; color: #c87920; }
.teal   { background: #e0f5f3; color: #18847b; }
.pink   { background: #ffebf1; color: #c54d79; }
.red    { background: #ffebeb; color: #c74343; }

/* Quick actions */
.quick-links {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 13px;
}

.quick-link {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 15px;
    border: 1px solid #e6ebf1;
    border-radius: 9px;
    background: white;
    color: #2c3b31;
    box-shadow: 0 3px 10px rgba(25,50,75,.025);
    transition: .2s ease;
}

.quick-link:hover {
    transform: translateY(-2px);
    border-color: #b8d6ef;
    box-shadow: 0 7px 16px rgba(25,50,75,.07);
}

.quick-icon {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    font-size: 17px;
}

.quick-text {
    min-width: 0;
    flex: 1;
}

.quick-text strong {
    display: block;
    font-size: 11px;
    line-height: 1.5;
}

.quick-text span {
    display: block;
    margin-top: 4px;
    color: var(--muted);
    font-size: 10px;
}

.quick-link > .fa-chevron-right {
    color: #a6b1aa;
    font-size: 10px;
}

/* Footer */
.footer {
    margin-top: 30px;
    padding: 17px 0 5px;
    border-top: 1px solid #e1e8ef;
    color: #89958d;
    text-align: center;
    font-size: 10px;
    line-height: 1.8;
}

/* Tablet */
@media (max-width: 1100px) {
    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .quick-links {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .content {
        padding: 20px;
    }

    .topbar {
        padding: 12px 17px;
    }
}

/* Small screens */
@media (max-width: 700px) {
    .sidebar {
        width: 65px;
    }

    .brand {
        padding: 13px 4px;
    }

    .brand-logo {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        margin-bottom: 0;
    }

    .brand-logo i {
        font-size: 20px;
    }

    .brand h3,
    .brand p,
    .menu-heading,
    .sidebar a span {
        display: none;
    }

    .sidebar a {
        justify-content: center;
        gap: 0;
        margin: 4px 5px;
        padding: 13px 3px;
    }

    .sidebar a i {
        width: auto;
        font-size: 17px;
    }

    .main {
        margin-left: 65px;
    }

    .topbar {
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        gap: 5px;
        min-height: 65px;
        padding: 8px;
    }

    .topbar-title h2 {
        font-size: 14px;
    }

    .topbar-title h2 i,
    .topbar-title p {
        display: none;
    }

    .topbar-emblem img {
        width: 39px;
        height: 39px;
    }

    .user-info {
        gap: 4px;
    }

    .user-avatar {
        display: none;
    }

    .user-details strong {
        font-size: 9px;
    }

    .user-details span {
        font-size: 8px;
    }

    .content {
        padding: 13px;
    }

    .welcome {
        padding: 18px;
        margin-bottom: 20px;
    }

    .welcome h1 {
        font-size: 19px;
    }

    .welcome p {
        font-size: 11px;
    }

    .welcome-icon {
        display: none;
    }

    .stats,
    .quick-links {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .stat-card {
        padding: 15px;
    }
}
</style>
</head>
<?php require_once __DIR__ . '/includes/appearance.php'; ?>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="brand">
        <div class="brand-logo">
            <i class="fas fa-hands-helping"></i>
        </div>
        <h3>Social Welfare</h3>
        <p>Tonkolili District</p>
    </div>

    <div class="menu-heading">Main Menu</div>

    <?php foreach ($menu as $item): ?>
        <?php
        if ($item[3] === "admin" && !$is_admin) {
            continue;
        }
        ?>
        <a href="<?= htmlspecialchars($item[1]) ?>"
           class="<?= $item[1] === "index.php" ? "active" : "" ?>">
            <i class="fas <?= htmlspecialchars($item[2]) ?>"></i>
            <span><?= htmlspecialchars($item[0]) ?></span>
        </a>
    <?php endforeach; ?>

    <div class="menu-heading">Account</div>

    <a href="auth/logout.php" class="logout-link">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>
</aside>

<!-- MAIN CONTENT -->
<main class="main">

    <header class="topbar">
        <div class="topbar-title">
            <h2><i class="fas fa-th-large"></i> Dashboard</h2>
            <p>Social welfare records and services</p>
        </div>

        <div class="topbar-emblem">
            <img src="assets/images/sierra_leone_logo.png"
                 alt="Sierra Leone national emblem"
                 onerror="this.style.display='none'">
        </div>

        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>

            <div class="user-details">
                <strong><?= htmlspecialchars($full_name) ?></strong>
                <span><?= htmlspecialchars($role) ?></span>
            </div>
        </div>
    </header>

    <div class="content">

        <!-- WELCOME BANNER -->
        <section class="welcome">
            <div class="welcome-text">
                <p class="eyebrow">Tonkolili District • Social Welfare</p>

                <h1>Welcome, <?= htmlspecialchars($full_name) ?>!</h1>

                <p>
                    Welcome to the Tonkolili District Social Welfare
                    Records Management System. Manage records, coordinate
                    services, and keep district information organised.
                    <?php if ($is_admin): ?>
                        You have Administrator dashboard access.
                    <?php elseif ($is_records_officer): ?>
                        Your dashboard is focused on records and information management.
                    <?php else: ?>
                        Your dashboard is focused on social welfare records and services.
                    <?php endif; ?>
                </p>
            </div>

            <div class="welcome-icon">
                <i class="fas fa-hands-helping"></i>
            </div>
        </section>

        <!-- SYSTEM OVERVIEW -->
        <div class="section-heading">
            <h2><i class="fas fa-chart-line"></i> System Overview</h2>
            <span>Current record totals</span>
        </div>

        <section class="stats">

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h3><?= $client_count ?></h3>
                    <p>Total Clients</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <h3><?= $case_count ?></h3>
                    <p>Total Cases</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div>
                    <h3><?= $document_count ?></h3>
                    <p>Documents</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-house-user"></i>
                </div>
                <div>
                    <h3><?= $household_count ?></h3>
                    <p>Households</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon teal">
                    <i class="fas fa-tasks"></i>
                </div>
                <div>
                    <h3><?= $activity_count ?></h3>
                    <p>Activities</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon pink">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h3><?= $appointment_count ?></h3>
                    <p>Appointments</p>
                </div>
            </div>

            <?php if ($is_admin): ?>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div>
                        <h3><?= $user_count ?></h3>
                        <p>System Users</p>
                    </div>
                </div>
            <?php endif; ?>

        </section>

        <!-- QUICK ACTIONS -->
        <div class="section-heading">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <span>Open a module</span>
        </div>

        <section class="quick-links">
            <?php foreach ($quick_actions as $action): ?>
                <a class="quick-link"
                   href="<?= htmlspecialchars($action[1]) ?>">

                    <div class="quick-icon <?= htmlspecialchars($action[3]) ?>">
                        <i class="fas <?= htmlspecialchars($action[2]) ?>"></i>
                    </div>

                    <div class="quick-text">
                        <strong><?= htmlspecialchars($action[0]) ?></strong>
                        <span>Open module</span>
                    </div>

                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endforeach; ?>
        </section>

        <footer class="footer">
            &copy; <?= date("Y") ?>
            Tonkolili District Social Welfare Records Management System
            <br>
            Supporting organised records and social welfare services.
        </footer>

    </div>
</main>

</body>
</html>
