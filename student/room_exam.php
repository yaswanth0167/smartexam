<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = $_SESSION["user_id"];


// ============================================================
// VALIDATE ROOM ID
// ============================================================

if (
    !isset($_GET["room_id"]) ||
    !is_numeric($_GET["room_id"])
) {

    header("Location: join_room.php");
    exit();

}

$room_id = intval($_GET["room_id"]);


// ============================================================
// FETCH ROOM + EXAM
// ============================================================

$sql = "

    SELECT

        r.id AS room_id,
        r.room_code,
        r.room_name,
        r.start_time,
        r.end_time,
        r.status,
        r.exam_type,
        r.creator_id,

        re.id AS exam_id,
        re.title,
        re.description,
        re.duration,
        re.total_questions

    FROM rooms r

    INNER JOIN room_exams re

        ON re.room_id = r.id

    WHERE r.id = ?

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
    "i",
    $room_id
);


$stmt->execute();


$result = $stmt->get_result();


if (
    $result->num_rows !== 1
) {

    die("Exam room not found.");

}


$exam = $result->fetch_assoc();


$stmt->close();


// ============================================================
// TIME VALIDATION
// ============================================================

date_default_timezone_set("Asia/Kolkata");


$current_time = time();


$start_timestamp = strtotime(
    $exam["start_time"]
);


$end_timestamp = strtotime(
    $exam["end_time"]
);


$exam_started =
    $current_time >= $start_timestamp;


$exam_expired =
    $current_time > $end_timestamp;


// ============================================================
// FORMAT DATES
// ============================================================

$formatted_start = date(
    "d M Y, h:i A",
    $start_timestamp
);


$formatted_end = date(
    "d M Y, h:i A",
    $end_timestamp
);

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

        <?php

        echo htmlspecialchars(
            $exam["title"]
        );

        ?>

        - SmartExam

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


        .exam-card {

            padding: 35px;

            border-radius: 18px;

        }


        .exam-title {

            font-size: 30px;

            margin-bottom: 12px;

        }


        .exam-description {

            color: var(--text-muted);

            line-height: 1.7;

            margin-bottom: 30px;

        }


        .room-badge {

            display: inline-block;

            padding: 10px 18px;

            border-radius: 10px;

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

            font-weight: 700;

            letter-spacing: 2px;

            margin-bottom: 25px;

        }


        .info-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(

                        200px,

                        1fr

                    )

                );

            gap: 18px;

            margin: 25px 0;

        }


        .info-box {

            padding: 20px;

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

                    0.08

                );

        }


        .info-label {

            display: block;

            color: var(--text-muted);

            font-size: 13px;

            margin-bottom: 8px;

        }


        .info-value {

            font-size: 18px;

            font-weight: 700;

        }


        .schedule-box {

            margin-top: 25px;

            padding: 20px;

            border-radius: 14px;

            background:

                linear-gradient(

                    135deg,

                    rgba(

                        34,

                        197,

                        94,

                        0.12

                    ),

                    rgba(

                        59,

                        130,

                        246,

                        0.10

                    )

                );

            border:

                1px solid

                rgba(

                    34,

                    197,

                    94,

                    0.25

                );

        }


        .schedule-box p {

            margin: 8px 0;

        }


        .action-area {

            margin-top: 30px;

            text-align: center;

        }


        .start-button {

            width: 100%;

            padding: 17px;

            font-size: 17px;

        }


        .notice {

            padding: 18px;

            border-radius: 12px;

            margin-top: 20px;

            text-align: center;

        }


        .notice.warning {

            background:

                rgba(

                    245,

                    158,

                    11,

                    0.12

                );

            border:

                1px solid

                rgba(

                    245,

                    158,

                    11,

                    0.35

                );

        }


        .notice.danger {

            background:

                rgba(

                    239,

                    68,

                    68,

                    0.12

                );

            border:

                1px solid

                rgba(

                    239,

                    68,

                    68,

                    0.35

                );

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
                class="sidebar-link active"
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


        <div class="room-container">


            <div class="page-header">


                <h1>

                    📝 Examination Details

                </h1>


                <p>

                    Review the examination information before starting.

                </p>


            </div>


            <div class="card exam-card">


                <div class="room-badge">

                    ROOM CODE:

                    <?php

                    echo htmlspecialchars(

                        $exam["room_code"]

                    );

                    ?>

                </div>


                <h2 class="exam-title">


                    <?php

                    echo htmlspecialchars(

                        $exam["title"]

                    );

                    ?>


                </h2>


                <p class="exam-description">


                    <?php

                    echo nl2br(

                        htmlspecialchars(

                            $exam["description"]

                        )

                    );

                    ?>


                </p>


                <div class="info-grid">


                    <div class="info-box">


                        <span class="info-label">

                            🏠 Room Name

                        </span>


                        <span class="info-value">

                            <?php

                            echo htmlspecialchars(

                                $exam["room_name"]

                            );

                            ?>

                        </span>


                    </div>


                    <div class="info-box">


                        <span class="info-label">

                            ❓ Total Questions

                        </span>


                        <span class="info-value">

                            <?php

                            echo htmlspecialchars(

                                $exam["total_questions"]

                            );

                            ?>

                        </span>


                    </div>


                    <div class="info-box">


                        <span class="info-label">

                            ⏱️ Exam Duration

                        </span>


                        <span class="info-value">

                            <?php

                            echo htmlspecialchars(

                                $exam["duration"]

                            );

                            ?>

                            Minutes

                        </span>


                    </div>


                    <div class="info-box">


                        <span class="info-label">

                            🤖 Exam Type

                        </span>


                        <span class="info-value">

                            <?php

                            echo strtoupper(

                                htmlspecialchars(

                                    $exam["exam_type"]

                                )

                            );

                            ?>

                        </span>


                    </div>


                </div>


                <div class="schedule-box">


                    <p>

                        🟢

                        <strong>

                            Exam Starts:

                        </strong>


                        <?php

                        echo $formatted_start;

                        ?>


                    </p>


                    <p>

                        🔴

                        <strong>

                            Exam Ends:

                        </strong>


                        <?php

                        echo $formatted_end;

                        ?>


                    </p>


                </div>


                <div class="action-area">


                    <?php if (!$exam_started): ?>


                        <div class="notice warning">


                            ⏳

                            The examination has not started yet.

                            <br><br>

                            Please return during the scheduled time.

                        </div>


                    <?php elseif ($exam_expired): ?>


                        <div class="notice danger">


                            ❌

                            This examination has already ended.

                        </div>


                    <?php else: ?>


                        <!-- =================================================
                             CORRECT START EXAM LINK
                        ================================================== -->

                        <a

                            href="room_take_exam.php?id=<?php echo $exam["exam_id"]; ?>&room_id=<?php echo $exam["room_id"]; ?>"

                            class="btn btn-primary start-button"

                        >

                            🚀 Start Examination

                        </a>


                    <?php endif; ?>


                </div>


            </div>


        </div>


    </main>


</div>


</body>

</html>