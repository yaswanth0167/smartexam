<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: exams.php");
    exit();
}

$question_id = intval($_GET["id"]);

$sql = "
    SELECT exam_id
    FROM questions
    WHERE id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $question_id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    header("Location: exams.php");
    exit();
}

$question = $result->fetch_assoc();

$exam_id = $question["exam_id"];

$stmt->close();

$delete_sql = "
    DELETE FROM questions
    WHERE id = ?
";

$stmt = $conn->prepare($delete_sql);

$stmt->bind_param("i", $question_id);

$stmt->execute();

$stmt->close();

header("Location: questions.php?exam_id=" . $exam_id);

exit();

?>