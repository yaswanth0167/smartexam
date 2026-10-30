<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = $_SESSION["user_id"];

$message = "";
$message_type = "";


// ==========================================
// VALIDATE ROOM ID
// ==========================================

if (
    !isset($_GET["room_id"]) ||
    !is_numeric($_GET["room_id"])
) {
    header("Location: dashboard.php");
    exit();
}

$room_id = intval($_GET["room_id"]);


// ==========================================
// FETCH ROOM
// ==========================================

$room_sql = "
    SELECT
        id,
        creator_id,
        room_code,
        room_name
    FROM rooms
    WHERE id = ?
    AND creator_id = ?
";

$stmt = $conn->prepare($room_sql);

$stmt->bind_param(
    "ii",
    $room_id,
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    die("Room not found or access denied.");

}

$room = $result->fetch_assoc();

$stmt->close();


// ==========================================
// CREATE EXAM
// ==========================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $title = trim(
        $_POST["title"] ?? ""
    );

    $description = trim(
        $_POST["description"] ?? ""
    );

    $duration = intval(
        $_POST["duration"] ?? 0
    );

    $questions = $_POST["questions"] ?? [];


    // ======================================
    // VALIDATION
    // ======================================

    if (
        empty($title) ||
        $duration <= 0 ||
        empty($questions)
    ) {

        $message =
            "Please fill all required fields.";

        $message_type =
            "error";

    } else {


        $valid_questions = [];


        foreach (
            $questions
            as
            $question
        ) {


            $question_text = trim(
                $question["question_text"] ?? ""
            );


            $option_a = trim(
                $question["option_a"] ?? ""
            );


            $option_b = trim(
                $question["option_b"] ?? ""
            );


            $option_c = trim(
                $question["option_c"] ?? ""
            );


            $option_d = trim(
                $question["option_d"] ?? ""
            );


            $correct_answer = strtoupper(
                trim(
                    $question["correct_answer"] ?? ""
                )
            );


            $explanation = trim(
                $question["explanation"] ?? ""
            );


            $marks = intval(
                $question["marks"] ?? 1
            );


            if (
                empty($question_text) ||
                empty($option_a) ||
                empty($option_b) ||
                empty($option_c) ||
                empty($option_d) ||
                !in_array(
                    $correct_answer,
                    [
                        "A",
                        "B",
                        "C",
                        "D"
                    ],
                    true
                )
            ) {

                continue;

            }


            $valid_questions[] = [

                "question_text"
                    => $question_text,

                "option_a"
                    => $option_a,

                "option_b"
                    => $option_b,

                "option_c"
                    => $option_c,

                "option_d"
                    => $option_d,

                "correct_answer"
                    => $correct_answer,

                "explanation"
                    => $explanation,

                "marks"
                    => $marks

            ];

        }


        if (
            count($valid_questions) === 0
        ) {

            $message =
                "Please add at least one valid question.";

            $message_type =
                "error";

        } else {


            $conn->begin_transaction();


            try {


                // ==================================
                // INSERT EXAM
                // ==================================

                $exam_sql = "

                    INSERT INTO room_exams

                    (

                        room_id,
                        title,
                        description,
                        duration,
                        total_questions

                    )

                    VALUES (?, ?, ?, ?, ?)

                ";


                $stmt =
                    $conn->prepare(
                        $exam_sql
                    );


                $total_questions =
                    count(
                        $valid_questions
                    );


                $stmt->bind_param(

                    "issii",

                    $room_id,
                    $title,
                    $description,
                    $duration,
                    $total_questions

                );


                if (
                    !$stmt->execute()
                ) {

                    throw new Exception(
                        "Failed to create exam."
                    );

                }


                $room_exam_id =
                    $stmt->insert_id;


                $stmt->close();


                // ==================================
                // INSERT QUESTIONS
                // ==================================

                $question_sql = "

                    INSERT INTO room_questions

                    (

                        room_exam_id,
                        question_text,
                        option_a,
                        option_b,
                        option_c,
                        option_d,
                        correct_answer,
                        explanation,
                        marks

                    )

                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)

                ";


                $question_stmt =
                    $conn->prepare(
                        $question_sql
                    );


                foreach (
                    $valid_questions
                    as
                    $question
                ) {


                    $question_stmt->bind_param(

                        "isssssssi",

                        $room_exam_id,

                        $question[
                            "question_text"
                        ],

                        $question[
                            "option_a"
                        ],

                        $question[
                            "option_b"
                        ],

                        $question[
                            "option_c"
                        ],

                        $question[
                            "option_d"
                        ],

                        $question[
                            "correct_answer"
                        ],

                        $question[
                            "explanation"
                        ],

                        $question[
                            "marks"
                        ]

                    );


                    if (
                        !$question_stmt->execute()
                    ) {

                        throw new Exception(
                            "Failed to save question."
                        );

                    }

                }


                $question_stmt->close();


                $conn->commit();


                header(

                    "Location: room_exam_created.php?id="
                    . $room_id

                );

                exit();


            }

            catch (
                Exception $e
            ) {


                $conn->rollback();


                $message =
                    $e->getMessage();

                $message_type =
                    "error";

            }

        }

    }

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
    Create Room Exam - SmartExam
</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css"
>

<style>

.room-container {

    max-width: 950px;

    margin: 0 auto;

}

.question-box {

    margin-top: 25px;

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

.question-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;

}

.question-header h3 {

    margin: 0;

}

.form-grid {

    display: grid;

    grid-template-columns:

        repeat(
            auto-fit,
            minmax(
                280px,
                1fr
            )
        );

    gap: 18px;

}

.full-width {

    grid-column: 1 / -1;

}

.remove-question {

    background:

        rgba(
            239,
            68,
            68,
            0.15
        );

    color: #fca5a5;

    border: 1px solid #ef4444;

    padding: 8px 12px;

    border-radius: 8px;

    cursor: pointer;

}

.add-question {

    width: 100%;

    margin-top: 25px;

}

.submit-button {

    width: 100%;

    margin-top: 30px;

    padding: 15px;

    font-size: 16px;

}

.error-message {

    padding: 14px;

    margin-bottom: 20px;

    border-radius: 10px;

    background:

        rgba(
            239,
            68,
            68,
            0.15
        );

    color: #fca5a5;

}

</style>

</head>

<body>

<div class="dashboard-layout">

<aside class="sidebar">

<div class="sidebar-logo">

Smart<span>Exam</span>

</div>

<nav class="sidebar-nav">

<a
    href="dashboard.php"
    class="sidebar-link"
>
📊 Dashboard
</a>

<a
    href="create_room.php"
    class="sidebar-link active"
>
🏠 Create Room
</a>

<a
    href="join_room.php"
    class="sidebar-link"
>
🔑 Join Room
</a>

<a
    href="attempt_history.php"
    class="sidebar-link"
>
⏱️ My Attempts
</a>

<a
    href="leaderboard.php"
    class="sidebar-link"
>
🏆 Leaderboard
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


<main class="main-content">

<div class="room-container">


<h1>

📝 Create Your Own Room Exam

</h1>

<p style="color:var(--text-muted);">

Room:

<strong>

<?php

echo htmlspecialchars(
    $room["room_name"]
);

?>

</strong>

</p>


<?php if (!empty($message)): ?>

<div class="error-message">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>


<div class="card">

<form
    method="POST"
>


<div class="form-grid">


<div
    class="form-group full-width"
>

<label
    class="form-label"
>

Exam Title

</label>

<input
    type="text"
    name="title"
    class="form-control"
    placeholder="Example: Python Assessment"
    required
>

</div>


<div
    class="form-group full-width"
>

<label
    class="form-label"
>

Description

</label>

<textarea
    name="description"
    class="form-control"
    rows="3"
    placeholder="Enter exam description"
></textarea>

</div>


<div
    class="form-group"
>

<label
    class="form-label"
>

Duration (Minutes)

</label>

<input
    type="number"
    name="duration"
    class="form-control"
    min="1"
    required
>

</div>

</div>


<div id="questionsContainer">


<div
    class="question-box"
>


<div
    class="question-header"
>

<h3>

Question 1

</h3>

</div>


<div class="form-grid">


<div
    class="form-group full-width"
>

<label
    class="form-label"
>

Question

</label>

<textarea
    name="questions[0][question_text]"
    class="form-control"
    rows="3"
    required
></textarea>

</div>


<div
    class="form-group"
>

<label
    class="form-label"
>

Option A

</label>

<input
    type="text"
    name="questions[0][option_a]"
    class="form-control"
    required
>

</div>


<div
    class="form-group"
>

<label
    class="form-label"
>

Option B

</label>

<input
    type="text"
    name="questions[0][option_b]"
    class="form-control"
    required
>

</div>


<div
    class="form-group"
>

<label
    class="form-label"
>

Option C

</label>

<input
    type="text"
    name="questions[0][option_c]"
    class="form-control"
    required
>

</div>


<div
    class="form-group"
>

<label
    class="form-label"
>

Option D

</label>

<input
    type="text"
    name="questions[0][option_d]"
    class="form-control"
    required
>

</div>


<div
    class="form-group"
>

<label
    class="form-label"
>

Correct Answer

</label>

<select
    name="questions[0][correct_answer]"
    class="form-control"
    required
>

<option value="">

Select Correct Answer

</option>

<option value="A">

A

</option>

<option value="B">

B

</option>

<option value="C">

C

</option>

<option value="D">

D

</option>

</select>

</div>


<div
    class="form-group"
>

<label
    class="form-label"
>

Marks

</label>

<input
    type="number"
    name="questions[0][marks]"
    class="form-control"
    value="1"
    min="1"
    required
>

</div>


<div
    class="form-group full-width"
>

<label
    class="form-label"
>

Explanation

</label>

<textarea
    name="questions[0][explanation]"
    class="form-control"
    rows="2"
    placeholder="Optional explanation"
></textarea>

</div>


</div>

</div>

</div>


<button
    type="button"
    class="btn btn-secondary add-question"
    onclick="addQuestion()"
>

➕ Add Another Question

</button>


<button
    type="submit"
    class="btn btn-primary submit-button"
>

🚀 Save Room Exam

</button>


</form>

</div>

</div>

</main>

</div>


<script>

let questionIndex = 1;


function addQuestion() {


    const container =
        document.getElementById(
            "questionsContainer"
        );


    const questionBox =
        document.createElement(
            "div"
        );


    questionBox.className =
        "question-box";


    questionBox.innerHTML = `

        <div class="question-header">

            <h3>

                Question ${questionIndex + 1}

            </h3>

            <button

                type="button"

                class="remove-question"

                onclick="this.closest('.question-box').remove()"

            >

                ✖ Remove

            </button>

        </div>


        <div class="form-grid">


            <div class="form-group full-width">

                <label class="form-label">

                    Question

                </label>

                <textarea

                    name="questions[${questionIndex}][question_text]"

                    class="form-control"

                    rows="3"

                    required

                ></textarea>

            </div>


            <div class="form-group">

                <label class="form-label">

                    Option A

                </label>

                <input

                    type="text"

                    name="questions[${questionIndex}][option_a]"

                    class="form-control"

                    required

                >

            </div>


            <div class="form-group">

                <label class="form-label">

                    Option B

                </label>

                <input

                    type="text"

                    name="questions[${questionIndex}][option_b]"

                    class="form-control"

                    required

                >

            </div>


            <div class="form-group">

                <label class="form-label">

                    Option C

                </label>

                <input

                    type="text"

                    name="questions[${questionIndex}][option_c]"

                    class="form-control"

                    required

                >

            </div>


            <div class="form-group">

                <label class="form-label">

                    Option D

                </label>

                <input

                    type="text"

                    name="questions[${questionIndex}][option_d]"

                    class="form-control"

                    required

                >

            </div>


            <div class="form-group">

                <label class="form-label">

                    Correct Answer

                </label>

                <select

                    name="questions[${questionIndex}][correct_answer]"

                    class="form-control"

                    required

                >

                    <option value="">

                        Select Correct Answer

                    </option>

                    <option value="A">A</option>

                    <option value="B">B</option>

                    <option value="C">C</option>

                    <option value="D">D</option>

                </select>

            </div>


            <div class="form-group">

                <label class="form-label">

                    Marks

                </label>

                <input

                    type="number"

                    name="questions[${questionIndex}][marks]"

                    class="form-control"

                    value="1"

                    min="1"

                    required

                >

            </div>


            <div class="form-group full-width">

                <label class="form-label">

                    Explanation

                </label>

                <textarea

                    name="questions[${questionIndex}][explanation]"

                    class="form-control"

                    rows="2"

                ></textarea>

            </div>


        </div>

    `;


    container.appendChild(
        questionBox
    );


    questionIndex++;

}

</script>

</body>

</html>