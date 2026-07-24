USE `lsrp`;

ALTER TABLE `player_accounts`
    ADD COLUMN IF NOT EXISTS `email` VARCHAR(190) NULL AFTER `username`,
    ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME NULL AFTER `email`;
