USE `lsrp`;

-- --------------------------------------------------------------------------
-- UCP V4.4 — Self-healing #TWOYEARS tracking + persistent notifications
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ucp_twoyears_accounts` (
    `account_id` INT UNSIGNED NOT NULL,
    `card_code` VARCHAR(32) NOT NULL,
    `package_code` VARCHAR(32) NOT NULL DEFAULT '#TWOYEARS',
    `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`account_id`),
    UNIQUE KEY `uq_twoyears_card_code` (`card_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- All accounts that exist while the early-registration campaign is active are enrolled.
INSERT INTO `ucp_twoyears_accounts` (`account_id`, `card_code`, `package_code`)
SELECT `account_id`, CONCAT('TWY-', LPAD(`account_id`, 6, '0')), '#TWOYEARS'
FROM `player_accounts`
ON DUPLICATE KEY UPDATE `package_code` = VALUES(`package_code`);

CREATE TABLE IF NOT EXISTS `ucp_notifications` (
    `notification_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(40) NOT NULL DEFAULT 'system',
    `title` VARCHAR(160) NOT NULL,
    `message` TEXT NOT NULL,
    `action_url` VARCHAR(255) NULL,
    `action_label` VARCHAR(80) NULL,
    `dedupe_key` VARCHAR(120) NULL,
    `meta_json` LONGTEXT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`notification_id`),
    UNIQUE KEY `uq_ucp_notifications_dedupe` (`dedupe_key`),
    KEY `idx_ucp_notifications_account` (`account_id`, `is_read`, `created_at`),
    KEY `idx_ucp_notifications_type` (`account_id`, `type`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Backfill the early-registration message for accounts already enrolled in #TWOYEARS.
INSERT IGNORE INTO `ucp_notifications`
(`account_id`, `type`, `title`, `message`, `action_url`, `action_label`, `dedupe_key`, `created_at`)
SELECT
    t.`account_id`,
    'twoyears_joined',
    'Bạn đã tham gia sớm #TWOYEARS',
    'Bạn đã đăng ký tham gia sớm Los Santos Roleplay. Khi nhân vật đầu tiên của bạn được tạo và duyệt, tài khoản sẽ được ghi nhận phần thưởng #TWOYEARS gồm 01 Faggio khởi đầu cùng các quyền lợi dành cho thành viên sớm.',
    'characters.php',
    'Xem nhân vật',
    CONCAT('twoyears-account-', t.`account_id`),
    t.`joined_at`
FROM `ucp_twoyears_accounts` t;

-- Backfill approval/reward notifications for characters already recorded by V4.3.
INSERT IGNORE INTO `ucp_notifications`
(`account_id`, `type`, `title`, `message`, `action_url`, `action_label`, `dedupe_key`, `created_at`)
SELECT
    r.`account_id`,
    'character_approved',
    'Nhân vật đã được duyệt · #TWOYEARS',
    CONCAT(REPLACE(pc.`name`, '_', ' '), ' đã được Ban quản trị duyệt. Vì tài khoản đăng ký sớm, nhân vật đã được ghi nhận quyền nhận 01 Faggio khởi đầu #TWOYEARS. Xe chưa được cấp vào hệ thống phương tiện ở phiên bản UCP hiện tại.'),
    CONCAT('character.php?id=', r.`character_id`),
    'Xem thông tin nhân vật',
    CONCAT('twoyears-character-approved-', r.`character_id`),
    r.`created_at`
FROM `ucp_twoyears_character_rewards` r
INNER JOIN `player_characters` pc ON pc.`character_id` = r.`character_id`;
