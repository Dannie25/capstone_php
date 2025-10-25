-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2025 at 05:19 PM
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
-- Database: `capstone_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_content`
--

CREATE TABLE `about_content` (
  `id` int(11) NOT NULL,
  `content_key` varchar(191) NOT NULL,
  `content_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_content`
--

INSERT INTO `about_content` (`id`, `content_key`, `content_value`, `updated_at`) VALUES
(1, 'about_title', 'About MTC Clothing', '2025-10-01 05:47:26'),
(2, 'about_tagline', 'Mastering tailoring and fabrics — where quality meets craft. We create garments that fit beautifully, feel great, and last longer.', '2025-10-01 05:47:26'),
(3, 'about_why_title', 'Why customers choose us', '2025-10-01 05:47:26'),
(4, 'about_why_1', 'Tailor-grade craftsmanship with modern fits', '2025-10-01 05:47:26'),
(5, 'about_why_2', 'Curated, durable, and comfortable fabrics', '2025-10-01 05:47:26'),
(6, 'about_why_3', 'Small-batch production and careful QC', '2025-10-01 05:47:26'),
(7, 'about_why_4', 'Friendly support and a fit-first mindset', '2025-10-01 05:47:26'),
(8, 'about_story_title', 'Our Story', '2025-10-01 05:47:26'),
(9, 'about_story_p1', 'MTC Clothing began with a simple belief: clothes should be crafted to fit you, not the other way around. What started as a small tailoring workshop has grown into a community of makers and customers who care about quality, comfort, and timeless style.', '2025-10-01 05:47:26'),
(10, 'about_story_p2', 'From fabric selection to the final stitch, we obsess over details—so your garments look great, feel better, and last longer.', '2025-10-01 05:47:26'),
(11, 'about_mission_title', 'Mission & Values', '2025-10-01 05:47:26'),
(12, 'about_values_1', 'Quality first — built to last and to love', '2025-10-01 05:47:26'),
(13, 'about_values_2', 'Craftsmanship — precise tailoring and clean finishes', '2025-10-01 05:47:26'),
(14, 'about_values_3', 'Comfort & Fit — modern silhouettes that move with you', '2025-10-01 05:47:26'),
(15, 'about_values_4', 'Customer-first — friendly support and easy help', '2025-10-01 05:47:26'),
(16, 'about_values_5', 'Responsible production — small batches, less waste', '2025-10-01 05:47:26'),
(17, 'about_craft_title', 'Materials & Craft', '2025-10-01 05:47:26'),
(18, 'about_craft_p1', 'We work with breathable, durable fabrics and finish every piece with careful construction—reinforced stress points, clean seams, and a thorough quality check before your order leaves our workshop.', '2025-10-01 05:47:26'),
(19, 'about_diff_title', 'What Sets Us Apart', '2025-10-01 05:47:26'),
(20, 'about_diff_1', 'Tailor-grade construction with everyday wearability', '2025-10-01 05:47:26'),
(21, 'about_diff_2', 'Limited runs for better quality control', '2025-10-01 05:47:26'),
(22, 'about_diff_3', 'Local craftsmanship you can trust', '2025-10-01 05:47:26'),
(23, 'about_diff_4', 'Fit-first support and easy alterations', '2025-10-01 05:47:26');

-- --------------------------------------------------------

--
-- Table structure for table `about_map`
--

CREATE TABLE `about_map` (
  `id` int(11) NOT NULL,
  `content_key` varchar(191) NOT NULL,
  `content_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_map`
--

INSERT INTO `about_map` (`id`, `content_key`, `content_value`, `updated_at`) VALUES
(1, 'about_map_lat', '14.3306101', '2025-10-01 05:47:26'),
(2, 'about_map_lng', '120.9364813', '2025-10-01 05:47:26'),
(3, 'about_map_zoom', '15', '2025-10-01 05:47:26'),
(4, 'about_map_popup', 'MTC Clothing Workshop', '2025-10-01 05:47:26');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `size` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `size`, `color`, `created_at`) VALUES
(45, 1, 3, 1, NULL, NULL, '2025-10-06 06:16:11');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_faqs`
--

CREATE TABLE `chatbot_faqs` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_faqs`
--

INSERT INTO `chatbot_faqs` (`id`, `question`, `answer`, `sort_order`, `is_active`, `created_at`) VALUES
(3, 'What are your store hours?', 'Our online store is open 24/7. Support hours are Monday–Friday, 9:00 AM–6:00 PM.', 1, 1, '2025-10-06 07:32:17'),
(4, 'How long does shipping take?', 'Standard shipping usually takes 3–7 business days, depending on your location and courier timelines.', 2, 1, '2025-10-06 07:32:17'),
(5, 'How much is the shipping fee?', 'Shipping fee is calculated at checkout based on your address and courier rates.', 3, 1, '2025-10-06 07:32:17'),
(6, 'Do you deliver to my area?', 'We ship nationwide. Enter your address at checkout to confirm delivery and fees.', 4, 1, '2025-10-06 07:32:17'),
(7, 'How can I track my order?', 'Go to My Orders after logging in to view status and tracking info once available.', 5, 1, '2025-10-06 07:32:17'),
(8, 'What payment methods do you accept?', 'We accept GCash and other available payment options shown at checkout. COD may be available in select locations.', 6, 1, '2025-10-06 07:32:17'),
(9, 'Do you accept Cash on Delivery (COD)?', 'Yes, COD is available for select areas. You’ll see the option at checkout if eligible.', 7, 1, '2025-10-06 07:32:17'),
(10, 'Can I pay via GCash?', 'Yes. Choose GCash at checkout and follow the instructions. Upload proof if prompted.', 8, 1, '2025-10-06 07:32:17'),
(11, 'What is your return policy?', 'Returns are accepted within 7 days of delivery for unused items with tags and original packaging. Contact support to start a return.', 9, 1, '2025-10-06 07:32:17'),
(12, 'How do I request an exchange?', 'Contact us with your order number and item details. Exchanges depend on stock availability.', 10, 1, '2025-10-06 07:32:17'),
(13, 'Can I cancel my order?', 'You can cancel before the order is processed or shipped. Visit My Orders or contact support.', 11, 1, '2025-10-06 07:32:17'),
(14, 'How do I change my delivery address after ordering?', 'If the order isn’t shipped yet, contact support immediately so we can update the address.', 12, 1, '2025-10-06 07:32:17'),
(15, 'Do you offer size guides?', 'Yes. Check the Size Guide on the product page. If unsure, message us your measurements for assistance.', 13, 1, '2025-10-06 07:32:17'),
(16, 'What are the fabric and care instructions?', 'Fabric details and care instructions are listed on each product page. Generally, wash gently and avoid high heat.', 14, 1, '2025-10-06 07:32:17'),
(17, 'Do you have custom clothing or personalization?', 'Yes. Visit the Create Your Style page for customization requests and details.', 15, 1, '2025-10-06 07:32:17'),
(18, 'Do you accept bulk or sub-contract (SUB-CON) orders?', 'We handle bulk/SUB-CON orders. Visit the SUB-CON page or contact us with your requirements.', 16, 1, '2025-10-06 07:32:17'),
(19, 'An item I want is out of stock. Will it be restocked?', 'Popular items are restocked when possible. Click “Notify me” if available or check back soon.', 17, 1, '2025-10-06 07:32:17'),
(20, 'Do you offer discounts or promotions?', 'Yes. Follow our announcements and check the homepage for ongoing promos and voucher codes.', 18, 1, '2025-10-06 07:32:17'),
(21, 'I entered a promo code but it didn’t work. Why?', 'Codes may have conditions (minimum spend, specific items, expiry). Check the terms and try again.', 19, 1, '2025-10-06 07:32:17'),
(22, 'How can I contact customer support?', 'Use the Help & Support page, the chat/inquiry page, or email us. We respond during support hours.', 20, 1, '2025-10-06 07:32:17');

-- --------------------------------------------------------

--
-- Table structure for table `cms_pages`
--

CREATE TABLE `cms_pages` (
  `id` int(11) NOT NULL,
  `page_name` varchar(50) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `content` text DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_pages`
--

INSERT INTO `cms_pages` (`id`, `page_name`, `section_name`, `content`, `last_updated`) VALUES
(1, 'home', 'hero_title', 'Welcome to MTC Clothing', '2025-08-15 04:06:34'),
(2, 'home', 'hero_subtitle', 'Discover our latest collection of stylish clothing', '2025-08-15 04:06:34'),
(3, 'about', 'about_content', 'We are a leading clothing brand dedicated to providing high-quality, fashionable apparel for everyone.', '2025-08-15 04:50:17'),
(4, 'contact', 'business_hours', 'Monday - Friday: 9:00 AM - 8:00 PM<br>Saturday - Sunday: 10:00 AM - 6:00 PM', '2025-08-15 04:06:34'),
(5, 'contact', 'address', '123 Fashion Street, Metro Manila, Philippines', '2025-08-15 04:06:34'),
(6, 'contact', 'email', 'info@mtcclothing.com', '2025-08-15 04:06:34'),
(7, 'contact', 'phone', '+63 912 345 6789', '2025-08-15 04:06:34'),
(10, 'home', 'featured_title', 'Featured Products', '2025-08-15 04:35:09'),
(11, 'home', 'featured_subtitle', 'Check out our best selling items', '2025-08-15 04:35:09'),
(13, 'about', 'mission', '<p>Our mission is to provide stylish and affordable clothing for all HAHA</p>', '2025-08-18 04:30:13'),
(14, 'about', 'vision', 'To be the most trusted clothing brand in the country', '2025-08-15 04:50:17'),
(19, 'contact', 'map_embed', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.7155788773!2d121.0144!3d14.5542!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTTCsDMzJzE1LjEiTiAxMjHCsDAwJzUxLjgiRQ!5e0!3m2!1sen!2sph!4v1234567890123!5m2!1sen!2sph\" width=\"100%\" height=\"300\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\"></iframe>', '2025-08-15 04:35:09'),
(20, 'products', 'page_title', 'Our Products', '2025-08-15 04:35:09'),
(21, 'products', 'page_subtitle', 'Browse our wide range of products', '2025-08-15 04:35:09'),
(22, 'men', 'page_title', 'Men\'s Collection', '2025-08-15 04:35:09'),
(23, 'men', 'page_subtitle', 'Stylish and comfortable clothing for men', '2025-08-15 04:35:09'),
(24, 'women', 'page_title', 'Women\'s Collection', '2025-08-15 04:35:09'),
(25, 'women', 'page_subtitle', 'Fashionable clothing for women', '2025-08-15 04:35:09'),
(26, 'subcon', 'page_title', 'Become a Subcontractor', '2025-08-15 04:35:09'),
(27, 'subcon', 'page_subtitle', 'Join our network of clothing manufacturers', '2025-08-15 04:35:09'),
(28, 'subcon', 'form_content', '<p>Fill out the form below to apply as a subcontractor.</p>', '2025-08-15 04:35:09');

-- --------------------------------------------------------

--
-- Table structure for table `cms_settings`
--

CREATE TABLE `cms_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_settings`
--

INSERT INTO `cms_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'gcash_page_title', 'GCash Payment', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(2, 'gcash_qr_heading', 'Scan QR Code', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(3, 'gcash_qr_description', 'Use your GCash app to scan this QR code', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(4, 'gcash_instructions_title', 'Payment Instructions', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(5, 'gcash_instruction_step1', 'Open your GCash app and scan the QR code above', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(6, 'gcash_instruction_step2', 'Complete the payment of the total amount shown', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(7, 'gcash_instruction_step3', 'Copy the Reference Number from your GCash receipt', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(8, 'gcash_instruction_step4', 'Enter the reference number below to complete your order', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(9, 'gcash_reference_label', 'GCash Reference Number', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(10, 'gcash_reference_placeholder', 'Enter your 13-digit reference number', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(11, 'gcash_reference_help', 'The reference number can be found in your GCash transaction receipt', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(12, 'gcash_button_text', 'Confirm Payment', '2025-10-06 06:25:33', '2025-10-06 06:25:33'),
(13, 'gcash_amount_label', 'Total Amount to Pay', '2025-10-06 06:25:33', '2025-10-06 06:25:33');

-- --------------------------------------------------------

--
-- Table structure for table `colors`
--

CREATE TABLE `colors` (
  `id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `color_code` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `region_code` varchar(16) DEFAULT '',
  `region_name` varchar(191) DEFAULT '',
  `province_code` varchar(16) DEFAULT '',
  `province_name` varchar(191) DEFAULT '',
  `city_code` varchar(16) DEFAULT '',
  `city_name` varchar(191) DEFAULT '',
  `barangay_code` varchar(16) DEFAULT '',
  `barangay_name` varchar(191) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_addresses`
--

INSERT INTO `customer_addresses` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `postal_code`, `is_default`, `created_at`, `updated_at`, `region_code`, `region_name`, `province_code`, `province_name`, `city_code`, `city_name`, `barangay_code`, `barangay_name`) VALUES
(1, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-07 15:37:21', '2025-09-11 15:23:20', '', '', '', '', '', '', '', ''),
(2, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 0, '2025-09-08 00:32:38', '2025-09-08 01:04:54', '', '', '', '', '', '', '', ''),
(3, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 0, '2025-09-08 01:04:54', '2025-09-08 01:08:35', '', '', '', '', '', '', '', ''),
(4, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 1, '2025-09-08 01:08:35', '2025-09-08 01:08:35', '', '', '', '', '', '', '', ''),
(5, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-11 15:23:20', '2025-09-11 15:24:03', '', '', '', '', '', '', '', ''),
(6, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-11 15:24:03', '2025-09-11 15:30:01', '', '', '', '', '', '', '', ''),
(7, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-11 15:30:01', '2025-09-11 15:34:42', '', '', '', '', '', '', '', ''),
(8, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-11 15:34:42', '2025-09-11 15:41:35', '', '', '', '', '', '', '', ''),
(9, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-11 15:41:35', '2025-09-11 16:35:17', '', '', '', '', '', '', '', ''),
(10, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-11 16:35:17', '2025-09-12 00:10:01', '', '', '', '', '', '', '', ''),
(11, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-12 00:10:01', '2025-09-12 01:14:26', '', '', '', '', '', '', '', ''),
(12, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-12 01:14:26', '2025-09-12 01:31:30', '', '', '', '', '', '', '', ''),
(13, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-12 01:31:30', '2025-09-12 01:53:23', '', '', '', '', '', '', '', ''),
(14, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 0, '2025-09-12 01:53:23', '2025-09-19 00:59:38', '', '', '', '', '', '', '', ''),
(15, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'dasmarinas', 'dasmarinas', '4114', 0, '2025-09-18 11:03:09', '2025-09-18 11:32:12', '', '', '', '', '', '', '', ''),
(16, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'anabul 1, Anabu I-C, City of Imus, Cavite, CALABARZON', 'City of Imus', '4114', 0, '2025-09-18 11:32:12', '2025-09-18 11:34:33', '', '', '', '', '', '', '', ''),
(17, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'bgr.14, Bgy. 14 - Ilawod Pob., City of Legazpi, Albay, Bicol Region', 'City of Legazpi', '4114', 0, '2025-09-18 11:34:33', '2025-09-18 11:36:08', '', '', '', '', '', '', '', ''),
(18, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'hijo, Hijo, Maco, Davao De Oro, Davao Region', 'Maco', '4114', 1, '2025-09-18 11:36:08', '2025-09-18 11:36:08', '', '', '', '', '', '', '', ''),
(19, 5, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'blk 4 lot 32 westwood highlands, Luzviminda II, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 0, '2025-09-18 23:07:55', '2025-09-18 23:23:06', '', '', '', '', '', '', '', ''),
(20, 5, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'blk 4 lot 32 westwood highlands, Luzviminda II, City of Dasmariñas, Cavite, CALABARZON, Emmanuel Bergado II, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 1, '2025-09-18 23:23:06', '2025-09-18 23:23:06', '', '', '', '', '', '', '', ''),
(21, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 0, '2025-09-19 00:59:38', '2025-10-01 01:24:17', '', '', '', '', '', '', '', ''),
(22, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 0, '2025-10-01 01:24:17', '2025-10-01 01:26:49', '', '', '', '', '', 'City of Dasmariñas', '', ''),
(23, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 0, '2025-10-01 01:26:49', '2025-10-06 03:14:02', '', '', '', '', '', 'City of Dasmariñas', '', ''),
(24, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 0, '2025-10-06 03:14:02', '2025-10-06 03:26:10', '', '', '', '', '', '', '', ''),
(25, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON, Linongan, Akbar, Basilan, BARMM', 'Akbar', '4114', 0, '2025-10-06 03:26:10', '2025-10-06 06:16:37', '', '', '', '', '', 'Akbar', '', ''),
(26, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Concordia, Alitagtag, Batangas, CALABARZON', 'Alitagtag', '4114', 0, '2025-10-06 06:16:37', '2025-10-06 06:18:21', '', '', '', '', '', 'Alitagtag', '', ''),
(27, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Concordia, Alitagtag, Batangas, CALABARZON, Annafatan, Amulung, Cagayan, Cagayan Valley', 'Amulung', '4114', 0, '2025-10-06 06:18:21', '2025-10-06 06:21:05', '', '', '', '', '', 'Amulung', '', ''),
(28, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Concordia, Alitagtag, Batangas, CALABARZON, Annafatan, Amulung, Cagayan, Cagayan Valley, Lower Baguer, Pigkawayan, Cotabato, SOCCSKSARGEN', 'Pigkawayan', '4114', 1, '2025-10-06 06:21:05', '2025-10-06 06:21:05', '', '', '', '', '', 'Pigkawayan', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `customer_inquiries`
--

CREATE TABLE `customer_inquiries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('processing','accepted','delivered') DEFAULT 'processing',
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_inquiries`
--

INSERT INTO `customer_inquiries` (`id`, `user_id`, `user_name`, `user_email`, `category`, `message`, `status`, `admin_response`, `created_at`, `responded_at`) VALUES
(1, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'Orders', 'hello', '', 'hello\\', '2025-08-07 20:16:05', '2025-08-11 06:15:43'),
(2, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'Orders', 'hello', '', NULL, '2025-08-07 20:17:00', NULL),
(3, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'Orders', 'hello', '', 'Hi', '2025-08-08 05:44:27', '2025-08-08 22:10:35'),
(4, NULL, 'Aliyah', 'aliyahpizana028@gmail.com', 'Orders', 'HEHE', '', 'hello\\', '2025-08-15 01:28:35', '2025-08-15 01:29:03'),
(5, NULL, 'Aliyah', 'aliyahpizana028@gmail.com', 'Orders', 'HEHE', '', NULL, '2025-08-15 01:29:09', NULL),
(6, NULL, 'Aliyah', 'aliyahpizana028@gmail.com', 'Orders', 'HEHE', '', NULL, '2025-08-15 01:29:21', NULL),
(7, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'Order', 'heelooo', '', 'hello\\', '2025-08-15 02:21:54', '2025-08-15 02:23:36'),
(8, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'Order', 'hello', 'processing', NULL, '2025-08-15 06:15:29', NULL),
(9, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-08-15 06:58:58', NULL),
(10, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-08-15 07:26:47', NULL),
(11, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-08-15 23:43:23', NULL),
(12, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-08-15 23:50:40', NULL),
(13, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-09-08 03:59:02', NULL),
(14, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-09-08 03:59:02', NULL),
(15, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-09-08 03:59:15', NULL),
(16, 5, 'siji', 'dititubaling@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'accepted', NULL, '2025-09-18 23:19:27', '2025-09-18 23:57:55'),
(17, 5, 'siji', 'dititubaling@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-09-18 23:57:38', NULL),
(18, 5, 'siji', 'dititubaling@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-09-18 23:57:48', NULL),
(19, 5, 'siji', 'dititubaling@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'accepted', NULL, '2025-09-19 00:03:59', '2025-09-19 00:04:19'),
(20, 5, 'siji', 'dititubaling@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'accepted', NULL, '2025-09-19 00:04:32', '2025-09-19 00:04:46'),
(21, 5, 'siji', 'dititubaling@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'accepted', NULL, '2025-09-19 00:04:55', '2025-09-19 00:11:32'),
(22, 5, 'siji', 'dititubaling@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-09-19 00:12:34', NULL),
(23, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-01 06:03:47', NULL),
(24, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-01 07:04:33', NULL),
(25, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 05:51:39', NULL),
(26, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 05:52:12', NULL),
(27, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 06:18:55', NULL),
(28, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 06:19:11', NULL),
(29, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 06:26:15', NULL),
(30, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 06:32:07', NULL),
(31, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 06:32:09', NULL),
(32, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 06:32:12', NULL),
(33, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 06:32:16', NULL),
(34, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 06:32:18', NULL),
(35, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 06:32:25', NULL),
(36, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 06:32:27', NULL),
(37, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 07:18:13', NULL),
(38, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 07:18:15', NULL),
(39, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 07:54:36', NULL),
(40, 1, 'aliyah', 'aliyahpizana028@gmail.com', 'General Inquiry', 'Hello, I have a question.', 'processing', NULL, '2025-10-06 07:54:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customization_requests`
--

CREATE TABLE `customization_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_type` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','in_review','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gcash_qr`
--

CREATE TABLE `gcash_qr` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gcash_qr`
--

INSERT INTO `gcash_qr` (`id`, `image_path`, `uploaded_at`) VALUES
(1, 'gcash_qr_1759731643.png', '2025-10-06 06:20:43');

-- --------------------------------------------------------

--
-- Table structure for table `inquiry_messages`
--

CREATE TABLE `inquiry_messages` (
  `id` int(11) NOT NULL,
  `inquiry_id` int(11) NOT NULL,
  `sender_type` enum('customer','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inquiry_messages`
--

INSERT INTO `inquiry_messages` (`id`, `inquiry_id`, `sender_type`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'customer', 'hello', 1, '2025-08-07 20:16:05'),
(2, 2, 'customer', 'hello', 1, '2025-08-07 20:17:00'),
(3, 3, 'customer', 'hello', 1, '2025-08-08 05:44:27'),
(4, 4, 'customer', 'HEHE', 1, '2025-08-15 01:28:35'),
(5, 5, 'customer', 'HEHE', 1, '2025-08-15 01:29:09'),
(6, 6, 'customer', 'HEHE', 1, '2025-08-15 01:29:21'),
(7, 3, 'customer', 'hello po', 1, '2025-08-15 02:21:27'),
(8, 7, 'customer', 'heelooo', 1, '2025-08-15 02:21:54'),
(9, 1, 'customer', 'helloo', 1, '2025-08-15 02:25:30'),
(10, 1, 'customer', 'fwww', 1, '2025-08-15 02:25:49'),
(11, 7, 'customer', 'hello', 1, '2025-08-15 02:26:27'),
(12, 7, 'customer', 'hii', 1, '2025-08-15 02:27:12'),
(13, 7, 'customer', 'hi', 1, '2025-08-15 02:32:16'),
(14, 8, 'customer', 'hello', 1, '2025-08-15 06:15:29'),
(15, 9, 'customer', 'Hello, I have a question.', 1, '2025-08-15 06:58:58'),
(16, 10, 'customer', 'Hello, I have a question.', 1, '2025-08-15 07:26:47'),
(17, 10, 'customer', 'hi', 1, '2025-08-15 07:26:59'),
(18, 10, 'customer', 'hi', 1, '2025-08-15 07:41:38'),
(19, 10, 'customer', 'HI', 1, '2025-08-15 07:41:45'),
(20, 11, 'customer', 'Hello, I have a question.', 1, '2025-08-15 23:43:23'),
(21, 12, 'customer', 'Hello, I have a question.', 1, '2025-08-15 23:50:40'),
(22, 13, 'customer', 'Hello, I have a question.', 1, '2025-09-08 03:59:02'),
(23, 14, 'customer', 'Hello, I have a question.', 1, '2025-09-08 03:59:02'),
(24, 15, 'customer', 'Hello, I have a question.', 1, '2025-09-08 03:59:15'),
(25, 16, 'customer', 'Hello, I have a question.', 1, '2025-09-18 23:19:27'),
(26, 16, 'customer', 'hdloo', 1, '2025-09-18 23:21:54'),
(27, 17, 'customer', 'Hello, I have a question.', 0, '2025-09-18 23:57:38'),
(28, 18, 'customer', 'Hello, I have a question.', 1, '2025-09-18 23:57:48'),
(29, 16, 'admin', 'hello', 0, '2025-09-18 23:57:55'),
(30, 19, 'customer', 'Hello, I have a question.', 1, '2025-09-19 00:03:59'),
(31, 19, 'admin', 'hello', 1, '2025-09-19 00:04:19'),
(32, 20, 'customer', 'Hello, I have a question.', 1, '2025-09-19 00:04:32'),
(33, 20, 'admin', 'nnnnnn', 1, '2025-09-19 00:04:46'),
(34, 21, 'customer', 'Hello, I have a question.', 1, '2025-09-19 00:04:55'),
(35, 21, 'admin', 'hello', 1, '2025-09-19 00:08:15'),
(36, 21, 'admin', 'hello', 0, '2025-09-19 00:11:32'),
(37, 22, 'customer', 'Hello, I have a question.', 1, '2025-09-19 00:12:34'),
(38, 23, 'customer', 'Hello, I have a question.', 0, '2025-10-01 06:03:47'),
(39, 24, 'customer', 'Hello, I have a question.', 0, '2025-10-01 07:04:33'),
(40, 25, 'customer', 'Hello, I have a question.', 0, '2025-10-06 05:51:39'),
(41, 26, 'customer', 'Hello, I have a question.', 0, '2025-10-06 05:52:12'),
(42, 27, 'customer', 'Hello, I have a question.', 0, '2025-10-06 06:18:55'),
(43, 28, 'customer', 'Hello, I have a question.', 0, '2025-10-06 06:19:11'),
(44, 29, 'customer', 'Hello, I have a question.', 0, '2025-10-06 06:26:15'),
(45, 30, 'customer', 'Hello, I have a question.', 0, '2025-10-06 06:32:07'),
(46, 31, 'customer', 'Hello, I have a question.', 0, '2025-10-06 06:32:09'),
(47, 32, 'customer', 'Hello, I have a question.', 0, '2025-10-06 06:32:12'),
(48, 33, 'customer', 'Hello, I have a question.', 0, '2025-10-06 06:32:16'),
(49, 34, 'customer', 'Hello, I have a question.', 0, '2025-10-06 06:32:18'),
(50, 35, 'customer', 'Hello, I have a question.', 0, '2025-10-06 06:32:25'),
(51, 36, 'customer', 'Hello, I have a question.', 0, '2025-10-06 06:32:27'),
(52, 36, 'customer', 'What is the status of my order?', 0, '2025-10-06 06:40:11'),
(53, 37, 'customer', 'Hello, I have a question.', 0, '2025-10-06 07:18:13'),
(54, 38, 'customer', 'Hello, I have a question.', 0, '2025-10-06 07:18:15'),
(55, 39, 'customer', 'Hello, I have a question.', 0, '2025-10-06 07:54:36'),
(56, 40, 'customer', 'Hello, I have a question.', 0, '2025-10-06 07:54:44');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gcash_receipt` varchar(255) DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `postal_code`, `payment_method`, `subtotal`, `shipping`, `tax`, `total_amount`, `status`, `created_at`, `updated_at`, `gcash_receipt`, `cancel_reason`, `cancelled_at`) VALUES
(1, 1, 'aliyah', 'pizana', 'aliyahpizana028@gmail.com', '09457448452', 'blk 6 lot 8 windsor homes', 'dasma', '4114', 'cod', 8207.00, 50.00, 984.84, 9241.84, 'completed', '2025-09-08 05:09:28', '2025-09-07 14:54:42', NULL, NULL, NULL),
(2, 1, 'aliyah', 'pizana', 'aliyahpizana028@gmail.com', '09457448452', 'blk 6 lot 8 windsor homes', 'dasma', '4114', 'cod', 350.00, 50.00, 42.00, 442.00, 'shipped', '2025-09-08 05:09:57', '2025-09-07 14:54:34', NULL, NULL, NULL),
(3, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 130.00, 50.00, 15.60, 195.60, 'shipped', '2025-09-07 14:30:41', '2025-09-07 15:10:28', NULL, NULL, NULL),
(4, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 123.00, 50.00, 14.76, 187.76, 'completed', '2025-09-07 14:54:26', '2025-09-07 15:05:10', NULL, NULL, NULL),
(5, 1, 'aliyah', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 130.00, 50.00, 15.60, 195.60, 'cancelled', '2025-09-07 15:10:13', '2025-09-11 15:19:56', NULL, NULL, NULL),
(6, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 350.00, 50.00, 42.00, 442.00, 'completed', '2025-09-07 15:28:37', '2025-09-11 15:01:19', NULL, NULL, NULL),
(7, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 350.00, 50.00, 42.00, 442.00, 'cancelled', '2025-09-07 15:37:21', '2025-09-11 15:22:32', NULL, NULL, NULL),
(8, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 'cod', 130.00, 50.00, 15.60, 195.60, 'completed', '2025-09-08 00:32:38', '2025-09-08 00:33:03', NULL, NULL, NULL),
(9, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 'cod', 123.00, 50.00, 14.76, 187.76, 'shipped', '2025-09-08 01:04:54', '2025-09-08 01:10:10', NULL, NULL, NULL),
(10, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 'cod', 449.00, 50.00, 53.88, 552.88, 'completed', '2025-09-08 01:08:35', '2025-09-08 01:09:36', NULL, NULL, NULL),
(11, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 349.00, 50.00, 41.88, 440.88, 'cancelled', '2025-09-11 15:23:20', '2025-09-11 15:23:32', NULL, NULL, NULL),
(12, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 123.00, 50.00, 14.76, 187.76, 'cancelled', '2025-09-11 15:24:03', '2025-09-11 15:26:37', NULL, NULL, NULL),
(13, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 999.00, 50.00, 119.88, 1168.88, 'cancelled', '2025-09-11 15:30:01', '2025-09-11 15:30:08', NULL, NULL, NULL),
(14, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 350.00, 50.00, 42.00, 442.00, 'cancelled', '2025-09-11 15:34:42', '2025-09-11 15:34:49', NULL, NULL, NULL),
(15, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 99.00, 50.00, 11.88, 160.88, 'shipped', '2025-09-11 15:41:35', '2025-09-12 01:58:50', NULL, NULL, NULL),
(16, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 120.00, 50.00, 14.40, 184.40, 'completed', '2025-09-11 16:35:17', '2025-09-11 16:35:53', NULL, NULL, NULL),
(17, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 123.00, 50.00, 14.76, 187.76, 'cancelled', '2025-09-12 00:10:01', '2025-09-12 01:58:03', NULL, NULL, NULL),
(18, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 369.00, 50.00, 44.28, 463.28, 'shipped', '2025-09-12 01:14:26', '2025-09-12 01:18:10', NULL, NULL, NULL),
(19, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 246.00, 50.00, 29.52, 325.52, 'completed', '2025-09-12 01:31:30', '2025-09-12 01:32:28', NULL, NULL, NULL),
(20, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 246.00, 50.00, 29.52, 325.52, 'completed', '2025-09-12 01:53:23', '2025-09-12 01:57:24', NULL, NULL, NULL),
(21, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'dasmarinas', 'dasmarinas', '4114', 'cod', 123.00, 50.00, 14.76, 187.76, 'completed', '2025-09-18 11:03:09', '2025-09-18 11:06:41', NULL, NULL, NULL),
(22, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'anabul 1, Anabu I-C, City of Imus, Cavite, CALABARZON', 'City of Imus', '4114', 'cod', 999.00, 50.00, 119.88, 1168.88, 'completed', '2025-09-18 11:32:12', '2025-09-18 11:32:24', NULL, NULL, NULL),
(23, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'bgr.14, Bgy. 14 - Ilawod Pob., City of Legazpi, Albay, Bicol Region', 'City of Legazpi', '4114', 'cod', 999.00, 50.00, 119.88, 1168.88, 'completed', '2025-09-18 11:34:33', '2025-09-18 11:34:46', NULL, NULL, NULL),
(24, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'hijo, Hijo, Maco, Davao De Oro, Davao Region', 'Maco', '4114', 'cod', 11988.00, 50.00, 1438.56, 13476.56, 'completed', '2025-09-18 11:36:08', '2025-09-18 11:36:28', NULL, NULL, NULL),
(25, 5, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'blk 4 lot 32 westwood highlands, Luzviminda II, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'cod', 200.00, 50.00, 24.00, 274.00, 'completed', '2025-09-18 23:07:55', '2025-09-18 23:08:07', NULL, NULL, NULL),
(26, 5, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'blk 4 lot 32 westwood highlands, Luzviminda II, City of Dasmariñas, Cavite, CALABARZON, Emmanuel Bergado II, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'cod', 350.00, 50.00, 42.00, 442.00, 'pending', '2025-09-18 23:23:06', '2025-09-18 23:23:06', NULL, NULL, NULL),
(27, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'cod', 3000.00, 50.00, 360.00, 3410.00, 'completed', '2025-09-19 00:59:38', '2025-09-19 01:02:54', NULL, NULL, NULL),
(28, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'cod', 1960.00, 50.00, 235.20, 2245.20, 'pending', '2025-10-01 01:26:49', '2025-10-01 01:26:49', NULL, NULL, NULL),
(29, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'gcash', 999.00, 50.00, 119.88, 1168.88, 'pending', '2025-10-06 03:14:02', '2025-10-06 03:14:02', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_feedback`
--

CREATE TABLE `order_feedback` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `feedback_text` text NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `size` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `size`, `color`, `created_at`) VALUES
(1, 1, 2, 'Hoodie 2', 130.00, 47, NULL, NULL, '2025-09-08 05:09:28'),
(2, 1, 14, 'long sleeve', 999.00, 2, NULL, NULL, '2025-09-08 05:09:28'),
(3, 1, 5, 'short', 99.00, 1, NULL, NULL, '2025-09-08 05:09:28'),
(4, 2, 3, 'Women Dress', 350.00, 1, NULL, NULL, '2025-09-08 05:09:57'),
(5, 3, 2, 'Hoodie 2', 130.00, 1, NULL, NULL, '2025-09-07 14:30:41'),
(6, 4, 15, 'long sleeve', 123.00, 1, NULL, NULL, '2025-09-07 14:54:26'),
(7, 5, 2, 'Hoodie 2', 130.00, 1, NULL, NULL, '2025-09-07 15:10:13'),
(8, 6, 3, 'Women Dress', 350.00, 1, NULL, NULL, '2025-09-07 15:28:37'),
(9, 7, 3, 'Women Dress', 350.00, 1, NULL, NULL, '2025-09-07 15:37:21'),
(10, 8, 2, 'Hoodie 2', 130.00, 1, NULL, NULL, '2025-09-08 00:32:38'),
(11, 9, 16, 'dress1', 123.00, 1, NULL, NULL, '2025-09-08 01:04:54'),
(12, 10, 5, 'short', 99.00, 1, NULL, NULL, '2025-09-08 01:08:35'),
(13, 10, 3, 'Women Dress', 350.00, 1, NULL, NULL, '2025-09-08 01:08:35'),
(14, 11, 2, 'Hoodie 2', 130.00, 1, NULL, NULL, '2025-09-11 15:23:20'),
(15, 11, 5, 'short', 99.00, 1, NULL, NULL, '2025-09-11 15:23:20'),
(16, 11, 6, 'short', 120.00, 1, NULL, NULL, '2025-09-11 15:23:20'),
(17, 12, 15, 'long sleeve', 123.00, 1, NULL, NULL, '2025-09-11 15:24:03'),
(18, 13, 14, 'long sleeve', 999.00, 1, NULL, NULL, '2025-09-11 15:30:01'),
(19, 14, 3, 'Women Dress', 350.00, 1, NULL, NULL, '2025-09-11 15:34:42'),
(20, 15, 5, 'short', 99.00, 1, NULL, NULL, '2025-09-11 15:41:35'),
(21, 16, 6, 'short', 120.00, 1, NULL, NULL, '2025-09-11 16:35:17'),
(22, 17, 16, 'dress1', 123.00, 1, NULL, NULL, '2025-09-12 00:10:01'),
(23, 18, 16, 'dress1', 123.00, 3, NULL, NULL, '2025-09-12 01:14:26'),
(24, 19, 16, 'dress1', 123.00, 2, 'XL', 'Beige', '2025-09-12 01:31:30'),
(25, 20, 16, 'dress1', 123.00, 2, 'L', 'Pink', '2025-09-12 01:53:23'),
(26, 21, 15, 'long sleeve', 123.00, 1, NULL, NULL, '2025-09-18 11:03:09'),
(27, 22, 14, 'long sleeve', 999.00, 1, NULL, NULL, '2025-09-18 11:32:12'),
(28, 23, 14, 'long sleeve', 999.00, 1, NULL, NULL, '2025-09-18 11:34:33'),
(29, 24, 14, 'long sleeve', 999.00, 12, NULL, NULL, '2025-09-18 11:36:08'),
(30, 25, 18, 'crop top', 200.00, 1, 'S', 'Blue', '2025-09-18 23:07:55'),
(31, 26, 3, 'Women Dress', 350.00, 1, NULL, NULL, '2025-09-18 23:23:06'),
(32, 27, 18, 'crop top', 200.00, 15, 'L', 'Black', '2025-09-19 00:59:38'),
(33, 28, 3, 'Women Dress', 350.00, 3, NULL, NULL, '2025-10-01 01:26:49'),
(34, 28, 2, 'Hoodie 2', 130.00, 7, NULL, NULL, '2025-10-01 01:26:49'),
(35, 29, 14, 'long sleeve', 999.00, 1, NULL, NULL, '2025-10-06 03:14:02');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `max_per_order` int(11) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'unisex',
  `subcategory` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `discount_enabled` tinyint(1) DEFAULT 0,
  `discount_type` enum('percent','fixed') DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `max_per_order`, `image`, `category`, `subcategory`, `description`, `material`, `discount_enabled`, `discount_type`, `discount_value`) VALUES
(2, 'Hoodie 2', 130.00, NULL, 'hoodie1.png', 'women', NULL, NULL, NULL, 0, NULL, NULL),
(3, 'Women Dress', 350.00, NULL, 'dress.png', 'women', NULL, NULL, NULL, 0, NULL, NULL),
(5, 'short', 99.00, NULL, '6894f82717558_1754593319.png', 'women', NULL, NULL, NULL, 0, NULL, NULL),
(6, 'short', 120.00, NULL, '6894fab1a490c_1754593969.png', 'women', NULL, NULL, NULL, 0, NULL, NULL),
(14, 'long sleeve', 999.00, NULL, '689f94e4c5dd1_1755288804.png', 'men', NULL, NULL, NULL, 0, NULL, NULL),
(15, 'long sleeve', 123.00, NULL, '68be53a79f86c_1757303719.png', 'men', NULL, NULL, NULL, 0, NULL, NULL),
(16, 'dress1', 123.00, NULL, '68be2af8c01ca_1757293304.png', 'Women', '', 'Slight Used ', '100% Cotton', 0, NULL, NULL),
(17, 'Cj\'s Used Pants', 499.00, 10, '68c3631164295.png', 'Men', 'Pants', 'Slightly Used pants', '100% Cotton', 0, NULL, NULL),
(18, 'crop top', 200.00, 15, '68cbf570c4a74.png', 'Women', 'Dresses', '...', '', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_colors`
--

CREATE TABLE `product_colors` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `color_code` varchar(7) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_colors`
--

INSERT INTO `product_colors` (`id`, `product_id`, `color`, `color_code`, `quantity`) VALUES
(4, 16, 'Yellow', NULL, 0),
(5, 16, 'Pink', NULL, 0),
(6, 16, 'Beige', NULL, 0),
(11, 17, 'Black', NULL, 0),
(12, 17, 'Blue', NULL, 0),
(13, 17, 'Green', NULL, 0),
(26, 18, 'Black', NULL, 2),
(27, 18, 'White', NULL, 3),
(28, 18, 'Red', NULL, 0),
(29, 18, 'Blue', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

CREATE TABLE `product_sizes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`id`, `product_id`, `size`) VALUES
(5, 16, 'XS'),
(6, 16, 'S'),
(7, 16, 'M'),
(8, 16, 'L'),
(9, 16, 'XL'),
(10, 16, 'XXL'),
(16, 17, 'XS'),
(17, 17, 'S'),
(18, 17, 'M'),
(19, 17, 'L'),
(33, 18, 'XS'),
(34, 18, 'S'),
(35, 18, 'M');

-- --------------------------------------------------------

--
-- Table structure for table `site_content`
--

CREATE TABLE `site_content` (
  `content_key` varchar(100) NOT NULL,
  `content_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_content`
--

INSERT INTO `site_content` (`content_key`, `content_value`) VALUES
('featured_collections_title', 'Featured Item'),
('hero_subtitle', 'At MTC Clothing, we specialize in high-quality tailoring and fabrics, combining skilled craftsmanship with attention to detail. Our focus is to deliver exceptional products and services that exceed customer expectations.'),
('hero_title', 'MASTERING TAILORING & FABRICS,<br>WHERE QUALITY MEETS CRAFT'),
('mens_collection_subtitle', 'Trendy styles for men'),
('mens_collection_title', 'Men\'s Collection'),
('new_arrivals_subtitle', 'Discover the latest trends'),
('new_arrivals_title', 'New Arrivals'),
('womens_collection_subtitle', 'Elegant designs for women'),
('womens_collection_title', 'Women\'s Collection');

-- --------------------------------------------------------

--
-- Table structure for table `sizes`
--

CREATE TABLE `sizes` (
  `id` int(11) NOT NULL,
  `size` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'aliyah', 'aliyahpizana028@gmail.com', '$2y$10$1zt.vKxy8QTkGiBOm6N/6ut.VXATKIKrm6FMlwIgivlmklukX95Om', '2025-08-07 20:02:51'),
(2, 'Dete', 'detetulabing03@gmail.com', '$2y$10$MpcP3OfCeExSnUzpyJenUeyFFz7gcQhGQWhVI1f1r1ojgfv7KOaiq', '2025-08-08 05:46:53'),
(3, 'saycony', 'christanjhonsaycon02@gmail.com', '$2y$10$2SAjyslTjmwJM/NMq/zjRO/ZBrrICW1zlWf/Pva/VYftGgBxeYMMW', '2025-09-08 00:31:21'),
(4, 'cj', 'cjsaycon@gmail.com', '$2y$10$gCtDXgfXoFmzQtYKDbnPQO68AmnhAXTZCj3Rh43Hb.deibm.pnbR6', '2025-09-18 11:01:36'),
(5, 'siji', 'dititubaling@gmail.com', '$2y$10$pp1aWMvdNEySa8Wj10RmtOAlz8jHt4.5kS3V2iOWnox8f6bWF2Rnm', '2025-09-18 23:05:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_content`
--
ALTER TABLE `about_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_key` (`content_key`);

--
-- Indexes for table `about_map`
--
ALTER TABLE `about_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_key` (`content_key`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_user_product` (`user_id`,`product_id`,`size`,`color`);

--
-- Indexes for table `chatbot_faqs`
--
ALTER TABLE `chatbot_faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_pages`
--
ALTER TABLE `cms_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_section` (`page_name`,`section_name`);

--
-- Indexes for table `cms_settings`
--
ALTER TABLE `cms_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `color` (`color`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `customer_inquiries`
--
ALTER TABLE `customer_inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customization_requests`
--
ALTER TABLE `customization_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `gcash_qr`
--
ALTER TABLE `gcash_qr`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiry_messages`
--
ALTER TABLE `inquiry_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_feedback`
--
ALTER TABLE `order_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_colors`
--
ALTER TABLE `product_colors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `site_content`
--
ALTER TABLE `site_content`
  ADD PRIMARY KEY (`content_key`);

--
-- Indexes for table `sizes`
--
ALTER TABLE `sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `size` (`size`);

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
-- AUTO_INCREMENT for table `about_content`
--
ALTER TABLE `about_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `about_map`
--
ALTER TABLE `about_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `chatbot_faqs`
--
ALTER TABLE `chatbot_faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `cms_pages`
--
ALTER TABLE `cms_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `cms_settings`
--
ALTER TABLE `cms_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `colors`
--
ALTER TABLE `colors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `customer_inquiries`
--
ALTER TABLE `customer_inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `customization_requests`
--
ALTER TABLE `customization_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gcash_qr`
--
ALTER TABLE `gcash_qr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inquiry_messages`
--
ALTER TABLE `inquiry_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `order_feedback`
--
ALTER TABLE `order_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `product_colors`
--
ALTER TABLE `product_colors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `sizes`
--
ALTER TABLE `sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customization_requests`
--
ALTER TABLE `customization_requests`
  ADD CONSTRAINT `customization_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_feedback`
--
ALTER TABLE `order_feedback`
  ADD CONSTRAINT `order_feedback_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_feedback_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_colors`
--
ALTER TABLE `product_colors`
  ADD CONSTRAINT `product_colors_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD CONSTRAINT `product_sizes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


CREATE TABLE IF NOT EXISTS chatbot_conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  sender ENUM('user','bot') NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  session_id VARCHAR(64) DEFAULT NULL,
  is_read TINYINT(1) DEFAULT 1,
  archived TINYINT(1) DEFAULT 0
 );


-- Create wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE IF NOT EXISTS `product_color_images` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `color` varchar(50) NOT NULL,
    `image` varchar(255) NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `color_idx` (`color`),
    CONSTRAINT `product_color_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Migration: Add product_color_size_inventory table for color-size matrix
-- Date: 2025-01-16
-- Description: Creates a new table to store inventory quantities for each color-size combination

-- Create the inventory table
CREATE TABLE IF NOT EXISTS `product_color_size_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `size` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_color_size` (`product_id`, `color`, `size`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_color` (`color`),
  KEY `idx_size` (`size`),
  CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add color_image column to product_colors if it doesn't exist
ALTER TABLE `product_colors` 
ADD COLUMN IF NOT EXISTS `color_image` varchar(255) DEFAULT NULL AFTER `quantity`;
