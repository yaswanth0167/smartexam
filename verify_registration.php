<?php

session_start();

require_once "config/database.php";

$message = "";
$message_type = "";

if (
    !isset($_SESSION["registration_email"]) ||
    !isset($_SESSION["registration_otp"]) ||
    !isset($_SESSION["registration_otp_expiry"])
) {
    header("Location: register.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $entered_otp = trim($_POST["otp"] ?? "");

    if (!preg_match("/^[0-9]{6}$/", $entered_otp)) {

        $message = "Please enter a valid 6-digit OTP.";
        $message_type = "error";

    } elseif (time() > $_SESSION["registration_otp_expiry"]) {

        $message = "OTP has expired. Please register again.";
        $message_type = "error";

    } elseif (
        !password_verify(
            $entered_otp,
            $_SESSION["registration_otp"]
        )
    ) {

        $message = "Invalid OTP. Please try again.";
        $message_type = "error";

    } else {

        $name = $_SESSION["registration_name"];
        $email = $_SESSION["registration_email"];
        $hashed_password = $_SESSION["registration_password"];
        $role = $_SESSION["registration_role"];

        $insert_sql = "
            INSERT INTO users
            (
                name,
                email,
                password,
                role,
                is_verified
            )
            VALUES (?, ?, ?, ?, 1)
        ";

        $stmt = $conn->prepare($insert_sql);

        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param(
            "ssss",
            $name,
            $email,
            $hashed_password,
            $role
        );

        if ($stmt->execute()) {

            unset(
                $_SESSION["registration_name"],
                $_SESSION["registration_email"],
                $_SESSION["registration_password"],
                $_SESSION["registration_role"],
                $_SESSION["registration_otp"],
                $_SESSION["registration_otp_expiry"]
            );

            $_SESSION["registration_success"] =
                "Registration successful! You can now login.";

            header("Location: login.php");
            exit();

        } else {

            $message = "Registration failed. Please try again.";
            $message_type = "error";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Verify Email - SmartExam</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

    <div class="auth-wrapper">

        <div class="card auth-card">

            <div class="auth-header">

                <h1
                    class="sidebar-logo"
                    style="
                        margin-bottom: 10px;
                        font-size: 36px;
                        text-align: center;
                    "
                >

                    <a href="index.php">
                        Smart<span>Exam</span>
                    </a>

                </h1>

                <h2
                    style="
                        font-size: 18px;
                        font-weight: 500;
                        color: var(--text-muted);
                    "
                >

                    Verify Your Email

                </h2>

            </div>

            <?php if (!empty($message)): ?>

                <div
                    class="alert
                    <?php
                        echo $message_type === "success"
                            ? "alert-success"
                            : "alert-danger";
                    ?>"
                >

                    <span>⚠️</span>

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>

            <p
                style="
                    text-align: center;
                    color: var(--text-muted);
                    margin-bottom: 20px;
                "
            >

                Enter the 6-digit OTP sent to your registered email.

            </p>

            <form method="POST">

                <div class="form-group">

                    <label
                        class="form-label"
                        for="otp"
                    >

                        Enter OTP

                    </label>

                    <input
                        type="text"
                        id="otp"
                        name="otp"
                        class="form-control"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        placeholder="Enter 6-digit OTP"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                    style="width: 100%;"
                >

                    Verify OTP

                </button>

            </form>

        </div>

    </div>

</body>

</html>