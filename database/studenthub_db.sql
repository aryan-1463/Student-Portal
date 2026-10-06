-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: studenthub_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) NOT NULL,
  `event_date` date NOT NULL,
  `location` varchar(100) NOT NULL,
  `max_capacity` int(11) NOT NULL DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`event_id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'Web Development BootCamp 2026','Intensive hands-on workshop on modern full-stack web development with PHP, MySQL & responsive UI frameworks.','Technical','2026-10-15','Lab 301, IT Building',60,'2026-10-06 19:13:30'),(2,'AI & Machine Learning Symposium','Exploring practical deep learning models, neural networks and computer vision applications in industry.','Academic','2026-10-22','Auditorium A',120,'2026-10-06 19:13:30'),(3,'StudentHub Annual Hackathon','48-hour continuous coding sprint to build innovative campus solutions and developer productivity utilities.','Competition','2026-11-05','Central Computing Centre',80,'2026-10-06 19:13:30'),(4,'Cloud Architecture & DevOps Masterclass','Architecting scalable cloud deployments using Docker containers, CI/CD pipelines and microservices.','Workshop','2026-11-12','Seminar Hall 2',50,'2026-10-06 19:13:30');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registrations` (
  `reg_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `status` enum('Confirmed','Pending','Cancelled') NOT NULL DEFAULT 'Confirmed',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reg_id`),
  UNIQUE KEY `uq_student_event` (`student_id`,`event_id`),
  KEY `fk_reg_event` (`event_id`),
  CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
INSERT INTO `registrations` VALUES (1,1,1,'Confirmed','2026-10-06 19:13:30'),(2,1,3,'Confirmed','2026-10-06 19:13:30'),(3,2,1,'Confirmed','2026-10-06 19:13:30'),(4,2,2,'Pending','2026-10-06 19:13:30'),(5,3,2,'Confirmed','2026-10-06 19:13:30'),(6,3,4,'Confirmed','2026-10-06 19:13:30'),(7,4,3,'Confirmed','2026-10-06 19:13:30');
/*!40000 ALTER TABLE `registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_no` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `course` varchar(50) NOT NULL,
  `year` int(11) NOT NULL CHECK (`year` between 1 and 4),
  `gender` enum('Male','Female','Other') NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `enrollment_no` (`enrollment_no`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_enrollment` (`enrollment_no`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'D26DCE156','Aryan Joshi','d26dce156@charusat.edu.in','9876543210','B.Tech IT',3,'Male','$2y$10$uzwCwQu2B21ITxAsED0PoeHTAykA/9..y33izjG18XzSvf8V5WUyu','2026-10-06 19:13:30'),(2,'D26DCE152','Aarav Patel','aarav.patel@charusat.edu.in','9825100001','B.Tech CSE',3,'Male','$2y$10$A0dFCsixvFWrj9hMEI5sgOvRhUxRPXHvROsHx5WQzzoS065Mz1QUS','2026-10-06 19:13:30'),(3,'D26DCE154','Diya Sharma','diya.sharma@charusat.edu.in','9825100002','B.Tech IT',3,'Female','$2y$10$H9n54N.Tuvu2NnGCsX5NeeIUxDhB1XugNgm8feO4rTGaN3aCNC8vu','2026-10-06 19:13:30'),(4,'D26DCE155','Rohan Mehta','rohan.mehta@charusat.edu.in','9825100003','B.Tech CE',2,'Male','$2y$10$uzwCwQu2B21ITxAsED0PoeHTAykA/9..y33izjG18XzSvf8V5WUyu','2026-10-06 19:13:30'),(5,'D26Dce122','Test Student','teststudent@charusat.edu.in','9384832349','B.tech IT',2,'Male','Test@123','2026-10-06 19:32:10');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'studenthub_db'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_GetStudentRegistrations` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetStudentRegistrations`(IN p_student_id INT)
BEGIN
    SELECT 
        s.student_id,
        s.enrollment_no,
        s.full_name,
        e.event_id,
        e.title AS event_title,
        e.category AS event_category,
        e.event_date,
        e.location,
        r.status AS registration_status,
        r.registered_at
    FROM registrations r
    JOIN students s ON r.student_id = s.student_id
    JOIN events e ON r.event_id = e.event_id
    WHERE s.student_id = p_student_id
    ORDER BY e.event_date ASC;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_RegisterStudent` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RegisterStudent`(
    IN p_student_id INT,
    IN p_event_id INT,
    OUT p_result VARCHAR(100)
)
BEGIN
    DECLARE v_exists INT DEFAULT 0;
    
    
    SELECT COUNT(*) INTO v_exists 
    FROM registrations 
    WHERE student_id = p_student_id AND event_id = p_event_id;
    
    IF v_exists > 0 THEN
        SET p_result = 'ALREADY_REGISTERED';
    ELSE
        INSERT INTO registrations (student_id, event_id, status)
        VALUES (p_student_id, p_event_id, 'Confirmed');
        SET p_result = 'SUCCESS';
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-07  1:05:34
