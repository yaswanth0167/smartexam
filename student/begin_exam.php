<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_student();

$student_id = intval($_SESSION["user_id"]);


// ============================================================
// VALIDATE EXAM ID
// ============================================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {

    die("Invalid exam ID.");

}

$exam_id = intval($_GET["id"]);


// ============================================================
// CHECK EXAM EXISTS
// ============================================================

$exam_sql = "

    SELECT
        id,
        duration

    FROM exams

    WHERE id = ?

    AND status = 'published'

";

$stmt = $conn->prepare($exam_sql);

if (!$stmt) {

    die(
        "Exam query failed: "
        . htmlspecialchars($conn->error)
    );

}

$stmt->bind_param(
    "i",
    $exam_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    die("Exam not found or not published.");
}

$exam = $result->fetch_assoc();
$stmt->close();


// ============================================================
// CHECK ACTIVE ATTEMPT
// ============================================================

$active_sql = "
    SELECT id, started_at
    FROM attempts
    WHERE user_id = ?
    AND exam_id = ?
    AND submitted_at IS NULL
    ORDER BY id DESC
    LIMIT 1
";

$stmt = $conn->prepare($active_sql);

if (!$stmt) {
    die("Active attempt query failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("ii", $student_id, $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $attempt = $result->fetch_assoc();
    $attempt_id = intval($attempt["id"]);
    $stmt->close();

    $started_ts = strtotime($attempt["started_at"]);
    $duration_s = intval($exam["duration"]) * 60;

    if ($started_ts && (time() - $started_ts) > ($duration_s + 60)) {
        // Old orphaned attempt is expired, close it out so a fresh attempt starts
        $conn->query("UPDATE attempts SET submitted_at = NOW() WHERE id = $attempt_id");
    } else {
        header("Location: take_exam.php?id=" . $exam_id . "&attempt_id=" . $attempt_id);
        exit();
    }
} else {
    $stmt->close();
}


// ============================================================
// CREATE NEW ATTEMPT
// ============================================================

$now_time = date("Y-m-d H:i:s");

$insert_sql = "
    INSERT INTO attempts
    (
        user_id,
        exam_id,
        score,
        total_questions,
        correct_answers,
        started_at
    )
    VALUES
    (
        ?,
        ?,
        0,
        0,
        0,
        ?
    )
";

$stmt = $conn->prepare($insert_sql);

if (!$stmt) {
    die("Attempt insert failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param(
    "iis",
    $student_id,
    $exam_id,
    $now_time
);

if (!$stmt->execute()) {

    die(
        "Attempt creation failed: "
        . htmlspecialchars($stmt->error)
    );

}

$attempt_id = intval(
    $stmt->insert_id
);

$stmt->close();


// ============================================================
// REDIRECT TO TAKE EXAM
// ============================================================

header(

    "Location: take_exam.php?id="
    . $exam_id
    . "&attempt_id="
    . $attempt_id

);

exit();

?>