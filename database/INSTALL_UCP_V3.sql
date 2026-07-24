USE `lsrp`;

CREATE TABLE IF NOT EXISTS `character_applications` (
    `application_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` INT UNSIGNED NOT NULL,
    `slot` TINYINT UNSIGNED NOT NULL,
    `character_name` VARCHAR(25) NOT NULL,
    `concept` VARCHAR(1000) NOT NULL,
    `background` TEXT NOT NULL,
    `roleplay_goal` TEXT NOT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note` TEXT NULL,
    `reviewed_by` INT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`application_id`),
    KEY `idx_character_applications_account` (`account_id`),
    KEY `idx_character_applications_status` (`status`),
    KEY `idx_character_applications_name` (`character_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `character_applications`
    ADD COLUMN IF NOT EXISTS `admin_note` TEXT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `reviewed_by` INT UNSIGNED NULL AFTER `admin_note`,
    ADD COLUMN IF NOT EXISTS `reviewed_at` DATETIME NULL AFTER `reviewed_by`,
    ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

CREATE TABLE IF NOT EXISTS `ucp_news` (
    `news_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(180) NOT NULL,
    `excerpt` VARCHAR(500) NULL,
    `content` MEDIUMTEXT NOT NULL,
    `is_hot` TINYINT(1) NOT NULL DEFAULT 0,
    `is_published` TINYINT(1) NOT NULL DEFAULT 0,
    `published_at` DATETIME NULL,
    `created_by` INT UNSIGNED NULL,
    `updated_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`news_id`),
    KEY `idx_ucp_news_publish` (`is_published`, `published_at`),
    KEY `idx_ucp_news_hot` (`is_hot`, `is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ucp_admin_logs` (
    `log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_account_id` INT UNSIGNED NULL,
    `action` VARCHAR(80) NOT NULL,
    `target_type` VARCHAR(40) NOT NULL,
    `target_id` BIGINT UNSIGNED NULL,
    `details` LONGTEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `idx_ucp_admin_logs_admin` (`admin_account_id`),
    KEY `idx_ucp_admin_logs_target` (`target_type`, `target_id`),
    KEY `idx_ucp_admin_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ucp_news`
(`title`, `excerpt`, `content`, `is_hot`, `is_published`, `published_at`)
SELECT
'#TWO YEARS — Chặng đường tiếp theo',
'2 năm không quá lâu nhưng cũng không nhanh, 2 năm đã qua và 2 năm tới.',
'Los Santos Roleplay Vietnamese tiếp tục chặng đường xây dựng một cộng đồng roleplay nghiêm túc. Những câu chuyện mới, hệ thống mới và hai năm tiếp theo đang chờ phía trước.',
1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ucp_news` LIMIT 1);
