<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$creator_id = $_SESSION["user_id"];


// ============================================================
// FETCH ROOMS CREATED BY CURRENT STUDENT
// ============================================================

$sql = "

    SELECT

        r.id,
        r.room_code,
        r.room_name,
        r.exam_type,
        r.start_time,
        r.end_time,
        r.status,
        r.created_at,

        re.id AS room_exam_id,
        re.title,
        re.total_questions,
        re.duration,

        COUNT(DISTINCT ra.id) AS total_attempts

    FROM rooms r

    LEFT JOIN room_exams re

        ON r.id = re.room_id

    LEFT JOIN room_attempts ra

        ON re.id = ra.room_exam_id

    WHERE r.creator_id = ?

    GROUP BY

        r.id,
        r.room_code,
        r.room_name,
        r.exam_type,
        r.start_time,
        r.end_time,
        r.status,
        r.created_at,
        re.id,
        re.title,
        re.total_questions,
        re.duration

    ORDER BY r.created_at DESC

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
    $creator_id
);


$stmt->execute();


$result = $stmt->get_result();


$rooms = [];


while (
    $row = $result->fetch_assoc()
) {

    $rooms[] = $row;

}


$stmt->close();

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
        My Created Rooms - SmartExam
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>


        .page-container {

            max-width: 1200px;

            margin: auto;

        }


        .page-header {

            margin-bottom: 30px;

        }


        .page-header h1 {

            font-size: 32px;

            margin-bottom: 8px;

        }


        .page-header p {

            color: var(--text-muted);

        }


        .rooms-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(
                        320px,
                        1fr
                    )

                );

            gap: 22px;

        }


        .room-card {

            position: relative;

            padding: 25px;

            border-radius: 18px;

            background:

                linear-gradient(

                    145deg,

                    rgba(
                        30,
                        41,
                        59,
                        0.95
                    ),

                    rgba(
                        15,
                        23,
                        42,
                        0.95
                    )

                );

            border:

                1px solid

                rgba(
                    148,
                    163,
                    184,
                    0.15
                );

            transition:

                transform 0.3s ease,

                box-shadow 0.3s ease;

        }


        .room-card:hover {

            transform:

                translateY(
                    -6px
                );

            box-shadow:

                0 15px 35px

                rgba(
                    0,
                    0,
                    0,
                    0.3
                );

        }


        .room-card-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 15px;

            margin-bottom: 20px;

        }


        .room-card h2 {

            font-size: 21px;

            margin: 0;

        }


        .room-code {

            display: inline-block;

            margin-top: 10px;

            padding: 7px 12px;

            border-radius: 8px;

            background:

                rgba(
                    99,
                    102,
                    241,
                    0.15
                );

            color: #a5b4fc;

            font-size: 14px;

            font-weight: bold;

            letter-spacing: 1px;

        }


        .status-badge {

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 12px;

            text-transform: uppercase;

            font-weight: bold;

        }


        .status-scheduled {

            background:

                rgba(
                    59,
                    130,
                    246,
                    0.15
                );

            color: #93c5fd;

        }


        .status-active {

            background:

                rgba(
                    34,
                    197,
                    94,
                    0.15
                );

            color: #86efac;

        }


        .status-completed {

            background:

                rgba(
                    168,
                    85,
                    247,
                    0.15
                );

            color: #d8b4fe;

        }


        .room-info {

            display: grid;

            grid-template-columns:

                repeat(
                    2,
                    1fr
                );

            gap: 15px;

            margin: 20px 0;

        }


        .info-item {

            padding: 13px;

            border-radius: 10px;

            background:

                rgba(
                    255,
                    255,
                    255,
                    0.04
                );

        }


        .info-label {

            display: block;

            color: var(--text-muted);

            font-size: 12px;

            margin-bottom: 5px;

        }


        .info-value {

            font-size: 16px;

            font-weight: bold;

        }


        .room-dates {

            margin-bottom: 20px;

            padding: 13px;

            border-radius: 10px;

            background:

                rgba(
                    255,
                    255,
                    255,
                    0.04
                );

            font-size: 13px;

            color: var(--text-muted);

        }


        .room-actions {

            display: grid;

            grid-template-columns:

                repeat(
                    2,
                    1fr
                );

            gap: 10px;

        }


        .room-actions a {

            text-align: center;

            padding: 11px 8px;

            border-radius: 9px;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;

        }


        .btn-results {

            background: #4f46e5;

            color: white;

        }


        .btn-leaderboard {

            background: #7c3aed;

            color: white;

        }


        .empty-state {

            padding: 60px 20px;

            text-align: center;

            border-radius: 16px;

            background:

                rgba(
                    255,
                    255,
                    255,
                    0.04
                );

            color: var(--text-muted);

        }


        .empty-state h2 {

            color: white;

            margin-bottom: 10px;

        }


        @media (max-width: 600px) {


            .room-info {

                grid-template-columns: 1fr;

            }


            .room-actions {

                grid-template-columns: 1fr;

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


            <div class="page-header">


                <h1>

                    📝 My Created Rooms

                </h1>


                <p>

                    View the exam rooms you created and check student performance.

                </p>


            </div>


            <?php if (empty($rooms)): ?>


                <div class="empty-state">


                    <h2>

                        🏠 No Rooms Created Yet

                    </h2>


                    <p>

                        Create your first exam room to see it here.

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


                <div class="rooms-grid">


                    <?php foreach ($rooms as $room): ?>


                        <div class="room-card">


                            <div
                                class="room-card-header"
                            >


                                <div>


                                    <h2>

                                        <?php

                                        echo htmlspecialchars(

                                            $room["room_name"]

                                        );

                                        ?>

                                    </h2>


                                    <span
                                        class="room-code"
                                    >

                                        🔑

                                        <?php

                                        echo htmlspecialchars(

                                            $room["room_code"]

                                        );

                                        ?>

                                    </span>


                                </div>


                                <span

                                    class="status-badge status-<?php

                                    echo htmlspecialchars(

                                        strtolower(

                                            $room["status"]

                                        )

                                    );

                                    ?>"

                                >

                                    <?php

                                    echo htmlspecialchars(

                                        $room["status"]

                                    );

                                    ?>

                                </span>


                            </div>


                            <div
                                class="room-info"
                            >


                                <div
                                    class="info-item"
                                >

                                    <span
                                        class="info-label"
                                    >

                                        Exam Type

                                    </span>


                                    <span
                                        class="info-value"
                                    >

                                        <?php

                                        echo htmlspecialchars(

                                            strtoupper(

                                                $room["exam_type"]

                                            )

                                        );

                                        ?>

                                    </span>

                                </div>


                                <div
                                    class="info-item"
                                >

                                    <span
                                        class="info-label"
                                    >

                                        Questions

                                    </span>


                                    <span
                                        class="info-value"
                                    >

                                        <?php

                                        echo intval(

                                            $room["total_questions"]

                                        );

                                    ?>

                                    </span>

                                </div>


                                <div
                                    class="info-item"
                                >

                                    <span
                                        class="info-label"
                                    >

                                        Duration

                                    </span>


                                    <span
                                        class="info-value"
                                    >

                                        <?php

                                        echo intval(

                                            $room["duration"]

                                        );

                                    ?>

                                    min

                                    </span>

                                </div>


                                <div
                                    class="info-item"
                                >

                                    <span
                                        class="info-label"
                                    >

                                        Attempts

                                    </span>


                                    <span
                                        class="info-value"
                                    >

                                        <?php

                                        echo intval(

                                            $room["total_attempts"]

                                        );

                                    ?>

                                    </span>

                                </div>


                            </div>


                            <div
                                class="room-dates"
                            >

                                🕒

                                <strong>
                                    Starts:
                                </strong>

                                <?php

                                echo date(

                                    "d M Y, h:i A",

                                    strtotime(

                                        $room["start_time"]

                                    )

                                );

                                ?>

                                <br><br>

                                🏁

                                <strong>
                                    Ends:
                                </strong>

                                <?php

                                echo date(

                                    "d M Y, h:i A",

                                    strtotime(

                                        $room["end_time"]

                                    )

                                );

                                ?>

                            </div>


                            <?php if (!empty($room["room_exam_id"])): ?>


                                <div
                                    class="room-actions"
                                >


                                    <a

                                        href="room_student_results.php?exam_id=<?php

                                        echo intval(

                                            $room["room_exam_id"]

                                        );

                                        ?>"

                                        class="btn-results"

                                    >

                                        📊 View Results

                                    </a>


                                    <a

                                        href="room_leaderboard.php?exam_id=<?php

                                        echo intval(

                                            $room["room_exam_id"]

                                        );

                                        ?>"

                                        class="btn-leaderboard"

                                    >

                                        🏆 Leaderboard

                                    </a>


                                </div>


                            <?php endif; ?>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>