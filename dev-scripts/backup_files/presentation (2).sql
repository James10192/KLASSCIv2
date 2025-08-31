-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2025 at 10:40 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `presentation`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'absent',
  `remarks` text DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_excuses`
--

CREATE TABLE `attendance_excuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attendance_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `document_path` varchar(191) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(191) NOT NULL,
  `reference_number` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `issued_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_courses`
--

CREATE TABLE `class_courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_class_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `semester` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) NOT NULL,
  `hours` int(11) NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `ufr_id` bigint(20) UNSIGNED NOT NULL,
  `semester` varchar(191) NOT NULL,
  `academic_year` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `element_constitutifs`
--

CREATE TABLE `element_constitutifs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) NOT NULL DEFAULT 0,
  `hours_cm` int(11) NOT NULL DEFAULT 0 COMMENT 'Heures de cours magistraux',
  `hours_td` int(11) NOT NULL DEFAULT 0 COMMENT 'Heures de travaux dirigés',
  `hours_tp` int(11) NOT NULL DEFAULT 0 COMMENT 'Heures de travaux pratiques',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_absences`
--

CREATE TABLE `esbtp_absences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `hours` decimal(5,2) NOT NULL DEFAULT 1.00,
  `justified` tinyint(1) NOT NULL DEFAULT 0,
  `justification_text` text DEFAULT NULL,
  `justification_date` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_annee_universitaires`
--

CREATE TABLE `esbtp_annee_universitaires` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `libelle` varchar(191) DEFAULT NULL,
  `annee_debut` year(4) DEFAULT NULL,
  `annee_fin` year(4) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_annee_universitaires`
--

INSERT INTO `esbtp_annee_universitaires` (`id`, `name`, `libelle`, `annee_debut`, `annee_fin`, `start_date`, `end_date`, `is_current`, `is_active`, `description`, `created_at`, `updated_at`, `deleted_at`, `est_actif`) VALUES
(1, '2020-2021', NULL, '2020', '2021', '2020-09-15', '2021-07-15', 0, 0, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(2, '2021-2022', NULL, '2021', '2022', '2021-09-15', '2022-07-15', 0, 0, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(3, '2022-2023', NULL, '2022', '2023', '2022-09-15', '2023-07-15', 0, 0, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(4, '2023-2024', NULL, '2023', '2024', '2023-09-15', '2024-07-15', 0, 0, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(5, '2024-2025', NULL, '2024', '2025', '2024-09-15', '2025-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(6, '2025-2026', NULL, '2025', '2026', '2025-09-15', '2026-07-15', 1, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(7, '2026-2027', NULL, '2026', '2027', '2026-09-15', '2027-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(8, '2027-2028', NULL, '2027', '2028', '2027-09-15', '2028-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(9, '2028-2029', NULL, '2028', '2029', '2028-09-15', '2029-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(10, '2029-2030', NULL, '2029', '2030', '2029-09-15', '2030-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(11, '2030-2031', NULL, '2030', '2031', '2030-09-15', '2031-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(12, '2031-2032', NULL, '2031', '2032', '2031-09-15', '2032-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(13, '2032-2033', NULL, '2032', '2033', '2032-09-15', '2033-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(14, '2033-2034', NULL, '2033', '2034', '2033-09-15', '2034-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(15, '2034-2035', NULL, '2034', '2035', '2034-09-15', '2035-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(16, '2035-2036', NULL, '2035', '2036', '2035-09-15', '2036-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(17, '2036-2037', NULL, '2036', '2037', '2036-09-15', '2037-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(18, '2037-2038', NULL, '2037', '2038', '2037-09-15', '2038-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(19, '2038-2039', NULL, '2038', '2039', '2038-09-15', '2039-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(20, '2039-2040', NULL, '2039', '2040', '2039-09-15', '2040-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(21, '2040-2041', NULL, '2040', '2041', '2040-09-15', '2041-07-15', 0, 1, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL, 0),
(22, '2020-2021', NULL, '2020', '2021', '2020-09-15', '2021-07-15', 0, 0, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(23, '2021-2022', NULL, '2021', '2022', '2021-09-15', '2022-07-15', 0, 0, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(24, '2022-2023', NULL, '2022', '2023', '2022-09-15', '2023-07-15', 0, 0, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(25, '2023-2024', NULL, '2023', '2024', '2023-09-15', '2024-07-15', 0, 0, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(26, '2024-2025', NULL, '2024', '2025', '2024-09-15', '2025-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(27, '2025-2026', NULL, '2025', '2026', '2025-09-15', '2026-07-15', 1, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(28, '2026-2027', NULL, '2026', '2027', '2026-09-15', '2027-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(29, '2027-2028', NULL, '2027', '2028', '2027-09-15', '2028-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(30, '2028-2029', NULL, '2028', '2029', '2028-09-15', '2029-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(31, '2029-2030', NULL, '2029', '2030', '2029-09-15', '2030-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(32, '2030-2031', NULL, '2030', '2031', '2030-09-15', '2031-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(33, '2031-2032', NULL, '2031', '2032', '2031-09-15', '2032-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(34, '2032-2033', NULL, '2032', '2033', '2032-09-15', '2033-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(35, '2033-2034', NULL, '2033', '2034', '2033-09-15', '2034-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(36, '2034-2035', NULL, '2034', '2035', '2034-09-15', '2035-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(37, '2035-2036', NULL, '2035', '2036', '2035-09-15', '2036-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(38, '2036-2037', NULL, '2036', '2037', '2036-09-15', '2037-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(39, '2037-2038', NULL, '2037', '2038', '2037-09-15', '2038-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(40, '2038-2039', NULL, '2038', '2039', '2038-09-15', '2039-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(41, '2039-2040', NULL, '2039', '2040', '2039-09-15', '2040-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(42, '2040-2041', NULL, '2040', '2041', '2040-09-15', '2041-07-15', 0, 1, NULL, '2025-05-07 14:17:41', '2025-05-07 14:17:41', NULL, 0),
(43, '2020-2021', NULL, '2020', '2021', '2020-09-15', '2021-07-15', 0, 0, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(44, '2021-2022', NULL, '2021', '2022', '2021-09-15', '2022-07-15', 0, 0, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(45, '2022-2023', NULL, '2022', '2023', '2022-09-15', '2023-07-15', 0, 0, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(46, '2023-2024', NULL, '2023', '2024', '2023-09-15', '2024-07-15', 0, 0, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(47, '2024-2025', NULL, '2024', '2025', '2024-09-15', '2025-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(48, '2025-2026', NULL, '2025', '2026', '2025-09-15', '2026-07-15', 1, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(49, '2026-2027', NULL, '2026', '2027', '2026-09-15', '2027-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(50, '2027-2028', NULL, '2027', '2028', '2027-09-15', '2028-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(51, '2028-2029', NULL, '2028', '2029', '2028-09-15', '2029-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(52, '2029-2030', NULL, '2029', '2030', '2029-09-15', '2030-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(53, '2030-2031', NULL, '2030', '2031', '2030-09-15', '2031-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(54, '2031-2032', NULL, '2031', '2032', '2031-09-15', '2032-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(55, '2032-2033', NULL, '2032', '2033', '2032-09-15', '2033-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(56, '2033-2034', NULL, '2033', '2034', '2033-09-15', '2034-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(57, '2034-2035', NULL, '2034', '2035', '2034-09-15', '2035-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(58, '2035-2036', NULL, '2035', '2036', '2035-09-15', '2036-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(59, '2036-2037', NULL, '2036', '2037', '2036-09-15', '2037-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(60, '2037-2038', NULL, '2037', '2038', '2037-09-15', '2038-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(61, '2038-2039', NULL, '2038', '2039', '2038-09-15', '2039-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(62, '2039-2040', NULL, '2039', '2040', '2039-09-15', '2040-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(63, '2040-2041', NULL, '2040', '2041', '2040-09-15', '2041-07-15', 0, 1, NULL, '2025-05-07 14:27:48', '2025-05-07 14:27:48', NULL, 0),
(64, '2020-2021', NULL, '2020', '2021', '2020-09-15', '2021-07-15', 0, 0, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(65, '2021-2022', NULL, '2021', '2022', '2021-09-15', '2022-07-15', 0, 0, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(66, '2022-2023', NULL, '2022', '2023', '2022-09-15', '2023-07-15', 0, 0, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(67, '2023-2024', NULL, '2023', '2024', '2023-09-15', '2024-07-15', 0, 0, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(68, '2024-2025', NULL, '2024', '2025', '2024-09-15', '2025-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(69, '2025-2026', NULL, '2025', '2026', '2025-09-15', '2026-07-15', 1, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(70, '2026-2027', NULL, '2026', '2027', '2026-09-15', '2027-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(71, '2027-2028', NULL, '2027', '2028', '2027-09-15', '2028-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(72, '2028-2029', NULL, '2028', '2029', '2028-09-15', '2029-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(73, '2029-2030', NULL, '2029', '2030', '2029-09-15', '2030-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(74, '2030-2031', NULL, '2030', '2031', '2030-09-15', '2031-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(75, '2031-2032', NULL, '2031', '2032', '2031-09-15', '2032-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(76, '2032-2033', NULL, '2032', '2033', '2032-09-15', '2033-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(77, '2033-2034', NULL, '2033', '2034', '2033-09-15', '2034-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(78, '2034-2035', NULL, '2034', '2035', '2034-09-15', '2035-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(79, '2035-2036', NULL, '2035', '2036', '2035-09-15', '2036-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(80, '2036-2037', NULL, '2036', '2037', '2036-09-15', '2037-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(81, '2037-2038', NULL, '2037', '2038', '2037-09-15', '2038-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(82, '2038-2039', NULL, '2038', '2039', '2038-09-15', '2039-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(83, '2039-2040', NULL, '2039', '2040', '2039-09-15', '2040-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0),
(84, '2040-2041', NULL, '2040', '2041', '2040-09-15', '2041-07-15', 0, 1, NULL, '2025-05-07 15:10:07', '2025-05-07 15:10:07', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_annonces`
--

CREATE TABLE `esbtp_annonces` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titre` varchar(191) NOT NULL,
  `contenu` text NOT NULL,
  `type` enum('general','classe','etudiant') NOT NULL DEFAULT 'general',
  `date_publication` datetime NOT NULL,
  `date_expiration` datetime DEFAULT NULL,
  `priorite` tinyint(4) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_annonce_classe`
--

CREATE TABLE `esbtp_annonce_classe` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `annonce_id` bigint(20) UNSIGNED NOT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_annonce_etudiant`
--

CREATE TABLE `esbtp_annonce_etudiant` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `annonce_id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_annonce_lectures`
--

CREATE TABLE `esbtp_annonce_lectures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `annonce_id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `read_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_attendances`
--

CREATE TABLE `esbtp_attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `seance_cours_id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `statut` varchar(255) NOT NULL DEFAULT 'present',
  `date` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `commentaire` text DEFAULT NULL,
  `document_path` varchar(191) DEFAULT NULL,
  `justified_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_attendance_settings`
--

CREATE TABLE `esbtp_attendance_settings` (
  `id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `esbtp_attendance_settings`
--

INSERT INTO `esbtp_attendance_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'geolocation_required', '0', '2025-05-13 21:34:58', '2025-05-13 21:34:58');

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_bourses`
--

CREATE TABLE `esbtp_bourses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `type_bourse` varchar(191) NOT NULL,
  `montant` decimal(10,2) DEFAULT NULL,
  `pourcentage` decimal(5,2) DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `statut` varchar(191) NOT NULL DEFAULT 'active',
  `organisme_financeur` varchar(191) DEFAULT NULL,
  `conditions` text DEFAULT NULL,
  `commentaires` text DEFAULT NULL,
  `createur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_bulletins`
--

CREATE TABLE `esbtp_bulletins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `periode` enum('semestre1','semestre2','annuel') NOT NULL DEFAULT 'semestre1',
  `moyenne_generale` decimal(5,2) DEFAULT NULL,
  `rang` int(11) DEFAULT NULL,
  `effectif_classe` int(11) DEFAULT NULL,
  `appreciation_generale` text DEFAULT NULL,
  `config_matieres` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_matieres`)),
  `professeurs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`professeurs`)),
  `decision_conseil` varchar(191) DEFAULT NULL,
  `mention` varchar(191) DEFAULT NULL,
  `signature_directeur` tinyint(1) NOT NULL DEFAULT 0,
  `signature_responsable` tinyint(1) NOT NULL DEFAULT 0,
  `signature_parent` tinyint(1) NOT NULL DEFAULT 0,
  `date_signature_directeur` timestamp NULL DEFAULT NULL,
  `date_signature_responsable` timestamp NULL DEFAULT NULL,
  `date_signature_parent` timestamp NULL DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `absences_justifiees` double(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Nombre d''heures d''absences justifiées',
  `absences_non_justifiees` double(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Nombre d''heures d''absences non justifiées',
  `total_absences` double(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Total des heures d''absences',
  `note_assiduite` double(8,2) DEFAULT NULL COMMENT 'Note d''assiduité calculée sur les absences',
  `details_absences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Détails des absences au format JSON' CHECK (json_valid(`details_absences`))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_bulletin_details`
--

CREATE TABLE `esbtp_bulletin_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bulletin_id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `note_cc` decimal(5,2) DEFAULT NULL,
  `note_examen` decimal(5,2) DEFAULT NULL,
  `moyenne` decimal(5,2) DEFAULT NULL,
  `moyenne_classe` decimal(5,2) DEFAULT NULL,
  `coefficient` decimal(5,2) NOT NULL DEFAULT 1.00,
  `credits` int(11) DEFAULT NULL,
  `credits_valides` int(11) DEFAULT NULL,
  `rang` int(11) DEFAULT NULL,
  `effectif` int(11) DEFAULT NULL,
  `appreciation` text DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_categories_depenses`
--

CREATE TABLE `esbtp_categories_depenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_categories_depenses`
--

INSERT INTO `esbtp_categories_depenses` (`id`, `nom`, `code`, `description`, `parent_id`, `est_actif`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Salaires et charges sociales', 'SALAIRES', 'Dépenses liées aux salaires, primes, indemnités et charges sociales du personnel', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(2, 'Fournitures pédagogiques', 'PEDAGOG', 'Matériel pédagogique, manuels, supports de cours, etc.', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(3, 'Équipements informatiques', 'EQUIP_INFO', 'Ordinateurs, imprimantes, projecteurs, et autres matériels informatiques', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(4, 'Maintenance et réparations', 'MAINT', 'Entretien des bâtiments, réparations diverses, maintenance des équipements', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(5, 'Frais administratifs', 'ADMIN', 'Dépenses administratives générales', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(6, 'Factures d\'eau et électricité', 'UTIL', 'Factures d\'eau, d\'électricité et autres services publics', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(7, 'Services de nettoyage', 'NETTOYAGE', 'Services de nettoyage et d\'entretien des locaux', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(8, 'Sécurité', 'SECURITE', 'Services de sécurité, équipements de sécurité, etc.', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(9, 'Assurances', 'ASSUR', 'Assurances diverses (responsabilité civile, locaux, etc.)', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(10, 'Frais de communication', 'COMM', 'Téléphone, internet, frais postaux, etc.', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(11, 'Matériel de bureau', 'BUREAU', 'Fournitures de bureau, papeterie, etc.', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(12, 'Logiciels et licences', 'LOGICIEL', 'Logiciels, licences, abonnements à des services numériques', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(13, 'Frais de déplacement', 'DEPLACE', 'Transports, missions, frais de déplacement du personnel', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(14, 'Frais de formation du personnel', 'FORMATION', 'Formations, séminaires, conférences pour le personnel', NULL, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_categorie_paiements`
--

CREATE TABLE `esbtp_categorie_paiements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `icone` varchar(191) NOT NULL DEFAULT 'fas fa-money-bill-alt',
  `couleur` varchar(191) NOT NULL DEFAULT '#3498db',
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `est_obligatoire` tinyint(1) NOT NULL DEFAULT 0,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_categorie_paiements`
--

INSERT INTO `esbtp_categorie_paiements` (`id`, `nom`, `code`, `slug`, `description`, `icone`, `couleur`, `est_actif`, `est_obligatoire`, `parent_id`, `ordre`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Frais de scolarité', 'SCOLARITE', 'frais-de-scolarite', 'Frais couvrant la formation académique pour l\'année universitaire', 'fas fa-graduation-cap', '#3498db', 1, 1, NULL, 1, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(2, 'Frais d\'inscription', 'INSCRIPTION', 'frais-inscription', 'Frais administratifs pour l\'inscription à l\'établissement', 'fas fa-file-signature', '#2ecc71', 1, 1, NULL, 2, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(3, 'Frais de dossier', 'DOSSIER', 'frais-dossier', 'Frais pour le traitement du dossier administratif', 'fas fa-folder-open', '#f39c12', 1, 0, NULL, 3, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(4, 'Frais d\'examen', 'EXAMEN', 'frais-examen', 'Frais relatifs aux examens et évaluations', 'fas fa-edit', '#e74c3c', 1, 0, NULL, 4, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(5, 'Frais de laboratoire', 'LABORATOIRE', 'frais-laboratoire', 'Frais pour l\'utilisation des laboratoires et équipements scientifiques', 'fas fa-flask', '#9b59b6', 1, 0, NULL, 5, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(6, 'Frais de stage', 'STAGE', 'frais-stage', 'Frais liés à l\'organisation et au suivi des stages professionnels', 'fas fa-briefcase', '#34495e', 1, 0, NULL, 6, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(7, 'Frais de soutenance', 'SOUTENANCE', 'frais-soutenance', 'Frais pour l\'organisation et l\'évaluation des soutenances', 'fas fa-user-graduate', '#16a085', 1, 0, NULL, 7, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(8, 'Frais de diplôme', 'DIPLOME', 'frais-diplome', 'Frais pour l\'établissement et la délivrance du diplôme', 'fas fa-award', '#27ae60', 1, 0, NULL, 8, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(9, 'Location de matériel', 'LOCATION', 'location-materiel', 'Frais pour la location d\'équipements et de matériel pédagogique', 'fas fa-tools', '#f1c40f', 1, 0, NULL, 9, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(10, 'Services annexes', 'SERVICES', 'services-annexes', 'Services annexes tels que photocopies, impression, cartes étudiantes, etc.', 'fas fa-print', '#e67e22', 1, 0, NULL, 10, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(11, 'Pénalités de retard', 'PENALITES', 'penalites-retard', 'Pénalités appliquées en cas de retard de paiement', 'fas fa-exclamation-triangle', '#c0392b', 1, 0, NULL, 11, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL),
(12, 'Autres recettes', 'AUTRES', 'autres-recettes', 'Autres types de paiements et recettes divers', 'fas fa-ellipsis-h', '#7f8c8d', 1, 0, NULL, 12, '2025-05-07 11:39:28', '2025-05-07 11:39:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_classes`
--

CREATE TABLE `esbtp_classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `libelle` varchar(191) DEFAULT NULL,
  `code` varchar(191) NOT NULL,
  `filiere_id` bigint(20) UNSIGNED NOT NULL,
  `niveau_etude_id` bigint(20) UNSIGNED NOT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 50,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_classes`
--

INSERT INTO `esbtp_classes` (`id`, `name`, `libelle`, `code`, `filiere_id`, `niveau_etude_id`, `annee_universitaire_id`, `capacity`, `description`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '1ère année BTS Génie Civil Option Bâtiment', NULL, '1BTS-GC-BAT', 1, 1, 5, 30, NULL, 1, 1, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_classe_matiere`
--

CREATE TABLE `esbtp_classe_matiere` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `coefficient` double(8,2) NOT NULL DEFAULT 1.00,
  `total_heures` int(11) NOT NULL DEFAULT 30,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_classe_matiere`
--

INSERT INTO `esbtp_classe_matiere` (`id`, `classe_id`, `matiere_id`, `coefficient`, `total_heures`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(2, 1, 2, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(3, 1, 3, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(4, 1, 4, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(5, 1, 5, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(6, 1, 6, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(7, 1, 7, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(8, 1, 8, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(9, 1, 9, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(10, 1, 10, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(11, 1, 11, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(12, 1, 12, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(13, 1, 13, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(14, 1, 14, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(15, 1, 15, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(16, 1, 16, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(17, 1, 17, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(18, 1, 18, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(19, 1, 19, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(20, 1, 20, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(21, 1, 21, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(22, 1, 22, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(23, 1, 23, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(24, 1, 24, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(25, 1, 25, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(26, 1, 26, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(27, 1, 27, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(28, 1, 28, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(29, 1, 29, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(30, 1, 30, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(31, 1, 31, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(32, 1, 32, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(33, 1, 33, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(34, 1, 34, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(35, 1, 35, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(36, 1, 36, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(37, 1, 37, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(38, 1, 38, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(39, 1, 39, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(40, 1, 40, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(41, 1, 41, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11'),
(42, 1, 42, 1.00, 0, 1, '2025-05-07 15:17:11', '2025-05-07 15:17:11');

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_comptabilite_configurations`
--

CREATE TABLE `esbtp_comptabilite_configurations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cle` varchar(191) NOT NULL,
  `valeur` text DEFAULT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_config_matieres`
--

CREATE TABLE `esbtp_config_matieres` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `periode` varchar(191) NOT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `coefficient` decimal(3,1) NOT NULL DEFAULT 1.0,
  `nb_heures_cours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `nb_heures_td` decimal(5,2) NOT NULL DEFAULT 0.00,
  `nb_heures_tp` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_config_matiere_type_formations`
--

CREATE TABLE `esbtp_config_matiere_type_formations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `periode` varchar(191) NOT NULL DEFAULT 'annuel',
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`configuration`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_cours`
--

CREATE TABLE `esbtp_cours` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `enseignant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `jour` varchar(20) NOT NULL COMMENT 'Jour de la semaine',
  `heure_debut` time NOT NULL COMMENT 'Heure de début du cours',
  `heure_fin` time NOT NULL COMMENT 'Heure de fin du cours',
  `salle` varchar(191) DEFAULT NULL COMMENT 'Salle où se déroule le cours',
  `type` enum('CM','TD','TP') NOT NULL DEFAULT 'CM' COMMENT 'Type de cours: CM (Cours Magistral), TD (Travaux Dirigés), TP (Travaux Pratiques)',
  `periode` varchar(50) DEFAULT NULL COMMENT 'Période académique (S1, S2, etc.)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Indique si le cours est actif',
  `commentaire` text DEFAULT NULL COMMENT 'Commentaire ou note sur le cours',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_daily_codes`
--

CREATE TABLE `esbtp_daily_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_depenses`
--

CREATE TABLE `esbtp_depenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `categorie_id` bigint(20) UNSIGNED NOT NULL,
  `reference` varchar(191) NOT NULL,
  `libelle` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_depense` date NOT NULL,
  `mode_paiement` varchar(191) NOT NULL,
  `numero_transaction` varchar(191) DEFAULT NULL,
  `fournisseur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `statut` varchar(191) NOT NULL DEFAULT 'validée',
  `createur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `validateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_validation` timestamp NULL DEFAULT NULL,
  `path_justificatif` varchar(191) DEFAULT NULL,
  `notes_internes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_emploi_temps`
--

CREATE TABLE `esbtp_emploi_temps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titre` varchar(191) NOT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `semestre` varchar(191) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_emploi_temps`
--

INSERT INTO `esbtp_emploi_temps` (`id`, `titre`, `classe_id`, `semestre`, `date_debut`, `date_fin`, `annee_universitaire_id`, `is_active`, `is_current`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Emploi du temps BTS 1ère année Génie Civil - Semestre 1', 1, 'Semestre 1', '2025-05-05', '2025-05-09', 26, 0, 0, 1, NULL, '2025-05-07 15:28:55', '2025-05-14 20:08:49', NULL),
(2, 'Emploi du temps BTS 1ère année Génie Civil - Semestre 1', 1, 'Semestre 1', '2025-05-12', '2025-05-16', 69, 1, 1, 1, NULL, '2025-05-14 20:08:49', '2025-05-14 20:08:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_enseignant_matiere`
--

CREATE TABLE `esbtp_enseignant_matiere` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `enseignant_id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_enseignant_presence`
--

CREATE TABLE `esbtp_enseignant_presence` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `enseignant_id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `heure_arrivee` time DEFAULT NULL,
  `heure_depart` time DEFAULT NULL,
  `statut` varchar(191) NOT NULL DEFAULT 'present',
  `remarques` text DEFAULT NULL,
  `adresse_ip` varchar(191) DEFAULT NULL,
  `info_appareil` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_etudiants`
--

CREATE TABLE `esbtp_etudiants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `classe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `matricule` varchar(191) NOT NULL,
  `nom` varchar(191) NOT NULL,
  `prenoms` varchar(191) NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(191) DEFAULT NULL,
  `ville_naissance` varchar(191) DEFAULT NULL,
  `commune_naissance` varchar(191) DEFAULT NULL,
  `nationalite` varchar(191) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` varchar(191) DEFAULT NULL,
  `commune` varchar(191) DEFAULT NULL,
  `telephone` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `email_personnel` varchar(191) DEFAULT NULL,
  `photo` varchar(191) DEFAULT NULL,
  `statut` enum('actif','inactif','diplômé','abandon','exclu') NOT NULL DEFAULT 'actif',
  `groupe_sanguin` varchar(191) DEFAULT NULL,
  `situation_matrimoniale` varchar(191) DEFAULT NULL,
  `nombre_enfants` int(11) NOT NULL DEFAULT 0,
  `urgence_contact_nom` varchar(191) DEFAULT NULL,
  `urgence_contact_telephone` varchar(191) DEFAULT NULL,
  `urgence_contact_relation` varchar(191) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_etudiants`
--

INSERT INTO `esbtp_etudiants` (`id`, `user_id`, `classe_id`, `annee_universitaire_id`, `matricule`, `nom`, `prenoms`, `sexe`, `date_naissance`, `lieu_naissance`, `ville_naissance`, `commune_naissance`, `nationalite`, `adresse`, `ville`, `commune`, `telephone`, `email`, `email_personnel`, `photo`, `statut`, `groupe_sanguin`, `situation_matrimoniale`, `nombre_enfants`, `urgence_contact_nom`, `urgence_contact_telephone`, `urgence_contact_relation`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 6, NULL, NULL, 'ETU2025367', 'Test', 'Etudiant', 'M', '2000-01-01', 'Abidjan', NULL, NULL, NULL, 'Cocody', NULL, NULL, '0700000000', 'etudiant@esbtp.ci', NULL, NULL, 'actif', NULL, NULL, 0, NULL, NULL, NULL, 4, NULL, '2025-05-07 14:27:49', '2025-05-07 14:27:49', NULL),
(2, 9, NULL, NULL, '12041587AAZ', 'N\'guessan', 'Marc', 'M', '2002-04-16', 'Cocody, abidjan', NULL, NULL, NULL, NULL, 'Abidjan', 'Koumassi', '+2250705843901', NULL, 'djedje@gmail.com', NULL, 'actif', NULL, NULL, 0, NULL, NULL, NULL, 1, 1, '2025-05-14 17:38:12', '2025-05-14 17:38:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_etudiant_parent`
--

CREATE TABLE `esbtp_etudiant_parent` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED NOT NULL,
  `relation` varchar(191) NOT NULL COMMENT 'père, mère, tuteur, etc.',
  `is_tuteur` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_etudiant_parent`
--

INSERT INTO `esbtp_etudiant_parent` (`id`, `etudiant_id`, `parent_id`, `relation`, `is_tuteur`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'Tuteur', 1, '2025-05-14 17:38:12', '2025-05-14 17:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_evaluations`
--

CREATE TABLE `esbtp_evaluations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(191) NOT NULL,
  `type` enum('examen','devoir','controle','tp','projet','autre') NOT NULL DEFAULT 'examen',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_factures`
--

CREATE TABLE `esbtp_factures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `numero_facture` varchar(191) NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `inscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `date_emission` date NOT NULL,
  `date_echeance` date NOT NULL,
  `montant_ht` decimal(10,2) NOT NULL DEFAULT 0.00,
  `taux_taxe` decimal(5,2) NOT NULL DEFAULT 0.00,
  `montant_taxe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `montant_ttc` decimal(10,2) NOT NULL,
  `montant_regle` decimal(10,2) NOT NULL DEFAULT 0.00,
  `montant_du` decimal(10,2) NOT NULL,
  `statut` varchar(191) NOT NULL,
  `notes` text DEFAULT NULL,
  `path_pdf` varchar(191) DEFAULT NULL,
  `createur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `validateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_validation` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_facture_details`
--

CREATE TABLE `esbtp_facture_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `facture_id` bigint(20) UNSIGNED NOT NULL,
  `designation` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `total_ligne` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_filieres`
--

CREATE TABLE `esbtp_filieres` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `libelle` varchar(191) DEFAULT NULL,
  `code` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `parent_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_filieres`
--

INSERT INTO `esbtp_filieres` (`id`, `name`, `libelle`, `code`, `description`, `is_active`, `parent_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'BTS1 Tronc commun', NULL, 'BTS1-TC', 'BTS première année Tronc Commun', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL),
(2, 'BTS1 BATIMENT', NULL, 'BTS1-BAT', 'BTS première année Bâtiment', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL),
(3, 'BTS1 GTP', NULL, 'BTS1-GTP', 'BTS première année Génie civil option TRAVAUX D1 IBI', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 15:10:07', NULL),
(4, 'BTS1 GGT', NULL, 'BTS1-GGT', 'BTS première année Génie civil option GEOMETRE-TOPOGRAPHE', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL),
(5, 'BTS1 MGP', NULL, 'BTS1-MGP', 'BTS première année MINE - GEOLOGIE - PETROLE', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL),
(6, 'BTS1 URBANISME', NULL, 'BTS1-URB', 'BTS première année Génie civil option URBANISME', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL),
(7, 'BTS2 Tronc commun', NULL, 'BTS2-TC', 'BTS deuxième année Tronc Commun', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL),
(8, 'BTS2 BAT', NULL, 'BTS2-BAT', 'BTS deuxième année Bâtiment', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL),
(9, 'BTS2 GTP', NULL, 'BTS2-GTP', 'BTS deuxième année Génie des Travaux Publics', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL),
(10, 'BTS2 GGT', NULL, 'BTS2-GGT', 'BTS deuxième année Génie civil option GEOMETRE-TOPOGRAPHE', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL),
(11, 'BTS2 MGP', NULL, 'BTS2-MGP', 'BTS deuxième année MINE - GEOLOGIE - PETROLE', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL),
(12, 'BTS2 URBANISME', NULL, 'BTS2-URB', 'BTS deuxième année Génie civil option URBANISME', 1, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_filiere_niveau`
--

CREATE TABLE `esbtp_filiere_niveau` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `filiere_id` bigint(20) UNSIGNED NOT NULL,
  `niveau_etude_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_filiere_niveau`
--

INSERT INTO `esbtp_filiere_niveau` (`id`, `filiere_id`, `niveau_etude_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(2, 2, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(3, 3, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(4, 4, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(5, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(6, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(7, 7, 2, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(8, 8, 2, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(9, 9, 2, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(10, 10, 2, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(11, 11, 2, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(12, 12, 2, '2025-05-07 13:34:39', '2025-05-07 13:34:39');

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_fournisseurs`
--

CREATE TABLE `esbtp_fournisseurs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(191) NOT NULL,
  `nom` varchar(191) NOT NULL,
  `type` varchar(191) DEFAULT NULL,
  `adresse` varchar(191) DEFAULT NULL,
  `ville` varchar(191) DEFAULT NULL,
  `pays` varchar(191) NOT NULL DEFAULT 'Cote Ivoire',
  `telephone` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `site_web` varchar(191) DEFAULT NULL,
  `numero_fiscal` varchar(191) DEFAULT NULL,
  `compte_bancaire` varchar(191) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_frais_scolarite`
--

CREATE TABLE `esbtp_frais_scolarite` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `filiere_id` bigint(20) UNSIGNED NOT NULL,
  `niveau_etude_id` bigint(20) UNSIGNED NOT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  `frais_inscription` decimal(10,2) NOT NULL,
  `nombre_tranches` int(11) NOT NULL DEFAULT 1,
  `details_tranches` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details_tranches`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_inscriptions`
--

CREATE TABLE `esbtp_inscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `filiere_id` bigint(20) UNSIGNED NOT NULL,
  `niveau_id` bigint(20) UNSIGNED NOT NULL,
  `classe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_inscription` date NOT NULL,
  `type_inscription` enum('première_inscription','réinscription','transfert') NOT NULL DEFAULT 'première_inscription',
  `status` enum('en_attente','active','annulée','terminée') NOT NULL DEFAULT 'en_attente',
  `montant_scolarite` decimal(10,2) NOT NULL,
  `frais_inscription` decimal(10,2) NOT NULL,
  `numero_recu` varchar(191) DEFAULT NULL,
  `date_paiement` date DEFAULT NULL,
  `mode_paiement` varchar(191) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `documents_fournis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents_fournis`)),
  `date_validation` date DEFAULT NULL,
  `validated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_inscriptions`
--

INSERT INTO `esbtp_inscriptions` (`id`, `etudiant_id`, `annee_universitaire_id`, `filiere_id`, `niveau_id`, `classe_id`, `date_inscription`, `type_inscription`, `status`, `montant_scolarite`, `frais_inscription`, `numero_recu`, `date_paiement`, `mode_paiement`, `observations`, `documents_fournis`, `date_validation`, `validated_by`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 2, 5, 1, 1, 1, '2025-05-14', 'première_inscription', 'en_attente', 0.00, 0.00, 'INSC-2025-1088', NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-05-14 17:38:12', '2025-05-14 17:38:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_matieres`
--

CREATE TABLE `esbtp_matieres` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `coefficient` int(11) NOT NULL DEFAULT 1,
  `heures_cm` int(11) NOT NULL DEFAULT 0,
  `heures_td` int(11) NOT NULL DEFAULT 0,
  `heures_tp` int(11) NOT NULL DEFAULT 0,
  `heures_stage` int(11) NOT NULL DEFAULT 0,
  `heures_perso` int(11) NOT NULL DEFAULT 0,
  `niveau_etude_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type_formation` enum('generale','technologique_professionnelle') NOT NULL DEFAULT 'generale',
  `couleur` varchar(191) DEFAULT NULL,
  `coefficient_default` double(8,2) NOT NULL DEFAULT 1.00,
  `total_heures_default` int(11) NOT NULL DEFAULT 30,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_matieres`
--

INSERT INTO `esbtp_matieres` (`id`, `name`, `code`, `description`, `coefficient`, `heures_cm`, `heures_td`, `heures_tp`, `heures_stage`, `heures_perso`, `niveau_etude_id`, `type_formation`, `couleur`, `coefficient_default`, `total_heures_default`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Dessin Technique', 'DESSIN_TECHNIQUE', 'Matière : Dessin Technique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(2, 'Mathématiques', 'MATHEMATIQUES', 'Matière : Mathématiques', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(3, 'Physique', 'PHYSIQUE', 'Matière : Physique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(4, 'Chimie', 'CHIMIE', 'Matière : Chimie', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(5, 'Informatique', 'INFORMATIQUE', 'Matière : Informatique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(6, 'Français', 'FRANCAIS', 'Matière : Français', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(7, 'Anglais', 'ANGLAIS', 'Matière : Anglais', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(8, 'Résistance des Matériaux', 'RDM', 'Matière : Résistance des Matériaux', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(9, 'Mécanique des Sols', 'MDS', 'Matière : Mécanique des Sols', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(10, 'Topographie', 'TOPO', 'Matière : Topographie', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(11, 'Construction Métallique', 'CM', 'Matière : Construction Métallique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(12, 'Hydrologie', 'HYDROLOGIE', 'Matière : Hydrologie', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(13, 'Hydraulique', 'HYDRAULIQUE', 'Matière : Hydraulique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(14, 'Géotechnique', 'GEOTECHNIQUE', 'Matière : Géotechnique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(15, 'Technique des Engins', 'TECHNIQUE_ENGINS', 'Matière : Technique des Engins', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(16, 'IHH', 'IHH', 'Matière : IHH', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(17, 'Electricité', 'ELECTRICITE', 'Matière : Electricité', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(18, 'Sécurité', 'SECURITE', 'Matière : Sécurité', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(19, 'Matériaux', 'MATERIAUX', 'Matière : Matériaux', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(20, 'IGC', 'IGC', 'Matière : IGC', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(21, 'GRV', 'GRV', 'Matière : GRV', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(22, 'Calcul Topo', 'CALCUL_TOPO', 'Matière : Calcul Topo', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(23, 'Topo Générale', 'TOPO_GENERALE', 'Matière : Topo Générale', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:38', '2025-05-07 13:34:38', NULL),
(24, 'TP Topo', 'TP_TOPO', 'Matière : TP Topo', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(25, 'Géochimie', 'GEOCHIMIE', 'Matière : Géochimie', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(26, 'Géologie Générale', 'GEOLOGIE_GENERALE', 'Matière : Géologie Générale', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(27, 'Géologie Historique', 'GEOLOGIE_HISTORIQUE', 'Matière : Géologie Historique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(28, 'Mécanique des Sols', 'MECA_SOL', 'Matière : Mécanique des Sols', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(29, 'Mécanique des Roches', 'MECA_ROCHE', 'Matière : Mécanique des Roches', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(30, 'Minéralogie', 'MINERALOGIE', 'Matière : Minéralogie', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(31, 'Mécanique des Fluides', 'MECA_FLUIDES', 'Matière : Mécanique des Fluides', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(32, 'Topographie Minière', 'TOPO_MINIERE', 'Matière : Topographie Minière', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(33, 'Architecture', 'ARCHITECTURE', 'Matière : Architecture', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(34, 'Démographie', 'DEMOGRAPHIE', 'Matière : Démographie', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(35, 'Dessin Bâtiment', 'DESSIN_BATIMENT', 'Matière : Dessin Bâtiment', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(36, 'Géographie Urbaine', 'GEOGRAPHIE_URBAINE', 'Matière : Géographie Urbaine', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(37, 'Introduction à l\'urbanisme', 'INTRO_URBANISME', 'Matière : Introduction à l\'urbanisme', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(38, 'Lecture Photo', 'LECTURE_PHOTO', 'Matière : Lecture Photo', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(39, 'Métré et Etude de prix', 'METRE_PRIX', 'Matière : Métré et Etude de prix', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(40, 'Sociologie Urbaine', 'SOCIOLOGIE_URBAINE', 'Matière : Sociologie Urbaine', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(41, 'Technique Graphique', 'TECHNIQUE_GRAPHIQUE', 'Matière : Technique Graphique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(42, 'Technologie du Bâtiment', 'TECHNO_BAT', 'Matière : Technologie du Bâtiment', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(43, 'Archicad', 'ARCHICAD', 'Matière : Archicad', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(44, 'Béton Armé', 'BETON_ARME', 'Matière : Béton Armé', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(45, 'CAO-DAO', 'CAO_DAO', 'Matière : CAO-DAO', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(46, 'Droit', 'DROIT', 'Matière : Droit', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(47, 'Droit de la Construction', 'DROIT_CONSTRUCTION', 'Matière : Droit de la Construction', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(48, 'Entrepreneuriat', 'ENTREPRENEURIAT', 'Matière : Entrepreneuriat', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(49, 'Gestion', 'GESTION', 'Matière : Gestion', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(50, 'Métré et Etude de Prix', 'METRE_PRIX_1', 'Matière : Métré et Etude de Prix', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(51, 'OGC', 'OGC_1', 'Matière : OGC', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(52, 'Optique', 'OPTIQUE', 'Matière : Optique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(53, 'Projet', 'PROJET_1', 'Matière : Projet', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(54, 'Statique RDM', 'STATIQUE_RDM_1', 'Matière : Statique RDM', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(55, 'Technique de Recherche d\'emploi', 'TRE_1', 'Matière : Technique de Recherche d\'emploi', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(56, 'Technique d\'expression', 'TECHNIQUE_EXPRESSION', 'Matière : Technique d\'expression', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(57, 'Technologie du Bâtiment-Pathologie', 'TECHNO_BAT_PATHO', 'Matière : Technologie du Bâtiment-Pathologie', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(58, 'Topographie', 'TOPOGRAPHIE', 'Matière : Topographie', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(59, 'Urbanisme', 'URBANISME', 'Matière : Urbanisme', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(60, 'VRD', 'VRD', 'Matière : VRD', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(61, 'Béton Armé', 'BA', 'Matière : Béton Armé', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(62, 'Géotechnique', 'GEO', 'Matière : Géotechnique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(63, 'Routes', 'ROUTES', 'Matière : Routes', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(64, 'Hydraulique', 'HYDRO', 'Matière : Hydraulique', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(65, 'Alimentation en Eau potable', 'AEP', 'Matière : Alimentation en Eau potable', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(66, 'Assainissement', 'ASSAINISSEMENT', 'Matière : Assainissement', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(67, 'COVADIS', 'COVADIS', 'Matière : COVADIS', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(68, 'Dessin', 'DESSIN', 'Matière : Dessin', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(69, 'Drainage', 'DRAINAGE', 'Matière : Drainage', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(70, 'Entretien Routier', 'ENTRETIEN_ROUTIER', 'Matière : Entretien Routier', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(71, 'Environnement', 'ENVIRONNEMENT', 'Matière : Environnement', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(72, 'Métré et étude de Prix', 'METRE_PRIX_2', 'Matière : Métré et étude de Prix', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(73, 'OGC', 'OGC_2', 'Matière : OGC', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(74, 'Projet', 'PROJET_2', 'Matière : Projet', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(75, 'Qualité et Traitement des Eaux', 'QTE', 'Matière : Qualité et Traitement des Eaux', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(76, 'Signalisation Routière', 'SIGNALISATION', 'Matière : Signalisation Routière', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(77, 'Statique RDM', 'STATIQUE_RDM_2', 'Matière : Statique RDM', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(78, 'Technique de Recherche d\'emploi', 'TRE_2', 'Matière : Technique de Recherche d\'emploi', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(79, 'Techniques Routières', 'TECHNIQUES_ROUTIERES', 'Matière : Techniques Routières', 1, 0, 0, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(80, 'Mathématiques', 'MATH', 'Cours de mathématiques générales', 3, 30, 20, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(81, 'Physique', 'PHYS', 'Cours de physique générale', 3, 30, 20, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(82, 'Français', 'FR', 'Cours de français technique', 2, 20, 10, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(83, 'Anglais Technique', 'ANG', 'Cours d\'anglais technique', 2, 20, 10, 0, 0, 0, NULL, 'generale', NULL, 1.00, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_matiere_filiere`
--

CREATE TABLE `esbtp_matiere_filiere` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `filiere_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_matiere_filiere`
--

INSERT INTO `esbtp_matiere_filiere` (`id`, `matiere_id`, `filiere_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(2, 2, 1, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(3, 3, 1, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(4, 4, 1, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(5, 5, 1, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(6, 6, 1, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(7, 7, 1, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(8, 8, 2, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(9, 9, 2, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(10, 10, 2, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(11, 11, 2, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(12, 12, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(13, 13, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(14, 14, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(15, 15, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(16, 16, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(17, 8, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(18, 17, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(19, 18, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(20, 19, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(21, 20, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(22, 5, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(23, 21, 3, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(24, 2, 4, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(25, 22, 4, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(26, 23, 4, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(27, 17, 4, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(28, 18, 4, 1, '2025-05-07 13:34:38', '2025-05-07 13:34:38'),
(29, 5, 4, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(30, 24, 4, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(31, 17, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(32, 25, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(33, 26, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(34, 27, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(35, 12, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(36, 5, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(37, 28, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(38, 29, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(39, 30, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(40, 18, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(41, 31, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(42, 32, 5, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(43, 33, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(44, 34, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(45, 35, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(46, 17, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(47, 36, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(48, 16, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(49, 5, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(50, 37, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(51, 38, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(52, 39, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(53, 18, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(54, 40, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(55, 41, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(56, 42, 6, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(57, 7, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(58, 33, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(59, 43, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(60, 44, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(61, 45, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(62, 35, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(63, 46, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(64, 47, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(65, 48, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(66, 14, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(67, 49, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(68, 5, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(69, 19, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(70, 2, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(71, 50, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(72, 51, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(73, 52, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(74, 53, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(75, 54, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(76, 55, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(77, 56, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(78, 57, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(79, 58, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(80, 59, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(81, 60, 8, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(82, 61, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(83, 62, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(84, 63, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(85, 64, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(86, 65, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(87, 7, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(88, 66, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(89, 45, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(90, 67, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(91, 68, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(92, 69, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(93, 46, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(94, 48, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(95, 70, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(96, 71, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(97, 21, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(98, 49, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(99, 5, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(100, 19, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(101, 2, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(102, 72, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(103, 73, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(104, 74, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(105, 75, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(106, 76, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(107, 77, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(108, 78, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(109, 56, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(110, 79, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(111, 58, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39'),
(112, 60, 9, 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39');

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_matiere_niveau`
--

CREATE TABLE `esbtp_matiere_niveau` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `niveau_etude_id` bigint(20) UNSIGNED NOT NULL,
  `coefficient` int(11) NOT NULL DEFAULT 1,
  `heures_cours` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_matiere_niveau`
--

INSERT INTO `esbtp_matiere_niveau` (`id`, `matiere_id`, `niveau_etude_id`, `coefficient`, `heures_cours`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(2, 2, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(3, 3, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(4, 4, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(5, 5, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(6, 6, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(7, 7, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(8, 8, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(9, 9, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(10, 10, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(11, 11, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(12, 12, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(13, 13, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(14, 14, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(15, 15, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(16, 16, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(17, 17, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(18, 18, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(19, 19, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(20, 20, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(21, 21, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(22, 22, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(23, 23, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(24, 24, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(25, 25, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(26, 26, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(27, 27, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(28, 28, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(29, 29, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(30, 30, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(31, 31, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(32, 32, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(33, 33, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(34, 34, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(35, 35, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(36, 36, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(37, 37, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(38, 38, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(39, 39, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(40, 40, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(41, 41, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(42, 42, 1, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(43, 2, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(44, 5, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(45, 7, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(46, 19, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(47, 21, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(48, 35, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(49, 39, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(50, 43, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(51, 44, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(52, 45, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(53, 46, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(54, 47, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(55, 48, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(56, 49, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(57, 52, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(58, 56, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(59, 57, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(60, 58, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(61, 59, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(62, 60, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(63, 61, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(64, 62, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(65, 63, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(66, 64, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(67, 65, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(68, 66, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(69, 67, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(70, 68, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(71, 69, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(72, 70, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(73, 71, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(74, 75, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(75, 76, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(76, 79, 2, 1, 30, 1, NULL, NULL, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_niveau_etudes`
--

CREATE TABLE `esbtp_niveau_etudes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `libelle` varchar(191) DEFAULT NULL,
  `code` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL,
  `year` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_niveau_etudes`
--

INSERT INTO `esbtp_niveau_etudes` (`id`, `name`, `libelle`, `code`, `type`, `year`, `description`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Première année BTS', NULL, 'BTS1', 'BTS', 1, 'Niveau BTS première année - Formation sur 30 semaines', 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL),
(2, 'Deuxième année BTS', NULL, 'BTS2', 'BTS', 2, 'Niveau BTS deuxième année - Formation sur 28 semaines', 1, '2025-05-07 13:34:39', '2025-05-07 13:34:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_niveau_matiere`
--

CREATE TABLE `esbtp_niveau_matiere` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `niveau_id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_notes`
--

CREATE TABLE `esbtp_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `evaluation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `semestre` varchar(191) DEFAULT NULL,
  `annee_universitaire` varchar(191) DEFAULT NULL,
  `note` decimal(5,2) NOT NULL,
  `type_evaluation` varchar(191) DEFAULT NULL,
  `moyenne_matiere` decimal(5,2) DEFAULT NULL,
  `rang_matiere` int(11) DEFAULT NULL,
  `appreciation` text DEFAULT NULL,
  `is_absent` tinyint(1) NOT NULL DEFAULT 0,
  `commentaire` text DEFAULT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_paiements`
--

CREATE TABLE `esbtp_paiements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inscription_id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_paiement` date NOT NULL,
  `mode_paiement` varchar(191) NOT NULL COMMENT 'Espèces, chèque, virement, etc.',
  `reference_paiement` varchar(191) DEFAULT NULL COMMENT 'Numéro de chèque, de transaction, etc.',
  `tranche` varchar(191) DEFAULT NULL COMMENT 'Première tranche, deuxième tranche, etc.',
  `motif` varchar(191) NOT NULL COMMENT 'Scolarité, frais d''inscription, frais divers, etc.',
  `numero_recu` varchar(191) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `status` enum('en_attente','validé','rejeté') NOT NULL DEFAULT 'en_attente',
  `date_validation` date DEFAULT NULL,
  `validated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `categorie_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type_paiement` varchar(191) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_parents`
--

CREATE TABLE `esbtp_parents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nom` varchar(191) NOT NULL,
  `prenoms` varchar(191) NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `profession` varchar(191) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `telephone` varchar(191) NOT NULL,
  `telephone_secondaire` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `type_piece_identite` varchar(191) DEFAULT NULL COMMENT 'CNI, Passeport, etc.',
  `numero_piece_identite` varchar(191) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esbtp_parents`
--

INSERT INTO `esbtp_parents` (`id`, `user_id`, `nom`, `prenoms`, `sexe`, `profession`, `adresse`, `telephone`, `telephone_secondaire`, `email`, `type_piece_identite`, `numero_piece_identite`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, 'Marci', 'Marc', 'M', NULL, NULL, '+2258980189780', NULL, 'marc@gmail.com', NULL, NULL, 1, 1, '2025-05-14 17:38:12', '2025-05-14 17:38:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_resultats`
--

CREATE TABLE `esbtp_resultats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `etudiant_id` bigint(20) UNSIGNED NOT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `periode` varchar(191) NOT NULL DEFAULT 'semestre1',
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `moyenne` decimal(5,2) NOT NULL DEFAULT 0.00,
  `coefficient` decimal(5,2) NOT NULL DEFAULT 1.00,
  `rang` int(11) DEFAULT NULL,
  `appreciation` varchar(191) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_resultats_matieres`
--

CREATE TABLE `esbtp_resultats_matieres` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bulletin_id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `moyenne` decimal(5,2) NOT NULL,
  `coefficient` int(11) NOT NULL,
  `rang` int(11) DEFAULT NULL,
  `appreciation` varchar(191) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_salaires`
--

CREATE TABLE `esbtp_salaires` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `mois` int(11) NOT NULL,
  `annee` int(11) NOT NULL,
  `salaire_base` decimal(10,2) NOT NULL,
  `heures_supplementaires` decimal(10,2) NOT NULL DEFAULT 0.00,
  `primes` decimal(10,2) NOT NULL DEFAULT 0.00,
  `indemnites` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retenues` decimal(10,2) NOT NULL DEFAULT 0.00,
  `charges_sociales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `impots` decimal(10,2) NOT NULL DEFAULT 0.00,
  `montant_net` decimal(10,2) NOT NULL,
  `date_paiement` date DEFAULT NULL,
  `reference_paiement` varchar(191) DEFAULT NULL,
  `statut` varchar(191) NOT NULL DEFAULT 'calculé',
  `path_bulletin` varchar(191) DEFAULT NULL,
  `createur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `validateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_validation` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_seance_cours`
--

CREATE TABLE `esbtp_seance_cours` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `emploi_temps_id` bigint(20) UNSIGNED NOT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `matiere_id` bigint(20) UNSIGNED NOT NULL,
  `enseignant` varchar(191) DEFAULT NULL,
  `jour` varchar(191) NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `salle` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `type_seance` varchar(191) NOT NULL DEFAULT 'cours',
  `annee_universitaire_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_student_grades`
--

CREATE TABLE `esbtp_student_grades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `evaluation_id` bigint(20) UNSIGNED NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','exempt') NOT NULL DEFAULT 'present',
  `comment` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_transactions_financieres`
--

CREATE TABLE `esbtp_transactions_financieres` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(191) NOT NULL,
  `transactionable_type` varchar(191) NOT NULL,
  `transactionable_id` bigint(20) UNSIGNED NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `sens` varchar(191) NOT NULL,
  `categorie` varchar(191) NOT NULL,
  `reference` varchar(191) NOT NULL,
  `date_transaction` date NOT NULL,
  `description` text DEFAULT NULL,
  `compte_id` bigint(20) UNSIGNED DEFAULT NULL,
  `createur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esbtp_unites_enseignement`
--

CREATE TABLE `esbtp_unites_enseignement` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `credit` int(11) NOT NULL DEFAULT 0,
  `filiere_id` bigint(20) UNSIGNED DEFAULT NULL,
  `niveau_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `element_constitutif_id` bigint(20) UNSIGNED NOT NULL COMMENT 'EC concerné',
  `type` enum('examen','controle_continu','tp','projet','autre') NOT NULL DEFAULT 'examen',
  `coefficient` double(4,2) NOT NULL DEFAULT 1.00,
  `max_score` double(8,2) NOT NULL DEFAULT 20.00,
  `date_time` datetime NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Durée en minutes',
  `location` varchar(191) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si les résultats sont publiés',
  `publication_date` datetime DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `semester_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Étudiant concerné',
  `evaluation_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Évaluation concernée',
  `score` double(8,2) DEFAULT NULL COMMENT 'Note obtenue',
  `comments` text DEFAULT NULL COMMENT 'Commentaires sur la note',
  `is_absent` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si l''étudiant était absent',
  `is_excused` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si l''absence est justifiée',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Utilisateur ayant créé la note',
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Utilisateur ayant mis à jour la note',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laboratories`
--

CREATE TABLE `laboratories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient_type` varchar(191) DEFAULT NULL,
  `recipient_group` varchar(191) DEFAULT NULL,
  `subject` varchar(191) NOT NULL,
  `content` text NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_attachments`
--

CREATE TABLE `message_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(191) NOT NULL,
  `file_path` varchar(191) NOT NULL,
  `mime_type` varchar(191) NOT NULL,
  `file_size` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_recipients`
--

CREATE TABLE `message_recipients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(3, '2023_01_01_000000_create_permission_tables', 1),
(4, '2023_01_01_000001_add_additional_fields_to_permissions_and_roles_tables', 1),
(5, '2023_06_20_000002_create_student_grades_table', 1),
(6, '2024_07_10_000000_reorganize_controllers', 1),
(7, '2025_02_26_000000_create_parcours_table', 1),
(8, '2025_02_26_000001_create_departments_table', 1),
(9, '2025_02_26_000002_create_laboratories_table', 1),
(10, '2025_02_26_000003_create_element_constitutifs_table', 1),
(11, '2025_02_26_000004_create_ufrs_table', 1),
(12, '2025_02_26_000005_create_sessions_table', 1),
(13, '2025_02_26_000006_create_designations_table', 1),
(14, '2025_02_26_000007_create_sections_table', 1),
(15, '2025_02_27_200009_create_students_table', 1),
(16, '2025_02_27_200010_create_teachers_table', 1),
(17, '2025_02_27_200013_create_evaluations_table', 1),
(18, '2025_02_27_200014_create_grades_table', 1),
(19, '2025_02_27_214309_create_certificates_table', 1),
(20, '2025_02_27_215343_create_notifications_table', 1),
(21, '2025_02_27_215606_create_messages_table', 1),
(22, '2025_02_27_215802_create_courses_table', 1),
(23, '2025_02_27_215803_create_attendances_table', 1),
(24, '2025_02_27_220141_create_school_classes_table', 1),
(25, '2025_02_27_220239_create_class_courses_table', 1),
(26, '2025_02_28_000001_create_classes_table', 1),
(27, '2025_02_28_000005_create_subjects_table', 1),
(28, '2025_02_28_000006_create_school_teachers_table', 1),
(29, '2025_02_28_000008_create_timetables_table', 1),
(30, '2025_02_28_161015_create_esbtp_filieres_table', 1),
(31, '2025_02_28_161026_create_esbtp_niveau_etudes_table', 1),
(32, '2025_02_28_161331_create_esbtp_annee_universitaires_table', 1),
(33, '2025_02_28_233231_create_semesters_table', 1),
(34, '2025_03_01_000000_create_esbtp_classes_table', 1),
(35, '2025_03_01_000003_create_esbtp_matieres_table', 1),
(36, '2025_03_01_100000_create_esbtp_etudiants_table', 1),
(37, '2025_03_01_100001_create_esbtp_parents_table', 1),
(38, '2025_03_01_100002_create_esbtp_inscriptions_table', 1),
(39, '2025_03_01_100003_create_esbtp_paiements_table', 1),
(40, '2025_03_01_185915_add_additional_columns_to_permission_tables', 1),
(41, '2025_03_01_190250_modify_exams_table', 1),
(42, '2025_03_01_211638_create_settings_table', 1),
(43, '2025_03_02_075921_create_esbtp_cours_table', 1),
(44, '2025_03_02_131111_add_annee_debut_to_esbtp_annee_universitaires_table', 1),
(45, '2025_03_02_134953_add_role_column_to_users_table', 1),
(46, '2025_03_02_151331_add_last_login_at_to_users_table', 1),
(47, '2025_03_02_191756_create_esbtp_matiere_niveau_table', 1),
(48, '2025_03_02_192555_create_esbtp_unites_enseignement_table', 1),
(49, '2025_03_02_201532_add_active_column_to_esbtp_filieres', 1),
(50, '2025_03_02_223948_create_esbtp_matiere_filiere_table', 1),
(51, '2025_03_03_035615_add_email_to_esbtp_etudiants', 1),
(52, '2025_03_10_095537_create_esbtp_filiere_niveau_table', 1),
(53, '2025_03_10_105300_add_columns_to_esbtp_filiere_niveau_table', 1),
(54, '2025_03_10_113757_add_missing_columns_to_esbtp_filiere_niveau_table', 1),
(55, '2025_03_10_202825_remove_formation_id_from_esbtp_classes', 1),
(56, '2025_03_10_202942_remove_formation_references', 1),
(57, '2025_03_10_224800_create_esbtp_emploi_temps_table', 1),
(58, '2025_03_10_224812_add_is_current_to_esbtp_emploi_temps_table', 1),
(59, '2025_03_11_081600_create_esbtp_attendances_table', 1),
(60, '2025_03_11_081628_add_status_to_esbtp_attendances_table', 1),
(61, '2025_03_11_083905_add_libelle_to_esbtp_tables', 1),
(62, '2025_03_11_095748_add_est_actif_to_esbtp_annee_universitaires_table', 1),
(63, '2025_03_13_154632_add_last_login_at_to_users_table', 1),
(64, '2025_03_13_155400_create_esbtp_bulletins_table', 1),
(65, '2025_03_13_155406_update_esbtp_bulletins_table', 1),
(66, '2025_03_13_182823_create_esbtp_filiere_niveau_table', 1),
(67, '2025_03_13_183030_create_esbtp_matiere_niveau_table', 1),
(68, '2025_03_13_225219_add_type_and_year_to_esbtp_niveau_etudes_table', 1),
(69, '2025_03_13_230452_create_esbtp_inscriptions_table', 1),
(70, '2025_03_13_231220_create_esbtp_unites_enseignement_table', 1),
(71, '2025_03_13_231953_create_esbtp_matiere_filiere_table', 1),
(72, '2025_03_13_232731_update_esbtp_matiere_niveau_table', 1),
(73, '2025_03_13_232830_recreate_esbtp_matiere_niveau_table', 1),
(74, '2025_03_13_233440_create_esbtp_unites_enseignement_table', 1),
(75, '2025_03_14_154425_create_esbtp_seance_cours_table', 1),
(76, '2025_03_14_233112_update_esbtp_emploi_temps_table', 1),
(77, '2025_03_15_002142_add_emploi_temps_id_to_esbtp_seance_cours_table', 1),
(78, '2025_03_15_004836_fix_emploi_temps_id_in_esbtp_seance_cours_table', 1),
(79, '2025_03_15_140635_update_esbtp_seance_cours_table_for_enseignant_text', 1),
(80, '2025_03_16_143325_add_contact_info_to_users_table', 1),
(81, '2025_03_16_153718_create_esbtp_annonces_table', 1),
(82, '2025_03_16_204249_add_missing_fields_to_esbtp_seance_cours_table', 1),
(83, '2025_03_16_222526_add_foreign_key_constraint_to_esbtp_seances_cours_table', 1),
(84, '2025_03_16_230825_update_esbtp_attendances_table_for_seance_cours', 1),
(85, '2025_03_17_000000_add_missing_columns_to_esbtp_evaluations_table', 1),
(86, '2025_03_17_161433_add_missing_columns_to_esbtp_evaluations_table', 1),
(87, '2025_03_17_164152_add_evaluation_id_to_esbtp_notes_table', 1),
(88, '2025_03_17_164854_add_is_absent_to_esbtp_notes_table', 1),
(89, '2025_03_17_165647_add_commentaire_to_esbtp_notes_table', 1),
(90, '2025_03_17_210856_add_periode_to_esbtp_evaluations_table', 1),
(91, '2025_03_18_000000_add_missing_columns_to_esbtp_etudiants', 1),
(92, '2025_03_18_103356_add_composite_index_to_esbtp_notes_table', 1),
(93, '2025_03_18_153313_align_semestre_periode_types', 1),
(94, '2025_03_24_000000_create_esbtp_notes_table', 1),
(95, '2025_03_25_143735_add_professional_info_to_users_table', 1),
(96, '2025_03_26_000000_create_esbtp_resultats_matieres_table', 1),
(97, '2025_03_26_110000_create_esbtp_absences_table', 1),
(98, '2025_03_27_000000_add_mention_and_signatures_to_bulletins', 1),
(99, '2025_03_27_120400_add_config_matieres_table', 1),
(100, '2025_03_27_185955_create_esbtp_config_matiere_type_formations_table', 1),
(101, '2025_03_28_141900_create_jobs_table', 1),
(102, '2025_03_28_144333_add_document_path_to_esbtp_attendances_table', 1),
(103, '2025_03_28_165542_create_failed_jobs_table', 1),
(104, '2025_03_28_214511_create_esbtp_annonce_lectures_table', 1),
(105, '2025_03_30_000002_update_esbtp_notes_table', 1),
(106, '2025_04_01_000000_add_deleted_at_to_users_table', 1),
(107, '2025_04_01_000001_add_first_name_last_name_to_users_table', 1),
(108, '2025_04_01_101453_add_soft_delete_to_users_table', 1),
(109, '2025_04_01_112035_add_ville_commune_to_esbtp_etudiants_table', 1),
(110, '2025_04_01_112712_add_ville_commune_naissance_to_esbtp_etudiants_table', 1),
(111, '2025_04_02_091352_add_missing_commentaire_to_esbtp_notes_table', 1),
(112, '2025_04_02_094730_add_config_column_to_esbtp_config_matieres_table', 1),
(113, '2025_04_02_095249_add_user_tracking_to_esbtp_config_matieres_table', 1),
(114, '2025_04_02_161809_add_config_matieres_to_bulletins_table', 1),
(115, '2025_04_02_195141_add_professeurs_to_esbtp_bulletins_table', 1),
(116, '2025_04_04_152307_create_esbtp_bulletin_details_table', 1),
(117, '2025_04_08_091936_add_absences_fields_to_esbtp_bulletins_table', 1),
(118, '2025_04_09_134922_add_profile_photo_path_to_users_table', 1),
(119, '2025_04_15_000001_cleanup_filiere_management', 1),
(120, '2025_04_23_204155_add_evaluation_grades', 1),
(121, '2025_04_23_231835_create_esbtp_comptabilite_tables', 1),
(122, '2025_04_28_111123_create_e_s_b_t_p_categorie_paiements_table', 1),
(123, '2025_04_28_111249_add_categorie_id_to_esbtp_paiements_table', 1),
(124, '2025_05_05_000000_create_esbtp_resultats_table', 1),
(125, '2025_05_10_000001_create_esbtp_evaluations_table', 1),
(126, '2025_05_10_000002_create_esbtp_student_grades_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(1, 'App\\Models\\User', 2),
(1, 'App\\Models\\User', 4),
(2, 'App\\Models\\User', 5),
(3, 'App\\Models\\User', 6),
(3, 'App\\Models\\User', 9),
(4, 'App\\Models\\User', 8),
(5, 'App\\Models\\User', 3),
(5, 'App\\Models\\User', 7);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(191) NOT NULL,
  `notifiable_type` varchar(191) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parcours`
--

CREATE TABLE `parcours` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(125) NOT NULL,
  `guard_name` varchar(125) NOT NULL,
  `category` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `category`, `description`, `created_at`, `updated_at`) VALUES
(1, 'create filieres', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(2, 'view filieres', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(3, 'edit filieres', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(4, 'delete filieres', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(5, 'create niveau etudes', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(6, 'view niveau etudes', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(7, 'edit niveau etudes', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(8, 'delete niveau etudes', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(9, 'create classes', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(10, 'view classes', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(11, 'edit classes', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(12, 'delete classes', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(13, 'create students', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(14, 'view students', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(15, 'edit students', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(16, 'delete students', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(17, 'view own profile', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(18, 'view own grades', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(19, 'view own timetable', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(20, 'view own bulletin', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(21, 'view own attendances', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(22, 'view own exams', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(23, 'receive own messages', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(24, 'create exams', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(25, 'view exams', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(26, 'edit exams', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(27, 'delete exams', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(28, 'create matieres', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(29, 'view matieres', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(30, 'edit matieres', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(31, 'delete matieres', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(32, 'create grades', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(33, 'view grades', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(34, 'edit grades', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(35, 'delete grades', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(36, 'generate bulletin', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(37, 'view bulletins', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(38, 'edit bulletins', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(39, 'delete bulletins', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(40, 'create timetable', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(41, 'view timetables', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(42, 'edit timetables', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(43, 'delete timetables', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(44, 'send messages', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(45, 'receive messages', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(46, 'create attendance', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(47, 'view attendances', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(48, 'edit attendances', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(49, 'delete attendances', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(50, 'inscriptions.view', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(51, 'inscriptions.create', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(52, 'inscriptions.edit', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(53, 'inscriptions.delete', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(54, 'inscriptions.validate', 'web', NULL, NULL, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(55, 'view_filieres', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(56, 'create_filieres', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(57, 'edit_filieres', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(58, 'delete_filieres', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(59, 'view_formations', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(60, 'create_formations', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(61, 'edit_formations', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(62, 'delete_formations', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(63, 'view_niveaux_etudes', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(64, 'create_niveaux_etudes', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(65, 'edit_niveaux_etudes', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(66, 'delete_niveaux_etudes', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(67, 'view_classes', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(68, 'create_classe', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(69, 'edit_classes', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(70, 'delete_classes', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(71, 'view_students', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(72, 'create_student', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(73, 'edit_students', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(74, 'delete_students', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(75, 'view_own_profile', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(76, 'view_exams', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(77, 'create_exam', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(78, 'edit_exams', 'web', NULL, NULL, '2025-05-07 13:41:21', '2025-05-07 13:41:21'),
(79, 'delete_exams', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(80, 'view_own_exams', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(81, 'view_matieres', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(82, 'create_matieres', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(83, 'edit_matieres', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(84, 'delete_matieres', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(85, 'view_grades', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(86, 'create_grade', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(87, 'edit_grades', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(88, 'delete_grades', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(89, 'view_own_grades', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(90, 'view_bulletins', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(91, 'generate_bulletin', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(92, 'edit_bulletins', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(93, 'delete_bulletins', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(94, 'view_own_bulletin', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(95, 'view_timetables', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(96, 'create_timetable', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(97, 'edit_timetables', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(98, 'delete_timetables', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(99, 'view_own_timetable', 'web', NULL, NULL, '2025-05-07 13:41:22', '2025-05-07 13:41:22'),
(100, 'send_messages', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(101, 'receive_messages', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(102, 'view_attendances', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(103, 'create_attendance', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(104, 'edit_attendances', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(105, 'delete_attendances', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(106, 'view_own_attendances', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(107, 'view-paiements', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(108, 'create-paiements', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(109, 'edit-paiements', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(110, 'delete-paiements', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(111, 'validate-paiements', 'web', NULL, NULL, '2025-05-07 13:41:23', '2025-05-07 13:41:23'),
(112, 'access_comptabilite_module', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(113, 'view_paiements', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(114, 'create_paiements', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(115, 'edit_paiements', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(116, 'delete_paiements', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(117, 'validate_paiements', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(118, 'view_frais_scolarite', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(119, 'view_depenses', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(120, 'view_bourses', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(121, 'view_rapports_financiers', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(122, 'view_comptabilite_dashboard', 'web', NULL, NULL, '2025-05-08 09:40:04', '2025-05-08 09:40:04'),
(123, 'access_comptabilite', 'web', NULL, NULL, '2025-05-08 09:40:05', '2025-05-08 09:40:05'),
(124, 'view_comptabilite', 'web', NULL, NULL, '2025-05-08 09:40:05', '2025-05-08 09:40:05'),
(125, 'view_payments', 'web', NULL, NULL, '2025-05-08 09:40:05', '2025-05-08 09:40:05'),
(126, 'create_payment', 'web', NULL, NULL, '2025-05-08 09:40:05', '2025-05-08 09:40:05'),
(127, 'edit_payment', 'web', NULL, NULL, '2025-05-08 09:40:05', '2025-05-08 09:40:05'),
(128, 'delete_payment', 'web', NULL, NULL, '2025-05-08 09:40:05', '2025-05-08 09:40:05'),
(129, 'validate_payment', 'web', NULL, NULL, '2025-05-08 09:40:05', '2025-05-08 09:40:05'),
(130, 'create_frais_scolarite', 'web', NULL, NULL, '2025-05-08 10:42:03', '2025-05-08 10:42:03'),
(131, 'edit_frais_scolarite', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(132, 'delete_frais_scolarite', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(133, 'create_depenses', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(134, 'edit_depenses', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(135, 'delete_depenses', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(136, 'view_salaires', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(137, 'create_salaires', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(138, 'edit_salaires', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(139, 'delete_salaires', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(140, 'create_bourses', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(141, 'edit_bourses', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(142, 'delete_bourses', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(143, 'view_reporting_financier', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(144, 'export_reporting_financier', 'web', NULL, NULL, '2025-05-08 10:42:04', '2025-05-08 10:42:04'),
(145, 'create_students', 'web', NULL, NULL, '2025-05-14 19:44:51', '2025-05-14 19:44:51'),
(146, 'view_own_attendance', 'web', NULL, 'Voir ses propres émargements', '2025-05-14 19:49:31', '2025-05-14 19:49:31'),
(147, 'sign_attendance', 'web', NULL, 'Signer sa présence', '2025-05-14 19:49:31', '2025-05-14 19:49:31'),
(148, 'manage_attendance_codes', 'web', NULL, 'Gérer les codes d\'émargement', '2025-05-14 19:49:31', '2025-05-14 19:49:31'),
(149, 'validate_attendance', 'web', NULL, 'Valider les émargements', '2025-05-14 19:49:31', '2025-05-14 19:49:31'),
(150, 'view_all_attendance', 'web', NULL, 'Voir tous les émargements', '2025-05-14 19:49:31', '2025-05-14 19:49:31');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(125) NOT NULL,
  `guard_name` varchar(125) NOT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `description`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 'superAdmin', 'web', NULL, 0, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(2, 'secretaire', 'web', NULL, 0, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(3, 'etudiant', 'web', NULL, 0, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(4, 'enseignant', 'web', NULL, 0, '2025-05-07 11:39:32', '2025-05-07 11:39:32'),
(5, 'teacher', 'web', NULL, 0, '2025-05-07 13:34:39', '2025-05-07 13:34:39');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(2, 2),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(10, 2),
(10, 4),
(11, 1),
(12, 1),
(13, 1),
(13, 2),
(14, 1),
(14, 2),
(14, 4),
(15, 1),
(16, 1),
(17, 1),
(17, 3),
(18, 1),
(18, 3),
(19, 1),
(19, 3),
(20, 1),
(20, 3),
(21, 1),
(21, 3),
(22, 1),
(22, 3),
(23, 1),
(23, 3),
(24, 1),
(25, 1),
(25, 2),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(29, 2),
(29, 4),
(30, 1),
(31, 1),
(32, 1),
(32, 2),
(32, 4),
(33, 1),
(33, 2),
(33, 4),
(34, 1),
(35, 1),
(36, 1),
(36, 2),
(37, 1),
(37, 2),
(38, 1),
(39, 1),
(40, 1),
(40, 2),
(41, 1),
(41, 2),
(41, 4),
(42, 1),
(43, 1),
(44, 1),
(44, 2),
(44, 4),
(45, 1),
(46, 1),
(46, 2),
(46, 4),
(47, 1),
(47, 2),
(47, 4),
(48, 1),
(48, 2),
(49, 1),
(50, 1),
(50, 2),
(51, 1),
(51, 2),
(52, 1),
(52, 2),
(53, 1),
(54, 1),
(54, 2),
(55, 1),
(56, 1),
(57, 1),
(58, 1),
(59, 1),
(60, 1),
(61, 1),
(62, 1),
(63, 1),
(64, 1),
(65, 1),
(66, 1),
(67, 1),
(68, 1),
(69, 1),
(70, 1),
(71, 1),
(71, 2),
(72, 1),
(72, 2),
(73, 1),
(73, 2),
(74, 1),
(75, 1),
(76, 1),
(77, 1),
(78, 1),
(79, 1),
(80, 1),
(81, 1),
(81, 2),
(82, 1),
(83, 1),
(84, 1),
(85, 1),
(86, 1),
(87, 1),
(88, 1),
(89, 1),
(90, 1),
(90, 2),
(91, 1),
(91, 2),
(92, 1),
(93, 1),
(94, 1),
(95, 1),
(95, 2),
(96, 1),
(96, 2),
(97, 1),
(97, 2),
(98, 1),
(99, 1),
(99, 4),
(100, 1),
(100, 2),
(101, 1),
(101, 2),
(102, 1),
(102, 2),
(103, 1),
(103, 2),
(104, 1),
(104, 2),
(105, 1),
(106, 1),
(107, 1),
(107, 2),
(108, 1),
(108, 2),
(109, 1),
(109, 2),
(110, 1),
(111, 1),
(111, 2),
(112, 1),
(112, 2),
(113, 1),
(113, 2),
(114, 1),
(114, 2),
(115, 1),
(115, 2),
(116, 1),
(116, 2),
(117, 2),
(118, 1),
(118, 2),
(119, 1),
(119, 2),
(120, 1),
(120, 2),
(121, 2),
(122, 2),
(123, 2),
(124, 2),
(125, 2),
(126, 2),
(127, 2),
(128, 2),
(129, 2),
(130, 1),
(131, 1),
(132, 1),
(133, 1),
(134, 1),
(135, 1),
(136, 1),
(137, 1),
(138, 1),
(139, 1),
(140, 1),
(141, 1),
(142, 1),
(143, 1),
(144, 1),
(146, 1),
(146, 4),
(147, 1),
(147, 4),
(148, 1),
(148, 2),
(149, 1),
(149, 2),
(150, 1),
(150, 2);

-- --------------------------------------------------------

--
-- Table structure for table `school_classes`
--

CREATE TABLE `school_classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `ufr_id` bigint(20) UNSIGNED NOT NULL,
  `level` varchar(191) NOT NULL,
  `year` int(11) NOT NULL,
  `academic_year` varchar(191) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 50,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_teachers`
--

CREATE TABLE `school_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `designation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_id` varchar(191) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `address` varchar(191) DEFAULT NULL,
  `qualification` varchar(191) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `class_id` bigint(20) UNSIGNED DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(191) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(191) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` varchar(191) NOT NULL COMMENT 'Numéro étudiant',
  `parcours_id` bigint(20) UNSIGNED DEFAULT NULL,
  `promotion` varchar(191) DEFAULT NULL COMMENT 'Année d''entrée',
  `current_year` varchar(191) DEFAULT NULL COMMENT 'L1, L2, L3, M1, M2, etc.',
  `status` varchar(191) NOT NULL DEFAULT 'active' COMMENT 'Actif, en congé, diplômé, etc.',
  `registration_date` date DEFAULT NULL,
  `expected_graduation_date` date DEFAULT NULL,
  `actual_graduation_date` date DEFAULT NULL,
  `scholarship_status` varchar(191) DEFAULT NULL,
  `scholarship_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scholarship_details`)),
  `special_needs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`special_needs`)),
  `international_student` tinyint(1) NOT NULL DEFAULT 0,
  `country_of_origin` varchar(191) DEFAULT NULL,
  `visa_status` varchar(191) DEFAULT NULL,
  `visa_expiry_date` date DEFAULT NULL,
  `emergency_contact_name` varchar(191) DEFAULT NULL,
  `emergency_contact_relationship` varchar(191) DEFAULT NULL,
  `emergency_contact_phone` varchar(191) DEFAULT NULL,
  `previous_institution` varchar(191) DEFAULT NULL,
  `previous_qualification` varchar(191) DEFAULT NULL,
  `admission_score` double(8,2) DEFAULT NULL,
  `notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notes`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_grades`
--

CREATE TABLE `student_grades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `evaluation_id` bigint(20) UNSIGNED NOT NULL,
  `grade` double(8,2) DEFAULT NULL,
  `status` enum('present','absent','exempt') NOT NULL DEFAULT 'present',
  `comment` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` varchar(191) NOT NULL COMMENT 'Numéro d''employé',
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `laboratory_id` bigint(20) UNSIGNED DEFAULT NULL,
  `specialties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specialties`)),
  `grade` varchar(191) DEFAULT NULL COMMENT 'Professeur, Maître de conférences, etc.',
  `status` varchar(191) DEFAULT NULL COMMENT 'PRAG, MCF, PR, vacataire, ATER, etc.',
  `teaching_hours_due` int(11) NOT NULL DEFAULT 0,
  `teaching_hours_done` int(11) NOT NULL DEFAULT 0,
  `office_location` varchar(191) DEFAULT NULL,
  `office_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`office_hours`)),
  `bio` text DEFAULT NULL,
  `research_interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`research_interests`)),
  `publications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`publications`)),
  `website` varchar(191) DEFAULT NULL,
  `availability` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`availability`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `section_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `day_of_week` varchar(191) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_number` varchar(191) DEFAULT NULL,
  `session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ufrs`
--

CREATE TABLE `ufrs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `contact_email` varchar(191) DEFAULT NULL,
  `contact_phone` varchar(191) DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `first_name` varchar(191) DEFAULT NULL,
  `last_name` varchar(191) DEFAULT NULL,
  `username` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` enum('superAdmin','secretaire','etudiant') NOT NULL DEFAULT 'etudiant',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(191) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `position` varchar(191) DEFAULT NULL,
  `department` varchar(191) DEFAULT NULL,
  `office_location` varchar(191) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `profile_photo_path` varchar(191) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `first_name`, `last_name`, `username`, `email`, `email_verified_at`, `password`, `is_active`, `remember_token`, `created_at`, `updated_at`, `role`, `last_login_at`, `last_login_ip`, `phone`, `address`, `city`, `position`, `department`, `office_location`, `employee_id`, `appointment_date`, `birth_date`, `deleted_at`, `profile_photo_path`) VALUES
(1, 'kouame kan pascal', NULL, NULL, 'kouadio', 'pascalkouadio96@gmail.com', NULL, '$2y$10$qyXAa3yEJ3LW0tN21ne1rOnrsxEyT/w.nEizmhfF01LpeojCdlbum', 1, NULL, '2025-05-07 12:56:32', '2025-05-14 16:47:08', 'superAdmin', '2025-05-14 16:47:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Super Admin', NULL, NULL, 'superadmin_test_133439', 'superadmin@klassci.edu', '2025-05-07 13:34:40', '$2y$10$jm3Ii5Ev86cP/w0zleGupujBuUahp74b9L6ErQXuupV/rL1SFPM5q', 1, NULL, '2025-05-07 13:34:40', '2025-05-07 13:34:40', 'etudiant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Enseignant Test', NULL, NULL, 'enseignant_test_133439', 'enseignant@klassci.edu', '2025-05-07 13:34:40', '$2y$10$ljQ9gcgoKd93A/PxhoiwOe9dCPHjM3I/pGau5LlkmnqTGdDtg7KFO', 1, NULL, '2025-05-07 13:34:40', '2025-05-07 13:34:40', 'etudiant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Super Admin', 'Super', 'Admin', 'superadmin', 'superadmin@esbtp.ci', '2025-05-07 14:27:48', '$2y$10$8a.HTNkdRAO3CLa5zotNzuB8qi/ClFRczT9.cyhB3gaz2/k1VqYJu', 1, 'q4wxcMxSho', '2025-05-07 14:27:48', '2025-05-07 14:27:48', 'etudiant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Secretaire Test', 'Secretaire', 'Test', 'secretaire', 'secretaire@esbtp.ci', '2025-05-07 14:27:48', '$2y$10$Qv8clv.aUr4LAuX508pgM.5Xydsdrk2nWn1oFJt0M2lLsxhdJgbtq', 1, 'GYUaDiCg52', '2025-05-07 14:27:48', '2025-05-07 14:27:48', 'etudiant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Etudiant Test', 'Etudiant', 'Test', 'etudiant', 'etudiant@esbtp.ci', '2025-05-07 14:27:48', '$2y$10$uCHcol/n1VDp2eENxFhNLON0Ek5iym/5hfMp8JCx.SCwkoOIrUTMy', 1, 'cNf6CebVTp', '2025-05-07 14:27:48', '2025-05-07 14:27:48', 'etudiant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Teacher Test', 'Teacher', 'Test', 'teacher', 'teacher@esbtp.ci', '2025-05-07 14:27:48', '$2y$10$Op79m774GcoDH8OSsbpDQuKKNvwKjE.aROXlyPcXLCCh45OVpQc7a', 1, 'ETwQaiTEww', '2025-05-07 14:27:49', '2025-05-07 14:27:49', 'etudiant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'N\'guessan', NULL, NULL, 'MARCEL', 'djedjelipatrick@gmail.com', NULL, '$2y$10$Zk6rgTDDFeHxIjjLs8rzqe5v77e66fr23Ilj79Oja7uZRwK07q/TS', 1, NULL, '2025-05-13 20:41:46', '2025-05-14 19:37:29', 'etudiant', '2025-05-14 19:37:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'Marc N\'guessan', 'Marc', 'N\'guessan', 'marc.nguessan', 'marc.nguessan@esbtp.edu', NULL, '$2y$10$LeGTHjMHwgESAn5igc020.YDlm5bQsiFTHips7mjqBqdKlvlk1C.C', 1, NULL, '2025-05-14 17:38:12', '2025-05-14 17:38:12', 'etudiant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendances_student_id_foreign` (`student_id`),
  ADD KEY `attendances_course_id_foreign` (`course_id`),
  ADD KEY `attendances_teacher_id_foreign` (`teacher_id`),
  ADD KEY `attendances_recorded_by_foreign` (`recorded_by`);

--
-- Indexes for table `attendance_excuses`
--
ALTER TABLE `attendance_excuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_excuses_attendance_id_foreign` (`attendance_id`),
  ADD KEY `attendance_excuses_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificates_reference_number_unique` (`reference_number`),
  ADD KEY `certificates_student_id_foreign` (`student_id`),
  ADD KEY `certificates_issued_by_foreign` (`issued_by`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `classes_session_id_foreign` (`session_id`);

--
-- Indexes for table `class_courses`
--
ALTER TABLE `class_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_courses_school_class_id_course_id_semester_unique` (`school_class_id`,`course_id`,`semester`),
  ADD KEY `class_courses_course_id_foreign` (`course_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `courses_code_unique` (`code`),
  ADD KEY `courses_teacher_id_foreign` (`teacher_id`),
  ADD KEY `courses_ufr_id_foreign` (`ufr_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_code_unique` (`code`),
  ADD KEY `departments_created_by_foreign` (`created_by`),
  ADD KEY `departments_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `designations_created_by_foreign` (`created_by`),
  ADD KEY `designations_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `element_constitutifs`
--
ALTER TABLE `element_constitutifs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `element_constitutifs_code_unique` (`code`),
  ADD KEY `element_constitutifs_created_by_foreign` (`created_by`),
  ADD KEY `element_constitutifs_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_absences`
--
ALTER TABLE `esbtp_absences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_absences_etudiant_id_foreign` (`etudiant_id`),
  ADD KEY `esbtp_absences_matiere_id_foreign` (`matiere_id`),
  ADD KEY `esbtp_absences_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_absences_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_annee_universitaires`
--
ALTER TABLE `esbtp_annee_universitaires`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `esbtp_annonces`
--
ALTER TABLE `esbtp_annonces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_annonces_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_annonces_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_annonce_classe`
--
ALTER TABLE `esbtp_annonce_classe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_annonce_classe_annonce_id_classe_id_unique` (`annonce_id`,`classe_id`),
  ADD KEY `esbtp_annonce_classe_classe_id_foreign` (`classe_id`);

--
-- Indexes for table `esbtp_annonce_etudiant`
--
ALTER TABLE `esbtp_annonce_etudiant`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_annonce_etudiant_annonce_id_etudiant_id_unique` (`annonce_id`,`etudiant_id`),
  ADD KEY `esbtp_annonce_etudiant_etudiant_id_foreign` (`etudiant_id`);

--
-- Indexes for table `esbtp_annonce_lectures`
--
ALTER TABLE `esbtp_annonce_lectures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_annonce_lectures_annonce_id_etudiant_id_unique` (`annonce_id`,`etudiant_id`),
  ADD KEY `esbtp_annonce_lectures_etudiant_id_foreign` (`etudiant_id`);

--
-- Indexes for table `esbtp_attendances`
--
ALTER TABLE `esbtp_attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_attendances_etudiant_id_foreign` (`etudiant_id`),
  ADD KEY `esbtp_attendances_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_attendances_updated_by_foreign` (`updated_by`),
  ADD KEY `esbtp_attendances_seance_cours_id_foreign` (`seance_cours_id`);

--
-- Indexes for table `esbtp_attendance_settings`
--
ALTER TABLE `esbtp_attendance_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `esbtp_bourses`
--
ALTER TABLE `esbtp_bourses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_bourses_etudiant_id_foreign` (`etudiant_id`),
  ADD KEY `esbtp_bourses_annee_universitaire_id_foreign` (`annee_universitaire_id`),
  ADD KEY `esbtp_bourses_createur_id_foreign` (`createur_id`);

--
-- Indexes for table `esbtp_bulletins`
--
ALTER TABLE `esbtp_bulletins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bulletin_unique` (`etudiant_id`,`classe_id`,`annee_universitaire_id`,`periode`),
  ADD KEY `esbtp_bulletins_classe_id_foreign` (`classe_id`),
  ADD KEY `esbtp_bulletins_annee_universitaire_id_foreign` (`annee_universitaire_id`),
  ADD KEY `esbtp_bulletins_user_id_foreign` (`user_id`),
  ADD KEY `esbtp_bulletins_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_bulletins_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_bulletin_details`
--
ALTER TABLE `esbtp_bulletin_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bulletin_matiere_unique` (`bulletin_id`,`matiere_id`),
  ADD KEY `esbtp_bulletin_details_bulletin_id_index` (`bulletin_id`),
  ADD KEY `esbtp_bulletin_details_matiere_id_index` (`matiere_id`);

--
-- Indexes for table `esbtp_categories_depenses`
--
ALTER TABLE `esbtp_categories_depenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_categories_depenses_code_unique` (`code`),
  ADD KEY `esbtp_categories_depenses_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `esbtp_categorie_paiements`
--
ALTER TABLE `esbtp_categorie_paiements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_categorie_paiements_code_unique` (`code`),
  ADD UNIQUE KEY `esbtp_categorie_paiements_slug_unique` (`slug`),
  ADD KEY `esbtp_categorie_paiements_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `esbtp_classes`
--
ALTER TABLE `esbtp_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_classes_code_unique` (`code`),
  ADD KEY `esbtp_classes_niveau_etude_id_foreign` (`niveau_etude_id`),
  ADD KEY `esbtp_classes_annee_universitaire_id_foreign` (`annee_universitaire_id`),
  ADD KEY `esbtp_classes_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_classes_updated_by_foreign` (`updated_by`),
  ADD KEY `idx_esbtp_classes_filiere_niveau_annee` (`filiere_id`,`niveau_etude_id`,`annee_universitaire_id`);

--
-- Indexes for table `esbtp_classe_matiere`
--
ALTER TABLE `esbtp_classe_matiere`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_classe_matiere_classe_id_matiere_id_unique` (`classe_id`,`matiere_id`),
  ADD KEY `esbtp_classe_matiere_matiere_id_foreign` (`matiere_id`);

--
-- Indexes for table `esbtp_comptabilite_configurations`
--
ALTER TABLE `esbtp_comptabilite_configurations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `esbtp_config_matieres`
--
ALTER TABLE `esbtp_config_matieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_matieres_unique_idx` (`classe_id`,`annee_universitaire_id`,`periode`,`matiere_id`),
  ADD KEY `esbtp_config_matieres_matiere_id_foreign` (`matiere_id`),
  ADD KEY `esbtp_config_matieres_annee_universitaire_id_foreign` (`annee_universitaire_id`),
  ADD KEY `esbtp_config_matieres_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_config_matieres_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_config_matiere_type_formations`
--
ALTER TABLE `esbtp_config_matiere_type_formations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ecmtf_unique_config` (`classe_id`,`annee_universitaire_id`,`periode`),
  ADD KEY `ecmtf_annee_id_foreign` (`annee_universitaire_id`),
  ADD KEY `ecmtf_created_by_foreign` (`created_by`),
  ADD KEY `ecmtf_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_cours`
--
ALTER TABLE `esbtp_cours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cours_unique` (`matiere_id`,`classe_id`,`jour`,`heure_debut`),
  ADD KEY `esbtp_cours_classe_id_foreign` (`classe_id`),
  ADD KEY `esbtp_cours_enseignant_id_foreign` (`enseignant_id`),
  ADD KEY `esbtp_cours_annee_universitaire_id_foreign` (`annee_universitaire_id`);

--
-- Indexes for table `esbtp_daily_codes`
--
ALTER TABLE `esbtp_daily_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `esbtp_depenses`
--
ALTER TABLE `esbtp_depenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_depenses_reference_unique` (`reference`),
  ADD KEY `esbtp_depenses_categorie_id_foreign` (`categorie_id`),
  ADD KEY `esbtp_depenses_fournisseur_id_foreign` (`fournisseur_id`),
  ADD KEY `esbtp_depenses_createur_id_foreign` (`createur_id`),
  ADD KEY `esbtp_depenses_validateur_id_foreign` (`validateur_id`);

--
-- Indexes for table `esbtp_emploi_temps`
--
ALTER TABLE `esbtp_emploi_temps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_emploi_temps_classe_id_foreign` (`classe_id`),
  ADD KEY `esbtp_emploi_temps_annee_universitaire_id_foreign` (`annee_universitaire_id`),
  ADD KEY `esbtp_emploi_temps_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_emploi_temps_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_enseignant_matiere`
--
ALTER TABLE `esbtp_enseignant_matiere`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enseignant_matiere_annee_unique` (`enseignant_id`,`matiere_id`,`annee_universitaire_id`),
  ADD KEY `esbtp_enseignant_matiere_matiere_id_foreign` (`matiere_id`),
  ADD KEY `esbtp_enseignant_matiere_annee_universitaire_id_foreign` (`annee_universitaire_id`);

--
-- Indexes for table `esbtp_enseignant_presence`
--
ALTER TABLE `esbtp_enseignant_presence`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `esbtp_etudiants`
--
ALTER TABLE `esbtp_etudiants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_etudiants_matricule_unique` (`matricule`),
  ADD UNIQUE KEY `esbtp_etudiants_email_unique` (`email`),
  ADD KEY `esbtp_etudiants_user_id_foreign` (`user_id`),
  ADD KEY `esbtp_etudiants_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_etudiants_updated_by_foreign` (`updated_by`),
  ADD KEY `esbtp_etudiants_classe_id_foreign` (`classe_id`),
  ADD KEY `esbtp_etudiants_annee_universitaire_id_foreign` (`annee_universitaire_id`);

--
-- Indexes for table `esbtp_etudiant_parent`
--
ALTER TABLE `esbtp_etudiant_parent`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_etudiant_parent_etudiant_id_parent_id_relation_unique` (`etudiant_id`,`parent_id`,`relation`),
  ADD KEY `esbtp_etudiant_parent_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `esbtp_evaluations`
--
ALTER TABLE `esbtp_evaluations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `esbtp_factures`
--
ALTER TABLE `esbtp_factures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_factures_numero_facture_unique` (`numero_facture`),
  ADD KEY `esbtp_factures_etudiant_id_foreign` (`etudiant_id`),
  ADD KEY `esbtp_factures_inscription_id_foreign` (`inscription_id`),
  ADD KEY `esbtp_factures_annee_universitaire_id_foreign` (`annee_universitaire_id`),
  ADD KEY `esbtp_factures_createur_id_foreign` (`createur_id`),
  ADD KEY `esbtp_factures_validateur_id_foreign` (`validateur_id`);

--
-- Indexes for table `esbtp_facture_details`
--
ALTER TABLE `esbtp_facture_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_facture_details_facture_id_foreign` (`facture_id`);

--
-- Indexes for table `esbtp_filieres`
--
ALTER TABLE `esbtp_filieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_filieres_code_unique` (`code`);

--
-- Indexes for table `esbtp_filiere_niveau`
--
ALTER TABLE `esbtp_filiere_niveau`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_filiere_niveau_filiere_id_foreign` (`filiere_id`),
  ADD KEY `esbtp_filiere_niveau_niveau_etude_id_foreign` (`niveau_etude_id`);

--
-- Indexes for table `esbtp_fournisseurs`
--
ALTER TABLE `esbtp_fournisseurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_fournisseurs_code_unique` (`code`);

--
-- Indexes for table `esbtp_frais_scolarite`
--
ALTER TABLE `esbtp_frais_scolarite`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `frais_scolarite_unique` (`filiere_id`,`niveau_etude_id`,`annee_universitaire_id`),
  ADD KEY `esbtp_frais_scolarite_niveau_etude_id_foreign` (`niveau_etude_id`),
  ADD KEY `esbtp_frais_scolarite_annee_universitaire_id_foreign` (`annee_universitaire_id`);

--
-- Indexes for table `esbtp_inscriptions`
--
ALTER TABLE `esbtp_inscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_active_inscription` (`etudiant_id`,`annee_universitaire_id`,`status`),
  ADD KEY `esbtp_inscriptions_annee_universitaire_id_foreign` (`annee_universitaire_id`),
  ADD KEY `esbtp_inscriptions_filiere_id_foreign` (`filiere_id`),
  ADD KEY `esbtp_inscriptions_niveau_id_foreign` (`niveau_id`),
  ADD KEY `esbtp_inscriptions_classe_id_foreign` (`classe_id`),
  ADD KEY `esbtp_inscriptions_validated_by_foreign` (`validated_by`),
  ADD KEY `esbtp_inscriptions_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_inscriptions_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_matieres`
--
ALTER TABLE `esbtp_matieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_matieres_code_unique` (`code`),
  ADD KEY `esbtp_matieres_niveau_etude_id_foreign` (`niveau_etude_id`),
  ADD KEY `esbtp_matieres_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_matieres_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_matiere_filiere`
--
ALTER TABLE `esbtp_matiere_filiere`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_matiere_filiere_matiere_id_filiere_id_unique` (`matiere_id`,`filiere_id`),
  ADD KEY `esbtp_matiere_filiere_filiere_id_foreign` (`filiere_id`);

--
-- Indexes for table `esbtp_matiere_niveau`
--
ALTER TABLE `esbtp_matiere_niveau`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_matiere_niveau_matiere_id_niveau_etude_id_unique` (`matiere_id`,`niveau_etude_id`),
  ADD KEY `esbtp_matiere_niveau_niveau_etude_id_foreign` (`niveau_etude_id`),
  ADD KEY `esbtp_matiere_niveau_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_matiere_niveau_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_niveau_etudes`
--
ALTER TABLE `esbtp_niveau_etudes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_niveau_etudes_code_unique` (`code`);

--
-- Indexes for table `esbtp_niveau_matiere`
--
ALTER TABLE `esbtp_niveau_matiere`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_niveau_matiere_niveau_id_matiere_id_unique` (`niveau_id`,`matiere_id`),
  ADD KEY `esbtp_niveau_matiere_matiere_id_foreign` (`matiere_id`);

--
-- Indexes for table `esbtp_notes`
--
ALTER TABLE `esbtp_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_notes_matiere_id_foreign` (`matiere_id`),
  ADD KEY `esbtp_notes_etudiant_id_foreign` (`etudiant_id`),
  ADD KEY `esbtp_notes_classe_id_foreign` (`classe_id`),
  ADD KEY `esbtp_notes_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_notes_updated_by_foreign` (`updated_by`),
  ADD KEY `esbtp_notes_evaluation_id_index` (`evaluation_id`);

--
-- Indexes for table `esbtp_paiements`
--
ALTER TABLE `esbtp_paiements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_paiements_inscription_id_foreign` (`inscription_id`),
  ADD KEY `esbtp_paiements_validated_by_foreign` (`validated_by`),
  ADD KEY `esbtp_paiements_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_paiements_updated_by_foreign` (`updated_by`),
  ADD KEY `esbtp_paiements_etudiant_id_inscription_id_index` (`etudiant_id`,`inscription_id`),
  ADD KEY `esbtp_paiements_numero_recu_index` (`numero_recu`),
  ADD KEY `esbtp_paiements_date_paiement_index` (`date_paiement`),
  ADD KEY `esbtp_paiements_status_index` (`status`);

--
-- Indexes for table `esbtp_parents`
--
ALTER TABLE `esbtp_parents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_parents_user_id_foreign` (`user_id`),
  ADD KEY `esbtp_parents_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_parents_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_resultats`
--
ALTER TABLE `esbtp_resultats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_resultats_unique` (`etudiant_id`,`classe_id`,`matiere_id`,`periode`,`annee_universitaire_id`),
  ADD KEY `esbtp_resultats_classe_id_foreign` (`classe_id`),
  ADD KEY `esbtp_resultats_matiere_id_foreign` (`matiere_id`),
  ADD KEY `esbtp_resultats_annee_universitaire_id_foreign` (`annee_universitaire_id`),
  ADD KEY `esbtp_resultats_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_resultats_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_resultats_matieres`
--
ALTER TABLE `esbtp_resultats_matieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_resultats_matieres_bulletin_id_matiere_id_unique` (`bulletin_id`,`matiere_id`),
  ADD KEY `esbtp_resultats_matieres_matiere_id_foreign` (`matiere_id`),
  ADD KEY `esbtp_resultats_matieres_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_resultats_matieres_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_salaires`
--
ALTER TABLE `esbtp_salaires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `salaire_unique` (`user_id`,`mois`,`annee`),
  ADD KEY `esbtp_salaires_annee_universitaire_id_foreign` (`annee_universitaire_id`),
  ADD KEY `esbtp_salaires_createur_id_foreign` (`createur_id`),
  ADD KEY `esbtp_salaires_validateur_id_foreign` (`validateur_id`);

--
-- Indexes for table `esbtp_seance_cours`
--
ALTER TABLE `esbtp_seance_cours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esbtp_seance_cours_classe_id_foreign` (`classe_id`),
  ADD KEY `esbtp_seance_cours_matiere_id_foreign` (`matiere_id`),
  ADD KEY `esbtp_seance_cours_annee_universitaire_id_foreign` (`annee_universitaire_id`),
  ADD KEY `esbtp_seance_cours_emploi_temps_id_foreign` (`emploi_temps_id`);

--
-- Indexes for table `esbtp_student_grades`
--
ALTER TABLE `esbtp_student_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_evaluation_unique` (`student_id`,`evaluation_id`),
  ADD KEY `esbtp_student_grades_evaluation_id_foreign` (`evaluation_id`),
  ADD KEY `esbtp_student_grades_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_student_grades_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `esbtp_transactions_financieres`
--
ALTER TABLE `esbtp_transactions_financieres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transactionable` (`transactionable_type`,`transactionable_id`),
  ADD KEY `esbtp_transactions_financieres_createur_id_foreign` (`createur_id`);

--
-- Indexes for table `esbtp_unites_enseignement`
--
ALTER TABLE `esbtp_unites_enseignement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esbtp_unites_enseignement_code_unique` (`code`),
  ADD KEY `esbtp_unites_enseignement_filiere_id_foreign` (`filiere_id`),
  ADD KEY `esbtp_unites_enseignement_niveau_id_foreign` (`niveau_id`),
  ADD KEY `esbtp_unites_enseignement_created_by_foreign` (`created_by`),
  ADD KEY `esbtp_unites_enseignement_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evaluations_element_constitutif_id_foreign` (`element_constitutif_id`),
  ADD KEY `evaluations_created_by_foreign` (`created_by`),
  ADD KEY `evaluations_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exams_session_id_foreign` (`session_id`),
  ADD KEY `exams_semester_id_foreign` (`semester_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grades_student_id_evaluation_id_unique` (`student_id`,`evaluation_id`),
  ADD KEY `grades_evaluation_id_foreign` (`evaluation_id`),
  ADD KEY `grades_created_by_foreign` (`created_by`),
  ADD KEY `grades_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `laboratories`
--
ALTER TABLE `laboratories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `laboratories_code_unique` (`code`),
  ADD KEY `laboratories_created_by_foreign` (`created_by`),
  ADD KEY `laboratories_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_sender_id_foreign` (`sender_id`),
  ADD KEY `messages_recipient_id_foreign` (`recipient_id`),
  ADD KEY `messages_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_attachments_message_id_foreign` (`message_id`);

--
-- Indexes for table `message_recipients`
--
ALTER TABLE `message_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_recipients_message_id_foreign` (`message_id`),
  ADD KEY `message_recipients_user_id_foreign` (`user_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `parcours`
--
ALTER TABLE `parcours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parcours_code_unique` (`code`),
  ADD KEY `parcours_created_by_foreign` (`created_by`),
  ADD KEY `parcours_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `school_classes`
--
ALTER TABLE `school_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_classes_code_unique` (`code`),
  ADD KEY `school_classes_ufr_id_foreign` (`ufr_id`);

--
-- Indexes for table `school_teachers`
--
ALTER TABLE `school_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_teachers_employee_id_unique` (`employee_id`),
  ADD KEY `school_teachers_user_id_foreign` (`user_id`),
  ADD KEY `school_teachers_designation_id_foreign` (`designation_id`),
  ADD KEY `school_teachers_department_id_foreign` (`department_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sections_code_unique` (`code`),
  ADD KEY `sections_created_by_foreign` (`created_by`),
  ADD KEY `sections_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `semesters_code_unique` (`code`),
  ADD KEY `semesters_session_id_foreign` (`session_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_created_by_foreign` (`created_by`),
  ADD KEY `sessions_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_student_id_unique` (`student_id`),
  ADD KEY `students_user_id_foreign` (`user_id`),
  ADD KEY `students_parcours_id_foreign` (`parcours_id`),
  ADD KEY `students_created_by_foreign` (`created_by`),
  ADD KEY `students_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_grades_student_id_evaluation_id_unique` (`student_id`,`evaluation_id`),
  ADD KEY `student_grades_evaluation_id_foreign` (`evaluation_id`),
  ADD KEY `student_grades_created_by_foreign` (`created_by`),
  ADD KEY `student_grades_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teachers_employee_id_unique` (`employee_id`),
  ADD KEY `teachers_user_id_foreign` (`user_id`),
  ADD KEY `teachers_department_id_foreign` (`department_id`),
  ADD KEY `teachers_laboratory_id_foreign` (`laboratory_id`),
  ADD KEY `teachers_created_by_foreign` (`created_by`),
  ADD KEY `teachers_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timetables_class_id_foreign` (`class_id`),
  ADD KEY `timetables_section_id_foreign` (`section_id`),
  ADD KEY `timetables_subject_id_foreign` (`subject_id`),
  ADD KEY `timetables_teacher_id_foreign` (`teacher_id`),
  ADD KEY `timetables_session_id_foreign` (`session_id`);

--
-- Indexes for table `ufrs`
--
ALTER TABLE `ufrs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ufrs_code_unique` (`code`),
  ADD KEY `ufrs_created_by_foreign` (`created_by`),
  ADD KEY `ufrs_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_excuses`
--
ALTER TABLE `attendance_excuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_courses`
--
ALTER TABLE `class_courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `element_constitutifs`
--
ALTER TABLE `element_constitutifs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_absences`
--
ALTER TABLE `esbtp_absences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_annee_universitaires`
--
ALTER TABLE `esbtp_annee_universitaires`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `esbtp_annonces`
--
ALTER TABLE `esbtp_annonces`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_annonce_classe`
--
ALTER TABLE `esbtp_annonce_classe`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_annonce_etudiant`
--
ALTER TABLE `esbtp_annonce_etudiant`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_annonce_lectures`
--
ALTER TABLE `esbtp_annonce_lectures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_attendances`
--
ALTER TABLE `esbtp_attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_attendance_settings`
--
ALTER TABLE `esbtp_attendance_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `esbtp_bourses`
--
ALTER TABLE `esbtp_bourses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_bulletins`
--
ALTER TABLE `esbtp_bulletins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_bulletin_details`
--
ALTER TABLE `esbtp_bulletin_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_categories_depenses`
--
ALTER TABLE `esbtp_categories_depenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `esbtp_categorie_paiements`
--
ALTER TABLE `esbtp_categorie_paiements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `esbtp_classes`
--
ALTER TABLE `esbtp_classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `esbtp_classe_matiere`
--
ALTER TABLE `esbtp_classe_matiere`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `esbtp_comptabilite_configurations`
--
ALTER TABLE `esbtp_comptabilite_configurations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_config_matieres`
--
ALTER TABLE `esbtp_config_matieres`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_config_matiere_type_formations`
--
ALTER TABLE `esbtp_config_matiere_type_formations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_cours`
--
ALTER TABLE `esbtp_cours`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_daily_codes`
--
ALTER TABLE `esbtp_daily_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_depenses`
--
ALTER TABLE `esbtp_depenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_emploi_temps`
--
ALTER TABLE `esbtp_emploi_temps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `esbtp_enseignant_matiere`
--
ALTER TABLE `esbtp_enseignant_matiere`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_enseignant_presence`
--
ALTER TABLE `esbtp_enseignant_presence`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_etudiants`
--
ALTER TABLE `esbtp_etudiants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `esbtp_etudiant_parent`
--
ALTER TABLE `esbtp_etudiant_parent`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `esbtp_evaluations`
--
ALTER TABLE `esbtp_evaluations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_factures`
--
ALTER TABLE `esbtp_factures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_facture_details`
--
ALTER TABLE `esbtp_facture_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_filieres`
--
ALTER TABLE `esbtp_filieres`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `esbtp_filiere_niveau`
--
ALTER TABLE `esbtp_filiere_niveau`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `esbtp_fournisseurs`
--
ALTER TABLE `esbtp_fournisseurs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_frais_scolarite`
--
ALTER TABLE `esbtp_frais_scolarite`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_inscriptions`
--
ALTER TABLE `esbtp_inscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `esbtp_matieres`
--
ALTER TABLE `esbtp_matieres`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `esbtp_matiere_filiere`
--
ALTER TABLE `esbtp_matiere_filiere`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `esbtp_matiere_niveau`
--
ALTER TABLE `esbtp_matiere_niveau`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `esbtp_niveau_etudes`
--
ALTER TABLE `esbtp_niveau_etudes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `esbtp_niveau_matiere`
--
ALTER TABLE `esbtp_niveau_matiere`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_notes`
--
ALTER TABLE `esbtp_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_paiements`
--
ALTER TABLE `esbtp_paiements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_parents`
--
ALTER TABLE `esbtp_parents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `esbtp_resultats`
--
ALTER TABLE `esbtp_resultats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_resultats_matieres`
--
ALTER TABLE `esbtp_resultats_matieres`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_salaires`
--
ALTER TABLE `esbtp_salaires`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_seance_cours`
--
ALTER TABLE `esbtp_seance_cours`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_student_grades`
--
ALTER TABLE `esbtp_student_grades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_transactions_financieres`
--
ALTER TABLE `esbtp_transactions_financieres`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esbtp_unites_enseignement`
--
ALTER TABLE `esbtp_unites_enseignement`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laboratories`
--
ALTER TABLE `laboratories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_attachments`
--
ALTER TABLE `message_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_recipients`
--
ALTER TABLE `message_recipients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `parcours`
--
ALTER TABLE `parcours`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `school_classes`
--
ALTER TABLE `school_classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_teachers`
--
ALTER TABLE `school_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ufrs`
--
ALTER TABLE `ufrs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
