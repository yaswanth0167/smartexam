<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();


// ============================================================
// CREATOR ID
// ============================================================

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

$room_exam_id = intval($_GET["exam_id"]);


// ============================================================
// FETCH ROOM EXAM
// ONLY THE CREATOR CAN VIEW THIS LEADERBOARD
// ============================================================

$exam_sql = "

    SELECT

        re.id AS room_exam_id,

        re.title,

        re.total_questions,

        r.id AS room_id,

        r.room_name,

        r.room_code,

        r.creator_id

    FROM room_exams re

    INNER JOIN rooms r

        ON re.room_id = r.id

    WHERE re.id = ?

    AND r.creator_id = ?

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

    $creator_id

);


$exam_stmt->execute();


$exam_result =
    $exam_stmt->get_result();


if (
    $exam_result->num_rows !== 1
) {

    die("Room examination not found or access denied.");

}


$exam =
    $exam_result->fetch_assoc();


$exam_stmt->close();


// ============================================================
// FETCH LEADERBOARD
// ============================================================

$leaderboard_sql = "

    SELECT

        ra.id AS attempt_id,

        ra.user_id,

        ra.score,

        ra.total_questions,

        ra.correct_answers,

        ra.submitted_at,

        u.name,

        u.email

    FROM room_attempts ra

    INNER JOIN users u

        ON ra.user_id = u.id

    WHERE ra.room_exam_id = ?

    ORDER BY

        ra.score DESC,

        ra.submitted_at ASC

";


$leaderboard_stmt =
    $conn->prepare(
        $leaderboard_sql
    );


if (!$leaderboard_stmt) {

    die(

        "Database Error: "

        .

        htmlspecialchars(

            $conn->error

        )

    );

}


$leaderboard_stmt->bind_param(

    "i",

    $room_exam_id

);


$leaderboard_stmt->execute();


$leaderboard_result =

    $leaderboard_stmt->get_result();


$leaderboard = [];


while (

    $row =

    $leaderboard_result->fetch_assoc()

) {

    $leaderboard[] = $row;

}


$leaderboard_stmt->close();


// ============================================================
// CALCULATE RANK
// ============================================================

$rank = 1;

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
        Room Leaderboard - SmartExam
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .page-container {

            max-width: 1100px;

            margin: auto;

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


        .exam-info {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(

                        200px,

                        1fr

                    )

                );

            gap: 15px;

            margin-bottom: 25px;

        }


        .info-card {

            padding: 18px;

            border-radius: 14px;

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

                    0.10

                );

        }


        .info-label {

            display: block;

            color: var(--text-muted);

            font-size: 13px;

            margin-bottom: 7px;

        }


        .info-value {

            font-size: 18px;

            font-weight: bold;

        }


        .leaderboard-card {

            padding: 25px;

            border-radius: 18px;

            overflow-x: auto;

        }


        .leaderboard-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 750px;

        }


        .leaderboard-table th {

            text-align: left;

            padding: 15px;

            color: var(--text-muted);

            font-size: 13px;

            border-bottom:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.12

                );

        }


        .leaderboard-table td {

            padding: 16px 15px;

            border-bottom:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.07

                );

        }


        .leaderboard-table tr:hover {

            background:

                rgba(

                    255,

                    255,

                    255,

                    0.04

                );

        }


        .rank {

            font-size: 20px;

            font-weight: bold;

        }


        .rank-1 {

            color: #facc15;

        }


        .rank-2 {

            color: #cbd5e1;

        }


        .rank-3 {

            color: #cd7f32;

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

            font-size: 18px;

            font-weight: bold;

            color: #86efac;

        }


        .progress-container {

            width: 150px;

            height: 8px;

            border-radius: 10px;

            background:

                rgba(

                    255,

                    255,

                    255,

                    0.10

                );

            overflow: hidden;

        }


        .progress-bar {

            height: 100%;

            background: var(--accent);

            border-radius: 10px;

        }


        .empty-state {

            padding: 60px 20px;

            text-align: center;

            color: var(--text-muted);

        }


        .back-button {

            display: inline-block;

            margin-bottom: 20px;

        }


        @media (max-width: 600px) {

            .page-header h1 {

                font-size: 25px;

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


        <div class="page-container">


            <a
                href="my_created_rooms.php"
                class="btn btn-secondary back-button"
            >

                ← Back to My Created Rooms

            </a>


            <div class="page-header">


                <h1>

                    🏆 Room Leaderboard

                </h1>


                <p>

                    View the performance ranking of students who attempted this examination.

                </p>


            </div>


            <!-- EXAM INFORMATION -->

            <div class="exam-info">


                <div class="info-card">


                    <span class="info-label">

                        Room Name

                    </span>


                    <span class="info-value">

                        <?php

                        echo htmlspecialchars(

                            $exam["room_name"]

                        );

                        ?>

                    </span>


                </div>


                <div class="info-card">


                    <span class="info-label">

                        Exam

                    </span>


                    <span class="info-value">

                        <?php

                        echo htmlspecialchars(

                            $exam["title"]

                        );

                        ?>

                    </span>


                </div>


                <div class="info-card">


                    <span class="info-label">

                        Room Code

                    </span>


                    <span class="info-value">

                        <?php

                        echo htmlspecialchars(

                            $exam["room_code"]

                        );

                        ?>

                    </span>


                </div>


                <div class="info-card">


                    <span class="info-label">

                        Total Attempts

                    </span>


                    <span class="info-value">

                        <?php

                        echo count(

                            $leaderboard

                        );

                        ?>

                    </span>


                </div>


            </div>


            <!-- LEADERBOARD -->

            <div class="card leaderboard-card">


                <?php if (empty($leaderboard)): ?>


                    <div class="empty-state">


                        <h2>

                            📝 No Attempts Yet

                        </h2>


                        <p>

                            Students have not attempted this examination yet.

                        </p>


                    </div>


                <?php else: ?>


                    <table class="leaderboard-table">


                        <thead>


                            <tr>


                                <th>

                                    Rank

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


                            <?php foreach (

                                $leaderboard

                                as

                                $student

                            ): ?>


                                <?php

                                $total_questions =

                                    intval(

                                        $student[

                                            "total_questions"

                                        ]

                                    );


                                $score =

                                    intval(

                                        $student[

                                            "score"

                                        ]

                                    );


                                $percentage =

                                    $total_questions > 0

                                    ?

                                    round(

                                        (

                                            $score

                                            /

                                            $total_questions

                                        )

                                        *

                                        100,

                                        2

                                    )

                                    :

                                    0;


                                ?>


                                <tr>


                                    <td>


                                        <span

                                            class="rank

                                            <?php

                                            if (

                                                $rank === 1

                                            ) {

                                                echo "rank-1";

                                            }

                                            elseif (

                                                $rank === 2

                                            ) {

                                                echo "rank-2";

                                            }

                                            elseif (

                                                $rank === 3

                                            ) {

                                                echo "rank-3";

                                            }

                                            ?>

                                            "

                                        >

                                            <?php

                                            if (

                                                $rank === 1

                                            ) {

                                                echo "🥇";

                                            }

                                            elseif (

                                                $rank === 2

                                            ) {

                                                echo "🥈";

                                            }

                                            elseif (

                                                $rank === 3

                                            ) {

                                                echo "🥉";

                                            }

                                            else {

                                                echo $rank;

                                            }

                                            ?>

                                        </span>


                                    </td>


                                    <td>


                                        <div

                                            class="student-name"

                                        >


                                            <?php

                                            echo htmlspecialchars(

                                                $student["name"]

                                            );

                                            ?>


                                        </div>


                                        <div

                                            class="student-email"

                                        >


                                            <?php

                                            echo htmlspecialchars(

                                                $student["email"]

                                            );

                                            ?>


                                        </div>


                                    </td>


                                    <td>


                                        <span

                                            class="score"

                                        >

                                            <?php

                                            echo $score;

                                            ?>

                                        </span>


                                    </td>


                                    <td>


                                        <?php

                                        echo intval(

                                            $student[

                                                "correct_answers"

                                            ]

                                        );

                                        ?>

                                        /

                                        <?php

                                        echo $total_questions;

                                        ?>


                                    </td>


                                    <td>


                                        <div

                                            class="progress-container"

                                        >


                                            <div

                                                class="progress-bar"

                                                style="width:

                                                <?php

                                                echo min(

                                                    100,

                                                    $percentage

                                                );

                                                ?>%;"

                                            ></div>


                                        </div>


                                        <small>


                                            <?php

                                            echo $percentage;

                                            ?>%

                                        </small>


                                    </td>


                                    <td>


                                        <?php

                                        echo date(

                                            "d M Y, h:i A",

                                            strtotime(

                                                $student[

                                                    "submitted_at"

                                                ]

                                            )

                                        );

                                        ?>


                                    </td>


                                </tr>


                                <?php

                                $rank++;

                                ?>


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