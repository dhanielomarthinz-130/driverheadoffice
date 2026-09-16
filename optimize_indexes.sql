-- =========================================================
-- OPTIMASI DATABASE TMS DRIVER (SAFE INDEXING SQL)
-- Menggunakan IF NOT EXISTS & Pernyataan Terpisah agar aman di phpMyAdmin
-- =========================================================

-- 1. Index untuk tabel deliveries
ALTER TABLE `deliveries` ADD INDEX IF NOT EXISTS `idx_deliveries_driver_status` (`driver_id`, `status`);
ALTER TABLE `deliveries` ADD INDEX IF NOT EXISTS `idx_deliveries_target_date` (`target_date`);
ALTER TABLE `deliveries` ADD INDEX IF NOT EXISTS `idx_deliveries_created_at` (`created_at`);
ALTER TABLE `deliveries` ADD INDEX IF NOT EXISTS `idx_deliveries_surat_jalan` (`surat_jalan`);
ALTER TABLE `deliveries` ADD INDEX IF NOT EXISTS `idx_deliveries_status` (`status`);
ALTER TABLE `deliveries` ADD INDEX IF NOT EXISTS `idx_deliveries_task_type` (`task_type`);

-- 2. Index untuk tabel pickup_requests
ALTER TABLE `pickup_requests` ADD INDEX IF NOT EXISTS `idx_pickup_status` (`status`);
ALTER TABLE `pickup_requests` ADD INDEX IF NOT EXISTS `idx_pickup_requester` (`requester_id`);
ALTER TABLE `pickup_requests` ADD INDEX IF NOT EXISTS `idx_pickup_scheduled_date` (`scheduled_date`);
ALTER TABLE `pickup_requests` ADD INDEX IF NOT EXISTS `idx_pickup_created_at` (`created_at`);

-- 3. Index untuk tabel vehicle_logs
ALTER TABLE `vehicle_logs` ADD INDEX IF NOT EXISTS `idx_vlogs_driver_released` (`driver_id`, `released_at`);

-- 4. Index untuk tabel users
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_users_role_active` (`role`, `is_active`);
