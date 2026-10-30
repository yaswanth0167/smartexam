<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = $_SESSION["user_id"];

$message = "";
$message_type = "";


// ============================================================
// JOIN ROOM
// ============================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $room_code = strtoupper(
        trim($_POST["room_code"] ?? "")
    );


    // ========================================================
    // VALIDATE ROOM CODE
    // ========================================================

    if (empty($room_code)) {

        $message = "Please enter a room code.";
        $message_type = "error";

    } else {


        // ====================================================
        // FETCH ROOM
        // ====================================================

        $room_sql = "

            SELECT
                id,
                creator_id,
                room_code,
                room_name,
                start_time,
                end_time,
                status,
                exam_type,
                ai_exam_id

            FROM rooms

            WHERE room_code = ?

            LIMIT 1

        ";


        $stmt = $conn->prepare($room_sql);


        if (!$stmt) {

            die(
                "Database Error: "
                . htmlspecialchars($conn->error)
            );

        }


        $stmt->bind_param(
            "s",
            $room_code
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        if ($result->num_rows !== 1) {

            $message =
                "Invalid room code. Please check the code and try again.";

            $message_type =
                "error";

            $stmt->close();

        } else {


            $room =
                $result->fetch_assoc();


            $stmt->close();


            // =================================================
            // CURRENT TIME
            // =================================================

            date_default_timezone_set(
                "Asia/Kolkata"
            );


            $current_time =
                time();


            $start_time =
                strtotime(
                    $room["start_time"]
                );


            $end_time =
                strtotime(
                    $room["end_time"]
                );


            // =================================================
            // ROOM NOT STARTED
            // =================================================

            if (

                $current_time
                <
                $start_time

            ) {


                $formatted_start =
                    date(
                        "d M Y, h:i A",
                        $start_time
                    );


                $message =

                    "This room is not open yet.

                    The exam will start on:
                    "
                    . $formatted_start;


                $message_type =
                    "error";

            }


            // =================================================
            // ROOM EXPIRED
            // =================================================

            elseif (

                $current_time
                >
                $end_time

            ) {


                $formatted_end =
                    date(
                        "d M Y, h:i A",
                        $end_time
                    );


                $message =

                    "This exam room is closed.

                    The room closed on:
                    "
                    . $formatted_end;


                $message_type =
                    "error";

            }


            // =================================================
            // ROOM IS OPEN
            // =================================================

            else {


                // =================================================
                // CHECK EXAM EXISTS
                // =================================================

                $exam_sql = "

                    SELECT
                        id,
                        title,
                        description,
                        duration,
                        total_questions

                    FROM room_exams

                    WHERE room_id = ?

                    LIMIT 1

                ";


                $exam_stmt =
                    $conn->prepare(
                        $exam_sql
                    );


                if (!$exam_stmt) {

                    die(
                        "Database Error: "
                        . htmlspecialchars(
                            $conn->error
                        )
                    );

                }


                $exam_stmt->bind_param(
                    "i",
                    $room["id"]
                );


                $exam_stmt->execute();


                $exam_result =
                    $exam_stmt->get_result();


                if (

                    $exam_result->num_rows
                    !==
                    1

                ) {

                    $message =
                        "This room does not have an examination yet.";

                    $message_type =
                        "error";

                    $exam_stmt->close();

                } else {


                    $exam =
                        $exam_result->fetch_assoc();


                    $exam_stmt->close();


                    // =================================================
                    // CHECK WHETHER CREATOR IS JOINING OWN ROOM
                    // =================================================

                    if (

                        $room["creator_id"]
                        ==
                        $student_id

                    ) {

                        header(

                            "Location: room_exam.php?room_id="
                            . $room["id"]

                        );

                        exit();

                    }


                    // =================================================
                    // CHECK DUPLICATE PARTICIPATION
                    // =================================================

                    /*
                        If you have a room_participants table,
                        participation can be saved here.

                        For now, directly open the room exam.
                    */


                    header(

                        "Location: room_exam.php?room_id="
                        . $room["id"]

                    );

                    exit();

                }

            }

        }

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

        Join Exam Room - SmartExam

    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .join-container {

            max-width: 700px;

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


        .join-card {

            padding: 40px;

            text-align: center;

        }


        .join-icon {

            font-size: 65px;

            margin-bottom: 20px;

        }


        .join-card h2 {

            margin-bottom: 10px;

        }


        .join-card p {

            color: var(--text-muted);

            margin-bottom: 30px;

            line-height: 1.6;

        }


        .room-code-input {

            width: 100%;

            padding: 18px;

            text-align: center;

            font-size: 24px;

            font-weight: 800;

            letter-spacing: 6px;

            text-transform: uppercase;

        }


        .join-button {

            width: 100%;

            margin-top: 20px;

            padding: 16px;

            font-size: 16px;

        }


        .message {

            padding: 15px 18px;

            margin-bottom: 20px;

            border-radius: 10px;

            white-space: pre-line;

            text-align: left;

        }


        .message.error {

            background:

                rgba(

                    239,

                    68,

                    68,

                    0.12

                );

            border:

                1px solid

                rgba(

                    239,

                    68,

                    68,

                    0.35

                );

            color: #fca5a5;

        }


        .instructions {

            margin-top: 25px;

            padding: 20px;

            text-align: left;

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


        .instructions h3 {

            margin-bottom: 12px;

        }


        .instructions ul {

            padding-left: 20px;

            color: var(--text-muted);

            line-height: 1.8;

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
                href="create_room.php"
                class="sidebar-link"
            >

                <span>🏠</span>

                Create Room

            </a>


            <a
                href="join_room.php"
                class="sidebar-link active"
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


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <div class="join-container">


            <div class="page-header">


                <h1>

                    🔑 Join Exam Room

                </h1>


                <p>

                    Enter the unique room code provided by the exam creator.

                </p>


            </div>


            <?php if (!empty($message)): ?>


                <div
                    class="message <?php echo $message_type; ?>"
                >

                    <?php

                    echo htmlspecialchars(
                        $message
                    );

                    ?>

                </div>


            <?php endif; ?>


            <div class="card join-card">


                <div class="join-icon">

                    🔐

                </div>


                <h2>

                    Enter Room Code

                </h2>


                <p>

                    Use the unique code shared by the exam creator to access the scheduled examination.

                </p>


                <form
                    method="POST"
                >


                    <input

                        type="text"

                        name="room_code"

                        class="form-control room-code-input"

                        placeholder="EXAMPLE: ABC123"

                        maxlength="6"

                        autocomplete="off"

                        required

                    >


                    <button

                        type="submit"

                        class="btn btn-primary join-button"

                    >

                        🚀 Join Examination

                    </button>


                </form>


                <div class="instructions">


                    <h3>

                        📌 How it works

                    </h3>


                    <ul>

                        <li>

                            Enter the room code provided by the exam creator.

                        </li>


                        <li>

                            The room will open only during its scheduled time.

                        </li>


                        <li>

                            Once the closing time is reached, the room will automatically become unavailable.

                        </li>


                        <li>

                            After joining, you can start and complete the examination.

                        </li>


                    </ul>


                </div>


            </div>


        </div>


    </main>


</div>


<script>


    const roomCodeInput =
        document.querySelector(
            ".room-code-input"
        );


    roomCodeInput.addEventListener(

        "input",

        function () {

            this.value =
                this.value
                .toUpperCase()
                .replace(
                    /[^A-Z0-9]/g,
                    ""
                );

        }

    );

</script>


</body>

</html>