USE `lsrp`;

-- ============================================================================
-- UCP V4.5.1 HOTFIX - player_vehicles schema compatibility
-- Fixes: #1054 Unknown column 'pv.character_id'
--
-- Supported ownership aliases:
--   character_id, owner_character_id, owner_id, characterid
-- Supported vehicle id aliases:
--   vehicle_id, id, sql_id, vehicle_db_id
-- Supported model aliases:
--   model_id, model, vehicle_model, modelid
-- Supported plate aliases:
--   plate, number_plate, license_plate
--
-- Safe to run again after the old 015 migration stopped halfway.
-- ============================================================================

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

-- Only creates this schema when player_vehicles does not already exist.
-- Existing gamemode vehicle tables are NEVER replaced.
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
DELETE newer
FROM `ucp_twoyears_character_rewards` newer
INNER JOIN `ucp_twoyears_character_rewards` older
    ON older.`account_id` = newer.`account_id`
   AND (
        older.`created_at` < newer.`created_at`
        OR (older.`created_at` = newer.`created_at` AND older.`reward_id` < newer.`reward_id`)
   );

-- Existing early accounts that already have characters but no reward row.
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

-- --------------------------------------------------------------------------
-- Detect the real player_vehicles schema.
-- --------------------------------------------------------------------------
SET @pv_owner_col := (
    SELECT `COLUMN_NAME`
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('character_id', 'owner_character_id', 'owner_id', 'characterid')
    ORDER BY FIELD(`COLUMN_NAME`, 'character_id', 'owner_character_id', 'owner_id', 'characterid')
    LIMIT 1
);

SET @pv_id_col := (
    SELECT `COLUMN_NAME`
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('vehicle_id', 'id', 'sql_id', 'vehicle_db_id')
    ORDER BY FIELD(`COLUMN_NAME`, 'vehicle_id', 'id', 'sql_id', 'vehicle_db_id')
    LIMIT 1
);

SET @pv_model_col := (
    SELECT `COLUMN_NAME`
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('model_id', 'model', 'vehicle_model', 'modelid')
    ORDER BY FIELD(`COLUMN_NAME`, 'model_id', 'model', 'vehicle_model', 'modelid')
    LIMIT 1
);

SET @pv_plate_col := (
    SELECT `COLUMN_NAME`
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('plate', 'number_plate', 'license_plate')
    ORDER BY FIELD(`COLUMN_NAME`, 'plate', 'number_plate', 'license_plate')
    LIMIT 1
);

-- Optional field aliases.
SET @pv_color1_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('color1','colour1','primary_color')
    ORDER BY FIELD(`COLUMN_NAME`,'color1','colour1','primary_color') LIMIT 1
);
SET @pv_color2_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('color2','colour2','secondary_color')
    ORDER BY FIELD(`COLUMN_NAME`,'color2','colour2','secondary_color') LIMIT 1
);
SET @pv_mileage_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('mileage','odometer','kilometers','km')
    ORDER BY FIELD(`COLUMN_NAME`,'mileage','odometer','kilometers','km') LIMIT 1
);
SET @pv_fuel_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('fuel','fuel_level')
    ORDER BY FIELD(`COLUMN_NAME`,'fuel','fuel_level') LIMIT 1
);
SET @pv_health_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('health','vehicle_health')
    ORDER BY FIELD(`COLUMN_NAME`,'health','vehicle_health') LIMIT 1
);
SET @pv_stored_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('is_stored','stored','in_garage')
    ORDER BY FIELD(`COLUMN_NAME`,'is_stored','stored','in_garage') LIMIT 1
);
SET @pv_spawned_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('is_spawned','spawned')
    ORDER BY FIELD(`COLUMN_NAME`,'is_spawned','spawned') LIMIT 1
);
SET @pv_favorite_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('is_favorite','favorite','favourite')
    ORDER BY FIELD(`COLUMN_NAME`,'is_favorite','favorite','favourite') LIMIT 1
);
SET @pv_x_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('pos_x','x','spawn_x')
    ORDER BY FIELD(`COLUMN_NAME`,'pos_x','x','spawn_x') LIMIT 1
);
SET @pv_y_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('pos_y','y','spawn_y')
    ORDER BY FIELD(`COLUMN_NAME`,'pos_y','y','spawn_y') LIMIT 1
);
SET @pv_z_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('pos_z','z','spawn_z')
    ORDER BY FIELD(`COLUMN_NAME`,'pos_z','z','spawn_z') LIMIT 1
);
SET @pv_a_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('pos_a','a','angle','spawn_a')
    ORDER BY FIELD(`COLUMN_NAME`,'pos_a','a','angle','spawn_a') LIMIT 1
);
SET @pv_interior_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('interior_id','interior')
    ORDER BY FIELD(`COLUMN_NAME`,'interior_id','interior') LIMIT 1
);
SET @pv_world_col := (
    SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'player_vehicles'
      AND `COLUMN_NAME` IN ('virtual_world','world','vw')
    ORDER BY FIELD(`COLUMN_NAME`,'virtual_world','world','vw') LIMIT 1
);

SET @pv_can_grant := IF(
    @pv_owner_col IS NOT NULL
    AND @pv_model_col IS NOT NULL
    AND @pv_plate_col IS NOT NULL,
    1,
    0
);

-- Build a compatible INSERT using only columns that really exist.
SET @pv_insert_cols := CONCAT('`', @pv_owner_col, '`,`', @pv_model_col, '`,`', @pv_plate_col, '`');
SET @pv_insert_vals := "r.`character_id`,462,CONCAT('TWY', LPAD(r.`character_id`, 6, '0'))";

SET @pv_insert_cols := IF(@pv_color1_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_color1_col, '`'));
SET @pv_insert_vals := IF(@pv_color1_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',3'));
SET @pv_insert_cols := IF(@pv_color2_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_color2_col, '`'));
SET @pv_insert_vals := IF(@pv_color2_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',3'));
SET @pv_insert_cols := IF(@pv_mileage_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_mileage_col, '`'));
SET @pv_insert_vals := IF(@pv_mileage_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',0'));
SET @pv_insert_cols := IF(@pv_fuel_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_fuel_col, '`'));
SET @pv_insert_vals := IF(@pv_fuel_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',100'));
SET @pv_insert_cols := IF(@pv_health_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_health_col, '`'));
SET @pv_insert_vals := IF(@pv_health_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',1000'));
SET @pv_stored_val := 1;
SET @pv_insert_cols := IF(@pv_stored_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_stored_col, '`'));
SET @pv_insert_vals := IF(@pv_stored_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',1'));
SET @pv_insert_cols := IF(@pv_spawned_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_spawned_col, '`'));
SET @pv_insert_vals := IF(@pv_spawned_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',0'));
SET @pv_insert_cols := IF(@pv_favorite_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_favorite_col, '`'));
SET @pv_insert_vals := IF(@pv_favorite_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',0'));
SET @pv_insert_cols := IF(@pv_x_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_x_col, '`'));
SET @pv_insert_vals := IF(@pv_x_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',2495.3633'));
SET @pv_insert_cols := IF(@pv_y_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_y_col, '`'));
SET @pv_insert_vals := IF(@pv_y_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',-1687.3105'));
SET @pv_insert_cols := IF(@pv_z_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_z_col, '`'));
SET @pv_insert_vals := IF(@pv_z_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',13.5156'));
SET @pv_insert_cols := IF(@pv_a_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_a_col, '`'));
SET @pv_insert_vals := IF(@pv_a_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',0'));
SET @pv_insert_cols := IF(@pv_interior_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_interior_col, '`'));
SET @pv_insert_vals := IF(@pv_interior_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',0'));
SET @pv_insert_cols := IF(@pv_world_col IS NULL, @pv_insert_cols, CONCAT(@pv_insert_cols, ',`', @pv_world_col, '`'));
SET @pv_insert_vals := IF(@pv_world_col IS NULL, @pv_insert_vals, CONCAT(@pv_insert_vals, ',0'));

SET @sql := IF(
    @pv_can_grant = 1,
    CONCAT(
        'INSERT INTO `player_vehicles` (', @pv_insert_cols, ') ',
        'SELECT ', @pv_insert_vals, ' ',
        'FROM `ucp_twoyears_character_rewards` r ',
        'WHERE r.`vehicle_granted` = 0 ',
        'AND NOT EXISTS (',
            'SELECT 1 FROM `player_vehicles` pv ',
            'WHERE pv.`', @pv_owner_col, '` = r.`character_id` ',
            'AND pv.`', @pv_plate_col, '` = CONCAT(''TWY'', LPAD(r.`character_id`, 6, ''0''))',
        ')' 
    ),
    "SELECT 'SKIPPED FAGGIO INSERT: player_vehicles needs an ownership, model and plate column supported by UCP.' AS `UCP_V45_FIX`"
);
PREPARE ucp_stmt FROM @sql;
EXECUTE ucp_stmt;
DEALLOCATE PREPARE ucp_stmt;

-- Sync reward state using the real owner/plate/id column names.
SET @pv_reward_id_set := IF(
    @pv_id_col IS NULL,
    '',
    CONCAT(', r.`vehicle_id` = pv.`', @pv_id_col, '`')
);

SET @sql := IF(
    @pv_can_grant = 1,
    CONCAT(
        'UPDATE `ucp_twoyears_character_rewards` r ',
        'INNER JOIN `player_vehicles` pv ',
        'ON pv.`', @pv_owner_col, '` = r.`character_id` ',
        'AND pv.`', @pv_plate_col, '` = CONCAT(''TWY'', LPAD(r.`character_id`, 6, ''0'')) ',
        'SET r.`vehicle_granted` = 1', @pv_reward_id_set, ', ',
        'r.`vehicle_granted_at` = COALESCE(r.`vehicle_granted_at`, NOW()), ',
        'r.`last_error` = NULL'
    ),
    "SELECT 'SKIPPED REWARD SYNC: incompatible player_vehicles schema.' AS `UCP_V45_FIX`"
);
PREPARE ucp_stmt FROM @sql;
EXECUTE ucp_stmt;
DEALLOCATE PREPARE ucp_stmt;

-- Every submitted application has a persistent notification.
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

-- Refresh old approval/reward notifications without assuming pv.character_id or pv.vehicle_id.
UPDATE `ucp_notifications` n
INNER JOIN `ucp_twoyears_character_rewards` r
    ON n.`dedupe_key` = CONCAT('twoyears-character-approved-', r.`character_id`)
INNER JOIN `player_characters` pc ON pc.`character_id` = r.`character_id`
SET
    n.`title` = 'Nhân vật đã được duyệt · Nhận thưởng #TWOYEARS',
    n.`message` = CONCAT(
        REPLACE(pc.`name`, '_', ' '),
        ' đã được Ban quản trị phê duyệt. ',
        IF(
            r.`vehicle_granted` = 1,
            CONCAT('Nhân vật đã nhận 01 Faggio model 462, biển số TWY', LPAD(r.`character_id`, 6, '0'), '.'),
            'Quyền nhận 01 Faggio model 462 đã được ghi nhận.'
        )
    ),
    n.`action_url` = CONCAT('character.php?id=', r.`character_id`),
    n.`action_label` = 'Xem thông tin nhân vật',
    n.`meta_json` = CONCAT(
        '{"character_id":', r.`character_id`,
        ',"character_name":"', REPLACE(pc.`name`, '"', '\\"'), '"',
        ',"vehicle_model":462',
        ',"vehicle_id":', COALESCE(r.`vehicle_id`, 0),
        ',"vehicle_plate":"TWY', LPAD(r.`character_id`, 6, '0'), '"',
        ',"vehicle_granted":', IF(r.`vehicle_granted` = 1, 'true', 'false'),
        '}'
    );

-- Show what the migration detected so you can verify immediately in phpMyAdmin.
SELECT
    @pv_owner_col AS `detected_owner_column`,
    @pv_id_col AS `detected_vehicle_id_column`,
    @pv_model_col AS `detected_model_column`,
    @pv_plate_col AS `detected_plate_column`,
    IF(@pv_can_grant = 1, 'OK', 'CHECK PLAYER_VEHICLES SCHEMA') AS `status`;

SELECT
    r.`reward_id`,
    r.`account_id`,
    r.`character_id`,
    r.`vehicle_model`,
    r.`vehicle_granted`,
    r.`vehicle_id`,
    r.`vehicle_granted_at`,
    r.`last_error`
FROM `ucp_twoyears_character_rewards` r
ORDER BY r.`reward_id` DESC;

-- ============================================================================
-- V4.5.3: backfill approval notifications that were lost by the old
-- "There is no active transaction" flow.
-- ============================================================================

-- Approved #TWOYEARS characters.
INSERT INTO `ucp_notifications`
    (`account_id`, `type`, `title`, `message`, `action_url`, `action_label`, `dedupe_key`, `meta_json`, `is_read`, `created_at`)
SELECT
    ca.`account_id`,
    'character_approved',
    IF(r.`vehicle_granted` = 1,
       'Nhân vật đã được duyệt · Đã nhận #TWOYEARS',
       'Nhân vật đã được duyệt · #TWOYEARS'),
    CONCAT(
        REPLACE(pc.`name`, '_', ' '),
        ' đã được Ban quản trị phê duyệt. ',
        IF(
            r.`vehicle_granted` = 1,
            CONCAT('Nhân vật đã nhận 01 Faggio model 462 #TWOYEARS, biển số TWY', LPAD(pc.`character_id`, 6, '0'), '.'),
            'Quyền lợi #TWOYEARS đã được ghi nhận; Faggio đang chờ hệ thống cấp lại.'
        )
    ),
    CONCAT('character.php?id=', pc.`character_id`),
    'Xem thông tin nhân vật',
    CONCAT('twoyears-character-approved-', pc.`character_id`),
    CONCAT(
        '{"application_id":', ca.`application_id`,
        ',"character_id":', pc.`character_id`,
        ',"character_name":"', REPLACE(pc.`name`, '"', '\\"'), '"',
        ',"slot":', pc.`slot`,
        ',"vehicle_model":462',
        ',"vehicle_id":', COALESCE(r.`vehicle_id`, 0),
        ',"vehicle_plate":"TWY', LPAD(pc.`character_id`, 6, '0'), '"',
        ',"vehicle_granted":', IF(r.`vehicle_granted` = 1, 'true', 'false'),
        '}'
    ),
    0,
    COALESCE(ca.`reviewed_at`, ca.`created_at`, NOW())
FROM `character_applications` ca
INNER JOIN `player_characters` pc
    ON pc.`account_id` = ca.`account_id`
   AND pc.`slot` = ca.`slot`
   AND pc.`name` = ca.`character_name`
INNER JOIN `ucp_twoyears_character_rewards` r
    ON r.`account_id` = ca.`account_id`
   AND r.`character_id` = pc.`character_id`
WHERE ca.`status` = 'approved'
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `message` = VALUES(`message`),
    `action_url` = VALUES(`action_url`),
    `action_label` = VALUES(`action_label`),
    `meta_json` = VALUES(`meta_json`);

-- Approved normal characters (no #TWOYEARS reward row).
INSERT INTO `ucp_notifications`
    (`account_id`, `type`, `title`, `message`, `action_url`, `action_label`, `dedupe_key`, `meta_json`, `is_read`, `created_at`)
SELECT
    ca.`account_id`,
    'character_approved_basic',
    'Nhân vật đã được phê duyệt',
    CONCAT(REPLACE(pc.`name`, '_', ' '), ' đã được Ban quản trị phê duyệt và Character Slot đã được tạo.'),
    CONCAT('character.php?id=', pc.`character_id`),
    'Xem thông tin nhân vật',
    CONCAT('character-approved-', pc.`character_id`),
    CONCAT(
        '{"application_id":', ca.`application_id`,
        ',"character_id":', pc.`character_id`,
        ',"character_name":"', REPLACE(pc.`name`, '"', '\\"'), '"',
        ',"slot":', pc.`slot`,
        '}'
    ),
    0,
    COALESCE(ca.`reviewed_at`, ca.`created_at`, NOW())
FROM `character_applications` ca
INNER JOIN `player_characters` pc
    ON pc.`account_id` = ca.`account_id`
   AND pc.`slot` = ca.`slot`
   AND pc.`name` = ca.`character_name`
LEFT JOIN `ucp_twoyears_character_rewards` r
    ON r.`account_id` = ca.`account_id`
   AND r.`character_id` = pc.`character_id`
WHERE ca.`status` = 'approved'
  AND r.`reward_id` IS NULL
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `message` = VALUES(`message`),
    `action_url` = VALUES(`action_url`),
    `action_label` = VALUES(`action_label`),
    `meta_json` = VALUES(`meta_json`);

-- Final verification.
SELECT
    r.`reward_id`, r.`account_id`, r.`character_id`, r.`vehicle_model`,
    r.`vehicle_granted`, r.`vehicle_id`, r.`vehicle_granted_at`, r.`last_error`
FROM `ucp_twoyears_character_rewards` r
ORDER BY r.`reward_id` DESC
LIMIT 50;

SELECT
    n.`notification_id`, n.`account_id`, n.`type`, n.`title`, n.`is_read`, n.`created_at`
FROM `ucp_notifications` n
WHERE n.`type` IN ('character_approved', 'character_approved_basic')
ORDER BY n.`notification_id` DESC
LIMIT 50;
