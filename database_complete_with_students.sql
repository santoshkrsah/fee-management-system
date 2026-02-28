mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 9.6.0, for macos26.2 (arm64)
--
-- Host: localhost    Database: fee_management_system
-- ------------------------------------------------------
-- Server version	8.0.42

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
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'sysadmin','$2y$12$JfM7JZGyrxFuqGUJZWV4muGAyDOc6uk5/r3HpWw6Cw3VjR19Lmatu','System Administrator','sysadmin@school.com','sysadmin','2026-02-13 16:46:46','2026-02-13 16:55:04','active'),(2,'admin','$2y$12$mEZJAmO08xdW8ENpg9K.quMKZZfetqMlM9QysJstEztfhT1gnASOe','Administrator','admin@school.com','admin','2026-02-13 16:46:46','2026-02-13 16:52:01','active');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
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
  `fee_structure_id` int NOT NULL,
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
  `total_paid` decimal(10,2) GENERATED ALWAYS AS (((((((((`tuition_fee_paid` + `exam_fee_paid`) + `library_fee_paid`) + `sports_fee_paid`) + `lab_fee_paid`) + `transport_fee_paid`) + `other_charges_paid`) + `fine`) - `discount`)) STORED,
  `payment_mode` enum('Cash','Card','UPI','Net Banking','Cheque') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `remarks` text,
  `collected_by` int NOT NULL,
  `fee_month` tinyint DEFAULT NULL COMMENT 'Only used in monthly mode: 1=April ... 12=March',
  `monthly_fee_structure_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_collection`
--

LOCK TABLES `fee_collection` WRITE;
/*!40000 ALTER TABLE `fee_collection` DISABLE KEYS */;
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
  `total_fee` decimal(10,2) GENERATED ALWAYS AS (((((((`tuition_fee` + `exam_fee`) + `library_fee`) + `sports_fee`) + `lab_fee`) + `transport_fee`) + `other_charges`)) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`fee_structure_id`),
  UNIQUE KEY `unique_class_year` (`class_id`,`academic_year`),
  KEY `idx_fee_structure_class_year` (`class_id`,`academic_year`),
  CONSTRAINT `fee_structure_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structure`
--

LOCK TABLES `fee_structure` WRITE;
/*!40000 ALTER TABLE `fee_structure` DISABLE KEYS */;
INSERT INTO `fee_structure` (`fee_structure_id`, `class_id`, `academic_year`, `tuition_fee`, `exam_fee`, `library_fee`, `sports_fee`, `lab_fee`, `transport_fee`, `other_charges`, `created_at`, `updated_at`, `status`) VALUES (1,1,'2026-2027',5000.00,500.00,300.00,200.00,0.00,1000.00,100.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(2,2,'2026-2027',5000.00,500.00,300.00,200.00,0.00,1000.00,100.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(3,3,'2026-2027',5000.00,500.00,300.00,200.00,0.00,1000.00,100.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(4,4,'2026-2027',6000.00,600.00,400.00,300.00,0.00,1200.00,100.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(5,5,'2026-2027',6000.00,600.00,400.00,300.00,0.00,1200.00,100.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(6,6,'2026-2027',7000.00,700.00,500.00,400.00,0.00,1500.00,100.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(7,7,'2026-2027',7000.00,700.00,500.00,400.00,0.00,1500.00,100.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(8,8,'2026-2027',8000.00,800.00,600.00,500.00,0.00,1500.00,100.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(9,9,'2026-2027',9000.00,900.00,700.00,600.00,500.00,2000.00,200.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(10,10,'2026-2027',9000.00,900.00,700.00,600.00,500.00,2000.00,200.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(11,11,'2026-2027',10000.00,1000.00,800.00,700.00,1000.00,2000.00,300.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(12,12,'2026-2027',12000.00,1200.00,1000.00,800.00,1500.00,2500.00,400.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(13,13,'2026-2027',15000.00,1500.00,1200.00,1000.00,2000.00,3000.00,500.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(14,14,'2026-2027',15000.00,1500.00,1200.00,1000.00,2000.00,3000.00,500.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active'),(15,15,'2026-2027',15000.00,1500.00,1200.00,1000.00,2000.00,3000.00,500.00,'2026-02-13 16:46:46','2026-02-13 16:46:46','active');
/*!40000 ALTER TABLE `fee_structure` ENABLE KEYS */;
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
  `total_fee` decimal(10,2) GENERATED ALWAYS AS (((((((`tuition_fee` + `exam_fee`) + `library_fee`) + `sports_fee`) + `lab_fee`) + `transport_fee`) + `other_charges`)) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`monthly_fee_id`),
  UNIQUE KEY `unique_class_year_month` (`class_id`,`academic_year`,`fee_month`),
  KEY `idx_monthly_fee_class_year` (`class_id`,`academic_year`),
  CONSTRAINT `monthly_fee_structure_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monthly_fee_structure`
--

LOCK TABLES `monthly_fee_structure` WRITE;
/*!40000 ALTER TABLE `monthly_fee_structure` DISABLE KEYS */;
/*!40000 ALTER TABLE `monthly_fee_structure` ENABLE KEYS */;
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
INSERT INTO `settings` VALUES ('fee_mode','monthly','2026-02-13 16:57:34'),('school_address','School Address Line 1, City, State - PIN','2026-02-13 16:46:46'),('school_email','school@example.com','2026-02-13 16:46:46'),('school_logo','','2026-02-13 16:46:46'),('school_name','Fee Management System','2026-02-13 16:46:46'),('school_phone','+91 XXXXX XXXXX','2026-02-13 16:46:46');
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
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (2,'ADM20260101','Ridhi','Nair','Arjun Nair','Pooja Nair','2011-04-19','Female',11,33,'29','9882811784','ridhi.nair224@gmail.com','659, Gandhi Nagar, Ahmedabad - 178492','2024-04-29','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(3,'ADM20260102','Aditya','Malhotra','Nitin Malhotra','Ridhi Malhotra','2020-08-13','Male',2,2,'18','9842142939','aditya.malhotra487@gmail.com','228, Sardar Patel Marg, Pune - 264234','2026-02-04','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(4,'ADM20260103','Kavya','Tiwari','Harsh Tiwari','Myra Tiwari','2019-10-16','Female',3,3,'05','9835015954','kavya.tiwari563@gmail.com','517, Brigade Road, Chennai - 169520','2025-04-13','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(5,'ADM20260104','Karan','Malhotra','Aryan Malhotra','Saanvi Malhotra','2020-03-28','Male',2,17,'11','9895309213','karan.malhotra586@gmail.com','659, Sardar Patel Marg, Hyderabad - 596897','2025-04-30','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(6,'ADM20260105','Meera','Malhotra','Vijay Malhotra','Sara Malhotra','2008-04-28','Female',14,36,'04','9873733575','meera.malhotra389@gmail.com','390, Gandhi Nagar, Jaipur - 640738','2025-06-12','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(7,'ADM20260106','Zara','Shah','Aarav Shah','Tara Shah','2018-10-18','Female',4,4,'16','9856075946','zara.shah484@gmail.com','52, Park Street, Chennai - 437318','2025-07-02','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(8,'ADM20260107','Dhruv','Chopra','Aarav Chopra','Tara Chopra','2016-01-13','Male',7,7,'34','9812402222','dhruv.chopra118@gmail.com','893, MG Road, Hyderabad - 397374','2025-05-29','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(9,'ADM20260108','Shruti','Kapoor','Aarav Kapoor','Ishita Kapoor','2013-04-09','Female',9,24,'01','9839642981','shruti.kapoor358@gmail.com','542, Residency Road, Pune - 911635','2024-12-20','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(10,'ADM20260109','Ridhi','Gupta','Karan Gupta','Tanvi Gupta','2009-10-07','Female',13,13,'47','9862724924','ridhi.gupta232@gmail.com','297, Gandhi Nagar, Chennai - 517652','2024-11-27','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(11,'ADM20260110','Vijay','Verma','Rohan Verma','Ananya Verma','2008-01-20','Male',15,30,'19','9845037262','vijay.verma860@gmail.com','983, Station Road, Lucknow - 521675','2024-09-10','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(12,'ADM20260111','Rohan','Pandey','Ishaan Pandey','Ananya Pandey','2019-04-30','Male',3,18,'24','9866640638','rohan.pandey978@gmail.com','404, Brigade Road, Bangalore - 621611','2024-04-20','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(13,'ADM20260112','Meera','Joshi','Vijay Joshi','Aadhya Joshi','2017-09-01','Female',5,5,'07','9845127444','meera.joshi977@gmail.com','390, Brigade Road, Hyderabad - 153683','2025-02-10','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(14,'ADM20260113','Myra','Mishra','Rahul Mishra','Zara Mishra','2019-01-20','Female',4,19,'31','9876369946','myra.mishra763@gmail.com','306, Brigade Road, Bangalore - 267345','2024-03-28','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(15,'ADM20260114','Ravi','Singh','Vijay Singh','Kavya Singh','2009-08-31','Male',13,35,'48','9888175106','ravi.singh670@gmail.com','296, Park Street, Kolkata - 735881','2024-06-02','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(16,'ADM20260115','Siya','Chopra','Raj Chopra','Tara Chopra','2010-05-24','Female',12,12,'43','9819109090','siya.chopra326@gmail.com','256, Nehru Street, Delhi - 480735','2026-02-12','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(17,'ADM20260116','Amit','Sharma','Samar Sharma','Saanvi Sharma','2020-03-17','Male',2,2,'46','9853730917','amit.sharma747@gmail.com','456, Sardar Patel Marg, Lucknow - 156320','2025-11-24','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(18,'ADM20260117','Ishaan','Saxena','Ravi Saxena','Ridhi Saxena','2019-10-22','Male',3,18,'45','9820357568','ishaan.saxena642@gmail.com','965, Mall Road, Ahmedabad - 961943','2024-07-18','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(19,'ADM20260118','Dev','Sharma','Aarav Sharma','Ananya Sharma','2015-10-04','Male',7,7,'37','9842528933','dev.sharma230@gmail.com','537, Nehru Street, Ahmedabad - 401454','2024-12-30','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(20,'ADM20260119','Zara','Malhotra','Kabir Malhotra','Neha Malhotra','2015-12-11','Female',7,7,'40','9840607204','zara.malhotra855@gmail.com','645, Gandhi Nagar, Bangalore - 848143','2024-10-10','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(21,'ADM20260120','Dhruv','Tiwari','Dev Tiwari','Ishita Tiwari','2014-04-23','Male',8,23,'09','9881213652','dhruv.tiwari595@gmail.com','533, Park Street, Hyderabad - 804352','2024-05-17','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(22,'ADM20260121','Harsh','Kapoor','Harsh Kapoor','Sara Kapoor','2017-05-26','Male',5,20,'06','9845631981','harsh.kapoor245@gmail.com','498, Mall Road, Jaipur - 593950','2024-09-06','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(23,'ADM20260122','Reyansh','Gupta','Sanjay Gupta','Shruti Gupta','2015-05-07','Male',7,22,'46','9858586161','reyansh.gupta392@gmail.com','568, Ashok Nagar, Pune - 768923','2024-11-09','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(24,'ADM20260123','Sanjay','Jain','Advait Jain','Ananya Jain','2015-06-17','Male',7,22,'29','9820603226','sanjay.jain123@gmail.com','140, Nehru Street, Ahmedabad - 225166','2025-12-24','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(25,'ADM20260124','Harsh','Mishra','Advait Mishra','Priya Mishra','2016-05-03','Male',6,21,'46','9878066341','harsh.mishra839@gmail.com','292, Park Street, Pune - 691466','2024-09-05','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(26,'ADM20260125','Raj','Singh','Karan Singh','Kavya Singh','2012-01-16','Male',11,33,'11','9859993692','raj.singh146@gmail.com','891, Park Street, Pune - 141456','2024-03-29','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(27,'ADM20260126','Ananya','Sharma','Vivek Sharma','Pari Sharma','2016-08-04','Female',6,21,'22','9846613289','ananya.sharma218@gmail.com','853, Brigade Road, Hyderabad - 621214','2024-07-16','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(28,'ADM20260127','Rahul','Desai','Shaurya Desai','Neha Desai','2013-04-14','Male',9,24,'18','9830173821','rahul.desai310@gmail.com','883, MG Road, Delhi - 973815','2024-02-21','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(29,'ADM20260128','Sanjay','Bose','Rohan Bose','Riya Bose','2013-07-28','Male',9,31,'40','9862617326','sanjay.bose283@gmail.com','810, Park Street, Jaipur - 110537','2024-09-27','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(30,'ADM20260129','Riya','Kumar','Vivek Kumar','Aisha Kumar','2011-01-04','Female',12,12,'09','9858172681','riya.kumar770@gmail.com','334, Station Road, Delhi - 227519','2026-02-07','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(31,'ADM20260130','Amit','Mehta','Ravi Mehta','Aisha Mehta','2019-02-04','Male',4,4,'35','9817825323','amit.mehta680@gmail.com','27, Nehru Street, Mumbai - 833314','2025-05-17','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(32,'ADM20260131','Siya','Desai','Harsh Desai','Myra Desai','2020-11-08','Female',2,17,'40','9831038923','siya.desai3@gmail.com','191, Ashok Nagar, Ahmedabad - 723217','2026-02-13','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(33,'ADM20260132','Advait','Shah','Dev Shah','Neha Shah','2012-04-25','Male',10,10,'10','9875166688','advait.shah558@gmail.com','924, MG Road, Mumbai - 629814','2025-04-06','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(34,'ADM20260133','Ananya','Kumar','Ishaan Kumar','Aisha Kumar','2009-10-19','Female',13,28,'34','9810917376','ananya.kumar426@gmail.com','467, Gandhi Nagar, Mumbai - 281622','2025-11-06','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(35,'ADM20260134','Aryan','Kumar','Amit Kumar','Riya Kumar','2018-01-11','Male',5,5,'02','9873891821','aryan.kumar962@gmail.com','391, Station Road, Delhi - 625824','2024-11-09','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(36,'ADM20260135','Ravi','Saxena','Rohan Saxena','Pooja Saxena','2017-04-09','Male',5,20,'13','9893549799','ravi.saxena525@gmail.com','686, MG Road, Bangalore - 998176','2024-08-17','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(37,'ADM20260136','Amit','Pandey','Amit Pandey','Tanvi Pandey','2014-10-02','Male',8,23,'45','9857669218','amit.pandey1@gmail.com','353, Nehru Street, Jaipur - 416103','2025-06-23','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(38,'ADM20260137','Vijay','Kumar','Karan Kumar','Saanvi Kumar','2018-05-01','Male',4,19,'48','9841285126','vijay.kumar105@gmail.com','57, Brigade Road, Delhi - 626159','2025-12-03','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(39,'ADM20260138','Dhruv','Mehta','Rohan Mehta','Anika Mehta','2016-07-26','Male',6,6,'33','9816794464','dhruv.mehta199@gmail.com','813, Residency Road, Mumbai - 723804','2024-10-29','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(40,'ADM20260139','Pari','Iyer','Aryan Iyer','Riya Iyer','2012-09-28','Female',10,32,'04','9895530342','pari.iyer502@gmail.com','765, Brigade Road, Mumbai - 637688','2024-11-06','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(41,'ADM20260140','Ananya','Tiwari','Amit Tiwari','Kavya Tiwari','2016-06-11','Female',6,21,'31','9829607021','ananya.tiwari739@gmail.com','392, Nehru Street, Bangalore - 604515','2025-01-26','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(42,'ADM20260141','Aryan','Kumar','Ishaan Kumar','Diya Kumar','2015-01-17','Male',8,8,'47','9829911528','aryan.kumar581@gmail.com','628, Nehru Street, Lucknow - 518713','2024-04-29','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(43,'ADM20260142','Kabir','Singh','Raj Singh','Tara Singh','2017-10-05','Male',5,20,'41','9864899112','kabir.singh52@gmail.com','622, Gandhi Nagar, Ahmedabad - 492808','2024-05-15','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(44,'ADM20260143','Amit','Mishra','Raj Mishra','Neha Mishra','2017-04-06','Male',5,20,'03','9845868580','amit.mishra70@gmail.com','771, Ashok Nagar, Pune - 272118','2025-09-28','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(45,'ADM20260144','Aisha','Agarwal','Nitin Agarwal','Siya Agarwal','2020-06-06','Female',2,17,'10','9890617979','aisha.agarwal287@gmail.com','363, Park Street, Delhi - 887132','2024-07-16','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(46,'ADM20260145','Diya','Joshi','Vihaan Joshi','Diya Joshi','2013-08-03','Female',9,9,'04','9815401118','diya.joshi254@gmail.com','741, MG Road, Bangalore - 366291','2025-12-29','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(47,'ADM20260146','Meera','Desai','Karan Desai','Zara Desai','2016-12-09','Female',6,6,'06','9845965160','meera.desai509@gmail.com','347, Sardar Patel Marg, Kolkata - 435582','2024-12-17','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(48,'ADM20260147','Sara','Roy','Aryan Roy','Siya Roy','2011-06-25','Female',11,26,'31','9831108362','sara.roy809@gmail.com','664, Nehru Street, Jaipur - 931691','2024-06-13','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(49,'ADM20260148','Ravi','Kumar','Aryan Kumar','Kavya Kumar','2017-05-10','Male',5,5,'07','9859216354','ravi.kumar560@gmail.com','361, Nehru Street, Bangalore - 695057','2025-06-16','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(50,'ADM20260149','Kavya','Iyer','Vivek Iyer','Aadhya Iyer','2018-03-19','Female',4,4,'46','9824659672','kavya.iyer658@gmail.com','759, Sardar Patel Marg, Lucknow - 188454','2025-05-10','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12'),(51,'ADM20260150','Siya','Reddy','Shaurya Reddy','Pari Reddy','2008-03-07','Female',14,14,'10','9872544310','siya.reddy358@gmail.com','199, Brigade Road, Kolkata - 754928','2025-04-25','2026-2027','active','2026-02-13 16:57:12','2026-02-13 16:57:12');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
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
-- Final view structure for view `vw_student_fee_summary`
--

/*!50001 DROP VIEW IF EXISTS `vw_student_fee_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
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
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
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

-- Dump completed on 2026-02-13 22:31:49
