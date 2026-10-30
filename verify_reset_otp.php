<?php

session_start();

$message = "";
$message_type = "";


// ==========================================
// CHECK RESET SESSION
// ==========================================

if (
    !isset($_SESSION["reset_user_id"]) ||
    !isset($_SESSION["reset_email"]) ||
    !isset($_SESSION["reset_otp"]) ||
    !isset($_SESSION["reset_otp_expiry"])
) {
    header("Location: forgot_password.php");
    exit();
}


// ==========================================
// MASK EMAIL
// ==========================================

$email = $_SESSION["reset_email"];

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
        time() > $_SESSION["reset_otp_expiry"]
    ) {

        $message =
            "OTP has expired. Please request a new OTP.";

        $message_type = "error";


    // ==========================================
    // VERIFY OTP
    // ==========================================

    } elseif (
        password_verify(
            $entered_otp,
            $_SESSION["reset_otp"]
        )
    ) {


        // ==========================================
        // OTP VERIFIED
        // ==========================================

        $_SESSION["reset_otp_verified"] = true;


        // Remove OTP after successful verification

        unset(
            $_SESSION["reset_otp"],
            $_SESSION["reset_otp_expiry"]
        );


        header(
            "Location: reset_password.php"
        );

        exit();


    } else {

        $message = "Invalid OTP.";
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

    <title>Verify Reset OTP - SmartExam</title>


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

                    Verify Reset OTP

                </h2>


            </div>


            <?php if (!empty($message)): ?>


                <div
                    class="alert alert-danger"
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
                    margin-bottom: 25px;
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

                    Verify OTP

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
                    href="forgot_password.php"
                    style="font-weight: 600;"
                >

                    Request New OTP

                </a>

            </p>


        </div>


    </div>


</body>

</html>