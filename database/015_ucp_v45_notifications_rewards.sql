USE `lsrp`;

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
