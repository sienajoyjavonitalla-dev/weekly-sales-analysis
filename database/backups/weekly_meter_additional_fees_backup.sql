-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: weekly_sales_analysis
-- ------------------------------------------------------
-- Server version	8.0.30

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
-- Table structure for table `weekly_meter_additional_fees`
--

DROP TABLE IF EXISTS `weekly_meter_additional_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `weekly_meter_additional_fees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint unsigned NOT NULL,
  `month` tinyint unsigned NOT NULL,
  `model_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `weekly_meter_additional_fees_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `wm_fees_period_model_idx` (`year`,`month`,`model_label`),
  KEY `weekly_meter_additional_fees_year_index` (`year`),
  KEY `weekly_meter_additional_fees_month_index` (`month`),
  KEY `weekly_meter_additional_fees_model_label_index` (`model_label`),
  CONSTRAINT `weekly_meter_additional_fees_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weekly_meter_additional_fees`
--

LOCK TABLES `weekly_meter_additional_fees` WRITE;
/*!40000 ALTER TABLE `weekly_meter_additional_fees` DISABLE KEYS */;
INSERT INTO `weekly_meter_additional_fees` VALUES (6,2026,4,'RHP L6 Kits',2270.12,0,2,'2026-09-29 02:03:58','2026-09-29 02:03:58'),(7,2026,4,'RHP L6 Readr/Sens Pk',423.42,0,2,'2026-09-29 02:04:31','2026-09-29 02:04:31'),(21,2026,4,'RHP L6 5Pk Sensors*',1998.05,0,2,'2026-09-29 02:35:57','2026-09-29 02:35:57'),(22,2026,4,'RHP L6 5Pk Sensors*',623.05,1,2,'2026-09-29 02:35:57','2026-09-29 02:35:57'),(23,2026,4,'RHP L6 5Pk Sensors*',371.38,2,2,'2026-09-29 02:35:57','2026-09-29 02:35:57'),(24,2026,4,'RHP L6 5Pk Sensors*',1937.85,3,2,'2026-09-29 02:35:57','2026-09-29 02:35:57'),(25,2026,4,'RHP L6 Misc',699.57,0,2,'2026-09-29 03:03:58','2026-09-29 03:03:58'),(26,2026,4,'C555 Concrete Kits',3006.42,0,2,'2026-10-05 02:27:27','2026-10-05 02:27:27'),(27,2026,4,'Orion 910',1336.57,0,2,'2026-10-05 02:42:02','2026-10-05 02:42:02'),(28,2026,4,'Orion 920',73.12,0,2,'2026-10-05 02:42:32','2026-10-05 02:42:32'),(29,2026,4,'Orion 930',2678.23,0,2,'2026-10-05 02:42:59','2026-10-05 02:42:59'),(30,2026,4,'Orion 950',2961.36,0,2,'2026-10-05 02:45:12','2026-10-05 02:45:12'),(31,2026,4,'Handmeter Misc',38.26,0,2,'2026-10-05 02:45:33','2026-10-05 02:45:33');
/*!40000 ALTER TABLE `weekly_meter_additional_fees` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-16 19:41:58
