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

-- --------------------------------------------------------------------------
-- UCP V4: Master Account email + Support Center
-- --------------------------------------------------------------------------
ALTER TABLE `player_accounts`
    ADD COLUMN IF NOT EXISTS `email` VARCHAR(190) NULL AFTER `username`,
    ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME NULL AFTER `email`;

CREATE TABLE IF NOT EXISTS `ucp_support_requests` (
    `ticket_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` INT UNSIGNED NOT NULL,
    `category` ENUM('account','character','technical','other') NOT NULL DEFAULT 'account',
    `subject` VARCHAR(120) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('open','in_progress','closed') NOT NULL DEFAULT 'open',
    `admin_note` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ticket_id`),
    KEY `idx_ucp_support_account` (`account_id`),
    KEY `idx_ucp_support_status` (`status`),
    KEY `idx_ucp_support_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


USE `lsrp`;

-- --------------------------------------------------------------------------
-- UCP V4.3 — #TWOYEARS early registration package
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ucp_twoyears_accounts` (
    `account_id` INT UNSIGNED NOT NULL,
    `card_code` VARCHAR(32) NOT NULL,
    `package_code` VARCHAR(32) NOT NULL DEFAULT '#TWOYEARS',
    `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`account_id`),
    UNIQUE KEY `uq_twoyears_card_code` (`card_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Existing Master Accounts are also considered early registrants when V4.3 is installed.
INSERT INTO `ucp_twoyears_accounts` (`account_id`, `card_code`, `package_code`)
SELECT `account_id`, CONCAT('TWY-', LPAD(`account_id`, 6, '0')), '#TWOYEARS'
FROM `player_accounts`
ON DUPLICATE KEY UPDATE `package_code` = VALUES(`package_code`);

CREATE TABLE IF NOT EXISTS `ucp_twoyears_character_rewards` (
    `reward_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` INT UNSIGNED NOT NULL,
    `character_id` INT UNSIGNED NOT NULL,
    `vehicle_model` SMALLINT UNSIGNED NOT NULL DEFAULT 462,
    `vehicle_granted` TINYINT(1) NOT NULL DEFAULT 0,
    `vehicle_id` BIGINT UNSIGNED NULL,
    `vehicle_granted_at` DATETIME NULL,
    `notice_seen_at` DATETIME NULL,
    `last_error` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`reward_id`),
    UNIQUE KEY `uq_twoyears_character` (`character_id`),
    KEY `idx_twoyears_account_notice` (`account_id`, `notice_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- The UCP already reads character-owned vehicles from player_vehicles.
-- Create a compact compatible table only when the server does not have one yet.
-- If your gamemode already owns player_vehicles, this statement leaves it untouched.
CREATE TABLE IF NOT EXISTS `player_vehicles` (
    `vehicle_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `character_id` INT UNSIGNED NOT NULL,
    `model_id` SMALLINT UNSIGNED NOT NULL,
    `plate` VARCHAR(16) NOT NULL,
    `color1` SMALLINT NOT NULL DEFAULT 3,
    `color2` SMALLINT NOT NULL DEFAULT 3,
    `mileage` DECIMAL(12,1) NOT NULL DEFAULT 0,
    `fuel` DECIMAL(6,2) NOT NULL DEFAULT 100,
    `health` FLOAT NOT NULL DEFAULT 1000,
    `is_stored` TINYINT(1) NOT NULL DEFAULT 1,
    `is_spawned` TINYINT(1) NOT NULL DEFAULT 0,
    `is_favorite` TINYINT(1) NOT NULL DEFAULT 0,
    `pos_x` FLOAT NOT NULL DEFAULT 2495.3633,
    `pos_y` FLOAT NOT NULL DEFAULT -1687.3105,
    `pos_z` FLOAT NOT NULL DEFAULT 13.5156,
    `pos_a` FLOAT NOT NULL DEFAULT 0,
    `interior_id` INT NOT NULL DEFAULT 0,
    `virtual_world` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`vehicle_id`),
    UNIQUE KEY `uq_player_vehicle_plate` (`plate`),
    KEY `idx_player_vehicles_character` (`character_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
