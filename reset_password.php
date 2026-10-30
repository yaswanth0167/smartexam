<?php

session_start();

require_once "config/database.php";

$message = "";
$message_type = "";


// ==========================================
// SECURITY CHECK
// ==========================================

if (
    !isset($_SESSION["reset_user_id"]) ||
    !isset($_SESSION["reset_otp_verified"]) ||
    $_SESSION["reset_otp_verified"] !== true
) {

    header("Location: forgot_password.php");

    exit();
}


// ==========================================
// PASSWORD RESET
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password =
        $_POST["password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";


    // ==========================================
    // VALIDATION
    // ==========================================

    if (
        empty($password) ||
        empty($confirm_password)
    ) {

        $message =
            "All fields are required.";

        $message_type = "error";


    } elseif (
        $password !== $confirm_password
    ) {

        $message =
            "Passwords do not match.";

        $message_type = "error";


    } elseif (
        strlen($password) < 8
    ) {

        $message =
            "Password must be at least 8 characters.";

        $message_type = "error";


    } elseif (
        !preg_match("/[A-Z]/", $password)
    ) {

        $message =
            "Password must contain at least one capital letter.";

        $message_type = "error";


    } elseif (
        !preg_match("/[0-9]/", $password)
    ) {

        $message =
            "Password must contain at least one number.";

        $message_type = "error";


    } elseif (
        !preg_match(
            "/[^a-zA-Z0-9]/",
            $password
        )
    ) {

        $message =
            "Password must contain at least one special character.";

        $message_type = "error";


    } else {


        // ==========================================
        // HASH NEW PASSWORD
        // ==========================================

        $hashed_password =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        // ==========================================
        // UPDATE DATABASE
        // ==========================================

        $sql = "
            UPDATE users
            SET password = ?
            WHERE id = ?
        ";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            die(
                "Database Error: " .
                $conn->error
            );
        }


        $stmt->bind_param(
            "si",
            $hashed_password,
            $_SESSION["reset_user_id"]
        );


        if ($stmt->execute()) {


            // ==========================================
            // CLEAR RESET SESSION
            // ==========================================

            unset(
                $_SESSION["reset_user_id"],
                $_SESSION["reset_email"],
                $_SESSION["reset_otp_verified"]
            );


            $message =
                "Password reset successful! You can now login.";

            $message_type =
                "success";


        } else {

            $message =
                "Unable to reset password.";

            $message_type =
                "error";
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

    <title>Reset Password - SmartExam</title>


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

                    Create New Password

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

                        echo htmlspecialchars(
                            $message
                        );

                    ?>


                </div>


            <?php endif; ?>


            <?php if ($message_type !== "success"): ?>


                <form method="POST">


                    <div class="form-group">


                        <label
                            class="form-label"
                            for="password"
                        >

                            New Password

                        </label>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Min 8 characters, 1 capital, 1 number & 1 special symbol"
                            required
                        >


                    </div>


                    <div class="form-group">


                        <label
                            class="form-label"
                            for="confirm_password"
                        >

                            Confirm New Password

                        </label>


                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Re-enter your new password"
                            required
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

                        Reset Password

                    </button>


                </form>


            <?php else: ?>


                <div
                    style="
                        text-align: center;
                        margin-top: 20px;
                    "
                >

                    <a
                        href="login.php"
                        class="btn btn-primary"
                        style="
                            display: inline-block;
                            width: 100%;
                        "
                    >

                        Go to Login

                    </a>

                </div>


            <?php endif; ?>


        </div>


    </div>


</body>

</html>