-- MySQL dump 10.13  Distrib 9.6.0, for macos26.2 (arm64)
--
-- Host: localhost    Database: fee_management_system
-- ------------------------------------------------------
-- Server version	9.6.0

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
-- Table structure for table `academic_sessions`
--
USE `u638211070_ssvm_litipara`;

DROP TABLE IF EXISTS `academic_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `session_name` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_name` (`session_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_sessions`
--

LOCK TABLES `academic_sessions` WRITE;
/*!40000 ALTER TABLE `academic_sessions` DISABLE KEYS */;
INSERT INTO `academic_sessions` VALUES (1,'2026-2027','2026-04-01','2027-03-31',1,'2026-02-13 16:46:46');
/*!40000 ALTER TABLE `academic_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('sysadmin','admin','operator') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `account_locked_until` datetime DEFAULT NULL,
  `failed_login_attempts` int DEFAULT '0',
  `session_id` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'sysadmin','$2y$12$JfM7JZGyrxFuqGUJZWV4muGAyDOc6uk5/r3HpWw6Cw3VjR19Lmatu','System Administrator','sysadmin@school.com','sysadmin','2026-02-13 16:46:46','2026-04-05 13:55:46',NULL,0,'70b08fcb6e2759627c1d84c817973072','active'),(2,'admin','$2y$12$mEZJAmO08xdW8ENpg9K.quMKZZfetqMlM9QysJstEztfhT1gnASOe','Administrator','tempmail831004@gmail.com','admin','2026-02-13 16:46:46','2026-04-05 07:22:39',NULL,0,NULL,'active'),(3,'staff','$2y$12$UNBf01isIOc1.E05W2c2luKCkm0rNi1CAHha2KQKInKdVuMZKJNxe','staff name','rockalmax16@gmail.com','operator','2026-02-16 17:04:32','2026-02-26 14:31:01',NULL,0,NULL,'inactive');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `new_values` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_table_name` (`table_name`)
) ENGINE=InnoDB AUTO_INCREMENT=435 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for all system activities';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (374,1,'sysadmin','AUDIT_LOG_RESET','audit_log',NULL,NULL,'{\"deleted_entries\":42}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-03 19:07:45'),(375,1,'sysadmin','SESSION_TIMEOUT','session',NULL,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-04 05:06:55'),(376,1,'sysadmin','LOGOUT','admin',1,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-04 05:06:55'),(377,2,'admin','LOGIN_SUCCESS','admin',2,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-04 05:10:00'),(378,2,'admin','LOGOUT','admin',2,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-04 05:10:19'),(379,2,'admin','LOGIN_SUCCESS','admin',2,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-04 05:21:48'),(380,2,'admin','SESSION_TIMEOUT','session',NULL,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:21:41'),(381,2,'admin','LOGOUT','admin',2,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:21:41'),(382,2,'admin','LOGIN_SUCCESS','admin',2,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:22:39'),(383,2,'admin','LOGOUT','admin',2,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:22:52'),(384,1,'sysadmin','LOGIN_SUCCESS','admin',1,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:22:59'),(385,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:24:13'),(386,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:29:32'),(387,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:29:33'),(388,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:29:35'),(389,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:29:37'),(390,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:29:38'),(391,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:40:53'),(392,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:54:48'),(393,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:54:49'),(394,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:54:51'),(395,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:54:52'),(396,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:55:00'),(397,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:55:01'),(398,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:55:02'),(399,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:55:03'),(400,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:55:03'),(401,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:55:04'),(402,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:55:06'),(403,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:56:11'),(404,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:56:11'),(405,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:56:14'),(406,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:56:15'),(407,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 08:01:09'),(408,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 08:06:41'),(409,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 08:16:18'),(410,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 08:16:46'),(411,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 08:45:54'),(412,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 08:48:24'),(413,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 09:17:48'),(414,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 09:18:04'),(415,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 09:30:03'),(416,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 09:30:13'),(417,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 09:30:22'),(418,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 09:31:15'),(419,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 10:43:36'),(420,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 10:44:01'),(421,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 10:45:17'),(422,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 10:45:23'),(423,1,'sysadmin','SESSION_TIMEOUT','session',NULL,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 13:55:31'),(424,1,'sysadmin','LOGOUT','admin',1,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 13:55:31'),(425,1,'sysadmin','LOGIN_SUCCESS','admin',1,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 13:55:46'),(426,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 14:59:34'),(427,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 14:59:42'),(428,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 15:03:09'),(429,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 15:03:30'),(430,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 15:07:14'),(431,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 15:07:31'),(432,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"monthly\"}','{\"fee_mode\":\"annual\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 16:58:28'),(433,1,'sysadmin','FEE_MODE_CHANGED','settings',NULL,'{\"fee_mode\":\"annual\"}','{\"fee_mode\":\"monthly\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 16:58:39'),(434,1,'sysadmin','RESET_ALL_LOCKOUTS','admin',NULL,NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 17:17:15');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `class_id` int NOT NULL AUTO_INCREMENT,
  `class_name` varchar(50) NOT NULL,
  `class_numeric` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'Nursery',0,'2026-02-13 16:46:46','active'),(2,'LKG',0,'2026-02-13 16:46:46','active'),(3,'UKG',0,'2026-02-13 16:46:46','active'),(4,'Class 1',1,'2026-02-13 16:46:46','active'),(5,'Class 2',2,'2026-02-13 16:46:46','active'),(6,'Class 3',3,'2026-02-13 16:46:46','active'),(7,'Class 4',4,'2026-02-13 16:46:46','active'),(8,'Class 5',5,'2026-02-13 16:46:46','active'),(9,'Class 6',6,'2026-02-13 16:46:46','active'),(10,'Class 7',7,'2026-02-13 16:46:46','active'),(11,'Class 8',8,'2026-02-13 16:46:46','active'),(12,'Class 9',9,'2026-02-13 16:46:46','active'),(13,'Class 10',10,'2026-02-13 16:46:46','active'),(14,'Class 11',11,'2026-02-13 16:46:46','active'),(15,'Class 12',12,'2026-02-13 16:46:46','active');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_collection`
--

DROP TABLE IF EXISTS `fee_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_collection` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `fee_structure_id` int DEFAULT NULL,
  `payment_date` date NOT NULL,
  `tuition_fee_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `exam_fee_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `library_fee_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sports_fee_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `lab_fee_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `transport_fee_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other_charges_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fine` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_paid` decimal(10,2) GENERATED ALWAYS AS ((((((((((`tuition_fee_paid` + `exam_fee_paid`) + `sports_fee_paid`) + `other_charges_paid`) + `custom_admission_fee_paid`) + `custom_development_fee_paid`) + `custom_ceromany_festival_fee_paid`) + `custom_hostel_fees_paid`) + `fine`) - `discount`)) STORED,
  `payment_mode` enum('Cash','Card','UPI','Net Banking','Cheque') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `remarks` text,
  `collected_by` int NOT NULL,
  `fee_month` tinyint DEFAULT NULL COMMENT 'Only used in monthly mode: 1=April ... 12=March',
  `monthly_fee_structure_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `custom_admission_fee_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custom_development_fee_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custom_ceromany_festival_fee_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custom_hostel_fees_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `collected_by` (`collected_by`),
  KEY `idx_payment_student` (`student_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_payment_receipt` (`receipt_no`),
  KEY `idx_fee_collection_structure` (`fee_structure_id`),
  KEY `idx_fee_collection_month` (`fee_month`),
  KEY `idx_fee_collection_monthly_struct` (`monthly_fee_structure_id`),
  CONSTRAINT `fee_collection_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  CONSTRAINT `fee_collection_ibfk_2` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structure` (`fee_structure_id`),
  CONSTRAINT `fee_collection_ibfk_3` FOREIGN KEY (`collected_by`) REFERENCES `admin` (`admin_id`),
  CONSTRAINT `fee_collection_ibfk_4` FOREIGN KEY (`monthly_fee_structure_id`) REFERENCES `monthly_fee_structure` (`monthly_fee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_collection`
--

LOCK TABLES `fee_collection` WRITE;
/*!40000 ALTER TABLE `fee_collection` DISABLE KEYS */;
INSERT INTO `fee_collection` (`payment_id`, `receipt_no`, `student_id`, `academic_year`, `fee_structure_id`, `payment_date`, `tuition_fee_paid`, `exam_fee_paid`, `library_fee_paid`, `sports_fee_paid`, `lab_fee_paid`, `transport_fee_paid`, `other_charges_paid`, `fine`, `discount`, `payment_mode`, `transaction_id`, `remarks`, `collected_by`, `fee_month`, `monthly_fee_structure_id`, `created_at`, `custom_admission_fee_paid`, `custom_development_fee_paid`, `custom_ceromany_festival_fee_paid`, `custom_hostel_fees_paid`) VALUES (59,'RCPT202604052215',52,'2026-2027',NULL,'2026-04-05',5400.00,1600.00,700.00,1450.00,1150.00,550.00,850.00,0.00,0.00,'Cash','','',1,1,229,'2026-04-05 16:14:24',0.00,0.00,0.00,0.00),(60,'RCPT202604057767',31,'2026-2027',NULL,'2026-04-05',450.00,1700.00,1000.00,600.00,1850.00,1550.00,300.00,0.00,0.00,'UPI','','',1,2,218,'2026-04-05 16:15:10',0.00,0.00,0.00,0.00),(68,'RCPT202604057316',14,'2026-2027',NULL,'2026-04-05',4600.00,1650.00,800.00,1200.00,1450.00,800.00,1000.00,0.00,0.00,'Cash','','',1,1,217,'2026-04-05 16:27:56',0.00,0.00,0.00,0.00),(70,'RCPT202604056780',31,'2026-2027',NULL,'2026-04-05',4600.00,1650.00,800.00,1200.00,1450.00,800.00,1000.00,0.00,0.00,'Cash','','',1,1,217,'2026-04-05 16:29:49',0.00,0.00,0.00,0.00),(71,'RCPT202604053693',38,'2026-2027',NULL,'2026-04-05',4600.00,1650.00,800.00,1200.00,1450.00,800.00,1000.00,0.00,0.00,'Cash','','',1,1,217,'2026-04-05 16:31:41',0.00,0.00,0.00,0.00),(72,'RCPT202604052683',35,'2026-2027',NULL,'2026-04-05',150.00,300.00,950.00,1350.00,1200.00,2250.00,450.00,0.00,0.00,'Cash','','',1,2,242,'2026-04-05 16:33:01',0.00,0.00,0.00,0.00),(73,'RCPT202604059081',52,'2026-2027',NULL,'2026-04-05',100.00,1600.00,700.00,1450.00,1150.00,550.00,850.00,0.00,0.00,'Cash','','',1,3,231,'2026-04-05 16:34:45',0.00,0.00,0.00,0.00),(74,'RCPT202604051257',13,'2026-2027',NULL,'2026-04-05',2150.00,250.00,850.00,700.00,500.00,2250.00,750.00,0.00,0.00,'Cash','','',1,4,244,'2026-04-05 16:53:03',0.00,0.00,0.00,0.00),(75,'RCPT202604055286',14,'2026-2027',NULL,'2026-04-05',6250.00,1100.00,350.00,300.00,1700.00,2950.00,950.00,0.00,0.00,'Card','','',1,3,219,'2026-04-05 16:53:29',0.00,0.00,0.00,0.00),(76,'RCPT202604051016',52,'2026-2027',NULL,'2026-04-03',5400.00,1600.00,700.00,1450.00,1150.00,550.00,850.00,0.00,0.00,'Cash','','',1,9,237,'2026-04-05 16:54:58',0.00,0.00,0.00,0.00);
/*!40000 ALTER TABLE `fee_collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_structure`
--

DROP TABLE IF EXISTS `fee_structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_structure` (
  `fee_structure_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `tuition_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `exam_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `library_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sports_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `lab_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `transport_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other_charges` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_fee` decimal(10,2) GENERATED ALWAYS AS ((((((((`tuition_fee` + `exam_fee`) + `sports_fee`) + `other_charges`) + `custom_admission_fee`) + `custom_development_fee`) + `custom_ceromany_festival_fee`) + `custom_hostel_fees`)) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  `custom_admission_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custom_development_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custom_ceromany_festival_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custom_hostel_fees` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`fee_structure_id`),
  UNIQUE KEY `unique_class_year` (`class_id`,`academic_year`),
  KEY `idx_fee_structure_class_year` (`class_id`,`academic_year`),
  CONSTRAINT `fee_structure_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structure`
--

LOCK TABLES `fee_structure` WRITE;
/*!40000 ALTER TABLE `fee_structure` DISABLE KEYS */;
INSERT INTO `fee_structure` (`fee_structure_id`, `class_id`, `academic_year`, `tuition_fee`, `exam_fee`, `library_fee`, `sports_fee`, `lab_fee`, `transport_fee`, `other_charges`, `created_at`, `updated_at`, `status`, `custom_admission_fee`, `custom_development_fee`, `custom_ceromany_festival_fee`, `custom_hostel_fees`) VALUES (19,4,'2026-2027',122.00,23.00,0.00,0.00,0.00,0.00,0.00,'2026-04-03 19:04:33','2026-04-03 19:04:33','active',0.00,0.00,0.00,0.00),(20,1,'2026-2027',1111.00,11.00,11.00,11.00,11.00,11.00,11.00,'2026-04-05 07:25:25','2026-04-05 07:25:25','active',0.00,0.00,0.00,0.00),(21,5,'2026-2027',12.00,21312.00,12312.00,123.00,12.00,0.00,0.00,'2026-04-05 07:26:03','2026-04-05 07:26:03','active',0.00,0.00,0.00,0.00);
/*!40000 ALTER TABLE `fee_structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_structure_audit`
--

DROP TABLE IF EXISTS `fee_structure_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_structure_audit` (
  `audit_id` int NOT NULL AUTO_INCREMENT,
  `fee_structure_id` int NOT NULL,
  `fee_type_id` int NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `recorded_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  KEY `idx_fee_structure` (`fee_structure_id`),
  KEY `idx_fee_type` (`fee_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structure_audit`
--

LOCK TABLES `fee_structure_audit` WRITE;
/*!40000 ALTER TABLE `fee_structure_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_structure_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_types`
--

DROP TABLE IF EXISTS `fee_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_types` (
  `fee_type_id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'System identifier: tuition_fee, exam_fee, etc.',
  `label` varchar(100) NOT NULL COMMENT 'Display name: Tuition Fee, Exam Fee, etc.',
  `description` text COMMENT 'Detailed description of fee type',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = active, 0 = deactivated (soft delete)',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT 'Display order in forms and reports',
  `column_name` varchar(50) NOT NULL COMMENT 'Database column: tuition_fee, exam_fee, etc.',
  `is_system_defined` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = hardcoded, 0 = custom',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`fee_type_id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `unique_code_active` (`code`,`is_active`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=3613 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_types`
--

LOCK TABLES `fee_types` WRITE;
/*!40000 ALTER TABLE `fee_types` DISABLE KEYS */;
INSERT INTO `fee_types` VALUES (1,'tuition_fee','Tuition Fee','Core tuition charges for academic instruction',1,1,'tuition_fee',1,'2026-04-03 09:39:00','2026-04-03 18:13:22'),(2,'exam_fee','Exam Fee','Examination fees including assessments and tests',1,2,'exam_fee',1,'2026-04-03 09:39:00','2026-04-03 18:14:30'),(3,'library_fee','Library Fee','Library access and reference materials',0,3,'library_fee',1,'2026-04-03 09:39:00','2026-04-05 17:03:10'),(4,'sports_fee','Sports Fee','Sports activities facilities and programs',1,4,'sports_fee',1,'2026-04-03 09:39:00','2026-04-03 18:39:46'),(5,'lab_fee','Lab Fee','Laboratory access and equipment usage',0,5,'lab_fee',1,'2026-04-03 09:39:00','2026-04-05 17:08:55'),(6,'transport_fee','Transport Fee','School transportation and conveyance',0,6,'transport_fee',1,'2026-04-03 09:39:00','2026-04-05 17:06:30'),(7,'other_charges','Other Charges','Miscellaneous charges and fees',1,7,'other_charges',1,'2026-04-03 09:39:00','2026-04-03 19:01:17'),(3441,'custom_admission_fee','Admission Fee','up-front charge required to secure enrollment at an institution',1,8,'custom_admission_fee',0,'2026-04-05 17:04:56','2026-04-05 17:05:30'),(3470,'custom_development_fee','Development Fee','a charge levied to fund infrastructure improvements, project-specific upgrades, or operational costs',1,9,'custom_development_fee',0,'2026-04-05 17:06:18','2026-04-05 17:06:18'),(3520,'custom_ceromany_festival_fee','Ceromany / Festival Fee','A ceremony or festival fee is a charge imposed for hosting a formal event, ritual, or celebration',1,10,'custom_ceromany_festival_fee',0,'2026-04-05 17:11:57','2026-04-05 17:11:57'),(3528,'custom_hostel_fees','Hostel Fees','The charges paid for accommodation, often including amenities like mess (food), electricity, water, and security.',1,11,'custom_hostel_fees',0,'2026-04-05 17:13:10','2026-04-05 17:13:10');
/*!40000 ALTER TABLE `fee_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_types_audit`
--

DROP TABLE IF EXISTS `fee_types_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_types_audit` (
  `audit_id` int NOT NULL AUTO_INCREMENT,
  `fee_type_id` int DEFAULT NULL,
  `action` enum('created','updated','deleted') NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `changed_by` int NOT NULL COMMENT 'admin.admin_id',
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  KEY `idx_fee_type_id` (`fee_type_id`),
  KEY `idx_action` (`action`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_types_audit`
--

LOCK TABLES `fee_types_audit` WRITE;
/*!40000 ALTER TABLE `fee_types_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_types_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `attempt_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attempt_id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track login attempts for rate limiting';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (1,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-13 18:30:39'),(2,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-13 18:37:29'),(3,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-13 18:37:48'),(5,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-14 16:58:24'),(6,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 13:16:56'),(7,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 13:26:44'),(8,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 13:58:12'),(9,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 14:08:27'),(11,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 17:03:41'),(12,'staff','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 17:04:50'),(13,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 17:08:00'),(14,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 17:31:03'),(16,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 17:39:38'),(17,'staff','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 18:01:02'),(18,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 18:01:12'),(22,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-16 18:02:06'),(25,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-17 10:36:34'),(26,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 13:29:21'),(32,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 13:36:34'),(33,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 13:36:48'),(40,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 13:40:35'),(41,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 13:40:52'),(47,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 13:45:38'),(52,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 15:08:59'),(53,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 15:09:17'),(54,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 15:11:33'),(55,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 15:22:35'),(56,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 15:22:51'),(57,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-18 18:50:09'),(58,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-19 16:18:33'),(59,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-20 15:00:56'),(60,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 13:39:50'),(62,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 13:42:42'),(63,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 13:48:19'),(65,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 14:22:45'),(66,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 14:24:42'),(67,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 14:24:58'),(68,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 14:27:48'),(69,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 14:28:13'),(70,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 14:29:23'),(71,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 14:30:14'),(72,'staff','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 14:31:01'),(77,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 14:54:55'),(82,'ADM20260102','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 15:49:14'),(83,'ADM20260137','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 15:57:00'),(84,'ADM20260103','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 15:57:51'),(85,'ADM20260103','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 16:03:04'),(86,'ADM20260103','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 16:03:23'),(88,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 16:10:54'),(89,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 16:11:07'),(91,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 16:12:26'),(92,'ADM20260103','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 16:13:10'),(98,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 16:22:49'),(99,'ADM202q60103','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 16:26:57'),(104,'ADM20260103','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 16:57:46'),(105,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 16:57:58'),(106,'ADM20260124','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 17:11:38'),(107,'ADM20260137','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 17:56:16'),(108,'ADM202q60103','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 17:56:45'),(109,'sysadmin','::1',1,'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36','2026-03-09 18:49:08'),(110,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 18:49:40'),(111,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-04 05:10:00'),(112,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-04 05:21:48'),(113,'admin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:22:39'),(114,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 07:22:59'),(115,'sysadmin','::1',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-05 13:55:46');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monthly_fee_structure`
--

DROP TABLE IF EXISTS `monthly_fee_structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monthly_fee_structure` (
  `monthly_fee_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `fee_month` tinyint NOT NULL COMMENT '1=April, 2=May, ..., 12=March',
  `month_label` varchar(20) NOT NULL COMMENT 'Display name: April, May, etc.',
  `tuition_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `exam_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `library_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sports_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `lab_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `transport_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other_charges` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_fee` decimal(10,2) GENERATED ALWAYS AS ((((((((`tuition_fee` + `exam_fee`) + `sports_fee`) + `other_charges`) + `custom_admission_fee`) + `custom_development_fee`) + `custom_ceromany_festival_fee`) + `custom_hostel_fees`)) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  `custom_admission_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custom_development_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custom_ceromany_festival_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custom_hostel_fees` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`monthly_fee_id`),
  UNIQUE KEY `unique_class_year_month` (`class_id`,`academic_year`,`fee_month`),
  KEY `idx_monthly_fee_class_year` (`class_id`,`academic_year`),
  CONSTRAINT `monthly_fee_structure_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monthly_fee_structure`
--

LOCK TABLES `monthly_fee_structure` WRITE;
/*!40000 ALTER TABLE `monthly_fee_structure` DISABLE KEYS */;
INSERT INTO `monthly_fee_structure` (`monthly_fee_id`, `class_id`, `academic_year`, `fee_month`, `month_label`, `tuition_fee`, `exam_fee`, `library_fee`, `sports_fee`, `lab_fee`, `transport_fee`, `other_charges`, `created_at`, `updated_at`, `status`, `custom_admission_fee`, `custom_development_fee`, `custom_ceromany_festival_fee`, `custom_hostel_fees`) VALUES (217,4,'2026-2027',1,'April',4600.00,1650.00,800.00,1200.00,1450.00,800.00,1000.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(218,4,'2026-2027',2,'May',7450.00,1700.00,1000.00,600.00,1850.00,1550.00,300.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(219,4,'2026-2027',3,'June',6250.00,1100.00,350.00,300.00,1700.00,2950.00,950.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(220,4,'2026-2027',4,'July',4450.00,1650.00,500.00,1300.00,450.00,2750.00,150.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(221,4,'2026-2027',5,'August',3650.00,1350.00,900.00,1200.00,1600.00,2900.00,300.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(222,4,'2026-2027',6,'September',2500.00,550.00,750.00,850.00,600.00,2450.00,950.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(223,4,'2026-2027',7,'October',5350.00,1750.00,450.00,1100.00,800.00,1350.00,350.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(224,4,'2026-2027',8,'November',3000.00,1150.00,950.00,1350.00,1400.00,2500.00,950.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(225,4,'2026-2027',9,'December',5200.00,1300.00,700.00,100.00,850.00,2500.00,1000.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(226,4,'2026-2027',10,'January',9950.00,1400.00,850.00,1150.00,1750.00,2850.00,450.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(227,4,'2026-2027',11,'February',6350.00,1450.00,250.00,700.00,750.00,3000.00,450.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(228,4,'2026-2027',12,'March',7550.00,250.00,100.00,700.00,900.00,1450.00,100.00,'2026-04-03 19:02:53','2026-04-03 19:02:53','active',0.00,0.00,0.00,0.00),(229,1,'2026-2027',1,'April',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(230,1,'2026-2027',2,'May',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(231,1,'2026-2027',3,'June',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(232,1,'2026-2027',4,'July',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(233,1,'2026-2027',5,'August',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(234,1,'2026-2027',6,'September',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(235,1,'2026-2027',7,'October',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(236,1,'2026-2027',8,'November',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(237,1,'2026-2027',9,'December',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(238,1,'2026-2027',10,'January',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(239,1,'2026-2027',11,'February',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(240,1,'2026-2027',12,'March',100.00,0.00,700.00,0.00,1150.00,550.00,0.00,'2026-04-05 07:23:29','2026-04-05 17:16:07','active',0.00,0.00,0.00,0.00),(241,5,'2026-2027',1,'April',3800.00,500.00,600.00,800.00,900.00,2800.00,300.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(242,5,'2026-2027',2,'May',2450.00,300.00,950.00,1350.00,1200.00,2250.00,450.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(243,5,'2026-2027',3,'June',3900.00,400.00,250.00,250.00,1900.00,1700.00,250.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(244,5,'2026-2027',4,'July',2150.00,250.00,850.00,700.00,500.00,2250.00,750.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(245,5,'2026-2027',5,'August',4250.00,800.00,450.00,400.00,1600.00,800.00,550.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(246,5,'2026-2027',6,'September',5200.00,1050.00,900.00,500.00,850.00,950.00,700.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(247,5,'2026-2027',7,'October',6500.00,1450.00,500.00,1050.00,1600.00,1250.00,350.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(248,5,'2026-2027',8,'November',6550.00,500.00,350.00,250.00,350.00,1350.00,300.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(249,5,'2026-2027',9,'December',6500.00,300.00,350.00,1000.00,1650.00,2950.00,600.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(250,5,'2026-2027',10,'January',6250.00,500.00,550.00,1100.00,700.00,950.00,650.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(251,5,'2026-2027',11,'February',5050.00,1700.00,600.00,1100.00,950.00,2800.00,150.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00),(252,5,'2026-2027',12,'March',5250.00,700.00,150.00,100.00,400.00,1300.00,600.00,'2026-04-05 07:34:48','2026-04-05 07:34:48','active',0.00,0.00,0.00,0.00);
/*!40000 ALTER TABLE `monthly_fee_structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `token_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Password reset tokens';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES (1,2,'a6fd2267f5246cd6aa05f1372f16b8d2bd8644aef036529ac2898f92292167c9','2026-02-13 19:38:21',0,'::1','2026-02-13 18:38:21'),(2,2,'2fbd49e8bc0471840aaa71273a5ba68a14fc37f28d9be250391c373851fdb721','2026-02-13 19:40:06',0,'::1','2026-02-13 18:40:06');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_versions`
--

DROP TABLE IF EXISTS `schema_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_versions` (
  `version_id` int NOT NULL AUTO_INCREMENT,
  `version_name` varchar(50) NOT NULL,
  `description` text,
  `applied_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','applied','failed') DEFAULT 'applied',
  PRIMARY KEY (`version_id`),
  UNIQUE KEY `version_name` (`version_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_versions`
--

LOCK TABLES `schema_versions` WRITE;
/*!40000 ALTER TABLE `schema_versions` DISABLE KEYS */;
INSERT INTO `schema_versions` VALUES (1,'master_complete_schema','Complete Fee Management System with all features','2026-04-03 09:39:01','applied');
/*!40000 ALTER TABLE `schema_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `section_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `section_name` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `unique_class_section` (`class_id`,`section_name`),
  CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sections`
--

LOCK TABLES `sections` WRITE;
/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
INSERT INTO `sections` VALUES (1,1,'A','2026-02-13 16:46:46','active'),(2,2,'A','2026-02-13 16:46:46','active'),(3,3,'A','2026-02-13 16:46:46','active'),(4,4,'A','2026-02-13 16:46:46','active'),(5,5,'A','2026-02-13 16:46:46','active'),(6,6,'A','2026-02-13 16:46:46','active'),(7,7,'A','2026-02-13 16:46:46','active'),(8,8,'A','2026-02-13 16:46:46','active'),(9,9,'A','2026-02-13 16:46:46','active'),(10,10,'A','2026-02-13 16:46:46','active'),(11,11,'A','2026-02-13 16:46:46','active'),(12,12,'A','2026-02-13 16:46:46','active'),(13,13,'A','2026-02-13 16:46:46','active'),(14,14,'A','2026-02-13 16:46:46','active'),(15,15,'A','2026-02-13 16:46:46','active'),(16,1,'B','2026-02-13 16:46:46','active'),(17,2,'B','2026-02-13 16:46:46','active'),(18,3,'B','2026-02-13 16:46:46','active'),(19,4,'B','2026-02-13 16:46:46','active'),(20,5,'B','2026-02-13 16:46:46','active'),(21,6,'B','2026-02-13 16:46:46','active'),(22,7,'B','2026-02-13 16:46:46','active'),(23,8,'B','2026-02-13 16:46:46','active'),(24,9,'B','2026-02-13 16:46:46','active'),(25,10,'B','2026-02-13 16:46:46','active'),(26,11,'B','2026-02-13 16:46:46','active'),(27,12,'B','2026-02-13 16:46:46','active'),(28,13,'B','2026-02-13 16:46:46','active'),(29,14,'B','2026-02-13 16:46:46','active'),(30,15,'B','2026-02-13 16:46:46','active'),(31,9,'C','2026-02-13 16:46:46','active'),(32,10,'C','2026-02-13 16:46:46','active'),(33,11,'C','2026-02-13 16:46:46','active'),(34,12,'C','2026-02-13 16:46:46','active'),(35,13,'C','2026-02-13 16:46:46','active'),(36,14,'C','2026-02-13 16:46:46','active'),(37,15,'C','2026-02-13 16:46:46','active');
/*!40000 ALTER TABLE `sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('fee_mode','monthly','2026-04-05 16:58:39'),('school_address','School Address Line 1, City, State - PIN','2026-02-13 16:46:46'),('school_email','school@example.com','2026-02-13 16:46:46'),('school_logo','assets/img/school_logo.jpg','2026-02-16 14:01:23'),('school_name','SSVM','2026-04-03 19:05:43'),('school_phone','0000000000','2026-02-16 14:00:52'),('student_login_enabled','1','2026-02-26 16:57:27');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `admission_no` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `father_name` varchar(100) NOT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `class_id` int NOT NULL,
  `section_id` int NOT NULL,
  `roll_number` varchar(20) DEFAULT NULL,
  `contact_number` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `password_changed` tinyint(1) NOT NULL DEFAULT '0',
  `address` text NOT NULL,
  `admission_date` date NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `admission_no` (`admission_no`),
  KEY `section_id` (`section_id`),
  KEY `idx_student_admission` (`admission_no`),
  KEY `idx_student_class` (`class_id`,`section_id`),
  KEY `idx_student_status` (`status`),
  KEY `idx_student_class_status` (`class_id`,`section_id`,`status`),
  KEY `idx_students_academic_year` (`academic_year`),
  KEY `idx_student_admission_status` (`admission_no`,`status`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (2,'ADM20260101','Ridhi','Nair','Arjun Nair','Pooja Nair','2011-04-19','Female',11,33,'29','9882811784','ridhi.nair224@gmail.com','$2y$12$Ji.tE0d.vlTpmqP6jIx6qe/mfNrzw/P5qGkIOPTaOHaagj.BHKAqG',0,'659, Gandhi Nagar, Ahmedabad - 178492','2024-04-29','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:35'),(3,'ADM20260102','Aditya','Malhotra','Nitin Malhotra','Ridhi Malhotra','2020-08-13','Male',2,2,'18','9842142939','aditya.malhotra487@gmail.com','$2y$12$MoqXg5HMPbNl6W56Xs5IHOTtGD1MlSb3qO5nsBeYiwDhbV8yM7K2m',0,'228, Sardar Patel Marg, Pune - 264234','2026-02-04','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:35'),(4,'ADM20260103','Kavya','Tiwari','Harsh Tiwari','Myra Tiwari','2019-10-16','Female',3,3,'05','9835015954','kavya.tiwari563@gmail.com','$2y$12$RM2HhX4aV1B8B6AwP4Ioi.nzFoqN5KXVdJDGqFeoV0feEjLUvYUJK',0,'517, Brigade Road, Chennai - 169520','2025-04-13','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:35'),(5,'ADM20260104','Karan','Malhotra','Aryan Malhotra','Saanvi Malhotra','2020-03-28','Male',2,17,'11','9895309213','karan.malhotra586@gmail.com','$2y$12$Brn7XhHe65B8Zb/5IqHTHOdwIsohtkzmU8bhrR9yRITOtkw9ma2J6',0,'659, Sardar Patel Marg, Hyderabad - 596897','2025-04-30','2026-2027','inactive','2026-02-13 16:57:12','2026-02-26 15:47:36'),(6,'ADM20260105','Meera','Malhotra','Vijay Malhotra','Sara Malhotra','2008-04-28','Female',14,36,'04','9873733575','meera.malhotra389@gmail.com','$2y$12$udJUQdH.zL9KdbP3KK.ree2dhchK0Rnf/nUaQe9uIueqmVT/0ti.O',0,'390, Gandhi Nagar, Jaipur - 640738','2025-06-12','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:36'),(7,'ADM20260106','Zara','Shah','Aarav Shah','Tara Shah','2018-10-18','Female',4,4,'16','9856075946','zara.shah484@gmail.com','$2y$12$PdeTXpsdAYLY/nzMPrKlqOImgJpSAha65FOTuGaI0b1.XcFdOKzAG',0,'52, Park Street, Chennai - 437318','2025-07-02','2026-2027','inactive','2026-02-13 16:57:12','2026-02-26 15:47:36'),(8,'ADM20260107','Dhruv','Chopra','Aarav Chopra','Tara Chopra','2016-01-13','Male',7,7,'34','9812402222','dhruv.chopra118@gmail.com','$2y$12$api.rsUfbGVlNXQIUDyuOughKNHcC93rR4X6PG0Z/WBF7ejJ5ReOG',0,'893, MG Road, Hyderabad - 397374','2025-05-29','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:36'),(9,'ADM20260108','Shruti','Kapoor','Aarav Kapoor','Ishita Kapoor','2013-04-09','Female',9,24,'01','9839642981','shruti.kapoor358@gmail.com','$2y$12$vOe29029oouv.UMsGrNwAe3yEdsXz0Pu0mBAqGETF5l4zdDURMZyG',0,'542, Residency Road, Pune - 911635','2024-12-20','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:37'),(10,'ADM20260109','Ridhi','Gupta','Karan Gupta','Tanvi Gupta','2009-10-07','Female',13,13,'47','9862724924','ridhi.gupta232@gmail.com','$2y$12$JlXgYhHHCGHgX.ivJChyGevdknjSIgksYi74wT6PwllTG1FoKBziK',0,'297, Gandhi Nagar, Chennai - 517652','2024-11-27','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:37'),(11,'ADM20260110','Vijay','Verma','Rohan Verma','Ananya Verma','2008-01-20','Male',15,30,'19','9845037262','vijay.verma860@gmail.com','$2y$12$ilTS7qE8XPLTd/aivVjx6OJ5FDyjQCz24tOO97FgjY3L/gE4eVpDO',0,'983, Station Road, Lucknow - 521675','2024-09-10','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:37'),(12,'ADM20260111','Rohan','Pandey','Ishaan Pandey','Ananya Pandey','2019-04-30','Male',3,18,'24','9866640638','rohan.pandey978@gmail.com','$2y$12$KqDx9tCA3ZiNZF/ao8y2YexaMZmw/lvOVjbEhe3wwyVIVfatlVPey',0,'404, Brigade Road, Bangalore - 621611','2024-04-20','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:37'),(13,'ADM20260112','Meera','Joshi','Vijay Joshi','Aadhya Joshi','2017-09-01','Female',5,5,'07','9845127444','meera.joshi977@gmail.com','$2y$12$kCfBp1goPfw24lAHE4z.CeGVYEkbZ.laPP2JGenYA7mmDtPCudDJe',0,'390, Brigade Road, Hyderabad - 153683','2025-02-10','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:38'),(14,'ADM20260113','Myra','Mishra','Rahul Mishra','Zara Mishra','2019-01-20','Female',4,19,'31','9876369946','myra.mishra763@gmail.com','$2y$12$/zrjQY97YU12T58mPdZMKO2UsYs60eGnUU.Tju2SB5j35WYsKaAqK',0,'306, Brigade Road, Bangalore - 267345','2024-03-28','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:38'),(15,'ADM20260114','Ravi','Singh','Vijay Singh','Kavya Singh','2009-08-31','Male',13,35,'48','9888175106','ravi.singh670@gmail.com','$2y$12$itMxS52BRaVJtNC/9inL1OZLjC68zIbhdB25/C4Z2ctySuLAEK.l.',0,'296, Park Street, Kolkata - 735881','2024-06-02','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:38'),(16,'ADM20260115','Siya','Chopra','Raj Chopra','Tara Chopra','2010-05-24','Female',12,12,'43','9819109090','siya.chopra326@gmail.com','$2y$12$c2bqD4O8hnlqAN/MushiG.sOwD8wdLVlXmSI97S4KyEOBBPeYjQzq',0,'256, Nehru Street, Delhi - 480735','2026-02-12','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:38'),(17,'ADM20260116','Amit','Sharma','Samar Sharma','Saanvi Sharma','2020-03-17','Male',2,2,'46','9853730917','amit.sharma747@gmail.com','$2y$12$/xSVI924Q8NBTDdPM8sIFOFcZ5d7WgxfZIzJ8XyvZtizu2biFrvQu',0,'456, Sardar Patel Marg, Lucknow - 156320','2025-11-24','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:39'),(18,'ADM20260117','Ishaan','Saxena','Ravi Saxena','Ridhi Saxena','2019-10-22','Male',3,18,'45','9820357568','ishaan.saxena642@gmail.com','$2y$12$kjHPlVBvCrY4LB1d0mHWIOoc0hgKgRWn8W3motlcvwaJaz23xrQW6',0,'965, Mall Road, Ahmedabad - 961943','2024-07-18','2026-2027','inactive','2026-02-13 16:57:12','2026-02-26 15:47:39'),(19,'ADM20260118','Dev','Sharma','Aarav Sharma','Ananya Sharma','2015-10-04','Male',7,7,'37','9842528933','dev.sharma230@gmail.com','$2y$12$MJMEjfHpRpwH5Ra8axClFuOtQqfu5SY5y3meA75x/TtOEb8lEimi6',0,'537, Nehru Street, Ahmedabad - 401454','2024-12-30','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:39'),(20,'ADM20260119','Zara','Malhotra','Kabir Malhotra','Neha Malhotra','2015-12-11','Female',7,7,'40','9840607204','zara.malhotra855@gmail.com','$2y$12$9xfsedLr3GrE2suup0ciqOteQp8OvTOgPtyb5Qi6VrjX2qHvgGhuu',0,'645, Gandhi Nagar, Bangalore - 848143','2024-10-10','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:39'),(21,'ADM20260120','Dhruv','Tiwari','Dev Tiwari','Ishita Tiwari','2014-04-23','Male',8,23,'09','9881213652','dhruv.tiwari595@gmail.com','$2y$12$kwnh2UhX0R228gCd.6OukOWe5cVgLO4dMRzftB.yRycwPXuRhoYK2',0,'533, Park Street, Hyderabad - 804352','2024-05-17','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:40'),(22,'ADM20260121','Harsh','Kapoor','Harsh Kapoor','Sara Kapoor','2017-05-26','Male',5,20,'06','9845631981','harsh.kapoor245@gmail.com','$2y$12$vI3TRmZo2iNo4.JfUnkomudftc/Nn4svzbBSPSeqgZIfNQK2h/ZtW',0,'498, Mall Road, Jaipur - 593950','2024-09-06','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:40'),(23,'ADM20260122','Reyansh','Gupta','Sanjay Gupta','Shruti Gupta','2015-05-07','Male',7,22,'46','9858586161','reyansh.gupta392@gmail.com','$2y$12$LzQqBH7t9V2s0dVCwix/xelTsEQgSLOyEdRMHigQUrYPXgrRQW18e',0,'568, Ashok Nagar, Pune - 768923','2024-11-09','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:40'),(24,'ADM20260123','Sanjay','Jain','Advait Jain','Ananya Jain','2015-06-17','Male',7,22,'29','9820603226','sanjay.jain123@gmail.com','$2y$12$iA3FwrRyaRc5zsJO2y0VdeEC.3BxQ949OZqkYhE6wobbH8smIFRB6',0,'140, Nehru Street, Ahmedabad - 225166','2025-12-24','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:40'),(25,'ADM20260124','Harsh','Mishra','Advait Mishra','Priya Mishra','2016-05-03','Male',2,2,'46','9878066341','harsh.mishra839@gmail.com','$2y$12$yBzDnxJAB2/qVR2XkimlTuSR4Q88EaVOfUk5rlVMc6Vjk0lLiEoZy',0,'292, Park Street, Pune - 691466','2024-09-05','2026-2027','active','2026-02-13 16:57:12','2026-02-26 17:11:14'),(26,'ADM20260125','Raj','Singh','Karan Singh','Kavya Singh','2012-01-16','Male',11,33,'11','9859993692','raj.singh146@gmail.com','$2y$12$a22NVerNKrZvvaSs2RnaBeoxba9SI6pq2drqkdfGVopjeMgo6p.MK',0,'891, Park Street, Pune - 141456','2024-03-29','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:41'),(27,'ADM20260126','Ananya','Sharma','Vivek Sharma','Pari Sharma','2016-08-04','Female',6,21,'22','9846613289','ananya.sharma218@gmail.com','$2y$12$7DqJ.LIXmPTAuCapk8lSoOQ4vb28a0PHBDlf8Y3.WuFY6YSnImfTq',0,'853, Brigade Road, Hyderabad - 621214','2024-07-16','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:41'),(28,'ADM20260127','Rahul','Desai','Shaurya Desai','Neha Desai','2013-04-14','Male',9,24,'18','9830173821','rahul.desai310@gmail.com','$2y$12$WsSgFBzwa.dkwUmKzOWD0epawR1w.HcEAu56jEzS6RP5iPbv3RKaG',0,'883, MG Road, Delhi - 973815','2024-02-21','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:41'),(29,'ADM20260128','Sanjay','Bose','Rohan Bose','Riya Bose','2013-07-28','Male',9,31,'40','9862617326','sanjay.bose283@gmail.com','$2y$12$mgZwF8wnC.rGnPw12Uq1muDWNVpp8VBRH4eyft4dWMS1esNzkN0JC',0,'810, Park Street, Jaipur - 110537','2024-09-27','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:42'),(30,'ADM20260129','Riya','Kumar','Vivek Kumar','Aisha Kumar','2011-01-04','Female',12,12,'09','9858172681','riya.kumar770@gmail.com','$2y$12$srC8AGWhuQRmFfsc71A10uB3V2OkQ0mHAj/K.Uuw27wp8dQ4YPkrW',0,'334, Station Road, Delhi - 227519','2026-02-07','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:42'),(31,'ADM20260130','Amit','Mehta','Ravi Mehta','Aisha Mehta','2019-02-04','Male',4,4,'35','9817825323','amit.mehta680@gmail.com','$2y$12$tNucNAMtj14JHjiMXfdAy.A2QCKKsSwGm2zv0hMQrBbefMPtaLf6e',0,'27, Nehru Street, Mumbai - 833314','2025-05-17','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:42'),(32,'ADM20260131','Siya','Desai','Harsh Desai','Myra Desai','2020-11-08','Female',2,17,'40','9831038923','siya.desai3@gmail.com','$2y$12$ZKTSt8CjBM1UXzlnFSZy/.DM15nvzRlyeREzwQUaY7CzR9gaeVmVi',0,'191, Ashok Nagar, Ahmedabad - 723217','2026-02-13','2026-2027','inactive','2026-02-13 16:57:12','2026-02-26 15:47:42'),(33,'ADM20260132','Advait','Shah','Dev Shah','Neha Shah','2012-04-25','Male',10,10,'10','9875166688','advait.shah558@gmail.com','$2y$12$JNkYFYDMMaGaSzpU6HU1CeBO0wMdBe4Z0HtLONbRY0jwtmevC1X3C',0,'924, MG Road, Mumbai - 629814','2025-04-06','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:43'),(34,'ADM20260133','Ananya','Kumar','Ishaan Kumar','Aisha Kumar','2009-10-19','Female',13,28,'34','9810917376','ananya.kumar426@gmail.com','$2y$12$msQLvhtS3bvv0X0fLQOk/.MCz0vPiQ0C9EgUPj4YAkoBPEOL4lqLe',0,'467, Gandhi Nagar, Mumbai - 281622','2025-11-06','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:43'),(35,'ADM20260134','Aryan','Kumar','Amit Kumar','Riya Kumar','2018-01-11','Male',5,5,'02','9873891821','aryan.kumar962@gmail.com','$2y$12$4G0Kex.JgQGubM7AWmgRB.CUp8Y902pjaaULka4dh7tHQBeRBTTmC',0,'391, Station Road, Delhi - 625824','2024-11-09','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:43'),(36,'ADM20260135','Ravi','Saxena','Rohan Saxena','Pooja Saxena','2017-04-09','Male',5,20,'13','9893549799','ravi.saxena525@gmail.com','$2y$12$9JNT2GntyE6k8Ih5pbQBbeJCG9htrZhx/DbNZ8p5Y4Iak7obGKmTC',0,'686, MG Road, Bangalore - 998176','2024-08-17','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:43'),(37,'ADM20260136','Amit','Pandey','Amit Pandey','Tanvi Pandey','2014-10-02','Male',8,23,'45','9857669218','amit.pandey1@gmail.com','$2y$12$95U2ycTropxnNkK5iNb7OuHTtWSjJq5tgnJsf/oxC.k67KVxMC1qy',0,'353, Nehru Street, Jaipur - 416103','2025-06-23','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:44'),(38,'ADM20260137','Vijay','Kumar','Karan Kumar','Saanvi Kumar','2018-05-01','Male',4,19,'48','9841285126','vijay.kumar105@gmail.com','$2y$12$VL4UedElr/bLW1SAV7w4v.0/M2uz7wTgb8gnW9NPpxolkw/7psoz6',0,'57, Brigade Road, Delhi - 626159','2025-12-03','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:44'),(39,'ADM20260138','Dhruv','Mehta','Rohan Mehta','Anika Mehta','2016-07-26','Male',6,6,'33','9816794464','dhruv.mehta199@gmail.com','$2y$12$ckSoCuzeflCWlP8swvUCxOTe48ZGWDhDLdfCI2F3zI52qwC.dVLvS',0,'813, Residency Road, Mumbai - 723804','2024-10-29','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:44'),(40,'ADM20260139','Pari','Iyer','Aryan Iyer','Riya Iyer','2012-09-28','Female',10,32,'04','9895530342','pari.iyer502@gmail.com','$2y$12$qMZz6mPVNNXcN84R8bh6zubIJdE46cgyh6M17y2Yq2Vb/6C2Zcv8S',0,'765, Brigade Road, Mumbai - 637688','2024-11-06','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:44'),(41,'ADM20260140','Ananya','Tiwari','Amit Tiwari','Kavya Tiwari','2016-06-11','Female',6,21,'31','9829607021','ananya.tiwari739@gmail.com','$2y$12$lU1HyJnowv6n.XszFrxHeuFytIlJDYDKxtcqSyFj.rMHwCR2bwoBK',0,'392, Nehru Street, Bangalore - 604515','2025-01-26','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:45'),(42,'ADM20260141','Aryan','Kumar','Ishaan Kumar','Diya Kumar','2015-01-17','Male',8,8,'47','9829911528','aryan.kumar581@gmail.com','$2y$12$bCUpoZ3.Oy1eKYetv.xPIeWhq58ashzmCe3vkJpi0JwIr0lYOH6jS',0,'628, Nehru Street, Lucknow - 518713','2024-04-29','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:45'),(43,'ADM20260142','Kabir','Singh','Raj Singh','Tara Singh','2017-10-05','Male',5,20,'41','9864899112','kabir.singh52@gmail.com','$2y$12$2U4e9EjWhmVPbNukUbMOnudhTEJkWDPKjd7teirNrNE.FNcbcxT0m',0,'622, Gandhi Nagar, Ahmedabad - 492808','2024-05-15','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:45'),(44,'ADM20260143','Amit','Mishra','Raj Mishra','Neha Mishra','2017-04-06','Male',5,20,'03','9845868580','amit.mishra70@gmail.com','$2y$12$qc3/oIe6Y7uTnZAKCN0Qj.m4H3pE4FyeHoSl4r2N.jUCC13/4cCe.',0,'771, Ashok Nagar, Pune - 272118','2025-09-28','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:45'),(45,'ADM20260144','Aisha','Agarwal','Nitin Agarwal','Siya Agarwal','2020-06-06','Female',2,17,'10','9890617979','aisha.agarwal287@gmail.com','$2y$12$El2tQDlQO9rQKIFyMeA00eI7OLFxPJ./XLiBOgdnu/fVw/5G2L1OK',0,'363, Park Street, Delhi - 887132','2024-07-16','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:46'),(46,'ADM20260145','Diya','Joshi','Vihaan Joshi','Diya Joshi','2013-08-03','Female',9,9,'04','9815401118','diya.joshi254@gmail.com','$2y$12$w/BJdVQDHR3i3jA9OeAX..7uDn8K5Fm/Co7G8Jx1rlP6tTKtwaiby',0,'741, MG Road, Bangalore - 366291','2025-12-29','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:46'),(47,'ADM20260146','Meera','Desai','Karan Desai','Zara Desai','2016-12-09','Female',6,6,'06','9845965160','meera.desai509@gmail.com','$2y$12$r0sOHLMGMEnYH5AASKONR.hCD0pKq0a22uSKAq8ivSmu/v6JfTe3.',0,'347, Sardar Patel Marg, Kolkata - 435582','2024-12-17','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:46'),(48,'ADM20260147','Sara','Roy','Aryan Roy','Siya Roy','2011-06-25','Female',11,26,'31','9831108362','sara.roy809@gmail.com','$2y$12$v2nDS6DSsiSZZDee9eGvKOGuEFUZKik.2Pz6EJTnB2RDR8nBtKsd6',0,'664, Nehru Street, Jaipur - 931691','2024-06-13','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:46'),(49,'ADM20260148','Ravi','Kumar','Aryan Kumar','Kavya Kumar','2017-05-10','Male',5,5,'07','9859216354','ravi.kumar560@gmail.com','$2y$12$GI3AweH9bAkkMNRqGRbpV.84zJ3PQWRdGnEUp4O07s4PIr2WYpHg2',0,'361, Nehru Street, Bangalore - 695057','2025-06-16','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:47'),(50,'ADM20260149','Kavya','Iyer','Vivek Iyer','Aadhya Iyer','2018-03-19','Female',2,17,'46','9824659672','kavya.iyer658@gmail.com','$2y$12$ZBzRWnFxznkIXxTgdJVOaO.1mFQyd6pdiMyAI8mxWekOo1ZAgXMnC',0,'759, Sardar Patel Marg, Lucknow - 188454','2025-05-10','2026-2027','active','2026-02-13 16:57:12','2026-02-26 17:10:53'),(51,'ADM20260150','Siya','Reddy','Shaurya Reddy','Pari Reddy','2008-03-07','Female',14,14,'10','9872544310','siya.reddy358@gmail.com','$2y$12$0mZBfEfLc9CbPXLWhBdo7.8PdRbrLOPwDDVlIovFT3X2o1eLTRLrW',0,'199, Brigade Road, Kolkata - 754928','2025-04-25','2026-2027','active','2026-02-13 16:57:12','2026-02-26 15:47:47'),(52,'ADM202q60103','1113','wqeq','wsa','','2026-01-01','Female',1,16,'213','1212121212','','$2y$12$DPfV6S801HoP.GPaKhRpveAJHPA2TE4iMXAQAw.SdUsj4/vagES4S',0,'wsz','2026-01-01','2026-2027','active','2026-02-26 16:26:21','2026-02-26 16:26:21');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription`
--

DROP TABLE IF EXISTS `subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription` (
  `id` int NOT NULL AUTO_INCREMENT,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `subscription_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription`
--

LOCK TABLES `subscription` WRITE;
/*!40000 ALTER TABLE `subscription` DISABLE KEYS */;
INSERT INTO `subscription` VALUES (1,'2026-02-12','2027-02-22',1,'2026-02-26 14:27:30','2026-02-26 15:04:38');
/*!40000 ALTER TABLE `subscription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','int','boolean','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'smtp_enabled','1','boolean','Enable/disable email sending',1,'2026-02-16 17:58:39','2026-02-13 18:56:42'),(2,'smtp_host','smtp.hostinger.com','string','SMTP server hostname',1,'2026-02-16 17:58:39','2026-02-13 18:56:42'),(3,'smtp_port','465','int','SMTP server port',1,'2026-02-16 17:58:39','2026-02-13 18:56:42'),(4,'smtp_encryption','ssl','string','SMTP encryption type (tls/ssl)',1,'2026-02-16 17:58:39','2026-02-13 18:56:42'),(5,'smtp_username','info@santoshkr.in','string','SMTP username/email address',1,'2026-02-16 17:58:39','2026-02-13 18:56:42'),(6,'smtp_password','Sk@5219981998','string','SMTP password or app-specific password',1,'2026-02-16 17:58:39','2026-02-13 18:56:42'),(7,'smtp_from_email','info@santoshkr.in','string','From email address',1,'2026-02-16 17:58:39','2026-02-13 18:56:42'),(8,'smtp_from_name','Fee Management System','string','From name',1,'2026-02-16 17:58:39','2026-02-13 18:56:42'),(209,'upi_enabled','0','boolean',NULL,1,'2026-02-26 18:15:24','2026-02-26 17:54:03'),(210,'upi_id','F3KdgvQMP7YBVV2CGodSXzo6QldQWEdlTXBJS0YwbVpjNXVRaEg5Zz09','string',NULL,1,'2026-02-26 18:15:24','2026-02-26 17:54:03'),(211,'upi_payee_name','School Name','string',NULL,1,'2026-02-26 18:15:24','2026-02-26 17:54:03');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `upi_payments`
--

DROP TABLE IF EXISTS `upi_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `upi_payments` (
  `upi_payment_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `utr_number` varchar(50) NOT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `rejection_reason` text,
  `fee_month` tinyint DEFAULT NULL COMMENT 'Only for monthly mode: 1=April...12=March',
  `payment_id` int DEFAULT NULL COMMENT 'FK to fee_collection after approval',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`upi_payment_id`),
  UNIQUE KEY `uk_utr_number` (`utr_number`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_status` (`status`),
  KEY `idx_academic_year` (`academic_year`),
  KEY `idx_submitted_at` (`submitted_at`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `payment_id` (`payment_id`),
  CONSTRAINT `upi_payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  CONSTRAINT `upi_payments_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `admin` (`admin_id`),
  CONSTRAINT `upi_payments_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `fee_collection` (`payment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `upi_payments`
--

LOCK TABLES `upi_payments` WRITE;
/*!40000 ALTER TABLE `upi_payments` DISABLE KEYS */;
INSERT INTO `upi_payments` VALUES (1,52,'2026-2027',111.00,'434324234525',NULL,'Approved',NULL,NULL,12,'2026-02-26 18:10:26',1,'2026-02-26 18:11:59','::1','2026-02-26 18:10:26','2026-02-26 18:11:59'),(2,52,'2026-2027',14539.00,'65434567876411',NULL,'Rejected','test',NULL,NULL,'2026-02-26 18:13:33',1,'2026-02-26 18:14:22','::1','2026-02-26 18:13:33','2026-02-26 18:14:22');
/*!40000 ALTER TABLE `upi_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `vw_student_fee_summary`
--

DROP TABLE IF EXISTS `vw_student_fee_summary`;
/*!50001 DROP VIEW IF EXISTS `vw_student_fee_summary`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_student_fee_summary` AS SELECT 
 1 AS `student_id`,
 1 AS `admission_no`,
 1 AS `student_name`,
 1 AS `father_name`,
 1 AS `class_name`,
 1 AS `section_name`,
 1 AS `academic_year`,
 1 AS `total_fee_amount`,
 1 AS `total_paid_amount`,
 1 AS `balance_amount`,
 1 AS `payment_status`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_student_monthly_fee_summary`
--

DROP TABLE IF EXISTS `vw_student_monthly_fee_summary`;
/*!50001 DROP VIEW IF EXISTS `vw_student_monthly_fee_summary`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_student_monthly_fee_summary` AS SELECT 
 1 AS `student_id`,
 1 AS `admission_no`,
 1 AS `student_name`,
 1 AS `father_name`,
 1 AS `class_name`,
 1 AS `section_name`,
 1 AS `academic_year`,
 1 AS `fee_month`,
 1 AS `month_label`,
 1 AS `monthly_fee_amount`,
 1 AS `monthly_paid_amount`,
 1 AS `monthly_balance`,
 1 AS `payment_status`*/;
SET character_set_client = @saved_cs_client;

--
-- Dumping routines for database 'fee_management_system'
--

--
-- Final view structure for view `vw_student_fee_summary`
--

/*!50001 DROP VIEW IF EXISTS `vw_student_fee_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `vw_student_fee_summary` AS select `s`.`student_id` AS `student_id`,`s`.`admission_no` AS `admission_no`,concat(`s`.`first_name`,' ',`s`.`last_name`) AS `student_name`,`s`.`father_name` AS `father_name`,`c`.`class_name` AS `class_name`,`sec`.`section_name` AS `section_name`,`fs`.`academic_year` AS `academic_year`,`fs`.`total_fee` AS `total_fee_amount`,coalesce(sum(`fc`.`total_paid`),0) AS `total_paid_amount`,(`fs`.`total_fee` - coalesce(sum(`fc`.`total_paid`),0)) AS `balance_amount`,(case when ((`fs`.`total_fee` - coalesce(sum(`fc`.`total_paid`),0)) <= 0) then 'Paid' when (coalesce(sum(`fc`.`total_paid`),0) > 0) then 'Partial' else 'Unpaid' end) AS `payment_status` from ((((`students` `s` join `classes` `c` on((`s`.`class_id` = `c`.`class_id`))) join `sections` `sec` on((`s`.`section_id` = `sec`.`section_id`))) left join `fee_structure` `fs` on(((`s`.`class_id` = `fs`.`class_id`) and (`fs`.`status` = 'active')))) left join `fee_collection` `fc` on(((`s`.`student_id` = `fc`.`student_id`) and (`fc`.`academic_year` = `fs`.`academic_year`)))) where (`s`.`status` = 'active') group by `s`.`student_id`,`fs`.`academic_year` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_student_monthly_fee_summary`
--

/*!50001 DROP VIEW IF EXISTS `vw_student_monthly_fee_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `vw_student_monthly_fee_summary` AS select `s`.`student_id` AS `student_id`,`s`.`admission_no` AS `admission_no`,concat(`s`.`first_name`,' ',`s`.`last_name`) AS `student_name`,`s`.`father_name` AS `father_name`,`c`.`class_name` AS `class_name`,`sec`.`section_name` AS `section_name`,`mfs`.`academic_year` AS `academic_year`,`mfs`.`fee_month` AS `fee_month`,`mfs`.`month_label` AS `month_label`,`mfs`.`total_fee` AS `monthly_fee_amount`,coalesce(sum(`fc`.`total_paid`),0) AS `monthly_paid_amount`,(`mfs`.`total_fee` - coalesce(sum(`fc`.`total_paid`),0)) AS `monthly_balance`,(case when ((`mfs`.`total_fee` - coalesce(sum(`fc`.`total_paid`),0)) <= 0) then 'Paid' when (coalesce(sum(`fc`.`total_paid`),0) > 0) then 'Partial' else 'Unpaid' end) AS `payment_status` from ((((`students` `s` join `classes` `c` on((`s`.`class_id` = `c`.`class_id`))) join `sections` `sec` on((`s`.`section_id` = `sec`.`section_id`))) left join `monthly_fee_structure` `mfs` on(((`s`.`class_id` = `mfs`.`class_id`) and (`mfs`.`status` = 'active')))) left join `fee_collection` `fc` on(((`s`.`student_id` = `fc`.`student_id`) and (`fc`.`fee_month` = `mfs`.`fee_month`) and (`fc`.`academic_year` = `mfs`.`academic_year`)))) where (`s`.`status` = 'active') group by `s`.`student_id`,`mfs`.`academic_year`,`mfs`.`fee_month` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-05 22:56:08
