<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: exams.php");

    exit();
}

$exam_id = intval($_GET["id"]);

$sql = "
    SELECT status
    FROM exams
    WHERE id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $exam_id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    header("Location: exams.php");

    exit();
}

$exam = $result->fetch_assoc();

$stmt->close();

$current_status = $exam["status"];

if ($current_status === "draft") {

    $new_status = "published";

} else {

    $new_status = "draft";
}

$update_sql = "
    UPDATE exams
    SET status = ?
    WHERE id = ?
";

$stmt = $conn->prepare($update_sql);

$stmt->bind_param(
    "si",
    $new_status,
    $exam_id
);

$stmt->execute();

$stmt->close();

header("Location: exams.php");

exit();

?>