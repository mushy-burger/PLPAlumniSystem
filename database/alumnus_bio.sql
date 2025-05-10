-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2025 at 03:35 PM
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
  `connected_to` tinyint(1) NOT NULL,
  `avatar` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0= Unverified, 1= Verified',
  `date_created` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alumnus_bio`
--

INSERT INTO `alumnus_bio` (`id`, `alumni_id`, `firstname`, `middlename`, `lastname`, `gender`, `batch`, `course_id`, `email`, `connected_to`, `avatar`, `status`, `date_created`) VALUES
(14, '2025-2025', 'aurel', 'rumbaoa', 'klyrhon', 'Male', '2025', 1, 'aurelklyrhonmiko@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(15, '2025-0001', 'daniela', 'cruz', 'santos', 'Male', '2025', 1, 'danielsantos25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(16, '2025-0002', 'marias', 'lopez', 'delacruz', 'Female', '2025', 1, 'mariadlc25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(17, '2025-0003', 'john', 'manuel', 'torres', 'Male', '2025', 1, 'jtorres25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(18, '2025-0004', 'angelica', 'mae', 'villanueva', 'Female', '2025', 1, 'angelvill25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(19, '2025-0005', 'kyle', 'andrew', 'reyes', 'Male', '2025', 1, 'kyle.reyes25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(20, '2025-0006', 'beatrice', 'luna', 'garcia', 'Female', '2025', 1, 'bealgarcia25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(21, '2025-0007', 'miguel', 'antonio', 'fernandez', 'Male', '2025', 1, 'migfernandez25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(22, '2025-0008', 'sophia', 'jane', 'cruz', 'Female', '2025', 1, 'sophiajcruz25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(23, '2025-0009', 'rafael', 'isidro', 'mendoza', 'Male', '2025', 1, 'rafmendoza25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(24, '2025-0010', 'kristine', 'ann', 'bautista', 'Female', '2025', 1, 'kbautista25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(25, '2025-0011', 'joshua', 'emil', 'dela rosa', 'Male', '2025', 1, 'jdrosa25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(26, '2025-0012', 'alyssa', 'kate', 'toribio', 'Female', '2025', 1, 'alytoribio25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(27, '2025-0013', 'nathaniel', 'jose', 'salazar', 'Male', '2025', 1, 'njsalazar25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(28, '2025-0014', 'camille', 'joy', 'ramos', 'Female', '2025', 1, 'cramos25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(29, '2025-0015', 'vincent', 'leo', 'lim', 'Male', '2025', 1, 'vinleolim25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(30, '2025-0016', 'hannah', 'claire', 'santos', 'Female', '2025', 1, 'hcsantos25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(31, '2025-0017', 'jerome', 'david', 'morales', 'Male', '2025', 1, 'jeromem25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(32, '2025-0018', 'cheska', 'denise', 'uy', 'Female', '2025', 1, 'cheska.uy25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(33, '2025-0019', 'leo', 'francis', 'aguirre', 'Male', '2025', 1, 'leofaguirre25@gmail.com', 0, 'avatar.png', 1, '2025-05-09'),
(34, '2025-0020', 'isabella', 'marie', 'tan', 'Female', '2025', 1, 'isabellatan25@gmail.com', 0, 'avatar.png', 1, '2025-05-09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alumnus_bio`
--
ALTER TABLE `alumnus_bio`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alumnus_bio`
--
ALTER TABLE `alumnus_bio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
