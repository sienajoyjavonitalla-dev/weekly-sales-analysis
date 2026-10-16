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
-- Table structure for table `report_totals`
--

DROP TABLE IF EXISTS `report_totals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_totals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `import_batch_id` bigint unsigned NOT NULL,
  `report_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metric_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(15,4) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_totals_metric_unique` (`import_batch_id`,`report_type`,`metric_key`),
  KEY `report_totals_report_type_index` (`report_type`),
  KEY `report_totals_period_label_index` (`period_label`),
  KEY `report_totals_metric_key_index` (`metric_key`),
  CONSTRAINT `report_totals_import_batch_id_foreign` FOREIGN KEY (`import_batch_id`) REFERENCES `import_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_totals`
--

LOCK TABLES `report_totals` WRITE;
/*!40000 ALTER TABLE `report_totals` DISABLE KEYS */;
INSERT INTO `report_totals` VALUES (17,20,'total_sales_report','February 2026','ptd_orders',NULL,0.00,'{\"label\": \"PTD Orders\"}','2026-07-05 23:21:16','2026-07-05 23:21:16'),(18,20,'total_sales_report','February 2026','open_orders_current_month',NULL,0.00,'{\"label\": \"Open Orders for this month\"}','2026-07-05 23:21:16','2026-07-05 23:21:16'),(19,20,'total_sales_report','February 2026','open_orders_next_month',NULL,0.00,'{\"label\": \"Open Orders for next month\"}','2026-07-05 23:21:16','2026-07-05 23:21:16'),(20,20,'total_sales_report','February 2026','open_orders_future',NULL,0.00,'{\"label\": \"Open Orders beyond next month\"}','2026-07-05 23:21:16','2026-07-05 23:21:16'),(25,28,'total_sales_report','March 2026','ptd_orders',NULL,0.00,'{\"label\": \"PTD Orders\"}','2026-08-07 02:10:43','2026-08-07 02:10:43'),(26,28,'total_sales_report','March 2026','open_orders_current_month',NULL,0.00,'{\"label\": \"Open Orders for this month\"}','2026-08-07 02:10:43','2026-08-07 02:10:43'),(27,28,'total_sales_report','March 2026','open_orders_next_month',NULL,0.00,'{\"label\": \"Open Orders for next month\"}','2026-08-07 02:10:43','2026-08-07 02:10:43'),(28,28,'total_sales_report','March 2026','open_orders_future',NULL,0.00,'{\"label\": \"Open Orders beyond next month\"}','2026-08-07 02:10:43','2026-08-07 02:10:43'),(29,29,'total_sales_report','April 2026','ptd_orders',NULL,0.00,'{\"label\": \"PTD Orders\"}','2026-09-21 02:27:24','2026-09-21 02:27:24'),(30,29,'total_sales_report','April 2026','open_orders_current_month',NULL,0.00,'{\"label\": \"Open Orders for this month\"}','2026-09-21 02:27:24','2026-09-21 02:27:24'),(31,29,'total_sales_report','April 2026','open_orders_next_month',NULL,0.00,'{\"label\": \"Open Orders for next month\"}','2026-09-21 02:27:24','2026-09-21 02:27:24'),(32,29,'total_sales_report','April 2026','open_orders_future',NULL,0.00,'{\"label\": \"Open Orders beyond next month\"}','2026-09-21 02:27:24','2026-09-21 02:27:24'),(33,30,'total_sales_report','April 2026','ptd_orders',NULL,0.00,'{\"label\": \"PTD Orders\"}','2026-09-21 22:35:35','2026-09-21 22:35:35'),(34,30,'total_sales_report','April 2026','open_orders_current_month',NULL,0.00,'{\"label\": \"Open Orders for this month\"}','2026-09-21 22:35:35','2026-09-21 22:35:35'),(35,30,'total_sales_report','April 2026','open_orders_next_month',NULL,0.00,'{\"label\": \"Open Orders for next month\"}','2026-09-21 22:35:35','2026-09-21 22:35:35'),(36,30,'total_sales_report','April 2026','open_orders_future',NULL,0.00,'{\"label\": \"Open Orders beyond next month\"}','2026-09-21 22:35:35','2026-09-21 22:35:35'),(37,31,'total_sales_report','April 2026','ptd_orders',NULL,0.00,'{\"label\": \"PTD Orders\"}','2026-09-21 22:35:39','2026-09-21 22:35:39'),(38,31,'total_sales_report','April 2026','open_orders_current_month',NULL,0.00,'{\"label\": \"Open Orders for this month\"}','2026-09-21 22:35:39','2026-09-21 22:35:39'),(39,31,'total_sales_report','April 2026','open_orders_next_month',NULL,0.00,'{\"label\": \"Open Orders for next month\"}','2026-09-21 22:35:39','2026-09-21 22:35:39'),(40,31,'total_sales_report','April 2026','open_orders_future',NULL,0.00,'{\"label\": \"Open Orders beyond next month\"}','2026-09-21 22:35:39','2026-09-21 22:35:39'),(41,32,'total_sales_report','April 2026','ptd_orders',NULL,0.00,'{\"label\": \"PTD Orders\"}','2026-09-21 22:35:47','2026-09-21 22:35:47'),(42,32,'total_sales_report','April 2026','open_orders_current_month',NULL,0.00,'{\"label\": \"Open Orders for this month\"}','2026-09-21 22:35:47','2026-09-21 22:35:47'),(43,32,'total_sales_report','April 2026','open_orders_next_month',NULL,0.00,'{\"label\": \"Open Orders for next month\"}','2026-09-21 22:35:47','2026-09-21 22:35:47'),(44,32,'total_sales_report','April 2026','open_orders_future',NULL,0.00,'{\"label\": \"Open Orders beyond next month\"}','2026-09-21 22:35:47','2026-09-21 22:35:47'),(45,33,'total_sales_report','April 2026','ptd_orders',NULL,0.00,'{\"label\": \"PTD Orders\"}','2026-09-21 22:35:52','2026-09-21 22:35:52'),(46,33,'total_sales_report','April 2026','open_orders_current_month',NULL,0.00,'{\"label\": \"Open Orders for this month\"}','2026-09-21 22:35:52','2026-09-21 22:35:52'),(47,33,'total_sales_report','April 2026','open_orders_next_month',NULL,0.00,'{\"label\": \"Open Orders for next month\"}','2026-09-21 22:35:52','2026-09-21 22:35:52'),(48,33,'total_sales_report','April 2026','open_orders_future',NULL,0.00,'{\"label\": \"Open Orders beyond next month\"}','2026-09-21 22:35:52','2026-09-21 22:35:52');
/*!40000 ALTER TABLE `report_totals` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-16 19:38:12
