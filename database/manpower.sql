-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2026 at 10:16 AM
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
-- Database: `manpower`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `subject`, `created_at`, `updated_at`) VALUES
(1, 2, 'Signed in', 'Admin Approver', '2026-10-04 08:12:19', '2026-10-04 08:12:19'),
(2, 2, 'Logout', 'POST logout', '2026-10-04 08:13:07', '2026-10-04 08:13:07'),
(3, 1, 'Signed in', 'Super Admin', '2026-10-04 08:13:20', '2026-10-04 08:13:20'),
(4, 2, 'Signed in', 'Admin Approver', '2026-10-04 08:16:45', '2026-10-04 08:16:45'),
(5, 2, 'Signed in', 'Admin Approver', '2026-10-05 04:52:21', '2026-10-05 04:52:21'),
(6, 1, 'Created account', 'Payroll Administrator À admin', '2026-10-05 04:54:01', '2026-10-05 04:54:01'),
(7, 1, 'Created account', 'HR Administrator À admin', '2026-10-05 04:54:01', '2026-10-05 04:54:01'),
(8, 1, 'Created account', 'Operations Manager À manager', '2026-10-05 04:54:01', '2026-10-05 04:54:01'),
(9, 1, 'Created account', 'Timesheet Manager À manager', '2026-10-05 04:54:01', '2026-10-05 04:54:01'),
(10, 1, 'Created account', 'Workforce Manager À manager', '2026-10-05 04:54:01', '2026-10-05 04:54:01'),
(13, 1, 'Created employee', 'Ahmed Al Harbi À rental', '2026-10-05 04:55:02', '2026-10-05 04:55:02'),
(14, 1, 'Created employee', 'Mohammed Rahman À rental', '2026-10-05 04:55:02', '2026-10-05 04:55:02'),
(15, 1, 'Created employee', 'Joseph Dsouza À rental', '2026-10-05 04:55:02', '2026-10-05 04:55:02'),
(16, 1, 'Created employee', 'Faisal Al Qahtani À own', '2026-10-05 04:55:02', '2026-10-05 04:55:02'),
(17, 1, 'Created employee', 'Sara Al Mutairi À own', '2026-10-05 04:55:02', '2026-10-05 04:55:02'),
(20, 2, 'Updated employee', 'Employee #1 · Ahmed Al Harbi', '2026-10-05 05:00:40', '2026-10-05 05:00:40'),
(21, 2, 'Updated employee', 'Employee #2 · Mohammed Rahman', '2026-10-05 05:00:56', '2026-10-05 05:00:56'),
(22, 2, 'Saved hours', 'Timesheet #1 · Mohammed Rahman · 2026-01-01', '2026-10-05 05:03:13', '2026-10-05 05:03:13'),
(23, 2, 'Saved hours', 'Timesheet #2 · Mohammed Rahman · 2026-01-03', '2026-10-05 05:03:13', '2026-10-05 05:03:13'),
(24, 2, 'Saved hours', 'Timesheet #3 · Mohammed Rahman · 2026-01-04', '2026-10-05 05:03:13', '2026-10-05 05:03:13'),
(25, 2, 'Saved hours', 'Timesheet #4 · Mohammed Rahman · 2026-01-05', '2026-10-05 05:03:13', '2026-10-05 05:03:13'),
(26, 2, 'Saved hours', 'Timesheet #5 · Mohammed Rahman · 2026-01-06', '2026-10-05 05:03:13', '2026-10-05 05:03:13'),
(27, 2, 'Saved hours', 'Timesheet #6 · Mohammed Rahman · 2026-01-07', '2026-10-05 05:03:13', '2026-10-05 05:03:13'),
(28, 2, 'Saved hours', 'Timesheet #7 · Mohammed Rahman · 2026-01-08', '2026-10-05 05:03:13', '2026-10-05 05:03:13'),
(29, 2, 'Saved hours', 'Timesheet #8 · Mohammed Rahman · 2026-01-10', '2026-10-05 05:03:13', '2026-10-05 05:03:13'),
(30, 2, 'Saved hours', 'Timesheet #9 · Mohammed Rahman · 2026-01-11', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(31, 2, 'Saved hours', 'Timesheet #10 · Mohammed Rahman · 2026-01-12', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(32, 2, 'Saved hours', 'Timesheet #11 · Mohammed Rahman · 2026-01-13', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(33, 2, 'Saved hours', 'Timesheet #12 · Mohammed Rahman · 2026-01-14', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(34, 2, 'Saved hours', 'Timesheet #13 · Mohammed Rahman · 2026-01-15', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(35, 2, 'Saved hours', 'Timesheet #14 · Mohammed Rahman · 2026-01-17', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(36, 2, 'Saved hours', 'Timesheet #15 · Mohammed Rahman · 2026-01-18', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(37, 2, 'Saved hours', 'Timesheet #16 · Mohammed Rahman · 2026-01-19', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(38, 2, 'Saved hours', 'Timesheet #17 · Mohammed Rahman · 2026-01-20', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(39, 2, 'Saved hours', 'Timesheet #18 · Mohammed Rahman · 2026-01-21', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(40, 2, 'Saved hours', 'Timesheet #19 · Mohammed Rahman · 2026-01-22', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(41, 2, 'Saved hours', 'Timesheet #20 · Mohammed Rahman · 2026-01-24', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(42, 2, 'Saved hours', 'Timesheet #21 · Mohammed Rahman · 2026-01-25', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(43, 2, 'Saved hours', 'Timesheet #22 · Mohammed Rahman · 2026-01-26', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(44, 2, 'Saved hours', 'Timesheet #23 · Mohammed Rahman · 2026-01-27', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(45, 2, 'Saved hours', 'Timesheet #24 · Mohammed Rahman · 2026-01-28', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(46, 2, 'Saved hours', 'Timesheet #25 · Mohammed Rahman · 2026-01-29', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(47, 2, 'Saved hours', 'Timesheet #26 · Mohammed Rahman · 2026-01-31', '2026-10-05 05:03:14', '2026-10-05 05:03:14'),
(48, 2, 'Bulk approved hours', '20 entries · Timesheets #26, #25, #24, #23, #22, #21, #20, #19, #18, #17, #16, #15, #14, #13, #12, #11, #10, #9, #8, #7', '2026-10-05 05:04:06', '2026-10-05 05:04:06'),
(49, 2, 'Saved hours', 'Timesheet #3 · Mohammed Rahman · 2026-01-04', '2026-10-05 05:04:41', '2026-10-05 05:04:41'),
(50, 2, 'Bulk approved hours', '6 entries · Timesheets #6, #5, #4, #3, #2, #1', '2026-10-05 05:05:46', '2026-10-05 05:05:46'),
(51, 2, 'Timesheets Employee Pdf', 'employee #2 · workforce #rental', '2026-10-05 05:06:23', '2026-10-05 05:06:23'),
(52, 2, 'Updated employee', 'Employee #2 · Mohammed Rahman', '2026-10-05 05:07:26', '2026-10-05 05:07:26'),
(53, 2, 'Generated salary', 'Payslip #1 · Mohammed Rahman · 2026-01', '2026-10-05 05:08:41', '2026-10-05 05:08:41'),
(54, 2, 'Approved salary', 'Payslip #1 · Mohammed Rahman', '2026-10-05 05:08:52', '2026-10-05 05:08:52'),
(55, 2, 'Signed in', 'Admin Approver', '2026-10-05 11:18:08', '2026-10-05 11:18:08'),
(56, 2, 'Logout', 'POST logout', '2026-10-05 12:29:08', '2026-10-05 12:29:08'),
(57, 1, 'Signed in', 'Super Admin', '2026-10-05 12:29:21', '2026-10-05 12:29:21'),
(58, 1, 'Updated invoice design', 'TAX INVOICE', '2026-10-05 12:41:06', '2026-10-05 12:41:06'),
(59, 2, 'Signed in', 'Admin Approver', '2026-10-06 05:16:32', '2026-10-06 05:16:32'),
(60, 2, 'Signed in', 'Admin Approver', '2026-10-06 05:33:52', '2026-10-06 05:33:52'),
(61, 2, 'Saved safety shop location', 'Main Safety Shop', '2026-10-06 05:34:50', '2026-10-06 05:34:50'),
(62, 2, 'Saved safety shop category', 'Shoes', '2026-10-06 05:43:13', '2026-10-06 05:43:13'),
(63, 2, 'Saved safety shop category', 'Safety Vast', '2026-10-06 05:43:26', '2026-10-06 05:43:26'),
(64, 2, 'Created safety shop product', '22222', '2026-10-06 05:46:34', '2026-10-06 05:46:34'),
(65, 2, 'Posted safety shop adjustment', '22222 · movement #1', '2026-10-06 05:50:06', '2026-10-06 05:50:06'),
(66, 2, 'Posted safety shop issue', '22222 · movement #2', '2026-10-06 06:03:04', '2026-10-06 06:03:04'),
(67, 2, 'Posted safety shop sale', 'SALE-1', '2026-10-06 06:03:04', '2026-10-06 06:03:04'),
(68, 2, 'Posted safety shop issue', '22222 · movement #3', '2026-10-06 06:05:31', '2026-10-06 06:05:31'),
(69, 2, 'Posted safety shop sale', 'SALE-2', '2026-10-06 06:05:31', '2026-10-06 06:05:31'),
(70, 2, 'Uploaded library document', 'ss', '2026-10-06 06:12:29', '2026-10-06 06:12:29'),
(71, 2, 'Viewed library document', 'ss', '2026-10-06 06:14:19', '2026-10-06 06:14:19'),
(72, 2, 'Viewed library document', 'ss', '2026-10-06 06:18:58', '2026-10-06 06:18:58'),
(73, 2, 'Posted safety shop return', '22222 · movement #4', '2026-10-06 06:42:53', '2026-10-06 06:42:53'),
(74, 2, 'Posted safety shop customer return', 'RETURN-1 · SALE-1', '2026-10-06 06:42:53', '2026-10-06 06:42:53'),
(75, 2, 'Posted safety shop return', '22222 · movement #5', '2026-10-06 06:43:28', '2026-10-06 06:43:28'),
(76, 2, 'Posted safety shop customer return', 'RETURN-2 · SALE-2', '2026-10-06 06:43:28', '2026-10-06 06:43:28');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(150) NOT NULL DEFAULT 'Not specified',
  `registration_number` varchar(100) DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `location`, `registration_number`, `contact_person`, `phone`, `email`, `address`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Manpower Operations', 'Riyadh, Saudi Arabia', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-10-04 08:10:12', '2026-10-04 08:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `company_user`
--

CREATE TABLE `company_user` (
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_user`
--

INSERT INTO `company_user` (`company_id`, `user_id`) VALUES
(1, 3),
(1, 6),
(1, 7),
(1, 8);

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'General Worker', 1, '2026-10-04 08:10:12', '2026-10-04 08:10:12'),
(2, 'Electrician', 1, '2026-10-04 08:10:12', '2026-10-04 08:10:12'),
(3, 'Plumber', 1, '2026-10-04 08:10:12', '2026-10-04 08:10:12'),
(4, 'Welder', 1, '2026-10-04 08:10:12', '2026-10-04 08:10:12'),
(5, 'Driver', 1, '2026-10-04 08:10:12', '2026-10-04 08:10:12'),
(6, 'Supervisor', 1, '2026-10-04 08:10:12', '2026-10-04 08:10:12'),
(7, 'Accountant', 1, '2026-10-04 08:10:12', '2026-10-04 08:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `document_library_files`
--

CREATE TABLE `document_library_files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `issued_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `storage_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_library_files`
--

INSERT INTO `document_library_files` (`id`, `owner_id`, `uploaded_by`, `title`, `category`, `document_number`, `issued_on`, `expires_on`, `original_name`, `storage_path`, `mime_type`, `size_bytes`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 'ss', 'ss', '43', '2026-10-06', '2026-10-30', 'Rakibul_Islam_Software_Technician_CV.pdf', 'document-library/2/JZCzNXZeJYqFTMIksk0mntfHTjdkg4g5lFWdh2rr.pdf', 'application/pdf', 83484, NULL, '2026-10-06 06:12:29', '2026-10-06 06:12:29');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `iqama_number` varchar(30) NOT NULL,
  `passport_number` varchar(30) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `personal_email` varchar(255) DEFAULT NULL,
  `professional_summary` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `designation_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `directorate` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `previous_experience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`previous_experience`)),
  `blood_group` varchar(3) NOT NULL,
  `hourly_rate_cents` int(10) UNSIGNED NOT NULL,
  `regular_hours_units` int(10) UNSIGNED NOT NULL DEFAULT 800,
  `overtime_rate_cents` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `salary_type` varchar(255) NOT NULL DEFAULT 'hourly',
  `employment_type` varchar(255) NOT NULL DEFAULT 'rental',
  `monthly_salary_cents` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `meal_allowance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `transportation_allowance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `housing_allowance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `medical_allowance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `retirement_insurance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `overtime_multiplier_units` int(10) UNSIGNED NOT NULL DEFAULT 150,
  `joined_on` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `photo_path`, `document_path`, `iqama_number`, `passport_number`, `phone`, `nationality`, `personal_email`, `professional_summary`, `education`, `skills`, `designation_id`, `company_id`, `directorate`, `department`, `previous_experience`, `blood_group`, `hourly_rate_cents`, `regular_hours_units`, `overtime_rate_cents`, `salary_type`, `employment_type`, `monthly_salary_cents`, `meal_allowance_cents`, `transportation_allowance_cents`, `housing_allowance_cents`, `medical_allowance_cents`, `retirement_insurance_cents`, `tax_cents`, `overtime_multiplier_units`, `joined_on`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Ahmed Al Harbi', 'employee-photos/demo-1.png', 'employee-documents/demo-10.pdf', '2500000001', 'RNT250001', '+966550100001', 'Saudi Arabian', 'ahmed.harbi@manpower.local', 'Experienced industrial electrician focused on safe installation, preventive maintenance, and accurate reporting.', 'Diploma in Electrical Technology', 'Electrical installation, Preventive maintenance, LOTO, Safety reporting', 2, 1, 'Operations', 'Electrical Maintenance', '[{\"company_name\":\"Eastern Engineering\",\"location\":\"Dammam\",\"position\":\"Electrician\",\"start_date\":null,\"end_date\":null,\"responsibilities\":\"Installed and maintained industrial electrical systems and completed preventive maintenance reports.\"}]', 'O+', 2200, 1000, 3000, 'hourly', 'rental', 0, 0, 0, 0, 0, 0, 0, 100, '2025-01-15', 'active', 1, '2026-10-05 04:55:02', '2026-10-05 05:00:40'),
(2, 'Mohammed Rahman', 'employee-photos/demo-2.png', 'employee-documents/demo-20.pdf', '2500000002', 'RNT250002', '+966550100002', 'Bangladeshi', 'mohammed.rahman@manpower.local', 'Certified welder with strong fabrication, inspection, and site safety experience.', 'Technical Certificate in Welding', 'MIG welding, ARC welding, Fabrication, Quality inspection', 4, 1, 'Projects', 'Fabrication', '[{\"company_name\":\"Gulf Fabrication Works\",\"location\":\"Jubail\",\"position\":\"Welder\",\"start_date\":null,\"end_date\":null,\"responsibilities\":\"Performed structural welding, joint preparation, visual inspection, and daily equipment checks.\"}]', 'A+', 2000, 1000, 2000, 'hourly', 'rental', 0, 0, 0, 0, 0, 0, 0, 100, '2025-02-01', 'active', 1, '2026-10-05 04:55:02', '2026-10-05 05:07:26'),
(3, 'Joseph Dsouza', 'employee-photos/demo-3.png', 'employee-documents/demo-30.pdf', '2500000003', 'RNT250003', '+966550100003', 'Indian', 'joseph.dsouza@manpower.local', 'Reliable heavy vehicle driver experienced in personnel transport, logistics, and vehicle inspection.', 'Higher Secondary Certificate and Heavy Vehicle Licence', 'Defensive driving, Route planning, Vehicle inspection, Logistics', 5, 1, 'Logistics', 'Transportation', '[{\"company_name\": \"Arabian Logistics\", \"location\": \"Riyadh\", \"position\": \"Heavy Vehicle Driver\", \"duration\": \"2018-06-01 to 2024-12-31\", \"responsibilities\": \"Transported personnel and materials, maintained trip logs, and completed pre-trip safety inspections.\"}]', 'B+', 1800, 1000, 2500, 'hourly', 'rental', 0, 0, 0, 0, 0, 0, 0, 150, '2025-03-10', 'active', 1, '2026-10-05 04:55:02', '2026-10-05 04:55:02'),
(4, 'Faisal Al Qahtani', 'employee-photos/demo-4.png', 'employee-documents/demo-40.pdf', '2500000004', 'OWN250004', '+966550100004', 'Saudi Arabian', 'faisal.qahtani@manpower.local', 'Operations supervisor experienced in workforce coordination, client communication, and performance reporting.', 'Bachelor of Business Administration', 'Team leadership, Workforce planning, Client coordination, KPI reporting', 6, 1, 'Operations', 'Workforce Management', '[{\"company_name\": \"National Industrial Services\", \"location\": \"Riyadh\", \"position\": \"Site Supervisor\", \"duration\": \"2017-01-01 to 2024-08-31\", \"responsibilities\": \"Supervised field teams, coordinated daily assignments, and prepared client performance reports.\"}]', 'AB+', 0, 800, 7500, 'monthly', 'own', 850000, 75000, 50000, 200000, 40000, 35000, 15000, 150, '2024-09-01', 'active', 1, '2026-10-05 04:55:02', '2026-10-05 04:55:02'),
(5, 'Sara Al Mutairi', 'employee-photos/demo-5.png', 'employee-documents/demo-50.pdf', '2500000005', 'OWN250005', '+966550100005', 'Saudi Arabian', 'sara.mutairi@manpower.local', 'Detail-oriented accountant with experience in payroll reconciliation, cost control, and monthly financial reporting.', 'Bachelor of Accounting', 'Payroll reconciliation, Excel, Cost control, Financial reporting', 7, 1, 'Finance', 'Accounts and Payroll', '[{\"company_name\": \"Riyadh Business Group\", \"location\": \"Riyadh\", \"position\": \"Payroll Accountant\", \"duration\": \"2019-03-01 to 2025-02-28\", \"responsibilities\": \"Prepared payroll reconciliations, verified deductions and allowances, and supported monthly closing.\"}]', 'A-', 0, 800, 6500, 'monthly', 'own', 720000, 60000, 45000, 180000, 35000, 30000, 12000, 150, '2025-03-01', 'active', 1, '2026-10-05 04:55:02', '2026-10-05 04:55:02');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(255) DEFAULT NULL,
  `uuid` varchar(36) NOT NULL,
  `document_type` varchar(20) NOT NULL DEFAULT 'invoice',
  `invoice_type` varchar(10) NOT NULL DEFAULT 'standard',
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `reference_invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `issue_date` date NOT NULL,
  `supply_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `contract_po` varchar(255) DEFAULT NULL,
  `delivery_note` varchar(255) DEFAULT NULL,
  `invoice_period` varchar(255) DEFAULT NULL,
  `project_reference` varchar(255) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'SAR',
  `subtotal_cents` bigint(20) UNSIGNED NOT NULL,
  `discount_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_cents` bigint(20) UNSIGNED NOT NULL,
  `total_cents` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `zatca_status` varchar(30) NOT NULL DEFAULT 'not_submitted',
  `notes` text DEFAULT NULL,
  `zatca_message` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `paid_by` bigint(20) UNSIGNED DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `uuid`, `document_type`, `invoice_type`, `customer_id`, `reference_invoice_id`, `issue_date`, `supply_date`, `due_date`, `contract_po`, `delivery_note`, `invoice_period`, `project_reference`, `currency`, `subtotal_cents`, `discount_cents`, `tax_cents`, `total_cents`, `status`, `zatca_status`, `notes`, `zatca_message`, `created_by`, `approved_by`, `approved_at`, `paid_by`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 'DEMO-2026-000001', '00000000-0000-4000-8000-000000000001', 'invoice', 'standard', 1, NULL, '2026-10-05', NULL, NULL, NULL, NULL, NULL, NULL, 'SAR', 710000, 0, 106500, 816500, 'approved', 'not_connected', 'Payment due within 30 days. Showcase document only.', NULL, 1, 1, '2026-10-05 11:23:03', NULL, NULL, '2026-10-05 11:23:03', '2026-10-05 11:58:21');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_customers`
--

CREATE TABLE `invoice_customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_ar` varchar(255) DEFAULT NULL,
  `customer_type` varchar(10) NOT NULL DEFAULT 'business',
  `vat_number` varchar(15) DEFAULT NULL,
  `commercial_registration` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country_code` varchar(2) NOT NULL DEFAULT 'SA',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_customers`
--

INSERT INTO `invoice_customers` (`id`, `name`, `name_ar`, `customer_type`, `vat_number`, `commercial_registration`, `email`, `phone`, `address`, `city`, `postal_code`, `country_code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Eastern Engineering Ltd', 'شركة الهندسة الشرقية', 'business', '310000000000003', '2050000001', 'accounts@eastern.example', '+966 13 800 1000', 'Industrial City', 'Dammam', '32241', 'SA', 1, '2026-10-05 11:13:25', '2026-10-05 11:13:25'),
(2, 'Riyadh Facility Services', 'خدمات مرافق الرياض', 'business', '310000000000011', '1010000002', 'finance@facility.example', '+966 11 800 2000', 'Olaya District', 'Riyadh', '12214', 'SA', 1, '2026-10-05 11:13:25', '2026-10-05 11:13:25'),
(3, 'Walk-in Customer', 'عميل نقدي', 'consumer', NULL, NULL, NULL, NULL, 'Riyadh', 'Riyadh', '12345', 'SA', 1, '2026-10-05 11:13:25', '2026-10-05 11:13:25');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_events`
--

CREATE TABLE `invoice_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_events`
--

INSERT INTO `invoice_events` (`id`, `invoice_id`, `user_id`, `event`, `details`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Approved', 'Showcase invoice created and mock-validated. No ZATCA submission performed.', '2026-10-05 11:23:03', '2026-10-05 11:23:03');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_ar` varchar(255) DEFAULT NULL,
  `unit_code` varchar(10) NOT NULL DEFAULT 'PCE',
  `unit_price_cents` bigint(20) UNSIGNED NOT NULL,
  `tax_category` varchar(10) NOT NULL DEFAULT 'standard',
  `tax_rate_units` int(10) UNSIGNED NOT NULL DEFAULT 1500,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `sku`, `name`, `name_ar`, `unit_code`, `unit_price_cents`, `tax_category`, `tax_rate_units`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'MP-HOUR', 'Manpower service hour', 'ساعة خدمة قوى عاملة', 'HUR', 3500, 'standard', 1500, 1, '2026-10-05 11:13:25', '2026-10-05 11:13:25'),
(2, 'SUP-MONTH', 'Monthly supervision service', 'خدمة إشراف شهرية', 'MON', 500000, 'standard', 1500, 1, '2026-10-05 11:13:25', '2026-10-05 11:13:25'),
(3, 'TRANSPORT', 'Employee transportation', 'نقل الموظفين', 'PCE', 75000, 'standard', 1500, 1, '2026-10-05 11:13:25', '2026-10-05 11:13:25'),
(4, 'ADMIN', 'Administration service', 'خدمة إدارية', 'PCE', 100000, 'standard', 1500, 1, '2026-10-05 11:13:25', '2026-10-05 11:13:25');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_lines`
--

CREATE TABLE `invoice_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `description_ar` varchar(255) DEFAULT NULL,
  `quantity_units` int(10) UNSIGNED NOT NULL,
  `unit_code` varchar(10) NOT NULL,
  `unit_price_cents` bigint(20) UNSIGNED NOT NULL,
  `line_subtotal_cents` bigint(20) UNSIGNED NOT NULL,
  `discount_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_category` varchar(10) NOT NULL,
  `tax_rate_units` int(10) UNSIGNED NOT NULL,
  `tax_cents` bigint(20) UNSIGNED NOT NULL,
  `line_total_cents` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_lines`
--

INSERT INTO `invoice_lines` (`id`, `invoice_id`, `item_id`, `description`, `description_ar`, `quantity_units`, `unit_code`, `unit_price_cents`, `line_subtotal_cents`, `discount_cents`, `tax_category`, `tax_rate_units`, `tax_cents`, `line_total_cents`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Manpower service hour', NULL, 16000, 'HUR', 3500, 560000, 0, 'standard', 1500, 84000, 644000, '2026-10-05 11:23:03', '2026-10-05 11:23:03'),
(2, 1, 3, 'Employee transportation', NULL, 200, 'PCE', 75000, 150000, 0, 'standard', 1500, 22500, 172500, '2026-10-05 11:23:03', '2026-10-05 11:23:03');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_settings`
--

CREATE TABLE `invoice_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `legal_name` varchar(255) NOT NULL,
  `legal_name_ar` varchar(255) DEFAULT NULL,
  `vat_number` varchar(15) NOT NULL,
  `commercial_registration` varchar(30) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `country_code` varchar(2) NOT NULL DEFAULT 'SA',
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(40) DEFAULT NULL,
  `iban` varchar(34) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `invoice_prefix` varchar(20) NOT NULL DEFAULT 'DEMO',
  `next_number` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `demo_mode` tinyint(1) NOT NULL DEFAULT 1,
  `zatca_environment` varchar(255) NOT NULL DEFAULT 'disabled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `design_primary_color` varchar(7) NOT NULL DEFAULT '#167896',
  `design_text_color` varchar(7) NOT NULL DEFAULT '#17343a',
  `design_header_bg` varchar(7) NOT NULL DEFAULT '#e5f4f8',
  `invoice_title` varchar(255) NOT NULL DEFAULT 'TAX INVOICE',
  `invoice_title_ar` varchar(255) NOT NULL DEFAULT 'فاتورة ضريبية',
  `design_density` varchar(10) NOT NULL DEFAULT 'compact',
  `invoice_footer` text DEFAULT NULL,
  `show_bank_details` tinyint(1) NOT NULL DEFAULT 1,
  `show_signatures` tinyint(1) NOT NULL DEFAULT 1,
  `show_qr` tinyint(1) NOT NULL DEFAULT 1,
  `design_header_layout` varchar(20) NOT NULL DEFAULT 'split',
  `design_title_alignment` varchar(10) NOT NULL DEFAULT 'center',
  `design_logo_width` smallint(5) UNSIGNED NOT NULL DEFAULT 58,
  `design_font_size` decimal(4,1) NOT NULL DEFAULT 7.3,
  `design_border_color` varchar(7) NOT NULL DEFAULT '#86999d',
  `show_company_cr` tinyint(1) NOT NULL DEFAULT 1,
  `show_seller_details` tinyint(1) NOT NULL DEFAULT 1,
  `show_customer_details` tinyint(1) NOT NULL DEFAULT 1,
  `show_references` tinyint(1) NOT NULL DEFAULT 1,
  `show_amount_words` tinyint(1) NOT NULL DEFAULT 1,
  `show_notes` tinyint(1) NOT NULL DEFAULT 1,
  `show_footer_uuid` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_settings`
--

INSERT INTO `invoice_settings` (`id`, `legal_name`, `legal_name_ar`, `vat_number`, `commercial_registration`, `address`, `city`, `postal_code`, `country_code`, `bank_name`, `bank_account_name`, `bank_account_number`, `iban`, `bank_branch`, `invoice_prefix`, `next_number`, `demo_mode`, `zatca_environment`, `created_at`, `updated_at`, `logo_path`, `design_primary_color`, `design_text_color`, `design_header_bg`, `invoice_title`, `invoice_title_ar`, `design_density`, `invoice_footer`, `show_bank_details`, `show_signatures`, `show_qr`, `design_header_layout`, `design_title_alignment`, `design_logo_width`, `design_font_size`, `design_border_color`, `show_company_cr`, `show_seller_details`, `show_customer_details`, `show_references`, `show_amount_words`, `show_notes`, `show_footer_uuid`) VALUES
(1, 'Manpower Demo Company LLC', 'شركة القوى العاملة التجريبية', '300000000000003', '1010000000', 'King Fahd Road, Demo Building', 'Riyadh', '12345', 'SA', NULL, NULL, NULL, NULL, NULL, 'INV', 2, 0, 'disabled', '2026-10-05 11:13:25', '2026-10-05 12:41:06', NULL, '#167896', '#17343a', '#e5f4f8', 'TAX INVOICE', 'فاتورة ضريبية', 'compact', NULL, 1, 1, 1, 'blank', 'center', 58, 7.3, '#86999d', 1, 1, 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2026_10_03_000001_create_manpower_tables', 1),
(5, '2026_10_04_000001_remove_demo_labels', 1),
(6, '2026_10_04_000002_add_username_to_users', 1),
(7, '2026_10_04_000003_add_cv_fields_to_employees', 1),
(8, '2026_10_04_000004_add_company_master_fields', 1),
(9, '2026_10_04_000005_add_payroll_approval_and_overtime_rate', 1),
(10, '2026_10_04_000006_add_regular_hours_to_employees', 1),
(11, '2026_10_04_000007_add_own_employee_salary_components', 1),
(12, '2026_10_04_000008_add_housing_allowance', 1),
(13, '2026_10_04_000009_convert_overtime_multiplier_to_fixed_rate', 2),
(14, '2026_10_04_000010_add_high_volume_query_indexes', 2),
(15, '2026_10_05_000001_create_invoicing_module_tables', 3),
(16, '2026_10_05_000002_separate_zatca_from_invoice_platform', 4),
(17, '2026_10_05_000003_add_professional_invoice_fields', 5),
(18, '2026_10_05_000004_add_invoice_design_settings', 6),
(19, '2026_10_05_000005_expand_invoice_design_controls', 7),
(20, '2026_10_05_000006_create_safety_shop_inventory_tables', 8),
(21, '2026_10_06_000001_add_customer_phone_to_safety_shop_sales', 9),
(22, '2026_10_06_000002_create_document_library_files_table', 10),
(23, '2026_10_06_000003_add_discount_and_split_payments_to_safety_shop_sales', 11),
(24, '2026_10_06_000004_create_safety_shop_receipt_settings_table', 12),
(25, '2026_10_06_000005_create_safety_shop_returns_tables', 13),
(26, '2026_10_06_000006_add_financial_snapshots_to_safety_shop', 14),
(27, '2026_10_06_000007_backfill_safety_shop_return_financials', 15),
(28, '2026_10_06_000008_create_safety_shop_customers', 16);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payrolls`
--

CREATE TABLE `payrolls` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `month` varchar(7) NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `salary_type` varchar(255) NOT NULL,
  `employment_type` varchar(255) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `designation_name` varchar(255) NOT NULL,
  `directorate` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `iqama_number` varchar(255) NOT NULL,
  `regular_units` int(10) UNSIGNED NOT NULL,
  `overtime_units` int(10) UNSIGNED NOT NULL,
  `regular_pay_cents` bigint(20) UNSIGNED NOT NULL,
  `overtime_pay_cents` bigint(20) UNSIGNED NOT NULL,
  `allowance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meal_allowance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `transportation_allowance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `housing_allowance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `medical_allowance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `deduction_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `retirement_insurance_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `net_pay_cents` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `paid_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payrolls`
--

INSERT INTO `payrolls` (`id`, `employee_id`, `month`, `company_id`, `salary_type`, `employment_type`, `employee_name`, `company_name`, `designation_name`, `directorate`, `department`, `iqama_number`, `regular_units`, `overtime_units`, `regular_pay_cents`, `overtime_pay_cents`, `allowance_cents`, `meal_allowance_cents`, `transportation_allowance_cents`, `housing_allowance_cents`, `medical_allowance_cents`, `deduction_cents`, `retirement_insurance_cents`, `tax_cents`, `net_pay_cents`, `status`, `notes`, `paid_at`, `created_by`, `approved_by`, `approved_at`, `paid_by`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-01', 1, 'hourly', 'rental', 'Mohammed Rahman', 'Manpower Operations', 'Welder', 'Projects', 'Fabrication', '2500000002', 25700, 0, 514000, 0, 0, 0, 0, 0, 0, 50000, 0, 0, 464000, 'approved', NULL, NULL, 2, 2, '2026-10-05 05:08:52', NULL, '2026-10-05 05:08:41', '2026-10-05 05:08:52');

-- --------------------------------------------------------

--
-- Table structure for table `safety_shop_customers`
--

CREATE TABLE `safety_shop_customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `purchase_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `lifetime_value_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `last_purchase_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `safety_shop_masters`
--

CREATE TABLE `safety_shop_masters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `safety_shop_masters`
--

INSERT INTO `safety_shop_masters` (`id`, `type`, `name`, `phone`, `email`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'location', 'Main Safety Shop', NULL, NULL, 'Jubail', 1, '2026-10-06 05:34:50', '2026-10-06 05:34:50'),
(2, 'category', 'Shoes', NULL, NULL, NULL, 1, '2026-10-06 05:43:13', '2026-10-06 05:43:13'),
(3, 'category', 'Safety Vast', NULL, NULL, NULL, 1, '2026-10-06 05:43:26', '2026-10-06 05:43:26');

-- --------------------------------------------------------

--
-- Table structure for table `safety_shop_movements`
--

CREATE TABLE `safety_shop_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_key` char(36) NOT NULL,
  `type` varchar(20) NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `destination_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `balance_after` int(10) UNSIGNED NOT NULL,
  `destination_balance_after` int(10) UNSIGNED DEFAULT NULL,
  `movement_date` date NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `recipient` varchar(150) DEFAULT NULL,
  `notes` text NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `safety_shop_movements`
--

INSERT INTO `safety_shop_movements` (`id`, `request_key`, `type`, `product_id`, `location_id`, `destination_id`, `supplier_id`, `quantity`, `balance_after`, `destination_balance_after`, `movement_date`, `reference`, `recipient`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '8d18c7e5-8cae-4057-a1f5-e9111f463538', 'adjustment', 1, 1, NULL, NULL, 20, 20, NULL, '2026-10-06', NULL, '12345', 'stock', 2, '2026-10-06 05:50:06', '2026-10-06 05:50:06'),
(2, '832d7815-bafd-429a-a979-d45b5a4aa854', 'issue', 1, 1, NULL, NULL, -3, 17, NULL, '2026-10-06', 'SALE-1', 'Walk-in customer', 'Barcode checkout sale #1', 2, '2026-10-06 06:03:04', '2026-10-06 06:03:04'),
(3, 'ef8da0db-413a-4544-b734-a9743e50e2e8', 'issue', 1, 1, NULL, NULL, -2, 15, NULL, '2026-10-06', 'SALE-2', 'Walk-in customer', 'Barcode checkout sale #2', 2, '2026-10-06 06:05:31', '2026-10-06 06:05:31'),
(4, '4e4b4460-7bf1-420f-94ea-7c3d076ac5f2', 'return', 1, 1, NULL, NULL, 2, 17, NULL, '2026-10-06', 'RETURN-1 / SALE-1', 'Walk-in customer', 'Customer return #1 · w', 2, '2026-10-06 06:42:53', '2026-10-06 06:42:53'),
(5, '37284399-6f19-493b-9f56-baef8ad01f17', 'return', 1, 1, NULL, NULL, 1, 18, NULL, '2026-10-06', 'RETURN-2 / SALE-2', 'Walk-in customer', 'Customer return #2 · e', 2, '2026-10-06 06:43:28', '2026-10-06 06:43:28');

-- --------------------------------------------------------

--
-- Table structure for table `safety_shop_products`
--

CREATE TABLE `safety_shop_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `unit` varchar(30) NOT NULL DEFAULT 'piece',
  `safety_standard` varchar(150) DEFAULT NULL,
  `reorder_level` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cost_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `price_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `safety_shop_products`
--

INSERT INTO `safety_shop_products` (`id`, `barcode`, `sku`, `name`, `category_id`, `brand`, `size`, `unit`, `safety_standard`, `reorder_level`, `cost_cents`, `price_cents`, `is_active`, `notes`, `created_at`, `updated_at`) VALUES
(1, '123456789', '22222', 'Shoes', 2, 'new', 'xl', 'piece', 'iso', 10, 2000, 5000, 1, 'testing', '2026-10-06 05:46:34', '2026-10-06 05:46:34');

-- --------------------------------------------------------

--
-- Table structure for table `safety_shop_receipt_settings`
--

CREATE TABLE `safety_shop_receipt_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(180) NOT NULL DEFAULT 'Safety Shop',
  `tagline` varchar(180) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `vat_number` varchar(30) DEFAULT NULL,
  `commercial_registration` varchar(30) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `footer_text` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `safety_shop_receipt_settings`
--

INSERT INTO `safety_shop_receipt_settings` (`id`, `company_name`, `tagline`, `logo_path`, `vat_number`, `commercial_registration`, `phone`, `email`, `website`, `address`, `city`, `postal_code`, `footer_text`, `created_at`, `updated_at`) VALUES
(1, 'Safety Shop', 'Manpower · Workforce Management', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Thank you for your business.', '2026-10-06 06:12:53', '2026-10-06 06:12:53');

-- --------------------------------------------------------

--
-- Table structure for table `safety_shop_returns`
--

CREATE TABLE `safety_shop_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_key` char(36) NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `refund_method` varchar(20) NOT NULL,
  `refund_cents` bigint(20) UNSIGNED NOT NULL,
  `gross_refund_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `discount_refund_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_refund_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `cost_reversal_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `reason` varchar(500) NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `safety_shop_returns`
--

INSERT INTO `safety_shop_returns` (`id`, `request_key`, `sale_id`, `location_id`, `refund_method`, `refund_cents`, `gross_refund_cents`, `discount_refund_cents`, `tax_refund_cents`, `cost_reversal_cents`, `reason`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '3314a0e3-d5ce-4a66-a98b-329fcc9d624d', 1, 1, 'cash', 9200, 10000, 2000, 1200, 4000, 'w', 2, '2026-10-06 06:42:53', '2026-10-06 06:42:53'),
(2, '7af9b48b-cda9-4b02-a04e-900e3c4055c0', 2, 1, 'cash', 5175, 5000, 500, 675, 2000, 'e', 2, '2026-10-06 06:43:28', '2026-10-06 06:43:28');

-- --------------------------------------------------------

--
-- Table structure for table `safety_shop_return_lines`
--

CREATE TABLE `safety_shop_return_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `return_id` bigint(20) UNSIGNED NOT NULL,
  `sale_line_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `cost_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `refund_cents` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `safety_shop_return_lines`
--

INSERT INTO `safety_shop_return_lines` (`id`, `return_id`, `sale_line_id`, `product_id`, `quantity`, `cost_cents`, `refund_cents`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 2, 4000, 9200, '2026-10-06 06:42:53', '2026-10-06 06:42:53'),
(2, 2, 2, 1, 1, 2000, 5175, '2026-10-06 06:43:28', '2026-10-06 06:43:28');

-- --------------------------------------------------------

--
-- Table structure for table `safety_shop_sales`
--

CREATE TABLE `safety_shop_sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_key` char(36) NOT NULL,
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer` varchar(150) NOT NULL,
  `customer_phone` varchar(30) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_address` varchar(500) DEFAULT NULL,
  `subtotal_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `discount_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `taxable_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_rate_units` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tax_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `total_cents` bigint(20) UNSIGNED NOT NULL,
  `paid_cents` bigint(20) UNSIGNED NOT NULL,
  `cash_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `card_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `bank_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `payment_method` varchar(20) NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `safety_shop_sales`
--

INSERT INTO `safety_shop_sales` (`id`, `request_key`, `location_id`, `customer_id`, `customer`, `customer_phone`, `customer_email`, `customer_address`, `subtotal_cents`, `discount_cents`, `taxable_cents`, `tax_rate_units`, `tax_cents`, `total_cents`, `paid_cents`, `cash_cents`, `card_cents`, `bank_cents`, `payment_method`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'e2a3985b-975c-4ae8-9c46-6411b2f8a6d0', 1, NULL, 'Walk-in customer', NULL, NULL, NULL, 15000, 3000, 12000, 1500, 1800, 13800, 15000, 2000, 10000, 3000, 'split', 2, '2026-10-06 06:03:04', '2026-10-06 06:03:04'),
(2, '68776958-dfee-4747-a3b7-d93a769738f5', 1, NULL, 'Walk-in customer', NULL, NULL, NULL, 10000, 1000, 9000, 1500, 1350, 10350, 12000, 12000, 0, 0, 'cash', 2, '2026-10-06 06:05:31', '2026-10-06 06:05:31');

-- --------------------------------------------------------

--
-- Table structure for table `safety_shop_sale_lines`
--

CREATE TABLE `safety_shop_sale_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `sku` varchar(50) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `cost_cents` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `price_cents` bigint(20) UNSIGNED NOT NULL,
  `total_cents` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `safety_shop_sale_lines`
--

INSERT INTO `safety_shop_sale_lines` (`id`, `sale_id`, `product_id`, `sku`, `barcode`, `name`, `unit`, `quantity`, `cost_cents`, `price_cents`, `total_cents`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '22222', '123456789', 'Shoes', 'piece', 3, 2000, 5000, 15000, '2026-10-06 06:03:04', '2026-10-06 06:03:04'),
(2, 2, 1, '22222', '123456789', 'Shoes', 'piece', 2, 2000, 5000, 10000, '2026-10-06 06:05:31', '2026-10-06 06:05:31');

-- --------------------------------------------------------

--
-- Table structure for table `safety_shop_stocks`
--

CREATE TABLE `safety_shop_stocks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `safety_shop_stocks`
--

INSERT INTO `safety_shop_stocks` (`id`, `product_id`, `location_id`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 18, '2026-10-06 05:50:06', '2026-10-06 06:43:28');

-- --------------------------------------------------------

--
-- Table structure for table `timesheets`
--

CREATE TABLE `timesheets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `work_date` date NOT NULL,
  `regular_units` int(10) UNSIGNED NOT NULL,
  `overtime_units` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `hourly_rate_cents` int(10) UNSIGNED NOT NULL,
  `overtime_rate_cents` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `overtime_multiplier_units` int(10) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `payroll_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timesheets`
--

INSERT INTO `timesheets` (`id`, `employee_id`, `work_date`, `regular_units`, `overtime_units`, `hourly_rate_cents`, `overtime_rate_cents`, `overtime_multiplier_units`, `status`, `notes`, `review_note`, `created_by`, `reviewed_by`, `reviewed_at`, `payroll_id`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-01-01', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:05:46', 1, '2026-10-05 05:03:13', '2026-10-05 05:08:41'),
(2, 2, '2026-01-03', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:05:46', 1, '2026-10-05 05:03:13', '2026-10-05 05:08:41'),
(3, 2, '2026-01-04', 700, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:05:46', 1, '2026-10-05 05:03:13', '2026-10-05 05:08:41'),
(4, 2, '2026-01-05', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:05:46', 1, '2026-10-05 05:03:13', '2026-10-05 05:08:41'),
(5, 2, '2026-01-06', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:05:46', 1, '2026-10-05 05:03:13', '2026-10-05 05:08:41'),
(6, 2, '2026-01-07', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:05:46', 1, '2026-10-05 05:03:13', '2026-10-05 05:08:41'),
(7, 2, '2026-01-08', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:13', '2026-10-05 05:08:41'),
(8, 2, '2026-01-10', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:13', '2026-10-05 05:08:41'),
(9, 2, '2026-01-11', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(10, 2, '2026-01-12', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(11, 2, '2026-01-13', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(12, 2, '2026-01-14', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(13, 2, '2026-01-15', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(14, 2, '2026-01-17', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(15, 2, '2026-01-18', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(16, 2, '2026-01-19', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(17, 2, '2026-01-20', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(18, 2, '2026-01-21', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(19, 2, '2026-01-22', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(20, 2, '2026-01-24', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(21, 2, '2026-01-25', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(22, 2, '2026-01-26', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(23, 2, '2026-01-27', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(24, 2, '2026-01-28', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(25, 2, '2026-01-29', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41'),
(26, 2, '2026-01-31', 1000, 0, 2000, 2800, 100, 'approved', 'January 2025', NULL, 2, 2, '2026-10-05 05:04:06', 1, '2026-10-05 05:03:14', '2026-10-05 05:08:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'manager',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`, `is_active`) VALUES
(1, 'Super Admin', 'superadmin', 'superadmin@manpower.local', NULL, '$2y$10$j4EmHDXN/3z.O0phvSa2KOg2xSFXcaeu95BmbIe7lBimszDBAkL0a', NULL, '2026-10-04 08:10:12', '2026-10-04 08:10:55', 'super_admin', 1),
(2, 'Admin Approver', 'admin', 'admin@manpower.local', NULL, '$2y$10$j4EmHDXN/3z.O0phvSa2KOg2xSFXcaeu95BmbIe7lBimszDBAkL0a', NULL, '2026-10-04 08:10:12', '2026-10-04 08:10:55', 'admin', 1),
(3, 'Manager', 'manager', 'manager@manpower.local', NULL, '$2y$10$j4EmHDXN/3z.O0phvSa2KOg2xSFXcaeu95BmbIe7lBimszDBAkL0a', NULL, '2026-10-04 08:10:12', '2026-10-04 08:10:55', 'manager', 1),
(4, 'Payroll Administrator', 'payroll_admin', 'payroll_admin@manpower.local', '2026-10-05 04:54:01', '$2y$10$j4EmHDXN/3z.O0phvSa2KOg2xSFXcaeu95BmbIe7lBimszDBAkL0a', NULL, '2026-10-05 04:54:01', '2026-10-05 04:54:01', 'admin', 1),
(5, 'HR Administrator', 'hr_admin', 'hr_admin@manpower.local', '2026-10-05 04:54:01', '$2y$10$j4EmHDXN/3z.O0phvSa2KOg2xSFXcaeu95BmbIe7lBimszDBAkL0a', NULL, '2026-10-05 04:54:01', '2026-10-05 04:54:01', 'admin', 1),
(6, 'Operations Manager', 'operations_manager', 'operations_manager@manpower.local', '2026-10-05 04:54:01', '$2y$10$j4EmHDXN/3z.O0phvSa2KOg2xSFXcaeu95BmbIe7lBimszDBAkL0a', NULL, '2026-10-05 04:54:01', '2026-10-05 04:54:01', 'manager', 1),
(7, 'Timesheet Manager', 'timesheet_manager', 'timesheet_manager@manpower.local', '2026-10-05 04:54:01', '$2y$10$j4EmHDXN/3z.O0phvSa2KOg2xSFXcaeu95BmbIe7lBimszDBAkL0a', NULL, '2026-10-05 04:54:01', '2026-10-05 04:54:01', 'manager', 1),
(8, 'Workforce Manager', 'workforce_manager', 'workforce_manager@manpower.local', '2026-10-05 04:54:01', '$2y$10$j4EmHDXN/3z.O0phvSa2KOg2xSFXcaeu95BmbIe7lBimszDBAkL0a', NULL, '2026-10-05 04:54:01', '2026-10-05 04:54:01', 'manager', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_user_id_foreign` (`user_id`),
  ADD KEY `activity_action_created_index` (`action`,`created_at`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `companies_name_unique` (`name`);

--
-- Indexes for table `company_user`
--
ALTER TABLE `company_user`
  ADD PRIMARY KEY (`company_id`,`user_id`),
  ADD KEY `company_user_user_id_foreign` (`user_id`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `designations_name_unique` (`name`);

--
-- Indexes for table `document_library_files`
--
ALTER TABLE `document_library_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_library_files_storage_path_unique` (`storage_path`),
  ADD KEY `document_library_files_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `document_library_files_owner_id_category_index` (`owner_id`,`category`),
  ADD KEY `document_library_files_expires_on_index` (`expires_on`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_iqama_number_unique` (`iqama_number`),
  ADD UNIQUE KEY `employees_passport_number_unique` (`passport_number`),
  ADD KEY `employees_designation_id_foreign` (`designation_id`),
  ADD KEY `employees_created_by_foreign` (`created_by`),
  ADD KEY `employees_company_id_status_index` (`company_id`,`status`),
  ADD KEY `employees_employment_type_index` (`employment_type`),
  ADD KEY `employees_type_company_status_index` (`employment_type`,`company_id`,`status`),
  ADD KEY `employees_name_index` (`name`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  ADD KEY `invoices_customer_id_foreign` (`customer_id`),
  ADD KEY `invoices_reference_invoice_id_foreign` (`reference_invoice_id`),
  ADD KEY `invoices_created_by_foreign` (`created_by`),
  ADD KEY `invoices_approved_by_foreign` (`approved_by`),
  ADD KEY `invoices_paid_by_foreign` (`paid_by`),
  ADD KEY `invoices_issue_date_status_index` (`issue_date`,`status`),
  ADD KEY `invoices_invoice_type_zatca_status_index` (`invoice_type`,`zatca_status`);

--
-- Indexes for table `invoice_customers`
--
ALTER TABLE `invoice_customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_customers_customer_type_is_active_index` (`customer_type`,`is_active`);

--
-- Indexes for table `invoice_events`
--
ALTER TABLE `invoice_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_events_invoice_id_foreign` (`invoice_id`),
  ADD KEY `invoice_events_user_id_foreign` (`user_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_items_sku_unique` (`sku`);

--
-- Indexes for table `invoice_lines`
--
ALTER TABLE `invoice_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_lines_invoice_id_foreign` (`invoice_id`),
  ADD KEY `invoice_lines_item_id_foreign` (`item_id`);

--
-- Indexes for table `invoice_settings`
--
ALTER TABLE `invoice_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payrolls_employee_id_month_unique` (`employee_id`,`month`),
  ADD KEY `payrolls_company_id_foreign` (`company_id`),
  ADD KEY `payrolls_created_by_foreign` (`created_by`),
  ADD KEY `payrolls_paid_by_foreign` (`paid_by`),
  ADD KEY `payrolls_approved_by_foreign` (`approved_by`),
  ADD KEY `payrolls_type_month_company_status_index` (`employment_type`,`month`,`company_id`,`status`),
  ADD KEY `payrolls_employee_name_index` (`employee_name`);

--
-- Indexes for table `safety_shop_customers`
--
ALTER TABLE `safety_shop_customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `safety_shop_customers_phone_unique` (`phone`),
  ADD KEY `safety_shop_customers_name_index` (`name`);

--
-- Indexes for table `safety_shop_masters`
--
ALTER TABLE `safety_shop_masters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `safety_shop_masters_type_name_unique` (`type`,`name`);

--
-- Indexes for table `safety_shop_movements`
--
ALTER TABLE `safety_shop_movements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `safety_shop_movements_request_key_unique` (`request_key`),
  ADD KEY `safety_shop_movements_location_id_foreign` (`location_id`),
  ADD KEY `safety_shop_movements_destination_id_foreign` (`destination_id`),
  ADD KEY `safety_shop_movements_supplier_id_foreign` (`supplier_id`),
  ADD KEY `safety_shop_movements_created_by_foreign` (`created_by`),
  ADD KEY `safety_shop_movements_movement_date_type_index` (`movement_date`,`type`),
  ADD KEY `safety_shop_movements_product_id_location_id_index` (`product_id`,`location_id`);

--
-- Indexes for table `safety_shop_products`
--
ALTER TABLE `safety_shop_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `safety_shop_products_sku_unique` (`sku`),
  ADD UNIQUE KEY `safety_shop_products_barcode_unique` (`barcode`),
  ADD KEY `safety_shop_products_category_id_foreign` (`category_id`);

--
-- Indexes for table `safety_shop_receipt_settings`
--
ALTER TABLE `safety_shop_receipt_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `safety_shop_returns`
--
ALTER TABLE `safety_shop_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `safety_shop_returns_request_key_unique` (`request_key`),
  ADD KEY `safety_shop_returns_location_id_foreign` (`location_id`),
  ADD KEY `safety_shop_returns_created_by_foreign` (`created_by`),
  ADD KEY `safety_shop_returns_sale_id_created_at_index` (`sale_id`,`created_at`);

--
-- Indexes for table `safety_shop_return_lines`
--
ALTER TABLE `safety_shop_return_lines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `safety_shop_return_lines_return_id_sale_line_id_unique` (`return_id`,`sale_line_id`),
  ADD KEY `safety_shop_return_lines_product_id_foreign` (`product_id`),
  ADD KEY `safety_shop_return_lines_sale_line_id_index` (`sale_line_id`);

--
-- Indexes for table `safety_shop_sales`
--
ALTER TABLE `safety_shop_sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `safety_shop_sales_request_key_unique` (`request_key`),
  ADD KEY `safety_shop_sales_location_id_foreign` (`location_id`),
  ADD KEY `safety_shop_sales_created_by_foreign` (`created_by`),
  ADD KEY `safety_shop_sales_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `safety_shop_sale_lines`
--
ALTER TABLE `safety_shop_sale_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `safety_shop_sale_lines_sale_id_foreign` (`sale_id`),
  ADD KEY `safety_shop_sale_lines_product_id_foreign` (`product_id`);

--
-- Indexes for table `safety_shop_stocks`
--
ALTER TABLE `safety_shop_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `safety_shop_stocks_product_id_location_id_unique` (`product_id`,`location_id`),
  ADD KEY `safety_shop_stocks_location_id_foreign` (`location_id`);

--
-- Indexes for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `timesheets_employee_id_work_date_unique` (`employee_id`,`work_date`),
  ADD KEY `timesheets_created_by_foreign` (`created_by`),
  ADD KEY `timesheets_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `timesheets_payroll_id_foreign` (`payroll_id`),
  ADD KEY `timesheets_work_date_status_index` (`work_date`,`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `document_library_files`
--
ALTER TABLE `document_library_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoice_customers`
--
ALTER TABLE `invoice_customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoice_events`
--
ALTER TABLE `invoice_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoice_lines`
--
ALTER TABLE `invoice_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoice_settings`
--
ALTER TABLE `invoice_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `payrolls`
--
ALTER TABLE `payrolls`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `safety_shop_customers`
--
ALTER TABLE `safety_shop_customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `safety_shop_masters`
--
ALTER TABLE `safety_shop_masters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `safety_shop_movements`
--
ALTER TABLE `safety_shop_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `safety_shop_products`
--
ALTER TABLE `safety_shop_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `safety_shop_receipt_settings`
--
ALTER TABLE `safety_shop_receipt_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `safety_shop_returns`
--
ALTER TABLE `safety_shop_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `safety_shop_return_lines`
--
ALTER TABLE `safety_shop_return_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `safety_shop_sales`
--
ALTER TABLE `safety_shop_sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `safety_shop_sale_lines`
--
ALTER TABLE `safety_shop_sale_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `safety_shop_stocks`
--
ALTER TABLE `safety_shop_stocks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timesheets`
--
ALTER TABLE `timesheets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `company_user`
--
ALTER TABLE `company_user`
  ADD CONSTRAINT `company_user_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_library_files`
--
ALTER TABLE `document_library_files`
  ADD CONSTRAINT `document_library_files_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `document_library_files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `employees_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `employees_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `invoice_customers` (`id`),
  ADD CONSTRAINT `invoices_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoices_reference_invoice_id_foreign` FOREIGN KEY (`reference_invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `invoice_events`
--
ALTER TABLE `invoice_events`
  ADD CONSTRAINT `invoice_events_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_lines`
--
ALTER TABLE `invoice_lines`
  ADD CONSTRAINT `invoice_lines_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `invoice_items` (`id`);

--
-- Constraints for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD CONSTRAINT `payrolls_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payrolls_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `payrolls_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payrolls_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `payrolls_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `safety_shop_movements`
--
ALTER TABLE `safety_shop_movements`
  ADD CONSTRAINT `safety_shop_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `safety_shop_movements_destination_id_foreign` FOREIGN KEY (`destination_id`) REFERENCES `safety_shop_masters` (`id`),
  ADD CONSTRAINT `safety_shop_movements_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `safety_shop_masters` (`id`),
  ADD CONSTRAINT `safety_shop_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `safety_shop_products` (`id`),
  ADD CONSTRAINT `safety_shop_movements_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `safety_shop_masters` (`id`);

--
-- Constraints for table `safety_shop_products`
--
ALTER TABLE `safety_shop_products`
  ADD CONSTRAINT `safety_shop_products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `safety_shop_masters` (`id`);

--
-- Constraints for table `safety_shop_returns`
--
ALTER TABLE `safety_shop_returns`
  ADD CONSTRAINT `safety_shop_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `safety_shop_returns_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `safety_shop_masters` (`id`),
  ADD CONSTRAINT `safety_shop_returns_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `safety_shop_sales` (`id`);

--
-- Constraints for table `safety_shop_return_lines`
--
ALTER TABLE `safety_shop_return_lines`
  ADD CONSTRAINT `safety_shop_return_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `safety_shop_products` (`id`),
  ADD CONSTRAINT `safety_shop_return_lines_return_id_foreign` FOREIGN KEY (`return_id`) REFERENCES `safety_shop_returns` (`id`),
  ADD CONSTRAINT `safety_shop_return_lines_sale_line_id_foreign` FOREIGN KEY (`sale_line_id`) REFERENCES `safety_shop_sale_lines` (`id`);

--
-- Constraints for table `safety_shop_sales`
--
ALTER TABLE `safety_shop_sales`
  ADD CONSTRAINT `safety_shop_sales_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `safety_shop_sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `safety_shop_customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `safety_shop_sales_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `safety_shop_masters` (`id`);

--
-- Constraints for table `safety_shop_sale_lines`
--
ALTER TABLE `safety_shop_sale_lines`
  ADD CONSTRAINT `safety_shop_sale_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `safety_shop_products` (`id`),
  ADD CONSTRAINT `safety_shop_sale_lines_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `safety_shop_sales` (`id`);

--
-- Constraints for table `safety_shop_stocks`
--
ALTER TABLE `safety_shop_stocks`
  ADD CONSTRAINT `safety_shop_stocks_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `safety_shop_masters` (`id`),
  ADD CONSTRAINT `safety_shop_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `safety_shop_products` (`id`);

--
-- Constraints for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD CONSTRAINT `timesheets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `timesheets_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `timesheets_payroll_id_foreign` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`),
  ADD CONSTRAINT `timesheets_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
