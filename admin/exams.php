<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

$sql = "
    SELECT 
        exams.id,
        exams.title,
        exams.description,
        exams.duration,
        exams.total_marks,
        exams.status,
        exams.created_at,
        users.name AS creator_name
    FROM exams
    INNER JOIN users
        ON exams.created_by = users.id
    ORDER BY exams.created_at DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - SmartExam</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .exam-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 25px;
        }
        .exam-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 30px;
        }
        .exam-details {
            flex-grow: 1;
        }
        .exam-meta {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .exam-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 180px;
        }
        @media (max-width: 768px) {
            .exam-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .exam-actions {
                width: 100%;
                flex-direction: row;
                flex-wrap: wrap;
            }
            .exam-actions .btn {
                flex-grow: 1;
            }
        }
    </style>
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
                <a href="exams.php" class="sidebar-link active">
                    <span>📝</span> Manage Exams
                </a>
                <a href="results.php" class="sidebar-link">
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="font-size: 28px; margin-bottom: 5px;">Manage Exams</h1>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 0;">Create, edit, delete, and publish examinations for students.</p>
                </div>
                <a href="create_exam.php" class="btn btn-primary">
                    <span>➕</span> Create New Exam
                </a>
            </div>

            <?php if ($result && $result->num_rows > 0): ?>
                <div class="exam-grid">
                    <?php while ($exam = $result->fetch_assoc()): ?>
                        <div class="card exam-card">
                            <div class="exam-details">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px; flex-wrap: wrap;">
                                    <h3 style="margin-bottom: 0; font-size: 20px; color: #ffffff;"><?php echo htmlspecialchars($exam["title"]); ?></h3>
                                    <?php if ($exam["status"] === "published"): ?>
                                        <span class="badge badge-success">Published</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Draft</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">
                                    <?php echo htmlspecialchars($exam["description"] ?: "No description provided."); ?>
                                </p>
                                
                                <div class="exam-meta">
                                    <span>⏱️ <strong>Duration:</strong> <?php echo $exam["duration"]; ?> mins</span>
                                    <span>🎯 <strong>Total Marks:</strong> <?php echo $exam["total_marks"]; ?></span>
                                    <span>👤 <strong>By:</strong> <?php echo htmlspecialchars($exam["creator_name"]); ?></span>
                                </div>
                            </div>
                            
                            <div class="exam-actions">
                                <a href="questions.php?exam_id=<?php echo $exam["id"]; ?>" class="btn btn-primary" style="font-size: 13px; padding: 10px;">
                                    <span>❓</span> Questions
                                </a>
                                <div style="display: flex; gap: 8px; width: 100%;">
                                    <a href="edit_exam.php?id=<?php echo $exam["id"]; ?>" class="btn btn-secondary" style="flex-grow: 1; font-size: 13px; padding: 10px;">
                                        Edit
                                    </a>
                                    <a 
                                        href="delete_exam.php?id=<?php echo $exam["id"]; ?>" 
                                        class="btn btn-danger" 
                                        style="flex-grow: 1; font-size: 13px; padding: 10px;"
                                        onclick="return confirm('Are you sure you want to delete this exam? All related questions and student attempt data will be deleted.');"
                                    >
                                        Delete
                                    </a>
                                </div>
                                <a 
                                    href="toggle_exam_status.php?id=<?php echo $exam["id"]; ?>" 
                                    class="btn btn-secondary" 
                                    style="width: 100%; font-size: 13px; padding: 10px; border-color: rgba(255,255,255,0.15);"
                                    onclick="return confirm('Change status of this exam?');"
                                >
                                    <?php echo $exam["status"] === "draft" ? "🚀 Publish Exam" : "🔒 Unpublish (Draft)"; ?>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 50px;">
                    <div style="font-size: 48px; margin-bottom: 20px;">📝</div>
                    <h3 style="margin-bottom: 10px;">No Exams Found</h3>
                    <p style="color: var(--text-muted); margin-bottom: 25px;">You haven't created any exams yet. Start by creating your first exam.</p>
                    <a href="create_exam.php" class="btn btn-primary">Create New Exam</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>