CREATE TABLE IF NOT EXISTS `system_license_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `extended_by` VARCHAR(100) NOT NULL,
  `extension_type` VARCHAR(100) NOT NULL,
  `previous_expiry` DATETIME NULL,
  `new_expiry` DATETIME NOT NULL,
  `payment_notes` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
