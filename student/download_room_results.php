<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$creator_id = $_SESSION["user_id"];


// ============================================================
// VALIDATE EXAM ID
// ============================================================

if (
    !isset($_GET["exam_id"]) ||
    !is_numeric($_GET["exam_id"])
) {

    die("Invalid exam ID.");

}

$room_exam_id = intval($_GET["exam_id"]);


// ============================================================
// VERIFY CREATOR
// ============================================================

$exam_sql = "

    SELECT

        re.id,
        re.title,
        re.total_questions,

        r.room_name,
        r.room_code,
        r.creator_id

    FROM room_exams re

    INNER JOIN rooms r

        ON re.room_id = r.id

    WHERE

        re.id = ?

        AND

        r.creator_id = ?

";


$exam_stmt =
    $conn->prepare($exam_sql);


if (!$exam_stmt) {

    die(
        "Database Error: "
        . htmlspecialchars($conn->error)
    );

}


$exam_stmt->bind_param(

    "ii",

    $room_exam_id,
    $creator_id

);


$exam_stmt->execute();


$exam_result =
    $exam_stmt->get_result();


if (
    $exam_result->num_rows !== 1
) {

    die(
        "You are not authorized to download these results."
    );

}


$exam =
    $exam_result->fetch_assoc();


$exam_stmt->close();


// ============================================================
// FETCH RESULTS
// ============================================================

$result_sql = "

    SELECT

        ra.score,
        ra.total_questions,
        ra.correct_answers,
        ra.submitted_at,

        u.name,
        u.email

    FROM room_attempts ra

    INNER JOIN users u

        ON ra.user_id = u.id

    WHERE

        ra.room_exam_id = ?

    ORDER BY

        ra.score DESC,

        ra.submitted_at ASC

";


$result_stmt =
    $conn->prepare($result_sql);


if (!$result_stmt) {

    die(
        "Database Error: "
        . htmlspecialchars($conn->error)
    );

}


$result_stmt->bind_param(

    "i",

    $room_exam_id

);


$result_stmt->execute();


$result_data =
    $result_stmt->get_result();


// ============================================================
// DOWNLOAD AS CSV
// ============================================================

$filename =

    "SmartExam_Results_"

    .

    preg_replace(

        "/[^a-zA-Z0-9_-]/",

        "_",

        $exam["title"]

    )

    .

    "_"

    .

    date("Y-m-d")

    .

    ".csv";


header(
    "Content-Type: text/csv; charset=utf-8"
);

header(

    "Content-Disposition: attachment; filename=\""

    .

    $filename

    .

    "\""

);


// ============================================================
// OPEN OUTPUT
// ============================================================

$output = fopen(
    "php://output",
    "w"
);


// ============================================================
// CSV TITLE
// ============================================================

fputcsv(

    $output,

    [

        "SmartExam - Student Results"

    ]

);


fputcsv(

    $output,

    [

        "Exam",

        $exam["title"]

    ]

);


fputcsv(

    $output,

    [

        "Room",

        $exam["room_name"]

    ]

);


fputcsv(

    $output,

    [

        "Room Code",

        $exam["room_code"]

    ]

);


fputcsv(

    $output,

    [

        ""

    ]

);


// ============================================================
// TABLE HEADER
// ============================================================

fputcsv(

    $output,

    [

        "Rank",

        "Student Name",

        "Email",

        "Score",

        "Total Questions",

        "Correct Answers",

        "Percentage",

        "Submitted At"

    ]

);


// ============================================================
// RESULTS
// ============================================================

$rank = 1;


while (

    $row =

    $result_data->fetch_assoc()

) {


    $total_questions =

        intval(

            $row["total_questions"]

        );


    $correct_answers =

        intval(

            $row["correct_answers"]

        );


    $percentage = 0;


    if (

        $total_questions > 0

    ) {


        $percentage =

            round(

                (

                    $correct_answers

                    /

                    $total_questions

                )

                *

                100,

                2

            );

    }


    $submitted_at =

        "Not Submitted";


    if (

        !empty(

            $row["submitted_at"]

        )

    ) {


        $submitted_at =

            date(

                "d M Y, h:i A",

                strtotime(

                    $row["submitted_at"]

                )

            );

    }


    fputcsv(

        $output,

        [

            $rank++,

            $row["name"],

            $row["email"],

            $row["score"],

            $total_questions,

            $correct_answers,

            $percentage . "%",

            $submitted_at

        ]

    );

}


fclose($output);

exit();

?>