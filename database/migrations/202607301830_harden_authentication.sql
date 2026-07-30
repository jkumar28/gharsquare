-- Add shared authentication throttling and per-code OTP attempt limits.

ALTER TABLE `user_otps`
    ADD COLUMN `attempt_count` tinyint unsigned NOT NULL DEFAULT 0 AFTER `is_used`,
    ADD COLUMN `max_attempts` tinyint unsigned NOT NULL DEFAULT 5 AFTER `attempt_count`,
    ADD KEY `idx_user_otps_cleanup` (`expires_at`, `is_used`);

CREATE TABLE `auth_rate_limits` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `scope` varchar(50) NOT NULL,
    `identifier_hash` char(64) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `attempt_count` smallint unsigned NOT NULL DEFAULT 0,
    `window_started_at` datetime NOT NULL,
    `blocked_until` datetime DEFAULT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_auth_rate_limit_identity` (`scope`, `identifier_hash`, `ip_address`),
    KEY `idx_auth_rate_limits_cleanup` (`updated_at`, `blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
