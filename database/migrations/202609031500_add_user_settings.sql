CREATE TABLE IF NOT EXISTS `user_settings` (
  `user_id` bigint(20) unsigned NOT NULL,
  `preferred_contact` enum('call','email','whatsapp') NOT NULL DEFAULT 'call',
  `enquiry_updates` tinyint(1) NOT NULL DEFAULT 1,
  `listing_updates` tinyint(1) NOT NULL DEFAULT 1,
  `saved_search_alerts` tinyint(1) NOT NULL DEFAULT 0,
  `marketing_updates` tinyint(1) NOT NULL DEFAULT 0,
  `activity_personalization` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_user_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
