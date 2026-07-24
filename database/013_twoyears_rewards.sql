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

-- V4.4 note:
-- The reward table only records eligibility. UCP does NOT create or insert into
-- player_vehicles yet. Vehicle delivery will be connected to the gamemode module later.
