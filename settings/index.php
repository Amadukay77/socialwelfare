 
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once "../includes/db.php";
require_once "../includes/permissions.php";

require_login();

$userId = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'User';
$role = current_user_role();
$isAdmin = function_exists('is_admin') && is_admin();

/* Create a CSRF security token */
if (empty($_SESSION['settings_csrf'])) {
    $_SESSION['settings_csrf'] = bin2hex(random_bytes(32));
}

/* Load saved settings */
$defaults = [
    'system_name' => 'Tonkolili District Social Welfare',
    'system_subtitle' => 'Records Management System',
    'theme' => 'dark',
    'accent_color' => '#2563eb',
    'sidebar_color' => '#171717',
    'login_background' => null,
    'logo_path' => null
];

$result = $conn->query(
    "SELECT * FROM system_settings WHERE setting_id = 1 LIMIT 1"
);

$settings = $defaults;

if ($result && $row = $result->fetch_assoc()) {
    $settings = array_merge($defaults, $row);
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';

if (isset($_GET['upload']) && $_GET['upload'] === 'success') {
    $message = 'Image uploaded and saved successfully.';
}

/* Save system appearance and identity */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'save_settings') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        empty($_SESSION['settings_csrf']) ||
        !hash_equals($_SESSION['settings_csrf'], $token)
    ) {
        $error = 'Security token expired. Refresh the page and try again.';
    } elseif (!$isAdmin) {
        http_response_code(403);
        exit('Administrator permission is required.');
    } else {
        $systemName = trim($_POST['system_name'] ?? '');
        $subtitle = trim($_POST['system_subtitle'] ?? '');
        $theme = $_POST['theme'] ?? 'dark';
        $accent = $_POST['accent_color'] ?? '#2563eb';
        $sidebar = $_POST['sidebar_color'] ?? '#171717';

        if ($systemName === '' || strlen($systemName) > 150) {
            $error = 'Enter a system name of no more than 150 characters.';
        } elseif (strlen($subtitle) > 200) {
            $error = 'The subtitle must not exceed 200 characters.';
        } elseif (!in_array($theme, ['dark', 'light', 'blue'], true)) {
            $error = 'Choose a valid theme.';
        } elseif (
            !preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ||
            !preg_match('/^#[0-9a-fA-F]{6}$/', $sidebar)
        ) {
            $error = 'Choose valid colours.';
        } else {
            $stmt = $conn->prepare(
                "UPDATE system_settings
                 SET system_name = ?, system_subtitle = ?,
                     theme = ?, accent_color = ?, sidebar_color = ?
                 WHERE setting_id = 1"
            );

            if ($stmt) {
                $stmt->bind_param(
                    "sssss",
                    $systemName,
                    $subtitle,
                    $theme,
                    $accent,
                    $sidebar
                );

                if ($stmt->execute()) {
                    $_SESSION['settings_csrf'] = bin2hex(random_bytes(32));
                    header("Location: index.php?saved=1");
                    exit;
                } else {
                    $error = 'Could not save the settings.';
                }

                $stmt->close();
            } else {
                $error = 'Could not prepare the settings update.';
            }
        }
    }
}

if (isset($_GET['saved'])) {
    $message = 'Settings saved successfully.';
}

/* Reload values after any update */
$result = $conn->query(
    "SELECT * FROM system_settings WHERE setting_id = 1 LIMIT 1"
);

if ($result && $row = $result->fetch_assoc()) {
    $settings = array_merge($defaults, $row);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Settings</title>
<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
:root {
    --accent: <?= e($settings['accent_color']) ?>;
    --sidebar: <?= e($settings['sidebar_color']) ?>;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: <?= $settings['theme'] === 'light' ? '#f3f5f9' : ($settings['theme'] === 'blue' ? '#eaf2ff' : '#101318') ?>;
    color: <?= $settings['theme'] === 'light' || $settings['theme'] === 'blue' ? '#202938' : '#edf0f5' ?>;
}
.sidebar {
    width: 235px;
    position: fixed;
    inset: 0 auto 0 0;
    background: var(--sidebar);
    color: white;
    padding: 24px 15px;
}
.brand { font-size: 18px; font-weight: bold; margin: 0 8px 25px; }
.sidebar a {
    display: block;
    padding: 12px;
    margin: 5px 0;
    border-radius: 7px;
    color: #e5e7eb;
    text-decoration: none;
}
.sidebar a i { width: 24px; }
.sidebar a:hover, .sidebar a.active {
    background: #30343b;
    border-left: 3px solid var(--accent);
}
main { margin-left: 235px; padding: 28px; max-width: 1250px; }
h1 { margin-top: 0; }
.muted { color: #929baa; }
.card {
    background: <?= $settings['theme'] === 'dark' ? '#1b2028' : '#ffffff' ?>;
    border: 1px solid <?= $settings['theme'] === 'dark' ? '#303744' : '#dfe4ec' ?>;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 20px;
}
.grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
label { display: block; font-weight: bold; margin: 13px 0 7px; }
input, select {
    width: 100%; padding: 11px; border: 1px solid #bbc3ce;
    border-radius: 7px; font: inherit; background: white; color: #202938;
}
input[type="color"] { height: 45px; padding: 4px; }
button {
    margin-top: 15px; padding: 11px 17px; border: 0;
    border-radius: 7px; background: var(--accent);
    color: white; font-weight: bold; cursor: pointer;
}
button:hover { opacity: .9; }
.notice { padding: 12px; border-radius: 7px; margin-bottom: 15px; }
.success { background: #d9fbe5; color: #14532d; }
.failure { background: #fee2e2; color: #991b1b; }
.preview {
    max-width: 100%; max-height: 230px; object-fit: contain;
    display: block; margin-top: 12px; border-radius: 8px;
}
.upload-form { border-top: 1px solid #858b9633; padding-top: 12px; margin-top: 12px; }
@media (max-width: 800px) {
    .sidebar { width: 100%; position: relative; }
    main { margin-left: 0; padding: 16px; }
    .grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="brand"><i class="fas fa-hands-helping"></i> SOCIAL WELFARE</div>
    <a href="../index.php"><i class="fas fa-tachometer-alt" style="color:#60a5fa"></i> Dashboard</a>
    <a href="../clients/index.php"><i class="fas fa-users" style="color:#4ade80"></i> Clients</a>
    <a href="../cases/index.php"><i class="fas fa-folder-open" style="color:#fbbf24"></i> Cases</a>
    <a href="../documents/index.php"><i class="fas fa-file-alt" style="color:#c084fc"></i> Documents</a>
    <a href="../social_workers/index.php"><i class="fas fa-user-tie" style="color:#fb923c"></i> Social Workers</a>
    <a href="../reports/index.php"><i class="fas fa-chart-bar" style="color:#2dd4bf"></i> Reports</a>
    <a class="active" href="index.php"><i class="fas fa-cog" style="color:#f87171"></i> Settings</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt" style="color:#d1d5db"></i> Logout</a>
</aside>

<main>
    <h1><i class="fas fa-sliders-h"></i> System Settings</h1>
    <p class="muted">Welcome, <?= e($username) ?> — <?= e($role) ?></p>

    <?php if ($message): ?>
        <div class="notice success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="notice failure"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <section class="card">
        <h2><i class="fas fa-desktop"></i> System identity and appearance</h2>
        <p class="muted">Changes are saved to the system settings database.</p>

        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['settings_csrf']) ?>">

            <div class="grid">
                <div>
                    <label for="system_name">System name</label>
                    <input id="system_name" name="system_name" maxlength="150"
                           required value="<?= e($settings['system_name']) ?>">
                </div>
                <div>
                    <label for="system_subtitle">System subtitle</label>
                    <input id="system_subtitle" name="system_subtitle" maxlength="200"
                           value="<?= e($settings['system_subtitle']) ?>">
                </div>
                <div>
                    <label for="theme">Theme</label>
                    <select id="theme" name="theme">
                        <option value="dark" <?= $settings['theme'] === 'dark' ? 'selected' : '' ?>>Dark</option>
                        <option value="light" <?= $settings['theme'] === 'light' ? 'selected' : '' ?>>Light</option>
                        <option value="blue" <?= $settings['theme'] === 'blue' ? 'selected' : '' ?>>Blue</option>
                    </select>
                </div>
                <div>
                    <label for="accent_color">Accent colour</label>
                    <input id="accent_color" type="color" name="accent_color"
                           value="<?= e($settings['accent_color']) ?>">
                </div>
                <div>
                    <label for="sidebar_color">Sidebar colour</label>
                    <input id="sidebar_color" type="color" name="sidebar_color"
                           value="<?= e($settings['sidebar_color']) ?>">
                </div>
            </div>
            <button type="submit"><i class="fas fa-save"></i> Save settings</button>
        </form>
    </section>

    <section class="card">
        <h2><i class="fas fa-images"></i> Login background</h2>
        <p class="muted">Upload a JPG, PNG, or WebP image (maximum 5 MB).</p>

        <?php if (!empty($settings['login_background'])): ?>
            <img class="preview"
                 src="../<?= e($settings['login_background']) ?>"
                 alt="Current login background preview">
        <?php endif; ?>

        <form class="upload-form" method="POST" action="upload_image.php"
              enctype="multipart/form-data">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['settings_csrf']) ?>">
            <input type="hidden" name="image_type" value="login_background">
            <label for="login_background_file">Choose background image</label>
            <input id="login_background_file" type="file" name="image_file"
                   accept="image/jpeg,image/png,image/webp" required>
            <button type="submit"><i class="fas fa-upload"></i> Upload background</button>
        </form>
    </section>

    <section class="card">
        <h2><i class="fas fa-image"></i> System logo</h2>

        <?php if (!empty($settings['logo_path'])): ?>
            <img class="preview" src="../<?= e($settings['logo_path']) ?>"
                 alt="Current system logo preview">
        <?php endif; ?>

        <form class="upload-form" method="POST" action="upload_image.php"
              enctype="multipart/form-data">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['settings_csrf']) ?>">
            <input type="hidden" name="image_type" value="logo_path">
            <label for="logo_file">Choose logo image</label>
            <input id="logo_file" type="file" name="image_file"
                   accept="image/jpeg,image/png,image/webp" required>
            <button type="submit"><i class="fas fa-upload"></i> Upload logo</button>
        </form>
    </section>
    <?php else: ?>
        <section class="card">
            Settings changes and image uploads are available to administrators only.
        </section>
    <?php endif; ?>

    <section class="card">
        <h2><i class="fas fa-info-circle"></i> Account and system information</h2>
        <p><strong>Username:</strong> <?= e($username) ?></p>
        <p><strong>Role:</strong> <?= e($role) ?></p>
        <p><strong>System:</strong> Tonkolili District Social Welfare Records Management System</p>
        <p class="muted">Keep your administrator login details secure.</p>
    </section>
</main>
</body>
</html>
