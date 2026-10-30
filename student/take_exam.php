<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

date_default_timezone_set("Asia/Kolkata");


// ============================================================
// 1. VALIDATE PARAMETERS
// ============================================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"]) ||
    !isset($_GET["attempt_id"]) ||
    !is_numeric($_GET["attempt_id"])
) {

    die("Invalid exam or attempt ID.");

}


$exam_id = intval($_GET["id"]);

$attempt_id = intval($_GET["attempt_id"]);

$student_id = intval($_SESSION["user_id"]);


// ============================================================
// 2. FETCH ACTIVE ATTEMPT
// ============================================================

$attempt_sql = "

    SELECT

        a.id,
        a.exam_id,
        a.user_id,
        a.started_at,
        a.submitted_at,

        e.title,
        e.description,
        e.duration

    FROM attempts a

    INNER JOIN exams e

        ON a.exam_id = e.id

    WHERE a.id = ?

    AND a.exam_id = ?

    AND a.user_id = ?

    AND e.status = 'published'

    LIMIT 1

";


$stmt = $conn->prepare($attempt_sql);


if (!$stmt) {

    die(

        "Database error while preparing attempt query: "

        . htmlspecialchars($conn->error)

    );

}


$stmt->bind_param(

    "iii",

    $attempt_id,

    $exam_id,

    $student_id

);


if (!$stmt->execute()) {

    die(

        "Database error while executing attempt query: "

        . htmlspecialchars($stmt->error)

    );

}


$attempt_result = $stmt->get_result();


// ============================================================
// DEBUG IF ATTEMPT IS NOT FOUND
// ============================================================

if ($attempt_result->num_rows !== 1) {

    $stmt->close();

    die("

        <div style='

            font-family: Arial;

            padding: 30px;

            background: #111827;

            color: white;

            min-height: 100vh;

        '>

            <h2 style='color: #f87171;'>

                Attempt Not Found

            </h2>

            <p>

                The exam attempt could not be matched with the current student.

            </p>

            <hr>

            <p>

                <b>Attempt ID:</b>

                " . htmlspecialchars($attempt_id) . "

            </p>

            <p>

                <b>Exam ID:</b>

                " . htmlspecialchars($exam_id) . "

            </p>

            <p>

                <b>Student ID from Session:</b>

                " . htmlspecialchars($student_id) . "

            </p>

            <p>

                <b>Required Exam Status:</b>

                published

            </p>

            <br>

            <a

                href='dashboard.php'

                style='

                    color: white;

                    background: #7c3aed;

                    padding: 10px 20px;

                    text-decoration: none;

                    border-radius: 6px;

                '

            >

                Back to Dashboard

            </a>

        </div>

    ");

}


$attempt = $attempt_result->fetch_assoc();

$stmt->close();


// ============================================================
// 3. PREVENT OPENING COMPLETED ATTEMPT
// ============================================================

if (

    !empty(

        $attempt["submitted_at"]

    )

) {

    header(

        "Location: result.php?id="

        . $attempt_id

    );

    exit();

}


// ============================================================
// 4. CALCULATE REMAINING TIME
// ============================================================

$duration_seconds =

    intval(

        $attempt["duration"]

    )

    *

    60;


$started_timestamp =

    strtotime(

        $attempt["started_at"]

    );


$current_timestamp = time();


$elapsed_seconds = max(0, $current_timestamp - $started_timestamp);

// If MySQL stored time in UTC while PHP is in Asia/Kolkata (offset discrepancy)
if ($elapsed_seconds > $duration_seconds && abs($elapsed_seconds - 19800) <= $duration_seconds) {
    // 5.5 hour UTC to IST offset detected, adjust
    $elapsed_seconds = max(0, $elapsed_seconds - 19800);
}

$remaining_seconds = $duration_seconds - $elapsed_seconds;

// ============================================================
// 5. AUTO SUBMIT IF TIME EXPIRED
// ============================================================

if ($remaining_seconds <= 0) {

    header(

        "Location: submit_exam.php"

        . "?attempt_id="

        . $attempt_id

        . "&auto_submit=1"

    );

    exit();

}


// ============================================================
// 6. FETCH QUESTIONS
// ============================================================

$question_sql = "

    SELECT

        id,

        question_text,

        option_a,

        option_b,

        option_c,

        option_d

    FROM questions

    WHERE exam_id = ?

    ORDER BY RAND()

";


$stmt = $conn->prepare($question_sql);


if (!$stmt) {

    die(

        "Question query failed: "

        . htmlspecialchars($conn->error)

    );

}


$stmt->bind_param(

    "i",

    $exam_id

);


if (!$stmt->execute()) {

    die(

        "Question query execution failed: "

        . htmlspecialchars($stmt->error)

    );

}


$question_result =

    $stmt->get_result();


$questions = [];


while (

    $row =

    $question_result->fetch_assoc()

) {

    $questions[] = $row;

}


$stmt->close();


if (

    count($questions) === 0

) {

    die(

        "No questions found for this examination."

    );

}


// ============================================================
// 7. FETCH PREVIOUS ANSWERS
// ============================================================

$answers_sql = "

    SELECT

        question_id,

        selected_answer

    FROM answers

    WHERE attempt_id = ?

";


$stmt = $conn->prepare($answers_sql);


$previous_answers = [];


if ($stmt) {

    $stmt->bind_param(

        "i",

        $attempt_id

    );


    $stmt->execute();


    $answer_result =

        $stmt->get_result();


    while (

        $answer =

        $answer_result->fetch_assoc()

    ) {

        $previous_answers[

            intval(

                $answer["question_id"]

            )

        ] =

            strtoupper(

                trim(

                    $answer["selected_answer"]

                )

            );

    }


    $stmt->close();

}


$total_questions =

    count($questions);

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

        <?php

        echo htmlspecialchars(

            $attempt["title"]

        );

        ?>

        - SmartExam

    </title>


    <link

        rel="stylesheet"

        href="../assets/css/style.css"

    >


    <style>


        body {

            overflow-x: hidden;

        }


        .exam-header {

            position: sticky;

            top: 0;

            z-index: 100;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            padding: 18px 25px;

            margin-bottom: 25px;

            background:

                rgba(

                    15,

                    23,

                    42,

                    0.96

                );

            border-bottom:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.1

                );

            backdrop-filter: blur(12px);

        }


        .exam-title {

            font-size: 20px;

            font-weight: bold;

        }


        .exam-subtitle {

            color:

                var(--text-muted);

            font-size: 13px;

            margin-top: 5px;

        }


        .timer-box {

            min-width: 150px;

            padding: 12px 18px;

            text-align: center;

            border-radius: 12px;

            background:

                rgba(

                    239,

                    68,

                    68,

                    0.12

                );

            border:

                1px solid

                rgba(

                    239,

                    68,

                    68,

                    0.35

                );

        }


        .timer-label {

            font-size: 11px;

            color:

                var(--text-muted);

            text-transform: uppercase;

            letter-spacing: 1px;

        }


        #timer {

            margin-top: 4px;

            font-size: 25px;

            font-weight: bold;

            color: #fca5a5;

        }


        .exam-layout {

            display: grid;

            grid-template-columns:

                minmax(

                    0,

                    1fr

                )

                280px;

            gap: 25px;

            align-items: start;

        }


        .question-card {

            padding: 30px;

            min-height: 480px;

        }


        .question-number {

            color:

                var(--accent-cyan);

            font-size: 14px;

            font-weight: bold;

            margin-bottom: 18px;

        }


        .question-text {

            font-size: 20px;

            line-height: 1.6;

            margin-bottom: 28px;

            color: #ffffff;

        }


        .options {

            display: flex;

            flex-direction: column;

            gap: 14px;

        }


        .option-label {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 16px;

            cursor: pointer;

            border-radius: 12px;

            border:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.1

                );

            background:

                rgba(

                    255,

                    255,

                    255,

                    0.03

                );

            transition: 0.25s ease;

        }


        .option-label:hover {

            border-color:

                var(--accent);

            background:

                rgba(

                    124,

                    58,

                    237,

                    0.12

                );

        }


        .option-label.selected {

            border-color:

                var(--accent);

            background:

                rgba(

                    124,

                    58,

                    237,

                    0.18

                );

        }


        .option-label input {

            margin-top: 4px;

        }


        .option-text {

            line-height: 1.5;

            color: #ffffff;

        }


        .question-navigation {

            display: grid;

            grid-template-columns:

                repeat(

                    5,

                    1fr

                );

            gap: 8px;

            margin-top: 20px;

        }


        .question-number-btn {

            height: 40px;

            border-radius: 8px;

            border:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.12

                );

            background:

                rgba(

                    255,

                    255,

                    255,

                    0.04

                );

            color: #ffffff;

            cursor: pointer;

        }


        .question-number-btn.active {

            background:

                var(--accent);

            border-color:

                var(--accent);

        }


        .question-number-btn.answered {

            background:

                rgba(

                    34,

                    197,

                    94,

                    0.25

                );

            border-color:

                rgba(

                    34,

                    197,

                    94,

                    0.5

                );

        }


        .navigation-card {

            position: sticky;

            top: 100px;

        }


        .navigation-title {

            font-size: 18px;

            margin-bottom: 15px;

        }


        .progress-text {

            color:

                var(--text-muted);

            font-size: 13px;

            margin-top: 15px;

        }


        .submit-button {

            width: 100%;

            margin-top: 22px;

            padding: 14px;

            font-size: 15px;

        }


        @media (

            max-width: 850px

        ) {

            .exam-layout {

                grid-template-columns: 1fr;

            }


            .navigation-card {

                position: static;

                order: 2;

            }

        }


        @media (

            max-width: 600px

        ) {

            .exam-header {

                flex-direction: column;

            }


            .timer-box {

                width: 100%;

            }


            .question-card {

                padding: 20px;

            }


            .question-text {

                font-size: 17px;

            }

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


            <span

                style="

                    color:

                        var(--text-muted);

                    font-size: 12px;

                "

            >

                Exam in progress

            </span>


        </div>


    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <!-- HEADER -->

        <div class="exam-header">


            <div>


                <div class="exam-title">


                    <?php

                    echo htmlspecialchars(

                        $attempt["title"]

                    );

                    ?>


                </div>


                <div class="exam-subtitle">


                    Question

                    <span

                        id="currentQuestion"

                    >

                        1

                    </span>

                    of

                    <?php

                    echo $total_questions;

                    ?>


                </div>


            </div>


            <div class="timer-box">


                <div class="timer-label">

                    Time Remaining

                </div>


                <div id="timer">

                    --:--

                </div>


            </div>


        </div>


        <!-- EXAM FORM -->

        <form

            method="POST"

            action="submit_exam.php"

            id="examForm"

        >


            <input

                type="hidden"

                name="attempt_id"

                value="<?php

                    echo $attempt_id;

                ?>"

            >


            <input

                type="hidden"

                name="exam_id"

                value="<?php

                    echo $exam_id;

                ?>"

            >


            <div class="exam-layout">


                <!-- QUESTIONS -->

                <div>


                    <?php

                    foreach (

                        $questions

                        as $index =>

                        $question

                    ):

                    ?>


                        <div

                            class="card question-card question"

                            data-index="<?php

                                echo $index;

                            ?>"

                            style="display: <?php

                                echo $index === 0

                                    ? "block"

                                    : "none";

                            ?>;"

                        >


                            <div

                                class="question-number"

                            >

                                QUESTION

                                <?php

                                echo $index + 1;

                                ?>

                                OF

                                <?php

                                echo $total_questions;

                                ?>


                            </div>


                            <div

                                class="question-text"

                            >

                                <?php

                                echo htmlspecialchars(

                                    $question[

                                        "question_text"

                                    ]

                                );

                                ?>


                            </div>


                            <div class="options">


                                <?php

                                $options = [

                                    "A" =>

                                        $question[

                                            "option_a"

                                        ],

                                    "B" =>

                                        $question[

                                            "option_b"

                                        ],

                                    "C" =>

                                        $question[

                                            "option_c"

                                        ],

                                    "D" =>

                                        $question[

                                            "option_d"

                                        ]

                                ];


                                foreach (

                                    $options

                                    as $letter =>

                                    $option

                                ):


                                    if (

                                        trim(

                                            $option

                                        )

                                        ===

                                        ""

                                    ) {

                                        continue;

                                    }


                                    $question_id =

                                        intval(

                                            $question["id"]

                                        );


                                    $checked =

                                        (

                                            isset(

                                                $previous_answers[

                                                    $question_id

                                                ]

                                            )

                                            &&

                                            $previous_answers[

                                                $question_id

                                            ]

                                            ===

                                            $letter

                                        )

                                        ?

                                        "checked"

                                        :

                                        "";

                                ?>


                                    <label

                                        class="option-label <?php

                                            echo $checked

                                                ?

                                                "selected"

                                                :

                                                "";

                                        ?>"

                                    >


                                        <input

                                            type="radio"

                                            name="answers[<?php

                                                echo $question_id;

                                            ?>]"

                                            value="<?php

                                                echo $letter;

                                            ?>"

                                            <?php

                                            echo $checked;

                                            ?>

                                        >


                                        <span

                                            class="option-text"

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


                                        </span>


                                    </label>


                                <?php

                                endforeach;

                                ?>


                            </div>


                        </div>


                    <?php

                    endforeach;

                    ?>


                    <!-- NAVIGATION -->

                    <div

                        style="

                            display: flex;

                            justify-content:

                                space-between;

                            gap: 12px;

                            margin-top: 20px;

                        "

                    >


                        <button

                            type="button"

                            class="btn btn-secondary"

                            id="previousBtn"

                        >

                            ← Previous

                        </button>


                        <button

                            type="button"

                            class="btn btn-primary"

                            id="nextBtn"

                        >

                            Next →

                        </button>


                    </div>


                </div>


                <!-- QUESTION NAVIGATOR -->

                <div

                    class="card navigation-card"

                >


                    <div

                        class="navigation-title"

                    >

                        Question Navigator

                    </div>


                    <div

                        class="question-navigation"

                    >


                        <?php

                        foreach (

                            $questions

                            as $index =>

                            $question

                        ):

                        ?>


                            <button

                                type="button"

                                class="question-number-btn <?php

                                    echo $index === 0

                                        ? "active"

                                        : "";

                                ?>"

                                data-index="<?php

                                    echo $index;

                                ?>"

                            >

                                <?php

                                echo $index + 1;

                                ?>


                            </button>


                        <?php

                        endforeach;

                        ?>


                    </div>


                    <div

                        class="progress-text"

                    >

                        Answered:

                        <strong

                            id="answeredCount"

                        >

                            0

                        </strong>

                        /

                        <?php

                        echo $total_questions;

                        ?>


                    </div>


                    <button

                        type="submit"

                        class="btn btn-primary submit-button"

                        onclick="return confirmSubmit();"

                    >

                        Submit Exam

                    </button>


                </div>


            </div>


        </form>


    </main>


</div>


<script>


// ============================================================
// TIMER
// ============================================================


let remainingSeconds =

    <?php

    echo intval(

        $remaining_seconds

    );

    ?>;


const timerElement =

    document.getElementById(

        "timer"

    );


const examForm =

    document.getElementById(

        "examForm"

    );


let timerInterval;


function updateTimer() {


    let minutes =

        Math.floor(

            remainingSeconds

            /

            60

        );


    let seconds =

        remainingSeconds

        %

        60;


    timerElement.textContent =

        String(

            minutes

        ).padStart(

            2,

            "0"

        )

        + ":"

        +

        String(

            seconds

        ).padStart(

            2,

            "0"

        );


    if (

        remainingSeconds <= 60

    ) {

        timerElement.style.color =

            "#f87171";

    }


    if (

        remainingSeconds <= 0

    ) {

        clearInterval(

            timerInterval

        );


        alert(

            "Time is over. Your exam will be submitted automatically."

        );


        examForm.submit();


        return;

    }


    remainingSeconds--;

}


updateTimer();


timerInterval =

    setInterval(

        updateTimer,

        1000

    );


// ============================================================
// QUESTION NAVIGATION
// ============================================================


const questionCards =

    document.querySelectorAll(

        ".question"

    );


const navButtons =

    document.querySelectorAll(

        ".question-number-btn"

    );


const currentQuestionElement =

    document.getElementById(

        "currentQuestion"

    );


const previousBtn =

    document.getElementById(

        "previousBtn"

    );


const nextBtn =

    document.getElementById(

        "nextBtn"

    );


let currentIndex = 0;


function showQuestion(

    index

) {


    if (

        index < 0

        ||

        index >= questionCards.length

    ) {

        return;

    }


    questionCards.forEach(

        (

            card,

            i

        ) => {


            card.style.display =

                i === index

                    ? "block"

                    : "none";

        }

    );


    navButtons.forEach(

        (

            button,

            i

        ) => {


            button.classList.toggle(

                "active",

                i === index

            );

        }

    );


    currentIndex = index;


    currentQuestionElement.textContent =

        index + 1;


    previousBtn.disabled =

        index === 0;


    nextBtn.textContent =

        index ===

        questionCards.length - 1

            ? "Review →"

            : "Next →";


    window.scrollTo({

        top: 0,

        behavior: "smooth"

    });

}


previousBtn.addEventListener(

    "click",

    function () {

        showQuestion(

            currentIndex - 1

        );

    }

);


nextBtn.addEventListener(

    "click",

    function () {


        if (

            currentIndex <

            questionCards.length - 1

        ) {

            showQuestion(

                currentIndex + 1

            );

        }

        else {


            alert(

                "You are on the last question. Please review your answers and submit the exam."

            );

        }

    }

);


navButtons.forEach(

    (

        button

    ) => {


        button.addEventListener(

            "click",

            function () {


                showQuestion(

                    parseInt(

                        this.dataset.index

                    )

                );

            }

        );

    }

);


// ============================================================
// SELECTED OPTIONS
// ============================================================


const optionLabels =

    document.querySelectorAll(

        ".option-label"

    );


optionLabels.forEach(

    (

        label

    ) => {


        const radio =

            label.querySelector(

                "input"

            );


        radio.addEventListener(

            "change",

            function () {


                const questionCard =

                    label.closest(

                        ".question"

                    );


                questionCard

                    .querySelectorAll(

                        ".option-label"

                    )

                    .forEach(

                        (

                            other

                        ) => {


                            other.classList.remove(

                                "selected"

                            );

                        }

                    );


                label.classList.add(

                    "selected"

                );


                const questionIndex =

                    parseInt(

                        questionCard.dataset.index

                    );


                navButtons[

                    questionIndex

                ].classList.add(

                    "answered"

                );


                updateAnsweredCount();

            }

        );

    }

);


// ============================================================
// UPDATE ANSWERED COUNT
// ============================================================


function updateAnsweredCount() {


    const checkedAnswers =

        document.querySelectorAll(

            "input[type='radio']:checked"

        );


    document.getElementById(

        "answeredCount"

    ).textContent =

        checkedAnswers.length;


}


updateAnsweredCount();


// ============================================================
// SUBMIT CONFIRMATION
// ============================================================


function confirmSubmit() {


    const answered =

        document.querySelectorAll(

            "input[type='radio']:checked"

        ).length;


    const unanswered =

        <?php

        echo $total_questions;

        ?>

        -

        answered;


    if (

        unanswered > 0

    ) {


        return confirm(

            "You have "

            +

            unanswered

            +

            " unanswered question(s). Are you sure you want to submit?"

        );

    }


    return confirm(

        "Are you sure you want to submit the exam?"

    );

}


// ============================================================
// PREVENT ACCIDENTAL REFRESH/BACK
// ============================================================


window.addEventListener(

    "beforeunload",

    function (

        event

    ) {


        if (

            remainingSeconds > 0

        ) {

            event.preventDefault();

            event.returnValue = "";

        }

    }

);

</script>


</body>

</html>