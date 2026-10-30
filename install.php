<?php
session_start();
mysqli_report(MYSQLI_REPORT_OFF);

$config_file = __DIR__ . "/config/database.php";

// Default detected values
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_localhost = in_array($http_host, ['localhost', '127.0.0.1', '::1']) 
    || str_contains($http_host, 'localhost') 
    || str_contains($http_host, '127.0.0.1');

if ($is_localhost) {
    $host     = "127.0.0.1";
    $username = "root";
    $password = "";
    $database = "smartexam_db";
} else {
    $host     = "sql304.infinityfree.me";
    $username = "if0_42445904";
    $password = "n61ktjNhqvK";
    $database = "if0_42445904_smartexam";
}

$status = [];
$success = true;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['install'])) {
    $host     = trim($_POST['host'] ?? $host);
    $username = trim($_POST['username'] ?? $username);
    $password = $_POST['password'] ?? $password;
    $database = trim($_POST['database'] ?? $database);

    // 1. Try connecting directly to target database
    $conn = @new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        // Try connecting to server without db and create it if allowed
        $server_conn = @new mysqli($host, $username, $password);
        if (!$server_conn->connect_error) {
            $db_escaped = $server_conn->real_escape_string($database);
            @$server_conn->query("CREATE DATABASE IF NOT EXISTS `$db_escaped` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $server_conn->close();
            // Retry connecting to database
            $conn = @new mysqli($host, $username, $password, $database);
        }
    }

    if ($conn->connect_error) {
        $status[] = ["type" => "error", "msg" => "Connection to database failed: " . $conn->connect_error];
        $status[] = ["type" => "error", "msg" => "Tip: On InfinityFree, ensure you created database '$database' in Control Panel > MySQL Databases."];
        $success = false;
    } else {
        $status[] = ["type" => "success", "msg" => "Connected to database `$database` successfully!"];
        $conn->set_charset("utf8mb4");

        // 2. Table Definitions
        $tables = [
            "users" => "
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(150) NOT NULL UNIQUE,
                    `password` VARCHAR(255) NOT NULL,
                    `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `is_verified` TINYINT(1) NOT NULL DEFAULT 1,
                    `otp` VARCHAR(6) DEFAULT NULL,
                    `otp_expiry` DATETIME DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            "exams" => "
                CREATE TABLE IF NOT EXISTS `exams` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(150) NOT NULL,
                    `description` TEXT,
                    `duration` INT NOT NULL,
                    `total_marks` INT DEFAULT 0,
                    `status` ENUM('draft', 'published') DEFAULT 'draft',
                    `created_by` INT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            "questions" => "
                CREATE TABLE IF NOT EXISTS `questions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `exam_id` INT NOT NULL,
                    `question_text` TEXT NOT NULL,
                    `option_a` VARCHAR(255) NOT NULL,
                    `option_b` VARCHAR(255) NOT NULL,
                    `option_c` VARCHAR(255) NOT NULL,
                    `option_d` VARCHAR(255) NOT NULL,
                    `correct_answer` CHAR(1) NOT NULL,
                    `marks` INT DEFAULT 1
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            "attempts" => "
                CREATE TABLE IF NOT EXISTS `attempts` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `exam_id` INT NOT NULL,
                    `room_exam_id` INT DEFAULT NULL,
                    `score` INT DEFAULT 0,
                    `total_questions` INT DEFAULT 0,
                    `correct_answers` INT DEFAULT 0,
                    `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `submitted_at` DATETIME DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            "answers" => "
                CREATE TABLE IF NOT EXISTS `answers` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `attempt_id` INT NOT NULL,
                    `question_id` INT NOT NULL,
                    `selected_option` ENUM('A', 'B', 'C', 'D') DEFAULT NULL,
                    `is_correct` TINYINT(1) DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            "login_otps" => "
                CREATE TABLE IF NOT EXISTS `login_otps` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `otp_hash` VARCHAR(255) NOT NULL,
                    `expires_at` DATETIME NOT NULL,
                    `attempts` INT DEFAULT 0,
                    `is_verified` TINYINT(1) DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            "
        ];

        foreach ($tables as $t_name => $t_sql) {
            if ($conn->query($t_sql)) {
                $status[] = ["type" => "success", "msg" => "Table `$t_name` configured."];
            } else {
                $status[] = ["type" => "error", "msg" => "Error creating `$t_name`: " . $conn->error];
                $success = false;
            }
        }

        // 3. Seed Default Accounts
        if ($success) {
            $admin_hash = password_hash("admin123", PASSWORD_DEFAULT);
            $student_hash = password_hash("student123", PASSWORD_DEFAULT);

            // Admin
            $check_admin = $conn->query("SELECT id FROM users WHERE email = 'admin@smartexam.com'");
            if ($check_admin && $check_admin->num_rows === 0) {
                $admin_insert = "INSERT INTO users (name, email, password, role, is_verified) VALUES ('System Administrator', 'admin@smartexam.com', '$admin_hash', 'admin', 1)";
                $conn->query($admin_insert);
                $status[] = ["type" => "success", "msg" => "Default Admin created (admin@smartexam.com / admin123)."];
            } else {
                $status[] = ["type" => "success", "msg" => "Admin user verified."];
            }

            // Student
            $check_stu = $conn->query("SELECT id FROM users WHERE email = 'student@smartexam.com'");
            if ($check_stu && $check_stu->num_rows === 0) {
                $stu_insert = "INSERT INTO users (name, email, password, role, is_verified) VALUES ('Demo Student', 'student@smartexam.com', '$student_hash', 'student', 1)";
                $conn->query($stu_insert);
                $status[] = ["type" => "success", "msg" => "Default Student created (student@smartexam.com / student123)."];
            } else {
                $status[] = ["type" => "success", "msg" => "Student user verified."];
            }

            // 4. Seed Sample Quiz
            $admin_res = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $admin_id = ($admin_res && $row = $admin_res->fetch_assoc()) ? $row['id'] : 1;

            $check_exam = $conn->query("SELECT id FROM exams WHERE title = 'General Knowledge & Science Quiz'");
            if ($check_exam && $check_exam->num_rows === 0) {
                $exam_insert = "INSERT INTO exams (title, description, duration, total_marks, status, created_by) 
                    VALUES ('General Knowledge & Science Quiz', 'Comprehensive demo quiz testing general science, web protocols, and modern technology.', 10, 3, 'published', $admin_id)";
                if ($conn->query($exam_insert)) {
                    $exam_id = $conn->insert_id;
                    $status[] = ["type" => "success", "msg" => "Sample Exam created."];

                    $qs = [
                        [
                            "What does PHP stand for in modern web development?",
                            "Personal Home Page",
                            "Hypertext Preprocessor",
                            "Private Hypertext Protocol",
                            "Programming Hosting Platform",
                            "B"
                        ],
                        [
                            "Which standard protocol encrypts and secures web traffic?",
                            "HTTP",
                            "FTP",
                            "HTTPS",
                            "SMTP",
                            "C"
                        ],
                        [
                            "What is the default port for standard MySQL database servers?",
                            "80",
                            "3306",
                            "8080",
                            "21",
                            "B"
                        ]
                    ];

                    $q_stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, marks) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                    foreach ($qs as $q) {
                        $q_stmt->bind_param("issssss", $exam_id, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]);
                        $q_stmt->execute();
                    }
                    $q_stmt->close();
                    $status[] = ["type" => "success", "msg" => "Seeded 3 sample questions for demo quiz."];
                }
            }
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - SmartExam</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .status-box {
            max-height: 250px;
            overflow-y: auto;
            background: rgba(0, 0, 0, 0.4);
            border-radius: var(--border-radius-md);
            padding: 15px;
            margin-bottom: 25px;
            border: 1px solid var(--glass-border);
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        .status-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .icon-success { color: var(--success); }
        .icon-error { color: var(--error); }
    </style>
</head>
<body>
    <div class="auth-wrapper" style="padding: 40px 20px;">
        <div class="card auth-card" style="max-width: 540px;">
            <div class="auth-header">
                <h1 class="sidebar-logo" style="margin-bottom: 10px; font-size: 36px; text-align: center;">Smart<span>Exam</span></h1>
                <h2 style="font-size: 18px; font-weight: 500; color: var(--text-muted);">Database Schema Installer</h2>
            </div>
            
            <?php if (!isset($_POST['install']) || !$success): ?>
                <?php if (isset($_POST['install']) && !$success): ?>
                    <div class="status-box">
                        <?php foreach ($status as $item): ?>
                            <div class="status-item">
                                <span class="<?php echo $item['type'] === 'success' ? 'icon-success' : 'icon-error'; ?>">
                                    <?php echo $item['type'] === 'success' ? '✔' : '✖'; ?>
                                </span>
                                <span style="color: <?php echo $item['type'] === 'success' ? '#ffffff' : 'var(--error)'; ?>;">
                                    <?php echo htmlspecialchars($item['msg']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success" style="background: rgba(0, 212, 255, 0.08); border-color: rgba(0, 212, 255, 0.2); color: #8be5f5;">
                        Connects to your MySQL server and initializes database tables, default accounts, and a sample quiz.
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Database Host</label>
                        <input type="text" name="host" class="form-control" value="<?php echo htmlspecialchars($host); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Database Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Database Password</label>
                        <input type="password" name="password" class="form-control" value="<?php echo htmlspecialchars($password); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="database" class="form-control" value="<?php echo htmlspecialchars($database); ?>" required>
                    </div>

                    <button type="submit" name="install" value="1" class="btn btn-primary" style="width: 100%; margin-top: 15px;">
                        Run Installer & Seed DB
                    </button>
                </form>
            <?php else: ?>
                <div class="status-box">
                    <?php foreach ($status as $item): ?>
                        <div class="status-item">
                            <span class="<?php echo $item['type'] === 'success' ? 'icon-success' : 'icon-error'; ?>">
                                <?php echo $item['type'] === 'success' ? '✔' : '✖'; ?>
                            </span>
                            <span style="color: <?php echo $item['type'] === 'success' ? '#ffffff' : 'var(--error)'; ?>;">
                                <?php echo htmlspecialchars($item['msg']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="alert alert-success">
                    Database setup completed successfully!
                </div>
                <div class="card" style="padding: 15px 20px; background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2); margin-bottom: 25px;">
                    <h4 style="color: var(--success); font-size: 15px; margin-bottom: 10px;">Ready-to-Use Accounts:</h4>
                    <p style="font-size: 14px; margin-bottom: 6px; color: var(--text-muted);"><strong>Admin:</strong> admin@smartexam.com / admin123</p>
                    <p style="font-size: 14px; color: var(--text-muted);"><strong>Student:</strong> student@smartexam.com / student123</p>
                </div>
                <a href="login.php" class="btn btn-primary" style="width: 100%;">Proceed to Login</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
