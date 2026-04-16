-- ==========================================
-- Marks Table for Create Values & Marks Feature
-- ==========================================

CREATE TABLE IF NOT EXISTS `mcc_marks` (
  `marks_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `report_id` INT NOT NULL,
  `value` INT DEFAULT 0,
  `rating` VARCHAR(50) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `Mcc_users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`report_id`) REFERENCES `Mcc_reports`(`report_id`) ON DELETE CASCADE,
  INDEX `idx_user_report` (`user_id`, `report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
