<?php

session_start();

require_once "../includes/db.php";

/* Make sure user came through Forgot Password */
if (!isset($_SESSION["reset_user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["reset_user_id"];

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (empty($password) || empty($confirm_password)) {

        $error = "Please enter and confirm your new password.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        /* Securely hash the new password */
        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare(
            "UPDATE users
             SET password = ?
             WHERE user_id = ?"
        );

        $stmt->bind_param(
            "si",
            $hashed_password,
            $user_id
        );

        if ($stmt->execute()) {

            /* Remove password-reset session */
            unset($_SESSION["reset_user_id"]);
            unset($_SESSION["reset_username"]);

            $success =
                "Your password has been changed successfully.";

        } else {

            $error =
                "Unable to change your password. Please try again.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Reset Password - Social Welfare System
    </title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            min-height: 100vh;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    rgba(0,95,65,0.78),
                    rgba(0,55,45,0.82)
                ),
                url("../assets/images/social-welfare-bg.jpg");

            background-size: cover;

            background-position: center;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;
        }

        .container {

            width: 430px;

            max-width: 100%;

            background:
                rgba(255,255,255,0.97);

            padding: 38px;

            border-radius: 18px;

            box-shadow:
                0 15px 45px
                rgba(0,0,0,0.30);
        }

        .icon {

            width: 75px;

            height: 75px;

            margin: auto;

            border-radius: 50%;

            background:
                linear-gradient(
                    135deg,
                    #198754,
                    #0f5132
                );

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;
        }

        h2 {

            text-align: center;

            color: #198754;

            margin:
                18px 0 6px;
        }

        .subtitle {

            text-align: center;

            color: #777;

            font-size: 13px;

            margin-bottom: 25px;
        }

        .success {

            background: #eaf7ed;

            color: #146c43;

            border:
                1px solid #badbcc;

            padding: 13px;

            border-radius: 8px;

            text-align: center;

            font-size: 13px;

            margin-bottom: 20px;
        }

        .error {

            background: #fdecec;

            color: #842029;

            border:
                1px solid #f5c2c7;

            padding: 13px;

            border-radius: 8px;

            font-size: 13px;

            margin-bottom: 20px;
        }

        label {

            display: block;

            font-size: 13px;

            font-weight: bold;

            margin-bottom: 7px;

            color: #333;
        }

        .form-group {

            margin-bottom: 18px;
        }

        .input-wrapper {

            position: relative;
        }

        .input-wrapper > i {

            position: absolute;

            left: 13px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #198754;
        }

        input {

            width: 100%;

            padding:
                13px 45px 13px 40px;

            border:
                1px solid #ccc;

            border-radius: 8px;

            font-size: 14px;
        }

        input:focus {

            outline: none;

            border-color: #198754;

            box-shadow:
                0 0 0 3px
                rgba(25,135,84,0.10);
        }

        .toggle-password {

            position: absolute;

            right: 10px;

            top: 50%;

            transform:
                translateY(-50%);

            width: auto;

            padding: 5px;

            border: none;

            background: transparent;

            color: #777;

            cursor: pointer;
        }

        .toggle-password:hover {

            background: transparent;

            color: #198754;
        }

        .requirements {

            background: #f7f7f7;

            border-radius: 7px;

            padding: 11px 13px;

            margin-top: -5px;

            margin-bottom: 20px;

            font-size: 12px;

            color: #777;
        }

        .requirements i {

            color: #198754;

            margin-right: 5px;
        }

        .reset-button {

            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 8px;

            background:
                linear-gradient(
                    135deg,
                    #198754,
                    #146c43
                );

            color: white;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;
        }

        .reset-button:hover {

            background: #146c43;
        }

        .login-link {

            text-align: center;

            margin-top: 20px;
        }

        .login-link a {

            color: #198754;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;
        }

        .login-link a:hover {

            text-decoration: underline;
        }

        .footer {

            text-align: center;

            margin-top: 22px;

            padding-top: 17px;

            border-top: 1px solid #eee;

            color: #888;

            font-size: 11px;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="icon">

        <i class="fas fa-lock"></i>

    </div>

    <h2>
        Reset Password
    </h2>

    <div class="subtitle">

        Create a new password for your account.

    </div>


    <?php if (!empty($error)): ?>

        <div class="error">

            <i class="fas fa-exclamation-circle"></i>

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <?php if (!empty($success)): ?>

        <div class="success">

            <i class="fas fa-check-circle"></i>

            <?php echo htmlspecialchars($success); ?>

        </div>

        <div class="login-link">

            <a href="login.php">

                <i class="fas fa-sign-in-alt"></i>

                Go to Login

            </a>

        </div>

    <?php else: ?>


        <form method="POST">

            <!-- NEW PASSWORD -->

            <div class="form-group">

                <label for="password">

                    New Password

                </label>

                <div class="input-wrapper">

                    <i class="fas fa-lock"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter new password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('password', 'eye1')">

                        <i
                            class="fas fa-eye"
                            id="eye1">
                        </i>

                    </button>

                </div>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_password">

                    Confirm New Password

                </label>

                <div class="input-wrapper">

                    <i class="fas fa-lock"></i>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('confirm_password', 'eye2')">

                        <i
                            class="fas fa-eye"
                            id="eye2">
                        </i>

                    </button>

                </div>

            </div>


            <div class="requirements">

                <i class="fas fa-info-circle"></i>

                Password must contain at least
                <strong>6 characters</strong>.

            </div>


            <button
                type="submit"
                class="reset-button">

                <i class="fas fa-key"></i>

                Change Password

            </button>

        </form>


        <div class="login-link">

            <a href="login.php">

                <i class="fas fa-arrow-left"></i>

                Back to Login

            </a>

        </div>

    <?php endif; ?>


    <div class="footer">

        Tonkolili District Social Welfare

    </div>

</div>


<script>

function togglePassword(fieldId, iconId) {

    const field =
        document.getElementById(fieldId);

    const icon =
        document.getElementById(iconId);

    if (field.type === "password") {

        field.type = "text";

        icon.classList.remove("fa-eye");

        icon.classList.add("fa-eye-slash");

    } else {

        field.type = "password";

        icon.classList.remove("fa-eye-slash");

        icon.classList.add("fa-eye");
    }
}

</script>

</body>

</html>