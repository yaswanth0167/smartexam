<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();


// ============================================================
// CURRENT USER
// ============================================================

$user_id = intval($_SESSION["user_id"]);


// ============================================================
// FETCH ROOMS CREATED BY CURRENT USER
// ============================================================
//
// rooms.creator_id = logged-in student
// room_exams.room_id = room
// room_attempts.room_exam_id = room exam
//
// IMPORTANT:
// Attempts are counted using room_attempts.room_exam_id.
// This is different from normal exams.
//

$sql = "

    SELECT

        r.id AS room_id,

        r.room_code,

        r.room_name,

        r.start_time,

        r.end_time,

        r.status,

        re.id AS room_exam_id,

        re.title,

        re.total_questions,

        COUNT(ra.id) AS total_attempts,

        COALESCE(
            MAX(ra.score),
            0
        ) AS highest_score,

        COALESCE(
            MAX(ra.total_questions),
            re.total_questions
        ) AS maximum_questions

    FROM rooms r

    INNER JOIN room_exams re

        ON re.room_id = r.id

    LEFT JOIN room_attempts ra

        ON ra.room_exam_id = re.id

    WHERE r.creator_id = ?

    GROUP BY

        r.id,

        r.room_code,

        r.room_name,

        r.start_time,

        r.end_time,

        r.status,

        re.id,

        re.title,

        re.total_questions

    ORDER BY r.id DESC

";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(

        "Database Error: "

        . htmlspecialchars(

            $conn->error

        )

    );

}


$stmt->bind_param(

    "i",

    $user_id

);


$stmt->execute();


$result = $stmt->get_result();

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


        .results-container {

            max-width: 1200px;

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


        .results-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(

                        330px,

                        1fr

                    )

                );

            gap: 24px;

        }


        .result-card {

            padding: 25px;

            border-radius: 18px;

            transition: 0.25s ease;

        }


        .result-card:hover {

            transform: translateY(-5px);

        }


        .room-code {

            display: inline-block;

            padding: 8px 14px;

            margin-bottom: 15px;

            border-radius: 8px;

            background:

                rgba(

                    99,

                    102,

                    241,

                    0.18

                );

            border:

                1px solid

                rgba(

                    139,

                    92,

                    246,

                    0.35

                );

            color: #c4b5fd;

            font-weight: 700;

            letter-spacing: 2px;

        }


        .result-card h2 {

            font-size: 22px;

            margin-bottom: 8px;

        }


        .result-card p {

            color: var(--text-muted);

            margin-bottom: 20px;

        }


        .stats-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    3,

                    1fr

                );

            gap: 12px;

            margin: 20px 0;

        }


        .stat-box {

            padding: 15px 10px;

            text-align: center;

            border-radius: 12px;

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

            font-size: 22px;

            font-weight: 800;

            margin-bottom: 5px;

        }


        .stat-label {

            display: block;

            font-size: 12px;

            color: var(--text-muted);

        }


        .room-details {

            margin-top: 20px;

            padding-top: 15px;

            border-top:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.10

                );

        }


        .detail-row {

            display: flex;

            justify-content: space-between;

            gap: 10px;

            padding: 7px 0;

            font-size: 13px;

        }


        .detail-row span {

            color: var(--text-muted);

        }


        .detail-row strong {

            text-align: right;

        }


        .action-buttons {

            display: flex;

            gap: 10px;

            margin-top: 22px;

        }


        .action-buttons a {

            flex: 1;

            text-align: center;

        }


        .empty-state {

            padding: 60px 20px;

            text-align: center;

            border-radius: 18px;

        }


        .empty-state h2 {

            margin-bottom: 10px;

        }


        .empty-state p {

            color: var(--text-muted);

        }


        @media (max-width: 600px) {


            .stats-grid {

                grid-template-columns:

                    1fr;

            }


            .action-buttons {

                flex-direction: column;

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

                class="sidebar-link"

            >

                <span>⏱️</span>

                My Attempts

            </a>


            <a

                href="room_results.php"

                class="sidebar-link active"

            >

                <span>📈</span>

                Room Results

            </a>


            <a

                href="room_leaderboard.php"

                class="sidebar-link"

            >

                <span>🏆</span>

                Room Leaderboard

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


        <div class="results-container">


            <!-- HEADER -->


            <div class="page-header">


                <h1>

                    📈 Room Results

                </h1>


                <p>

                    View student participation and performance in the exam rooms you created.

                </p>


            </div>


            <?php if ($result->num_rows === 0): ?>


                <div class="card empty-state">


                    <h2>

                        📭 No Room Results Yet

                    </h2>


                    <p>

                        You have not created any examination rooms yet.

                    </p>


                    <br>


                    <a

                        href="create_room.php"

                        class="btn btn-primary"

                    >

                        🚀 Create Exam Room

                    </a>


                </div>


            <?php else: ?>


                <div class="results-grid">


                    <?php while (

                        $room =

                        $result->fetch_assoc()

                    ): ?>


                        <div class="card result-card">


                            <!-- ROOM CODE -->


                            <div class="room-code">


                                ROOM:

                                <?php

                                echo htmlspecialchars(

                                    $room["room_code"]

                                );

                                ?>


                            </div>


                            <!-- TITLE -->


                            <h2>


                                <?php

                                echo htmlspecialchars(

                                    $room["title"]

                                );

                                ?>


                            </h2>


                            <p>


                                🏠

                                <?php

                                echo htmlspecialchars(

                                    $room["room_name"]

                                );

                                ?>


                            </p>


                            <!-- STATISTICS -->


                            <div class="stats-grid">


                                <div class="stat-box">


                                    <span

                                        class="stat-value"

                                    >


                                        <?php

                                        echo intval(

                                            $room["total_attempts"]

                                        );

                                        ?>


                                    </span>


                                    <span

                                        class="stat-label"

                                    >

                                        Attempts

                                    </span>


                                </div>


                                <div class="stat-box">


                                    <span

                                        class="stat-value"

                                    >


                                        <?php

                                        echo intval(

                                            $room["highest_score"]

                                        );

                                        ?>

                                        /


                                        <?php

                                        echo intval(

                                            $room["maximum_questions"]

                                        );

                                        ?>


                                    </span>


                                    <span

                                        class="stat-label"

                                    >

                                        Highest Score

                                    </span>


                                </div>


                                <div class="stat-box">


                                    <span

                                        class="stat-value"

                                    >


                                        <?php

                                        echo intval(

                                            $room["total_questions"]

                                        );

                                        ?>


                                    </span>


                                    <span

                                        class="stat-label"

                                    >

                                        Questions

                                    </span>


                                </div>


                            </div>


                            <!-- ROOM DETAILS -->


                            <div class="room-details">


                                <div class="detail-row">


                                    <span>

                                        Start Time

                                    </span>


                                    <strong>


                                        <?php

                                        echo date(

                                            "d M Y, h:i A",

                                            strtotime(

                                                $room["start_time"]

                                            )

                                        );

                                        ?>


                                    </strong>


                                </div>


                                <div class="detail-row">


                                    <span>

                                        End Time

                                    </span>


                                    <strong>


                                        <?php

                                        echo date(

                                            "d M Y, h:i A",

                                            strtotime(

                                                $room["end_time"]

                                            )

                                        );

                                        ?>


                                    </strong>


                                </div>


                                <div class="detail-row">


                                    <span>

                                        Status

                                    </span>


                                    <strong>


                                        <?php

                                        echo strtoupper(

                                            htmlspecialchars(

                                                $room["status"]

                                            )

                                        );

                                        ?>


                                    </strong>


                                </div>


                            </div>


                            <!-- ACTIONS -->


                            <div class="action-buttons">


                                <a

                                    href="room_leaderboard.php?room_id=<?php echo intval($room["room_id"]); ?>"

                                    class="btn btn-primary"

                                >

                                    🏆 Leaderboard

                                </a>


                                <a

                                    href="room_result_details.php?room_id=<?php echo intval($room["room_id"]); ?>"

                                    class="btn btn-secondary"

                                >

                                    👥 View Students

                                </a>


                            </div>


                        </div>


                    <?php endwhile; ?>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>


<?php

$stmt->close();

?>