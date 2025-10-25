-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2025 at 02:19 PM
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
(57, 1, 24, 1, '23232', 'werwer', '2025-10-12 11:33:03');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_conversations`
--

CREATE TABLE `chatbot_conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sender` enum('user','bot') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(64) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 1,
  `archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_conversations`
--

INSERT INTO `chatbot_conversations` (`id`, `user_id`, `sender`, `message`, `created_at`, `session_id`, `is_read`, `archived`) VALUES
(1, 1, 'bot', 'Hi! I\'m here to help. Choose a question below or type your own.', '2025-10-11 05:38:35', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(2, 1, 'user', 'asdasd', '2025-10-11 05:38:38', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(3, 1, 'user', 'Can you help me', '2025-10-11 05:39:03', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(4, 1, 'user', 'asdasd', '2025-10-11 05:39:17', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(5, 1, 'user', 'wrqwwer', '2025-10-11 05:39:23', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(6, 1, 'user', 'rtyrty', '2025-10-11 05:39:25', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(7, 1, 'user', 'hjmhjm', '2025-10-11 05:39:27', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(8, 1, 'user', 'Do you offer size guides?', '2025-10-11 05:39:37', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(9, 1, 'bot', 'Yes. Check the Size Guide on the product page. If unsure, message us your measurements for assistance.', '2025-10-11 05:39:37', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(10, 1, 'user', 'Do you offer discounts or promotions?', '2025-10-11 05:39:46', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(11, 1, 'bot', 'Yes. Follow our announcements and check the homepage for ongoing promos and voucher codes.', '2025-10-11 05:39:46', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(12, 1, 'user', 'How can I contact customer support?', '2025-10-11 05:39:54', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(13, 1, 'bot', 'Use the Help & Support page, the chat/inquiry page, or email us. We respond during support hours.', '2025-10-11 05:39:55', 'br909upv3gf4k9lq9hgikkkb7t', 1, 0),
(14, 1, 'user', 'Aray', '2025-10-11 08:28:18', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(15, 1, 'user', 'How can I track my order?', '2025-10-11 08:28:25', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(16, 1, 'bot', 'Go to My Orders after logging in to view status and tracking info once available.', '2025-10-11 08:28:25', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(17, 1, 'user', 'Do you offer size guides?', '2025-10-11 08:28:31', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(18, 1, 'bot', 'Yes. Check the Size Guide on the product page. If unsure, message us your measurements for assistance.', '2025-10-11 08:28:32', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(19, 1, 'user', 'asdasd', '2025-10-11 08:28:51', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(20, 1, 'user', 'werwer', '2025-10-11 08:47:51', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(21, 1, 'bot', 'Wala pang admin na sumasagot sa ngayon. Mangyaring maghintay, at sasagutin ng admin ang iyong concern sa lalong madaling panahon.', '2025-10-11 08:48:01', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(22, 1, 'user', 'Do you deliver to my area?', '2025-10-11 08:48:16', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(23, 1, 'bot', 'We ship nationwide. Enter your address at checkout to confirm delivery and fees.', '2025-10-11 08:48:16', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(24, 1, 'user', 'sdfnjlfgjldfgjl', '2025-10-11 08:48:25', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(25, 1, 'bot', 'Wala pang admin na sumasagot sa ngayon. Mangyaring maghintay, at sasagutin ng admin ang iyong concern sa lalong madaling panahon.', '2025-10-11 08:48:35', 'ep0ho9rn0m48doksp5jikej6nj', 1, 0),
(26, 1, 'bot', 'sfdsdfsdfsf', '2025-10-11 08:49:37', NULL, 1, 0);

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

--
-- Dumping data for table `colors`
--

INSERT INTO `colors` (`id`, `color`, `color_code`) VALUES
(1, 'werwer', NULL),
(2, '111', NULL);

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
(2, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 0, '2025-09-08 00:32:38', '2025-09-08 01:04:54', '', '', '', '', '', '', '', ''),
(3, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 0, '2025-09-08 01:04:54', '2025-09-08 01:08:35', '', '', '', '', '', '', '', ''),
(4, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 1, '2025-09-08 01:08:35', '2025-09-08 01:08:35', '', '', '', '', '', '', '', ''),
(15, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'dasmarinas', 'dasmarinas', '4114', 0, '2025-09-18 11:03:09', '2025-09-18 11:32:12', '', '', '', '', '', '', '', ''),
(16, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'anabul 1, Anabu I-C, City of Imus, Cavite, CALABARZON', 'City of Imus', '4114', 0, '2025-09-18 11:32:12', '2025-09-18 11:34:33', '', '', '', '', '', '', '', ''),
(17, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'bgr.14, Bgy. 14 - Ilawod Pob., City of Legazpi, Albay, Bicol Region', 'City of Legazpi', '4114', 0, '2025-09-18 11:34:33', '2025-09-18 11:36:08', '', '', '', '', '', '', '', ''),
(18, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'hijo, Hijo, Maco, Davao De Oro, Davao Region', 'Maco', '4114', 1, '2025-09-18 11:36:08', '2025-09-18 11:36:08', '', '', '', '', '', '', '', ''),
(19, 5, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'blk 4 lot 32 westwood highlands, Luzviminda II, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 0, '2025-09-18 23:07:55', '2025-09-18 23:23:06', '', '', '', '', '', '', '', ''),
(20, 5, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'blk 4 lot 32 westwood highlands, Luzviminda II, City of Dasmariñas, Cavite, CALABARZON, Emmanuel Bergado II, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 1, '2025-09-18 23:23:06', '2025-09-18 23:23:06', '', '', '', '', '', '', '', ''),
(28, 1, 'aliyahqqq', 'Tulabing', 'aliyahpizana028@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Rosario', '4114', 1, '2025-10-06 06:21:05', '2025-10-12 05:32:14', '040000000', '\r\n                                CALABARZON                            ', '042100000', 'Cavite', '042117000', 'Rosario', '042117016', 'Sapa II'),
(29, 6, 'Sejeh', 'Sakon', 'saycony123@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'City of Dasmariñas', '4114', 1, '2025-10-11 09:29:58', '2025-10-11 09:29:58', '040000000', 'CALABARZON', '042100000', 'Cavite', '042106000', 'City of Dasmariñas', '042106056', 'Paliparan III'),
(30, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'San Miguel', '4114', 0, '2025-10-12 05:21:48', '2025-10-12 05:21:48', '090000000', 'Zamboanga Peninsula', '097300000', 'Zamboanga Del Sur', '097324000', 'San Miguel', '097324018', 'Ocapan'),
(31, 1, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Antipas', '1234', 0, '2025-10-12 05:21:59', '2025-10-12 05:21:59', '120000000', 'SOCCSKSARGEN', '124700000', 'Cotabato', '124715000', 'Antipas', '124715010', 'New Pontevedra');

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
-- Table structure for table `customization_cms`
--

CREATE TABLE `customization_cms` (
  `id` int(11) NOT NULL,
  `content_key` varchar(100) NOT NULL,
  `content_value` text DEFAULT NULL,
  `content_type` varchar(50) DEFAULT 'text',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customization_cms`
--

INSERT INTO `customization_cms` (`id`, `content_key`, `content_value`, `content_type`, `display_order`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'page_title', 'Create your own shirt design!', 'text', 1, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(2, 'page_subtitle', 'Design your perfect custom shirt with our interactive 3D designer', 'text', 2, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(3, 'color_section_title', 'Color', 'text', 10, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(4, 'size_section_title', 'Size', 'text', 20, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(5, 'logo_section_title', 'Customize with your logo', 'text', 30, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(6, 'shirt_type_section_title', 'Pick a shirt type', 'text', 40, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(7, 'submit_button_text', 'Submit Design', 'text', 50, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(8, 'color_1', '#b2c2a8', 'color', 101, '2025-10-11 07:30:42', '2025-10-11 07:46:08', 1),
(9, 'color_2', '#f4c430', 'color', 102, '2025-10-11 07:30:42', '2025-10-11 07:52:49', 1),
(10, 'color_3', '#1a1a1a', 'color', 103, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(11, 'color_4', '#5d4e37', 'color', 104, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(12, 'color_5', '#e57373', 'color', 105, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(13, 'color_6', '#ef5350', 'color', 106, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(14, 'color_7', '#81c784', 'color', 107, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(15, 'color_8', '#1e3a5f', 'color', 108, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(16, 'color_9', '#42a5f5', 'color', 109, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(17, 'color_10', '#90caf9', 'color', 110, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(18, 'color_11', '#6b7c5a', 'color', 111, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(19, 'color_12', '#26c6da', 'color', 112, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(20, 'color_13', '#fff176', 'color', 113, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(21, 'color_14', '#ffeb3b', 'color', 114, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(22, 'shirt_type_1', 'Vneck', 'shirt_type', 201, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(23, 'shirt_type_2', 'Plain T', 'shirt_type', 202, '2025-10-11 07:30:42', '2025-10-11 08:08:27', 0),
(24, 'shirt_type_3', 'Roun Neck', 'shirt_type', 203, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(25, 'shirt_type_4', 'Polo Shirt', 'shirt_type', 204, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(26, 'shirt_type_5', 'Hoodie', 'shirt_type', 205, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(27, 'shirt_type_6', 'Sports', 'shirt_type', 206, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(28, 'shirt_type_7', 'Jersey', 'shirt_type', 207, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(29, 'sizes_adult', 'XS,S,M,L,2XL,3XL,4XL,XL,XXL', 'size_list', 301, '2025-10-11 07:30:42', '2025-10-11 07:56:58', 1),
(30, 'sizes_kids', 'XS,S,M,L,XL', 'size_list', 302, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(31, 'instruction_text', 'Drag to rotate • Click colors to change • Upload your logo', 'text', 60, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1),
(32, 'success_message', 'Your customization request has been submitted successfully!', 'text', 70, '2025-10-11 07:30:42', '2025-10-11 07:30:42', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customization_content`
--

CREATE TABLE `customization_content` (
  `id` int(11) NOT NULL,
  `content_key` varchar(100) NOT NULL,
  `content_value` text NOT NULL,
  `content_type` enum('text','color','size','shirt_type') DEFAULT 'text',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customization_content`
--

INSERT INTO `customization_content` (`id`, `content_key`, `content_value`, `content_type`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'page_title', 'Create your own shirt design!', 'text', 1, 1, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(2, 'page_subtitle', 'Design your perfect custom shirt with our interactive 3D designer', 'text', 1, 2, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(3, 'color_section_title', 'Color', 'text', 1, 3, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(4, 'size_section_title', 'Size', 'text', 1, 4, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(5, 'logo_section_title', 'Customize with your logo', 'text', 1, 5, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(6, 'shirt_type_section_title', 'Pick a shirt type', 'text', 1, 6, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(7, 'submit_button_text', 'Submit Design', 'text', 1, 7, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(8, 'preview_hint_text', '• Drag to rotate', 'text', 1, 8, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(9, 'color_sage_green', '#a8b5a0', 'color', 0, 10, '2025-10-11 07:14:37', '2025-10-11 07:19:01'),
(10, 'color_gold', '#f4c430', 'color', 1, 11, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(11, 'color_black', '#1a1a1a', 'color', 1, 12, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(12, 'color_brown', '#5d4e37', 'color', 1, 13, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(13, 'color_light_red', '#e57373', 'color', 1, 14, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(14, 'color_red', '#ef5350', 'color', 1, 15, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(15, 'color_green', '#81c784', 'color', 1, 16, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(16, 'color_navy', '#1e3a5f', 'color', 1, 17, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(17, 'color_blue', '#42a5f5', 'color', 1, 18, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(18, 'color_light_blue', '#90caf9', 'color', 1, 19, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(19, 'color_olive', '#6b7c5a', 'color', 1, 20, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(20, 'color_cyan', '#26c6da', 'color', 1, 21, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(21, 'color_light_yellow', '#fff176', 'color', 1, 22, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(22, 'color_yellow', '#ffeb3b', 'color', 1, 23, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(23, 'size_xs', 'XS', 'size', 1, 30, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(24, 'size_s', 'S', 'size', 1, 31, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(25, 'size_m', 'M', 'size', 1, 32, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(26, 'size_l', 'L', 'size', 1, 33, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(27, 'size_xl', 'XL', 'size', 1, 34, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(28, 'size_2xl', '2XL', 'size', 1, 35, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(29, 'size_3xl', '3XL', 'size', 1, 36, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(30, 'size_4xl', '4XL', 'size', 1, 37, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(31, 'shirt_type_vneck', 'Vneck', 'shirt_type', 1, 50, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(32, 'shirt_type_plain', 'Plain T', 'shirt_type', 1, 51, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(33, 'shirt_type_round', 'Round Neck', 'shirt_type', 1, 52, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(34, 'shirt_type_polo', 'Polo Shirt', 'shirt_type', 1, 53, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(35, 'shirt_type_hoodie', 'Hoodie', 'shirt_type', 1, 54, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(36, 'shirt_type_sports', 'Sports', 'shirt_type', 1, 55, '2025-10-11 07:14:37', '2025-10-11 07:14:37'),
(37, 'shirt_type_jersey', 'Jersey', 'shirt_type', 1, 56, '2025-10-11 07:14:37', '2025-10-11 07:14:37');

-- --------------------------------------------------------

--
-- Table structure for table `customization_requests`
--

CREATE TABLE `customization_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_type` varchar(100) DEFAULT NULL,
  `garment_style` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `neckline_type` varchar(50) DEFAULT NULL,
  `sleeve_type` varchar(50) DEFAULT NULL,
  `fit_type` varchar(50) DEFAULT NULL,
  `chest_width` decimal(10,2) DEFAULT NULL,
  `waist_width` decimal(10,2) DEFAULT NULL,
  `shoulder_width` decimal(10,2) DEFAULT NULL,
  `sleeve_length` decimal(10,2) DEFAULT NULL,
  `garment_length` decimal(10,2) DEFAULT NULL,
  `hip_width` decimal(10,2) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `reference_image_path` varchar(255) DEFAULT NULL,
  `status` enum('submitted','pending','in_review','approved','rejected','completed') DEFAULT 'submitted',
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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'admin_reply', 'You have a new reply from admin: sfdsdfsdfsf', 1, '2025-10-11 16:49:37'),
(2, 1, 'order_status', 'Your order #000032 status is now: Shipped', 1, '2025-10-12 13:31:08'),
(3, 1, 'order_status', 'Your order #000032 status is now: Completed', 1, '2025-10-12 13:31:10'),
(4, 1, 'order_status', 'Your order #000033 status is now: Shipped', 1, '2025-10-12 13:35:13'),
(5, 1, 'order_status', 'Your order #000033 status is now: Completed', 1, '2025-10-12 13:35:15');

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
  `delivery_mode` varchar(50) DEFAULT 'pickup',
  `subtotal` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gcash_receipt` varchar(255) DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `gcash_reference_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `postal_code`, `payment_method`, `delivery_mode`, `subtotal`, `shipping`, `tax`, `total_amount`, `status`, `created_at`, `updated_at`, `gcash_receipt`, `cancel_reason`, `cancelled_at`, `gcash_reference_number`) VALUES
(1, 1, 'aliyah', 'pizana', 'aliyahpizana028@gmail.com', '09457448452', 'blk 6 lot 8 windsor homes', 'dasma', '4114', 'cod', 'pickup', 8207.00, 50.00, 984.84, 9241.84, 'completed', '2025-09-08 05:09:28', '2025-09-07 14:54:42', NULL, NULL, NULL, NULL),
(2, 1, 'aliyah', 'pizana', 'aliyahpizana028@gmail.com', '09457448452', 'blk 6 lot 8 windsor homes', 'dasma', '4114', 'cod', 'pickup', 350.00, 50.00, 42.00, 442.00, 'shipped', '2025-09-08 05:09:57', '2025-09-07 14:54:34', NULL, NULL, NULL, NULL),
(3, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 130.00, 50.00, 15.60, 195.60, 'shipped', '2025-09-07 14:30:41', '2025-09-07 15:10:28', NULL, NULL, NULL, NULL),
(4, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 123.00, 50.00, 14.76, 187.76, 'completed', '2025-09-07 14:54:26', '2025-09-07 15:05:10', NULL, NULL, NULL, NULL),
(5, 1, 'aliyah', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 130.00, 50.00, 15.60, 195.60, 'cancelled', '2025-09-07 15:10:13', '2025-09-11 15:19:56', NULL, NULL, NULL, NULL),
(6, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 350.00, 50.00, 42.00, 442.00, 'completed', '2025-09-07 15:28:37', '2025-09-11 15:01:19', NULL, NULL, NULL, NULL),
(7, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 350.00, 50.00, 42.00, 442.00, 'cancelled', '2025-09-07 15:37:21', '2025-09-11 15:22:32', NULL, NULL, NULL, NULL),
(8, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 'cod', 'pickup', 130.00, 50.00, 15.60, 195.60, 'completed', '2025-09-08 00:32:38', '2025-09-08 00:33:03', NULL, NULL, NULL, NULL),
(9, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 'cod', 'pickup', 123.00, 50.00, 14.76, 187.76, 'shipped', '2025-09-08 01:04:54', '2025-09-08 01:10:10', NULL, NULL, NULL, NULL),
(10, 3, 'Cj', 'Saycony', 'christanjhonsaycon02@gmail.com', '09123456789', 'block 123 lot 456 phase 789', 'Dasma Caloocan', '1234', 'cod', 'pickup', 449.00, 50.00, 53.88, 552.88, 'completed', '2025-09-08 01:08:35', '2025-09-08 01:09:36', NULL, NULL, NULL, NULL),
(11, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 349.00, 50.00, 41.88, 440.88, 'cancelled', '2025-09-11 15:23:20', '2025-09-11 15:23:32', NULL, NULL, NULL, NULL),
(12, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 123.00, 50.00, 14.76, 187.76, 'cancelled', '2025-09-11 15:24:03', '2025-09-11 15:26:37', NULL, NULL, NULL, NULL),
(13, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 999.00, 50.00, 119.88, 1168.88, 'cancelled', '2025-09-11 15:30:01', '2025-09-11 15:30:08', NULL, NULL, NULL, NULL),
(14, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 350.00, 50.00, 42.00, 442.00, 'cancelled', '2025-09-11 15:34:42', '2025-09-11 15:34:49', NULL, NULL, NULL, NULL),
(15, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 99.00, 50.00, 11.88, 160.88, 'shipped', '2025-09-11 15:41:35', '2025-09-12 01:58:50', NULL, NULL, NULL, NULL),
(16, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 120.00, 50.00, 14.40, 184.40, 'completed', '2025-09-11 16:35:17', '2025-09-11 16:35:53', NULL, NULL, NULL, NULL),
(17, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 123.00, 50.00, 14.76, 187.76, 'cancelled', '2025-09-12 00:10:01', '2025-09-12 01:58:03', NULL, NULL, NULL, NULL),
(18, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 369.00, 50.00, 44.28, 463.28, 'shipped', '2025-09-12 01:14:26', '2025-09-12 01:18:10', NULL, NULL, NULL, NULL),
(19, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 246.00, 50.00, 29.52, 325.52, 'completed', '2025-09-12 01:31:30', '2025-09-12 01:32:28', NULL, NULL, NULL, NULL),
(20, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789', 'Damsa', '4114', 'cod', 'pickup', 246.00, 50.00, 29.52, 325.52, 'completed', '2025-09-12 01:53:23', '2025-09-12 01:57:24', NULL, NULL, NULL, NULL),
(21, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'dasmarinas', 'dasmarinas', '4114', 'cod', 'pickup', 123.00, 50.00, 14.76, 187.76, 'completed', '2025-09-18 11:03:09', '2025-09-18 11:06:41', NULL, NULL, NULL, NULL),
(22, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'anabul 1, Anabu I-C, City of Imus, Cavite, CALABARZON', 'City of Imus', '4114', 'cod', 'pickup', 999.00, 50.00, 119.88, 1168.88, 'completed', '2025-09-18 11:32:12', '2025-09-18 11:32:24', NULL, NULL, NULL, NULL),
(23, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'bgr.14, Bgy. 14 - Ilawod Pob., City of Legazpi, Albay, Bicol Region', 'City of Legazpi', '4114', 'cod', 'pickup', 999.00, 50.00, 119.88, 1168.88, 'completed', '2025-09-18 11:34:33', '2025-09-18 11:34:46', NULL, NULL, NULL, NULL),
(24, 4, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'hijo, Hijo, Maco, Davao De Oro, Davao Region', 'Maco', '4114', 'cod', 'pickup', 11988.00, 50.00, 1438.56, 13476.56, 'completed', '2025-09-18 11:36:08', '2025-09-18 11:36:28', NULL, NULL, NULL, NULL),
(25, 5, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'blk 4 lot 32 westwood highlands, Luzviminda II, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'cod', 'pickup', 200.00, 50.00, 24.00, 274.00, 'completed', '2025-09-18 23:07:55', '2025-09-18 23:08:07', NULL, NULL, NULL, NULL),
(26, 5, 'christan', 'saycon', 'cjsaycon02@gmail.com', '09936575064', 'blk 4 lot 32 westwood highlands, Luzviminda II, City of Dasmariñas, Cavite, CALABARZON, Emmanuel Bergado II, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'cod', 'pickup', 350.00, 50.00, 42.00, 442.00, 'pending', '2025-09-18 23:23:06', '2025-09-18 23:23:06', NULL, NULL, NULL, NULL),
(27, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'cod', 'pickup', 3000.00, 50.00, 360.00, 3410.00, 'completed', '2025-09-19 00:59:38', '2025-09-19 01:02:54', NULL, NULL, NULL, NULL),
(28, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'cod', 'pickup', 1960.00, 50.00, 235.20, 2245.20, 'pending', '2025-10-01 01:26:49', '2025-10-01 01:26:49', NULL, NULL, NULL, NULL),
(29, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON, Burol III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'gcash', 'pickup', 999.00, 50.00, 119.88, 1168.88, 'pending', '2025-10-06 03:14:02', '2025-10-06 03:14:02', NULL, NULL, NULL, NULL),
(30, 1, 'Dete', 'Tulabing', 'tulabingdete03@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Burol III, City of Dasmariñas, Cavite, CALABARZON, Concordia, Alitagtag, Batangas, CALABARZON, Annafatan, Amulung, Cagayan, Cagayan Valley, Lower Baguer, Pigkawayan, Cotabato, SOCCSKSARGEN, Binawangan, Capalonga, Camarines Norte, Bicol Region', 'Capalonga', '4114', 'cod', 'lalamove', 750.00, 0.00, 90.00, 840.00, 'pending', '2025-10-11 08:26:59', '2025-10-11 08:26:59', NULL, NULL, NULL, NULL),
(31, 6, 'Sejeh', 'Sakon', 'saycony123@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Paliparan III, City of Dasmariñas, Cavite, CALABARZON', 'City of Dasmariñas', '4114', 'gcash', 'jnt', -5187.29, 100.00, -622.47, -5709.76, 'pending', '2025-10-11 09:49:30', '2025-10-11 09:49:30', NULL, NULL, NULL, '1234567891234'),
(32, 1, 'aliyahqqq', 'Tulabing', 'aliyahpizana028@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Sapa IV, Rosario, Cavite, CALABARZON', 'Rosario', '4114', 'cod', 'pickup', 777.00, 0.00, 93.24, 870.24, 'completed', '2025-10-12 05:31:00', '2025-10-12 05:31:10', NULL, NULL, NULL, NULL),
(33, 1, 'aliyahqqq', 'Tulabing', 'aliyahpizana028@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Sapa II, Rosario, Cavite, CALABARZON', 'Rosario', '4114', 'cod', 'pickup', 111.00, 0.00, 13.32, 124.32, 'completed', '2025-10-12 05:35:07', '2025-10-12 05:35:15', NULL, NULL, NULL, NULL),
(34, 1, 'aliyahqqq', 'Tulabing', 'aliyahpizana028@gmail.com', '09777059884', 'block 123 lot 456 phase 789, Sapa II, Rosario, Cavite, CALABARZON', 'Rosario', '4114', 'gcash', 'jnt', 111.00, 100.00, 13.32, 224.32, 'pending', '2025-10-12 05:39:04', '2025-10-12 05:39:04', NULL, NULL, NULL, '1234567891234');

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

--
-- Dumping data for table `order_feedback`
--

INSERT INTO `order_feedback` (`id`, `order_id`, `user_id`, `product_id`, `feedback_text`, `rating`, `created_at`) VALUES
(1, 32, 1, NULL, '111', 5, '2025-10-12 13:31:35'),
(2, 33, 1, NULL, 'tite', 5, '2025-10-12 13:35:22');

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
(35, 29, 14, 'long sleeve', 999.00, 1, NULL, NULL, '2025-10-06 03:14:02'),
(36, 30, 3, 'Women Dress', 350.00, 1, NULL, NULL, '2025-10-11 08:26:59'),
(37, 30, 18, 'crop top', 200.00, 1, 'M', 'Black', '2025-10-11 08:26:59'),
(38, 30, 18, 'crop top', 200.00, 1, 'S', 'Black', '2025-10-11 08:26:59'),
(39, 31, 18, 'crop top', 200.00, 1, 'M', 'Black', '2025-10-11 09:49:30'),
(40, 31, 19, 'Dete', -5387.29, 1, NULL, NULL, '2025-10-11 09:49:30'),
(41, 32, 21, 'aliyah', 111.00, 7, '23232', 'werwer', '2025-10-12 05:31:00'),
(42, 33, 21, 'aliyah', 111.00, 1, '23232', 'werwer', '2025-10-12 05:35:07'),
(43, 34, 21, 'aliyah', 111.00, 1, '23232', '111', '2025-10-12 05:39:04');

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
(24, 'aliyah', 23.00, NULL, '68eb63831731a.png', 'Men', 'Shirts', '', '100% Cotton', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_colors`
--

CREATE TABLE `product_colors` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `color_code` varchar(7) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `color_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_colors`
--

INSERT INTO `product_colors` (`id`, `product_id`, `color`, `color_code`, `quantity`, `color_image`) VALUES
(53, 24, '111', NULL, 2, 'color_68eb656b9a482.png'),
(54, 24, 'werwer', NULL, 2, 'color_68eb656b9a674.png');

-- --------------------------------------------------------

--
-- Table structure for table `product_color_images`
--

CREATE TABLE `product_color_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `created_at`) VALUES
(25, 24, '68eb63831731a.png', 0, '2025-10-12 08:14:59');

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
(45, 24, '23232');

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

--
-- Dumping data for table `sizes`
--

INSERT INTO `sizes` (`id`, `size`) VALUES
(1, '23232');

-- --------------------------------------------------------

--
-- Table structure for table `subcontract_requests`
--

CREATE TABLE `subcontract_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `what_for` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `design_file` text DEFAULT NULL,
  `date_needed` date NOT NULL,
  `time_needed` time NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `delivery_method` enum('Pick-up','Lalamove') DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcontract_requests`
--

INSERT INTO `subcontract_requests` (`id`, `user_id`, `what_for`, `quantity`, `design_file`, `date_needed`, `time_needed`, `customer_name`, `address`, `email`, `delivery_method`, `note`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'school', 122, '[\"uploads\\/subcontract_designs\\/68eb2f57998a0_0.jpg\",\"uploads\\/subcontract_designs\\/68eb2f5799b0a_1.jpg\",\"uploads\\/subcontract_designs\\/68eb2f5799cc7_2.png\",\"uploads\\/subcontract_designs\\/68eb2f5799e70_3.jpg\",\"uploads\\/subcontract_designs\\/68eb2f5799fc8_4.jpg\"]', '2025-10-23', '12:33:00', 'Dete Tulabing', 'block 123 lot 456 phase 789', 'tulabingdete03@gmail.com', 'Pick-up', '3dq2', 'pending', '2025-10-12 04:32:23', '2025-10-12 04:32:23'),
(2, 1, 'school', 122, '[\"uploads\\/subcontract_designs\\/68eb30358edc5_0.png\",\"uploads\\/subcontract_designs\\/68eb30358efaa_1.jpg\",\"uploads\\/subcontract_designs\\/68eb30358f11f_2.jpg\"]', '2025-10-24', '12:35:00', 'Dete Tulabing', 'block 123 lot 456 phase 789', 'tulabingdete03@gmail.com', 'Pick-up', 'wrw3qq', 'pending', '2025-10-12 04:36:05', '2025-10-12 04:36:05'),
(3, 1, 'school', 122, NULL, '2025-10-17', '12:43:00', 'Dete Tulabing', 'block 123 lot 456 phase 789', 'tulabingdete03@gmail.com', 'Pick-up', 'd3d3', 'pending', '2025-10-12 04:43:17', '2025-10-12 04:43:17'),
(4, 1, 'school', 122, '[\"uploads\\/subcontract_designs\\/68eb33c925dcb_0.png\",\"uploads\\/subcontract_designs\\/68eb33c925fae_1.png\",\"uploads\\/subcontract_designs\\/68eb33c92614f_2.png\",\"uploads\\/subcontract_designs\\/68eb33c9262ce_3.png\"]', '2025-10-17', '12:51:00', 'Dete Tulabing', 'block 123 lot 456 phase 789', 'tulabingdete03@gmail.com', 'Pick-up', 'dghfj', 'pending', '2025-10-12 04:51:21', '2025-10-12 04:51:21');

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
(1, 'aliyahqqq Tulabing', 'aliyahpizana028@gmail.com', '$2y$10$1zt.vKxy8QTkGiBOm6N/6ut.VXATKIKrm6FMlwIgivlmklukX95Om', '2025-08-07 20:02:51'),
(2, 'Dete', 'detetulabing03@gmail.com', '$2y$10$MpcP3OfCeExSnUzpyJenUeyFFz7gcQhGQWhVI1f1r1ojgfv7KOaiq', '2025-08-08 05:46:53'),
(3, 'saycony', 'christanjhonsaycon02@gmail.com', '$2y$10$2SAjyslTjmwJM/NMq/zjRO/ZBrrICW1zlWf/Pva/VYftGgBxeYMMW', '2025-09-08 00:31:21'),
(4, 'cj', 'cjsaycon@gmail.com', '$2y$10$gCtDXgfXoFmzQtYKDbnPQO68AmnhAXTZCj3Rh43Hb.deibm.pnbR6', '2025-09-18 11:01:36'),
(5, 'siji', 'dititubaling@gmail.com', '$2y$10$pp1aWMvdNEySa8Wj10RmtOAlz8jHt4.5kS3V2iOWnox8f6bWF2Rnm', '2025-09-18 23:05:12'),
(6, 'Sejeh Sakon', 'saycony123@gmail.com', '$2y$10$dB9sxMG3pkwjYlzmTpRO/OggXXA5Fy99XxzVKV9dSQuHsfcrtt.Ra', '2025-10-11 09:29:58');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `customization_cms`
--
ALTER TABLE `customization_cms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_key` (`content_key`);

--
-- Indexes for table `customization_content`
--
ALTER TABLE `customization_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_key` (`content_key`),
  ADD KEY `idx_content_key` (`content_key`),
  ADD KEY `idx_is_active` (`is_active`);

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
-- Indexes for table `product_color_images`
--
ALTER TABLE `product_color_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `color_idx` (`color`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
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
-- Indexes for table `subcontract_requests`
--
ALTER TABLE `subcontract_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `customer_inquiries`
--
ALTER TABLE `customer_inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `customization_cms`
--
ALTER TABLE `customization_cms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=417;

--
-- AUTO_INCREMENT for table `customization_content`
--
ALTER TABLE `customization_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `order_feedback`
--
ALTER TABLE `order_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `product_colors`
--
ALTER TABLE `product_colors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `product_color_images`
--
ALTER TABLE `product_color_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `sizes`
--
ALTER TABLE `sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subcontract_requests`
--
ALTER TABLE `subcontract_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `product_color_images`
--
ALTER TABLE `product_color_images`
  ADD CONSTRAINT `product_color_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD CONSTRAINT `product_sizes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subcontract_requests`
--
ALTER TABLE `subcontract_requests`
  ADD CONSTRAINT `subcontract_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
