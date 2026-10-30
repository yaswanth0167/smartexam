<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = $_SESSION["user_id"];


// ==========================================
// VALIDATE ATTEMPT ID
// ==========================================

$attempt_id = intval($_GET["id"] ?? 0);

if ($attempt_id <= 0) {
    die("Invalid result.");
}


// ==========================================
// FETCH ATTEMPT + EXAM DETAILS
// ==========================================

$attempt_sql = "
    SELECT
        a.id,
        a.student_id,
        a.ai_exam_id,
        a.score,
        a.total_marks,
        a.percentage,
        a.correct_answers,
        a.wrong_answers,
        a.unanswered,
        a.started_at,
        a.submitted_at,

        e.subject,
        e.topic,
        e.education_level,
        e.exam_type,
        e.difficulty,
        e.question_count,
        e.duration,
        e.mode

    FROM ai_attempts a

    INNER JOIN ai_exams e
        ON a.ai_exam_id = e.id

    WHERE a.id = ?
    AND a.student_id = ?
";

$stmt = $conn->prepare($attempt_sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $attempt_id,
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Result not found or access denied.");
}

$attempt = $result->fetch_assoc();

$stmt->close();


// ==========================================
// FETCH QUESTIONS INTO ARRAY
// ==========================================

$questions = [];

$question_sql = "
    SELECT
        id,
        question_text,
        option_a,
        option_b,
        option_c,
        option_d,
        correct_answer,
        explanation,
        marks,
        difficulty

    FROM ai_questions

    WHERE ai_exam_id = ?

    ORDER BY id ASC
";

$stmt = $conn->prepare($question_sql);

if (!$stmt) {
    die("Question Database Error: " . $conn->error);
}

$stmt->bind_param(
    "i",
    $attempt["ai_exam_id"]
);

$stmt->execute();

$question_result = $stmt->get_result();

while ($row = $question_result->fetch_assoc()) {
    $questions[] = $row;
}

$stmt->close();


// ==========================================
// FETCH STUDENT ANSWERS
// ==========================================

$answers = [];

$answer_sql = "
    SELECT
        question_id,
        selected_answer

    FROM ai_answers

    WHERE attempt_id = ?
";

$stmt = $conn->prepare($answer_sql);

if ($stmt) {

    $stmt->bind_param(
        "i",
        $attempt_id
    );

    $stmt->execute();

    $answer_result = $stmt->get_result();

    while ($row = $answer_result->fetch_assoc()) {

        $answers[
            intval($row["question_id"])
        ] = strtoupper(
            trim(
                $row["selected_answer"] ?? ""
            )
        );

    }

    $stmt->close();
}


// ==========================================
// PERFORMANCE MESSAGE
// ==========================================

$percentage = floatval(
    $attempt["percentage"]
);

if ($percentage >= 90) {
    $performance_message = "Outstanding Performance! 🏆";
} elseif ($percentage >= 75) {
    $performance_message = "Excellent Work! 🎉";
} elseif ($percentage >= 60) {
    $performance_message = "Good Performance! 👍";
} elseif ($percentage >= 40) {
    $performance_message = "Keep Practicing! 💪";
} else {
    $performance_message = "Don't Give Up! Keep Learning! 🚀";
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

    <title>AI Exam Result - SmartExam</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .result-hero {
            text-align: center;
            padding: 40px 20px;
            margin-bottom: 30px;
        }

        .score-circle {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 10px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            margin: 25px auto;
        }

        .score-value {
            font-size: 42px;
            font-weight: 800;
        }

        .score-label {
            color: var(--text-muted);
            font-size: 14px;
        }

        .result-stats {
            display: grid;
            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(180px, 1fr)
                );

            gap: 20px;
            margin-bottom: 35px;
        }

        .result-stat {
            text-align: center;
            padding: 25px;
        }

        .result-stat-value {
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .result-stat-label {
            color: var(--text-muted);
            font-size: 14px;
        }

        .question-review {
            margin-top: 40px;
        }

        .review-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .review-card {
            margin-bottom: 22px;
            padding: 25px;
        }

        .answer-option {
            padding: 14px 15px;
            margin: 9px 0;
            border-radius: 8px;
            background: rgba(255,255,255,0.04);
            border: 1px solid transparent;
        }

        .correct-answer {
            border: 1px solid #22c55e;
            background: rgba(34,197,94,0.12);
        }

        .wrong-answer {
            border: 1px solid #ef4444;
            background: rgba(239,68,68,0.12);
        }

        .unanswered-answer {
            border: 1px solid #f59e0b;
            background: rgba(245,158,11,0.12);
        }

        .answer-status {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-correct {
            color: #86efac;
            background: rgba(34,197,94,0.12);
        }

        .status-wrong {
            color: #fca5a5;
            background: rgba(239,68,68,0.12);
        }

        .status-unanswered {
            color: #fcd34d;
            background: rgba(245,158,11,0.12);
        }

        .explanation {
            margin-top: 18px;
            padding: 16px;
            border-left: 4px solid var(--accent);
            background: rgba(255,255,255,0.04);
            color: var(--text-muted);
            line-height: 1.6;
        }

        .empty-review {
            text-align: center;
            padding: 50px 20px;
        }

    </style>

</head>

<body>

<div class="dashboard-layout">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-logo">
            Smart<span>Exam</span>
        </div>


        <nav class="sidebar-nav">

            <a
                href="dashboard.php"
                class="sidebar-link"
            >
                <span>📊</span>
                Dashboard
            </a>


            <a
                href="ai_exam_generator.php"
                class="sidebar-link"
            >
                <span>🤖</span>
                AI Exam Generator
            </a>


            <a
                href="attempt_history.php"
                class="sidebar-link"
            >
                <span>⏱️</span>
                My Attempts
            </a>


            <a
                href="leaderboard.php"
                class="sidebar-link"
            >
                <span>🏆</span>
                Leaderboard
            </a>

        </nav>


        <div class="sidebar-footer">

            <a
                href="../logout.php"
                class="btn btn-danger"
                style="width: 100%;"
            >
                🚪 Logout
            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <!-- RESULT HERO -->

        <div class="card result-hero">

            <h1>
                AI Exam Completed! 🎉
            </h1>


            <p
                style="
                color: var(--text-muted);
                margin-top: 10px;
                "
            >

                <?php echo htmlspecialchars(
                    $attempt["subject"]
                ); ?>

                -

                <?php echo htmlspecialchars(
                    $attempt["topic"]
                ); ?>

            </p>


            <div class="score-circle">

                <div class="score-value">

                    <?php echo htmlspecialchars(
                        $attempt["percentage"]
                    ); ?>%

                </div>


                <div class="score-label">
                    Final Score
                </div>

            </div>


            <h2>
                <?php echo $performance_message; ?>
            </h2>


            <p
                style="
                color: var(--text-muted);
                "
            >

                Score:

                <strong>

                    <?php echo $attempt["score"]; ?>

                    /

                    <?php echo $attempt["total_marks"]; ?>

                </strong>

            </p>


            <div
                style="
                margin-top: 25px;
                display: flex;
                justify-content: center;
                gap: 12px;
                flex-wrap: wrap;
                "
            >

                <a
                    href="ai_exam_generator.php"
                    class="btn btn-primary"
                >
                    🤖 Generate New AI Exam
                </a>


                <a
                    href="dashboard.php"
                    class="btn btn-secondary"
                >
                    📊 Dashboard
                </a>

            </div>

        </div>


        <!-- RESULT STATS -->

        <div class="result-stats">


            <div class="card result-stat">

                <div class="result-stat-value">
                    <?php echo $attempt["correct_answers"]; ?>
                </div>

                <div class="result-stat-label">
                    ✅ Correct Answers
                </div>

            </div>


            <div class="card result-stat">

                <div class="result-stat-value">
                    <?php echo $attempt["wrong_answers"]; ?>
                </div>

                <div class="result-stat-label">
                    ❌ Wrong Answers
                </div>

            </div>


            <div class="card result-stat">

                <div class="result-stat-value">
                    <?php echo $attempt["unanswered"]; ?>
                </div>

                <div class="result-stat-label">
                    ⭕ Unanswered
                </div>

            </div>


            <div class="card result-stat">

                <div class="result-stat-value">
                    <?php echo count($questions); ?>
                </div>

                <div class="result-stat-label">
                    📝 Total Questions
                </div>

            </div>

        </div>


        <!-- EXAM INFORMATION -->

        <div
            class="card"
            style="margin-bottom: 30px;"
        >

            <h2>
                📋 Exam Information
            </h2>


            <div
                style="
                display: grid;
                grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(200px, 1fr)
                );
                gap: 15px;
                margin-top: 20px;
                "
            >

                <p>

                    <strong>Education:</strong>

                    <?php echo htmlspecialchars(
                        $attempt["education_level"]
                    ); ?>

                </p>


                <p>

                    <strong>Exam Type:</strong>

                    <?php echo htmlspecialchars(
                        $attempt["exam_type"]
                    ); ?>

                </p>


                <p>

                    <strong>Difficulty:</strong>

                    <?php echo htmlspecialchars(
                        $attempt["difficulty"]
                    ); ?>

                </p>


                <p>

                    <strong>Duration:</strong>

                    <?php echo $attempt["duration"]; ?>

                    minutes

                </p>


                <p>

                    <strong>Mode:</strong>

                    <?php echo htmlspecialchars(
                        $attempt["mode"]
                    ); ?>

                </p>

            </div>

        </div>


        <!-- QUESTION REVIEW -->

        <section
            class="question-review"
            id="question-review"
        >

            <div class="review-heading">

                <h2>
                    🧠 Question-wise Review
                </h2>


                <span
                    style="
                    color: var(--text-muted);
                    "
                >

                    <?php echo count($questions); ?>

                    Questions

                </span>

            </div>


            <?php if (count($questions) > 0): ?>


                <?php

                $question_number = 1;

                foreach (
                    $questions
                    as
                    $question
                ):

                    $question_id =
                        intval(
                            $question["id"]
                        );


                    $student_answer =
                        strtoupper(

                            trim(

                                $answers[
                                    $question_id
                                ]
                                ?? ""

                            )

                        );


                    $correct_answer =
                        strtoupper(

                            trim(

                                $question[
                                    "correct_answer"
                                ]

                            )

                        );


                    if (
                        $student_answer === ""
                    ) {

                        $status_class =
                            "status-unanswered";

                        $status_text =
                            "⭕ Not Answered";

                    }

                    elseif (
                        $student_answer
                        ===
                        $correct_answer
                    ) {

                        $status_class =
                            "status-correct";

                        $status_text =
                            "✅ Correct Answer";

                    }

                    else {

                        $status_class =
                            "status-wrong";

                        $status_text =
                            "❌ Wrong Answer";

                    }


                    $options = [

                        "A" =>
                            $question["option_a"],

                        "B" =>
                            $question["option_b"],

                        "C" =>
                            $question["option_c"],

                        "D" =>
                            $question["option_d"]

                    ];

                ?>


                    <div
                        class="card review-card"
                    >

                        <h3>

                            Q<?php echo $question_number; ?>.

                            <?php echo htmlspecialchars(
                                $question["question_text"]
                            ); ?>

                        </h3>


                        <div
                            style="
                            margin-top: 20px;
                            "
                        >

                            <?php foreach (
                                $options
                                as
                                $letter
                                =>
                                $option
                            ):

                                $class = "";

                                if (
                                    $letter
                                    ===
                                    $correct_answer
                                ) {

                                    $class =
                                        "correct-answer";

                                }

                                elseif (

                                    $letter
                                    ===
                                    $student_answer

                                ) {

                                    $class =
                                        "wrong-answer";

                                }

                            ?>

                                <div
                                    class="
                                    answer-option
                                    <?php echo $class; ?>
                                    "
                                >

                                    <strong>

                                        <?php echo $letter; ?>.

                                    </strong>

                                    <?php echo htmlspecialchars(
                                        $option
                                    ); ?>


                                    <?php if (
                                        $letter
                                        ===
                                        $correct_answer
                                    ): ?>

                                        <strong
                                            style="
                                            margin-left: 10px;
                                            "
                                        >
                                            ✅ Correct
                                        </strong>

                                    <?php endif; ?>


                                    <?php if (
                                        $letter
                                        ===
                                        $student_answer
                                    ): ?>

                                        <strong
                                            style="
                                            margin-left: 10px;
                                            "
                                        >
                                            👤 Your Answer
                                        </strong>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <div
                            class="
                            answer-status
                            <?php echo $status_class; ?>
                            "
                        >

                            <?php echo $status_text; ?>

                        </div>


                        <?php if (
                            $student_answer !== ""
                        ): ?>

                            <p
                                style="margin-top: 12px;
                                color: var(--text-muted);
                                "
                            >

                                Your Answer:

                                <strong>

                                    <?php echo $student_answer; ?>

                                </strong>

                                &nbsp; | &nbsp;

                                Correct Answer:

                                <strong>

                                    <?php echo $correct_answer; ?>

                                </strong>

                            </p>

                        <?php else: ?>

                            <p
                                style="
                                margin-top: 12px;
                                color: var(--text-muted);
                                "
                            >

                                Your Answer:

                                <strong>
                                    Not Answered
                                </strong>

                                &nbsp; | &nbsp;

                                Correct Answer:

                                <strong>
                                    <?php echo $correct_answer; ?>
                                </strong>

                            </p>

                        <?php endif; ?>


                        <div
                            class="explanation"
                        >

                            <strong>
                                💡 Explanation:
                            </strong>

                            <br><br>

                            <?php echo htmlspecialchars(
                                $question["explanation"]
                            ); ?>

                        </div>

                    </div>


                <?php

                    $question_number++;

                endforeach;

                ?>


            <?php else: ?>


                <div
                    class="card empty-review"
                >

                    <h3>
                        No question review available.
                    </h3>

                    <p
                        style="
                        color: var(--text-muted);
                        margin-top: 10px;
                        "
                    >

                        No questions were found for this AI exam.

                    </p>

                </div>


            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>