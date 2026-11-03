-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: sparkling_test
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.3

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
-- Table structure for table `accommodation_proposals`
--

DROP TABLE IF EXISTS `accommodation_proposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accommodation_proposals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `accommodation_request_id` bigint unsigned NOT NULL,
  `hotel_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `rooms` int unsigned NOT NULL,
  `room_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meal_plan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nightly_rate` decimal(15,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inclusions` text COLLATE utf8mb4_unicode_ci,
  `terms` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reservation_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proposed',
  `supplier_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `availability_checked_at` timestamp NOT NULL,
  `hold_expires_at` timestamp NULL DEFAULT NULL,
  `internal_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accommodation_proposals_accommodation_request_id_foreign` (`accommodation_request_id`),
  CONSTRAINT `accommodation_proposals_accommodation_request_id_foreign` FOREIGN KEY (`accommodation_request_id`) REFERENCES `accommodation_requests` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accommodation_proposals`
--

LOCK TABLES `accommodation_proposals` WRITE;
/*!40000 ALTER TABLE `accommodation_proposals` DISABLE KEYS */;
/*!40000 ALTER TABLE `accommodation_proposals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accommodation_requests`
--

DROP TABLE IF EXISTS `accommodation_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accommodation_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `preferences` json NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `selected_proposal_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accommodation_requests_booking_id_unique` (`booking_id`),
  KEY `accommodation_requests_selected_proposal_id_foreign` (`selected_proposal_id`),
  CONSTRAINT `accommodation_requests_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  CONSTRAINT `accommodation_requests_selected_proposal_id_foreign` FOREIGN KEY (`selected_proposal_id`) REFERENCES `accommodation_proposals` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accommodation_requests`
--

LOCK TABLES `accommodation_requests` WRITE;
/*!40000 ALTER TABLE `accommodation_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `accommodation_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_items`
--

DROP TABLE IF EXISTS `booking_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `catalog_item_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int unsigned NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `snapshot` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_items_booking_id_foreign` (`booking_id`),
  KEY `booking_items_catalog_item_id_foreign` (`catalog_item_id`),
  CONSTRAINT `booking_items_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  CONSTRAINT `booking_items_catalog_item_id_foreign` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_items`
--

LOCK TABLES `booking_items` WRITE;
/*!40000 ALTER TABLE `booking_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idempotency_key` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guest_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region_id` bigint unsigned NOT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preferred_contact` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `special_requests` text COLLATE utf8mb4_unicode_ci,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `adults` int unsigned NOT NULL,
  `children` int unsigned NOT NULL DEFAULT '0',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `known_subtotal` decimal(15,2) NOT NULL,
  `quote_required` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `availability_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `internal_notes` text COLLATE utf8mb4_unicode_ci,
  `privacy_accepted_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_public_id_unique` (`public_id`),
  UNIQUE KEY `bookings_reference_unique` (`reference`),
  UNIQUE KEY `bookings_idempotency_key_unique` (`idempotency_key`),
  KEY `bookings_region_id_foreign` (`region_id`),
  KEY `bookings_assigned_to_foreign` (`assigned_to`),
  KEY `bookings_kind_start_date_index` (`kind`,`start_date`),
  KEY `bookings_kind_index` (`kind`),
  KEY `bookings_status_index` (`status`),
  CONSTRAINT `bookings_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `bookings_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
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
  `expiration` bigint NOT NULL,
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
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
-- Table structure for table `catalog_items`
--

DROP TABLE IF EXISTS `catalog_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `region_id` bigint unsigned NOT NULL,
  `media_asset_id` bigint unsigned DEFAULT NULL,
  `kind` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_sw` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TZS',
  `pricing_basis` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'per_person',
  `is_free` tinyint(1) NOT NULL DEFAULT '0',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `duration_days` int unsigned NOT NULL DEFAULT '1',
  `itinerary` text COLLATE utf8mb4_unicode_ci,
  `inclusions` text COLLATE utf8mb4_unicode_ci,
  `exclusions` text COLLATE utf8mb4_unicode_ci,
  `included_item_ids` json DEFAULT NULL,
  `requirements` text COLLATE utf8mb4_unicode_ci,
  `languages` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catalog_items_slug_unique` (`slug`),
  KEY `catalog_items_media_asset_id_foreign` (`media_asset_id`),
  KEY `catalog_items_region_id_kind_published_index` (`region_id`,`kind`,`published`),
  KEY `catalog_items_kind_index` (`kind`),
  CONSTRAINT `catalog_items_media_asset_id_foreign` FOREIGN KEY (`media_asset_id`) REFERENCES `media_assets` (`id`),
  CONSTRAINT `catalog_items_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=337 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catalog_items`
--

LOCK TABLES `catalog_items` WRITE;
/*!40000 ALTER TABLE `catalog_items` DISABLE KEYS */;
INSERT INTO `catalog_items` VALUES (325,55,192,'destination','Stone Town','stone-town',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.','Unguja, Zanzibar',NULL,'TZS','per_person',0,1,1,NULL,NULL,NULL,NULL,'Walking routes and entrance fees will be confirmed for the selected itinerary.',NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(326,55,191,'destination','Zanzibar Coast','zanzibar-coast',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.','Unguja coast',0.00,'TZS','per_person',1,1,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(327,56,195,'destination','Serengeti landscapes','serengeti',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.',NULL,NULL,'TZS','per_person',0,1,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(328,56,194,'destination','Kilimanjaro foothills','kilimanjaro',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.',NULL,NULL,'TZS','per_person',0,1,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(329,55,192,'guide','Local cultural guide','zanzibar-guide',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.',NULL,120000.00,'TZS','per_day',0,1,1,NULL,NULL,NULL,NULL,NULL,'English, Kiswahili','2026-10-15 07:06:20','2026-10-15 07:06:20'),(330,55,191,'food','Zanzibar lunch experience','zanzibar-lunch',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.',NULL,25000.00,'TZS','per_person_meal',0,1,1,NULL,NULL,NULL,NULL,'Tell us about dietary requirements when submitting your request.',NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(331,55,190,'activity','Coastal dhow experience','coastal-boat-experience',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.',NULL,60000.00,'TZS','per_person',0,1,1,NULL,NULL,NULL,NULL,'Marine conditions and suitable participation requirements will be checked by the operator.',NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(332,56,193,'guide','Mainland safari guide','mainland-guide',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.',NULL,150000.00,'TZS','per_day',0,1,1,NULL,NULL,NULL,NULL,NULL,'English, Kiswahili','2026-10-15 07:06:20','2026-10-15 07:06:20'),(333,55,192,'tour','Stone Town & island rhythm','stone-town-and-coast',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.',NULL,160000.00,'TZS','per_person',0,1,1,'Day 1: Discover Stone Town, then enjoy time on the coast.','Local guide\nSelected walking experiences','Accommodation\nMeals unless added separately\nStandalone airport transfers','[329]',NULL,NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(334,55,190,'tour','A slower Zanzibar escape','zanzibar-island-discovery',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.',NULL,340000.00,'TZS','per_person',0,1,2,'Day 1: Stone Town and local culture.\nDay 2: Coastal scenery and a dhow experience.','Guided experiences\nConfigured dhow activity','Accommodation\nMeals\nAirport transfers','[331, 329]',NULL,NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(335,56,193,'tour','The Serengeti chapter','serengeti-sample-safari',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.',NULL,2400000.00,'TZS','per_person',0,1,4,'Day 1: Arrival and introductory landscapes.\nDay 2: Wildlife exploration.\nDay 3: Further safari experiences.\nDay 4: Return arrangements.','Safari guide\nTour transport as agreed','Accommodation pending arrangement\nFlights\nServices outside the agreed itinerary','[332]',NULL,NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(336,56,194,'tour','At the foot of Kilimanjaro','kilimanjaro-foothills-discovery',NULL,'An illustrative experience to help you explore your travel options.','Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.',NULL,280000.00,'TZS','per_person',0,1,1,'Day 1: A guided foothills and landscape experience near Moshi.','Guide service','Accommodation and transfers outside the published itinerary','[332]',NULL,NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20');
/*!40000 ALTER TABLE `catalog_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_sequences`
--

DROP TABLE IF EXISTS `document_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_sequences` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` bigint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_sequences`
--

LOCK TABLES `document_sequences` WRITE;
/*!40000 ALTER TABLE `document_sequences` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_tokens`
--

DROP TABLE IF EXISTS `document_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_tokens_token_hash_unique` (`token_hash`),
  KEY `document_tokens_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `document_tokens_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_tokens`
--

LOCK TABLES `document_tokens` WRITE;
/*!40000 ALTER TABLE `document_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_tokens` ENABLE KEYS */;
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

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiries`
--

LOCK TABLES `inquiries` WRITE;
/*!40000 ALTER TABLE `inquiries` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int unsigned NOT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_id` bigint unsigned NOT NULL,
  `supersedes_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_date` date DEFAULT NULL,
  `due_date` date NOT NULL,
  `payment_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'deposit',
  `deposit_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent',
  `deposit_value` decimal(15,2) NOT NULL DEFAULT '30.00',
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `deposit_required` decimal(15,2) NOT NULL DEFAULT '0.00',
  `company_snapshot` json DEFAULT NULL,
  `customer_snapshot` json DEFAULT NULL,
  `terms` text COLLATE utf8mb4_unicode_ci,
  `void_reason` text COLLATE utf8mb4_unicode_ci,
  `issued_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_public_id_unique` (`public_id`),
  UNIQUE KEY `invoices_number_unique` (`number`),
  KEY `invoices_booking_id_foreign` (`booking_id`),
  KEY `invoices_supersedes_id_foreign` (`supersedes_id`),
  CONSTRAINT `invoices_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  CONSTRAINT `invoices_supersedes_id_foreign` FOREIGN KEY (`supersedes_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
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
  `attempts` smallint unsigned NOT NULL,
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
-- Table structure for table `media_assets`
--

DROP TABLE IF EXISTS `media_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `region_id` bigint unsigned DEFAULT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gallery` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_assets_region_id_foreign` (`region_id`),
  CONSTRAINT `media_assets_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=197 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media_assets`
--

LOCK TABLES `media_assets` WRITE;
/*!40000 ALTER TABLE `media_assets` DISABLE KEYS */;
INSERT INTO `media_assets` VALUES (190,55,'images/zanzibar-dhow.jpg','A dhow on the Zanzibar coast',1,0,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(191,55,'images/zanzibar-beach.jpg','Zanzibar coastline',1,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(192,55,'images/stone-town.jpg','Stone Town architecture',1,2,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(193,56,'images/serengeti-safari-vehicle.jpg','Safari in the Serengeti (illustrative vehicle)',1,3,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(194,56,'images/kilimanjaro-moshi.jpg','Kilimanjaro viewed from Moshi',1,4,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(195,56,'images/serengeti-lion.jpg','A lion in the Serengeti',1,5,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(196,56,'images/tanzania-safari-sunset.jpg','Tanzanian safari landscape',1,6,'2026-10-15 07:06:20','2026-10-15 07:06:20');
/*!40000 ALTER TABLE `media_assets` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_15_000001_create_tourism_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outbox_messages`
--

DROP TABLE IF EXISTS `outbox_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outbox_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` int unsigned NOT NULL DEFAULT '0',
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `processing_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `outbox_messages_public_id_unique` (`public_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outbox_messages`
--

LOCK TABLES `outbox_messages` WRITE;
/*!40000 ALTER TABLE `outbox_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `outbox_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (82,'about','About Sparkling Tours and Safaris','Travel should feel personal.\n\nThis website helps you discover Tanzania Mainland and Zanzibar, explore published tours, and plan your own itinerary. You can request help arranging accommodation at the end of a tour booking, or book an independent transport route.\n\nOur team reviews each request, checks availability, and sends an invoice for the agreed services. This demonstration content should be replaced with the operator’s own verified business story and contacts before launch.',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(83,'privacy','Privacy notice','We use the contact details, travel information and preferences you submit to review your request, communicate with you and arrange agreed services. Necessary booking details may be shared with the relevant hotel or transport provider.\n\nAccess is limited to authorized staff and protected customer links. Please avoid submitting passport scans or card details through the initial form.\n\nThe operator must publish its real contact address, retention policy, service-provider disclosures and procedures for privacy requests before launch. This is editable draft policy text for the operator to complete.',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(84,'terms','Booking terms','A submitted form is a request for review. It does not by itself confirm a hotel room, vehicle or tour. Availability, final services, price, deposit and cancellation conditions will be communicated by the operator.\n\nAccommodation preferences help our guide source a suitable stay. Any unquoted accommodation is excluded from the tour services subtotal. Independent transport is arranged on the selected route and vehicle basis.\n\nPay using the agreed invoice instructions. Staff verify payments before updating the ledger. Supplier confirmation and the agreed payment conditions determine final reservation status.\n\nThe operator must replace this editable draft with its actual cancellation, refund, delay and supplier terms before accepting real customers.',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
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
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `recorded_by` bigint unsigned NOT NULL,
  `source_payment_id` bigint unsigned DEFAULT NULL,
  `kind` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paid_on` date NOT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_method_transaction_reference_unique` (`method`,`transaction_reference`),
  KEY `payments_invoice_id_foreign` (`invoice_id`),
  KEY `payments_recorded_by_foreign` (`recorded_by`),
  KEY `payments_source_payment_id_foreign` (`source_payment_id`),
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `payments_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payments_source_payment_id_foreign` FOREIGN KEY (`source_payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preference_options`
--

DROP TABLE IF EXISTS `preference_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `preference_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region_id` bigint unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `preference_options_region_id_foreign` (`region_id`),
  CONSTRAINT `preference_options_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=925 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preference_options`
--

LOCK TABLES `preference_options` WRITE;
/*!40000 ALTER TABLE `preference_options` DISABLE KEYS */;
INSERT INTO `preference_options` VALUES (892,'type','Hotel',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(893,'type','Resort',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(894,'type','Boutique hotel',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(895,'type','Guesthouse',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(896,'type','Apartment',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(897,'type','Villa',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(898,'class','Budget',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(899,'class','Mid-range',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(900,'class','Luxury',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(901,'class','3-star preference',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(902,'class','4-star preference',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(903,'class','5-star preference',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(904,'room','Single',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(905,'room','Double',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(906,'room','Twin',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(907,'room','Family',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(908,'meal','Room only',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(909,'meal','Breakfast',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(910,'meal','Half board',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(911,'meal','Full board',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(912,'meal','All inclusive',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(913,'amenity','Beachfront',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(914,'amenity','Swimming pool',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(915,'amenity','Air conditioning',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(916,'amenity','Wi-Fi',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(917,'amenity','Accessible room',NULL,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(918,'location','Stone Town',55,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(919,'location','Nungwi',55,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(920,'location','Paje',55,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(921,'location','Jambiani',55,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(922,'location','Arusha',56,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(923,'location','Moshi',56,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(924,'location','Dar es Salaam',56,1,'2026-10-15 07:06:20','2026-10-15 07:06:20');
/*!40000 ALTER TABLE `preference_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `regions_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regions`
--

LOCK TABLES `regions` WRITE;
/*!40000 ALTER TABLE `regions` DISABLE KEYS */;
INSERT INTO `regions` VALUES (55,'Zanzibar','zanzibar','The islands and Swahili coast.','2026-10-15 07:06:20','2026-10-15 07:06:20'),(56,'Mainland','mainland','Tanzania Mainland wildlife, landscapes and culture.','2026-10-15 07:06:20','2026-10-15 07:06:20');
/*!40000 ALTER TABLE `regions` ENABLE KEYS */;
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
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES ('about_text','Discover the coast, culture and wide-open landscapes of Tanzania with a journey shaped around you. Choose a published tour or build your own plan, and let our team help arrange the details.'),('address',''),('company_name','Sparkling Tours and Safaris'),('demo_mode','1'),('email',''),('hero_heading','Find your kind of Tanzania.'),('hero_text','From quiet island shores to the wild heart of the mainland, make room for a journey that feels like yours.'),('invoice_terms','Prices, supplier availability and cancellation conditions must be agreed before final confirmation.'),('notification_email',''),('payment_instructions',''),('phone',''),('whatsapp',''),('why_text','A thoughtful trip begins with a clear conversation. Tell us your interests and we’ll help put the right pieces together.');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_booking_details`
--

DROP TABLE IF EXISTS `transport_booking_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transport_booking_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `transport_fare_id` bigint unsigned NOT NULL,
  `pickup_at` timestamp NOT NULL,
  `return_at` timestamp NULL DEFAULT NULL,
  `passengers` int unsigned NOT NULL,
  `vehicles` int unsigned NOT NULL,
  `luggage` int unsigned NOT NULL DEFAULT '0',
  `pickup_details` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dropoff_details` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `arrival_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snapshot` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transport_booking_details_booking_id_unique` (`booking_id`),
  KEY `transport_booking_details_transport_fare_id_foreign` (`transport_fare_id`),
  CONSTRAINT `transport_booking_details_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  CONSTRAINT `transport_booking_details_transport_fare_id_foreign` FOREIGN KEY (`transport_fare_id`) REFERENCES `transport_fares` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_booking_details`
--

LOCK TABLES `transport_booking_details` WRITE;
/*!40000 ALTER TABLE `transport_booking_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `transport_booking_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_fares`
--

DROP TABLE IF EXISTS `transport_fares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transport_fares` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transport_route_id` bigint unsigned NOT NULL,
  `vehicle_type_id` bigint unsigned NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `return_price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TZS',
  `pricing_basis` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'per_vehicle',
  `capacity` int unsigned NOT NULL,
  `luggage_allowance` int unsigned DEFAULT NULL,
  `minimum_passengers` int unsigned NOT NULL DEFAULT '1',
  `maximum_passengers` int unsigned NOT NULL DEFAULT '100',
  `multiple_vehicles` tinyint(1) NOT NULL DEFAULT '0',
  `maximum_vehicles` int unsigned NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transport_fares_transport_route_id_vehicle_type_id_unique` (`transport_route_id`,`vehicle_type_id`),
  KEY `transport_fares_vehicle_type_id_foreign` (`vehicle_type_id`),
  CONSTRAINT `transport_fares_transport_route_id_foreign` FOREIGN KEY (`transport_route_id`) REFERENCES `transport_routes` (`id`),
  CONSTRAINT `transport_fares_vehicle_type_id_foreign` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_fares`
--

LOCK TABLES `transport_fares` WRITE;
/*!40000 ALTER TABLE `transport_fares` DISABLE KEYS */;
INSERT INTO `transport_fares` VALUES (110,83,84,80000.00,80000.00,'TZS','per_vehicle',4,3,1,12,1,3,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(111,83,85,120000.00,120000.00,'TZS','per_vehicle',8,8,1,8,0,1,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(112,84,85,160000.00,NULL,'TZS','per_vehicle',8,8,1,8,0,1,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(113,85,86,30000.00,NULL,'TZS','per_person',20,20,1,20,0,1,1,'2026-10-15 07:06:20','2026-10-15 07:06:20');
/*!40000 ALTER TABLE `transport_fares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_routes`
--

DROP TABLE IF EXISTS `transport_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transport_routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `region_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_minutes` int unsigned NOT NULL DEFAULT '60',
  `description` text COLLATE utf8mb4_unicode_ci,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `inclusions` text COLLATE utf8mb4_unicode_ci,
  `exclusions` text COLLATE utf8mb4_unicode_ci,
  `cutoff_hours` int unsigned NOT NULL DEFAULT '0',
  `active_from` date DEFAULT NULL,
  `active_until` date DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `media_asset_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transport_routes_slug_unique` (`slug`),
  KEY `transport_routes_region_id_foreign` (`region_id`),
  KEY `transport_routes_media_asset_id_foreign` (`media_asset_id`),
  CONSTRAINT `transport_routes_media_asset_id_foreign` FOREIGN KEY (`media_asset_id`) REFERENCES `media_assets` (`id`),
  CONSTRAINT `transport_routes_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_routes`
--

LOCK TABLES `transport_routes` WRITE;
/*!40000 ALTER TABLE `transport_routes` DISABLE KEYS */;
INSERT INTO `transport_routes` VALUES (83,55,'Zanzibar Airport to Stone Town','airport-stone-town','Zanzibar Airport','Stone Town',25,'A direct transfer within the stated airport and town areas.','Provide your arrival flight number and the drop-off address within Stone Town.','Driver and standard route transfer','Unagreed stops or changes of route',2,NULL,NULL,1,NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(84,55,'Stone Town to Nungwi','stone-town-nungwi','Stone Town','Nungwi',90,'A north-coast road transfer.','Provide pickup and drop-off addresses within the stated areas.',NULL,NULL,4,NULL,NULL,1,NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(85,55,'Configured island boat transfer','illustrative-boat-transfer','Stone Town departure point','Agreed island landing point',45,'Illustrative route: the operator must replace this with a specific service before publication for real bookings.','Departure and landing arrangements require operator confirmation.',NULL,NULL,24,NULL,NULL,0,NULL,'2026-10-15 07:06:20','2026-10-15 07:06:20');
/*!40000 ALTER TABLE `transport_routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guide',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `can_invoice` tinyint(1) NOT NULL DEFAULT '0',
  `can_verify_payments` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `two_factor_last_step` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_types`
--

DROP TABLE IF EXISTS `vehicle_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'road',
  `description` text COLLATE utf8mb4_unicode_ci,
  `media_asset_id` bigint unsigned DEFAULT NULL,
  `capacity` int unsigned NOT NULL,
  `luggage_allowance` int unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_types_media_asset_id_foreign` (`media_asset_id`),
  CONSTRAINT `vehicle_types_media_asset_id_foreign` FOREIGN KEY (`media_asset_id`) REFERENCES `media_assets` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_types`
--

LOCK TABLES `vehicle_types` WRITE;
/*!40000 ALTER TABLE `vehicle_types` DISABLE KEYS */;
INSERT INTO `vehicle_types` VALUES (84,'Private car','road','Illustrative private road transfer option.',NULL,4,3,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(85,'Minivan','road','Illustrative group road transfer option.',NULL,8,8,1,'2026-10-15 07:06:20','2026-10-15 07:06:20'),(86,'Boat transfer','sea','Illustrative marine transfer; subject to operator and sea conditions.',190,20,20,1,'2026-10-15 07:06:20','2026-10-15 07:06:20');
/*!40000 ALTER TABLE `vehicle_types` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
