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
    header("Location: dashboard.php");
    exit();
}

$room_exam_id = intval($_GET["id"]);


// ==========================================
// FETCH ROOM EXAM
// ==========================================

$sql = "

    SELECT

        re.id,
        re.room_id,
        re.title,
        re.description,
        re.duration,
        re.total_questions,

        r.room_name,
        r.room_code,
        r.start_time,
        r.end_time,
        r.creator_id

    FROM room_exams re

    INNER JOIN rooms r

        ON re.room_id = r.id

    WHERE re.id = ?

    AND r.creator_id = ?

";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die("Database Error: " . $conn->error);

}

$stmt->bind_param(
    "ii",
    $room_exam_id,
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    die("Exam not found or access denied.");

}

$exam = $result->fetch_assoc();

$stmt->close();


// ==========================================
// FETCH QUESTIONS
// ==========================================

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
        marks

    FROM room_questions

    WHERE room_exam_id = ?

    ORDER BY id ASC

";

$question_stmt = $conn->prepare($question_sql);

if (!$question_stmt) {

    die("Database Error: " . $conn->error);

}

$question_stmt->bind_param(
    "i",
    $room_exam_id
);

$question_stmt->execute();

$questions_result = $question_stmt->get_result();

$questions = [];

while (
    $row = $questions_result->fetch_assoc()
) {

    $questions[] = $row;

}

$question_stmt->close();


// ==========================================
// TOTAL MARKS
// ==========================================

$total_marks = 0;

foreach ($questions as $question) {

    $total_marks += intval(
        $question["marks"]
    );

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

    <title>

        Exam Preview - SmartExam

    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .preview-container {

            max-width: 1100px;

            margin: 0 auto;

        }


        .page-header {

            margin-bottom: 30px;

        }


        .page-header h1 {

            font-size: 32px;

            margin-bottom: 10px;

        }


        .page-header p {

            color: var(--text-muted);

        }


        .exam-banner {

            padding: 28px;

            margin-bottom: 25px;

            border-radius: 16px;

            background:

                linear-gradient(

                    135deg,

                    rgba(
                        99,
                        102,
                        241,
                        0.20
                    ),

                    rgba(
                        168,
                        85,
                        247,
                        0.14
                    )

                );

            border:

                1px solid

                rgba(
                    139,
                    92,
                    246,
                    0.35
                );

        }


        .exam-banner h2 {

            margin-bottom: 10px;

        }


        .room-code {

            display: inline-block;

            margin-top: 15px;

            padding: 10px 20px;

            border-radius: 10px;

            background:

                rgba(
                    0,
                    0,
                    0,
                    0.25
                );

            font-size: 22px;

            font-weight: 800;

            letter-spacing: 4px;

        }


        .exam-stats {

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

            margin-bottom: 30px;

        }


        .stat-box {

            padding: 20px;

            text-align: center;

            border-radius: 12px;

            background:

                rgba(
                    255,
                    255,
                    255,
                    0.05
                );

            border:

                1px solid

                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

        }


        .stat-box strong {

            display: block;

            font-size: 26px;

            margin-bottom: 5px;

        }


        .stat-box span {

            color: var(--text-muted);

        }


        .question-card {

            margin-bottom: 20px;

            padding: 25px;

            border-radius: 14px;

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

        }


        .question-number {

            color: var(--accent);

            font-weight: 700;

            margin-bottom: 12px;

        }


        .question-text {

            font-size: 18px;

            font-weight: 600;

            margin-bottom: 18px;

            line-height: 1.6;

        }


        .option {

            padding: 12px 15px;

            margin-bottom: 10px;

            border-radius: 8px;

            background:

                rgba(
                    255,
                    255,
                    255,
                    0.05
                );

        }


        .correct-answer {

            border:

                1px solid

                rgba(
                    34,
                    197,
                    94,
                    0.6
                );

            background:

                rgba(
                    34,
                    197,
                    94,
                    0.12
                );

        }


        .explanation {

            margin-top: 15px;

            padding: 14px;

            border-radius: 8px;

            background:

                rgba(
                    59,
                    130,
                    246,
                    0.10
                );

            color: var(--text-muted);

        }


        .action-buttons {

            display: flex;

            gap: 15px;

            margin-top: 30px;

            margin-bottom: 40px;

        }


        .action-buttons .btn {

            flex: 1;

            padding: 15px;

            text-align: center;

        }


        .warning-box {

            padding: 18px;

            margin-bottom: 25px;

            border-radius: 10px;

            background:

                rgba(
                    245,
                    158,
                    11,
                    0.12
                );

            border:

                1px solid

                rgba(
                    245,
                    158,
                    11,
                    0.35
                );

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
                href="create_room.php"
                class="sidebar-link active"
            >

                <span>🏠</span>

                Create Room

            </a>


            <a
                href="join_room.php"
                class="sidebar-link"
            >

                <span>🔑</span>

                Join Room

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


            <a
                href="ai_exam_generator.php"
                class="sidebar-link"
            >

                <span>🤖</span>

                AI Exam Generator

            </a>

        </nav>


        <div class="sidebar-footer">

            <a
                href="../logout.php"
                class="btn btn-danger"
                style="width:100%;"
            >

                🚪 Logout

            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <div class="preview-container">


            <!-- HEADER -->

            <div class="page-header">

                <h1>

                    👀 AI Exam Preview

                </h1>


                <p>

                    Review the AI-generated examination before making it available to students.

                </p>

            </div>


            <!-- EXAM BANNER -->

            <div class="exam-banner">

                <h2>

                    📝

                    <?php

                    echo htmlspecialchars(
                        $exam["title"]
                    );

                    ?>

                </h2>


                <?php if (
                    !empty($exam["description"])
                ): ?>

                    <p>

                        <?php

                        echo nl2br(

                            htmlspecialchars(

                                $exam["description"]

                            )

                        );

                        ?>

                    </p>

                <?php endif; ?>


                <p>

                    🏠 Room:

                    <strong>

                        <?php

                        echo htmlspecialchars(

                            $exam["room_name"]

                        );

                        ?>

                    </strong>

                </p>


                <p>

                    Share this code with students:

                </p>


                <div class="room-code">

                    <?php

                    echo htmlspecialchars(

                        $exam["room_code"]

                    );

                    ?>

                </div>

            </div>


            <!-- EXAM STATS -->

            <div class="exam-stats">


                <div class="stat-box">

                    <strong>

                        <?php

                        echo count($questions);

                        ?>

                    </strong>

                    <span>

                        Questions

                    </span>

                </div>


                <div class="stat-box">

                    <strong>

                        <?php

                        echo $total_marks;

                        ?>

                    </strong>

                    <span>

                        Total Marks

                    </span>

                </div>


                <div class="stat-box">

                    <strong>

                        <?php

                        echo intval(

                            $exam["duration"]

                        );

                        ?>

                        min

                    </strong>

                    <span>

                        Duration

                    </span>

                </div>


                <div class="stat-box">

                    <strong>

                        🤖 AI

                    </strong>

                    <span>

                        Generated Exam

                    </span>

                </div>

            </div>


            <!-- WARNING -->

            <div class="warning-box">

                ⚠️

                <strong>

                    Review Carefully

                </strong>

                <br>

                Verify that all questions, options, answers, and explanations are correct before confirming the examination.

            </div>


            <!-- QUESTIONS -->

            <?php foreach (

                $questions

                as

                $index => $question

            ): ?>


                <div class="question-card">


                    <div class="question-number">

                        Question

                        <?php

                        echo $index + 1;

                        ?>

                        •

                        <?php

                        echo intval(

                            $question["marks"]

                        );

                        ?>

                        Mark(s)

                    </div>


                    <div class="question-text">

                        <?php

                        echo htmlspecialchars(

                            $question["question_text"]

                        );

                        ?>

                    </div>


                    <?php

                    $options = [

                        "A" => $question["option_a"],

                        "B" => $question["option_b"],

                        "C" => $question["option_c"],

                        "D" => $question["option_d"]

                    ];

                    ?>


                    <?php foreach (

                        $options

                        as $letter => $option

                    ): ?>


                        <?php if (

                            trim($option) !== ""

                        ): ?>


                            <div

                                class="option

                                <?php

                                if (

                                    strtoupper(

                                        $question

                                        ["correct_answer"]

                                    )

                                    ===

                                    $letter

                                ) {

                                    echo "correct-answer";

                                }

                                ?>

                                "

                            >

                                <strong>

                                    <?php

                                    echo $letter;

                                    ?>.

                                </strong>


                                <?php

                                echo htmlspecialchars(

                                    $option

                                );

                                ?>


                                <?php if (

                                    strtoupper(

                                        $question

                                        ["correct_answer"]

                                    )

                                    ===

                                    $letter

                                ): ?>

                                    ✅

                                <?php endif; ?>

                            </div>


                        <?php endif; ?>


                    <?php endforeach; ?>


                    <div class="explanation">

                        💡

                        <strong>

                            Explanation:

                        </strong>


                        <?php

                        echo htmlspecialchars(

                            $question["explanation"]

                        );

                        ?>

                    </div>


                </div>


            <?php endforeach; ?>


            <!-- ACTION BUTTONS -->

            <div class="action-buttons">


                <a

                    href="room_ai_exam_generator.php?room_id=<?php echo $exam["room_id"]; ?>"

                    class="btn btn-secondary"

                >

                    🔄 Generate Again

                </a>


                <a

                    href="room_exam_confirm.php?id=<?php echo $room_exam_id; ?>"

                    class="btn btn-primary"

                >

                    ✅ Confirm Examination

                </a>

            </div>


        </div>


    </main>


</div>


</body>

</html>