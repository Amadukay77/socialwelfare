```php
<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "Administrator") {
    http_response_code(403);
    exit("Access denied. Only Administrators can edit user accounts.");
}

$user_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$user_id || $user_id < 1) {
    header("Location: index.php?error=Invalid user ID.");
    exit;
}

$stmt = $conn->prepare(
    "SELECT user_id, full_name, username, role, status
     FROM users WHERE user_id = ? LIMIT 1"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: index.php?error=User not found.");
    exit;
}

$allowed_roles = [
    "Administrator",
    "Social Worker",
    "Records Officer"
];

$error = "";
$full_name = $user["full_name"];
$username = $user["username"];
$role = $user["role"];
$status = $user["status"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $role = $_POST["role"] ?? "";
    $status = $_POST["status"] ?? "";
    $new_password = $_POST["new_password"] ?? "";

    if ($full_name === "" || $username === "") {
        $error = "Full name and username are required.";
    } elseif (strlen($username) > 100) {
        $error = "Username cannot exceed 100 characters.";
    } elseif (!in_array($role, $allowed_roles, true)) {
        $error = "Please select a valid role.";
    } elseif (!in_array($status, ["Active", "Inactive"], true)) {
        $error = "Please select a valid account status.";
    } elseif ($new_password !== "" && strlen($new_password) < 8) {
        $error = "The new password must contain at least 8 characters.";
    } elseif (
        $user_id === (int) $_SESSION["user_id"] &&
        ($status !== "Active" || $role !== "Administrator")
    ) {
        $error = "You cannot deactivate your own account or remove your Administrator role.";
    }

    if ($error === "") {
        $check = $conn->prepare(
            "SELECT user_id FROM users
             WHERE username = ? AND user_id != ? LIMIT 1"
        );
        $check->bind_param("si", $username, $user_id);
        $check->execute();
        $duplicate = $check->get_result()->num_rows > 0;
        $check->close();

        if ($duplicate) {
            $error = "That username is already in use.";
        }
    }

    /*
     * Prevent removing or deactivating the last active Administrator.
     */
    if ($error === "" && $user["role"] === "Administrator") {
        $removing_admin =
            $role !== "Administrator" || $status !== "Active";

        if ($removing_admin) {
            $admin_check = $conn->prepare(
                "SELECT COUNT(*) AS total
                 FROM users
                 WHERE role = 'Administrator'
                   AND status = 'Active'
                   AND user_id != ?"
            );
            $admin_check->bind_param("i", $user_id);
            $admin_check->execute();
            $admin_count = (int) $admin_check
                ->get_result()->fetch_assoc()["total"];
            $admin_check->close();

            if ($admin_count < 1) {
                $error = "You cannot remove or deactivate the last active Administrator.";
            }
        }
    }

    if ($error === "") {
        if ($new_password !== "") {
            $hashed_password = password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );

            $update = $conn->prepare(
                "UPDATE users
                 SET full_name = ?, username = ?, role = ?,
                     status = ?, password = ?
                 WHERE user_id = ?"
            );
            $update->bind_param(
                "sssssi",
                $full_name,
                $username,
                $role,
                $status,
                $hashed_password,
                $user_id
            );
        } else {
            $update = $conn->prepare(
                "UPDATE users
                 SET full_name = ?, username = ?, role = ?, status = ?
                 WHERE user_id = ?"
            );
            $update->bind_param(
                "ssssi",
                $full_name,
                $username,
                $role,
                $status,
                $user_id
            );
        }

        if ($update->execute()) {
            $update->close();

            if ($user_id === (int) $_SESSION["user_id"]) {
                $_SESSION["full_name"] = $full_name;
                $_SESSION["username"] = $username;
                $_SESSION["role"] = $role;
            }

            header("Location: index.php?success=User account updated successfully.");
            exit;
        } else {
            $error = "Unable to update the account. Please try again.";
            $update->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User | Tonkolili District Social Welfare</title>
<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#f4f5f7;color:#252525}
.sidebar{position:fixed;left:0;top:0;width:235px;height:100vh;background:#171717;color:white;padding:22px 14px;overflow-y:auto}
.brand{font-size:17px;font-weight:bold;margin:0 8px 28px;line-height:1.6}
.brand small{display:block;font-size:12px;color:#bbb;font-weight:normal}
.nav a{display:flex;align-items:center;gap:12px;color:#ddd;text-decoration:none;padding:12px 13px;border-radius:5px;margin:4px 0;font-size:14px}
.nav a i{width:18px;text-align:center}
.nav a:hover,.nav a.active{background:#292929;color:white}
.nav a.active{border-left:4px solid #e53935}
.nav .users-icon{color:#bb86fc}
.nav .dashboard-icon{color:#4dabf7}
.nav .clients-icon{color:#66bb6a}
.nav .cases-icon{color:#ffb74d}
.nav .documents-icon{color:#4dd0e1}
.nav .workers-icon{color:#f06292}
.nav .households-icon{color:#aed581}
.nav .activities-icon{color:#ffd54f}
.nav .appointments-icon{color:#90caf9}
.nav .reports-icon{color:#ce93d8}
.nav .settings-icon{color:#b0bec5}
.nav .logout-icon{color:#ef5350}
.main{margin-left:235px;padding:28px}
.topbar{background:#fff;padding:18px 22px;border-radius:8px;display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:24px;box-shadow:0 2px 8px #00000008}
.topbar h2{margin:0;font-size:21px}
.topbar p{margin:6px 0 0;color:#777;font-size:13px}
.panel{background:white;border-radius:9px;padding:25px;max-width:850px;box-shadow:0 2px 10px #00000008}
.panel h3{margin-top:0;margin-bottom:6px}
.muted{color:#777;font-size:13px;margin-top:0;margin-bottom:22px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.field label{display:block;font-size:13px;font-weight:bold;margin-bottom:8px}
.field input,.field select{width:100%;padding:12px;border:1px solid #d5d8dc;border-radius:5px;font-size:14px;background:white}
.field input:focus,.field select:focus{outline:2px solid #e5393530;border-color:#e53935}
.full{grid-column:1/-1}
.note{background:#f7f7f7;padding:12px;border-radius:5px;color:#555;font-size:13px;line-height:1.5}
.error{background:#ffebee;color:#b71c1c;padding:12px;border-radius:5px;margin-bottom:18px;font-size:14px}
.actions{display:flex;gap:10px;margin-top:22px;flex-wrap:wrap}
.btn{border:0;border-radius:5px;padding:12px 17px;font-size:14px;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.primary{background:#e53935;color:white}
.secondary{background:#e9ecef;color:#333}
@media(max-width:800px){.sidebar{position:relative;width:100%;height:auto}.main{margin-left:0;padding:15px}.form-grid{grid-template-columns:1fr}.full{grid-column:auto}.topbar{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="brand">SOCIAL WELFARE<small>Tonkolili District</small></div>
    <nav class="nav">
        <a href="../index.php"><i class="fas fa-tachometer-alt dashboard-icon"></i>Dashboard</a>
        <a href="../clients/index.php"><i class="fas fa-users clients-icon"></i>Clients</a>
        <a href="../cases/index.php"><i class="fas fa-folder-open cases-icon"></i>Cases</a>
        <a href="../documents/index.php"><i class="fas fa-file-alt documents-icon"></i>Documents</a>
        <a href="../social_workers/index.php"><i class="fas fa-user-tie workers-icon"></i>Social Workers</a>
        <a href="../households/index.php"><i class="fas fa-home households-icon"></i>Households</a>
        <a href="../activities/index.php"><i class="fas fa-calendar-check activities-icon"></i>Activities</a>
        <a href="../appointments/index.php"><i class="fas fa-calendar-alt appointments-icon"></i>Appointments</a>
        <a href="index.php" class="active"><i class="fas fa-user-cog users-icon"></i>Users</a>
        <a href="../reports/index.php"><i class="fas fa-chart-bar reports-icon"></i>Reports</a>
        <a href="../settings/index.php"><i class="fas fa-cog settings-icon"></i>Settings</a>
        <a href="../auth/logout.php"><i class="fas fa-sign-out-alt logout-icon"></i>Logout</a>
    </nav>
</aside>

<main class="main">
    <header class="topbar">
        <div>
            <h2><i class="fas fa-user-edit"></i> Edit User Account</h2>
            <p>Update account details and system access.</p>
        </div>
        <div><i class="fas fa-user-shield"></i> Administrator</div>
    </header>

    <section class="panel">
        <h3>Edit: <?= htmlspecialchars($user["full_name"]) ?></h3>
        <p class="muted">User ID: <?= (int)$user["user_id"] ?></p>

        <?php if ($error !== ""): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="field">
                    <label for="full_name">Full Name</label>
                    <input id="full_name" name="full_name" required maxlength="150"
                           value="<?= htmlspecialchars($full_name) ?>">
                </div>

                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" required maxlength="100"
                           value="<?= htmlspecialchars($username) ?>">
                </div>

                <div class="field">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <?php foreach ($allowed_roles as $allowed_role): ?>
                            <option value="<?= htmlspecialchars($allowed_role) ?>"
                                <?= $role === $allowed_role ? "selected" : "" ?>>
                                <?= htmlspecialchars($allowed_role) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="status">Account Status</label>
                    <select id="status" name="status" required>
                        <option value="Active" <?= $status === "Active" ? "selected" : "" ?>>Active</option>
                        <option value="Inactive" <?= $status === "Inactive" ? "selected" : "" ?>>Inactive</option>
                    </select>
                </div>

                <div class="field full">
                    <label for="new_password">New Password (Optional)</label>
                    <input type="password" id="new_password" name="new_password"
                           minlength="8" autocomplete="new-password">
                    <p class="muted">Leave blank to keep the current password.</p>
                </div>

                <div class="field full note">
                    <i class="fas fa-shield-alt"></i>
                    You cannot deactivate your own Administrator account or remove the last active Administrator.
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="index.php" class="btn secondary">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
```