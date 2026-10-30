<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartExam - Smart Online Quiz & Examination System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .landing-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .landing-card {
            max-width: 600px;
            text-align: center;
            padding: 50px 40px;
        }
        .landing-card h1 {
            font-size: 42px;
            margin-bottom: 10px;
        }
        .landing-card h2 {
            font-size: 20px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 25px;
        }
        .landing-card p {
            color: var(--text-muted);
            margin-bottom: 35px;
            font-size: 16px;
        }
        .cta-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .cta-buttons .btn {
            min-width: 160px;
        }
        .user-welcome {
            margin-bottom: 25px;
            font-size: 16px;
            background: rgba(0, 212, 255, 0.08);
            border: 1px solid rgba(0, 212, 255, 0.2);
            padding: 10px 20px;
            border-radius: 30px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <div class="card landing-card">
            <h1>Smart<span class="accent-text">Exam</span></h1>
            <h2>Smart Online Quiz & Examination System</h2>
            
            <p>
                Empower your learning. Create, attempt, and analyze examinations seamlessly in an interactive, glassmorphic environment.
            </p>

            <?php if (isset($_SESSION["user_id"])): ?>
                <div class="user-welcome">
                    Welcome back, <strong style="color: var(--accent-cyan);"><?php echo htmlspecialchars($_SESSION["user_name"]); ?></strong>!
                </div>
                <div class="cta-buttons">
                    <?php if ($_SESSION["role"] === "admin"): ?>
                        <a href="admin/dashboard.php" class="btn btn-primary">Go to Admin Dashboard</a>
                    <?php else: ?>
                        <a href="student/dashboard.php" class="btn btn-primary">Go to Student Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            <?php else: ?>
                <div class="cta-buttons">
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-secondary">Register as Student</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>