<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: attempt_history.php");
    exit();
}

$attempt_id = intval($_GET["id"]);
$student_id = $_SESSION["user_id"];

// Fetch attempt details
$attempt_sql = "
    SELECT
        attempts.id,
        attempts.score,
        attempts.total_questions,
        attempts.correct_answers,
        attempts.submitted_at,
        exams.title
    FROM attempts
    INNER JOIN exams
        ON attempts.exam_id = exams.id
    WHERE attempts.id = ?
    AND attempts.user_id = ?
";

$stmt = $conn->prepare($attempt_sql);
$stmt->bind_param("ii", $attempt_id, $student_id);
$stmt->execute();
$attempt_result = $stmt->get_result();

if ($attempt_result->num_rows !== 1) {
    header("Location: attempt_history.php");
    exit();
}

$attempt = $attempt_result->fetch_assoc();
$stmt->close();

// Fetch question-wise answers
$answer_sql = "
    SELECT
        answers.selected_option,
        answers.is_correct,
        questions.question_text,
        questions.correct_answer,
        questions.option_a,
        questions.option_b,
        questions.option_c,
        questions.option_d
    FROM answers
    INNER JOIN questions
        ON answers.question_id = questions.id
    WHERE answers.attempt_id = ?
    ORDER BY questions.id ASC
";

$stmt = $conn->prepare($answer_sql);
$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$answers_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attempt Details - SmartExam</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .analysis-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-md);
            padding: 25px;
            margin-bottom: 25px;
        }
        .analysis-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }
        .analysis-opt {
            padding: 12px 18px;
            background: rgba(0,0,0,0.15);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-md);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .analysis-opt.user-correct {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.08);
            color: #ffffff;
        }
        .analysis-opt.user-wrong {
            border-color: var(--error);
            background: rgba(239, 68, 68, 0.08);
            color: #ffffff;
        }
        .analysis-opt.should-be {
            border-color: var(--accent-cyan);
            background: rgba(0, 212, 255, 0.05);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.1);
        }
        .opt-indicator {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
        }
        .user-correct .opt-indicator {
            background: var(--success);
            color: #ffffff;
            border-color: var(--success);
        }
        .user-wrong .opt-indicator {
            background: var(--error);
            color: #ffffff;
            border-color: var(--error);
        }
        .should-be .opt-indicator {
            background: var(--accent-cyan);
            color: var(--bg-primary);
            border-color: var(--accent-cyan);
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
                <a href="dashboard.php#exams" class="sidebar-link">
                    <span>▣</span> Available Exams
                </a>
                <a href="attempt_history.php" class="sidebar-link active">
                    <span>⏱️</span> My Attempts
                </a>
                <a href="leaderboard.php" class="sidebar-link">
                    <span>🏆</span> Leaderboard
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
            <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <span class="badge badge-info" style="margin-bottom: 8px;">Attempt Analysis</span>
                    <h1 style="font-size: 28px; margin-bottom: 5px;"><?php echo htmlspecialchars($attempt["title"]); ?></h1>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 0;">Completed on <?php echo date('M d, Y - h:i A', strtotime($attempt["submitted_at"])); ?></p>
                </div>
                <a href="attempt_history.php" class="btn btn-secondary">
                    Back to History
                </a>
            </div>

            <!-- Summary Card -->
            <div class="card" style="margin-bottom: 35px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 5px;">Score Obtained</p>
                    <h2 style="font-size: 36px; margin-bottom: 0; color: #ffffff;">
                        <span class="accent-text"><?php echo $attempt["score"]; ?></span> <span style="font-size: 18px; color: var(--text-muted);">/ <?php echo $attempt["total_questions"]; ?></span>
                    </h2>
                </div>
                <div>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 5px;">Accuracy Rate</p>
                    <?php
                    $accuracy = $attempt["total_questions"] > 0 ? round(($attempt["score"] / $attempt["total_questions"]) * 100, 2) : 0;
                    ?>
                    <h2 style="font-size: 36px; margin-bottom: 0; color: #ffffff;">
                        <?php echo $accuracy; ?>%
                    </h2>
                </div>
                <div>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 5px;">Correct Answers</p>
                    <h2 style="font-size: 36px; margin-bottom: 0; color: var(--success);">
                        <?php echo $attempt["correct_answers"]; ?>
                    </h2>
                </div>
                <div>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 5px;">Exam Status</p>
                    <h2 style="font-size: 36px; margin-bottom: 0;">
                        <?php if ($accuracy >= 70): ?>
                            <span style="color: var(--success); font-size: 24px; font-weight: 700;">Excellent ✔</span>
                        <?php elseif ($accuracy >= 40): ?>
                            <span style="color: var(--warning); font-size: 24px; font-weight: 700;">Passed 👍</span>
                        <?php else: ?>
                            <span style="color: var(--error); font-size: 24px; font-weight: 700;">Failed ✖</span>
                        <?php endif; ?>
                    </h2>
                </div>
            </div>

            <!-- Question-wise Analysis -->
            <h2 style="font-size: 20px; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;">Question-wise Analysis</h2>

            <?php if ($answers_result && $answers_result->num_rows > 0): ?>
                <?php $question_number = 1; ?>
                <?php while ($answer = $answers_result->fetch_assoc()): ?>
                    <?php 
                    $selected = $answer["selected_option"];
                    $correct = $answer["correct_answer"];
                    $is_correct = $answer["is_correct"] == 1;
                    ?>
                    <div class="analysis-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h3 style="font-size: 16px; margin-bottom: 0; color: var(--accent-cyan);">Question <?php echo $question_number; ?></h3>
                            <?php if ($selected === null): ?>
                                <span class="badge badge-warning">Not Answered</span>
                            <?php elseif ($is_correct): ?>
                                <span class="badge badge-success">Correct</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Wrong</span>
                            <?php endif; ?>
                        </div>

                        <p style="font-size: 16px; color: #ffffff; font-weight: 500; margin-bottom: 20px;">
                            <?php echo htmlspecialchars($answer["question_text"]); ?>
                        </p>

                        <div class="analysis-options">
                            <?php 
                            $options = [
                                "A" => $answer["option_a"],
                                "B" => $answer["option_b"],
                                "C" => $answer["option_c"],
                                "D" => $answer["option_d"]
                            ];
                            
                            foreach ($options as $key => $val):
                                // Determine styling class
                                $class = "";
                                $status_indicator = $key;
                                
                                if ($key === $correct) {
                                    $class = "should-be"; // Always highlight correct answer
                                    if ($selected === $key) {
                                        $class = "user-correct";
                                    }
                                } elseif ($selected === $key) {
                                    $class = "user-wrong";
                                }
                            ?>
                                <div class="analysis-opt <?php echo $class; ?>">
                                    <span class="opt-indicator"><?php echo $status_indicator; ?></span>
                                    <span><?php echo htmlspecialchars($val); ?></span>
                                    
                                    <?php if ($key === $correct && $selected === $key): ?>
                                        <span style="margin-left: auto; color: var(--success); font-weight: 600; font-size: 12px;">Your Correct Choice</span>
                                    <?php elseif ($key === $selected && !$is_correct): ?>
                                        <span style="margin-left: auto; color: var(--error); font-weight: 600; font-size: 12px;">Your Incorrect Choice</span>
                                    <?php elseif ($key === $correct): ?>
                                        <span style="margin-left: auto; color: var(--accent-cyan); font-weight: 600; font-size: 12px;">Correct Answer</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $question_number++; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 45px;">
                    <p style="color: var(--text-muted); margin-bottom: 0;">No question details were recorded for this attempt.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>