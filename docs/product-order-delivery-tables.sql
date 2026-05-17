CREATE TABLE IF NOT EXISTS `product_order_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_order_id` BIGINT UNSIGNED NOT NULL,
  `handyman_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_order_assignments_product_order_id_index` (`product_order_id`),
  KEY `product_order_assignments_handyman_id_index` (`handyman_id`),
  CONSTRAINT `product_order_assignments_product_order_id_foreign`
    FOREIGN KEY (`product_order_id`) REFERENCES `product_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_order_assignments_handyman_id_foreign`
    FOREIGN KEY (`handyman_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_order_activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_order_id` BIGINT UNSIGNED NOT NULL,
  `activity_type` VARCHAR(255) NULL DEFAULT NULL,
  `activity_message` TEXT NULL,
  `activity_data` LONGTEXT NULL,
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `datetime` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_order_activities_product_order_id_index` (`product_order_id`),
  KEY `product_order_activities_created_by_index` (`created_by`),
  CONSTRAINT `product_order_activities_product_order_id_foreign`
    FOREIGN KEY (`product_order_id`) REFERENCES `product_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_order_activities_created_by_foreign`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_order_live_locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `latitude` DECIMAL(10,7) NULL DEFAULT NULL,
  `longitude` DECIMAL(10,7) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_order_live_locations_product_order_id_unique` (`product_order_id`),
  KEY `product_order_live_locations_user_id_index` (`user_id`),
  CONSTRAINT `product_order_live_locations_product_order_id_foreign`
    FOREIGN KEY (`product_order_id`) REFERENCES `product_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_order_live_locations_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_order_proofs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_order_proofs_product_order_id_index` (`product_order_id`),
  KEY `product_order_proofs_user_id_index` (`user_id`),
  CONSTRAINT `product_order_proofs_product_order_id_foreign`
    FOREIGN KEY (`product_order_id`) REFERENCES `product_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_order_proofs_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
