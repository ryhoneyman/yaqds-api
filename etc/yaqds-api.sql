-- MySQL dump 10.13  Distrib 8.0.36, for Linux (x86_64)
--
-- Host: localhost    Database: yaqds-api
-- ------------------------------------------------------
-- Server version	8.0.36-0ubuntu0.22.04.1

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
-- Table structure for table `api_key`
--

DROP TABLE IF EXISTS `api_key`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_key` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` varchar(64) DEFAULT NULL,
  `client_secret` varchar(64) NOT NULL,
  `requestor` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `roles` text,
  `limit_rate` smallint unsigned DEFAULT '1',
  `limit_concurrent` smallint unsigned DEFAULT '1',
  `last_used` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `active` tinyint unsigned DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`)
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_key`
--

LOCK TABLES `api_key` WRITE;
/*!40000 ALTER TABLE `api_key` DISABLE KEYS */;
INSERT INTO `api_key` VALUES (1,'{{YAQDS-CLIENTID}}','{{YAQDS-CLIENTSECRET}}','api@localhost',NULL,'[\"super-admin\"]',1000,1000,NULL,'2024-03-07 21:34:57','2024-03-07 21:34:57',1);
/*!40000 ALTER TABLE `api_key` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_log`
--

DROP TABLE IF EXISTS `api_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_log` (
  `accessed` datetime NOT NULL,
  `api_token` varchar(64) NOT NULL,
  `api_key_id` int unsigned NOT NULL,
  `request` varchar(255) NOT NULL,
  `method` varchar(15) DEFAULT NULL,
  `status_code` smallint unsigned DEFAULT NULL,
  `status_mesg` varchar(50) DEFAULT NULL,
  `elapsed_sec` decimal(10,5) DEFAULT NULL,
  `bytes` int unsigned DEFAULT NULL
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_log`
--

LOCK TABLES `api_log` WRITE;
/*!40000 ALTER TABLE `api_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_role`
--

DROP TABLE IF EXISTS `api_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_role` (
  `name` varchar(50) NOT NULL,
  `access` text,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_role`
--

LOCK TABLES `api_role` WRITE;
/*!40000 ALTER TABLE `api_role` DISABLE KEYS */;
INSERT INTO `api_role` VALUES ('example-admin','{\"roles\":[\"ro-example\",\"rw-example\"]}'),('ro-admin','{\"controller\":\"any\",\"function\":\"any\",\"method\":\"GET\"}'),('ro-example','{\"privilege\":[{\"controller\":\"ExampleController\",\"function\":\"any\",\"method\":\"GET\"}]}'),('ro-spell','{\"privilege\":[{\"controller\":\"SpellController\",\"function\":\"any\",\"method\":\"GET\"}]}'),('rw-example','{\"privilege\":[{\"controller\":\"ExampleController\",\"function\":\"any\",\"method\":\"GET\"},{\"controller\":\"ExampleController\",\"function\":\"any\",\"method\":\"POST\"}]}'),('super-admin','{\"privilege\":[{\"controller\":\"any\",\"function\":\"any\",\"method\":\"any\"}]}');
/*!40000 ALTER TABLE `api_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_token`
--

DROP TABLE IF EXISTS `api_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_token` (
  `api_key_id` int unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `role_access` text,
  `limit_rate` smallint unsigned DEFAULT NULL,
  `count_rate` smallint unsigned DEFAULT NULL,
  `expire_rate` datetime DEFAULT NULL,
  `limit_concurrent` smallint unsigned DEFAULT NULL,
  `count_concurrent` smallint unsigned DEFAULT NULL,
  `expire_concurrent` datetime DEFAULT NULL,
  `last_used` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  PRIMARY KEY (`api_key_id`)
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_token`
--

LOCK TABLES `api_token` WRITE;
/*!40000 ALTER TABLE `api_token` DISABLE KEYS */;
INSERT INTO `api_token` VALUES (1,'{{YAQDS-TOKEN}}','[\"any:any:any\"]',1000,0,'2024-02-27 15:30:31',1000,0,'2024-02-27 15:30:31','2024-05-07 21:05:33','2024-05-07 21:05:33','9999-12-31 00:00:00'),(2,'10091b2ee9431bd719954873947bad803973fcea2b77c82d43918ca015a836d4','[\"any:any:any\"]',1,0,'2024-03-07 21:39:39',1,0,'2024-03-07 21:39:39','2024-05-08 17:35:59','2024-05-08 17:35:59','2024-05-08 18:35:59');
/*!40000 ALTER TABLE `api_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `defines`
--

DROP TABLE IF EXISTS `defines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `defines` (
  `name` varchar(100) NOT NULL,
  `value` text,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `defines`
--

LOCK TABLES `defines` WRITE;
/*!40000 ALTER TABLE `defines` DISABLE KEYS */;
INSERT INTO `defines` VALUES ('YAQDS_API_AUTH_TOKEN','{{YAQDS-TOKEN}}'),('YAQDS_API_CLIENT_ID','{{YAQDS-CLIENTID}}'),('YAQDS_API_CLIENT_SECRET','{{YAQDS-CLIENTSCRET}}'),('YAQDS_API_URL','https://yaqds.cc');
/*!40000 ALTER TABLE `defines` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-06-23 15:57:34
