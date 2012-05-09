-- MySQL dump 10.13  Distrib 5.1.58, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: stfquiz
-- ------------------------------------------------------
-- Server version	5.1.58-1ubuntu1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `answers`
--

DROP TABLE IF EXISTS `answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `answers` (
  `quizid` int(11) NOT NULL,
  `questionid` int(11) NOT NULL,
  `answernumber` int(11) NOT NULL,
  `answertext` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`quizid`,`questionid`,`answernumber`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questions` (
  `quizid` int(11) NOT NULL,
  `questionnumber` int(11) NOT NULL,
  `questionheading` varchar(200) DEFAULT '',
  `questioningress` varchar(1000) DEFAULT '',
  `questiontext` varchar(1000) NOT NULL DEFAULT '',
  `correctanswer` int(11) NOT NULL DEFAULT '0',
  `idquestion` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`idquestion`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quiz`
--

DROP TABLE IF EXISTS `quiz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz` (
  `idquiz` int(11) NOT NULL AUTO_INCREMENT,
  `quizname` varchar(45) NOT NULL,
  `quizheader` varchar(200) DEFAULT '',
  `quizingress` varchar(1000) DEFAULT '',
  `quizfooter` varchar(200) DEFAULT '',
  `expiredate` datetime DEFAULT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `keyword` varchar(20) NOT NULL,
  PRIMARY KEY (`idquiz`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `teamanswers`
--

DROP TABLE IF EXISTS `teamanswers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teamanswers` (
  `idanswers` int(11) NOT NULL AUTO_INCREMENT,
  `phonenumber` varchar(20) NOT NULL,
  `questionid` int(11) NOT NULL,
  `answer` int(11) NOT NULL,
  PRIMARY KEY (`idanswers`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `teammember`
--

DROP TABLE IF EXISTS `teammember`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teammember` (
  `phonenumber` varchar(20) NOT NULL,
  `quizid` int(11) NOT NULL,
  `teamid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`quizid`,`phonenumber`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teams` (
  `idteam` int(11) NOT NULL AUTO_INCREMENT,
  `teamname` varchar(80) NOT NULL,
  PRIMARY KEY (`idteam`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-03-01 13:43:49
