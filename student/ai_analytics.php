<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = $_SESSION["user_id"];


// =====================================================
// 1. TOTAL AI EXAMS GENERATED
// =====================================================

$sql = "
    SELECT COUNT(*) AS total
    FROM ai_exams
    WHERE student_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$total_exams = intval(
    $stmt->get_result()->fetch_assoc()["total"]
);

$stmt->close();


// =====================================================
// 2. TOTAL AI ATTEMPTS
// =====================================================

$sql = "
    SELECT COUNT(*) AS total
    FROM ai_attempts
    WHERE student_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$total_attempts = intval(
    $stmt->get_result()->fetch_assoc()["total"]
);

$stmt->close();


// =====================================================
// 3. AVERAGE SCORE
// =====================================================

$sql = "
    SELECT AVG(percentage) AS average_score
    FROM ai_attempts
    WHERE student_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$average_score = $stmt
    ->get_result()
    ->fetch_assoc()["average_score"];

$average_score = round(
    floatval($average_score ?? 0),
    2
);

$stmt->close();


// =====================================================
// 4. BEST SCORE
// =====================================================

$sql = "
    SELECT MAX(percentage) AS best_score
    FROM ai_attempts
    WHERE student_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$best_score = $stmt
    ->get_result()
    ->fetch_assoc()["best_score"];

$best_score = round(
    floatval($best_score ?? 0),
    2
);

$stmt->close();


// =====================================================
// 5. ANSWER BREAKDOWN
// =====================================================

$sql = "
    SELECT
        COALESCE(SUM(correct_answers), 0) AS correct_answers,
        COALESCE(SUM(wrong_answers), 0) AS wrong_answers,
        COALESCE(SUM(unanswered), 0) AS unanswered
    FROM ai_attempts
    WHERE student_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$answer_stats = $stmt
    ->get_result()
    ->fetch_assoc();

$correct_answers = intval(
    $answer_stats["correct_answers"]
);

$wrong_answers = intval(
    $answer_stats["wrong_answers"]
);

$unanswered = intval(
    $answer_stats["unanswered"]
);

$stmt->close();


// =====================================================
// 6. SUBJECT-WISE PERFORMANCE
// =====================================================

$subject_data = [];

$sql = "
    SELECT
        e.subject,
        ROUND(AVG(a.percentage), 2) AS average_score,
        COUNT(a.id) AS attempts
    FROM ai_attempts a
    INNER JOIN ai_exams e
        ON a.ai_exam_id = e.id
    WHERE a.student_id = ?
    GROUP BY e.subject
    ORDER BY average_score DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $subject_data[] = $row;

}

$stmt->close();


// =====================================================
// 7. PERFORMANCE TREND
// =====================================================

$trend_data = [];

$sql = "
    SELECT
        DATE(submitted_at) AS exam_date,
        percentage
    FROM ai_attempts
    WHERE student_id = ?
    ORDER BY submitted_at ASC
    LIMIT 10
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $trend_data[] = $row;

}

$stmt->close();


// =====================================================
// 8. RECENT ATTEMPTS
// =====================================================

$recent_attempts = [];

$sql = "
    SELECT
        a.id,
        a.percentage,
        a.score,
        a.total_marks,
        a.submitted_at,
        e.subject,
        e.topic
    FROM ai_attempts a
    INNER JOIN ai_exams e
        ON a.ai_exam_id = e.id
    WHERE a.student_id = ?
    ORDER BY a.submitted_at DESC
    LIMIT 10
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $recent_attempts[] = $row;

}

$stmt->close();


// =====================================================
// 9. CHART DATA
// =====================================================

$subject_labels = [];
$subject_scores = [];

foreach ($subject_data as $subject) {

    $subject_labels[] =
        $subject["subject"];

    $subject_scores[] =
        floatval(
            $subject["average_score"]
        );

}


$trend_labels = [];
$trend_scores = [];

foreach ($trend_data as $trend) {

    $trend_labels[] =
        date(
            "d M",
            strtotime(
                $trend["exam_date"]
            )
        );

    $trend_scores[] =
        floatval(
            $trend["percentage"]
        );

}


// =====================================================
// 10. PERFORMANCE INSIGHT
// =====================================================

if ($total_attempts === 0) {

    $insight_title =
        "Start Your AI Learning Journey";

    $insight_text =
        "Generate your first AI exam and start tracking your performance.";

}

elseif ($average_score >= 80) {

    $insight_title =
        "Excellent Performance! 🏆";

    $insight_text =
        "Your average score is excellent. Keep practicing to maintain your consistency.";

}

elseif ($average_score >= 60) {

    $insight_title =
        "Good Progress! 🚀";

    $insight_text =
        "You are performing well. Focus on weak areas to improve your score further.";

}

else {

    $insight_title =
        "Keep Practicing! 💪";

    $insight_text =
        "Regular practice can significantly improve your performance. Review explanations after every exam.";

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
        AI Analytics - SmartExam
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js"
    ></script>


    <style>

        /* =================================================
           HEADER
        ================================================= */

        .analytics-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 30px;

            flex-wrap: wrap;

        }

        .analytics-header p {

            color: var(--text-muted);

            margin-top: 8px;

        }


        /* =================================================
           STAT CARDS
        ================================================= */

        .analytics-grid {

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

            margin-bottom: 30px;

        }

        .stat-card {

            position: relative;

            padding: 25px;

            overflow: hidden;

            transition: 0.3s;

        }

        .stat-card:hover {

            transform: translateY(-5px);

        }

        .stat-icon {

            font-size: 32px;

            margin-bottom: 15px;

        }

        .stat-value {

            font-size: 32px;

            font-weight: 800;

            margin-bottom: 8px;

        }

        .stat-label {

            color: var(--text-muted);

            font-size: 14px;

        }


        /* =================================================
           INSIGHT CARD
        ================================================= */

        .insight-card {

            padding: 25px;

            margin-bottom: 30px;

            border-left:

                4px solid
                var(--accent);

        }

        .insight-card h2 {

            margin-bottom: 10px;

        }

        .insight-card p {

            color: var(--text-muted);

            line-height: 1.6;

        }


        /* =================================================
           ANSWER BREAKDOWN
        ================================================= */

        .answer-grid {

            display: grid;

            grid-template-columns:

                repeat(
                    auto-fit,
                    minmax(
                        180px,
                        1fr
                    )
                );

            gap: 20px;

            margin-bottom: 30px;

        }

        .answer-card {

            padding: 25px;

            text-align: center;

        }

        .answer-value {

            font-size: 32px;

            font-weight: 800;

        }

        .answer-label {

            color: var(--text-muted);

            margin-top: 8px;

        }


        /* =================================================
           CHARTS
        ================================================= */

        .charts-grid {

            display: grid;

            grid-template-columns:

                repeat(
                    auto-fit,
                    minmax(
                        350px,
                        1fr
                    )
                );

            gap: 25px;

            margin-bottom: 30px;

        }

        .chart-card {

            padding: 25px;

            min-height: 390px;

        }

        .chart-card h2 {

            margin-bottom: 20px;

        }

        .chart-wrapper {

            position: relative;

            height: 290px;

        }


        /* =================================================
           TABLE
        ================================================= */

        .recent-table {

            width: 100%;

            border-collapse: collapse;

        }

        .recent-table th,

        .recent-table td {

            padding: 15px;

            text-align: left;

            border-bottom:

                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

        }

        .recent-table th {

            color: var(--text-muted);

            font-size: 13px;

        }

        .recent-table tr {

            transition: 0.2s;

        }

        .recent-table tr:hover {

            background:

                rgba(
                    255,
                    255,
                    255,
                    0.03
                );

        }


        /* =================================================
           SCORE BADGES
        ================================================= */

        .score-badge {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-weight: 700;

        }

        .good-score {

            background:

                rgba(
                    34,
                    197,
                    94,
                    0.15
                );

            color: #22c55e;

        }

        .average-score {

            background:

                rgba(
                    234,
                    179,
                    8,
                    0.15
                );

            color: #eab308;

        }

        .low-score {

            background:

                rgba(
                    239,
                    68,
                    68,
                    0.15
                );

            color: #ef4444;

        }


        /* =================================================
           EMPTY STATE
        ================================================= */

        .empty-state {

            text-align: center;

            padding: 50px 20px;

            color: var(--text-muted);

        }

        .empty-state-icon {

            font-size: 50px;

            margin-bottom: 15px;

        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 700px) {

            .charts-grid {

                grid-template-columns: 1fr;

            }

            .chart-card {

                min-height: 350px;

            }

            .recent-table th,

            .recent-table td {

                padding: 10px;

                font-size: 13px;

            }

        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =================================================
         SIDEBAR
    ================================================= -->

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
                href="ai_exam_generator.php"
                class="sidebar-link"
            >

                <span>🤖</span>

                AI Exam Generator

            </a>


            <a
                href="ai_analytics.php"
                class="sidebar-link active"
            >

                <span>📈</span>

                AI Analytics

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


    <!-- =================================================
         MAIN CONTENT
    ================================================= -->

    <main class="main-content">


        <!-- HEADER -->

        <div class="analytics-header">


            <div>

                <h1>

                    📈 AI Performance Analytics

                </h1>


                <p>

                    Track your AI-powered examination performance.

                </p>

            </div>


            <a
                href="ai_exam_generator.php"
                class="btn btn-primary"
            >

                🤖 Generate AI Exam

            </a>


        </div>


        <!-- =================================================
             STATISTICS
        ================================================= -->

        <div class="analytics-grid">


            <div class="card stat-card">


                <div class="stat-icon">

                    🤖

                </div>


                <div class="stat-value">

                    <?php echo $total_exams; ?>

                </div>


                <div class="stat-label">

                    AI Exams Generated

                </div>


            </div>


            <div class="card stat-card">


                <div class="stat-icon">

                    📝

                </div>


                <div class="stat-value">

                    <?php echo $total_attempts; ?>

                </div>


                <div class="stat-label">

                    Total Attempts

                </div>


            </div>


            <div class="card stat-card">


                <div class="stat-icon">

                    📊

                </div>


                <div class="stat-value">

                    <?php echo $average_score; ?>%

                </div>


                <div class="stat-label">

                    Average Score

                </div>


            </div>


            <div class="card stat-card">


                <div class="stat-icon">

                    🏆

                </div>


                <div class="stat-value">

                    <?php echo $best_score; ?>%

                </div>


                <div class="stat-label">

                    Best Score

                </div>


            </div>


        </div>


        <!-- =================================================
             AI INSIGHT
        ================================================= -->

        <div class="card insight-card">


            <h2>

                🧠 <?php echo $insight_title; ?>

            </h2>


            <p>

                <?php echo $insight_text; ?>

            </p>


        </div>


        <!-- =================================================
             ANSWER BREAKDOWN
        ================================================= -->

        <div class="answer-grid">


            <div class="card answer-card">


                <div class="answer-value">

                    <?php echo $correct_answers; ?>

                </div>


                <div class="answer-label">

                    ✅ Correct Answers

                </div>


            </div>


            <div class="card answer-card">


                <div class="answer-value">

                    <?php echo $wrong_answers; ?>

                </div>


                <div class="answer-label">

                    ❌ Wrong Answers

                </div>


            </div>


            <div class="card answer-card">


                <div class="answer-value">

                    <?php echo $unanswered; ?>

                </div>


                <div class="answer-label">

                    ⭕ Unanswered

                </div>


            </div>


        </div>


        <!-- =================================================
             CHARTS
        ================================================= -->

        <div class="charts-grid">


            <div class="card chart-card">


                <h2>

                    📈 Performance Trend

                </h2>


                <?php if (count($trend_data) > 0): ?>


                    <div class="chart-wrapper">


                        <canvas
                            id="trendChart"
                        ></canvas>


                    </div>


                <?php else: ?>


                    <div class="empty-state">


                        <div class="empty-state-icon">

                            📈

                        </div>


                        <p>

                            Complete an AI exam to see your performance trend.

                        </p>


                    </div>


                <?php endif; ?>


            </div>


            <div class="card chart-card">


                <h2>

                    📚 Subject-wise Performance

                </h2>


                <?php if (count($subject_data) > 0): ?>


                    <div class="chart-wrapper">


                        <canvas
                            id="subjectChart"
                        ></canvas>


                    </div>


                <?php else: ?>


                    <div class="empty-state">


                        <div class="empty-state-icon">

                            📚

                        </div>


                        <p>

                            Complete exams to view subject-wise performance.

                        </p>


                    </div>


                <?php endif; ?>


            </div>


        </div>


        <!-- =================================================
             RECENT ATTEMPTS
        ================================================= -->

        <div class="card">


            <h2>

                📝 Recent AI Attempts

            </h2>


            <?php if (count($recent_attempts) === 0): ?>


                <div class="empty-state">


                    <div class="empty-state-icon">

                        📝

                    </div>


                    <p>

                        No AI exam attempts yet.

                    </p>


                    <br>


                    <a
                        href="ai_exam_generator.php"
                        class="btn btn-primary"
                    >

                        Generate Your First AI Exam

                    </a>


                </div>


            <?php else: ?>


                <div
                    style="
                    overflow-x:auto;
                    margin-top:20px;
                    "
                >


                    <table
                        class="recent-table"
                    >


                        <thead>


                            <tr>


                                <th>

                                    Subject

                                </th>


                                <th>

                                    Topic

                                </th>


                                <th>

                                    Score

                                </th>


                                <th>

                                    Percentage

                                </th>


                                <th>

                                    Date

                                </th>


                                <th>

                                    Action

                                </th>


                            </tr>


                        </thead>


                        <tbody>


                        <?php foreach (

                            $recent_attempts
                            as
                            $attempt

                        ): ?>


                            <?php

                            $score = floatval(
                                $attempt["percentage"]
                            );


                            if ($score >= 75) {

                                $badge_class =
                                    "good-score";

                            }

                            elseif ($score >= 50) {

                                $badge_class =
                                    "average-score";

                            }

                            else {

                                $badge_class =
                                    "low-score";

                            }

                            ?>


                            <tr>


                                <td>

                                    <?php echo htmlspecialchars(

                                        $attempt["subject"]

                                    ); ?>

                                </td>


                                <td>

                                    <?php echo htmlspecialchars(

                                        $attempt["topic"]

                                    ); ?>

                                </td>


                                <td>

                                    <?php echo $attempt["score"]; ?>

                                    /

                                    <?php echo $attempt["total_marks"]; ?>

                                </td>


                                <td>


                                    <span
                                        class="
                                        score-badge
                                        <?php echo $badge_class; ?>
                                        "
                                    >

                                        <?php echo $score; ?>%

                                    </span>


                                </td>


                                <td>

                                    <?php echo date(

                                        "d M Y",

                                        strtotime(

                                            $attempt["submitted_at"]

                                        )

                                    ); ?>


                                </td>


                                <td>


                                    <a
                                        href="ai_result.php?id=<?php echo $attempt["id"]; ?>"
                                        class="btn btn-secondary"
                                    >

                                        View

                                    </a>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


<script>


// =====================================================
// PERFORMANCE TREND CHART
// =====================================================

<?php if (count($trend_data) > 0): ?>


const trendLabels =

    <?php echo json_encode($trend_labels); ?>;


const trendScores =

    <?php echo json_encode($trend_scores); ?>;


new Chart(

    document.getElementById(
        "trendChart"
    ),

    {

        type: "line",

        data: {

            labels: trendLabels,

            datasets: [

                {

                    label: "Score %",

                    data: trendScores,

                    tension: 0.4,

                    fill: true,

                    borderWidth: 3,

                    pointRadius: 5

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    display: true

                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    max: 100

                }

            }

        }

    }

);


<?php endif; ?>



// =====================================================
// SUBJECT PERFORMANCE CHART
// =====================================================

<?php if (count($subject_data) > 0): ?>


const subjectLabels =

    <?php echo json_encode($subject_labels); ?>;


const subjectScores =

    <?php echo json_encode($subject_scores); ?>;


new Chart(

    document.getElementById(
        "subjectChart"
    ),

    {

        type: "bar",

        data: {

            labels: subjectLabels,

            datasets: [

                {

                    label: "Average Score %",

                    data: subjectScores,

                    borderRadius: 8

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {

                    beginAtZero: true,

                    max: 100

                }

            }

        }

    }

);


<?php endif; ?>


</script>


</body>

</html>