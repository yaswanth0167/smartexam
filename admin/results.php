<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

$sql = "
    SELECT 
        attempts.id,
        users.name AS student_name,
        users.email AS student_email,
        exams.title AS exam_title,
        attempts.score,
        attempts.total_questions,
        attempts.submitted_at
    FROM attempts
    INNER JOIN users ON attempts.user_id = users.id
    INNER JOIN exams ON attempts.exam_id = exams.id
    WHERE attempts.submitted_at IS NOT NULL
    ORDER BY attempts.submitted_at DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Results - SmartExam</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">Smart<span>Exam</span></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="sidebar-link">
                    <span>📊</span> Dashboard
                </a>
                <a href="exams.php" class="sidebar-link">
                    <span>📝</span> Manage Exams
                </a>
                <a href="results.php" class="sidebar-link active">
                    <span>🏆</span> Student Results
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php" class="btn btn-danger" style="width: 100%;">
                    <span>🚪</span> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="font-size: 28px; margin-bottom: 5px;">Student Results</h1>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 0;">Monitor student performance and exam attempt logs.</p>
                </div>
                <a href="dashboard.php" class="btn btn-secondary">
                    Back to Dashboard
                </a>
            </div>

            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Exam Name</th>
                                <th>Score</th>
                                <th>Accuracy</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php 
                                $score = $row["score"];
                                $total_questions = $row["total_questions"];
                                $accuracy = $total_questions > 0 ? round(($score / $total_questions) * 100, 2) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($row["student_name"]); ?></div>
                                        <span style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($row["student_email"]); ?></span>
                                    </td>
                                    <td style="color: #ffffff;"><?php echo htmlspecialchars($row["exam_title"]); ?></td>
                                    <td>
                                        <span style="color: var(--accent-cyan); font-weight: bold;"><?php echo $score; ?></span> / <?php echo $total_questions; ?>
                                    </td>
                                    <td>
                                        <?php if ($accuracy >= 70): ?>
                                            <span class="badge badge-success"><?php echo $accuracy; ?>%</span>
                                        <?php elseif ($accuracy >= 40): ?>
                                            <span class="badge badge-warning"><?php echo $accuracy; ?>%</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><?php echo $accuracy; ?>%</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y - h:i A', strtotime($row["submitted_at"])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 50px; border-color: rgba(255,255,255,0.05);">
                    <div style="font-size: 48px; margin-bottom: 20px;">🏆</div>
                    <h3 style="margin-bottom: 10px;">No Results Found</h3>
                    <p style="color: var(--text-muted); margin-bottom: 0;">Once students attempt quizzes and submit their answers, detailed performance logs will appear here.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
