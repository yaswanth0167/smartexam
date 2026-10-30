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
    DELETE FROM exams
    WHERE id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $exam_id);

if ($stmt->execute()) {

    header("Location: exams.php?message=deleted");

} else {

    header("Location: exams.php?message=error");
}

$stmt->close();

exit();

?>