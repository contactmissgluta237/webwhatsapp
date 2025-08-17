-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: web-whatsapp-saas
-- ------------------------------------------------------
-- Server version	8.0.43-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ai_contexts`
--

DROP TABLE IF EXISTS `ai_contexts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_contexts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `whatsapp_account_id` bigint unsigned NOT NULL,
  `business_context` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_tone` enum('friendly','professional','casual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'friendly',
  `greeting_message` text COLLATE utf8mb4_unicode_ci,
  `fallback_message` text COLLATE utf8mb4_unicode_ci,
  `auto_reply_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `response_delay_seconds` int NOT NULL DEFAULT '2',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_contexts_whatsapp_account_id_foreign` (`whatsapp_account_id`),
  CONSTRAINT `ai_contexts_whatsapp_account_id_foreign` FOREIGN KEY (`whatsapp_account_id`) REFERENCES `whatsapp_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_contexts`
--

LOCK TABLES `ai_contexts` WRITE;
/*!40000 ALTER TABLE `ai_contexts` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_contexts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_models`
--

DROP TABLE IF EXISTS `ai_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_models` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `endpoint_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_api_key` tinyint(1) NOT NULL DEFAULT '1',
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_config` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `cost_per_1k_tokens` decimal(10,6) DEFAULT NULL,
  `max_context_length` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_models_is_active_is_default_index` (`is_active`,`is_default`),
  KEY `ai_models_provider_index` (`provider`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_models`
--

LOCK TABLES `ai_models` WRITE;
/*!40000 ALTER TABLE `ai_models` DISABLE KEYS */;
INSERT INTO `ai_models` VALUES (1,'GenericIA (Ollama Gemma2)','ollama','gemma2:2b','Modèle IA interne optimisé pour les conversations WhatsApp. Rapide, efficace et économique. Idéal pour débuter.','http://209.126.83.125:11434',0,NULL,'{\"top_p\": 0.9, \"max_tokens\": 1000, \"temperature\": 0.7}',1,0,0.000000,8192,'2025-08-14 16:57:47','2025-08-14 16:57:47'),(2,'ChatGPT 4o-mini','openai','gpt-4o-mini','Modèle OpenAI rapide et économique, excellent pour les conversations générales et le support client.','https://api.openai.com/v1',1,NULL,'{\"max_tokens\": 1500, \"temperature\": 0.7, \"presence_penalty\": 0, \"frequency_penalty\": 0}',1,0,0.000150,128000,'2025-08-14 16:57:47','2025-08-14 16:57:47'),(3,'Claude 3.5 Sonnet','anthropic','claude-3-5-sonnet-20241022','Modèle Anthropic avancé, excellent pour les conversations naturelles et les tâches complexes.','https://api.anthropic.com/v1',1,NULL,'{\"top_p\": 0.9, \"max_tokens\": 1500, \"temperature\": 0.7}',1,0,0.003000,200000,'2025-08-14 16:57:47','2025-08-14 16:57:47'),(4,'DeepSeek Chat','deepseek','deepseek-chat','Modèle DeepSeek polyvalent et performant pour diverses tâches conversationnelles.','https://api.deepseek.com/v1',1,'sk-56e24ec85bc54bcfb81ecf7cc1cae631','{\"top_p\": 0.95, \"max_tokens\": 1500, \"temperature\": 0.7}',1,1,0.000140,32000,'2025-08-14 16:57:47','2025-08-14 16:57:47');
/*!40000 ALTER TABLE `ai_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('web_whatsapp_saas_cache_spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:38:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:10:\"users.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:12:\"users.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:10:\"users.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"users.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:18:\"users.manage_roles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:14:\"customers.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:14:\"customers.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:16:\"customers.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:26:\"transactions.view_external\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:26:\"transactions.view_internal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:28:\"transactions.create_recharge\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:30:\"transactions.create_withdrawal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:31:\"transactions.approve_withdrawal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:30:\"transactions.reject_withdrawal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:37:\"transactions.create_customer_recharge\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:39:\"transactions.create_customer_withdrawal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:29:\"system_accounts.view_balances\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:33:\"system_accounts.view_transactions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:24:\"system_accounts.recharge\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:24:\"system_accounts.withdraw\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:22:\"system_accounts.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:14:\"dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:12:\"profile.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:12:\"profile.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:13:\"settings.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:13:\"settings.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:14:\"referrals.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:16:\"referrals.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:11:\"wallet.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:15:\"wallet.recharge\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:15:\"wallet.withdraw\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:11:\"orders.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:12:\"tickets.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:14:\"tickets.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:13:\"tickets.reply\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:13:\"tickets.close\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:14:\"tickets.assign\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:21:\"tickets.change_status\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}}s:5:\"roles\";a:3:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:11:\"super-admin\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:8:\"customer\";s:1:\"c\";s:3:\"web\";}}}',1755376746);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
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
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cities_country_id_name_unique` (`country_id`,`name`),
  CONSTRAINT `cities_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cities`
--

LOCK TABLES `cities` WRITE;
/*!40000 ALTER TABLE `cities` DISABLE KEYS */;
/*!40000 ALTER TABLE `cities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `whatsapp_account_id` bigint unsigned NOT NULL,
  `chat_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT '0',
  `last_message_at` timestamp NULL DEFAULT NULL,
  `unread_count` int NOT NULL DEFAULT '0',
  `is_ai_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversations_whatsapp_account_id_foreign` (`whatsapp_account_id`),
  CONSTRAINT `conversations_whatsapp_account_id_foreign` FOREIGN KEY (`whatsapp_account_id`) REFERENCES `whatsapp_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
INSERT INTO `conversations` VALUES (1,5,'23755332183@c.us','23755332183',NULL,0,'2025-08-16 09:44:02',4,1,'2025-08-16 08:34:32','2025-08-16 09:44:02'),(2,5,'120363421007042366@g.us','120363421007042366',NULL,1,'2025-08-16 08:35:04',1,1,'2025-08-16 08:35:04','2025-08-16 08:35:04'),(3,5,'23799949264-1521397278@g.us','23799949264-1521397278',NULL,1,'2025-08-16 08:35:32',1,1,'2025-08-16 08:35:32','2025-08-16 08:35:32'),(4,5,'23790128226-1501499582@g.us','23790128226-1501499582',NULL,1,'2025-08-16 08:36:08',1,1,'2025-08-16 08:36:08','2025-08-16 08:36:08'),(5,5,'23775448704-1498832223@g.us','23775448704-1498832223',NULL,1,'2025-08-16 09:40:33',22,1,'2025-08-16 08:46:16','2025-08-16 09:40:33'),(6,5,'243974186886-1593969886@g.us','243974186886-1593969886',NULL,1,'2025-08-16 09:34:59',2,1,'2025-08-16 08:56:56','2025-08-16 09:34:59'),(7,5,'919729664876-1553310155@g.us','919729664876-1553310155',NULL,1,'2025-08-16 09:04:58',2,1,'2025-08-16 09:04:58','2025-08-16 09:04:58'),(8,5,'237676636794@c.us','237676636794',NULL,0,'2025-08-16 09:34:58',4,1,'2025-08-16 09:20:05','2025-08-16 09:34:58'),(9,5,'23798656678-1522443414@g.us','23798656678-1522443414',NULL,1,'2025-08-16 09:24:23',2,1,'2025-08-16 09:24:23','2025-08-16 09:24:23'),(10,5,'237696062739@c.us','237696062739',NULL,0,'2025-08-16 09:46:30',7,1,'2025-08-16 09:29:12','2025-08-16 09:46:30'),(11,5,'23795826588-1546982304@g.us','23795826588-1546982304',NULL,1,'2025-08-16 09:40:01',6,1,'2025-08-16 09:31:07','2025-08-16 09:40:01'),(12,5,'23794033025-1528445424@g.us','23794033025-1528445424',NULL,1,'2025-08-16 09:31:48',2,1,'2025-08-16 09:31:48','2025-08-16 09:31:48'),(13,5,'23791278891@c.us','23791278891',NULL,0,'2025-08-16 09:38:25',4,1,'2025-08-16 09:33:21','2025-08-16 09:38:25'),(14,5,'23793971089@c.us','23793971089',NULL,0,'2025-08-16 15:58:35',2,1,'2025-08-16 15:58:35','2025-08-16 15:58:35'),(15,5,'12404237945@c.us','12404237945',NULL,0,'2025-08-16 16:09:01',3,1,'2025-08-16 16:08:16','2025-08-16 16:09:01');
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_emoji` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '999',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_code_unique` (`code`),
  KEY `countries_is_active_sort_order_index` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'Cameroun','CM','+237','🇨🇲',1,1,'2025-08-14 16:57:39','2025-08-14 16:57:39'),(2,'Sénégal','SN','+221','🇸🇳',1,2,'2025-08-14 16:57:39','2025-08-14 16:57:39'),(3,'Côte d\'Ivoire','CI','+225','🇨🇮',1,3,'2025-08-14 16:57:39','2025-08-14 16:57:39'),(4,'Mali','ML','+223','🇲🇱',1,4,'2025-08-14 16:57:39','2025-08-14 16:57:39'),(5,'Burkina Faso','BF','+226','🇧🇫',1,5,'2025-08-14 16:57:39','2025-08-14 16:57:39'),(6,'France','FR','+33','🇫🇷',1,10,'2025-08-14 16:57:39','2025-08-14 16:57:39'),(7,'Canada','CA','+1','🇨🇦',1,11,'2025-08-14 16:57:39','2025-08-14 16:57:39'),(8,'États-Unis','US','+1','🇺🇸',1,12,'2025-08-14 16:57:39','2025-08-14 16:57:39'),(9,'Nigeria','NG','+234','🇳🇬',1,20,'2025-08-14 16:57:39','2025-08-14 16:57:39'),(10,'Ghana','GH','+233','🇬🇭',1,21,'2025-08-14 16:57:39','2025-08-14 16:57:39'),(11,'Kenya','KE','+254','🇰🇪',1,22,'2025-08-14 16:57:39','2025-08-14 16:57:39');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_user_id_foreign` (`user_id`),
  CONSTRAINT `customers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,2,'2025-08-14 16:57:40','2025-08-14 16:57:40'),(2,3,'2025-08-14 16:57:40','2025-08-14 16:57:40'),(3,4,'2025-08-14 16:57:41','2025-08-14 16:57:41'),(4,5,'2025-08-14 16:57:41','2025-08-14 16:57:41'),(5,6,'2025-08-14 16:57:41','2025-08-14 16:57:41'),(6,7,'2025-08-14 16:57:41','2025-08-14 16:57:41'),(7,8,'2025-08-14 16:57:42','2025-08-14 16:57:42'),(8,9,'2025-08-14 16:57:42','2025-08-14 16:57:42'),(9,10,'2025-08-14 16:57:42','2025-08-14 16:57:42'),(10,11,'2025-08-14 16:57:42','2025-08-14 16:57:42'),(11,12,'2025-08-14 16:57:42','2025-08-14 16:57:42'),(12,13,'2025-08-14 16:57:43','2025-08-14 16:57:43'),(13,14,'2025-08-14 16:57:43','2025-08-14 16:57:43'),(14,15,'2025-08-14 16:57:43','2025-08-14 16:57:43'),(15,16,'2025-08-14 16:57:43','2025-08-14 16:57:43'),(16,17,'2025-08-14 16:57:44','2025-08-14 16:57:44'),(17,18,'2025-08-14 16:57:44','2025-08-14 16:57:44'),(18,19,'2025-08-14 16:57:44','2025-08-14 16:57:44'),(19,20,'2025-08-14 16:57:44','2025-08-14 16:57:44'),(20,21,'2025-08-14 16:57:45','2025-08-14 16:57:45'),(21,22,'2025-08-14 16:57:45','2025-08-14 16:57:45'),(22,23,'2025-08-14 16:57:45','2025-08-14 16:57:45'),(23,24,'2025-08-14 16:57:45','2025-08-14 16:57:45'),(24,25,'2025-08-14 16:57:45','2025-08-14 16:57:45'),(25,26,'2025-08-14 16:57:46','2025-08-14 16:57:46'),(26,27,'2025-08-14 16:57:46','2025-08-14 16:57:46'),(27,28,'2025-08-14 16:57:46','2025-08-14 16:57:46'),(28,29,'2025-08-14 16:57:46','2025-08-14 16:57:46'),(29,30,'2025-08-14 16:57:47','2025-08-14 16:57:47'),(30,31,'2025-08-14 16:57:47','2025-08-14 16:57:47');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `external_transactions`
--

DROP TABLE IF EXISTS `external_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('recharge','withdrawal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` enum('automatic','manual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `payment_method` enum('mobile_money','orange_money','bank_card','cash') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_response` text COLLATE utf8mb4_unicode_ci,
  `sender_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiver_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiver_account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_external_transaction` (`external_transaction_id`,`payment_method`),
  KEY `external_transactions_created_by_foreign` (`created_by`),
  KEY `external_transactions_wallet_id_transaction_type_index` (`wallet_id`,`transaction_type`),
  KEY `external_transactions_payment_method_index` (`payment_method`),
  KEY `external_transactions_status_index` (`status`),
  KEY `external_transactions_approved_by_index` (`approved_by`),
  CONSTRAINT `external_transactions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `external_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `external_transactions_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `external_transactions`
--

LOCK TABLES `external_transactions` WRITE;
/*!40000 ALTER TABLE `external_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `external_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
-- Table structure for table `internal_transactions`
--

DROP TABLE IF EXISTS `internal_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `internal_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('credit','debit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `related_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` bigint unsigned DEFAULT NULL,
  `recipient_user_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `internal_transactions_recipient_user_id_foreign` (`recipient_user_id`),
  KEY `internal_transactions_created_by_foreign` (`created_by`),
  KEY `internal_transactions_wallet_id_transaction_type_index` (`wallet_id`,`transaction_type`),
  KEY `internal_transactions_wallet_id_status_index` (`wallet_id`,`status`),
  KEY `internal_transactions_related_type_related_id_index` (`related_type`,`related_id`),
  CONSTRAINT `internal_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `internal_transactions_recipient_user_id_foreign` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `internal_transactions_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internal_transactions`
--

LOCK TABLES `internal_transactions` WRITE;
/*!40000 ALTER TABLE `internal_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `internal_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
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
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `whatsapp_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direction` enum('inbound','outbound') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_type` enum('text','image','document','audio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `is_ai_generated` tinyint(1) NOT NULL DEFAULT '0',
  `ai_model_used` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_confidence` float DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_conversation_id_foreign` (`conversation_id`),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,7,'false_919729664876-1553310155@g.us_3EB02CFFBC348B6A4710','inbound','2348063549016@c.us','text',0,NULL,NULL,NULL,'2025-08-16 09:04:58','2025-08-16 09:04:58'),(2,7,NULL,'outbound','Bonjour ! Je suis votre assistant WhatsApp professionnel. Comment puis-je vous aider aujourd\'hui ? \n\nPour une prise en charge optimale, merci de préciser votre demande. Je reste à votre disposition pour toute information ou assistance dont vous pourriez avoir besoin. \n\nCordialement,\n[Votre Assistant Professionnel]','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:05:06','2025-08-16 09:05:06','2025-08-16 09:05:06'),(3,5,'false_23775448704-1498832223@g.us_41DAFBE6BEEEF87EB66081A3A40C22B4_237691019849@c.us','inbound','Ludovic va pointer ce matchs. Qui dit mieux ?','text',0,NULL,NULL,NULL,'2025-08-16 09:06:41','2025-08-16 09:06:41'),(4,5,NULL,'outbound','Je comprends votre enthousiasme concernant Ludovic ! Pour vous répondre de manière professionnelle et utile :\n\n1. Orthographe : On écrit \"ce match\" (singulier) ou \"ces matchs\" (pluriel)\n2. Formulation alternative : \"Ludovic va assurer pendant ce match, personne ne fait mieux !\"\n\nSi vous souhaitez que je vous aide à formuler un message plus percutant ou professionnel, n\'hésitez pas à me dire dans quel contexte vous utilisez cette phrase (message privé, post public, etc.). Je me ferai un plaisir de vous aider à peaufiner votre communication. \n\nBonne journée à vous !','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:06:53','2025-08-16 09:06:53','2025-08-16 09:06:53'),(5,5,'false_23775448704-1498832223@g.us_BA31ACC9FEC6562D3DD541BB7F51FCB8_23775448704@c.us','inbound','Mais Pdt @54245571227689 est-ce que il est prévu un *Tournoi B* pour les *joueurs tombés* hier!? Ou alors...😂😂😂','text',0,NULL,NULL,NULL,'2025-08-16 09:08:33','2025-08-16 09:08:33'),(6,5,NULL,'outbound','Je vois que vous êtes passionné par l\'organisation des tournois ! Permettez-moi de vous répondre de manière claire et constructive :\n\n1. Correction linguistique :\n   - \"est-ce qu\'il\" (élision requise)\n   - \"les joueurs éliminés\" serait plus approprié que \"tombés\"\n\n2. Concernant votre question :\n   Malheureusement, je n\'ai pas d\'information sur l\'organisation d\'un éventuel Tournoi B. Pour obtenir une réponse précise, je vous recommande de :\n   - Contacter directement l\'organisateur (@54245571227689)\n   - Consulter le règlement officiel du tournoi\n   - Vérifier les annonces récentes des administrateurs\n\nSi vous souhaitez que je vous aide à formuler une demande officielle ou à rédiger un message plus structuré, n\'hésitez pas à me le dire. Je reste à votre disposition pour toute assistance complémentaire.\n\nBonne continuation dans la compétition !','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:08:49','2025-08-16 09:08:49','2025-08-16 09:08:49'),(7,5,'false_23775448704-1498832223@g.us_F6FF91FEC096AF5B07C4EA95B1B1C810_23775448704@c.us','inbound','Non non Knoxi, c\'est retour au Village direct. \n\nIl y a même certains qui pensaient que 3000 d\'inscription c\'est 3 repas par jour. Donc on t\'élimine, tu reviens les autres jours pour tes repas😀😀😅\n\nD\'abord le repas c\'est plus de 1000 FCFA sans compter que tu as consommé aussi les pions, les damiers, pendules et autres de TALENTALIS😀😀😀','text',0,NULL,NULL,NULL,'2025-08-16 09:08:50','2025-08-16 09:08:50'),(8,5,NULL,'outbound','Je vois que vous avez le sens de l\'humour concernant l\'organisation du tournoi ! Permettez-moi de répondre avec sérieux tout en gardant une touche légère :\n\n1. Concernant les frais d\'inscription (3000 FCFA) :\n   - C\'est effectivement important de préciser ce qu\'ils incluent\n   - Une clarification officielle éviterait les malentendus sur les repas\n\n2. Matériel utilisé :\n   - Vous soulevez un point pertinent sur l\'usure du matériel (pions, damiers, pendules)\n   - Cela fait partie des coûts cachés d\'organisation\n\nPour améliorer la transparence, je suggérerais :\n✔️ Une communication claire sur ce que couvre l\'inscription\n✔️ Un rappel des règles concernant l\'utilisation du matériel\n✔️ Eventuellement un système de caution pour le matériel\n\nSi vous souhaitez que j\'aide à rédiger des propositions plus formelles pour les organisateurs, n\'hésitez pas ! Je reste à votre disposition pour toute assistance.\n\nBonne continuation dans la compétition (et bon appétit pour ceux qui restent ! 😉)','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:09:07','2025-08-16 09:09:07','2025-08-16 09:09:07'),(9,5,'false_23775448704-1498832223@g.us_99CDD3684C049806E667E786DF8D126F_23759188150@c.us','inbound','C\'est parti pour marquer mais il doit beaucoup travailler et manœuvrer. Empêcher que son adversaire se dégage et mettre un pion à 27 et maintenir la pression avec la percée à 24','text',0,NULL,NULL,NULL,'2025-08-16 09:09:27','2025-08-16 09:09:27'),(10,5,NULL,'outbound','Je comprends votre analyse stratégique du jeu ! Voici une réponse structurée pour accompagner votre réflexion :\n\n1. Votre stratégie identifiée :\n   - Blocage adverse (empêcher le dégagement)\n   - Positionnement clé (pion à 27) \n   - Pression continue (percée à 24)\n\n2. Suggestions complémentaires :\n   ✔️ Vérifier la sécurité des pions arrières\n   ✔️ Anticiper les contre-attaques possibles\n   ✔️ Calculer les variantes après la percée\n\n3. Pour approfondir :\n   - Ce plan rappelle la stratégie classique du \"jeu de blocage\"\n   - La case 27 est effectivement une position forte au milieu de jeu\n\nSi vous souhaitez :\n- Analyser plus en détail cette configuration\n- Recevoir des conseils sur l\'exécution technique\n- Préparer des variantes alternatives\n\nN\'hésitez pas à me demander ! Je peux vous aider à affiner cette stratégie ou à étudier des parties similaires pour inspiration.\n\nBonne continuation dans votre partie ! 🏆','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:09:44','2025-08-16 09:09:44','2025-08-16 09:09:44'),(11,5,'false_23775448704-1498832223@g.us_87F32561D9E1F1733DB8A0F9DD35037D_23759188150@c.us','inbound','😁😁😂😂😂 j\'ai vu boris qui rôde tout autour','text',0,NULL,NULL,NULL,'2025-08-16 09:10:38','2025-08-16 09:10:38'),(12,5,NULL,'outbound','Ah, Boris le rôdeur ! 😄 Je vois que l\'ambiance est détendue autour du tournoi. Permettez-moi de répondre avec professionnalisme et une pointe d\'humour :\n\n1. Concernant Boris :\n- Son attitude de \"rôder\" pourrait indiquer :\n  ✔️ Une analyse attentive des parties en cours\n  ✔️ Une préparation stratégique pour ses prochains matchs\n  ✔️ Ou simplement l\'envie de suivre la compétition !\n\n2. Pour rester concentré :\n- C\'est toujours bon de :\n  ✔️ Garder un œil sur son environnement\n  ✔️ Rester focalisé sur sa propre stratégie\n  ✔️ Profiter de l\'ambiance conviviale\n\nSi jamais vous souhaitez :\n- Analyser le style de jeu de Boris\n- Préparer une stratégie contre son approche\n- Ou simplement échanger sur l\'ambiance du tournoi\n\nJe suis à votre disposition ! En attendant, bon tournoi à tous... et à Boris aussi ! 😉🏆','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:10:56','2025-08-16 09:10:56','2025-08-16 09:10:56'),(13,5,'false_23775448704-1498832223@g.us_5CF5CC10B02052D7E288B7E9E3EA7D17_237691019849@c.us','inbound','Il va beaucoup travailler. Dans la position là, il faut seulement appliquer ses livres sans réfléchir. 😎','text',0,NULL,NULL,NULL,'2025-08-16 09:11:23','2025-08-16 09:11:23'),(14,5,NULL,'outbound','Je vois que vous avez une approche très méthodique du jeu ! Voici quelques réflexions sur votre stratégie :\n\n1. Application des connaissances théoriques :\n   - L\'avantage des \"livres\" : ✔️ Positions éprouvées ✔️ Stratégies fiables\n   - Mais attention à ne pas devenir trop prévisible !\n\n2. Équilibre à trouver :\n   ✔️ 70% de théorie (vos \"livres\")\n   ✔️ 30% d\'adaptation à la situation réelle\n   ✔️ Tougarder une marge de créativité\n\n3. Pour aller plus loin :\n   - Quels ouvrages spécifiques utilisez-vous ?\n   - Avez-vous des positions types favorites ?\n   - Comment adaptez-vous quand l\'adversaire sort des sentiers battus ?\n\nSi vous souhaitez approfondir :\n- Analyser des positions classiques\n- Étudier des variantes moins connues\n- Préparer des pièges théoriques\n\nJe reste à votre disposition pour échanger sur ces aspects techniques. Après tout, même les \"livres\" ont été écrits par des joueurs qui ont osé innover ! 😉📚♟️','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:11:40','2025-08-16 09:11:40','2025-08-16 09:11:40'),(15,5,'false_23775448704-1498832223@g.us_B7EC54ED0F16FBCF2F7C04EBFE08E6BA_23759188150@c.us','inbound','Tu as vu que c\'est lui qui risque d\'encaisser maintenant noh','text',0,NULL,NULL,NULL,'2025-08-16 09:13:29','2025-08-16 09:13:29'),(16,5,NULL,'outbound','Ah, la roue tourne ! 😄 Voici une analyse stratégique de la situation :\n\n1. Observation clé :\n   - Votre adversaire semble en difficulté\n   - La pression que vous avez mise porte ses fruits\n\n2. Points à considérer :\n   ✔️ Maintenir la concentration (ne pas relâcher trop tôt)\n   ✔️ Anticiper les contre-attaques désespérées\n   ✔️ Profiter de l\'avantage sans précipitation\n\n3. Stratégie recommandée :\n   - Continuer à appliquer votre plan initial\n   - Rester méthodique malgré l\'avantage\n   - Éviter les coups \"trop beaux pour être vrais\"\n\n\"En échecs comme au jacquet, le plus dangereux est souvent l\'adversaire acculé !\" \n\nSi vous voulez :\n- Analyser des fins de partie similaires\n- Préparer des variantes défensives\n- Ou simplement célébrer ce moment stratégique\n\nJe suis là ! Bonne continuation dans cette partie prometteuse. L\'important maintenant : ne rien lâcher ! 💪🏆','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:13:46','2025-08-16 09:13:46','2025-08-16 09:13:46'),(17,5,'false_23775448704-1498832223@g.us_D7E862F60C1ECF863DD618B99C1EE168_23759188150@c.us','inbound','Un joueur prévenu ne peut pas accepter prendre si facilement dans la semi-ouverte ( semi-classique)','text',0,NULL,NULL,NULL,'2025-08-16 09:14:16','2025-08-16 09:14:16'),(18,5,NULL,'outbound','Ah, vous touchez là à une subtilité stratégique passionnante ! Voici une analyse détaillée de cette situation :\n\n1. Le piège de la semi-ouverte :\n   ✔️ Position apparemment favorable\n   ✔️ Mais remplie de chausse-trapes pour le joueur imprudent\n   ✔️ Votre adversaire semble tomber dans le panneau\n\n2. Pour capitaliser :\n   - Exploiter l\'erreur sans se découvrir\n   - Préparer une contre-attaque sournoise\n   - Rester vigilant sur l\'ensemble du damier\n\n3. Rappel important :\n   \"Dans les ouvertures semi-classiques, la meilleure prise est souvent... celle qu\'on ne fait pas !\"\n\nSi vous souhaitez :\n- Analyser des exemples concrets de ce type de piège\n- Préparer des variantes encore plus subtiles\n- Ou simplement savourer ce moment tactique\n\nJe suis à votre disposition. Après tout, comme disait un grand maître : \"Les meilleures combinaisons sont celles que l\'adversaire joue pour nous !\" 😉♟️\n\nContinuez à jouer malin !','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:14:33','2025-08-16 09:14:33','2025-08-16 09:14:33'),(19,5,'false_23775448704-1498832223@g.us_52224601D8688E111F6EFCE52DAD0185_237691019849@c.us','inbound','🙆🙆🙆🙆, appliquer seulement les cours ?? Wouaaah','text',0,NULL,NULL,NULL,'2025-08-16 09:14:34','2025-08-16 09:14:34'),(20,5,NULL,'outbound','Ah, je sens votre enthousiasme et votre question pertinente ! 😊 Voici une réflexion nuancée sur l\'application des cours :\n\n1. La théorie vs la pratique :\n   ✔️ Les cours donnent une base solide\n   ✔️ Mais chaque partie est unique !\n   ✔️ L\'équilibre est la clé\n\n2. Pour progresser :\n   - Assimiler les principes fondamentaux\n   - Développer son intuition de jeu\n   - Oser sortir du cadre quand la situation l\'exige\n\n3. Mon conseil perso :\n   \"Apprenez les règles comme un professionnel, \n   puis jouez comme un artiste !\" 🎨♟️\n\nSi vous voulez :\n- Discuter de cas concrets où innover\n- Analyser des parties créatives\n- Trouver votre style personnel\n\nJe suis là pour en parler ! Après tout, même les plus grands maîtres ont dû un jour... lâcher leurs livres ! 😉📚➡️🎲','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:14:50','2025-08-16 09:14:50','2025-08-16 09:14:50'),(21,5,'false_23775448704-1498832223@g.us_A654F63E3F7E073C2DD314563B59F74D_237691019849@c.us','inbound','Il était déjà pris','text',0,NULL,NULL,NULL,'2025-08-16 09:14:55','2025-08-16 09:14:55'),(22,5,NULL,'outbound','Ah, la fameuse prise anticipée ! 😉 Voici comment analyser cette situation délicate :\n\n1. Le piège révélé :\n   ✔️ Votre adversaire a sous-estimé votre préparation\n   ✔️ La position semblait gagnante... mais était déjà contrôlée\n   ✔️ Classic case de \"trop beau pour être vrai\" !\n\n2. Pour capitaliser :\n   - Maintenir l\'avantage psychologique\n   - Rester vigilant sur les contre-attaques\n   - Profiter de l\'erreur sans arrogance\n\n3. Rappel stratégique :\n   \"En stratégie, ce qui paraît offert est souvent le plus dangereux\"\n\nProchaine étape ?\n- Analyser comment il s\'est fait piéger\n- Préparer d\'autres pièges similaires\n- Ou simplement savourer ce moment tactique\n\nJe reste à votre disposition pour approfondir. Après tout, comme disait Sun Tzu : \"Toute guerre est fondée sur la tromperie\" ! 😉♟️','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:15:11','2025-08-16 09:15:11','2025-08-16 09:15:11'),(23,5,'false_23775448704-1498832223@g.us_A302221DE00E46CD5139DE76B1B0F076_23759188150@c.us','inbound','Non. Il avait tellement le temps de se dégager. Tu as vu son tempérament ? Et joueur déjà pris on voit ça même avec sa tête','text',0,NULL,NULL,NULL,'2025-08-16 09:16:03','2025-08-16 09:16:03'),(24,5,NULL,'outbound','Ah, vous pointez du doigt quelque chose de fondamental dans le jeu ! Voici une analyse plus approfondie :\n\n1. Lecture du jeu ET du joueur :\n   ✔️ L\'aspect psychologique est capital\n   ✔️ Un joueur \"pris\" trahit son stress par son langage corporel\n   ✔️ Son tempérament influence ses choix tactiques\n\n2. Stratégie gagnante :\n   - Exploiter cette tension visible\n   - Maintenir la pression sans précipitation\n   - Laisser l\'adversaire s\'enfermer lui-même\n\n3. Vérité du jeu :\n   \"Les pièces ne mentent jamais, mais les visages parlent parfois plus que les coups !\" 😉\n\nSi vous voulez :\n- Approfondir l\'analyse psychologique du jeu\n- Étudier des cas concrets de \"tells\" comportementaux\n- Perfectionner votre lecture des adversaires\n\nJe suis là pour échanger. Après tout, comme disait Lasker : \"Sur l\'échiquier, mentir est impossible... mais les joueurs, eux, le font souvent !\" 😄♟️','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:16:19','2025-08-16 09:16:19','2025-08-16 09:16:19'),(25,1,'false_23755332183@c.us_3EB0890D9DE3A88E5AD209','inbound','mon grand 1','text',0,NULL,NULL,NULL,'2025-08-16 09:19:56','2025-08-16 09:19:56'),(26,1,NULL,'outbound','Je suis votre assistant WhatsApp professionnel. Pour mieux vous aider, pourriez-vous préciser votre demande ou votre question ? Je reste à votre disposition pour tout renseignement complémentaire.','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:20:02','2025-08-16 09:20:02','2025-08-16 09:20:02'),(27,8,'false_237676636794@c.us_3EB01C989D13B7532016F1','inbound','Je suis votre assistant WhatsApp professionnel. Pour mieux vous aider, pourriez-vous préciser votre demande ou votre question ? Je reste à votre disposition pour tout renseignement complémentaire.','text',0,NULL,NULL,NULL,'2025-08-16 09:20:05','2025-08-16 09:20:05'),(28,8,NULL,'outbound','Je comprends qu\'il semble y avoir une répétition dans votre message. Pour vous apporter la meilleure assistance possible, pourriez-vous reformuler ou préciser votre demande ? \n\nJe reste à votre écoute pour toute information complémentaire dont vous pourriez avoir besoin. N\'hésitez pas à me faire part de votre question ou de votre requête spécifique.','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:20:13','2025-08-16 09:20:13','2025-08-16 09:20:13'),(29,1,'false_23755332183@c.us_3EB0FCA30B7A692E00BB3B','inbound','Je comprends qu\'il semble y avoir une répétition dans votre message. Pour vous apporter la meilleure assistance possible, pourriez-vous reformuler ou préciser votre demande ? \n\nJe reste à votre écoute pour toute information complémentaire dont vous pourriez avoir besoin. N\'hésitez pas à me faire part de votre question ou de votre requête spécifique.','text',0,NULL,NULL,NULL,'2025-08-16 09:20:39','2025-08-16 09:20:39'),(30,1,NULL,'outbound','Je constate qu\'il y a effectivement une répétition dans notre échange. Permettez-moi de recentrer la conversation pour vous offrir un service optimal.\n\nPourriez-vous m\'indiquer précisément :\n1. La nature de votre demande\n2. Vos attentes spécifiques\n3. Toute information utile pour vous accompagner au mieux\n\nJe reste à votre disposition pour traiter votre requête de manière efficace et personnalisée.','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:20:49','2025-08-16 09:20:49','2025-08-16 09:20:49'),(31,8,'false_237676636794@c.us_3EB023BB8DF82635278B50','inbound','Je constate qu\'il y a effectivement une répétition dans notre échange. Permettez-moi de recentrer la conversation pour vous offrir un service optimal.\n\nPourriez-vous m\'indiquer précisément :\n1. La nature de votre demande\n2. Vos attentes spécifiques\n3. Toute information utile pour vous accompagner au mieux\n\nJe reste à votre disposition pour traiter votre requête de manière efficace et personnalisée.','text',0,NULL,NULL,NULL,'2025-08-16 09:20:51','2025-08-16 09:20:51'),(32,8,NULL,'outbound','Je note que nous tournons en cercle dans cet échange. Pour sortir de cette boucle et vous apporter une réelle valeur ajoutée, je vous propose concrètement :\n\n1. Soit vous formulez clairement votre demande ou question\n2. Soit vous me précisez le domaine dans lequel vous souhaitez être assisté (service client, informations produit, support technique, etc.)\n\nCette précision me permettra de :\n- Vous orienter vers le bon interlocuteur si nécessaire\n- Vous fournir une réponse complète et adaptée\n- Gagner du temps pour tous les deux\n\nJe reste bien entendu à votre disposition pour vous accompagner dès que vous m\'aurez communiqué ces éléments clés.','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:21:03','2025-08-16 09:21:03','2025-08-16 09:21:03'),(33,5,'false_23775448704-1498832223@g.us_DD48B6F11188BB8E98A93D3413320DBD_237699647586@c.us','inbound','C\'est un match nul','text',0,NULL,NULL,NULL,'2025-08-16 09:21:25','2025-08-16 09:21:25'),(34,5,NULL,'outbound','Ah, la sagesse du résultat équilibré ! 😊 Voici comment tirer le meilleur de cette situation :\n\n1. Analyse du match nul :\n   ✔️ Preuve d\'une belle résistance des deux côtés\n   ✔️ Signe que la partie était bien disputée\n   ✔️ Opportunité d\'apprentissage précieuse\n\n2. Leçons à retenir :\n   - Identifier les moments charnières\n   - Analyser les occasions manquées\n   - Capitaliser sur les bonnes décisions\n\n3. Perspective stratégique :\n   \"Un nul bien joué vaut parfois mieux qu\'une victoire facile !\"\n\nEt maintenant ?\n- Revoir les coups clés ensemble\n- Préparer la revanche\n- Célébrer ce beau duel d\'égal à égal\n\nJe reste à votre disposition pour approfondir l\'analyse. Comme disait Capablanca : \"Pour progresser, il faut d\'abord comprendre pourquoi on n\'a pas perdu !\" 😉🏆♟️','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:21:41','2025-08-16 09:21:41','2025-08-16 09:21:41'),(35,5,'false_23775448704-1498832223@g.us_7B39C129A1D253749BAE202E215A0AED_23759188150@c.us','inbound','Oui mais c\'est Ludovic qui doit chercher la remise maintenant','text',0,NULL,NULL,NULL,'2025-08-16 09:22:22','2025-08-16 09:22:22'),(36,5,NULL,'outbound','Ah, la pression change de camp ! Voici comment analyser ce renversement stratégique :  \n\n1. **Le retournement psychologique** :  \n   ✔️ Ludovic est désormais en position de demandeur  \n   ✔️ Votre avantage : vous contrôlez le tempo de la fin de partie  \n   ✔️ Son impatience pourrait devenir son point faible  \n\n2. **Stratégie pour maximiser l\'avantage** :  \n   - Rester impassible et méthodique  \n   - Lui laisser l\'initiative des propositions  \n   - Profiter de chaque hésitation pour consolider votre position  \n\n3. **Rappel essentiel** :  \n   *\"Dans les finales, celui qui doit prouver prend souvent des risques inconsidérés...\"*  \n\nProchaines étapes ?  \n- Étudier ses options de remise possibles  \n- Préparer des réponses à ses éventuelles offres  \n- Garder une longueur d\'avance sur son raisonnement  \n\nJe suis là pour affiner cette stratégie. Comme disait Tal : *\"La meilleure défense, c\'est de laisser l\'adversaire se défendre contre lui-même !\"* 😏♟️  \n\nÀ vous de jouer froidement !','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:22:40','2025-08-16 09:22:40','2025-08-16 09:22:40'),(37,5,'false_23775448704-1498832223@g.us_3F917C3A806CD97D7ECD248ACB6AC070_242064603899@c.us','inbound','https://www.facebook.com/eiffel.mondeleke/videos/2501595423572441/?mibextid=rS40aB7S9Ucbxw6v','text',0,NULL,NULL,NULL,'2025-08-16 09:23:19','2025-08-16 09:23:19'),(38,5,NULL,'outbound','Ah, une vidéo à analyser ! Malheureusement, je ne peux pas accéder directement aux contenus Facebook pour des raisons techniques et de confidentialité. Mais parlons stratégie :\n\n1. **Pour tirer le meilleur de cette ressource** :\n   ✔️ Identifiez les moments clés de la partie\n   ✔️ Analysez les expressions des joueurs\n   ✔️ Notez les coups qui ont fait basculer le jeu\n\n2. **Comment l\'exploiter** :\n   - Étudiez la gestion du temps\n   - Observez le langage corporel\n   - Repérez les patterns récurrents\n\n3. **Mon conseil** :\n   \"Une vidéo bien analysée vaut dix parties jouées à la hâte !\"\n\nJe peux vous aider à :\n- Décortiquer les concepts visibles\n- Élaborer des contre-stratégies\n- Préparer votre prochaine rencontre\n\nEnvoyez-moi les points clés qui vous ont marqué, et travaillons dessus ensemble ! Comme disait Botvinnik : \"Les champions ne naissent pas - ils se construisent pièce par pièce.\" ♟️📽️','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:23:36','2025-08-16 09:23:36','2025-08-16 09:23:36'),(39,9,'false_23798656678-1522443414@g.us_619BA562AA6AA08A213727520C06D79E_237694260137@c.us','inbound','Les revendeurs sont ou chaud chaud du siècle Toyota Yaris année 2009 boite manuelle volant direct climatisation d\'origine prix 2.5millions','text',0,NULL,NULL,NULL,'2025-08-16 09:24:23','2025-08-16 09:24:23'),(40,9,NULL,'outbound','Je comprends que vous recherchez une Toyota Yaris 2009 avec des caractéristiques bien précises. Voici quelques suggestions pour vous aider dans votre recherche :\n\n1. Pour trouver des revendeurs sérieux, je vous recommande de :\n- Consulter les plateformes spécialisées (Jumia Deals, Afribaba, Automobile.tn)\n- Vérifier les annonces chez les concessionnaires agréés\n- Visiter les marchés automobiles de votre région\n\n2. Concernant le prix de 2.5 millions :\n- Ce tarif semble compétitif pour ce modèle\n- Pensez à vérifier l\'état général du véhicule\n- N\'hésitez pas à négocier selon l\'état et le kilométrage\n\n3. Points à vérifier absolument :\n- Historique d\'entretien\n- Contrôle technique à jour\n- Absence de châssis endommagé\n\nSouhaitez-vous que je vous aide à trouver des annonces correspondant à ces critères ? Je peux vous orienter vers des sources fiables si vous me précisez votre localisation.','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:24:39','2025-08-16 09:24:39','2025-08-16 09:24:39'),(41,5,'false_23775448704-1498832223@g.us_108FD79B8284EE32C1AE2B41626F7CC0_237699647586@c.us','inbound','Peut être que tu ignores le vieux joueur contre qui joue Ludovic....','text',0,NULL,NULL,NULL,'2025-08-16 09:24:45','2025-08-16 09:24:45'),(42,5,NULL,'outbound','Ah, l\'expérience contre la jeunesse ! Un duel fascinant. Voici comment décrypter cette situation :\n\n1. **Analyse du vieux briscard** :\n   ✔️ Son âge cache un répertoire de pièges ancestraux\n   ✔️ Il joue probablement sur l\'usure psychologique\n   ✔️ Son calme apparent peut être trompeur\n\n2. **Stratégie pour Ludovic** :\n   - Ne pas sous-estimer l\'effet \"vieux renard\"\n   - Contrer la lenteur calculée par un rythme varié\n   - Anticiper les coups \"d\'école\" classiques\n\n3. **Le saviez-vous ?** :\n   \"Les joueurs chevronnés ont souvent un style identifiable après 10 coups !\"\n\nComment exploiter cela ?\n- Étudier ses anciennes parties si disponibles\n- Repérer ses ouvertures fétiches\n- Préparer des variantes peu connues\n\nComme disait Najdorf : \"Contre un vieux lion, il faut parfois jouer à l\'agneau... pour mieux le croquer après !\" 😉♟️\n\nVous voulez creuser un aspect particulier de son jeu ?','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:25:03','2025-08-16 09:25:03','2025-08-16 09:25:03'),(43,5,'false_23775448704-1498832223@g.us_69A27A9279E02A1DED34E0178B4631C5_23759188150@c.us','inbound','Je connais les deux','text',0,NULL,NULL,NULL,'2025-08-16 09:25:05','2025-08-16 09:25:05'),(44,5,NULL,'outbound','Ah, voilà qui change la donne ! Puisque vous connaissez intimement les deux protagonistes, voici comment transformer cette connaissance en avantage stratégique :\n\n1. **Exploitation de l\'intelligence terrain** :\n   ✔️ Votre connaissance des styles respectifs est un atout majeur\n   ✔️ Vous pouvez anticiper les dynamiques psychologiques invisibles\n   ✔️ La mémoire de leurs précédents duels offre des patterns précieux\n\n2. **Stratégie sur mesure** :\n   - Croiser leurs forces/faiblesses réciproques\n   - Identifier les \"angles morts\" de leur rivalité\n   - Préparer des pièges basés sur leurs habitudes mutuelles\n\n3. **Levier décisif** :\n   \"Dans un duel connu, le gagnant est souvent celui qui réinvente ses propres patterns !\"\n\nComment maximiser cet avantage ?\n- Dresser un comparatif précis de leurs évolutions récentes\n- Repérer les automatismes installés entre eux\n- Introduire des éléments de surprise dans les schémas connus\n\nComme disait Karpov : \"Le meilleur connaisseur d\'une rivalité en détient les clés.\" 🗝️♟️\n\nSouhaitez-vous établir une cartographie précise de leurs interactions typiques ?','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:25:26','2025-08-16 09:25:26','2025-08-16 09:25:26'),(45,5,'false_23775448704-1498832223@g.us_764AC1C1EF5A75AE43E7E32882EDB2AC_242064603899@c.us','inbound','Les premières rencontres','text',0,NULL,NULL,NULL,'2025-08-16 09:28:54','2025-08-16 09:28:54'),(46,5,NULL,'outbound','Ah, les prémices de cette rivalité ! Voici comment analyser ces débuts stratégiques :\n\n1. **Archéologie tactique** :\n   ✔️ Leurs premières parties révèlent leurs fondements stratégiques\n   ✔️ On y décèle déjà leurs marques de fabrique respectives\n   ✔️ Les erreurs initiales sont souvent révélatrices de failles durables\n\n2. **Évolution notable** :\n   - Comparer leurs approches d\'alors et d\'aujourd\'hui\n   - Identifier quels schémas se sont rigidifiés ou assouplis\n   - Repérer les leçons qu\'ils ont retenues (ou non)\n\n3. **Perle d\'analyse** :\n   \"Les premiers duels sont comme des partitions originales - on y trouve la mélodie pure avant les variations ultérieures !\"\n\nPour approfondir :\n- Reconstituer l\'arbre décisionnel de leurs débuts\n- Cartographier leurs adaptations mutuelles\n- Extraire les principes inchangés malgré les années\n\nComme le soulignait Lasker : \"Une rivalité qui dure s\'enracine toujours dans ses premières pousses.\" 🌱♟️\n\nVoulez-vous que nous examinions une période particulière de leur histoire commune ?','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:29:12','2025-08-16 09:29:12','2025-08-16 09:29:12'),(47,10,'false_237696062739@c.us_8DB1FF8BED712F255909DA0302186917','inbound','Bonjour bb','text',0,NULL,NULL,NULL,'2025-08-16 09:29:12','2025-08-16 09:29:12'),(48,10,NULL,'outbound','Je vous remercie pour votre message. Pour maintenir un échange professionnel, je vous propose d\'utiliser un ton plus formel. Comment puis-je vous aider aujourd\'hui ?','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:29:19','2025-08-16 09:29:19','2025-08-16 09:29:19'),(49,5,'false_23775448704-1498832223@g.us_B9E2CB214E1AE381DCBD1F144E078170_237691019849@c.us','inbound','Maintenant ludovic réfléchit au collage qu\'il va placer   après cinq coups 😀😀😀','text',0,NULL,NULL,NULL,'2025-08-16 09:29:19','2025-08-16 09:29:19'),(50,5,NULL,'outbound','Ah, nous entrons dans le vif du sujet stratégique ! Voici comment optimiser cette réflexion cruciale :\n\n1. **Analyse du piège en préparation** :\n   ✔️ Un collage après 5 coups est un timing psychologique parfait\n   ✔️ Cela correspond au moment où l\'adversaire relâche sa vigilance initiale\n   ✔️ Le choix du collage doit exploiter les tendances observées\n\n2. **Stratégie d\'exécution** :\n   - Variante A : Collage positionnel (usure progressive)\n   - Variante B : Collage tactique (effet surprise immédiat)\n   - Variante C : Faux collage (préparation d\'un piège secondaire)\n\n3. **Le saviez-vous ?** :\n   \"Les meilleurs collages semblent naturels jusqu\'à ce qu\'il soit trop tard pour réagir !\"\n\nPour affiner :\n- Étudier les réactions types de l\'adversaire à ce stade\n- Préparer 2-3 options adaptatives\n- Intégrer un élément de surprise visuel\n\nComme disait Bronstein : \"Un bon collage est comme une bonne blague - le timing fait tout !\" 😄♟️\n\nVous voulez explorer des variantes spécifiques pour ce coup clé ?','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:29:37','2025-08-16 09:29:37','2025-08-16 09:29:37'),(51,11,'false_23795826588-1546982304@g.us_4866644FF7EA20B5FA8BD6BFC45528BF_269410447130711@lid','inbound','Gar tu es slmt entrain de mettre la chaleur aux gens ici tu veux slmt qu\'on parle 🙌🙌','text',0,NULL,NULL,NULL,'2025-08-16 09:31:07','2025-08-16 09:31:07'),(52,11,NULL,'outbound','Je vois que l\'ambiance est au rendez-vous ! 😊 Mon rôle c\'est justement de faciliter la conversation et d\'apporter des réponses utiles. Si tu veux échanger sur un sujet précis ou si tu as besoin d\'aide, je suis là pour ça. \n\nQu\'est-ce qui te ferait plaisir qu\'on aborde aujourd\'hui ? 👀 (Et pas de problème pour garder cette bonne énergie, c\'est contagieux ! 🔥)','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:31:16','2025-08-16 09:31:16','2025-08-16 09:31:16'),(53,12,'false_23794033025-1528445424@g.us_3EB08D930AE7F3E07C67','inbound','68887131500701@lid','text',0,NULL,NULL,NULL,'2025-08-16 09:31:48','2025-08-16 09:31:48'),(54,12,NULL,'outbound','Bonjour ! Je vois que vous avez envoyé un identifiant. Comment puis-je vous aider aujourd\'hui ? N\'hésitez pas à me préciser votre demande pour que je puisse vous apporter la meilleure assistance possible. \n\nJe reste à votre disposition pour toute information complémentaire.','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:31:56','2025-08-16 09:31:56','2025-08-16 09:31:56'),(55,11,'false_23795826588-1546982304@g.us_3EB09AC6B939F51BEC0A','inbound','68887131500701@lid','text',0,NULL,NULL,NULL,'2025-08-16 09:31:56','2025-08-16 09:31:56'),(56,11,NULL,'outbound','Je vois que tu partages une série de chiffres et de lettres - malheureusement je ne peux pas interpréter ce code seul(e). 😅  \n\nSi c\'est une référence ou un identifiant spécifique, peux-tu me dire à quoi il correspond ? Je serai ravi(e) de t\'aider à trouver une solution ou une réponse précise !  \n\nTu cherches des infos sur un produit ? Un service ? Ou autre chose ? 👀','text',1,'DeepSeek Chat',0.9,'2025-08-16 09:32:04','2025-08-16 09:32:04','2025-08-16 09:32:04'),(57,13,'false_23791278891@c.us_5FF6F3931DA142D55C39E62774D403D9','inbound','Yo tu es occupé ?','text',0,NULL,NULL,NULL,'2025-08-16 09:33:21','2025-08-16 09:33:21'),(58,11,'false_23795826588-1546982304@g.us_6235DE1CB38F38D166336D1EF51E0A9D_269410447130711@lid','inbound','Il faut d\'abord qu\'il comprenne ce que tu dis nr 🤧','text',0,NULL,NULL,NULL,'2025-08-16 09:33:24','2025-08-16 09:33:24'),(59,10,'false_237696062739@c.us_6129F3F2DC75ACC64C00EDC7ADAED69F','inbound','La mougou est la','text',0,NULL,NULL,NULL,'2025-08-16 09:33:34','2025-08-16 09:33:34'),(60,10,'false_237696062739@c.us_BE8D2285604E4A15C19BEF664AFEEB13','inbound','Mince encore ?','text',0,NULL,NULL,NULL,'2025-08-16 09:33:36','2025-08-16 09:33:36'),(61,8,'test_message_disabled_agent','inbound','Test message avec agent désactivé','text',0,NULL,NULL,NULL,'2025-08-16 09:34:58','2025-08-16 09:34:58'),(62,6,'false_243974186886-1593969886@g.us_3A481F55E888FC8E5392_243829600008@c.us','inbound','Merci beaucoup 🥰🥰🥰🥰','text',0,NULL,NULL,NULL,'2025-08-16 09:34:59','2025-08-16 09:34:59'),(63,10,'false_237696062739@c.us_AFA022F59A724D7474C2A3FB8B3C04C9','inbound','Non est ce que c\'est moi qui veut c\'est la vie qui veut me mougouliser','text',0,NULL,NULL,NULL,'2025-08-16 09:35:05','2025-08-16 09:35:05'),(64,5,'false_23775448704-1498832223@g.us_490C7B9F831E6C1CF51B4808AB424842_242064603899@c.us','inbound','But du GMI Itoua','text',0,NULL,NULL,NULL,'2025-08-16 09:36:04','2025-08-16 09:36:04'),(65,11,'false_23795826588-1546982304@g.us_514D346D938FAFE51AD54C3DF2ECDCBE_269410447130711@lid','inbound','J\'aime ce que tu as proposé 😂','text',0,NULL,NULL,NULL,'2025-08-16 09:36:29','2025-08-16 09:36:29'),(66,13,'false_23791278891@c.us_17BB56CB9A833B8CB139F9C3B8ADF7AB','inbound','Je vérifié','text',0,NULL,NULL,NULL,'2025-08-16 09:36:51','2025-08-16 09:36:51'),(67,13,'false_23791278891@c.us_5E2EEEEE2860562027BB01541D13AC0F','inbound','Je vais alors chercher','text',0,NULL,NULL,NULL,'2025-08-16 09:38:25','2025-08-16 09:38:25'),(68,10,'false_237696062739@c.us_0EC2B542AC2E818083CFA8BD77596CE7','inbound','Tu ris de quoi bb','text',0,NULL,NULL,NULL,'2025-08-16 09:38:29','2025-08-16 09:38:29'),(69,11,'false_23795826588-1546982304@g.us_3AA315DE4542BFC3646B_49010308022420@lid','inbound','En fait 😭','text',0,NULL,NULL,NULL,'2025-08-16 09:40:01','2025-08-16 09:40:01'),(70,5,'false_23775448704-1498832223@g.us_59170AD84CD7E16915437C9410BD7721_23759188150@c.us','inbound','La fin de partie de Atse svp','text',0,NULL,NULL,NULL,'2025-08-16 09:40:12','2025-08-16 09:40:12'),(71,5,'false_23775448704-1498832223@g.us_C43BDBCF3A5E27C8CBE4BF2154D3D0BB_23759188150@c.us','inbound','Président salenko les résultats svp','text',0,NULL,NULL,NULL,'2025-08-16 09:40:33','2025-08-16 09:40:33'),(72,1,'false_23755332183@c.us_778EC982C2025C98076BD971B8493041','inbound','Non non grand','text',0,NULL,NULL,NULL,'2025-08-16 09:44:02','2025-08-16 09:44:02'),(73,10,'false_237696062739@c.us_EFEDF954688C0C5AA9DF37369C87544B','inbound','D\'accord le père','text',0,NULL,NULL,NULL,'2025-08-16 09:46:30','2025-08-16 09:46:30'),(74,14,'false_23793971089@c.us_3A688F55E7F86C2B6955','inbound','Gars lep je ne veux pas m\'expliquer. Comprends slmt que c\'est chaud','text',0,NULL,NULL,NULL,'2025-08-16 15:58:35','2025-08-16 15:58:35'),(75,14,NULL,'outbound','Je comprends que la situation est délicate et que vous ne souhaitez pas en dire plus. Sachez que je suis là pour vous aider du mieux possible, même avec peu d\'informations. \n\nSi vous changez d\'avis et souhaitez des conseils plus précis, n\'hésitez pas à m\'en dire plus. En attendant, je reste disponible pour ce dont vous pourriez avoir besoin. \n\nCourage en tout cas, je sens que c\'est pas simple.','text',1,'DeepSeek Chat',0.9,'2025-08-16 15:58:46','2025-08-16 15:58:46','2025-08-16 15:58:46'),(76,15,'false_12404237945@c.us_3AE0AAF04EFC2C3CAD14','inbound','😂','text',0,NULL,NULL,NULL,'2025-08-16 16:08:16','2025-08-16 16:08:16'),(77,15,NULL,'outbound','Je vois que vous êtes de bonne humeur aujourd\'hui ! 😊 Comment puis-vous vous aider ? Je reste à votre disposition pour toute question ou demande professionnelle.  \n\nBien à vous,  \n[Votre assistant WhatsApp]','text',1,'DeepSeek Chat',0.9,'2025-08-16 16:08:25','2025-08-16 16:08:25','2025-08-16 16:08:25'),(78,15,'false_12404237945@c.us_3A56AC0701719EAE6D4D','inbound','😂😂😂','text',0,NULL,NULL,NULL,'2025-08-16 16:09:01','2025-08-16 16:09:01'),(79,15,NULL,'outbound','On dirait que quelque chose vous amuse beaucoup aujourd\'hui ! 😄 N\'hésitez pas à partager ce qui vous fait rire - je serai ravi d\'échanger avec vous.  \n\nEn attendant, je reste disponible pour toute demande professionnelle ou assistance dont vous pourriez avoir besoin.  \n\nBien cordialement,  \n[Votre assistant WhatsApp]','text',1,'DeepSeek Chat',0.9,'2025-08-16 16:09:11','2025-08-16 16:09:11','2025-08-16 16:09:11');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2025_05_26_150000_create_countries_table',1),(4,'2025_05_26_150001_create_cities_table',1),(5,'2025_05_26_150002_create_municipalities_table',1),(6,'2025_05_26_150003_create_neighborhoods_table',1),(7,'2025_05_26_200000_create_users_table',1),(8,'2025_06_27_163710_create_notifications_table',1),(9,'2025_07_04_154530_create_personal_access_tokens_table',1),(10,'2025_07_21_132250_create_services_table',1),(11,'2025_07_21_153528_create_permission_tables',1),(12,'2025_07_21_161920_create_wallets_table',1),(13,'2025_07_23_170940_create_customers_table',1),(14,'2025_07_23_224818_create_media_table',1),(15,'2025_07_24_181454_create_internal_transactions_table',1),(16,'2025_07_24_181525_create_external_transactions_table',1),(17,'2025_07_25_125146_create_system_accounts_table',1),(18,'2025_07_25_125216_create_system_account_transactions_table',1),(19,'2025_07_27_000000_create_tickets_table',1),(20,'2025_07_27_000001_create_ticket_messages_table',1),(21,'2025_07_29_081543_update_pending_tickets_to_replied_status',1),(22,'2025_07_30_105912_create_push_subscriptions_table',1),(23,'2025_08_06_191652_create_ai_models_table',1),(24,'2025_08_06_204231_create_whatsapp_accounts_table',1),(25,'2025_08_06_204232_create_ai_contexts_table',1),(26,'2025_08_06_204232_create_conversations_table',1),(27,'2025_08_06_204232_create_messages_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',2),(2,'App\\Models\\User',3),(2,'App\\Models\\User',4),(2,'App\\Models\\User',5),(2,'App\\Models\\User',6),(2,'App\\Models\\User',7),(2,'App\\Models\\User',8),(2,'App\\Models\\User',9),(2,'App\\Models\\User',10),(2,'App\\Models\\User',11),(2,'App\\Models\\User',12),(2,'App\\Models\\User',13),(2,'App\\Models\\User',14),(2,'App\\Models\\User',15),(2,'App\\Models\\User',16),(2,'App\\Models\\User',17),(2,'App\\Models\\User',18),(2,'App\\Models\\User',19),(2,'App\\Models\\User',20),(2,'App\\Models\\User',21),(2,'App\\Models\\User',22),(2,'App\\Models\\User',23),(2,'App\\Models\\User',24),(2,'App\\Models\\User',25),(2,'App\\Models\\User',26),(2,'App\\Models\\User',27),(2,'App\\Models\\User',28),(2,'App\\Models\\User',29),(2,'App\\Models\\User',30),(2,'App\\Models\\User',31);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `municipalities`
--

DROP TABLE IF EXISTS `municipalities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `municipalities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `city_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `municipalities_city_id_name_unique` (`city_id`,`name`),
  CONSTRAINT `municipalities_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `municipalities`
--

LOCK TABLES `municipalities` WRITE;
/*!40000 ALTER TABLE `municipalities` DISABLE KEYS */;
/*!40000 ALTER TABLE `municipalities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `neighborhoods`
--

DROP TABLE IF EXISTS `neighborhoods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `neighborhoods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `municipality_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `neighborhoods_municipality_id_name_unique` (`municipality_id`,`name`),
  CONSTRAINT `neighborhoods_municipality_id_foreign` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `neighborhoods`
--

LOCK TABLES `neighborhoods` WRITE;
/*!40000 ALTER TABLE `neighborhoods` DISABLE KEYS */;
/*!40000 ALTER TABLE `neighborhoods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

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

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'users.view','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(2,'users.create','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(3,'users.edit','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(4,'users.delete','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(5,'users.manage_roles','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(6,'customers.view','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(7,'customers.edit','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(8,'customers.delete','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(9,'transactions.view_external','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(10,'transactions.view_internal','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(11,'transactions.create_recharge','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(12,'transactions.create_withdrawal','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(13,'transactions.approve_withdrawal','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(14,'transactions.reject_withdrawal','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(15,'transactions.create_customer_recharge','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(16,'transactions.create_customer_withdrawal','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(17,'system_accounts.view_balances','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(18,'system_accounts.view_transactions','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(19,'system_accounts.recharge','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(20,'system_accounts.withdraw','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(21,'system_accounts.manage','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(22,'dashboard.view','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(23,'profile.view','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(24,'profile.edit','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(25,'settings.view','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(26,'settings.edit','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(27,'referrals.view','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(28,'referrals.manage','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(29,'wallet.view','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(30,'wallet.recharge','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(31,'wallet.withdraw','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(32,'orders.view','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(33,'tickets.view','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(34,'tickets.create','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(35,'tickets.reply','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(36,'tickets.close','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(37,'tickets.assign','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(38,'tickets.change_status','web','2025-08-14 16:57:39','2025-08-14 16:57:39');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subscribable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscribable_id` bigint unsigned NOT NULL,
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auth_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content_encoding` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `push_subscriptions_subscribable_type_subscribable_id_index` (`subscribable_type`,`subscribable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_subscriptions`
--

LOCK TABLES `push_subscriptions` WRITE;
/*!40000 ALTER TABLE `push_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(9,2),(10,2),(15,2),(16,2),(22,2),(23,2),(24,2),(27,2),(29,2),(30,2),(31,2),(32,2),(33,2),(34,2),(35,2),(1,3),(2,3),(3,3),(4,3),(5,3),(6,3),(7,3),(8,3),(9,3),(10,3),(11,3),(12,3),(13,3),(14,3),(15,3),(16,3),(17,3),(18,3),(19,3),(20,3),(21,3),(22,3),(23,3),(24,3),(25,3),(26,3),(27,3),(28,3),(29,3),(30,3),(31,3),(32,3),(33,3),(34,3),(35,3),(36,3),(37,3),(38,3);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(2,'customer','web','2025-08-14 16:57:39','2025-08-14 16:57:39'),(3,'super-admin','web','2025-08-14 16:57:39','2025-08-14 16:57:39');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_name_unique` (`name`),
  KEY `services_created_by_foreign` (`created_by`),
  CONSTRAINT `services_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

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

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('5UYchmZ9K6NSn4tnVgNXvxLsckNKMH915llmUpF6',2,'127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOUxGUDQ4cTl2NkRTS0F5a3hsTGo0aDExNTd3YzViWWRCcjRqbHBKbyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC93aGF0c2FwcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==',1755368008);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_account_transactions`
--

DROP TABLE IF EXISTS `system_account_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_account_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `system_account_id` bigint unsigned NOT NULL,
  `type` enum('recharge','withdrawal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sender_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiver_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiver_account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_account_transactions_created_by_foreign` (`created_by`),
  KEY `system_account_transactions_system_account_id_created_at_index` (`system_account_id`,`created_at`),
  CONSTRAINT `system_account_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `system_account_transactions_system_account_id_foreign` FOREIGN KEY (`system_account_id`) REFERENCES `system_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_account_transactions`
--

LOCK TABLES `system_account_transactions` WRITE;
/*!40000 ALTER TABLE `system_account_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_account_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_accounts`
--

DROP TABLE IF EXISTS `system_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('mobile_money','orange_money','bank_card','cash') COLLATE utf8mb4_unicode_ci NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_accounts_type_unique` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_accounts`
--

LOCK TABLES `system_accounts` WRITE;
/*!40000 ALTER TABLE `system_accounts` DISABLE KEYS */;
INSERT INTO `system_accounts` VALUES (1,'mobile_money',0.00,1,'2025-08-14 16:57:47','2025-08-14 16:57:47'),(2,'orange_money',0.00,1,'2025-08-14 16:57:47','2025-08-14 16:57:47'),(3,'bank_card',0.00,1,'2025-08-14 16:57:47','2025-08-14 16:57:47'),(4,'cash',0.00,1,'2025-08-14 16:57:47','2025-08-14 16:57:47');
/*!40000 ALTER TABLE `system_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` json DEFAULT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_messages_ticket_id_created_at_index` (`ticket_id`,`created_at`),
  KEY `ticket_messages_user_id_index` (`user_id`),
  KEY `ticket_messages_sender_type_index` (`sender_type`),
  CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_messages`
--

LOCK TABLES `ticket_messages` WRITE;
/*!40000 ALTER TABLE `ticket_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MEDIUM',
  `attachments` json DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_ticket_number_unique` (`ticket_number`),
  KEY `tickets_user_id_status_index` (`user_id`,`status`),
  KEY `tickets_assigned_to_index` (`assigned_to`),
  KEY `tickets_status_index` (`status`),
  KEY `tickets_created_at_index` (`created_at`),
  CONSTRAINT `tickets_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `affiliation_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referrer_id` bigint unsigned DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `locale` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fr',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_phone_number_unique` (`email`,`phone_number`),
  UNIQUE KEY `users_affiliation_code_unique` (`affiliation_code`),
  KEY `users_referrer_id_foreign` (`referrer_id`),
  KEY `users_country_id_foreign` (`country_id`),
  CONSTRAINT `users_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `users_referrer_id_foreign` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System','Admin','admin@genericsaas.com','+237655332183','16185 Kiarra Ridges\nScottymouth, IL 71218-5905','2025-08-14 16:57:40',NULL,'$2y$12$nN/jFOWXLM8eMk66iXsQ/.Ph351z8LPmMGhi0e2gvCZosgXWJG/l2','WFT0',NULL,NULL,1,'fr',NULL,'c0UYaNZF1c','2025-08-14 16:57:40','2025-08-14 16:57:40',NULL),(2,'Beatty','Hollis','customer1@example.com','+1 (762) 919-0705','854 Valerie Prairie\nZellaport, NC 50047-5717','2025-08-14 16:57:40',NULL,'$2y$12$.3f6IgwSMSHvxtc/jGVCAu3d8tC3/LiG0lPDxPCwSmpXZBilfktTq','6TUL',25,1,1,'fr',NULL,'UFyz6wFLNT','2025-08-14 16:57:40','2025-08-14 16:57:47',NULL),(3,'Bartoletti','Kale','customer2@example.com','(346) 670-2623','56021 Romaguera River\nLake Devintown, RI 86336-5639','2025-08-14 16:57:40',NULL,'$2y$12$uxq48djxg4sIdlji597TOOBjnn5y9ZkGuKkuB1am3wld2L26XrOzi','OZIW',NULL,1,1,'fr',NULL,'oL56uDjbK5','2025-08-14 16:57:40','2025-08-14 16:57:40',NULL),(4,'Harris','Benton','customer3@example.com','267.401.7578','38294 Joanie Common Apt. 152\nRafaelaton, CT 11790-3349','2025-08-14 16:57:40',NULL,'$2y$12$1qNx1cynLKDQMA.FWT.qmeyle1rgQQztISZRHvGyM/jIPlXt0isP2','TXT4',NULL,1,1,'fr',NULL,'QloVSaFnz7','2025-08-14 16:57:41','2025-08-14 16:57:41',NULL),(5,'Sporer','Samara','customer4@example.com','832.293.9077','80457 Hansen Hills\nChazmouth, MT 11398','2025-08-14 16:57:41',NULL,'$2y$12$NNltet9GQEn3K7aZHXcMCOR1RAp0deKT/RnOigpZFBC.D6tAFKHh.','7ZYD',NULL,1,1,'fr',NULL,'2QAWqRVd4o','2025-08-14 16:57:41','2025-08-14 16:57:41',NULL),(6,'Stark','Anabel','customer5@example.com','+1-980-638-6076','664 Rafael Prairie\nElsiemouth, ME 55447','2025-08-14 16:57:41',NULL,'$2y$12$dvxJxul934BTMzHiGIyNku1MnBXtUswWDSIJFn22CKIwn4.ulcIIG','IBPL',NULL,1,1,'fr',NULL,'u9TBtnO2qM','2025-08-14 16:57:41','2025-08-14 16:57:41',NULL),(7,'Predovic','Marcus','customer6@example.com','+1-832-389-8109','2327 O\'Hara Inlet Suite 769\nBayerton, OH 04524','2025-08-14 16:57:41',NULL,'$2y$12$V7nMHKI1.ezo3ARlyNEpo.oHXMyResB3J6L/9Ko1AH36aGm1eCqWy','AUHM',27,1,1,'fr',NULL,'Hl4kiCMIlL','2025-08-14 16:57:41','2025-08-14 16:57:47',NULL),(8,'Will','Laurianne','customer7@example.com','(559) 990-0387','2487 Lindgren Fort\nChristiansenburgh, NC 46585-3998','2025-08-14 16:57:41',NULL,'$2y$12$5Tsw0EuKhN9rgOU.D8OLlOPuQSrMOS0sWS7Uaw3UGH6i.0Jb6PuIW','RCBY',NULL,1,1,'fr',NULL,'SnbIXN3zi8','2025-08-14 16:57:41','2025-08-14 16:57:41',NULL),(9,'Schowalter','Anastacio','customer8@example.com','281.329.5834','12399 Klocko Hills\nEast Abbigail, RI 62876-9610','2025-08-14 16:57:42',NULL,'$2y$12$6Tas2MRISBFSUp.ky9AprunZhjVlN.ZIHF5qZrOUpA/Nk/aL8lBfy','WLRC',NULL,1,1,'fr',NULL,'t1NRfgKtfo','2025-08-14 16:57:42','2025-08-14 16:57:42',NULL),(10,'Kautzer','Audrey','customer9@example.com','(678) 803-0527','707 Wehner Viaduct Apt. 861\nHacketttown, NM 44751-0615','2025-08-14 16:57:42',NULL,'$2y$12$/bbnKXca3QfyV9VL6jigH.NmRBB9ycUZxcV5SqqorVj5yUhTz.vk6','HG6C',NULL,1,1,'fr',NULL,'KNMP70781E','2025-08-14 16:57:42','2025-08-14 16:57:42',NULL),(11,'Reichel','Flossie','customer10@example.com','217-962-3117','773 Quitzon Island\nSouth Grayson, NM 71592-0582','2025-08-14 16:57:42',NULL,'$2y$12$oFAfIalEcNgvabxQ2K4g4OBLKw9EufHfTstbK.32so9L/.ErffFUS','FDS4',NULL,1,1,'fr',NULL,'h1YPCdHwbR','2025-08-14 16:57:42','2025-08-14 16:57:42',NULL),(12,'Marvin','Gudrun','customer11@example.com','870.726.0913','7962 Erdman View\nNew Rylan, MO 50402','2025-08-14 16:57:42',NULL,'$2y$12$KXrtsOIKi/b4VRtICmTjDuS7iAY4BYKN97/vgIgODniMfILKIOi4W','EX9E',17,1,1,'fr',NULL,'zwURg2n3rz','2025-08-14 16:57:42','2025-08-14 16:57:47',NULL),(13,'Hackett','Delta','customer12@example.com','302-935-0740','6682 Chase Tunnel\nWillmsmouth, VT 72489-4981','2025-08-14 16:57:42',NULL,'$2y$12$q0bvLqrSRwpY4PDhVKZbqe/ZCRH9UukORWp0d9067zjNS0JxBsSye','GEVJ',NULL,1,1,'fr',NULL,'LhvMmU74Ta','2025-08-14 16:57:43','2025-08-14 16:57:43',NULL),(14,'Torp','Matilda','customer13@example.com','(925) 378-6219','41404 Pfeffer Via Apt. 147\nNorth Andrew, SC 33481-2660','2025-08-14 16:57:43',NULL,'$2y$12$2UHPuECkAlHciMCmS0poLuBOih/40NO6j/0S9BWktUhj5esQX66tS','LLL5',NULL,1,1,'fr',NULL,'Hi4h3BAi9H','2025-08-14 16:57:43','2025-08-14 16:57:43',NULL),(15,'Bogisich','Elissa','customer14@example.com','323-781-0875','13385 Kuhlman Vista\nBodeport, MN 06441-3407','2025-08-14 16:57:43',NULL,'$2y$12$Lb.2jTi9LT7jzCG0vCAJa.fXPfDhKX85OrhenWuUyA6PdmfC4YZ6m','I1YT',NULL,1,1,'fr',NULL,'cgAhwPYHM8','2025-08-14 16:57:43','2025-08-14 16:57:43',NULL),(16,'Funk','Jeremy','customer15@example.com','(912) 762-0464','4210 Feil Coves\nSouth Flossietown, NV 50180','2025-08-14 16:57:43',NULL,'$2y$12$MN4gkcoovky6.DtFrAeoHeGs6rzyZGcGx1RCtGzf7rw86d8tF32di','P76X',NULL,1,1,'fr',NULL,'NeXLYeDyLW','2025-08-14 16:57:43','2025-08-14 16:57:43',NULL),(17,'Ankunding','Dejon','customer16@example.com','1-870-925-6988','13363 Luettgen Drive Apt. 367\nLehnerbury, OR 14990','2025-08-14 16:57:43',NULL,'$2y$12$DWSER9TQ4YWaPNW1QQHUQOaPyvZ0rx4smsQSiEoVWx6RyGdART9yW','INWT',28,1,1,'fr',NULL,'dbQq5WPIcC','2025-08-14 16:57:44','2025-08-14 16:57:47',NULL),(18,'D\'Amore','Rodger','customer17@example.com','(603) 449-1924','596 Jeffrey Radial Apt. 936\nWildermanview, GA 62501','2025-08-14 16:57:44',NULL,'$2y$12$CYKMZVLG3shT3nTzy9KxCuPkIr8x5KX7LDWDkQhq89jq/Yc.8h4yq','WMKQ',NULL,1,1,'fr',NULL,'UATkvTlPIu','2025-08-14 16:57:44','2025-08-14 16:57:44',NULL),(19,'Boehm','Arturo','customer18@example.com','520.325.8544','258 Savion Tunnel Apt. 723\nPort Kale, IA 50200-2122','2025-08-14 16:57:44',NULL,'$2y$12$ByZWmtShSkOm94BuQfDyzuIHe/0dW5M5yGlko02rtZ8rfnD7Q3vO.','DOVC',NULL,1,1,'fr',NULL,'XKmVfrIdvZ','2025-08-14 16:57:44','2025-08-14 16:57:44',NULL),(20,'Johns','Arvid','customer19@example.com','1-539-592-5502','27990 Pacocha Neck\nWest Santos, NY 87568','2025-08-14 16:57:44',NULL,'$2y$12$HIzxF/1ovf/jg2O3bIDg3ePoe46WF7UADSMX6fQIOUHoVpLmxbIDO','F2WV',NULL,1,1,'fr',NULL,'xpOBpK0JqK','2025-08-14 16:57:44','2025-08-14 16:57:44',NULL),(21,'Pollich','Cordell','customer20@example.com','+1-707-531-6046','32423 Eichmann Bridge Apt. 264\nHoppeview, VA 22616-0034','2025-08-14 16:57:44',NULL,'$2y$12$Ipng287joyYR.Fgyro91LOFuJSSCjNHxD3uD6jKtcXTC1nZoTh6hi','QSN8',NULL,1,1,'fr',NULL,'jeiU0Cxqul','2025-08-14 16:57:45','2025-08-14 16:57:45',NULL),(22,'Leannon','Winston','customer21@example.com','+1-802-273-8806','2084 Wilderman Road Suite 564\nCandidoland, WI 96635-8110','2025-08-14 16:57:45',NULL,'$2y$12$MSQRJ0K4kl8l2/57nOsHi.oNQ9AqTWwZUvgSnTwmGJJj/3pWdICvi','XTST',16,1,1,'fr',NULL,'E5eVpG9X9f','2025-08-14 16:57:45','2025-08-14 16:57:47',NULL),(23,'Dibbert','Amparo','customer22@example.com','484-706-9612','7901 Rocky Hill\nSylvesterland, NE 19654-4182','2025-08-14 16:57:45',NULL,'$2y$12$SpnPxlPtwg0b.z7bxVc7sOv31pInGQtRgtYYlXpHZqG0GW1kdZjD2','LORG',12,1,1,'fr',NULL,'hJXz2awj6H','2025-08-14 16:57:45','2025-08-14 16:57:47',NULL),(24,'Kuhlman','Lisette','customer23@example.com','1-870-642-3886','4538 Stanton Path Apt. 972\nMaciechester, PA 11603','2025-08-14 16:57:45',NULL,'$2y$12$CeYavRPdxBb7O9FmPsHdKed8wBk0xh5x.sKbG/VbCF88ewWIdypKS','TWM9',31,1,1,'fr',NULL,'IuJiXA47qp','2025-08-14 16:57:45','2025-08-14 16:57:47',NULL),(25,'Crist','Sid','customer24@example.com','+1.669.691.4588','74271 Hassan Groves Apt. 028\nSouth Horace, TX 04116-5329','2025-08-14 16:57:45',NULL,'$2y$12$12X4/.mBZNMy9oX/xn5Q/e5wuLA0vqceN3DeK7Fv8JrmE09ctZPaa','TEQG',14,1,1,'fr',NULL,'U8kQDE2wQN','2025-08-14 16:57:45','2025-08-14 16:57:47',NULL),(26,'Zboncak','Ali','customer25@example.com','(979) 905-7770','492 Terry Fort Suite 042\nLangworthtown, MA 24270-6567','2025-08-14 16:57:45',NULL,'$2y$12$397mN1ZlhVWM71emf3.XrehstJOuQ9s8kEGkoJToPcxKtBjyuYogm','A2JD',NULL,1,1,'fr',NULL,'0bHnwC9N6V','2025-08-14 16:57:46','2025-08-14 16:57:46',NULL),(27,'Kuhn','Micah','customer26@example.com','(669) 222-7091','403 Syble Road Apt. 474\nGoodwinport, MA 75584','2025-08-14 16:57:46',NULL,'$2y$12$aiv2xaat90G/npjXm2zcN.diDsFlCcb1uHH9Kmga./tZ7Vz4kTMu2','ZDPH',NULL,1,1,'fr',NULL,'MkFuT8OAGX','2025-08-14 16:57:46','2025-08-14 16:57:46',NULL),(28,'Kris','Kaylie','customer27@example.com','1-757-665-1321','4651 Wilkinson Center\nLake Carmine, ME 13038-3371','2025-08-14 16:57:46',NULL,'$2y$12$cnx/OthfVQLDfI/axVSEF.ltk3KQ5wQlFq3fo5RhouBTbrOJralXG','GAH9',NULL,1,1,'fr',NULL,'6j8sKe7jRP','2025-08-14 16:57:46','2025-08-14 16:57:46',NULL),(29,'Halvorson','Clementina','customer28@example.com','650.935.4395','4783 Ludie Glen Apt. 881\nBartolettiside, RI 84652','2025-08-14 16:57:46',NULL,'$2y$12$BY5cdLaj6LXcXObWhY6gzOZBVLwnCprYn7ZiC1j6f.akaYAMBvvIW','GAR6',12,1,1,'fr',NULL,'4VmwmYSs6w','2025-08-14 16:57:46','2025-08-14 16:57:47',NULL),(30,'Erdman','Brennon','customer29@example.com','(623) 755-6177','2496 Turcotte Gardens Suite 757\nLake Harmontown, NY 88570','2025-08-14 16:57:46',NULL,'$2y$12$hoLYYhU3YK.VQ1fra20bUua2jSQcwrZr8buFQ8zISeNonVqn7yQIy','PDAQ',NULL,1,1,'fr',NULL,'V8gV4JN4lC','2025-08-14 16:57:47','2025-08-14 16:57:47',NULL),(31,'Berge','Toy','customer30@example.com','364-918-6688','9146 Moen Stravenue\nNew Colten, AK 45196-3143','2025-08-14 16:57:47',NULL,'$2y$12$uK86otLe33oQzvy2qAJ4Keg5RR3OfuCk8OEGmCuvIymH0YkS07WDS','Y6AJ',NULL,1,1,'fr',NULL,'ehAyFvWwtB','2025-08-14 16:57:47','2025-08-14 16:57:47',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallets_user_id_unique` (`user_id`),
  CONSTRAINT `wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallets`
--

LOCK TABLES `wallets` WRITE;
/*!40000 ALTER TABLE `wallets` DISABLE KEYS */;
INSERT INTO `wallets` VALUES (1,2,0.00,'2025-08-14 16:57:40','2025-08-14 16:57:40'),(2,3,0.00,'2025-08-14 16:57:40','2025-08-14 16:57:40'),(3,4,0.00,'2025-08-14 16:57:41','2025-08-14 16:57:41'),(4,5,0.00,'2025-08-14 16:57:41','2025-08-14 16:57:41'),(5,6,0.00,'2025-08-14 16:57:41','2025-08-14 16:57:41'),(6,7,0.00,'2025-08-14 16:57:41','2025-08-14 16:57:41'),(7,8,0.00,'2025-08-14 16:57:42','2025-08-14 16:57:42'),(8,9,0.00,'2025-08-14 16:57:42','2025-08-14 16:57:42'),(9,10,0.00,'2025-08-14 16:57:42','2025-08-14 16:57:42'),(10,11,0.00,'2025-08-14 16:57:42','2025-08-14 16:57:42'),(11,12,0.00,'2025-08-14 16:57:42','2025-08-14 16:57:42'),(12,13,0.00,'2025-08-14 16:57:43','2025-08-14 16:57:43'),(13,14,0.00,'2025-08-14 16:57:43','2025-08-14 16:57:43'),(14,15,0.00,'2025-08-14 16:57:43','2025-08-14 16:57:43'),(15,16,0.00,'2025-08-14 16:57:43','2025-08-14 16:57:43'),(16,17,0.00,'2025-08-14 16:57:44','2025-08-14 16:57:44'),(17,18,0.00,'2025-08-14 16:57:44','2025-08-14 16:57:44'),(18,19,0.00,'2025-08-14 16:57:44','2025-08-14 16:57:44'),(19,20,0.00,'2025-08-14 16:57:44','2025-08-14 16:57:44'),(20,21,0.00,'2025-08-14 16:57:45','2025-08-14 16:57:45'),(21,22,0.00,'2025-08-14 16:57:45','2025-08-14 16:57:45'),(22,23,0.00,'2025-08-14 16:57:45','2025-08-14 16:57:45'),(23,24,0.00,'2025-08-14 16:57:45','2025-08-14 16:57:45'),(24,25,0.00,'2025-08-14 16:57:45','2025-08-14 16:57:45'),(25,26,0.00,'2025-08-14 16:57:46','2025-08-14 16:57:46'),(26,27,0.00,'2025-08-14 16:57:46','2025-08-14 16:57:46'),(27,28,0.00,'2025-08-14 16:57:46','2025-08-14 16:57:46'),(28,29,0.00,'2025-08-14 16:57:46','2025-08-14 16:57:46'),(29,30,0.00,'2025-08-14 16:57:47','2025-08-14 16:57:47'),(30,31,0.00,'2025-08-14 16:57:47','2025-08-14 16:57:47');
/*!40000 ALTER TABLE `wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_accounts`
--

DROP TABLE IF EXISTS `whatsapp_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `session_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('disconnected','connecting','connected','error','initializing','waiting_qr','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disconnected',
  `qr_code` text COLLATE utf8mb4_unicode_ci,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `session_data` json DEFAULT NULL,
  `agent_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `ai_model_id` bigint unsigned DEFAULT NULL,
  `response_time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'random',
  `agent_prompt` text COLLATE utf8mb4_unicode_ci,
  `trigger_words` json DEFAULT NULL,
  `stop_on_human_reply` tinyint(1) NOT NULL DEFAULT '0',
  `contextual_information` text COLLATE utf8mb4_unicode_ci,
  `ignore_words` json DEFAULT NULL,
  `last_ai_response_at` timestamp NULL DEFAULT NULL,
  `daily_ai_responses` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_accounts_session_id_unique` (`session_id`),
  KEY `whatsapp_accounts_user_id_foreign` (`user_id`),
  KEY `whatsapp_accounts_ai_model_id_foreign` (`ai_model_id`),
  CONSTRAINT `whatsapp_accounts_ai_model_id_foreign` FOREIGN KEY (`ai_model_id`) REFERENCES `ai_models` (`id`) ON DELETE SET NULL,
  CONSTRAINT `whatsapp_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_accounts`
--

LOCK TABLES `whatsapp_accounts` WRITE;
/*!40000 ALTER TABLE `whatsapp_accounts` DISABLE KEYS */;
INSERT INTO `whatsapp_accounts` VALUES (5,2,'test1','session_2_17552805081829_3d3b6b43','237676636794','connected',NULL,'2025-08-15 17:55:45','{\"user_id\": 2, \"connected_at\": \"2025-08-15T17:55:35.596Z\"}',NULL,1,4,'random',NULL,NULL,0,NULL,NULL,NULL,0,'2025-08-15 17:55:45','2025-08-16 09:46:47'),(6,2,'test2','session_2_17552805689246_e3929ee8','23755332183','connected',NULL,'2025-08-15 17:57:19','{\"user_id\": 2, \"connected_at\": \"2025-08-15T17:57:18.840Z\"}',NULL,0,4,'random',NULL,NULL,0,NULL,NULL,NULL,0,'2025-08-15 17:57:19','2025-08-16 09:23:37');
/*!40000 ALTER TABLE `whatsapp_accounts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-16 19:18:23
