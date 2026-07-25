-- TK-EDC MySQL 5.7.43 数据库结构
-- 先在服务器面板创建数据库并选中该数据库，再执行本文件。
-- 推荐字符集：utf8mb4；排序规则：utf8mb4_unicode_ci。

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `id` VARCHAR(100) NOT NULL,
  `category` VARCHAR(191) NOT NULL DEFAULT '',
  `project_name` VARCHAR(191) NOT NULL DEFAULT '',
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `start_date` DATE DEFAULT NULL,
  `owner` VARCHAR(191) NOT NULL DEFAULT '',
  `client` VARCHAR(255) NOT NULL DEFAULT '',
  `payment_status` VARCHAR(50) NOT NULL DEFAULT '未收款',
  `statuses` TEXT NOT NULL,
  `files` MEDIUMTEXT NOT NULL,
  `note` MEDIUMTEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `id`),
  KEY `idx_projects_start_date` (`user_id`, `start_date`),
  KEY `idx_projects_category` (`user_id`, `category`),
  CONSTRAINT `fk_projects_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `presets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `preset_type` VARCHAR(100) NOT NULL,
  `preset_hash` CHAR(64) NOT NULL,
  `preset_value` MEDIUMTEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_presets_value` (`user_id`, `preset_type`, `preset_hash`),
  KEY `idx_presets_type` (`user_id`, `preset_type`),
  CONSTRAINT `fk_presets_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
