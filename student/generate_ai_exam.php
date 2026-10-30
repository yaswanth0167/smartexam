<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../config/ai_config.php";
require_once "../includes/ai_procedural_fallback.php";

require_student();

$student_id = $_SESSION["user_id"];


// ==========================================
// ONLY POST REQUESTS ALLOWED
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ai_exam_generator.php");
    exit();

}


// ==========================================
// RECEIVE FORM DATA
// ==========================================

$education_level = trim($_POST["education_level"] ?? "");
$exam_type       = trim($_POST["exam_type"] ?? "");
$stream          = trim($_POST["stream"] ?? "");
$subject         = trim($_POST["subject"] ?? "");
$topic           = trim($_POST["topic"] ?? "");
$question_count  = intval($_POST["question_count"] ?? 0);
$difficulty      = strtolower(trim($_POST["difficulty"] ?? ""));
$question_type   = strtolower(trim($_POST["question_type"] ?? ""));
$duration        = intval($_POST["duration"] ?? 0);
$mode            = strtolower(trim($_POST["mode"] ?? "mock"));


// ==========================================
// ALLOWED VALUES
// ==========================================

$allowed_difficulties = [
    "easy",
    "medium",
    "hard"
];

$allowed_question_types = [
    "mcq",
    "true_false"
];

$allowed_modes = [
    "mock",
    "practice",
    "speed",
    "weak_topic",
    "challenge"
];


// ==========================================
// BASIC VALIDATION
// ==========================================

if (

    empty($education_level) ||
    empty($exam_type) ||
    empty($subject) ||
    empty($topic) ||
    $question_count <= 0 ||
    $duration <= 0

) {

    die("Invalid exam details.");

}


if (

    !in_array(
        $difficulty,
        $allowed_difficulties,
        true
    )

) {

    die("Invalid difficulty.");

}


if (

    !in_array(
        $question_type,
        $allowed_question_types,
        true
    )

) {

    die("Invalid question type.");

}


if (

    !in_array(
        $mode,
        $allowed_modes,
        true
    )

) {

    die("Invalid exam mode.");

}


if (

    $question_count > 30

) {

    die("Maximum 30 questions allowed.");

}


// ==========================================
// QUESTION TYPE RULES
// ==========================================

if (

    $question_type === "mcq"

) {


    $question_rules = "

MCQ RULES:

1. Every question must have exactly four options.
2. Options must be unique.
3. correct_answer must be only A, B, C, or D.
4. All four options must be relevant to the exact selected subject and topic.
5. Do not create unrelated options.

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
    "marks": 1,
    "difficulty": "' . $difficulty . '"
}

';


} else {


    $question_rules = "

TRUE/FALSE RULES:

1. The question must be answerable only with True or False.
2. option_a MUST be exactly \"True\".
3. option_b MUST be exactly \"False\".
4. option_c MUST be an empty string.
5. option_d MUST be an empty string.
6. correct_answer must be only A or B.
7. A means True.
8. B means False.
9. Do not create ambiguous statements.
10. The statement must be strictly related to the exact selected subject and topic.

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
    "marks": 1,
    "difficulty": "' . $difficulty . '"
}

';

}


// ==========================================
// CREATE STRICT AI PROMPT
// ==========================================

$prompt = "

You are an expert examination question generator.

Generate exactly {$question_count} questions.

==========================================
EXAM CONFIGURATION
==========================================

Education Level:
{$education_level}

Exam Type:
{$exam_type}

Stream:
{$stream}

SELECTED SUBJECT:
{$subject}

SELECTED TOPIC:
{$topic}

Difficulty:
{$difficulty}

Question Type:
{$question_type}

Exam Mode:
{$mode}


==========================================
STRICT SUBJECT AND TOPIC RESTRICTION
==========================================

IMPORTANT:

1. Generate questions ONLY from the exact selected subject:

   {$subject}

2. Generate questions ONLY from the exact selected topic:

   {$topic}

3. Every question must directly test knowledge related to both the selected subject and selected topic.

4. DO NOT MIX SUBJECTS.

5. DO NOT CHANGE THE SELECTED SUBJECT.

6. DO NOT ASSUME THAT THE SUBJECT IS PHYSICS.

7. DO NOT GENERATE QUESTIONS FROM PHYSICS UNLESS THE SELECTED SUBJECT IS EXACTLY PHYSICS.

8. DO NOT GENERATE QUESTIONS FROM CHEMISTRY UNLESS THE SELECTED SUBJECT IS EXACTLY CHEMISTRY.

9. DO NOT GENERATE QUESTIONS FROM BIOLOGY UNLESS THE SELECTED SUBJECT IS EXACTLY BIOLOGY.

10. DO NOT GENERATE QUESTIONS FROM MATHEMATICS UNLESS THE SELECTED SUBJECT IS EXACTLY MATHEMATICS.

11. DO NOT GENERATE QUESTIONS FROM HISTORY, GEOGRAPHY, CURRENT AFFAIRS OR ANY OTHER SUBJECT UNLESS THAT SUBJECT IS SELECTED.

12. If the selected subject is General Knowledge or GK, generate ONLY General Knowledge questions related to the selected topic.

13. The selected subject has the highest priority.

14. The selected topic has the second highest priority.

15. Before generating every question, verify internally:

   Does this question belong to the exact selected subject:
   {$subject}

   AND the exact selected topic:
   {$topic}

   If the answer is NO, discard that question and generate a new one.

16. Every question must be clearly understandable and directly related to the selected subject and topic.


==========================================
GENERAL RULES
==========================================

1. Generate exactly {$question_count} questions.

2. Every question must be relevant to the exact selected subject and topic.

3. Every question must have a clear explanation.

4. marks must be an integer greater than 0.

5. difficulty must be exactly {$difficulty}.

6. Do not add markdown.

7. Do not add comments.

8. Do not add any text outside JSON.

9. Return ONLY valid JSON.

10. Do not return introductory text.

11. Do not return concluding text.


==========================================
QUESTION TYPE RULES
==========================================

{$question_rules}


==========================================
REQUIRED JSON FORMAT
==========================================

{
    \"questions\": [
        {$json_format}
    ]
}

";


// ==========================================
// OPENROUTER REQUEST
// ==========================================

$request_data = [

    "model" => OPENROUTER_MODEL,

    "messages" => [

        [

            "role" => "system",

            "content" =>

                "You generate exam questions strictly according to the exact subject and topic provided."

        ],

        [

            "role" => "user",

            "content" => $prompt

        ]

    ],

    "temperature" => 0.3,

    "response_format" => [

        "type" => "json_object"

    ]

];


$json_request = json_encode(
    $request_data
);


// ==========================================
// CURL REQUEST
// ==========================================

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


$response = curl_exec($ch);


$http_code = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);


curl_close($ch);


// ==========================================
// PROCESS AI RESPONSE WITH AUTO FALLBACK
// ==========================================

$questions = [];

if ($response !== false && $http_code === 200) {
    $ai_res = json_decode($response, true);
    if (isset($ai_res["choices"][0]["message"]["content"])) {
        $ai_text = $ai_res["choices"][0]["message"]["content"];
        $questions_data = json_decode($ai_text, true);
        if (isset($questions_data["questions"]) && is_array($questions_data["questions"])) {
            $questions = $questions_data["questions"];
        }
    }
}

// Seamlessly fall back to procedural questions if API returned errors, invalid format, or insufficient count
if (empty($questions) || count($questions) < $question_count) {
    $questions = get_procedural_ai_questions($subject, $topic, $question_count, $difficulty, $question_type);
}

// Exactly match requested question count
if (count($questions) > $question_count) {
    $questions = array_slice($questions, 0, $question_count);
}


// ==========================================
// VALIDATE QUESTIONS
// ==========================================

foreach (

    $questions
    as
    $index => $question

) {


    $required_fields = [

        "question",
        "option_a",
        "option_b",
        "option_c",
        "option_d",
        "correct_answer",
        "explanation",
        "marks",
        "difficulty"

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

            die(

                "Question "
                . ($index + 1)
                . " has missing field: "
                . $field

            );

        }

    }


    $correct_answer = strtoupper(

        trim(
            $question["correct_answer"]
        )

    );


    // ======================================
    // MCQ VALIDATION
    // ======================================

    if (

        $question_type === "mcq"

    ) {


        $options = [

            trim(
                $question["option_a"]
            ),

            trim(
                $question["option_b"]
            ),

            trim(
                $question["option_c"]
            ),

            trim(
                $question["option_d"]
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

            die(

                "Question "
                . ($index + 1)
                . " contains duplicate options."

            );

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

            die(

                "Question "
                . ($index + 1)
                . " has invalid MCQ answer."

            );

        }

    }


    // ======================================
    // TRUE / FALSE VALIDATION
    // ======================================

    if (

        $question_type === "true_false"

    ) {


        if (

            trim(
                $question["option_a"]
            )
            !==
            "True"

        ) {

            die(

                "Question "
                . ($index + 1)
                . " must have option A as True."

            );

        }


        if (

            trim(
                $question["option_b"]
            )
            !==
            "False"

        ) {

            die(

                "Question "
                . ($index + 1)
                . " must have option B as False."

            );

        }


        if (

            trim(
                $question["option_c"]
            )
            !==
            ""

            ||

            trim(
                $question["option_d"]
            )
            !==
            ""

        ) {

            die(

                "True/False questions cannot have C or D options."

            );

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

            die(

                "Question "
                . ($index + 1)
                . " has invalid True/False answer."

            );

        }

    }


    // ======================================
    // MARKS VALIDATION
    // ======================================

    if (

        !is_numeric(
            $question["marks"]
        )

        ||

        intval(
            $question["marks"]
        )
        <=
        0

    ) {

        die(

            "Question "
            . ($index + 1)
            . " has invalid marks."

        );

    }


    // ======================================
    // DIFFICULTY VALIDATION
    // ======================================

    if (

        strtolower(

            trim(
                $question["difficulty"]
            )

        )

        !==

        strtolower(
            $difficulty
        )

    ) {

        die(

            "Question "
            . ($index + 1)
            . " has invalid difficulty."

        );

    }

}


// ==========================================
// SAVE AI EXAM
// ==========================================

$conn->begin_transaction();


try {


    // ======================================
    // INSERT AI EXAM
    // ======================================

    $exam_sql = "

        INSERT INTO ai_exams

        (

            student_id,
            education_level,
            exam_type,
            stream,
            subject,
            topic,
            question_count,
            difficulty,
            question_type,
            duration,
            mode

        )

        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)

    ";


    $stmt = $conn->prepare(
        $exam_sql
    );


    if (

        !$stmt

    ) {

        throw new Exception(

            "Exam preparation failed: "
            . $conn->error

        );

    }


    $stmt->bind_param(

        "isssssissis",

        $student_id,
        $education_level,
        $exam_type,
        $stream,
        $subject,
        $topic,
        $question_count,
        $difficulty,
        $question_type,
        $duration,
        $mode

    );


    if (

        !$stmt->execute()

    ) {

        throw new Exception(

            "Failed to save AI exam: "
            . $stmt->error

        );

    }


    $ai_exam_id =
        $stmt->insert_id;


    $stmt->close();


    // ======================================
    // INSERT QUESTIONS
    // ======================================

    $question_sql = "

        INSERT INTO ai_questions

        (

            ai_exam_id,
            question_text,
            option_a,
            option_b,
            option_c,
            option_d,
            correct_answer,
            explanation,
            marks,
            difficulty

        )

        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)

    ";


    $question_stmt =
        $conn->prepare(
            $question_sql
        );


    if (

        !$question_stmt

    ) {

        throw new Exception(

            "Question preparation failed: "
            . $conn->error

        );

    }


    foreach (

        $questions
        as
        $question

    ) {


        $question_text =
            trim(
                $question["question"]
            );


        $option_a =
            trim(
                $question["option_a"]
            );


        $option_b =
            trim(
                $question["option_b"]
            );


        $option_c =
            trim(
                $question["option_c"]
            );


        $option_d =
            trim(
                $question["option_d"]
            );


        $correct_answer =
            strtoupper(

                trim(
                    $question["correct_answer"]
                )

            );


        $explanation =
            trim(
                $question["explanation"]
            );


        $marks =
            intval(
                $question["marks"]
            );


        $question_difficulty =
            strtolower(

                trim(
                    $question["difficulty"]
                )

            );


        $question_stmt->bind_param(

            "isssssssis",

            $ai_exam_id,
            $question_text,
            $option_a,
            $option_b,
            $option_c,
            $option_d,
            $correct_answer,
            $explanation,
            $marks,
            $question_difficulty

        );


        if (

            !$question_stmt->execute()

        ) {

            throw new Exception(

                "Failed to save AI question: "
                . $question_stmt->error

            );

        }

    }


    $question_stmt->close();


    // ======================================
    // COMMIT TRANSACTION
    // ======================================

    $conn->commit();


    header(

        "Location: ai_exam_preview.php?id="
        . $ai_exam_id

    );


    exit();


}

catch (

    Exception $e

) {


    $conn->rollback();


    die(

        "Database Error: "
        . htmlspecialchars(

            $e->getMessage()

        )

    );

}

?>