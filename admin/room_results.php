<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

date_default_timezone_set("Asia/Kolkata");


// ============================================================
// VALIDATE ROOM ID
// ============================================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {

    header("Location: manage_rooms.php");

    exit();

}

$room_id = intval($_GET["id"]);


// ============================================================
// FETCH ROOM DETAILS
// ============================================================

$room_sql = "

    SELECT

        r.id,

        r.room_name,

        r.room_code,

        r.status,

        r.start_time,

        r.end_time,

        r.created_at,

        u.name AS creator_name,

        u.email AS creator_email

    FROM rooms r

    LEFT JOIN users u

        ON r.creator_id = u.id

    WHERE r.id = ?

";

$stmt = $conn->prepare($room_sql);

$stmt->bind_param(
    "i",
    $room_id
);

$stmt->execute();

$room_result = $stmt->get_result();

$room = $room_result->fetch_assoc();

$stmt->close();


if (!$room) {

    header("Location: manage_rooms.php");

    exit();

}


// ============================================================
// ROOM STATISTICS
// ============================================================


// TOTAL PARTICIPANTS

$total_participants = 0;

$sql = "

    SELECT COUNT(*) AS total

    FROM room_attempts ra

    INNER JOIN room_exams re

        ON ra.room_exam_id = re.id

    WHERE re.room_id = ?

";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $room_id
);

$stmt->execute();

$total_participants = intval(

    $stmt
        ->get_result()
        ->fetch_assoc()["total"]

);

$stmt->close();


// COMPLETED PARTICIPANTS

$completed_participants = 0;

$sql = "

    SELECT COUNT(*) AS total

    FROM room_attempts ra

    INNER JOIN room_exams re

        ON ra.room_exam_id = re.id

    WHERE re.room_id = ?

    AND ra.submitted_at IS NOT NULL

";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $room_id
);

$stmt->execute();

$completed_participants = intval(

    $stmt
        ->get_result()
        ->fetch_assoc()["total"]

);

$stmt->close();


// AVERAGE SCORE

$average_score = 0;

$sql = "

    SELECT

        AVG(

            CASE

                WHEN ra.total_questions > 0

                THEN

                    (ra.score / ra.total_questions) * 100

                ELSE 0

            END

        ) AS average_score

    FROM room_attempts ra

    INNER JOIN room_exams re

        ON ra.room_exam_id = re.id

    WHERE re.room_id = ?

    AND ra.submitted_at IS NOT NULL

";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $room_id
);

$stmt->execute();

$average_score = round(

    floatval(

        $stmt
            ->get_result()
            ->fetch_assoc()["average_score"] ?? 0

    ),

    2

);

$stmt->close();


// BEST SCORE

$best_score = 0;

$sql = "

    SELECT

        MAX(

            CASE

                WHEN ra.total_questions > 0

                THEN

                    (ra.score / ra.total_questions) * 100

                ELSE 0

            END

        ) AS best_score

    FROM room_attempts ra

    INNER JOIN room_exams re

        ON ra.room_exam_id = re.id

    WHERE re.room_id = ?

    AND ra.submitted_at IS NOT NULL

";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $room_id
);

$stmt->execute();

$best_score = round(

    floatval(

        $stmt
            ->get_result()
            ->fetch_assoc()["best_score"] ?? 0

    ),

    2

);

$stmt->close();


// ============================================================
// FETCH ROOM RESULTS
// ============================================================

$results_sql = "

    SELECT

        ra.id AS attempt_id,

        ra.score,

        ra.total_questions,

        ra.correct_answers,

        ra.started_at,

        ra.submitted_at,

        u.name AS student_name,

        u.email AS student_email

    FROM room_attempts ra

    INNER JOIN room_exams re

        ON ra.room_exam_id = re.id

    INNER JOIN users u

        ON ra.user_id = u.id

    WHERE re.room_id = ?

    ORDER BY

        ra.submitted_at DESC,

        ra.id DESC

";

$stmt = $conn->prepare($results_sql);

$stmt->bind_param(
    "i",
    $room_id
);

$stmt->execute();

$results = $stmt->get_result();

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

        Room Results - SmartExam

    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .page-header {

            margin-bottom: 30px;

        }


        .back-link {

            display: inline-flex;

            margin-bottom: 20px;

            color: var(--text-muted);

            font-size: 14px;

        }


        .room-header {

            padding: 30px;

            margin-bottom: 30px;

            border-radius: var(--border-radius-lg);

            background:

                linear-gradient(

                    135deg,

                    rgba(0, 212, 255, 0.10),

                    rgba(124, 58, 237, 0.10)

                );

            border:

                1px solid

                rgba(0, 212, 255, 0.18);

        }


        .room-header h1 {

            margin-bottom: 8px;

        }


        .room-meta {

            display: flex;

            flex-wrap: wrap;

            gap: 20px;

            color: var(--text-muted);

            font-size: 14px;

        }


        .room-code {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 7px;

            color: var(--accent-cyan);

            background:

                rgba(0, 212, 255, 0.10);

            border:

                1px solid

                rgba(0, 212, 255, 0.25);

            font-weight: 800;

            letter-spacing: 1px;

        }


        .stats-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(

                        210px,

                        1fr

                    )

                );

            gap: 20px;

            margin-bottom: 35px;

        }


        .stat-card {

            padding: 24px;

            border-radius: var(--border-radius-md);

            background: var(--glass-bg);

            border: 1px solid var(--glass-border);

            transition: var(--transition);

        }


        .stat-card:hover {

            transform: translateY(-5px);

            border-color: var(--glass-border-hover);

        }


        .stat-icon {

            font-size: 28px;

            margin-bottom: 10px;

        }


        .stat-value {

            font-size: 30px;

            font-weight: 800;

            margin: 5px 0;

        }


        .stat-label {

            color: var(--text-muted);

            font-size: 13px;

        }


        .section-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;

        }


        .section-header h2 {

            margin: 0;

        }


        .table-wrapper {

            width: 100%;

            overflow-x: auto;

            border-radius: var(--border-radius-md);

            border: 1px solid var(--glass-border);

            background: var(--glass-bg);

            box-shadow: var(--box-shadow);

        }


        .results-table {

            width: 100%;

            min-width: 950px;

            border-collapse: collapse;

        }


        .results-table th {

            padding: 18px 20px;

            text-align: left;

            background:

                rgba(

                    0,

                    0,

                    0,

                    0.35

                );

            color: var(--accent-cyan);

            font-size: 12px;

            text-transform: uppercase;

            letter-spacing: 0.06em;

            border-bottom: 1px solid var(--glass-border);

        }


        .results-table td {

            padding: 18px 20px;

            color: var(--text-muted);

            border-bottom: 1px solid var(--glass-border);

        }


        .results-table tr:last-child td {

            border-bottom: none;

        }


        .results-table tbody tr {

            transition: var(--transition);

        }


        .results-table tbody tr:hover {

            background:

                rgba(

                    0,

                    212,

                    255,

                    0.035

                );

        }


        .student-name {

            color: #ffffff;

            font-weight: 700;

        }


        .student-email {

            color: var(--text-muted);

            font-size: 12px;

            margin-top: 4px;

        }


        .score-badge {

            display: inline-block;

            padding: 7px 12px;

            border-radius: 8px;

            color: #a8f9ff;

            background:

                rgba(

                    0,

                    242,

                    254,

                    0.12

                );

            border:

                1px solid

                rgba(

                    0,

                    242,

                    254,

                    0.25

                );

            font-weight: 800;

        }


        .percentage {

            color: var(--accent-cyan);

            font-weight: 800;

        }


        .pending {

            color: #ffd580;

            font-weight: 700;

        }


        .empty-state {

            text-align: center;

            padding: 70px 20px;

            color: var(--text-muted);

        }


        .empty-icon {

            font-size: 55px;

            margin-bottom: 15px;

        }


        @media (max-width: 768px) {

            .room-meta {

                flex-direction: column;

                gap: 8px;

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

                href="manage_exams.php"

                class="sidebar-link"

            >

                <span>📝</span>

                Manage Exams

            </a>


            <a

                href="manage_questions.php"

                class="sidebar-link"

            >

                <span>❓</span>

                Manage Questions

            </a>


            <a

                href="manage_users.php"

                class="sidebar-link"

            >

                <span>👥</span>

                Manage Users

            </a>


            <a

                href="manage_rooms.php"

                class="sidebar-link active"

            >

                <span>🏠</span>

                Manage Rooms

            </a>


            <a

                href="results.php"

                class="sidebar-link"

            >

                <span>📈</span>

                Results

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


        <a

            href="manage_rooms.php"

            class="back-link"

        >

            ← Back to Manage Rooms

        </a>


        <!-- ROOM INFORMATION -->

        <div class="room-header">


            <h1>

                🏠

                <?php

                echo htmlspecialchars(

                    $room["room_name"]

                );

                ?>

            </h1>


            <div class="room-meta">


                <span>

                    🔑 Code:

                    <strong class="room-code">

                        <?php

                        echo htmlspecialchars(

                            $room["room_code"]

                        );

                        ?>

                    </strong>

                </span>


                <span>

                    👤 Creator:

                    <?php

                    echo htmlspecialchars(

                        $room["creator_name"]

                    );

                    ?>

                </span>


                <span>

                    📧

                    <?php

                    echo htmlspecialchars(

                        $room["creator_email"]

                    );

                    ?>

                </span>


                <span>

                    📌 Status:

                    <?php

                    echo htmlspecialchars(

                        ucfirst(

                            $room["status"]

                        )

                    );

                    ?>

                </span>


            </div>


        </div>


        <!-- STATISTICS -->

        <div class="stats-grid">


            <div class="stat-card">


                <div class="stat-icon">

                    👥

                </div>


                <div class="stat-value">

                    <?php

                    echo $total_participants;

                    ?>

                </div>


                <div class="stat-label">

                    Total Participants

                </div>


            </div>


            <div class="stat-card">


                <div class="stat-icon">

                    ✅

                </div>


                <div class="stat-value">

                    <?php

                    echo $completed_participants;

                    ?>

                </div>


                <div class="stat-label">

                    Completed Attempts

                </div>


            </div>


            <div class="stat-card">


                <div class="stat-icon">

                    🎯

                </div>


                <div class="stat-value">

                    <?php

                    echo $average_score;

                    ?>%

                </div>


                <div class="stat-label">

                    Average Score

                </div>


            </div>


            <div class="stat-card">


                <div class="stat-icon">

                    🏆

                </div>


                <div class="stat-value">

                    <?php

                    echo $best_score;

                    ?>%

                </div>


                <div class="stat-label">

                    Best Score

                </div>


            </div>


        </div>


        <!-- RESULTS -->

        <div class="section-header">


            <h2>

                📊 Student Results

            </h2>


            <a

                href="manage_rooms.php"

                class="btn btn-secondary"

            >

                ← Back

            </a>


        </div>


        <div class="table-wrapper">


            <?php

            if (

                $results &&

                $results->num_rows > 0

            ):

            ?>


                <table class="results-table">


                    <thead>

                        <tr>

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

                                Started At

                            </th>


                            <th>

                                Submitted At

                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php

                        while (

                            $result =

                            $results->fetch_assoc()

                        ):

                        ?>


                            <?php

                            $score =

                                intval(

                                    $result["score"]

                                );


                            $total_questions =

                                intval(

                                    $result[

                                        "total_questions"

                                    ]

                                );


                            $correct_answers =

                                intval(

                                    $result[

                                        "correct_answers"

                                    ]

                                );


                            $percentage =

                                $total_questions > 0

                                ?

                                round(

                                    (

                                        $score /

                                        $total_questions

                                    ) * 100,

                                    2

                                )

                                :

                                0;

                            ?>


                            <tr>


                                <td>


                                    <div

                                        class="student-name"

                                    >

                                        <?php

                                        echo htmlspecialchars(

                                            $result[

                                                "student_name"

                                            ]

                                        );

                                        ?>

                                    </div>


                                    <div

                                        class="student-email"

                                    >

                                        <?php

                                        echo htmlspecialchars(

                                            $result[

                                                "student_email"

                                            ]

                                        );

                                        ?>

                                    </div>


                                </td>


                                <td>


                                    <span

                                        class="score-badge"

                                    >

                                        <?php

                                        echo $score;

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

                                            $result[

                                                "started_at"

                                            ]

                                        )

                                    ) {

                                        echo date(

                                            "d M Y, h:i A",

                                            strtotime(

                                                $result[

                                                    "started_at"

                                                ]

                                            )

                                        );

                                    } else {

                                        echo "—";

                                    }

                                    ?>

                                </td>


                                <td>


                                    <?php

                                    if (

                                        !empty(

                                            $result[

                                                "submitted_at"

                                            ]

                                        )

                                    ) {

                                        echo date(

                                            "d M Y, h:i A",

                                            strtotime(

                                                $result[

                                                    "submitted_at"

                                                ]

                                            )

                                        );

                                    } else {

                                        echo "<span class='pending'>In Progress</span>";

                                    }

                                    ?>

                                </td>


                            </tr>


                        <?php

                        endwhile;

                        ?>


                    </tbody>


                </table>


            <?php else: ?>


                <div class="empty-state">


                    <div class="empty-icon">

                        📊

                    </div>


                    <h3>

                        No Student Results Yet

                    </h3>


                    <p>

                        Students who attempt this examination room will appear here.

                    </p>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>