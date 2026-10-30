<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = $_SESSION["user_id"];


// ==================================================
// NORMAL + AI EXAMS LEADERBOARD
// ==================================================

$sql = "

    SELECT

        user_id,

        name,

        email,

        COUNT(*) AS total_attempts,

        AVG(percentage) AS avg_percentage,

        MAX(percentage) AS best_percentage

    FROM

    (

        /* ==========================================
           NORMAL EXAM ATTEMPTS
        ========================================== */

        SELECT

            u.id AS user_id,

            u.name,

            u.email,

            CASE

                WHEN a.total_questions > 0

                THEN

                    (

                        a.score

                        /

                        a.total_questions

                    )

                    *

                    100

                ELSE 0

            END AS percentage

        FROM attempts a

        INNER JOIN users u

            ON a.user_id = u.id

        WHERE a.submitted_at IS NOT NULL


        UNION ALL


        /* ==========================================
           AI EXAM ATTEMPTS
        ========================================== */

        SELECT

            u.id AS user_id,

            u.name,

            u.email,

            aa.percentage AS percentage

        FROM ai_attempts aa

        INNER JOIN users u

            ON aa.student_id = u.id

        WHERE aa.submitted_at IS NOT NULL

    ) AS combined_attempts


    GROUP BY

        user_id,

        name,

        email


    ORDER BY

        best_percentage DESC,

        avg_percentage DESC


    LIMIT 10

";


$result = $conn->query($sql);


if (!$result) {

    die(

        "Database Error: "

        .

        htmlspecialchars(

            $conn->error

        )

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

        Leaderboard - SmartExam

    </title>


    <link

        rel="stylesheet"

        href="../assets/css/style.css"

    >


    <style>


        .leaderboard-list {

            display: flex;

            flex-direction: column;

            gap: 15px;

            margin-top: 25px;

        }


        .leaderboard-row {

            background: var(--glass-bg);

            border: 1px solid var(--glass-border);

            border-radius: var(--border-radius-md);

            padding: 16px 25px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            transition: var(--transition);

        }


        .leaderboard-row:hover {

            border-color:

                var(--glass-border-hover);

            transform:

                translateX(4px);

            background:

                var(--glass-bg-hover);

        }


        .rank-badge {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

            font-weight: 800;

            background:

                rgba(

                    255,

                    255,

                    255,

                    0.05

                );

            border:

                1.5px solid

                var(--glass-border);

        }


        .rank-badge.rank-1 {

            background:

                rgba(

                    255,

                    215,

                    0,

                    0.15

                );

            border-color:

                #ffd700;

            color:

                #ffd700;

            box-shadow:

                0 0 15px

                rgba(

                    255,

                    215,

                    0,

                    0.2

                );

        }


        .rank-badge.rank-2 {

            background:

                rgba(

                    192,

                    192,

                    192,

                    0.15

                );

            border-color:

                #c0c0c0;

            color:

                #c0c0c0;

            box-shadow:

                0 0 15px

                rgba(

                    192,

                    192,

                    192,

                    0.2

                );

        }


        .rank-badge.rank-3 {

            background:

                rgba(

                    205,

                    127,

                    50,

                    0.15

                );

            border-color:

                #cd7f32;

            color:

                #cd7f32;

            box-shadow:

                0 0 15px

                rgba(

                    205,

                    127,

                    50,

                    0.2

                );

        }


        .student-info {

            display: flex;

            align-items: center;

            gap: 20px;

            flex-grow: 1;

            margin-left: 20px;

        }


        .scores-box {

            display: flex;

            gap: 30px;

            text-align: right;

        }


        @media (max-width: 600px) {


            .leaderboard-row {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

            }


            .student-info {

                margin-left: 0;

            }


            .scores-box {

                text-align: left;

                width: 100%;

                justify-content:

                    space-between;

            }

        }


    </style>


</head>


<body>


<div class="dashboard-layout">


    <!-- ==========================================
         SIDEBAR
    =========================================== -->


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

                href="dashboard.php#exams"

                class="sidebar-link"

            >

                <span>▣</span>

                Available Exams

            </a>


            <a

                href="ai_exam_generator.php"

                class="sidebar-link"

            >

                <span>🤖</span>

                AI Exam Generator

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

                class="sidebar-link active"

            >

                <span>🏆</span>

                Leaderboard

            </a>


        </nav>


        <div class="sidebar-footer">


            <a

                href="../logout.php"

                class="btn btn-danger"

                style="width: 100%;"

            >

                <span>🚪</span>

                Logout

            </a>


        </div>


    </aside>


    <!-- ==========================================
         MAIN CONTENT
    =========================================== -->


    <main class="main-content">


        <div

            style="

            margin-bottom: 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            flex-wrap: wrap;

            gap: 15px;

            "

        >


            <div>


                <h1

                    style="

                    font-size: 28px;

                    margin-bottom: 5px;

                    "

                >

                    Global Leaderboard

                </h1>


                <p

                    style="

                    color: var(--text-muted);

                    font-size: 14px;

                    margin-bottom: 0;

                    "

                >

                    Top students ranked by performance across

                    Normal and AI Exams.

                </p>


            </div>


            <a

                href="dashboard.php"

                class="btn btn-secondary"

            >

                Back to Dashboard

            </a>


        </div>


        <?php if (

            $result->num_rows > 0

        ): ?>


            <div

                class="leaderboard-list"

            >


                <?php

                $rank = 1;

                ?>


                <?php while (

                    $row =

                    $result->fetch_assoc()

                ): ?>


                    <?php


                    $badge_class = "";


                    $rank_label =

                        $rank;


                    if (

                        $rank === 1

                    ) {


                        $badge_class =

                            "rank-1";


                        $rank_label =

                            "🥇";


                    }


                    elseif (

                        $rank === 2

                    ) {


                        $badge_class =

                            "rank-2";


                        $rank_label =

                            "🥈";


                    }


                    elseif (

                        $rank === 3

                    ) {


                        $badge_class =

                            "rank-3";


                        $rank_label =

                            "🥉";


                    }


                    ?>


                    <div

                        class="leaderboard-row"

                    >


                        <!-- RANK -->


                        <div

                            class="rank-badge

                            <?php echo

                                $badge_class;

                            ?>"

                        >


                            <?php echo

                                $rank_label;

                            ?>


                        </div>


                        <!-- STUDENT INFO -->


                        <div

                            class="student-info"

                        >


                            <div>


                                <h3

                                    style="

                                    font-size: 16px;

                                    margin-bottom: 3px;

                                    color: #ffffff;

                                    "

                                >


                                    <?php echo

                                        htmlspecialchars(

                                            $row["name"]

                                        );

                                    ?>


                                </h3>


                                <span

                                    style="

                                    font-size: 12px;

                                    color:

                                    var(--text-muted);

                                    "

                                >


                                    <?php echo

                                        htmlspecialchars(

                                            $row["email"]

                                        );

                                    ?>


                                </span>


                            </div>


                        </div>


                        <!-- SCORES -->


                        <div

                            class="scores-box"

                        >


                            <div>


                                <p

                                    style="

                                    font-size: 11px;

                                    color:

                                    var(--text-muted);

                                    text-transform:

                                    uppercase;

                                    "

                                >

                                    Total Attempts

                                </p>


                                <strong

                                    style="

                                    color: #ffffff;

                                    "

                                >


                                    <?php echo

                                        intval(

                                            $row[

                                                "total_attempts"

                                            ]

                                        );

                                    ?>


                                </strong>


                            </div>


                            <div>


                                <p

                                    style="

                                    font-size: 11px;

                                    color:

                                    var(--text-muted);

                                    text-transform:

                                    uppercase;

                                    "

                                >

                                    Average

                                </p>


                                <strong

                                    style="

                                    color:

                                    var(--accent-cyan);

                                    "

                                >


                                    <?php echo

                                        round(

                                            $row[

                                                "avg_percentage"

                                            ],

                                            1

                                        );

                                    ?>%


                                </strong>


                            </div>


                            <div>


                                <p

                                    style="

                                    font-size: 11px;

                                    color:

                                    var(--text-muted);

                                    text-transform:

                                    uppercase;

                                    "

                                >

                                    Best Accuracy

                                </p>


                                <strong

                                    style="

                                    color:

                                    var(--success);

                                    font-size:

                                    16px;

                                    "

                                >


                                    <?php echo

                                        round(

                                            $row[

                                                "best_percentage"

                                            ],

                                            1

                                        );

                                    ?>%


                                </strong>


                            </div>


                        </div>


                    </div>


                    <?php

                    $rank++;

                    ?>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <div

                class="card"

                style="

                text-align: center;

                padding: 50px;

                border-color:

                rgba(

                    255,

                    255,

                    255,

                    0.05

                );

                "

            >


                <div

                    style="

                    font-size: 48px;

                    margin-bottom: 20px;

                    "

                >

                    🏆

                </div>


                <h3

                    style="

                    margin-bottom: 10px;

                    "

                >

                    Leaderboard is Empty

                </h3>


                <p

                    style="

                    color: var(--text-muted);

                    margin-bottom: 0;

                    "

                >

                    Once students attempt Normal or AI Exams

                    and obtain grades, the top ranks will be displayed here!

                </p>


            </div>


        <?php endif; ?>


    </main>


</div>


</body>


</html>