
<?php
session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "Administrator") {
    http_response_code(403);
    exit("Access denied. Only the Administrator can create user accounts.");
}

$errors = [];

$full_name = "";
$username = "";
$role = "Social Worker";
$status = "Active";

$allowed_roles = [
    "Administrator",
    "Social Worker",
    "Records Officer"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $role = trim($_POST["role"] ?? "");
    $status = trim($_POST["status"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($full_name === "") {
        $errors[] = "Full name is required.";
    }

    if ($username === "") {
        $errors[] = "Username is required.";
    } elseif (strlen($username) > 100) {
        $errors[] = "Username cannot exceed 100 characters.";
    }

    if (!in_array($role, $allowed_roles, true)) {
        $errors[] = "Please select a valid role.";
    }

    if (!in_array($status, ["Active", "Inactive"], true)) {
        $errors[] = "Please select a valid account status.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "The passwords do not match.";
    }

    if (!$errors) {
        $check = $conn->prepare(
            "SELECT user_id FROM users WHERE username = ? LIMIT 1"
        );

        if (!$check) {
            $errors[] = "Could not check the username. Please try again.";
        } else {
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $errors[] = "That username already exists. Choose another.";
            }

            $check->close();
        }
    }

    if (!$errors) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $insert = $conn->prepare(
            "INSERT INTO users (full_name, username, password, role, status)
             VALUES (?, ?, ?, ?, ?)"
        );

        if (!$insert) {
            $errors[] = "Could not prepare the account. Please try again.";
        } else {
            $insert->bind_param(
                "sssss",
                $full_name,
                $username,
                $password_hash,
                $role,
                $status
            );

            if ($insert->execute()) {
                $insert->close();
                header("Location: index.php?success=" . urlencode("User account created successfully."));
                exit;
            }

            if ($insert->errno === 1062) {
                $errors[] = "That username already exists. Choose another.";
            } else {
                $errors[] = "Unable to create the account. Please check the database and try again.";
            }

            $insert->close();
        }
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add User | Tonkolili Social Welfare</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f4f5f7;color:#222}
.sidebar{position:fixed;inset:0 auto 0 0;width:245px;background:#171717;color:#fff}
.brand{text-align:center;padding:25px 18px;border-bottom:1px solid #333}
.brand i{font-size:30px;margin-bottom:10px}
.brand h3{font-size:17px;margin:0 0 5px}
.brand p{font-size:12px;color:#aaa;margin:0}
.menu-title{padding:20px 20px 8px;color:#888;font-size:11px;text-transform:uppercase;letter-spacing:1px}
.menu a{display:block;padding:13px 20px;color:#ccc;text-decoration:none;font-size:14px;border-left:3px solid transparent}
.menu a:hover{background:#252525;color:#fff}
.menu a.active{background:#252525;border-left-color:#e53935;color:#fff}
.menu a i{width:25px;margin-right:8px}
.main{margin-left:245px}
.topbar{height:70px;background:white;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;padding:0 28px}
.topbar h2{font-size:20px;margin:0}
.admin{font-size:13px;color:#555}
.content{padding:28px}
.heading{margin-bottom:22px}
.heading h1{font-size:25px;margin:0 0 7px}
.heading p{font-size:14px;color:#777;margin:0}
.panel{background:#fff;border:1px solid #e5e5e5;border-radius:9px;max-width:850px;padding:25px}
.panel h3{font-size:17px;margin:0 0 22px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.field{margin-bottom:18px}
.field label{display:block;font-size:13px;font-weight:bold;margin-bottom:8px}
.field input,.field select{width:100%;padding:12px;border:1px solid #d8d8d8;border-radius:5px;font-size:14px;background:#fff}
.field input:focus,.field select:focus{outline:2px solid #e5393533;border-color:#e53935}
.help{font-size:11px;color:#777;display:block;margin-top:6px}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}
.button{border:0;border-radius:5px;padding:12px 17px;background:#e53935;color:white;text-decoration:none;font-size:13px;font-weight:bold;cursor:pointer}
.button:hover{background:#c62828}
.button.secondary{background:#333}
.notice{padding:12px 15px;border-radius:5px;margin-bottom:18px;font-size:13px}
.notice.error{background:#ffebee;color:#b71c1c}
.notice ul{margin:8px 0 0;padding-left:20px}
.footer{text-align:center;padding:24px;color:#888;font-size:12px}
@media(max-width:900px){.sidebar{width:205px}.main{margin-left:205px}.content{padding:18px}}
@media(max-width:600px){.sidebar{position:relative;width:100%}.main{margin-left:0}.content{padding:14px}.form-grid{grid-template-columns:1fr}.topbar{padding:0 15px}.panel{padding:18px}}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="brand">
        <i class="fas fa-landmark"></i>
        <h3>Social Welfare</h3>
        <p>Tonkolili District</p>
    </div>
    <div class="menu-title">Main Menu</div>
    <nav class="menu">
        <a href="../index.php"><i class="fas fa-tachometer-alt" style="color:#42a5f5"></i>Dashboard</a>
        <a href="../clients/index.php"><i class="fas fa-users" style="color:#66bb6a"></i>Clients</a>
        <a href="../cases/index.php"><i class="fas fa-folder-open" style="color:#ffca28"></i>Cases</a>
        <a href="../documents/index.php"><i class="fas fa-file-alt" style="color:#ab47bc"></i>Documents</a>
        <a href="../social_workers/index.php"><i class="fas fa-user-tie" style="color:#29b6f6"></i>Social Workers</a>
        <a href="../households/index.php"><i class="fas fa-home" style="color:#ffa726"></i>Households</a>
        <a href="../activities/index.php"><i class="fas fa-calendar-check" style="color:#26a69a"></i>Activities</a>
        <a href="../appointments/index.php"><i class="fas fa-calendar-alt" style="color:#ef5350"></i>Appointments</a>
    </nav>
    <div class="menu-title">Management</div>
    <nav class="menu">
        <a href="../reports/"><i class="fas fa-chart-bar" style="color:#ec407a"></i>Reports</a>
        <a href="index.php" class="active"><i class="fas fa-user-cog" style="color:#90a4ae"></i>Users</a>
        <a href="../settings/"><i class="fas fa-cog" style="color:#b0bec5"></i>Settings</a>
        <a href="../auth/logout.php"><i class="fas fa-sign-out-alt" style="color:#ef5350"></i>Logout</a>
    </nav>
</aside>

<main class="main">
    <header class="topbar">
        <h2>Add User</h2>
        <div class="admin"><i class="fas fa-user-shield"></i> <?php echo e($_SESSION["full_name"] ?? "Administrator"); ?></div>
    </header>

    <section class="content">
        <div class="heading">
            <h1>Create User Account</h1>
            <p>Create an account for an administrator or staff member.</p>
        </div>

        <div class="panel">
            <h3><i class="fas fa-user-plus"></i> Account Details</h3>

            <?php if ($errors): ?>
                <div class="notice error">
                    <strong>Please correct the following:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="add.php" autocomplete="off">
                <div class="form-grid">
                    <div class="field">
                        <label for="full_name">Full Name *</label>
                        <input id="full_name" name="full_name" maxlength="150"
                               value="<?php echo e($full_name); ?>" required>
                    </div>

                    <div class="field">
                        <label for="username">Username *</label>
                        <input id="username" name="username" maxlength="100"
                               value="<?php echo e($username); ?>" required>
                    </div>

                    <div class="field">
                        <label for="role">User Role *</label>
                        <select id="role" name="role" required>
                            <?php foreach ($allowed_roles as $allowed_role): ?>
                                <option value="<?php echo e($allowed_role); ?>"
                                    <?php echo $role === $allowed_role ? "selected" : ""; ?>>
                                    <?php echo e($allowed_role); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="status">Account Status *</label>
                        <select id="status" name="status" required>
                            <option value="Active" <?php echo $status === "Active" ? "selected" : ""; ?>>Active</option>
                            <option value="Inactive" <?php echo $status === "Inactive" ? "selected" : ""; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password"
                               minlength="8" autocomplete="new-password" required>
                        <span class="help">Use at least 8 characters.</span>
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password"
                               name="confirm_password" minlength="8"
                               autocomplete="new-password" required>
                    </div>
                </div>

                <div class="actions">
                    <button class="button" type="submit">
                        <i class="fas fa-save"></i> Create Account
                    </button>
                    <a class="button secondary" href="index.php">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
            </form>
        </div>

        <div class="footer">
            Tonkolili District Social Welfare Records Management System
            <br>© <?php echo date("Y"); ?> All Rights Reserved
        </div>
    </section>
</main>
</body>
</html>
