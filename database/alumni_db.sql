-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2025 at 05:17 PM
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
-- Database: `alumni_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `alumni_officers`
--

CREATE TABLE `alumni_officers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(100) NOT NULL,
  `class_year` varchar(10) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alumni_officers`
--

INSERT INTO `alumni_officers` (`id`, `name`, `position`, `class_year`, `course`, `image_path`, `display_order`, `created_at`, `updated_at`) VALUES
(4, 'Last Pres', 'President', '2024', 'BS Information Technology', '', 0, '2025-05-18 18:21:44', '2025-05-18 18:21:44'),
(6, ':ast Vice Pres', 'Vice President', '2024', 'BS Information Technology', '', 0, '2025-05-18 18:22:03', '2025-05-18 18:22:03');

-- --------------------------------------------------------

--
-- Table structure for table `alumnus_bio`
--

CREATE TABLE `alumnus_bio` (
  `id` int(11) NOT NULL,
  `alumni_id` varchar(20) NOT NULL,
  `firstname` varchar(200) NOT NULL,
  `middlename` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `batch` year(4) NOT NULL,
  `course_id` int(30) NOT NULL,
  `email` varchar(250) NOT NULL,
  `current_company` varchar(250) DEFAULT NULL,
  `current_job_title` varchar(250) DEFAULT NULL,
  `connected_to` tinyint(1) NOT NULL,
  `avatar` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0= Unverified, 1= Verified',
  `date_created` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alumnus_bio`
--

INSERT INTO `alumnus_bio` (`id`, `alumni_id`, `firstname`, `middlename`, `lastname`, `gender`, `batch`, `course_id`, `email`, `current_company`, `current_job_title`, `connected_to`, `avatar`, `status`, `date_created`) VALUES
(37, '0001-0001', 'Juan', 'Dela', 'Cruz', 'Male', '2018', 1, 'juan.delacruz@example.com', 'Google', 'Softawre', 0, 'avatar.png', 1, '2025-05-10'),
(38, '0001-0002', 'Maria', 'Santos', 'Reyes', 'Female', '2019', 2, 'maria.reyes@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(39, '0001-0003', 'Pedro', 'Garcia', 'Lim', 'Male', '2020', 3, 'pedro.lim@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(40, '0001-0004', 'Ana', 'Bautista', 'Santos', 'Female', '2018', 1, 'ana.santos@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(41, '0001-0005', 'Carlos', 'Mendoza', 'Reyes', 'Male', '2019', 4, 'carlos.reyes@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(42, '0001-0006', 'Sofia', 'Tan', 'Gonzales', 'Female', '2020', 2, 'sofia.gonzales@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(43, '0001-0007', 'Miguel', 'Santos', 'Tan', 'Male', '2021', 3, 'miguel.tan@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(44, '0001-0008', 'Isabella', 'Lopez', 'Garcia', 'Female', '2019', 2, 'isabella.garcia@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(45, '0001-0009', 'Gabriel', 'Diaz', 'Hernandez', 'Male', '2018', 1, 'gabriel.hernandez@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(46, '0001-0010', 'Camila', 'Reyes', 'Rodriguez', 'Female', '2020', 4, 'camila.rodriguez@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(47, '0002-0001', 'Rafael', 'Cruz', 'Martinez', 'Male', '2019', 1, 'rafael.martinez@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(48, '0002-0002', 'Victoria', 'Gomez', 'Flores', 'Female', '2021', 2, 'victoria.flores@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(49, '0002-0003', 'Alejandro', 'Morales', 'Sanchez', 'Male', '2018', 3, 'alejandro.sanchez@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(50, '0002-0004', 'Valentina', 'Torres', 'Rivera', 'Female', '2020', 4, 'valentina.rivera@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(51, '0002-0005', 'Daniel', 'Ortiz', 'Gutierrez', 'Male', '2019', 1, 'daniel.gutierrez@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(52, '0002-0006', 'Natalia', 'Ramos', 'Castillo', 'Female', '2018', 2, 'natalia.castillo@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(53, '0002-0007', 'Sebastian', 'Aguilar', 'Vargas', 'Male', '2021', 3, 'sebastian.vargas@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(54, '0002-0008', 'Olivia', 'Jimenez', 'Romero', 'Female', '2020', 4, 'olivia.romero@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(55, '0002-0009', 'Mateo', 'Navarro', 'Herrera', 'Male', '2019', 1, 'mateo.herrera@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(56, '0002-0010', 'Emma', 'Dominguez', 'Ruiz', 'Female', '2018', 2, 'emma.ruiz@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(57, '0003-0001', 'Samuel', 'Alvarez', 'Medina', 'Male', '2020', 3, 'samuel.medina@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(58, '0003-0002', 'Sophia', 'Castro', 'Fernandez', 'Female', '2019', 4, 'sophia.fernandez@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(59, '0003-0003', 'Lucas', 'Vasquez', 'Ramirez', 'Male', '2021', 1, 'lucas.ramirez@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(60, '0003-0004', 'Ava', 'Paredes', 'Mendoza', 'Female', '2018', 2, 'ava.mendoza@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(61, '0003-0005', 'Nicolas', 'Cabrera', 'Salazar', 'Male', '2020', 3, 'nicolas.salazar@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(62, '0003-0006', 'Chloe', 'Sandoval', 'Espinoza', 'Female', '2019', 4, 'chloe.espinoza@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(63, '0003-0007', 'Benjamin', 'Fuentes', 'Campos', 'Male', '2018', 1, 'benjamin.campos@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(64, '0003-0008', 'Mia', 'Rios', 'Delgado', 'Female', '2021', 2, 'mia.delgado@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(65, '0003-0009', 'Matias', 'Nunez', 'Pena', 'Male', '2020', 3, 'matias.pena@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(66, '0003-0010', 'Zoe', 'Lozano', 'Escobar', 'Female', '2019', 4, 'zoe.escobar@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(67, '0004-0001', 'David', 'Silva', 'Vega', 'Male', '2018', 1, 'david.vega@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(68, '0004-0002', 'Luna', 'Gallegos', 'Contreras', 'Female', '2020', 2, 'luna.contreras@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(69, '0004-0003', 'Emilio', 'Valencia', 'Solis', 'Male', '2019', 3, 'emilio.solis@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(70, '0004-0004', 'Lucia', 'Pineda', 'Molina', 'Female', '2021', 4, 'lucia.molina@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(71, '0004-0005', 'Santiago', 'Acosta', 'Miranda', 'Male', '2018', 1, 'santiago.miranda@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(72, '0004-0006', 'Valeria', 'Zuniga', 'Rojas', 'Female', '2020', 2, 'valeria.rojas@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10'),
(73, '0004-0007', 'Eduardo', 'Alvarado', 'Beltran', 'Male', '2019', 3, 'eduardo.beltran@example.com', NULL, NULL, 0, 'avatar.png', 1, '2025-05-10');

-- --------------------------------------------------------

--
-- Table structure for table `archived_events`
--

CREATE TABLE `archived_events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `schedule` datetime NOT NULL,
  `banner` varchar(255) NOT NULL DEFAULT 'no-image.jpg',
  `gform_link` varchar(255) DEFAULT NULL,
  `archived_date` datetime NOT NULL DEFAULT current_timestamp(),
  `original_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_events`
--

INSERT INTO `archived_events` (`id`, `title`, `content`, `schedule`, `banner`, `gform_link`, `archived_date`, `original_id`) VALUES
(19, 'qweqwe', 'qweqweqwe', '2025-05-19 20:00:00', 'no-image.jpg', '', '2025-05-19 19:06:34', 28),
(20, 'qweqwe', 'qweqwe', '2025-05-19 20:09:00', 'no-image.jpg', '', '2025-05-19 20:08:10', 29),
(21, 'qweqweqe', 'qweqwe', '2025-05-19 20:19:00', 'no-image.jpg', '', '2025-05-19 20:19:33', 30),
(22, 'qweeqw', 'qweqwqwe', '2025-05-19 20:29:00', 'no-image.jpg', '', '2025-05-19 20:28:44', 31),
(23, 'qweqwewe', 'qwqwe', '2025-05-19 21:40:00', 'no-image.jpg', '', '2025-05-19 20:39:04', 32),
(24, 'qwqweqwe', 'qweqweqweqwe', '2025-05-19 20:50:00', 'no-image.jpg', '', '2025-05-19 20:46:52', 33),
(25, 'qweqwe', 'qwqweqwee', '2025-05-19 20:59:00', 'no-image.jpg', '', '2025-05-19 20:52:57', 34),
(26, 'qweqwe', 'qwweqwe', '2025-05-19 23:01:00', 'no-image.jpg', '', '2025-05-19 21:02:17', 35),
(27, 'qweqwe', 'qweqwe', '2025-05-19 21:04:00', 'no-image.jpg', '', '2025-05-19 21:04:06', 36),
(28, 'qwewewq', 'qweqwe', '2025-05-19 21:10:00', 'no-image.jpg', '', '2025-05-19 21:10:09', 37),
(29, 'qweqweqwee', 'qweqwe', '2025-05-19 22:18:00', 'no-image.jpg', '', '2025-05-19 21:17:57', 38),
(30, 'qwqweqwe', 'qweqwewqwe', '2025-05-29 22:19:00', 'no-image.jpg', '', '2025-05-19 21:18:28', 39),
(31, 'qweqwe', 'qweqweqwe', '2025-05-19 21:27:00', 'no-image.jpg', '', '2025-05-19 21:26:52', 40),
(32, 'qwqwe', 'qweqweqwe', '2025-05-19 21:41:00', 'no-image.jpg', '', '2025-05-19 21:42:08', 41),
(33, 'qweqwee', 'qweqweqwe', '2025-05-19 22:51:00', 'no-image.jpg', '', '2025-05-19 22:50:49', 42);

-- --------------------------------------------------------

--
-- Table structure for table `careers`
--

CREATE TABLE `careers` (
  `id` int(30) NOT NULL,
  `company` varchar(250) NOT NULL,
  `location` text NOT NULL,
  `job_title` text NOT NULL,
  `description` text NOT NULL,
  `user_id` varchar(9) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `careers`
--

INSERT INTO `careers` (`id`, `company`, `location`, `job_title`, `description`, `user_id`, `date_created`) VALUES
(3, 'TEst', 'Pasig City (WFH)', 'Software Engineer', 'test\\r\\n', '1', '2025-05-19 00:40:00'),
(5, 'TEst', 'Pasig City (Onsite)', 'qwew', 'test', '0001-0001', '2025-05-19 04:22:19');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(30) NOT NULL,
  `course` text NOT NULL,
  `about` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course`, `about`) VALUES
(1, 'BS Information Technology', ''),
(2, 'BS Computer Science', ''),
(3, 'BS Information Systems', 'Added from CSV import'),
(4, 'BS Computer Engineering', 'Added from CSV import');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(30) NOT NULL,
  `title` varchar(250) NOT NULL,
  `content` text NOT NULL,
  `schedule` datetime NOT NULL,
  `banner` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `gform_link` varchar(200) NOT NULL,
  `archive_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `content`, `schedule`, `banner`, `date_created`, `gform_link`, `archive_date`) VALUES
(4, 'Test', 'test\r\n', '2025-05-17 01:48:00', 'no-image-available.png', '2025-05-17 01:48:44', '', NULL),
(5, 'test', 'test test', '2025-05-17 01:48:00', 'no-image-available.png', '2025-05-17 01:48:54', '', NULL),
(6, 'qweqweqweqwe', 'qweqweqweqweqwe', '2025-05-17 01:49:00', 'no-image-available.png', '2025-05-17 01:49:07', '', NULL),
(7, 'test12', 'qweqwewe', '2025-05-18 15:22:00', 'no-image-available.png', '2025-05-18 21:22:09', '', NULL),
(16, 'qweqwewe', 'qweqwee', '2025-05-19 18:30:00', 'no-image.jpg', '2025-05-19 18:30:11', '', '2025-05-20');

-- --------------------------------------------------------

--
-- Table structure for table `event_commits`
--

CREATE TABLE `event_commits` (
  `id` int(30) NOT NULL,
  `event_id` int(30) NOT NULL,
  `user_id` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_commits`
--

INSERT INTO `event_commits` (`id`, `event_id`, `user_id`) VALUES
(1, 1, '3');

-- --------------------------------------------------------

--
-- Table structure for table `forum_comments`
--

CREATE TABLE `forum_comments` (
  `id` int(30) NOT NULL,
  `topic_id` int(30) NOT NULL,
  `comment` text NOT NULL,
  `user_id` varchar(9) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_comments`
--

INSERT INTO `forum_comments` (`id`, `topic_id`, `comment`, `user_id`, `date_created`) VALUES
(1, 3, 'Sample updated Comment', '3', '2020-10-15 15:46:03'),
(3, 3, 'Sample', '1', '2020-10-16 08:48:02'),
(5, 0, '', '1', '2020-10-16 09:49:34'),
(6, 8, 'test comment\r\n', '0001-0002', '2025-05-19 04:45:47');

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics`
--

CREATE TABLE `forum_topics` (
  `id` int(30) NOT NULL,
  `title` varchar(250) NOT NULL,
  `description` text NOT NULL,
  `user_id` varchar(9) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_topics`
--

INSERT INTO `forum_topics` (`id`, `title`, `description`, `user_id`, `date_created`) VALUES
(2, 'Sample Topic 2', 'lorem', '3', '2020-10-15 15:20:51'),
(3, 'Sample Topic 3', 'lorem', '3', '2020-10-15 15:22:30'),
(4, 'Topic by Admin', 'lorem', '1', '2020-10-16 08:31:45'),
(5, 'test', 'test', '1', '2025-05-19 01:57:33'),
(6, 'test', 'testtt', '1', '2025-05-19 01:57:38'),
(7, 'teestttt', 'test', '1', '2025-05-19 01:57:44'),
(8, 'Test topic', 'ewan', '0001-0001', '2025-05-19 04:45:08');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `upload_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `title`, `description`, `image_path`, `upload_date`) VALUES
(1, 'Campus View', 'Beautiful view of our university campus', 'images/plpasigg.jpg', '2025-05-08 12:15:39'),
(2, 'qweqwe', 'qweqweqwe', 'uploads/gallery/6829e0389e2e9_Screenshot 2025-05-16 202338.png', '2025-05-18 21:27:20'),
(3, 'qweqwe', 'qweqweqweqwewqew', 'uploads/gallery/6829e04d868ab_Screenshot 2024-08-26 094918.png', '2025-05-18 21:27:41'),
(4, 'qweqweqweqwe', 'qweqweqwe', 'uploads/gallery/6829e05dc1fa8_Screenshot 2024-08-27 192135.png', '2025-05-18 21:27:57'),
(5, 'qweqwe', 'qweqwe', 'uploads/gallery/6829e06d1bd4c_18.1.png', '2025-05-18 21:28:13'),
(6, 'qweqw', 'eqweqwew', 'uploads/gallery/6829e08ef0bca_Screenshot 2025-05-16 201530.png', '2025-05-18 21:28:46'),
(7, 'test', 'eqweqwewewe', 'uploads/gallery/6829e09d73b9e_Screenshot 2025-05-03 022127.png', '2025-05-18 21:29:01'),
(8, 'test test', 'eqweqwewewe', 'uploads/gallery/682a394dc2a7f_exce5.png', '2025-05-19 03:47:25'),
(9, 'test test test test', 'eqweqwewewe', 'uploads/gallery/682a3a30c9b6c_Screenshot (1).png', '2025-05-19 03:47:45');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(30) NOT NULL,
  `name` text NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `cover_img` text NOT NULL,
  `about_content` text NOT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `name`, `email`, `contact`, `cover_img`, `about_content`, `facebook`, `twitter`, `instagram`, `linkedin`) VALUES
(1, 'Alumni Management System', 'info@sample.comm', '+6948 8542 623', 'uploads/1747595420_Screenshot 2025-04-23 235231.png', 'The Pamantasan ng Lungsod ng Pasig Alumni Portal is designed to strengthen the bond between the university and its graduates. We believe in fostering a vibrant community where alumni can connect, collaborate, and contribute to the growth of each other and the institution.\r\n\r\nThrough this platform, we aim to create a supportive network that spans across generations, industries, and geographical boundaries, uniting all PLP graduates under one virtual roof.', 'https://www.facebook.com/', 'https://www.facebook.com/', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `alumni_id` varchar(20) NOT NULL,
  `name` text NOT NULL,
  `username` varchar(200) NOT NULL,
  `password` text NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 3 COMMENT '1=Admin,2=Alumni officer, 3= alumnus',
  `auto_generated_pass` text NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `is_default_password` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Using default password, 0=Password changed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `alumni_id`, `name`, `username`, `password`, `type`, `auto_generated_pass`, `reset_token`, `token_expiry`, `is_default_password`) VALUES
(1, 'admin', 'Administrator', 'admin', '0192023a7bbd73250516f069df18b500', 1, '', NULL, NULL, 1),
(38, '0001-0001', 'Juan Cruz', 'juan.delacruz@example.com', '2168ad5e463d9accb215edaafa31c8d9', 3, '5e11ed4f', NULL, NULL, 0),
(39, '0001-0002', 'Maria Reyes', 'maria.reyes@example.com', 'c8e429150891b60f9c0f2c4a4259f45c', 3, '8eb65c43', NULL, NULL, 1),
(40, '0001-0003', 'Pedro Lim', 'pedro.lim@example.com', 'bef8405a6f651bda7fa5fb2db2d841ef', 3, 'a61e07c4', NULL, NULL, 1),
(41, '0001-0004', 'Ana Santos', 'ana.santos@example.com', '1ad04a7663f25d000052f01e007ea639', 3, 'e1c4a880', NULL, NULL, 1),
(42, '0001-0005', 'Carlos Reyes', 'carlos.reyes@example.com', '99a065970af500451c2f2aa6ae28635b', 3, 'e3618e93', NULL, NULL, 1),
(43, '0001-0006', 'Sofia Gonzales', 'sofia.gonzales@example.com', '4d9def0e91a410152fa739ff2abe847c', 3, 'd2f0b5a3', NULL, NULL, 1),
(44, '0001-0007', 'Miguel Tan', 'miguel.tan@example.com', '446a0fbf3d3467fde88b1bc3acfea253', 3, '2778be3a', NULL, NULL, 1),
(45, '0001-0008', 'Isabella Garcia', 'isabella.garcia@example.com', '00e38ffcebd8f9a8f0e7db538baff4aa', 3, 'c92e080d', NULL, NULL, 1),
(46, '0001-0009', 'Gabriel Hernandez', 'gabriel.hernandez@example.com', 'df56dbd4806073663ab493317d6a42cf', 3, '00bc4905', NULL, NULL, 1),
(47, '0001-0010', 'Camila Rodriguez', 'camila.rodriguez@example.com', 'f0a50414002e2cc49a06da762a268bfe', 3, 'dbd77082', NULL, NULL, 1),
(48, '0002-0001', 'Rafael Martinez', 'rafael.martinez@example.com', '5900b0b2891cc4803af48aeb087b2862', 3, 'bc327096', NULL, NULL, 1),
(49, '0002-0002', 'Victoria Flores', 'victoria.flores@example.com', 'b79e1e0809e884b72f5ec9a37b90eab6', 3, 'dd729cbb', NULL, NULL, 1),
(50, '0002-0003', 'Alejandro Sanchez', 'alejandro.sanchez@example.com', '0b76c56852120cccab79997491b5b6d6', 3, 'cf7cf52d', NULL, NULL, 1),
(51, '0002-0004', 'Valentina Rivera', 'valentina.rivera@example.com', '06fe4a93674045ffb0cd3f62be594a7e', 3, '709a9e2d', NULL, NULL, 1),
(52, '0002-0005', 'Daniel Gutierrez', 'daniel.gutierrez@example.com', '73bce577ac9341efe4787fdf3147f73b', 3, '56213da4', NULL, NULL, 1),
(53, '0002-0006', 'Natalia Castillo', 'natalia.castillo@example.com', '5851ab6b3afaf0b8f531bb65c87e9dcb', 3, 'd5a0b95f', NULL, NULL, 1),
(54, '0002-0007', 'Sebastian Vargas', 'sebastian.vargas@example.com', '38e0c2a8de7bf33ca33047f9d9dcb331', 3, '31dc5ad9', NULL, NULL, 1),
(55, '0002-0008', 'Olivia Romero', 'olivia.romero@example.com', '294f68534cb34783526107d81cbe17ca', 3, 'a99b3c88', NULL, NULL, 1),
(56, '0002-0009', 'Mateo Herrera', 'mateo.herrera@example.com', 'bf8aa47d37ca7abe88a75612e88e1263', 3, '0b2c6822', NULL, NULL, 1),
(57, '0002-0010', 'Emma Ruiz', 'emma.ruiz@example.com', 'e98c15628385c109515553f35df7fe5c', 3, '196059c0', NULL, NULL, 1),
(58, '0003-0001', 'Samuel Medina', 'samuel.medina@example.com', 'cb63ced13efcbf73f87942227810af40', 3, '3f32c429', NULL, NULL, 1),
(59, '0003-0002', 'Sophia Fernandez', 'sophia.fernandez@example.com', '72b85e3947d6fc7c93063a6ff3b71794', 3, '62318c1b', NULL, NULL, 1),
(60, '0003-0003', 'Lucas Ramirez', 'lucas.ramirez@example.com', 'd034c47c2aeb37144e0d0faed81de405', 3, '6d903002', NULL, NULL, 1),
(61, '0003-0004', 'Ava Mendoza', 'ava.mendoza@example.com', '3649709f5ddd445e371d956ef6aa69f1', 3, '73aa64f3', NULL, NULL, 1),
(62, '0003-0005', 'Nicolas Salazar', 'nicolas.salazar@example.com', 'c8a7386544d1a5316eacf31ffc9a7df5', 3, '5caaa092', NULL, NULL, 1),
(63, '0003-0006', 'Chloe Espinoza', 'chloe.espinoza@example.com', '1c056a2ca532eb5e09ddf22ddda3460e', 3, '6a5fef33', NULL, NULL, 1),
(64, '0003-0007', 'Benjamin Campos', 'benjamin.campos@example.com', '995b1a4413061c60d03373d8237a7a9a', 3, '8a0ced24', NULL, NULL, 1),
(65, '0003-0008', 'Mia Delgado', 'mia.delgado@example.com', '0adc191dc43c9d609a541510c7aa0f33', 3, 'cb67e56c', NULL, NULL, 1),
(66, '0003-0009', 'Matias Pena', 'matias.pena@example.com', '6e0ac24f5c31c3d6befa1f826bfac169', 3, '7b6b8d74', NULL, NULL, 1),
(67, '0003-0010', 'Zoe Escobar', 'zoe.escobar@example.com', 'f2db443c5feef36cef0454fd4da1e916', 3, '3830ad4d', NULL, NULL, 1),
(68, '0004-0001', 'David Vega', 'david.vega@example.com', '281d1e7f616045c154eb73a55e95a6e7', 3, '34bdbe15', NULL, NULL, 1),
(69, '0004-0002', 'Luna Contreras', 'luna.contreras@example.com', '3e87d8ce96a8df521d5b80a598c21f6d', 3, '7140afe3', NULL, NULL, 1),
(70, '0004-0003', 'Emilio Solis', 'emilio.solis@example.com', '413f9b33dbd815d6fbba3674ca26048e', 3, '6e43f95f', NULL, NULL, 1),
(71, '0004-0004', 'Lucia Molina', 'lucia.molina@example.com', 'fd0dadbe74551b5be81f99edaf428202', 3, '6f743175', NULL, NULL, 1),
(72, '0004-0005', 'Santiago Miranda', 'santiago.miranda@example.com', '2e736e04abf5c40d0c8d49d335a45fa6', 3, 'bef5ab78', NULL, NULL, 1),
(73, '0004-0006', 'Valeria Rojas', 'valeria.rojas@example.com', '5f8aa1f273e5864564d87a12685b643c', 3, '8c7e1fab', NULL, NULL, 1),
(74, '0004-0007', 'Eduardo Beltran', 'eduardo.beltran@example.com', 'a8faa0b7cfa848ed22cc366e70603460', 3, 'd3759345', NULL, NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alumni_officers`
--
ALTER TABLE `alumni_officers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `alumnus_bio`
--
ALTER TABLE `alumnus_bio`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_events`
--
ALTER TABLE `archived_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `careers`
--
ALTER TABLE `careers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_commits`
--
ALTER TABLE `event_commits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forum_comments`
--
ALTER TABLE `forum_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alumni_officers`
--
ALTER TABLE `alumni_officers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `alumnus_bio`
--
ALTER TABLE `alumnus_bio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `archived_events`
--
ALTER TABLE `archived_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `careers`
--
ALTER TABLE `careers`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `event_commits`
--
ALTER TABLE `event_commits`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `forum_comments`
--
ALTER TABLE `forum_comments`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `forum_topics`
--
ALTER TABLE `forum_topics`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
