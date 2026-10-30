<?php

// ============================================================
// ROOM AI EXAM GENERATOR
// ============================================================

error_reporting(E_ALL);
ini_set("display_errors", 1);


// ============================================================
// REQUIRE FILES
// ============================================================

require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../config/ai_config.php";
require_once "../includes/ai_procedural_fallback.php";

require_student();


// ============================================================
// STUDENT ID
// ============================================================

$student_id = $_SESSION["user_id"];


// ============================================================
// VALIDATE ROOM ID
// ============================================================

if (
    !isset($_GET["room_id"]) ||
    !is_numeric($_GET["room_id"])
) {

    header("Location: dashboard.php");

    exit();

}

$room_id = intval($_GET["room_id"]);


// ============================================================
// FETCH ROOM
// ============================================================

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

    die(
        "Database Error: "
        . htmlspecialchars($conn->error)
    );

}

$stmt->bind_param(
    "ii",
    $room_id,
    $student_id
);

$stmt->execute();

$room_result = $stmt->get_result();

if (
    $room_result->num_rows !== 1
) {

    die("Room not found or access denied.");

}

$room = $room_result->fetch_assoc();

$stmt->close();


// ============================================================
// MESSAGE
// ============================================================

$message = "";

$message_type = "";


// ============================================================
// FORM SUBMISSION
// ============================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


    // ========================================================
    // RECEIVE FORM DATA
    // ========================================================

    $subject = trim(
        $_POST["subject"] ?? ""
    );

    $topic = trim(
        $_POST["topic"] ?? ""
    );

    $question_count = intval(
        $_POST["question_count"] ?? 0
    );

    $difficulty = strtolower(
        trim(
            $_POST["difficulty"] ?? ""
        )
    );

    $question_type = strtolower(
        trim(
            $_POST["question_type"] ?? ""
        )
    );

    $duration = intval(
        $_POST["duration"] ?? 0
    );

    $description = trim(
        $_POST["description"] ?? ""
    );


    // ========================================================
    // VALIDATION
    // ========================================================

    $allowed_difficulties = [

        "easy",
        "medium",
        "hard"

    ];


    $allowed_question_types = [

        "mcq",
        "true_false"

    ];


    if (

        empty($subject) ||

        empty($topic) ||

        $question_count <= 0 ||

        $duration <= 0

    ) {

        $message =
            "Please fill all required fields.";

        $message_type =
            "error";

    }


    elseif (

        $question_count > 50

    ) {

        $message =
            "Maximum 50 questions are allowed.";

        $message_type =
            "error";

    }


    elseif (

        !in_array(

            $difficulty,

            $allowed_difficulties,

            true

        )

    ) {

        $message =
            "Invalid difficulty selected.";

        $message_type =
            "error";

    }


    elseif (

        !in_array(

            $question_type,

            $allowed_question_types,

            true

        )

    ) {

        $message =
            "Invalid question type selected.";

        $message_type =
            "error";

    }


    else {


        // ====================================================
        // QUESTION RULES
        // ====================================================

        if (

            $question_type === "mcq"

        ) {


            $question_rules = "

MCQ RULES:

1. Every question must have exactly four options.
2. Options must be unique.
3. correct_answer must be only A, B, C, or D.
4. All questions must strictly belong to the selected subject and topic.

";


            $json_format = '

{
    "question": "Question text",
    "option_a": "Option A",
    "option_b": "Option B",
    "option_c": "Option C",
    "option_d": "Option D",
    "correct_answer": "A",
    "explanation": "Explanation",
    "marks": 1
}

';


        }

        else {


            $question_rules = "

TRUE/FALSE RULES:

1. The question must be a clear True or False statement.
2. option_a must be exactly True.
3. option_b must be exactly False.
4. option_c must be an empty string.
5. option_d must be an empty string.
6. correct_answer must be A or B.
7. A means True.
8. B means False.

";


            $json_format = '

{
    "question": "Statement",
    "option_a": "True",
    "option_b": "False",
    "option_c": "",
    "option_d": "",
    "correct_answer": "A",
    "explanation": "Explanation",
    "marks": 1
}

';

        }


        // ====================================================
        // AI PROMPT
        // ====================================================

        $prompt = "

You are an expert examination question generator.

Generate exactly {$question_count} examination questions.

========================================
EXAM DETAILS
========================================

Subject:
{$subject}

Topic:
{$topic}

Difficulty:
{$difficulty}

Question Type:
{$question_type}

========================================
STRICT SUBJECT AND TOPIC RULE
========================================

IMPORTANT:

1. Generate questions ONLY from the exact subject:

{$subject}

2. Generate questions ONLY from the exact topic:

{$topic}

3. Do not mix subjects.

4. Do not generate unrelated questions.

5. Every question must directly test knowledge of both:

Subject: {$subject}

AND

Topic: {$topic}

6. Generate exactly {$question_count} questions.

7. All questions must have clear explanations.

8. marks must be a positive integer.

9. Return ONLY valid JSON.

10. Do not add markdown.

11. Do not add any text outside JSON.

========================================
QUESTION RULES
========================================

{$question_rules}

========================================
REQUIRED JSON FORMAT
========================================

{
    \"questions\": [
        {$json_format}
    ]
}

";


        // ====================================================
        // OPENROUTER REQUEST
        // ====================================================

        $request_data = [

            "model" =>
                OPENROUTER_MODEL,

            "messages" => [

                [

                    "role" =>
                        "system",

                    "content" =>
                        "Generate strictly valid examination questions based only on the exact subject and topic provided."

                ],

                [

                    "role" =>
                        "user",

                    "content" =>
                        $prompt

                ]

            ],

            "temperature" =>
                0.3,

            "response_format" => [

                "type" =>
                    "json_object"

            ]

        ];


        $json_request = json_encode(
            $request_data
        );


        // ====================================================
        // CURL REQUEST
        // ====================================================

        $ch = curl_init(
            OPENROUTER_API_URL
        );


        curl_setopt(
            $ch,
            CURLOPT_RETURNTRANSFER,
            true
        );

        curl_setopt(
            $ch,
            CURLOPT_POST,
            true
        );

        curl_setopt(
            $ch,
            CURLOPT_TIMEOUT,
            25
        );

        curl_setopt(
            $ch,
            CURLOPT_CONNECTTIMEOUT,
            8
        );

        curl_setopt(
            $ch,
            CURLOPT_SSL_VERIFYPEER,
            false
        );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                "Content-Type: application/json",
                "Authorization: Bearer "
                . OPENROUTER_API_KEY,
                "HTTP-Referer: https://smartexam.infinityfree.me",
                "X-Title: SmartExam"
            ]
        );

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $json_request
        );

        $response =
            curl_exec($ch);

        $http_code =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

        curl_close($ch);

        // ====================================================
        // PROCESS AI RESPONSE WITH AUTO FALLBACK
        // ====================================================

        $questions = [];

        if ($response !== false && $http_code === 200) {
            $openrouter_data = json_decode($response, true);
            if (isset($openrouter_data["choices"][0]["message"]["content"])) {
                $ai_text = $openrouter_data["choices"][0]["message"]["content"];
                $questions_data = json_decode($ai_text, true);
                if (isset($questions_data["questions"]) && is_array($questions_data["questions"])) {
                    $questions = $questions_data["questions"];
                }
            }
        }

        // Seamless fallback if API failed or returned insufficient questions
        if (empty($questions) || count($questions) < $question_count) {
            $questions = get_procedural_ai_questions($subject, $topic, $question_count, $difficulty, $question_type);
        }

        if (count($questions) > $question_count) {
            $questions = array_slice($questions, 0, $question_count);
        }

        if (count($questions) !== $question_count) {
            $message = "Unable to generate required number of questions.";
            $message_type = "error";
        } else {


                        // =====================================
                        // VALIDATE QUESTIONS
                        // =====================================

                        $valid_questions = true;


                        foreach (

                            $questions
                            as
                            $index
                            =>
                            $question

                        ) {


                            $required_fields = [

                                "question",

                                "option_a",

                                "option_b",

                                "option_c",

                                "option_d",

                                "correct_answer",

                                "explanation",

                                "marks"

                            ];


                            foreach (

                                $required_fields
                                as
                                $field

                            ) {

                                if (

                                    !array_key_exists(

                                        $field,

                                        $question

                                    )

                                ) {

                                    $valid_questions =
                                        false;

                                    $message =
                                        "Question "
                                        . ($index + 1)
                                        . " is missing field: "
                                        . $field;

                                    break 2;

                                }

                            }


                            $correct_answer =
                                strtoupper(

                                    trim(

                                        $question
                                        ["correct_answer"]

                                    )

                                );


                            // =================================
                            // MCQ VALIDATION
                            // =================================

                            if (

                                $question_type
                                ===
                                "mcq"

                            ) {


                                $options = [

                                    trim(

                                        $question
                                        ["option_a"]

                                    ),

                                    trim(

                                        $question
                                        ["option_b"]

                                    ),

                                    trim(

                                        $question
                                        ["option_c"]

                                    ),

                                    trim(

                                        $question
                                        ["option_d"]

                                    )

                                ];


                                if (

                                    count(

                                        array_unique(

                                            $options

                                        )

                                    )
                                    !==
                                    4

                                ) {

                                    $valid_questions =
                                        false;

                                    $message =
                                        "Question "
                                        . ($index + 1)
                                        . " has duplicate options.";

                                    break;

                                }


                                if (

                                    !in_array(

                                        $correct_answer,

                                        [

                                            "A",

                                            "B",

                                            "C",

                                            "D"

                                        ],

                                        true

                                    )

                                ) {

                                    $valid_questions =
                                        false;

                                    $message =
                                        "Question "
                                        . ($index + 1)
                                        . " has invalid correct answer.";

                                    break;

                                }

                            }


                            // =================================
                            // TRUE/FALSE VALIDATION
                            // =================================

                            if (

                                $question_type
                                ===
                                "true_false"

                            ) {


                                if (

                                    trim(

                                        $question
                                        ["option_a"]

                                    )
                                    !==
                                    "True"

                                    ||

                                    trim(

                                        $question
                                        ["option_b"]

                                    )
                                    !==
                                    "False"

                                    ||

                                    trim(

                                        $question
                                        ["option_c"]

                                    )
                                    !==
                                    ""

                                    ||

                                    trim(

                                        $question
                                        ["option_d"]

                                    )
                                    !==
                                    ""

                                ) {

                                    $valid_questions =
                                        false;

                                    $message =
                                        "Invalid True/False question format.";

                                    break;

                                }


                                if (

                                    !in_array(

                                        $correct_answer,

                                        [

                                            "A",

                                            "B"

                                        ],

                                        true

                                    )

                                ) {

                                    $valid_questions =
                                        false;

                                    $message =
                                        "Invalid True/False answer.";

                                    break;

                                }

                            }


                            // =================================
                            // MARKS VALIDATION
                            // =================================

                            if (

                                !is_numeric(

                                    $question
                                    ["marks"]

                                )

                                ||

                                intval(

                                    $question
                                    ["marks"]

                                )
                                <=
                                0

                            ) {

                                $valid_questions =
                                    false;

                                $message =
                                    "Invalid marks in question "
                                    . ($index + 1);

                                break;

                            }

                        }


                        // =====================================
                        // SAVE EXAM
                        // =====================================

                        if (

                            $valid_questions

                        ) {


                            $conn->begin_transaction();


                            try {


                                // =================================
                                // INSERT ROOM EXAM
                                // =================================

                                $exam_sql = "

                                    INSERT INTO room_exams

                                    (

                                        room_id,

                                        title,

                                        description,

                                        duration,

                                        total_questions

                                    )

                                    VALUES (?, ?, ?, ?, ?)

                                ";


                                $exam_stmt =
                                    $conn->prepare(

                                        $exam_sql

                                    );


                                if (

                                    !$exam_stmt

                                ) {

                                    throw new Exception(

                                        $conn->error

                                    );

                                }


                                $title =
                                    $room
                                    ["room_name"];


                                $exam_stmt->bind_param(

                                    "issii",

                                    $room_id,

                                    $title,

                                    $description,

                                    $duration,

                                    $question_count

                                );


                                if (

                                    !$exam_stmt->execute()

                                ) {

                                    throw new Exception(

                                        $exam_stmt->error

                                    );

                                }


                                $room_exam_id =
                                    $exam_stmt->insert_id;


                                $exam_stmt->close();


                                // =================================
                                // INSERT QUESTIONS
                                // =================================

                                $question_sql = "

                                    INSERT INTO room_questions

                                    (

                                        room_exam_id,

                                        question_text,

                                        option_a,

                                        option_b,

                                        option_c,

                                        option_d,

                                        correct_answer,

                                        explanation,

                                        marks

                                    )

                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)

                                ";


                                $question_stmt =
                                    $conn->prepare(

                                        $question_sql

                                    );


                                if (

                                    !$question_stmt

                                ) {

                                    throw new Exception(

                                        $conn->error

                                    );

                                }


                                foreach (

                                    $questions
                                    as
                                    $question

                                ) {


                                    $question_text =
                                        trim(

                                            $question
                                            ["question"]

                                        );


                                    $option_a =
                                        trim(

                                            $question
                                            ["option_a"]

                                        );


                                    $option_b =
                                        trim(

                                            $question
                                            ["option_b"]

                                        );


                                    $option_c =
                                        trim(

                                            $question
                                            ["option_c"]

                                        );


                                    $option_d =
                                        trim(

                                            $question
                                            ["option_d"]

                                        );


                                    $correct_answer =
                                        strtoupper(

                                            trim(

                                                $question
                                                ["correct_answer"]

                                            )

                                        );


                                    $explanation =
                                        trim(

                                            $question
                                            ["explanation"]

                                        );


                                    $marks =
                                        intval(

                                            $question
                                            ["marks"]

                                        );


                                    $question_stmt->bind_param(

                                        "isssssssi",

                                        $room_exam_id,

                                        $question_text,

                                        $option_a,

                                        $option_b,

                                        $option_c,

                                        $option_d,

                                        $correct_answer,

                                        $explanation,

                                        $marks

                                    );


                                    if (

                                        !$question_stmt->execute()

                                    ) {

                                        throw new Exception(

                                            $question_stmt->error

                                        );

                                    }

                                }


                                $question_stmt->close();


                                // =================================
                                // UPDATE ROOM
                                // =================================

                                $update_room_sql = "

                                    UPDATE rooms

                                    SET

                                        exam_type = 'ai',

                                        ai_exam_id = ?,

                                        status = 'scheduled'

                                    WHERE id = ?

                                    AND creator_id = ?

                                ";


                                $update_stmt =
                                    $conn->prepare(

                                        $update_room_sql

                                    );


                                if (

                                    !$update_stmt

                                ) {

                                    throw new Exception(

                                        $conn->error

                                    );

                                }


                                $update_stmt->bind_param(

                                    "iii",

                                    $room_exam_id,

                                    $room_id,

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


                                // =================================
                                // COMMIT
                                // =================================

                                $conn->commit();


                                header(

                                    "Location: room_exam_preview.php?id="
                                    . $room_exam_id

                                );

                                exit();


                            }

                            catch (

                                Exception $e

                            ) {


                                $conn->rollback();


                                $message =
                                    "Database Error: "
                                    . $e->getMessage();

                                $message_type =
                                    "error";

                            }

                        }

                    }

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

        AI Room Exam Generator - SmartExam

    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .room-container {

            max-width: 950px;

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


        .room-info h2 {

            margin-bottom: 8px;

        }


        .room-code {

            display: inline-block;

            margin-top: 12px;

            padding: 10px 18px;

            border-radius: 8px;

            background:

                rgba(
                    0,
                    0,
                    0,
                    0.25
                );

            font-weight: 800;

            letter-spacing: 3px;

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


        textarea.form-control {

            min-height: 100px;

            resize: vertical;

        }


        .generate-button {

            width: 100%;

            margin-top: 25px;

            padding: 16px;

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

            white-space: pre-wrap;

            word-break: break-word;

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

            <div class="page-header">


                <h1>

                    🤖 Generate AI Exam for Room

                </h1>


                <p>

                    AI will generate a brand-new exam specifically for this private examination room.

                </p>


            </div>


            <!-- ROOM INFO -->

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

                    Room Code:

                </p>


                <div class="room-code">


                    <?php

                    echo htmlspecialchars(

                        $room["room_code"]

                    );

                    ?>


                </div>


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

                >


                    <div class="form-grid">


                        <!-- SUBJECT -->

                        <div

                            class="form-group"

                        >


                            <label

                                class="form-label"

                                for="subject"

                            >

                                Subject

                            </label>


                            <input

                                type="text"

                                name="subject"

                                id="subject"

                                class="form-control"

                                placeholder="Example: Python"

                                required

                            >


                        </div>


                        <!-- TOPIC -->

                        <div

                            class="form-group"

                        >


                            <label

                                class="form-label"

                                for="topic"

                            >

                                Topic

                            </label>


                            <input

                                type="text"

                                name="topic"

                                id="topic"

                                class="form-control"

                                placeholder="Example: OOP Concepts"

                                required

                            >


                        </div>


                        <!-- QUESTION COUNT -->

                        <div

                            class="form-group"

                        >


                            <label

                                class="form-label"

                                for="question_count"

                            >

                                Number of Questions

                            </label>


                            <input

                                type="number"

                                name="question_count"

                                id="question_count"

                                class="form-control"

                                min="1"

                                max="50"

                                value="10"

                                required

                            >


                        </div>


                        <!-- DIFFICULTY -->

                        <div

                            class="form-group"

                        >


                            <label

                                class="form-label"

                                for="difficulty"

                            >

                                Difficulty

                            </label>


                            <select

                                name="difficulty"

                                id="difficulty"

                                class="form-control"

                                required

                            >

                                <option

                                    value="easy"

                                >

                                    Easy

                                </option>


                                <option

                                    value="medium"

                                    selected

                                >

                                    Medium

                                </option>


                                <option

                                    value="hard"

                                >

                                    Hard

                                </option>


                            </select>


                        </div>


                        <!-- QUESTION TYPE -->

                        <div

                            class="form-group"

                        >


                            <label

                                class="form-label"

                                for="question_type"

                            >

                                Question Type

                            </label>


                            <select

                                name="question_type"

                                id="question_type"

                                class="form-control"

                                required

                            >

                                <option

                                    value="mcq"

                                    selected

                                >

                                    Multiple Choice Questions

                                </option>


                                <option

                                    value="true_false"

                                >

                                    True / False

                                </option>


                            </select>


                        </div>


                        <!-- DURATION -->

                        <div

                            class="form-group"

                        >


                            <label

                                class="form-label"

                                for="duration"

                            >

                                Exam Duration (Minutes)

                            </label>


                            <input

                                type="number"

                                name="duration"

                                id="duration"

                                class="form-control"

                                min="1"

                                max="300"

                                value="30"

                                required

                            >


                        </div>


                        <!-- DESCRIPTION -->

                        <div

                            class="form-group full-width"

                        >


                            <label

                                class="form-label"

                                for="description"

                            >

                                Exam Description

                            </label>


                            <textarea

                                name="description"

                                id="description"

                                class="form-control"

                                placeholder="Describe this examination..."

                            ></textarea>


                        </div>


                    </div>


                    <button

                        type="submit"

                        class="btn btn-primary generate-button"

                    >

                        🤖 Generate New AI Examination

                    </button>


                </form>


            </div>


        </div>


    </main>


</div>


</body>

</html>