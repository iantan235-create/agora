-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 13, 2026 at 07:08 AM
-- Server version: 8.0.44
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agora`
--

-- --------------------------------------------------------

--
-- Table structure for table `club_follows`
--

CREATE TABLE `club_follows` (
  `follow_id` int NOT NULL,
  `student_user_id` int NOT NULL,
  `organizer_user_id` int NOT NULL,
  `followed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `club_follows`
--

INSERT INTO `club_follows` (`follow_id`, `student_user_id`, `organizer_user_id`, `followed_at`) VALUES
(2, 1, 2, '2026-11-10 01:10:39');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `location` varchar(100) NOT NULL,
  `description` text,
  `organizer_id` int DEFAULT NULL,
  `event_status` varchar(20) NOT NULL DEFAULT 'active',
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `event_date`, `event_time`, `location`, `description`, `organizer_id`, `event_status`, `cancelled_at`) VALUES
(1, 'Beginner Guitar Workshop', '2026-11-18', '14:00:00', 'Taylor\'s Lakeside Campus', 'A beginner-friendly guitar workshop organized by Music Club.', NULL, 'active', NULL),
(3, 'Introduction to Blockchain', '2026-12-05', '14:00:00', 'D3.06', 'A beginner workshop introducing blockchain technology.', 2, 'cancelled', '2026-11-10 09:28:08'),
(5, 'Blockchain Workshop 2026', '2026-12-12', '08:00:00', 'D3.06', 'An introductory workshop for students interested in blockchain technology.', 2, 'active', NULL),
(6, 'Kloth Recyling', '2026-12-12', '08:00:00', 'C9.01', 'Learn how to recycle and reuse your old clothes', 2, 'active', NULL),
(7, 'Rubik Cubes', '2026-12-13', '15:00:00', 'C9.01', 'Have a cube learning class and competition', 2, 'active', NULL),
(8, 'xasxsx', '2028-02-12', '12:12:00', 'D3.06', '12121', 2, 'cancelled', '2026-11-10 10:51:29'),
(9, 'Welcome Party -- Music Club', '2026-11-19', '20:00:00', 'Lecture 21&22', 'You are here to enjoy music and introduced to know Music Club.', 3, 'active', NULL),
(10, 'Introduction to Lunar New Year', '2027-02-01', '10:00:00', 'LT 21&22', 'Learn what Lunar New Year is', 5, 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int NOT NULL,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `registered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `attendance_status` varchar(20) NOT NULL DEFAULT 'not_marked',
  `attendance_marked_at` datetime DEFAULT NULL,
  `checkin_token` char(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`registration_id`, `event_id`, `user_id`, `registered_at`, `attendance_status`, `attendance_marked_at`, `checkin_token`) VALUES
(5, 1, 1, '2026-11-10 01:07:01', 'not_marked', NULL, '7a02c20ff44086cf0c3c5c5b69bb31bc901aecaaf2ccf782a8d08663c3edc69b'),
(6, 3, 1, '2026-11-10 01:18:21', 'not_marked', NULL, 'bd08427ed174b13f06f33e0a48399df24824fb1d22c7b7446bb0ba96aa36bfeb'),
(7, 5, 1, '2026-11-10 01:37:17', 'not_marked', NULL, '0608d19177b88e99a2465bcda1c8d01741d570ab9edfd4b71d0370195f90a495'),
(9, 1, 7, '2026-11-13 06:56:29', 'not_marked', NULL, 'b7550de73700fab242b5b20e171d6109335baad6dd4e1ebf7b305c41cc80f39a'),
(10, 9, 1, '2026-11-13 06:58:47', 'not_marked', NULL, 'a020d12c9c9efab19753016ca10348997cf5186dc29b4b44ab5f921d43d5b984'),
(11, 10, 1, '2026-11-13 06:58:54', 'not_marked', NULL, 'b267b75221a9016f5df0ab0a7fd1332d6946b5f963241c6ce3ec432e7927dba6'),
(12, 6, 1, '2026-11-13 06:59:00', 'not_marked', NULL, 'f49141c10706a5c6bcea3b0706dabdf276d8cb058505d86d083d284086fbf302'),
(13, 7, 1, '2026-11-13 06:59:08', 'not_marked', NULL, 'bc3dca816d9652c9f57afef5cda39f7f3893d0e12007cba41f2f696a7a7a1042'),
(14, 9, 7, '2026-11-13 06:59:38', 'not_marked', NULL, '8d46e5393a9ee95e469d060b27415a5a28252b3f67b146e24a7fa19c56472093'),
(15, 5, 7, '2026-11-13 06:59:50', 'not_marked', NULL, '1ea73df58596e01cb68dc568d475b03f72e5a8e760d84d62e3f83fd6e4c71fef'),
(16, 6, 7, '2026-11-13 06:59:56', 'not_marked', NULL, '97821f40cd7cc41e63f591f667235d5219d16276c99001e94f2cb5af14812b66'),
(17, 7, 7, '2026-11-13 07:00:02', 'not_marked', NULL, '8ca778d37c910c2af1ba4b4a4a2176b17c259937b3d18944fa4c9e86ac252184'),
(18, 10, 7, '2026-11-13 07:00:13', 'not_marked', NULL, 'a7c8b967f7b6279ee05c0b49ba18152a5b20cd6d69a85e52a973c504e0fed793'),
(19, 1, 4, '2026-11-13 07:00:50', 'not_marked', NULL, 'fd6d00c0450471b57cc83b6814d91692655f78390503e69be4e24c39d79899a8'),
(20, 9, 4, '2026-11-13 07:00:56', 'not_marked', NULL, '53801316b68e48cfa294e235ac0238aa85b942e25d1f6836b59f04dda01529ec'),
(21, 5, 4, '2026-11-13 07:01:03', 'not_marked', NULL, 'ddc0f0db677ae4de671d55c803084b08b6dcc6f230ff8192c4bd067daa0c6ba9'),
(22, 6, 4, '2026-11-13 07:01:09', 'not_marked', NULL, '39ba5f687b02f0fca75bf50c5323fd85b99930bfe4d4d7d6368aa8ac99136d40'),
(23, 7, 4, '2026-11-13 07:01:15', 'not_marked', NULL, 'fb4dad668dd42c88880316e8b3ea7907d8cad9d73dce0d2e4ea12c2a3ca2f451'),
(24, 10, 4, '2026-11-13 07:01:22', 'not_marked', NULL, '8cfb6a17913b25df4f833826f55b0dcda0899608248353367671f2c831f175b8');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int NOT NULL,
  `user_id` int NOT NULL,
  `event_id` int DEFAULT NULL,
  `notification_type` varchar(30) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `event_id`, `notification_type`, `message`, `is_read`, `created_at`) VALUES
(3, 1, 1, 'registration', 'You have successfully registered for Beginner Guitar Workshop.', 1, '2026-11-10 01:07:01'),
(4, 1, 3, 'registration', 'You have successfully registered for Introduction to Blockchain.', 0, '2026-11-10 01:18:21'),
(5, 1, 6, 'new_event', 'New event: Kloth Recyling. A club you follow has published a new event.', 0, '2026-11-10 01:26:21'),
(6, 1, 7, 'new_event', 'New event: Rubik Cubes. A club you follow has published a new event.', 0, '2026-11-10 01:27:32'),
(7, 1, 3, 'event_cancelled', 'Event cancelled: Introduction to Blockchain. The organizer has cancelled this event.', 0, '2026-11-10 01:28:08'),
(8, 1, 5, 'registration', 'Registration confirmed: Blockchain Workshop 2026. You have successfully registered.', 0, '2026-11-10 01:37:17'),
(9, 1, 8, 'new_event', 'New event: xasxsx. A club you follow has published a new event.', 0, '2026-11-10 02:51:24'),
(10, 1, 9, 'registration', 'Registration confirmed: Welcome Party -- Music Club. You have successfully registered.', 0, '2026-11-11 08:14:41'),
(11, 7, 1, 'registration', 'Registration confirmed: Beginner Guitar Workshop. You have successfully registered.', 0, '2026-11-13 06:56:29'),
(12, 1, 9, 'registration', 'Registration confirmed: Welcome Party -- Music Club. You have successfully registered.', 0, '2026-11-13 06:58:47'),
(13, 1, 10, 'registration', 'Registration confirmed: Introduction to Lunar New Year. You have successfully registered.', 0, '2026-11-13 06:58:54'),
(14, 1, 6, 'registration', 'Registration confirmed: Kloth Recyling. You have successfully registered.', 0, '2026-11-13 06:59:00'),
(15, 1, 7, 'registration', 'Registration confirmed: Rubik Cubes. You have successfully registered.', 0, '2026-11-13 06:59:08'),
(16, 7, 9, 'registration', 'Registration confirmed: Welcome Party -- Music Club. You have successfully registered.', 0, '2026-11-13 06:59:38'),
(17, 7, 5, 'registration', 'Registration confirmed: Blockchain Workshop 2026. You have successfully registered.', 0, '2026-11-13 06:59:50'),
(18, 7, 6, 'registration', 'Registration confirmed: Kloth Recyling. You have successfully registered.', 0, '2026-11-13 06:59:56'),
(19, 7, 7, 'registration', 'Registration confirmed: Rubik Cubes. You have successfully registered.', 0, '2026-11-13 07:00:02'),
(20, 7, 10, 'registration', 'Registration confirmed: Introduction to Lunar New Year. You have successfully registered.', 0, '2026-11-13 07:00:13'),
(21, 4, 1, 'registration', 'Registration confirmed: Beginner Guitar Workshop. You have successfully registered.', 0, '2026-11-13 07:00:50'),
(22, 4, 9, 'registration', 'Registration confirmed: Welcome Party -- Music Club. You have successfully registered.', 0, '2026-11-13 07:00:56'),
(23, 4, 5, 'registration', 'Registration confirmed: Blockchain Workshop 2026. You have successfully registered.', 0, '2026-11-13 07:01:03'),
(24, 4, 6, 'registration', 'Registration confirmed: Kloth Recyling. You have successfully registered.', 0, '2026-11-13 07:01:09'),
(25, 4, 7, 'registration', 'Registration confirmed: Rubik Cubes. You have successfully registered.', 0, '2026-11-13 07:01:15'),
(26, 4, 10, 'registration', 'Registration confirmed: Introduction to Lunar New Year. You have successfully registered.', 0, '2026-11-13 07:01:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `student_id` varchar(30) DEFAULT NULL,
  `club_name` varchar(100) DEFAULT NULL,
  `account_status` varchar(20) NOT NULL DEFAULT 'pending',
  `club_description` text,
  `club_contact_email` varchar(100) DEFAULT NULL,
  `preferred_language` varchar(5) NOT NULL DEFAULT 'en'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role`, `student_id`, `club_name`, `account_status`, `club_description`, `club_contact_email`, `preferred_language`) VALUES
(1, 'Gita', '000000@sd.taylors.edu.my', '$2y$10$iI9rizN6tqnncYgcREqtCe3bhL2GBSromy9EoaUy2l79xowen.XQe', 'student', '0000000', NULL, 'approved', NULL, NULL, 'en'),
(2, 'Ultraman', '001110@sd.taylors.edu.my', '$2y$10$sdplmHCyg5r5P4a3ZfzfiuQidbv3hfGR4a4QWPpfSqRXlmFUEgfGe', 'organizer', NULL, 'Blockchain', 'approved', 'Blockchain club welcomes you', '0011100@sd.taylors.edu.my', 'en'),
(3, 'Joseph', '01234567@sd.taylors.edu.my', '$2y$10$.IV4q9rTsl7aPV1SoFmuZeZf6bW9cegdRn/17cmKq9gOFiK5uGfGS', 'organizer', NULL, 'Taylor\'s Music Club', 'approved', 'We are here to bring music into Taylor\'s. Join us and enjoy music in Taylor\'s.', 'taylorsmusic@sd.taylors.edu.my', 'en'),
(4, 'Trump', '23456789@sd.taylors.edu.my', '$2y$10$bNExL5lxDgHIY6eltimwpeGWuplTUHsWh1ln4.PUD0fmOx3rhytfi', 'student', '23456789', NULL, 'approved', NULL, NULL, 'en'),
(5, 'Adam', '03456789@sd.taylors.edu.my', '$2y$10$./4edpNg5cNBxAq0bV6IXezAz3grXq4.1VmaBia3/G0xa641KIY72', 'organizer', NULL, 'Chinese club', 'approved', 'Learn Chinese culture together', 'chineseclub@sd.taylors.edu.my', 'en'),
(6, 'Tan Teck Yao', '0374338@sd.taylors.edu.my', '$2y$10$aaeHfbFkQQRD8mRCyxUwC.3us0TCTBAGGCFyUNyBCbDkUQddTH8/S', 'student', '0374338', NULL, 'approved', NULL, NULL, 'en'),
(7, 'David', '32145678@sd.taylors.edu.my', '$2y$10$9oA3Da6Q/EjcJ.lx2iaGzuluYPHBTb6p1XosehbiyaYptDaAM4/Am', 'student', '32145678', NULL, 'approved', NULL, NULL, 'en');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `club_follows`
--
ALTER TABLE `club_follows`
  ADD PRIMARY KEY (`follow_id`),
  ADD UNIQUE KEY `student_user_id` (`student_user_id`,`organizer_user_id`),
  ADD KEY `organizer_user_id` (`organizer_user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `event_id` (`event_id`,`user_id`),
  ADD UNIQUE KEY `checkin_token` (`checkin_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `club_follows`
--
ALTER TABLE `club_follows`
  MODIFY `follow_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `club_follows`
--
ALTER TABLE `club_follows`
  ADD CONSTRAINT `club_follows_ibfk_1` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_follows_ibfk_2` FOREIGN KEY (`organizer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
