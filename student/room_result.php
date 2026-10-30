<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();


// ============================================================
// STUDENT ID
// ============================================================

$student_id = $_SESSION["user_id"];


// ============================================================
// VALIDATE ATTEMPT ID
// ============================================================

if (

    !isset($_GET["id"]) ||

    !is_numeric($_GET["id"])

) {

    header("Location: dashboard.php");

    exit();

}


$attempt_id = intval($_GET["id"]);


// ============================================================
// FETCH ROOM EXAM RESULT
// ============================================================

$sql = "

    SELECT

        ra.id,

        ra.user_id,

        ra.room_exam_id,

        ra.score,

        ra.total_questions,

        ra.correct_answers,

        ra.started_at,

        ra.submitted_at,

        re.title,

        re.description,

        re.duration,

        re.room_id,

        r.room_name,

        r.room_code

    FROM room_attempts ra

    INNER JOIN room_exams re

        ON ra.room_exam_id = re.id

    INNER JOIN rooms r

        ON re.room_id = r.id

    WHERE ra.id = ?

    AND ra.user_id = ?

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

    "ii",

    $attempt_id,

    $student_id

);


$stmt->execute();


$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    die(

        "Result not found for this student."

    );

}


$attempt = $result->fetch_assoc();


$stmt->close();


// ============================================================
// CALCULATE PERCENTAGE
// ============================================================

$total_questions = intval(

    $attempt["total_questions"]

);


$correct_answers = intval(

    $attempt["correct_answers"]

);


$score = intval(

    $attempt["score"]

);


$percentage = 0;


if (

    $total_questions > 0

) {

    $percentage = round(

        (

            $correct_answers

            /

            $total_questions

        )

        *

        100,

        2

    );

}


// ============================================================
// FORMAT SUBMITTED DATE
// ============================================================

$submitted_date = "Not Available";


if (

    !empty(

        $attempt["submitted_at"]

    )

) {

    $submitted_date = date(

        "d M Y, h:i A",

        strtotime(

            $attempt["submitted_at"]

        )

    );

}


// ============================================================
// PERFORMANCE MESSAGE
// ============================================================

if (

    $percentage >= 80

) {

    $status = "Excellent Performance!";

    $status_class = "excellent";

    $status_icon = "🏆";

}

elseif (

    $percentage >= 60

) {

    $status = "Good Performance!";

    $status_class = "good";

    $status_icon = "🎉";

}

elseif (

    $percentage >= 40

) {

    $status = "Keep Practicing!";

    $status_class = "average";

    $status_icon = "📚";

}

else {

    $status = "Needs Improvement";

    $status_class = "poor";

    $status_icon = "💪";

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

        Room Exam Result - SmartExam

    </title>


    <link

        rel="stylesheet"

        href="../assets/css/style.css"

    >


    <style>

        .result-container {

            max-width: 950px;

            margin: 0 auto;

        }


        .page-header {

            margin-bottom: 30px;

        }


        .page-header h1 {

            font-size: 34px;

            margin-bottom: 10px;

        }


        .page-header p {

            color: var(--text-muted);

        }


        .result-card {

            padding: 40px;

            text-align: center;

            border-radius: 20px;

        }


        .result-icon {

            font-size: 70px;

            margin-bottom: 15px;

        }


        .exam-title {

            font-size: 30px;

            margin-bottom: 12px;

        }


        .room-info {

            color: var(--text-muted);

            margin-bottom: 10px;

        }


        .submitted-date {

            color: var(--text-muted);

            margin-bottom: 35px;

        }


        .score-circle {

            width: 200px;

            height: 200px;

            margin: 0 auto 25px;

            border-radius: 50%;

            display: flex;

            flex-direction: column;

            justify-content: center;

            align-items: center;

            background:

                rgba(

                    99,

                    102,

                    241,

                    0.15

                );

            border:

                8px solid

                var(--accent);

            box-shadow:

                0 0 35px

                rgba(

                    99,

                    102,

                    241,

                    0.25

                );

        }


        .score-number {

            font-size: 42px;

            font-weight: 900;

        }


        .score-label {

            color: var(--text-muted);

            font-size: 14px;

        }


        .status {

            font-size: 21px;

            font-weight: 800;

            margin-bottom: 35px;

        }


        .status.excellent {

            color: #22c55e;

        }


        .status.good {

            color: #3b82f6;

        }


        .status.average {

            color: #f59e0b;

        }


        .status.poor {

            color: #ef4444;

        }


        .stats-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(

                        180px,

                        1fr

                    )

                );

            gap: 18px;

            margin: 30px 0;

        }


        .stat-box {

            padding: 24px;

            border-radius: 15px;

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


        .stat-value {

            display: block;

            font-size: 30px;

            font-weight: 900;

            margin-bottom: 8px;

        }


        .stat-label {

            color: var(--text-muted);

            font-size: 14px;

        }


        .result-details {

            margin-top: 30px;

            padding: 22px;

            text-align: left;

            border-radius: 15px;

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


        .detail-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 12px 0;

            border-bottom:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.08

                );

        }


        .detail-row:last-child {

            border-bottom: none;

        }


        .detail-label {

            color: var(--text-muted);

        }


        .detail-value {

            font-weight: 700;

            text-align: right;

        }


        .actions {

            display: flex;

            justify-content: center;

            gap: 15px;

            margin-top: 35px;

        }


        .actions a {

            min-width: 190px;

        }


        @media (max-width: 600px) {

            .result-card {

                padding: 25px 18px;

            }


            .actions {

                flex-direction: column;

            }


            .actions a {

                width: 100%;

            }


            .detail-row {

                flex-direction: column;

                gap: 5px;

            }


            .detail-value {

                text-align: left;

            }

        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

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

                class="sidebar-link"

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

                class="sidebar-link active"

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


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <div class="result-container">


            <div class="page-header">


                <h1>

                    🎉 Examination Completed

                </h1>


                <p>

                    Your room examination result is ready.

                </p>


            </div>


            <div class="card result-card">


                <div class="result-icon">

                    <?php

                    echo $status_icon;

                    ?>

                </div>


                <h2 class="exam-title">


                    <?php

                    echo htmlspecialchars(

                        $attempt["title"]

                    );

                    ?>


                </h2>


                <p class="room-info">


                    🏠

                    <?php

                    echo htmlspecialchars(

                        $attempt["room_name"]

                    );

                    ?>


                    &nbsp; | &nbsp;


                    🔑

                    <?php

                    echo htmlspecialchars(

                        $attempt["room_code"]

                    );

                    ?>


                </p>


                <p class="submitted-date">


                    Submitted on:


                    <?php

                    echo htmlspecialchars(

                        $submitted_date

                    );

                    ?>


                </p>


                <div class="score-circle">


                    <span class="score-number">


                        <?php

                        echo htmlspecialchars(

                            $percentage

                        );

                        ?>%


                    </span>


                    <span class="score-label">

                        Percentage

                    </span>


                </div>


                <div

                    class="status <?php echo $status_class; ?>"

                >

                    <?php

                    echo $status;

                    ?>

                </div>


                <div class="stats-grid">


                    <div class="stat-box">


                        <span class="stat-value">


                            <?php

                            echo htmlspecialchars(

                                $score

                            );

                            ?>


                        </span>


                        <span class="stat-label">

                            Score

                        </span>


                    </div>


                    <div class="stat-box">


                        <span class="stat-value">


                            <?php

                            echo htmlspecialchars(

                                $correct_answers

                            );

                            ?>


                        </span>


                        <span class="stat-label">

                            Correct Answers

                        </span>


                    </div>


                    <div class="stat-box">


                        <span class="stat-value">


                            <?php

                            echo htmlspecialchars(

                                $total_questions

                            );

                            ?>


                        </span>


                        <span class="stat-label">

                            Total Questions

                        </span>


                    </div>


                    <div class="stat-box">


                        <span class="stat-value">


                            <?php

                            echo htmlspecialchars(

                                $attempt["duration"]

                            );

                            ?>


                            min


                        </span>


                        <span class="stat-label">

                            Exam Duration

                        </span>


                    </div>


                </div>


                <div class="result-details">


                    <div class="detail-row">


                        <span class="detail-label">

                            📝 Examination

                        </span>


                        <span class="detail-value">


                            <?php

                            echo htmlspecialchars(

                                $attempt["title"]

                            );

                            ?>


                        </span>


                    </div>


                    <div class="detail-row">


                        <span class="detail-label">

                            🏠 Room Name

                        </span>


                        <span class="detail-value">


                            <?php

                            echo htmlspecialchars(

                                $attempt["room_name"]

                            );

                            ?>


                        </span>


                    </div>


                    <div class="detail-row">


                        <span class="detail-label">

                            🔑 Room Code

                        </span>


                        <span class="detail-value">


                            <?php

                            echo htmlspecialchars(

                                $attempt["room_code"]

                            );

                            ?>


                        </span>


                    </div>


                    <div class="detail-row">


                        <span class="detail-label">

                            📊 Performance

                        </span>


                        <span class="detail-value">


                            <?php

                            echo $correct_answers;

                            ?>

                            /

                            <?php

                            echo $total_questions;

                            ?>

                            Correct


                        </span>


                    </div>


                </div>


                <div class="actions">


                    <a

                        href="dashboard.php"

                        class="btn btn-primary"

                    >

                        📊 Go to Dashboard

                    </a>


                    <a

                        href="attempt_history.php"

                        class="btn btn-secondary"

                    >

                        📚 View My Attempts

                    </a>


                </div>


            </div>


        </div>


    </main>


</div>


</body>

</html>