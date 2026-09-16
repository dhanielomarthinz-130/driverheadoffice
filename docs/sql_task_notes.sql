-- ================================================
-- SQL Tabel: task_notes (Catatan Task Pribadi)
-- ================================================

-- FULL CREATE TABLE (untuk database baru):
CREATE TABLE IF NOT EXISTS `task_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `note_date` DATE NOT NULL,
  `note_time` TIME DEFAULT NULL COMMENT 'Jam alarm pengingat',
  `priority` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `is_done` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_date` (`user_id`, `note_date`),
  INDEX `idx_user_date_time` (`user_id`, `note_date`, `note_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ================================================
-- ALTER TABLE (jika tabel sudah ada, tambah kolom note_time):
-- ================================================
-- Jalankan query ini di hosting server Anda:

ALTER TABLE `task_notes` ADD COLUMN `note_time` TIME DEFAULT NULL COMMENT 'Jam alarm pengingat' AFTER `note_date`;
ALTER TABLE `task_notes` ADD INDEX `idx_user_date_time` (`user_id`, `note_date`, `note_time`);
