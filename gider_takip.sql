-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 08 Oca 2026, 14:30:20
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `gider_takip`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(100) NOT NULL,
  `expense_date` date NOT NULL,
  `status` enum('Bekliyor','Onaylandı','Reddedildi') DEFAULT 'Bekliyor',
  `receipt_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Tablo döküm verisi `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `description`, `amount`, `category`, `expense_date`, `status`, `receipt_path`) VALUES
(2, 5, 'malzeme', 150000.00, 'Yemek', '2025-11-06', 'Onaylandı', NULL),
(3, 7, 'yemek', 2000.00, 'Yemek', '2025-11-06', 'Onaylandı', NULL),
(4, 7, '.', 200.00, 'Seyahat', '2025-11-09', 'Onaylandı', NULL),
(5, 7, '..', 20000.00, 'Konaklama', '2025-11-12', 'Onaylandı', NULL),
(6, 7, 'yemek', 500.00, 'Yemek', '2025-11-06', 'Onaylandı', NULL),
(7, 7, 'malzeme', 2000.00, 'Temsil', '2025-11-22', 'Reddedildi', NULL),
(8, 7, 'yemek', 20000.00, 'Seyahat', '2025-11-06', 'Reddedildi', NULL),
(9, 7, ',', 25555.00, 'Konaklama', '2025-11-06', 'Onaylandı', NULL),
(10, 7, 'malzeme', 1200.00, 'Temsil', '2025-12-01', 'Onaylandı', NULL),
(11, 7, 'ydet', 2000.00, 'Seyahat', '2025-12-30', 'Onaylandı', NULL),
(12, 7, 'malzeme', 2000.00, 'Ofis', '2025-12-30', 'Reddedildi', NULL),
(13, 6, 'ekim ayı vergi ödemesi', 125000.00, 'Vergi', '2025-10-15', 'Onaylandı', NULL),
(14, 7, '2025-12 Dönemi Maaş Ödemesi', 32000.00, 'Maaş', '2025-12-30', 'Onaylandı', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `expense_id` int(11) NOT NULL,
  `action_user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Tablo döküm verisi `logs`
--

INSERT INTO `logs` (`id`, `expense_id`, `action_user_id`, `action_type`, `action_time`) VALUES
(1, 9, 6, 'Onaylandı', '2025-11-06 21:13:09'),
(2, 8, 6, 'Reddedildi', '2025-11-06 21:13:12'),
(3, 10, 6, 'Onaylandı', '2025-12-01 08:44:44'),
(4, 11, 6, 'Onaylandı', '2025-12-30 08:04:41'),
(5, 12, 6, 'Reddedildi', '2025-12-30 08:35:12');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `monthly_income`
--

CREATE TABLE `monthly_income` (
  `id` int(11) NOT NULL,
  `month_year` char(7) NOT NULL,
  `income_amount` decimal(10,2) NOT NULL,
  `recorded_by_user_id` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Tablo döküm verisi `monthly_income`
--

INSERT INTO `monthly_income` (`id`, `month_year`, `income_amount`, `recorded_by_user_id`, `recorded_at`) VALUES
(1, '2025-11', 525.00, 6, '2025-11-06 21:18:15'),
(2, '2025-12', 500000.00, 6, '2025-12-01 08:42:58');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patron','personel','muhasebeci') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `created_at`) VALUES
(5, 'salim', '$2y$10$bvsB82UCanJOoIsqi1B30OSPBCJfK/mmpNod9qgXdVzCRbYYta3bS', 'patron', 'salim@gmail.com', '2025-11-06 18:01:16'),
(6, 'muhasebe', '$2y$10$gAkGWs5Fsh09PLXno8FbJOv7WvjBV2VutBt6VUzDRn1bJO5XtJKBq', 'muhasebeci', 'muha@gmail.com', '2025-11-06 20:47:55'),
(7, 'ahmet', '$2y$10$DZu19OnEvJuJcTasZOF4kObANFso0KOl9JJI43MmMRpQqORgJM/sm', 'personel', 'ahmet@gmail.com', '2025-11-06 20:50:53');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_id` (`expense_id`),
  ADD KEY `action_user_id` (`action_user_id`);

--
-- Tablo için indeksler `monthly_income`
--
ALTER TABLE `monthly_income`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `month_year` (`month_year`),
  ADD UNIQUE KEY `unique_month_year` (`month_year`),
  ADD KEY `recorded_by_user_id` (`recorded_by_user_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Tablo için AUTO_INCREMENT değeri `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `monthly_income`
--
ALTER TABLE `monthly_income`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`action_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `monthly_income`
--
ALTER TABLE `monthly_income`
  ADD CONSTRAINT `monthly_income_ibfk_1` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
