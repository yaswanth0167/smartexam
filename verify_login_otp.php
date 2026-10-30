<?php

session_start();

require_once "config/database.php";

$message = "";
$message_type = "";


// ==========================================
// CHECK LOGIN OTP SESSION
// ==========================================

if (
    !isset($_SESSION["login_user_id"]) ||
    !isset($_SESSION["login_user_name"]) ||
    !isset($_SESSION["login_user_email"]) ||
    !isset($_SESSION["login_user_role"]) ||
    !isset($_SESSION["login_otp"]) ||
    !isset($_SESSION["login_otp_expiry"])
) {
    header("Location: login.php");
    exit();
}


// ==========================================
// MASK EMAIL
// ==========================================

$email = $_SESSION["login_user_email"];

$email_parts = explode("@", $email);

$local_part = $email_parts[0];
$domain = $email_parts[1];

if (strlen($local_part) > 2) {

    $masked_email =
        substr($local_part, 0, 2) .
        str_repeat("*", max(2, strlen($local_part) - 2)) .
        "@" .
        $domain;

} else {

    $masked_email = "***@" . $domain;
}


// ==========================================
// OTP VERIFICATION
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $entered_otp = trim($_POST["otp"] ?? "");


    // ==========================================
    // VALIDATE OTP FORMAT
    // ==========================================

    if (!preg_match("/^[0-9]{6}$/", $entered_otp)) {

        $message = "Please enter a valid 6-digit OTP.";
        $message_type = "error";


    // ==========================================
    // CHECK OTP EXPIRY
    // ==========================================

    } elseif (
        time() > $_SESSION["login_otp_expiry"]
    ) {

        $message =
            "OTP has expired. Please login again.";

        $message_type = "error";


    // ==========================================
    // LIMIT WRONG ATTEMPTS
    // ==========================================

    } elseif (
        $_SESSION["login_otp_attempts"] >= 5
    ) {

        $message =
            "Too many incorrect attempts. Please login again.";

        $message_type = "error";


    // ==========================================
    // VERIFY OTP
    // ==========================================

    } elseif (
        password_verify(
            $entered_otp,
            $_SESSION["login_otp"]
        )
    ) {


        // ==========================================
        // REGENERATE SESSION ID
        // ==========================================

        session_regenerate_id(true);


        // ==========================================
        // CREATE FINAL LOGIN SESSION
        // ==========================================

        $_SESSION["user_id"] =
            $_SESSION["login_user_id"];

        $_SESSION["user_name"] =
            $_SESSION["login_user_name"];

        $_SESSION["user_email"] =
            $_SESSION["login_user_email"];

        $_SESSION["role"] =
            $_SESSION["login_user_role"];


        // ==========================================
        // REMOVE TEMPORARY LOGIN OTP DATA
        // ==========================================

        unset(
            $_SESSION["login_user_id"],
            $_SESSION["login_user_name"],
            $_SESSION["login_user_email"],
            $_SESSION["login_user_role"],
            $_SESSION["login_otp"],
            $_SESSION["login_otp_expiry"],
            $_SESSION["login_otp_attempts"]
        );


        // ==========================================
        // REDIRECT BASED ON ROLE
        // ==========================================

        if ($_SESSION["role"] === "admin") {

            header(
                "Location: admin/dashboard.php"
            );

        } else {

            header(
                "Location: student/dashboard.php"
            );
        }

        exit();


    } else {


        // ==========================================
        // WRONG OTP
        // ==========================================

        $_SESSION["login_otp_attempts"]++;

        $remaining_attempts =
            5 - $_SESSION["login_otp_attempts"];

        if ($remaining_attempts > 0) {

            $message =
                "Invalid OTP. " .
                $remaining_attempts .
                " attempts remaining.";

        } else {

            $message =
                "Too many incorrect attempts. Please login again.";
        }

        $message_type = "error";
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

    <title>Verify Login OTP - SmartExam</title>


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

                    Verify Login

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


                    <?php

                        echo htmlspecialchars($message);

                    ?>


                </div>


            <?php endif; ?>


            <p
                style="
                    text-align: center;
                    color: var(--text-muted);
                    margin-bottom: 20px;
                "
            >

                Enter the 6-digit OTP sent to:

                <br>

                <strong>

                    <?php

                        echo htmlspecialchars(
                            $masked_email
                        );

                    ?>

                </strong>

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
                        autofocus
                    >


                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                    style="width: 100%;"
                >

                    Verify & Login

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

                OTP is valid for 5 minutes.

            </p>


            <p
                style="
                    text-align: center;
                    margin-top: 10px;
                    font-size: 14px;
                "
            >

                <a
                    href="login.php"
                    style="font-weight: 600;"
                >

                    Back to Login

                </a>

            </p>


        </div>


    </div>


</body>

</html>