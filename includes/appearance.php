
<?php
/*
 * Shared appearance settings.
 * Include this file inside the <head> section of each page,
 * after that page's existing CSS.
 */

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/db.php';
}

$appearanceDefaults = [
    'system_name' => 'Tonkolili District Social Welfare',
    'system_subtitle' => 'Records Management System',
    'theme' => 'dark',
    'accent_color' => '#2563eb',
    'sidebar_color' => '#171717',
    'login_background' => null,
    'logo_path' => null
];

$appearanceSettings = $appearanceDefaults;

$appearanceResult = $conn->query(
    "SELECT system_name, system_subtitle, theme, accent_color,
            sidebar_color, login_background, logo_path
     FROM system_settings
     WHERE setting_id = 1 LIMIT 1"
);

if ($appearanceResult && $appearanceRow = $appearanceResult->fetch_assoc()) {
    $appearanceSettings = array_merge(
        $appearanceDefaults,
        $appearanceRow
    );
}

$appearanceTheme = $appearanceSettings['theme'];

if (!in_array($appearanceTheme, ['dark', 'light', 'blue'], true)) {
    $appearanceTheme = 'dark';
}

$appearanceBg = '#101318';
$appearanceText = '#edf0f5';
$appearanceCard = '#1b2028';
$appearanceBorder = '#303744';

if ($appearanceTheme === 'light') {
    $appearanceBg = '#f3f5f9';
    $appearanceText = '#202938';
    $appearanceCard = '#ffffff';
    $appearanceBorder = '#dfe4ec';
} elseif ($appearanceTheme === 'blue') {
    $appearanceBg = '#eaf2ff';
    $appearanceText = '#202938';
    $appearanceCard = '#ffffff';
    $appearanceBorder = '#cbdcf7';
}

$appearanceAccent = $appearanceSettings['accent_color'];
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $appearanceAccent)) {
    $appearanceAccent = '#2563eb';
}

$appearanceSidebar = $appearanceSettings['sidebar_color'];
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $appearanceSidebar)) {
    $appearanceSidebar = '#171717';
}
?>

<style id="shared-system-appearance">
:root {
    --system-accent: <?= htmlspecialchars($appearanceAccent, ENT_QUOTES, 'UTF-8') ?>;
    --system-sidebar: <?= htmlspecialchars($appearanceSidebar, ENT_QUOTES, 'UTF-8') ?>;
    --system-page-bg: <?= $appearanceBg ?>;
    --system-text: <?= $appearanceText ?>;
    --system-card-bg: <?= $appearanceCard ?>;
    --system-border: <?= $appearanceBorder ?>;
}

body {
    background-color: var(--system-page-bg) !important;
    color: var(--system-text);
}

.sidebar, .main-sidebar, aside.sidebar {
    background-color: var(--system-sidebar) !important;
}

.card, .stat-card, .content-card, .panel, .settings-card {
    background-color: var(--system-card-bg);
    border-color: var(--system-border);
    color: var(--system-text);
}

.btn-primary, button.primary, .active, .nav-link.active {
    --bs-btn-bg: var(--system-accent);
}

a.active, .sidebar a.active, .main-sidebar a.active {
    border-color: var(--system-accent);
}

input, select, textarea {
    color: #202938;
}

</style>
