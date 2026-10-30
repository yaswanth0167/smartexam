<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = $_SESSION["user_id"];


// ==========================================
// VALIDATE EXAM ID
// ==========================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: ai_exam_generator.php");
    exit();
}

$ai_exam_id = intval($_GET["id"]);


// ==========================================
// FETCH AI EXAM DETAILS
// ==========================================

$exam_sql = "

    SELECT
        id,
        student_id,
        education_level,
        exam_type,
        stream,
        subject,
        topic,
        question_count,
        difficulty,
        question_type,
        duration,
        mode,
        created_at

    FROM ai_exams

    WHERE id = ?
    AND student_id = ?

";

$stmt = $conn->prepare($exam_sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $ai_exam_id,
    $student_id
);

$stmt->execute();

$exam_result = $stmt->get_result();

if ($exam_result->num_rows !== 1) {
    die("AI Exam not found or access denied.");
}

$exam = $exam_result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        AI Exam Preview - SmartExam
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .preview-header {

            margin-bottom: 30px;

        }

        .ai-badge {

            display: inline-block;

            padding: 7px 14px;

            border-radius: 20px;

            background:
                rgba(
                    99,
                    102,
                    241,
                    0.15
                );

            color: #818cf8;

            font-size: 12px;

            font-weight: 700;

            margin-bottom: 15px;

        }

        .exam-info-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(
                        180px,
                        1fr
                    )
                );

            gap: 15px;

            margin-top: 25px;

        }

        .info-box {

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.04
                );

            border:

                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

            border-radius: 12px;

            padding: 18px;

        }

        .info-label {

            color: var(--text-muted);

            font-size: 12px;

            margin-bottom: 7px;

            text-transform: uppercase;

        }

        .info-value {

            font-size: 16px;

            font-weight: 600;

        }

        .instructions {

            margin-top: 30px;

        }

        .instruction-list {

            list-style: none;

            padding: 0;

            margin: 20px 0 0;

        }

        .instruction-list li {

            padding: 13px 15px;

            margin-bottom: 10px;

            border-radius: 9px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.04
                );

            color: var(--text-muted);

        }

        .instruction-list li strong {

            color: #ffffff;

        }

        .action-section {

            margin-top: 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            flex-wrap: wrap;

        }

        .action-section p {

            color: var(--text-muted);

            margin: 0;

        }

        .action-buttons {

            display: flex;

            gap: 12px;

            flex-wrap: wrap;

        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =====================================
         SIDEBAR
    ====================================== -->

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
                class="sidebar-link active"
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
                style="width:100%;"
            >

                <span>🚪</span>

                Logout

            </a>


        </div>


    </aside>


    <!-- =====================================
         MAIN CONTENT
    ====================================== -->

    <main class="main-content">


        <!-- =====================================
             HEADER
        ====================================== -->

        <div class="hero preview-header">


            <div class="ai-badge">

                🤖 AI GENERATED EXAM

            </div>


            <h1>

                <?php

                echo htmlspecialchars(
                    $exam["subject"]
                );

                ?>

            </h1>


            <p>

                Topic:

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $exam["topic"]
                    );

                    ?>

                </strong>

            </p>


            <p
                style="
                margin-top:10px;
                "
            >

                Your personalized AI examination is ready.

                Review the exam details and start whenever you are ready.

            </p>


        </div>


        <!-- =====================================
             EXAM INFORMATION
        ====================================== -->

        <div class="card">


            <h2>

                📋 Exam Information

            </h2>


            <div class="exam-info-grid">


                <div class="info-box">


                    <div class="info-label">

                        Education Level

                    </div>


                    <div class="info-value">

                        <?php

                        echo htmlspecialchars(
                            $exam["education_level"]
                        );

                        ?>

                    </div>


                </div>


                <div class="info-box">


                    <div class="info-label">

                        Exam Type

                    </div>


                    <div class="info-value">

                        <?php

                        echo htmlspecialchars(
                            $exam["exam_type"]
                        );

                        ?>

                    </div>


                </div>


                <?php if (!empty($exam["stream"])): ?>


                    <div class="info-box">


                        <div class="info-label">

                            Stream

                        </div>


                        <div class="info-value">

                            <?php

                            echo htmlspecialchars(
                                $exam["stream"]
                            );

                            ?>

                        </div>


                    </div>


                <?php endif; ?>


                <div class="info-box">


                    <div class="info-label">

                        Questions

                    </div>


                    <div class="info-value">

                        <?php

                        echo intval(
                            $exam["question_count"]
                        );

                        ?>

                    </div>


                </div>


                <div class="info-box">


                    <div class="info-label">

                        Duration

                    </div>


                    <div class="info-value">

                        <?php

                        echo intval(
                            $exam["duration"]
                        );

                        ?>

                        Minutes

                    </div>


                </div>


                <div class="info-box">


                    <div class="info-label">

                        Difficulty

                    </div>


                    <div class="info-value">

                        <?php

                        echo ucfirst(

                            htmlspecialchars(

                                $exam["difficulty"]

                            )

                        );

                        ?>

                    </div>


                </div>


                <div class="info-box">


                    <div class="info-label">

                        Question Type

                    </div>


                    <div class="info-value">

                        <?php

                        if (
                            $exam["question_type"]
                            ===
                            "true_false"
                        ) {

                            echo "True / False";

                        } else {

                            echo "Multiple Choice";

                        }

                        ?>

                    </div>


                </div>


                <div class="info-box">


                    <div class="info-label">

                        Mode

                    </div>


                    <div class="info-value">

                        <?php

                        echo ucfirst(

                            str_replace(

                                "_",

                                " ",

                                htmlspecialchars(

                                    $exam["mode"]

                                )

                            )

                        );

                        ?>

                    </div>


                </div>


            </div>


        </div>


        <!-- =====================================
             IMPORTANT INSTRUCTIONS
        ====================================== -->

        <div
            class="card instructions"
        >


            <h2>

                ⚠️ Important Instructions

            </h2>


            <ul class="instruction-list">


                <li>

                    ⏱️

                    <strong>

                        Timer:

                    </strong>

                    The exam timer starts immediately after clicking

                    <strong>

                        Start AI Exam

                    </strong>

                    and cannot be paused.

                </li>


                <li>

                    📝

                    <strong>

                        Questions:

                    </strong>

                    Answer all questions carefully before submitting.

                </li>


                <li>

                    🔄

                    <strong>

                        Navigation:

                    </strong>

                    You can review your answers before submitting the exam.

                </li>


                <li>

                    🚀

                    <strong>

                        Auto Submit:

                    </strong>

                    The exam will be automatically submitted when the timer reaches zero.

                </li>


                <li>

                    🧠

                    <strong>

                        Review:

                    </strong>

                    Correct answers and explanations will be available after submitting the exam.

                </li>


            </ul>


        </div>


        <!-- =====================================
             ACTIONS
        ====================================== -->

        <div class="card action-section">


            <p>

                🎯

                Ready to test your knowledge?

            </p>


            <div class="action-buttons">


                <a
                    href="ai_exam_generator.php"
                    class="btn btn-secondary"
                >

                    ← Generate Another Exam

                </a>


                <a
                    href="ai_take_exam.php?id=<?php echo $ai_exam_id; ?>"
                    class="btn btn-primary"
                >

                    🚀 Start AI Exam

                </a>


            </div>


        </div>


    </main>


</div>


</body>

</html>