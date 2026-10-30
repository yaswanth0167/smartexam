<?php

require_once "../includes/auth.php";

require_student();

$student_name = $_SESSION["user_name"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>AI Exam Generator - SmartExam</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .generator-container {

            max-width: 900px;

            margin: 0 auto;

        }

        .generator-header {

            margin-bottom: 30px;

        }

        .generator-header h1 {

            font-size: 32px;

            margin-bottom: 10px;

        }

        .generator-header p {

            color: var(--text-muted);

        }

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(280px, 1fr)
                );

            gap: 20px;

        }

        .full-width {

            grid-column: 1 / -1;

        }

        .ai-banner {

            padding: 25px;

            margin-bottom: 25px;

            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    rgba(99, 102, 241, 0.18),
                    rgba(168, 85, 247, 0.12)
                );

            border:
                1px solid
                rgba(139, 92, 246, 0.3);

        }

        .ai-banner h2 {

            margin-bottom: 8px;

        }

        .ai-banner p {

            color: var(--text-muted);

            margin-bottom: 0;

        }

        .form-control {

            width: 100%;

        }

        .hidden {

            display: none;

        }

        .generate-button {

            margin-top: 25px;

            width: 100%;

            font-size: 16px;

            padding: 14px;

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
                    href="dashboard.php#exams"
                    class="sidebar-link"
                >

                    <span>▣</span>

                    Available Exams

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
                    class="sidebar-link active"
                >

                    <span>🤖</span>

                    AI Exam Generator

                </a>


            </nav>


            <div class="sidebar-footer">


                <a
                    href="../logout.php"
                    class="btn btn-danger"
                    style="width: 100%;"
                >

                    <span>🚪</span>

                    Logout

                </a>


            </div>


        </aside>


        <!-- MAIN CONTENT -->

        <main class="main-content">


            <div class="generator-container">


                <!-- HEADER -->

                <div class="generator-header">


                    <h1>

                        🤖 AI Exam Generator

                    </h1>


                    <p>

                        Create a personalized examination
                        powered by Artificial Intelligence.

                    </p>


                </div>


                <!-- AI BANNER -->

                <div class="ai-banner">


                    <h2>

                        🧠 Build Your Personalized Exam

                    </h2>


                    <p>

                        Select your education level, subject,
                        topic, difficulty, and exam preferences.
                        SmartExam AI will generate a personalized
                        examination for you.

                    </p>


                </div>


                <!-- FORM CARD -->

                <div class="card">


                    <form
                        method="POST"
                        action="generate_ai_exam.php"
                    >


                        <div class="form-grid">


                            <!-- EDUCATION LEVEL -->

                            <div class="form-group">


                                <label
                                    class="form-label"
                                    for="education_level"
                                >

                                    Education Level

                                </label>


                                <select
                                    name="education_level"
                                    id="education_level"
                                    class="form-control"
                                    required
                                >

                                    <option value="">

                                        Select Education Level

                                    </option>


                                    <option value="school">

                                        School

                                    </option>


                                    <option value="intermediate">

                                        Intermediate

                                    </option>


                                    <option value="entrance">

                                        Entrance Exam

                                    </option>


                                    <option value="competitive">

                                        Competitive Exam

                                    </option>


                                    <option value="engineering">

                                        Engineering

                                    </option>


                                    <option value="job">

                                        Job-Oriented Exam

                                    </option>


                                </select>


                            </div>


                            <!-- EXAM TYPE -->

                            <div class="form-group">


                                <label
                                    class="form-label"
                                    for="exam_type"
                                >

                                    Exam Type

                                </label>


                                <select
                                    name="exam_type"
                                    id="exam_type"
                                    class="form-control"
                                    required
                                >

                                    <option value="">

                                        Select Exam Type

                                    </option>


                                    <option value="school_exam">

                                        School Examination

                                    </option>


                                    <option value="intermediate_exam">

                                        Intermediate Examination

                                    </option>


                                    <option value="entrance_exam">

                                        Entrance Examination

                                    </option>


                                    <option value="competitive_exam">

                                        Competitive Examination

                                    </option>


                                    <option value="technical_interview">

                                        Technical Interview Preparation

                                    </option>


                                </select>


                            </div>


                            <!-- STREAM -->

                            <div class="form-group">


                                <label
                                    class="form-label"
                                    for="stream"
                                >

                                    Stream / Branch

                                </label>


                                <select
                                    name="stream"
                                    id="stream"
                                    class="form-control"
                                >

                                    <option value="">

                                        Select Stream / Branch

                                    </option>


                                    <option value="MPC">

                                        MPC

                                    </option>


                                    <option value="BiPC">

                                        BiPC

                                    </option>


                                    <option value="MEC">

                                        MEC

                                    </option>


                                    <option value="CEC">

                                        CEC

                                    </option>


                                    <option value="CSE">

                                        CSE

                                    </option>


                                    <option value="AI and Data Science">

                                        AI and Data Science

                                    </option>


                                    <option value="ECE">

                                        ECE

                                    </option>


                                    <option value="EEE">

                                        EEE

                                    </option>


                                    <option value="Mechanical">

                                        Mechanical

                                    </option>


                                    <option value="Civil">

                                        Civil

                                    </option>


                                </select>


                            </div>


                            <!-- SUBJECT -->

                            <div class="form-group">


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
                                    placeholder="Example: Physics, Python, DBMS"
                                    required
                                >


                            </div>


                            <!-- TOPIC -->

                            <div class="form-group full-width">


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
                                    placeholder="Example: Electrostatics, SQL Joins, Machine Learning"
                                    required
                                >


                            </div>


                            <!-- NUMBER OF QUESTIONS -->

                            <div class="form-group">


                                <label
                                    class="form-label"
                                    for="question_count"
                                >

                                    Number of Questions

                                </label>


                                <select
                                    name="question_count"
                                    id="question_count"
                                    class="form-control"
                                    required
                                >

                                    <option value="5">

                                        5 Questions

                                    </option>


                                    <option value="10">

                                        10 Questions

                                    </option>


                                    <option value="15">

                                        15 Questions

                                    </option>


                                    <option value="20">

                                        20 Questions

                                    </option>


                                    <option value="30">

                                        30 Questions

                                    </option>


                                </select>


                            </div>


                            <!-- DIFFICULTY -->

                            <div class="form-group">


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

                                    <option value="easy">

                                        Easy

                                    </option>


                                    <option
                                        value="medium"
                                        selected
                                    >

                                        Medium

                                    </option>


                                    <option value="hard">

                                        Hard

                                    </option>


                                </select>


                            </div>


                            <!-- QUESTION TYPE -->

                            <div class="form-group">


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

                                    <option value="mcq">

                                        Multiple Choice Questions

                                    </option>


                                    <option value="true_false">

                                        True / False

                                    </option>


                                </select>


                            </div>


                            <!-- DURATION -->

                            <div class="form-group">


                                <label
                                    class="form-label"
                                    for="duration"
                                >

                                    Exam Duration

                                </label>


                                <select
                                    name="duration"
                                    id="duration"
                                    class="form-control"
                                    required
                                >

                                    <option value="10">

                                        10 Minutes

                                    </option>


                                    <option value="15">

                                        15 Minutes

                                    </option>


                                    <option value="30">

                                        30 Minutes

                                    </option>


                                    <option value="45">

                                        45 Minutes

                                    </option>


                                    <option value="60">

                                        60 Minutes

                                    </option>


                                    <option value="90">

                                        90 Minutes

                                    </option>


                                </select>


                            </div>


                            <!-- EXAM MODE -->

                            <div class="form-group full-width">


                                <label
                                    class="form-label"
                                    for="mode"
                                >

                                    Exam Mode

                                </label>


                                <select
                                    name="mode"
                                    id="mode"
                                    class="form-control"
                                    required
                                >

                                    <option value="mock">

                                        📝 Mock Test Mode

                                    </option>


                                    <option value="practice">

                                        📚 Practice Mode

                                    </option>


                                    <option value="speed">

                                        ⚡ Speed Test Mode

                                    </option>


                                    <option value="weak_topic">

                                        🎯 Weak Topic Mode

                                    </option>


                                    <option value="challenge">

                                        🔥 Challenge Mode

                                    </option>


                                </select>


                            </div>
</div>


                        <!-- GENERATE BUTTON -->

                        <button
                            type="submit"
                            class="btn btn-primary generate-button"
                        >

                            🤖 Generate AI Exam

                        </button>


                    </form>


                </div>


            </div>


        </main>


    </div>


</body>

</html>