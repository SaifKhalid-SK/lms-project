-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2026 at 12:21 PM
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
-- Database: `lms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin123');

-- --------------------------------------------------------

--
-- Table structure for table `all_usernames`
--

CREATE TABLE `all_usernames` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `all_usernames`
--

INSERT INTO `all_usernames` (`id`, `username`, `role`) VALUES
(1, 'admin', 'admin'),
(5, 'Saif', 'student'),
(6, 'saif2', 'student'),
(7, 'teacher1', 'teacher'),
(8, 'teacher2', 'teacher'),
(9, 'teacher3', 'teacher'),
(10, 'teacher4', 'teacher'),
(11, 'teacher5', 'teacher'),
(12, 'teacher6', 'teacher'),
(13, 'Saif3', 'student');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `subject_id`, `date`, `status`) VALUES
(3, 1, 16, '2026-04-21', 'Absent');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'Computer Science'),
(2, 'Business Administration'),
(3, 'Electrical Engineering');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `subject_id`) VALUES
(1, 1, 13),
(2, 1, 14),
(3, 1, 15),
(4, 1, 16);

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Paid','Unpaid','Pending') NOT NULL DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `student_id`, `description`, `amount`, `due_date`, `status`) VALUES
(1, 1, 'fee spring 2025', 20000.00, '2026-09-22', 'Paid'),
(2, 1, 'fee spring 2025', 20000.00, '2026-09-22', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `type` enum('Quiz','Assignment') NOT NULL,
  `number` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `type`, `number`, `marks_obtained`, `total_marks`) VALUES
(3, 1, 16, 'Quiz', 1, 10.00, 20.00);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `semester` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `department`, `semester`, `teacher_id`) VALUES
(1, 'Programming Fundamentals', 'Computer Science', 1, 1),
(2, 'Introduction to Computing', 'Computer Science', 1, 1),
(3, 'Calculus & Analytical Geometry', 'Computer Science', 1, 1),
(4, 'English Composition', 'Computer Science', 1, 1),
(5, 'Object Oriented Programming', 'Computer Science', 2, 1),
(6, 'Discrete Mathematics', 'Computer Science', 2, 1),
(7, 'Digital Logic Design', 'Computer Science', 2, 1),
(8, 'Communication Skills', 'Computer Science', 2, 1),
(9, 'Data Structures', 'Computer Science', 3, 1),
(10, 'Database Systems', 'Computer Science', 3, 1),
(11, 'Linear Algebra', 'Computer Science', 3, 1),
(12, 'Web Technologies', 'Computer Science', 3, 1),
(13, 'Operating Systems', 'Computer Science', 4, 1),
(14, 'Computer Networks', 'Computer Science', 4, 1),
(15, 'Software Engineering', 'Computer Science', 4, 1),
(16, 'Artificial Intelligence', 'Computer Science', 4, 1),
(17, 'Computer Architecture', 'Computer Science', 5, 2),
(18, 'Theory of Automata', 'Computer Science', 5, 2),
(19, 'Information Security', 'Computer Science', 5, 2),
(20, 'Mobile App Development', 'Computer Science', 5, 2),
(21, 'Compiler Construction', 'Computer Science', 6, 2),
(22, 'Machine Learning', 'Computer Science', 6, 2),
(23, 'Cloud Computing', 'Computer Science', 6, 2),
(24, 'Human Computer Interaction', 'Computer Science', 6, 2),
(25, 'Final Year Project I', 'Computer Science', 7, 2),
(26, 'Deep Learning', 'Computer Science', 7, 2),
(27, 'Big Data Analytics', 'Computer Science', 7, 2),
(28, 'Blockchain Technology', 'Computer Science', 7, 2),
(29, 'Final Year Project II', 'Computer Science', 8, 2),
(30, 'Software Project Management', 'Computer Science', 8, 2),
(31, 'Professional Ethics in IT', 'Computer Science', 8, 2),
(32, 'Enterprise Resource Planning', 'Computer Science', 8, 2),
(33, 'Principles of Management', 'Business Administration', 1, 3),
(34, 'Introduction to Business', 'Business Administration', 1, 3),
(35, 'Business Mathematics', 'Business Administration', 1, 3),
(36, 'English Communication', 'Business Administration', 1, 3),
(37, 'Financial Accounting', 'Business Administration', 2, 3),
(38, 'Microeconomics', 'Business Administration', 2, 3),
(39, 'Business Statistics', 'Business Administration', 2, 3),
(40, 'Business Communication', 'Business Administration', 2, 3),
(41, 'Marketing Management', 'Business Administration', 3, 3),
(42, 'Macroeconomics', 'Business Administration', 3, 3),
(43, 'Cost Accounting', 'Business Administration', 3, 3),
(44, 'Organizational Behavior', 'Business Administration', 3, 3),
(45, 'Human Resource Management', 'Business Administration', 4, 3),
(46, 'Financial Management', 'Business Administration', 4, 3),
(47, 'Business Law', 'Business Administration', 4, 3),
(48, 'Research Methods', 'Business Administration', 4, 3),
(49, 'Strategic Management', 'Business Administration', 5, 4),
(50, 'International Business', 'Business Administration', 5, 4),
(51, 'Operations Management', 'Business Administration', 5, 4),
(52, 'Entrepreneurship', 'Business Administration', 5, 4),
(53, 'Investment Analysis', 'Business Administration', 6, 4),
(54, 'Supply Chain Management', 'Business Administration', 6, 4),
(55, 'Digital Marketing', 'Business Administration', 6, 4),
(56, 'Corporate Finance', 'Business Administration', 6, 4),
(57, 'Final Year Project I', 'Business Administration', 7, 4),
(58, 'Mergers and Acquisitions', 'Business Administration', 7, 4),
(59, 'Business Ethics', 'Business Administration', 7, 4),
(60, 'Project Management', 'Business Administration', 7, 4),
(61, 'Final Year Project II', 'Business Administration', 8, 4),
(62, 'Leadership and Management', 'Business Administration', 8, 4),
(63, 'Global Financial Markets', 'Business Administration', 8, 4),
(64, 'Management Information Systems', 'Business Administration', 8, 4),
(65, 'Circuit Analysis', 'Electrical Engineering', 1, 5),
(66, 'Engineering Mathematics I', 'Electrical Engineering', 1, 5),
(67, 'Applied Physics', 'Electrical Engineering', 1, 5),
(68, 'Engineering Drawing', 'Electrical Engineering', 1, 5),
(69, 'Digital Logic Design', 'Electrical Engineering', 2, 5),
(70, 'Engineering Mathematics II', 'Electrical Engineering', 2, 5),
(71, 'Electronic Devices', 'Electrical Engineering', 2, 5),
(72, 'Programming for Engineers', 'Electrical Engineering', 2, 5),
(73, 'Signals and Systems', 'Electrical Engineering', 3, 5),
(74, 'Electromagnetic Theory', 'Electrical Engineering', 3, 5),
(75, 'Linear Control Systems', 'Electrical Engineering', 3, 5),
(76, 'Electrical Machines I', 'Electrical Engineering', 3, 5),
(77, 'Microprocessors', 'Electrical Engineering', 4, 5),
(78, 'Control Systems', 'Electrical Engineering', 4, 5),
(79, 'Power Electronics', 'Electrical Engineering', 4, 5),
(80, 'Communication Systems', 'Electrical Engineering', 4, 5),
(81, 'Digital Signal Processing', 'Electrical Engineering', 5, 6),
(82, 'Power Systems', 'Electrical Engineering', 5, 6),
(83, 'Embedded Systems', 'Electrical Engineering', 5, 6),
(84, 'Wireless Communications', 'Electrical Engineering', 5, 6),
(85, 'Electrical Machines II', 'Electrical Engineering', 6, 6),
(86, 'VLSI Design', 'Electrical Engineering', 6, 6),
(87, 'Renewable Energy Systems', 'Electrical Engineering', 6, 6),
(88, 'Antenna and Wave Propagation', 'Electrical Engineering', 6, 6),
(89, 'Final Year Project I', 'Electrical Engineering', 7, 6),
(90, 'Smart Grid Technology', 'Electrical Engineering', 7, 6),
(91, 'Internet of Things', 'Electrical Engineering', 7, 6),
(92, 'Robotics and Automation', 'Electrical Engineering', 7, 6),
(93, 'Final Year Project II', 'Electrical Engineering', 8, 6),
(94, 'High Voltage Engineering', 'Electrical Engineering', 8, 6),
(95, 'Power System Protection', 'Electrical Engineering', 8, 6),
(96, 'Professional Engineering Ethics', 'Electrical Engineering', 8, 6);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `department` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_online` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `full_name`, `email`, `phone`, `department`, `username`, `password`, `is_online`, `created_at`) VALUES
(1, 'Dr. Usman Ali', 'usman.ali@lms.com', '03001234567', 'Computer Science', 'teacher1', 'teach123', 0, '2026-04-21 08:13:31'),
(2, 'Dr. Kamran Malik', 'kamran.malik@lms.com', '03009876543', 'Computer Science', 'teacher2', 'teach123', 0, '2026-04-21 08:13:31'),
(3, 'Dr. Sara Khan', 'sara.khan@lms.com', '03011234567', 'Business Administration', 'teacher3', 'teach123', 0, '2026-04-21 08:13:31'),
(4, 'Dr. Nadia Hussain', 'nadia.hussain@lms.com', '03019876543', 'Business Administration', 'teacher4', 'teach123', 0, '2026-04-21 08:13:31'),
(5, 'Dr. Bilal Ahmed', 'bilal.ahmed@lms.com', '03021234567', 'Electrical Engineering', 'teacher5', 'teach123', 0, '2026-04-21 08:13:31'),
(6, 'Dr. Tariq Mehmood', 'tariq.mehmood@lms.com', '03029876543', 'Electrical Engineering', 'teacher6', 'teach123', 0, '2026-04-21 08:13:31');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `subject_id`, `day`, `time_start`, `time_end`) VALUES
(1, 1, 'Monday', '08:00:00', '09:30:00'),
(2, 5, 'Monday', '10:00:00', '11:30:00'),
(3, 9, 'Monday', '12:00:00', '13:30:00'),
(4, 13, 'Monday', '14:00:00', '15:30:00'),
(5, 2, 'Tuesday', '08:00:00', '09:30:00'),
(6, 6, 'Tuesday', '10:00:00', '11:30:00'),
(7, 10, 'Tuesday', '12:00:00', '13:30:00'),
(8, 14, 'Tuesday', '14:00:00', '15:30:00'),
(9, 3, 'Wednesday', '08:00:00', '09:30:00'),
(10, 7, 'Wednesday', '10:00:00', '11:30:00'),
(11, 11, 'Wednesday', '12:00:00', '13:30:00'),
(12, 15, 'Wednesday', '14:00:00', '15:30:00'),
(13, 4, 'Thursday', '08:00:00', '09:30:00'),
(14, 8, 'Thursday', '10:00:00', '11:30:00'),
(15, 12, 'Thursday', '12:00:00', '13:30:00'),
(16, 16, 'Thursday', '14:00:00', '15:30:00'),
(17, 1, 'Friday', '08:00:00', '09:30:00'),
(18, 9, 'Friday', '10:00:00', '11:30:00'),
(19, 13, 'Friday', '12:00:00', '13:30:00'),
(20, 5, 'Friday', '14:00:00', '15:30:00'),
(21, 17, 'Monday', '08:00:00', '09:30:00'),
(22, 21, 'Monday', '10:00:00', '11:30:00'),
(23, 25, 'Monday', '12:00:00', '13:30:00'),
(24, 29, 'Monday', '14:00:00', '15:30:00'),
(25, 18, 'Tuesday', '08:00:00', '09:30:00'),
(26, 22, 'Tuesday', '10:00:00', '11:30:00'),
(27, 26, 'Tuesday', '12:00:00', '13:30:00'),
(28, 30, 'Tuesday', '14:00:00', '15:30:00'),
(29, 19, 'Wednesday', '08:00:00', '09:30:00'),
(30, 23, 'Wednesday', '10:00:00', '11:30:00'),
(31, 27, 'Wednesday', '12:00:00', '13:30:00'),
(32, 31, 'Wednesday', '14:00:00', '15:30:00'),
(33, 20, 'Thursday', '08:00:00', '09:30:00'),
(34, 24, 'Thursday', '10:00:00', '11:30:00'),
(35, 28, 'Thursday', '12:00:00', '13:30:00'),
(36, 32, 'Thursday', '14:00:00', '15:30:00'),
(37, 17, 'Friday', '08:00:00', '09:30:00'),
(38, 21, 'Friday', '10:00:00', '11:30:00'),
(39, 25, 'Friday', '12:00:00', '13:30:00'),
(40, 29, 'Friday', '14:00:00', '15:30:00'),
(41, 33, 'Monday', '08:00:00', '09:30:00'),
(42, 37, 'Monday', '10:00:00', '11:30:00'),
(43, 41, 'Monday', '12:00:00', '13:30:00'),
(44, 45, 'Monday', '14:00:00', '15:30:00'),
(45, 34, 'Tuesday', '08:00:00', '09:30:00'),
(46, 38, 'Tuesday', '10:00:00', '11:30:00'),
(47, 42, 'Tuesday', '12:00:00', '13:30:00'),
(48, 46, 'Tuesday', '14:00:00', '15:30:00'),
(49, 35, 'Wednesday', '08:00:00', '09:30:00'),
(50, 39, 'Wednesday', '10:00:00', '11:30:00'),
(51, 43, 'Wednesday', '12:00:00', '13:30:00'),
(52, 47, 'Wednesday', '14:00:00', '15:30:00'),
(53, 36, 'Thursday', '08:00:00', '09:30:00'),
(54, 40, 'Thursday', '10:00:00', '11:30:00'),
(55, 44, 'Thursday', '12:00:00', '13:30:00'),
(56, 48, 'Thursday', '14:00:00', '15:30:00'),
(57, 33, 'Friday', '08:00:00', '09:30:00'),
(58, 41, 'Friday', '10:00:00', '11:30:00'),
(59, 45, 'Friday', '12:00:00', '13:30:00'),
(60, 37, 'Friday', '14:00:00', '15:30:00'),
(61, 49, 'Monday', '08:00:00', '09:30:00'),
(62, 53, 'Monday', '10:00:00', '11:30:00'),
(63, 57, 'Monday', '12:00:00', '13:30:00'),
(64, 61, 'Monday', '14:00:00', '15:30:00'),
(65, 50, 'Tuesday', '08:00:00', '09:30:00'),
(66, 54, 'Tuesday', '10:00:00', '11:30:00'),
(67, 58, 'Tuesday', '12:00:00', '13:30:00'),
(68, 62, 'Tuesday', '14:00:00', '15:30:00'),
(69, 51, 'Wednesday', '08:00:00', '09:30:00'),
(70, 55, 'Wednesday', '10:00:00', '11:30:00'),
(71, 59, 'Wednesday', '12:00:00', '13:30:00'),
(72, 63, 'Wednesday', '14:00:00', '15:30:00'),
(73, 52, 'Thursday', '08:00:00', '09:30:00'),
(74, 56, 'Thursday', '10:00:00', '11:30:00'),
(75, 60, 'Thursday', '12:00:00', '13:30:00'),
(76, 64, 'Thursday', '14:00:00', '15:30:00'),
(77, 49, 'Friday', '08:00:00', '09:30:00'),
(78, 53, 'Friday', '10:00:00', '11:30:00'),
(79, 57, 'Friday', '12:00:00', '13:30:00'),
(80, 61, 'Friday', '14:00:00', '15:30:00'),
(81, 65, 'Monday', '08:00:00', '09:30:00'),
(82, 69, 'Monday', '10:00:00', '11:30:00'),
(83, 73, 'Monday', '12:00:00', '13:30:00'),
(84, 77, 'Monday', '14:00:00', '15:30:00'),
(85, 66, 'Tuesday', '08:00:00', '09:30:00'),
(86, 70, 'Tuesday', '10:00:00', '11:30:00'),
(87, 74, 'Tuesday', '12:00:00', '13:30:00'),
(88, 78, 'Tuesday', '14:00:00', '15:30:00'),
(89, 67, 'Wednesday', '08:00:00', '09:30:00'),
(90, 71, 'Wednesday', '10:00:00', '11:30:00'),
(91, 75, 'Wednesday', '12:00:00', '13:30:00'),
(92, 79, 'Wednesday', '14:00:00', '15:30:00'),
(93, 68, 'Thursday', '08:00:00', '09:30:00'),
(94, 72, 'Thursday', '10:00:00', '11:30:00'),
(95, 76, 'Thursday', '12:00:00', '13:30:00'),
(96, 80, 'Thursday', '14:00:00', '15:30:00'),
(97, 65, 'Friday', '08:00:00', '09:30:00'),
(98, 73, 'Friday', '10:00:00', '11:30:00'),
(99, 77, 'Friday', '12:00:00', '13:30:00'),
(100, 69, 'Friday', '14:00:00', '15:30:00'),
(101, 81, 'Monday', '08:00:00', '09:30:00'),
(102, 85, 'Monday', '10:00:00', '11:30:00'),
(103, 89, 'Monday', '12:00:00', '13:30:00'),
(104, 93, 'Monday', '14:00:00', '15:30:00'),
(105, 82, 'Tuesday', '08:00:00', '09:30:00'),
(106, 86, 'Tuesday', '10:00:00', '11:30:00'),
(107, 90, 'Tuesday', '12:00:00', '13:30:00'),
(108, 94, 'Tuesday', '14:00:00', '15:30:00'),
(109, 83, 'Wednesday', '08:00:00', '09:30:00'),
(110, 87, 'Wednesday', '10:00:00', '11:30:00'),
(111, 91, 'Wednesday', '12:00:00', '13:30:00'),
(112, 95, 'Wednesday', '14:00:00', '15:30:00'),
(113, 84, 'Thursday', '08:00:00', '09:30:00'),
(114, 88, 'Thursday', '10:00:00', '11:30:00'),
(115, 92, 'Thursday', '12:00:00', '13:30:00'),
(116, 96, 'Thursday', '14:00:00', '15:30:00'),
(117, 81, 'Friday', '08:00:00', '09:30:00'),
(118, 85, 'Friday', '10:00:00', '11:30:00'),
(119, 89, 'Friday', '12:00:00', '13:30:00'),
(120, 93, 'Friday', '14:00:00', '15:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `reg_no` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `semester` int(11) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_online` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `reg_no`, `department`, `semester`, `dob`, `gender`, `username`, `password`, `is_online`, `created_at`) VALUES
(1, 'Saif', 'saifkhalid790@gmail.com', '03075559810', 'BCS243134', 'Computer Science', 4, '2004-09-03', 'Male', 'Saif', '000000', 0, '2026-04-20 19:48:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `all_usernames`
--
ALTER TABLE `all_usernames`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`subject_id`,`date`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_grade` (`student_id`,`subject_id`,`type`,`number`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reg_no` (`reg_no`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `all_usernames`
--
ALTER TABLE `all_usernames`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
