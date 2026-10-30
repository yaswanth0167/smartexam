<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = $_SESSION["user_id"];
$student_name = $_SESSION["user_name"];


// ============================================================
// 1. AVAILABLE TRADITIONAL EXAMS
// ============================================================

$exam_sql = "
    SELECT
        id,
        title,
        description,
        duration,
        total_marks
    FROM exams
    WHERE status = 'published'
    ORDER BY created_at DESC
";

$exam_result = $conn->query($exam_sql);


// ============================================================
// 2. TOTAL AVAILABLE EXAMS
// ============================================================

$total_exams = 0;

$total_exams_sql = "
    SELECT COUNT(*) AS total
    FROM exams
    WHERE status = 'published'
";

$total_exams_res = $conn->query($total_exams_sql);

if ($total_exams_res) {

    $total_exams = intval(
        $total_exams_res
            ->fetch_assoc()["total"]
    );

}


// ============================================================
// 3. TOTAL TRADITIONAL ATTEMPTS
// ============================================================

$traditional_attempts = 0;

$attempt_sql = "
    SELECT COUNT(*) AS total
    FROM attempts
    WHERE user_id = ?
    AND submitted_at IS NOT NULL
";

$stmt = $conn->prepare($attempt_sql);

if ($stmt) {

    $stmt->bind_param("i", $student_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {

        $traditional_attempts = intval(
            $result
                ->fetch_assoc()["total"]
        );

    }

    $stmt->close();

}


// ============================================================
// 4. TOTAL ROOM ATTEMPTS
// ============================================================

$room_attempts = 0;

$room_attempt_sql = "
    SELECT COUNT(*) AS total
    FROM room_attempts
    WHERE user_id = ?
    AND submitted_at IS NOT NULL
";

$stmt = $conn->prepare($room_attempt_sql);

if ($stmt) {

    $stmt->bind_param("i", $student_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {

        $room_attempts = intval(
            $result
                ->fetch_assoc()["total"]
        );

    }

    $stmt->close();

}


// ============================================================
// 5. TOTAL ALL ATTEMPTS
// ============================================================

$total_attempts =
    $traditional_attempts
    +
    $room_attempts;


// ============================================================
// 6. BEST TRADITIONAL SCORE
// ============================================================

$traditional_best = 0;

$best_score_sql = "
    SELECT
        MAX(
            CASE
                WHEN total_questions > 0
                THEN
                    (score / total_questions) * 100
                ELSE 0
            END
        ) AS best_score
    FROM attempts
    WHERE user_id = ?
    AND submitted_at IS NOT NULL
";

$stmt = $conn->prepare($best_score_sql);

if ($stmt) {

    $stmt->bind_param("i", $student_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {

        $data = $result->fetch_assoc();

        $traditional_best =
            floatval(
                $data["best_score"] ?? 0
            );

    }

    $stmt->close();

}


// ============================================================
// 7. BEST ROOM SCORE
// ============================================================

$room_best = 0;

$room_best_sql = "
    SELECT
        MAX(
            CASE
                WHEN total_questions > 0
                THEN
                    (correct_answers / total_questions) * 100
                ELSE 0
            END
        ) AS best_score
    FROM room_attempts
    WHERE user_id = ?
    AND submitted_at IS NOT NULL
";

$stmt = $conn->prepare($room_best_sql);

if ($stmt) {

    $stmt->bind_param("i", $student_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {

        $data = $result->fetch_assoc();

        $room_best =
            floatval(
                $data["best_score"] ?? 0
            );

    }

    $stmt->close();

}


// ============================================================
// 8. FINAL BEST SCORE
// ============================================================

$best_score = round(
    max(
        $traditional_best,
        $room_best
    ),
    2
);


// ============================================================
// 9. TOTAL SCORE CALCULATION
// ============================================================

$total_score_sum = 0;
$total_score_count = 0;


// TRADITIONAL SCORES

$score_sql = "
    SELECT
        SUM(
            CASE
                WHEN total_questions > 0
                THEN
                    (score / total_questions) * 100
                ELSE 0
            END
        ) AS score_sum,

        COUNT(*) AS score_count

    FROM attempts

    WHERE user_id = ?

    AND submitted_at IS NOT NULL
";

$stmt = $conn->prepare($score_sql);

if ($stmt) {

    $stmt->bind_param("i", $student_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {

        $traditional_data =
            $result->fetch_assoc();

        $total_score_sum += floatval(
            $traditional_data["score_sum"] ?? 0
        );

        $total_score_count += intval(
            $traditional_data["score_count"] ?? 0
        );

    }

    $stmt->close();

}


// ROOM SCORES

$room_score_sql = "
    SELECT
        SUM(
            CASE
                WHEN total_questions > 0
                THEN
                    (correct_answers / total_questions) * 100
                ELSE 0
            END
        ) AS score_sum,

        COUNT(*) AS score_count

    FROM room_attempts

    WHERE user_id = ?

    AND submitted_at IS NOT NULL
";

$stmt = $conn->prepare($room_score_sql);

if ($stmt) {

    $stmt->bind_param("i", $student_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {

        $room_data =
            $result->fetch_assoc();

        $total_score_sum += floatval(
            $room_data["score_sum"] ?? 0
        );

        $total_score_count += intval(
            $room_data["score_count"] ?? 0
        );

    }

    $stmt->close();

}


// ============================================================
// 10. FINAL AVERAGE SCORE
// ============================================================

$average_score = 0;

if ($total_score_count > 0) {

    $average_score = round(
        $total_score_sum /
        $total_score_count,
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

    Student Dashboard - SmartExam

</title>


<link
    rel="stylesheet"
    href="../assets/css/style.css"
>


<style>


/* ============================================================
   EXAM GRID
============================================================ */

.exam-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(
                300px,
                1fr
            )
        );

    gap: 25px;

    margin-top: 25px;

}


.exam-card {

    display: flex;

    flex-direction: column;

    justify-content: space-between;

    min-height: 220px;

}


.exam-meta {

    display: flex;

    justify-content: space-between;

    font-size: 14px;

    color: var(--text-muted);

    margin: 15px 0;

    border-top:
        1px solid
        rgba(
            255,
            255,
            255,
            0.05
        );

    padding-top: 15px;

}


/* ============================================================
   AI ANALYTICS CARD
============================================================ */

.ai-analytics-section {

    margin-top: 35px;

    margin-bottom: 40px;

}


.ai-analytics-card {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 25px;

    padding: 28px;

    border-radius: 18px;

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

            0.35

        );

}


.ai-analytics-content h2 {

    margin-bottom: 10px;

    font-size: 23px;

}


.ai-analytics-content p {

    color: var(--text-muted);

    line-height: 1.6;

    margin-bottom: 18px;

}


.ai-analytics-icon {

    font-size: 65px;

    min-width: 90px;

    text-align: center;

}


@media (max-width: 700px) {

    .ai-analytics-card {

        flex-direction: column;

        align-items: flex-start;

    }

    .ai-analytics-icon {

        order: -1;

    }

}


/* ============================================================
   AI SIDEBAR BADGE
============================================================ */

.ai-generator-link {

    position: relative;

}


.ai-generator-link::after {

    content: "NEW";

    position: absolute;

    right: 12px;

    top: 50%;

    transform: translateY(-50%);

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 0.5px;

    padding: 3px 6px;

    border-radius: 5px;

    background: var(--accent);

    color: white;

}


</style>

</head>


<body>


<div class="dashboard-layout">


<!-- ============================================================
     SIDEBAR
============================================================ -->

<aside class="sidebar">


    <div class="sidebar-logo">

        Smart<span>Exam</span>

    </div>


    <nav class="sidebar-nav">


        <a

            href="dashboard.php"

            class="sidebar-link active"

        >

            <span>📊</span>

            Dashboard

        </a>


        <a

            href="#exams"

            class="sidebar-link"

        >

            <span>▣</span>

            Available Exams

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

            href="my_created_rooms.php"

            class="sidebar-link"

        >

            <span>📝</span>

            My Created Rooms

        </a>


        <!-- AI EXAM GENERATOR -->

        <a

            href="ai_exam_generator.php"

            class="sidebar-link ai-generator-link"

        >

            <span>🤖</span>

            AI Exam Generator

        </a>


        <!-- AI ANALYTICS -->

        <a

            href="ai_analytics.php"

            class="sidebar-link"

        >

            <span>📈</span>

            AI Analytics

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


<!-- ============================================================
     MAIN CONTENT
============================================================ -->

<main class="main-content">


    <!-- HERO -->

    <div class="hero">


        <h1

            style="

                font-size: 32px;

                margin-bottom: 10px;

            "

        >

            Welcome back,

            <span class="accent-text">

                <?php

                echo htmlspecialchars(

                    $student_name

                );

                ?>

            </span>

            ! 👋

        </h1>


        <p>

            Ready to challenge yourself?

            Browse exams, join private rooms,

            track your performance, and use

            AI-powered analytics to understand

            your strengths and weaknesses.

        </p>


    </div>


    <!-- ========================================================
         STATISTICS
    ========================================================= -->

    <div class="stats-grid">


        <div class="stat-card">


            <div style="font-size:24px;">

                📚

            </div>


            <div class="stat-value">

                <?php

                echo $total_exams;

                ?>

            </div>


            <div class="stat-label">

                Available Exams

            </div>


        </div>


        <div class="stat-card">


            <div style="font-size:24px;">

                📝

            </div>


            <div class="stat-value">

                <?php

                echo $total_attempts;

                ?>

            </div>


            <div class="stat-label">

                Completed Attempts

            </div>


        </div>


        <div class="stat-card">


            <div style="font-size:24px;">

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


        <div class="stat-card">


            <div style="font-size:24px;">

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


    </div>


    <!-- ========================================================
         AI ANALYTICS FEATURE
    ========================================================= -->

    <section class="ai-analytics-section">


        <div class="ai-analytics-card">


            <div class="ai-analytics-content">


                <h2>

                    🤖 AI-Powered Performance Analytics

                </h2>


                <p>

                    Get intelligent insights into your exam performance.

                    Analyze your strengths, identify weak topics,

                    track score trends, and receive personalized

                    recommendations to improve your learning.

                </p>


                <a

                    href="ai_analytics.php"

                    class="btn btn-primary"

                >

                    📈 Open AI Analytics

                </a>


            </div>


            <div class="ai-analytics-icon">

                🧠📊

            </div>


        </div>


    </section>


    <!-- ========================================================
         AVAILABLE EXAMS
    ========================================================= -->

    <section

        id="exams"

        style="

            margin-top: 40px;

            scroll-margin-top: 40px;

        "

    >


        <div

            style="

                display: flex;

                justify-content: space-between;

                align-items: center;

                margin-bottom: 25px;

                border-bottom:

                    1px solid

                    rgba(

                        255,

                        255,

                        255,

                        0.05

                    );

                padding-bottom: 15px;

            "

        >


            <h2

                style="

                    margin-bottom: 0;

                    font-size: 24px;

                "

            >

                Available Examinations

            </h2>


            <a

                href="attempt_history.php"

                class="btn btn-secondary"

                style="

                    font-size: 13px;

                    padding: 8px 16px;

                "

            >

                View My History

            </a>


        </div>


        <?php

        if (

            $exam_result

            &&

            $exam_result->num_rows > 0

        ):

        ?>


            <div class="exam-grid">


                <?php

                while (

                    $exam =

                    $exam_result

                        ->fetch_assoc()

                ):

                ?>


                    <div class="card exam-card">


                        <div>


                            <h3

                                style="

                                    font-size:18px;

                                    color:#ffffff;

                                    margin-bottom:10px;

                                "

                            >

                                <?php

                                echo htmlspecialchars(

                                    $exam["title"]

                                );

                                ?>

                            </h3>


                            <p

                                style="

                                    color:

                                        var(--text-muted);

                                    font-size:14px;

                                    line-height:1.5;

                                "

                            >

                                <?php

                                echo htmlspecialchars(

                                    $exam["description"]

                                    ?:

                                    "No description provided."

                                );

                                ?>

                            </p>


                        </div>


                        <div>


                            <div class="exam-meta">


                                <span>

                                    ⏱️

                                    <?php

                                    echo intval(

                                        $exam["duration"]

                                    );

                                    ?>

                                    min

                                </span>


                                <span>

                                    🎯

                                    <?php

                                    echo intval(

                                        $exam["total_marks"]

                                    );

                                    ?>

                                    Marks

                                </span>


                            </div>


                            <a

                                href="start_exam.php?id=<?php

                                    echo intval(

                                        $exam["id"]

                                    );

                                ?>"

                                class="btn btn-primary"

                                style="

                                    width:100%;

                                    font-size:14px;

                                "

                            >

                                Start Exam

                            </a>


                        </div>


                    </div>


                <?php

                endwhile;

                ?>


            </div>


        <?php

        else:

        ?>


            <div

                class="card"

                style="

                    text-align:center;

                    padding:50px;

                "

            >


                <div

                    style="

                        font-size:48px;

                        margin-bottom:20px;

                    "

                >

                    📚

                </div>


                <h3>

                    No Exams Available

                </h3>


                <p

                    style="

                        color:var(--text-muted);

                    "

                >

                    Currently there are no active exams.

                    Please check back later.

                </p>


            </div>


        <?php

        endif;

        ?>


    </section>


</main>


</div>


</body>

</html>