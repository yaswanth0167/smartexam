<?php

session_start();

require_once "config/database.php";
require_once "config/mail.php";

$message = "";
$success_message = isset($_GET["registered"]) ? "Registration successful! You can now login with your password." : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $message = "Email and password are required!";

    } else {

        $sql = "
            SELECT
                id,
                name,
                email,
                password,
                role
            FROM users
            WHERE email = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();


        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();


            // ==========================================
            // VERIFY PASSWORD
            // ==========================================

            if (password_verify($password, $user["password"])) {

                // Instant login for demo recruiter accounts and project admin
                if (str_ends_with($user["email"], "@smartexam.com") || $user["email"] === "smartexam0167@gmail.com") {
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_name"] = $user["name"];
                    $_SESSION["user_email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];

                    if ($user["role"] === "admin") {
                        header("Location: admin/dashboard.php");
                    } else {
                        header("Location: student/dashboard.php");
                    }
                    exit();
                }

                // ==========================================
                // GENERATE LOGIN OTP (For Real Users)
                // ==========================================
                $otp = (string) random_int(100000, 999999);


                // ==========================================
                // STORE LOGIN DATA TEMPORARILY
                // ==========================================

                $_SESSION["login_user_id"] =
                    $user["id"];

                $_SESSION["login_user_name"] =
                    $user["name"];

                $_SESSION["login_user_email"] =
                    $user["email"];

                $_SESSION["login_user_role"] =
                    $user["role"];


                // ==========================================
                // HASH OTP
                // ==========================================

                $_SESSION["login_otp"] =
                    password_hash(
                        $otp,
                        PASSWORD_DEFAULT
                    );


                // ==========================================
                // OTP VALID FOR 5 MINUTES
                // ==========================================

                $_SESSION["login_otp_expiry"] =
                    time() + (5 * 60);


                // ==========================================
                // OTP ATTEMPTS
                // ==========================================

                $_SESSION["login_otp_attempts"] = 0;


                // ==========================================
                // SEND OTP EMAIL
                // ==========================================

                if (sendOTP($user["email"], $otp)) {
                    header("Location: verify_login_otp.php");
                    exit();
                } else {
                    // Fallback for hosting environments where outbound SMTP ports (587/465) are blocked
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_name"] = $user["name"];
                    $_SESSION["user_email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];

                    if ($user["role"] === "admin") {
                        header("Location: admin/dashboard.php");
                    } else {
                        header("Location: student/dashboard.php");
                    }
                    exit();
                }


            } else {

                $message =
                    "Invalid email or password!";
            }


        } else {

            $message =
                "Invalid email or password!";
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

    <title>Login - SmartExam</title>

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

                    Sign in to your account

                </h2>


            </div>


            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <span>✔</span>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>


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

                                ? htmlspecialchars(
                                    $_POST["email"]
                                )

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
                        placeholder="••••••••"
                        required
                    >


                </div>


                <!-- FORGOT PASSWORD -->

                <div
                    style="
                        text-align: right;
                        margin-top: -8px;
                        margin-bottom: 15px;
                    "
                >

                    <a
                        href="forgot_password.php"
                        style="
                            font-size: 14px;
                            font-weight: 600;
                        "
                    >

                        Forgot Password?

                    </a>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                    style="
                        width: 100%;
                        margin-top: 10px;
                    "
                >

                    Sign In

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

                Don't have an account?


                <a
                    href="register.php"
                    style="font-weight: 600;"
                >

                    Register as Student

                </a>


            </p>


        </div>


    </div>


</body>

</html>