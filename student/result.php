<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$attempt_id = isset($_GET["id"])
    ? intval($_GET["id"])
    : 0;

$student_id = intval($_SESSION["user_id"]);

if ($attempt_id <= 0) {
    die("Invalid result ID.");
}


// ============================================================
// FETCH ATTEMPT RESULT
// ============================================================

$sql = "

    SELECT

        a.id,
        a.score,
        a.total_questions,
        a.submitted_at,

        e.title,
        e.total_marks

    FROM attempts a

    INNER JOIN exams e

        ON a.exam_id = e.id

    WHERE a.id = ?

    AND a.user_id = ?

    LIMIT 1

";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(

        "Database error: "

        . htmlspecialchars($conn->error)

    );

}


$stmt->bind_param(

    "ii",

    $attempt_id,

    $student_id

);


if (!$stmt->execute()) {

    die(

        "Database execution error: "

        . htmlspecialchars($stmt->error)

    );

}


$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    $stmt->close();

    die("Result not found.");

}


$data = $result->fetch_assoc();


$stmt->close();


// ============================================================
// CALCULATE SCORE
// ============================================================

$score = intval($data["score"]);

$total_questions = intval(

    $data["total_questions"]

);

$total_marks = intval(

    $data["total_marks"]

);


$percentage = 0;


if ($total_questions > 0) {

    $percentage = round(

        ($score / $total_questions) * 100,

        2

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

    Exam Result - SmartExam

</title>


<link

    rel="stylesheet"

    href="../assets/css/style.css"

>


<style>

body {

    min-height: 100vh;

}


.result-wrapper {

    max-width: 850px;

    margin: 60px auto;

    padding: 20px;

}


.result-card {

    text-align: center;

    padding: 45px 30px;

}


.result-icon {

    font-size: 70px;

    margin-bottom: 20px;

}


.result-title {

    font-size: 32px;

    margin-bottom: 10px;

}


.result-subtitle {

    color: var(--text-muted);

    margin-bottom: 35px;

}


.score-box {

    display: inline-block;

    min-width: 220px;

    padding: 25px;

    margin-bottom: 30px;

    border-radius: 18px;

    background: rgba(

        124,

        58,

        237,

        0.15

    );

    border: 1px solid rgba(

        124,

        58,

        237,

        0.4

    );

}


.score-value {

    font-size: 48px;

    font-weight: bold;

    color: #a78bfa;

}


.score-label {

    color: var(--text-muted);

    margin-top: 5px;

}


.result-stats {

    display: grid;

    grid-template-columns:

        repeat(

            3,

            1fr

        );

    gap: 20px;

    margin: 25px 0 35px;

}


.result-stat {

    padding: 20px;

    border-radius: 12px;

    background: rgba(

        255,

        255,

        255,

        0.04

    );

    border: 1px solid rgba(

        255,

        255,

        255,

        0.08

    );

}


.result-stat strong {

    display: block;

    font-size: 24px;

    margin-bottom: 5px;

}


.result-stat span {

    color: var(--text-muted);

    font-size: 13px;

}


.result-actions {

    display: flex;

    justify-content: center;

    gap: 15px;

}


@media (max-width: 600px) {

    .result-stats {

        grid-template-columns: 1fr;

    }


    .result-actions {

        flex-direction: column;

    }

}

</style>

</head>


<body>


<div class="result-wrapper">


    <div class="card result-card">


        <div class="result-icon">

            🎉

        </div>


        <h1 class="result-title">

            Exam Completed!

        </h1>


        <p class="result-subtitle">

            <?php

            echo htmlspecialchars(

                $data["title"]

            );

            ?>

        </p>


        <div class="score-box">


            <div class="score-value">

                <?php

                echo $percentage;

                ?>%

            </div>


            <div class="score-label">

                Your Score

            </div>


        </div>


        <div class="result-stats">


            <div class="result-stat">


                <strong>

                    <?php

                    echo $score;

                    ?>

                </strong>


                <span>

                    Score

                </span>


            </div>


            <div class="result-stat">


                <strong>

                    <?php

                    echo $total_questions;

                    ?>

                </strong>


                <span>

                    Total Questions

                </span>


            </div>


            <div class="result-stat">


                <strong>

                    <?php

                    echo $total_marks;

                    ?>

                </strong>


                <span>

                    Total Marks

                </span>


            </div>


        </div>


        <div class="result-actions">


            <a

                href="dashboard.php"

                class="btn btn-primary"

            >

                📊 Back to Dashboard

            </a>


            <a

                href="attempt_history.php"

                class="btn btn-secondary"

            >

                ⏱️ View History

            </a>


        </div>


    </div>


</div>


</body>

</html>