<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

date_default_timezone_set("Asia/Kolkata");


// ============================================================
// ROOM STATISTICS
// ============================================================

$total_rooms = 0;

$sql = "SELECT COUNT(*) AS total FROM rooms";
$result = $conn->query($sql);

if ($result) {
    $total_rooms = intval($result->fetch_assoc()["total"]);
}


// ACTIVE ROOMS
$active_rooms = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM rooms
    WHERE status = 'active'
";

$result = $conn->query($sql);

if ($result) {
    $active_rooms = intval($result->fetch_assoc()["total"]);
}


// COMPLETED ROOMS
$completed_rooms = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM rooms
    WHERE status = 'completed'
";

$result = $conn->query($sql);

if ($result) {
    $completed_rooms = intval($result->fetch_assoc()["total"]);
}


// TOTAL PARTICIPANTS
$total_participants = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM room_attempts
";

$result = $conn->query($sql);

if ($result) {
    $total_participants = intval($result->fetch_assoc()["total"]);
}


// ============================================================
// FETCH ALL ROOMS
// ============================================================

$rooms_sql = "

    SELECT

        r.id,
        r.room_name,
        r.room_code,
        r.status,
        r.start_time,
        r.end_time,
        r.created_at,

        u.name AS creator_name,
        u.email AS creator_email,

        COUNT(
            DISTINCT
            CASE
                WHEN ra.submitted_at IS NOT NULL
                THEN ra.id
            END
        ) AS total_attempts

    FROM rooms r

    LEFT JOIN users u
        ON r.creator_id = u.id

    LEFT JOIN room_exams re
        ON r.id = re.room_id

    LEFT JOIN room_attempts ra
        ON re.id = ra.room_exam_id

    GROUP BY

        r.id,
        r.room_name,
        r.room_code,
        r.status,
        r.start_time,
        r.end_time,
        r.created_at,
        u.name,
        u.email

    ORDER BY r.created_at DESC

";

$rooms_result = $conn->query($rooms_sql);

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
        Manage Rooms - SmartExam
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>


        /* =====================================================
           IMPORTANT PAGE FIX
        ===================================================== */

        html,
        body {

            width: 100%;

            max-width: 100%;

            overflow-x: hidden;

        }


        .dashboard-layout {

            width: 100%;

            max-width: 100%;

            overflow-x: hidden;

        }


        /* =====================================================
           MAIN CONTENT RESPONSIVE FIX
        ===================================================== */

        .main-content {

            width: calc(100% - 260px);

            max-width: calc(100% - 260px);

            min-width: 0;

            margin-left: 260px;

            padding: 35px;

            overflow-x: hidden;

        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-header {

            margin-bottom: 30px;

        }


        .page-header h1 {

            font-size: clamp(24px, 3vw, 32px);

            margin-bottom: 8px;

        }


        .page-header p {

            color: var(--text-muted);

            font-size: 14px;

        }


        /* =====================================================
           ROOM STATS
        ===================================================== */

        .room-stats {

            width: 100%;

            display: grid;

            grid-template-columns:

                repeat(

                    4,

                    minmax(0, 1fr)

                );

            gap: 18px;

            margin-bottom: 35px;

        }


        .room-stat-card {

            min-width: 0;

            padding: 22px;

            border-radius: var(--border-radius-md);

            background: var(--glass-bg);

            border: 1px solid var(--glass-border);

            transition: var(--transition);

        }


        .room-stat-card:hover {

            transform: translateY(-5px);

            border-color: var(--glass-border-hover);

            box-shadow:

                0 15px 35px

                rgba(

                    0,

                    212,

                    255,

                    0.18

                );

        }


        .room-stat-icon {

            font-size: 26px;

            margin-bottom: 8px;

        }


        .room-stat-value {

            font-size: 30px;

            font-weight: 800;

            margin: 6px 0;

        }


        .room-stat-label {

            color: var(--text-muted);

            font-size: 13px;

            font-weight: 600;

        }


        /* =====================================================
           SECTION HEADER
        ===================================================== */

        .section-header {

            width: 100%;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 18px;

        }


        .section-header h2 {

            margin: 0;

            font-size: 23px;

            white-space: nowrap;

        }


        /* =====================================================
           TABLE WRAPPER
        ===================================================== */

        .rooms-table-wrapper {

            width: 100%;

            max-width: 100%;

            overflow-x: auto;

            overflow-y: hidden;

            border-radius: var(--border-radius-md);

            border: 1px solid var(--glass-border);

            background: var(--glass-bg);

            box-shadow: var(--box-shadow);

            -webkit-overflow-scrolling: touch;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .rooms-table {

            width: 100%;

            min-width: 980px;

            border-collapse: collapse;

            table-layout: auto;

        }


        .rooms-table th {

            text-align: left;

            padding: 16px 18px;

            background:

                rgba(

                    0,

                    0,

                    0,

                    0.35

                );

            color: var(--accent-cyan);

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 0.06em;

            border-bottom: 1px solid var(--glass-border);

            white-space: nowrap;

        }


        .rooms-table td {

            padding: 17px 18px;

            color: var(--text-muted);

            border-bottom: 1px solid var(--glass-border);

            vertical-align: middle;

        }


        .rooms-table tr:last-child td {

            border-bottom: none;

        }


        .rooms-table tbody tr {

            transition: var(--transition);

        }


        .rooms-table tbody tr:hover {

            background:

                rgba(

                    0,

                    212,

                    255,

                    0.035

                );

        }


        /* =====================================================
           COLUMN WIDTHS
        ===================================================== */

        .rooms-table th:nth-child(1) {

            min-width: 150px;

        }


        .rooms-table th:nth-child(2) {

            min-width: 125px;

        }


        .rooms-table th:nth-child(3) {

            min-width: 220px;

        }


        .rooms-table th:nth-child(4) {

            min-width: 110px;

        }


        .rooms-table th:nth-child(5) {

            min-width: 80px;

        }


        .rooms-table th:nth-child(6) {

            min-width: 120px;

        }


        .rooms-table th:nth-child(7) {

            min-width: 190px;

        }


        /* =====================================================
           ROOM NAME
        ===================================================== */

        .room-name {

            color: #ffffff;

            font-weight: 700;

            margin-bottom: 4px;

            max-width: 180px;

            word-break: break-word;

        }


        .room-id {

            font-size: 11px;

            color: var(--text-muted);

        }


        /* =====================================================
           ROOM CODE
        ===================================================== */

        .room-code {

            display: inline-block;

            padding: 7px 12px;

            border-radius: 8px;

            background:

                rgba(

                    0,

                    212,

                    255,

                    0.1

                );

            border: 1px solid

                rgba(

                    0,

                    212,

                    255,

                    0.25

                );

            color: var(--accent-cyan);

            font-weight: 800;

            letter-spacing: 1px;

            white-space: nowrap;

        }


        /* =====================================================
           CREATOR
        ===================================================== */

        .creator-name {

            color: #ffffff;

            font-weight: 600;

            white-space: nowrap;

        }


        .creator-email {

            font-size: 11px;

            color: var(--text-muted);

            margin-top: 3px;

            white-space: nowrap;

        }


        /* =====================================================
           STATUS
        ===================================================== */

        .room-status {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 6px 11px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            white-space: nowrap;

        }


        .status-active {

            color: #a8f9ff;

            background:

                rgba(

                    0,

                    242,

                    254,

                    0.12

                );

            border: 1px solid

                rgba(

                    0,

                    242,

                    254,

                    0.25

                );

        }


        .status-scheduled {

            color: #93c5fd;

            background:

                rgba(

                    59,

                    130,

                    246,

                    0.12

                );

            border: 1px solid

                rgba(

                    59,

                    130,

                    246,

                    0.25

                );

        }


        .status-completed {

            color: #d8b4fe;

            background:

                rgba(

                    168,

                    85,

                    247,

                    0.12

                );

            border: 1px solid

                rgba(

                    168,

                    85,

                    247,

                    0.25

                );

        }


        .status-pending {

            color: #ffd580;

            background:

                rgba(

                    255,

                    153,

                    0,

                    0.12

                );

            border: 1px solid

                rgba(

                    255,

                    153,

                    0,

                    0.25

                );

        }


        /* =====================================================
           DATE
        ===================================================== */

        .date-text {

            color: #ffffff;

            font-size: 13px;

            white-space: nowrap;

        }


        .time-text {

            color: var(--text-muted);

            font-size: 11px;

            margin-top: 3px;

            white-space: nowrap;

        }


        /* =====================================================
           ATTEMPTS
        ===================================================== */

        .attempt-count {

            color: var(--accent-cyan);

            font-size: 18px;

            font-weight: 800;

        }


        /* =====================================================
           ACTIONS
        ===================================================== */

        .room-actions {

            display: flex;

            align-items: center;

            gap: 7px;

            flex-wrap: nowrap;

        }


        .small-btn {

            padding: 8px 11px;

            border-radius: 8px;

            font-size: 11px;

            font-weight: 700;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            white-space: nowrap;

        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {

            text-align: center;

            padding: 70px 20px;

            color: var(--text-muted);

        }


        .empty-icon {

            font-size: 55px;

            margin-bottom: 15px;

        }


        .empty-state h3 {

            margin-bottom: 8px;

        }


        /* =====================================================
           LARGE LAPTOPS / SMALL DESKTOPS
        ===================================================== */

        @media (max-width: 1200px) {


            .main-content {

                padding: 28px;

            }


            .room-stats {

                grid-template-columns:

                    repeat(

                        2,

                        minmax(0, 1fr)

                    );

            }


        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 900px) {


            .sidebar {

                width: 220px;

            }


            .main-content {

                width: calc(100% - 220px);

                max-width: calc(100% - 220px);

                margin-left: 220px;

                padding: 24px;

            }


            .section-header h2 {

                font-size: 20px;

            }


        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 700px) {


            .dashboard-layout {

                display: block;

            }


            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

                min-height: auto;

                padding: 20px;

            }


            .sidebar-logo {

                margin-bottom: 20px;

            }


            .sidebar-nav {

                display: grid;

                grid-template-columns:

                    repeat(

                        2,

                        minmax(0, 1fr)

                    );

                gap: 8px;

            }


            .sidebar-link {

                padding: 11px;

                font-size: 12px;

            }


            .sidebar-footer {

                margin-top: 20px;

            }


            .main-content {

                width: 100%;

                max-width: 100%;

                margin-left: 0;

                padding: 20px;

            }


            .room-stats {

                grid-template-columns: 1fr;

            }


            .section-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .section-header h2 {

                white-space: normal;

            }


            .rooms-table {

                min-width: 980px;

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


            <h1>

                🏠 Manage Examination Rooms

            </h1>


            <p>

                Monitor, review and manage all student-created examination rooms.

            </p>


        </div>


        <!-- =================================================
             ROOM STATISTICS
        ================================================== -->


        <div class="room-stats">


            <div class="room-stat-card">


                <div class="room-stat-icon">

                    🏠

                </div>


                <div class="room-stat-value">

                    <?php echo $total_rooms; ?>

                </div>


                <div class="room-stat-label">

                    Total Rooms

                </div>


            </div>


            <div class="room-stat-card">


                <div class="room-stat-icon">

                    🟢

                </div>


                <div class="room-stat-value">

                    <?php echo $active_rooms; ?>

                </div>


                <div class="room-stat-label">

                    Active Rooms

                </div>


            </div>


            <div class="room-stat-card">


                <div class="room-stat-icon">

                    🏁

                </div>


                <div class="room-stat-value">

                    <?php echo $completed_rooms; ?>

                </div>


                <div class="room-stat-label">

                    Completed Rooms

                </div>


            </div>


            <div class="room-stat-card">


                <div class="room-stat-icon">

                    👥

                </div>


                <div class="room-stat-value">

                    <?php echo $total_participants; ?>

                </div>


                <div class="room-stat-label">

                    Total Participants

                </div>


            </div>


        </div>


        <!-- =================================================
             SECTION HEADER
        ================================================== -->


        <div class="section-header">


            <h2>

                📋 All Examination Rooms

            </h2>


            <a
                href="../student/dashboard.php"
                class="btn btn-secondary"
            >

                ↗ Student View

            </a>


        </div>


        <!-- =================================================
             ROOMS TABLE
        ================================================== -->


        <div class="rooms-table-wrapper">


            <?php if (

                $rooms_result &&

                $rooms_result->num_rows > 0

            ): ?>


                <table class="rooms-table">


                    <thead>


                        <tr>


                            <th>

                                Room

                            </th>


                            <th>

                                Room Code

                            </th>


                            <th>

                                Creator

                            </th>


                            <th>

                                Status

                            </th>


                            <th>

                                Attempts

                            </th>


                            <th>

                                Schedule

                            </th>


                            <th>

                                Actions

                            </th>


                        </tr>


                    </thead>


                    <tbody>


                        <?php while (

                            $room =

                            $rooms_result->fetch_assoc()

                        ):


                            $status = strtolower(

                                trim(

                                    $room["status"]

                                )

                            );


                            $status_class =

                                "status-" .

                                $status;

                        ?>


                            <tr>


                                <!-- ROOM -->


                                <td>


                                    <div class="room-name">


                                        <?php echo htmlspecialchars(

                                            $room["room_name"]

                                        ); ?>


                                    </div>


                                    <div class="room-id">


                                        Room ID:

                                        <?php echo intval(

                                            $room["id"]

                                        ); ?>


                                    </div>


                                </td>


                                <!-- ROOM CODE -->


                                <td>


                                    <span class="room-code">


                                        <?php echo htmlspecialchars(

                                            $room["room_code"]

                                        ); ?>


                                    </span>


                                </td>


                                <!-- CREATOR -->


                                <td>


                                    <div class="creator-name">


                                        <?php echo htmlspecialchars(

                                            $room["creator_name"]

                                        ); ?>


                                    </div>


                                    <div class="creator-email">


                                        <?php echo htmlspecialchars(

                                            $room["creator_email"]

                                        ); ?>


                                    </div>


                                </td>


                                <!-- STATUS -->


                                <td>


                                    <span class="room-status <?php echo htmlspecialchars(

                                        $status_class

                                    ); ?>">


                                        <?php echo htmlspecialchars(

                                            $room["status"]

                                        ); ?>


                                    </span>


                                </td>


                                <!-- ATTEMPTS -->


                                <td>


                                    <span class="attempt-count">


                                        <?php echo intval(

                                            $room["total_attempts"]

                                        ); ?>


                                    </span>


                                </td>


                                <!-- SCHEDULE -->


                                <td>


                                    <div class="date-text">


                                        <?php

                                        if (

                                            !empty(

                                                $room["start_time"]

                                            )

                                        ) {

                                            echo date(

                                                "d M Y",

                                                strtotime(

                                                    $room["start_time"]

                                                )

                                            );

                                        } else {

                                            echo "Not scheduled";

                                        }

                                        ?>


                                    </div>


                                    <div class="time-text">


                                        <?php

                                        if (

                                            !empty(

                                                $room["start_time"]

                                            )

                                        ) {

                                            echo date(

                                                "h:i A",

                                                strtotime(

                                                    $room["start_time"]

                                                )

                                            );

                                        }

                                        ?>


                                    </div>


                                </td>


                                <!-- ACTIONS -->


                                <td>


                                    <div class="room-actions">


                                        <a

                                            href="room_results.php?id=<?php echo intval(

                                                $room["id"]

                                            ); ?>"

                                            class="btn btn-primary small-btn"

                                        >

                                            📊 Results

                                        </a>


                                        <a

                                            href="room_details.php?id=<?php echo intval(

                                                $room["id"]

                                            ); ?>"

                                            class="btn btn-secondary small-btn"

                                        >

                                            👁 View

                                        </a>


                                    </div>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    </tbody>


                </table>


            <?php else: ?>


                <div class="empty-state">


                    <div class="empty-icon">

                        🏠

                    </div>


                    <h3>

                        No Examination Rooms Found

                    </h3>


                    <p>

                        Student-created examination rooms will appear here.

                    </p>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>