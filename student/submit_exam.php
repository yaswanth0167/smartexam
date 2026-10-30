<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

// ==========================================
// 1. CHECK REQUEST METHOD
// ==========================================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    if (isset($_GET["auto_submit"]) && isset($_GET["attempt_id"])) {
        $attempt_id = intval($_GET["attempt_id"]);
        $conn->query("UPDATE attempts SET submitted_at = NOW() WHERE id = $attempt_id AND submitted_at IS NULL");
        header("Location: result.php?id=" . $attempt_id);
        exit();
    }
    header("Location: dashboard.php");
    exit();
}

// ==========================================
// 2. VALIDATE EXAM ID
// ==========================================
if (!isset($_POST["exam_id"]) || !is_numeric($_POST["exam_id"])) {
    header("Location: dashboard.php");
    exit();
}
$exam_id = intval($_POST["exam_id"]);
$student_id = $_SESSION["user_id"];
$answers = $_POST["answers"] ?? [];

// ==========================================
// 3. VALIDATE ATTEMPT ID
// ==========================================
if (!isset($_POST["attempt_id"]) || !is_numeric($_POST["attempt_id"])) {
    header("Location: dashboard.php");
    exit();
}
$attempt_id = intval($_POST["attempt_id"]);

// ==========================================
// 4. FETCH EXAM
// ==========================================
$exam_sql = "
    SELECT id, title, duration, total_marks
    FROM exams
    WHERE id = ? AND status = 'published'
";
$stmt = $conn->prepare($exam_sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam_result = $stmt->get_result();

if ($exam_result->num_rows !== 1) {
    header("Location: dashboard.php");
    exit();
}
$exam = $exam_result->fetch_assoc();
$stmt->close();

// ==========================================
// 5. VERIFY ACTIVE ATTEMPT
// ==========================================
$attempt_sql = "
    SELECT id, started_at
    FROM attempts
    WHERE id = ? AND user_id = ? AND exam_id = ? AND submitted_at IS NULL
";
$stmt = $conn->prepare($attempt_sql);
$stmt->bind_param("iii", $attempt_id, $student_id, $exam_id);
$stmt->execute();
$attempt_result = $stmt->get_result();

if ($attempt_result->num_rows !== 1) {
    header("Location: dashboard.php");
    exit();
}
$attempt = $attempt_result->fetch_assoc();
$stmt->close();

// ==========================================
// 7. FETCH CORRECT ANSWERS
// ==========================================
$question_sql = "
    SELECT id, correct_answer
    FROM questions
    WHERE exam_id = ?
";
$stmt = $conn->prepare($question_sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$questions_result = $stmt->get_result();

$total_questions = $questions_result->num_rows;
$score = 0;
$question_answers = [];

// ==========================================
// 8. CHECK EACH ANSWER
// ==========================================
while ($question = $questions_result->fetch_assoc()) {
    $question_id = $question["id"];
    $correct_answer = $question["correct_answer"];
    $selected_answer = $answers[$question_id] ?? null;
    $is_correct = 0;

    if ($selected_answer !== null && $selected_answer === $correct_answer) {
        $score++;
        $is_correct = 1;
    }

    $question_answers[] = [
        "question_id" => $question_id,
        "selected_answer" => $selected_answer,
        "is_correct" => $is_correct
    ];
}
$stmt->close();

// ==========================================
// 9. UPDATE ATTEMPT
// ==========================================
$update_attempt = "
    UPDATE attempts
    SET
        score = ?,
        total_questions = ?,
        correct_answers = ?,
        submitted_at = NOW()
    WHERE id = ?
    AND user_id = ?
    AND exam_id = ?
    AND submitted_at IS NULL
";
$stmt = $conn->prepare($update_attempt);
$correct_answers = $score;
$stmt->bind_param("iiiiii", $score, $total_questions, $correct_answers, $attempt_id, $student_id, $exam_id);
$stmt->execute();
$stmt->close();

// ==========================================
// 10. INSERT ANSWERS
// ==========================================
$insert_answer = "
    INSERT INTO answers
    (attempt_id, question_id, selected_option, is_correct)
    VALUES (?, ?, ?, ?)
";

foreach ($question_answers as $answer) {
    $question_id = $answer["question_id"];
    $selected_option = $answer["selected_answer"];
    $is_correct = $answer["is_correct"];

    $stmt = $conn->prepare($insert_answer);
    $stmt->bind_param("iisi", $attempt_id, $question_id, $selected_option, $is_correct);
    $stmt->execute();
    $stmt->close();
}

$percentage = $total_questions > 0 ? round(($score / $total_questions) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Completed - SmartExam</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .result-score-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(0, 212, 255, 0.05);
            border: 3px solid var(--accent-cyan);
            margin: 25px auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
        }
        .result-score-val {
            font-size: 38px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.1;
        }
        .result-score-lbl {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card" style="text-align: center; max-width: 500px; padding: 45px 35px;">
            <div style="font-size: 54px; margin-bottom: 15px;">🏆</div>
            <h1 style="font-size: 26px; margin-bottom: 8px;">Exam Submitted!</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Your answers were successfully evaluated.</p>
            
            <div class="result-score-circle">
                <span class="result-score-val"><?php echo $score; ?>/<?php echo $total_questions; ?></span>
                <span class="result-score-lbl">Total Score</span>
            </div>

            <div style="margin-bottom: 30px;">
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 8px;">Accuracy Percentage</p>
                <h3 style="font-size: 28px; margin-bottom: 15px;" class="accent-text"><?php echo $percentage; ?>%</h3>
                
                <?php if ($percentage >= 70): ?>
                    <div class="badge badge-success" style="font-size: 13px; padding: 8px 16px;">Pass - Grade A</div>
                <?php elseif ($percentage >= 40): ?>
                    <div class="badge badge-warning" style="font-size: 13px; padding: 8px 16px;">Pass - Grade B</div>
                <?php else: ?>
                    <div class="badge badge-danger" style="font-size: 13px; padding: 8px 16px;">Fail</div>
                <?php endif; ?>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="attempt_details.php?id=<?php echo $attempt_id; ?>" class="btn btn-primary">
                    View Detailed Analysis
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>