/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: sip_manager
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0+deb12u2-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `sip_manager`
--

/*!40000 DROP DATABASE IF EXISTS `sip_manager`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sip_manager` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `sip_manager`;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event` varchar(100) NOT NULL,
  `details` varchar(500) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `level` enum('info','warning','error','success') NOT NULL DEFAULT 'info',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES
(1,'Trunk créé','OVH-SIP → sbc6.fr.sip.ovh:5060','Trunk',1,'success',1,'172.20.0.1','2026-04-01 18:38:25','2026-04-01 18:38:25'),
(2,'Trunk modifié','OVH-SIP','Trunk',1,'info',1,'172.20.0.1','2026-04-01 18:39:00','2026-04-01 18:39:00'),
(3,'Ligne créée','Extension 1001 — jean','SipLine',1,'success',1,'172.20.0.1','2026-04-01 19:40:05','2026-04-01 19:40:05'),
(4,'Statut modifié','Extension 1001 → online','SipLine',1,'info',1,'172.20.0.1','2026-04-01 19:40:10','2026-04-01 19:40:10'),
(5,'Statut modifié','Extension 1001 → offline','SipLine',1,'info',1,'172.20.0.1','2026-04-01 19:40:11','2026-04-01 19:40:11'),
(6,'Statut modifié','Extension 1001 → online','SipLine',1,'info',1,'172.20.0.1','2026-04-01 19:40:12','2026-04-01 19:40:12'),
(7,'Statut modifié','Extension 1001 → offline','SipLine',1,'info',1,'172.20.0.1','2026-04-01 19:40:13','2026-04-01 19:40:13'),
(8,'Statut modifié','Extension 1001 → online','SipLine',1,'info',1,'172.20.0.1','2026-04-01 19:40:14','2026-04-01 19:40:14'),
(9,'Contexte desactive','from-trunk-ovh','CallContext',6,'info',1,'172.20.0.1','2026-04-01 20:56:31','2026-04-01 20:56:31'),
(10,'Contexte desactive','from-trunk','CallContext',2,'info',1,'172.20.0.1','2026-04-01 20:56:40','2026-04-01 20:56:40'),
(11,'Contexte desactive','from-internal','CallContext',1,'info',1,'172.20.0.1','2026-04-01 21:06:10','2026-04-01 21:06:10'),
(12,'Contexte active','from-trunk-ovh','CallContext',6,'info',1,'172.20.0.1','2026-04-01 21:09:08','2026-04-01 21:09:08'),
(13,'Contexte active','from-trunk','CallContext',2,'info',1,'172.20.0.1','2026-04-01 21:09:42','2026-04-01 21:09:42'),
(14,'Contexte supprime','from-trunk-ovh',NULL,NULL,'warning',1,'172.20.0.1','2026-04-01 21:16:07','2026-04-01 21:16:07'),
(15,'Contexte supprime','from-trunk',NULL,NULL,'warning',1,'172.20.0.1','2026-04-01 21:16:16','2026-04-01 21:16:16'),
(16,'Ligne modifiée','Extension 1001','SipLine',1,'info',1,'172.20.0.1','2026-04-02 04:50:22','2026-04-02 04:50:22'),
(17,'Ligne modifiée','Extension 1001','SipLine',1,'info',1,'172.20.0.1','2026-04-02 04:56:11','2026-04-02 04:56:11'),
(18,'Ligne modifiée','Extension 1001','SipLine',1,'info',1,'172.20.0.1','2026-04-02 04:59:43','2026-04-02 04:59:43');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_contexts`
--

DROP TABLE IF EXISTS `call_contexts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_contexts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `direction` enum('inbound','outbound','internal') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `dial_pattern` text DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `destination_type` varchar(255) NOT NULL DEFAULT 'extensions',
  `trunk_id` varchar(255) DEFAULT NULL,
  `caller_id_override` varchar(255) DEFAULT NULL,
  `prefix_strip` varchar(255) DEFAULT NULL,
  `prefix_add` varchar(255) DEFAULT NULL,
  `timeout` int(11) NOT NULL DEFAULT 30,
  `ring_timeout` int(11) NOT NULL DEFAULT 25,
  `record_calls` tinyint(1) NOT NULL DEFAULT 0,
  `voicemail_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `voicemail_box` varchar(255) DEFAULT NULL,
  `greeting_sound` varchar(255) DEFAULT NULL,
  `music_on_hold` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 10,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_contexts_name_unique` (`name`),
  KEY `call_contexts_created_by_foreign` (`created_by`),
  CONSTRAINT `call_contexts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_contexts`
--

LOCK TABLES `call_contexts` WRITE;
/*!40000 ALTER TABLE `call_contexts` DISABLE KEYS */;
INSERT INTO `call_contexts` VALUES
(1,'from-internal','internal','Appels entre extensions internes','_1XXX',NULL,'extensions',NULL,NULL,NULL,NULL,30,25,0,1,'${EXTEN}@default',NULL,NULL,0,1,NULL,NULL,'2026-04-01 18:48:06','2026-04-01 21:06:10'),
(3,'outbound-national','outbound','Appels sortants nationaux (0X...)','_0XXXXXXXXX',NULL,'trunk','1',NULL,'0','+33',45,25,0,0,NULL,NULL,NULL,1,5,NULL,NULL,'2026-04-01 18:48:06','2026-04-01 18:58:58'),
(4,'outbound-international','outbound','Appels sortants internationaux (+...)','_+X.',NULL,'trunk','1',NULL,NULL,NULL,45,25,0,0,NULL,NULL,NULL,1,6,NULL,NULL,'2026-04-01 18:48:06','2026-04-01 18:58:58'),
(5,'outbound-urgences','outbound','Numeros d\'urgence (15, 17, 18, 112, 114, 115, 119, 191, 196)','_1X',NULL,'trunk','1',NULL,NULL,NULL,60,25,0,0,NULL,NULL,NULL,1,1,NULL,NULL,'2026-04-01 18:48:06','2026-04-01 18:58:58');
/*!40000 ALTER TABLE `call_contexts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_flows`
--

DROP TABLE IF EXISTS `call_flows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_flows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `trunk_id` bigint(20) unsigned NOT NULL,
  `inbound_context` varchar(50) NOT NULL,
  `steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`steps`)),
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 50,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_flows_name_unique` (`name`),
  KEY `call_flows_trunk_id_foreign` (`trunk_id`),
  KEY `call_flows_created_by_foreign` (`created_by`),
  CONSTRAINT `call_flows_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `call_flows_trunk_id_foreign` FOREIGN KEY (`trunk_id`) REFERENCES `trunks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_flows`
--

LOCK TABLES `call_flows` WRITE;
/*!40000 ALTER TABLE `call_flows` DISABLE KEYS */;
INSERT INTO `call_flows` VALUES
(5,'accueil-standard','Appel entrant : sonnerie sur poste 1001, puis repondeur si pas de reponse',1,'from-trunk-ovh-sip','[{\"type\":\"answer\",\"wait\":1},{\"type\":\"queue\",\"queue_name\":\"test\",\"timeout\":60},{\"type\":\"hangup\"}]',1,10,NULL,'2026-04-02 00:27:32','2026-04-02 01:15:11');
/*!40000 ALTER TABLE `call_flows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_logs`
--

DROP TABLE IF EXISTS `call_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uniqueid` varchar(150) NOT NULL,
  `src` varchar(80) DEFAULT NULL,
  `dst` varchar(80) DEFAULT NULL,
  `src_name` varchar(255) DEFAULT NULL,
  `context` varchar(80) DEFAULT NULL,
  `channel` varchar(200) DEFAULT NULL,
  `dst_channel` varchar(200) DEFAULT NULL,
  `direction` enum('inbound','outbound','internal') NOT NULL DEFAULT 'internal',
  `trunk_name` varchar(255) DEFAULT NULL,
  `disposition` enum('ANSWERED','NO ANSWER','BUSY','FAILED','CONGESTION') NOT NULL DEFAULT 'NO ANSWER',
  `duration` int(11) NOT NULL DEFAULT 0,
  `billsec` int(11) NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `recording_file` varchar(255) DEFAULT NULL,
  `hangup_cause` varchar(255) DEFAULT NULL,
  `extra` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `call_logs_src_index` (`src`),
  KEY `call_logs_dst_index` (`dst`),
  KEY `call_logs_started_at_index` (`started_at`),
  KEY `call_logs_disposition_index` (`disposition`),
  KEY `call_logs_uniqueid_index` (`uniqueid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_logs`
--

LOCK TABLES `call_logs` WRITE;
/*!40000 ALTER TABLE `call_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `call_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_queues`
--

DROP TABLE IF EXISTS `call_queues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_queues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(150) DEFAULT NULL,
  `strategy` varchar(30) NOT NULL DEFAULT 'ringall',
  `timeout` int(11) NOT NULL DEFAULT 30,
  `retry` int(11) NOT NULL DEFAULT 5,
  `max_wait_time` int(11) NOT NULL DEFAULT 300,
  `music_on_hold` varchar(80) NOT NULL DEFAULT 'default',
  `announce_frequency` varchar(10) DEFAULT NULL,
  `announce_holdtime` varchar(3) NOT NULL DEFAULT 'yes',
  `members` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`members`)),
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_queues_name_unique` (`name`),
  KEY `call_queues_created_by_foreign` (`created_by`),
  CONSTRAINT `call_queues_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_queues`
--

LOCK TABLES `call_queues` WRITE;
/*!40000 ALTER TABLE `call_queues` DISABLE KEYS */;
INSERT INTO `call_queues` VALUES
(2,'test','test','ringall',25,5,300,'default','0','no','[{\"extension\":\"1001\",\"penalty\":\"0\"}]',1,1,'2026-04-01 21:21:22','2026-04-01 21:21:22'),
(3,'accueil','Accueil','ringall',25,5,120,'default','0','no','[{\"extension\":\"1001\",\"penalty\":0}]',1,NULL,'2026-04-02 00:27:31','2026-04-02 00:27:31');
/*!40000 ALTER TABLE `call_queues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2024_01_01_000001_create_sip_lines_table',1),
(5,'2024_01_01_000002_create_trunks_table',1),
(6,'2024_01_01_000003_create_activity_logs_table',1),
(7,'2024_01_01_000004_create_sip_settings_table',1),
(8,'2024_01_01_000005_create_call_contexts_table',2),
(9,'2024_01_01_000006_create_call_logs_table',2),
(10,'2024_01_01_000007_add_voicemail_to_call_contexts',3),
(11,'2024_01_01_000008_add_inbound_ips_to_trunks',4),
(12,'2024_01_01_000009_create_call_flows_table',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sip_lines`
--

DROP TABLE IF EXISTS `sip_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sip_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `extension` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `secret` text NOT NULL,
  `protocol` enum('SIP/UDP','SIP/TCP','SIP/TLS','WebRTC') NOT NULL DEFAULT 'SIP/UDP',
  `caller_id` varchar(50) DEFAULT NULL,
  `context` varchar(50) NOT NULL DEFAULT 'from-internal',
  `codecs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`codecs`)),
  `status` enum('online','offline','busy') NOT NULL DEFAULT 'offline',
  `transport` varchar(50) NOT NULL DEFAULT 'transport-udp',
  `max_contacts` int(11) NOT NULL DEFAULT 1,
  `voicemail_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `voicemail_email` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sip_lines_extension_unique` (`extension`),
  KEY `sip_lines_created_by_foreign` (`created_by`),
  CONSTRAINT `sip_lines_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sip_lines`
--

LOCK TABLES `sip_lines` WRITE;
/*!40000 ALTER TABLE `sip_lines` DISABLE KEYS */;
INSERT INTO `sip_lines` VALUES
(1,'1001','1001','toto@d4.fr','eyJpdiI6ImxrMzB5eW9DNzJBM1F0V2NlL0JKL3c9PSIsInZhbHVlIjoiM2lzNFU2Q1ZmS1p2V0crWTVFL3dJUT09IiwibWFjIjoiOTJkMjZmMjZjMDM1N2I0NWQ5NDI4NjM3ZGY5MzQ2Y2JkMGEyZTY0YzJhYjhlNGQ5Mjc0YjNmOTUzOTY0ZmYyMiIsInRhZyI6IiJ9','SIP/UDP','0185095298','from-internal-ovh','[\"ulaw\",\"alaw\",\"g722\"]','online','transport-udp',1,1,NULL,'test',1,'2026-04-01 19:40:05','2026-04-02 04:59:43',NULL);
/*!40000 ALTER TABLE `sip_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sip_settings`
--

DROP TABLE IF EXISTS `sip_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sip_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(50) NOT NULL DEFAULT 'general',
  `type` varchar(20) NOT NULL DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sip_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sip_settings`
--

LOCK TABLES `sip_settings` WRITE;
/*!40000 ALTER TABLE `sip_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `sip_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trunks`
--

DROP TABLE IF EXISTS `trunks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trunks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('SIP','IAX','PRI') NOT NULL DEFAULT 'SIP',
  `transport` enum('UDP','TCP','TLS') NOT NULL DEFAULT 'UDP',
  `host` varchar(255) NOT NULL,
  `port` int(10) unsigned NOT NULL DEFAULT 5060,
  `username` varchar(100) DEFAULT NULL,
  `secret` text DEFAULT NULL,
  `max_channels` int(10) unsigned NOT NULL DEFAULT 30,
  `codecs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`codecs`)),
  `caller_id` varchar(50) DEFAULT NULL,
  `context` varchar(50) NOT NULL DEFAULT 'from-trunk',
  `inbound_ips` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inbound_ips`)),
  `inbound_context` varchar(50) DEFAULT NULL,
  `status` enum('online','offline','error') NOT NULL DEFAULT 'offline',
  `register` tinyint(1) NOT NULL DEFAULT 1,
  `retry_interval` int(10) unsigned NOT NULL DEFAULT 60,
  `expiration` int(10) unsigned NOT NULL DEFAULT 3600,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trunks_name_unique` (`name`),
  KEY `trunks_created_by_foreign` (`created_by`),
  CONSTRAINT `trunks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trunks`
--

LOCK TABLES `trunks` WRITE;
/*!40000 ALTER TABLE `trunks` DISABLE KEYS */;
INSERT INTO `trunks` VALUES
(1,'OVH-SIP','SIP','UDP','sbc6.fr.sip.ovh',5060,'0033185090002','eyJpdiI6InBaQ0FnejF0cWdrdUZrb1JFbFZqekE9PSIsInZhbHVlIjoiK0Vmbi9LcU43bjBoRjhiTFd2cytES2hOdnZzTWI5QlNHRXdBN2ZadUVLbz0iLCJtYWMiOiI0YzBmZDEyMDI0YTgwMjZjZTFhZTEyYmM1MGEwN2JjMDAyZDI0YjY2Yjg5NDE2MzMwZTFmZTczYjBlZjI2ZjIwIiwidGFnIjoiIn0=',30,'[\"ulaw\",\"alaw\",\"g722\",\"g729\",\"opus\",\"gsm\",\"ilbc\",\"speex\"]','+33185090002','from-trunk-ovh','[\"91.121.129.0\\/24\",\"91.121.128.0\\/24\"]','from-trunk-ovh-sip','online',1,60,3600,NULL,1,'2026-04-01 18:38:25','2026-04-01 21:19:55',NULL);
/*!40000 ALTER TABLE `trunks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Admin','admin@sip.local',NULL,'$2y$12$GX9g/w6XTt8lepMcqW5EEO8Oyue58TaY5zGzLhXlQaauCKX59s6IW',NULL,'2026-04-01 17:57:46','2026-04-01 17:57:46');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Current Database: `asterisk_rt`
--

/*!40000 DROP DATABASE IF EXISTS `asterisk_rt`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `asterisk_rt` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `asterisk_rt`;

--
-- Table structure for table `ps_aors`
--

DROP TABLE IF EXISTS `ps_aors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ps_aors` (
  `id` varchar(40) NOT NULL,
  `max_contacts` int(11) DEFAULT 1,
  `remove_existing` varchar(3) DEFAULT 'yes',
  `default_expiration` int(11) DEFAULT 3600,
  `qualify_frequency` int(11) DEFAULT 60,
  `contact` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ps_aors`
--

LOCK TABLES `ps_aors` WRITE;
/*!40000 ALTER TABLE `ps_aors` DISABLE KEYS */;
INSERT INTO `ps_aors` VALUES
('1001',1,'yes',3600,60,NULL),
('trunk-ovh-sip',1,'yes',3600,60,'sip:sbc6.fr.sip.ovh:5060'),
('trunk-ovh-sip-in',1,'yes',3600,0,NULL);
/*!40000 ALTER TABLE `ps_aors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ps_auths`
--

DROP TABLE IF EXISTS `ps_auths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ps_auths` (
  `id` varchar(40) NOT NULL,
  `auth_type` varchar(10) DEFAULT NULL,
  `username` varchar(40) DEFAULT NULL,
  `password` varchar(80) DEFAULT NULL,
  `realm` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ps_auths`
--

LOCK TABLES `ps_auths` WRITE;
/*!40000 ALTER TABLE `ps_auths` DISABLE KEYS */;
INSERT INTO `ps_auths` VALUES
('1001','userpass','1001','wawa2346',NULL),
('trunk-ovh-sip-auth','userpass','0033185090002','OaBLY9nMX3j9W46Y',NULL);
/*!40000 ALTER TABLE `ps_auths` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ps_domain_aliases`
--

DROP TABLE IF EXISTS `ps_domain_aliases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ps_domain_aliases` (
  `id` varchar(40) NOT NULL,
  `domain` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ps_domain_aliases`
--

LOCK TABLES `ps_domain_aliases` WRITE;
/*!40000 ALTER TABLE `ps_domain_aliases` DISABLE KEYS */;
/*!40000 ALTER TABLE `ps_domain_aliases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ps_endpoint_id_ips`
--

DROP TABLE IF EXISTS `ps_endpoint_id_ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ps_endpoint_id_ips` (
  `id` varchar(40) NOT NULL,
  `endpoint` varchar(40) DEFAULT NULL,
  `match` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ps_endpoint_id_ips`
--

LOCK TABLES `ps_endpoint_id_ips` WRITE;
/*!40000 ALTER TABLE `ps_endpoint_id_ips` DISABLE KEYS */;
INSERT INTO `ps_endpoint_id_ips` VALUES
('ovh-sip-ip-1','trunk-ovh-sip-in','91.121.129.0/24'),
('ovh-sip-ip-2','trunk-ovh-sip-in','91.121.128.0/24');
/*!40000 ALTER TABLE `ps_endpoint_id_ips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ps_endpoints`
--

DROP TABLE IF EXISTS `ps_endpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ps_endpoints` (
  `id` varchar(40) NOT NULL,
  `transport` varchar(40) DEFAULT NULL,
  `aors` varchar(200) DEFAULT NULL,
  `auth` varchar(40) DEFAULT NULL,
  `context` varchar(40) DEFAULT NULL,
  `disallow` varchar(200) DEFAULT NULL,
  `allow` varchar(200) DEFAULT NULL,
  `direct_media` varchar(3) DEFAULT NULL,
  `force_rport` varchar(3) DEFAULT NULL,
  `rewrite_contact` varchar(3) DEFAULT NULL,
  `rtp_symmetric` varchar(3) DEFAULT NULL,
  `callerid` varchar(200) DEFAULT NULL,
  `dtmf_mode` varchar(10) DEFAULT NULL,
  `media_encryption` varchar(10) DEFAULT NULL,
  `ice_support` varchar(3) DEFAULT NULL,
  `from_user` varchar(40) DEFAULT NULL,
  `from_domain` varchar(40) DEFAULT NULL,
  `trust_id_inbound` varchar(3) DEFAULT NULL,
  `device_state_busy_at` int(11) DEFAULT 1,
  `language` varchar(10) DEFAULT 'fr',
  `mailboxes` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ps_endpoints`
--

LOCK TABLES `ps_endpoints` WRITE;
/*!40000 ALTER TABLE `ps_endpoints` DISABLE KEYS */;
INSERT INTO `ps_endpoints` VALUES
('1001','','1001','1001','from-internal-ovh','all','ulaw,alaw,g722','no','yes','yes','yes','\"1001\" <0185095298>','rfc4733','no','no',NULL,NULL,NULL,1,'fr',NULL),
('trunk-ovh-sip','transport-udp','trunk-ovh-sip','trunk-ovh-sip-auth','from-trunk-ovh','all','ulaw,alaw,g722,g729,opus,gsm,ilbc,speex','no','yes','yes',NULL,'+33185090002','rfc4733',NULL,NULL,'0033185090002','sbc6.fr.sip.ovh','yes',1,'fr',NULL),
('trunk-ovh-sip-in','transport-udp',NULL,NULL,'from-trunk-ovh-sip','all','ulaw,alaw,g722,g729,opus,gsm,ilbc,speex','no','yes','yes','yes',NULL,'rfc4733',NULL,NULL,NULL,NULL,'yes',1,'fr',NULL);
/*!40000 ALTER TABLE `ps_endpoints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ps_registrations`
--

DROP TABLE IF EXISTS `ps_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ps_registrations` (
  `id` varchar(40) NOT NULL,
  `transport` varchar(40) DEFAULT NULL,
  `outbound_auth` varchar(40) DEFAULT NULL,
  `server_uri` varchar(255) DEFAULT NULL,
  `client_uri` varchar(255) DEFAULT NULL,
  `retry_interval` int(11) DEFAULT 60,
  `expiration` int(11) DEFAULT 3600,
  `contact_user` varchar(40) DEFAULT NULL,
  `line` varchar(3) DEFAULT NULL,
  `endpoint` varchar(40) DEFAULT NULL,
  `auth_rejection_permanent` varchar(3) DEFAULT 'no',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ps_registrations`
--

LOCK TABLES `ps_registrations` WRITE;
/*!40000 ALTER TABLE `ps_registrations` DISABLE KEYS */;
INSERT INTO `ps_registrations` VALUES
('trunk-ovh-sip-reg','transport-udp','trunk-ovh-sip-auth','sip:sbc6.fr.sip.ovh:5060','sip:0033185090002@sbc6.fr.sip.ovh:5060',60,3600,'0033185090002','no','','no');
/*!40000 ALTER TABLE `ps_registrations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-02 16:56:03
