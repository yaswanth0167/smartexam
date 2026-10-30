<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = $_SESSION["user_id"];


// ==================================================
// FETCH NORMAL + AI EXAM ATTEMPTS
// ==================================================

$sql = "

    SELECT

        attempts.id AS attempt_id,

        exams.title AS exam_name,

        attempts.score,

        attempts.total_questions AS total_marks,

        CASE

            WHEN attempts.total_questions > 0

            THEN (

                attempts.score

                /

                attempts.total_questions

            ) * 100

            ELSE 0

        END AS percentage,

        attempts.submitted_at,

        'normal' AS exam_type


    FROM attempts


    INNER JOIN exams

        ON attempts.exam_id = exams.id


    WHERE attempts.user_id = ?


    UNION ALL


    SELECT

        ai_attempts.id AS attempt_id,

        CONCAT(

            '🤖 AI: ',

            ai_exams.subject,

            ' - ',

            ai_exams.topic

        ) AS exam_name,

        ai_attempts.score,

        ai_attempts.total_marks,

        ai_attempts.percentage,

        ai_attempts.submitted_at,

        'ai' AS exam_type


    FROM ai_attempts


    INNER JOIN ai_exams

        ON ai_attempts.ai_exam_id = ai_exams.id


    WHERE ai_attempts.student_id = ?


    ORDER BY submitted_at DESC

";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(

        "Database Error: "

        .

        htmlspecialchars(

            $conn->error

        )

    );

}


$stmt->bind_param(

    "ii",

    $student_id,

    $student_id

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

        Attempt History - SmartExam

    </title>


    <link

        rel="stylesheet"

        href="../assets/css/style.css"

    >


    <style>


        .exam-type-badge {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

            margin-top: 5px;

        }


        .normal-badge {

            background:

                rgba(

                    99,

                    102,

                    241,

                    0.15

                );

            color:

                #a5b4fc;

        }


        .ai-badge {

            background:

                rgba(

                    168,

                    85,

                    247,

                    0.15

                );

            color:

                #d8b4fe;

        }


        .ai-row {

            background:

                rgba(

                    168,

                    85,

                    247,

                    0.03

                );

        }


        @media (max-width: 800px) {


            .table-container {

                overflow-x: auto;

            }


            .table {

                min-width: 750px;

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

                class="sidebar-link active"

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

                    My Attempt History

                </h1>


                <p

                    style="

                    color: var(--text-muted);

                    font-size: 14px;

                    margin-bottom: 0;

                    "

                >

                    Review your Normal and AI Exam performance.

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

            $result

            &&

            $result->num_rows > 0

        ): ?>


            <div

                class="table-container"

            >


                <table

                    class="table"

                >


                    <thead>


                        <tr>


                            <th>

                                Exam Name

                            </th>


                            <th>

                                Type

                            </th>


                            <th>

                                Score

                            </th>


                            <th>

                                Percentage

                            </th>


                            <th>

                                Date & Time

                            </th>


                            <th>

                                Action

                            </th>


                        </tr>


                    </thead>


                    <tbody>


                        <?php while (

                            $attempt =

                            $result->fetch_assoc()

                        ): ?>


                            <?php


                            $percentage =

                                round(

                                    floatval(

                                        $attempt[

                                            "percentage"

                                        ]

                                    ),

                                    2

                                );


                            $is_ai =

                                $attempt[

                                    "exam_type"

                                ]

                                ===

                                "ai";


                            ?>


                            <tr

                                class="<?php echo

                                    $is_ai

                                        ? "ai-row"

                                        : "";

                                ?>"

                            >


                                <!-- EXAM NAME -->


                                <td

                                    style="

                                    font-weight: 600;

                                    color: #ffffff;

                                    "

                                >


                                    <?php echo

                                        htmlspecialchars(

                                            $attempt[

                                                "exam_name"

                                            ]

                                        );

                                    ?>


                                </td>


                                <!-- TYPE -->


                                <td>


                                    <?php if (

                                        $is_ai

                                    ): ?>


                                        <span

                                            class="

                                            exam-type-badge

                                            ai-badge

                                            "

                                        >

                                            🤖 AI Exam

                                        </span>


                                    <?php else: ?>


                                        <span

                                            class="

                                            exam-type-badge

                                            normal-badge

                                            "

                                        >

                                            📝 Normal Exam

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- SCORE -->


                                <td>


                                    <span

                                        style="

                                        color:

                                        var(--accent-cyan);

                                        font-weight: bold;

                                        "

                                    >


                                        <?php echo

                                            intval(

                                                $attempt[

                                                    "score"

                                                ]

                                            );

                                        ?>


                                    </span>


                                    /


                                    <?php echo

                                        intval(

                                            $attempt[

                                                "total_marks"

                                            ]

                                        );

                                    ?>


                                </td>


                                <!-- PERCENTAGE -->


                                <td>


                                    <?php if (

                                        $percentage >= 70

                                    ): ?>


                                        <span

                                            class="

                                            badge

                                            badge-success

                                            "

                                        >

                                            <?php echo

                                                $percentage;

                                            ?>%

                                        </span>


                                    <?php elseif (

                                        $percentage >= 40

                                    ): ?>


                                        <span

                                            class="

                                            badge

                                            badge-warning

                                            "

                                        >

                                            <?php echo

                                                $percentage;

                                            ?>%

                                        </span>


                                    <?php else: ?>


                                        <span

                                            class="

                                            badge

                                            badge-danger

                                            "

                                        >

                                            <?php echo

                                                $percentage;

                                            ?>%

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- DATE -->


                                <td>


                                    <?php echo

                                        date(

                                            "M d, Y - h:i A",

                                            strtotime(

                                                $attempt[

                                                    "submitted_at"

                                                ]

                                            )

                                        );

                                    ?>


                                </td>


                                <!-- ACTION -->


                                <td>


                                    <?php if (

                                        $is_ai

                                    ): ?>


                                        <a

                                            href="ai_result.php?id=<?php echo

                                                intval(

                                                    $attempt[

                                                        "attempt_id"

                                                    ]

                                                );

                                            ?>"

                                            class="btn btn-secondary"

                                            style="

                                            padding:

                                            6px 12px;

                                            font-size:

                                            13px;

                                            border-radius:

                                            var(

                                                --border-radius-sm

                                            );

                                            "

                                        >

                                            View AI Result

                                        </a>


                                    <?php else: ?>


                                        <a

                                            href="attempt_details.php?id=<?php echo

                                                intval(

                                                    $attempt[

                                                        "attempt_id"

                                                    ]

                                                );

                                            ?>"

                                            class="btn btn-secondary"

                                            style="

                                            padding:

                                            6px 12px;

                                            font-size:

                                            13px;

                                            border-radius:

                                            var(

                                                --border-radius-sm

                                            );

                                            "

                                        >

                                            View Details

                                        </a>


                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    </tbody>


                </table>


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

                    ⏱️

                </div>


                <h3

                    style="

                    margin-bottom: 10px;

                    "

                >

                    No Attempts Found

                </h3>


                <p

                    style="

                    color: var(--text-muted);

                    margin-bottom: 25px;

                    "

                >

                    You have not taken any Normal or AI Exams yet.

                </p>


                <a

                    href="dashboard.php#exams"

                    class="btn btn-primary"

                >

                    Browse Exams

                </a>


            </div>


        <?php endif; ?>


    </main>


</div>


</body>


</html>