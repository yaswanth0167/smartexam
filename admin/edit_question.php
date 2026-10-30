<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: exams.php");
    exit();
}

$question_id = intval($_GET["id"]);
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $question_text = trim($_POST["question_text"]);
    $option_a = trim($_POST["option_a"]);
    $option_b = trim($_POST["option_b"]);
    $option_c = trim($_POST["option_c"]);
    $option_d = trim($_POST["option_d"]);
    $correct_answer = $_POST["correct_answer"];

    if (
        empty($question_text) ||
        empty($option_a) ||
        empty($option_b) ||
        empty($option_c) ||
        empty($option_d)
    ) {
        $message = "All fields are required!";
        $message_type = "error";
    } elseif (!in_array($correct_answer, ["A", "B", "C", "D"])) {
        $message = "Invalid correct answer!";
        $message_type = "error";
    } else {
        $sql = "
            UPDATE questions
            SET question_text = ?,
                option_a = ?,
                option_b = ?,
                option_c = ?,
                option_d = ?,
                correct_answer = ?
            WHERE id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssi",
            $question_text,
            $option_a,
            $option_b,
            $option_c,
            $option_d,
            $correct_answer,
            $question_id
        );

        if ($stmt->execute()) {
            $message = "Question updated successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to update question!";
            $message_type = "error";
        }
        $stmt->close();
    }
}

$sql = "
    SELECT *
    FROM questions
    WHERE id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $question_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: exams.php");
    exit();
}

$question = $result->fetch_assoc();
$exam_id = $question["exam_id"];
$stmt->close();

// Fetch exam details to show name
$exam_res = $conn->query("SELECT title FROM exams WHERE id = $exam_id");
$exam = $exam_res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question - SmartExam</title>
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
                <span class="badge badge-info" style="margin-bottom: 8px;">Exam: <?php echo htmlspecialchars($exam["title"]); ?></span>
                <h1 style="font-size: 28px; margin-bottom: 5px;">Edit Question</h1>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 0;">Modify the question description and its options.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-danger'; ?>" style="max-width: 700px;">
                    <span><?php echo $message_type === 'success' ? '✔' : '⚠️'; ?></span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 700px;">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="question_text">Question Text</label>
                        <textarea
                            id="question_text"
                            name="question_text"
                            class="form-control"
                            rows="4"
                            required
                        ><?php echo htmlspecialchars($question["question_text"]); ?></textarea>
                    </div>

                    <div class="grid-2" style="gap: 15px;">
                        <div class="form-group">
                            <label class="form-label" for="option_a">Option A</label>
                            <input
                                type="text"
                                id="option_a"
                                name="option_a"
                                class="form-control"
                                value="<?php echo htmlspecialchars($question["option_a"]); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="option_b">Option B</label>
                            <input
                                type="text"
                                id="option_b"
                                name="option_b"
                                class="form-control"
                                value="<?php echo htmlspecialchars($question["option_b"]); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="option_c">Option C</label>
                            <input
                                type="text"
                                id="option_c"
                                name="option_c"
                                class="form-control"
                                value="<?php echo htmlspecialchars($question["option_c"]); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="option_d">Option D</label>
                            <input
                                type="text"
                                id="option_d"
                                name="option_d"
                                class="form-control"
                                value="<?php echo htmlspecialchars($question["option_d"]); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group" style="max-width: 330px;">
                        <label class="form-label" for="correct_answer">Correct Answer</label>
                        <select
                            id="correct_answer"
                            name="correct_answer"
                            class="form-control"
                            required
                        >
                            <option value="A" <?php if ($question["correct_answer"] === "A") echo "selected"; ?>>Option A</option>
                            <option value="B" <?php if ($question["correct_answer"] === "B") echo "selected"; ?>>Option B</option>
                            <option value="C" <?php if ($question["correct_answer"] === "C") echo "selected"; ?>>Option C</option>
                            <option value="D" <?php if ($question["correct_answer"] === "D") echo "selected"; ?>>Option D</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 15px;">
                        <button type="submit" class="btn btn-primary">
                            Save Changes
                        </button>
                        <a href="questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>