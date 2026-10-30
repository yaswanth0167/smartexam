<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: exams.php");
    exit();
}

$exam_id = intval($_GET["id"]);
$message = "";
$message_type = "";

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
        $update_sql = "
            UPDATE exams
            SET title = ?,
                description = ?,
                duration = ?,
                total_marks = ?
            WHERE id = ?
        ";

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param(
            "ssiii",
            $title,
            $description,
            $duration,
            $total_marks,
            $exam_id
        );

        if ($stmt->execute()) {
            $message = "Exam updated successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to update exam!";
            $message_type = "error";
        }
        $stmt->close();
    }
}

$sql = "
    SELECT *
    FROM exams
    WHERE id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: exams.php");
    exit();
}

$exam = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam - SmartExam</title>
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
                <h1 style="font-size: 28px; margin-bottom: 5px;">Edit Exam</h1>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 0;">Update configuration details of your exam.</p>
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
                            value="<?php echo htmlspecialchars($exam["title"]); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            rows="4"
                        ><?php echo htmlspecialchars($exam["description"]); ?></textarea>
                    </div>

                    <div class="grid-2" style="gap: 15px;">
                        <div class="form-group">
                            <label class="form-label" for="duration">Duration (Minutes)</label>
                            <input
                                type="number"
                                id="duration"
                                name="duration"
                                class="form-control"
                                value="<?php echo $exam["duration"]; ?>"
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
                                value="<?php echo $exam["total_marks"]; ?>"
                                min="1"
                                required
                            >
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 15px;">
                        <button type="submit" class="btn btn-primary">
                            Save Changes
                        </button>
                        <a href="exams.php" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>