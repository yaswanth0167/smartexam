-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jul 26, 2026 at 08:13 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smartexam_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_answers`
--

CREATE TABLE `ai_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_answer` char(1) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_attempts`
--

CREATE TABLE `ai_attempts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `ai_exam_id` int(11) NOT NULL,
  `score` int(11) DEFAULT 0,
  `total_marks` int(11) DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `correct_answers` int(11) DEFAULT 0,
  `wrong_answers` int(11) DEFAULT 0,
  `unanswered` int(11) DEFAULT 0,
  `started_at` datetime NOT NULL,
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_exams`
--

CREATE TABLE `ai_exams` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `education_level` varchar(100) NOT NULL,
  `exam_type` varchar(100) NOT NULL,
  `stream` varchar(100) DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `question_count` int(11) NOT NULL,
  `difficulty` enum('easy','medium','hard') NOT NULL,
  `question_type` varchar(50) NOT NULL,
  `duration` int(11) NOT NULL,
  `mode` enum('mock','practice','speed','weak_topic','challenge') DEFAULT 'mock',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_questions`
--

CREATE TABLE `ai_questions` (
  `id` int(11) NOT NULL,
  `ai_exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` text NOT NULL,
  `option_b` text NOT NULL,
  `option_c` text NOT NULL,
  `option_d` text NOT NULL,
  `correct_answer` char(1) NOT NULL,
  `explanation` text DEFAULT NULL,
  `marks` int(11) DEFAULT 1,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option` enum('A','B','C','D') DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attempts`
--

CREATE TABLE `attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `room_exam_id` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT 0,
  `total_questions` int(11) DEFAULT 0,
  `correct_answers` int(11) DEFAULT 0,
  `started_at` datetime DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `total_marks` int(11) DEFAULT 0,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `title`, `description`, `duration`, `total_marks`, `status`, `created_by`, `created_at`) VALUES
(5, 'GK', 'ANSWER IT', 3, 3, 'published', 10, '2026-07-25 12:54:25');

-- --------------------------------------------------------

--
-- Table structure for table `login_otps`
--

CREATE TABLE `login_otps` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_answer` char(1) NOT NULL,
  `marks` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `marks`) VALUES
(8, 5, 'wHO IS AP CM?', 'PAWAN KALYAN', 'NAGARJUNA', 'CHANDRA BABU NAIDU', 'JAGAN', 'C', 1),
(9, 5, 'WHO IS DCM?', 'PAWAN KALYAN', 'NAGARJUNA', 'CHANDRA BABU NAIDU', 'CHIRANJEEVI', 'A', 1),
(10, 5, 'WHO IS RC?', 'ACTOR', 'POLITICIAN', 'DANCER', 'PLAYER', 'A', 1);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_code` varchar(20) NOT NULL,
  `room_name` varchar(255) NOT NULL,
  `exam_type` enum('normal','ai') NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `ai_exam_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('scheduled','active','closed') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_answers`
--

CREATE TABLE `room_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_answers`
--

INSERT INTO `room_answers` (`id`, `attempt_id`, `question_id`, `answer`) VALUES
(1, 1, 11, 'B'),
(2, 1, 12, 'B'),
(3, 1, 13, 'B'),
(4, 1, 14, 'B'),
(5, 1, 15, 'B'),
(6, 1, 16, 'B'),
(7, 1, 17, 'B'),
(8, 1, 18, 'B'),
(9, 1, 19, 'B'),
(10, 1, 20, 'B'),
(11, 3, 21, 'A'),
(12, 3, 22, 'B'),
(13, 3, 23, 'B'),
(14, 3, 24, 'B'),
(15, 3, 25, 'B');

-- --------------------------------------------------------

--
-- Table structure for table `room_attempts`
--

CREATE TABLE `room_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_exam_id` int(11) NOT NULL,
  `score` int(11) DEFAULT 0,
  `total_questions` int(11) DEFAULT 0,
  `correct_answers` int(11) DEFAULT 0,
  `started_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_attempts`
--

INSERT INTO `room_attempts` (`id`, `user_id`, `room_exam_id`, `score`, `total_questions`, `correct_answers`, `started_at`, `submitted_at`) VALUES
(1, 16, 2, 8, 10, 8, NULL, '2026-07-26 16:44:46'),
(2, 16, 3, 0, 0, 0, '2026-07-26 16:55:14', NULL),
(3, 16, 3, 2, 5, 2, NULL, '2026-07-26 16:55:32');

-- --------------------------------------------------------

--
-- Table structure for table `room_exams`
--

CREATE TABLE `room_exams` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_questions`
--

CREATE TABLE `room_questions` (
  `id` int(11) NOT NULL,
  `room_exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` text NOT NULL,
  `option_b` text NOT NULL,
  `option_c` text DEFAULT NULL,
  `option_d` text DEFAULT NULL,
  `correct_answer` varchar(10) NOT NULL,
  `explanation` text DEFAULT NULL,
  `marks` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `is_verified`, `otp`, `otp_expiry`) VALUES
(10, 'System Administrator', 'smartexam0167@gmail.com', '$2y$10$CeTbrev/TknvQYYXrdQKcO2tmEY1OTYODvUKLZgJFa.fK4To3YOX.', 'admin', '2026-07-18 16:58:03', 0, NULL, NULL),
(17, 'Yaswanth Kumar Bandi', 'yaswanthkumarbandi2007@gmail.com', '$2y$10$qQpi6/hL7eLeVKoWDJDDiuhgtsp/ggw8vtQNmaM.tCWY1cC/AudqC', 'student', '2026-07-26 18:04:01', 1, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_answers`
--
ALTER TABLE `ai_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `ai_attempts`
--
ALTER TABLE `ai_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `ai_exam_id` (`ai_exam_id`);

--
-- Indexes for table `ai_exams`
--
ALTER TABLE `ai_exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `ai_questions`
--
ALTER TABLE `ai_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_exam_id` (`ai_exam_id`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `attempts`
--
ALTER TABLE `attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `login_otps`
--
ALTER TABLE `login_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_code` (`room_code`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indexes for table `room_answers`
--
ALTER TABLE `room_answers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_attempts`
--
ALTER TABLE `room_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_exams`
--
ALTER TABLE `room_exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `room_questions`
--
ALTER TABLE `room_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_exam_id` (`room_exam_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_answers`
--
ALTER TABLE `ai_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `ai_attempts`
--
ALTER TABLE `ai_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `ai_exams`
--
ALTER TABLE `ai_exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `ai_questions`
--
ALTER TABLE `ai_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `attempts`
--
ALTER TABLE `attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_otps`
--
ALTER TABLE `login_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `room_answers`
--
ALTER TABLE `room_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `room_attempts`
--
ALTER TABLE `room_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `room_exams`
--
ALTER TABLE `room_exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `room_questions`
--
ALTER TABLE `room_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_answers`
--
ALTER TABLE `ai_answers`
  ADD CONSTRAINT `ai_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `ai_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `ai_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_attempts`
--
ALTER TABLE `ai_attempts`
  ADD CONSTRAINT `ai_attempts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_attempts_ibfk_2` FOREIGN KEY (`ai_exam_id`) REFERENCES `ai_exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_exams`
--
ALTER TABLE `ai_exams`
  ADD CONSTRAINT `ai_exams_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_questions`
--
ALTER TABLE `ai_questions`
  ADD CONSTRAINT `ai_questions_ibfk_1` FOREIGN KEY (`ai_exam_id`) REFERENCES `ai_exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attempts`
--
ALTER TABLE `attempts`
  ADD CONSTRAINT `attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_otps`
--
ALTER TABLE `login_otps`
  ADD CONSTRAINT `login_otps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_exams`
--
ALTER TABLE `room_exams`
  ADD CONSTRAINT `room_exams_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_questions`
--
ALTER TABLE `room_questions`
  ADD CONSTRAINT `room_questions_ibfk_1` FOREIGN KEY (`room_exam_id`) REFERENCES `room_exams` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
