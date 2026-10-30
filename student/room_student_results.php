<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$creator_id = $_SESSION["user_id"];


// ============================================================
// VALIDATE EXAM ID
// ============================================================

if (
    !isset($_GET["exam_id"]) ||
    !is_numeric($_GET["exam_id"])
) {

    header("Location: my_created_rooms.php");

    exit();

}


$room_exam_id = intval(
    $_GET["exam_id"]
);


// ============================================================
// FETCH EXAM AND VERIFY CREATOR
// ============================================================

$exam_sql = "

    SELECT

        re.id,
        re.title,
        re.description,
        re.duration,
        re.total_questions,

        r.id AS room_id,
        r.room_name,
        r.room_code,
        r.creator_id

    FROM room_exams re

    INNER JOIN rooms r

        ON re.room_id = r.id

    WHERE

        re.id = ?

        AND

        r.creator_id = ?

";


$exam_stmt =
    $conn->prepare(
        $exam_sql
    );


if (!$exam_stmt) {

    die(

        "Database Error: "

        .

        htmlspecialchars(
            $conn->error
        )

    );

}


$exam_stmt->bind_param(

    "ii",

    $room_exam_id,

    $creator_id

);


$exam_stmt->execute();


$exam_result =
    $exam_stmt->get_result();


if (

    $exam_result->num_rows !== 1

) {

    die(

        "You are not authorized to view this exam results."

    );

}


$exam =
    $exam_result->fetch_assoc();


$exam_stmt->close();


// ============================================================
// FETCH STUDENT RESULTS
// ============================================================

$result_sql = "

    SELECT

        ra.id AS attempt_id,

        ra.user_id,

        ra.score,

        ra.total_questions,

        ra.correct_answers,

        ra.started_at,

        ra.submitted_at,

        u.name,

        u.email

    FROM room_attempts ra

    INNER JOIN users u

        ON ra.user_id = u.id

    WHERE

        ra.room_exam_id = ?

    ORDER BY

        ra.score DESC,

        ra.submitted_at ASC

";


$result_stmt =
    $conn->prepare(
        $result_sql
    );


if (!$result_stmt) {

    die(

        "Database Error: "

        .

        htmlspecialchars(
            $conn->error
        )

    );

}


$result_stmt->bind_param(

    "i",

    $room_exam_id

);


$result_stmt->execute();


$result_data =
    $result_stmt->get_result();


$results = [];


while (

    $row =
    $result_data->fetch_assoc()

) {

    $results[] = $row;

}


$result_stmt->close();


// ============================================================
// CALCULATE SUMMARY
// ============================================================

$total_attempts =
    count(
        $results
    );


$total_score = 0;


$highest_score = 0;


foreach (

    $results
    as
    $row

) {


    $current_score =

        intval(

            $row["score"]

        );


    $total_score +=
        $current_score;


    if (

        $current_score
        >
        $highest_score

    ) {

        $highest_score =
            $current_score;

    }

}


$average_score = 0;


if (

    $total_attempts > 0

) {

    $average_score =

        round(

            $total_score
            /
            $total_attempts,

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
        Student Results - SmartExam
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>


        .results-container {

            max-width: 1250px;

            margin: 0 auto;

        }


        .page-header {

            margin-bottom: 25px;

        }


        .page-header h1 {

            font-size: 32px;

            margin-bottom: 8px;

        }


        .page-header p {

            color: var(--text-muted);

        }


        .top-actions {

            display: flex;

            flex-wrap: wrap;

            gap: 12px;

            margin-bottom: 20px;

        }


        .back-button,
        .download-button {

            display: inline-block;

            padding: 11px 18px;

            border-radius: 9px;

            text-decoration: none;

            font-weight: bold;

            transition: 0.3s ease;

        }


        .back-button {

            background: rgba(
                255,
                255,
                255,
                0.08
            );

            color: white;

        }


        .download-button {

            background:

                linear-gradient(

                    135deg,

                    #16a34a,

                    #15803d

                );

            color: white;

        }


        .back-button:hover,
        .download-button:hover {

            transform: translateY(-2px);

        }


        .download-button:hover {

            box-shadow:

                0 8px 20px

                rgba(
                    22,
                    163,
                    74,
                    0.35
                );

        }


        .exam-info {

            padding: 25px;

            margin-bottom: 25px;

            border-radius: 16px;

            background:

                linear-gradient(

                    135deg,

                    rgba(
                        99,
                        102,
                        241,
                        0.18
                    ),

                    rgba(
                        168,
                        85,
                        247,
                        0.12
                    )

                );

            border:

                1px solid

                rgba(
                    139,
                    92,
                    246,
                    0.3
                );

        }


        .exam-info h2 {

            margin-bottom: 10px;

        }


        .exam-meta {

            display: flex;

            flex-wrap: wrap;

            gap: 20px;

            color: var(--text-muted);

            font-size: 14px;

        }


        .stats-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    3,

                    1fr

                );

            gap: 18px;

            margin-bottom: 25px;

        }


        .stat-card {

            padding: 22px;

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


        .stat-label {

            color: var(--text-muted);

            font-size: 13px;

            margin-bottom: 8px;

        }


        .stat-value {

            font-size: 28px;

            font-weight: bold;

        }


        .table-card {

            overflow-x: auto;

            padding: 20px;

            border-radius: 16px;

            background:

                rgba(
                    255,
                    255,
                    255,
                    0.04
                );

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 850px;

        }


        th {

            text-align: left;

            padding: 15px;

            font-size: 13px;

            color: var(--text-muted);

            border-bottom:

                1px solid

                rgba(
                    255,
                    255,
                    255,
                    0.1
                );

        }


        td {

            padding: 16px 15px;

            border-bottom:

                1px solid

                rgba(
                    255,
                    255,
                    255,
                    0.06
                );

        }


        tr:hover {

            background:

                rgba(
                    255,
                    255,
                    255,
                    0.03
                );

        }


        .student-name {

            font-weight: bold;

        }


        .student-email {

            color: var(--text-muted);

            font-size: 12px;

            margin-top: 4px;

        }


        .score {

            font-weight: bold;

            font-size: 17px;

        }


        .percentage {

            display: inline-block;

            padding: 6px 10px;

            border-radius: 8px;

            background:

                rgba(
                    34,
                    197,
                    94,
                    0.15
                );

            color: #86efac;

            font-weight: bold;

        }


        .empty-results {

            text-align: center;

            padding: 60px 20px;

            color: var(--text-muted);

        }


        @media (max-width: 700px) {


            .stats-grid {

                grid-template-columns: 1fr;

            }


            .top-actions {

                flex-direction: column;

            }


            .back-button,
            .download-button {

                text-align: center;

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
                class="sidebar-link"
            >

                <span>⏱️</span>

                My Attempts

            </a>


            <a
                href="my_created_rooms.php"
                class="sidebar-link active"
            >

                <span>📝</span>

                My Created Rooms

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


        <div class="results-container">


            <!-- ACTION BUTTONS -->

            <div class="top-actions">


                <a

                    href="my_created_rooms.php"

                    class="back-button"

                >

                    ← Back to My Created Rooms

                </a>


                <a

                    href="download_room_results.php?exam_id=<?php

                        echo intval(

                            $room_exam_id

                        );

                    ?>"

                    class="download-button"

                >

                    📥 Download Results

                </a>


            </div>


            <!-- HEADER -->

            <div class="page-header">


                <h1>

                    📊 Student Results

                </h1>


                <p>

                    View the performance of students who attempted your exam.

                </p>


            </div>


            <!-- EXAM INFORMATION -->

            <div class="exam-info">


                <h2>

                    <?php

                    echo htmlspecialchars(

                        $exam["title"]

                    );

                    ?>

                </h2>


                <div class="exam-meta">


                    <span>

                        🏠

                        <?php

                        echo htmlspecialchars(

                            $exam["room_name"]

                        );

                        ?>

                    </span>


                    <span>

                        🔑 Room Code:

                        <?php

                        echo htmlspecialchars(

                            $exam["room_code"]

                        );

                        ?>

                    </span>


                    <span>

                        📝

                        <?php

                        echo intval(

                            $exam["total_questions"]

                        );

                        ?>

                        Questions

                    </span>


                    <span>

                        ⏱️

                        <?php

                        echo intval(

                            $exam["duration"]

                        );

                        ?>

                        Minutes

                    </span>


                </div>


            </div>


            <!-- SUMMARY -->

            <div class="stats-grid">


                <div class="stat-card">


                    <div class="stat-label">

                        Total Attempts

                    </div>


                    <div class="stat-value">

                        <?php

                        echo $total_attempts;

                        ?>

                    </div>


                </div>


                <div class="stat-card">


                    <div class="stat-label">

                        Highest Score

                    </div>


                    <div class="stat-value">

                        <?php

                        echo $highest_score;

                        ?>

                        /

                        <?php

                        echo intval(

                            $exam["total_questions"]

                        );

                        ?>

                    </div>


                </div>


                <div class="stat-card">


                    <div class="stat-label">

                        Average Score

                    </div>


                    <div class="stat-value">

                        <?php

                        echo $average_score;

                        ?>

                    </div>


                </div>


            </div>


            <!-- RESULTS TABLE -->

            <div class="table-card">


                <?php if (empty($results)): ?>


                    <div class="empty-results">


                        <h2>

                            📭 No Students Have Attempted Yet

                        </h2>


                        <p>

                            Results will appear here after students complete the exam.

                        </p>


                    </div>


                <?php else: ?>


                    <table>


                        <thead>


                            <tr>


                                <th>

                                    #

                                </th>


                                <th>

                                    Student

                                </th>


                                <th>

                                    Score

                                </th>


                                <th>

                                    Correct Answers

                                </th>


                                <th>

                                    Percentage

                                </th>


                                <th>

                                    Submitted At

                                </th>


                            </tr>


                        </thead>


                        <tbody>


                            <?php

                            $rank = 1;


                            foreach (

                                $results

                                as

                                $row

                            ):

                            ?>


                                <?php


                                $total_questions =

                                    intval(

                                        $row[

                                            "total_questions"

                                        ]

                                    );


                                $correct_answers =

                                    intval(

                                        $row[

                                            "correct_answers"

                                        ]

                                    );


                                $percentage = 0;


                                if (

                                    $total_questions > 0

                                ) {


                                    $percentage =

                                        round(

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


                                ?>


                                <tr>


                                    <td>

                                        <?php

                                        echo $rank++;

                                        ?>

                                    </td>


                                    <td>


                                        <div
                                            class="student-name"
                                        >

                                            <?php

                                            echo htmlspecialchars(

                                                $row["name"]

                                            );

                                            ?>

                                        </div>


                                        <div
                                            class="student-email"
                                        >

                                            <?php

                                            echo htmlspecialchars(

                                                $row["email"]

                                            );

                                            ?>

                                        </div>


                                    </td>


                                    <td>


                                        <span
                                            class="score"
                                        >

                                            <?php

                                            echo intval(

                                                $row["score"]

                                            );

                                            ?>

                                            /

                                            <?php

                                            echo $total_questions;

                                            ?>

                                        </span>


                                    </td>


                                    <td>

                                        <?php

                                        echo $correct_answers;

                                        ?>

                                        /

                                        <?php

                                        echo $total_questions;

                                        ?>

                                    </td>


                                    <td>


                                        <span
                                            class="percentage"
                                        >

                                            <?php

                                            echo $percentage;

                                            ?>%

                                        </span>


                                    </td>


                                    <td>


                                        <?php

                                        if (

                                            !empty(

                                                $row[

                                                    "submitted_at"

                                                ]

                                            )

                                        ) {


                                            echo date(

                                                "d M Y, h:i A",

                                                strtotime(

                                                    $row[

                                                        "submitted_at"

                                                    ]

                                                )

                                            );


                                        } else {


                                            echo "Not Submitted";


                                        }

                                        ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


</body>

</html>