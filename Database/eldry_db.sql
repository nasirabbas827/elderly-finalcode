-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 03, 2025 at 11:31 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eldry_db`
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
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `elderly_id` int(11) NOT NULL,
  `family_member_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `type` enum('physical','virtual') NOT NULL,
  `status` enum('pending','approved','completed','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `elderly_id`, `family_member_id`, `appointment_date`, `type`, `status`, `notes`) VALUES
(1, 3, 3, '2025-06-02 13:51:00', 'physical', 'completed', 'ds');

-- --------------------------------------------------------

--
-- Table structure for table `daily_activities`
--

CREATE TABLE `daily_activities` (
  `activity_id` int(11) NOT NULL,
  `elderly_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `activity_type` enum('meal','medication','exercise','event','other') NOT NULL,
  `description` text NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `status` enum('scheduled','completed','skipped') DEFAULT 'scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_activities`
--

INSERT INTO `daily_activities` (`activity_id`, `elderly_id`, `date`, `activity_type`, `description`, `start_time`, `end_time`, `staff_id`, `status`) VALUES
(1, 1, '2025-06-02', 'medication', 'Nothing', '11:46:00', '11:51:00', NULL, 'completed'),
(2, 1, '2025-06-02', 'meal', 'sd', '13:06:00', '14:06:00', 1, 'scheduled'),
(3, 3, '2025-06-03', 'exercise', 'sd', '13:59:00', '14:59:00', 1, 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `donation_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `donation_date` date NOT NULL,
  `donation_type` varchar(20) NOT NULL,
  `transaction_picture` varchar(255) DEFAULT NULL,
  `payment_reference_id` bigint(20) DEFAULT NULL,
  `purpose` varchar(50) NOT NULL,
  `acknowledged` enum('pending','approved','not approved') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`donation_id`, `donor_id`, `amount`, `donation_date`, `donation_type`, `transaction_picture`, `payment_reference_id`, `purpose`, `acknowledged`) VALUES
(1, 2, 300.00, '2025-06-03', 'online', 'txn_683eb1b027f24.png', 453253, 'general', 'not approved');

-- --------------------------------------------------------

--
-- Table structure for table `elderly_members`
--

CREATE TABLE `elderly_members` (
  `member_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `admission_date` date NOT NULL,
  `family_member_id` int(11) DEFAULT NULL,
  `room_number` varchar(20) NOT NULL,
  `health_status` text DEFAULT NULL,
  `status` enum('active','deceased','transferred') DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elderly_members`
--

INSERT INTO `elderly_members` (`member_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `admission_date`, `family_member_id`, `room_number`, `health_status`, `status`, `profile_picture`, `created_at`) VALUES
(1, 'NASIR', 'ABBAS', '2025-06-06', 'Female', '2025-06-01', NULL, '1121', 'Checking Health', 'active', '1748931724_502-2.png', '2025-06-03 06:22:04'),
(2, 'Second ', 'Member', '2025-06-01', 'Female', '2025-06-01', NULL, '1122', 'ds', 'active', '1748931946_cs201.png', '2025-06-03 06:25:46'),
(3, 'John', 'Doe', '2025-06-03', 'Male', '2025-06-03', 3, '233', 'ds', 'active', '1748940315_cs201.png', '2025-06-03 08:45:15');

-- --------------------------------------------------------

--
-- Table structure for table `emergency_reports`
--

CREATE TABLE `emergency_reports` (
  `id` int(11) NOT NULL,
  `elderly_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `report_time` datetime NOT NULL,
  `description` text NOT NULL,
  `notified_admin` tinyint(1) DEFAULT 0,
  `notified_family` tinyint(1) DEFAULT 0,
  `status` enum('open','resolved') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergency_reports`
--

INSERT INTO `emergency_reports` (`id`, `elderly_id`, `reported_by`, `report_time`, `description`, `notified_admin`, `notified_family`, `status`) VALUES
(1, 1, 1, '2025-06-01 12:59:00', 'dfsa', 1, 1, 'open'),
(2, 2, 1, '2025-06-01 13:02:00', 'sd', 1, 1, 'open'),
(3, 3, 1, '2025-06-02 03:00:00', 'ds', 1, 1, 'resolved');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `elderly_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `record_date` date NOT NULL,
  `record_type` enum('checkup','treatment','other') NOT NULL,
  `diagnosis` text NOT NULL,
  `medications` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `severity` enum('normal','urgent','critical') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`id`, `elderly_id`, `staff_id`, `record_date`, `record_type`, `diagnosis`, `medications`, `notes`, `severity`, `created_at`) VALUES
(1, 1, NULL, '2025-06-02', 'treatment', 'dsd', 'dsds', 'dssd', 'urgent', '2025-06-03 06:40:20'),
(2, 1, 1, '2025-06-02', 'checkup', 'kjlda', 'lkesa', 'fdesa', 'critical', '2025-06-03 07:52:18'),
(3, 3, 1, '2025-06-03', 'checkup', 'ds', 'ds', 'sd', 'normal', '2025-06-03 08:59:37');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `designation` enum('caregiver','medical_staff','manager') NOT NULL,
  `shift` enum('morning','evening','night') NOT NULL,
  `assigned_area` varchar(100) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `joining_date` date NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `user_id`, `designation`, `shift`, `assigned_area`, `qualification`, `joining_date`, `salary`, `created_at`) VALUES
(1, 1, 'manager', 'evening', 'North Soyth', ' Obstetrician', '2025-06-01', 2344.00, '2025-06-03 06:30:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `user_type` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `phone`, `age`, `bio`, `user_type`, `profile_picture`, `status`) VALUES
(1, 'staff', '$2y$10$o6mWY5uFBLtkyRSI13zn1eaLmZzKBhn46d5f6dqlSw1KgPtVpwH6m', 'nasiryt.827@gmail.com', '9853', 23, NULL, 'staff', NULL, 'approved'),
(2, 'donor', '$2y$10$Mzcyp5Fm1G553blFM9yu6.Ma1mcttYwtMeeTHdbrhIJutvrui7HaK', 'donor@gmail.com', '3176526827', 23, 'fdsads', 'donor', 'images/1748938486_CS201p2.png', 'approved'),
(3, 'family member', '$2y$10$zdsioOZKIr18sIymtICmqeqot/FFdqwblR5oeUmPmKDYMa9nKflwy', 'family@gmail.com', '031887895222', 23, 'ds', 'family_member', 'images/1748941476_CS201p2-2.png', 'approved');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `elderly_id` (`elderly_id`),
  ADD KEY `family_member_id` (`family_member_id`);

--
-- Indexes for table `daily_activities`
--
ALTER TABLE `daily_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `elderly_id` (`elderly_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `donor_id` (`donor_id`);

--
-- Indexes for table `elderly_members`
--
ALTER TABLE `elderly_members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `family_member_id` (`family_member_id`);

--
-- Indexes for table `emergency_reports`
--
ALTER TABLE `emergency_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `elderly_id` (`elderly_id`),
  ADD KEY `reported_by` (`reported_by`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `elderly_id` (`elderly_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `daily_activities`
--
ALTER TABLE `daily_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `elderly_members`
--
ALTER TABLE `elderly_members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `emergency_reports`
--
ALTER TABLE `emergency_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`elderly_id`) REFERENCES `elderly_members` (`member_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`family_member_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `daily_activities`
--
ALTER TABLE `daily_activities`
  ADD CONSTRAINT `daily_activities_ibfk_1` FOREIGN KEY (`elderly_id`) REFERENCES `elderly_members` (`member_id`),
  ADD CONSTRAINT `daily_activities_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `elderly_members`
--
ALTER TABLE `elderly_members`
  ADD CONSTRAINT `elderly_members_ibfk_1` FOREIGN KEY (`family_member_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `emergency_reports`
--
ALTER TABLE `emergency_reports`
  ADD CONSTRAINT `emergency_reports_ibfk_1` FOREIGN KEY (`elderly_id`) REFERENCES `elderly_members` (`member_id`),
  ADD CONSTRAINT `emergency_reports_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`elderly_id`) REFERENCES `elderly_members` (`member_id`),
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
