<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/database.php";
require_once "config/mail.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    // ==========================================
    // VALIDATION
    // ==========================================

    if (empty($email)) {

        $message = "Please enter your email address.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } else {

        // ==========================================
        // CHECK USER
        // ==========================================

        $sql = "
            SELECT id, name, email
            FROM users
            WHERE email = ?
            AND role = 'student'
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        $stmt->close();


        if (!$user) {

            $message =
                "If an account exists with this email, an OTP will be sent.";

            $message_type = "success";

        } else {

            // ==========================================
            // GENERATE 6-DIGIT OTP
            // ==========================================

            $otp = (string) random_int(100000, 999999);


            // ==========================================
            // OTP EXPIRY - 5 MINUTES
            // ==========================================

            $otp_expiry = time() + (5 * 60);


            // ==========================================
            // STORE RESET DATA IN SESSION
            // ==========================================

            $_SESSION["reset_user_id"] = $user["id"];

            $_SESSION["reset_email"] = $user["email"];

            $_SESSION["reset_otp"] =
                password_hash(
                    $otp,
                    PASSWORD_DEFAULT
                );

            $_SESSION["reset_otp_expiry"] =
                $otp_expiry;


            // ==========================================
            // SEND OTP
            // ==========================================

            if (sendOTP($email, $otp)) {

                header(
                    "Location: verify_reset_otp.php"
                );

                exit();

            } else {

                unset(
                    $_SESSION["reset_user_id"],
                    $_SESSION["reset_email"],
                    $_SESSION["reset_otp"],
                    $_SESSION["reset_otp_expiry"]
                );

                $message =
                    "Unable to send OTP. Please try again.";

                $message_type = "error";
            }
        }
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

    <title>Forgot Password - SmartExam</title>


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

                    Reset Your Password

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


                    <span>

                        <?php

                            echo $message_type === "success"

                                ? "✔"

                                : "⚠️";

                        ?>

                    </span>


                    <?php

                        echo htmlspecialchars($message);

                    ?>


                </div>


            <?php endif; ?>


            <p
                style="
                    text-align: center;
                    color: var(--text-muted);
                    margin-bottom: 25px;
                "
            >

                Enter your registered email address.
                We will send you a password reset OTP.

            </p>


            <form method="POST">


                <div class="form-group">


                    <label
                        class="form-label"
                        for="email"
                    >

                        Email Address

                    </label>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="you@example.com"
                        required
                        value="<?php

                            echo isset($_POST["email"])

                                ? htmlspecialchars($_POST["email"])

                                : "";

                        ?>"
                    >


                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                    style="
                        width: 100%;
                        margin-top: 10px;
                    "
                >

                    Send Reset OTP

                </button>


            </form>


            <p
                style="
                    text-align: center;
                    margin-top: 25px;
                    font-size: 14px;
                    color: var(--text-muted);
                "
            >

                Remember your password?


                <a
                    href="login.php"
                    style="font-weight: 600;"
                >

                    Sign In

                </a>


            </p>


        </div>


    </div>


</body>

</html>