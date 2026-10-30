<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = intval($_SESSION["user_id"]);


// ==================================================
// ONLY POST REQUEST ALLOWED
// ==================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ai_exam_generator.php");

    exit();

}


// ==================================================
// RECEIVE FORM DATA
// ==================================================

$ai_exam_id = intval(
    $_POST["ai_exam_id"] ?? 0
);

$answers = $_POST["answers"] ?? [];


// ==================================================
// VALIDATE EXAM ID
// ==================================================

if ($ai_exam_id <= 0) {

    die("Invalid AI Exam ID.");

}


// ==================================================
// VERIFY EXAM OWNERSHIP
// ==================================================

$exam_sql = "

    SELECT
        id,
        student_id,
        subject,
        topic,
        duration

    FROM ai_exams

    WHERE id = ?

    AND student_id = ?

";


$stmt = $conn->prepare($exam_sql);


if (!$stmt) {

    die(
        "Database Error: "
        . htmlspecialchars($conn->error)
    );

}


$stmt->bind_param(

    "ii",

    $ai_exam_id,

    $student_id

);


if (!$stmt->execute()) {

    die(
        "Exam verification failed: "
        . htmlspecialchars($stmt->error)
    );

}


$exam_result = $stmt->get_result();


if ($exam_result->num_rows !== 1) {

    die(
        "Exam not found or access denied."
    );

}


$exam = $exam_result->fetch_assoc();


$stmt->close();


// ==================================================
// FETCH ALL QUESTIONS
// ==================================================

$question_sql = "

    SELECT
        id,
        correct_answer,
        marks

    FROM ai_questions

    WHERE ai_exam_id = ?

    ORDER BY id ASC

";


$stmt = $conn->prepare($question_sql);


if (!$stmt) {

    die(
        "Question Database Error: "
        . htmlspecialchars($conn->error)
    );

}


$stmt->bind_param(

    "i",

    $ai_exam_id

);


if (!$stmt->execute()) {

    die(
        "Question fetch failed: "
        . htmlspecialchars($stmt->error)
    );

}


$question_result = $stmt->get_result();


// ==================================================
// SCORE VARIABLES
// ==================================================

$score = 0;

$total_marks = 0;

$correct_answers = 0;

$wrong_answers = 0;

$unanswered = 0;


// Store results temporarily
$question_results = [];


// ==================================================
// CHECK EVERY QUESTION
// ==================================================

while (

    $question =
    $question_result->fetch_assoc()

) {


    $question_id = intval(

        $question["id"]

    );


    $correct_answer = strtoupper(

        trim(

            $question["correct_answer"]

        )

    );


    $marks = intval(

        $question["marks"]

    );


    $total_marks += $marks;


    // ------------------------------------------
    // GET STUDENT ANSWER
    // ------------------------------------------

    $student_answer = "";


    if (

        isset(

            $answers[$question_id]

        )

    ) {

        $student_answer = strtoupper(

            trim(

                $answers[$question_id]

            )

        );

    }


    // ------------------------------------------
    // VALIDATE ANSWER VALUE
    // ------------------------------------------

    $allowed_answers = [

        "A",
        "B",
        "C",
        "D"

    ];


    if (

        !in_array(

            $student_answer,

            $allowed_answers,

            true

        )

    ) {

        $student_answer = "";

    }


    // ------------------------------------------
    // CHECK ANSWER
    // ------------------------------------------

    if (

        $student_answer === ""

    ) {


        $unanswered++;


        $status = "unanswered";


    }

    elseif (

        $student_answer ===

        $correct_answer

    ) {


        $score += $marks;


        $correct_answers++;


        $status = "correct";


    }

    else {


        $wrong_answers++;


        $status = "wrong";

    }


    // ------------------------------------------
    // STORE QUESTION RESULT
    // ------------------------------------------

    $question_results[] = [

        "question_id" =>

            $question_id,


        "selected_answer" =>

            $student_answer,


        "correct_answer" =>

            $correct_answer,


        "status" =>

            $status

    ];

}


$stmt->close();


// ==================================================
// CALCULATE PERCENTAGE
// ==================================================

$percentage = 0;


if (

    $total_marks > 0

) {


    $percentage = round(

        (

            $score

            /

            $total_marks

        )

        *

        100,

        2

    );

}


// ==================================================
// START DATABASE TRANSACTION
// ==================================================

$conn->begin_transaction();


try {


    // ==================================================
    // INSERT AI ATTEMPT
    // ==================================================

    $insert_attempt_sql = "

        INSERT INTO ai_attempts

        (

            student_id,

            ai_exam_id,

            score,

            total_marks,

            percentage,

            correct_answers,

            wrong_answers,

            unanswered,

            started_at,

            submitted_at

        )

        VALUES

        (

            ?,

            ?,

            ?,

            ?,

            ?,

            ?,

            ?,

            ?,

            NOW(),

            NOW()

        )

    ";


    $attempt_stmt = $conn->prepare(

        $insert_attempt_sql

    );


    if (!$attempt_stmt) {

        throw new Exception(

            "Attempt preparation failed: "

            .

            $conn->error

        );

    }


    $attempt_stmt->bind_param(

        "iiiidiii",

        $student_id,

        $ai_exam_id,

        $score,

        $total_marks,

        $percentage,

        $correct_answers,

        $wrong_answers,

        $unanswered

    );


    if (

        !$attempt_stmt->execute()

    ) {

        throw new Exception(

            "Attempt insertion failed: "

            .

            $attempt_stmt->error

        );

    }


    // Get generated attempt ID
    $attempt_id =

        $attempt_stmt->insert_id;


    $attempt_stmt->close();


    // ==================================================
    // INSERT STUDENT ANSWERS
    // ==================================================

    $answer_sql = "

        INSERT INTO ai_answers

        (

            attempt_id,

            question_id,

            selected_answer

        )

        VALUES

        (

            ?,

            ?,

            ?

        )

    ";


    $answer_stmt = $conn->prepare(

        $answer_sql

    );


    if (!$answer_stmt) {

        throw new Exception(

            "Answer preparation failed: "

            .

            $conn->error

        );

    }


    foreach (

        $question_results

        as

        $result

    ) {


        $question_id = intval(

            $result["question_id"]

        );


        $selected_answer =

            $result["selected_answer"];


        // Empty answer → NULL
        if (

            $selected_answer === ""

        ) {

            $selected_answer = null;

        }


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

                "Answer insertion failed: "

                .

                $answer_stmt->error

            );

        }

    }


    $answer_stmt->close();


    // ==================================================
    // COMMIT TRANSACTION
    // ==================================================

    $conn->commit();


    // ==================================================
    // REDIRECT TO AI RESULT PAGE
    // ==================================================

    header(

        "Location: ai_result.php?id="

        .

        $attempt_id

    );


    exit();


}

catch (

    Exception $e

) {


    // Rollback if any error occurs
    $conn->rollback();


    die(

        "Database Error: "

        .

        htmlspecialchars(

            $e->getMessage()

        )

    );

}

?>