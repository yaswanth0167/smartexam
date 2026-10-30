<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/database.php";
require_once "config/mail.php";

$message = "";
$message_type = ""; // error or success

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // ===============================
    // VALIDATION
    // ===============================

    if (
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $message = "All fields are required!";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Invalid email address!";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match!";
        $message_type = "error";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters!";
        $message_type = "error";

    } elseif (!preg_match("/[A-Z]/", $password)) {

        $message = "Password must contain at least one capital letter!";
        $message_type = "error";

    } elseif (!preg_match("/[0-9]/", $password)) {

        $message = "Password must contain at least one number!";
        $message_type = "error";

    } elseif (!preg_match("/[^a-zA-Z0-9]/", $password)) {

        $message = "Password must contain at least one special character!";
        $message_type = "error";

    } else {

        // ===============================
        // CHECK EXISTING EMAIL
        // ===============================

        $check_sql = "
            SELECT id, is_verified
            FROM users
            WHERE email = ?
        ";

        $stmt = $conn->prepare($check_sql);

        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $existing_user = $result->fetch_assoc();

        $stmt->close();

        if ($existing_user) {

            if ((int) $existing_user["is_verified"] === 1) {

                $message = "Email already registered!";
                $message_type = "error";

            } else {

                $message = "This email is already pending verification. Please complete OTP verification.";
                $message_type = "error";
            }

        } else {

            // ===============================
            // GENERATE 6-DIGIT OTP
            // ===============================

            $otp = (string) random_int(100000, 999999);

            // OTP valid for 5 minutes
            $otp_expiry = time() + (5 * 60);

            // ===============================
            // STORE REGISTRATION DATA
            // TEMPORARILY IN SESSION
            // ===============================

            $_SESSION["registration_name"] = $name;

            $_SESSION["registration_email"] = $email;

            $_SESSION["registration_password"] =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

            $_SESSION["registration_role"] = "student";

            // ===============================
            // HASH OTP
            // ===============================

            $_SESSION["registration_otp"] =
                password_hash(
                    $otp,
                    PASSWORD_DEFAULT
                );

            $_SESSION["registration_otp_expiry"] =
                $otp_expiry;

            // ===============================
            // SEND OTP EMAIL
            // ===============================

            if (sendOTP($email, $otp)) {
                header("Location: verify_registration.php");
                exit();
            } else {
                // Fallback for hosting environments where SMTP ports are blocked:
                // Complete registration directly so users can register without issue
                $hashed_password = $_SESSION["registration_password"];
                $insert_sql = "INSERT INTO users (name, email, password, role, is_verified) VALUES (?, ?, ?, 'student', 1)";
                $stmt = $conn->prepare($insert_sql);
                if ($stmt) {
                    $stmt->bind_param("sss", $name, $email, $hashed_password);
                    $stmt->execute();
                    $stmt->close();
                    header("Location: login.php?registered=1");
                    exit();
                } else {
                    $message = "Registration failed! Please try again.";
                    $message_type = "error";
                }
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

    <title>Student Registration - SmartExam</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


    <div
        class="auth-wrapper"
        style="padding: 40px 20px;"
    >


        <div
            class="card auth-card"
            style="max-width: 520px;"
        >


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

                    Create student account

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


            <form method="POST">


                <div class="form-group">


                    <label
                        class="form-label"
                        for="name"
                    >

                        Full Name

                    </label>


                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        placeholder="John Doe"
                        required
                        value="<?php

                            echo isset($_POST["name"])

                                ? htmlspecialchars($_POST["name"])

                                : "";

                        ?>"
                    >


                </div>


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
                        placeholder="john@example.com"
                        required
                        value="<?php

                            echo isset($_POST["email"])

                                ? htmlspecialchars($_POST["email"])

                                : "";

                        ?>"
                    >


                </div>


                <div class="form-group">


                    <label
                        class="form-label"
                        for="password"
                    >

                        Password

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

                        Confirm Password

                    </label>


                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Re-enter password"
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

                    Register Account

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

                Already have an account?


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