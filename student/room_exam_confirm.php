<?php

// ============================================================
// ROOM EXAM CONFIRM
// ============================================================

error_reporting(E_ALL);
ini_set("display_errors", 1);


// ============================================================
// REQUIRE FILES
// ============================================================

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();


// ============================================================
// STUDENT ID
// ============================================================

$student_id = $_SESSION["user_id"];


// ============================================================
// VALIDATE EXAM ID
// ============================================================

if (

    !isset($_GET["id"]) ||

    !is_numeric($_GET["id"])

) {

    header(

        "Location: dashboard.php"

    );

    exit();

}


$room_exam_id = intval(

    $_GET["id"]

);


// ============================================================
// FETCH ROOM EXAM
// ============================================================

$exam_sql = "

    SELECT

        re.id,

        re.room_id,

        re.title,

        re.description,

        re.duration,

        re.total_questions,

        r.room_name,

        r.room_code,

        r.creator_id,

        r.start_time,

        r.end_time,

        r.status

    FROM room_exams re

    INNER JOIN rooms r

        ON re.room_id = r.id

    WHERE re.id = ?

    AND r.creator_id = ?

";


$stmt = $conn->prepare(

    $exam_sql

);


if (!$stmt) {

    die(

        "Database Error: "

        . htmlspecialchars(

            $conn->error

        )

    );

}


$stmt->bind_param(

    "ii",

    $room_exam_id,

    $student_id

);


$stmt->execute();


$result = $stmt->get_result();


if (

    $result->num_rows !== 1

) {

    die(

        "Exam not found or access denied."

    );

}


$exam = $result->fetch_assoc();


$stmt->close();


// ============================================================
// CONFIRM EXAM
// ============================================================

if (

    $_SERVER["REQUEST_METHOD"] === "POST"

) {


    $conn->begin_transaction();


    try {


        // ================================================
        // UPDATE ROOM STATUS
        // ================================================

        $update_sql = "

            UPDATE rooms

            SET

                status = 'published'

            WHERE id = ?

            AND creator_id = ?

        ";


        $update_stmt = $conn->prepare(

            $update_sql

        );


        if (!$update_stmt) {

            throw new Exception(

                $conn->error

            );

        }


        $update_stmt->bind_param(

            "ii",

            $exam["room_id"],

            $student_id

        );


        if (

            !$update_stmt->execute()

        ) {

            throw new Exception(

                $update_stmt->error

            );

        }


        $update_stmt->close();


        // ================================================
        // COMMIT
        // ================================================

        $conn->commit();


        // ================================================
        // REDIRECT
        // ================================================

        header(

            "Location: room_created_success.php?id="

            . $exam["room_id"]

        );

        exit();


    }

    catch (

        Exception $e

    ) {


        $conn->rollback();


        die(

            "Confirmation failed: "

            . htmlspecialchars(

                $e->getMessage()

            )

        );

    }

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

        Confirm Examination - SmartExam

    </title>


    <link

        rel="stylesheet"

        href="../assets/css/style.css"

    >


    <style>


        .confirm-container {

            max-width: 850px;

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


        .success-banner {

            padding: 25px;

            margin-bottom: 25px;

            border-radius: 16px;


            background:

                linear-gradient(

                    135deg,

                    rgba(

                        34,

                        197,

                        94,

                        0.18

                    ),

                    rgba(

                        16,

                        185,

                        129,

                        0.10

                    )

                );


            border:

                1px solid

                rgba(

                    34,

                    197,

                    94,

                    0.35

                );

        }


        .success-banner h2 {

            margin-bottom: 8px;

        }


        .success-banner p {

            color: var(--text-muted);

            margin: 0;

        }


        .exam-card {

            padding: 30px;

            margin-bottom: 25px;

        }


        .exam-title {

            font-size: 28px;

            margin-bottom: 10px;

        }


        .exam-description {

            color: var(--text-muted);

            margin-bottom: 25px;

        }


        .details-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(

                        200px,

                        1fr

                    )

                );

            gap: 15px;

        }


        .detail-box {

            padding: 18px;

            border-radius: 12px;


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


        .detail-label {

            display: block;

            font-size: 13px;

            color: var(--text-muted);

            margin-bottom: 6px;

        }


        .detail-value {

            font-size: 18px;

            font-weight: 700;

        }


        .room-code {

            display: inline-block;

            margin-top: 20px;

            padding: 12px 22px;

            border-radius: 10px;


            background:

                rgba(

                    0,

                    0,

                    0,

                    0.25

                );


            font-size: 24px;

            font-weight: 800;

            letter-spacing: 4px;

        }


        .warning-box {

            padding: 18px;

            margin-bottom: 25px;

            border-radius: 12px;


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


            color: #fcd34d;

        }


        .button-group {

            display: flex;

            gap: 15px;

            margin-top: 25px;

        }


        .button-group .btn {

            flex: 1;

            padding: 15px;

            text-align: center;

        }


        @media (

            max-width: 600px

        ) {


            .button-group {

                flex-direction: column;

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


    <!-- MAIN CONTENT -->


    <main class="main-content">


        <div class="confirm-container">


            <div class="page-header">


                <h1>

                    ✅ Confirm Examination

                </h1>


                <p>

                    Review your examination details before making the room available to students.

                </p>


            </div>


            <div class="success-banner">


                <h2>

                    🤖 AI Examination Generated Successfully

                </h2>


                <p>

                    Your new AI-generated examination is ready. Confirm it to activate the examination room.

                </p>


            </div>


            <div class="card exam-card">


                <h2 class="exam-title">


                    <?php

                    echo htmlspecialchars(

                        $exam["title"]

                    );

                    ?>


                </h2>


                <?php if (

                    !empty(

                        $exam["description"]

                    )

                ): ?>


                    <p class="exam-description">


                        <?php

                        echo nl2br(

                            htmlspecialchars(

                                $exam["description"]

                            )

                        );

                        ?>


                    </p>


                <?php endif; ?>


                <div class="details-grid">


                    <div class="detail-box">


                        <span class="detail-label">

                            🏠 Room Name

                        </span>


                        <span class="detail-value">


                            <?php

                            echo htmlspecialchars(

                                $exam["room_name"]

                            );

                            ?>


                        </span>


                    </div>


                    <div class="detail-box">


                        <span class="detail-label">

                            📝 Questions

                        </span>


                        <span class="detail-value">


                            <?php

                            echo intval(

                                $exam["total_questions"]

                            );

                            ?>


                        </span>


                    </div>


                    <div class="detail-box">


                        <span class="detail-label">

                            ⏱️ Duration

                        </span>


                        <span class="detail-value">


                            <?php

                            echo intval(

                                $exam["duration"]

                            );

                            ?>

                            Minutes


                        </span>


                    </div>


                    <div class="detail-box">


                        <span class="detail-label">

                            🕒 Scheduled Start

                        </span>


                        <span class="detail-value">


                            <?php

                            echo date(

                                "d M Y, h:i A",

                                strtotime(

                                    $exam["start_time"]

                                )

                            );

                            ?>


                        </span>


                    </div>


                    <div class="detail-box">


                        <span class="detail-label">

                            🔒 Scheduled End

                        </span>


                        <span class="detail-value">


                            <?php

                            echo date(

                                "d M Y, h:i A",

                                strtotime(

                                    $exam["end_time"]

                                )

                            );

                            ?>


                        </span>


                    </div>


                </div>


                <div>


                    <span class="detail-label">


                        🔑 Room Code

                    </span>


                    <div class="room-code">


                        <?php

                        echo htmlspecialchars(

                            $exam["room_code"]

                        );

                        ?>


                    </div>


                </div>


            </div>


            <div class="warning-box">


                ⚠️

                After confirmation, students can join this room using the room code only during the scheduled time.


            </div>


            <form

                method="POST"

            >


                <div class="button-group">


                    <a

                        href="room_exam_preview.php?id=<?php echo $room_exam_id; ?>"

                        class="btn btn-secondary"

                    >

                        ← Back to Preview

                    </a>


                    <button

                        type="submit"

                        class="btn btn-primary"

                    >

                        🚀 Confirm & Publish Room

                    </button>


                </div>


            </form>


        </div>


    </main>


</div>


</body>


</html>