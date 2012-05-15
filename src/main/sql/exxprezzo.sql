# ************************************************************
# Sequel Pro SQL dump
# Version 3408
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: localhost (MySQL 5.5.23)
# Database: exxprezzo
# Generation Time: 2012-05-15 23:13:49 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table config
# ------------------------------------------------------------

DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `key` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;

INSERT INTO `config` (`key`, `value`)
VALUES
	('urlManager','QueryString');

/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table hostGroup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hostGroup`;

CREATE TABLE `hostGroup` (
  `hostGroupId` int(11) unsigned NOT NULL,
  `hostName` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `type` enum('primary','slave','redirect') CHARACTER SET ascii NOT NULL DEFAULT 'slave',
  PRIMARY KEY (`hostGroupId`,`hostName`),
  UNIQUE KEY `hostName` (`hostName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table layout
# ------------------------------------------------------------

DROP TABLE IF EXISTS `layout`;

CREATE TABLE `layout` (
  `layoutId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `defaultBox` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  PRIMARY KEY (`layoutId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table moduleInstance
# ------------------------------------------------------------

DROP TABLE IF EXISTS `moduleInstance`;

CREATE TABLE `moduleInstance` (
  `moduleInstanceId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(255) NOT NULL DEFAULT '',
  `root` varchar(255) NOT NULL DEFAULT '',
  `hostGroup` int(11) unsigned NOT NULL,
  `param` text,
  PRIMARY KEY (`moduleInstanceId`),
  UNIQUE KEY `mountpoint` (`root`,`hostGroup`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table page
# ------------------------------------------------------------

DROP TABLE IF EXISTS `page`;

CREATE TABLE `page` (
  `pageId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `moduleInstanceId` int(11) DEFAULT NULL,
  `function` varchar(255) CHARACTER SET ascii DEFAULT '',
  `layoutId` int(11) unsigned NOT NULL,
  PRIMARY KEY (`pageId`),
  UNIQUE KEY `moduleInstanceId` (`moduleInstanceId`,`pageId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table widget
# ------------------------------------------------------------

DROP TABLE IF EXISTS `widget`;

CREATE TABLE `widget` (
  `widgetId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pageId` int(11) unsigned DEFAULT NULL,
  `moduleInstanceId` int(11) unsigned NOT NULL,
  `function` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `box` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `priority` int(11) unsigned NOT NULL,
  `param` text,
  PRIMARY KEY (`widgetId`),
  KEY `pageId` (`pageId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
