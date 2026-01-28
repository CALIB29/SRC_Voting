-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 22, 2026 at 02:04 AM
-- Server version: 10.1.25-MariaDB
-- PHP Version: 5.6.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `src_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_year`
--

CREATE TABLE `academic_year` (
  `ay_id` int(11) NOT NULL,
  `ay_name` varchar(50) NOT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `academic_year`
--

INSERT INTO `academic_year` (`ay_id`, `ay_name`, `date_start`, `date_end`) VALUES
(1, '2024-2025', '0000-00-00', '0000-00-00');

-- --------------------------------------------------------

--
-- Table structure for table `admission`
--

CREATE TABLE `admission` (
  `admission_id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `year_level_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Late','Absent') NOT NULL,
  `admission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `year` varchar(10) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `platform` text,
  `photo_path` varchar(255) DEFAULT NULL,
  `votes` int(11) NOT NULL DEFAULT '0',
  `election_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_id`, `course_code`, `course_name`, `department_id`) VALUES
(1, 'BSIS', 'Bachelor of Science in Information System', 1),
(2, 'BSAIS', 'Bachelor of Science in Accounting Information System', 2),
(3, 'BSED', 'Bachelor of Science in Secondary Education', 3),
(4, 'BEED', 'Bachelor of Science in Elementary Education', 3);

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`) VALUES
(2, 'College of Business Studies'),
(1, 'College of Computer Studies'),
(3, 'College of Education'),
(9, 'Elementary School'),
(4, 'Senior High School');

-- --------------------------------------------------------

--
-- Table structure for table `elections`
--

CREATE TABLE `elections` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `elections`
--

INSERT INTO `elections` (`id`, `title`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Default Election', 'Main election for current academic year', 1, '2025-11-25 13:50:56', '2025-11-25 13:50:56');

-- --------------------------------------------------------

--
-- Table structure for table `election_history`
--

CREATE TABLE `election_history` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `position` varchar(50) DEFAULT NULL,
  `year_section` varchar(50) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `platforms` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `election_history_photos`
--

CREATE TABLE `election_history_photos` (
  `id` int(11) NOT NULL,
  `history_id` int(11) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Dean','Teacher') NOT NULL DEFAULT 'Teacher',
  `profile_pic` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `firstname`, `lastname`, `email`, `password`, `role`, `profile_pic`, `department_id`) VALUES
(652, 'CCS', 'Administrator', 'ccsadmin@src.edu.ph', 'admin123', '', NULL, 1),
(653, 'CBS', 'Admin', 'cbsadmin@src.edu.ph', 'admin123', '', NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `facility`
--

CREATE TABLE `facility` (
  `lab_id` int(11) NOT NULL,
  `lab_name` varchar(100) NOT NULL,
  `location` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `facility`
--

INSERT INTO `facility` (`lab_id`, `lab_name`, `location`) VALUES
(1, 'Computer Laboratory A', NULL),
(2, 'Computer Laboratory B', NULL),
(3, 'Computer Laboratory C', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pc_assignment`
--

CREATE TABLE `pc_assignment` (
  `pc_assignment_id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `pc_number` varchar(20) NOT NULL,
  `date_assigned` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `schedule_id` int(11) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `time_start` datetime NOT NULL,
  `time_end` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `level` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`section_id`, `section_name`, `level`) VALUES
(1, '1A', 1),
(2, '2B', 1),
(3, '2A', 2),
(4, '2B', 2),
(5, '3A', 3),
(6, '3B', 3),
(7, '4A', 4),
(8, '4B', 4);

-- --------------------------------------------------------

--
-- Table structure for table `semester`
--

CREATE TABLE `semester` (
  `semester_id` int(11) NOT NULL,
  `ay_id` int(11) NOT NULL,
  `semester_now` enum('1','2') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `semester`
--

INSERT INTO `semester` (`semester_id`, `ay_id`, `semester_now`) VALUES
(1, 1, '1'),
(2, 1, '2');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(10) NOT NULL,
  `rfid_number` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT '',
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(50) DEFAULT '',
  `gender` varchar(6) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `has_voted` tinyint(1) NOT NULL DEFAULT '0',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `rfid_number`, `profile_picture`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `email`, `password`, `is_approved`, `has_voted`, `reset_token`, `reset_token_expires_at`, `department_id`, `course_id`) VALUES
('24-000342', ' 24-0003425', '', ' RUFFA', 'ROMERO', 'CALILUNG', '', 'Female', NULL, 'password123', 1, 0, NULL, NULL, 1, NULL),
('19-0000124', '19-0000124', '', ' ALDRIN', 'PEREZ', 'FAVOR', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('20-0000651', '20-0000651', '', ' OLIVER', 'LANSANGAN', 'DELFIN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('21-0000840', '21-0000840', '', ' DIETHER JOSHUA', 'SAGUN', 'CALAGUAS', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('21-0000897', '21-0000897', '', ' ERIS', 'ESPIRITU', 'PONIO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('21-0000905', '21-0000905', '', ' CRISTOPHER JAMES', 'BARNES', 'ANGELES', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('21-0001062', '21-0001062', '', ' JOHN MICHAEL', 'FLORES', 'DIZON', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('21-0001280', '21-0001280', '', ' VINCE NICOLAS', 'ENRIQUEZ', 'SANGALANG', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0001230', '22-0001230', '', ' PRINCE', 'JAN', 'VITUG', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0001234', '22-0001234', '', ' ROSA', 'CAMMILE', 'MANGAYA', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0001235', '22-0001235', '', ' JAYANNE', '', 'MONTEMAYOR', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0001236', '22-0001236', '', ' DEANA', '', 'NULUD', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0001237', '22-0001237', '', ' TENCHI', '', 'SENYO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0001238', '22-0001238', '', ' ALAN', '', 'TOLENTINO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0001239', '22-0001239', '', ' ANGELA', '', 'VALDEZ', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0001456', '22-0001456', '', ' LORENZO EMMANUEL', 'MINGUINTO', 'URBANO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0001559', '22-0001559', '', ' NINO ANJELO', '', 'DIZON', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002120', '22-0002120', '', ' PATRICK JOHN', 'LAPIRA', 'ALIPIO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002123', '22-0002123', '', ' JEROME ANGELO', 'LEJARDE', 'LANSANG', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002127', '22-0002127', '', ' NICOLE', '', 'ENRIQUEZ', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002128', '22-0002128', '', ' ANGELA', 'ENRIQUEZ', 'AVILA', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002129', '22-0002129', '', ' JOHN LESTER', 'GARCIA', 'BACANI', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002131', '22-0002131', '', ' JOHN CARL', 'DELA PENA', 'DIZON', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002141', '22-0002141', '', ' PRINCESS', 'OCAMPO', 'CALMA', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002142', '22-0002142', '', ' KYLE', 'MANALAC', 'FERNANDEZ', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002145', '22-0002145', '', ' AIRA', 'MANALAC', 'FERNANDEZ', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002146', '22-0002146', '', ' RIMARCH', 'ROQUE', 'DIZON', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002147', '22-0002147', '', ' LHOURD ANDREI', 'LEANO', 'GANZON', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002148', '22-0002148', '', ' MARK GLEN', 'PINEDA', 'GUEVARRA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002149', '22-0002149', '', ' JEROME', 'PAMAGAN', 'GARCIA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002152', '22-0002152', '', ' MICAELLA', 'PINEDA', 'MILLOS', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002153', '22-0002153', '', ' ELAINE', 'SALALILA', 'MONTEMAYOR', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002154', '22-0002154', '', ' CLARENCE', 'BUAN', 'DULA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002155', '22-0002155', '', ' ROY', 'DELA CRUZ', 'JUNTILLA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002156', '22-0002156', '', ' ASHLIE JOHN', 'VALENCIA', 'GATCHALIAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002157', '22-0002157', '', ' RAINIER', 'JOVELLAR', 'LAXAMANA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002158', '22-0002158', '', ' ROMAN', 'SANTOS', 'MERCADO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002167', '22-0002167', '', ' GENER JR.', 'VALENCIA', 'MANLAPAZ', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002170', '22-0002170', '', ' LAWRENCE ANDREI', '', 'GUIAO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002171', '22-0002171', '', ' LLANYELL', 'REYES', 'MANALANG', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002191', '22-0002191', '', ' JOHN EMIL', 'MANALAC', 'TUPAS', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002199', '22-0002199', '', ' JANIRO', 'MENDOZA', 'SERRANO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002200', '22-0002200', '', ' MARK ANTHONY', 'SISON', 'VILLAFUERTE', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002201', '22-0002201', '', ' FRENCER GIL', 'MANANSALA', 'ROMERO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002202', '22-0002202', '', ' LIMUEL', 'VARQUEZ', 'MIRANDA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002204', '22-0002204', '', ' JONNARIE', 'MERCADO', 'ROLL', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002209', '22-0002209', '', ' RONIEL MARCO', 'PUNZALAN', 'BAYAUA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002224', '22-0002224', '', ' JANESSA', 'HICBAN', 'SANTOS', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002225', '22-0002225', '', ' MARK EDRIAN', 'DE DIOS', 'ROQUE', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002226', '22-0002226', '', ' RALPH', 'AGUILAR', 'SIMBUL', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002264', '22-0002264', '', ' MICHELLE', 'DAGOY', 'GUANLAO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002294', '22-0002294', '', ' JEROME', 'DETERA', 'OCAMPO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002365', '22-0002365', '', ' JOHN ARLEY', 'MANALANSAN', 'DABU', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002372', '22-0002372', '', ' TRICIA ANN', 'MANABAT', 'NEPOMUCENO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002376', '22-0002376', '', ' CRISTINE', '', 'MAAMBONG', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002382', '22-0002382', '', ' VINCENT', '', 'TIATCO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002387', '22-0002387', '', ' GUEN CARLO', '', 'GOMEZ', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002388', '22-0002388', '', ' JOSEPH LORENZ', 'DIMACALI', 'SISON', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002389', '22-0002389', '', ' JESSA', 'VERZOSA', 'GUANLAO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002390', '22-0002390', '', ' NEIL TRISTAN', 'PAYUMO', 'MANGILIMAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002391', '22-0002391', '', ' KHIAN CARL', 'BORJA', 'HERODICO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002393', '22-0002393', '', ' RAMLEY JON', 'RAMOS', 'MAGPAYO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002394', '22-0002394', '', ' LEONEL', 'PACHICO', 'POPATCO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002398', '22-0002398', '', ' KING WESHLEY', 'GALANG', 'MUTUC', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002400', '22-0002400', '', ' STEVEN', 'LOBERO', 'GONZALES', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002401', '22-0002401', '', ' RICHARD', 'BUNQUE', 'GUEVARRA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002403', '22-0002403', '', ' CHRISTOPHER', 'MADEJA', 'PANOY', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002407', '22-0002407', '', ' JHAY-R', 'LLENAS', 'MERCADO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002409', '22-0002409', '', ' VAL NERIE', 'ONG', 'ESPELETA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002413', '22-0002413', '', ' JOHN LOUISE', 'CUNANAN', 'SEMSEM', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002414', '22-0002414', '', ' RAPH JUSTINE', 'BAUTISTA', 'BUTIAL', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002415', '22-0002415', '', ' ANGEL ROSE ANNE', 'FABROA', 'MALLARI', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002416', '22-0002416', '', ' KELSEY KEMP', 'SAZON', 'BONOAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002419', '22-0002419', '', ' PRINCESS SHAINE', 'BUCUD', 'SANTIAGO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002420', '22-0002420', '', ' YVES ANDREI', 'MANALO', 'SANTOS', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002421', '22-0002421', '', ' CHRISTINE ANNE', 'MALLARI', 'FLORENDO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002425', '22-0002425', '', ' RICHMOND', 'MARTIN', 'SAFICO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002431', '22-0002431', '', ' JANRIX HARVEY', 'CRUZ', 'RIVERA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002434', '22-0002434', '', ' AERIAL JERAMY', 'APARICI', 'LAYUG', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002436', '22-0002436', '', ' RUSSEL KENNETH', 'CASTLLO', 'LIM', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002438', '22-0002438', '', ' ANGELITO', '', 'CRUZ', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002439', '22-0002439', '', ' JOANNA', 'DUNGCA', 'JULIAN', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002442', '22-0002442', '', ' PRINCE ALVIER', 'GALANG', 'NUNEZ', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002453', '22-0002453', '', ' DEXTER', 'SALALILA', 'VILLEGAS', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002455', '22-0002455', '', ' JHAYZHELLE', 'DUNGCA', 'ALVARADO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002458', '22-0002458', '', ' VERONICA', 'ALBISA', 'MERCADO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002460', '22-0002460', '', ' JOHN MICHAEL', 'JIMENEZ', 'ELILIO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002467', '22-0002467', '', ' ROSE ANN', 'DELA CRUZ', 'DELA ROSA', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002507', '22-0002507', '', ' ABRAHAM CHRISTIAN', 'SIMBAHAN', 'GAPPI', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002509', '22-0002509', '', ' JHON LOUIE', 'BOGNOT', 'DIZON', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002525', '22-0002525', '', ' JOHN REVELYN', 'DURAN', 'GONZALES', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002534', '22-0002534', '', ' RHAINE JUSTIN', '', 'MANALAC', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002686', '22-0002686', '', ' JOHN BENEDICT', 'DE GUZMAN', 'DEL ROSARIO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002726', '22-0002726', '', ' QUEEN MEILANIE', 'BILLENA', 'BENRIL', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0002822', '22-0002822', '', ' RHEALLE', 'DELA CRUZ', 'ALKUINO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('22-0003082', '22-0003082', '', ' JAYSON', '', 'BACSAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0002973', '23-0002973', '', ' JOHN MICHAEL', 'GALANG', 'DAVID', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003005', '23-0003005', '', ' MERWIN', 'PASCUAL', 'HIPOLITO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003011', '23-0003011', '', ' IGIDIAN VINCE', 'GUINTU', 'CASTRO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003012', '23-0003012', '', ' REYMART', 'LANSANG', 'PINEDA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003021', '23-0003021', '', ' C-JAY', 'HICBAN', 'SANTOS', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003022', '23-0003022', '', ' RENZ YUAN', 'GUEVARRA', 'CAYANAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003023', '23-0003023', '', ' LEAN', 'CRUZ', 'LAXAMANA', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003026', '23-0003026', '', ' JULIUS CEDRICK', 'GUIAO', 'VIRAY', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003028', '23-0003028', '', ' MARK ATHAN', 'GUANZON', 'MANALANG', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003031', '23-0003031', '', ' JHON MICHAEL', 'OCAMPO', 'BATAC', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003034', '23-0003034', '', ' JOSEPH MIGUEL', '', 'URBANO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003053', '23-0003053', '', ' ROY FRANCIS', 'SALALILA', 'ENRIQUEZ', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003054', '23-0003054', '', ' KATE LYN', 'PINEDA', 'BUAN', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003058', '23-0003058', '', ' KEN HARVEY', 'REQUIRON', 'SORIANO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003060', '23-0003060', '', ' JOHN KEISLY', 'DY', 'BACANI', 'LabB-PC20', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003062', '23-0003062', '', ' JOHN CLARENCE', 'MUTUC', 'DAVID', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003063', '23-0003063', '', ' TIMOTHY EARL', 'CORONA', 'BUAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003082', '23-0003082', '', ' JAYSON', 'INDIONGCO', 'BACSAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003087', '23-0003087', '', ' MHARK CHEDRICK', '', 'FERNANDO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003098', '23-0003098', '', ' NICK IVAN', 'BUAN', 'MARIANO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003103', '23-0003103', '', ' JULIANA CLAIR', 'PINEDA', 'IGNACIO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003108', '23-0003108', '', ' RENELLE ROBIE', 'DULCE', 'LOPEZ', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('23-0003167', '23-0003167', '', ' RYAN', 'MULI', 'GUINTO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24 -000326', '24 -0003269', '', ' GIRLLY', 'MANALAC', 'FERNANDEZ', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003044', '24-0003044', '', ' ELKAN', 'ALONZO', 'SARMIENTO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003174', '24-0003174', '', ' REX', 'LAPURE', 'GATCHALIAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003256', '24-0003256', '', ' JUSTINE', 'LICAME', 'ANGELES', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003261', '24-0003261', '', ' JOHN PAUL', 'DUNGCA', 'ARCILLA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003262', '24-0003262', '', ' KAREN', 'DAVID', 'MONTES', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003267', '24-0003267', '', ' KIM WESLEY', 'ANTONIO', 'PERALTA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003280', '24-0003280', '', ' EDRON', 'BATAC', 'GARCIA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003285', '24-0003285', '', ' JUSTINE', 'PITUC', 'SINGAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003290', '24-0003290', '', ' RONNIE JR.', 'BARBIN', 'HALOG', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003292', '24-0003292', '', ' SHIANN KELLY', 'GARCIA', 'PAYUMO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003303', '24-0003303', '', ' ANTONETTE', 'DELFIN', 'BERNARDO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003306', '24-0003306', '', ' JOHN BENEDICT', 'GOMEZ', 'PERRERAS', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003307', '24-0003307', '', ' JERALD', 'FORTIN', 'GALANG', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003308', '24-0003308', '', ' WARREN KING', 'DIMAANO', 'CANLAS', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003309', '24-0003309', '', ' IYA NEL', 'SERRANO', 'MANGARING', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003310', '24-0003310', '', ' SHIN', 'GARCIA', 'BARTOCILLO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003314', '24-0003314', '', ' JHON FRANCIS', 'GUANZON', 'ALAVE', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003315', '24-0003315', '', ' ALEXANDER JEHRIEL', 'ARRIOLA', 'NULUD', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003318', '24-0003318', '', ' NICOLE', 'BUAN', 'SAMBILE', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003321', '24-0003321', '', ' MARVIN JOEY', 'OCAMPO', 'APAREJADO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003325', '24-0003325', '', ' MARLYN', '', 'MERCADO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003331', '24-0003331', '', ' SOPIA MAE', 'CARLOS', 'GUINTO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003339', '24-0003339', '', ' KEVIN', 'MARIANO', 'CASTRO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003343', '24-0003343', '', ' ARJAY', 'PERENIA', 'DEL CASTILLO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003349', '24-0003349', '', ' JAZELLE ANNE', 'GARCES', 'BATAS', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003362', '24-0003362', '', ' ERIC', 'SUYOM', 'CADOCOY', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003375', '24-0003375', '', ' KATHEINE JOY', 'CORTEZ', 'FERNANDO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003393', '24-0003393', '', ' ERICAH MAE', 'INFANTE', 'VALENCIA', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003410', '24-0003410', '', ' JESSICA', 'CABACUNGAN', 'SALALILA', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003414', '24-0003414', '', ' VHON LEAMBEER', 'DELOS REYES', 'GONZALES', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003425', '24-0003425', '', ' RUFFA', '', 'CALILUNG', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003426', '24-0003426', '', ' JOHN PAUL', 'MARMETO', 'SANTOS', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003433', '24-0003433', '', ' LYKA NICOLE', 'TORRES', 'LAYUG', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003434', '24-0003434', '', ' TRISTAN', 'LUSUNG', 'DUQUE', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('24-0003435', '24-0003435', '', ' ALEXA KEITH', 'CALAGOS', 'BOSTERO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003688', '25-0003688', '', ' TRISHA', 'CABILES', 'BARRUGA', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003690', '25-0003690', '', ' KERWIN', 'PADILLA', 'BUAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003691', '25-0003691', '', ' JOSHUA', 'RAMIREZ', 'CAMITAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003692', '25-0003692', '', ' JOHN CHLOE', 'TUMINTIN', 'CASUPANAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003693', '25-0003693', '', ' DAVE GABRIEL', 'BALTAZAR', 'CRUZ', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003694', '25-0003694', '', ' KAYCEE LYN', 'NARVAREZ', 'DIMAL', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003695', '25-0003695', '', ' NORMAN', 'SAMPANG', 'FRESNOZA JR.', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003698', '25-0003698', '', ' MAUI', 'MALLARI', 'MARCELO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003704', '25-0003704', '', ' ELLAIZA', 'BACANI', 'NEPOMUCENO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003706', '25-0003706', '', ' JEROME', 'TORANO', 'PINEDA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003707', '25-0003707', '', ' JOSHUA', 'LUCINO', 'PINEDA', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003708', '2874015315', '', ' JOLAINE', 'JIMENEZ', 'ANDAMON', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003709', '25-0003709', '', ' EMY JANE', 'LUBIANO', 'ROYO', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003711', '25-0003711', '', ' CID', 'MALIGLIG', 'SOTTO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003736', '25-0003736', '', ' CINDY', 'ENRIQUEZ', 'ROQUE', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003751', '25-0003751', '', ' GERALD', 'DELA CRUZ', 'PANTIG', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003756', '25-0003756', '', ' TRISTAN', 'CENAL', 'BUAN', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003763', '25-0003763', '', ' JOHN RUSTI', 'BUTIAL', 'NIO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003765', '25-0003765', '', ' IVAN', 'DELA CRUZ', 'MARIANO', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003768', '25-0003768', '', ' KIRK RINGO', 'BEJASA', 'SERIOS', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003771', '25-0003771', '', ' JAN MARK', 'PAMINTUAN', 'TUAZON', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003774', '25-0003774', '', ' KYLE ZEDDRICK', 'MACALINO', 'SUBOC', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003781', '25-0003781', '', ' SHANNEN', 'MONTEALTO', 'MONSALOD', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('25-0003782', '25-0003782', '', ' ANGEL', 'LOBERO', 'GONZALES', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('26-4378547', '26-43785476', '', ' JHOANA MARIE', 'MANLULU', 'SALVADOR', '', 'Female', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('2643794436', '2643794436', '', ' Mark', 'Glen', 'Pineda', '', 'Male', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL),
('student_id', '	rfid_number', 'profile_picture', 'first_name', 'middle_name', 'last_name', 'suffix', 'gender', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `vote_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(10) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `position` varchar(100) NOT NULL,
  `election_id` int(11) NOT NULL,
  PRIMARY KEY (`vote_id`),
  KEY `user_id` (`user_id`),
  KEY `candidate_id` (`candidate_id`),
  KEY `election_id` (`election_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `units` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_id`, `subject_code`, `subject_name`, `units`) VALUES
(1, 'VOTE-SCHED', 'Voting Schedule Window', 0);

-- --------------------------------------------------------

--
-- Table structure for table `year_level`
--

CREATE TABLE `year_level` (
  `year_id` int(11) NOT NULL,
  `year_name` varchar(20) NOT NULL,
  `level` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `year_level`
--

INSERT INTO `year_level` (`year_id`, `year_name`, `level`) VALUES
(1, '1', 1),
(2, '2', 2),
(3, '3', 3),
(4, '4', 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_year`
--
ALTER TABLE `academic_year`
  ADD PRIMARY KEY (`ay_id`);

--
-- Indexes for table `admission`
--
ALTER TABLE `admission`
  ADD PRIMARY KEY (`admission_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `year_level_id` (`year_level_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `admission_id` (`admission_id`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_candidates_department` (`department_id`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `fk_course_department` (`department_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `elections`
--
ALTER TABLE `elections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `election_history`
--
ALTER TABLE `election_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `election_history_photos`
--
ALTER TABLE `election_history_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `history_id` (`history_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_employees_department` (`department_id`);

--
-- Indexes for table `facility`
--
ALTER TABLE `facility`
  ADD PRIMARY KEY (`lab_id`);

--
-- Indexes for table `pc_assignment`
--
ALTER TABLE `pc_assignment`
  ADD PRIMARY KEY (`pc_assignment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `lab_id` (`lab_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `lab_id` (`lab_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD PRIMARY KEY (`section_id`);

--
-- Indexes for table `semester`
--
ALTER TABLE `semester`
  ADD PRIMARY KEY (`semester_id`),
  ADD KEY `ay_id` (`ay_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `rfid_number` (`rfid_number`),
  ADD KEY `idx_students_is_approved` (`is_approved`),
  ADD KEY `idx_students_has_voted` (`has_voted`),
  ADD KEY `fk_students_department` (`department_id`),
  ADD KEY `fk_students_course` (`course_id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `year_level`
--
ALTER TABLE `year_level`
  ADD PRIMARY KEY (`year_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_year`
--
ALTER TABLE `academic_year`
  MODIFY `ay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `admission`
--
ALTER TABLE `admission`
  MODIFY `admission_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `elections`
--
ALTER TABLE `elections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `election_history`
--
ALTER TABLE `election_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `election_history_photos`
--
ALTER TABLE `election_history_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=654;
--
-- AUTO_INCREMENT for table `facility`
--
ALTER TABLE `facility`
  MODIFY `lab_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `pc_assignment`
--
ALTER TABLE `pc_assignment`
  MODIFY `pc_assignment_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `semester`
--
ALTER TABLE `semester`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `year_level`
--
ALTER TABLE `year_level`
  MODIFY `year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `admission`
--
ALTER TABLE `admission`
  ADD CONSTRAINT `admission_academic_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_year` (`ay_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_course_fk` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_schedule_fk` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_section_fk` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_semester_fk` FOREIGN KEY (`semester_id`) REFERENCES `semester` (`semester_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_year_level_fk` FOREIGN KEY (`year_level_id`) REFERENCES `year_level` (`year_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_admission_fk` FOREIGN KEY (`admission_id`) REFERENCES `admission` (`admission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_schedule_fk` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `fk_candidates_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`);

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `fk_course_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `election_history_photos`
--
ALTER TABLE `election_history_photos`
  ADD CONSTRAINT `election_history_photos_ibfk_1` FOREIGN KEY (`history_id`) REFERENCES `election_history` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`),
  ADD CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`);

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`lab_id`) REFERENCES `facility` (`lab_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_3` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `semester`
--
ALTER TABLE `semester`
  ADD CONSTRAINT `semester_ibfk_1` FOREIGN KEY (`ay_id`) REFERENCES `academic_year` (`ay_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
