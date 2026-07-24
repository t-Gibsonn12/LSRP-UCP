USE `lsrp`;

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
