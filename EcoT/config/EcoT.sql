-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 10:32 AM
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
-- Database: `ecot`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('order','status','system') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `order_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 1, 'Your order has been placed and is pending confirmation.', 'order', 1, '2025-05-16 07:28:16'),
(2, 3, 2, 'Your order has been placed and is pending confirmation.', 'order', 1, '2025-05-16 07:32:02'),
(3, 3, 3, 'Your order has been placed and is pending confirmation.', 'order', 0, '2025-05-16 07:33:08'),
(4, 4, 4, 'Your order has been placed and is pending confirmation.', 'order', 0, '2025-05-16 07:38:35'),
(9, 2, 5, 'Your order has been placed and is pending confirmation.', 'order', 0, '2025-05-16 07:39:46'),
(10, 2, 6, 'Your order has been placed and is pending confirmation.', 'order', 0, '2025-05-16 07:40:54');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `tracking_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `tracking_number`, `created_at`) VALUES
(1, 2, 630.00, 'cancelled', NULL, '2025-05-16 07:28:16'),
(2, 3, 650.00, 'pending', NULL, '2025-05-16 07:32:02'),
(3, 3, 1040.00, 'cancelled', NULL, '2025-05-16 07:33:08'),
(4, 4, 840.00, 'completed', NULL, '2025-05-16 07:38:35'),
(5, 2, 80.00, 'cancelled', NULL, '2025-05-16 07:39:46'),
(6, 2, 15.00, 'completed', NULL, '2025-05-16 07:40:54'),
(7, 2, 380.00, 'completed', NULL, '2025-05-16 07:42:42'),
(8, 2, 20.00, 'completed', NULL, '2025-05-16 07:50:28'),
(9, 2, 20.00, 'cancelled', NULL, '2025-05-16 07:52:11'),
(10, 2, 230.00, 'cancelled', NULL, '2025-05-16 07:52:26'),
(11, 5, 700.00, 'completed', NULL, '2025-05-16 07:54:21'),
(12, 5, 20.00, 'completed', NULL, '2025-05-16 07:55:52'),
(13, 4, 50.00, 'completed', NULL, '2025-05-16 07:59:39'),
(14, 4, 20.00, 'completed', NULL, '2025-05-16 08:04:52'),
(15, 6, 520.00, 'completed', NULL, '2025-05-16 08:09:31'),
(16, 6, 150.00, 'completed', NULL, '2025-05-16 08:11:08'),
(17, 6, 20.00, 'completed', NULL, '2025-05-16 08:12:22'),
(18, 6, 380.00, 'completed', NULL, '2025-05-16 08:13:51'),
(19, 6, 15.00, 'completed', NULL, '2025-05-16 08:15:36'),
(20, 6, 20.00, 'completed', NULL, '2025-05-16 08:16:52'),
(21, 6, 230.00, 'completed', NULL, '2025-05-16 08:30:55');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `received` tinyint(1) DEFAULT 0,
  `received_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `received`, `received_at`) VALUES
(1, 1, 2, 1, 20.00, 0, NULL),
(2, 1, 4, 1, 230.00, 0, NULL),
(3, 1, 5, 1, 380.00, 0, NULL),
(4, 2, 5, 1, 380.00, 0, NULL),
(5, 2, 4, 1, 230.00, 0, NULL),
(6, 2, 2, 2, 20.00, 0, NULL),
(7, 3, 5, 1, 380.00, 0, NULL),
(8, 3, 1, 4, 50.00, 0, NULL),
(9, 3, 4, 2, 230.00, 0, NULL),
(10, 4, 4, 2, 230.00, 1, '2025-05-16 07:38:51'),
(11, 4, 5, 1, 380.00, 1, '2025-05-16 07:38:50'),
(12, 5, 2, 4, 20.00, 0, NULL),
(13, 6, 3, 1, 15.00, 1, '2025-05-16 07:42:18'),
(14, 7, 5, 1, 380.00, 1, '2025-05-16 07:50:14'),
(15, 8, 2, 1, 20.00, 1, '2025-05-16 07:50:36'),
(16, 9, 2, 1, 20.00, 0, NULL),
(17, 10, 4, 1, 230.00, 0, NULL),
(18, 11, 1, 1, 50.00, 1, '2025-05-16 07:54:29'),
(19, 11, 2, 2, 20.00, 1, '2025-05-16 07:54:30'),
(20, 11, 4, 1, 230.00, 1, '2025-05-16 07:54:31'),
(21, 11, 5, 1, 380.00, 1, '2025-05-16 07:54:32'),
(22, 12, 2, 1, 20.00, 1, '2025-05-16 07:56:38'),
(23, 13, 1, 1, 50.00, 1, '2025-05-16 07:59:43'),
(24, 14, 2, 1, 20.00, 1, '2025-05-16 08:04:56'),
(25, 15, 2, 3, 20.00, 1, '2025-05-16 08:09:35'),
(26, 15, 4, 2, 230.00, 1, '2025-05-16 08:09:35'),
(27, 16, 1, 3, 50.00, 1, '2025-05-16 08:11:12'),
(28, 17, 2, 1, 20.00, 1, '2025-05-16 08:12:26'),
(29, 18, 5, 1, 380.00, 1, '2025-05-16 08:13:54'),
(30, 19, 3, 1, 15.00, 1, '2025-05-16 08:15:38'),
(31, 20, 2, 1, 20.00, 1, '2025-05-16 08:16:54'),
(32, 21, 4, 1, 230.00, 1, '2025-05-16 08:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `image`, `rating`, `created_at`) VALUES
(1, 'Whiteboard marker', 'A writing tool designed for use on whiteboards and other non-porous surfaces.', 50.00, 445, '6826e82ebdf77.png', 0, '2025-05-16 07:24:30'),
(2, 'Ballpen', 'A writing implement that uses a small, rotating metal ball to dispense ink onto paper.', 20.00, 488, '6826e85588f22.jpeg', 0, '2025-05-16 07:25:09'),
(3, 'Bio Eraser', 'Staedtler Mars Green Eraser, that is made with up to 56% bio-based materials, making it a sustainable alternative to traditional erasers.', 15.00, 298, '6826e88823d2e.jpg', 0, '2025-05-16 07:26:00'),
(4, 'Reusable Water Bottle', 'A durable container, often made of stainless steel, glass, or BPA-free plastic, designed to be filled and refilled with water for multiple uses', 230.00, 373, '6826e8b79de82.jpeg', 0, '2025-05-16 07:26:47'),
(5, 'Solar Powered Calculator', 'A handheld calculator that runs on solar energy, typically using a solar cell to convert sunlight into electricity.', 380.00, 45, '6826e8eb4888e.jpg', 0, '2025-05-16 07:27:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `active`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$tt/3WYc90R0.skP3UAdXLO6lpZO7ZpMzuCwWeyZC4i1xtOHl54pm6', 'admin', 1, '2025-05-16 07:22:53'),
(2, 'Mane', 'sabanalmane@gmail.com', '$2y$10$WuPRoT0rk4093r3RAi0bp.Tzlk9ba8j19ARFHPY2Padl0U55bPHXS', 'user', 1, '2025-05-16 07:28:02'),
(3, 'ritchell', 'balanayritchell@gmail.com', '$2y$10$4i0uJZ4Fg2mTPYP4SiUC2e1cBZM.QZnotvQ3pvNFlaQYwg.JMusqS', 'user', 1, '2025-05-16 07:31:37'),
(4, 'jonard', 'brawlstarsupercellid79@gmail.com', '$2y$10$7ipNAmw2ugTNXmekpgkBRejB9UTU7t45S26Gup6IJXB7aDiC9GZkC', 'user', 1, '2025-05-16 07:38:21'),
(5, 'jhases anak ni Moises', 'jhasenambogna119@gmail.com', '$2y$10$/er6NcdzSPvQfFvJfbtB3.wJWewJWFOYjnMC7SvoX8GQOuRe1Rm1.', 'user', 1, '2025-05-16 07:53:40'),
(6, 'Kyle', 'kyletapac4@gmail.com', '$2y$10$/f4tjyZPBI8vFz/WTU5W/uTe83AmgDQN7QhinHhvVqwqQ761LqEfy', 'user', 1, '2025-05-16 08:08:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
