<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = $_SESSION["user_id"];


// ==========================================
// VALIDATE ROOM ID
// ==========================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: create_room.php");
    exit();
}

$room_id = intval($_GET["id"]);


// ==========================================
// FETCH ROOM
// ==========================================

$room_sql = "

    SELECT
        id,
        creator_id,
        room_code,
        room_name,
        start_time,
        end_time,
        status

    FROM rooms

    WHERE id = ?
    AND creator_id = ?

";

$stmt = $conn->prepare($room_sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $room_id,
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    die("Room not found or access denied.");

}

$room = $result->fetch_assoc();

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

        Choose Exam Type - SmartExam

    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .room-container {

            max-width: 1000px;

            margin: 0 auto;

        }


        .page-header {

            margin-bottom: 30px;

        }


        .page-header h1 {

            font-size: 32px;

            margin-bottom: 10px;

        }


        .page-header p {

            color: var(--text-muted);

        }


        .room-info {

            padding: 22px;

            margin-bottom: 30px;

            border-radius: 14px;

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
                    0.3
                );

        }


        .room-info h2 {

            margin-bottom: 8px;

        }


        .room-code {

            display: inline-block;

            margin-top: 15px;

            padding: 12px 22px;

            border-radius: 10px;

            background:

                rgba(
                    0,
                    0,
                    0,
                    0.25
                );

            border:

                1px solid

                rgba(
                    255,
                    255,
                    255,
                    0.15
                );

            font-size: 24px;

            font-weight: 800;

            letter-spacing: 4px;

            color: #ffffff;

        }


        .exam-options {

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

        }


        .exam-option {

            padding: 35px 30px;

            text-align: center;

            border-radius: 16px;

            background:

                rgba(
                    255,
                    255,
                    255,
                    0.04
                );

            border:

                1px solid

                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

            transition:

                transform 0.25s ease,

                border-color 0.25s ease,

                background 0.25s ease;

        }


        .exam-option:hover {

            transform:

                translateY(
                    -6px
                );

            border-color:

                var(--accent);

            background:

                rgba(
                    255,
                    255,
                    255,
                    0.07
                );

        }


        .option-icon {

            font-size: 58px;

            margin-bottom: 20px;

        }


        .exam-option h2 {

            margin-bottom: 12px;

        }


        .exam-option p {

            color: var(--text-muted);

            line-height: 1.6;

            min-height: 70px;

        }


        .exam-option .btn {

            width: 100%;

            margin-top: 20px;

        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =====================================
         SIDEBAR
    ====================================== -->

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
                class="sidebar-link active"
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


    <!-- =====================================
         MAIN CONTENT
    ====================================== -->

    <main class="main-content">


        <div class="room-container">


            <!-- =================================
                 HEADER
            ================================== -->

            <div class="page-header">


                <h1>

                    📝 Choose Exam Creation Method

                </h1>


                <p>

                    Choose how you want to create the examination for this room.

                </p>


            </div>


            <!-- =================================
                 ROOM INFORMATION
            ================================== -->

            <div class="room-info">


                <h2>

                    🏠

                    <?php

                    echo htmlspecialchars(

                        $room["room_name"]

                    );

                    ?>

                </h2>


                <p>

                    Share this unique code with students after creating the exam.

                </p>


                <div class="room-code">

                    <?php

                    echo htmlspecialchars(

                        $room["room_code"]

                    );

                    ?>

                </div>


            </div>


            <!-- =================================
                 EXAM OPTIONS
            ================================== -->

            <div class="exam-options">


                <!-- =================================
                     MANUAL EXAM
                ================================== -->

                <div class="card exam-option">


                    <div class="option-icon">

                        📝

                    </div>


                    <h2>

                        Create Your Own Exam

                    </h2>


                    <p>

                        Manually create your own examination by adding questions, options, correct answers, marks, and explanations.

                    </p>


                    <a

                        href="room_create_exam.php?room_id=<?php echo $room_id; ?>"

                        class="btn btn-primary"

                    >

                        ✍️ Create Manual Exam

                    </a>


                </div>


                <!-- =================================
                     AI EXAM
                ================================== -->

                <div class="card exam-option">


                    <div class="option-icon">

                        🤖

                    </div>


                    <h2>

                        Generate Exam with AI

                    </h2>


                    <p>

                        Let AI generate a complete examination based on your subject, topic, difficulty, question count, and question type.

                    </p>


                    <a

                        href="room_ai_exam_generator.php?room_id=<?php echo $room_id; ?>"

                        class="btn btn-primary"

                    >

                        🚀 Generate with AI

                    </a>


                </div>


            </div>


        </div>


    </main>


</div>


</body>

</html>