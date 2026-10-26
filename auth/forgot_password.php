<?php

session_start();

require_once "../includes/db.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");

    if (empty($username)) {

        $error = "Please enter your username.";

    } else {

        $stmt = $conn->prepare(
            "SELECT user_id, full_name, username, status
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

                $error = "This account is inactive. Please contact the administrator.";

            } else {

                $_SESSION["reset_user_id"] = $user["user_id"];
                $_SESSION["reset_username"] = $user["username"];

                header("Location: reset_password.php");
                exit;
            }

        } else {

            $error = "Username not found.";
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

    <title>Forgot Password - Social Welfare System</title>

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

            background: rgba(255,255,255,0.97);

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

            margin-bottom: 28px;
        }

        .info {

            background: #eaf7ed;

            color: #146c43;

            padding: 12px;

            border-radius: 7px;

            font-size: 13px;

            margin-bottom: 20px;
        }

        .error {

            background: #fdecec;

            color: #842029;

            padding: 12px;

            border-radius: 7px;

            font-size: 13px;

            margin-bottom: 20px;
        }

        label {

            display: block;

            font-weight: bold;

            font-size: 13px;

            margin-bottom: 7px;
        }

        .input-wrapper {

            position: relative;

            margin-bottom: 20px;
        }

        .input-wrapper i {

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
                13px 13px 13px 40px;

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

        button {

            width: 100%;

            padding: 13px;

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

        button:hover {

            background: #146c43;
        }

        .back {

            text-align: center;

            margin-top: 20px;
        }

        .back a {

            color: #198754;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;
        }

        .back a:hover {

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

        <i class="fas fa-key"></i>

    </div>

    <h2>
        Forgot Password?
    </h2>

    <div class="subtitle">

        Social Welfare Records Management System

    </div>

    <div class="info">

        <i class="fas fa-info-circle"></i>

        Enter your username to continue with
        password recovery.

    </div>

    <?php if (!empty($error)): ?>

        <div class="error">

            <i class="fas fa-exclamation-circle"></i>

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <label for="username">

            Username

        </label>

        <div class="input-wrapper">

            <i class="fas fa-user"></i>

            <input
                type="text"
                id="username"
                name="username"
                placeholder="Enter your username"
                required
            >

        </div>

        <button type="submit">

            <i class="fas fa-arrow-right"></i>

            Continue

        </button>

    </form>

    <div class="back">

        <a href="login.php">

            <i class="fas fa-arrow-left"></i>

            Back to Login

        </a>

    </div>

    <div class="footer">

        Tonkolili District Social Welfare

    </div>

</div>

</body>

</html>