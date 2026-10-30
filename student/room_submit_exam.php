<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

date_default_timezone_set("Asia/Kolkata");


// ============================================================
// USER ID
// ============================================================

$user_id = intval($_SESSION["user_id"]);


// ============================================================
// VALIDATE REQUEST
// ============================================================

if (
    $_SERVER["REQUEST_METHOD"] !== "POST" ||
    !isset($_POST["exam_id"]) ||
    !isset($_POST["room_id"])
) {

    header("Location: dashboard.php");
    exit();

}


$room_exam_id = intval($_POST["exam_id"]);
$room_id      = intval($_POST["room_id"]);

$submitted_answers = $_POST["answers"] ?? [];


// ============================================================
// FETCH ROOM EXAM
// ============================================================

$exam_sql = "

    SELECT
        id,
        room_id,
        title,
        duration

    FROM room_exams

    WHERE id = ?
    AND room_id = ?

    LIMIT 1

";


$exam_stmt = $conn->prepare($exam_sql);


if (!$exam_stmt) {

    die(
        "Database Error: "
        . htmlspecialchars($conn->error)
    );

}


$exam_stmt->bind_param(

    "ii",

    $room_exam_id,
    $room_id

);


$exam_stmt->execute();


$exam_result =
    $exam_stmt->get_result();


if (
    $exam_result->num_rows !== 1
) {

    die("Invalid examination.");

}


$exam =
    $exam_result->fetch_assoc();


$exam_stmt->close();


// ============================================================
// FETCH QUESTIONS
// ============================================================

$question_sql = "

    SELECT

        id,
        question_text,
        correct_answer,
        marks

    FROM room_questions

    WHERE room_exam_id = ?

    ORDER BY id ASC

";


$question_stmt =
    $conn->prepare($question_sql);


if (!$question_stmt) {

    die(
        "Database Error: "
        . htmlspecialchars($conn->error)
    );

}


$question_stmt->bind_param(

    "i",

    $room_exam_id

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

        "No questions found for this examination."

    );

}


// ============================================================
// CALCULATE SCORE
// ============================================================

$score = 0;

$correct_answers = 0;

$total_questions = count($questions);


foreach (

    $questions as $question

) {


    $question_id =

        intval(

            $question["id"]

        );


    $selected_answer =

        $submitted_answers[$question_id]

        ??

        "";


    $selected_answer =

        strtoupper(

            trim(

                $selected_answer

            )

        );


    $correct_answer =

        strtoupper(

            trim(

                $question["correct_answer"]

            )

        );


    if (

        $selected_answer !== ""

        &&

        $selected_answer === $correct_answer

    ) {


        $score +=

            intval(

                $question["marks"]

            );


        $correct_answers++;

    }

}


// ============================================================
// SAVE ATTEMPT
// ============================================================

$conn->begin_transaction();


try {


    // ========================================================
    // INSERT ATTEMPT
    // ========================================================

    $attempt_sql = "

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

        VALUES (?, ?, ?, ?, ?, NOW(), NOW())

    ";


    $attempt_stmt =

        $conn->prepare(

            $attempt_sql

        );


    if (!$attempt_stmt) {

        throw new Exception(

            $conn->error

        );

    }


    $attempt_stmt->bind_param(

        "iiii i",

        $user_id,

        $room_exam_id,

        $score,

        $total_questions,

        $correct_answers

    );


    /*
       IMPORTANT:
       Remove the space in "iiii i".
       Correct type string is "iiiii".
    */


    $attempt_stmt->bind_param(

        "iiiii",

        $user_id,

        $room_exam_id,

        $score,

        $total_questions,

        $correct_answers

    );


    if (

        !$attempt_stmt->execute()

    ) {

        throw new Exception(

            $attempt_stmt->error

        );

    }


    $attempt_id =

        $attempt_stmt->insert_id;


    $attempt_stmt->close();


    // ========================================================
    // SAVE ANSWERS
    // ========================================================

    $answer_sql = "

        INSERT INTO room_answers

        (

            attempt_id,

            question_id,

            answer

        )

        VALUES (?, ?, ?)

    ";


    $answer_stmt =

        $conn->prepare(

            $answer_sql

        );


    if (!$answer_stmt) {

        throw new Exception(

            $conn->error

        );

    }


    foreach (

        $questions as $question

    ) {


        $question_id =

            intval(

                $question["id"]

            );


        $selected_answer =

            $submitted_answers[$question_id]

            ??

            "";


        $selected_answer =

            strtoupper(

                trim(

                    $selected_answer

                )

            );


        $answer_stmt->bind_param(

            "iis",

            $attempt_id,

            $question_id,

            $selected_answer

        );


        if (

            !$answer_stmt->execute()

        ) {

            throw new Exception(

                $answer_stmt->error

            );

        }

    }


    $answer_stmt->close();


    // ========================================================
    // COMMIT
    // ========================================================

    $conn->commit();


    // ========================================================
    // REDIRECT TO RESULT
    // ========================================================

    header(

        "Location: room_result.php?id="

        .

        $attempt_id

    );

    exit();


}


catch (

    Exception $e

) {


    $conn->rollback();


    die(

        "Submission Error: "

        .

        htmlspecialchars(

            $e->getMessage()

        )

    );

}

?>