-- =========================================================
-- SYSTEM SETTINGS TABLE (Masa Aktif & Lisensi Sistem Web)
-- =========================================================

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Set default masa aktif aplikasi ke 3 bulan ke depan (apabila belum ada)
INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
VALUES ('app_active_until', DATE_ADD(NOW(), INTERVAL 3 MONTH))
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
