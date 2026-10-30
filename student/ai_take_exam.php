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
// FETCH AI EXAM
// ==========================================

$exam_sql = "
    SELECT
        id,
        student_id,
        subject,
        topic,
        question_count,
        difficulty,
        duration,
        mode
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
        marks
    FROM ai_questions
    WHERE ai_exam_id = ?
    ORDER BY id ASC
";

$stmt = $conn->prepare($question_sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param(
    "i",
    $ai_exam_id
);

$stmt->execute();

$questions_result = $stmt->get_result();

$questions = [];

while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}

$stmt->close();


// ==========================================
// VALIDATE QUESTIONS
// ==========================================

if (count($questions) === 0) {
    die("No questions found for this AI exam.");
}


// ==========================================
// TIMER - REFRESH SAFE
// ==========================================

$total_seconds = intval($exam["duration"]) * 60;


// Unique session key for this AI exam
$timer_key = "ai_exam_start_" . $ai_exam_id;


// Start timer only once
if (!isset($_SESSION[$timer_key])) {

    $_SESSION[$timer_key] = time();

}


// Original exam start time
$exam_start_time = $_SESSION[$timer_key];


// Calculate elapsed time
$elapsed_seconds = time() - $exam_start_time;


// Calculate remaining time
$remaining_seconds =
    $total_seconds - $elapsed_seconds;


// If time is already over
if ($remaining_seconds <= 0) {

    unset($_SESSION[$timer_key]);

    header(
        "Location: ai_submit_exam.php?timeout=1&id="
        . $ai_exam_id
    );

    exit();

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
        Take AI Exam - SmartExam
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .exam-topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;

            flex-wrap: wrap;

        }


        .exam-title h1 {

            margin-bottom: 6px;

        }


        .exam-title p {

            color: var(--text-muted);

            margin: 0;

        }


        .timer-box {

            min-width: 150px;

            text-align: center;

            padding: 14px 20px;

            border-radius: 12px;

            background: rgba(239,68,68,0.12);

            border: 1px solid rgba(239,68,68,0.35);

        }


        .timer-label {

            font-size: 12px;

            color: var(--text-muted);

            margin-bottom: 5px;

        }


        #timer {

            font-size: 26px;

            font-weight: 800;

            color: #f87171;

            letter-spacing: 2px;

        }


        .progress-card {

            margin-bottom: 25px;

        }


        .progress-header {

            display: flex;

            justify-content: space-between;

            margin-bottom: 10px;

            font-size: 14px;

        }


        .progress-bar {

            width: 100%;

            height: 8px;

            background: rgba(255,255,255,0.08);

            border-radius: 10px;

            overflow: hidden;

        }


        .progress-fill {

            height: 100%;

            width: 0%;

            background: var(--accent);

            transition: width 0.3s ease;

        }


        .question-card {

            margin-bottom: 25px;

            padding: 28px;

        }


        .question-number {

            color: var(--accent);

            font-weight: 700;

            margin-bottom: 15px;

        }


        .question-text {

            font-size: 19px;

            line-height: 1.6;

            font-weight: 600;

            margin-bottom: 22px;

        }


        .options {

            display: grid;

            gap: 12px;

        }


        .option-label {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 15px;

            border-radius: 10px;

            cursor: pointer;

            background: rgba(255,255,255,0.04);

            border: 1px solid rgba(255,255,255,0.08);

            transition: 0.2s ease;

        }


        .option-label:hover {

            border-color: var(--accent);

            background: rgba(255,255,255,0.07);

        }


        .option-label input {

            margin-top: 4px;

            accent-color: var(--accent);

        }


        .option-letter {

            font-weight: 700;

            min-width: 24px;

        }


        .question-meta {

            margin-top: 20px;

            padding-top: 15px;

            border-top: 1px solid rgba(255,255,255,0.08);

            color: var(--text-muted);

            font-size: 13px;

        }


        .submit-section {

            text-align: center;

            padding: 30px;

            margin-top: 30px;

        }


        .submit-section p {

            color: var(--text-muted);

            margin: 10px 0 20px;

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

                🚪 Logout

            </a>

        </div>


    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <!-- TOP BAR -->

        <div class="exam-topbar">


            <div class="exam-title">


                <h1>

                    🤖

                    <?php

                    echo htmlspecialchars(
                        $exam["subject"]
                    );

                    ?>

                </h1>


                <p>

                    Topic:

                    <?php

                    echo htmlspecialchars(
                        $exam["topic"]
                    );

                    ?>

                </p>


            </div>


            <div class="timer-box">


                <div class="timer-label">

                    TIME REMAINING

                </div>


                <div id="timer">

                    --:--

                </div>


            </div>


        </div>


        <!-- PROGRESS -->


        <div class="card progress-card">


            <div class="progress-header">


                <span>

                    Exam Progress

                </span>


                <span id="progressText">


                    0 /

                    <?php

                    echo count($questions);

                    ?>


                </span>


            </div>


            <div class="progress-bar">


                <div

                    id="progressFill"

                    class="progress-fill"

                >

                </div>


            </div>


        </div>


        <!-- EXAM FORM -->


        <form

            method="POST"

            action="ai_submit_exam.php"

            id="examForm"

        >


            <input

                type="hidden"

                name="ai_exam_id"

                value="<?php echo $ai_exam_id; ?>"

            >


            <?php foreach (

                $questions

                as $index

                =>

                $question

            ):

                $question_number = $index + 1;

            ?>


                <div

                    class="card question-card"

                >


                    <div

                        class="question-number"

                    >

                        Question

                        <?php

                        echo $question_number;

                        ?>

                        of

                        <?php

                        echo count(

                            $questions

                        );

                        ?>


                    </div>


                    <div

                        class="question-text"

                    >

                        <?php

                        echo htmlspecialchars(

                            $question["question_text"]

                        );

                        ?>


                    </div>


                    <div

                        class="options"

                    >


                        <label

                            class="option-label"

                        >


                            <input

                                type="radio"

                                name="answers[<?php echo $question["id"]; ?>]"

                                value="A"

                            >


                            <span

                                class="option-letter"

                            >

                                A.

                            </span>


                            <span>


                                <?php

                                echo htmlspecialchars(

                                    $question["option_a"]

                                );

                                ?>


                            </span>


                        </label>


                        <label

                            class="option-label"

                        >


                            <input

                                type="radio"

                                name="answers[<?php echo $question["id"]; ?>]"

                                value="B"

                            >


                            <span

                                class="option-letter"

                            >

                                B.

                            </span>


                            <span>


                                <?php

                                echo htmlspecialchars(

                                    $question["option_b"]

                                );

                                ?>


                            </span>


                        </label>


                        <label

                            class="option-label"

                        >


                            <input

                                type="radio"

                                name="answers[<?php echo $question["id"]; ?>]"

                                value="C"

                            >


                            <span

                                class="option-letter"

                            >

                                C.

                            </span>


                            <span>


                                <?php

                                echo htmlspecialchars(

                                    $question["option_c"]

                                );

                                ?>


                            </span>


                        </label>


                        <label

                            class="option-label"

                        >


                            <input

                                type="radio"

                                name="answers[<?php echo $question["id"]; ?>]"

                                value="D"

                            >


                            <span

                                class="option-letter"

                            >

                                D.

                            </span>


                            <span>


                                <?php

                                echo htmlspecialchars(

                                    $question["option_d"]

                                );

                                ?>


                            </span>


                        </label>


                    </div>


                    <div

                        class="question-meta"

                    >

                        🎯

                        <?php

                        echo intval(

                            $question["marks"]

                        );

                        ?>

                        Mark


                    </div>


                </div>


            <?php endforeach; ?>


            <!-- SUBMIT -->


            <div

                class="card submit-section"

            >


                <h2>

                    Ready to submit?

                </h2>


                <p>

                    Make sure you have answered all questions before submitting.

                </p>


                <button

                    type="submit"

                    class="btn btn-primary"

                    onclick="return confirmSubmit()"

                >

                    🚀 Submit AI Exam

                </button>


            </div>


        </form>


    </main>


</div>


<script>


    // ==========================================

    // REFRESH-SAFE TIMER

    // ==========================================


    let remainingSeconds =

        <?php echo $remaining_seconds; ?>;


    const timerElement =

        document.getElementById(

            "timer"

        );


    let examSubmitted = false;


    function updateTimer() {


        let minutes =

            Math.floor(

                remainingSeconds / 60

            );


        let seconds =

            remainingSeconds % 60;


        timerElement.textContent =


            String(minutes).padStart(

                2,

                "0"

            )

            +

            ":"

            +

            String(seconds).padStart(

                2,

                "0"

            );


        if (

            remainingSeconds <= 60

        ) {


            timerElement.style.color =

                "#ef4444";


        }


        if (

            remainingSeconds <= 0

        ) {


            if (!examSubmitted) {


                examSubmitted = true;


                alert(

                    "Time is over. Your exam will be submitted automatically."

                );


                document

                    .getElementById(

                        "examForm"

                    )

                    .submit();


            }


            return;


        }


        remainingSeconds--;

    }


    updateTimer();


    setInterval(

        updateTimer,

        1000

    );


    // ==========================================

    // PROGRESS

    // ==========================================


    const totalQuestions =

        <?php echo count($questions); ?>;


    const radioButtons =

        document.querySelectorAll(

            'input[type="radio"]'

        );


    const progressText =

        document.getElementById(

            "progressText"

        );


    const progressFill =

        document.getElementById(

            "progressFill"

        );


    function updateProgress() {


        const answered =

            document.querySelectorAll(

                'input[type="radio"]:checked'

            ).length;


        progressText.textContent =

            answered

            + " / "

            + totalQuestions;


        const percentage =

            (

                answered

                /

                totalQuestions

            )

            *

            100;


        progressFill.style.width =

            percentage + "%";


    }


    radioButtons.forEach(

        function(radio) {


            radio.addEventListener(

                "change",

                updateProgress

            );


        }

    );


    // ==========================================

    // SUBMIT CONFIRMATION

    // ==========================================


    function confirmSubmit() {


        const answered =

            document.querySelectorAll(

                'input[type="radio"]:checked'

            ).length;


        if (

            answered < totalQuestions

        ) {


            return confirm(

                "You have answered "

                + answered

                + " out of "

                + totalQuestions

                + " questions. Submit anyway?"

            );


        }


        return confirm(

            "Are you sure you want to submit the exam?"

        );


    }


</script>


</body>

</html>