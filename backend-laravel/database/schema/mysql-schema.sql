/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `discovered_host_id` bigint unsigned DEFAULT NULL,
  `agent_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enrollment_short_code` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code court 8 chars pour URL /e/{code} — copier-coller-free',
  `enrollment_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enrollment_token_expires_at` timestamp NULL DEFAULT NULL,
  `agent_api_key` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Clé API per-agent, générée à l''enrôlement. NULL = non encore enrôlé.',
  `agent_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hostname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host_role` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT 'client',
  `status` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `enrollment_status` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `risk_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `risk_score` int unsigned NOT NULL DEFAULT '0',
  `is_isolated` tinyint(1) NOT NULL DEFAULT '0',
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agents_agent_uuid_unique` (`agent_uuid`),
  UNIQUE KEY `agents_enrollment_short_code_unique` (`enrollment_short_code`),
  KEY `agents_status_risk_level_index` (`status`,`risk_level`),
  KEY `agents_ip_address_index` (`ip_address`),
  KEY `agents_hostname_index` (`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alert_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `alert_id` bigint unsigned DEFAULT NULL,
  `incident_id` bigint unsigned DEFAULT NULL,
  `channel` enum('ui','sound','mail') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','sent','read','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_notifications_alert_id_foreign` (`alert_id`),
  KEY `alert_notifications_incident_id_foreign` (`incident_id`),
  KEY `alert_notifications_channel_status_index` (`channel`,`status`),
  CONSTRAINT `alert_notifications_alert_id_foreign` FOREIGN KEY (`alert_id`) REFERENCES `alerts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `alert_notifications_incident_id_foreign` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `alert_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `incident_id` bigint unsigned DEFAULT NULL,
  `event_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `risk_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `score` int unsigned NOT NULL DEFAULT '0',
  `detected_at` timestamp NULL DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` bigint unsigned DEFAULT NULL,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alerts_alert_uuid_unique` (`alert_uuid`),
  KEY `alerts_agent_id_foreign` (`agent_id`),
  KEY `alerts_incident_id_foreign` (`incident_id`),
  KEY `alerts_event_id_foreign` (`event_id`),
  KEY `alerts_acknowledged_by_foreign` (`acknowledged_by`),
  KEY `alerts_resolved_by_foreign` (`resolved_by`),
  KEY `alerts_status_risk_level_index` (`status`,`risk_level`),
  KEY `alerts_detected_at_index` (`detected_at`),
  CONSTRAINT `alerts_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `alerts_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `alerts_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `alerts_incident_id_foreign` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `alerts_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attack_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attack_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_simulation` tinyint(1) NOT NULL DEFAULT '0',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `indicators` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attack_profiles_code_unique` (`code`),
  KEY `attack_profiles_is_simulation_is_enabled_index` (`is_simulation`,`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detection_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detection_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `risk_level` enum('normal','suspect','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'suspect',
  `score_weight` int unsigned NOT NULL DEFAULT '10',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `conditions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `detection_rules_code_unique` (`code`),
  KEY `detection_rules_event_type_risk_level_is_enabled_index` (`event_type`,`risk_level`,`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detection_thresholds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detection_thresholds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `risk_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `min_score` int NOT NULL DEFAULT '0',
  `max_score` int DEFAULT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `detection_thresholds_key_unique` (`key`),
  UNIQUE KEY `detection_thresholds_code_unique` (`code`),
  KEY `detection_thresholds_is_enabled_index` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discovered_hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discovered_hosts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `managed_network_id` bigint unsigned NOT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mac_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hostname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host_role` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT 'client',
  `device_vendor` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fabricant identifié par OUI (ex: Apple, Inc., Xiaomi…)',
  `device_category` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Catégorie : mobile | apple_device | computer | router | iot | printer | tv | unknown',
  `discovery_status` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'detected',
  `enrollment_status` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_enrolled',
  `is_monitored` tinyint(1) NOT NULL DEFAULT '1',
  `open_ports` json DEFAULT NULL,
  `detected_services` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `retired_at` timestamp NULL DEFAULT NULL,
  `retired_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discovered_hosts_managed_network_id_ip_address_unique` (`managed_network_id`,`ip_address`),
  KEY `discovered_hosts_host_role_discovery_status_index` (`host_role`,`discovery_status`),
  CONSTRAINT `discovered_hosts_managed_network_id_foreign` FOREIGN KEY (`managed_network_id`) REFERENCES `managed_networks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` bigint unsigned NOT NULL,
  `incident_id` bigint unsigned DEFAULT NULL,
  `event_uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` text COLLATE utf8mb4_unicode_ci,
  `old_path` text COLLATE utf8mb4_unicode_ci,
  `file_extension` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `file_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` int unsigned NOT NULL DEFAULT '0',
  `risk_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `is_simulation` tinyint(1) NOT NULL DEFAULT '0',
  `simulation_run_uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raw_payload` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `observed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_event_uuid_unique` (`event_uuid`),
  KEY `events_incident_id_foreign` (`incident_id`),
  KEY `events_event_type_risk_level_index` (`event_type`,`risk_level`),
  KEY `events_agent_id_observed_at_index` (`agent_id`,`observed_at`),
  KEY `events_is_simulation_index` (`is_simulation`),
  CONSTRAINT `events_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_incident_id_foreign` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incidents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `incident_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `attack_profile_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `risk_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `risk_score` int unsigned NOT NULL DEFAULT '0',
  `detected_at` timestamp NULL DEFAULT NULL,
  `contained_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `reopened_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `incidents_incident_uuid_unique` (`incident_uuid`),
  KEY `incidents_agent_id_foreign` (`agent_id`),
  KEY `incidents_attack_profile_id_foreign` (`attack_profile_id`),
  KEY `incidents_resolved_by_foreign` (`resolved_by`),
  KEY `incidents_status_risk_level_index` (`status`,`risk_level`),
  KEY `incidents_detected_at_index` (`detected_at`),
  CONSTRAINT `incidents_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidents_attack_profile_id_foreign` FOREIGN KEY (`attack_profile_id`) REFERENCES `attack_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidents_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `managed_networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `managed_networks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidr` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gateway_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interface_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'detected',
  `is_scannable` tinyint(1) NOT NULL DEFAULT '1',
  `is_monitored` tinyint(1) NOT NULL DEFAULT '1',
  `last_scanned_at` timestamp NULL DEFAULT NULL,
  `retired_at` timestamp NULL DEFAULT NULL,
  `retired_reason` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `managed_networks_cidr_unique` (`cidr`),
  KEY `managed_networks_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monitored_paths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monitored_paths` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` bigint unsigned NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '1',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monitored_paths_agent_id_path_unique` (`agent_id`,`path`),
  KEY `monitored_paths_is_enabled_index` (`is_enabled`),
  CONSTRAINT `monitored_paths_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `network_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `network_shares` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` bigint unsigned DEFAULT NULL,
  `share_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `share_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `protocol` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'smb',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `network_shares_agent_id_foreign` (`agent_id`),
  KEY `network_shares_protocol_is_enabled_index` (`protocol`,`is_enabled`),
  CONSTRAINT `network_shares_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `protection_action_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protection_action_decisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `protection_action_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `decision` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `protection_action_decisions_protection_action_id_foreign` (`protection_action_id`),
  KEY `protection_action_decisions_user_id_foreign` (`user_id`),
  KEY `protection_action_decisions_decision_decided_at_index` (`decision`,`decided_at`),
  CONSTRAINT `protection_action_decisions_protection_action_id_foreign` FOREIGN KEY (`protection_action_id`) REFERENCES `protection_actions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `protection_action_decisions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `protection_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protection_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `action_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `incident_id` bigint unsigned DEFAULT NULL,
  `protection_policy_id` bigint unsigned DEFAULT NULL,
  `action_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `decision_mode` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `execution_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approval_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_reversible` tinyint(1) NOT NULL DEFAULT '0',
  `rollback_available` tinyint(1) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `payload` json DEFAULT NULL,
  `result` json DEFAULT NULL,
  `proposed_at` timestamp NULL DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  `rolled_back_at` timestamp NULL DEFAULT NULL,
  `executed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `protection_actions_action_uuid_unique` (`action_uuid`),
  KEY `protection_actions_agent_id_foreign` (`agent_id`),
  KEY `protection_actions_incident_id_foreign` (`incident_id`),
  KEY `protection_actions_protection_policy_id_foreign` (`protection_policy_id`),
  KEY `protection_actions_executed_by_foreign` (`executed_by`),
  KEY `protection_actions_decision_mode_approval_status_index` (`decision_mode`,`approval_status`),
  KEY `protection_actions_execution_status_action_type_index` (`execution_status`,`action_type`),
  CONSTRAINT `protection_actions_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `protection_actions_executed_by_foreign` FOREIGN KEY (`executed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `protection_actions_incident_id_foreign` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `protection_actions_protection_policy_id_foreign` FOREIGN KEY (`protection_policy_id`) REFERENCES `protection_policies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `protection_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protection_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'agent',
  `risk_level` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'suspect',
  `action_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'notify',
  `alert_only` tinyint(1) NOT NULL DEFAULT '1',
  `emergency_backup` tinyint(1) NOT NULL DEFAULT '0',
  `lock_safe_copy` tinyint(1) NOT NULL DEFAULT '0',
  `isolate_host` tinyint(1) NOT NULL DEFAULT '0',
  `kill_process` tinyint(1) NOT NULL DEFAULT '0',
  `restrict_path` tinyint(1) NOT NULL DEFAULT '0',
  `execution_mode` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `allow_admin_override` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `configuration` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `protection_policies_code_unique` (`code`),
  KEY `pp_scope_risk_exec_enabled_idx` (`scope`,`risk_level`,`execution_mode`,`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `risk_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_snapshots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` bigint unsigned NOT NULL,
  `incident_id` bigint unsigned DEFAULT NULL,
  `score` int unsigned NOT NULL DEFAULT '0',
  `risk_level` enum('normal','suspect','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `signals` json DEFAULT NULL,
  `calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `risk_snapshots_incident_id_foreign` (`incident_id`),
  KEY `risk_snapshots_agent_id_risk_level_index` (`agent_id`,`risk_level`),
  KEY `risk_snapshots_calculated_at_index` (`calculated_at`),
  CONSTRAINT `risk_snapshots_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `risk_snapshots_incident_id_foreign` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sensitive_extensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sensitive_extensions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `extension` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `risk_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'suspect',
  `score_weight` int NOT NULL DEFAULT '10',
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `category` enum('important','suspicious') COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sensitive_extensions_extension_unique` (`extension`),
  KEY `sensitive_extensions_category_is_enabled_index` (`category`,`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `simulation_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `simulation_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `run_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `attack_profile_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('planned','running','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `simulation_runs_run_uuid_unique` (`run_uuid`),
  KEY `simulation_runs_agent_id_foreign` (`agent_id`),
  KEY `simulation_runs_attack_profile_id_foreign` (`attack_profile_id`),
  KEY `simulation_runs_status_started_at_index` (`status`,`started_at`),
  CONSTRAINT `simulation_runs_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `simulation_runs_attack_profile_id_foreign` FOREIGN KEY (`attack_profile_id`) REFERENCES `attack_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `value_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`),
  KEY `system_settings_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','analyst') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_05_18_000001_create_agents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_05_18_000002_create_managed_networks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_05_18_000003_create_discovered_hosts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_05_18_000004_create_monitored_paths_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_05_18_000005_create_network_shares_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_05_18_000006_create_attack_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_05_18_000007_create_detection_rules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_05_18_000008_create_detection_thresholds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_05_18_000009_create_protection_policies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_05_18_000010_create_system_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_05_18_000011_create_sensitive_extensions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_05_18_000012_create_incidents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_05_18_000013_create_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_05_18_000014_create_risk_snapshots_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_05_18_000015_create_alerts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_05_18_000016_create_protection_actions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_05_18_000017_create_protection_action_decisions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_05_18_000018_create_alert_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_05_18_000019_create_simulation_runs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_05_18_193237_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_05_19_010000_add_missing_config_link_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_05_19_011000_fix_protection_policies_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_05_19_012000_fix_runtime_enum_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_05_19_013000_fix_protection_action_decisions_enum',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_05_19_014000_add_monitoring_retirement_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_05_19_015000_fix_infrastructure_status_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_05_19_016000_link_hosts_to_agents_enrollment',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_05_19_017000_fix_infrastructure_pipeline_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_05_21_000001_backfill_system_settings_metadata_defaults',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_05_21_000002_backfill_detection_system_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_05_23_095951_add_enrollment_token_expires_at_to_agents_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_05_24_000001_add_agent_api_key_to_agents_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_05_24_000002_add_device_vendor_to_discovered_hosts',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_05_24_004241_fix_protection_actions_statuses_and_add_uuid',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_05_24_014043_add_enrollment_short_code_to_agents',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_05_24_083049_fix_detection_rules_dedup_and_add_suspicious_process',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_05_24_090001_fix_safety_setting_human_approval',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_05_24_090002_disable_phantom_detection_thresholds',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_05_24_100001_fix_mass_rename_rule_and_heartbeat_status',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_05_24_100002_fix_policy_duplicates_and_orphan_rules',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_05_24_110001_add_uuid_columns_to_incidents_and_alerts',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_05_24_110002_fix_hardcoded_rules_event_type_documentation',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_05_24_120001_fix_false_positives_and_command_pipeline',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_05_24_213003_fix_events_path_column_to_text',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_05_25_082146_add_role_to_users_table',14);
