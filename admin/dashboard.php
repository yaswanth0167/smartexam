<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

date_default_timezone_set("Asia/Kolkata");


// ============================================================
// BASIC COUNTS
// ============================================================


// TOTAL STUDENTS
$student_sql = "
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'student'
";

$student_result = $conn->query($student_sql);

$total_students = 0;

if ($student_result) {

    $row = $student_result->fetch_assoc();

    $total_students = intval($row["total"]);

}


// TOTAL TRADITIONAL EXAMS
$exam_sql = "
    SELECT COUNT(*) AS total
    FROM exams
";

$exam_result = $conn->query($exam_sql);

$total_exams = 0;

if ($exam_result) {

    $row = $exam_result->fetch_assoc();

    $total_exams = intval($row["total"]);

}


// TOTAL QUESTIONS
$question_sql = "
    SELECT COUNT(*) AS total
    FROM questions
";

$question_result = $conn->query($question_sql);

$total_questions = 0;

if ($question_result) {

    $row = $question_result->fetch_assoc();

    $total_questions = intval($row["total"]);

}


// TOTAL TRADITIONAL ATTEMPTS
$attempt_sql = "
    SELECT COUNT(*) AS total
    FROM attempts
    WHERE submitted_at IS NOT NULL
";

$attempt_result = $conn->query($attempt_sql);

$total_attempts = 0;

if ($attempt_result) {

    $row = $attempt_result->fetch_assoc();

    $total_attempts = intval($row["total"]);

}


// TOTAL ROOMS
$room_sql = "
    SELECT COUNT(*) AS total
    FROM rooms
";

$room_result = $conn->query($room_sql);

$total_rooms = 0;

if ($room_result) {

    $row = $room_result->fetch_assoc();

    $total_rooms = intval($row["total"]);

}


// TOTAL ROOM ATTEMPTS
$room_attempt_sql = "
    SELECT COUNT(*) AS total
    FROM room_attempts
    WHERE submitted_at IS NOT NULL
";

$room_attempt_result = $conn->query($room_attempt_sql);

$total_room_attempts = 0;

if ($room_attempt_result) {

    $row = $room_attempt_result->fetch_assoc();

    $total_room_attempts = intval($row["total"]);

}


// TOTAL ALL ATTEMPTS
$total_all_attempts =
    $total_attempts + $total_room_attempts;


// ============================================================
// AVERAGE SCORE
// ============================================================

$average_sql = "

    SELECT

        AVG(

            CASE

                WHEN q.total_questions > 0

                THEN

                    (a.score / q.total_questions) * 100

                ELSE 0

            END

        ) AS average_score

    FROM attempts a

    LEFT JOIN

    (

        SELECT

            exam_id,

            COUNT(*) AS total_questions

        FROM questions

        GROUP BY exam_id

    ) q

        ON a.exam_id = q.exam_id

    WHERE a.submitted_at IS NOT NULL

";

$average_result = $conn->query($average_sql);

$average_score = 0;

if ($average_result) {

    $row = $average_result->fetch_assoc();

    $average_score = round(

        floatval($row["average_score"]),

        2

    );

}


// ============================================================
// RECENT TRADITIONAL ATTEMPTS
// ============================================================

$recent_attempts_sql = "

    SELECT

        a.id,

        a.score,

        a.correct_answers,

        a.submitted_at,

        u.name AS student_name,

        u.email,

        e.title AS exam_title,

        COUNT(q.id) AS total_questions

    FROM attempts a

    INNER JOIN users u

        ON a.user_id = u.id

    INNER JOIN exams e

        ON a.exam_id = e.id

    LEFT JOIN questions q

        ON q.exam_id = e.id

    WHERE a.submitted_at IS NOT NULL

    GROUP BY

        a.id,

        a.score,

        a.correct_answers,

        a.submitted_at,

        u.name,

        u.email,

        e.title

    ORDER BY a.submitted_at DESC

    LIMIT 10

";

$recent_attempts_result =
    $conn->query($recent_attempts_sql);


// ============================================================
// RECENT ROOMS
// ============================================================

$recent_rooms_sql = "

    SELECT

        r.id,

        r.room_name,

        r.room_code,

        r.status,

        r.start_time,

        r.end_time,

        r.created_at,

        u.name AS creator_name,

        COUNT(

            DISTINCT

            CASE

                WHEN ra.submitted_at IS NOT NULL

                THEN ra.id

            END

        ) AS total_attempts

    FROM rooms r

    INNER JOIN users u

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

        u.name

    ORDER BY r.created_at DESC

    LIMIT 10

";

$recent_rooms_result =
    $conn->query($recent_rooms_sql);

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

        Admin Dashboard - SmartExam

    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .admin-container {

            max-width: 1400px;

            margin: 0 auto;

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

            padding: 22px;

            border-radius: 16px;

            background:

                linear-gradient(

                    145deg,

                    rgba(30, 41, 59, 0.95),

                    rgba(15, 23, 42, 0.95)

                );

            border:

                1px solid

                rgba(148, 163, 184, 0.15);

            transition: 0.3s ease;

        }


        .stat-card:hover {

            transform: translateY(-5px);

            box-shadow:

                0 15px 35px

                rgba(0, 0, 0, 0.25);

        }


        .stat-icon {

            font-size: 28px;

            margin-bottom: 12px;

        }


        .stat-value {

            font-size: 30px;

            font-weight: bold;

            margin-bottom: 5px;

        }


        .stat-label {

            color: var(--text-muted);

            font-size: 13px;

        }


        .section-title {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 18px;

        }


        .section-title h2 {

            margin: 0;

            font-size: 23px;

        }


        .table-card {

            padding: 20px;

            margin-bottom: 30px;

            border-radius: 16px;

            background:

                rgba(255, 255, 255, 0.04);

            border:

                1px solid

                rgba(255, 255, 255, 0.08);

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 850px;

        }


        th {

            text-align: left;

            padding: 14px;

            color: var(--text-muted);

            font-size: 12px;

            text-transform: uppercase;

            border-bottom:

                1px solid

                rgba(255, 255, 255, 0.1);

        }


        td {

            padding: 15px 14px;

            border-bottom:

                1px solid

                rgba(255, 255, 255, 0.06);

        }


        tr:hover {

            background:

                rgba(255, 255, 255, 0.03);

        }


        .student-name {

            font-weight: bold;

        }


        .student-email {

            color: var(--text-muted);

            font-size: 12px;

            margin-top: 4px;

        }


        .score-badge {

            padding: 6px 10px;

            border-radius: 8px;

            background:

                rgba(34, 197, 94, 0.15);

            color: #86efac;

            font-weight: bold;

        }


        .room-code {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 6px;

            background:

                rgba(99, 102, 241, 0.15);

            color: #a5b4fc;

            font-weight: bold;

            letter-spacing: 1px;

        }


        .status {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

            text-transform: uppercase;

        }


        .status-scheduled {

            background:

                rgba(59, 130, 246, 0.15);

            color: #93c5fd;

        }


        .status-active {

            background:

                rgba(34, 197, 94, 0.15);

            color: #86efac;

        }


        .status-completed {

            background:

                rgba(168, 85, 247, 0.15);

            color: #d8b4fe;

        }


        .empty-state {

            text-align: center;

            padding: 40px;

            color: var(--text-muted);

        }


        .quick-actions {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(

                        220px,

                        1fr

                    )

                );

            gap: 18px;

            margin-bottom: 35px;

        }


        .quick-action {

            display: block;

            padding: 20px;

            text-decoration: none;

            border-radius: 14px;

            background:

                rgba(255, 255, 255, 0.04);

            border:

                1px solid

                rgba(255, 255, 255, 0.08);

            transition: 0.3s;

        }


        .quick-action:hover {

            transform: translateY(-5px);

        }


        .quick-action-icon {

            font-size: 28px;

            margin-bottom: 10px;

        }


        .quick-action-title {

            color: white;

            font-weight: bold;

            margin-bottom: 5px;

        }


        .quick-action-text {

            color: var(--text-muted);

            font-size: 13px;

        }


        @media (max-width: 700px) {

            .page-header h1 {

                font-size: 26px;

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


            <!-- DASHBOARD -->

            <a

                href="dashboard.php"

                class="sidebar-link active"

            >

                <span>📊</span>

                Dashboard

            </a>


            <!-- MANAGE EXAMS -->

            <a

                href="exams.php"

                class="sidebar-link"

            >

                <span>📝</span>

                Manage Exams

            </a>


            <!-- QUESTION BANK -->

            <a

                href="questions.php"

                class="sidebar-link"

            >

                <span>❓</span>

                Question Bank

            </a>


            <!-- MANAGE USERS -->

            <a

                href="manage_users.php"

                class="sidebar-link"

            >

                <span>👥</span>

                Manage Users

            </a>


            <!-- MANAGE ROOMS -->

            <a

                href="manage_rooms.php"

                class="sidebar-link"

            >

                <span>🏠</span>

                Manage Rooms

            </a>


            <!-- RESULTS -->

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


        <div class="admin-container">


            <!-- HEADER -->

            <div class="page-header">


                <h1>

                    👋 Admin Dashboard

                </h1>


                <p>

                    Monitor SmartExam platform activity and performance.

                </p>


            </div>


            <!-- STATISTICS -->

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon">

                        👥

                    </div>

                    <div class="stat-value">

                        <?= $total_students ?>

                    </div>

                    <div class="stat-label">

                        Total Students

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">

                        📚

                    </div>

                    <div class="stat-value">

                        <?= $total_exams ?>

                    </div>

                    <div class="stat-label">

                        Traditional Exams

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">

                        ❓

                    </div>

                    <div class="stat-value">

                        <?= $total_questions ?>

                    </div>

                    <div class="stat-label">

                        Total Questions

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">

                        📝

                    </div>

                    <div class="stat-value">

                        <?= $total_attempts ?>

                    </div>

                    <div class="stat-label">

                        Traditional Attempts

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">

                        🏠

                    </div>

                    <div class="stat-value">

                        <?= $total_rooms ?>

                    </div>

                    <div class="stat-label">

                        Exam Rooms

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">

                        🏆

                    </div>

                    <div class="stat-value">

                        <?= $total_room_attempts ?>

                    </div>

                    <div class="stat-label">

                        Room Attempts

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">

                        📊

                    </div>

                    <div class="stat-value">

                        <?= $total_all_attempts ?>

                    </div>

                    <div class="stat-label">

                        Total Platform Attempts

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">

                        🎯

                    </div>

                    <div class="stat-value">

                        <?= $average_score ?>%

                    </div>

                    <div class="stat-label">

                        Average Traditional Score

                    </div>

                </div>


            </div>


            <!-- QUICK ACTIONS -->

            <div class="section-title">

                <h2>

                    ⚡ Quick Actions

                </h2>

            </div>


            <div class="quick-actions">


                <!-- CREATE EXAM -->

                <a

                    href="create_exam.php"

                    class="quick-action"

                >

                    <div class="quick-action-icon">

                        ➕📚

                    </div>

                    <div class="quick-action-title">

                        Create Exam

                    </div>

                    <div class="quick-action-text">

                        Create a new traditional examination.

                    </div>

                </a>


                <!-- MANAGE QUESTIONS -->

                <!-- IMPORTANT FIX:
                     questions.php EXISTS
                     manage_questions.php DOES NOT EXIST
                -->

                <a

                    href="questions.php"

                    class="quick-action"

                >

                    <div class="quick-action-icon">

                        ❓

                    </div>

                    <div class="quick-action-title">

                        Manage Questions

                    </div>

                    <div class="quick-action-text">

                        Add and manage examination questions.

                    </div>

                </a>


                <!-- MANAGE STUDENTS -->

                <a

                    href="manage_users.php"

                    class="quick-action"

                >

                    <div class="quick-action-icon">

                        👥

                    </div>

                    <div class="quick-action-title">

                        Manage Students

                    </div>

                    <div class="quick-action-text">

                        View registered students and accounts.

                    </div>

                </a>


                <!-- MANAGE ROOMS -->

                <a

                    href="manage_rooms.php"

                    class="quick-action"

                >

                    <div class="quick-action-icon">

                        🏠

                    </div>

                    <div class="quick-action-title">

                        Manage Exam Rooms

                    </div>

                    <div class="quick-action-text">

                        Monitor student-created examination rooms.

                    </div>

                </a>


            </div>


            <!-- RECENT TRADITIONAL ATTEMPTS -->

            <div class="section-title">

                <h2>

                    📝 Recent Traditional Exam Attempts

                </h2>

            </div>


            <div class="table-card">


                <?php if (

                    $recent_attempts_result &&

                    $recent_attempts_result->num_rows > 0

                ): ?>


                    <table>


                        <thead>

                            <tr>

                                <th>Student</th>

                                <th>Exam</th>

                                <th>Score</th>

                                <th>Correct</th>

                                <th>Submitted</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php while (

                                $attempt =

                                $recent_attempts_result

                                    ->fetch_assoc()

                            ): ?>


                                <tr>


                                    <td>

                                        <div

                                            class="student-name"

                                        >

                                            <?= htmlspecialchars(

                                                $attempt["student_name"]

                                            ) ?>

                                        </div>


                                        <div

                                            class="student-email"

                                        >

                                            <?= htmlspecialchars(

                                                $attempt["email"]

                                            ) ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(

                                            $attempt["exam_title"]

                                        ) ?>

                                    </td>


                                    <td>

                                        <span

                                            class="score-badge"

                                        >

                                            <?= intval(

                                                $attempt["score"]

                                            ) ?>

                                            /

                                            <?= intval(

                                                $attempt["total_questions"]

                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= intval(

                                            $attempt["correct_answers"]

                                        ) ?>

                                        /

                                        <?= intval(

                                            $attempt["total_questions"]

                                        ) ?>

                                    </td>


                                    <td>

                                        <?= date(

                                            "d M Y, h:i A",

                                            strtotime(

                                                $attempt["submitted_at"]

                                            )

                                        ) ?>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        </tbody>


                    </table>


                <?php else: ?>


                    <div class="empty-state">

                        📭 No completed traditional attempts yet.

                    </div>


                <?php endif; ?>


            </div>


            <!-- RECENT ROOMS -->

            <div class="section-title">

                <h2>

                    🏠 Recent Exam Rooms

                </h2>

            </div>


            <div class="table-card">


                <?php if (

                    $recent_rooms_result &&

                    $recent_rooms_result->num_rows > 0

                ): ?>


                    <table>


                        <thead>

                            <tr>

                                <th>Room</th>

                                <th>Code</th>

                                <th>Creator</th>

                                <th>Status</th>

                                <th>Attempts</th>

                                <th>Start Time</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php while (

                                $room =

                                $recent_rooms_result

                                    ->fetch_assoc()

                            ):


                                $status = strtolower(

                                    $room["status"]

                                );

                            ?>


                                <tr>


                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(

                                                $room["room_name"]

                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <span

                                            class="room-code"

                                        >

                                            <?= htmlspecialchars(

                                                $room["room_code"]

                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(

                                            $room["creator_name"]

                                        ) ?>

                                    </td>


                                    <td>

                                        <span

                                            class="status status-<?= htmlspecialchars(

                                                $status

                                            ) ?>"

                                        >

                                            <?= htmlspecialchars(

                                                $room["status"]

                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= intval(

                                            $room["total_attempts"]

                                        ) ?>

                                    </td>


                                    <td>

                                        <?= date(

                                            "d M Y, h:i A",

                                            strtotime(

                                                $room["start_time"]

                                            )

                                        ) ?>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        </tbody>


                    </table>


                <?php else: ?>


                    <div class="empty-state">

                        🏠 No examination rooms created yet.

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


</body>

</html>