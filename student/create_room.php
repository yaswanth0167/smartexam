<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

date_default_timezone_set("Asia/Kolkata");

$student_id = $_SESSION["user_id"];

$message = "";
$message_type = "";


// ==========================================
// CREATE ROOM
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $room_name  = trim($_POST["room_name"] ?? "");
    $start_date = $_POST["start_date"] ?? "";
    $start_time = $_POST["start_time"] ?? "";
    $end_date   = $_POST["end_date"] ?? "";
    $end_time   = $_POST["end_time"] ?? "";


    // ======================================
    // VALIDATION
    // ======================================

    if (
        empty($room_name) ||
        empty($start_date) ||
        empty($start_time) ||
        empty($end_date) ||
        empty($end_time)
    ) {

        $message = "Please fill all required fields.";
        $message_type = "error";

    } else {

        $start_datetime = $start_date . " " . $start_time . ":00";
        $end_datetime   = $end_date . " " . $end_time . ":00";


        // ==================================
        // DATE/TIME VALIDATION
        // ==================================

        $start_timestamp = strtotime($start_datetime);
        $end_timestamp   = strtotime($end_datetime);


        if (
            $start_timestamp === false ||
            $end_timestamp === false
        ) {

            $message = "Invalid date or time selected.";
            $message_type = "error";

        } elseif (
            $start_timestamp < time()
        ) {

            $message =
                "Start date and time cannot be in the past.";

            $message_type = "error";

        } elseif (
            $end_timestamp <= $start_timestamp
        ) {

            $message =
                "End date and time must be after start date and time.";

            $message_type = "error";

        } else {


            // ==================================
            // GENERATE UNIQUE ROOM CODE
            // ==================================

            function generateRoomCode($length = 6)
            {

                $characters =
                    "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";

                $code = "";

                for (
                    $i = 0;
                    $i < $length;
                    $i++
                ) {

                    $code .=
                        $characters[
                            random_int(
                                0,
                                strlen($characters) - 1
                            )
                        ];

                }

                return $code;

            }


            $room_code =
                generateRoomCode();


            // ==================================
            // CHECK DUPLICATE CODE
            // ==================================

            do {

                $check_sql = "

                    SELECT id

                    FROM rooms

                    WHERE room_code = ?

                ";

                $check_stmt =
                    $conn->prepare(
                        $check_sql
                    );

                $check_stmt->bind_param(
                    "s",
                    $room_code
                );

                $check_stmt->execute();

                $check_result =
                    $check_stmt->get_result();

                $check_stmt->close();


                if (
                    $check_result->num_rows > 0
                ) {

                    $room_code =
                        generateRoomCode();

                }

            } while (
                $check_result->num_rows > 0
            );


            // ==================================
            // INSERT ROOM
            // ==================================

            $room_sql = "

                INSERT INTO rooms

                (

                    creator_id,
                    room_code,
                    room_name,
                    start_time,
                    end_time,
                    status

                )

                VALUES (?, ?, ?, ?, ?, 'scheduled')

            ";


            $stmt =
                $conn->prepare(
                    $room_sql
                );


            if (!$stmt) {

                die(
                    "Database Error: "
                    . $conn->error
                );

            }


            $stmt->bind_param(

                "issss",

                $student_id,
                $room_code,
                $room_name,
                $start_datetime,
                $end_datetime

            );


            if (
                $stmt->execute()
            ) {

                $room_id =
                    $stmt->insert_id;


                $stmt->close();


                // ==================================
                // REDIRECT TO EXAM TYPE SELECTION
                // ==================================

                header(

                    "Location: room_exam_type.php?id="
                    . $room_id

                );

                exit();

            } else {

                $message =
                    "Failed to create room.";

                $message_type =
                    "error";

            }


            $stmt->close();

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
        Create Exam Room - SmartExam
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .room-container {

            max-width: 850px;

            margin: 0 auto;

        }


        .room-header {

            margin-bottom: 30px;

        }


        .room-header h1 {

            font-size: 32px;

            margin-bottom: 10px;

        }


        .room-header p {

            color: var(--text-muted);

        }


        .room-banner {

            padding: 25px;

            margin-bottom: 25px;

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


        .room-banner h2 {

            margin-bottom: 8px;

        }


        .room-banner p {

            color: var(--text-muted);

            margin: 0;

        }


        .form-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(
                        280px,
                        1fr
                    )

                );

            gap: 20px;

        }


        .full-width {

            grid-column: 1 / -1;

        }


        .form-control {

            width: 100%;

        }


        /* ==================================
           DATE AND TIME INPUTS
        ================================== */

        input[type="date"],
        input[type="time"] {

            cursor: pointer;

            color-scheme: dark;

        }


        input[type="date"]:focus,
        input[type="time"]:focus {

            outline: none;

            border-color: var(--accent);

            box-shadow:

                0 0 0 3px

                rgba(
                    99,
                    102,
                    241,
                    0.15
                );

        }


        .input-hint {

            display: block;

            margin-top: 7px;

            font-size: 12px;

            color: var(--text-muted);

        }


        .create-button {

            width: 100%;

            margin-top: 25px;

            padding: 15px;

            font-size: 16px;

        }


        .error-message {

            padding: 14px 18px;

            margin-bottom: 20px;

            border-radius: 10px;

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


        <div class="room-container">


            <!-- HEADER -->

            <div class="room-header">


                <h1>

                    🏠 Create Exam Room

                </h1>


                <p>

                    Create a private examination room and invite students using a unique room code.

                </p>


            </div>


            <!-- BANNER -->

            <div class="room-banner">


                <h2>

                    🔐 Private Scheduled Examination

                </h2>


                <p>

                    Create a room, add your own exam or generate questions using AI, and share the room code with participants.

                </p>


            </div>


            <?php if (!empty($message)): ?>


                <div
                    class="error-message"
                >

                    <?php

                    echo htmlspecialchars(
                        $message
                    );

                    ?>

                </div>


            <?php endif; ?>


            <!-- FORM -->

            <div class="card">


                <form
                    method="POST"
                    id="roomForm"
                >


                    <div class="form-grid">


                        <!-- ROOM NAME -->

                        <div
                            class="form-group full-width"
                        >


                            <label
                                class="form-label"
                                for="room_name"
                            >

                                📝 Room / Exam Name

                            </label>


                            <input
                                type="text"
                                name="room_name"
                                id="room_name"
                                class="form-control"
                                placeholder="Example: Python Assessment Test"
                                required
                            >


                        </div>


                        <!-- START DATE -->

                        <div
                            class="form-group"
                        >


                            <label
                                class="form-label"
                                for="start_date"
                            >

                                📅 Start Date

                            </label>


                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                class="form-control"
                                required
                            >


                            <small class="input-hint">

                                Select the date when the exam opens.

                            </small>


                        </div>


                        <!-- START TIME -->

                        <div
                            class="form-group"
                        >


                            <label
                                class="form-label"
                                for="start_time"
                            >

                                🕒 Start Time

                            </label>


                            <input
                                type="time"
                                name="start_time"
                                id="start_time"
                                class="form-control"
                                required
                            >


                            <small class="input-hint">

                                Exam becomes available from this time.

                            </small>


                        </div>


                        <!-- END DATE -->

                        <div
                            class="form-group"
                        >


                            <label
                                class="form-label"
                                for="end_date"
                            >

                                📅 End Date

                            </label>


                            <input
                                type="date"
                                name="end_date"
                                id="end_date"
                                class="form-control"
                                required
                            >


                            <small class="input-hint">

                                Select the date when the exam closes.

                            </small>


                        </div>


                        <!-- END TIME -->

                        <div
                            class="form-group"
                        >


                            <label
                                class="form-label"
                                for="end_time"
                            >

                                🕒 End Time

                            </label>


                            <input
                                type="time"
                                name="end_time"
                                id="end_time"
                                class="form-control"
                                required
                            >


                            <small class="input-hint">

                                Students cannot access the room after this time.

                            </small>


                        </div>


                    </div>


                    <!-- BUTTON -->

                    <button
                        type="submit"
                        class="btn btn-primary create-button"
                    >

                        🚀 Create Exam Room

                    </button>


                </form>


            </div>


        </div>


    </main>


</div>


<script>


// ==========================================
// DATE PICKER VALIDATION
// ==========================================

const startDate =
    document.getElementById(
        "start_date"
    );


const endDate =
    document.getElementById(
        "end_date"
    );


const startTime =
    document.getElementById(
        "start_time"
    );


const endTime =
    document.getElementById(
        "end_time"
    );


const roomForm =
    document.getElementById(
        "roomForm"
    );


// ==========================================
// GET TODAY'S DATE
// ==========================================

const today =

    new Date()

        .toISOString()

        .split("T")[0];


// Start date cannot be before today

startDate.min = today;


// End date cannot be before today

endDate.min = today;


// ==========================================
// START DATE CHANGE
// ==========================================

startDate.addEventListener(

    "change",

    function () {


        // End date cannot be before start date

        endDate.min =
            startDate.value;


        // If selected end date is before start date

        if (

            endDate.value
            &&
            endDate.value
            <
            startDate.value

        ) {

            endDate.value = "";

        }


        validateDateTime();

    }

);


// ==========================================
// END DATE CHANGE
// ==========================================

endDate.addEventListener(

    "change",

    function () {

        validateDateTime();

    }

);


// ==========================================
// TIME CHANGE
// ==========================================

startTime.addEventListener(

    "change",

    validateDateTime

);


endTime.addEventListener(

    "change",

    validateDateTime

);


// ==========================================
// VALIDATE DATE AND TIME
// ==========================================

function validateDateTime() {


    if (

        !startDate.value
        ||
        !startTime.value
        ||
        !endDate.value
        ||
        !endTime.value

    ) {

        return true;

    }


    const startDateTime =

        new Date(

            startDate.value
            + "T"
            + startTime.value

        );


    const endDateTime =

        new Date(

            endDate.value
            + "T"
            + endTime.value

        );


    if (

        endDateTime
        <=
        startDateTime

    ) {

        endTime.setCustomValidity(

            "End date and time must be after start date and time."

        );

        return false;

    }


    endTime.setCustomValidity("");


    return true;

}


// ==========================================
// FORM SUBMISSION VALIDATION
// ==========================================

roomForm.addEventListener(

    "submit",

    function (event) {


        const startDateTime =

            new Date(

                startDate.value
                + "T"
                + startTime.value

            );


        const endDateTime =

            new Date(

                endDate.value
                + "T"
                + endTime.value

            );


        const currentTime =

            new Date();


        if (

            startDateTime
            <
            currentTime

        ) {

            event.preventDefault();


            alert(

                "Start date and time cannot be in the past."

            );


            return;

        }


        if (

            endDateTime
            <=
            startDateTime

        ) {

            event.preventDefault();


            alert(

                "End date and time must be after start date and time."

            );


            return;

        }

    }

);


// ==========================================
// PRESERVE END DATE VALIDATION
// ==========================================

if (

    startDate.value

) {

    endDate.min =
        startDate.value;

}


</script>


</body>

</html>