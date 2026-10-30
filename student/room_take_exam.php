<?php

// ============================================================
// ROOM TAKE EXAM
// REFRESH-SAFE TIMER VERSION
// ============================================================

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();


// ============================================================
// STUDENT INFORMATION
// ============================================================

$student_id = $_SESSION["user_id"];


// ============================================================
// VALIDATE ROOM ID
// ============================================================

if (

    !isset($_GET["room_id"]) ||

    !is_numeric($_GET["room_id"])

) {

    header("Location: dashboard.php");

    exit();

}


$room_id = intval($_GET["room_id"]);


// ============================================================
// TIMEZONE
// ============================================================

date_default_timezone_set("Asia/Kolkata");


// ============================================================
// FETCH ROOM + EXAM
// ============================================================

$sql = "

    SELECT

        r.id AS room_id,

        r.room_code,

        r.room_name,

        r.start_time,

        r.end_time,

        r.status,

        r.exam_type,

        re.id AS exam_id,

        re.title,

        re.description,

        re.duration,

        re.total_questions

    FROM rooms r

    INNER JOIN room_exams re

        ON r.id = re.room_id

    WHERE r.id = ?

    LIMIT 1

";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(

        "Database Error: "

        . htmlspecialchars($conn->error)

    );

}


$stmt->bind_param(

    "i",

    $room_id

);


$stmt->execute();


$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    die("Exam room not found.");

}


$exam = $result->fetch_assoc();


$stmt->close();


// ============================================================
// EXAM ID
// ============================================================

$exam_id = intval(

    $exam["exam_id"]

);


// ============================================================
// CURRENT TIME
// ============================================================

$current_time = time();


$start_timestamp = strtotime(

    $exam["start_time"]

);


$end_timestamp = strtotime(

    $exam["end_time"]

);


// ============================================================
// ROOM TIME VALIDATION
// ============================================================

if (

    $current_time < $start_timestamp

) {

    die(

        "This examination has not started yet."

    );

}


if (

    $current_time > $end_timestamp

) {

    die(

        "This examination has ended."

    );

}


// ============================================================
// FIND EXISTING ACTIVE ATTEMPT
// ============================================================
//
// IMPORTANT:
//
// We check whether the student already started this room exam.
//
// If an attempt exists:
//
//     USE THE SAME started_at
//
// If no attempt exists:
//
//     CREATE A NEW attempt
//
// Therefore refreshing the page does not restart the timer.
//

$attempt_sql = "

    SELECT

        id,

        started_at

    FROM room_attempts

    WHERE user_id = ?

    AND room_exam_id = ?

    AND submitted_at IS NULL

    ORDER BY id DESC

    LIMIT 1

";


$attempt_stmt = $conn->prepare(

    $attempt_sql

);


if (!$attempt_stmt) {

    die(

        "Database Error: "

        . htmlspecialchars(

            $conn->error

        )

    );

}


$attempt_stmt->bind_param(

    "ii",

    $student_id,

    $exam_id

);


$attempt_stmt->execute();


$attempt_result =

    $attempt_stmt->get_result();


if (

    $attempt_result->num_rows === 1

) {


    // ========================================================
    // EXISTING ATTEMPT
    // ========================================================

    $attempt =

        $attempt_result->fetch_assoc();


    $attempt_id = intval(

        $attempt["id"]

    );


    $started_at =

        $attempt["started_at"];


    $attempt_stmt->close();

}

else {


    // ========================================================
    // CREATE NEW ATTEMPT
    // ========================================================

    $attempt_stmt->close();


    $insert_attempt_sql = "

        INSERT INTO room_attempts

        (

            user_id,

            room_exam_id,

            score,

            total_questions,

            correct_answers,

            started_at,

            submitted_at

        )

        VALUES

        (

            ?,

            ?,

            0,

            0,

            0,

            NOW(),

            NULL

        )

    ";


    $insert_stmt =

        $conn->prepare(

            $insert_attempt_sql

        );


    if (!$insert_stmt) {

        die(

            "Database Error: "

            . htmlspecialchars(

                $conn->error

            )

        );

    }


    $insert_stmt->bind_param(

        "ii",

        $student_id,

        $exam_id

    );


    if (

        !$insert_stmt->execute()

    ) {

        die(

            "Attempt Creation Error: "

            . htmlspecialchars(

                $insert_stmt->error

            )

        );

    }


    $attempt_id =

        $insert_stmt->insert_id;


    $insert_stmt->close();


    // Get exact database start time

    $time_stmt = $conn->prepare("

        SELECT started_at

        FROM room_attempts

        WHERE id = ?

        LIMIT 1

    ");


    $time_stmt->bind_param(

        "i",

        $attempt_id

    );


    $time_stmt->execute();


    $time_result =

        $time_stmt->get_result();


    $time_data =

        $time_result->fetch_assoc();


    $started_at =

        $time_data["started_at"];


    $time_stmt->close();

}


// ============================================================
// CALCULATE TIMER FROM STORED START TIME
// ============================================================

$started_timestamp = strtotime(

    $started_at

);


$elapsed_seconds =

    $current_time

    -

    $started_timestamp;


$exam_duration_seconds =

    intval(

        $exam["duration"]

    )

    *

    60;


$remaining_exam_seconds =

    $exam_duration_seconds

    -

    $elapsed_seconds;


// Remaining room time

$remaining_room_seconds =

    $end_timestamp

    -

    $current_time;


// Final timer is whichever finishes first

$remaining_seconds = min(

    $remaining_exam_seconds,

    $remaining_room_seconds

);


// ============================================================
// AUTO-SUBMIT IF TIME IS OVER
// ============================================================

if (

    $remaining_seconds <= 0

) {

    header(

        "Location: room_submit_exam.php"

        . "?exam_id="

        . $exam_id

        . "&room_id="

        . $room_id

        . "&auto_submit=1"

    );

    exit();

}


// ============================================================
// FETCH QUESTIONS
// ============================================================

$question_sql = "

    SELECT

        id,

        question_text,

        option_a,

        option_b,

        option_c,

        option_d,

        marks

    FROM room_questions

    WHERE room_exam_id = ?

    ORDER BY id ASC

";


$question_stmt = $conn->prepare(

    $question_sql

);


if (!$question_stmt) {

    die(

        "Database Error: "

        . htmlspecialchars(

            $conn->error

        )

    );

}


$question_stmt->bind_param(

    "i",

    $exam_id

);


$question_stmt->execute();


$question_result =

    $question_stmt->get_result();


$questions = [];


while (

    $row =

    $question_result->fetch_assoc()

) {

    $questions[] = $row;

}


$question_stmt->close();


if (

    count($questions) === 0

) {

    die(

        "No questions are available for this examination."

    );

}


$formatted_end = date(

    "d M Y, h:i A",

    $end_timestamp

);

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

        Take Examination - SmartExam

    </title>


    <link

        rel="stylesheet"

        href="../assets/css/style.css"

    >


    <style>

        * {

            box-sizing: border-box;

        }


        body {

            overflow-x: hidden;

        }


        .exam-container {

            max-width: 1200px;

            margin: 0 auto;

        }


        .exam-header {

            position: sticky;

            top: 0;

            z-index: 100;

            padding: 18px 22px;

            margin-bottom: 25px;

            border-radius: 16px;

            background:

                rgba(

                    20,

                    20,

                    35,

                    0.94

                );

            backdrop-filter: blur(15px);

            border:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.10

                );

        }


        .exam-header-top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

        }


        .exam-title h1 {

            font-size: 24px;

            margin-bottom: 6px;

        }


        .exam-title p {

            color: var(--text-muted);

            margin: 0;

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

                    0.15

                );

            border:

                1px solid

                rgba(

                    239,

                    68,

                    68,

                    0.4

                );

        }


        .timer-label {

            display: block;

            color: var(--text-muted);

            font-size: 12px;

            margin-bottom: 4px;

        }


        #timer {

            font-size: 25px;

            font-weight: 900;

            letter-spacing: 2px;

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

            border-radius: 16px;

        }


        .question-number {

            display: inline-block;

            margin-bottom: 18px;

            padding: 7px 14px;

            border-radius: 8px;

            background:

                rgba(

                    99,

                    102,

                    241,

                    0.18

                );

            color: #c4b5fd;

            font-size: 14px;

            font-weight: 700;

        }


        .question-text {

            font-size: 21px;

            line-height: 1.6;

            margin-bottom: 28px;

        }


        .options {

            display: grid;

            gap: 14px;

        }


        .option-label {

            display: flex;

            align-items: flex-start;

            gap: 14px;

            padding: 17px;

            cursor: pointer;

            border-radius: 12px;

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

                    0.09

                );

        }


        .option-label:hover {

            border-color:

                var(--accent);

        }


        .option-label input {

            margin-top: 4px;

            accent-color:

                var(--accent);

        }


        .option-letter {

            min-width: 28px;

            height: 28px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background:

                rgba(

                    99,

                    102,

                    241,

                    0.2

                );

            font-weight: 800;

        }


        .option-text {

            line-height: 1.5;

        }


        .navigation-buttons {

            display: flex;

            justify-content: space-between;

            gap: 15px;

            margin-top: 28px;

        }


        .navigation-buttons button {

            min-width: 130px;

        }


        .question-sidebar {

            position: sticky;

            top: 110px;

            padding: 22px;

            border-radius: 16px;

        }


        .question-sidebar h3 {

            margin-bottom: 18px;

        }


        .question-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    5,

                    1fr

                );

            gap: 9px;

        }


        .question-number-button {

            width: 40px;

            height: 40px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

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

            color: white;

            cursor: pointer;

        }


        .question-number-button.active {

            background:

                var(--accent);

            border-color:

                var(--accent);

        }


        .question-number-button.answered {

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

                    0.6

                );

        }


        .exam-info {

            margin-top: 22px;

            padding-top: 18px;

            border-top:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.10

                );

        }


        .exam-info-row {

            display: flex;

            justify-content: space-between;

            gap: 10px;

            padding: 8px 0;

            color: var(--text-muted);

            font-size: 13px;

        }


        .exam-info-row strong {

            color: white;

        }


        .submit-button {

            width: 100%;

            margin-top: 22px;

            padding: 14px;

            font-size: 15px;

        }


        .question-page {

            display: none;

        }


        .question-page.active {

            display: block;

        }


        @media (max-width: 850px) {


            .exam-layout {

                grid-template-columns: 1fr;

            }


            .question-sidebar {

                position: static;

                order: -1;

            }


        }


        @media (max-width: 600px) {


            .exam-header-top {

                flex-direction: column;

                align-items: stretch;

            }


            .timer-box {

                width: 100%;

            }


            .question-card {

                padding: 20px;

            }


            .question-text {

                font-size: 18px;

            }


            .navigation-buttons {

                flex-direction: column;

            }


            .navigation-buttons button {

                width: 100%;

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

                href="join_room.php"

                class="sidebar-link active"

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


        <div class="exam-container">


            <div class="exam-header">


                <div class="exam-header-top">


                    <div class="exam-title">


                        <h1>


                            📝

                            <?php

                            echo htmlspecialchars(

                                $exam["room_name"]

                            );

                            ?>


                        </h1>


                        <p>


                            <?php

                            echo htmlspecialchars(

                                $exam["title"]

                            );

                            ?>

                            |

                            <?php

                            echo count(

                                $questions

                            );

                            ?>

                            Questions


                        </p>


                    </div>


                    <div class="timer-box">


                        <span class="timer-label">

                            ⏱️ Time Remaining

                        </span>


                        <span id="timer">

                            00:00

                        </span>


                    </div>


                </div>


            </div>


            <!-- EXAM FORM -->

            <form

                method="POST"

                action="room_submit_exam.php"

                id="examForm"

            >


                <input

                    type="hidden"

                    name="exam_id"

                    value="<?php echo $exam_id; ?>"

                >


                <input

                    type="hidden"

                    name="room_id"

                    value="<?php echo $room_id; ?>"

                >


                <input

                    type="hidden"

                    name="attempt_id"

                    value="<?php echo $attempt_id; ?>"

                >


                <input

                    type="hidden"

                    name="auto_submit"

                    id="auto_submit"

                    value="0"

                >


                <div class="exam-layout">


                    <!-- QUESTIONS -->

                    <div>


                        <?php foreach (

                            $questions

                            as

                            $index =>

                            $question

                        ): ?>


                            <div

                                class="card question-card question-page <?php echo $index === 0 ? 'active' : ''; ?>"

                                id="question-<?php echo $index; ?>"

                            >


                                <div class="question-number">


                                    Question

                                    <?php echo $index + 1; ?>

                                    of

                                    <?php echo count($questions); ?>


                                </div>


                                <div class="question-text">


                                    <?php

                                    echo nl2br(

                                        htmlspecialchars(

                                            $question["question_text"]

                                        )

                                    );

                                    ?>


                                </div>


                                <div class="options">


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

                                        as $letter =>

                                        $option

                                    ): ?>


                                        <?php

                                        if (

                                            trim(

                                                $option

                                            ) === ""

                                        ) {

                                            continue;

                                        }

                                        ?>


                                        <label

                                            class="option-label"

                                        >


                                            <input

                                                type="radio"

                                                name="answers[<?php echo $question["id"]; ?>]"

                                                value="<?php echo $letter; ?>"

                                                data-question="<?php echo $index; ?>"

                                            >


                                            <span

                                                class="option-letter"

                                            >

                                                <?php echo $letter; ?>

                                            </span>


                                            <span

                                                class="option-text"

                                            >

                                                <?php

                                                echo htmlspecialchars(

                                                    $option

                                                );

                                                ?>


                                            </span>


                                        </label>


                                    <?php endforeach; ?>


                                </div>


                                <div

                                    class="navigation-buttons"

                                >


                                    <?php if (

                                        $index > 0

                                    ): ?>


                                        <button

                                            type="button"

                                            class="btn btn-secondary previous-button"

                                            data-target="<?php echo $index - 1; ?>"

                                        >

                                            ← Previous

                                        </button>


                                    <?php else: ?>


                                        <span></span>


                                    <?php endif; ?>


                                    <?php if (

                                        $index < count($questions) - 1

                                    ): ?>


                                        <button

                                            type="button"

                                            class="btn btn-primary next-button"

                                            data-target="<?php echo $index + 1; ?>"

                                        >

                                            Next →

                                        </button>


                                    <?php else: ?>


                                        <button

                                            type="submit"

                                            class="btn btn-primary"

                                        >

                                            ✅ Submit Exam

                                        </button>


                                    <?php endif; ?>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                    <!-- QUESTION SIDEBAR -->

                    <aside

                        class="card question-sidebar"

                    >


                        <h3>

                            📌 Questions

                        </h3>


                        <div

                            class="question-grid"

                        >


                            <?php foreach (

                                $questions

                                as

                                $index =>

                                $question

                            ): ?>


                                <button

                                    type="button"

                                    class="question-number-button <?php echo $index === 0 ? 'active' : ''; ?>"

                                    data-target="<?php echo $index; ?>"

                                >

                                    <?php echo $index + 1; ?>


                                </button>


                            <?php endforeach; ?>


                        </div>


                        <div class="exam-info">


                            <div class="exam-info-row">


                                <span>

                                    Room Code

                                </span>


                                <strong>


                                    <?php

                                    echo htmlspecialchars(

                                        $exam["room_code"]

                                    );

                                    ?>


                                </strong>


                            </div>


                            <div class="exam-info-row">


                                <span>

                                    Duration

                                </span>


                                <strong>


                                    <?php

                                    echo htmlspecialchars(

                                        $exam["duration"]

                                    );

                                    ?>

                                    min


                                </strong>


                            </div>


                            <div class="exam-info-row">


                                <span>

                                    End Time

                                </span>


                                <strong>


                                    <?php

                                    echo htmlspecialchars(

                                        $formatted_end

                                    );

                                    ?>


                                </strong>


                            </div>


                        </div>


                        <button

                            type="submit"

                            class="btn btn-primary submit-button"

                        >

                            🚀 Submit Exam

                        </button>


                    </aside>


                </div>


            </form>


        </div>


    </main>


</div>


<script>


// ============================================================
// REFRESH-SAFE TIMER
// ============================================================

let remainingSeconds =

    <?php echo intval($remaining_seconds); ?>;


const timerElement =

    document.getElementById(

        "timer"

    );


const examForm =

    document.getElementById(

        "examForm"

    );


const autoSubmitInput =

    document.getElementById(

        "auto_submit"

    );


function updateTimer() {


    const minutes = Math.floor(

        remainingSeconds / 60

    );


    const seconds =

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


        autoSubmitInput.value =

            "1";


        alert(

            "Time is over. Your examination will be submitted automatically."

        );


        examForm.submit();


        return;

    }


    remainingSeconds--;

}


updateTimer();


setInterval(

    updateTimer,

    1000

);


// ============================================================
// QUESTION NAVIGATION
// ============================================================

const questionPages =

    document.querySelectorAll(

        ".question-page"

    );


const questionButtons =

    document.querySelectorAll(

        ".question-number-button"

    );


function showQuestion(index) {


    questionPages.forEach(

        function(page) {

            page.classList.remove(

                "active"

            );

        }

    );


    questionButtons.forEach(

        function(button) {

            button.classList.remove(

                "active"

            );

        }

    );


    if (

        questionPages[index]

    ) {

        questionPages[index].classList.add(

            "active"

        );

    }


    if (

        questionButtons[index]

    ) {

        questionButtons[index].classList.add(

            "active"

        );

    }


    window.scrollTo({

        top: 0,

        behavior: "smooth"

    });

}


document.querySelectorAll(

    ".next-button"

).forEach(

    function(button) {


        button.addEventListener(

            "click",

            function() {


                showQuestion(

                    parseInt(

                        this.dataset.target

                    )

                );


            }

        );


    }

);


document.querySelectorAll(

    ".previous-button"

).forEach(

    function(button) {


        button.addEventListener(

            "click",

            function() {


                showQuestion(

                    parseInt(

                        this.dataset.target

                    )

                );


            }

        );


    }

);


questionButtons.forEach(

    function(button) {


        button.addEventListener(

            "click",

            function() {


                showQuestion(

                    parseInt(

                        this.dataset.target

                    )

                );


            }

        );


    }

);


// ============================================================
// MARK ANSWERED QUESTIONS
// ============================================================

document.querySelectorAll(

    'input[type="radio"]'

).forEach(

    function(radio) {


        radio.addEventListener(

            "change",

            function() {


                const questionIndex =

                    parseInt(

                        this.dataset.question

                    );


                if (

                    questionButtons[

                        questionIndex

                    ]

                ) {


                    questionButtons[

                        questionIndex

                    ]

                    .classList.add(

                        "answered"

                    );

                }


            }

        );


    }

);


// ============================================================
// CONFIRM SUBMISSION
// ============================================================

examForm.addEventListener(

    "submit",

    function(event) {


        if (

            autoSubmitInput.value ===

            "1"

        ) {

            return;

        }


        const confirmed = confirm(

            "Are you sure you want to submit your examination?"

        );


        if (

            !confirmed

        ) {

            event.preventDefault();

        }


    }

);

</script>


</body>

</html>