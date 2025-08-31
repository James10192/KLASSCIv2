-- Table pour les souscriptions aux frais optionnels
CREATE TABLE `esbtp_frais_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inscription_id` bigint(20) unsigned NOT NULL,
  `frais_category_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `subscribed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint(20) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `esbtp_frais_subscriptions_inscription_id_frais_category_id_unique` (`inscription_id`,`frais_category_id`),
  KEY `esbtp_frais_subscriptions_inscription_id_is_active_index` (`inscription_id`,`is_active`),
  KEY `esbtp_frais_subscriptions_frais_category_id_is_active_index` (`frais_category_id`,`is_active`),
  KEY `esbtp_frais_subscriptions_created_by_foreign` (`created_by`),
  CONSTRAINT `esbtp_frais_subscriptions_inscription_id_foreign` FOREIGN KEY (`inscription_id`) REFERENCES `esbtp_inscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `esbtp_frais_subscriptions_frais_category_id_foreign` FOREIGN KEY (`frais_category_id`) REFERENCES `esbtp_frais_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `esbtp_frais_subscriptions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;