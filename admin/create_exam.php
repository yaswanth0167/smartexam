<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

$message = "";
$message_type = ""; // 'error' or 'success'

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $duration = intval($_POST["duration"]);
    $total_marks = intval($_POST["total_marks"]);

    if (empty($title)) {
        $message = "Exam title is required!";
        $message_type = "error";
    } elseif ($duration <= 0) {
        $message = "Duration must be greater than 0!";
        $message_type = "error";
    } elseif ($total_marks <= 0) {
        $message = "Total marks must be greater than 0!";
        $message_type = "error";
    } else {
        $created_by = $_SESSION["user_id"];

        $sql = "
            INSERT INTO exams
            (title, description, duration, total_marks, status, created_by)
            VALUES (?, ?, ?, ?, 'draft', ?)
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssiii",
            $title,
            $description,
            $duration,
            $total_marks,
            $created_by
        );

        if ($stmt->execute()) {
            $message = "Exam created successfully! You can now add questions to it.";
            $message_type = "success";
        } else {
            $message = "Failed to create exam!";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam - SmartExam</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">Smart<span>Exam</span></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="sidebar-link">
                    <span>📊</span> Dashboard
                </a>
                <a href="exams.php" class="sidebar-link active">
                    <span>📝</span> Manage Exams
                </a>
                <a href="results.php" class="sidebar-link">
                    <span>🏆</span> Student Results
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php" class="btn btn-danger" style="width: 100%;">
                    <span>🚪</span> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div style="margin-bottom: 30px;">
                <h1 style="font-size: 28px; margin-bottom: 5px;">Create Exam</h1>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 0;">Add a new online quiz or exam. It will initially be saved as a draft.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-danger'; ?>" style="max-width: 600px;">
                    <span><?php echo $message_type === 'success' ? '✔' : '⚠️'; ?></span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 600px;">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="title">Exam Title</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="form-control"
                            placeholder="Example: CSS Layouts Masterclass"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            placeholder="What topic does this cover? Provide any instructions for the student."
                            rows="4"
                        ></textarea>
                    </div>

                    <div class="grid-2" style="gap: 15px;">
                        <div class="form-group">
                            <label class="form-label" for="duration">Duration (Minutes)</label>
                            <input
                                type="number"
                                id="duration"
                                name="duration"
                                class="form-control"
                                placeholder="Example: 30"
                                min="1"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="total_marks">Total Marks</label>
                            <input
                                type="number"
                                id="total_marks"
                                name="total_marks"
                                class="form-control"
                                placeholder="Example: 50"
                                min="1"
                                required
                            >
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 15px;">
                        <button type="submit" class="btn btn-primary">
                            Create Exam
                        </button>
                        <a href="exams.php" class="btn btn-secondary">
                            Back to List
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>