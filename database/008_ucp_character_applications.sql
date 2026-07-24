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

-- Compatibility with earlier V2 installations.
ALTER TABLE `character_applications`
    ADD COLUMN IF NOT EXISTS `admin_note` TEXT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `reviewed_by` INT UNSIGNED NULL AFTER `admin_note`,
    ADD COLUMN IF NOT EXISTS `reviewed_at` DATETIME NULL AFTER `reviewed_by`,
    ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
