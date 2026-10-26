 <?php

session_start();

require_once "../includes/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {

        $error = "Please enter your username and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT user_id, full_name, username, password, role, status
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if ($user["status"] !== "Active") {

                $error = "Your account is inactive. Please contact the administrator.";

            } elseif (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];

                header("Location: ../index.php");
                exit;

            } else {

                $error = "Invalid username or password.";
            }

        } else {

            $error = "Invalid username or password.";
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

    <title>Login - Social Welfare System</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            min-height: 100vh;

            background:
                linear-gradient(
                    rgba(0, 95, 65, 0.78),
                    rgba(0, 55, 45, 0.82)
                ),
                url("../assets/images/social-welfare-bg.jpg");

            background-size: cover;
            background-position: center;
            background-attachment: fixed;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 20px;
        }

        /* =========================
           LOGIN CONTAINER
        ========================= */

        .login-container {

            width: 430px;
            max-width: 100%;

            background:
                rgba(255,255,255,0.97);

            padding: 38px;

            border-radius: 18px;

            box-shadow:
                0 15px 45px rgba(0,0,0,0.30);

            animation:
                fadeIn 0.6s ease;
        }

        @keyframes fadeIn {

            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =========================
           LOGO
        ========================= */

        .logo {

            text-align: center;

            margin-bottom: 15px;
        }

        .logo-icon {

            width: 78px;
            height: 78px;

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

            font-size: 27px;

            font-weight: bold;

            box-shadow:
                0 6px 15px rgba(25,135,84,0.30);
        }

        .logo-icon i {

            margin-right: 4px;
        }

        /* =========================
           TITLE
        ========================= */

        h2 {

            text-align: center;

            color: #198754;

            margin:
                10px 0 5px;

            font-size: 23px;
        }

        .subtitle {

            text-align: center;

            color: #777;

            font-size: 14px;

            margin-bottom: 28px;
        }

        /* =========================
           ERROR
        ========================= */

        .error {

            background: #fdecec;

            border:
                1px solid #f5c2c7;

            color: #842029;

            padding: 12px 14px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 13px;

            display: flex;

            align-items: center;

            gap: 9px;
        }

        /* =========================
           FORM
        ========================= */

        .form-group {

            margin-bottom: 18px;
        }

        label {

            display: block;

            margin-bottom: 7px;

            font-size: 13px;

            font-weight: bold;

            color: #333;
        }

        .input-wrapper {

            position: relative;
        }

        .input-icon {

            position: absolute;

            left: 13px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #198754;

            font-size: 15px;
        }

        input {

            width: 100%;

            padding:
                13px 45px 13px 40px;

            border:
                1px solid #d5d5d5;

            border-radius: 8px;

            font-size: 14px;

            background: #fff;

            transition: 0.2s;
        }

        input:focus {

            outline: none;

            border-color: #198754;

            box-shadow:
                0 0 0 3px
                rgba(25,135,84,0.10);
        }

        /* =========================
           SHOW PASSWORD
        ========================= */

        .toggle-password {

            position: absolute;

            right: 13px;

            top: 50%;

            transform:
                translateY(-50%);

            border: none;

            background: none;

            color: #777;

            cursor: pointer;

            padding: 3px;

            width: auto;

            font-size: 15px;
        }

        .toggle-password:hover {

            color: #198754;

            background: none;
        }

        /* =========================
           FORGOT PASSWORD
        ========================= */

        .login-options {

            display: flex;

            justify-content:
                flex-end;

            margin-top: -7px;

            margin-bottom: 20px;
        }

        .forgot-password {

            color: #198754;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;
        }

        .forgot-password:hover {

            color: #146c43;

            text-decoration: underline;
        }

        /* =========================
           LOGIN BUTTON
        ========================= */

        .login-button {

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

            transition: 0.25s;

            box-shadow:
                0 5px 12px
                rgba(25,135,84,0.25);
        }

        .login-button:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 7px 16px
                rgba(25,135,84,0.30);
        }

        .login-button i {

            margin-right: 7px;
        }

        /* =========================
           FOOTER
        ========================= */

        .footer {

            text-align: center;

            margin-top: 25px;

            padding-top: 18px;

            border-top:
                1px solid #eee;

            font-size: 12px;

            color: #888;

            line-height: 1.6;
        }

        .footer strong {

            color: #198754;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 500px) {

            .login-container {

                padding: 28px 22px;

                border-radius: 14px;
            }

            h2 {

                font-size: 20px;
            }

            .logo-icon {

                width: 68px;
                height: 68px;

                font-size: 23px;
            }
        }

    </style>

</head>

<body>

<div class="login-container">

    <!-- LOGO -->

    <div class="logo">

        <div class="logo-icon">

            <i class="fas fa-hands-helping"></i>

        </div>

    </div>


    <!-- TITLE -->

    <h2>
        Social Welfare System
    </h2>

    <div class="subtitle">

        Tonkolili District

    </div>


    <!-- ERROR -->

    <?php if (!empty($error)): ?>

        <div class="error">

            <i class="fas fa-exclamation-circle"></i>

            <span>
                <?php echo htmlspecialchars($error); ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- LOGIN FORM -->

    <form method="POST"
          id="loginForm">

        <!-- USERNAME -->

        <div class="form-group">

            <label for="username">
                Username
            </label>

            <div class="input-wrapper">

                <i class="fas fa-user input-icon"></i>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    autocomplete="username"
                    required
                >

            </div>

        </div>


        <!-- PASSWORD -->

        <div class="form-group">

            <label for="password">
                Password
            </label>

            <div class="input-wrapper">

                <i class="fas fa-lock input-icon"></i>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    onclick="togglePassword()"
                    aria-label="Show password">

                    <i
                        class="fas fa-eye"
                        id="eyeIcon">
                    </i>

                </button>

            </div>

        </div>


        <!-- FORGOT PASSWORD -->

        <div class="login-options">

            <a
                href="forgot_password.php"
                class="forgot-password">

                <i class="fas fa-key"></i>

                Forgot Password?

            </a>

        </div>


        <!-- LOGIN -->

        <button
            type="submit"
            class="login-button"
            id="loginButton">

            <i class="fas fa-sign-in-alt"></i>

            Login

        </button>

    </form>


    <!-- FOOTER -->

    <div class="footer">

        <strong>
            Social Welfare Records Management System
        </strong>

        <br>

        Tonkolili District

    </div>

</div>


<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const eyeIcon =
        document.getElementById("eyeIcon");

    const toggle =
        document.querySelector(".toggle-password");


    if (password.type === "password") {

        password.type = "text";

        eyeIcon.classList.remove("fa-eye");

        eyeIcon.classList.add("fa-eye-slash");

        toggle.setAttribute(
            "aria-label",
            "Hide password"
        );

    } else {

        password.type = "password";

        eyeIcon.classList.remove("fa-eye-slash");

        eyeIcon.classList.add("fa-eye");

        toggle.setAttribute(
            "aria-label",
            "Show password"
        );
    }
}


/* LOGIN LOADING */

document
    .getElementById("loginForm")
    .addEventListener("submit", function() {

        const button =
            document.getElementById("loginButton");

        button.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Logging in...';

        button.disabled = true;

    });

</script>

</body>

</html>