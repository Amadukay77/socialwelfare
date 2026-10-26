 
<?php

// Start the session only if it has not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Return the role of the logged-in user.
 */
function current_user_role()
{
    return trim($_SESSION["role"] ?? "");
}

/**
 * Role checks.
 */
function is_admin()
{
    return current_user_role() === "Administrator";
}

function is_social_worker()
{
    return current_user_role() === "Social Worker";
}

function is_records_officer()
{
    return current_user_role() === "Records Officer";
}

/**
 * Require a logged-in user.
 */
function require_login()
{
    if (empty($_SESSION["user_id"])) {
        header("Location: /social_welfare/auth/login.php");
        exit;
    }
}

/**
 * Central permission list.
 *
 * Administrators have access to all permissions.
 * Other roles receive only the permissions listed below.
 */
function has_permission($permission)
{
    $role = current_user_role();

    if ($role === "Administrator") {
        return true;
    }

    $social_worker_permissions = [
        "dashboard",

        "clients_view",
        "clients_add",
        "clients_edit",

        "cases_view",
        "cases_add",
        "cases_edit",

        "documents_view",
        "documents_add",

        "households_view",
        "households_add",
        "households_edit",

        "activities_view",
        "activities_add",
        "activities_edit",

        "appointments_view",
        "appointments_add",
        "appointments_edit",

        "reports_view"
    ];

    $records_officer_permissions = [
        "dashboard",

        "clients_view",
        "clients_add",
        "clients_edit",

        "cases_view",

        "documents_view",
        "documents_add",

        "households_view",
        "households_add",
        "households_edit",

        "activities_view",

        "appointments_view",

        "reports_view"
    ];

    if ($role === "Social Worker") {
        return in_array(
            $permission,
            $social_worker_permissions,
            true
        );
    }

    if ($role === "Records Officer") {
        return in_array(
            $permission,
            $records_officer_permissions,
            true
        );
    }

    // Unknown or unrecognized roles are denied.
    return false;
}

/**
 * Require a specific permission before showing a page.
 */
function require_permission($permission)
{
    require_login();

    if (!has_permission($permission)) {
        http_response_code(403);

        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Access Denied</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f4f5f7;
                    text-align: center;
                    padding: 70px 20px;
                    color: #222;
                }
                .box {
                    max-width: 500px;
                    margin: auto;
                    background: #fff;
                    padding: 35px;
                    border-radius: 10px;
                    box-shadow: 0 4px 18px rgba(0,0,0,.08);
                }
                h1 { color: #c62828; }
                a {
                    display: inline-block;
                    margin-top: 15px;
                    color: #c62828;
                    text-decoration: none;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class='box'>
                <h1>403 - Access Denied</h1>
                <p>Your account does not have permission to access this page.</p>
                <a href='/social_welfare/index.php'>Return to Dashboard</a>
            </div>
        </body>
        </html>";

        exit;
    }
}

/**
 * Require Administrator access.
 */

function require_admin() {
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Denied</title>
            <style>
                * { box-sizing: border-box; }

                body {
                    margin: 0;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    font-family: Arial, sans-serif;
                    background: #f0f3f8;
                    color: #17243a;
                }

                .denied-card {
                    width: 100%;
                    max-width: 650px;
                    padding: 55px 35px;
                    text-align: center;
                    background: #ffffff;
                    border-radius: 18px;
                    box-shadow: 0 15px 45px rgba(0,0,0,0.12);
                    border-top: 7px solid #dc3545;
                }

                .denied-icon {
                    font-size: 75px;
                    color: #dc3545;
                    margin-bottom: 20px;
                }

                h1 {
                    margin: 0 0 18px;
                    font-size: 38px;
                    color: #c82333;
                }

                .error-code {
                    display: inline-block;
                    margin-bottom: 20px;
                    padding: 8px 18px;
                    border-radius: 30px;
                    background: #fde8e9;
                    color: #c82333;
                    font-size: 18px;
                    font-weight: bold;
                }

                p {
                    margin: 0 auto 28px;
                    max-width: 480px;
                    font-size: 17px;
                    line-height: 1.7;
                    color: #5b6573;
                }

                a {
                    display: inline-block;
                    padding: 13px 25px;
                    border-radius: 8px;
                    background: #17243a;
                    color: white;
                    text-decoration: none;
                    font-size: 16px;
                    font-weight: bold;
                }

                a:hover { background: #29476b; }

                @media (max-width: 480px) {
                    .denied-card { padding: 40px 20px; }
                    h1 { font-size: 29px; }
                    .denied-icon { font-size: 60px; }
                }
            </style>
        </head>
        <body>
            <div class="denied-card">
                <div class="denied-icon">&#9888;</div>
                <div class="error-code">ERROR 403</div>
                <h1>Access Denied</h1>
                <p>
                    You do not have permission to perform this action.
                    Please contact your system administrator if you
                    believe you should have access.
                </p>
                <a href="/social_welfare/index.php">Return to Dashboard</a>
            </div>
        </body>
        </html>';
        exit;
    }
}
