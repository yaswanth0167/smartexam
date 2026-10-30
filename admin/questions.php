<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

if (!isset($_GET["exam_id"]) || !is_numeric($_GET["exam_id"])) {
    header("Location: exams.php");
    exit();
}

$exam_id = intval($_GET["exam_id"]);

$exam_sql = "
    SELECT id, title
    FROM exams
    WHERE id = ?
";

$stmt = $conn->prepare($exam_sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam_result = $stmt->get_result();

if ($exam_result->num_rows !== 1) {
    header("Location: exams.php");
    exit();
}

$exam = $exam_result->fetch_assoc();
$stmt->close();

$question_sql = "
    SELECT *
    FROM questions
    WHERE exam_id = ?
    ORDER BY id ASC
";

$stmt = $conn->prepare($question_sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$questions_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - SmartExam</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .question-card-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 25px;
        }
        .q-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-md);
            padding: 25px;
        }
        .q-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .options-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .option-item {
            padding: 10px 15px;
            background: rgba(0,0,0,0.15);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-sm);
            font-size: 14px;
            color: var(--text-muted);
        }
        .option-item.correct {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.08);
            color: #ffffff;
        }
    </style>
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <span class="badge badge-info" style="margin-bottom: 8px;">Exam: <?php echo htmlspecialchars($exam["title"]); ?></span>
                    <h1 style="font-size: 28px; margin-bottom: 5px;">Manage Questions</h1>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 0;">Add, modify, or remove questions from this examination.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="add_question.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-primary">
                        <span>➕</span> Add Question
                    </a>
                    <a href="exams.php" class="btn btn-secondary">
                        Back to Exams
                    </a>
                </div>
            </div>

            <?php if ($questions_result && $questions_result->num_rows > 0): ?>
                <div class="question-card-list">
                    <?php $question_number = 1; ?>
                    <?php while ($question = $questions_result->fetch_assoc()): ?>
                        <div class="q-card">
                            <div class="q-header">
                                <h3 style="font-size: 16px; margin-bottom: 0; color: var(--accent-cyan);">Question <?php echo $question_number; ?></h3>
                                <div style="display: flex; gap: 8px;">
                                    <a href="edit_question.php?id=<?php echo $question["id"]; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; border-radius: var(--border-radius-sm);">
                                        Edit
                                    </a>
                                    <a 
                                        href="delete_question.php?id=<?php echo $question["id"]; ?>&exam_id=<?php echo $exam_id; ?>" 
                                        class="btn btn-danger" 
                                        style="padding: 6px 12px; font-size: 12px; border-radius: var(--border-radius-sm);"
                                        onclick="return confirm('Are you sure you want to delete this question?');"
                                    >
                                        Delete
                                    </a>
                                </div>
                            </div>
                            
                            <p style="font-size: 16px; margin-bottom: 20px; color: #ffffff; font-weight: 500;">
                                <?php echo htmlspecialchars($question["question_text"]); ?>
                            </p>
                            
                            <div class="options-list">
                                <div class="option-item <?php echo $question["correct_answer"] === "A" ? "correct" : ""; ?>">
                                    <strong>A:</strong> <?php echo htmlspecialchars($question["option_a"]); ?>
                                    <?php echo $question["correct_answer"] === "A" ? " (Correct)" : ""; ?>
                                </div>
                                <div class="option-item <?php echo $question["correct_answer"] === "B" ? "correct" : ""; ?>">
                                    <strong>B:</strong> <?php echo htmlspecialchars($question["option_b"]); ?>
                                    <?php echo $question["correct_answer"] === "B" ? " (Correct)" : ""; ?>
                                </div>
                                <div class="option-item <?php echo $question["correct_answer"] === "C" ? "correct" : ""; ?>">
                                    <strong>C:</strong> <?php echo htmlspecialchars($question["option_c"]); ?>
                                    <?php echo $question["correct_answer"] === "C" ? " (Correct)" : ""; ?>
                                </div>
                                <div class="option-item <?php echo $question["correct_answer"] === "D" ? "correct" : ""; ?>">
                                    <strong>D:</strong> <?php echo htmlspecialchars($question["option_d"]); ?>
                                    <?php echo $question["correct_answer"] === "D" ? " (Correct)" : ""; ?>
                                </div>
                            </div>
                        </div>
                        <?php $question_number++; ?>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 50px; border-color: rgba(255,255,255,0.05);">
                    <div style="font-size: 48px; margin-bottom: 20px;">❓</div>
                    <h3 style="margin-bottom: 10px;">No Questions Found</h3>
                    <p style="color: var(--text-muted); margin-bottom: 25px;">This exam currently has no questions. Students won't be able to begin the exam until questions are added.</p>
                    <a href="add_question.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-primary">Add First Question</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>