USE `lsrp`;

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
