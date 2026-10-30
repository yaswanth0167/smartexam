<?php

// ============================================================
// ROOM CREATED SUCCESS
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
// VALIDATE ROOM ID
// ============================================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {

    header("Location: dashboard.php");

    exit();

}


$room_id = intval($_GET["id"]);


// ============================================================
// FETCH ROOM AND EXAM DETAILS
// ============================================================

$sql = "

    SELECT

        r.id,
        r.room_code,
        r.room_name,
        r.start_time,
        r.end_time,
        r.status,
        r.exam_type,

        re.id AS exam_id,
        re.title AS exam_title,
        re.description AS exam_description,
        re.duration,
        re.total_questions

    FROM rooms r

    LEFT JOIN room_exams re

        ON r.id = re.room_id

    WHERE r.id = ?

    AND r.creator_id = ?

";


$stmt = $conn->prepare($sql);


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

    $room_id,

    $student_id

);


$stmt->execute();


$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    die(

        "Room not found or access denied."

    );

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

        Room Created Successfully - SmartExam

    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>


        /* =========================================
           MAIN CONTAINER
        ========================================== */

        .success-container {

            max-width: 950px;

            margin: 0 auto;

        }


        /* =========================================
           SUCCESS HEADER
        ========================================== */

        .success-header {

            text-align: center;

            margin-bottom: 35px;

        }


        .success-icon {

            font-size: 75px;

            margin-bottom: 15px;

        }


        .success-header h1 {

            font-size: 34px;

            margin-bottom: 10px;

        }


        .success-header p {

            color: var(--text-muted);

        }


        /* =========================================
           ROOM SUCCESS CARD
        ========================================== */

        .room-card {

            padding: 30px;

            margin-bottom: 25px;

            border-radius: 18px;


            background:

                linear-gradient(

                    135deg,

                    rgba(

                        34,

                        197,

                        94,

                        0.15

                    ),

                    rgba(

                        16,

                        185,

                        129,

                        0.08

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


        .room-title {

            font-size: 28px;

            margin-bottom: 8px;

        }


        .room-status {

            display: inline-block;

            margin-top: 10px;

            padding: 7px 14px;

            border-radius: 20px;


            background:

                rgba(

                    34,

                    197,

                    94,

                    0.15

                );


            color: #86efac;

            font-size: 13px;

            font-weight: 700;

            text-transform: uppercase;

        }


        /* =========================================
           ROOM CODE
        ========================================== */

        .code-section {

            text-align: center;

            padding: 30px;

            margin-top: 25px;

            border-radius: 16px;


            background:

                rgba(

                    0,

                    0,

                    0,

                    0.22

                );

        }


        .code-label {

            color: var(--text-muted);

            margin-bottom: 10px;

        }


        .room-code {

            font-size: 42px;

            font-weight: 900;

            letter-spacing: 8px;

            color: #ffffff;

            margin-bottom: 20px;

        }


        .copy-button {

            padding: 11px 22px;

            border: none;

            border-radius: 8px;

            cursor: pointer;

            background: var(--accent);

            color: white;

            font-weight: 700;

        }


        .copy-button:hover {

            opacity: 0.9;

        }


        /* =========================================
           EXAM DETAILS
        ========================================== */

        .details-card {

            padding: 28px;

            margin-bottom: 25px;

        }


        .details-card h2 {

            margin-bottom: 22px;

        }


        .details-grid {

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

        }


        .detail-box {

            padding: 18px;

            border-radius: 12px;


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

        }


        .detail-label {

            display: block;

            color: var(--text-muted);

            font-size: 13px;

            margin-bottom: 7px;

        }


        .detail-value {

            font-weight: 700;

            font-size: 16px;

        }


        /* =========================================
           EXAM INFORMATION MESSAGE
        ========================================== */

        .instruction-box {

            padding: 28px;

            margin-bottom: 25px;

            border-radius: 16px;


            background:

                linear-gradient(

                    135deg,

                    rgba(

                        99,

                        102,

                        241,

                        0.12

                    ),

                    rgba(

                        168,

                        85,

                        247,

                        0.08

                    )

                );


            border:

                1px solid

                rgba(

                    99,

                    102,

                    241,

                    0.30

                );

        }


        .instruction-box h2 {

            margin-bottom: 20px;

        }


        .exam-info-list {

            display: grid;

            gap: 12px;

            margin-bottom: 22px;

        }


        .exam-info-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 13px 16px;

            border-radius: 10px;


            background:

                rgba(

                    255,

                    255,

                    255,

                    0.04

                );

        }


        .exam-info-label {

            color: var(--text-muted);

        }


        .exam-info-value {

            font-weight: 700;

            text-align: right;

        }


        .share-message {

            padding: 20px;

            border-radius: 12px;


            background:

                rgba(

                    34,

                    197,

                    94,

                    0.10

                );


            border:

                1px solid

                rgba(

                    34,

                    197,

                    94,

                    0.25

                );

            line-height: 1.8;

        }


        .share-message strong {

            color: #86efac;

        }


        .good-luck {

            margin-top: 18px;

            font-size: 18px;

            font-weight: 800;

            text-align: center;

        }


        /* =========================================
           BUTTONS
        ========================================== */

        .button-group {

            display: flex;

            gap: 15px;

        }


        .button-group .btn {

            flex: 1;

            text-align: center;

            padding: 15px;

        }


        /* =========================================
           RESPONSIVE
        ========================================== */

        @media (

            max-width: 600px

        ) {


            .room-code {

                font-size: 30px;

                letter-spacing: 5px;

            }


            .button-group {

                flex-direction: column;

            }


            .exam-info-row {

                flex-direction: column;

                gap: 5px;

            }


            .exam-info-value {

                text-align: left;

            }

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


        <div class="success-container">


            <!-- =================================
                 SUCCESS HEADER
            ================================== -->

            <div class="success-header">


                <div class="success-icon">

                    🎉

                </div>


                <h1>

                    Exam Room Created Successfully!

                </h1>


                <p>

                    Your examination room is ready.

                    Share the details below with the participants.

                </p>


            </div>


            <!-- =================================
                 ROOM CARD
            ================================== -->

            <div class="room-card">


                <h2 class="room-title">


                    🏠

                    <?php

                    echo htmlspecialchars(

                        $room["room_name"]

                    );

                    ?>


                </h2>


                <span class="room-status">


                    <?php

                    echo htmlspecialchars(

                        $room["status"]

                    );

                    ?>


                </span>


                <!-- ROOM CODE -->

                <div class="code-section">


                    <div class="code-label">

                        🔑 Unique Room Code

                    </div>


                    <div

                        class="room-code"

                        id="roomCode"

                    >

                        <?php

                        echo htmlspecialchars(

                            $room["room_code"]

                        );

                        ?>


                    </div>


                    <button

                        type="button"

                        class="copy-button"

                        onclick="copyRoomCode()"

                    >

                        📋 Copy Room Code

                    </button>


                </div>


            </div>


            <!-- =================================
                 EXAM DETAILS
            ================================== -->

            <div class="card details-card">


                <h2>

                    📝 Examination Details

                </h2>


                <div class="details-grid">


                    <!-- SUBJECT / EXAM NAME -->

                    <div class="detail-box">


                        <span class="detail-label">

                            📚 Subject / Exam Name

                        </span>


                        <span class="detail-value">


                            <?php

                            echo htmlspecialchars(

                                $room["exam_title"]

                            );

                            ?>


                        </span>


                    </div>


                    <!-- EXAM TYPE -->

                    <div class="detail-box">


                        <span class="detail-label">

                            🤖 Exam Type

                        </span>


                        <span class="detail-value">

                            AI Generated

                        </span>


                    </div>


                    <!-- QUESTIONS -->

                    <div class="detail-box">


                        <span class="detail-label">

                            ❓ Total Questions

                        </span>


                        <span class="detail-value">


                            <?php

                            echo intval(

                                $room["total_questions"]

                            );

                            ?>


                        </span>


                    </div>


                    <!-- DURATION -->

                    <div class="detail-box">


                        <span class="detail-label">

                            ⏱️ Duration

                        </span>


                        <span class="detail-value">


                            <?php

                            echo intval(

                                $room["duration"]

                            );

                            ?>

                            Minutes

                        </span>


                    </div>


                    <!-- START TIME -->

                    <div class="detail-box">


                        <span class="detail-label">

                            🟢 Starts

                        </span>


                        <span class="detail-value">


                            <?php

                            echo date(

                                "d M Y, h:i A",

                                strtotime(

                                    $room["start_time"]

                                )

                            );

                            ?>


                        </span>


                    </div>


                    <!-- END TIME -->

                    <div class="detail-box">


                        <span class="detail-label">

                            🔴 Closes

                        </span>


                        <span class="detail-value">


                            <?php

                            echo date(

                                "d M Y, h:i A",

                                strtotime(

                                    $room["end_time"]

                                )

                            );

                            ?>


                        </span>


                    </div>


                </div>


            </div>


            <!-- =================================
                 SHAREABLE EXAM INFORMATION
            ================================== -->

            <div class="instruction-box">


                <h2>

                    📢 Examination Information

                </h2>


                <div class="exam-info-list">


                    <div class="exam-info-row">


                        <span class="exam-info-label">

                            📚 Subject

                        </span>


                        <span class="exam-info-value">


                            <?php

                            echo htmlspecialchars(

                                $room["exam_title"]

                            );

                            ?>


                        </span>


                    </div>


                    <div class="exam-info-row">


                        <span class="exam-info-label">

                            🕒 Examination Timing

                        </span>


                        <span class="exam-info-value">


                            <?php

                            echo date(

                                "d M Y, h:i A",

                                strtotime(

                                    $room["start_time"]

                                )

                            );

                            ?>


                            -

                            <?php

                            echo date(

                                "d M Y, h:i A",

                                strtotime(

                                    $room["end_time"]

                                )

                            );

                            ?>


                        </span>


                    </div>


                    <div class="exam-info-row">


                        <span class="exam-info-label">

                            🔑 Room Code

                        </span>


                        <span class="exam-info-value">


                            <?php

                            echo htmlspecialchars(

                                $room["room_code"]

                            );

                            ?>


                        </span>


                    </div>


                </div>


                <div class="share-message">


                    📌 <strong>Important Instructions:</strong>


                    <br><br>


                    Share the above

                    <strong>Room Code</strong>

                    with the students who need to attend this examination.


                    <br><br>


                    Students must click on

                    <strong>Join Room</strong>

                    and enter the correct Room Code to access the examination.


                    <br><br>


                    The examination room will be available only between the scheduled

                    <strong>Start Time</strong>

                    and

                    <strong>End Time</strong>.


                    <br><br>


                    Once the End Time is reached, the room will automatically close and students will no longer be able to start the examination.


                    <div class="good-luck">

                        🎓 All the best for your examination! ✨

                    </div>


                </div>


            </div>


            <!-- =================================
                 BUTTONS
            ================================== -->

            <div class="button-group">


                <a

                    href="room_results.php?room_id=<?php echo $room_id; ?>"

                    class="btn btn-primary"

                >

                    📊 View Results

                </a>


                <a

                    href="dashboard.php"

                    class="btn btn-secondary"

                >

                    🏠 Go to Dashboard

                </a>


            </div>


        </div>


    </main>


</div>


<!-- =========================================
     COPY ROOM CODE
========================================== -->

<script>


function copyRoomCode() {


    const code = document

        .getElementById(

            "roomCode"

        )

        .innerText;


    navigator.clipboard

        .writeText(code)

        .then(

            function() {


                alert(

                    "Room code copied successfully: "

                    + code

                );


            }

        )

        .catch(

            function() {


                alert(

                    "Unable to copy room code."

                );


            }

        );

}


</script>


</body>

</html>