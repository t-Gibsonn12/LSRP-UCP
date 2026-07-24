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

-- V4.4: reward eligibility only; no player_vehicles table or Faggio insert is created here.

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



-- ===== UCP V4.5 migration =====

-- --------------------------------------------------------------------------
-- UCP V4.5
-- - Persistent notification for submitted character applications
-- - #TWOYEARS starter Faggio is granted to the FIRST approved character only
-- - Backfill existing pending V4.4 reward rows into player_vehicles
-- --------------------------------------------------------------------------

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

-- Keep only the earliest #TWOYEARS starter reward per Master Account.
-- V4.4 may have recorded one reward for more than one character.
DELETE newer
FROM `ucp_twoyears_character_rewards` newer
INNER JOIN `ucp_twoyears_character_rewards` older
    ON older.`account_id` = newer.`account_id`
   AND (
        older.`created_at` < newer.`created_at`
        OR (older.`created_at` = newer.`created_at` AND older.`reward_id` < newer.`reward_id`)
   );

-- Existing early accounts that already have characters but no reward record:
-- attach the package to their first character.
INSERT INTO `ucp_twoyears_character_rewards`
    (`account_id`, `character_id`, `vehicle_model`)
SELECT
    t.`account_id`,
    MIN(pc.`character_id`) AS `character_id`,
    462
FROM `ucp_twoyears_accounts` t
INNER JOIN `player_characters` pc ON pc.`account_id` = t.`account_id`
LEFT JOIN `ucp_twoyears_character_rewards` r ON r.`account_id` = t.`account_id`
WHERE r.`reward_id` IS NULL
GROUP BY t.`account_id`;

-- Grant the Faggio directly to the character-owned vehicle table.
INSERT INTO `player_vehicles`
    (`character_id`, `model_id`, `plate`, `color1`, `color2`, `mileage`, `fuel`, `health`,
     `is_stored`, `is_spawned`, `is_favorite`, `pos_x`, `pos_y`, `pos_z`, `pos_a`,
     `interior_id`, `virtual_world`)
SELECT
    r.`character_id`,
    462,
    CONCAT('TWY', LPAD(r.`character_id`, 6, '0')),
    3, 3, 0, 100, 1000,
    1, 0, 0,
    2495.3633, -1687.3105, 13.5156, 0,
    0, 0
FROM `ucp_twoyears_character_rewards` r
WHERE r.`vehicle_granted` = 0
  AND NOT EXISTS (
      SELECT 1
      FROM `player_vehicles` pv
      WHERE pv.`character_id` = r.`character_id`
        AND pv.`plate` = CONCAT('TWY', LPAD(r.`character_id`, 6, '0'))
  );

-- Sync reward tracking with the actual vehicle row.
UPDATE `ucp_twoyears_character_rewards` r
INNER JOIN `player_vehicles` pv
    ON pv.`character_id` = r.`character_id`
   AND pv.`plate` = CONCAT('TWY', LPAD(r.`character_id`, 6, '0'))
SET
    r.`vehicle_granted` = 1,
    r.`vehicle_id` = pv.`vehicle_id`,
    r.`vehicle_granted_at` = COALESCE(r.`vehicle_granted_at`, NOW()),
    r.`last_error` = NULL;

-- Every submitted character application receives a persistent notification.
INSERT INTO `ucp_notifications`
    (`account_id`, `type`, `title`, `message`, `action_url`, `action_label`, `dedupe_key`, `meta_json`, `is_read`, `created_at`)
SELECT
    ca.`account_id`,
    'character_application_submitted',
    'Đã gửi yêu cầu tạo nhân vật',
    CONCAT('Hồ sơ ', REPLACE(ca.`character_name`, '_', ' '), ' đã được gửi tới Ban quản trị và đang chờ phê duyệt.'),
    'applications.php',
    'Xem nhân vật chờ duyệt',
    CONCAT('character-application-submitted-', ca.`application_id`),
    CONCAT('{"application_id":', ca.`application_id`, ',"character_name":"', REPLACE(ca.`character_name`, '"', '\\"'), '","slot":', ca.`slot`, '}'),
    0,
    ca.`created_at`
FROM `character_applications` ca
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `message` = VALUES(`message`),
    `action_url` = VALUES(`action_url`),
    `action_label` = VALUES(`action_label`),
    `meta_json` = VALUES(`meta_json`);

-- Refresh old V4.4 #TWOYEARS approval notifications now that the vehicle is real.
UPDATE `ucp_notifications` n
INNER JOIN `ucp_twoyears_character_rewards` r
    ON n.`dedupe_key` = CONCAT('twoyears-character-approved-', r.`character_id`)
INNER JOIN `player_characters` pc ON pc.`character_id` = r.`character_id`
LEFT JOIN `player_vehicles` pv ON pv.`vehicle_id` = r.`vehicle_id`
SET
    n.`title` = 'Nhân vật đã được duyệt · Nhận thưởng #TWOYEARS',
    n.`message` = CONCAT(REPLACE(pc.`name`, '_', ' '), ' đã được Ban quản trị phê duyệt. Nhân vật đã nhận 01 Faggio model 462', IF(pv.`plate` IS NULL, '.', CONCAT(', biển số ', pv.`plate`, '.'))),
    n.`action_url` = CONCAT('character.php?id=', r.`character_id`),
    n.`action_label` = 'Xem thông tin nhân vật',
    n.`meta_json` = CONCAT('{"character_id":', r.`character_id`, ',"character_name":"', REPLACE(pc.`name`, '"', '\\"'), '","vehicle_model":462,"vehicle_id":', COALESCE(r.`vehicle_id`, 0), ',"vehicle_plate":"', COALESCE(pv.`plate`, ''), '","vehicle_granted":true}');
