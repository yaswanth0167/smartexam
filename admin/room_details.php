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

    LIMIT 1

";


$room_stmt = $conn->prepare($room_sql);

$room_stmt->bind_param(

    "i",

    $room_id

);

$room_stmt->execute();

$room_result = $room_stmt->get_result();


if (

    $room_result->num_rows === 0

) {

    header("Location: manage_rooms.php");

    exit();

}


$room = $room_result->fetch_assoc();


// ============================================================
// FETCH ROOM EXAMS
// ============================================================

$exam_sql = "

    SELECT

        re.id AS room_exam_id,

        re.title,

        re.description,

        re.duration,

        re.total_questions,

        re.created_at,

        COUNT(

            DISTINCT

            CASE

                WHEN ra.submitted_at IS NOT NULL

                THEN ra.id

            END

        ) AS attempts

    FROM room_exams re

    LEFT JOIN room_attempts ra

        ON re.id = ra.room_exam_id

    WHERE re.room_id = ?

    GROUP BY

        re.id,

        re.title,

        re.description,

        re.duration,

        re.total_questions,

        re.created_at

    ORDER BY

        re.created_at DESC

";


$exam_stmt = $conn->prepare($exam_sql);

$exam_stmt->bind_param(

    "i",

    $room_id

);

$exam_stmt->execute();

$exams_result = $exam_stmt->get_result();


// ============================================================
// TOTAL PARTICIPANTS
// ============================================================

$participants_sql = "

    SELECT COUNT(*) AS total

    FROM room_attempts ra

    INNER JOIN room_exams re

        ON ra.room_exam_id = re.id

    WHERE re.room_id = ?

";


$participants_stmt = $conn->prepare(

    $participants_sql

);

$participants_stmt->bind_param(

    "i",

    $room_id

);

$participants_stmt->execute();

$participants_result =

    $participants_stmt->get_result();


$total_participants = intval(

    $participants_result

        ->fetch_assoc()["total"]

);


// ============================================================
// TOTAL SUBMISSIONS
// ============================================================

$submissions_sql = "

    SELECT COUNT(*) AS total

    FROM room_attempts ra

    INNER JOIN room_exams re

        ON ra.room_exam_id = re.id

    WHERE

        re.room_id = ?

        AND ra.submitted_at IS NOT NULL

";


$submissions_stmt = $conn->prepare(

    $submissions_sql

);

$submissions_stmt->bind_param(

    "i",

    $room_id

);

$submissions_stmt->execute();

$submissions_result =

    $submissions_stmt->get_result();


$total_submissions = intval(

    $submissions_result

        ->fetch_assoc()["total"]

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

        Room Details - SmartExam

    </title>


    <link

        rel="stylesheet"

        href="../assets/css/style.css"

    >


    <style>


        /* =====================================================
           PAGE HEADER
        ===================================================== */


        .page-header {

            margin-bottom: 30px;

        }


        .back-link {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 18px;

            color: var(--text-muted);

            font-size: 14px;

            font-weight: 600;

        }


        .back-link:hover {

            color: var(--accent-cyan);

        }


        .page-header h1 {

            font-size: 32px;

            margin-bottom: 8px;

        }


        .page-header p {

            color: var(--text-muted);

        }


        /* =====================================================
           ROOM HERO
        ===================================================== */


        .room-hero {

            padding: 30px;

            margin-bottom: 30px;

            border-radius: var(--border-radius-lg);

            background:

                linear-gradient(

                    135deg,

                    rgba(0, 212, 255, 0.10),

                    rgba(124, 58, 237, 0.10)

                );

            border: 1px solid var(--glass-border);

            box-shadow: var(--box-shadow);

        }


        .room-hero-top {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            flex-wrap: wrap;

        }


        .room-title {

            color: #ffffff;

            font-size: 28px;

            font-weight: 800;

            margin-bottom: 10px;

        }


        .room-code {

            display: inline-block;

            padding: 8px 14px;

            border-radius: 8px;

            color: var(--accent-cyan);

            background:

                rgba(0, 212, 255, 0.10);

            border: 1px solid

                rgba(0, 212, 255, 0.30);

            font-weight: 800;

            letter-spacing: 1px;

        }


        .room-status {

            display: inline-flex;

            padding: 8px 15px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 800;

            text-transform: uppercase;

        }


        .status-active {

            color: #a8f9ff;

            background:

                rgba(0, 242, 254, 0.12);

            border: 1px solid

                rgba(0, 242, 254, 0.30);

        }


        .status-completed {

            color: #d8b4fe;

            background:

                rgba(168, 85, 247, 0.12);

            border: 1px solid

                rgba(168, 85, 247, 0.30);

        }


        .status-scheduled {

            color: #93c5fd;

            background:

                rgba(59, 130, 246, 0.12);

            border: 1px solid

                rgba(59, 130, 246, 0.30);

        }


        .status-pending {

            color: #ffd580;

            background:

                rgba(255, 153, 0, 0.12);

            border: 1px solid

                rgba(255, 153, 0, 0.30);

        }


        /* =====================================================
           ROOM INFO
        ===================================================== */


        .room-info-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(

                        210px,

                        1fr

                    )

                );

            gap: 16px;

            margin-top: 25px;

        }


        .info-box {

            padding: 18px;

            border-radius: var(--border-radius-md);

            background: rgba(0, 0, 0, 0.25);

            border: 1px solid var(--glass-border);

        }


        .info-label {

            color: var(--text-muted);

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 0.06em;

            margin-bottom: 7px;

        }


        .info-value {

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;

        }


        .info-sub {

            color: var(--text-muted);

            font-size: 12px;

            margin-top: 4px;

        }


        /* =====================================================
           STAT CARDS
        ===================================================== */


        .stats-grid {

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

            margin-bottom: 35px;

        }


        .stat-card {

            padding: 23px;

            border-radius: var(--border-radius-md);

            background: var(--glass-bg);

            border: 1px solid var(--glass-border);

        }


        .stat-icon {

            font-size: 27px;

            margin-bottom: 8px;

        }


        .stat-value {

            color: #ffffff;

            font-size: 30px;

            font-weight: 800;

            margin: 5px 0;

        }


        .stat-label {

            color: var(--text-muted);

            font-size: 13px;

            font-weight: 600;

        }


        /* =====================================================
           SECTION
        ===================================================== */


        .section-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;

            flex-wrap: wrap;

        }


        .section-header h2 {

            margin: 0;

            font-size: 24px;

        }


        /* =====================================================
           EXAM CARDS
        ===================================================== */


        .exam-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(

                        280px,

                        1fr

                    )

                );

            gap: 20px;

        }


        .exam-card {

            padding: 25px;

            border-radius: var(--border-radius-md);

            background: var(--glass-bg);

            border: 1px solid var(--glass-border);

            transition: var(--transition);

        }


        .exam-card:hover {

            transform: translateY(-6px);

            border-color: var(--glass-border-hover);

            box-shadow:

                0 15px 35px

                rgba(

                    0,

                    212,

                    255,

                    0.15

                );

        }


        .exam-title {

            color: #ffffff;

            font-size: 19px;

            font-weight: 800;

            margin-bottom: 8px;

        }


        .exam-description {

            color: var(--text-muted);

            font-size: 13px;

            line-height: 1.6;

            min-height: 45px;

            margin-bottom: 18px;

        }


        .exam-meta {

            display: flex;

            gap: 14px;

            flex-wrap: wrap;

            padding-top: 15px;

            border-top: 1px solid var(--glass-border);

        }


        .exam-meta span {

            color: var(--text-muted);

            font-size: 12px;

        }


        .exam-meta strong {

            color: var(--accent-cyan);

        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */


        .empty-state {

            text-align: center;

            padding: 60px 20px;

            color: var(--text-muted);

            border: 1px solid var(--glass-border);

            border-radius: var(--border-radius-md);

            background: var(--glass-bg);

        }


        .empty-icon {

            font-size: 50px;

            margin-bottom: 12px;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */


        @media (max-width: 768px) {


            .main-content {

                margin-left: 0;

                padding: 25px 18px;

            }


            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

            }


            .room-title {

                font-size: 23px;

            }


            .page-header h1 {

                font-size: 26px;

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


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->


    <main class="main-content">


        <div class="page-header">


            <a

                href="manage_rooms.php"

                class="back-link"

            >

                ← Back to Manage Rooms

            </a>


            <h1>

                🏠 Examination Room Details

            </h1>


            <p>

                View complete information about this examination room.

            </p>


        </div>


        <!-- =================================================
             ROOM HERO
        ================================================== -->


        <?php

        $status = strtolower(

            trim(

                $room["status"]

            )

        );


        $status_class =

            "status-" .

            $status;

        ?>


        <div class="room-hero">


            <div class="room-hero-top">


                <div>


                    <div class="room-title">


                        <?php

                        echo htmlspecialchars(

                            $room["room_name"]

                        );

                        ?>


                    </div>


                    <span class="room-code">


                        <?php

                        echo htmlspecialchars(

                            $room["room_code"]

                        );

                        ?>


                    </span>


                </div>


                <span

                    class="room-status

                    <?php

                    echo htmlspecialchars(

                        $status_class

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


            <div class="room-info-grid">


                <div class="info-box">


                    <div class="info-label">

                        Created By

                    </div>


                    <div class="info-value">


                        <?php

                        echo htmlspecialchars(

                            $room["creator_name"]

                        );

                        ?>


                    </div>


                    <div class="info-sub">


                        <?php

                        echo htmlspecialchars(

                            $room["creator_email"]

                        );

                        ?>


                    </div>


                </div>


                <div class="info-box">


                    <div class="info-label">

                        Start Time

                    </div>


                    <div class="info-value">


                        <?php

                        if (

                            !empty(

                                $room["start_time"]

                            )

                        ) {

                            echo date(

                                "d M Y, h:i A",

                                strtotime(

                                    $room["start_time"]

                                )

                            );

                        } else {

                            echo "Not scheduled";

                        }

                        ?>


                    </div>


                </div>


                <div class="info-box">


                    <div class="info-label">

                        End Time

                    </div>


                    <div class="info-value">


                        <?php

                        if (

                            !empty(

                                $room["end_time"]

                            )

                        ) {

                            echo date(

                                "d M Y, h:i A",

                                strtotime(

                                    $room["end_time"]

                                )

                            );

                        } else {

                            echo "Not available";

                        }

                        ?>


                    </div>


                </div>


                <div class="info-box">


                    <div class="info-label">

                        Created At

                    </div>


                    <div class="info-value">


                        <?php

                        echo date(

                            "d M Y, h:i A",

                            strtotime(

                                $room["created_at"]

                            )

                        );

                        ?>


                    </div>


                </div>


            </div>


        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->


        <div class="stats-grid">


            <div class="stat-card">


                <div class="stat-icon">

                    📝

                </div>


                <div class="stat-value">


                    <?php

                    echo $exams_result->num_rows;

                    ?>


                </div>


                <div class="stat-label">

                    Room Exams

                </div>


            </div>


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

                    echo $total_submissions;

                    ?>


                </div>


                <div class="stat-label">

                    Completed Submissions

                </div>


            </div>


        </div>


        <!-- =================================================
             ROOM EXAMS
        ================================================== -->


        <div class="section-header">


            <h2>

                📋 Exams in This Room

            </h2>


            <a

                href="room_results.php?id=<?php

                echo $room_id;

                ?>"

                class="btn btn-primary"

            >

                📊 View Results

            </a>


        </div>


        <?php

        if (

            $exams_result &&

            $exams_result->num_rows > 0

        ):

        ?>


            <div class="exam-grid">


                <?php

                while (

                    $exam =

                    $exams_result->fetch_assoc()

                ):

                ?>


                    <div class="exam-card">


                        <div class="exam-title">


                            <?php

                            echo htmlspecialchars(

                                $exam["title"]

                            );

                            ?>


                        </div>


                        <div class="exam-description">


                            <?php

                            echo htmlspecialchars(

                                $exam["description"]

                                ??

                                "No description provided."

                            );

                            ?>


                        </div>


                        <div class="exam-meta">


                            <span>

                                Questions:

                                <strong>


                                    <?php

                                    echo intval(

                                        $exam["total_questions"]

                                    );

                                    ?>


                                </strong>

                            </span>


                            <span>

                                Duration:

                                <strong>


                                    <?php

                                    echo intval(

                                        $exam["duration"]

                                    );

                                    ?>

                                    min

                                </strong>

                            </span>


                            <span>

                                Attempts:

                                <strong>


                                    <?php

                                    echo intval(

                                        $exam["attempts"]

                                    );

                                    ?>


                                </strong>

                            </span>


                        </div>


                    </div>


                <?php

                endwhile;

                ?>


            </div>


        <?php else: ?>


            <div class="empty-state">


                <div class="empty-icon">

                    📝

                </div>


                <h3>

                    No Exams Found

                </h3>


                <p>

                    No exams have been added to this room yet.

                </p>


            </div>


        <?php endif; ?>


    </main>


</div>


</body>

</html>