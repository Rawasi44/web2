-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 09, 2025 at 08:26 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edulearndb`
--

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE `quiz` (
  `id` int(11) NOT NULL,
  `educatorID` int(11) NOT NULL,
  `topicID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `quiz`
--

INSERT INTO `quiz` (`id`, `educatorID`, `topicID`) VALUES
(18, 24, 1),
(19, 25, 1),
(20, 25, 2),
(21, 25, 3);

-- --------------------------------------------------------

--
-- Table structure for table `quizfeedback`
--

CREATE TABLE `quizfeedback` (
  `id` int(11) NOT NULL,
  `quizID` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comments` text,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `learnerID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `quizfeedback`
--

INSERT INTO `quizfeedback` (`id`, `quizID`, `rating`, `comments`, `date`, `learnerID`) VALUES
(1, 21, 3, 'Good', '2025-11-09 21:34:56', 23),
(2, 18, 2, 'not good', '2025-11-09 21:38:45', 22),
(3, 19, 5, 'amazing', '2025-11-09 21:39:55', 22),
(4, 20, 5, 'good', '2025-11-09 23:14:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quizquestion`
--

CREATE TABLE `quizquestion` (
  `id` int(11) NOT NULL,
  `quizID` int(11) NOT NULL,
  `question` text NOT NULL,
  `questionFigureFileName` varchar(255) DEFAULT NULL,
  `answerA` varchar(255) NOT NULL,
  `answerB` varchar(255) NOT NULL,
  `answerC` varchar(255) NOT NULL,
  `answerD` varchar(255) NOT NULL,
  `correctAnswer` enum('A','B','C','D') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `quizquestion`
--

INSERT INTO `quizquestion` (`id`, `quizID`, `question`, `questionFigureFileName`, `answerA`, `answerB`, `answerC`, `answerD`, `correctAnswer`) VALUES
(1, 19, 'Which HTML tag is used to create a hyperlink?', 'html.png', 'a', 'link', 'href', 'nav', 'A'),
(2, 19, 'How do we apply the same CSS style to multiple elements with the same class?', NULL, '#className { ... }', '.className { ... }', 'className { ... }', '*className { ... }', 'B'),
(4, 20, 'Which clause is used to filter records in a SELECT query?', NULL, 'WHERE', 'ORDER BY', 'GROUP BY', 'ON', 'A'),
(5, 20, 'Which key ensures that values are unique and not NULL?', NULL, ' FOREIGN KEY', 'PRIMARY KEY', 'INDEX', 'UNIQUE KEY', 'B'),
(6, 21, 'Which keyword is used to declare a variable in JavaScript?', NULL, ' var', 'let', 'Both var and let', 'None', 'C'),
(7, 18, 'How do you center text in CSS?', NULL, 'text-align center;', 'align: middle;', 'center-text: yes;', 'margin-center: true;', 'A');

-- --------------------------------------------------------

--
-- Table structure for table `recommendedquestion`
--

CREATE TABLE `recommendedquestion` (
  `id` int(11) NOT NULL,
  `quizID` int(11) NOT NULL,
  `learnerID` int(11) NOT NULL,
  `question` text NOT NULL,
  `questionFigureFileName` varchar(255) DEFAULT NULL,
  `answerA` varchar(255) NOT NULL,
  `answerB` varchar(255) NOT NULL,
  `answerC` varchar(255) NOT NULL,
  `answerD` varchar(255) NOT NULL,
  `correctAnswer` enum('A','B','C','D') NOT NULL,
  `status` enum('pending','approved','disapproved') NOT NULL DEFAULT 'pending',
  `comments` text,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `recommendedquestion`
--

INSERT INTO `recommendedquestion` (`id`, `quizID`, `learnerID`, `question`, `questionFigureFileName`, `answerA`, `answerB`, `answerC`, `answerD`, `correctAnswer`, `status`, `comments`, `createdAt`) VALUES
(1, 18, 22, 'Which unit is relative to the parent font size?', 'uploads/html.png', 'px', 'em', 'cm', 'pt', 'B', 'pending', NULL, '2025-11-09 21:28:02'),
(2, 21, 23, 'Which function is used to convert a string to an integer?', 'uploads/link.png', 'parseInt()', 'stringToInt()', 'toNumber()', 'convert()', 'A', 'pending', NULL, '2025-11-09 21:33:07'),
(3, 18, 23, 'Which CSS property controls the text color?', 'uploads/html.png', 'font-style', 'text-color', 'color', 'background-color', 'C', 'pending', NULL, '2025-11-09 21:34:30');

-- --------------------------------------------------------

--
-- Table structure for table `takenquiz`
--

CREATE TABLE `takenquiz` (
  `id` int(11) NOT NULL,
  `quizID` int(11) NOT NULL,
  `learnerID` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `takenAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `takenquiz`
--

INSERT INTO `takenquiz` (`id`, `quizID`, `learnerID`, `score`, `takenAt`) VALUES
(1, 21, 23, 100, '2025-11-09 21:34:44'),
(2, 18, 22, 0, '2025-11-09 21:37:28'),
(3, 19, 22, 100, '2025-11-09 21:38:58');

-- --------------------------------------------------------

--
-- Table structure for table `topic`
--

CREATE TABLE `topic` (
  `id` int(11) NOT NULL,
  `topicName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `topic`
--

INSERT INTO `topic` (`id`, `topicName`) VALUES
(2, 'Databases'),
(1, 'HTML & CSS'),
(3, 'JavaScript');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(30) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `emailAddress` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photoFileName` varchar(255) NOT NULL,
  `userType` enum('learner','educator') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `firstName`, `lastName`, `emailAddress`, `password`, `photoFileName`, `userType`) VALUES
(22, 'Marah', 'Basel', 'marah@learner.com', '$2y$10$gAHychZRB16f6/mmxVz86eT0YS0Z80zKr4XlxqxsYpuIwZjrITJNq', 'images/default.png', 'learner'),
(23, 'Numayr', 'Almqhem', 'Numayr@learner.com', '$2y$10$duO4y2bh///M0EW9G9P1L.XKatn6SA5xN83rhOFvkwf2H.SNLxVbu', '1.jpg', 'learner'),
(24, 'Rawasi', 'Almutairi', 'Rawasi@educator.com', '$2y$10$RQz0D1/RJk/9cYq/KM5LGOmnF4yIkJ1Q2DwL1Q2KxkV1Q5T1hznI.', '2.jpg', 'educator'),
(25, 'Afnan', 'Saher', 'Afnan@educator.com', '$2y$10$Y57E09Mu6mH5JKyH9LcHSeypwVol6l0kzhktjEOcUmTNUmTj4a1fu', 'images/default.png', 'educator');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_educator_topic` (`educatorID`,`topicID`),
  ADD KEY `fk_quiz_topic` (`topicID`);

--
-- Indexes for table `quizfeedback`
--
ALTER TABLE `quizfeedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_feedback_learner` (`learnerID`),
  ADD KEY `idx_feedback_quiz` (`quizID`);

--
-- Indexes for table `quizquestion`
--
ALTER TABLE `quizquestion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quizID` (`quizID`);

--
-- Indexes for table `recommendedquestion`
--
ALTER TABLE `recommendedquestion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reco_learner` (`learnerID`),
  ADD KEY `idx_reco_quiz` (`quizID`),
  ADD KEY `idx_reco_status` (`status`);

--
-- Indexes for table `takenquiz`
--
ALTER TABLE `takenquiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_taken_learner` (`learnerID`),
  ADD KEY `idx_quiz_learner` (`quizID`,`learnerID`);

--
-- Indexes for table `topic`
--
ALTER TABLE `topic`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `topicName` (`topicName`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`emailAddress`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `quizfeedback`
--
ALTER TABLE `quizfeedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quizquestion`
--
ALTER TABLE `quizquestion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `recommendedquestion`
--
ALTER TABLE `recommendedquestion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `takenquiz`
--
ALTER TABLE `takenquiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `topic`
--
ALTER TABLE `topic`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `fk_quiz_educator` FOREIGN KEY (`educatorID`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_quiz_topic` FOREIGN KEY (`topicID`) REFERENCES `topic` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizfeedback`
--
ALTER TABLE `quizfeedback`
  ADD CONSTRAINT `fk_feedback_learner` FOREIGN KEY (`learnerID`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_feedback_quiz` FOREIGN KEY (`quizID`) REFERENCES `quiz` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizquestion`
--
ALTER TABLE `quizquestion`
  ADD CONSTRAINT `fk_question_quiz` FOREIGN KEY (`quizID`) REFERENCES `quiz` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recommendedquestion`
--
ALTER TABLE `recommendedquestion`
  ADD CONSTRAINT `fk_reco_learner` FOREIGN KEY (`learnerID`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reco_quiz` FOREIGN KEY (`quizID`) REFERENCES `quiz` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `takenquiz`
--
ALTER TABLE `takenquiz`
  ADD CONSTRAINT `fk_taken_learner` FOREIGN KEY (`learnerID`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_taken_quiz` FOREIGN KEY (`quizID`) REFERENCES `quiz` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
