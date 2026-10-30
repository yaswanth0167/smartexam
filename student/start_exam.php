<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();


// ============================================================
// VALIDATE EXAM ID
// ============================================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = intval($_GET["id"]);


// ============================================================
// FETCH EXAM
// ============================================================

$sql = "
    SELECT
        id,
        title,
        description,
        duration,
        total_marks
    FROM exams
    WHERE id = ?
    AND status = 'published'
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $exam_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: dashboard.php");
    exit();
}

$exam = $result->fetch_assoc();

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
        <?php echo htmlspecialchars($exam["title"]); ?>
        - SmartExam
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .instructions-list {
            margin: 20px 0;
            padding-left: 20px;
        }

        .instructions-list li {
            color: var(--text-muted);
            margin-bottom: 12px;
            font-size: 15px;
            line-height: 1.6;
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
                href="dashboard.php#exams"
                class="sidebar-link active"
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

        </nav>


        <div class="sidebar-footer">

            <a
                href="../logout.php"
                class="btn btn-danger"
                style="width: 100%;"
            >

                🚪 Logout

            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <div
            style="
                margin-bottom: 30px;
            "
        >

            <span
                class="badge badge-info"
            >

                Exam Session Initialization

            </span>


            <h1
                style="
                    font-size: 28px;
                    margin-top: 10px;
                    margin-bottom: 5px;
                "
            >

                <?php

                echo htmlspecialchars(
                    $exam["title"]
                );

                ?>

            </h1>


            <p
                style="
                    color: var(--text-muted);
                    font-size: 14px;
                "
            >

                Please review the exam details carefully before starting.

            </p>

        </div>


        <div
            class="grid-2"
            style="
                align-items: start;
                gap: 30px;
            "
        >


            <!-- INSTRUCTIONS -->

            <div class="card">


                <h3
                    style="
                        color: var(--accent-cyan);
                        margin-bottom: 15px;
                    "
                >

                    Instructions

                </h3>


                <p
                    style="
                        color: #ffffff;
                        line-height: 1.6;
                        margin-bottom: 20px;
                    "
                >

                    <?php

                    echo htmlspecialchars(

                        $exam["description"]
                        ?:
                        "No special instructions provided."

                    );

                    ?>

                </p>


                <ul class="instructions-list">

                    <li>
                        Ensure you have a stable internet connection.
                    </li>

                    <li>
                        The exam is strictly timed.
                    </li>

                    <li>
                        Do not refresh or close the exam window.
                    </li>

                    <li>
                        The exam will automatically submit when time expires.
                    </li>

                    <li>
                        You can review your answers before submitting.
                    </li>

                </ul>


                <div
                    style="
                        display: flex;
                        gap: 15px;
                        margin-top: 30px;
                    "
                >


                    <!-- IMPORTANT -->

                    <a
                        href="begin_exam.php?id=<?php echo intval($exam["id"]); ?>"
                        class="btn btn-primary"
                    >

                        🚀 Begin Exam

                    </a>


                    <a
                        href="dashboard.php"
                        class="btn btn-secondary"
                    >

                        Cancel

                    </a>


                </div>


            </div>


            <!-- PARAMETERS -->

            <div
                class="card"
                style="
                    border-color:
                    rgba(124, 58, 237, 0.2);
                "
            >

                <h3
                    style="
                        color: var(--accent-purple);
                        margin-bottom: 20px;
                    "
                >

                    Exam Parameters

                </h3>


                <div
                    style="
                        display: flex;
                        flex-direction: column;
                        gap: 15px;
                    "
                >


                    <div
                        style="
                            display: flex;
                            justify-content: space-between;
                        "
                    >

                        <span
                            style="
                                color: var(--text-muted);
                            "
                        >

                            Duration

                        </span>


                        <strong>

                            <?php

                            echo intval(
                                $exam["duration"]
                            );

                            ?>

                            Minutes

                        </strong>

                    </div>


                    <div
                        style="
                            display: flex;
                            justify-content: space-between;
                        "
                    >

                        <span
                            style="
                                color: var(--text-muted);
                            "
                        >

                            Total Marks

                        </span>


                        <strong>

                            <?php

                            echo intval(
                                $exam["total_marks"]
                            );

                            ?>

                            Marks

                        </strong>

                    </div>


                    <div
                        style="
                            display: flex;
                            justify-content: space-between;
                        "
                    >

                        <span
                            style="
                                color: var(--text-muted);
                            "
                        >

                            Format

                        </span>


                        <strong>

                            MCQ

                        </strong>

                    </div>


                </div>

            </div>


        </div>


    </main>


</div>


</body>

</html>